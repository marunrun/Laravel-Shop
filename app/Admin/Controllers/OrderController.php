<?php

namespace App\Admin\Controllers;

use App\Events\OrderRefund;
use App\Exceptions\InvalidRequestException;
use App\Http\Requests\Admin\HandleRefundRequest;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;

class OrderController extends Controller
{
    use HasResourceActions;

    /**
     * 列表.
     *
     * @param Content $content
     *
     * @return Content
     */
    public function index(Content $content)
    {
        return $content
            ->header('订单')
            ->description('列表')
            ->body($this->grid());
    }

    /**
     * 展示订单详情.
     *
     * @param mixed $order 订单的id
     * @param Content $content
     *
     * @return Content
     */
    public function show(Order $order, Content $content)
    {
        return $content
            ->header('订单')
            ->description('详情')
            ->body(view('admin.orders.show', ['order' => $order]));
    }

    protected function grid()
    {
        $grid = new Grid(new Order());

        $grid->model()->whereNotNull('paid_at')->orderBy('paid_at', 'desc');

        $grid->no('流水号');
        // 展示关联关系的字段时 使用column
        $grid->column('user.name', '买家');
        $grid->total_amount('总金额');
        $grid->paid_at('支付时间')->sortable();
        $grid->ship_status('物流')->display(function ($v) {
            return Order::$shipStatusMap[$v];
        });
        $grid->refund_status('退款状态')->display(function ($v) {
            return Order::$refundStatusMap[$v];
        });

        $grid->disableCreateButton();
        $grid->actions(function ($actions) {
            $actions->disableDelete();
            $actions->disableEdit();
        });
        $grid->tools(function ($tools) {
            $tools->batch(function ($batch) {
                $batch->disableDelete();
            });
        });

        return $grid;
    }

    /**
     * @param Order $order
     * @param Request $request
     *
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws InvalidRequestException
     * @throws \Illuminate\Validation\ValidationException
     */
    public function ship(Order $order, Request $request)
    {
        // 判断当前订单是否支付
        if (!$order->paid_at) {
            throw new InvalidRequestException('该订单未支付');
        }

        // 判断当前订单发货状态是否为未发货
        if (Order::SHIP_STATUS_PENDING != $order->ship_status) {
            throw new InvalidRequestException('该订单已发货');
        }

        // Laravel 5.5 之后 validate 返回校验过的值
        $data = $this->validate($request, [
            'express_company' => ['required'],
            'express_no'      => ['required'],
        ], [], [
            'express_company' => '物流公司',
            'express_no'      => '物流单号',
        ]);
        // 更改发货状态为已发货
        $order->update([
            'ship_status' => Order::SHIP_STATUS_DELIVERED,
            'ship_data'   => $data,
        ]);

        // 返回上一页
        return redirect()->back();
    }

    /**
     * 是否同意退款.
     *
     * @param Order $order
     * @param HandleRefundRequest $request
     *
     * @return Order
     *
     * @throws InvalidRequestException
     * @throws \Yansongda\Pay\Exceptions\GatewayException
     * @throws \Yansongda\Pay\Exceptions\InvalidConfigException
     * @throws \Yansongda\Pay\Exceptions\InvalidSignException
     */
    public function handleRefund(Order $order, HandleRefundRequest $request)
    {
        // 判断订单状态是否正确
        if (Order::REFUND_STATUS_APPLIED !== $order->refund_status) {
            throw new InvalidRequestException('订单状态不正确');
        }

        // 是否同意退款
        if ($request->input('agree')) {
            // 清空 拒绝退款理由
            $extra = $order->extra ?: [];
            unset($extra['refund_disagree_reason']);

            $order->update(compact('extra'));
            $this->_refundOrder($order);
        } else {
            // 拒绝退款 ， 将拒绝退款的理由放在extra中
            $extra = $order->extra ?: [];
            $extra['refund_disagree_reason'] = $request->input('reason');
            // 将订单的状态改成未退款
            $order->update([
                'refund_status' => Order::REFUND_STATUS_PENDING,
                'extra'         => $extra,
            ]);
        }

        return $order;
    }

    /**
     * 同意退款
     * @param Order $order
     * @throws InvalidRequestException
     * @throws \Yansongda\Pay\Exceptions\GatewayException
     * @throws \Yansongda\Pay\Exceptions\InvalidConfigException
     * @throws \Yansongda\Pay\Exceptions\InvalidSignException
     * @throws \Exception
     */
    protected function _refundOrder(Order $order)
    {
        switch ($order->payment_method) {
            case 'wechat':
                // TODO 微信退款
                break;
            case 'alipay':
                $refundNo = Order::getAvailableRefundNo();
                $res = app('alipay')->refund([
                    'out_trade_no'   => $order->no, // 之前的订单流水号
                    'refund_amount'  => $order->total_amount, // 退款金额，单位元
                    'out_request_no' => $refundNo, // 退款订单号
                ]);

                // 如果返回值里有sub_code 字段 说明退款失败
                if ($res->sub_code) {
                    // 将退款失败字段保存到extra中
                    $extra = $order->extra ?: [];
                    $extra['refund_failed_code'] = $res->sub_code;
                    $order->update([
                        'refund_no'     => $refundNo,
                        'refund_status' => Order::REFUND_STATUS_FAILED,
                        'extra'         => $extra,
                    ]);
                } else {
                    $order->update([
                        'refund_no'     => $refundNo,
                        'refund_status' => Order::REFUND_STATUS_SUCCESS
                    ]);
//                    event(new OrderRefund($order));
                    \Log::info('订单:'.$order->id.' 退款成功!');
                }
                break;
            default:
                throw new InvalidRequestException('未知订单付款方式:' . $order->payment_method);
                break;
        }
    }
}
