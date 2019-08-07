<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\InstallmentItem.
 *
 * @property int $id
 * @property int $installment_id 分期id
 * @property int $sequence       还款顺序编号
 * @property float $base           当期本金
 * @property float $fee            当期手续费
 * @property float|null $fine           当期逾期费
 * @property string $due_date       还款截至日期
 * @property string|null $paid_at        还款日期
 * @property string|null $payment_method 还款支付方式
 * @property string|null $payment_no     还款平台订单号
 * @property string $refund_status  还款状态
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\InstallmentItem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\InstallmentItem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\InstallmentItem query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\InstallmentItem whereBase($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\InstallmentItem whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\InstallmentItem whereDueDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\InstallmentItem whereFee($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\InstallmentItem whereFine($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\InstallmentItem whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\InstallmentItem whereInstallmentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\InstallmentItem wherePaidAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\InstallmentItem wherePaymentMethod($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\InstallmentItem wherePaymentNo($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\InstallmentItem whereRefundStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\InstallmentItem whereSequence($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\InstallmentItem whereUpdatedAt($value)
 * @mixin \Eloquent
 * @property-read bool $is_overdue
 * @property-read string $total
 * @property-read \App\Models\Installment $installment
 */
class InstallmentItem extends Model
{
    const REFUND_STATUS_PENDING = 'pending';
    const REFUND_STATUS_PROCESSING = 'processing';
    const REFUND_STATUS_SUCCESS = 'success';
    const REFUND_STATUS_FAILED = 'failed';

    public static $refundStatusMap = [
        self::REFUND_STATUS_PENDING    => '未退款',
        self::REFUND_STATUS_PROCESSING => '退款中',
        self::REFUND_STATUS_SUCCESS    => '退款成功',
        self::REFUND_STATUS_FAILED     => '退款失败',
    ];

    protected $fillable = [
        'sequence',
        'base',
        'fee',
        'fine',
        'due_date',
        'paid_at',
        'payment_method',
        'payment_no',
        'refund_status',
    ];

    protected $dates = ['due_date', 'paid_at'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function installment()
    {
        return $this->belongsTo(Installment::class);
    }

    /**
     * 获取还款总金额
     * @return string
     */
    public function getTotalAttribute()
    {
        $total = big_number($this->base)->add($this->fee);
        if (!is_null($this->fine)) {
            $total->add($this->fine);
        }

        return $total->getValue();
    }

    /**
     * 判断当前是否逾期
     * @return bool
     */
    public function getIsOverdueAttribute()
    {
        return Carbon::now()->gt($this->due_date);
    }
}
