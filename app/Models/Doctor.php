<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Doctor extends Model
{
    use SoftDeletes;
    protected $table = 'doctors';

    protected $dates = ['deleted_at'];
    
    protected $fillable = [
        'name',
        'gender',
        'avatar',
        'specialty',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'name' => 'string',
        'gender' => 'string',
        'avatar' => 'string',
        'specialty' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    protected $appends = ['avatar_url'];

    public function getAvatarUrlAttribute()
    {
        $value = $this->attributes['avatar'];
        if (empty($value)) {
            return $this->attributes['avatar_url'] = '';
        } else {
            if (!filter_var($value, FILTER_VALIDATE_URL)) {
                return $this->attributes['avatar_url'] = Storage::disk('s3')->url($value);
            } else {
                return $this->attributes['avatar_url'] = $value;
            }
        }
    }

}
