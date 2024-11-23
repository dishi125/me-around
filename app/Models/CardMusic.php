<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class CardMusic extends Model
{
    protected $table = 'card_music';

    protected $fillable = [
        'card_id', 'music_file', 'menu_order', 'created_at','updated_at'
    ];

    protected $appends = ['music_file_url','music_name'];

    public function getMusicNameAttribute(){
        $value = $this->attributes['music_file'] ?? '';
        return $this->attributes['music_name'] = pathinfo($value, PATHINFO_BASENAME);
    }
    public function getMusicFileUrlAttribute()
    {
        $value = $this->attributes['music_file'] ?? '';
        $file_url = '';
        if (!empty($value) && !filter_var($value, FILTER_VALIDATE_URL)) {
            $file_url = Storage::disk('s3')->url($value);
        }
        return $this->attributes['music_file_url'] = $file_url;
    }
}
