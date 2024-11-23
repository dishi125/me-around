<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InstagramCategory extends Model
{
    use SoftDeletes;
    protected $table = "instagram_categories";

    protected $fillable = [
      'title',
      'sub_title',
      'order',
    ];

    public function categoryoption()
    {
        return $this->hasMany(InstagramCategoryOption::class, 'instagram_category_id', 'id')->orderBy('order','ASC');
    }

}
