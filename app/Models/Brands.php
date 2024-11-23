<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Brands extends Model
{
    protected $table = 'brands';
    protected $fillable = [
        'name', 'sort_order', 'brand_logo', 'category_id', 'created_at', 'updated_at'
    ];

    protected $appends = ['brand_logo_url'];

    public function getBrandLogoUrlAttribute()
    {
        $value = $this->attributes['brand_logo'];

        if (empty($value)) {
            return $this->attributes['brand_logo_url'] = '';
        } else {
            if (!filter_var($value, FILTER_VALIDATE_URL)) {
                if(Storage::disk('s3')->has($value)){
                    return $this->attributes['brand_logo_url'] = Storage::disk('s3')->url($value);
                }else{
                    return $this->attributes['brand_logo'] = '';
                    return $this->attributes['brand_logo_url'] = '';
                }
            } else {
                return $this->attributes['brand_logo_url'] = '';
            }
        }
    }
}
