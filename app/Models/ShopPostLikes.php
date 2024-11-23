<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShopPostLikes extends Model
{
    protected $table = 'shop_post_likes';

    protected $fillable = [
        'shop_post_id',
        'user_id',
        'shop_id',
        'created_at',
        'updated_at'
    ];
}
