<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserAddressRequest;
use App\Models\UserAddress;
use Illuminate\Http\Request;

class UserAddressesController extends Controller
{
    // 收货地址的首页 显示当前用户的所有收获地址
    public function index(Request $request)
    {
        return view('user_addresses.index',[
            'addresses' => $request->user()->addresses
        ]);
    }

    // 新增收获地址的页面
    public function create()
    {
        return view('user_addresses.create_and_edit',['address' => new UserAddress()]);
    }

    // 新增收获地址
    public function store(UserAddressRequest $request)
    {
        $request->user()->addresses()->create($request->only([
            'province',
            'city',
            'district',
            'address',
            'zip',
            'contact_name',
            'contact_phone',
        ]));

        return redirect()->route('user_addresses.index');
    }
    
    // 修改的页面
    public function edit(UserAddress $address)
    {
        $this->authorize('own',$address);

        return view('user_addresses.create_and_edit',['address' => $address]);
    }
    
    // 修改更新数据
    public function update(UserAddress $address, UserAddressRequest $request)
    {
        $this->authorize('own',$address);

        $address->update($request->only([
            'province',
            'city',
            'district',
            'address',
            'zip',
            'contact_name',
            'contact_phone',
        ]));

        return redirect()->route('user_addresses.index');
    }

    // 删除
    public function destroy(UserAddress $address)
    {
        $this->authorize('own',$address);

        $address->delete();
        return [];
    }
}
