<?php

namespace App\Http\Controllers;

use App\Exceptions\InvalidRequestException;
use App\Models\Category;
use App\Models\OrderItem;
use App\Models\Product;
use App\Services\CategoryService;
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
        $params = [
            'index' => 'products',
            'body' => [
                'from' => ($page - 1) * $perPage,
                'size' => $perPage,
                'query' => [
                    'bool' => [
                        'filter' => [
                            ['term' => ['on_sale' => true]],
                        ],
                    ],
                ],
            ],
        ];
        // 是否有提交 order 参数，如果有就赋值给 $order 变量
        // order 参数用来控制商品的排序规则
        if ($order = $request->input('order', '')) {
            // 是否是以 _asc 或者 _desc 结尾
            if (preg_match('/^(.+)_(asc|desc)$/', $order, $m)) {
                // 如果字符串的开头是这 3 个字符串之一，说明是一个合法的排序值
                if (in_array($m[1], ['price', 'sold_count', 'rating'])) {
                    // 根据传入的排序值来构造排序参数
                    $params['body']['sort'] = [[$m[1] => $m[2]]];
                }
            }
        }

        if ($request->input('category_id') && $category = Category::find($request->input('category_id'))) {
            if ($category->is_directory) {
                // 如果是父类目 使用category_path筛选
                $params['body']['query']['bool']['filter'][] = [
                    'prefix' => ['category_path' => $category->path.$category->id.'-'],
                ];
            } else {
                // 否则通过 category_id来筛选
                $params['body']['query']['bool']['filter'][] = [
                    'term' => ['category_id' => $category->id],
                ];
            }
        }

        if ($search = $request->input('search', '')) {
            // 将搜索词根据空格拆分成数组，并过滤掉空项
            $keywords = array_filter(explode(' ', $search));
            $params['body']['query']['bool']['must'] = [];
            foreach ($keywords as $keyword) {
                $params['body']['query']['bool']['must'][] = [
                    [
                        'multi_match' => [
                            'query' => $keyword,
                            'fields' => [
                                'title^2',
                                'long_title^2',
                                'category^2',
                                'description',
                                'skus_title',
                                'skus_description',
                                'properties_value',
                            ],
                        ],
                    ],
                ];
            }

        }

        $res = app('es')->search($params);
        $productsIds = collect($res['hits']['hits'])->pluck('_id')->all();
        $products = Product::query()->orderByRaw(sprintf("FIND_IN_SET(id,'%s')",
            join(',', $productsIds)))->findMany($productsIds);
        $paper = new LengthAwarePaginator($products, $res['hits']['total']['value'], $perPage, $page, [
            'path' => route('products.index', false),
        ]);

        return view('products.index', [
            'products' => $paper,
            'filters' => [
                'search' => $search,
                'order' => $order,
            ],
            'category' => $category ?? null,
//            'categoryTree' => $categoryService->getCategoryTree(),
        ]);


        // 创建一个查询构建器
        $builder = Product::query()->where('on_sale', true);

        // 模糊搜索 商品标题 描述  sku标题 sku描述
        if ($search = $request->get('search', '')) {
            $like = '%'.$search.'%';
            $builder->where(function (Builder $query) use ($like) {
                $query->where('title', 'like', $like)
                    ->orWhere('description', 'like', $like)
                    ->orWhereHas('skus', function (Builder $query) use ($like) {
                        $query->where('title', 'like', $like)
                            ->orWhere('description', 'like', $like);
                    });
            });
        }

        if ($request->input('category_id') && $category = Category::find($request->input('category_id'))) {
            // 如果这是一个父类目
            if ($category->is_directory) {
                // 则筛选出该类目下所有子类目的商品
                $builder->whereHas('category', function (Builder $query) use ($category) {
                    $query->where('path', 'like', $category->path.$category->id."-%");
                });
            } else {
                // 如果这不是一个父类目
                $builder->where('category_id', $category->id);
            }
        }


        // 是否有提交 order 参数 如果有 就赋值给$order
        if ($order = $request->get('order', '')) {
            if (preg_match('/^(.+)_(asc|desc)$/', $order, $m)) {
                if (in_array($m[1], ['price', 'sold_count', 'rating'])) {
                    $builder->orderBy($m[1], $m[2]);
                }
            }
        }

        $products = $builder->paginate(16);

        return view('products.index', [
            'products' => $products,
            'filters' => [
                'search' => $search,
                'order' => $order,
            ],
            'category' => $category ?? null,
            'categoryTree' => $categoryService->getCategoryTree(),
        ]);
    }

    /** 商品详情页面
     * @param  Product  $product
     * @param  Request  $request
     *
     * @return Factory|View
     *
     * @throws InvalidRequestException
     */
    public function show(Product $product, Request $request)
    {
        if (!$product->on_sale) {
            throw new InvalidRequestException('商品未上架');
        }

        $favored = false;
        if ($user = $request->user()) {
            $favored = boolval($user->favoriteProducts()->find($product->id));
        }

        $reviews = OrderItem::query()
            ->with(['order.user', 'productSku'])
            ->whereProductId($product->id)
            ->whereNotNull('reviewed_at')// 筛选出已评价的
            ->orderBy('reviewed_at', 'desc')
            ->limit(10)
            ->get();

        return view('products.show', compact('product', 'favored', 'reviews'));
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
