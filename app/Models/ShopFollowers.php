<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ShopFollowers extends Model
{
    use SoftDeletes;
    protected $table = 'shop_followers';

    protected $dates = ['deleted_at'];

    protected $fillable = [
        'shop_id',
        'user_id',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'shop_id' => 'int',
        'user_id' => 'int',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];
}
