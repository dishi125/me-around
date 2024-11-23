<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\UserDetail;
use App\Models\CommunityCommentLikes;
use App\Models\CommunityCommentReplyLikes;
use App\Models\CommunityComments;
use App\Models\ReportTypes;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use DB;
use App\Models\UserCards;

class CommunityCommentReply extends Model
{
    use SoftDeletes;
    protected $table = 'community_comment_reply';

    protected $dates = ['deleted_at'];

    protected $fillable = [
        'community_comment_id',
        'reply_parent_id',
        'user_id',
        'comment',
        'created_at',
        'updated_at',
        'is_admin_read'
    ];


    protected $casts = [
        'community_comment_id' => 'int',
        'reply_parent_id' => 'int',
        'user_id' => 'int',
        'comment' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    protected $appends = ['user_name','user_avatar','user_gender','default_avatar','comment_time','is_reported','is_like','reply_user_name','reply_user_avatar','is_edited','report_type_id','default_category_id','is_character_as_profile','user_applied_card'];

    const DEFAULT_CATEGORY_ID = 95;

    public function getUserNameAttribute()
    {
        $value = isset($this->attributes['user_id']) ? $this->attributes['user_id'] : NULL;
        if($value) {
            $userEntity = UserEntityRelation::where('user_id',$value)->where('entity_id',$value)->first();
            $entity_type_id = $userEntity ? $userEntity->entity_type_id : '';
            if($entity_type_id == EntityTypes::ADMIN || $entity_type_id == EntityTypes::MANAGER || $entity_type_id == EntityTypes::SUBMANAGER){
                $manager = Manager::where('user_id',$value)->first();
                $userName = !empty($manager) ? $manager->name : '';
            }else{
                $user = UserDetail::where('user_id',$value)->first();
                $userName = !empty($user) ? $user->name : '';
            }
            return $this->attributes['user_name'] = $userName;
        }

        return $this->attributes['user_name'] = '';
    }

    public function getUserAvatarAttribute()
    {
        $value = isset($this->attributes['user_id']) ? $this->attributes['user_id'] : NULL;
        if($value) {
            $userEntity = UserEntityRelation::where('user_id',$value)->where('entity_id',$value)->first();
            $entity_type_id = $userEntity ? $userEntity->entity_type_id : '';
            if($entity_type_id == EntityTypes::ADMIN || $entity_type_id == EntityTypes::MANAGER || $entity_type_id == EntityTypes::SUBMANAGER){
                $manager = Manager::where('user_id',$value)->first();
                $userAvatar = !empty($manager && $manager->avatar) ? $manager->avatar : asset('img/avatar/avatar-1.png');;
            }else {
                $user = UserDetail::where('user_id', $value)->first();
                $userAvatar = !empty($user) ? $user->avatar : '';
            }
            return $this->attributes['user_avatar'] = $userAvatar;
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

    public function getUserGenderAttribute()
    {
        $value = isset($this->attributes['user_id']) ? $this->attributes['user_id'] : NULL;
        if($value) {
            $user = UserDetail::where('user_id',$value)->first();
            return $this->attributes['user_gender'] = !empty($user) ? $user->gender : '';
        }

        return $this->attributes['user_gender'] = '';
    }

    public function getDefaultAvatarAttribute()
    {
       return $this->attributes['default_avatar'] = asset('img/avatar/avatar-1.png');;

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
            $reported = ReportClient::where('report_type_id',ReportTypes::COMMUNITY_COMMENT_REPLY)
                                        ->where('entity_id',$value)
                                        ->where('user_id',$user->id)->count();
        }else {
            $reported = 0;
        }

        return $this->attributes['is_reported'] = $reported > 0 ? true : false;

    }

    public function getIsLikeAttribute()
    {
        $communityCommentId = $this->attributes['id'];
        $communityLiked = 0;
        $user = Auth::user();
        if($user) {
            $communityLiked = CommunityCommentReplyLikes::where('user_id',$user->id)->where('community_comment_reply_id',$communityCommentId)->count();
        }

        return $this->attributes['is_like'] = $communityLiked > 0 ? true : false;

    }

    public function getReplyUserNameAttribute()
    {
        $review_comment_id = $this->attributes['community_comment_id'];
        $reply_parent_id = $this->attributes['reply_parent_id'];
        if($reply_parent_id) {
            $data = CommunityCommentReply::find($reply_parent_id);
        }else{
            $data = CommunityComments::find($review_comment_id);
        }

        return $this->attributes['reply_user_name'] = $data ? $data->user_name : "";

    }
    public function getReplyUserAvatarAttribute()
    {
        $review_comment_id = $this->attributes['community_comment_id'];
        $reply_parent_id = $this->attributes['reply_parent_id'];
        if($reply_parent_id) {
            $data = CommunityCommentReply::find($reply_parent_id);
        }else{
            $data = CommunityComments::find($review_comment_id);
        }

        return $this->attributes['reply_user_avatar'] = $data ? $data->user_avatar : "";

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
        return $date->format('Y-m-d H:i:s');
    }

    public function getReportTypeIdAttribute(){
        return $this->attributes['report_type_id'] = ReportTypes::COMMUNITY_COMMENT_REPLY;
    }

    public function getDefaultCategoryIdAttribute(){
        return $this->attributes['default_category_id'] = self::DEFAULT_CATEGORY_ID;
    }
}
