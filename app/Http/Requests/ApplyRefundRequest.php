<?php

namespace App\Http\Requests;

class ApplyRefundRequest extends Request
{
    public function rules()
    {
        return [
            'reason' => 'required'
        ];
    }

    public function attributes()
    {
        return [
            'reason' => '取消订单原因'
        ];
    }
}
