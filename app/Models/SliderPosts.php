<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SliderPosts extends Model
{
    use SoftDeletes;
    protected $table = 'slider_posts';

    protected $dates = ['deleted_at'];

    const HOME = 'home';
    const TOP = 'top';

    protected $fillable = [
        'section','entity_type_id','category_id','post_id','created_at','updated_at'
    ];
    
    protected $casts = [
        'section' => 'string',
        'entity_type_id' => 'int',
        'category_id' => 'int',
        'post_id' => 'int',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];
}
