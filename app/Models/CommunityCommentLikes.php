<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\UserDetail;
use App\Models\UserCards;
use Carbon\Carbon;

class CommunityCommentLikes extends Model
{
    use SoftDeletes;
    protected $table = 'community_comment_likes';

    protected $dates = ['deleted_at'];

    protected $fillable = [
        'community_comment_id',
        'user_id',
        'created_at',
        'updated_at'
    ];


    protected $casts = [
        'community_comment_id' => 'int',
        'user_id' => 'int',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    protected $appends = ['user_name','user_avatar','is_character_as_profile','user_applied_card'];

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

    public function getUserAppliedCardAttribute()
    {
        $id = $this->attributes['user_id'] ?? 0;
        $card = [];
        if(!empty($id)){
            $card = getUserAppliedCard($id);
        }
        return $this->attributes['user_applied_card'] = $card;
    }

    public function getIsCharacterAsProfileAttribute()
    {
        $id = $this->attributes['user_id'] ?? 0;
        $is_character_as_profile = 1;
        if(!empty($id)){
            $userDetail = DB::table('users_detail')->where('user_id',$id)->first('is_character_as_profile');
            $is_character_as_profile = $userDetail ? $userDetail->is_character_as_profile : 1;
        }
        return $this->attributes['is_character_as_profile'] = $is_character_as_profile;
    }

    public function getCreatedAtAttribute($date)
    {
        $date = new Carbon($date);
        return $date->format('Y-m-d H:i:s');
    }

    public function getUpdatedAtAttribute($date)
    {
        $date = new Carbon($date);
        return $date->format('Y-m-d H:i:s');
    }
}
