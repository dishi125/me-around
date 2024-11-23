<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\ReviewLikes;
use App\Models\ReviewCommentLikes;
use App\Models\ReviewCommentReply;
use App\Models\ReportClient;
use App\Models\ReportTypes;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ReviewComments extends Model
{
    use SoftDeletes;
    protected $table = 'review_comments';

    protected $dates = ['deleted_at'];

    protected $fillable = [
        'review_id',
        'user_id',
        'comment',
        'created_at',
        'updated_at'
    ];


    protected $casts = [
        'review_id' => 'int',
        'user_id' => 'int',
        'comment' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    protected $appends = ['user_name','user_avatar','comment_time','is_reported','is_like','likes_count','is_edited','comments_count','comments_reply','is_character_as_profile','user_applied_card'];

    public function comment_likes()
    {
        return $this->hasMany(ReviewLikes::class, 'review_id', 'id');
    }

    public function review_comment_likes()
    {
        return $this->hasMany(ReviewCommentLikes::class, 'review_comment_id', 'id');
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
            $reported = ReportClient::where('report_type_id',ReportTypes::REVIEWS_COMMENT)
                                        ->where('entity_id',$value)
                                        ->where('user_id',$user->id)->count();
        }else {
            $reported = 0;
        }

        return $this->attributes['is_reported'] = $reported > 0 ? true : false;

    }

    public function getIsLikeAttribute()
    {
        $reviewId = $this->attributes['id'];
        $reviewLiked = 0;
        $user = Auth::user();
        if($user) {
            $reviewLiked = ReviewCommentLikes::where('user_id',$user->id)->where('review_comment_id',$reviewId)->count();
        }

        return $this->attributes['is_like'] = $reviewLiked > 0 ? true : false;

    }

    public function getLikesCountAttribute()
    {
        $value = $this->attributes['id'];

        $reviewLikes = ReviewCommentLikes::where('review_comment_id',$value)->count();

        return $this->attributes['likes_count'] = $reviewLikes;

    }
    public function getCommentsCountAttribute()
    {
        $value = $this->attributes['id'];

        $reviewComments = ReviewCommentReply::where('review_comment_id',$value)->count();

        return $this->attributes['comments_count'] = $reviewComments;

    }
    public function getCommentsReplyAttribute()
    {
        $value = $this->attributes['id'];

        $reviewComments = ReviewCommentReply::where('review_comment_id',$value)->get();

        return $this->attributes['comments_reply'] = $reviewComments;

    }
    public function getIsEditedAttribute()
    {
        $created_at = $this->attributes['created_at'];
        $updated_at = $this->attributes['updated_at'];

        return $this->attributes['is_edited'] = $created_at == $updated_at ? false : true;
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
