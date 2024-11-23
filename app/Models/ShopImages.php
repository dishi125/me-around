<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class ShopImages extends Model
{
    use SoftDeletes;
    protected $table = 'shop_images';

    protected $dates = ['deleted_at'];

    protected $fillable = [
        'shop_id',
        'shop_image_type',
        'image',
        'created_at',
        'updated_at'
    ];

    protected $appends = ['thumb'];

    protected $casts = [
        'shop_id' => 'int',
        'shop_image_type' => 'string',
        'image' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function getImageAttribute()
    {
        $value = $this->attributes['image'];
        if (empty($value)) {
            return $this->attributes['image'] = '';
        } else {
            if (!filter_var($value, FILTER_VALIDATE_URL)) {
                return $this->attributes['image'] = Storage::disk('s3')->url($value);
            } else {
                return $this->attributes['image'] = $value;
            }
        }
    }

    public function getThumbAttribute()
    {
        $newThumbUrl = '';
        $value = $this->attributes['image'];
        if (empty($value)) {
            return $this->attributes['thumb'] = '';
        } else {
            $fileName = basename($value);
            $newValue = str_replace($fileName,"thumb/$fileName",$value);
            if (!filter_var($newValue, FILTER_VALIDATE_URL)) {
                $newThumbUrl = Storage::disk('s3')->url($newValue);
            } else {
                $newThumbUrl = $newValue;
            }
            return $this->attributes['thumb'] = $newThumbUrl;
        }
    }
}
