<?php

namespace App\Http\Controllers;

use App\Events\OrderReviewed;
use App\Exceptions\InvalidRequestException;
use App\Http\Requests\ApplyRefundRequest;
use App\Http\Requests\OrderRequest;
use App\Http\Requests\SendReviewRequest;
use App\Models\Order;
use App\Models\UserAddress;
use App\Services\OrderService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class OrdersController extends Controller
{
    /**
     * 提交订单的逻辑代码
     *
     * @param OrderRequest $request
     * @param OrderService $orderService
     *
     * @return mixed
     */
    public function store(OrderRequest $request, OrderService $orderService)
    {
        $user    = $request->user();
        $address = UserAddress::find($request->input('address_id'));

        return $orderService->store($user, $address, $request->input('remark'), $request->input('items'));
    }

    /** 订单列表
     * @param Request $request
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index(Request $request)
    {
        // 使用预加载避免n+1
        $orders = Order::query()
            ->with(['items.product', 'items.productSku'])
            ->where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->paginate();

        return view('orders.index', compact('orders'));
    }

    /**
     *  展示订单详情.
     *
     * @param Order $order
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function show(Order $order)
    {
        $this->authorize('own', $order);

        return view('orders.show', ['order' => $order->load(['items.productSku', 'items.product'])]);
    }

    /**
     * 确认收货.
     *
     * @param Order $order
     *
     * @return Order
     *
     * @throws InvalidRequestException
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function received(Order $order)
    {
        $this->authorize('own', $order);

        // 判断当前订单是否已发货
        if (Order::SHIP_STATUS_DELIVERED !== $order->ship_status) {
            throw new InvalidRequestException('发货状态不正确');
        }

        // 更新发货状态为已收货
        $order->update(['ship_status' => Order::SHIP_STATUS_RECEIVED]);

        // 返回
        return $order;
    }

    /**
     * 评价商品页面.
     *
     * @param Order $order
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     *
     * @throws InvalidRequestException
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function review(Order $order)
    {
        // 检验权限
        $this->authorize('own', $order);
        // 判断是否已支付
        if (!$order->paid_at) {
            throw new InvalidRequestException('该订单未支付，不可评价');
        }

        return view('orders.review', ['order' => $order->load('items.productSku', 'items.product')]);
    }

    /**
     * 提交评价.
     *
     * @param Order $order
     * @param SendReviewRequest $request
     *
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws InvalidRequestException
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Throwable
     */
    public function sendReview(Order $order, SendReviewRequest $request)
    {
        // 检验权限
        $this->authorize('own', $order);

        if (!$order->paid_at) {
            throw new InvalidRequestException('此订单未支付，不可评价');
        }

        if ($order->reviewed) {
            throw new InvalidRequestException('此订单已评价，不可重复提交');
        }
        $reviews = $request->input('reviews');
        \Log::info(111);
        \DB::transaction(function () use ($reviews, $order) {
            foreach ($reviews as $review) {
                $orderItem = $order->items()->find($review['id']);
                // 保存评分和评价
                $orderItem->update([
                    'rating'      => $review['rating'],
                    'review'      => $review['review'],
                    'reviewed_at' => Carbon::now(),
                ]);
            }

            // 将该订单标记为已评价
            $order->update(['reviewed' => true]);
        });

        // 评价订单后 触发事件
        event(new OrderReviewed($order));

        return redirect()->back();
    }

    /**
     * 申请退款
     * @param Order $order
     * @param ApplyRefundRequest $request
     * @return Order
     * @throws InvalidRequestException
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function applyRefund(Order $order, ApplyRefundRequest $request)
    {
        $this->authorize('own', $order);
        // 判断订单是否付款

        if (!$order->paid_at) {
            throw new InvalidRequestException('订单未支付，不可退款');
        }

        if ($order->refund_status !== Order::REFUND_STATUS_PENDING) {
            throw new InvalidRequestException('该订单已经申请过退款，请勿重复申请');
        }

        // 将用户的申请退款理由放到订单的 extra 字段中
        $extra = $order->extra ?: [];

        $extra['refund_reason'] = $request->input('reason');

        // 将订单退款状态改成已申请退款
        $order->update([
            'refund_status' => Order::REFUND_STATUS_APPLIED,
            'extra'         => $extra,
        ]);

        return $order;
    }
}
