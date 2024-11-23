<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BrandCategoryLanguage extends Model
{
    protected $table = 'brand_category_languages';
    protected $fillable = [
        'brand_category_id', 'post_language_id', 'name', 'created_at', 'updated_at'
    ];
}
