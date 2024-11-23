<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class AssociationImage extends Model
{
    protected $table = 'associations_image';

    protected $fillable = [
        'associations_id',
        'type',
        'image',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'community_id' => 'int',
        'type' => 'string',
        'image' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    const MAIN_IMAGE = 'main_image';
    const BANNER_IMAGE = 'banner_image';

    protected $appends = ['image_url'];

    public function getImageUrlAttribute()
    {
        $value = $this->attributes['image'];
        if (empty($value)) {
            return $this->attributes['image_url'] = '';
        } else {
            if (!filter_var($value, FILTER_VALIDATE_URL)) {
                if(Storage::disk('s3')->has($value)){
                    return $this->attributes['image_url'] = Storage::disk('s3')->url($value);
                }else{
                    return $this->attributes['image'] = '';
                    return $this->attributes['image_url'] = '';
                }
            } else {
                return $this->attributes['image_url'] = '';
            }
        }
    }
}
