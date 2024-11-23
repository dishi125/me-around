<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BigCategoryLanguage extends Model
{
    use SoftDeletes;
    protected $table = 'big_category_languages';

    protected $fillable = [
        'name','big_category_id','post_language_id','created_at','updated_at'
    ];

    protected $casts = [
        'name' => 'string',
        'big_category_id' => 'int',
        'post_language_id' => 'int',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    protected $dates = ['deleted_at'];

}
