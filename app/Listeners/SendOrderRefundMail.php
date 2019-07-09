<?php

namespace App\Listeners;

use App\Events\OrderRefund;
use App\Notifications\OrderRefundNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendOrderRefundMail implements ShouldQueue
{

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle(OrderRefund $event)
    {
        \Log::info('这里记录 订单退款!!!'.PHP_EOL);
        $order = $event->getOrder();

        $order->user->notify(new OrderRefundNotification($order));
    }
}
