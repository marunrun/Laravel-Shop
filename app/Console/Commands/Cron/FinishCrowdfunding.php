<?php

namespace App\Console\Commands\Cron;

use App\Jobs\RefundCrowdfundingOrders;
use App\Models\CrowdfundingProduct;
use Carbon\Carbon;
use Illuminate\Console\Command;

class FinishCrowdfunding extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cron:finish-crowdfunding';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '结束众筹';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        CrowdfundingProduct::query()
            ->where('end_at', '<=', Carbon::now())
            ->where('status', CrowdfundingProduct::STATUS_FUNDING)
            ->get()
            ->each(function (CrowdfundingProduct $crowdfundingProduct) {
                // 如果目标金额大于当前筹到的金额
                if ($crowdfundingProduct->target_amount > $crowdfundingProduct->total_amount) {
                    // 众筹失败
                    $this->crowdfundingFail($crowdfundingProduct);
                } else {
                    // 众筹成功
                    $this->crowdfundingSucceed($crowdfundingProduct);
                }
            });
    }


    /**
     * 众筹成功
     * @param CrowdfundingProduct $crowdfundingProduct
     */
    protected function crowdfundingSucceed(CrowdfundingProduct $crowdfundingProduct)
    {
        $crowdfundingProduct->update([
            'status' => CrowdfundingProduct::STATUS_SUCCESS,
        ]);
    }

    /**
     * 众筹失败
     * @param CrowdfundingProduct $crowdfundingProduct
     */
    protected function crowdfundingFail(CrowdfundingProduct $crowdfundingProduct)
    {
        $crowdfundingProduct->update([
            'status' => CrowdfundingProduct::STATUS_FAIL,
        ]);

        // 异步任务 分发 退款可能会很耗时,使用异步任务
        dispatch(new RefundCrowdfundingOrders($crowdfundingProduct));
    }
}
