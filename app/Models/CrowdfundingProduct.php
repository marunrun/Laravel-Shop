<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\CrowdfundingProduct
 *
 * @property-read float $percent
 * @property-read \App\Models\Product $product
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\CrowdfundingProduct newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\CrowdfundingProduct newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\CrowdfundingProduct query()
 * @mixin \Eloquent
 * @property int $id
 * @property int $product_id 商品id
 * @property float $target_amount 众筹目标金额
 * @property float $total_amount 当前已筹金额
 * @property int $user_count 当前参与众筹用户数
 * @property \Illuminate\Support\Carbon $end_at 结束时间
 * @property string $status
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\CrowdfundingProduct whereEndAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\CrowdfundingProduct whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\CrowdfundingProduct whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\CrowdfundingProduct whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\CrowdfundingProduct whereTargetAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\CrowdfundingProduct whereTotalAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\CrowdfundingProduct whereUserCount($value)
 */
class CrowdfundingProduct extends Model
{
    // 众筹的三种状态
    const STATUS_FUNDING = 'funding';
    const STATUS_SUCCESS = 'success';
    const STATUS_FAIL    = 'fail';

    public static $statusMap = [
        self::STATUS_FUNDING => '众筹中',
        self::STATUS_SUCCESS => '众筹成功',
        self::STATUS_FAIL    => '众筹失败',
    ];

    protected $fillable = [
        'total_amount',
        'target_amount',
        'user_count',
        'status',
        'end_at'
    ];

    public $timestamps = false;

    protected $dates = [
        'end_at',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }


    /**
     * 获取众筹进度
     * @return float
     */
    public function getPercentAttribute()
    {
        // 已筹金额除以目标金额
        $value = $this->attributes['total_amount'] / $this->attributes['target_amount'] ;

        return floatval(number_format($value * 100, 2,'.',''));
    }
}
