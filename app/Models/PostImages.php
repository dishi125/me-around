<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class PostImages extends Model
{
    use SoftDeletes;
    protected $table = 'post_images';

    const THUMBNAIL = 'thumbnail';
    const MAINPHOTO = 'main photo';

    protected $dates = ['deleted_at'];

    protected $fillable = [
        'post_id',
        'post_language_id',
        'type',
        'image',
        'created_at',
        'updated_at'
    ];


    protected $casts = [
        'post_id' => 'int',
        'post_language_id' => 'int',
        'type' => 'string',
        'image' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    protected $appends = ['image_path'];

    public function getImagePathAttribute(){
        $value = $this->attributes['image'];
        return $this->attributes['image_path'] = $value;
    }

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
}
