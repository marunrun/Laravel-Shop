<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddCartRequest;
use App\Models\CartItem;
use App\Models\ProductSku;
use App\Models\User;
use App\Services\CartService;
use Illuminate\Http\Request;

class CartItemsController extends Controller
{

    protected $cartService;

    public function __construct(CartService $cartService)
    {
        $this->cartService = $cartService;
    }

    /** 购物车列表
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index(Request $request)
    {
        $cartItems = $this->cartService->get();

        $addresses = $request->user()->addresses()->orderBy('last_used_at','desc')->get();
        return view('cart.index',compact('cartItems','addresses'));
    }

    /** 添加商品到购物车
     * @param AddCartRequest $request
     * @return array
     */
    public function add(AddCartRequest $request)
    {
        $this->cartService->add($request->input('sku_id'), $request->input('amount'));

        return [];
    }


    /** 移除购物车商品
     * @param ProductSku $sku
     * @param Request $request
     * @return array
     */
    public function remove(ProductSku $sku,Request $request)
    {
        $this->cartService->remove($sku->id);

        return [];
    }
}
