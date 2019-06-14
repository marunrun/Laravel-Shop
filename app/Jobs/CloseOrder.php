<?php

namespace App\Jobs;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class CloseOrder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $order;

    public $tries = 5;

    /**
     * CloseOrder constructor.
     * @param Order $order
     * @param $delay
     */
    public function __construct(Order $order, $delay)
    {
        $this->order = $order;
        // 设置延迟时间 delay代表秒
        $this->delay($delay);
    }

    /**
     *
     * 定义这个任务类具体的执行逻辑
     * 当队列处理器从队列中取出任务时，会调用 handle() 方法
     * @throws \Throwable
     */
    public function handle()
    {
        if ($this->order->paid_at) {
            return;
        }

        \DB::transaction(function () {
            // 将订单的closed 字段标记为true
            $this->order->update(['closed' => true]);
            // 循环遍历订单中的商品 SKU，将订单中的数量加回到 SKU 的库存中去
            foreach ($this->order->items as $item) {
                $item->productSku->addStock($item->amount);
            }
        });

    }
}
