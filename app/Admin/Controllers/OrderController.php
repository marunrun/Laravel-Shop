<?php

namespace App\Admin\Controllers;

use App\Exceptions\InvalidRequestException;
use App\Http\Requests\Admin\HandleRefundRequest;
use App\Models\CrowdfundingProduct;
use App\Services\OrderService;
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

        if (Order::TYPE_CROWDFUNDING === $order->type
            &&
            CrowdfundingProduct::STATUS_SUCCESS !== $order->items[0]->product->crowdfunding->status) {
            throw new InvalidRequestException('众筹订单只能在众筹成功之后发货');
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
     * @param OrderService $orderService
     * @return Order
     *
     * @throws InvalidRequestException
     */
    public function handleRefund(Order $order, HandleRefundRequest $request, OrderService $orderService)
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
            $orderService->refundOrder($order);
        } else {
            // 拒绝退款 ， 将拒绝退款的理由放在extra中
            $extra                           = $order->extra ?: [];
            $extra['refund_disagree_reason'] = $request->input('reason');
            // 将订单的状态改成未退款
            $order->update([
                'refund_status' => Order::REFUND_STATUS_PENDING,
                'extra'         => $extra,
            ]);
        }

        return $order;
    }
}
