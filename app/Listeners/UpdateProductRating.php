<?php

namespace App\Listeners;

use App\Events\OrderReviewed;
use App\Models\OrderItem;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;

class UpdateProductRating implements ShouldQueue
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  OrderReviewed  $event
     * @return void
     */
    public function handle(OrderReviewed $event)
    {
        $items = $event->getOrder()->items()->with(['product'])->get();

        foreach ($items as $item){
            $result = OrderItem::query()
                ->where('product_id',$item->product_id)
                ->whereHas('order',function (Builder $query){
                        $query->whereNotNull('paid_at');
                })
                ->first([
                    \DB::raw('count(*) as review_count'),
                    \DB::raw('avg (rating) as rating')
                ]);

            $item->product->update([
                'rating' => $result->rating,
                'review_count' => $result->review_count
            ]);
        }
    }
}
