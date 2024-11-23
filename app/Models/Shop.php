<?php

namespace App\Models;

use Carbon\Carbon;
use App\Models\Status;
use App\Models\Address;
use App\Models\Reviews;
use App\Models\Category;
use App\Models\ShopPost;
use App\Models\ShopImages;
use App\Models\EntityTypes;
use App\Models\ShopFollowers;
use App\Models\ShopImagesTypes;
use App\Models\RequestedCustomer;
use Illuminate\Support\Facades\DB;
use App\Models\RequestBookingStatus;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\SoftDeletes;

class Shop extends Model
{
    use SoftDeletes;
    protected $table = 'shops';

    protected $dates = ['deleted_at'];

    protected $fillable = [
        'main_name',
        'shop_name',
        'email',
        'mobile',
        'speciality_of',
        'best_portfolio',
        'business_licence',
        'identification_card',
        'business_license_number',
        'status_id',
        'deactivate_by_user',
        'last_activate_deactivate',
        'user_id',
        'is_discount',
        'category_id',
        'recommended_code',
        'manager_id',
        'credit_deduct_date',
        'created_at',
        'updated_at',
        'business_link',
        'another_mobile',
        'booking_link',
        'chat_option',
        'uuid',
        'show_price',
        'show_address',
        'count_days',
        'is_regular_service',
        'last_count_updated_at',
        'is_show'
    ];

    protected $casts = [
        'main_name' => 'string',
        'shop_name' => 'string',
        'email' => 'string',
        'speciality_of' => 'string',
        'best_portfolio' => 'string',
        'business_licence' => 'string',
        'identification_card' => 'string',
        'business_license_number' => 'string',
        'user_id' => 'int',
        'status_id' => 'int',
        'is_discount' => 'boolean',
        'mobile' => 'string',
        'category_id' => 'int',
        'recommended_code' => 'string',
        'booking_link' => 'string',
        'chat_option' => 'int',
        'manager_id' => 'int',
        'credit_deduct_date' => 'date',
        'last_activate_deactivate' => 'date',
        'deactivate_by_user' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    protected $appends = ['address','rating','category_name','category_icon','status_name','thumbnail_image','work_complete','portfolio','reviews','reviews_list','followers','main_profile_images','workplace_images','portfolio_images','is_follow','deeplink','is_block','instagram_status'];

    const MEAROUND_CHAT = 0;
    const EXTERNAL_CHAT = 1;
    const HIDE_CHAT = 2;

    public function getAddressAttribute()
    {
        $value = $this->attributes['id'];

        $emptyObject = new Address();
        $emptyObject->entity_type_id = EntityTypes::SHOP;
        $emptyObject->entity_id = $value;
        $emptyObject->address = '';
        $emptyObject->address2 = '';
        $emptyObject->zipcode = '';
        $emptyObject->latitude = 0;
        $emptyObject->longitude = 0;
        $emptyObject->country_id = 0;
        $emptyObject->state_id = 0;
        $emptyObject->city_id = 0;
        $emptyObject->main_address = 0;
        $emptyObject->main_country = '';

        $address = Address::where('entity_type_id',EntityTypes::SHOP)->where('entity_id',$value)->first();
        return $this->attributes['address'] = !empty($address) ? $address : $emptyObject;
    }

    public function category()
    {
        return $this->hasOne(Category::class, 'id', 'category_id');
    }

    public function shopFollowers()
    {
        return $this->hasMany(ShopFollowers::class, 'shop_id', 'id');
    }

    public function shopPostList()
    {
        return $this->hasMany(ShopPost::class, 'shop_id', 'id')->orderBy('post_order_date','desc')->orderBy('id','desc');
    }

    public function shopreviews()
    {
        return $this->hasMany(Reviews::class, 'entity_id', 'id')->where('entity_type_id', EntityTypes::SHOP);
    }

    public function completedCustomer()
    {
        return $this->hasMany(RequestedCustomer::class, 'entity_id', 'id')->where('entity_type_id', EntityTypes::SHOP)->where('request_booking_status_id', RequestBookingStatus::COMPLETE);
    }

    public function getMobileAttribute($mobile)
    {
        return $mobile ? $mobile : "";
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

    public function getMainNameAttribute($main_name)
    {

        return $main_name ? $main_name : '';
    }

    public function getCategoryIconAttribute()
    {
        $value = $this->attributes['category_id'];
        $category = Category::find($value);
        if (empty($category)) {
            return $this->attributes['category_icon'] = '';
        } else {
            if (!filter_var($value, FILTER_VALIDATE_URL)) {
                return $this->attributes['category_icon'] = $category->logo;
            } else {
                return $this->attributes['category_icon'] = $category->logo;
            }
        }
    }

    public function getStatusNameAttribute()
    {
        $value = $this->attributes['status_id'];

        $status = Status::find($value);

        return $this->attributes['status_name'] = $status->name;

    }
    public function getCategoryNameAttribute()
    {
        $value = $this->attributes['category_id'];

        $category = Category::find($value);

        return $this->attributes['category_name'] = $category->name ?? '';

    }
    public function getBestPortfolioAttribute()
    {
        $value = !empty($this->attributes['best_portfolio']) ? $this->attributes['best_portfolio'] : "";
        if (empty($value)) {
            return $this->attributes['best_portfolio'] = '';
        } else {
            if (!filter_var($value, FILTER_VALIDATE_URL)) {
                return $this->attributes['best_portfolio'] = Storage::disk('s3')->url($value);
            } else {
                return $this->attributes['best_portfolio'] = $value;
            }
        }
    }

    public function getBusinessLicenceAttribute()
    {
        $value = !empty($this->attributes['business_licence']) ? $this->attributes['business_licence'] : "";
        if (empty($value)) {
            return $this->attributes['business_licence'] = '';
        } else {
            if (!filter_var($value, FILTER_VALIDATE_URL)) {
                return $this->attributes['business_licence'] = Storage::disk('s3')->url($value);
            } else {
                return $this->attributes['business_licence'] = $value;
            }
        }
    }
    public function getIdentificationCardAttribute()
    {
        $value = !empty($this->attributes['identification_card']) ? $this->attributes['identification_card'] : "";
        if (empty($value)) {
            return $this->attributes['identification_card'] = '';
        } else {
            if (!filter_var($value, FILTER_VALIDATE_URL)) {
                return $this->attributes['identification_card'] = Storage::disk('s3')->url($value);
            } else {
                return $this->attributes['identification_card'] = $value;
            }
        }
    }

    public function getRatingAttribute()
    {
        $value = $this->attributes['id'];

        $rating = DB::table('reviews')->where('deleted_at')->where('entity_type_id',EntityTypes::SHOP)->where('entity_id',$value)->avg('rating');
        return $this->attributes['rating'] = $rating ? number_format($rating,1) : "0";

    }

    public function getReviewsAttribute()
    {
        $value = $this->attributes['id'];
        $reviews = DB::table('reviews')->where('deleted_at')->where('entity_type_id', EntityTypes::SHOP)->where('entity_id', $value)->count();

        return $this->attributes['reviews'] = $reviews;
    }

    public function getReviewsListAttribute()
    {
        $value = $this->attributes['id'];
        $reviews = Reviews::where('entity_type_id', EntityTypes::SHOP)->where('entity_id', $value)->paginate(config('constant.pagination_count'),"*","reviews_list_page");
        //$reviews = $reviews->makeHidden('comments');
        foreach($reviews as $review) {
            $temp = [
                'main_name' => $this->attributes['main_name'],
                'shop_name' => $this->attributes['shop_name'],
                'category_name' => $this->attributes['category_name'],
            ];

            $review['shop_detail'] = $temp;
        }

        return $this->attributes['reviews_list'] = $reviews;
    }
    public function getFollowersAttribute()
    {
        $value = $this->attributes['id'];
        $followers = ShopFollowers::where('shop_id', $value)->count();

        return $this->attributes['followers'] = $followers;
    }

    public function getMainProfileImagesAttribute()
    {
        $value = $this->attributes['id'];
        $main_profile_images = ShopImages::where('shop_id', $value)->where('shop_image_type',ShopImagesTypes::MAINPROFILE)->get(['id','image']);
        $images = [];
        if (empty($main_profile_images)) {
            return $this->attributes['main_profile_images'] = $images;
        } else {
            return $this->attributes['main_profile_images'] = $main_profile_images;
        }
    }
    public function getThumbnailImageAttribute()
    {
        $value = $this->attributes['id'];
        $thumbnail = ShopImages::where('shop_id', $value)->where('shop_image_type',ShopImagesTypes::THUMB)->select(['id','image'])->first();
        $images = (object)[];
        if (empty($thumbnail)) {
            return $this->attributes['thumbnail_image'] = $images;
        } else {
            return $this->attributes['thumbnail_image'] = $thumbnail;
        }
    }
    public function getWorkplaceImagesAttribute()
    {
        $value = $this->attributes['id'];
        $worplace_images = ShopImages::where('shop_id', $value)->where('shop_image_type',ShopImagesTypes::WORKPLACE)->get(['id','image']);
        $images = [];
        if (empty($worplace_images)) {
            return $this->attributes['workplace_images'] = $images;
        } else {

            return $this->attributes['workplace_images'] = $worplace_images;
        }
    }

    public function getPortfolioImagesAttribute()
    {
        $shop_detail_per_page = Config::get('shop_detail_per_page');
        $per_page = (!empty($shop_detail_per_page) && $shop_detail_per_page > 0 ) ? $shop_detail_per_page : 9;
        $value = $this->attributes['id'];
        //config('constant.pagination_count')
        $portfolio_images = ShopPost::where('shop_id', $value)->orderBy('post_order_date','desc')->orderBy('id','desc')->paginate($per_page,"*","portfolio_images_page");

        return $this->attributes['portfolio_images'] = $portfolio_images;
    }
    public function getPortfolioAttribute()
    {
        $value = $this->attributes['id'];
        $portfolio_images = ShopPost::where('deleted_at')->where('shop_id', $value)->count();

        return $this->attributes['portfolio'] = $portfolio_images;
    }

    public function getWorkCompleteAttribute()
    {
        $value = $this->attributes['id'];
        $work_complete = DB::table('requested_customer')->where('deleted_at')->where('entity_type_id',EntityTypes::SHOP)
                        ->where('entity_id',$value)
                        ->where('request_booking_status_id',RequestBookingStatus::COMPLETE)->count();

        return $this->attributes['work_complete'] = $work_complete;
    }

    public function getIsFollowAttribute()
    {
        $value = $this->attributes['id'];
        $user = Auth::user();
        if($user) {
            $followers = ShopFollowers::where('shop_id', $value)->where('user_id',$user->id)->count();
            return $this->attributes['is_follow'] = $followers > 0 ? 1 : 0;
        }else {
            return $this->attributes['is_follow'] = 0;
        }
    }

    public function getDeeplinkAttribute(){
        return $this->attributes['deeplink'] = getDeepLink(config('constant.shop_detail'),$this->attributes['id']);
    }

    public function getIsBlockAttribute(){
        $shopID = $this->attributes['id'];
        $user = Auth::user();
        if($user){
            $block = DB::table('shop_block_histories')->where('user_id',$user->id)->where('is_block',1)->where('shop_id',$shopID)->first();
            $is_block = !empty($block) ? true : false;
        }else{
            $is_block = false;
        }
        return $this->attributes['is_block'] = $is_block;
    }

    public function shopLanguageDetails() {
        return $this->hasMany(ShopDetailLanguage::class, 'shop_id', 'id');
    }

    public function getInstagramStatusAttribute(){
        $shopID = $this->attributes['id'];
        $linked_social_profiles = LinkedSocialProfile::where('shop_id',$shopID)->first();
        if (!empty($linked_social_profiles)){
            if ($linked_social_profiles->is_valid_token == 0){
                $status = "Yellow";
            }
            else {
                $status = "Green";
            }
        }
        else {
            $status = "Red";
        }

        return $this->attributes['instagram_status'] = $status;
    }

}
