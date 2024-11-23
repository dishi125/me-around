<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\CategoryTypes;
use App\Models\Status;
use Illuminate\Support\Facades\Storage;

class CategoryLanguage extends Model
{
    protected $table = 'category_languages';
    
    protected $fillable = [
        'name','category_id','post_language_id','created_at','updated_at'
    ];

    protected $casts = [
        'name' => 'string',
        'category_id' => 'int',
        'post_language_id' => 'int',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];
}
