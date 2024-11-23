<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\UserDetail;
use App\Models\UserCards;
use App\Models\ReviewCategory;
use App\Models\ReviewLikes;
use App\Models\ReviewComments;
use App\Models\ReportClient;
use App\Models\ReportTypes;
use App\Models\UserSavedHistory;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class Reviews extends Model
{
    use SoftDeletes;
    protected $table = 'reviews';

    protected $dates = ['deleted_at'];

    protected $fillable = [
        'user_id',
        'entity_type_id',
        'entity_id',
        'requested_customer_id',
        'doctor_id',
        'review_comment',
        'rating',
        'views_count',
        'created_at',
        'updated_at',
        'is_admin_read'
    ];

    protected $casts = [
        'user_id' => 'int',
        'entity_type_id' => 'int',
        'entity_id' => 'int',
        'requested_customer_id' => 'int',
        'doctor_id' => 'int',
        'review_comment' => 'string',
        'rating' => 'int',
        'views_count' => 'int',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    protected $appends = ['review_categories','user_name','user_avatar','before_images','after_images','is_saved_in_history','is_reported','is_liked','likes_count','comments_count','comments','is_character_as_profile','user_applied_card'];

    public function reviewLikes()
    {
        return $this->hasMany(ReviewLikes::class, 'review_id', 'id');
    }

    public function getDoctorIdAttribute($doctor_id)
    {
        $value = $doctor_id == NULL ? 0 : $doctor_id;
        return $value;
    }

    public function getReviewCategoriesAttribute()
    {
        $value = $this->attributes['id'];

        $categories = ReviewCategory::join('category','category.id','review_category.category_id')
                                    ->where('review_category.review_id',$value)
                                    ->get(['category.name','category.id']);

        return $this->attributes['review_categories'] = $categories;

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

    public function getUserAppliedCardAttribute()
    {
        $id = $this->attributes['user_id'] ?? 0;
        $card = [];
        if(!empty($id)){
            //$card = UserCards::select('id','background_riv','character_riv')->where(['user_id' => $id,'is_applied' => 1])->first();
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

    public function getBeforeImagesAttribute()
    {
        $value = $this->attributes['id'];

        $reviewImages = ReviewImages::where('review_id',$value)
                                    ->where('type',ReviewImages::BEFORE)
                                    ->get();
        $images = [];

        if (empty($reviewImages)) {
            return $this->attributes['before_images'] = $images;
        } else {
            foreach($reviewImages as $val){
                array_push($images,$val->image);
            }
        }

        return $this->attributes['before_images'] = $images;

    }
    public function getAfterImagesAttribute()
    {
        $value = $this->attributes['id'];

        $reviewImages = ReviewImages::where('review_id',$value)
                                    ->where('type',ReviewImages::AFTER)
                                    ->get();
        $images = [];

        if (empty($reviewImages)) {
            return $this->attributes['after_images'] = $images;
        } else {
            foreach($reviewImages as $val){
                array_push($images,$val->image);
            }
        }

        return $this->attributes['after_images'] = $images;

    }

    public function getIsSavedInHistoryAttribute()
    {
        $value = $this->attributes['id'];
        $user = Auth::user();
        $count = 0;
        if($user) {
            $count = UserSavedHistory::where('saved_history_type_id',SavedHistoryTypes::REVIEWS)
                                    ->where('is_like',1)
                                    ->where('entity_id',$value)
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
            $reported = ReportClient::where('report_type_id',ReportTypes::REVIEWS)
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
        $review = Reviews::find($value);
        $user = Auth::user();
        if($user) {
            $reviewLikes = ReviewLikes::where('review_id',$value)->where('user_id',$user->id)->count();
        }else {
            $reviewLikes = 0;
        }

        return $this->attributes['is_liked'] = $reviewLikes > 0 ? true : false;

    }

    public function getLikesCountAttribute()
    {
        $value = $this->attributes['id'];

        $reviewLikes = ReviewLikes::where('review_id',$value)->count();

        return $this->attributes['likes_count'] = $reviewLikes;

    }
    public function getCommentsCountAttribute()
    {
        $value = $this->attributes['id'];

        $reviewComments = ReviewComments::where('review_id',$value)->count();

        return $this->attributes['comments_count'] = $reviewComments;

    }
    public function getCommentsAttribute()
    {
        $value = $this->attributes['id'];

        $reviewComments = ReviewComments::where('review_id',$value)->orderBy('review_comment_likes_count', 'desc')->withCount([
            'review_comment_likes' => function($query) {
            }
        ])->paginate(config('constant.pagination_count'),"*","comments_page");

        return $this->attributes['comments'] = $reviewComments;

    }

    public function getCreatedAtAttribute($date)
    {
        $date = new Carbon($date);
        return $date->format('Y-m-d H:i:s');
    }

    public function getUpdatedAtAttribute($date)
    {
        $date = new Carbon($date);
        return $date->format('d-m-Y H:i');
    }

    public function reviewImages()
    {
        return $this->hasMany(ReviewImages::class, 'review_id', 'id');
    }
}
