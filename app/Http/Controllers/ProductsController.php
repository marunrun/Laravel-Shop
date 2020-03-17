<?php

namespace App\Http\Controllers;

use App\Exceptions\InvalidRequestException;
use App\Models\Category;
use App\Models\OrderItem;
use App\Models\Product;
use App\SearchBuilders\ProductSearchBuilder;
use App\Services\CategoryService;
use App\Services\ProductService;
use Illuminate\Contracts\View\Factory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;

class ProductsController extends Controller
{
    /** 首页
     * @param  Request  $request
     *
     * @param  CategoryService  $categoryService
     * @return Factory|View
     */
    public function index(Request $request, CategoryService $categoryService)
    {
        $page = $request->input('page', 1);
        $perPage = 16;

        // 默认查询上架商品 并且设置分页
        $builder = (new ProductSearchBuilder())->onSale()->paginate($perPage, $page);

        // order 参数用来控制商品的排序规则
        if ($order = $request->input('order', '')) {
            // 是否是以 _asc 或者 _desc 结尾
            if (preg_match('/^(.+)_(asc|desc)$/', $order, $m)) {
                // 如果字符串的开头是这 3 个字符串之一，说明是一个合法的排序值
                if (in_array($m[1], ['price', 'sold_count', 'rating'])) {
                    // 根据传入的排序值来构造排序参数
                    $params['body']['sort'] = [[$m[1] => $m[2]]];
                    $builder->orderBy($m[1], $m[2]);
                }
            }
        }

        if ($request->input('category_id') && $category = Category::find($request->input('category_id'))) {
            $builder->category($category);
        }

        if ($search = $request->input('search', '')) {
            // 将搜索词根据空格拆分成数组，并过滤掉空项
            $keywords = array_filter(explode(' ', $search));
            $builder->keywords($keywords);
        }

        // 分面搜索
        if ($search || isset($category)) {
            $builder->aggregateProperties();
        }

        $propertyFilters = [];
        // 商品属性筛选
        if ($filterString = $request->input('filters')) {
            $filterArr = explode('|', $filterString);
            foreach ($filterArr as $filter) {
                [$name, $value] = explode(':', $filter);
                $propertyFilters[$name] = $value;
                $builder->propertyFilter($name, $value);
            }
        }


        $res = app('es')->search($builder->getParams());
        $productsIds = collect($res['hits']['hits'])->pluck('_id')->all();
        $products = Product::query()->byIds($productsIds);
        $paper = new LengthAwarePaginator($products, $res['hits']['total']['value'], $perPage, $page, [
            'path' => route('products.index', false),
        ]);

        $properties = [];
        if (isset($res['aggregations'])) {
            $properties = collect($res['aggregations']['properties']['properties']['buckets'])
                ->map(function ($bucket) {
                    return [
                        'key' => $bucket['key'],
                        'values' => collect($bucket['value']['buckets'])->pluck('key')->all(),
                    ];
                })->filter(function ($property) use ($propertyFilters) {
                    return count($property['values']) > 1 && !isset($propertyFilters[$property['key']]);
                });

        }

        return view('products.index', [
            'products' => $paper,
            'filters' => [
                'search' => $search,
                'order' => $order,
            ],
            'category' => $category ?? null,
            'properties' => $properties,
            'propertyFilters' => $propertyFilters,
        ]);
    }

    /** 商品详情页面
     * @param  Product  $product
     * @param  Request  $request
     *
     * @param  ProductService  $productService
     * @return Factory|View
     *
     * @throws InvalidRequestException
     */
    public function show(Product $product, Request $request, ProductService $productService)
    {
        if (!$product->on_sale) {
            throw new InvalidRequestException('商品未上架');
        }

        $favored = false;
        if ($user = $request->user()) {
            $favored = boolval($user->favoriteProducts()->find($product->id));
        }

        // 获取相似商品
        $productIds = $productService->getSimilarProductIds($product, 4);
        // 从数据库中读取商品数据
        $similar = Product::query()
            ->byIds($productIds);

        $reviews = OrderItem::query()
            ->with(['order.user', 'productSku'])
            ->whereProductId($product->id)
            ->whereNotNull('reviewed_at')// 筛选出已评价的
            ->orderBy('reviewed_at', 'desc')
            ->limit(10)
            ->get();

        return view('products.show', compact('product', 'favored', 'reviews', 'similar'));
    }

    /** 收藏商品
     * @param  Product  $product
     * @param  Request  $request
     *
     * @return array
     */
    public function favor(Product $product, Request $request)
    {
        $user = $request->user();
        if ($user->favoriteProducts()->find($product->id)) {
            return [];
        }

        $user->favoriteProducts()->attach($product);

        return [];
    }

    /**取消收藏
     * @param  Product  $product
     * @param  Request  $request
     * @return array
     */
    public function disfavor(Product $product, Request $request)
    {
        $user = $request->user();

        $user->favoriteProducts()->detach($product);

        return [];
    }

    /**
     *  我的收藏列表.
     *
     * @param  Request  $request
     *
     * @return Factory|View
     */
    public function favorites(Request $request)
    {
        $products = $request->user()->favoriteProducts()->paginate(16);

        return view('products.favorites', compact('products'));
    }
}
