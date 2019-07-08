<?php

namespace App\Http\Controllers;

use App\Events\OrderPaid;
use App\Exceptions\InvalidRequestException;
use App\Models\Order;
use Carbon\Carbon;

class PaymentController extends Controller
{
    /**
     *  使用支付宝网页支付
     * @param Order $order
     * @return mixed
     * @throws InvalidRequestException
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function payByAlipay(Order $order)
    {

        // 判断当前的订单是否属于当前用户
        $this->authorize('own', $order);
        // 订单已支付或已关闭
        if ($order->paid_at || $order->closed) {
            throw new InvalidRequestException('订单状态不正确');
        }

        // 调用支付宝的网页支付
        return app('alipay')->web([
            'out_trade_no' => $order->no, // 订单编号，需保证在商户端不重复
            'total_amount' => $order->total_amount, // 订单金额，单位元，支持小数点后两位
            'subject'      => '支付 Laravel Shop 的订单：'.$order->no, // 订单标题
        ]);
    }

    /**
     *  支付宝网页同步回调
     */
    public function alipayReturn()
    {
        try {
            app('alipay')->verify();
        } catch (\Exception $e) {
            return view('pages.error', ['msg' => '数据不正确']);
        }

        return view('pages.success', ['msg' => '付款成功']);
    }

    /**
     * 支付宝服务器端的异步回调
     * @return string|\Symfony\Component\HttpFoundation\Response
     * @throws \Yansongda\Pay\Exceptions\InvalidConfigException
     * @throws \Yansongda\Pay\Exceptions\InvalidSignException
     */
    public function alipayNotify()
    {

        $data = app('alipay')->verify();

        // 如果订单状态不是成功或者结束，则不走后续的逻辑
        // 所有交易状态：https://docs.open.alipay.com/59/103672
        if (!in_array($data->trade_status, ['TRADE_SUCCESS', 'TRADE_FINISHED'])) {
            return app('alipay')->success();
        }

        /**
         * @var Order $order
         */
        $order = Order::where('no', $data->out_trade_no)->first();
        if (!$order) {
            return 'fail';
        }

        // 如果这笔订单的状态已经是已支付
        if ($order->paid_at) {
            // 返回数据给支付宝
            return app('alipay')->success();
        }

        $order->update([
            'paid_at'        => Carbon::now(),
            'payment_method' => 'alipay',
            'payment_no'     => $data->trade_no,
        ]);

        // 触发支付事件
        $this->afterPaid($order);

        return app('alipay')->success();
    }



    /**************************微信支付***************************************/


    /**
     *  微信扫码支付
     * @param Order $order
     * @return mixed
     * @throws InvalidRequestException
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function payByWechat(Order $order)
    {
        // 权限校验
        $this->authorize('own', $order);
        // 校验订单状态
        if ($order->paid_at || $order->closed) {
            throw new InvalidRequestException('订单状态不正确');
        }

        return app('wechat_pay')->scan([
            'out_trade_no' => $order->no, // 商户订单号
            'total_fee'    => $order->total_amount * 100, // 微信支付的金额单位是分，这里需要乘以100
            'body'         => '支付 Laravel Shop 的订单：'.$order->no, // 描述
        ]);
    }


    /**
     * @return string|\Symfony\Component\HttpFoundation\Response
     * @throws \Yansongda\Pay\Exceptions\InvalidArgumentException
     * @throws \Yansongda\Pay\Exceptions\InvalidSignException
     */
    public function wechatNotify()
    {
        // 校验回调参数是否正确
        $data = app('wechat_pay')->verify();

        // 找到对应的订单
        $order = Order::where('no', $data->out_trade_no)->first();
        // 订单不存在则告知微信支付
        if (!$order) {
            return 'fail';
        }

        // 订单已支付
        if ($order->paid_at) {
            // 告知微信支付此订单已处理
            return app('wechat_pay')->success();
        }

        // 将订单标记为已支付
        $order->update([
            'paid_at'        => Carbon::now(),
            'payment_method' => 'wechat',
            'payment_no'     => $data->transaction_id,
        ]);

        /**
         * @var Order $order
         */
        $this->afterPaid($order);

        return app('wechat_pay')->success();
    }

    /**
     *  注册支付后的事件
     * @param Order $order
     */
    protected function afterPaid(Order $order)
    {
        event(new OrderPaid($order));
    }
}
