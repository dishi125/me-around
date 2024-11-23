<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use App\Models\Category;
use App\Models\Address;
use App\Models\Status;
use App\Models\HospitalImages;
use App\Models\Doctor;
use App\Models\Review;
use App\Models\HospitalDoctor;
use App\Models\Post;
use App\Models\UserEntityRelation;
use App\Models\EntityTypes;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class Hospital extends Model
{
    use SoftDeletes;
    protected $table = 'hospitals';
    protected $dates = ['deleted_at'];
    
    protected $fillable = [
        'main_name',
        'email',
        'mobile',
        'recommended_code',
        'interior_photo',
        'business_licence',
        'business_license_number',
        'status_id',
        'deactivate_by_user',
        'category_id',
        'description',
        'manager_id',
        'credit_deduct_date',
        'last_activate_deactivate',
        'created_at',
        'updated_at',
        'business_link'
    ];

    protected $casts = [
        'main_name' => 'string',
        'email' => 'string',
        'recommended_code' => 'string',
        'interior_photo' => 'string',
        'business_licence' => 'string',
        'status_id' => 'int',
        'deactivate_by_user' => 'boolean',
        'mobile' => 'string',
        'description' => 'string',
        'business_license_number' => 'string',
        'manager_id' => 'int',
        'category_id' => 'int',
        'credit_deduct_date' => 'date',
        'last_activate_deactivate' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'business_link' => 'string'
    ];

    protected $appends = ['user_id','category_name', 'category_icon', 'work_complete','activate_post','hospital_avg_rating', 'reviews','status_name','interior_photo_url','business_licence_url','images','posts_list','reviews_list','doctors_list'];

    public function address()
    {
        return $this->hasOne(Address::class, 'entity_id', 'id')->where('entity_type_id',EntityTypes::HOSPITAL);
    }

    public function category()
    {
        return $this->hasOne(Category::class, 'id', 'category_id');
    }

    public function getUserIdAttribute()
    {
        $value = $this->attributes['id'];

        $user = UserEntityRelation::where('entity_type_id', EntityTypes::HOSPITAL)->where('entity_id', $value)->first();

        return $this->attributes['user_id'] = $user->user_id;
        
    }
    public function getCategoryNameAttribute()
    {
        $value = $this->attributes['category_id'];

        $category = Category::find($value);

        return $this->attributes['category_name'] = $category->name ?? '';
        
    }

    public function getCategoryIconAttribute()
    {
        $value = $this->attributes['category_id'];
        $category = Category::find($value);
        if (empty($category)) {
            return $this->attributes['category_icon'] = '';
        } else {
            if (!filter_var($value, FILTER_VALIDATE_URL)) {
                return $this->attributes['category_icon'] = Storage::disk('s3')->url($category->logo);
            } else {
                return $this->attributes['category_icon'] = $category->logo;
            }
        }        
    }

    public function getHospitalAvgRatingAttribute()
    {
        $value = $this->attributes['id'];

        $hospitalPostsIDs = DB::table('posts')->whereNull('deleted_at')->where('hospital_id',$value)->pluck('id')->toArray();
        $rating = DB::table('reviews')->whereNull('deleted_at')->where('entity_type_id',EntityTypes::HOSPITAL)
                    ->whereIn('entity_id',$hospitalPostsIDs)->pluck('rating')->avg();

        return $this->attributes['hospital_avg_rating'] = $rating ? number_format((float)$rating, 2, '.', '') : "0";
        
    }

    public function getReviewsAttribute()
    {
        $value = $this->attributes['id'];
        $reviews = DB::table('reviews')->join('posts', function ($join) use ($value) {
            $join->on('posts.id', '=', 'reviews.entity_id')
            ->where('posts.hospital_id', $value);
        })
        ->whereNull('posts.deleted_at')
        ->whereNull('reviews.deleted_at')
        ->where('reviews.entity_type_id', EntityTypes::HOSPITAL)->count();


        return $this->attributes['reviews'] = $reviews;
    }

    public function getWorkCompleteAttribute()
    {
        $value = $this->attributes['id'];
        $work_complete = DB::table('requested_customer')->join('posts','posts.id','requested_customer.entity_id')
            ->where('posts.hospital_id',$value)
            ->where('requested_customer.entity_type_id',EntityTypes::HOSPITAL)
            ->where('requested_customer.request_booking_status_id', RequestBookingStatus::COMPLETE)
            ->whereNull('requested_customer.deleted_at')
            ->count();

        return $this->attributes['work_complete'] = $work_complete;
    }
    public function getActivatePostAttribute()
    {
        $value = $this->attributes['id'];

        $userPlanPostCount = DB::table('user_entity_relation')->join('users_detail','users_detail.user_id','user_entity_relation.user_id')
                    ->join('credit_plans','credit_plans.package_plan_id','users_detail.package_plan_id')
                    ->where('user_entity_relation.entity_type_id', EntityTypes::HOSPITAL)->where('user_entity_relation.entity_id',$value)
                    ->select('credit_plans.package_plan_id','credit_plans.no_of_posts','credit_plans.no_of_posts', 'user_entity_relation.entity_id as hospital_id')
                    ->first();
                    
        $query = DB::table('posts')->where('hospital_id',$value)->where('status_id',Status::ACTIVE)->whereNull('deleted_at');

        if(!empty($userPlanPostCount)){
            $query->limit($userPlanPostCount->no_of_posts);
        }

        $activate_post = count($query->get());
        return $this->attributes['activate_post'] = $activate_post;
    }

    public function getInteriorPhotoUrlAttribute()
    {
        $value = $this->attributes['interior_photo'];
        if (empty($value)) {
            return $this->attributes['interior_photo_url'] = '';
        } else {
            if (!filter_var($value, FILTER_VALIDATE_URL)) {
                return $this->attributes['interior_photo_url'] = Storage::disk('s3')->url($value);
            } else {
                return $this->attributes['interior_photo_url'] = $value;
            }
        }
    }

    public function getBusinessLicenceUrlAttribute()
    {
        $value = $this->attributes['business_licence'];
        if (empty($value)) {
            return $this->attributes['business_licence_url'] = '';
        } else {
            if (!filter_var($value, FILTER_VALIDATE_URL)) {
                return $this->attributes['business_licence_url'] = Storage::disk('s3')->url($value);
            } else {
                return $this->attributes['business_licence_url'] = $value;
            }
        }
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

    public function getStatusNameAttribute()
    {
        $value = $this->attributes['status_id'];

        $status = Status::find($value);

        return $this->attributes['status_name'] = $status->name;
        
    }

    public function getImagesAttribute()
    {
        $value = $this->attributes['id'];
        $hospital_images = HospitalImages::where('hospital_id', $value)->get(['id','image']);
        $images = [];
        if (empty($hospital_images)) {
            return $this->attributes['images'] = $images;
        } else {            
            
            return $this->attributes['images'] = $hospital_images;
        }
    }

    public function getReviewsListAttribute()
    {
        $value = $this->attributes['id'];
        $reviews = Reviews::join('posts', function ($join) use ($value) {
            $join->on('posts.id', '=', 'reviews.entity_id')
            ->where('posts.hospital_id', $value);
        })
        ->join('hospitals', function ($join) {
            $join->on('posts.hospital_id', '=', 'hospitals.id');
        })
        ->leftjoin('addresses', function ($join) {
            $join->on('hospitals.id', '=', 'addresses.entity_id')
                ->where('addresses.entity_type_id', EntityTypes::HOSPITAL);
        })
        ->whereNull('posts.deleted_at')
        ->where('reviews.entity_type_id', EntityTypes::HOSPITAL)->select('reviews.*','posts.id as post_id')
        ->paginate(config('constant.pagination_count'),"*","reviews_list_page");

        $data = $reviews->makeHidden(['review_categories','comments']);
        $reviews->data = $data;

        foreach($reviews as $rp) {
            $post = DB::table('posts')->join('category','category.id','posts.category_id')
                ->where('posts.id',$rp->post_id)
                ->whereNull('posts.deleted_at')
                ->select('posts.title','category.name as category_name')
                ->first();
            $rp['post_detail'] = $post;
        }
        return $this->attributes['reviews_list'] = $reviews;
    }

    public function getPostsListAttribute()
    {
        $value = $this->attributes['id'];

        $userPlanPostCount = DB::table('user_entity_relation')->join('users_detail','users_detail.user_id','user_entity_relation.user_id')
                    ->join('credit_plans','credit_plans.package_plan_id','users_detail.package_plan_id')
                    ->where('user_entity_relation.entity_type_id', EntityTypes::HOSPITAL)->where('user_entity_relation.entity_id',$value)
                    ->select('credit_plans.package_plan_id','credit_plans.no_of_posts','credit_plans.no_of_posts', 'user_entity_relation.entity_id as hospital_id')
                    ->first();

        $query = Post::where('hospital_id',$value)->where('status_id',Status::ACTIVE);

        
        if(!empty($userPlanPostCount)){
            $query->limit($userPlanPostCount->no_of_posts);
        }
        $posts = $query->get();

        $posts = $posts->makeHidden(['reviews_list','hospital_images','hospital_avg_rating','coin_not_enough','request','saved_count','views_count']);
        // $posts = [];

        return $this->attributes['posts_list'] = $posts;
    }

    public function getDoctorsListAttribute()
    {
        $value = $this->attributes['id'];
        $doctors = Doctor::join('hospital_doctors','hospital_doctors.doctor_id','doctors.id')
                        ->where('hospital_doctors.hospital_id', $value)->get('doctors.*');

        return $this->attributes['doctors_list'] = $doctors;
    }
}
