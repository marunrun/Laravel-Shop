@extends('layouts.app')

@section('title',$product->title)

@section('content')
    <div class="row">
        <div class="col-lg-10 offset-lg-1">
            <div class="card">
                <div class="card-body product-info">
                    <div class="row">
                        <div class="col-5">
                            <img src="{{ $product->image_url }}" class="cover" alt="">
                        </div>
                        <div class="col-7">
                            <div class="title">{{ $product->long_title ?: $product->title }}</div>

                            {{--众筹模块开始--}}
                            @if ($product->type === \App\Models\Product::TYPE_CROWDFUNDING)
                                <div class="crowdfunding-info">
                                    <div class="have-text">已筹到</div>
                                    <div class="total-amount"><span class="symbol">￥</span>
                                        {{ $product->crowdfunding->total_amount }}</div>
                                    {{--使用bootstrap的进度条--}}
                                    <div class="progress">
                                        <div class="progress-bar progress-bar-success progress-bar-striped"
                                             role="progressbar"
                                             aria-valuenow="{{ $product->crowdfunding->percent }}"
                                             aria-valuemin="0"
                                             aria-valuemax="100"
                                             style="min-width: 1em; width: {{ min($product->crowdfunding->percent, 100) }}%">
                                        </div>
                                    </div>
                                    <div class="progress-info">
                                        <span
                                            class="current-progress">当前进度: {{ $product->crowdfunding->percent }}%</span>
                                        <span class="float-right user-count">{{ $product->crowdfunding->user_count }}名支持者</span>
                                    </div>
                                    {{--如果状态是众筹中, 则输出提示语--}}
                                    @if ($product->crowdfunding->status === \App\Models\CrowdfundingProduct::STATUS_FUNDING)
                                        <div>此项目必须在
                                            <span
                                                class="text-red">{{ $product->crowdfunding->end_at->format('Y-m-d H:i:s') }}</span>
                                            前得到
                                            <span class="text-red">￥{{ $product->crowdfunding->target_amount }}</span>
                                            的支持才可成功.
                                            <!-- Carbon 对象的 diffForHumans() 方法可以计算出与当前时间的相对时间，更人性化 -->
                                            筹款将在<span class="text-red">{{ $product->crowdfunding->end_at->diffForHumans(now()) }}结束!</span>
                                        </div>
                                    @endif
                                </div>
                            @else
                                {{--普通商品模块--}}
                                <div class="price"><label>价格</label><em>￥</em><span>{{ $product->price }}</span></div>
                                <div class="sales_and_reviews">
                                    <div class="sold_count">累计销量 <span class="count">{{ $product->sold_count }}</span>
                                    </div>
                                    <div class="review_count">累计评价 <span
                                            class="count">{{ $product->review_count }}</span>
                                    </div>
                                    <div class="rating" title="评分 {{ $product->rating }}">
                                        评分 <span class="count">
                                    {{ str_repeat('★',floor($product->rating)) }}{{ str_repeat('☆',5 - floor($product->rating)) }}
                                    </span>
                                    </div>
                                </div>
                                {{--普通商品模块结束--}}
                            @endif
                            {{--众筹商品模块结束--}}
                            <div class="skus">
                                <label>选择</label>
                                <div class="btn-group btn-group-toggle" data-toggle="buttons">
                                    @foreach($product->skus as $sku)
                                        <label class="btn sku-btn"
                                               data-price="{{ $sku->price }}"
                                               data-stock="{{ $sku->stock }}"
                                               data-toggle="tooltip"
                                               data-placement="bottom"
                                               title="{{ $sku->title }}">
                                            <input type="radio" name="skus" autocomplete="off"
                                                   value="{{ $sku->id }}">{{ $sku->title }}
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                            <div class="cart_amount">
                                <label>数量
                                    <input class="form-control form-control-sm" name="amount" type="text" value="1">
                                </label>
                                <span>件</span><span class="stock"></span>
                            </div>
                            <div class="buttons">
                                @if($favored)
                                    <button class="btn btn-danger btn-disfavor">取消收藏</button>
                                @else
                                    <button class="btn btn-success btn-favor">❤收藏</button>
                                @endif

                                {{--众筹商品下单按钮开始--}}
                                @if ($product->type === \App\Models\Product::TYPE_CROWDFUNDING)
                                    @if (Auth::check())
                                        @if ($product->crowdfunding->status === \App\Models\CrowdfundingProduct::STATUS_FUNDING)
                                            <button class="btn btn-primary btn-crowdfunding">参加众筹</button>
                                        @else
                                            <button class="btn btn-primary disabled">
                                                {{ \App\Models\CrowdfundingProduct::$statusMap[$product->crowdfunding->status] }}
                                            </button>
                                        @endif
                                    @else
                                        <a href="{{ route('login') }}" class="btn btn-primary">请先登录</a>
                                    @endif
                                @else
                                    <button class="btn btn-primary btn-add-to-cart">加入购物车</button>
                                @endif
                                {{--众筹下单结束--}}
                            </div>
                        </div>
                    </div>
                    <div class="product-detail">
                        <ul class="nav nav-tabs" role="tablist">
                            <li class="nav-item active">
                                <a href="#product-detail-tab" class="nav-link active" aria-controls="product-detail-tab"
                                   role="tab" data-toggle="tab" aria-selected="true">商品详情</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="#product-reviews-tab" aria-controls="product-reviews-tab"
                                   role="tab" data-toggle="tab" aria-selected="false">用户评价</a>
                            </li>
                        </ul>
                        <div class="tab-content">
                            <div role="tabpanel" class="tab-pane active" id="product-detail-tab">
                                {{-- 产品属性开始 --}}
                                <div class="properties-list">
                                    <div class="properties-list-title">产品参数：</div>
                                    <ul class="properties-list-body">
                                        @foreach($product->grouped_properties  as $name => $values)
                                            <li>{{ $name }} : {{ join(' ',$values) }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                                {{-- 产品属性结束 --}}
                                {{-- 在商品描述外面再包一层 --}}
                                <div class="product-description">
                                    {!! $product->description !!}
                                </div>
                            </div>
                            <div role="tabpanel" class="tab-pane" id="product-reviews-tab">
                                {{--评价列表开始--}}
                                <table class="table table-bordered table-striped">
                                    <thead>
                                    <tr>
                                        <td>用户</td>
                                        <td>商品</td>
                                        <td>评分</td>
                                        <td>评价</td>
                                        <td>时间</td>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($reviews as $review)
                                        <tr>
                                            <td>{{ $review->order->user->name }}</td>
                                            <td>{{ $review->productSku->title }}</td>
                                            <td>{{ str_repeat('★',$review->rating) }}{{ str_repeat('☆',5 - $review->rating) }}</td>
                                            <td>{{ $review->review }}</td>
                                            <td>{{ $review->reviewed_at->format('Y-m-d H:i') }}</td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                                {{--评价列表结束--}}
                            </div>
                        </div>
                    </div>
                    {{-- 猜你喜欢开始 --}}
                    @if(count($similar) > 0)
                        <div class="similar-products">
                            <div class="title">猜你喜欢</div>
                            <div class="row products-list">
                                @foreach($similar as $p)
                                    <div class="col-3 product-item">
                                        <div class="product-content">
                                            <div class="top">
                                                <div class="img">
                                                    <a href="{{ route('products.show',['product' => $p->id]) }}"><img
                                                            src="{{ $p->image_url }}" alt=""></a>
                                                </div>
                                                <div class="price"><b>￥</b>{{ $p->price }}</div>
                                                <div class="title">
                                                    <a href="{{ route('products.show',['product' => $p->id]) }}">{{ $p->title }}</a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                    {{-- 猜你喜欢结束 --}}
                </div>
            </div>
        </div>
    </div>
@stop

@section('scriptsAfterJs')
    <script>
        $(function () {
            let token = document.head.querySelector('meta[name="csrf-token"]');

            // 提示工具
            $('[data-toggle="tooltip"]').tooltip({trigger: 'hover'});

            // 给btns绑定点击事件
            $('.sku-btn').click(function () {
                $('.product-info .price span').text($(this).data('price'));
                $('.product-info .stock').text('库存:' + $(this).data('stock') + '件');
            });

            // 收藏按钮的点击事件
            $('.btn-favor').click(function () {
                axios.post('{{ route('products.favor',['product' => $product->id]) }}')
                    .then(function (response) { // 请求成功走这个回调
                        swal('收藏成功', '', 'success')
                            .then(function () {
                                location.reload();
                            })
                    }).catch(function (error) { // 请求失败走这个回调
                    if (error.response && error.response.status === 401) { // 未登陆时的提示
                        swal('请登陆后再重试', '', 'error');
                    } else if (error.response && error.response.msg) {
                        swal(error.response.msg, '', 'error');
                    } else if (error.response && error.response.status === 403) { // 未验证邮箱时的提示
                        swal('请先验证邮箱再进行操作', '', 'error')
                            .then(function () {
                                location.href = '{{ route('verification.notice') }}'
                            })
                    } else {
                        swal('系统错误', '', 'error'); // 这里就是系统的一些内存错误了
                    }
                })
            });

            // 取消收藏的点击事件
            $('.btn-disfavor').click(function () {
                axios.delete('{{ route('products.disfavor',['product' => $product->id]) }}')
                    .then(function () {
                        swal('已取消收藏', '', 'success')
                            .then(function () {
                                location.reload();
                            })
                    })
            });

            // 加入购物车的点击事件
            $('.btn-add-to-cart').click(function () {
                axios.post('{{ route('cart.add') }}', {
                    sku_id: $('label.active input[name=skus]').val(),
                    amount: $('.cart_amount input').val()
                })
                    .then(function () { //请求成功执行此回调
                        swal('加入购物车成功', '', 'success')
                            .then(function () {
                                location.href = '{{ route('cart.index') }}';
                            })
                    }).catch(function (error) {
                    if (error.response.status === 401) {
                        // 401 代表用户未登陆
                        swal('请登陆后重试', '', 'error');

                    } else if (error.response.status === 403) {
                        swal('验证邮箱后重试', '', 'error');
                    } else if (error.response.status === 422) {
                        //402 代表用户表单验证失败
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
                })
            });


            /**
             * 参与众筹 按钮点击事件
             */
            $('.btn-crowdfunding').click(function () {
                // 判断是否选中sku
                if (!$('label.active input[name=skus]').val()) {
                    swal('请先选择商品');
                    return false;
                }
                // 把用户的收货地址以 JSON 的形式放入页面，赋值给 addresses 变量
                var addresses = {!! json_encode(Auth::check() ? Auth::user()->addresses : []) !!};

                var $form = $('<form></form>');
                $form.append(
                    '<div class="form-group row">' +
                    '<label class="col-form-label col-sm-3">选择地址</label>' +
                    '<div class="col-sm-9">' +
                    '<select name="address_id" class="custom-select" "></select>' +
                    '</div>' +
                    '</div>')

                // 循环每个收货地址
                addresses.forEach(function (address) {
                    // 把当前收货地址添加到收货地址的下拉框选择选项中
                    $form.find('select[name=address_id]')
                        .append('<option value=' + address.id + '>' +
                            address.full_address + ' ' + address.contact_name + ' ' + address.contact_phone +
                            '</option>');
                });

                // 在表单中添加一个名为 购买数量 的输入框
                $form.append('<div class="form-group row">' +
                    '<label class="col-form-label col-sm-3" ">购买数量</label>' +
                    '<div class="col-sm-9"><input type="text" name="amount" class="form-control">' +
                    '</div></div>');

                // 弹窗
                swal({
                    title: '参与众筹',
                    width: 600,
                    padding: 100,
                    content: $form[0], // 弹窗的内容就是刚刚创建的表单
                    buttons: ['取消', '确定']
                }).then(function (res) {
                    if (!res) {
                        return false;
                    }

                    var req = {
                        address_id: $form.find('select[name=address_id]').val(),
                        amount: $form.find('input[name=amount]').val(),
                        sku_id: $('label.active input[name=skus]').val()
                    };

                    // 调用众筹下单的接口
                    axios.post('{{ route('crowdfunding_orders.store') }}', req)
                        .then(function (res) {
                            // 订单创建成功, 跳转到订单详情页
                            swal('订单提交成功', '', 'success')
                                .then(() => {
                                    location.href = '/order/' + res.data.id
                                });
                        }).catch(function (error) {
                        if (error.response.status === 422) {
                            var html = '<div>';
                            _.each(error.response.data.errors, function (errors) {
                                _.each(errors, function (error) {
                                    html += error + '<br>';
                                });
                            });
                            html += '</div>';

                            swal({content: $(html)[0], icon: 'error'})
                        } else if (error.response.status === 403) {
                            swal(error.response.data.msg, '', 'error');
                        } else {
                            swal('系统错误', '', 'error')
                        }
                    })
                })
            });

        });
    </script>
@stop
