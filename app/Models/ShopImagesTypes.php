<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShopImagesTypes extends Model
{
    protected $table = 'shop_image_types';

    const THUMB = 1;
    const MAINPROFILE = 2;
    const WORKPLACE = 3;
    const PORTFOLIO = 4;
    protected $fillable = [
        'name'
    ];

    protected $casts = [
        'id' => 'int',
        'name' => 'string',        
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
