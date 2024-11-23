<?php

namespace App\Models;

use Carbon\Carbon;
use \App\Models\Shop;
use \App\Models\ShopImages;
use App\Models\ShopFollowers;
use \App\Models\ShopImagesTypes;
use \App\Models\UserSavedHistory;
use \App\Models\SavedHistoryTypes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\SoftDeletes;

class ShopPost extends Model
{
    use SoftDeletes;
    protected $table = 'shop_posts';

    protected $dates = ['deleted_at'];

    const IMAGE = 1;
    const VIDEO = 2;

    protected $fillable = [
        'shop_id',
        'views_count',
        'post_item',
        'type',
        'video_thumbnail',
        'instagram_post_id',
        'post_order_date',
        'created_at',
        'updated_at',
        'description',
        'is_multiple',
        'is_admin_read',
        'insta_link',
        'is_like_order_admin_read',
        'display_video',
        'remain_download_insta',
    ];

    protected $casts = [
        'shop_id' => 'int',
        'views_count' => 'int',
        'post_item' => 'string',
        'type' => 'string',
        'video_thumbnail' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    protected $hidden = ['deleted_at'];

    protected $appends = ['shop_data', 'saved_count','is_saved_in_history','location','post_item_thumbnail','shop_thumbnail','workplace_images','is_follow', 'multiple_shop_posts','hash_tags','display_created_at','deeplink','display_updated_at'];

    // Removed -- user_id, shop_name, main_name, speciality_of, is_discount, business_link, another_mobile

    public function getShopDataAttribute(){
        $value = !empty($this->attributes['shop_id']) ? $this->attributes['shop_id'] : "";
        $shop = DB::table('shops')->whereId($value)->first();

        $subName = $shop->shop_name ? " / ".$shop->shop_name : '';
        $main_name = ($shop->main_name || $subName) ? $shop->main_name.$subName : '';

        return $this->attributes['shop_data'] = (object)[
            'user_id' => $shop->user_id ?? '',
            'main_name' => $main_name ?? '',
            'shop_name' => $shop->shop_name ?? '',
            'speciality_of' => $shop->speciality_of ?? '',
            'business_link' => $shop->business_link ?? '',
            'another_mobile' => $shop->another_mobile ?? '',
            'booking_link' => $shop->booking_link ?? '',
            'chat_option' => $shop->chat_option ?? 0,
            'show_price' => $shop->show_price ?? 0,
            'show_address' => $shop->show_address ?? 0,
            'is_discount' => ($shop) ? (boolean)$shop->is_discount : false,
        ];
    }

    public function getHashTagsAttribute()
    {
        $shop_post_id = !empty($this->attributes['id']) ? $this->attributes['id'] : 0;
        $hash_tags = [];
        if(!empty($shop_post_id)){
            $hash_tags = HashTag::join('hash_tag_mappings', function ($join) {
                $join->on('hash_tag_mappings.hash_tag_id', '=', 'hash_tags.id')
                ->where('hash_tag_mappings.entity_type_id', HashTag::SHOP_POST);
            })
            ->join('shop_posts', function ($join) {
                $join->on('shop_posts.id', '=', 'hash_tag_mappings.entity_id');
            })
            ->where('hash_tag_mappings.entity_id',$shop_post_id)
            ->select(
                'hash_tags.*',
                DB::raw('COUNT(hash_tag_mappings.id) as total_posts'),
                'shop_posts.id as post_id',
                DB::raw('group_concat(shop_posts.id) as shop_posts')
            )
            ->orderBy('total_posts','DESC')
            ->groupBy('hash_tags.id')
            ->get();
        }

        return $this->attributes['hash_tags'] = $hash_tags;
    }

    /* public function getUserIdAttribute()
    {
        $value = !empty($this->attributes['shop_id']) ? $this->attributes['shop_id'] : "";
        $shop = DB::table('shops')->whereId($value)->first();
        if (empty($shop)) {
            return $this->attributes['user_id'] = 0;
        } else {
            return $this->attributes['user_id'] = $shop->user_id;
        }
    } */
    public function getPostItemThumbnailAttribute()
    {
        $value = !empty($this->attributes['post_item']) ? $this->attributes['post_item'] : "";
        if (empty($value)) {
            return $this->attributes['post_item_thumbnail'] = '';
        } else {
            $fileName = basename($value);
            $newValue = str_replace($fileName,"thumb/$fileName",$value);
            if (!filter_var($newValue, FILTER_VALIDATE_URL)) {
                return $this->attributes['post_item_thumbnail'] = Storage::disk('s3')->url($newValue);
            } else {
                return $this->attributes['post_item_thumbnail'] = $newValue;
            }
        }
    }
    public function getPostItemAttribute()
    {
        $value = !empty($this->attributes['post_item']) ? $this->attributes['post_item'] : "";
        if (empty($value)) {
            return $this->attributes['post_item'] = '';
        } else {
            if (!filter_var($value, FILTER_VALIDATE_URL)) {
                return $this->attributes['post_item'] = Storage::disk('s3')->url($value);
            } else {
                return $this->attributes['post_item'] = $value;
            }
        }
    }
    public function getVideoThumbnailAttribute()
    {
        $value = !empty($this->attributes['video_thumbnail']) ? $this->attributes['video_thumbnail'] : "";
        if (empty($value)) {
            return $this->attributes['video_thumbnail'] = "";
        } else {
            if (!filter_var($value, FILTER_VALIDATE_URL)) {
                return $this->attributes['video_thumbnail'] = Storage::disk('s3')->url($value);
            } else {
                return $this->attributes['video_thumbnail'] = $value;
            }
        }
    }

    public function getDisplayUpdatedAtAttribute(){
        return $this->attributes['display_updated_at'] = $this->attributes['updated_at'];
    }
    public function getDisplayCreatedAtAttribute(){
        return $this->attributes['display_created_at'] = $this->attributes['created_at'];
    }
    public function getCreatedAtAttribute($date)
    {
        $date = new Carbon($date);
        $todayDate = Carbon::now();
        $yesterdayDate = Carbon::yesterday();
        $today = $todayDate->format('d-m-Y');
        $yesterday = $yesterdayDate->format('d-m-Y');
        $postDate = $date->format('d-m-Y');
        if($postDate == $today) {
            return "Today";
        }else if($postDate == $yesterday) {
            return "Yesterday";
        }else {
            return $date->format('Y m.d  A g:i');
        }
    }

    public function getUpdatedAtAttribute($date)
    {
        $date = new Carbon($date);
        return $date->format('d-m-Y');
    }

    public function getSavedCountAttribute()
    {
        $value = $this->attributes['id'];
        $count = UserSavedHistory::where('saved_history_type_id',SavedHistoryTypes::SHOP)->where('is_like',1)->where('entity_id',$value)->count();

        return $this->attributes['saved_count'] = $count;
    }
    public function getIsSavedInHistoryAttribute()
    {
        $value = $this->attributes['id'];
        $user = Auth::user();
        if($user) {
            $count = UserSavedHistory::where('saved_history_type_id',SavedHistoryTypes::SHOP)
                                    ->where('is_like',1)
                                    ->where('entity_id',$value)
                                    ->where('user_id',$user->id)
                                    ->count();
        }else {
            $count = 0;
        }

        return $this->attributes['is_saved_in_history'] = $count > 0 ? true : false;
    }

    /* public function getShopNameAttribute()
    {
        $value = $this->attributes['shop_id'];

        $shop = DB::table('shops')->whereId($value)->first();

        return $this->attributes['shop_name'] = $shop->shop_name ? $shop->shop_name : '';

    }
    public function getMainNameAttribute()
    {
        $value = $this->attributes['shop_id'];

        $shop = DB::table('shops')->whereId($value)->first();
        $subName = $shop->shop_name ? " / ".$shop->shop_name : '';

        return $this->attributes['main_name'] = ($shop->main_name || $subName) ? $shop->main_name.$subName : '';

    }
    public function getSpecialityOfAttribute()
    {
        $value = $this->attributes['shop_id'];

        $shop = DB::table('shops')->whereId($value)->first();

        return $this->attributes['speciality_of'] = $shop->speciality_of ? $shop->speciality_of : '';

    }
    public function getIsDiscountAttribute()
    {
        $value = $this->attributes['shop_id'];

        $shop = DB::table('shops')->whereId($value)->first();

        return $this->attributes['is_discount'] = ($shop) ? (boolean)$shop->is_discount : false;

    } */
    public function getShopThumbnailAttribute()
    {
        $value = $this->attributes['shop_id'];

        $shop = ShopImages::where('shop_id',$value)->where('shop_image_type',ShopImagesTypes::THUMB)->first();

        return $this->attributes['shop_thumbnail'] = $shop ? $shop->image :  "";

    }
    public function getLocationAttribute()
    {
        $value = $this->attributes['shop_id'];

        //$shop = Shop::where('id',$value)->first();

        return $this->attributes['location'] = Address::where('entity_type_id',EntityTypes::SHOP)->where('entity_id',$value)->first(); //$shop->address;

    }

    public function getWorkplaceImagesAttribute()
    {
        $value = $this->attributes['shop_id'];
        $worplace_images = ShopImages::where('shop_id', $value)->where('shop_image_type',ShopImagesTypes::WORKPLACE)->get(['id','image']);
        $images = [];
        if (empty($worplace_images)) {
            return $this->attributes['workplace_images'] = $images;
        } else {

            return $this->attributes['workplace_images'] = $worplace_images;
        }
    }

    public function getIsFollowAttribute()
    {
        $value = $this->attributes['shop_id'];
        $user = Auth::user();
        if($user) {
            $followers = ShopFollowers::where('shop_id', $value)->where('user_id',$user->id)->count();
            return $this->attributes['is_follow'] = $followers > 0 ? 1 : 0;
        }else {
            return $this->attributes['is_follow'] = 0;
        }
    }

    /* public function getBusinessLinkAttribute(){
        $value = $this->attributes['shop_id'];
        $shopData = DB::table('shops')->whereId($value)->first();
        return $this->attributes['business_link'] = $shopData->business_link ? $shopData->business_link : '';
    }
    public function getAnotherMobileAttribute(){
        $value = $this->attributes['shop_id'];
        $shopData = DB::table('shops')->whereId($value)->first();
        return $this->attributes['another_mobile'] = $shopData->another_mobile ? $shopData->business_link : '';
    } */
    public function getMultipleShopPostsAttribute()
    {
        $id = $this->attributes['id'];
        $type = $this->attributes['type'] ?? 'image';
        $display_video = $this->attributes['display_video'] ?? 0;
        $post_item = $this->attributes['post_item'] ?? '';
        $video_thumbnail = $this->attributes['video_thumbnail'] ?? '';

        $defaultItem = [];
        if(isset($video_thumbnail) && !empty($video_thumbnail)){
            $defaultItem[0]['video_thumbnail'] = $video_thumbnail;
        }else{
            $defaultItem[0]['video_thumbnail'] = '';
        }

        $defaultItem[0]['id'] = $id;
        $defaultItem[0]['type'] = $type;
        $defaultItem[0]['post_type'] = 'main';
        $defaultItem[0]['display_video'] = $display_video;

        $defaultItem[0]['post_item'] = !empty($post_item) ? $post_item : NULL;
        $defaultItem[0]['post_item_thumbnail'] = filterDataThumbnailUrl($post_item);

        $posts = MultipleShopPost::where('shop_posts_id',$id)->get();
        return $this->attributes['multiple_shop_posts'] =  collect($defaultItem)->merge($posts)->values();
    }

    public function getDeeplinkAttribute(){
        $value = !empty($this->attributes['shop_id']) ? $this->attributes['shop_id'] : "";
        return $this->attributes['deeplink'] = getDeepLink(config('constant.portfolio_detail'),$this->attributes['id'],config('constant.shop_detail'));
    }

    public function multiple_posts(){
        return $this->hasMany(MultipleShopPost::class, 'shop_posts_id', 'id');
    }

}
