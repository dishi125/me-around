<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class CardLevelDetail extends Model
{
    protected $table = 'card_level_details';

    protected $fillable = [
        'main_card_id', 'card_name', 'background_rive', 'background_thumbnail', 'character_rive', 'character_thumbnail', 'download_file', 'usd_price', 'japanese_yen_price', 'chinese_yuan_price', 'korean_won_price', 'required_love_in_days', 'love_amount', 'card_level', 'feeding_rive', 'created_at','updated_at'
    ];

    protected $appends = ['feeding_rive_url', 'background_rive_url', 'background_thumbnail_url','character_rive_url', 'character_thumbnail_url', 'download_file_url','background_rive_animation', 'character_rive_animation'];

    public function cardStatusRive() {
        return $this->hasMany(CardStatusRives::class, 'card_level_id', 'id');
    }
    public function getCharacterThumbnailUrlAttribute()
    {
        $value = $this->attributes['character_thumbnail'] ?? '';
        $file_url = '';
        if (!empty($value) && !filter_var($value, FILTER_VALIDATE_URL)) {
            $file_url = Storage::disk('s3')->url($value);
        }
        return $this->attributes['character_thumbnail_url'] = $file_url;
    }
    public function getBackgroundThumbnailUrlAttribute()
    {
        $value = $this->attributes['background_thumbnail'] ?? '';
        $file_url = '';
        if (!empty($value) && !filter_var($value, FILTER_VALIDATE_URL)) {
            $file_url = Storage::disk('s3')->url($value);
        }
        return $this->attributes['background_thumbnail_url'] = $file_url;
    }
    public function getFeedingRiveUrlAttribute()
    {
        $value = $this->attributes['feeding_rive'] ?? '';
        $file_url = '';
        if (!empty($value) && !filter_var($value, FILTER_VALIDATE_URL)) {
            $file_url = Storage::disk('s3')->url($value);
        }
        return $this->attributes['feeding_rive_url'] = $file_url;
    }
    public function getBackgroundRiveUrlAttribute()
    {
        $value = $this->attributes['background_rive'] ?? '';
        $file_url = '';
        if (!empty($value) && !filter_var($value, FILTER_VALIDATE_URL)) {
            $file_url = Storage::disk('s3')->url($value);
        }
        return $this->attributes['background_rive_url'] = $file_url;
    }

    public function getCharacterRiveUrlAttribute()
    {
        $value = $this->attributes['character_rive'] ?? '';
        $file_url = '';
        if (!empty($value) && !filter_var($value, FILTER_VALIDATE_URL)) {
            $file_url = Storage::disk('s3')->url($value);
        }
        return $this->attributes['character_rive_url'] = $file_url;
    }

    public function getDownloadFileUrlAttribute()
    {
        $value = $this->attributes['download_file'] ?? '';
        $file_url = '';
        if (!empty($value) && !filter_var($value, FILTER_VALIDATE_URL)) {
            $file_url = Storage::disk('s3')->url($value);
        }
        return $this->attributes['download_file_url'] = $file_url;
    }

    public function getBackgroundRiveAnimationAttribute()
    {
        $value = $this->attributes['background_rive'] ?? '';
        $animation = '';
        if (!empty($value)) {
            $animation = pathinfo($value, PATHINFO_FILENAME);
        }
        return $this->attributes['background_rive_animation'] = $animation;
    }

    public function getCharacterRiveAnimationAttribute()
    {
        $value = $this->attributes['character_rive'] ?? '';
        $animation = '';
        if (!empty($value)) {
            $animation = pathinfo($value, PATHINFO_FILENAME);
        }
        return $this->attributes['character_rive_animation'] = $animation;
    }
}
