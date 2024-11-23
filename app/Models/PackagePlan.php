<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PackagePlan extends Model
{
    protected $table = 'package_plans';
        
    const BRONZE = 1;
    const SILVER = 2;
    const GOLD = 3;
    const PLATINIUM = 4;

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
