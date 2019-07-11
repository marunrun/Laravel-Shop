<?php

namespace App\Http\Controllers;

use App\Exceptions\InvalidRequestException;
use App\Models\Category;
use App\Models\OrderItem;
use App\Models\Product;
use App\Services\CategoryService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class ProductsController extends Controller
{
    /** 首页
     * @param Request $request
     *
     * @param CategoryService $categoryService
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index(Request $request, CategoryService $categoryService)
    {
        $request->user();

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
            'products'     => $products,
            'filters'      => [
                'search' => $search,
                'order'  => $order,
            ],
            'category'     => $category ?? null,
            'categoryTree' => $categoryService->getCategoryTree(),
        ]);
    }

    /** 商品详情页面
     * @param Product $product
     * @param Request $request
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
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
     * @param Product $product
     * @param Request $request
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
     * @param Product $product
     * @param Request $request
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
     * @param Request $request
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function favorites(Request $request)
    {
        $products = $request->user()->favoriteProducts()->paginate(16);

        return view('products.favorites', compact('products'));
    }
}
