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

// 首页
Route::get('/','PageController@root')->name('root');

// 开启用户认证的所有路由 并开启邮箱检测
Auth::routes(['verify' => true]);


// 中间件，auth 需要用户登陆，verified 邮箱检测过的
Route::group(['middleware' => ['auth','verified']],function () {

    Route::get('user_addresses','UserAddressesController@index')->name('user_addresses.index');
    Route::get('user_addresses/create','UserAddressesController@create')->name('user_addresses.create');
    Route::post('user_addresses/create','UserAddressesController@store')->name('user_addresses.store');
    Route::get('user_addresses/{address}','UserAddressesController@edit')->name('user_addresses.edit');
    Route::put('user_addresses/{address}','UserAddressesController@update')->name('user_addresses.update');
    Route::delete('user_addresses/{address}','UserAddressesController@destroy')->name('user_addresses.destroy');
});

