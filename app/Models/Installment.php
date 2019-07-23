<?php

namespace App\Models;

use function foo\func;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Installment
 *
 * @property int $id
 * @property string $no 流水号
 * @property int $user_id 用户id
 * @property int $order_id 订单id
 * @property float $total_amount 订单总价
 * @property int $count 还款期数
 * @property float $fee_rate 还款费率
 * @property float $fine_rate 逾期费率
 * @property string $status 状态
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Installment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Installment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Installment query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Installment whereCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Installment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Installment whereFeeRate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Installment whereFineRate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Installment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Installment whereNo($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Installment whereOrderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Installment whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Installment whereTotalAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Installment whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Installment whereUserId($value)
 * @mixin \Eloquent
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\InstallmentItem[] $items
 * @property-read \App\Models\Order $order
 * @property-read \App\Models\User $user
 */
class Installment extends Model
{
    const STATUS_PENDING = 'pending';
    const STATUS_REPAYING = 'repaying';
    const STATUS_FINISHED = 'finished';

    public static $statusMap = [
        self::STATUS_PENDING  => '未执行',
        self::STATUS_REPAYING => '还款中',
        self::STATUS_FINISHED => '已完成',
    ];

    protected $fillable = ['no', 'total_amount', 'count', 'fee_rate', 'fine_rate', 'status'];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            // 如果模型的 no 字段未空
            if (!$model->no) {
                // 调用 findAvailableNo 生成分期流水号
                $model->no = static::findAvailableNo();
                // 如果生成失败, 则终止订单
                if (!$model->no) {
                    return false;
                }
            }
        });
    }


    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function items()
    {
        return $this->hasMany(InstallmentItem::class);
    }

    /**
     * 生成唯一的流水号
     * @return bool|string
     * @throws \Exception
     */
    public static function findAvailableNo()
    {
        $prefix = date('YmdHis');
        for ($i = 0; $i < 10; $i++) {
            // 随机生成6位数字
            $no = $prefix.str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            // 判断是否存在
            if (!static::query()->where('no', $no)->exists()) {
                return $no;
            }
        }

        \Log::warning(sprintf('find installment no failed'));

        return false;
    }
}
