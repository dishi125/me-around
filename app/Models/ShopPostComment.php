<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShopPostComment extends Model
{
    protected $table = "shop_post_comments";

    protected $fillable = [
        'shop_post_id',
        'user_id',
        'comment',
    ];
}
