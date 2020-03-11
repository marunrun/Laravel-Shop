<?php

use App\Models\Category;
use Faker\Generator as Faker;

$factory->define(App\Models\Product::class, function (Faker $faker) {
    $image = $faker->randomElement([
        'https://imgservice.suning.cn/uimg1/b2c/image/NiyXAh0Y4cCK7__LjTR59w.jpg_100w_100h_4e',
        'hthttps://imgservice.suning.cn/uimg1/b2c/image/h96_yArpzp6tuXKDW4o6Zw.jpg_100w_100h_4e',
    ]);
    $category = Category::query()->where('is_directory', false)->inRandomOrder()->first();

    return [
        'title' => $faker->word,
        'long_title' => $faker->sentence,
        'description' => $faker->sentence,
        'image' => $image,
        'on_sale' => true,
        'rating' => $faker->numberBetween(0, 5),
        'sold_count' => 0,
        'review_count' => 0,
        'price' => 0,
        // 将取出的类目 ID 赋给 category_id 字段
        // 如果数据库中没有类目则 $category 为 null，同样 category_id 也设成 null
        'category_id' => $category ? $category->id : null,
    ];
});
