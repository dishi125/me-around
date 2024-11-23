<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class WeddingSettings extends Model
{
    protected $table = 'wedding_settings';

    protected $fillable = [
        'key',
        'value',
        'type',
        'created_at',
        'updated_at'
    ];

    const VIDEO_FILE = 'video_file';
    const AUDIO_FILE = 'audio_file';

    const SETTING_OPTIONS = [
        self::VIDEO_FILE => 'Video',
        self::AUDIO_FILE => 'Audio',
    ];
    const SETTING_OPTION_TYPES = [
        self::VIDEO_FILE => ['label' => 'Video Animation', 'type' => 'file', 'accept' => "video/mp4,video/*"],
        self::AUDIO_FILE => ['label' => 'Audio', 'type' => 'file', 'accept' => ".mp3,audio/*"],
    ];

    protected $appends = ['filter_value'];

    public function getFilterValueAttribute()
    {
        $value = $this->attributes['value'];
        $type = $this->attributes['type'];

        if($type == 'file'){
            $filter_value = Storage::disk('s3')->url($value);
        }else{
            $filter_value = $value;
        }
                
        return $this->attributes['filter_value'] = $filter_value;
    }
}
