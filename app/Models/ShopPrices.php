<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;
use App\Models\ShopPriceCategory;

class ShopPrices extends Model
{
    use SoftDeletes;
    protected $table = 'shop_prices';

    protected $dates = ['deleted_at'];

    protected $fillable = [
        'shop_price_category_id',
        'name',
        'price',
        'discount',
        'created_at',
        'updated_at',
        'main_price_display'
    ];

    protected $casts = [
        'shop_price_category_id' => 'int',
        'name' => 'string',
        'price' => 'decimal:2',
        'discount' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    protected $appends = ['shop_price_category_name'];

    public function getShopPriceCategoryNameAttribute()
    {
        $value = $this->attributes['shop_price_category_id'];
        $shopPriceCategory = ShopPriceCategory::find($value);
        return $this->attributes['shop_price_category_name'] = $shopPriceCategory->name;
    }

    public function getPriceAttribute($price)
    {
        return number_format($price,0);
    }

    public function getDiscountAttribute($discount)
    {
        return number_format($discount,0);
    }

    public function getCreatedAtAttribute($date)
    {
        $date = new Carbon($date);
        return $date->format('d-m-Y H:i');
    }

    public function getUpdatedAtAttribute($date)
    {
        $date = new Carbon($date);
        return $date->format('d-m-Y H:i');
    }

    public function images() {
        return $this->hasMany(ShopPriceImages::class, 'shop_price_id', 'id')->orderBy('order','DESC');
    }

}
