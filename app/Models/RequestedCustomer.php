<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\UserDetail;
use App\Models\Reviews;
use App\Models\EntityTypes;
use App\Models\Post;
use App\Models\Shop;
use App\Models\CompletedCustomer;
use App\Models\RequestBookingStatus;
use Carbon\Carbon;
use DB;
use App\Models\UserCards;


class RequestedCustomer extends Model
{
    use SoftDeletes;
    protected $table = 'requested_customer';

    protected $dates = ['deleted_at'];

    protected $hidden = ['deleted_at'];

    protected $fillable = [
        'user_id',
        'entity_type_id',
        'entity_id',
        'comment',
        'request_booking_status_id',
        'is_cancelled_by_shop',
        'booking_date',
        'show_in_home',
        'created_at',
        'updated_at',
        'is_admin_read'
    ];


    protected $casts = [
        'user_id' => 'int',
        'entity_type_id' => 'int',
        'entity_id' => 'int',
        'request_booking_status_id' => 'int',
        'comment' => 'string',
        'is_cancelled_by_shop' => 'boolean',
        'show_in_home' => 'boolean',
        'booking_date' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    protected $appends = ['image','hospital_id','entity_user_id','user_name','user_image','request_booking_status_name','review_done','requested_for','requested_item_name','main_name','is_external_user','memo_completed','is_character_as_profile','user_applied_card'];

    public function getImageAttribute()
    {
        $entity_type_id = $this->attributes['entity_type_id'];
        $entity_id = $this->attributes['entity_id'];
        $image = '';

        if($entity_type_id == EntityTypes::SHOP) {
            $worplace_images = ShopImages::where('shop_id', $entity_id)->where('shop_image_type',ShopImagesTypes::WORKPLACE)->get(['id','image']);
            $image = $worplace_images && count($worplace_images) > 0 ? $worplace_images[0]->image : "";
        }else {
            $thumbnail = PostImages::where('post_id', $entity_id)->where('type',PostImages::THUMBNAIL)->select(['id','image'])->first();
            $image = (!empty($thumbnail) && !empty($thumbnail) && !empty($thumbnail->image)) ? $thumbnail->image : "";

        }

        return $image;

    }

    public function getHospitalIdAttribute()
    {
        $entity_type_id = $this->attributes['entity_type_id'];
        $entity_id = $this->attributes['entity_id'];

        if($entity_type_id == EntityTypes::SHOP) {
            $hospital_id = 0;
        }else {
            $post = Post::find($entity_id);
            $hospital_id = $post ? $post->hospital_id : 0;
        }

        return $this->attributes['hospital_id'] = $hospital_id;

    }
    public function getIsExternalUserAttribute()
    {
        $value = $this->attributes['user_id'];

        $user = User::find($value);

        return $this->attributes['is_external_user'] = $user && $user->email == NULL ? 1 : 0;

    }
    public function getUserNameAttribute()
    {
        $value = $this->attributes['user_id'];

        $user = UserDetail::where('user_id',$value)->first();

        return $this->attributes['user_name'] = $user ? $user->name : '';

    }
    public function getUserImageAttribute()
    {
        $value = $this->attributes['user_id'];

        $user = UserDetail::where('user_id',$value)->first();

        return $this->attributes['user_image'] = $user ? $user->avatar : '';

    }
    public function getRequestBookingStatusNameAttribute()
    {
        $value = $this->attributes['request_booking_status_id'];

        //$data = RequestBookingStatus::find($value);

        return $this->attributes['request_booking_status_name'] = $this->attributes['request_booking_status_id'];

    }

    public function getReviewDoneAttribute()
    {
        $bookingId = $this->attributes['id'];
        $userId = $this->attributes['user_id'];
        $entityTypeId = $this->attributes['entity_type_id'];

        if($entityTypeId == EntityTypes::HOSPITAL){
            $post = Post::find($this->attributes['entity_id']);
            $entityId = $post ? $post->hospital_id : 0;
        }else {
            $entityId = $this->attributes['entity_id'];
        }

        $bookingStatusId = $this->attributes['request_booking_status_id'];
        if($bookingStatusId == RequestBookingStatus::COMPLETE){
            $reviews = Reviews::where('user_id',$userId)->where('entity_type_id',$entityTypeId)
                    // ->where('requested_customer_id',$bookingId)
                    ->where('entity_id',$entityId)
                    ->whereDate('created_at', '>' , Carbon::now()->subDays(15))
                    ->count();


            return $this->attributes['review_done'] = $reviews;
        }

        return $this->attributes['review_done'] = 0;

    }
    public function getRequestedForAttribute()
    {
        $entityTypeId = $this->attributes['entity_type_id'];

        $entityType = EntityTypes::find($entityTypeId);
        return $this->attributes['requested_for'] = !empty($entityType) ? $entityType->name : '' ;

    }

    public function getCommentAttribute()
    {
        $value = !empty($this->attributes['comment']) ? $this->attributes['comment'] : "";

        return $this->attributes['comment'] = $value ? $value : '';

    }

    public function getRequestedItemNameAttribute()
    {
        $entityTypeId = $this->attributes['entity_type_id'];
        $name = '';
        if($entityTypeId == EntityTypes::HOSPITAL){
            $post = Post::find($this->attributes['entity_id']);
            $name = !empty($post) ? $post->title : '';
        }else {
            $shop = Shop::find($this->attributes['entity_id']);
            $name = !empty($shop) ? $shop->shop_name : '';
        }

        return $this->attributes['requested_item_name'] = $name;

    }
    public function getMainNameAttribute()
    {
        $entityTypeId = $this->attributes['entity_type_id'];
        $name = '';
        if($entityTypeId == EntityTypes::HOSPITAL){
            $post = Post::find($this->attributes['entity_id']);
            if($post) {
                $hospital = Hospital::find($post->hospital_id);
                $name = !empty($hospital) ? $hospital->main_name : '';
            }else {
                $name = '';
            }
        }else {
            $shop = Shop::find($this->attributes['entity_id']);
            $name = !empty($shop) ? $shop->main_name : '';
        }

        return $this->attributes['main_name'] = $name;

    }
    public function getEntityUserIdAttribute()
    {
        $entityTypeId = $this->attributes['entity_type_id'];
        $user_id = NULL;
        if($entityTypeId == EntityTypes::HOSPITAL){
            $post = Post::find($this->attributes['entity_id']);
            if($post) {
                $user_id = !empty($post) ? $post->user_id : '';
            }else {
                $user_id = null;
            }
        }else {
            $shop = Shop::find($this->attributes['entity_id']);
            $user_id = !empty($shop) ? $shop->user_id : '';
        }

        return $this->attributes['entity_user_id'] = $user_id;

    }
    public function getMemoCompletedAttribute()
    {
        $id = $this->attributes['id'];
        $memo = CompletedCustomer::where('requested_customer_id',$id)->first();

        return $this->attributes['memo_completed'] = $memo ? 1 : 0;

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

    public function getBookingDateAttribute($date)
    {
        $date = new Carbon($date);
        return $date->format('Y-m-d H:i:s');
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
}
