<?php

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use phpDocumentor\Reflection\Types\Static_;
use Storage;


/**
 * App\Models\Product
 *
 * @property int $id
 * @property string $type
 * @property int|null $category_id
 * @property string $title
 * @property string $description
 * @property string $image
 * @property bool $on_sale
 * @property float $rating
 * @property int $sold_count
 * @property int $review_count
 * @property float $price
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Category|null $category
 * @property-read CrowdfundingProduct $crowdfunding
 * @property-read mixed $grouped_properties
 * @property-read string $image_url
 * @property-read Collection|ProductProperty[] $properties
 * @property-read Collection|ProductSku[] $skus
 * @method static Builder|Product newModelQuery()
 * @method static Builder|Product newQuery()
 * @method static Builder|Product query()
 * @method static Builder|Product whereCategoryId($value)
 * @method static Builder|Product whereCreatedAt($value)
 * @method static Builder|Product whereDescription($value)
 * @method static Builder|Product whereId($value)
 * @method static Builder|Product whereImage($value)
 * @method static Builder|Product whereOnSale($value)
 * @method static Builder|Product wherePrice($value)
 * @method static Builder|Product whereRating($value)
 * @method static Builder|Product whereReviewCount($value)
 * @method static Builder|Product whereSoldCount($value)
 * @method static Builder|Product whereTitle($value)
 * @method static Builder|Product whereType($value)
 * @method static Builder|Product whereUpdatedAt($value)
 * @mixin Eloquent
 */
class Product extends Model
{
    const TYPE_NORMAL = 'normal';
    const TYPE_CROWDFUNDING = 'crowdfunding';

    public static $typeMap = [
        self::TYPE_NORMAL => '普通商品',
        self::TYPE_CROWDFUNDING => '众筹商品',
    ];

    protected $fillable = [
        'title',
        'long_title',
        'description',
        'image',
        'on_sale',
        'rating',
        'sold_count',
        'review_count',
        'price',
        'type',
    ];

    // 强制转换的属性
    protected $casts = [
        'on_sale' => 'boolean',   // 将on_sale强制转换成布尔类型
    ];

    /**
     * 商品的sku
     * @return HasMany
     */
    public function skus()
    {
        return $this->hasMany(ProductSku::class);
    }

    /**
     * 获取图片地址
     * @return string
     */
    public function getImageUrlAttribute()
    {
        if (Str::startsWith($this->attributes['image'], ['http://', 'https://'])) {
            return $this->attributes['image'];
        }

        return Storage::disk('public')->url($this->attributes['image']);
    }

    /**
     * 商品分类
     * @return BelongsTo
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * 众筹商品
     * @return HasOne
     */
    public function crowdfunding()
    {
        return $this->hasOne(CrowdfundingProduct::class);
    }

    /**
     * 商品属性
     * @return HasMany
     */
    public function properties()
    {
        return $this->hasMany(ProductProperty::class);
    }

    /**
     * 将商品属性分组 更好的显示返回
     * @return Collection|\Illuminate\Support\Collection
     */
    public function getGroupedPropertiesAttribute()
    {
        return $this->properties
            ->groupBy('name')
            ->map(function (\Illuminate\Support\Collection $properties) {
                return $properties->pluck("value")->all();
            });
    }

    public function scopeByIds(Builder $query, array $ids)
    {
        return $query->orderByRaw(sprintf("FIND_IN_SET(id,'%s')", join(',', $ids)))
            ->findMany($ids);
    }

    /**
     * 将商品组装成ElasticSearch的格式
     * @return array
     */
    public function toESArray()
    {
        $arr = Arr::only($this->toArray(), [
            'id',
            'type',
            'title',
            'category_id',
            'long_title',
            'on_sale',
            'rating',
            'sold_count',
            'review_count',
            'price',
        ]);
        // 如果商品有类目，则 category 字段为类目名数组，否则为空字符串
        $arr['category'] = $this->category ? explode('-', $this->category->full_name) : '';
        // 类目的path
        $arr['category_path'] = $this->category ? $this->category->path : '';
        $arr['description'] = strip_tags($this->description);
        // 只取出需要的 SKU 字段
        $arr['skus'] = $this->skus->map(function (ProductSku $sku) {
            return Arr::only($sku->toArray(), ['title', 'description', 'price']);
        });

        // 取出需要的商品属性字段
        $arr['properties'] = $this->properties->map(function (ProductProperty $property) {
            return array_merge(Arr::only($property->toArray(), ['name', 'value']), [
                'search_value' => $property->name.':'.$property->value,
            ]);
        });

        return $arr;
    }
}
