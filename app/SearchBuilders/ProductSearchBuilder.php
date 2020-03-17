<?php


namespace App\SearchBuilders;


use App\Models\Category;

class ProductSearchBuilder extends BaseBuilder
{
    protected $index = 'products';


    /**
     * 筛选上架商品
     * @return $this
     */
    public function onSale()
    {
        $this->appendFilter(['term' => ['on_sale' => true]]);

        return $this;
    }

    /**
     * 按照商品类目筛选
     * @param  Category  $category
     * @return $this
     */
    public function category(Category $category)
    {

        // 如果是顶级目录 就通过category_path前缀来搜索
        if ($category->is_directory) {
            $this->appendFilter([
                'prefix' => ['category_path' => $category->path.$category->id.'-'],
            ]);
        } else {
            // 否则就是按照id来搜索了
            $this->appendFilter([
                'term' => ['category_id' => true],
            ]);
        }

        return $this;
    }

    /**
     * 关键字搜索
     * @param $keywords
     * @return $this
     */
    public function keywords($keywords)
    {
        // 如果参数不是数组则转为数组
        $keywords = is_array($keywords) ? $keywords : [$keywords];
        foreach ($keywords as $keyword) {
            $this->appendMust([
                'multi_match' => [
                    'query' => $keyword,
                    'fields' => [
                        'title^3',
                        'long_title^2',
                        'category^2',
                        'description',
                        'skus_title',
                        'skus_description',
                        'properties_value',
                    ],
                ],
            ]);
        }

        return $this;
    }

    /**
     * 分面搜索
     * @return $this
     */
    public function aggregateProperties()
    {
        return $this->createAggregate([
            'properties' => [
                'nested' => [
                    'path' => 'properties',
                ],
                'aggs' => [
                    'properties' => [
                        'terms' => [
                            'field' => 'properties.name',
                        ],
                        'aggs' => [
                            'value' => [
                                'terms' => [
                                    'field' => 'properties.value',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);
    }


    /**
     * 按商品属性筛选
     * @param $name
     * @param $value
     * @param  string  $type
     * @return ProductSearchBuilder
     */
    public function propertyFilter($name, $value, $type = 'filter')
    {
        $typeName = 'append'.ucfirst($type);

        return $this->$typeName([
            'nested' => [
                'path' => 'properties',
                'query' => [
                    ['term' => ['properties.search_value' => $name.':'.$value]],
                ],
            ],
        ]);
    }
}
