<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShopGlobalPrice extends Model
{
    protected $table = 'shop_global_prices';

    protected $fillable = [
        'shop_global_price_category_id',
        'name',
        'price',
        'discount',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'shop_global_price_category_id' => 'int',
        'name' => 'string',
        'price' => 'decimal:2',
        'discount' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function price_languages() {
        return $this->hasMany(ShopGlobalPriceLanguage::class, 'entity_id', 'id')->where('entity_type',ShopGlobalPriceLanguage::PRICE);
    }

    public function getKoreanNameAttribute()
    {
        $korean_name = ShopGlobalPriceLanguage::where('entity_id', $this->attributes['id'])->where('entity_type',ShopGlobalPriceLanguage::PRICE)->where('language_id', 1)->pluck('name')->first();
        return $korean_name;
    }
}
