@extends('layouts.app')

@section('title','查看订单')

@section('content')
    <div class="row">
        <div class="col-lg-10 offset-lg-1">
            <div class="card">
                <div class="card-header"><h4>订单详情</h4></div>
                <div class="card-body">
                    <table class="table">
                        <thead>
                        <tr>
                            <th>商品信息</th>
                            <th class="text-center">单价</th>
                            <th class="text-center">数量</th>
                            <th class="text-right item-amount">小计</th>
                        </tr>
                        </thead>
                        @foreach($order->items as $index => $item)
                            <tr>
                                <td class="product-info">
                                    <div class="preview">
                                        <a target="_blank" href="{{ route('products.show',[$item->product_id]) }}">
                                            <img src="{{ $item->product->image_url }}" alt="">
                                        </a>
                                    </div>
                                    <div>
                                        <span class="product-title">
                                            <a href="{{ route('products.show',[$item->product_id]) }}">{{ $item->product->title }}</a>
                                        </span>
                                        <span class="sku-title">{{ $item->productSku->title }}</span>
                                    </div>
                                </td>
                                <td class="sku-price text-center vertical-middle">￥{{ $item->price }}</td>
                                <td class="sku-amount text-center vertical-middle">{{ $item->amount }}</td>
                                <td class="item-amount text-right vertical-middle">
                                    ￥{{ number_format($item->price * $item->amount, 2, '.', '') }}</td>
                            </tr>
                        @endforeach
                        <tr>
                            <td colspan="4"></td>
                        </tr>
                    </table>
                    <div class="order-bottom">
                        <div class="order-info">
                            <div class="line">
                                <div class="line-label">收货地址:</div>
                                <div class="line-value">{{ join(' ', $order->address) }}</div>
                            </div>
                            <div class="line">
                                <div class="line-label">收货备注:</div>
                                <div class="line-value">{{ $order->remark ?:'-' }}</div>
                            </div>
                            <div class="line">
                                <div class="line-label">订单编号:</div>
                                <div class="line-value">{{ $order->no }}</div>
                            </div>
                            <div class="line">
                                <div class="line-label">物流状态：</div>
                                <div class="line-value">{{ \App\Models\Order::$shipStatusMap[$order->ship_status] }}</div>
                            </div>
                            @if($order->ship_data)
                                <div class="line">
                                    <div class="line-label">物流信息：</div>
                                    <div class="line-value">{{ $order->ship_data['express_company'] }} {{ $order->ship_data['express_no'] }}</div>
                                </div>
                            @endif
                        </div>
                        <div class="order-summary text-right">
                            <div class="total-amount">
                                <span>订单总价: </span>
                                <div class="value">￥{{ $order->total_amount }}</div>
                            </div>
                            <div>
                                <span>订单状态:</span>
                                <div class="value">
                                    @if($order->paid_at)
                                        @if($order->refund_status === \App\Models\Order::REFUND_STATUS_PENDING)
                                            已支付
                                        @else
                                            {{\App\Models\Order::$refundStatusMap[$order->refund_status]}}
                                        @endif
                                    @elseif($order->closed)
                                        已关闭
                                    @else
                                        未支付
                                    @endif
                                </div>
                            </div>
                            @if(!$order->paid_at && !$order->closed)
                                <div class="payment-buttons">
                                    <a href="{{ route('payment.alipay',['order' => $order->id]) }}" class="btn btn-primary btn-sm">支付宝支付</a>
                                    <a href="{{ route('payment.wechat',['order' => $order->id]) }}" class="btn btn-success btn-sm">微信支付</a>
                                </div>
                            @endif
                            @if($order->ship_status === \App\Models\Order::SHIP_STATUS_DELIVERED)
                                <div class="receive-button">
                                    <button type="button" id="btn-receive" class="btn btn-sm btn-success">确认收货</button>
                                </div>
                            @endif
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
       $('#btn-receive').click(function () {
           // 弹出确认框
           swal({
               title:'确认已经收到商品？',
               icon: 'warning',
               dangerMode: true,
               buttons: ['取消','确认收到']
           })
           .then(function (ret) {
               if (!ret){
                   return;
               }
               // ajax
               axios.post('{{ route('orders.received',[$order->id]) }}')
                   .then(function () {
                       location.reload();
                   })
           })
       }) 
    });
</script>
@stop