<?php


namespace App\Services;


use App\Models\Product;
use App\SearchBuilders\ProductSearchBuilder;

class ProductService
{
    /**
     * 获取相似的商品
     * @param  Product  $product
     * @param int $amount 数量
     * @return array
     */
    public function getSimilarProductIds(Product $product,int $amount)
    {
        // 如果商品没有商品属性，则直接返回空
        if (count($product->properties) === 0) {
            return [];
        }
        $builder = (new ProductSearchBuilder())->onSale()->paginate($amount, 1);
        foreach ($product->properties as $property) {
            $builder->propertyFilter($property->name, $property->value, 'should');
        }
        $builder->minShouldMatch(1)->appendMustNot([
            'term' => ['_id' => $product->id],
        ]);

        $res = app('es')->search($builder->getParams());

        return collect($res['hits']['hits'])->pluck('_id')->all();
    }
}
