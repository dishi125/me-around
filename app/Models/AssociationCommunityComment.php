<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use DB;

class AssociationCommunityComment extends Model
{
    use SoftDeletes;

    protected $table = 'association_community_comments';
    protected $dates = ['deleted_at'];

    protected $fillable = [
        'community_id', 'user_id', 'comment', 'parent_id', 'created_at', 'updated_at', 'is_edited', 'is_admin_read'
    ];

    protected $appends = ['is_like','user_info','default_avatar', 'reply_user_name', 'user_name','gender','user_avatar', 'comments_count', 'is_reported','report_type_id','comment_time','likes_count','default_category_id','user_applied_card','is_character_as_profile'];

    protected $casts = [
        'is_edited' => 'bool',
    ];

    const DEFAULT_CATEGORY_ID = 95;

    public function comments_reply(){
        return $this->hasMany(AssociationCommunityComment::class, 'parent_id')->with('comments_reply');
    }

    public function comments_parent(){
        return $this->hasOne( AssociationCommunityComment::class, 'id', 'parent_id' );
    }

    public function likes() {
        return $this->hasMany(AssociationLikes::class, 'entity_id', 'id');
    }

    public function getIsLikeAttribute(){
        $user = auth()->user()->id;
        return $this->attributes['is_like'] = (($this->likes()->where('user_id',$user)->count()) > 0) ? true : false;
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

    public function getUserInfoAttribute(){

        $value = isset($this->attributes['user_id']) ? $this->attributes['user_id'] : NULL;
        $user_info = [];
        if($value) {
            $userEntity = UserEntityRelation::where('user_id',$value)->where('entity_id',$value)->first();
            $entity_type_id = $userEntity ? $userEntity->entity_type_id : '';
            if($entity_type_id == EntityTypes::ADMIN || $entity_type_id == EntityTypes::MANAGER || $entity_type_id == EntityTypes::SUBMANAGER){
                $manager = Manager::where('user_id',$value)->first();
                $user_info = (object)[
                    'user_id' => $value,
                    'name' => $manager->name,
                    'gender' => 'Male',
                    'avatar' => $manager->avatar,
                    'language_id' => 4
                ];
            }else{
                $user_info = UserDetail::select('user_id','name','gender','avatar','language_id','is_character_as_profile')->where('user_id',$value)->first();
                $user_info = $user_info->makeHidden(['language_id','language_name']);
            }
        }

        return $this->attributes['user_info'] = $user_info;
    }

    public function getReplyUserNameAttribute(){
        $value = $this->comments_parent;
        return $this->attributes['reply_user_name'] = !empty($value) ? $value->user_info->name : '';
    }

    public function getUserNameAttribute(){
        $value = isset($this->attributes['user_id']) ? $this->attributes['user_id'] : NULL;
        $userEntity = UserEntityRelation::where('user_id',$value)->where('entity_id',$value)->first();
        $entity_type_id = $userEntity ? $userEntity->entity_type_id : '';
        if($entity_type_id == EntityTypes::ADMIN || $entity_type_id == EntityTypes::MANAGER || $entity_type_id == EntityTypes::SUBMANAGER){
            $manager = Manager::where('user_id', $value)->first();
            return $this->attributes['user_name'] = !empty($manager) ? $manager->name : '';
        }else{
            $userDetail = DB::table('users_detail')->whereNull('deleted_at')->where('user_id',$value)->first();
            return $this->attributes['user_name'] = !empty($userDetail) ? $userDetail->name : '';
        }

    }

    public function getGenderAttribute(){
        $value = $this->attributes['user_info'] ?? '';
        return $this->attributes['gender'] = !empty($value) ? $value->gender: '';
    }

    public function getUserAvatarAttribute(){
        $value = $this->attributes['user_info'] ?? '';
        return $this->attributes['user_avatar'] = !empty($value && $value->avatar) ? $value->avatar: asset('img/avatar/avatar-1.png');
    }

    public function getIsReportedAttribute()
    {
        $user = auth()->user();
        if($user){
            $reported = DB::table('report_clients')->where('report_type_id',ReportTypes::ASSOCIATION_COMMUNITY_COMMENT)
                ->where('entity_id',$this->attributes['id'])
                ->where('user_id',$user->id)->count();
        }else{
            $reported = 0;
        }
        return $this->attributes['is_reported'] = $reported > 0 ? true : false;
    }

    public function getCommentsCountAttribute()
    {
        return $this->attributes['comments_count'] = $this->comments_reply()->count();
    }

    public function getLikesCountAttribute()
    {
        return $this->attributes['likes_count'] = $this->likes()->count();
    }

    public function getDefaultAvatarAttribute()
    {
        return $this->attributes['default_avatar'] = asset('img/avatar/avatar-1.png');

    }

    public function getReportTypeIdAttribute(){
        return $this->attributes['report_type_id'] = ReportTypes::ASSOCIATION_COMMUNITY_COMMENT;
    }

    public function getCommentTimeAttribute()
    {
        $value = $this->attributes['created_at'];
        return $this->attributes['comment_time'] = timeAgo($value);
    }

    public function getDefaultCategoryIdAttribute(){
        return $this->attributes['default_category_id'] = AssociationCommunityComment::DEFAULT_CATEGORY_ID;
    }
}
