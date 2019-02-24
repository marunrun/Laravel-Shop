<?php

namespace App\Http\Controllers;

use App\Exceptions\InvalidRequestException;
use App\Http\Requests\OrderRequest;
use App\Jobs\CloseOrder;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductSku;
use App\Models\UserAddress;
use Carbon\Carbon;
use Illuminate\Http\Request;

class OrdersController extends Controller
{


    /** 提交订单的逻辑代码
     * @param OrderRequest $request
     * @return mixed
     */
    public function store(OrderRequest $request)
    {
        $user = $request->user();

        // 开启事务
        $order = \DB::transaction(function () use ($user, $request) {
            $address = UserAddress::find($request->input('address_id'));

            // 更新地址的最新使用时间
            $address->update(['last_used_at' => Carbon::now()]);
            // 创建订单

            $order = new Order([
                'address' => [
                    'address' => $address->full_address,
                    'zip' => $address->zip,
                    'contact_name' => $address->contact_name,
                    'contact_phone' => $address->contact_phone,
                ],
                'remark' => $request->input('remark'),
                'total_amount' => 0
            ]);

            // 订单关联到用户

            $order->user()->associate($user);

            // 写入数据库
            $order->save();

            $totalAmount = 0;

            $items = $request->input('items');

            // 遍历提交的SKU
            foreach ($items as $data) {
                $sku = ProductSku::find($data['sku_id']);
                // 创建一个orderItem 与当前order关联

                $item = $order->items()->make([
                    'amount' => $data['amount'],
                    'price' => $sku->price * $data['amount']
                ]);
                $item->product()->associate($sku->product_id);
                $item->productSku()->associate($sku);
                $item->save();
                $totalAmount += $sku->price * $data['amount'];

                if ($sku->decreaseStock($data['amount']) <= 0) {
                    throw new InvalidRequestException('该商品库存不足');
                }
            }

            // 更新订单金额
            $order->update(['total_amount' => $totalAmount]);

            // 将下单的商品从购物车中移除
            $skuIds = collect($items)->pluck('sku_id');
            $user->cartItems()->whereIn('product_sku_id', $skuIds)->delete();

            // 延迟任务 30 分钟后关闭未付款订单
            $this->dispatch(new CloseOrder($order,config('shop.order_ttl')));

            return $order;
        });

        return $order;
    }

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
}
