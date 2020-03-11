<?php

namespace App\Jobs;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class SyncOneProduct implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    /**
     * @var Product
     */
    private $product;

    /**
     * Create a new job instance.
     *
     * @param  Product  $product
     */
    public function __construct(Product $product)
    {
        \Log::debug(__CLASS__);
        $this->product = $product;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $data = $this->product->toESArray();

        app('es')->index([
            'index' => 'products',
            'id' => $data['id'],
            'body' => $data,
        ]);
    }
}
