<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class NonLoginUserDetail extends Model
{
    protected $table = 'non_login_user_details';
    protected $fillable = [
        'username',  'gender', 'avatar', 'device_id', 'device_token', 'created_at','updated_at','last_access'
    ];

    protected $appends = ['avatar_url'];

    public function getAvatarUrlAttribute()
    {
        $value = $this->attributes['avatar'] ?? '';
        if (empty($value)) {
            return $this->attributes['avatar_url'] = asset('img/avatar/avatar-1.png');
        } else {
            if (!filter_var($value, FILTER_VALIDATE_URL)) {
                return $this->attributes['avatar_url'] = Storage::disk('s3')->url($value);
            } else {
                return $this->attributes['avatar_url'] = $value;
            }
        }
    }
}
