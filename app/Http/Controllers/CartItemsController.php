<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddCartRequest;
use App\Models\CartItem;
use App\Models\ProductSku;
use App\Models\User;
use Illuminate\Http\Request;

class CartItemsController extends Controller
{
    /** 添加商品到购物车
     * @param AddCartRequest $request
     * @return array
     */
    public function add(AddCartRequest $request)
    {
        $user = $request->user();
        $skuId = $request->input('sku_id');
        $amount = $request->input('amount');

        if ($cart = $user->cartItems()->where('product_sku_id',$skuId)->first()) {
            // 如果购物车中已存在 则在原有的基础上增加 数量
//            $cart->update([
//                'amount' => $cart->amount + $amount
//            ]);
            $cart->increment('amount',$amount);
        } else {
            // 否则 就添加一条新的记录

            $cart = new CartItem(['amount' => $amount]);
            $cart->user()->associate($user);
            $cart->productSku()->associate($skuId);
            $cart->save();
        }

        return [];
    }

    /** 购物车列表
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index(Request $request)
    {
        $user = $request->user();
        // 预加载
        $cartItems = $user->cartItems()->with('productSku.product')->get();

        return view('cart.index',compact('cartItems'));
    }


    public function remove(ProductSku $sku,Request $request)
    {
        $request->user()->cartItems()->where('product_sku_id',$sku->id)->delete();
        return ;
    }
}
