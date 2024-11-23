<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShopBlockHistory extends Model
{
    protected $table = 'shop_block_histories';

    protected $fillable = [
        'user_id',
        'shop_id',
        'is_block',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'user_id' => 'int',
        'shop_id' => 'int',
        'is_block' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];
}
