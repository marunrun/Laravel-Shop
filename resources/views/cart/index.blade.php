@extends('layouts.app')

@section('title','购物车')

@section('content')
    <div class="row">
        <div class="col-lg-10 offset-lg-1">
            <div class="card">
                <div class="card-header">我的购物车</div>
                <div class="card-body">
                    <table class="table table-striped">
                        <thead>
                        <tr>
                            <th><input type="checkbox" id="select-all"></th>
                            <th>商品信息</th>
                            <th>单价</th>
                            <th>数量</th>
                            <th>操作</th>
                        </tr>
                        </thead>
                        <tbody class="product_list">
                        @foreach($cartItems as $item)
                            <tr data-id="{{ $item->productSku->id }}">
                                <td>
                                    <input type="checkbox" name="select" value="{{ $item->productSku->id }}"
                                            {{ $item->productSku->product->on_sale ? 'checked' : 'disabled' }}>
                                </td>
                                <td class="product_info">
                                    <div class="preview">
                                        <a href="{{ route('products.show',['product' => $item->productSku->product->id]) }}"
                                           target="_blank">
                                            <img src="{{ $item->productSku->product->image_url }}" alt="">
                                        </a>
                                    </div>
                                    <div @if(!$item->productSku->product->on_sale) class="not_on_sale" @endif>
                                        <span class="product_title">
                                        <a href="{{ route('products.show',['product' => $item->productSku->product->id]) }}"
                                           target="_blank">
                                            {{ $item->productSku->product->title }}
                                        </a>
                                        </span>
                                        <span class="sku_title">{{ $item->productSku->title }}</span>
                                        @if(!$item->productSku->product->on_sale)
                                            <span class="warning">该商品已下架</span>
                                        @endif
                                    </div>
                                </td>
                                <td><span class="price">￥{{ $item->productSku->price }}</span></td>
                                <td>
                                    <input type="text" class="form-control form-control-sm amount"
                                           @if(!$item->productSku->product->on_sale) disabled @endif name="amount"
                                           value="{{ $item->amount }}">
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-danger btn-remove">移除</button>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@stop

@section('scriptsAfterJs')
    <script>
        $(function () {

            // 移除按钮的点击事件
            $('.btn-remove').click(function () {
                // 找到当前触发事件的dom
                var id = $(this).closest('tr').data(id).id;
                console.log(id);
                swal({
                    title: '确定要将此商品移除？',
                    icon: 'warning',
                    buttons: ['取消', '确认'],
                    dangerMode: true
                })
                    .then(function (willDelete) {
                        if (!willDelete) {
                            // 用户点击 确定 按钮，willDelete 的值就会是 true，否则为 false
                            return;
                        }
                        axios.delete('/cart/' + id)
                            .then(function () {
                                location.reload()
                            })
                    })
            });
        })
    </script>
@stop