@extends('layouts.app')

@section('title','商品列表')

@section('content')
    <div class="row">
        <div class="col-lg-10 offset-lg-1">
            <div class="card">
                <div class="card-body">
                    {{--筛选组件开始--}}
                    <form action="{{ route('products.index') }}" class="search-form">
                        {{-- 一个隐藏字段 --}}
                        <input type="hidden" name="filters">
                        <div class="form-row">
                            <div class="col-md-9">
                                <div class="form-row">
                                    {{-- 面包屑开始 --}}
                                    <div class="col-auto category-breadcrumb">
                                        {{-- 全部的链接,直接跳转到全部的商品列表 --}}
                                        <a href="{{ route('products.index') }}" class="all-products">全部</a> &gt;
                                        {{-- 如果当前是通过类目筛选的 --}}
                                        @if($category)
                                            {{-- 遍历所有的祖先类目 --}}
                                            @foreach($category->ancestors as $ancestor)
                                                {{-- 添加名为祖先类目的链接 --}}
                                                <span class="category">
                                                    <a href="{{ route('products.index',['category_id' => $ancestor->id]) }}">
                                                        {{ $ancestor->name }}
                                                    </a>
                                                </span>
                                                <span>&gt;</span>
                                            @endforeach
                                            {{-- 最后展示当前类目名称 --}}
                                            <span class="category">{{ $category->name }}</span>
                                            <span>&gt;</span>
                                            <input type="hidden" name="category_id" value="{{ $category->id }}">
                                        @endif

                                        {{-- 商品属性面包屑开始 --}}
                                        @foreach($propertyFilters as $name => $value)
                                            <span class="filter">{{ $name }}:
                                                <span class="filter-value">{{ $value }}</span>
                                                {{-- 调用自定义的 removeFilterFromQuery --}}
                                                <a class="remove-filter" href="javascript:  removeFilterFromQuery('{{ $name }}')">×</a>
                                            </span>
                                        @endforeach

                                    </div>
                                    {{-- 面包屑结束 --}}
                                    <div class="col-auto">
                                        <input type="text" class="form-control form-control-sm" name="search"
                                               placeholder="搜索">
                                    </div>
                                    <div class="col-auto">
                                        <button class="btn btn-primary btn-sm">搜索</button>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <select name="order" class="form-control form-control-sm float-right">
                                    <option value="">排序方式</option>
                                    <option value="price_desc">价格从高到低</option>
                                    <option value="price_asc">价格从低到高</option>
                                    <option value="sold_count_desc">销量从高到低</option>
                                    <option value="sold_count_asc">销量从低到高</option>
                                    <option value="rating_desc">评价从高到低</option>
                                    <option value="rating_asc">评价从低到高</option>
                                </select>
                            </div>
                        </div>
                    </form>
                    {{--筛选组件完成--}}

                    {{-- 展示子类目开始 --}}
                    <div class="filters">
                        {{-- 如果当前是通过类目筛选,并且此类目是一个父类目 --}}
                        @if($category && $category->is_directory)
                            <div class="row">
                                <div class="col-3 filter-key">子类目:</div>
                                <div class="col-9 filter-values">
                                    {{-- 遍历直接子类目 --}}
                                    @foreach($category->children as $child)
                                        <a href="{{ route('products.index',['category_id' => $child->id]) }}">{{ $child->name }}</a>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                        {{-- 分面搜索 --}}
                        @foreach($properties as $property)
                            <div class="row">
                                {{--  输出属性名 --}}
                                <div class="col-3 filter-key"> {{ $property['key'] }}:</div>
                                <div class="col-9 filter-values">
                                    {{-- 遍历属性值 --}}
                                    @foreach($property['values'] as $value)
                                        <a href="javascript: appendFilterToQuery('{{ $property['key'] }}','{{ $value }}')">{{ $value }}</a>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                        {{-- 分面搜索结束 --}}
                    </div>
                    {{--展示子类目结束--}}

                    <div class="row products-list">
                        @foreach($products as $product)
                            <div class="col-3 product-item">
                                <div class="product-content">
                                    <div class="top">
                                        <div class="img">
                                            <a href="{{ route('products.show',['product' => $product->id]) }}"><img
                                                    src="{{ $product->image_url }}" alt=""></a>
                                        </div>
                                        <div class="price"><b>￥</b>{{ $product->price }}</div>
                                        <div class="title"><a
                                                href="{{ route('products.show',['product' => $product->id]) }}">{{ $product->title }}</a>
                                        </div>
                                    </div>
                                    <div class="bottom">
                                        <div class="sold_count">销量 <span>{{ $product->sold_count }}笔</span></div>
                                        <div class="review_count">评价 <span>{{ $product->review_count }}条</span></div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="float-right">{{ $products->appends($filters)->render() }}</div>
                </div>
            </div>
        </div>
    </div>
@stop

@section('scriptsAfterJs')
    <script>
        var filters = {!! json_encode($filters) !!};
        $(document).ready(function () {
            $('.search-form input[name=search]').val(filters.search);
            $('.search-form select[name=order]').val(filters.order);

            $('.search-form select[name=order]').change(function () {

                let searches = parseSearch();

                if(searches['filters']) {
                    $('.search-form input[name=filters]').val(searches['filters']);
                }
                $('.search-form').submit();
            });
        });


        // 定义一个函数，用于解析当前 Url 里的参数，并以 Key-Value 对象形式返回
        function parseSearch() {
            // 初始化空对象
            let searches = {};
            // location.search 会返回 Url 中 ? 以及后面的查询参数
            // substr(1) 将 ? 去除，然后以符号 & 分割成数组，然后遍历这个数组
            location.search.substr(1).split('&').forEach(function (str) {
                let res = str.split('=');
                searches[decodeURIComponent(res[0])] = decodeURIComponent(res[1]);
            });

            return searches;
        }

        // 根据 key value 构建查询参数
        function buildSearch(searches) {
            let query = '?';
            _.forEach(searches, function (value, key) {
                query += encodeURIComponent(key) + '=' + encodeURIComponent(value) + '&';
            });

            return query.substr(0, query.length - 1);
        }

        function appendFilterToQuery(name, value) {
            // 解析Url 参数
            let searches = parseSearch();
            // 如果已经有了 filters 查询
            if (searches['filters']) {
                // 就在原有的filers参数后面追加
                searches['filters'] += '|' + name + ':' + value;
            } else {
                // 初始化
                searches['filters'] = name + ':' + value;
            }
            // 重新构建查询参数 并自动跳转
            location.search = buildSearch(searches);
        }

        // 移除筛选
        function removeFilterFromQuery(name) {
            let searches = parseSearch();

            // 如果没有 filters 查询则什么都不用做
            if (!searches['filters']) {
                return;
            }
            let filters = [];
            searches['filters'].split('|').forEach(function (filter) {
                // 解析出属性名和属性值
                let result = filter.split(':');
                // 如果当前属性名与要移除的属性名一致，则退出
                if (result[0] === name) {
                    return;
                }
                // 否则将这个filter放入之前初始化的数组中
                filters.push(filter)
            });

            // 重新给filters赋值
            searches['filters'] = filters.join('|');
            location.search = buildSearch(searches);
        }
    </script>
@stop
