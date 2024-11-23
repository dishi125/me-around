<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\UserDetail;
use App\Models\CommunityImages;
use App\Models\CommunityLikes;
use App\Models\CommunityComments;
use App\Models\Category;
use App\Models\SavedHistoryTypes;
use App\Models\UserSavedHistory;
use App\Models\ReportClient;
use App\Models\ReportTypes;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use DB;
use App\Models\UserCards;

class Community extends Model
{
    use SoftDeletes;
    protected $table = 'community';

    protected $dates = ['deleted_at'];
    protected $fillable = [
        'user_id',
        'category_id',
        'title',
        'description',
        'views_count',
        'country_code',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'user_id' => 'int',
        'category_id' => 'int',
        'title'=> 'string',
        'description'=> 'string',
        'views_count'=> 'int',
        'country_code'=> 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    protected $appends = ['save_type_id','user_name','user_avatar','user_gender','default_avatar','category_name','parent_category_id','parent_category_name','time_difference','images','likes_count','comments_count','is_saved_in_history','is_reported','is_liked','comments','report_type_id','block_type','is_character_as_profile','user_applied_card'];

    public function community_comments()
    {
         return $this->hasMany(CommunityComments::class, 'community_id', 'id');
    }

    public function getSaveTypeIdAttribute(){
        return $this->attributes['save_type_id'] = SavedHistoryTypes::COMMUNITY;
    }

    public function getUserNameAttribute()
    {
        $value = isset($this->attributes['user_id']) ? $this->attributes['user_id'] : NULL;
        if($value) {
            $user = UserDetail::where('user_id',$value)->first();
            return $this->attributes['user_name'] = !empty($user) ? $user->name : '';
        }

        return $this->attributes['user_name'] = '';
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

    public function getUserAvatarAttribute()
    {
        $value = isset($this->attributes['user_id']) ? $this->attributes['user_id'] : NULL;
        if($value) {
            $user = UserDetail::where('user_id',$value)->first();
            return $this->attributes['user_avatar'] = !empty($user) ? $user->avatar : '';
        }

        return $this->attributes['user_avatar'] = '';

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

    public function getCategoryNameAttribute()
    {
        $value = isset($this->attributes['category_id']) ? $this->attributes['category_id'] : NULL;
        if($value) {
            $category = Category::find($value);
            return $this->attributes['category_name'] = !empty($category) ? $category->name : NULL;
        }

        return $this->attributes['category_name'] = '';

    }

    public function getParentCategoryIdAttribute()
    {
        $value = isset($this->attributes['category_id']) ? $this->attributes['category_id'] : NULL;
        if($value) {
            $category = Category::find($value);
            return $this->attributes['parent_category_id'] = !empty($category) ? $category->parent_id : 0;
        }

        return $this->attributes['parent_category_id'] = 0;

    }

    public function getParentCategoryNameAttribute()
    {
        $value = isset($this->attributes['category_id']) ? $this->attributes['category_id'] : NULL;
        if($value) {
            $category = Category::find($value);
            return $this->attributes['parent_category_name'] = !empty($category) ? $category->parent_name : '';
        }

        return $this->attributes['parent_category_name'] = '';

    }

    public function getTimeDifferenceAttribute()
    {
        $value = $this->attributes['created_at'];

        return $this->attributes['time_difference'] = timeAgo($value);;

    }

    public function getImagesAttribute()
    {
        $value = $this->attributes['id'];

        $images = CommunityImages::where('community_id',$value)->get(['image']);

        return $this->attributes['images'] = $images;

    }
    public function getLikesCountAttribute()
    {
        $value = $this->attributes['id'];

        $communityLikes = CommunityLikes::where('community_id',$value)->count();

        return $this->attributes['likes_count'] = $communityLikes;

    }
    public function getCommentsCountAttribute()
    {
        $value = $this->attributes['id'];

        $communityComments = CommunityComments::where('community_id',$value)->count();

        return $this->attributes['comments_count'] = $communityComments;

    }
    public function getIsSavedInHistoryAttribute()
    {
        $value = $this->attributes['id'];
        $user = Auth::user();
        $count = 0;
        if($user) {
            $count = UserSavedHistory::where('saved_history_type_id',SavedHistoryTypes::COMMUNITY)
                                    ->where('entity_id',$value)
                                    ->where('is_like',1)
                                    ->where('user_id',$user->id)
                                    ->count();
        }
        return $this->attributes['is_saved_in_history'] = $count > 0 ? true : false;
    }
    public function getIsReportedAttribute()
    {
        $value = $this->attributes['id'];
        $user = Auth::user();
        if($user) {
            $reported = ReportClient::where('report_type_id',ReportTypes::COMMUNITY)
                                        ->where('entity_id',$value)
                                        ->where('user_id',$user->id)->count();
        }else {
            $reported = 0;
        }

        return $this->attributes['is_reported'] = $reported > 0 ? true : false;

    }

    public function getIsLikedAttribute()
    {
        $value = $this->attributes['id'];
        $user = Auth::user();
        if($user) {
            $reviewLikes = CommunityLikes::where('community_id',$value)->where('user_id',$user->id)->count();
        }else {
            $reviewLikes = 0;
        }

        return $this->attributes['is_liked'] = $reviewLikes > 0 ? true : false;

    }

    public function getCommentsAttribute()
    {
        $value = $this->attributes['id'];

        $communityComments = CommunityComments::select('community_comments.*')->join('users','users.id','community_comments.user_id')->whereNull('users.deleted_at')->where('community_comments.community_id',$value)->orderby('community_likes_count','desc')
            ->withCount([
                'community_likes' => function($query) {
                }
            ])
        ->paginate(config('constant.pagination_count'),"*","comments_page");

        return $this->attributes['comments'] = $communityComments;

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

    public function getReportTypeIdAttribute(){
        return $this->attributes['report_type_id'] = ReportTypes::COMMUNITY;
    }

    public function getBlockTypeAttribute(){
        return $this->attributes['block_type'] = UserBlockHistory::COMMUNITY_POST;
    }
}
