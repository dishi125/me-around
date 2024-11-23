<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class MultipleShopPost extends Model
{
    use SoftDeletes;
    protected $table = 'multiple_shop_posts';

    protected $dates = ['deleted_at'];

    protected $fillable = [
        'shop_posts_id',
        'post_item',
        'type',
        'video_thumbnail',
        'instagram_post_id',
        'created_at',
        'updated_at',
        'display_video'
    ];

    protected $appends = ['post_item_thumbnail'];

    public function getPostItemThumbnailAttribute()
    {
        $value = !empty($this->attributes['post_item']) ? $this->attributes['post_item'] : "";
        if (empty($value)) {
            return $this->attributes['post_item_thumbnail'] = '';
        } else {
            $fileName = basename($value);
            $newValue = str_replace($fileName,"thumb/$fileName",$value);
            if (!filter_var($newValue, FILTER_VALIDATE_URL)) {
                return $this->attributes['post_item_thumbnail'] = Storage::disk('s3')->url($newValue);
            } else {
                return $this->attributes['post_item_thumbnail'] = $newValue;
            }
        }
    }

    public function getPostItemAttribute()
    {
        $value = !empty($this->attributes['post_item']) ? $this->attributes['post_item'] : "";
        if (empty($value)) {
            return $this->attributes['post_item'] = '';
        } else {
            if (!filter_var($value, FILTER_VALIDATE_URL)) {
                return $this->attributes['post_item'] = Storage::disk('s3')->url($value);
            } else {
                return $this->attributes['post_item'] = $value;
            }
        }
    }
    public function getVideoThumbnailAttribute()
    {
        $value = !empty($this->attributes['video_thumbnail']) ? $this->attributes['video_thumbnail'] : "";
        if (empty($value)) {
            return $this->attributes['video_thumbnail'] = '';
        } else {
            if (!filter_var($value, FILTER_VALIDATE_URL)) {
                return $this->attributes['video_thumbnail'] = Storage::disk('s3')->url($value);
            } else {
                return $this->attributes['video_thumbnails'] = $value;
            }
        }
    }
}
