<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class PostLanguage extends Model
{
    protected $table = 'post_languages';

    protected $fillable = [
        'name','icon','is_support', 'created_at','updated_at'
    ];

    const ENGLISH = 4;
    const KOREAN = 1;
    const JAPANESE = 3;

    protected $casts = [
        'name' => 'string',
        'icon' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function getIconAttribute()
    {
        $value = $this->attributes['icon'];
        if (empty($value)) {
            return $this->attributes['icon'] = '';
        } else {
            if (!filter_var($value, FILTER_VALIDATE_URL)) {
                return $this->attributes['icon'] = Storage::disk('s3')->url($value);
            } else {
                return $this->attributes['icon'] = $value;
            }
        }
    }
}
