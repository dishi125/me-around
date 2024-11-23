<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InstagramCategoryOption extends Model
{

    protected $table = "instagram_category_options";

    protected $fillable = [
        'instagram_category_id',
        'title',
        'price',
        'link',
        'order',
    ];

    public function categoryoption_language()
    {
        return $this->hasMany(InstagramCategoryOptionLanguage::class, 'entity_id', 'id');
    }

}
