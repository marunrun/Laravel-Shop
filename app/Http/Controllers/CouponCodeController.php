<?php

namespace App\Http\Controllers;

use App\Models\CouponCode;
use Illuminate\Http\Request;

class CouponCodeController extends Controller
{
    /**
     * @param $code
     *
     * @return CouponCode|\Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|object|null
     *
     * @throws \App\Exceptions\CouponCodeUnavailableException
     */
    public function show($code, Request $request)
    {
        // 判断优惠券是否存在
        if (!$record = CouponCode::query()->where('code', $code)->first()) {
            abort(404);
        }

        $record->checkAvailable($request->user());

        return $record;
    }
}
