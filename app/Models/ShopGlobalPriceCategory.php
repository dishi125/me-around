<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShopGlobalPriceCategory extends Model
{
    protected $table = 'shop_global_price_categories';

    protected $fillable = [
        'name',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'name' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function globalprice()
    {
        return $this->hasMany(ShopGlobalPrice::class, 'shop_global_price_category_id', 'id')->orderBy('created_at', 'ASC');
    }

    public function category_languages() {
        return $this->hasMany(ShopGlobalPriceLanguage::class, 'entity_id', 'id')->where('entity_type',ShopGlobalPriceLanguage::CATEGORY);
    }

    public function prices() {
        return $this->hasMany(ShopGlobalPrice::class, 'shop_global_price_category_id', 'id');
    }

    public function getKoreanNameAttribute()
    {
        $korean_name = ShopGlobalPriceLanguage::where('entity_id', $this->attributes['id'])->where('entity_type',ShopGlobalPriceLanguage::CATEGORY)->where('language_id', 1)->pluck('name')->first();
        return $korean_name;
    }
}
