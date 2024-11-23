<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReviewCategory extends Model
{
    use SoftDeletes;
    protected $table = 'review_category';

    protected $dates = ['deleted_at'];

    protected $fillable = [
        'review_id',
        'category_id',
        'created_at',
        'updated_at'
    ];


    protected $casts = [
        'review_id' => 'int',
        'category_id' => 'int',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];
}
