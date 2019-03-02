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
                            <th><input type="checkbox" id="select-all" checked></th>
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
                    <div>
                        <form action="" class="form-horizontal" role="form" id="order-form">
                            <div class="form-group row">
                                <div class="col-form-label col-sm-2 text-md-right">选择收获地址</div>
                                <div class="col-sm-8 col-md-7">
                                    <select name="address" class="form-control">
                                        @foreach($addresses as $address)
                                            <option value="{{ $address->id }}">{{ $address->full_address }}
                                                {{ $address->contact_name }}
                                                {{ $address->contact_phone }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-sm-2">
                                    <a href="{{ route('user_addresses.create') }}?from={{ Request::path() }} " class="btn btn-outline-primary">新建收获地址</a>
                                </div>
                            </div>
                            <div class="form-group row">
                                <label class="col-form-label col-sm-2 text-md-right">备注</label>
                                <div class="col-sm-8 col-md-7">
                                    <textarea name="remark" class="form-control" rows="3"></textarea>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="offset-sm-3 col-sm-3">
                                    <button type="button" class="btn btn-primary btn-create-order">提交订单</button>
                                </div>

                            </div>
                        </form>
                    </div>
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
                        // ajax请求删除路由
                        axios.delete('/cart/' + id)
                            .then(function () {
                                location.reload()
                            })
                    })
            });

            // 获取所有 name=select 并且不带有 disabled 属性的勾选框
            // 对于已经下架的商品我们不希望对应的勾选框会被选中，因此我们需要加上 :not([disabled]) 这个条件
            var checkboxes = $('input[name=select][type=checkbox]:not(:disabled)');

            // 全选和取消全选
            $('#select-all').change(function () {
                //  获取单选框的选中状态。
                var checked = $(this).prop('checked');

                // 设置勾选状态跟改变之后的全选状态一样
                checkboxes.each(function () {
                    $(this).prop('checked', checked);
                })

            });

            // 购物车中所有未下架的商品
            var carts = checkboxes.length;

            // 其他的普通的勾选
            checkboxes.change(function () {
                // 已选择的复选框的数量
                var chosen = $('input[name=select][type=checkbox]:not(:disabled):checked').length;
                // 如果已选择的数量等于未下架商品的数量 那么就自动勾选全选 否则就不自动勾选全选
                var allChecked = chosen === carts;

                $('#select-all').prop('checked', allChecked);
            });


            // 监听创建订单按钮的事件
            $('.btn-create-order').click(function () {

                var errMsg;

                var req = {
                    address_id: $('#order-form').find('select[name=address]').val(),
                    items: [],
                    remark: $('#order-form').find('textarea[name=remark]').val(),
                };
                // 遍历 <table> 标签内所有带有 data-id 属性的 <tr> 标签，也就是每一个购物车中的商品 SKU
                $('table tr[data-id]').each(function () {
                    // 获取当前行的单选框
                    var $checkbox = $(this).find('input[name=select][type=checkbox]');
                    // 如果当前行没被选中或者被禁用就跳过
                    if ($checkbox.prop('disabled') || !$checkbox.prop('checked')) {
                        return;
                    }

                    // 获取当前行输入的数量
                    var $input = $(this).find('input[name=amount]');
                    // 如果当前输入的数量是0 或者不是一个数字 就跳过
                    if ($input.val() <= 0 || isNaN($input.val())) {
                        errMsg = '商品数量输入错误';
                        return false;
                    }

                    // 把 SKU id 和数量存入请求参数数组中
                    req.items.push({
                        sku_id: $(this).data('id'),
                        amount: $input.val()
                    })
                });

                // 如果有错误信息 就直接报错
                if (errMsg) {
                    return swal(errMsg,'','error');
                }

                // 提交
                axios.post('{{ route('orders.store') }}', req)
                    .then(function (response) {
                        swal('订单提交成功', '', 'success')
                            .then(function () {
                                location.href='/order/' + response.data.id;
                            });
                    }).catch(function (error) {
                    if (error.response.status == 422) {
                        // http 状态码为 422 代表用户输入校验失败
                        var html = '<div>';
                        _.each(error.response.data.errors, function (errors) {
                            _.each(errors, function (error) {
                                html += error + '<br>';
                            })
                        });
                        html += '</div>';
                        swal({content: $(html)[0], icon: 'error'})
                    } else {
                        swal('系统错误', '', 'error');
                    }
                });
            });
        });
    </script>
@stop