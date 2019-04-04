<?php

namespace App\Admin\Controllers;

use App\Exceptions\InvalidRequestException;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;

class OrderController extends Controller
{
    use HasResourceActions;

    /*** 列表*/
    public function index(Content $content)
    {
        return $content
            ->header('订单')
            ->description('列表')
            ->body($this->grid());
    }

    /**
     * 展示订单详情
     * @param mixed $order 订单的id
     * @param Content $content
     * @return Content
     */
    public function show(Order $order, Content $content)
    {
        return $content
            ->header('订单')
            ->description('详情')
            ->body(view('admin.orders.show',['order'=>$order]));
    }


    protected function grid()
    {
        $grid = new Grid(new Order);

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

    public function ship(Order $order, Request $request)
    {
        // 判断当前订单是否支付
        if (!$order->paid_at){
            throw new InvalidRequestException('该订单未支付');
        }

        // 判断当前订单发货状态是否为未发货
        if ($order->ship_status != Order::SHIP_STATUS_PENDING){
            throw new InvalidRequestException('该订单已发货');
        }


        // Laravel 5.5 之后 validate 返回校验过的值
        $data = $this->validate($request,[
            'express_company' => ['required'],
            'express_no'    =>['required'],
        ], [],[
            'express_company' => '物流公司',
            'express_no'    => '物流单号'
        ]);
        // 更改发货状态为已发货
        $order->update([
            'ship_status' => Order::SHIP_STATUS_DELIVERED,
            'ship_data' => $data
        ]);

        // 返回上一页
        return redirect()->back();
    }
}
