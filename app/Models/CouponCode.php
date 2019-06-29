<?php

namespace App\Models;

use App\Exceptions\CouponCodeUnavailableException;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * App\Models\CouponCode.
 *
 * @property int $id
 * @property string $name        优惠券标题
 * @property string $code        优惠码，唯一
 * @property string $type        优惠卷类型
 * @property float $value       折扣值
 * @property int $total       全站可兑换数量
 * @property int $used        已兑换数量
 * @property float $min_amount  使用该优惠券的最低金额
 * @property \Illuminate\Support\Carbon|null $not_before  在这之前不可用
 * @property \Illuminate\Support\Carbon|null $not_after   在这之后不可用
 * @property bool $enabled     优惠券是否生效
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property mixed $description
 *
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\CouponCode newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\CouponCode newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\CouponCode query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\CouponCode whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\CouponCode whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\CouponCode whereEnabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\CouponCode whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\CouponCode whereMinAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\CouponCode whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\CouponCode whereNotAfter($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\CouponCode whereNotBefore($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\CouponCode whereTotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\CouponCode whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\CouponCode whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\CouponCode whereUsed($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\CouponCode whereValue($value)
 * @mixin \Eloquent
 */
class CouponCode extends Model
{
    const TYPE_FIXED = 'fixed';
    const TYPE_PERCENT = 'percent';

    public static $typeMap = [
        self::TYPE_FIXED   => '固定比例',
        self::TYPE_PERCENT => '比例',
    ];

    protected $fillable = [
        'name',
        'code',
        'type',
        'value',
        'total',
        'used',
        'min_amount',
        'not_before',
        'not_after',
        'enabled',
    ];
    protected $casts = [
        'enabled' => 'boolean',
    ];

    protected $dates = ['not_before', 'not_after'];

    protected $appends = ['description'];

    /**
     * 得到一个不重复的随机优惠码
     *
     * @param int $length
     *
     * @return string
     */
    public static function findAvailableCode($length = 16)
    {
        do {
            $code = strtoupper(Str::random($length));
        } while (self::query()->where('code', $code)->exists());

        return $code;
    }

    public function getDescriptionAttribute()
    {
        $str = '';
        if ($this->min_amount > 0) {
            $str = '满' . str_replace('.00', '', $this->min_amount);
        }
        if (self::TYPE_PERCENT === $this->type) {
            return $str . '优惠' . str_replace('.00', '', $this->value) . '%';
        }

        return $str . '减' . str_replace('.00', '', $this->value);
    }

    /**
     * 检查优惠券是否可用.
     *
     * @param User $user
     * @param float|null $orderAmount 订单金额
     *
     * @throws CouponCodeUnavailableException
     */
    public function checkAvailable(User $user, float $orderAmount = null)
    {
        if (!$this->enabled) {
            throw new CouponCodeUnavailableException('优惠券不存在');
        }

        if ($this->total - $this->used <= 0) {
            throw new CouponCodeUnavailableException('该优惠券已被领完');
        }

        if ($this->not_before && $this->not_before->gt(Carbon::now())) {
            throw new CouponCodeUnavailableException('该优惠券现在还不能使用');
        }

        if ($this->not_after && $this->not_after->lt(Carbon::now())) {
            throw new CouponCodeUnavailableException('该优惠券已过期');
        }

        if (!is_null($orderAmount) && $orderAmount < $this->min_amount) {
            throw new CouponCodeUnavailableException('订单金额不满足该优惠券最低金额');
        }

        $used = Order::query()
            ->where('user_id', $user->id)
            ->where('coupon_code_id', $this->id)
            ->where(function (Builder $query) {
                $query->where(function (Builder $query) {
                    $query->whereNull('paid_at')
                        ->where('closed', false);
                })->orWhere(function (Builder $query) {
                    $query->whereNotNull('paid_at')
                        ->where('refund_status', '!=', Order::REFUND_STATUS_SUCCESS);
                });
            })
            ->exists();
        if ($used) {
            throw new CouponCodeUnavailableException('你已经使用过该优惠券了');
        }
    }

    /**
     * 获取优惠后的价钱.
     *
     * @param $orderAmount
     *
     * @return mixed|string
     */
    public function getAdjustedPrice($orderAmount)
    {
        // 固定金额
        if (self::TYPE_FIXED === $this->type) {
            // 为了系统的健壮性,订单金额不低于0.01
            return max(0.01, bcsub($orderAmount, $this->value));
        }

        return number_format($orderAmount * (100 - $this->value) / 100, 2, '.', '');
    }

    /**
     * 传入true 代表新增,否则就是减少.
     *
     * @param bool $increase
     *
     * @return int
     */
    public function changeUsed(bool $increase = true)
    {
        return $increase ? $this->newQuery()
            ->where('id', $this->id)
            ->where('used', '<', $this->total)
            ->increment('used') : $this->decrement('used');
    }
}
