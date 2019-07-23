<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/


// 开启用户认证的所有路由 并开启邮箱检测
Auth::routes(['verify' => true]);


// 中间件，auth 需要用户登陆，verified 邮箱检测过的
Route::group(['middleware' => ['auth', 'verified']], function () {

    // 关于用户地址的路由
    Route::get('user_addresses', 'UserAddressesController@index')->name('user_addresses.index');
    Route::get('user_addresses/create', 'UserAddressesController@create')->name('user_addresses.create');
    Route::post('user_addresses/create', 'UserAddressesController@store')->name('user_addresses.store');
    Route::get('user_addresses/{address}', 'UserAddressesController@edit')->name('user_addresses.edit');
    Route::put('user_addresses/{address}', 'UserAddressesController@update')->name('user_addresses.update');
    Route::delete('user_addresses/{address}', 'UserAddressesController@destroy')->name('user_addresses.destroy');

    // 收藏商品和取消收藏 我的收藏列表
    Route::post('products/{product}/favorite', 'ProductsController@favor')->name('products.favor');
    Route::delete('products/{product}/favorite', 'ProductsController@disfavor')->name('products.disfavor');
    Route::get('products/favorites', 'ProductsController@favorites')->name('products.favorites');

    // 购物车相关
    Route::post('cart', 'CartItemsController@add')->name('cart.add');
    Route::get('cart', 'CartItemsController@index')->name('cart.index');
    Route::delete('cart/{sku}', 'CartItemsController@remove')->name('cart.remove');

    // 订单相关
    Route::get('order/{order}/review','OrdersController@review')->name('orders.review.show');
    Route::post('order/{order}/review','OrdersController@sendReview')->name('orders.review.store'); // 订单评价
    Route::post('order', 'OrdersController@store')->name('orders.store'); // 创建订单
    Route::get('order', 'OrdersController@index')->name('orders.index');  // 订单列表
    Route::get('order/{order}', 'OrdersController@show')->name('orders.show'); // 订单详情
    Route::post('orders/{order}/received', 'OrdersController@received')->name('orders.received'); // 订单确认收货
    Route::post('orders/{order}/apply_refund','OrdersController@applyRefund')->name('orders.apply_refund'); // 订单退款
    Route::post('crowdfunding_orders','OrdersController@crowdfunding')->name('crowdfunding_orders.store');// 众筹商品下单

    // 支付相关
    Route::get('payment/{order}/alipay', 'PaymentController@payByAlipay')->name('payment.alipay');
    Route::get('payment/alipay/return', 'PaymentController@alipayReturn')->name('payment.alipay.return');
    Route::get('payment/{order}/wechat', 'PaymentController@payByWechat')->name('payment.wechat');
    Route::post('payment/{order}/installment','PaymentController@payByInstallment')->name('payment.installment');
    // 优惠券
    Route::get('coupon_codes/{code}', 'CouponCodeController@show')->name('coupon_codes.show');

    // 分期付款
    Route::get('installments','InstallmentsController@index')->name('installments.index');
    Route::get('installments/{installment}', 'InstallmentsController@show')->name('installments.show');
});

// 无需登陆 认证
// 首页 商品列表
Route::redirect('/', '/products')->name('root');
Route::get('/products', 'ProductsController@index')->name('products.index');

// 商品详情页
Route::get('/products/{product}', 'ProductsController@show')->name('products.show');
// 支付宝异步回调
Route::post('payment/alipay/notify', 'PaymentController@alipayNotify')->name('payment.alipay.notify');
// 微信支付异步回调
Route::get('payment/wechat/notify', 'PaymentController@wechatNotify')->name('payment.wechat.notify');