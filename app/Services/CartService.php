<?php
/**
 * Created by PhpStorm.
 * User: run
 * Date: 2019/3/2
 * Time: 13:39
 */

namespace App\Services;


use App\Models\CartItem;
use Illuminate\Support\Facades\Auth;

class CartService
{

    // 获取购物车信息
    public function get()
    {
        return Auth::user()->cartItems()->with(['productSku.product'])->get();
    }

    /** 添加商品到购物车
     * @param $skuId  商品SKU的id
     * @param $amount 商品数量
     * @return CartItem
     */
    public function add($skuId, $amount)
    {
        $user = Auth::user();

        if ($item = $user->cartItems()->where('product_sku_id', $skuId)->first()) {
            // 如果存在就叠加商品数量
            $item->update(['amount' => $item->amount + $amount]);
        } else {
            // 否则就创建一个新的
            $item = new CartItem(['amount' => $amount]);
            $item->user()->associate($user);
            $item->productSku()->associate($skuId);
            $item->save();
        }

        return $item;
    }

    public function remove($skuIds)
    {
        if (!is_array($skuIds)) {
            $skuIds = [$skuIds];
        }

        Auth::user()->cartItems()->whereIn('product_sku_id',$skuIds)->delete();
    }
}