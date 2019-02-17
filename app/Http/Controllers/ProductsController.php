<?php

namespace App\Http\Controllers;

use App\Exceptions\InvalidRequestException;
use App\Models\Product;
use App\Models\ProductSku;
use Illuminate\Http\Request;
use PhpParser\Node\Stmt\Throw_;

class ProductsController extends Controller
{
    public function index(Request $request)
    {
        // 创建一个查询构建器
        $builder = Product::query()->where('on_sale',true);

        // 模糊搜索 商品标题 描述  sku标题 sku描述
        if ($search = $request->get('search','')) {
            $like = '%'.$search.'%';
            $builder->where(function ($query) use ($like){
                $query->where('title','like',$like)
                    ->orWhere('description','like',$like)
                    ->orWhereHas('skus',function ($query) use ($like){
                        $query->where('title','like',$like)
                            ->orWhere('description','like',$like);
                    });
            });
        }
        // 是否有提交 order 参数 如果有 就赋值给$order
        if ($order = $request->get('order','')){
            if (preg_match('/^(.+)_(asc|desc)$/',$order,$m)) {
                if (in_array($m[1],['price','sold_count','rating'])){
                    $builder->orderBy($m[1],$m[2]);
                }
            }
        }

        $products = $builder->paginate(16);

        return view('products.index',[
            'products' => $products,
            'filters' => [
                'search' => $search,
                'order' => $order,
            ]
        ]);
    }

    public function show(Product $product, Request $request)
    {
        if (!$product->on_sale) {
            throw new InvalidRequestException('商品未上架');
        }

        return view('products.show',compact('product'));
    }
}
