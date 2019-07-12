<?php
/**
 * Created by PhpStorm.
 * User: run
 * Date: 2019/3/2
 * Time: 13:55.
 */

namespace App\Services;

use App\Exceptions\CouponCodeUnavailableException;
use App\Exceptions\InvalidRequestException;
use App\Jobs\CloseOrder;
use App\Models\CouponCode;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductSku;
use App\Models\User;
use App\Models\UserAddress;
use Carbon\Carbon;

class OrderService
{
    /**
     * @param User        $user
     * @param UserAddress $address
     * @param $remark
     * @param $items
     * @param CouponCode|null $coupon
     *
     * @return mixed
     *
     * @throws CouponCodeUnavailableException
     * @throws \Throwable
     */
    public function store(User $user, UserAddress $address, $remark, $items, CouponCode $coupon = null)
    {
        if ($coupon) {
            $coupon->checkAvailable($user);
        }

        $order = \DB::transaction(function () use ($user, $address, $remark, $items, $coupon) {
            // 更新地址的最新使用时间
            $address->update(['last_used_at' => Carbon::now()]);

            // 创建订单
            $order = new Order([
                'address' => [
                    'address' => $address->full_address,
                    'zip' => $address->zip,
                    'contact_name' => $address->contact_name,
                    'contact_phone' => $address->contact_phone,
                ],
                'remark' => $remark,
                'total_amount' => 0,
            ]);

            // 订单关联到用户
            $order->user()->associate($user);

            // 写入数据库
            $order->save();

            $totalAmount = 0;

            // 遍历提交的SKU
            foreach ($items as $data) {
                $sku = ProductSku::find($data['sku_id']);
                // 创建一个orderItem 与当前order关联
                /** @var OrderItem $item */
                $item = $order->items()->make([
                    'amount' => $data['amount'],
                    'price' => $sku->price,
                    'type' => Order::TYPE_NORMAL,
                ]);
                $item->product()->associate($sku->product_id);
                $item->productSku()->associate($sku);
                $item->save();
                $totalAmount += $sku->price * $data['amount'];

                if ($sku->decreaseStock($data['amount']) <= 0) {
                    throw new InvalidRequestException('该商品库存不足');
                }
            }

            if ($coupon) {
                // 检查当前订单金额是否符合优惠券使用规则
                $coupon->checkAvailable($user, $totalAmount);

                // 计算优惠后的价格
                $totalAmount = $coupon->getAdjustedPrice($totalAmount);

                // 将该订单与优惠券关联
                $order->couponCode()->associate($coupon);

                // 增加优惠券的用量, 需判断返回值
                if ($coupon->changeUsed() <= 0) {
                    throw new CouponCodeUnavailableException('该优惠券已被兑完');
                }
            }

            // 更新订单金额
            $order->update(['total_amount' => $totalAmount]);

            // 将下单的商品从购物车中移除
            $skuIds = collect($items)->pluck('sku_id')->all();
            app(CartService::class)->remove($skuIds);

            return $order;
        });

        // 延迟任务 30 分钟后关闭未付款订单
        dispatch(new CloseOrder($order, config('shop.order_ttl', 1800)));

        return $order;
    }

    /**
     * 众筹商品,下单.
     *
     * @param User        $user
     * @param UserAddress $address
     * @param ProductSku  $sku
     * @param $amount
     *
     * @return mixed
     *
     * @throws \Throwable
     */
    public function crowdfunding(User $user, UserAddress $address, ProductSku $sku, $amount)
    {
        $order = \DB::transaction(function () use ($user, $address, $sku, $amount) {
            // 更新地址最后使用时间
            $address->update(['last_used_at' => Carbon::now()]);

            $order = new Order([
                'address' => [
                    'address' => $address->full_address,
                    'zip' => $address->zip,
                    'contact_name' => $address->contact_name,
                    'contact_phone' => $address->contact_phone,
                ],
                'remark' => '',
                'total_amount' => $sku->price * $amount,
                'type' => Order::TYPE_CROWDFUNDING
            ]);

            // 将订单与用户相关联
            $order->user()->associate($user);
            // 写入数据库
            $order->save();

            /** @var OrderItem $item */
            $item = $order->items()->make([
                'amount' => $amount,
                'price' => $sku->price,
            ]);

            $item->product()->associate($sku->product_id);
            $item->productSku()->associate($sku);
            $item->save();

            // 扣减库存
            if ($sku->decreaseStock($amount) <= 0) {
                throw new InvalidRequestException('该商品库存不足');
            }

            return $order;
        }, 3);

        // 众筹结束时间 减去 当前时间 得到 剩余秒数
        $crowdfundingTtl = $sku->product->crowdfunding->end_at->getTimestamp() - time();

        // 剩余秒数 与 默认关闭时间取较小值作为订单关闭时间
        dispatch(new CloseOrder($order, min(config('shop.order_ttl'), $crowdfundingTtl)));

        return $order;
    }
}
