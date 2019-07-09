<?php

namespace App\Listeners;

use App\Events\OrderRefund;
use App\Notifications\OrderRefundNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendOrderRefundMail implements ShouldQueue
{

    public $tries = 3;


    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle(OrderRefund $event)
    {
        $order = $event->getOrder();

        $order->user->notify(new OrderRefundNotification($order));
    }
}
