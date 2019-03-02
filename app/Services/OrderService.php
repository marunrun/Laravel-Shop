<?php
/**
 * Created by PhpStorm.
 * User: run
 * Date: 2019/3/2
 * Time: 13:55
 */

namespace App\Services;


use App\Http\Requests\Request;
use App\Jobs\CloseOrder;
use App\Models\Order;
use App\Models\ProductSku;
use App\Models\User;
use App\Models\UserAddress;
use Carbon\Carbon;

class OrderService
{

    public function store(User $user, UserAddress $address, $remark, $items)
    {
        $order = \DB::transaction(function () use ($user, $address, $remark, $items) {

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
                'remark' => $remark,
                'total_amount' => 0
            ]);

            // 订单关联到用户

            $order->user()->associate($user);

            // 写入数据库
            $order->save();

            $totalAmount = 0;

            // 遍历提交的SKU
            foreach ($items as $data) {
                $sku = ProductSku::find($data['sku_id']);
                // 创建一个orderItem 与当前order关联

                $item = $order->items()->make([
                    'amount' => $data['amount'],
                    'price' => $sku->price
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
            $skuIds = collect($items)->pluck('sku_id')->all();
            app(CartService::class)->remove($skuIds);

            return $order;
        });


        // 延迟任务 30 分钟后关闭未付款订单
        dispatch(new CloseOrder($order, config('app.order_ttl')));

        return $order;
    }
}