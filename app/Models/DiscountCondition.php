<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\SoftDeletes;

class DiscountCondition extends Model
{
    protected $table = 'discount_conditions';

    protected $fillable = [
        'title','created_at','updated_at'
    ];

    protected $casts = [
        'title' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];
}
