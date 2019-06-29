<?php

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Seeder;

class OrdersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @throws Exception
     */
    public function run()
    {
        // 获取Faker实例
        $faker = app(Faker\Generator::class);

        // 创建 100 笔订单
        $orders = factory(Order::class, 100)->create();

        // 被购买的商品, 用于后面更新商品销量和评分
        $products = collect([]);

        /** @var Order $order */
        foreach ($orders as $order) {
            // 每笔订单 随机 1 - 3 个商品
            $items = factory(OrderItem::class, random_int(1, 3))->create([
                'order_id' => $order->id,
                'rating' => $order->reviewed ? random_int(1, 5) : null,
                'review' => $order->reviewed ? $faker->sentence : null,
                'reviewed_at' => $order->reviewed ? $faker->dateTimeBetween($order->paid_at) : null,
            ]);

            // 计算总价

            $total = $items->sum(function (OrderItem $item) {
                return $item->price * $item->amount;
            });

            // 如果有优惠券，则计算优惠后价格
            if ($order->couponCode) {
                $total = $order->couponCode->getAdjustedPrice($total);
            }

            // 更新订单总价
            $order->update([
                'total_amount' => $total,
            ]);

            // 将这笔订单的商品合并到商品集合中
            $products = $products->merge($items->pluck('product'));
        }

        $products->unique('id')->each(function (Product $product) {
            // 查出该商品的销量, 评分, 评论数
            $result = OrderItem::query()
                ->where('product_id', $product->id)
                ->whereHas('order', function (Builder $query) {
                    $query->whereNotNull('paid_at');
                })
                ->first([
                    DB::raw('count(*) as review_count'),
                    DB::raw('avg(rating) as rating'),
                    DB::raw('sum(amount) as sold_count'),
                ]);

            $product->update([
                'rating' => $result->rating,
                'review_count' => $result->review_count,
                'sold_count' => $result->sold_count,
            ]);
        });
    }
}
