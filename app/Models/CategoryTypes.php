<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CategoryTypes extends Model
{
    protected $table = 'category_types';
        
    const SHOP = 1;
    const HOSPITAL = 2;
    const COMMUNITY = 3;
    const REPORT = 4;
    const CUSTOM = 5;
    const CUSTOM2 = 6;
    const SHOP2 = 7;

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
