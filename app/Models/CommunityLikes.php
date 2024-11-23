<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\UserDetail;
use Carbon\Carbon;

class CommunityLikes extends Model
{
    use SoftDeletes;
    protected $table = 'community_likes';

    protected $dates = ['deleted_at'];

    protected $fillable = [
        'community_id',
        'user_id',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'community_id' => 'int',
        'user_id' => 'int',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    protected $appends = ['user_name','user_avatar'];

    public function getUserNameAttribute()
    {    
        $value = isset($this->attributes['user_id']) ? $this->attributes['user_id'] : NULL;
        if($value) {
            $user = UserDetail::where('user_id',$value)->first();
            return $this->attributes['user_name'] = !empty($user) ? $user->name : '';
        }

        return $this->attributes['user_name'] = '';        
    }

    public function getUserAvatarAttribute()
    {
        $value = isset($this->attributes['user_id']) ? $this->attributes['user_id'] : NULL;
        if($value) {
            $user = UserDetail::where('user_id',$value)->first();
            return $this->attributes['user_avatar'] = !empty($user) ? $user->avatar : '';
        }

        return $this->attributes['user_avatar'] = '';    
        
    }

    public function getCreatedAtAttribute($date)
    {
        $date = new Carbon($date);
        return $date->format('d-m-Y H:i');
    }

    public function getUpdatedAtAttribute($date)
    {
        $date = new Carbon($date);
        return $date->format('d-m-Y H:i');
    }
}
