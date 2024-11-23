<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class CardStatusRives extends Model
{
    protected $table = 'card_status_rives';
    protected $fillable = [
        'card_id','card_level_id','card_level_status','character_riv','created_at','updated_at'
    ];

    protected $appends = ['character_riv_url'];

    public function getCharacterRivUrlAttribute(): string
    {
        $value = $this->attributes['character_riv'] ?? '';
        $file_url = '';
        if (!empty($value) && !filter_var($value, FILTER_VALIDATE_URL)) {
            $file_url = Storage::disk('s3')->url($value);
        }
        return $this->attributes['character_riv_url'] = $file_url;
    }
}
