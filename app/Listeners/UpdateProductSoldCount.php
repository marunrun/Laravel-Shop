<?php

namespace App\Listeners;

use App\Events\OrderPaid;
use App\Models\OrderItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\Queue\ShouldQueue;


//  implements ShouldQueue 代表此监听器是异步执行的

class UpdateProductSoldCount implements ShouldQueue
{

  // Laravel 会默认执行监听器的 handle 方法，触发的事件会作为 handle 方法的参数
    public function handle(OrderPaid $event)
    {
        // 取出对应的订单对象
        $order = $event->getOrder();
        // 预加载商品数据
        $order->load('items.product');
        \Log::info(__FILE__.__LINE__.'商品:'.$order->id);
        // 循环遍历订单商品
        foreach ($order->items as $item) {
            $product = $item->product;
            // 计算对应商品的销量
            $soldCount = OrderItem::query()
                ->where('product_id',$product->id)
                ->whereHas('order',function (Builder $query){
                    $query->whereNotNull('paid_at'); // 关联的订单状态是已支付
                })->sum('amount');
            // 更新商品销量
            $product->update([
                'sold_count' => $soldCount
            ]);
        }
    }
}
