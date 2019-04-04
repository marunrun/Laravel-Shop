<?php

namespace App\Http\Controllers;

use App\Exceptions\InvalidRequestException;
use App\Http\Requests\OrderRequest;
use App\Models\Order;
use App\Models\UserAddress;
use App\Services\OrderService;
use Illuminate\Http\Request;

class OrdersController extends Controller
{


    /** 提交订单的逻辑代码
     * @param OrderRequest $request
     * @return mixed
     */
    public function store(OrderRequest $request, OrderService $orderService)
    {
        $user = $request->user();
        $address = UserAddress::find($request->input('address_id'));

        return $orderService->store($user, $address, $request->input('remark'), $request->input('items'));
    }

    /** 订单列表
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index(Request $request)
    {
        // 使用预加载避免n+1
        $orders = Order::query()
            ->with(['items.product','items.productSku'])
            ->where('user_id',$request->user()->id)
            ->orderBy('created_at','desc')
            ->paginate();

        return view('orders.index', compact('orders'));
    }


    /**
     *  展示订单详情
     * @param Order $order
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function show(Order $order, Request $request)
    {
        $this->authorize('own',$order);

        return view('orders.show',['order' => $order->load(['items.productSku', 'items.product'])]);
    }


    /** 确认收货
     * @param Order $order
     * @param Request $request
     */
    public function received(Order $order, Request $request)
    {
        $this->authorize('own',$order);

        // 判断当前订单是否已发货
        if ($order->ship_status !== Order::SHIP_STATUS_DELIVERED){
            throw new InvalidRequestException('发货状态不正确');
        }

        // 更新发货状态为已收货
        $order->update(['ship_status' => Order::SHIP_STATUS_RECEIVED]);
        // 返回
        return $order;
    }
}
