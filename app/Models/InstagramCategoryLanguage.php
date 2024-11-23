<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InstagramCategoryLanguage extends Model
{
    protected $table = "instagram_category_languages";

    protected $fillable = [
      'entity_id',
      'entity_type',
      'language_id',
      'value',
    ];

    const CATEGORY = 'insta_category_name';
    const SUB_TITLE = 'insta_category_sub_title';

}
