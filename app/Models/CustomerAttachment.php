<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class CustomerAttachment extends Model
{
    const OUTSIDE = "outside";
    const INSIDE = "inside";

    protected $table = 'customer_attachments';
    
    protected $appends = ['image_url'];

    protected $fillable = [
        'image','type', 'entity_id', 'created_at','updated_at'
    ];

    public function getImageUrlAttribute()
    {
        $value = $this->attributes['image'] ?? '';
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
