<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeletedShopPost extends Model
{
    protected $table = 'deleted_shop_posts';

    protected $fillable = [
        'user_id',
        'shop_post_id',
    ];

}
