<?php

namespace App\Models;

use App\Models\RequestBookingStatus;
use App\Models\UserDetail;
use App\Models\EntityTypes;
use App\Models\Shop;
use App\Models\Hospital;
use App\Models\Reviews;
use App\Models\Post;
use App\Models\RequestedCustomer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use DB;

class Notice extends Model
{
    use SoftDeletes;
    protected $dates = ['deleted_at'];
    protected $table = 'notices';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */

    const COMMUNITY_POST_COMMENT = 'community_post_comment';
    const COMMUNITY_REPLY_COMMENT = 'community_reply_comment';
    const COMMUNITY_POST_LIKE = 'community_post_like';
    const BOOKING = 'booking';
    const BOOKING_CANCEL = 'booking_cancel';
    const REPORT = 'report';
    const AREA_CHANGE = 'area_change';
    const ADMIN_NOTICE = 'admin_notice';
    const HOUR_2_BEFORE_VISIT = 'hour_2_before_visit';
    const NOSHOW = 'noshow';
    const HOUR_1_BEFORE_VISIT = 'hour_1_before_visit';
    const OUTSIDE_HOUR_1_BEFORE_VISIT = 'outside_hour_1_before_visit';
    const VISIT = 'visit';
    const OUTSIDE_VISIT = 'outside_visit';
    const OUT_OF_COINS = 'out_of_coins';
    const PROFILE_ACTIVATE = 'profile_activate';
    const PROFILE_DEACTIVATE = 'profile_deactivate';
    const PROFILE_PENDING = 'profile_pending';
    const PROFILE_HIDE = 'profile_hide';
    const PROFILE_UNHIDE = 'profile_unhide';
    const FOLLOW = 'follow';
    const FOLLOWED_BUSINESS = 'followed_business';
    const WRITE_REVIEW = 'write_review';
    const POST_EXPIRE = 'post_expire';
    const REWARD_INSTAGRAM = 'reward_instagram';
    const REWARD_RECOMMENDED = 'reward_recommended';
    const REWARD_RECOMMENDED_ONCE = 'reward_recommended_once';
    const INQUIRY_COIN_DEDUCT = 'inquiry_coin_deducted';
    const MONTHLY_COIN_DEDUCT = 'monthly_coin_deducted';
    const ADD_MULTI_PROFILE = 'add_multi_profile';
    const POST_LIKE = 'post_like';
    const ADMIN_SETTING_CHANGE_NOTIFICATION = 'admin_setting_change_notification';
    const REVIEW_POST_COMMENT = 'review_post_comment';
    const REVIEW_REPLY_COMMENT = 'review_reply_comment';
    const RELOAD_COIN_REQUEST = 'reload_coin_request';
    const RELOAD_COIN_REQUEST_ACCEPTED = 'reload_coin_request_accepted';
    const RELOAD_COIN_REQUEST_REJECTED = 'reload_coin_request_rejected';
    const BECAME_BUSINESS_USER = 'became_business_user';
    const ADDED_AS_CLIENT = 'added_as_client';
    const SNS_REWARD = 'sns_reward';
    const SNS_PENALTY = 'sns_penalty';
    const SNS_REJECT = 'sns_reject';
    const ASSOCIATION_COMMUNITY_COMMENT = 'association_community_comment';
    const ASSOCIATION_COMMUNITY_COMMENT_REPLY = 'association_community_comment_reply';
    const JOIN_ASSOCIATION = 'join_association';
    const ASSOCIATION_ADDED = 'association_added';
    const ASSOCIATION_DISCONNECTED = 'association_disconnected';
    const BECAME_PRESIDENT = 'became_president';
    const BECAME_MANAGER = 'became_manager';
    const ASSOCIATION_COMMUNITY_POST = 'association_community_post';

    const UPLOAD_SHOP_POST= 'upload_shop_post';
    const CONNECTING_FIRST_TIME_IN_DAY = 'connecting_first_time_in_day';
    const LEVEL_UP = 'level_up';
    const UPLOAD_COMMUNITY_POST = 'upload_community_post';
    const LIKE_COMMUNITY_OR_REVIEW_POST = 'like_community_or_review_post';
    const REVIEW_SHOP_POST = 'review_shop_post';
    const REVIEW_HOSPITAL_POST = 'review_hospital_post';
    const LIKE_SHOP_POST = 'like_shop_post';
    const COMMENT_ON_COMMUNITY_POST = 'comment_on_community_post';
    const NEW_CARD_ACQUIRED = 'new_card_acquired';
    const GIFTICON = 'gifticon';
    const NUMBER050 = '050_number';

    const SELL_CARD_SUCCESS = 'sell_card_success';
    const SELL_DEFAULT_CARD_SUCCESS = 'sell_default_card_success';
    const SELL_CARD_REJECT = 'sell_card_reject';
    const GIVE_REFERRAL_EXP = "give_referral_exp";
    const USER_MISSED_CARD = "user_missed_card";
    const DEAD_CARD = "dead_card";
    const LOVE_REFERRAL = "love_referral";

    // const REVIEW_POST_LIKE = 'review_post_like';

    protected $fillable = [
        'notify_type', 'title','sub_title','entity_type_id','entity_id','user_id','to_user_id','is_read','is_aninomity'
    ];

    protected $casts = [
        'notify_type' => 'string',
        'title' => 'string',
        'sub_title' => 'string',
        'entity_type_id' => 'int',
        'entity_id' => 'int',
        'user_id' => 'int',
        'to_user_id' => 'int',
        'is_read' => 'boolean',
        'is_aninomity' =>'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    protected $appends = ['user_name','user_avatar','is_character_as_profile','user_applied_card', 'thumbnail_user_applied_card'];

    public function getUserAppliedCardAttribute()
    {
        $id = $this->attributes['user_id'] ?? NULL;
        $card = [];
        if(!empty($id)){
            $card = getUserAppliedCard($id);
        }
        return $this->attributes['user_applied_card'] = $card;
    }

    public function getThumbnailUserAppliedCardAttribute()
    {
        $id = $this->attributes['user_id'] ?? NULL;
        $card = [];
        if(!empty($id)){
            $card = getThumbnailUserAppliedCard($id);
        }
        return $this->attributes['thumbnail_user_applied_card'] = $card;
    }

    public function getIsCharacterAsProfileAttribute()
    {
        $id = $this->attributes['user_id'] ?? NULL;
        $is_character_as_profile = 1;
        if(!empty($id)){
            $userDetail = DB::table('users_detail')->where('user_id',$id)->first('is_character_as_profile');
            $is_character_as_profile = $userDetail ? $userDetail->is_character_as_profile : 1;
        }
        return $this->attributes['is_character_as_profile'] = $is_character_as_profile;
    }

    public function getUserNameAttribute()
    {
        $value = $this->attributes['user_id'];
        $is_aninomity = $this->attributes['is_aninomity'];

        $user = UserDetail::where('user_id',$value)->first();
        $gender = '';
        $name = '';
        if(!empty($user)){
            $gender = (!empty($user->gender) && ($user->gender == 'Male')) ? 'Mr.' : 'Ms.';
            $name = $user->name;
        }

        if(empty($user)){
            $manager = Manager::where('user_id',$value)->first();
            $name = ($manager && $manager->name) ? $manager->name : '';
        }

        $userName = (!empty($is_aninomity)) ? $gender.substr($name,0,1) : $name;
        return $this->attributes['user_name'] = $userName;

    }

    public function getUserAvatarAttribute()
    {
        $notify_type_array = [
            Notice::AREA_CHANGE, Notice::OUT_OF_COINS, Notice::PROFILE_ACTIVATE, Notice::PROFILE_DEACTIVATE,
            Notice::PROFILE_PENDING, Notice::PROFILE_HIDE, Notice::PROFILE_UNHIDE, Notice::REWARD_INSTAGRAM,
            Notice::REWARD_RECOMMENDED, Notice::REWARD_RECOMMENDED_ONCE, Notice::INQUIRY_COIN_DEDUCT,
            Notice::MONTHLY_COIN_DEDUCT, Notice::RELOAD_COIN_REQUEST,
            Notice::RELOAD_COIN_REQUEST_REJECTED, Notice::ADMIN_SETTING_CHANGE_NOTIFICATION ,
            Notice::SNS_REWARD, Notice::SNS_PENALTY , Notice::SNS_REJECT,
            Notice::ADMIN_NOTICE , Notice::NOSHOW , Notice::REPORT , Notice::AREA_CHANGE, Notice::BECAME_BUSINESS_USER
        ];
        $value = $this->attributes['user_id'];
        $entity_id = $this->attributes['entity_id'];
        $notify_type = $this->attributes['notify_type'];
        $is_aninomity = $this->attributes['is_aninomity'];

        $user = Auth::user();
        $customer = RequestedCustomer::find($entity_id);
        $user_detail = UserDetail::where('user_id',$value)->first();
        $manager = Manager::where('user_id',$value)->first();

        if(($notify_type == Notice::BOOKING || $notify_type == Notice::BOOKING_CANCEL || $notify_type == Notice::HOUR_2_BEFORE_VISIT || $notify_type == Notice::HOUR_1_BEFORE_VISIT || $notify_type == Notice::VISIT) && $customer) {
            if($customer->user_id != $user->id) {
                $avatar = $customer->user_image;
            }else {
               if($customer->entity_type_id == EntityTypes::SHOP) {
                   $shop = Shop::find($customer->entity_id);
                   $avatar = ($shop && $shop->workplace_images && count($shop->workplace_images) > 0 ) ? $shop->workplace_images[0]->image : $user_detail->avatar;
               } else {
                    $hospital = Hospital::find($customer->hospital_id);
                    $avatar = $hospital && count($hospital->images) > 0 ? $hospital->images[0]['image'] : "";
               }
            }
        }elseif(in_array($notify_type,$notify_type_array)) {
            $avatar = asset('img/notice_logo.png');
        }else {
            if($is_aninomity == 1){
                $avatar = asset('img/avatar/avatar-1.png');
            }else if(!empty($manager)){
                 $avatar = $manager->avatar;
            }else{
                $avatar = $user_detail->avatar;
            }
        }

        return $this->attributes['user_avatar'] = $avatar;

    }
}
