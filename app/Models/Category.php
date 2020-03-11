<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Category
 *
 * @property int $id
 * @property string $name 名称
 * @property int|null $parent_id 上级id
 * @property bool $is_directory 是否拥有子类目
 * @property int $level 当前层级
 * @property string $path 该类目的所有父级
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Category[] $children
 * @property-read \Illuminate\Support\Collection $ancestors
 * @property-read string $full_name
 * @property-read array $path_ids
 * @property-read \App\Models\Category|null $parent
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Product[] $products
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Category newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Category newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Category query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Category whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Category whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Category whereIsDirectory($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Category whereLevel($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Category whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Category whereParentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Category wherePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Category whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Category extends Model
{
    protected $fillable = ['name', 'is_directory', 'level', 'path'];

    protected $casts = [
        'is_directory' => 'boolean',
    ];

    /**
     * The "booting" method of the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function (Category $category) {
            // 如果创建的是根目录
            if (is_null($category->parent_id)) {
                $category->level = 0;
                $category->path = '-';
            } else {
                // 将层级 设为父级的层级 +1
                $category->level = $category->parent->level + 1;
                // path 值 设为父类的 path 追加父类的 ID 以及最后追加一个 -
                $category->path = $category->parent->path . $category->parent_id . '-';
            }
        });
    }

    public function parent()
    {
        return $this->belongsTo(Category::class);
    }

    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    /**
     * 获取到所有的父类id.
     *
     * @return array
     */
    public function getPathIdsAttribute()
    {
        return array_filter(explode('-', trim($this->path, '-')));
    }


    /**
     * 获取所有的祖先分类,按层级排序
     *
     * @return \Illuminate\Support\Collection
     */
    public function getAncestorsAttribute()
    {

        return $this->path_ids ? Category::query()
            ->whereIn('id', $this->path_ids)
            ->orderBy('level')
            ->get() : collect([]);
    }

    /**
     * 获取以 - 为分隔的所有祖先类目名称以及当前类目的名称
     * @return string
     */
    public function getFullNameAttribute()
    {
        return $this->ancestors
            ->pluck('name')
            ->push($this->name)
            ->implode('-');
    }
}
