@extends('layouts.app')

@section('title',$product->title);

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
                            <div class="title">{{ $product->title }}</div>
                            <div class="price"><label>价格</label><em>￥</em><span>{{ $product->price }}</span></div>
                            <div class="sales_and_reviews">
                                <div class="sold_count">累计销量 <span class="count">{{ $product->sold_count }}</span></div>
                                <div class="review_count">累计评价 <span class="count">{{ $product->review_count }}</span>
                                </div>
                                <div class="rating" title="评分 {{ $product->rating }}">
                                    评分 <span class="count">
                                    {{ str_repeat('★',floor($product->rating)) }}{{ str_repeat('☆',5 - floor($product->rating)) }}
                                    </span>
                                </div>
                            </div>
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
                                <label>数量</label>
                                <input type="text" name="" class="form-control form-control-sm" value="1">
                                <span>件</span><span class="stock"></span>
                            </div>
                            <div class="buttons">
                                @if($favored)
                                <button class="btn btn-danger btn-disfavor">取消收藏</button>
                                @else
                                <button class="btn btn-success btn-favor">❤收藏</button>
                                @endif
                                <button class="btn btn-primary btn-add-to-cart">加入购物车</button>
                            </div>
                        </div>
                    </div>
                    <div class="product-detail">
                        <ul class="nav nav-tabs" role="tablist">
                            <li class="nav-item">
                                <a href="#product-detail-tab" class="nav-link active" aria-controls="product-detail-tab"
                                   role="tab" data-toggle="tab" aria-selected="true">商品详情</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="#product-reviews-tab" aria-controls="product-reviews-tab"
                                   role="tab" data-toggle="tab" aria-selected="false">用户评价</a>
                            </li>
                        </ul>
                        <div class="tab-content">
                            <div role="tabpanel" class="btn-pane active" id="product-detail-tab">
                                {!! $product->description !!}
                            </div>
                            <div role="tabpanel" class="tab-pane" id="product-reviews-tab"></div>
                        </div>
                    </div>
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
                    }).catch (function(error){ // 请求失败走这个回调
                        if (error.response && error.response.status == 401) { // 未登陆时的提示
                            swal('请登陆后再重试','','error');
                        } else if(error.response && error.response.msg ){
                            swal(error.response.msg,'','error');
                        }else if(error.response && error.response.status == 403){ // 未验证邮箱时的提示
                            swal('请先验证邮箱再进行操作','','error')
                                .then(function () {
                                    location.href = '{{ route('verification.notice') }}'
                                })
                        }else {
                            swal('系统错误','','error'); // 这里就是系统的一些内存错误了
                        }
                })
            });

            // 取消收藏的点击事件
            $('.btn-disfavor').click(function () {
                axios.delete('{{ route('products.disfavor',['product' => $product->id]) }}')
                    .then(function () {
                        swal('已取消收藏','','success')
                            .then(function () {
                                location.reload();
                            })
                    })
            });

            // 加入购物车的点击事件
            $('.btn-add-to-cart').click(function () {
                axios.post('{{ route('cart.add') }}',{
                    sku_id : $('label.active input[name=skus]').val(),
                    amount : $('.cart_amount input').val()
                })
                    .then(function () { //请求成功执行此回调
                        swal('加入购物车成功','','success');
                    }).catch(function (error) {
                        if (error.response.status === 401){
                            // 401 代表用户未登陆
                            swal('请登陆后重试','','error');

                        }else if (error.response.status === 403){
                            swal('验证邮箱后重试','','error');
                        }
                        else if (error.response.status === 422){
                            //402 代表用户表单验证失败
                            var html = '<div>';
                            _.each(error.response.data.errors, function (errors) {
                                _.each(errors, function (error) {
                                    html += error+'<br>';
                                })
                            });
                            html += '</div>';
                            swal({content: $(html)[0], icon: 'error'})
                        } else {
                            swal('系统错误', '', 'error');
                        }
                })
            });

        });
    </script>
@stop