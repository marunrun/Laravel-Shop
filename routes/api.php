<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/


Route::any("/test",function (Request $request) {

    Log::notice("快递100",$request->all());
    return response()->json([
        'result' => true,
        'returnCode' => 200,
        'message' => '成功',
    ]);});
