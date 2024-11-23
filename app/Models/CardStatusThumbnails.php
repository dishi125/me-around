<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class CardStatusThumbnails extends Model
{
    protected $table = 'card_status_thumbnails';
    protected $fillable = [
        'card_id','card_level_id','card_level_status','character_thumb','created_at','updated_at'
    ];

    protected $appends = ['character_thumb_url'];

    public function getCharacterThumbUrlAttribute(): string
    {
        $value = $this->attributes['character_thumb'] ?? '';
        $file_url = '';
        if (!empty($value) && !filter_var($value, FILTER_VALIDATE_URL)) {
            $file_url = Storage::disk('s3')->url($value);
        }
        return $this->attributes['character_thumb_url'] = $file_url;
    }
}
