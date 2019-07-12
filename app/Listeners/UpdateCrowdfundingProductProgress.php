<?php

namespace App\Listeners;

use App\Events\OrderPaid;
use App\Models\Order;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Query\Builder;
use Log;

class UpdateCrowdfundingProductProgress implements ShouldQueue
{

    /**
     * Handle the event.
     *
     * @param  OrderPaid  $event
     * @return void
     */
    public function handle(OrderPaid $event)
    {

        $order = $event->getOrder();
        Log::info('更新众筹进度:'.$order->id);
        // 如果订单类型不是众筹商品订单，无需处理
        if ($order->type !== Order::TYPE_CROWDFUNDING) {
            return;
        }


        $crowdfunding = $order->items[0]->product->crowdfunding;

        $data = Order::query()
            ->where('type',Order::TYPE_CROWDFUNDING)
            ->whereNotNull('paid_at')
            ->whereHas('items',function (Builder $query) use ($crowdfunding) {
                    $query->where('product_id',$crowdfunding->product->id);
            })
            ->first([
                \DB::raw('sum(total_amount) as total_amount'),
                \DB::raw('count(distinct(user_id)) as user_count')
            ]);

        Log::info('当前众筹进度:'.json_encode($data,JSON_PRETTY_PRINT));

        $crowdfunding->update([
            'total_amount' => $data->total_amount,
            'user_count'   => $data->user_count,
        ]);
    }
}
