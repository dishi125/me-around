<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;
use App\Models\ShopPrices;

class ShopPriceCategory extends Model
{
    use SoftDeletes;
    protected $table = 'shop_price_category';

    protected $dates = ['deleted_at'];

    protected $fillable = [
        'shop_id',
        'name',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'shop_id' => 'int',
        'name' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];
    protected $appends = ['shop_name','shop_mobile_number','shop_items'];

    public function getShopNameAttribute()
    {
        $value = $this->attributes['shop_id'];
        $shop = Shop::find($value);
        return $this->attributes['shop_name'] = $shop->shop_name;
    }
    public function getShopMobileNumberAttribute()
    {
        $value = $this->attributes['shop_id'];
        $shop = Shop::find($value);
        return $this->attributes['shop_mobile_number'] = $shop->mobile;
    }

    public function getShopItemsAttribute()
    {
        $value = $this->attributes['id'];
        $shop_items = ShopPrices::where('shop_price_category_id', $value)->with('images')->get();
        return $this->attributes['shop_items'] = $shop_items;
        
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

}
