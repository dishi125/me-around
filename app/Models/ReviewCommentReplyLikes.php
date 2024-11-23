<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\ReviewCommentLikes;
use Illuminate\Support\Facades\Auth;

class ReviewCommentReplyLikes extends Model
{
    use SoftDeletes;
    protected $table = 'review_comment_reply_likes';

    protected $dates = ['deleted_at'];

    protected $fillable = [
        'review_comment_reply_id',
        'user_id',
        'created_at',
        'updated_at'
    ];


    protected $casts = [
        'review_comment_reply_id' => 'int',
        'user_id' => 'int',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    protected $appends = ['user_name','user_avatar'];

    public function getUserNameAttribute()
    {
        $value = $this->attributes['user_id'];

        $user = UserDetail::where('user_id',$value)->first();

        return $this->attributes['user_name'] = $user->name;
        
    }

    public function getUserAvatarAttribute()
    {
        $value = $this->attributes['user_id'];

        $user = UserDetail::where('user_id',$value)->first();

        return $this->attributes['user_avatar'] = $user->avatar;
        
    }
}
