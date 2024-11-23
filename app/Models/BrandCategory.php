<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BrandCategory extends Model
{
    protected $table = 'brand_categories';
    protected $fillable = [
        'name', 'sort_order', 'country_code', 'created_at', 'updated_at'
    ];

    public function brands() {
        return $this->hasMany(Brands::class, 'category_id', 'id')->orderBy('sort_order');
    }
}
