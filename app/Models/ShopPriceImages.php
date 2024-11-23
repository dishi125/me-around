<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ShopPriceImages extends Model
{
    protected $table = 'shop_price_images';

    protected $fillable = [
        'shop_price_id',
        'image',
        'created_at',
        'updated_at',
        'thumb_url',
        'order'
    ];

    protected $appends = ['image_url','thumb_image','image_item_thumbnail'];

    protected $casts = [
        //'order' => 'string',
    ];

    public function getImageUrlAttribute(){
        $value = $this->attributes['image'];
        if (empty($value)) {
            return $this->attributes['image_url'] = '';
        } else {
            if (!filter_var($value, FILTER_VALIDATE_URL)) {
                return $this->attributes['image_url'] = Storage::disk('s3')->url($value);
            } else {
                return $this->attributes['image_url'] = $value;
            }
        }
    }

    public function getThumbImageAttribute(){
        $value = $this->attributes['thumb_url'];
        if (empty($value)) {
            return $this->attributes['thumb_image'] = '';
        } else {
            if (!filter_var($value, FILTER_VALIDATE_URL)) {
                return $this->attributes['thumb_image'] = Storage::disk('s3')->url($value);
            } else {
                return $this->attributes['thumb_image'] = $value;
            }
        }
    }

    //For image and thumb image
    public function getImageItemThumbnailAttribute()
    {
        $value = (!empty($this->attributes['thumb_url']) && $this->attributes['thumb_url']!='') ? $this->attributes['thumb_url'] : $this->attributes['image'];
        if (empty($value)) {
            return $this->attributes['image_item_thumbnail'] = '';
        } else {
            $fileName = basename($value);
            $newValue = str_replace($fileName,"thumb/$fileName",$value);
            if (!filter_var($newValue, FILTER_VALIDATE_URL)) {
                return $this->attributes['image_item_thumbnail'] = Storage::disk('s3')->url($newValue);
            } else {
                return $this->attributes['image_item_thumbnail'] = $newValue;
            }
        }
    }

}
