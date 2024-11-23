<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\SoftDeletes;

class ShopDiscountCondition extends Model
{
    protected $table = 'shop_discount_conditions';

    protected $fillable = [
        'shop_id','discount_condition_id','created_at','updated_at'
    ];

    protected $casts = [
        'shop_id' => 'int',
        'discount_condition_id' => 'int',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];
}
