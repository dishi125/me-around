<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ShopReportAttachment extends Model
{
    protected $table = 'shop_report_attachments';

    protected $fillable = [
        'shop_report_id',
        'attachment_item',
        'type',
        'video_thumbnail',
        'created_at',
        'updated_at'
    ];

    protected $appends = ['attachment_item_url', 'video_thumbnail_url'];

    public function getAttachmentItemUrlAttribute()
    {
        $value = $this->attributes['attachment_item'] ?? '';
        $file_url = '';
        if (!empty($value) && !filter_var($value, FILTER_VALIDATE_URL)) {
            $file_url = Storage::disk('s3')->url($value);
        }
        return $this->attributes['attachment_item_url'] = $file_url;
    }

    public function getVideoThumbnailUrlAttribute()
    {
        $value = $this->attributes['video_thumbnail'] ?? '';
        $file_url = '';
        if (!empty($value) && !filter_var($value, FILTER_VALIDATE_URL)) {
            $file_url = Storage::disk('s3')->url($value);
        }
        return $this->attributes['video_thumbnail_url'] = $file_url;
    }

}
