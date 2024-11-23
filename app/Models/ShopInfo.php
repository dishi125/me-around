<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShopInfo extends Model
{
    protected $table = "shop_infos";

    protected $fillable = [
        'shop_id',
        'title_1',
        'title_2',
        'title_3',
        'title_4',
        'title_5',
        'title_6',
    ];
}
