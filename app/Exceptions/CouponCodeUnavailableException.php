<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\Request;
use Throwable;

class CouponCodeUnavailableException extends Exception
{
    public function __construct(string $message = "", int $code = 403)
    {
        parent::__construct($message, $code);
    }

    public function render(Request $request)
    {
        // 如果是Api 请求, 返回json格式的错误信息
        if ($request->expectsJson()) {
            return response()->json(['msg' => $this->message],$this->code);
        }

        // 否则回到上一页并带上错误信息
        return redirect()->back()->withErrors(['coupon_code' => $this->message]);
    }
}
