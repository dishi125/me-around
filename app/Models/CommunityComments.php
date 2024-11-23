<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\UserDetail;
use Carbon\Carbon;
use App\Models\CommunityLikes;
use App\Models\CommunityCommentLikes;
use App\Models\CommunityCommentReply;
use App\Models\ReportTypes;
use Illuminate\Support\Facades\Auth;
use DB;
use App\Models\UserCards;

class CommunityComments extends Model
{
    use SoftDeletes;
    protected $table = 'community_comments';

    protected $dates = ['deleted_at'];

    protected $fillable = [
        'community_id',
        'user_id',
        'comment',
        'created_at',
        'updated_at',
        'is_admin_read'
    ];

    protected $casts = [
        'community_id' => 'int',
        'user_id' => 'int',
        'comment' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    protected $appends = ['user_name','user_avatar','user_gender','default_avatar','comment_time','is_edited','is_reported','is_like','likes_count','comments_count','comments_reply','report_type_id','default_category_id','is_character_as_profile','user_applied_card'];

    const DEFAULT_CATEGORY_ID = 95;

    public function community_likes()
    {
         return $this->hasMany(CommunityCommentLikes::class, 'community_comment_id', 'id');
    }

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

        return $this->attributes['comment_time'] = timeAgo($value);

    }

    public function getIsLikeAttribute()
    {
        $communityCommentId = $this->attributes['id'];
        $communityLiked = 0;
        $user = Auth::user();
        if($user) {
            $communityLiked = CommunityCommentLikes::where('user_id',$user->id)->where('community_comment_id',$communityCommentId)->count();
        }

        return $this->attributes['is_like'] = $communityLiked > 0 ? true : false;

    }

    public function getLikesCountAttribute()
    {
        $value = $this->attributes['id'];

        $communityLikes = CommunityCommentLikes::where('community_comment_id',$value)->count();

        return $this->attributes['likes_count'] = $communityLikes;

    }
    public function getCommentsCountAttribute()
    {
        $value = $this->attributes['id'];

        $communityComments = CommunityCommentReply::where('community_comment_id',$value)->count();

        return $this->attributes['comments_count'] = $communityComments;

    }
    public function getCommentsReplyAttribute()
    {
        $value = $this->attributes['id'];

        $communityComments = CommunityCommentReply::where('community_comment_id',$value)->get();

        return $this->attributes['comments_reply'] = $communityComments;

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

    public function getIsReportedAttribute()
    {
        $value = $this->attributes['id'];
        $user = Auth::user();
        if($user) {
            $reported = ReportClient::where('report_type_id',ReportTypes::COMMUNITY_COMMENT)
                                        ->where('entity_id',$value)
                                        ->where('user_id',$user->id)->count();
        }else {
            $reported = 0;
        }

        return $this->attributes['is_reported'] = $reported > 0 ? true : false;

    }

    public function getIsEditedAttribute()
    {
        $created_at = $this->attributes['created_at'];
        $updated_at = $this->attributes['updated_at'];

        return $this->attributes['is_edited'] = ($created_at == $updated_at) ? false : true;
    }

    public function getReportTypeIdAttribute(){
        return $this->attributes['report_type_id'] = ReportTypes::COMMUNITY_COMMENT;
    }

    public function getDefaultCategoryIdAttribute(){
        return $this->attributes['default_category_id'] = self::DEFAULT_CATEGORY_ID;
    }
}
