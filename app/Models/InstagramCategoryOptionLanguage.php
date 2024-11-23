<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InstagramCategoryOptionLanguage extends Model
{
    protected $table = "instagram_category_option_languages";

    protected $fillable = [
        'entity_id',
        'language_id',
        'title',
        'price',
        'link',
    ];

}
