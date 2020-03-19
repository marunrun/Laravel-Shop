<?php

namespace App\Http\Controllers;


use App\Http\Requests\Request;

class TestController extends Controller
{
    public function test(Request $request)
    {
        var_dump(111);
        \Log::notice("快递100", $request->all());

        return response()->json([
            'result' => true,
            'returnCode' => 200,
            'message' => '成功',
        ]);
    }
}
