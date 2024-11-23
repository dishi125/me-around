<?php

namespace App\Http\Controllers\Api;

use App\Models\Shop;
use App\Models\Status;
use App\Models\EntityTypes;
use App\Models\Reviews;
use App\Models\ReviewImages;
use App\Models\ReviewCategory;
use App\Models\Category;
use App\Models\CategoryTypes;
use App\Models\ReviewLikes;
use App\Models\ReviewComments;
use App\Models\ReviewCommentLikes;
use App\Models\ReviewCommentReply;
use App\Models\Doctor;
use App\Models\Hospital;
use App\Models\PackagePlan;
use App\Models\CreditPlans;
use App\Models\Post;
use App\Models\Address;
use App\Models\ActivityLog;
use App\Models\RequestedCustomer;
use App\Models\UserDetail;
use App\Models\Notice;
use App\Models\ReviewCommentReplyLikes;
use App\Models\UserDevices;
use App\Models\UserPoints;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use App\Validators\ReviewsValidator;
use Validator;
use Carbon\Carbon;
use App\Util\Firebase;


class ReviewsController extends Controller
{
    private $reviewsValidator;
    protected $firebase;

    function __construct()
    {
        $this->reviewsValidator = new ReviewsValidator();
        $this->firebase = new Firebase();
    }

    public function addShopReview(Request $request)
    {
        try {
            Log::info('Start code add shop review');
            $inputs = $request->all();
            $user = Auth::user();
            if($user){
                DB::beginTransaction();
                $validation = $this->reviewsValidator->validateShopReview($inputs);
                if ($validation->fails()) {
                    Log::info('End code for add shop review');
                    return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
                }
                $data = [
                    'user_id' => $user->id,
                    'entity_type_id' => EntityTypes::SHOP,
                    'requested_customer_id' => $inputs['booking_id'],
                    'entity_id' => $inputs['shop_id'],
                    'review_comment' => $inputs['review_comment'],
                    'rating' => $inputs['rating'],
                ];

                $review = Reviews::create($data);

                $isAvailable = UserPoints::where(['user_id' => $user->id,'entity_type' => UserPoints::REVIEW_SHOP_POST,'entity_created_by_id' => $user->id])->whereDate('created_at',Carbon::now()->format('Y-m-d'))->first();

                if(empty($isAvailable)){

                    UserPoints::create([
                        'user_id' => $user->id,
                        'entity_type' => UserPoints::REVIEW_SHOP_POST,
                        'entity_id' => $review->id,
                        'entity_created_by_id' => $user->id,
                        'points' => UserPoints::REVIEW_SHOP_POST_POINT]);

                    // Send Push notification start
                    $notice = Notice::create([
                        'notify_type' => Notice::REVIEW_SHOP_POST,
                        'user_id' => $user->id,
                        'to_user_id' => $user->id,
                        'entity_type_id' => EntityTypes::REVIEWS,
                        'entity_id' => $review->id,
                        'title' => '+'.UserPoints::REVIEW_SHOP_POST_POINT.'exp',
                        'sub_title' => '',
                        'is_aninomity' => 0
                    ]);

                    $user_detail = UserDetail::where('user_id', $user->id)->first();
                    $language_id = $user_detail->language_id;
                    $key = Notice::REVIEW_SHOP_POST.'_'.$language_id;
                    $userIds = [$user->id];

                    $format = '+'.UserPoints::REVIEW_SHOP_POST_POINT.'exp';
                    $devices = UserDevices::whereIn('user_id', $userIds)->pluck('device_token')->toArray();

                    $title_msg = __("notice.$key");
                    $notify_type = Notice::REVIEW_SHOP_POST;

                    $notificationData = [
                        'id' => $review->id,
                        'user_id' => $user->id,
                        'title' => $title_msg,
                    ];

                    if (count($devices) > 0) {
                        $result = $this->sentPushNotification($devices,$title_msg, $format, $notificationData ,$notify_type);
                    }
                            // Send Push notification end
                }

                $country = '';
                $address = Address::where('entity_type_id',EntityTypes::SHOP)
                                    ->where('entity_id',$inputs['shop_id'])->first();
                $country = $address ? $address->main_country : '';


                ActivityLog::create([
                    'entity_type_id' => EntityTypes::SHOP,
                    'entity_id' => $review->id,
                    'country' => $country,
                    'user_id' => $user->id,
                ]);
                $requestCustomer = RequestedCustomer::where('id',$inputs['booking_id'])->update(['show_in_home' => 0]);
                $shop  = Shop::find($inputs['shop_id']);
                $reviewCategory = ReviewCategory::create([
                    'review_id' => $review->id,
                    'category_id' => $shop->category_id,
                ]);

                $reviewsFolder = config('constant.reviews').'/'.$review->id;

                if (!Storage::exists($reviewsFolder)) {
                    Storage::makeDirectory($reviewsFolder);
                }

                if(!empty($inputs['before_images'])){
                    foreach($inputs['before_images'] as $beforeImage) {
                        $mainProfile = Storage::disk('s3')->putFile($reviewsFolder, $beforeImage,'public');
                        $fileName = basename($mainProfile);
                        $image_url = $reviewsFolder . '/' . $fileName;
                        $temp = [
                            'review_id' => $review->id,
                            'type' => ReviewImages::BEFORE,
                            'image' => $image_url
                        ];
                        print_r($temp);die;
                        ReviewImages::create($temp);
                    }
                }
                if(!empty($inputs['after_images'])){
                    foreach($inputs['after_images'] as $afterImage) {
                        $mainProfile = Storage::disk('s3')->putFile($reviewsFolder, $afterImage,'public');
                        $fileName = basename($mainProfile);
                        $image_url = $reviewsFolder . '/' . $fileName;
                        $temp = [
                            'review_id' => $review->id,
                            'type' => ReviewImages::AFTER,
                            'image' => $image_url
                        ];
                        ReviewImages::create($temp);
                    }
                }

                $devices = UserDevices::whereIn('user_id', [$shop->user_id])->pluck('device_token')->toArray();
                $user_detail = UserDetail::where('user_id', $shop->user_id)->first();
                $language_id = $user_detail->language_id;

                $key = Notice::WRITE_REVIEW.'_'.$language_id;
                $title_msg = __("notice.$key", ['name' => $user->name]);
                //$format = __("notice.$key", ['name' => $user->name]);
                $format = '';
                $notify_type = Notice::WRITE_REVIEW;
                $notice = Notice::create([
                    'notify_type' => Notice::WRITE_REVIEW,
                    'user_id' => $user->id,
                    'to_user_id' => $shop->user_id,
                    'entity_type_id' => EntityTypes::SHOP,
                    'entity_id' => $shop->id,
                    'title' => $shop->shop_name.'('.$shop->main_name.')',
                ]);

                $notificationData = [
                    'id' => $shop->id,
                    'main_name' => $shop->main_name,
                    'shop_name' => $shop->shop_name,
                    'category_id' => $shop->category_id,
                    'category_name' => $shop->category_name,
                    'category_icon' => $shop->category_icon,
                ];

                if (count($devices) > 0) {
                    $result = $this->sentPushNotification($devices,$title_msg, $format, $notificationData ,$notify_type, $shop->id);
                }
                DB::commit();
                Log::info('End code for the add shop review');
                return $this->sendSuccessResponse(Lang::get('messages.review.add-success'), 200,$review);

            }else{
                Log::info('End code for add shop review');
                return $this->sendSuccessResponse(Lang::get('messages.user.token_expired'), 401);
            }
        } catch (\Exception $e) {
            print_r($e->getMessage());die;
            Log::info('Exception in add shop review');
            Log::info($e);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }
    public function addHospitalReview(Request $request)
    {
        try {
            Log::info('Start code add hospital review');
            $inputs = $request->all();
            $user = Auth::user();
            if($user){
                DB::beginTransaction();
                $validation = $this->reviewsValidator->validateHospitalReview($inputs);
                if ($validation->fails()) {
                    Log::info('End code for add hospital review');
                    return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
                }
                $data = [
                    'user_id' => $user->id,
                    'entity_type_id' => EntityTypes::HOSPITAL,
                    'requested_customer_id' => $inputs['booking_id'],
                    'entity_id' => $inputs['hospital_post_id'],
                    'review_comment' => $inputs['review_comment'],
                    'rating' => $inputs['rating'],
                ];

                if(!empty($inputs['doctor_id'])) {
                    $data['doctor_id'] = $inputs['doctor_id'];
                }

                $review = Reviews::create($data);

                $isAvailable = UserPoints::where(['user_id' => $user->id,'entity_type' => UserPoints::REVIEW_HOSPITAL_POST,'entity_created_by_id' => $user->id])->whereDate('created_at',Carbon::now()->format('Y-m-d'))->first();

                if(empty($isAvailable)){

                    UserPoints::create([
                        'user_id' => $user->id,
                        'entity_type' => UserPoints::REVIEW_HOSPITAL_POST,
                        'entity_id' => $review->id,
                        'entity_created_by_id' => $user->id,
                        'points' => UserPoints::REVIEW_HOSPITAL_POST_POINT]);

                    // Send Push notification start
                    $notice = Notice::create([
                        'notify_type' => Notice::REVIEW_HOSPITAL_POST,
                        'user_id' => $user->id,
                        'to_user_id' => $user->id,
                        'entity_type_id' => EntityTypes::REVIEWS,
                        'entity_id' => $review->id,
                        'title' => '+'.UserPoints::REVIEW_HOSPITAL_POST_POINT.'exp',
                        'sub_title' => '',
                        'is_aninomity' => 0
                    ]);

                    $user_detail = UserDetail::where('user_id', $user->id)->first();
                    $language_id = $user_detail->language_id;
                    $key = Notice::REVIEW_HOSPITAL_POST.'_'.$language_id;
                    $userIds = [$user->id];

                    $format = '+'.UserPoints::REVIEW_HOSPITAL_POST_POINT.'exp';
                    $devices = UserDevices::whereIn('user_id', $userIds)->pluck('device_token')->toArray();

                    $title_msg = __("notice.$key");
                    $notify_type = Notice::REVIEW_HOSPITAL_POST;

                    $notificationData = [
                        'id' => $review->id,
                        'user_id' => $user->id,
                        'title' => $title_msg,
                    ];

                    if (count($devices) > 0) {
                        $result = $this->sentPushNotification($devices,$title_msg, $format, $notificationData ,$notify_type);
                    }
                            // Send Push notification end
                }


                $country = '';
                $post = Post::find($inputs['hospital_post_id']);
                $hospital_id = $post ? $post->hospital_id : null;

                $address = Address::where('entity_type_id',EntityTypes::HOSPITAL)
                                    ->where('entity_id',$hospital_id)->first();
                $country = $address ? $address->main_country : '';

                ActivityLog::create([
                    'entity_type_id' => EntityTypes::HOSPITAL,
                    'entity_id' => $review->id,
                    'country' => $country,
                    'user_id' => $user->id,
                ]);
                $requestCustomer = RequestedCustomer::where('id',$inputs['booking_id'])->update(['show_in_home' => 0]);
                if(!empty($inputs['category_id'])){
                    foreach($inputs['category_id'] as $category)
                    $reviewCategory = ReviewCategory::create([
                        'review_id' => $review->id,
                        'category_id' => $category,
                    ]);
                }


                $reviewsFolder = config('constant.reviews').'/'.$review->id;

                if (!Storage::exists($reviewsFolder)) {
                    Storage::makeDirectory($reviewsFolder);
                }

                if(!empty($inputs['before_images'])){
                    foreach($inputs['before_images'] as $beforeImage) {
                        $mainProfile = Storage::disk('s3')->putFile($reviewsFolder, $beforeImage,'public');
                        $fileName = basename($mainProfile);
                        $image_url = $reviewsFolder . '/' . $fileName;
                        $temp = [
                            'review_id' => $review->id,
                            'type' => ReviewImages::BEFORE,
                            'image' => $image_url
                        ];
                        ReviewImages::create($temp);
                    }
                }
                if(!empty($inputs['after_images'])){
                    foreach($inputs['after_images'] as $afterImage) {
                        $mainProfile = Storage::disk('s3')->putFile($reviewsFolder, $afterImage,'public');
                        $fileName = basename($mainProfile);
                        $image_url = $reviewsFolder . '/' . $fileName;
                        $temp = [
                            'review_id' => $review->id,
                            'type' => ReviewImages::AFTER,
                            'image' => $image_url
                        ];
                        ReviewImages::create($temp);
                    }
                }

                $devices = UserDevices::whereIn('user_id', [$post->user_id])->pluck('device_token')->toArray();
                $user_detail = UserDetail::where('user_id', $post->user_id)->first();
                $language_id = $user_detail->language_id;
                $key = Notice::WRITE_REVIEW.'_'.$language_id;
                //$format = __("notice.$key", ['name' => $user->name]);
                $format = '';
                $title_msg = __("notice.$key", ['name' => $user->name]);
                $notify_type = Notice::WRITE_REVIEW;
                $notice = Notice::create([
                    'notify_type' => Notice::WRITE_REVIEW,
                    'user_id' => $user->id,
                    'to_user_id' => $post->user_id,
                    'entity_type_id' => EntityTypes::HOSPITAL,
                    'entity_id' => $post->id,
                    'title' => $post->title,
                ]);

                $notificationData = [
                    'id' => $post->id,
                    'title' => $post->title,
                    'sub_title' => $post->sub_title,
                    'hospital_id' => $post->hospital_id,
                ];

                if (count($devices) > 0) {
                    $result = $this->sentPushNotification($devices,$title_msg, $format, $notificationData ,$notify_type, $post->id);
                }
                DB::commit();
                Log::info('End code for the add hospital review');
                return $this->sendSuccessResponse(Lang::get('messages.review.add-success'), 200,$review);

            }else{
                Log::info('End code for add hospital review');
                return $this->sendSuccessResponse(Lang::get('messages.user.token_expired'), 401);
            }
        } catch (\Exception $e) {
            Log::info('Exception in add hospital review');
            Log::info($e);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function getShopReview(Request $request)
    {
        try {
            Log::info('Start code get shop review');
            $inputs = $request->all();
                $user = Auth::user();
                DB::beginTransaction();
                $validation = $this->reviewsValidator->validateGetReview($inputs);
                if ($validation->fails()) {
                    Log::info('End code for get shop review');
                    return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
                }
                $language_id = isset($inputs['language_id']) ? $inputs['language_id'] : 4;
                if(isset($inputs['latitude']) && isset($inputs['longitude'])) {
                    $main_country = getCountryFromLatLong($inputs['latitude'],$inputs['longitude']);
                    $timezone = get_nearest_timezone($inputs['latitude'],$inputs['longitude'],$main_country);
                }else {
                    $timezone = '';
                }

                $category_id = isset($inputs['category_id']) ? $inputs['category_id'] : 0;
                $category = Category::find($category_id);

                $is_suggest_category = $category && $category->category_type_id == CategoryTypes::CUSTOM ? 1 : 0;

                $reviewsBestPickQuery = DB::table('reviews')->leftJoin('review_category','review_category.review_id','reviews.id')
                                        ->join('shops', function ($join) {
                                            $join->on('shops.id', '=', 'reviews.entity_id');
                                        })
                                        ->leftjoin('addresses', function ($join) {
                                            $join->on('shops.id', '=', 'addresses.entity_id')
                                                ->where('addresses.entity_type_id', EntityTypes::SHOP);
                                        })
                                        ->leftjoin('users_detail', function ($join) {
                                            $join->on('reviews.user_id', '=', 'users_detail.user_id');
                                        })
                                        ->join('category','category.id','shops.category_id')
                                        ->select(
                                            'reviews.id',
                                            'shops.user_id',
                                            'reviews.rating',
                                            'reviews.created_at',
                                            'reviews.review_comment',
                                            'shops.id as shop_id',
                                            'shops.main_name as main_name',
                                            'category.name as category_name',
                                            'users_detail.name as user_name'
                                        )
                                        ->selectSub(function($q) {
                                            $q->select( DB::raw('count(review_likes.id) as count'))->from('review_likes')->whereNull('review_likes.deleted_at')->whereRaw("`review_likes`.`review_id` = `reviews`.`id`");
                                        }, 'likes_count')
                                        ->selectSub(function($q) {
                                            $q->select( DB::raw('count(review_comments.id) as count'))->from('review_comments')->whereNull('review_comments.deleted_at')->whereRaw("`review_comments`.`review_id` = `reviews`.`id`");
                                        }, 'comments_count')
                                        ->whereNull('reviews.deleted_at')
                                        ->whereNull('shops.deleted_at')
                                        ->where('reviews.entity_type_id', EntityTypes::SHOP);

                $reviewsPopularQuery = DB::table('reviews')->leftJoin('review_category','review_category.review_id','reviews.id')
                                        ->join('shops', function ($join) {
                                            $join->on('shops.id', '=', 'reviews.entity_id');
                                        })
                                        ->leftjoin('addresses', function ($join) {
                                            $join->on('shops.id', '=', 'addresses.entity_id')
                                                ->where('addresses.entity_type_id', EntityTypes::SHOP);
                                        })
                                        ->leftjoin('users_detail', function ($join) {
                                            $join->on('reviews.user_id', '=', 'users_detail.user_id');
                                        })
                                        ->join('category','category.id','shops.category_id')
                                        ->select(
                                            'reviews.id',
                                            'shops.user_id',
                                            'reviews.rating',
                                            'reviews.created_at',
                                            'reviews.review_comment',
                                            'shops.id as shop_id',
                                            'shops.main_name as main_name',
                                            'category.name as category_name',
                                            'users_detail.name as user_name'
                                        )
                                        ->selectSub(function($q) {
                                            $q->select( DB::raw('count(review_comments.id) as count'))->from('review_comments')->whereNull('review_comments.deleted_at')->whereRaw("`review_comments`.`review_id` = `reviews`.`id`");
                                        }, 'comments_count')
                                        ->selectSub(function($q) use($user) {
                                            if($user){
                                                $q->select( DB::raw('count(review_likes.id) as count'))->from('review_likes')->whereNull('review_likes.deleted_at')->where('review_likes.user_id', $user->id)->whereRaw("`review_likes`.`review_id` = `reviews`.`id`");
                                            }else{
                                                $q->select( DB::raw('count(review_likes.id) as count'))->from('review_likes')->whereNull('review_likes.deleted_at')->whereRaw("`review_likes`.`review_id` = `reviews`.`id`");
                                            }
                                        }, 'likes_count')
                                        ->whereNull('reviews.deleted_at')
                                        ->whereNull('shops.deleted_at')
                                        ->where('reviews.entity_type_id', EntityTypes::SHOP);

                $reviewsRecentQuery = DB::table('reviews')->leftJoin('review_category','review_category.review_id','reviews.id')
                                        ->join('shops', function ($join) {
                                            $join->on('shops.id', '=', 'reviews.entity_id');
                                        })
                                        ->leftjoin('addresses', function ($join) {
                                            $join->on('shops.id', '=', 'addresses.entity_id')
                                                ->where('addresses.entity_type_id', EntityTypes::SHOP);
                                        })
                                        ->leftjoin('users_detail', function ($join) {
                                            $join->on('shops.user_id', '=', 'users_detail.user_id');
                                        })
                                        ->join('category','category.id','shops.category_id')
                                        ->select(
                                            'reviews.id',
                                            'shops.user_id',
                                            'reviews.rating',
                                            'reviews.created_at',
                                            'reviews.review_comment',
                                            'shops.id as shop_id',
                                            'shops.main_name as main_name',
                                            'category.name as category_name',
                                            'users_detail.name as user_name'
                                        )
                                        ->selectSub(function($q) {
                                            $q->select( DB::raw('count(review_comments.id) as count'))->from('review_comments')->whereNull('review_comments.deleted_at')->whereRaw("`review_comments`.`review_id` = `reviews`.`id`");
                                        }, 'comments_count')
                                        ->selectSub(function($q) use($user) {
                                            if($user){
                                                $q->select( DB::raw('count(review_likes.id) as count'))->from('review_likes')->whereNull('review_likes.deleted_at')->where('review_likes.user_id', $user->id)->whereRaw("`review_likes`.`review_id` = `reviews`.`id`");
                                            }else{
                                                $q->select( DB::raw('count(review_likes.id) as count'))->from('review_likes')->whereNull('review_likes.deleted_at')->whereRaw("`review_likes`.`review_id` = `reviews`.`id`");
                                            }
                                        }, 'likes_count')
                                        ->whereNull('reviews.deleted_at')
                                        ->whereNull('shops.deleted_at')
                                        ->where('reviews.entity_type_id', EntityTypes::SHOP);



                if(!empty($inputs['category_id'])){
                    $reviewsPopularQuery = $reviewsPopularQuery->where('review_category.category_id',$inputs['category_id']);
                    $reviewsRecentQuery = $reviewsRecentQuery->where('review_category.category_id',$inputs['category_id']);
                    $reviewsBestPickQuery = $reviewsBestPickQuery->where('review_category.category_id',$inputs['category_id']);
                } else {
                    $reviewsPopularQuery = $reviewsPopularQuery->where('category.category_type_id',CategoryTypes::SHOP);
                    $reviewsRecentQuery = $reviewsRecentQuery->where('category.category_type_id',CategoryTypes::SHOP);
                    $reviewsBestPickQuery = $reviewsBestPickQuery->where('category.category_type_id',CategoryTypes::SHOP);
                }
                $distance = "(6371 * acos(cos(radians(".$inputs['latitude']."))
                     * cos(radians(addresses.latitude))
                     * cos(radians(addresses.longitude)
                     - radians(".$inputs['longitude']."))
                     + sin(radians(".$inputs['latitude']."))
                     * sin(radians(addresses.latitude))))";

                $reviewsBestPick = $reviewsBestPickQuery->whereDate('reviews.created_at', '>=' , Carbon::now()->subDays(30))
                     ->distinct('reviews.id')
                     ->orderby('likes_count','desc')
                     ->orderby('distance')
                     ->selectRaw("{$distance} AS distance")
                     ->paginate(config('constant.review_pagination_count'),"*","best_pick_page");

                $reviewsPopular = $reviewsPopularQuery->distinct('reviews.id')
                                    ->orderby('likes_count','desc')
                                    ->orderby('distance')
                                    ->selectRaw("{$distance} AS distance")
                                    ->paginate(config('constant.review_pagination_count'),"*","popular_list_page");

                $reviewsRecent = $reviewsRecentQuery->distinct('reviews.id')
                                    ->orderby('reviews.id','desc')
                                    ->orderby('likes_count','desc')
                                    ->orderby('distance')
                                    ->selectRaw("{$distance} AS distance")
                                    ->paginate(config('constant.review_pagination_count'),"*","recent_list_page");

                $bestPickData = $this->timeLanguageFilterNew($this->shopDistanceFilterNew($reviewsBestPick),$language_id,$timezone);
                $data = [
                    'popular' => [
                        'best_pick' => $bestPickData,
                        'popular_list' => $this->timeLanguageFilterNew($this->shopDistanceFilterNew($reviewsPopular),$language_id,$timezone),
                    ],
                    'recent' => [
                        'best_pick' => $bestPickData,
                        'recent_list' => $this->timeLanguageFilterNew($this->shopDistanceFilterNew($reviewsRecent),$language_id,$timezone),
                    ],
                ];

                DB::commit();
                Log::info('End code for the get shop review');
            return $this->sendSuccessResponse(Lang::get('messages.review.get-success'), 200,$data);

        } catch (\Exception $e) {
            Log::info('Exception in get shop review');
            Log::info($e);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function shopDistanceFilterNew($data) {
        $user = Auth::user();
        $paginateData = collect($data)->toArray();

        foreach($paginateData['data'] as $key => $d) {
            $d = collect($d)->toArray();
            $user_id = $d['user_id'];

            $planDetail = DB::table('users_detail')
                    ->join('credit_plans','credit_plans.package_plan_id','users_detail.package_plan_id')
                    ->where('credit_plans.entity_type_id', EntityTypes::SHOP)->where('users_detail.user_id',$user_id)
                    ->select('credit_plans.package_plan_id','credit_plans.km')
                    ->first();

            $shop_details = DB::table('shops')->join('category','shops.category_id','category.id')
                    ->where('shops.id',$d['shop_id'])
                    ->select('shops.main_name', 'shops.shop_name','category.name','category.name as category_name')
                    ->first();

            $km = $planDetail ? $planDetail->km : 0;
            if($km >= $d['distance']) {
                if($user) {
                    $reviewLikes = ReviewLikes::where('review_id',$d['id'])->where('user_id',$user->id)->count();
                }else {
                    $reviewLikes = 0;
                }
                $paginateData['data'][$key]->shop_detail = $shop_details;
                $paginateData['data'][$key]->is_liked = $reviewLikes > 0 ? true : false;
                $paginateData['data'][$key]->before_images = ReviewImages::where('review_id',$d['id'])->where('type',ReviewImages::BEFORE)->pluck('image');
                $paginateData['data'][$key]->after_images = ReviewImages::where('review_id',$d['id'])->where('type',ReviewImages::AFTER)->pluck('image');
                $paginateData['data'][$key]->distance = number_format((float)$d['distance'], 1, '.', '');
            }else {
                unset($paginateData['data'][$key]);
            }
        }
        $paginateData['data'] = array_values($paginateData['data']);
        return $paginateData;
    }

    public function shopDistanceFilter($data,$is_post = 0) {
       $paginateData = $data->toArray();
        foreach($paginateData['data'] as $key => $d) {
            $shopData = Shop::find($d['shop_id']);
            $user_id = $shopData->user_id;

            $userdetail = UserDetail::where('user_id',$user_id)->first();
            $planDetail = CreditPlans::where('entity_type_id',EntityTypes::SHOP)->where('package_plan_id',$userdetail->package_plan_id)->first();
            $km = $planDetail ? $planDetail->km : 0;
            if($km >= $d['distance']) {
                $paginateData['data'][$key]['distance'] = number_format((float)$d['distance'], 1, '.', '');
                $paginateData['data'][$key]['shop_detail'] = $shopData;
            }else {
                unset($paginateData['data'][$key]);
            }
        }
        $paginateData['data'] = array_values($paginateData['data']);
        return $paginateData;
    }

    public function getShopReviewDetail(Request $request,$id)
    {
        try {
            Log::info('Start code get shop review');
            $inputs = $request->all();
            $user = Auth::user();
            $reviewExists = Reviews::find($id);
            if($reviewExists){
                DB::beginTransaction();
                $validation = Validator::make($request->all(), [
                    'latitude' => 'required',
                    'longitude' => 'required',
                ], [], [
                    'latitude' => 'Latitude',
                    'longitude' => 'Longitude',
                ]);

                if ($validation->fails()) {
                    Log::info('End code for get shop review');
                    return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
                }


                $language_id = isset($inputs['language_id']) ? $inputs['language_id'] : 4;
                if(isset($inputs['latitude']) && isset($inputs['longitude'])) {
                    $main_country = getCountryFromLatLong($inputs['latitude'],$inputs['longitude']);
                    $timezone = get_nearest_timezone($inputs['latitude'],$inputs['longitude'],$main_country);
                }else {
                    $timezone = '';
                }

                Reviews::where('id',$id)->update(['views_count' => DB::raw('views_count + 1')]);
                $returnData = [];
                $review = Reviews::where('entity_type_id', EntityTypes::SHOP)->where('id', $id)->first();
                if($review){
                    $returnData = $review->toArray();
                    $returnData['comments'] = $this->commentTimeFilter($returnData['comments'],$language_id,$timezone);
                    $reviewShop = Shop::find($review->entity_id);
                    $returnData['shop_detail'] = $reviewShop;
                    $returnData['is_owner'] = ($user && $user->id == $review->user_id) ? true : false;
                    $returnData['detail_before_images'] = ReviewImages::where('review_id',$id)
                            ->where('type',ReviewImages::BEFORE)
                            ->select('id','image')
                            ->get();
                    $returnData['detail_after_images'] = ReviewImages::where('review_id',$id)
                            ->where('type',ReviewImages::AFTER)
                            ->select('id','image')
                            ->get();
                    // $reviewComments = ReviewComments::where('review_id',$id)->get();
                    // $review['review_comments'] = $reviewComments;
                    // $returnData = $review;
                }
                DB::commit();
                Log::info('End code for the get shop review');
                return $this->sendSuccessResponse(Lang::get('messages.review.get-success'), 200,$returnData);
            }else{
                return $this->sendSuccessResponse(Lang::get('messages.review.empty'), 402);
            }

        } catch (\Exception $e) {
            Log::info('Exception in get shop review');
            Log::info($e);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }


    public function updateShopReviewDetail(Request $request, $id){

        $reviewsFolder = config('constant.reviews').'/'.$id;
        $inputs = $request->all();

        try{
            $data = ['review_comment' => $inputs['review_comment'], 'rating' => $inputs['rating']];

            if(isset($inputs['doctor_id']) && !empty($inputs['doctor_id'])) {
                $data['doctor_id'] = $inputs['doctor_id'];
            }

            Reviews::where('id',$id)->update($data);

            if(!empty($inputs['before_images'])){
                foreach($inputs['before_images'] as $beforeImage) {
                    $mainProfile = Storage::disk('s3')->putFile($reviewsFolder, $beforeImage,'public');
                    $fileName = basename($mainProfile);
                    $image_url = $reviewsFolder . '/' . $fileName;
                    $temp = [
                        'review_id' => $id,
                        'type' => ReviewImages::BEFORE,
                        'image' => $image_url
                    ];
                    ReviewImages::create($temp);
                }
            }
            if(!empty($inputs['after_images'])){
                foreach($inputs['after_images'] as $afterImage) {
                    $mainProfile = Storage::disk('s3')->putFile($reviewsFolder, $afterImage,'public');
                    $fileName = basename($mainProfile);
                    $image_url = $reviewsFolder . '/' . $fileName;
                    $temp = [
                        'review_id' => $id,
                        'type' => ReviewImages::AFTER,
                        'image' => $image_url
                    ];
                    ReviewImages::create($temp);
                }
            }

            if(isset($inputs['delete_images']) && !empty($inputs['delete_images'])){
                $deleteImages = DB::table('review_images')->whereIn('id',$inputs['delete_images'])->get();
                foreach($deleteImages as $data){
                    if($data->image){
                        Storage::disk('s3')->delete($data->image);
                    }
                }
                ReviewImages::whereIn('id',$inputs['delete_images'])->delete();
            }

            $review = Reviews::where('id',$id)->first();
            return $this->sendSuccessResponse(Lang::get('messages.review.add-success'), 200,$review);
        } catch (\Exception $e) {
            Log::info($e);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }
    public function getHospitalReview(Request $request)
    {
        try {
            Log::info('Start code get hospital review');
            $inputs = $request->all();
            $user =  Auth::user();
                DB::beginTransaction();
                $validation = $this->reviewsValidator->validateGetReview($inputs);
                if ($validation->fails()) {
                    Log::info('End code for get hospital review');
                    return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
                }

                $language_id = isset($inputs['language_id']) ? $inputs['language_id'] : 4;
                if(isset($inputs['latitude']) && isset($inputs['longitude'])) {
                    $main_country = getCountryFromLatLong($inputs['latitude'],$inputs['longitude']);
                    $timezone = get_nearest_timezone($inputs['latitude'],$inputs['longitude'],$main_country);
                }else {
                    $timezone = '';
                }

                $creditPlans = CreditPlans::where('entity_type_id', EntityTypes::HOSPITAL)->get();
                $bronzePlanKm = $silverPlanKm = $goldPlanKm = $platiniumPlanKm = 0;
                foreach($creditPlans as $plan) {
                    if($plan->package_plan_id == PackagePlan::BRONZE) {
                        $bronzePlanKm = $plan->km;
                    }else if($plan->package_plan_id == PackagePlan::SILVER) {
                        $silverPlanKm = $plan->km;
                    }else if($plan->package_plan_id == PackagePlan::GOLD) {
                        $goldPlanKm = $plan->km;
                    }else if($plan->package_plan_id == PackagePlan::PLATINIUM) {
                        $platiniumPlanKm = $plan->km;
                    }
                }

                $reviewsRecentQuery = DB::table('reviews')->leftJoin('review_category','review_category.review_id','reviews.id')
                                        ->leftJoin('posts','posts.id','reviews.entity_id')
                                        ->join('hospitals', function ($join) {
                                            $join->on('posts.hospital_id', '=', 'hospitals.id');
                                        })
                                        ->leftjoin('addresses', function ($join) {
                                            $join->on('hospitals.id', '=', 'addresses.entity_id')
                                                ->where('addresses.entity_type_id', EntityTypes::HOSPITAL);
                                        })
                                        ->leftjoin('user_entity_relation', function ($join) {
                                            $join->on('hospitals.id', '=', 'user_entity_relation.entity_id')
                                                ->where('user_entity_relation.entity_type_id', EntityTypes::HOSPITAL);
                                        })
                                        ->leftjoin('users_detail', function ($join) {
                                            $join->on('user_entity_relation.user_id', '=', 'users_detail.user_id');
                                        })
                                        ->join('category','category.id','posts.category_id')
                                        ->select(
                                            'reviews.id',
                                            'user_entity_relation.user_id',
                                            'reviews.rating',
                                            'reviews.created_at',
                                            'reviews.review_comment',
                                            'posts.id as post_id',
                                            'posts.title as title',
                                            'category.name as category_name'
                                        )
                                        ->selectSub(function($q) {
                                            $q->select( DB::raw('count(review_comments.id) as count'))->from('review_comments')->whereNull('review_comments.deleted_at')->whereRaw("`review_comments`.`review_id` = `reviews`.`id`");
                                        }, 'comments_count')
                                        ->selectSub(function($q) use($user) {
                                            if($user){
                                                $q->select( DB::raw('count(review_likes.id) as count'))->from('review_likes')->whereNull('review_likes.deleted_at')->where('review_likes.user_id', $user->id)->whereRaw("`review_likes`.`review_id` = `reviews`.`id`");
                                            }else{
                                                $q->select( DB::raw('count(review_likes.id) as count'))->from('review_likes')->whereNull('review_likes.deleted_at')->whereRaw("`review_likes`.`review_id` = `reviews`.`id`");
                                            }
                                        }, 'likes_count')
                                        ->where('reviews.entity_type_id', EntityTypes::HOSPITAL)
                                        ->whereNull('reviews.deleted_at')
                                        ->whereNull('posts.deleted_at');

                $reviewsPopularQuery = DB::table('reviews')->leftJoin('review_category','review_category.review_id','reviews.id')
                                        ->leftJoin('posts','posts.id','reviews.entity_id')
                                        ->join('hospitals', function ($join) {
                                            $join->on('posts.hospital_id', '=', 'hospitals.id');
                                        })
                                        ->leftjoin('addresses', function ($join) {
                                            $join->on('hospitals.id', '=', 'addresses.entity_id')
                                                ->where('addresses.entity_type_id', EntityTypes::HOSPITAL);
                                        })
                                        ->leftjoin('user_entity_relation', function ($join) {
                                            $join->on('hospitals.id', '=', 'user_entity_relation.entity_id')
                                                ->where('user_entity_relation.entity_type_id', EntityTypes::HOSPITAL);
                                        })
                                        ->leftjoin('users_detail', function ($join) {
                                            $join->on('user_entity_relation.user_id', '=', 'users_detail.user_id');
                                        })
                                        ->join('category','category.id','posts.category_id')
                                        ->select(
                                            'reviews.id',
                                            'user_entity_relation.user_id',
                                            'reviews.rating',
                                            'reviews.created_at',
                                            'reviews.review_comment',
                                            'posts.id as post_id',
                                            'posts.title as title',
                                            'category.name as category_name'
                                        )
                                        ->selectSub(function($q) {
                                            $q->select( DB::raw('count(review_comments.id) as count'))->from('review_comments')->whereNull('review_comments.deleted_at')->whereRaw("`review_comments`.`review_id` = `reviews`.`id`");
                                        }, 'comments_count')
                                        ->selectSub(function($q) use($user) {
                                            if($user){
                                                $q->select( DB::raw('count(review_likes.id) as count'))->from('review_likes')->whereNull('review_likes.deleted_at')->where('review_likes.user_id', $user->id)->whereRaw("`review_likes`.`review_id` = `reviews`.`id`");
                                            }else{
                                                $q->select( DB::raw('count(review_likes.id) as count'))->from('review_likes')->whereNull('review_likes.deleted_at')->whereRaw("`review_likes`.`review_id` = `reviews`.`id`");
                                            }
                                        }, 'likes_count')
                                        ->where('reviews.entity_type_id', EntityTypes::HOSPITAL)
                                        ->whereNull('reviews.deleted_at')
                                        ->whereNull('posts.deleted_at');

                $reviewsBestPickQuery = DB::table('reviews')->leftJoin('review_category','review_category.review_id','reviews.id')
                                        ->leftJoin('posts','posts.id','reviews.entity_id')
                                        ->join('hospitals', function ($join) {
                                            $join->on('posts.hospital_id', '=', 'hospitals.id');
                                        })
                                        ->leftjoin('addresses', function ($join) {
                                            $join->on('hospitals.id', '=', 'addresses.entity_id')
                                                ->where('addresses.entity_type_id', EntityTypes::HOSPITAL);
                                        })
                                        ->leftjoin('user_entity_relation', function ($join) {
                                            $join->on('hospitals.id', '=', 'user_entity_relation.entity_id')
                                                ->where('user_entity_relation.entity_type_id', EntityTypes::HOSPITAL);
                                        })
                                        ->leftjoin('users_detail', function ($join) {
                                            $join->on('user_entity_relation.user_id', '=', 'users_detail.user_id');
                                        })
                                        ->join('category','category.id','posts.category_id')
                                        ->select(
                                            'reviews.id',
                                            'user_entity_relation.user_id',
                                            'reviews.rating',
                                            'reviews.created_at',
                                            'reviews.review_comment',
                                            'posts.id as post_id'
                                        )
                                        ->selectSub(function($q) {
                                            $q->select( DB::raw('count(review_likes.id) as count'))->from('review_likes')->whereNull('review_likes.deleted_at')->whereRaw("`review_likes`.`review_id` = `reviews`.`id`");
                                        }, 'likes_count')
                                        ->where('reviews.entity_type_id', EntityTypes::HOSPITAL)
                                        ->whereNull('reviews.deleted_at')
                                        ->whereNull('posts.deleted_at');

                if(!empty($inputs['category_id'])){
                    $reviewsRecentQuery = $reviewsRecentQuery->where('posts.category_id',$inputs['category_id']);
                    $reviewsPopularQuery = $reviewsPopularQuery->where('posts.category_id',$inputs['category_id']);
                    $reviewsBestPickQuery = $reviewsBestPickQuery->where('posts.category_id',$inputs['category_id']);
                }

                $distance = "(6371 * acos(cos(radians(".$inputs['latitude']."))
                     * cos(radians(addresses.latitude))
                     * cos(radians(addresses.longitude)
                     - radians(".$inputs['longitude']."))
                     + sin(radians(".$inputs['latitude']."))
                     * sin(radians(addresses.latitude))))";

                $sqlPriority = DB::raw('case when `shops`.`expose_distance` IS NOT NULL then `shops`.`expose_distance`
                when `users_detail`.package_plan_id = '. PackagePlan::BRONZE .' and '. $distance .'  < '. $bronzePlanKm .' then 4
                when `users_detail`.package_plan_id = '. PackagePlan::SILVER .' and '. $distance .'  < '. $silverPlanKm .' then 3
                when `users_detail`.package_plan_id = '. PackagePlan::GOLD .' and '. $distance .' < '. $goldPlanKm .' then 2
                when `users_detail`.package_plan_id = '. PackagePlan::PLATINIUM .' and '. $distance .' < '. $platiniumPlanKm .' then 1
                else 5 end ');

                $reviewsBestPick = $reviewsBestPickQuery->whereDate('reviews.created_at', '>=' , Carbon::now()->subDays(30))
                                        ->distinct('reviews.id')
                                        ->orderby('likes_count','desc')
                                        ->orderby('distance')
                                        ->selectRaw("{$distance} AS distance")
                                        ->selectRaw("{$sqlPriority} AS priority")
                                        ->paginate(config('constant.review_pagination_count'),"*","best_pick_page");
                $reviewsPopular = $reviewsPopularQuery->distinct('reviews.id')
                                    ->orderby('likes_count','desc')
                                    ->orderby('priority')
                                    ->orderby('distance')
                                    ->selectRaw("{$distance} AS distance")
                                    ->selectRaw("{$sqlPriority} AS priority")
                                    ->paginate(config('constant.review_pagination_count'),"*","popular_list_page");

                $reviewsRecent = $reviewsRecentQuery->distinct('reviews.id')
                                    ->orderby('reviews.id','desc')
                                    ->orderby('likes_count','desc')
                                    ->orderby('priority')
                                    ->orderby('distance')
                                    ->selectRaw("{$distance} AS distance")
                                    ->selectRaw("{$sqlPriority} AS priority")
                                    ->paginate(config('constant.review_pagination_count'),"*","recent_list_page");

                $reviewsPopularData = collect($reviewsPopular)->toArray();
                foreach($reviewsPopularData['data'] as $rp) {
                    if($user) {
                        $reviewLikes = ReviewLikes::where('review_id',$rp->id)->where('user_id',$user->id)->count();
                    }else {
                        $reviewLikes = 0;
                    }
                    $rp->is_liked = $reviewLikes > 0 ? true : false;

                    $rp->before_images = ReviewImages::where('review_id',$rp->id)->where('type',ReviewImages::BEFORE)->pluck('image');
                    $rp->after_images = ReviewImages::where('review_id',$rp->id)->where('type',ReviewImages::AFTER)->pluck('image');

                    $userData = DB::table('users_detail')->where('user_id',$rp->user_id)->first();
                    $rp->user_name = $userData->name;

                    $rp->distance = number_format((float)$rp->distance, 1, '.', '');
                }

                $reviewsRecentData = collect($reviewsRecent)->toArray();
                foreach($reviewsRecentData['data'] as $rr) {
                    if($user) {
                        $reviewLikes = ReviewLikes::where('review_id',$rr->id)->where('user_id',$user->id)->count();
                    }else {
                        $reviewLikes = 0;
                    }
                    $rr->is_liked = $reviewLikes > 0 ? true : false;

                    $rr->before_images = ReviewImages::where('review_id',$rr->id)->where('type',ReviewImages::BEFORE)->pluck('image');
                    $rr->after_images = ReviewImages::where('review_id',$rr->id)->where('type',ReviewImages::AFTER)->pluck('image');

                    $userData = DB::table('users_detail')->where('user_id',$rr->user_id)->first();
                    $rr->user_name = $userData->name;
                    $rr->distance = number_format((float)$rr->distance, 1, '.', '');
                }

                $reviewsBestPickData = collect($reviewsBestPick)->toArray();
                foreach($reviewsBestPickData['data'] as $rbp) {
                    if($user) {
                        $reviewLikes = ReviewLikes::where('review_id',$rbp->id)->where('user_id',$user->id)->count();
                    }else {
                        $reviewLikes = 0;
                    }
                    $rbp->is_liked = $reviewLikes > 0 ? true : false;

                    $rbp->before_images = ReviewImages::where('review_id',$rbp->id)->where('type',ReviewImages::BEFORE)->pluck('image');
                    $rbp->after_images = ReviewImages::where('review_id',$rbp->id)->where('type',ReviewImages::AFTER)->pluck('image');

                    $userData = DB::table('users_detail')->where('user_id',$rbp->user_id)->first();
                    $rbp->user_name = $userData->name;
                    $rbp->distance = number_format((float)$rbp->distance, 1, '.', '');
                }

                $bestPickDetail = $this->timeLanguageFilterNew($reviewsBestPickData,$language_id,$timezone);
                $data = [
                    'popular' => [

                        'best_pick' => $bestPickDetail,
                        'popular_list' => $this->timeLanguageFilterNew($reviewsPopularData,$language_id,$timezone),
                    ],
                    'recent' => [
                        'best_pick' => $bestPickDetail,
                        'recent_list' => $this->timeLanguageFilterNew($reviewsRecentData,$language_id,$timezone),
                    ],
                ];

                DB::commit();
                Log::info('End code for the get hospital review');
            return $this->sendSuccessResponse(Lang::get('messages.review.get-success'), 200,$data);

        } catch (\Exception $e) {
            Log::info('Exception in get hospital review');
            Log::info($e);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function getHospitalReviewDetail(Request $request,$id)
    {
        try {
            Log::info('Start code get shop review');
            $inputs = $request->all();
            $user = Auth::user();
            $reviewExists = Reviews::find($id);
            if($reviewExists){
                DB::beginTransaction();
                $validation = Validator::make($request->all(), [
                    'latitude' => 'required',
                    'longitude' => 'required',
                ], [], [
                    'latitude' => 'Latitude',
                    'longitude' => 'Longitude',
                ]);

                if ($validation->fails()) {
                    Log::info('End code for get shop review');
                    return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
                }


                $language_id = isset($inputs['language_id']) ? $inputs['language_id'] : 4;
                if(isset($inputs['latitude']) && isset($inputs['longitude'])) {
                    $main_country = getCountryFromLatLong($inputs['latitude'],$inputs['longitude']);
                    $timezone = get_nearest_timezone($inputs['latitude'],$inputs['longitude'],$main_country);
                }else {
                    $timezone = '';
                }
                Reviews::where('id',$id)->update(['views_count' => DB::raw('views_count + 1')]);
                $returnData = [];
                $review = Reviews::where('id', $id)->first();

                if($review){
                    $returnData = $review->toArray();
                    $returnData['comments'] = $this->commentTimeFilter($returnData['comments'],$language_id,$timezone);
                    $post = Post::find($review->entity_id);
                    $returnData['post_detail'] = $post;
                    $returnData['is_owner'] = ($user && $user->id == $review->user_id) ? true : false;
                    $returnData['detail_before_images'] = ReviewImages::where('review_id',$id)
                            ->where('type',ReviewImages::BEFORE)
                            ->select('id','image')
                            ->get();
                    $returnData['detail_after_images'] = ReviewImages::where('review_id',$id)
                            ->where('type',ReviewImages::AFTER)
                            ->select('id','image')
                            ->get();

                    $returnData['doctor_detail'] = (object)[];
                    if($review->doctor_id){
                        $returnData['doctor_detail'] = Doctor::where('id',$review->doctor_id)->first();
                    }
                    // $reviewComments = ReviewComments::where('review_id',$id)->get();
                    // $review['review_comments'] = $reviewComments;
                    // $returnData = $review;
                }
                DB::commit();
                Log::info('End code for the get shop review');
                return $this->sendSuccessResponse(Lang::get('messages.review.get-success'), 200,$returnData);
            }else{
                return $this->sendSuccessResponse(Lang::get('messages.review.empty'), 402);
            }

        } catch (\Exception $e) {
            Log::info('Exception in get shop review');
            Log::info($e);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function likeReview($id)
    {
        try {
            Log::info('Start code like review');
            $user = Auth::user();
            if($user){
                $review = Reviews::find($id);
                if($review){
                    DB::beginTransaction();
                    $data = [
                        'user_id' => $user->id,
                        'review_id' => $id,
                    ];
                    $reviewLike = ReviewLikes::create($data);
                    $review = Reviews::find($id);

                    UserPoints::updateOrCreate([
                        'user_id' => $user->id,
                        'entity_type' => UserPoints::LIKE_COMMUNITY_OR_REVIEW_POST,
                        'entity_created_by_id' => $review->user_id,
                    ],['entity_id' => $id, 'points' => UserPoints::LIKE_COMMUNITY_OR_REVIEW_POST_POINT]);

                    $isNotice = Notice::where(['notify_type' => Notice::LIKE_COMMUNITY_OR_REVIEW_POST,'user_id' => $user->id,'to_user_id' => $review->user_id])->whereDate('created_at',Carbon::now()->format('Y-m-d'))->count();

                    if($isNotice == 0){
                    // Send Push notification start
                        $user_detail = UserDetail::where('user_id', $review->user_id)->first();
                        $language_id = $user_detail->language_id;
                        $nextCardLevel = getUserNextAwailLevel($user->id,$user_detail->level);
                        $next_level_key = "language_$language_id.next_level_card";
                        $next_level_msg = __("messages.$next_level_key", ['level' => $nextCardLevel]);

                        $notice = Notice::create([
                            'notify_type' => Notice::LIKE_COMMUNITY_OR_REVIEW_POST,
                            'user_id' => $user->id,
                            'to_user_id' => $review->user_id,
                            'entity_type_id' => EntityTypes::REVIEWS,
                            'entity_id' => $review->id,
                            'title' => '+'.UserPoints::LIKE_COMMUNITY_OR_REVIEW_POST_POINT.'exp',
                            'sub_title' => $nextCardLevel, //$review->review_comment,
                            'is_aninomity' => 0
                        ]);

                        $key = Notice::LIKE_COMMUNITY_OR_REVIEW_POST.'_'.$language_id;
                        $userIds = [$review->user_id];

                        $format =  '+'.UserPoints::LIKE_COMMUNITY_OR_REVIEW_POST_POINT.'exp \n'.$next_level_msg;
                        $devices = UserDevices::whereIn('user_id', $userIds)->pluck('device_token')->toArray();

                        $title_msg = __("notice.$key");
                        $notify_type = Notice::LIKE_COMMUNITY_OR_REVIEW_POST;

                        $notificationData = [
                            'id' => $review->id,
                            'user_id' => $review->user_id,
                            'title' => $title_msg,
                        ];
                        if (count($devices) > 0) {
                            $result = $this->sentPushNotification($devices,$title_msg, $format, $notificationData ,$notify_type);
                        }
                            // Send Push notification end
                    }

                    DB::commit();
                    Log::info('End code for the like review');
                    return $this->sendSuccessResponse(Lang::get('messages.review.like-success'), 200,$review);
                }else{
                    return $this->sendSuccessResponse(Lang::get('messages.review.empty'), 402);
                }
            }else{
                Log::info('End code for like review');
                return $this->sendSuccessResponse(Lang::get('messages.user.token_expired'), 401);
            }
        } catch (\Exception $e) {
            Log::info('Exception in like review');
            Log::info($e);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function unlikeReview($id)
    {
        try {
            Log::info('Start code unlike review');
            $user = Auth::user();
            if($user){
                $review = Reviews::find($id);
                if($review){
                    DB::beginTransaction();

                    UserPoints::updateOrCreate([
                        'user_id' => $user->id,
                        'entity_type' => UserPoints::LIKE_REVIEW_POST,
                        'entity_id' => $id,
                        'entity_created_by_id' => $review->user_id,
                    ])->delete();

                    $communityunLike = ReviewLikes::where('user_id',$user->id)->where('review_id',$id)->forcedelete();
                    DB::commit();
                    Log::info('End code for the unlike review');
                    return $this->sendSuccessResponse(Lang::get('messages.review.unlike-success'), 200);
                }else{
                    return $this->sendSuccessResponse(Lang::get('messages.review.empty'), 402);
                }
            }else{
                Log::info('End code for unlike review');
                return $this->sendSuccessResponse(Lang::get('messages.user.token_expired'), 401);
            }
        } catch (\Exception $e) {
            Log::info('Exception in unlike review');
            Log::info($e);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function commentReview(Request $request, $id)
    {
        try {
            Log::info('Start code comment review');
            $user = Auth::user();
            $inputs = $request->all();
            if($user){
                $review = Reviews::find($id);
                if($review){
                    DB::beginTransaction();
                    $validation = $this->reviewsValidator->validateReviewComment($inputs);
                    if ($validation->fails()) {
                        Log::info('End code for comment review');
                        return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
                    }
                    $data = [
                        'user_id' => $user->id,
                        'review_id' => $id,
                        'comment' => $inputs['comment']
                    ];
                    $reviewComment = ReviewComments::create($data);
                    $review = Reviews::find($id);

                    if($user->id != $review->user_id){

                        $isAvailable = UserPoints::where(['user_id' => $user->id,'entity_type' => UserPoints::COMMENT_ON_REVIEW_POST,'entity_created_by_id' => $user->id])->whereDate('created_at',Carbon::now()->format('Y-m-d'))->first();

                        if(empty($isAvailable)){

                            UserPoints::create([
                                'user_id' => $user->id,
                                'entity_type' => UserPoints::COMMENT_ON_REVIEW_POST,
                                'entity_id' => $reviewComment->id,
                                'entity_created_by_id' => $user->id,
                                'points' => UserPoints::COMMENT_ON_REVIEW_POST_POINT]);
                        }

                        $notice = Notice::create([
                            'notify_type' => Notice::REVIEW_POST_COMMENT,
                            'user_id' => $user->id,
                            'to_user_id' => $review->user_id,
                            'entity_type_id' => EntityTypes::REVIEWS,
                            'entity_id' => $review->id,
                            'title' => $review->review_comment,
                            'sub_title' => $inputs['comment']
                        ]);

                        $user_detail = UserDetail::where('user_id', $review->user_id)->first();
                        $language_id = $user_detail->language_id;
                        $key = Notice::REVIEW_POST_COMMENT.'_'.$language_id;

                        $userIds = [$review->user_id];

                        $format = __("notice.$key", ['username' => $user->name]);
                        $devices = UserDevices::whereIn('user_id', $userIds)->pluck('device_token')->toArray();
                        $title_msg = '';
                        $notify_type = 'notices';

                        $notificationData = $review->toArray();
                        if (count($devices) > 0) {
                            $result = $this->sentPushNotification($devices,$title_msg, $format, $notificationData ,$notify_type);
                        }
                    }


                    // $review = Reviews::find($id);
                    DB::commit();
                    Log::info('End code for the comment review');
                    return $this->sendSuccessResponse(Lang::get('messages.review.comment-success'), 200,$reviewComment);
                }else{
                    return $this->sendSuccessResponse(Lang::get('messages.review.empty'), 402);
                }
            }else{
                Log::info('End code for comment review');
                return $this->sendSuccessResponse(Lang::get('messages.user.token_expired'), 401);
            }
        } catch (\Exception $e) {
            Log::info('Exception in comment review');
            Log::info($e);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function reviewCommentReply(Request $request, $id)
    {
        try {
            Log::info('Start code comment review reply');
            $user = Auth::user();
            $inputs = $request->all();
            if($user){
                $review = ReviewComments::find($id);
                if($review){
                    DB::beginTransaction();
                    $validation = $this->reviewsValidator->validateReviewCommentReply($inputs);
                    if ($validation->fails()) {
                        Log::info('End code for comment review reply');
                        return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
                    }
                    $data = [
                        'user_id' => $user->id,
                        'review_comment_id' => $id,
                        'comment' => $inputs['comment'],
                        'reply_parent_id' => $request->has('main_comment_reply_id') && $inputs['main_comment_reply_id'] != 0 ? $inputs['main_comment_reply_id'] : null
                    ];
                    $reviewReply = ReviewCommentReply::create($data);
                    $review = ReviewComments::find($id);

                    // Send Notification and notice to parent
                    if($request->has('main_comment_reply_id') && $inputs['main_comment_reply_id'] != 0){
                        $parentCommentId = $inputs['main_comment_reply_id'];

                        $parentComment = ReviewCommentReply::where(['id' => $parentCommentId])->first();

                        if($user->id != $parentComment->user_id){

                            Notice::create([
                                'notify_type' => Notice::REVIEW_REPLY_COMMENT,
                                'user_id' => $user->id,
                                'to_user_id' => $parentComment->user_id,
                                'entity_type_id' => EntityTypes::REVIEWS,
                                'entity_id' => $id,
                                'sub_title' => $inputs['comment']
                            ]);

                            $user_detail = UserDetail::where('user_id', $parentComment->user_id)->first();

                            $language_id = $user_detail->language_id;
                            $key = Notice::REVIEW_REPLY_COMMENT.'_'.$language_id;

                            $userIds = [$parentComment->user_id];

                            $format = __("notice.$key", ['username' => $user->name]);
                            $devices = UserDevices::whereIn('user_id', $userIds)->pluck('device_token')->toArray();
                            $title_msg = '';
                            $notify_type = 'notices';

                            $notificationData = $review->toArray();
                            if (count($devices) > 0) {
                                $result = $this->sentPushNotification($devices,$title_msg, $format, $notificationData ,$notify_type);
                            }

                        }

                    }

                    // Send Notification and notice to Main parent Comment
                    if(empty($inputs['main_comment_reply_id'])){
                        if($user->id != $review->user_id){

                            Notice::create([
                                'notify_type' => Notice::REVIEW_REPLY_COMMENT,
                                'user_id' => $user->id,
                                'to_user_id' => $review->user_id,
                                'entity_type_id' => EntityTypes::REVIEWS,
                                'entity_id' => $review->review_id,
                                'sub_title' => $inputs['comment']
                            ]);

                            $user_detail = UserDetail::where('user_id', $review->user_id)->first();
                            $language_id = $user_detail->language_id;
                            $key = Notice::REVIEW_REPLY_COMMENT.'_'.$language_id;

                            $userIds = [$review->user_id];

                            $format = __("notice.$key", ['username' => $user->name]);
                            $devices = UserDevices::whereIn('user_id', $userIds)->pluck('device_token')->toArray();
                            $title_msg = '';
                            $notify_type = 'notices';

                            $notificationData = $review->toArray();
                            if (count($devices) > 0) {
                                $result = $this->sentPushNotification($devices,$title_msg, $format, $notificationData ,$notify_type);
                            }

                        }
                    }



                    DB::commit();
                    Log::info('End code for the comment review reply');
                    return $this->sendSuccessResponse(Lang::get('messages.review.comment-success'), 200,$reviewReply);
                }else{
                    return $this->sendSuccessResponse(Lang::get('messages.review.comment-empty'), 402);
                }
            }else{
                Log::info('End code for comment review reply');
                return $this->sendSuccessResponse(Lang::get('messages.user.token_expired'), 401);
            }
        } catch (\Exception $e) {
            print_r($e->getMessage());die;
            Log::info('Exception in comment review reply');
            Log::info($e);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function likeReviewComment($id)
    {
        try {
            Log::info('Start code like review');
            $user = Auth::user();
            if($user){
                $reviewComment = ReviewComments::find($id);
                if($reviewComment){
                    DB::beginTransaction();
                    $data = [
                        'user_id' => $user->id,
                        'review_comment_id' => $id,
                    ];
                    $reviewLike = ReviewCommentLikes::create($data);
                    $review = ReviewComments::find($id);
                    DB::commit();
                    Log::info('End code for the like review');
                    return $this->sendSuccessResponse(Lang::get('messages.review.comment-like-success'), 200,$review);
                }else{
                    return $this->sendSuccessResponse(Lang::get('messages.review.comment-empty'), 402);
                }
            }else{
                Log::info('End code for like review');
                return $this->sendSuccessResponse(Lang::get('messages.user.token_expired'), 401);
            }
        } catch (\Exception $e) {
            Log::info('Exception in like review');
            Log::info($e);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function deleteReviewComment($id)
    {
        try {
            Log::info('Start code delete review comment');
            $user = Auth::user();
            if($user){
                $reviewComment = ReviewComments::find($id);
                if($reviewComment){
                    DB::beginTransaction();
                    $review = ReviewComments::where('id',$id)->delete();
                    DB::commit();
                    Log::info('End code for the delete review comment');
                    return $this->sendSuccessResponse(Lang::get('messages.review.comment-delete-success'), 200,[]);
                }else{
                    return $this->sendSuccessResponse(Lang::get('messages.review.comment-empty'), 402);
                }
            }else{
                Log::info('End code for delete review comment');
                return $this->sendSuccessResponse(Lang::get('messages.user.token_expired'), 401);
            }
        } catch (\Exception $e) {
            Log::info('Exception in delete review comment');
            Log::info($e);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function reviewCommentReplyLike(Request $request, $id)
    {
        try {
            Log::info('Start code comment review reply like');
            $user = Auth::user();
            $inputs = $request->all();
            if($user){
                $reviewCommentReply = ReviewCommentReply::find($id);
                if($reviewCommentReply){
                    DB::beginTransaction();
                    $data = [
                        'user_id' => $user->id,
                        'review_comment_reply_id' => $id,
                    ];
                    $reviewLike = ReviewCommentReplyLikes::create($data);
                    $return = ReviewCommentReply::find($id);
                    DB::commit();
                    Log::info('End code for the comment review reply like');
                    return $this->sendSuccessResponse(Lang::get('messages.review.like-success'), 200,$return);
                }else{
                    return $this->sendSuccessResponse(Lang::get('messages.review.comment-empty'), 402);
                }
            }else{
                Log::info('End code for comment review reply like');
                return $this->sendSuccessResponse(Lang::get('messages.user.token_expired'), 401);
            }
        } catch (\Exception $e) {
            Log::info('Exception in comment review reply like');
            Log::info($e);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }
    public function reviewCommentReplyDelete(Request $request, $id)
    {
        try {
            Log::info('Start code comment review reply delete');
            $user = Auth::user();
            $inputs = $request->all();
            if($user){
                $reviewCommentReply = ReviewCommentReply::find($id);
                if($reviewCommentReply){
                    DB::beginTransaction();
                    $reviewReply = ReviewCommentReply::where('id',$id)->delete();
                    DB::commit();
                    Log::info('End code for the comment review reply delete');
                    return $this->sendSuccessResponse(Lang::get('messages.review.comment-delete-success'), 200,[]);
                }else{
                    return $this->sendSuccessResponse(Lang::get('messages.review.comment-empty'), 402);
                }
            }else{
                Log::info('End code for comment review reply delete');
                return $this->sendSuccessResponse(Lang::get('messages.user.token_expired'), 401);
            }
        } catch (\Exception $e) {
            Log::info('Exception in comment review reply delete');
            Log::info($e);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function updateReviewComment(Request $request,$id)
    {
        try {
            Log::info('Start code edit review comment');
            $inputs = $request->all();
            $user = Auth::user();
            if($user){
                $reviewComment = ReviewComments::find($id);
                if($reviewComment){
                    DB::beginTransaction();
                    $validation = $this->reviewsValidator->validateReviewComment($inputs);
                    if ($validation->fails()) {
                        Log::info('End code for comment review');
                        return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
                    }
                    $review = ReviewComments::where('id',$id)->update(['comment' => $inputs['comment']]);
                    $reviewComment = ReviewComments::find($id);
                    DB::commit();
                    Log::info('End code for the edit review comment');
                    return $this->sendSuccessResponse(Lang::get('messages.review.comment-edit-success'), 200,$reviewComment);
                }else{
                    return $this->sendSuccessResponse(Lang::get('messages.review.comment-empty'), 402);
                }
            }else{
                Log::info('End code for edit review comment');
                return $this->sendSuccessResponse(Lang::get('messages.user.token_expired'), 401);
            }
        } catch (\Exception $e) {
            Log::info('Exception in edit review comment');
            Log::info($e);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function updateReviewCommentReply(Request $request, $id)
    {
        try {
            Log::info('Start code comment review reply update');
            $user = Auth::user();
            $inputs = $request->all();
            if($user){
                $reviewCommentReply = ReviewCommentReply::find($id);
                if($reviewCommentReply){
                    DB::beginTransaction();
                    $reviewReply = ReviewCommentReply::where('id',$id)->update(['comment' => $inputs['comment']]);
                    DB::commit();
                    $reviewCommentReply = ReviewCommentReply::find($id);
                    Log::info('End code for the comment review reply update');
                    return $this->sendSuccessResponse(Lang::get('messages.review.comment-edit-success'), 200,$reviewCommentReply);
                }else{
                    return $this->sendSuccessResponse(Lang::get('messages.review.comment-empty'), 402);
                }
            }else{
                Log::info('End code for comment review reply update');
                return $this->sendSuccessResponse(Lang::get('messages.user.token_expired'), 401);
            }
        } catch (\Exception $e) {
            Log::info('Exception in comment review reply update');
            Log::info($e);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function deleteReview($id)
    {
        try {
            Log::info('Start code delete review');
            $user = Auth::user();
            if($user){
                $reviewComment = Reviews::find($id);
                if($reviewComment){
                    DB::beginTransaction();

                    UserPoints::where([
                        'entity_type' => ($reviewComment->entity_type_id == EntityTypes::SHOP) ? UserPoints::REVIEW_SHOP_POST : UserPoints::REVIEW_HOSPITAL_POST,
                        'entity_id' => $id,
                    ])->delete();

                    $review = Reviews::where('id',$id)->delete();
                    DB::commit();
                    Log::info('End code for the delete review');
                    return $this->sendSuccessResponse(Lang::get('messages.review.delete-success'), 200,[]);
                }else{
                    return $this->sendSuccessResponse(Lang::get('messages.review.comment-empty'), 402);
                }
            }else{
                Log::info('End code for delete review');
                return $this->sendSuccessResponse(Lang::get('messages.user.token_expired'), 401);
            }
        } catch (\Exception $e) {
            Log::info('Exception in delete review');
            Log::info($e);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }
}
