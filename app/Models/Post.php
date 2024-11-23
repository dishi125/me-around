<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;
use App\Models\Category;
use App\Models\Hospital;
use App\Models\Status;
use App\Models\PostImages;
use App\Models\RequestedCustomer;
use App\Models\SliderPosts;
use App\Models\Reviews;
use App\Models\PostLanguage;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use \App\Models\UserSavedHistory;
use \App\Models\SavedHistoryTypes;
use \App\Models\UserEntityRelation;
use \App\Models\UserDetail;
use \App\Models\CreditPlans;
use \App\Models\UserCredit;
use \App\Models\EntityTypes;
use Illuminate\Support\Facades\Auth;

class Post extends Model
{
    use SoftDeletes;
    protected $table = 'posts';

    protected $dates = ['deleted_at'];
    protected $hidden = ['deleted_at'];

    protected $fillable = [
        'hospital_id',
        'title',
        'sub_title',
        'from_date',
        'to_date',
        'before_price',
        'final_price',
        'currency_id',
        'discount_percentage',
        'category_id',
        'is_discount',
        'status_id',
        'view_count',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'hospital_id' => 'int',
        'title' => 'string',
        'sub_title' => 'string',
        'from_date' => 'datetime',
        'to_date' => 'datetime',
        'before_price' => 'decimal:0',
        'final_price' => 'decimal:0',
        'currency_id' => 'int',
        'discount_percentage' => 'float',
        'category_id' => 'int',
        'is_discount' => 'boolean',
        'status_id' => 'int',
        'view_count' => 'int',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    protected $appends = ['user_id','saved_count','is_saved_in_history','hospital_name','location','category_name','status_name','thumbnail_url','main_images','request','top_post','rating','reviews_list','currency_name','hospital_images','hospital_avg_rating','coin_not_enough'];

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
    public function getFromDateAttribute($date)
    {
        $date = new Carbon($date);
        return $date->format('Y-m-d');
    }

    public function getToDateAttribute($date)
    {
        $date = new Carbon($date);
        return $date->format('Y-m-d');
    }

    public function category()
    {
        return $this->hasOne(Category::class, 'id', 'category_id');
    }

    public function postreviews()
    {
        return $this->hasMany(Reviews::class, 'entity_id', 'id')->where('entity_type_id', EntityTypes::HOSPITAL);
    }

    public function getUserIdAttribute()
    {
        $value = $this->attributes['hospital_id'];

        $entityRelation = UserEntityRelation::where('entity_type_id',EntityTypes::HOSPITAL)->where('entity_id',$value)->first();

        return $this->attributes['user_id'] = $entityRelation->user_id;
        
    }
    public function getHospitalNameAttribute()
    {
        $value = $this->attributes['hospital_id'];

        $hospital = Hospital::find($value);

        return $this->attributes['hospital_name'] = $hospital->main_name;
        
    }

    public function getCurrencyNameAttribute()
    {
        $value = isset($this->attributes['currency_id']) ? $this->attributes['currency_id'] : null;

        if($value) {
            $currency = Currency::find($value);
            return $this->attributes['currency_name'] = $currency ? $currency->name : '';
        } else {

            return $this->attributes['currency_name'] = '';
        }
    }

    public function getLocationAttribute()
    {
        $value = $this->attributes['hospital_id'];

        $hospital = Hospital::with(['address' => function($query) {
            $query->where('entity_type_id', EntityTypes::HOSPITAL);
        }])->where('id',$value)->first();

        return $this->attributes['location'] = $hospital->address;
        
    }

    public function getCategoryNameAttribute()
    {
        $value = $this->attributes['category_id'];

        $category = Category::find($value);

        return $this->attributes['category_name'] = $category ? $category->name : '';
        
    }

    public function getStatusNameAttribute()
    {
        $value = $this->attributes['status_id'];

        $status = Status::find($value);

        return $this->attributes['status_name'] = $status->name;
        
    }

    public function getThumbnailUrlAttribute()
    {
        $value = $this->attributes['id'];
        $thumbnail = PostImages::where('post_id', $value)->where('type',PostImages::THUMBNAIL)->select(['id','image'])->first();
        $images = (object)[];
        if (empty($thumbnail)) {
            return $this->attributes['thumbnail_url'] = $images;
        } else {  
           
            return $this->attributes['thumbnail_url'] = $thumbnail;
        }
    }

    public function getMainImagesAttribute()
    {
        $value = $this->attributes['id'];
        $mainImages = PostImages::where('post_id', $value)->where('type',PostImages::MAINPHOTO)->groupBy('post_language_id')->get();
        $images = [];
        foreach($mainImages as $image){
            $temp['language_id'] = $image->post_language_id;
            $language = PostLanguage::find($image->post_language_id);
            $temp['language_name'] = $language ? $language->name : '';
            $temp['language_icon'] = $language ? $language->icon : '';
            $temp['photos'] = PostImages::where('post_id', $value)->where('type',PostImages::MAINPHOTO)->where('post_language_id',$image->post_language_id)->get();
            $images[] = $temp;
        }
        // if (empty($mainImages)) {
        //     return $this->attributes['main_images'] = $images;
        // } else {  
           
            return $this->attributes['main_images'] = $images;
        // }
    }

    public function getRequestAttribute()
    {
        $value = $this->attributes['id'];

        $request = RequestedCustomer::where('entity_type_id',EntityTypes::HOSPITAL)->where('entity_id',$value)->count();

        return $this->attributes['request'] = $request;
        
    }

    public function getTopPostAttribute()
    {
        $value = $this->attributes['id'];

        $topPost = SliderPosts::where('entity_type_id',EntityTypes::HOSPITAL)->where('post_id',$value)->count();

        return $this->attributes['top_post'] = $topPost == 0 ? false : true;        
    }

    public function getRatingAttribute()
    {
        $value = $this->attributes['id'];

        $rating = Reviews::where('entity_type_id',EntityTypes::HOSPITAL)->where('entity_id',$value)->avg('rating');
        return $this->attributes['rating'] = $rating ? number_format($rating,1) : "0";
        
    }

    public function getReviewsListAttribute()
    {
        $value = $this->attributes['id'];

        $reviews = Reviews::where('entity_type_id',EntityTypes::HOSPITAL)->where('entity_id',$value)->paginate(config('constant.pagination_count'),"*","reviews_list_page");
        return $this->attributes['reviews_list'] = $reviews;
        
    }

    public function getSavedCountAttribute()
    {
        $value = $this->attributes['id'];
        $count = UserSavedHistory::where('saved_history_type_id',SavedHistoryTypes::HOSPITAL)->where('entity_id',$value)->where('is_like',1)->count();

        return $this->attributes['saved_count'] = $count;             
    }
    public function getIsSavedInHistoryAttribute()
    {
        $value = $this->attributes['id'];
        $user = Auth::user();
        if($user) {
            $count = UserSavedHistory::where('saved_history_type_id',SavedHistoryTypes::HOSPITAL)
            ->where('is_like',1)
            ->where('entity_id',$value)
            ->where('user_id',$user->id)
            ->count();
        }else {
            $count = 0;
        }

        return $this->attributes['is_saved_in_history'] = $count > 0 ? true : false;             
    }

    public function getHospitalImagesAttribute()
    {
        $value = $this->attributes['hospital_id'];

        $hospital = Hospital::find($value);
        return $this->attributes['hospital_images'] = $hospital->images;
        
    }
    public function getHospitalAvgRatingAttribute()
    {
        $value = $this->attributes['hospital_id'];

        $hospital = Hospital::find($value);
        $hospitalPostsIDs = Post::where('hospital_id',$hospital->id)->pluck('id')->toArray();
        $rating = Reviews::where('entity_type_id',EntityTypes::HOSPITAL)
                    ->whereIn('entity_id',$hospitalPostsIDs)->pluck('rating')->avg();
                    
                    return $this->attributes['hospital_avg_rating'] = $rating ? number_format((float)$rating, 1, '.', '') : "0.0";
        
    }
    public function getCoinNotEnoughAttribute()
    {
        $value = $this->attributes['hospital_id'];

        $hospital = Hospital::find($value);
        $user_detail = UserDetail::where('user_id', $hospital->user_id)->first();
        $creditPlan = CreditPlans::where('entity_type_id', EntityTypes::HOSPITAL)->where('package_plan_id', $user_detail->package_plan_id)->first();
        $userCredits = UserCredit::where('user_id',$hospital->user_id)->first(); 
        $minHospitalCredit = $creditPlan ? $creditPlan->amount : 0;
        $coinNotEnough = $userCredits->credits < $minHospitalCredit ? 1 : 0;                    
        return $this->attributes['coin_not_enough'] = $coinNotEnough;
        
    }

    public function shopLanguageDetails() {
        return $this->hasMany(ShopDetailLanguage::class, 'shop_id', 'id');
    }
}
