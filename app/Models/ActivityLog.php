<?php

namespace App\Models;

use App\Models\RequestBookingStatus;
use App\Models\UserDetail;
use App\Models\EntityTypes;
use App\Models\Shop;
use App\Models\Hospital;
use App\Models\Reviews;
use App\Models\Post;
use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $table = 'activity_log';    
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'entity_type_id', 'entity_id','request_booking_status_id','user_id','is_cancelled_by_shop','country'
    ];

    protected $casts = [
        'entity_type_id' => 'int',
        'entity_id' => 'int',
        'request_booking_status_id' => 'int',
        'user_id' => 'int',
        'is_cancelled_by_shop' => 'boolean',
        'country' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    protected $appends = ['request_booking_status_name', 'user_name','business_name','business_address','business_user_name','category_id'];

    public function getRequestBookingStatusNameAttribute()
    {
        $value = $this->attributes['request_booking_status_id'];
        $cancelByShop = $this->attributes['is_cancelled_by_shop'];

        $data = RequestBookingStatus::find($value);

        if($data) {
            if($data->id == RequestBookingStatus::CANCEL) {
                $name = $cancelByShop == 1 ? 'Cancelled by Business' : 'Cancelled by User';
            }else {
                $name = $data->name;
            }
        }else {
            $name = 'Reviews'; 
        }

        return $this->attributes['request_booking_status_name'] = $name;
        
    }

    public function getUserNameAttribute()
    {
        $value = $this->attributes['user_id'];

        $user = UserDetail::where('user_id',$value)->first();

        return $this->attributes['user_name'] = $user ? $user->name : '';
        
    }
    public function getBusinessNameAttribute()
    {
        $entity_type_id = $this->attributes['entity_type_id'];
        $entity_id = $this->attributes['entity_id'];
        $request_booking_status_id = $this->attributes['request_booking_status_id'];
        $business_name = '';
        if($entity_type_id == EntityTypes::SHOP && $request_booking_status_id != NULL){
            $shop = Shop::find($entity_id);
            $business_name = $shop ? $shop->main_name : '';
        } else if($entity_type_id == EntityTypes::HOSPITAL && $request_booking_status_id != NULL){
            $post = Post::find($entity_id);
            $hospital_id = $post ? $post->hospital_id : NULL;
            $hospital = Hospital::find($hospital_id);
            $business_name = $hospital ? $hospital->main_name : '';
        }elseif(($entity_type_id == EntityTypes::SHOP || $entity_type_id == EntityTypes::HOSPITAL) && $request_booking_status_id == NULL){
            $review = Reviews::find($entity_id);
            if($review && $review->entity_type_id == EntityTypes::SHOP){
                $shop = Shop::find($review->entity_id);
                $business_name = $shop ? $shop->main_name : '';
            } elseif($review && $review->entity_type_id == EntityTypes::SHOP){
                $post = Post::find($review->entity_id);
                $hospital_id = $post ? $post->hospital_id : NULL;
                $hospital = Hospital::find($hospital_id);
                $business_name = $hospital ? $hospital->main_name : '';
            }else{
                $business_name = '';
            }
        }else{
            $business_name = '';
        }

        return $this->attributes['business_name'] = $business_name;
        
    }
    public function getBusinessAddressAttribute()
    {
        $entity_type_id = $this->attributes['entity_type_id'];
        $entity_id = $this->attributes['entity_id'];
        $request_booking_status_id = $this->attributes['request_booking_status_id'];
        $business_address = '';
        if($entity_type_id == EntityTypes::SHOP && $request_booking_status_id != NULL){
            $shop = Shop::where('id',$entity_id)->first();
            $business_address = $shop ? $shop->address : '';
        } else if($entity_type_id == EntityTypes::HOSPITAL && $request_booking_status_id != NULL){
            $post = Post::find($entity_id);
            $hospital_id = $post ? $post->hospital_id : NULL;
            $hospital = Hospital::with(['address' => function($query) {
                $query->where('entity_type_id', EntityTypes::HOSPITAL);
            }])->where('id',$hospital_id)->first();
            $business_address = $hospital ? $hospital->address : '';
        }elseif(($entity_type_id == EntityTypes::SHOP || $entity_type_id == EntityTypes::HOSPITAL) && $request_booking_status_id == NULL){
            $review = Reviews::find($entity_id);
            if($review && $review->entity_type_id == EntityTypes::SHOP){
                $shop = Shop::where('id',$review->entity_id)->first();
                $business_address = $shop ? $shop->address : '';
            } elseif($review && $review->entity_type_id == EntityTypes::HOSPITAL){
                $post = Post::find($review->entity_id);
                $hospital_id = $post ? $post->hospital_id : NULL;
                $hospital = Hospital::with(['address' => function($query) {
                    $query->where('entity_type_id', EntityTypes::HOSPITAL);
                }])->where('id',$hospital_id)->first();
                $business_address = $hospital ? $hospital->address : '';
            }else{
                $business_address = '';
            }
        }else{
            $business_address = '';
        }

        $address = '';
        $address .= $business_address && $business_address->address ? $business_address->address.',' : '';
        $address .= $business_address && $business_address->address2 ? $business_address->address2.',' : '';
        $address .= $business_address && $business_address->city_name ? $business_address->city_name.',' : '';
        $address .= $business_address && $business_address->state_name ? $business_address->state_name.',' : '';
        $address .= $business_address && $business_address->country_name ? $business_address->country_name : '';

        return $this->attributes['business_address'] = $address;
        
    }
    public function getBusinessUserNameAttribute()
    {
        $entity_type_id = $this->attributes['entity_type_id'];
        $entity_id = $this->attributes['entity_id'];
        $request_booking_status_id = $this->attributes['request_booking_status_id'];
        $user_id = NULL;
        if($entity_type_id == EntityTypes::SHOP && $request_booking_status_id != NULL){
            $shop = Shop::find($entity_id);
            $user_id = $shop ? $shop->user_id : NULL;
        } else if($entity_type_id == EntityTypes::HOSPITAL && $request_booking_status_id != NULL){
            $post = Post::find($entity_id);
            $hospital_id = $post ? $post->hospital_id : NULL;
            $hospital = Hospital::find($hospital_id);
            $user_id = $hospital ? $hospital->user_id : NULL;
        }elseif(($entity_type_id == EntityTypes::SHOP || $entity_type_id == EntityTypes::HOSPITAL) && $request_booking_status_id == NULL){
            $review = Reviews::find($entity_id);
            if($review && $review->entity_type_id == EntityTypes::SHOP){
                $shop = Shop::find($review->entity_id);
                $user_id = $shop ? $shop->user_id : NULL;
            } elseif($review && $review->entity_type_id == EntityTypes::SHOP){
                $post = Post::find($review->entity_id);
                $hospital_id = $post ? $post->hospital_id : NULL;
                $hospital = Hospital::find($hospital_id);
                $user_id = $hospital ? $hospital->user_id : NULL;
            }else{
                $user_id = NULL;
            }
        }else{
            $user_id = NULL;
        }

        $user = UserDetail::where('user_id',$user_id)->first();

        return $this->attributes['business_user_name'] = $user ? $user->name : '';        
    }
    public function getCategoryIdAttribute()
    {
        $entity_type_id = $this->attributes['entity_type_id'];
        $entity_id = $this->attributes['entity_id'];
        $category_id = '';
        if($entity_type_id == EntityTypes::SHOP){
            $shop = Shop::find($entity_id);
            $category_id = $shop ? $shop->category_id : '';
        } else if($entity_type_id == EntityTypes::HOSPITAL){
            $post = Post::find($entity_id);
            $hospital_id = $post ? $post->hospital_id : NULL;
            $hospital = Hospital::find($hospital_id);
            $category_id = $hospital ? $hospital->category_id : '';
        }elseif($entity_type_id == EntityTypes::REVIEWS){
            $review = Reviews::find($entity_id);
            if($review && $review->entity_type_id == EntityTypes::SHOP){
                $shop = Shop::find($review->entity_id);
                $category_id = $shop ? $shop->category_id : '';
            } elseif($review && $review->entity_type_id == EntityTypes::SHOP){
                $post = Post::find($review->entity_id);
                $hospital_id = $post ? $post->hospital_id : NULL;
                $hospital = Hospital::find($hospital_id);
                $category_id = $hospital ? $hospital->category_id : '';
            }else{
                $category_id = '';
            }
        }else{
            $category_id = '';
        }

        return $this->attributes['category_id'] = $category_id;
        
    }
}
