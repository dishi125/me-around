<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\ReviewCommentLikes;
use App\Models\ReviewCommentReplyLikes;
use App\Models\ReportClient;
use App\Models\ReviewComments;
use App\Models\ReportTypes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ReviewCommentReply extends Model
{
    use SoftDeletes;
    protected $table = 'review_comment_reply';

    protected $dates = ['deleted_at'];

    protected $fillable = [
        'review_comment_id',
        'reply_parent_id',
        'user_id',
        'comment',
        'created_at',
        'updated_at'
    ];


    protected $casts = [
        'review_comment_id' => 'int',
        'reply_parent_id' => 'int',
        'user_id' => 'int',
        'comment' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    protected $appends = ['user_name','user_avatar','comment_time','is_reported','is_like','reply_user_name','reply_user_avatar','is_edited','is_character_as_profile','user_applied_card'];

    public function getReplyParentIdAttribute($reply_parent_id)
    {
        $value = $reply_parent_id == NULL ? 0 : $reply_parent_id;
        return $value;
    }


    public function getReplyUserNameAttribute()
    {
        $review_comment_id = $this->attributes['review_comment_id'];
        $reply_parent_id = $this->attributes['reply_parent_id'];
        if($reply_parent_id) {
            $data = ReviewCommentReply::find($reply_parent_id);
        }else{
            $data = ReviewComments::find($review_comment_id);
        }

        return $this->attributes['reply_user_name'] = $data ? $data->user_name : "";

    }
    public function getReplyUserAvatarAttribute()
    {
        $review_comment_id = $this->attributes['review_comment_id'];
        $reply_parent_id = $this->attributes['reply_parent_id'];
        if($reply_parent_id) {
            $data = ReviewCommentReply::find($reply_parent_id);
        }else{
            $data = ReviewComments::find($review_comment_id);
        }

        return $this->attributes['reply_user_avatar'] = $data ? $data->user_avatar : "";

    }
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

    public function getCommentTimeAttribute()
    {
        $value = $this->attributes['created_at'];

        return $this->attributes['comment_time'] = timeAgo($value);;

    }

    public function getIsReportedAttribute()
    {
        $value = $this->attributes['id'];
        $user = Auth::user();
        if($user) {
            $reported = ReportClient::where('report_type_id',ReportTypes::REVIEWS_COMMENT_REPLY)
                                        ->where('entity_id',$value)
                                        ->where('user_id',$user->id)->count();
        }else {
            $reported = 0;
        }

        return $this->attributes['is_reported'] = $reported > 0 ? true : false;

    }

    public function getIsLikeAttribute()
    {
        $reviewCommentId = $this->attributes['id'];
        $reviewLiked = 0;
        $user = Auth::user();
        if($user) {
            $reviewLiked = ReviewCommentReplyLikes::where('user_id',$user->id)->where('review_comment_reply_id',$reviewCommentId)->count();
        }

        return $this->attributes['is_like'] = $reviewLiked > 0 ? true : false;
    }

    public function getIsEditedAttribute()
    {
        $created_at = $this->attributes['created_at'];
        $updated_at = $this->attributes['updated_at'];

        return $this->attributes['is_edited'] = $created_at == $updated_at ? false : true;
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
}
