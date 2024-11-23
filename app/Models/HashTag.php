<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HashTag extends Model
{
    protected $table = 'hash_tags';

    const SHOP_POST = 1;
    const USER_FEED = 10;

    protected $fillable = [
        'tags',
        'created_at',
        'updated_at',
        'is_show',
    ];
}
