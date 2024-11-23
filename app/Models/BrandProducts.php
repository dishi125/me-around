<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class BrandProducts extends Model
{
    protected $table = 'brand_products';
    protected $fillable = [
        'name', 'coin_amount', 'sort_order', 'product_image', 'brand_id', 'created_at', 'updated_at'
    ];

    protected $appends = ['product_image_url'];

    public function getProductImageUrlAttribute()
    {
        $value = $this->attributes['product_image'] ?? '';

        if (empty($value)) {
            return $this->attributes['product_image_url'] = '';
        } else {
            if(Storage::disk('s3')->has($value)){
                return $this->attributes['product_image_url'] = Storage::disk('s3')->url($value);
            }else{
                return $this->attributes['product_image'] = '';
                return $this->attributes['product_image_url'] = '';
            }
        }
    }
}
