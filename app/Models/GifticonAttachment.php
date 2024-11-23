<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class GifticonAttachment extends Model
{
    protected $table = 'gifticon_attachments';

    protected $fillable = [
        'gifticon_id',
        'attachment_item',
        'created_at',
        'updated_at'
    ];

    protected $appends = ['image_url'];

    public function getImageUrlAttribute()
    {
        $value = $this->attributes['attachment_item'];
        if (empty($value)) {
            return $this->attributes['attachment_item'] = '';
        } else {
            if (!filter_var($value, FILTER_VALIDATE_URL)) {
                return $this->attributes['attachment_item'] = Storage::disk('s3')->url($value);
            } else {
                return $this->attributes['attachment_item'] = $value;
            }
        }
    }
}
