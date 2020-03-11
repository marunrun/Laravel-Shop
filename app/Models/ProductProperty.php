<?php

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\ProductProperty
 *
 * @property int $id
 * @property int $product_id
 * @property string $name
 * @property string $value
 * @property string|null $created_at
 * @property string|null $updated_at
 * @property-read \App\Models\Product $product
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductProperty newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductProperty newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductProperty query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductProperty whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductProperty whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductProperty whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductProperty whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductProperty whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductProperty whereValue($value)
 * @mixin \Eloquent
 */
class ProductProperty extends Model
{
    protected $fillable = ['name','value'];

    public $timestamps = false;

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
