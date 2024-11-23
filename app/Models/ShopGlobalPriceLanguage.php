<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShopGlobalPriceLanguage extends Model
{
    protected $table = 'shop_global_price_languages';

    protected $fillable = [
        'entity_id',
        'entity_type',
        'language_id',
        'name',
        'created_at',
        'updated_at'
    ];

    const CATEGORY = 'category';
    const PRICE = 'price';

}
