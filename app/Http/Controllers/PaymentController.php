<?php

namespace App\Http\Controllers;

use App\Exceptions\InvalidRequestException;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    /**
     *  使用支付宝网页支付
     * @param Order $order
     * @param Request $request
     * @return mixed
     * @throws InvalidRequestException
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function payByAlipay(Order $order, Request $request)
    {

        // 判断当前的订单是否属于当前用户
        $this->authorize('own',$order);
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
     *  网页同步回调
     */
    public function alipayReturn()
    {
        try {
            app('alipay')->verify();
        }catch (\Exception $e) {
            return view('pages.error',['msg' => '数据不正确']);
        }

        return view('pages.success',['msg' => '付款成功']);
    }

    /**
     *  服务器端的异步回调
     */
    public function alipayNotify()
    {

        $data = app('alipay')->verify();
        // 如果订单状态不是成功或者结束，则不走后续的逻辑
        // 所有交易状态：https://docs.open.alipay.com/59/103672
        if (!in_array($data->trade_status,['TRADE_SUCCESS', 'TRADE_FINISHED'])) {
            return app('alipay')->success();
        }

        $order = Order::where('no',$data->out_trade_no)->first();
        if (!$order) {
            return 'fail';
        }

        // 如果这笔订单的状态已经是已支付
        if ($order->paid_at) {
            // 返回数据给支付宝
            return app('alipay')->success();
        }

        $order->update([
            'paid_at'   => Carbon::now(),
            'payment_method'    => 'alipay',
            'payment_no'    => $data->trade_no
        ]);


//        \Log::debug('Alipay notify',$data->all());

        return app('alipay')->success();
    }
    
    
}
