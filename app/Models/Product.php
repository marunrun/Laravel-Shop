<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'title', 'description', 'image', 'on_sale',
        'rating', 'sold_count', 'review_count', 'price'
    ];

    // 强制转换的属性
    protected $casts = [
        'on_sale' => 'boolean',   // 将on_sale强制转换成布尔类型
    ];

    // 与商品sku关联
    public function skus()
    {
        return $this->hasMany(ProductSku::class);
    }
}
