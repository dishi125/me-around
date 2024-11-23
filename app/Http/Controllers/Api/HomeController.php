<?php

namespace App\Http\Controllers\Api;

use Validator;
use Carbon\Carbon;
use App\Models\Post;
use App\Models\Shop;
use App\Models\Banner;
use App\Models\Config;
use App\Models\Status;
use App\Models\Address;
use App\Models\HashTag;
use App\Models\Category;
use App\Models\Hospital;
use App\Models\ShopPost;
use App\Models\PostImages;
use App\Models\ShopImages;
use App\Models\CreditPlans;
use App\Models\EntityTypes;
use App\Models\PackagePlan;
use App\Models\SliderPosts;
use Illuminate\Http\Request;
use App\Models\CategoryTypes;
use App\Models\ShopFollowers;
use App\Models\ConfigLanguages;
use App\Models\ShopImagesTypes;
use App\Models\MultipleShopPost;
use App\Models\RequestedCustomer;
use App\Models\SavedHistoryTypes;
use App\Models\ShopDetailLanguage;
use App\Models\UserEntityRelation;
use App\Models\UserHiddenCategory;
use Illuminate\Support\Facades\DB;
use App\Models\ConfigCountryDetail;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Models\RequestBookingStatus;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use App\Validators\ShopProfileValidator;
use JWTAuth;

class HomeController extends Controller
{
    private $shopProfileValidator;

    function __construct()
    {
        $this->shopProfileValidator = new ShopProfileValidator();
    }

    public function index(Request $request)
    {
        try {
            Log::info('Start code for get home data');
            DB::beginTransaction();
            $inputs = $request->all();
            $data = [];
            $user = Auth::user();
            $validation = $this->shopProfileValidator->validateGetShop($inputs);

            if ($validation->fails()) {
                Log::info('End code for get all shops');
                return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
            }

            $main_country = getCountryFromLatLong($inputs['latitude'], $inputs['longitude']);
            $timezone = get_nearest_timezone($inputs['latitude'], $inputs['longitude'], $main_country);

            $language_id = $inputs['language_id'] ?? 4;

            $latitude = $inputs['latitude'] ?? '';
            $longitude = $inputs['longitude'] ?? '';
            $menu_key = $inputs['menu_key'] ?? 'all';

            if(!empty($user)){
                $user_id = $user->id;
                $user_type = UserHiddenCategory::LOGIN;
            }else{
                $user_id = $inputs['user_id'] ?? null;
                $user_type = UserHiddenCategory::NONLOGIN;
            }

            $coordinate = $longitude . ',' . $latitude;

            $distance = "(6371 * acos(cos(radians(" . $inputs['latitude'] . "))
                            * cos(radians(addresses.latitude))
                        * cos(radians(addresses.longitude)
                    - radians(" . $inputs['longitude'] . "))
                    + sin(radians(" . $inputs['latitude'] . "))
                        * sin(radians(addresses.latitude))))";

            if ($user) {
                $upcomingBookings = RequestedCustomer::leftjoin('shops', function ($join) {
                    $join->on('shops.id', '=', 'requested_customer.entity_id')
                        ->where('requested_customer.entity_type_id', EntityTypes::SHOP);
                })
                    ->leftjoin('posts', function ($join) {
                        $join->on('posts.id', '=', 'requested_customer.entity_id')
                            ->where('requested_customer.entity_type_id', EntityTypes::HOSPITAL);
                    })
                    ->leftjoin('hospitals', function ($join) {
                        $join->on('hospitals.id', '=', 'posts.hospital_id');
                    })
                    ->leftjoin('user_entity_relation', function ($join) {
                        $join->on('hospitals.id', '=', 'user_entity_relation.entity_id')
                            ->where('user_entity_relation.entity_type_id', EntityTypes::HOSPITAL);
                    })
                    ->select(
                        'requested_customer.*',
                        \DB::raw('(CASE
                                                    WHEN requested_customer.entity_type_id = 1 THEN  shops.user_id
                                                    WHEN requested_customer.entity_type_id = 2 THEN user_entity_relation.user_id
                                                    ELSE ""
                                                    END) AS shop_user_id')
                    )
                    ->where(function ($q) {
                        $q->where('shops.status_id', Status::ACTIVE)->orWhere('posts.status_id', Status::ACTIVE);
                    })
                    ->where('requested_customer.user_id', $user->id)
                    ->whereIn('requested_customer.request_booking_status_id', [RequestBookingStatus::BOOK, RequestBookingStatus::COMPLETE])
                    ->where('requested_customer.show_in_home', 1)
                    ->orderBy('requested_customer.request_booking_status_id', 'asc')
                    ->orderBy('requested_customer.id', 'desc')
                    ->offset(0)->limit(config('constant.limit_count'))
                    ->get();

                $upcoming_and_completed_booking = [];
                foreach ($upcomingBookings as $booking) {
                    $entityId = $booking['entity_id'];
                    if ($booking['entity_type_id'] == EntityTypes::HOSPITAL) {
                        $post = Post::find($booking['entity_id']);
                        $entityId = $post->hospital_id;
                    }
                    $location = Address::where('entity_type_id', $booking['entity_type_id'])
                        ->where('entity_id', $entityId)->first();

                    $booking['location'] = $location;
                    if (count($upcoming_and_completed_booking) < config('constant.limit_count')) {
                        if ($booking->shop_user_id) {
                            $booking['shop_user_detail'] = DB::table('users')->select('chat_status', 'status_id')->where('id', $booking->shop_user_id)->whereNull('deleted_at')->first();
                        } else {
                            $booking['shop_user_detail'] = ["chat_status" => 0, "status_id" => 0];
                        }

                        if ($booking->request_booking_status_id == RequestBookingStatus::COMPLETE && $booking->review_done == 0) {
                            $booking['time_left'] = [
                                'days' => 0,
                                'hours' => 0,
                                'minutes' => 0,
                                'seconds' => 0,
                            ];
                            $booking->booking_date = Carbon::createFromFormat('Y-m-d H:i:s', $booking->booking_date, "UTC")->setTimezone($timezone);
                            $upcoming_and_completed_booking[] = $booking;
                        } else {
                            $currTime = Carbon::now()->format('Y-m-d H:i:s');
                            $startTime = Carbon::createFromFormat('Y-m-d H:i:s', $booking->booking_date, "UTC")->setTimezone($timezone);
                            $finishTime = Carbon::createFromFormat('Y-m-d H:i:s', $currTime, "UTC")->setTimezone($timezone);
                            $totalDuration = $finishTime->diffInSeconds($startTime);

                            $date = strtotime($booking->booking_date);
                            $seconds =  $totalDuration;
                            $days = floor($seconds / 86400);
                            $seconds %= 86400;
                            $hours = floor($seconds / 3600);
                            $seconds %= 3600;
                            $minutes = floor($seconds / 60);
                            $seconds %= 60;

                            $booking['time_left'] = [
                                'days' => $days,
                                'hours' => $hours,
                                'minutes' => $minutes,
                                'seconds' => $seconds,
                            ];
                            $booking->booking_date = Carbon::createFromFormat('Y-m-d H:i:s', $booking->booking_date, "UTC")->setTimezone($timezone);
                            $upcoming_and_completed_booking[] = $booking;
                        }
                    }
                }
                $data['upcoming_and_completed_booking'] = $upcoming_and_completed_booking;
                $data['following_shop_post'] = [];

                $followingShops = Shop::join('shop_followers', function ($join) use ($user) {
                    $join->on('shops.id', '=', 'shop_followers.shop_id')
                        ->where('shop_followers.user_id', $user->id);
                })
                    ->where('shops.status_id', Status::ACTIVE)
                    ->groupBy('shops.id')
                    ->get('shops.*')->count();

                $data['following_shop_count'] = $followingShops;

                if ($followingShops > 0) {
                    $data['following_shop_post'] = ShopPost::join('shops', 'shop_posts.shop_id', 'shops.id')
                        ->join('shop_followers', function ($join) use ($user, $followingShops) {
                            $join->on('shops.id', '=', 'shop_followers.shop_id');
                            if ($followingShops > 0) {
                                $join->where('shop_followers.user_id', $user->id);
                            }
                        })
                        ->join('category', function ($join) {
                            $join->on('shops.category_id', '=', 'category.id')
                                ->whereNull('category.deleted_at');
                        })
                        ->where('shops.status_id', Status::ACTIVE)
                        ->orderBy('shop_posts.created_at', 'desc')
                        ->limit(12)
                        ->groupBy('shop_posts.id')
                        ->select('shop_posts.*')
                        ->get();
                }
                //else{
                $recentFollowData = $this->shopRecentUpdatedPost($main_country, $distance, $language_id, false, 21, $coordinate,$user_id,$user_type,$menu_key);
                //}

                //$recentFollowPortfolio = filterPorfolioDate($recentFollowPortfolio,$timezone);
                $data['recent_following_shop_portfolio'] = $recentFollowData['results'];
                $data['recent_following_type'] = $recentFollowData['type'];


                $data['user_hospital_count'] = UserEntityRelation::where('entity_type_id', EntityTypes::HOSPITAL)->where('user_id', $user->id)->count();
                $data['user_shop_count'] = UserEntityRelation::where('entity_type_id', EntityTypes::SHOP)->where('user_id', $user->id)->count();
                $data['user_details'] = ['id' => $user->id, 'status_id' => $user->status_id, 'chat_status' => $user->chat_status];
            } else {
                $data['following_shop_post'] = [];
                $data['upcoming_and_completed_booking'] = [];
                // $data['review_completed_bookings'] = [];
                $recentFollowData = $this->shopRecentUpdatedPost($main_country, $distance, $language_id, false, 21, $coordinate,$user_id,$user_type,$menu_key);

                $data['recent_following_shop_portfolio'] = $recentFollowData['results'];
                $data['recent_following_type'] = $recentFollowData['type'];

                $data['following_shop_count'] = 0;
                $data['user_hospital_count'] = 0;
                $data['user_shop_count'] = 0;
                $data['user_details'] = (object)[];
            }

            $topPosts =  Post::join('slider_posts', 'slider_posts.post_id', 'posts.id')
                ->where('slider_posts.section', SliderPosts::HOME)->where('slider_posts.category_id', NULL)
                ->select('posts.*')
                ->get()->groupBy('category_name');
            $data['top_posts'] = $topPosts;


            $data['banner_images'] = loadBannerImages($main_country);

            $creditPlans = CreditPlans::where('entity_type_id', EntityTypes::SHOP)->get();
            $bronzePlanKm = $silverPlanKm = $goldPlanKm = $platiniumPlanKm = 0;
            foreach ($creditPlans as $plan) {
                if ($plan->package_plan_id == PackagePlan::BRONZE) {
                    $bronzePlanKm = $plan->km;
                } else if ($plan->package_plan_id == PackagePlan::SILVER) {
                    $silverPlanKm = $plan->km;
                } else if ($plan->package_plan_id == PackagePlan::GOLD) {
                    $goldPlanKm = $plan->km;
                } else if ($plan->package_plan_id == PackagePlan::PLATINIUM) {
                    $platiniumPlanKm = $plan->km;
                }
            }

            $limitByPackage = DB::raw('case when `shops`.`expose_distance` IS NOT NULL then `shops`.`expose_distance`
                when `users_detail`.package_plan_id = ' . PackagePlan::BRONZE . ' then ' . $bronzePlanKm . '
                when `users_detail`.package_plan_id = ' . PackagePlan::SILVER . ' then ' . $silverPlanKm . '
                when `users_detail`.package_plan_id = ' . PackagePlan::GOLD . ' then ' . $goldPlanKm . '
                when `users_detail`.package_plan_id = ' . PackagePlan::PLATINIUM . ' then ' . $platiniumPlanKm . '
                else 40 end ');

            $shopPost = ShopPost::join('shops', 'shop_posts.shop_id', 'shops.id')
                ->leftjoin('addresses', function ($join) {
                    $join->on('shops.id', '=', 'addresses.entity_id')
                        ->where('addresses.entity_type_id', EntityTypes::SHOP);
                })->leftjoin('user_saved_history', function ($join) use ($user) {
                    $join->on('shop_posts.id', '=', 'user_saved_history.entity_id')->where('user_saved_history.saved_history_type_id', SavedHistoryTypes::SHOP)->whereRaw('user_saved_history.id IN (select MAX(a2.id) from user_saved_history as a2 join shop_posts as u2 on u2.id = a2.entity_id group by u2.id ORDER BY MAX(a2.id) DESC)');
                })
                ->leftjoin('users_detail', function ($join) {
                    $join->on('shops.user_id', '=', 'users_detail.user_id');
                })
                ->whereNull('user_saved_history.deleted_at')
                ->where('shops.status_id', Status::ACTIVE)
                ->orderBy('user_saved_history.created_at', 'DESC')
                ->groupBy('user_saved_history.entity_id')
                ->select('shop_posts.*', 'user_saved_history.id as saved_id', 'user_saved_history.created_at as saved_created')
                ->selectRaw("{$distance} AS distance")
                ->selectRaw("{$limitByPackage} AS priority")
                ->whereRaw("{$distance} <= {$limitByPackage}")
                ->where('user_saved_history.is_like', 1);

            if ($shopPost->count() > 0) {
                $shopPost = $shopPost->paginate(config('constant.post_pagination_count'), "*", "shop_page");
                $data['recent_like_post'] = $shopPost;
                $data['recent_like_post_type'] = 'nearby';
            } else {

                $shopPost = ShopPost::join('shops', 'shop_posts.shop_id', 'shops.id')
                    ->leftjoin('addresses', function ($join) {
                        $join->on('shops.id', '=', 'addresses.entity_id')
                            ->where('addresses.entity_type_id', EntityTypes::SHOP);
                    })->leftjoin('user_saved_history', function ($join) use ($user) {
                        $join->on('shop_posts.id', '=', 'user_saved_history.entity_id')->where('user_saved_history.saved_history_type_id', SavedHistoryTypes::SHOP)->whereRaw('user_saved_history.id IN (select MAX(a2.id) from user_saved_history as a2 join shop_posts as u2 on u2.id = a2.entity_id group by u2.id ORDER BY MAX(a2.id) DESC)');
                    })
                    ->leftjoin('users_detail', function ($join) {
                        $join->on('shops.user_id', '=', 'users_detail.user_id');
                    })
                    ->where('addresses.main_country', $main_country)
                    ->where('shops.status_id', Status::ACTIVE)
                    ->whereNull('user_saved_history.deleted_at')
                    ->orderBy('user_saved_history.created_at', 'desc')
                    ->groupBy('shop_posts.id')
                    ->select('shop_posts.*')
                    ->selectRaw("{$limitByPackage} AS priority");
                $shopPost = $shopPost->paginate(config('constant.post_pagination_count'), "*", "shop_page");

                $data['recent_like_post'] = $shopPost;
                $data['recent_like_post_type'] = 'country';
            }

            DB::commit();
            Log::info('End code for the get home data');
            return $this->sendSuccessResponse(Lang::get('messages.home.success'), 200, $data);
        } catch (\Exception $e) {

            Log::info('Exception in get home data');
            Log::info($e);
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function LoadAllPortfolio(Request $request)
    {

        $inputs = $request->all();
        $data = [];
        $user = Auth::user();
        $validation = $this->shopProfileValidator->validateGetShop($inputs);

        if ($validation->fails()) {
            Log::info('End code for get all shops');
            return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
        }

        $language_id = $inputs['language_id'] ?? 4;
        $per_page = $inputs['per_page'] ?? 21;
        $latitude = $inputs['latitude'] ?? '';
        $longitude = $inputs['longitude'] ?? '';
        $menu_key = $inputs['menu_key'] ?? 'all';
        $main_country = $inputs['country'] ?? 'KR'; //getCountryFromLatLong($inputs['latitude'], $inputs['longitude']);

        if(!empty($user)){
            $user_id = $user->id;
            $user_type = UserHiddenCategory::LOGIN;

            if($user_id == 781 || $user_id == 5){
                Log::info('******************');
                Log::info($user_id);
                Log::info($inputs);
                Log::info('******************');
            }
        }else{
            $user_id = $inputs['user_id'] ?? null;
            $user_type = UserHiddenCategory::NONLOGIN;
        }

        $coordinate = $longitude . ',' . $latitude;

        $distance = "(6371 * acos(cos(radians(" . $inputs['latitude'] . "))
                            * cos(radians(addresses.latitude))
                        * cos(radians(addresses.longitude)
                    - radians(" . $inputs['longitude'] . "))
                    + sin(radians(" . $inputs['latitude'] . "))
                        * sin(radians(addresses.latitude))))";

        try {
            $recentFollowData = $this->shopRecentUpdatedPost($main_country, $distance, $language_id, true, $per_page, $coordinate,$user_id,$user_type,$menu_key);

            $data['recent_following_shop_portfolio'] = $recentFollowData['results'];
            $data['recent_following_type'] = $recentFollowData['type'];

            return $this->sendSuccessResponse(Lang::get('messages.home.success'), 200, $data);
        } catch (\Exception $e) {

            Log::info('Exception in get home data');
            Log::info($e);
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function getRecentLikedPost(Request $request)
    {
        try {
            Log::info('Start code for get home data');
            DB::beginTransaction();
            $inputs = $request->all();
            $data = [];
            //$user = Auth::user();
            $validation = $this->shopProfileValidator->validateGetShop($inputs);

            if ($validation->fails()) {
                Log::info('End code for get all shops');
                return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
            }

            $user = '';
            $token = $request->header('Authorization');
            $checktoken = str_replace('Bearer', '', $token);
            if (!empty($token) && !empty($checktoken) && !str_contains($checktoken, 'null')) {
                $user = JWTAuth::setToken(trim($checktoken))->toUser();
            }
            if(!empty($user)){
                $user_id = $user->id;
                $user_type = UserHiddenCategory::LOGIN;
            }else{
                $user_id = $inputs['user_id'] ?? null;
                $user_type = UserHiddenCategory::NONLOGIN;
            }
            $hiddenCategory = [];
            if(!empty($user_id)){
                $hiddenCategory = UserHiddenCategory::where('user_id',$user_id)->where('user_type',$user_type)->pluck('category_id')->toArray();
            }

            DB::enableQueryLog();

            $main_country = getCountryFromLatLong($inputs['latitude'], $inputs['longitude']);
            $timezone = get_nearest_timezone($inputs['latitude'], $inputs['longitude'], $main_country);

            $menu_key = $inputs['menu_key'] ?? 'all';
            $language_id = $inputs['language_id'] ?? 4;
            $latitude = $inputs['latitude'] ?? '';
            $longitude = $inputs['longitude'] ?? '';
            $coordinate = $longitude . ',' . $latitude;

            $per_page = $inputs['per_page'] ?? config('constant.post_pagination_count');
            $distance = "(6371 * acos(cos(radians(" . $inputs['latitude'] . "))
                            * cos(radians(addresses.latitude))
                        * cos(radians(addresses.longitude)
        - radians(" . $inputs['longitude'] . "))
        + sin(radians(" . $inputs['latitude'] . "))
                        * sin(radians(addresses.latitude))))";

            $categoryData = DB::table('category_settings')
                ->join('category','category.id','category_settings.category_id')
                ->whereNull('category.deleted_at')
                ->where('category_settings.is_show',1)
                ->where('category_settings.country_code',$main_country);
            /* if($menu_key != 'all'){
                $categoryData = $categoryData->where('category_settings.menu_key',$menu_key);
            } */
            $categoryData = $categoryData->select('category_settings.category_id as id','category_settings.menu_key')->get();

            if(empty($categoryData)){
                $categoryData = DB::table('category')
                ->whereNull('category.deleted_at')
                ->where('category.is_show',1)
                ->where('category.category_type_id',1);
                /* if($menu_key != 'all'){
                    $categoryData = $categoryData->where('category.menu_key',$menu_key);
                } */
                $categoryData = $categoryData->select('category.id','category.menu_key')->get();
            }

            if($menu_key != 'all'){
                $categoryData = $categoryData->where('menu_key',$menu_key)->pluck('id');
            }else{
                $categoryData = $categoryData->pluck('id');
            }

            $creditPlans = CreditPlans::where('entity_type_id', EntityTypes::SHOP)->get();
            $bronzePlanKm = $silverPlanKm = $goldPlanKm = $platiniumPlanKm = 0;
            foreach ($creditPlans as $plan) {
                if ($plan->package_plan_id == PackagePlan::BRONZE) {
                    $bronzePlanKm = $plan->km;
                } else if ($plan->package_plan_id == PackagePlan::SILVER) {
                    $silverPlanKm = $plan->km;
                } else if ($plan->package_plan_id == PackagePlan::GOLD) {
                    $goldPlanKm = $plan->km;
                } else if ($plan->package_plan_id == PackagePlan::PLATINIUM) {
                    $platiniumPlanKm = $plan->km;
                }
            }

            $limitByPackage = DB::raw('case when `shops`.`expose_distance` IS NOT NULL then `shops`.`expose_distance`
            when `users_detail`.package_plan_id = ' . PackagePlan::BRONZE . ' then ' . $bronzePlanKm . '
            when `users_detail`.package_plan_id = ' . PackagePlan::SILVER . ' then ' . $silverPlanKm . '
            when `users_detail`.package_plan_id = ' . PackagePlan::GOLD . ' then ' . $goldPlanKm . '
            when `users_detail`.package_plan_id = ' . PackagePlan::PLATINIUM . ' then ' . $platiniumPlanKm . '
            else 40 end ');

            $recentlyLikedShopPost = ShopPost::join('shops', 'shop_posts.shop_id', 'shops.id')
                ->leftjoin('addresses', function ($join) {
                    $join->on('shops.id', '=', 'addresses.entity_id')
                        ->where('addresses.entity_type_id', EntityTypes::SHOP);
                })->leftjoin('user_saved_history', function ($join) use ($user) {
                    $join->on('shop_posts.id', '=', 'user_saved_history.entity_id')->where('user_saved_history.saved_history_type_id', SavedHistoryTypes::SHOP);
                    //->whereRaw('user_saved_history.id IN (select MAX(a2.id) from user_saved_history as a2 join shop_posts as u2 on u2.id = a2.entity_id group by u2.id ORDER BY MAX(a2.id) DESC)');
                })
                ->leftjoin('users_detail', function ($join) {
                    $join->on('shops.user_id', '=', 'users_detail.user_id');
                })
                ->join('category', function ($join) {
                    $join->on('shops.category_id', '=', 'category.id')
                        ->whereNull('category.deleted_at');
                })
                ->whereIn('category.id',$categoryData)
                ->where(function ($query) use ($hiddenCategory) {
                    if (!empty($hiddenCategory)) {
                        $query->whereNotIn("category.id",$hiddenCategory);
                    }
                })
                ->where(function ($query) use ($user) {
                    if ($user) {
                        $query->whereRaw("shops.id NOT IN (select shop_id from shop_block_histories where user_id = {$user->id} AND is_block = 1)");
                    }
                })
                ->whereRaw('shop_posts.id in (SELECT (select (sp.id) as id from shop_posts as sp JOIN user_saved_history on user_saved_history.entity_id = sp.id where user_saved_history.saved_history_type_id = 1 AND user_saved_history.is_like = 1 AND sp.deleted_at is NULL AND sp.shop_id = shops.id ORDER BY user_saved_history.updated_at DESC LIMIT 1 ) as id FROM shops HAVING id is not null)')
                ->whereNull('user_saved_history.deleted_at')
                ->orderBy('user_saved_history.created_at', 'DESC')
                ->groupBy('user_saved_history.entity_id')
                ->select(
                    'shop_posts.*',
                    'user_saved_history.id as saved_id',
                    'user_saved_history.created_at as saved_created',
                    DB::raw("IFNULL( CAST(ROUND(st_distance_sphere(POINT(addresses.longitude,addresses.latitude), POINT( $coordinate)) * .001,2) AS DECIMAL(21,2)) , '') as shop_distance")
                )
                ->where('shops.status_id', Status::ACTIVE)
                ->selectRaw("{$distance} AS distance")
                ->selectRaw("{$limitByPackage} AS priority")
                ->whereRaw("{$distance} <= {$limitByPackage}")
                ->where('user_saved_history.is_like', 1);

            if ($recentlyLikedShopPost->count() > 0) {
                $recentlyLikedShopPost = $recentlyLikedShopPost->paginate($per_page, "*", "shop_page");
            } else {
                $recentlyLikedShopPost = ShopPost::join('shops', 'shop_posts.shop_id', 'shops.id')
                    ->leftjoin('addresses', function ($join) {
                        $join->on('shops.id', '=', 'addresses.entity_id')
                            ->where('addresses.entity_type_id', EntityTypes::SHOP);
                    })->leftjoin('user_saved_history', function ($join) use ($user) {
                        $join->on('shop_posts.id', '=', 'user_saved_history.entity_id')->where('user_saved_history.saved_history_type_id', SavedHistoryTypes::SHOP);
                        //->whereRaw('user_saved_history.id IN (select MAX(a2.id) from user_saved_history as a2 join shop_posts as u2 on u2.id = a2.entity_id group by u2.id ORDER BY MAX(a2.id) DESC)');
                    })
                    ->leftjoin('users_detail', function ($join) {
                        $join->on('shops.user_id', '=', 'users_detail.user_id');
                    })
                    ->join('category', function ($join) {
                        $join->on('shops.category_id', '=', 'category.id')
                            ->whereNull('category.deleted_at');
                    })
                    ->whereIn('category.id',$categoryData)
                    ->where(function ($query) use ($hiddenCategory) {
                        if (!empty($hiddenCategory)) {
                            $query->whereNotIn("category.id",$hiddenCategory);
                        }
                    })
                    ->where(function ($query) use ($user) {
                        if ($user) {
                            $query->whereRaw("shops.id NOT IN (select shop_id from shop_block_histories where user_id = {$user->id} AND is_block = 1)");
                        }
                    })
                    ->whereRaw('shop_posts.id in (SELECT (select (sp.id) as id from shop_posts as sp JOIN user_saved_history on user_saved_history.entity_id = sp.id where user_saved_history.saved_history_type_id = 1 AND user_saved_history.is_like = 1 AND sp.deleted_at is NULL AND sp.shop_id = shops.id ORDER BY user_saved_history.updated_at DESC LIMIT 1 ) as id FROM shops HAVING id is not null)')
                    ->whereNull('user_saved_history.deleted_at')
                    ->where('shops.status_id', Status::ACTIVE)
                    ->orderBy('user_saved_history.created_at', 'DESC')
                    ->groupBy('user_saved_history.entity_id')
                    ->select(
                        'shop_posts.*',
                        'user_saved_history.id as saved_id',
                        'user_saved_history.created_at as saved_created',
                        DB::raw("IFNULL( CAST(ROUND(st_distance_sphere(POINT(addresses.longitude,addresses.latitude), POINT( $coordinate)) * .001,2) AS DECIMAL(21,2)) , '') as shop_distance")
                    )
                    ->selectRaw("{$distance} AS distance")
                    ->selectRaw("{$limitByPackage} AS priority")
                    ->paginate($per_page, "*", "shop_page");
            }

            DB::commit();
            Log::info('End code for the get home data');
            return $this->sendSuccessResponse(Lang::get('messages.home.success'), 200, $recentlyLikedShopPost);
        } catch (\Exception $e) {
            Log::info('Exception in get home data');
            Log::info($e);
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function getUserBookingCompletedList()
    {
        try {
            Log::info('Start code for get booked and completed user data');
            DB::beginTransaction();
            $user = Auth::user();
            if ($user) {
                $upcomingBookings = RequestedCustomer::where('user_id', $user->id)
                    ->where('request_booking_status_id', [RequestBookingStatus::BOOK])
                    // ->where('show_in_home',1)
                    ->orderBy('id', 'desc')
                    ->paginate(config('constant.pagination_count'), "*", "booking_page");
                foreach ($upcomingBookings as $booking) {
                    $location = Address::where('entity_type_id', $booking['entity_type_id'])
                        ->where('entity_id', $booking['entity_id'])->first();

                    $booking['location'] = $location;
                    $date = strtotime($booking->booking_date);
                    $seconds = $date - time();
                    $days = floor($seconds / 86400);
                    $seconds %= 86400;
                    $hours = floor($seconds / 3600);
                    $seconds %= 3600;
                    $minutes = floor($seconds / 60);
                    $seconds %= 60;

                    $booking['time_left'] = [
                        'days' => $days,
                        'hours' => $hours,
                        'minutes' => $minutes,
                        'seconds' => $seconds,
                    ];
                    $upcoming_and_completed_booking[] = $booking;
                }
                $data['booking'] = $upcomingBookings;

                $completedBookings = RequestedCustomer::where('user_id', $user->id)
                    ->where('request_booking_status_id', [RequestBookingStatus::COMPLETE])
                    // ->where('show_in_home',1)
                    ->orderBy('id', 'desc')
                    ->paginate(config('constant.pagination_count'), "*", "completed_page")->toArray();

                foreach ($completedBookings['data'] as $key => $booking) {
                    if ($booking['review_done'] != 0) {
                        unset($completedBookings['data'][$key]);
                    }
                }
                $completedBookings['data'] = array_values($completedBookings['data']);
                $data['completed'] = $completedBookings;
                DB::commit();
                Log::info('End code for the get booked and completed user data');
                return $this->sendSuccessResponse(Lang::get('messages.home.success'), 200, $data);
            } else {
                Log::info('End code for the get booked and completed user data');
                return $this->sendSuccessResponse(Lang::get('messages.user.token_expired'), 401);
            }
        } catch (\Exception $e) {
            Log::info('Exception in get booked and completed user data');
            Log::info($e);
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function followingShops(Request $request)
    {
        $inputs = $request->all();

        try {
            Log::info('Start code for get user following shops');
            DB::beginTransaction();
            $data = [];
            $user = Auth::user();

            $validation = $this->shopProfileValidator->validateGetShop($inputs);

            if ($validation->fails()) {
                Log::info('End code for get all shops');
                return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
            }

            $followingShops = $recentFollowPortfolio = ["data" => []];

            $main_country = getCountryFromLatLong($inputs['latitude'], $inputs['longitude']);
            $timezone = get_nearest_timezone($inputs['latitude'], $inputs['longitude'], $main_country);
            $category_id = isset($inputs['category_id']) ? $inputs['category_id'] : 0;
            $category = Category::find($category_id);

            $is_suggest_category = $category && $category->category_type_id == CategoryTypes::CUSTOM ? 1 : 0;
            $language_id = $inputs['language_id'] ?? 4;
            $following_per_page = $inputs['following_per_page'] ?? 9;
            $recent_portfolio_per_page = $inputs['recent_portfolio_per_page'] ?? 9;
            $latitude = $inputs['latitude'] ?? '';
            $longitude = $inputs['longitude'] ?? '';

            $coordinate = $longitude . ',' . $latitude;

            $distance = "(6371 * acos(cos(radians(" . $inputs['latitude'] . "))
                            * cos(radians(addresses.latitude))
                        * cos(radians(addresses.longitude)
                    - radians(" . $inputs['longitude'] . "))
                    + sin(radians(" . $inputs['latitude'] . "))
                        * sin(radians(addresses.latitude))))";

            if ($user) {

                $followingShopsCount = DB::table('shops')->join('shop_followers', function ($join) use ($user) {
                    $join->on('shops.id', '=', 'shop_followers.shop_id')
                        ->where('shop_followers.user_id', $user->id);
                })
                    ->join('category', function ($join) {
                        $join->on('shops.category_id', '=', 'category.id')
                            ->whereNull('category.deleted_at');
                    })
                    ->where(function ($query) use ($user) {
                        if ($user) {
                            $query->whereRaw("shops.id NOT IN (select shop_id from shop_block_histories where user_id = {$user->id} AND is_block = 1)");
                        }
                    })
                    ->where('shops.status_id', Status::ACTIVE)
                    ->groupBy('shops.id')
                    ->whereNull('shops.deleted_at')
                    ->get('shops.id')->count();

                if ($request->has('type') && $request->type == 1) {
                    $followingShopsQuery = DB::table('shops')->leftjoin('addresses', function ($join) {
                        $join->on('shops.id', '=', 'addresses.entity_id')
                            ->where('addresses.entity_type_id', EntityTypes::SHOP);
                    })
                        ->join('category', function ($join) {
                            $join->on('shops.category_id', '=', 'category.id')
                                ->whereNull('category.deleted_at');
                        })
                        ->leftjoin('cities', function ($join) {
                            $join->on('addresses.city_id', '=', 'cities.id');
                        })
                        ->join('shop_followers', function ($join) use ($user, $followingShopsCount) {
                            $join->on('shops.id', '=', 'shop_followers.shop_id');
                            if ($followingShopsCount > 0) {
                                $join->where('shop_followers.user_id', $user->id);
                            }
                        })
                        ->where(function ($query) use ($user) {
                            if ($user) {
                                $query->whereRaw("shops.id NOT IN (select shop_id from shop_block_histories where user_id = {$user->id} AND is_block = 1)");
                            }
                        })
                        ->whereNull('shops.deleted_at');

                    if ($followingShopsCount > 0) {
                        $followingShopsQuery = $followingShopsQuery->where('shop_followers.user_id', $user->id);
                    }

                    $followingShops = $followingShopsQuery->groupBy('shops.id')
                        ->select('shops.*', 'cities.name as city_name', DB::raw("IFNULL( CAST(ROUND(st_distance_sphere(POINT(addresses.longitude,addresses.latitude), POINT( $coordinate)) * .001,2) AS DECIMAL(21,2)), '') as shop_distance"))
                        ->paginate($recent_portfolio_per_page, "*", "recent_portfolio_page");

                    collect($followingShops->items())->map(function ($item) use ($user) {

                        $followers = ShopFollowers::where('shop_id', $item->id)->where('user_id', $user->id)->count();
                        $item->is_follow = $followers > 0 ? 1 : 0;

                        $item->is_discount = (bool)$item->is_discount;
                        if (property_exists($item, 'city_name')) {
                            $item->address = ["city_name" => $item->city_name];
                        }

                        $worplace_images = ShopImages::where('shop_id', $item->id)->where('shop_image_type', ShopImagesTypes::WORKPLACE)->get(['id', 'image']);
                        $images = [];
                        if (empty($worplace_images)) {
                            $item->workplace_images = $images;
                        } else {
                            $item->workplace_images = $worplace_images;
                        }
                        return $item;
                    });
                } else {

                    if ($followingShopsCount > 0) {
                        $recentFollowPortfolio = ShopPost::join('shops', 'shop_posts.shop_id', 'shops.id')
                            ->join('shop_followers', function ($join) use ($user, $followingShopsCount) {
                                $join->on('shops.id', '=', 'shop_followers.shop_id');
                                $join->where('shop_followers.user_id', $user->id);
                            })
                            ->join('category', function ($join) {
                                $join->on('shops.category_id', '=', 'category.id')
                                    ->whereNull('category.deleted_at');
                            })
                            ->leftjoin('addresses', function ($join) {
                                $join->on('shops.id', '=', 'addresses.entity_id')
                                    ->where('addresses.entity_type_id', EntityTypes::SHOP);
                            })
                            ->where(function ($query) use ($user) {
                                if ($user) {
                                    $query->whereRaw("shops.id NOT IN (select shop_id from shop_block_histories where user_id = {$user->id} AND is_block = 1)");
                                }
                            })
                            ->where('shops.status_id', Status::ACTIVE)
                            ->orderBy('shop_posts.post_order_date', 'desc')
                            ->orderBy('shop_posts.created_at', 'desc')
                            ->groupBy('shop_posts.id')
                            ->select('shop_posts.*', DB::raw("IFNULL( CAST(ROUND(st_distance_sphere(POINT(addresses.longitude,addresses.latitude), POINT( $coordinate)) * .001,2) AS DECIMAL(21,2)), '') as shop_distance"))
                            ->paginate($following_per_page, "*", "recent_portfolio_page");
                    } else {
                        $shopCon = new \App\Http\Controllers\Api\ShopController;
                        $recentFollowPortfolio = $shopCon->shopRecentUpdatedPost($main_country, $category_id, $distance, $is_suggest_category, $language_id, $coordinate, $recent_portfolio_per_page);
                    }
                }
                $data['following_shops'] = $followingShops;
                $data['recent_following_shop_portfolio'] = $recentFollowPortfolio;

                DB::commit();
                Log::info('End code for the get user following shops');
                return $this->sendSuccessResponse(Lang::get('messages.home.success'), 200, $data);
            } else {

                //$recentFollowPortfolio = $this->shopRecentUpdatedPost($main_country,$distance,$language_id);
                $shopCon = new \App\Http\Controllers\Api\ShopController;
                $recentFollowPortfolio = $shopCon->shopRecentUpdatedPost($main_country, $category_id, $distance, $is_suggest_category, $language_id, $coordinate);

                $data['following_shops'] = $followingShops;
                $data['recent_following_shop_portfolio'] = $recentFollowPortfolio;

                return $this->sendSuccessResponse(Lang::get('messages.home.success'), 200, $data);
            }
        } catch (\Exception $e) {
            Log::info('Exception in get user following shops');
            Log::info($e);
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function getMultipleConfig(Request $request)
    {
        try {
            $inputs = $request->all();
            $data = [];
            $validator = Validator::make($request->all(), [
                'key' => 'required',
            ], [], [
                'key' => 'Key',
            ]);
            if ($validator->fails()) {
                return $this->sendCustomErrorMessage($validator->errors()->toArray(), 422);
            }

            $responseArray = [];
            $language_id = $inputs['language_id'] ?? 4;
            $country_code = $inputs['country_code'] ?? '';
            $configData = DB::table('config')->whereNull('deleted_at')->whereIn('key', $inputs['key'])->get();

            foreach ($configData as $key => $config) {
                if ($config->is_different_lang == true) {
                    $languageValue = ConfigLanguages::where('config_id', $config->id)->where('language_id', $language_id)->first();
                    $config->value = (!empty($languageValue)) ? $languageValue->value : $config->value;
                }

                if ($config->key == Config::ONLY_SHOP_MODE && !empty($country_code)) {
                    $configCountryData = ConfigCountryDetail::where('config_id', $config->id)->where('country_code', $country_code)->first();
                    $responseArray[$config->key] = $configCountryData ? $configCountryData->value : $config->value;
                } else {
                    $responseArray[$config->key] = $config->value;
                }
            }
            return $this->sendSuccessResponse(Lang::get('messages.home.success'), 200, $responseArray);
        } catch (\Exception $e) {
            Log::info($e);
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function getConfigLink(Request $request)
    {
        try {
            Log::info('Start code for get config link');
            $inputs = $request->all();
            $data = [];
            $validator = Validator::make($request->all(), [
                'key' => 'required',
            ], [], [
                'key' => 'Key',
            ]);
            if ($validator->fails()) {
                Log::info('End code for the get config link');
                return $this->sendCustomErrorMessage($validator->errors()->toArray(), 422);
            }

            $language_id = $inputs['language_id'] ?? 4;
            $config_data = Config::where('key', $inputs['key'])->first();
            if (!empty($config_data) && $config_data->is_different_lang == true) {
                $languageValue = ConfigLanguages::where('config_id', $config_data->id)->where('language_id', $language_id)->first();
                $config_data->value = (!empty($languageValue)) ? $languageValue->value : $config_data->value;
            }
            $data['config_data'] = $config_data ? $config_data : [];

            Log::info('End code for the get config link');
            return $this->sendSuccessResponse(Lang::get('messages.home.success'), 200, $data);
        } catch (\Exception $e) {
            Log::info('Exception in get config link');
            Log::info($e);
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function shopRecentUpdatedPost($main_country, $distance, $language_id, $ispaginate = false, $per_page = 21, $coordinate,$user_id,$user_type,$menu_key = 'all')
    {
        $user = Auth::user();
        $recentPostUpdated = [];

        $categoryData = DB::table('category_settings')
            ->join('category','category.id','category_settings.category_id')
            ->whereNull('category.deleted_at')
            ->where('category_settings.is_show',1)
            ->where('category_settings.country_code',$main_country);

            /* if($menu_key != 'all'){
                $categoryData = $categoryData->where('category_settings.menu_key',$menu_key);
            } */
            $categoryData = $categoryData->select('category_settings.category_id as id','category_settings.menu_key')->get();

        if(count($categoryData) == 0){
            $categoryData = DB::table('category')
            ->whereNull('category.deleted_at')
            ->where('category.is_show',1)
            ->where('category.category_type_id',1);

           /*  if($menu_key != 'all'){
                $categoryData = $categoryData->where('category.menu_key',$menu_key);
            } */
            $categoryData = $categoryData->select('category.id','category.menu_key')->get();
        }

        if($menu_key != 'all'){
            $categoryData = $categoryData->where('menu_key',$menu_key)->pluck('id');
        }else{
            $categoryData = $categoryData->pluck('id');
        }

        $limitByPackage = getLimitPackageByQuery(EntityTypes::SHOP);

        $hiddenCategory = [];
        if(!empty($user_id)){
            $hiddenCategory = UserHiddenCategory::where('user_id',$user_id)->where('user_type',$user_type)->pluck('category_id')->toArray();
        }

        $recentPortfolioQuery = ShopPost::join('shops', 'shop_posts.shop_id', 'shops.id')
            ->leftjoin('addresses', function ($join) {
                $join->on('shops.id', '=', 'addresses.entity_id')
                    ->where('addresses.entity_type_id', EntityTypes::SHOP);
            })
            ->leftjoin('shop_detail_languages', function ($join) use ($language_id) {
                $join->on('shops.id', '=', 'shop_detail_languages.shop_id')
                    ->where('shop_detail_languages.key', ShopDetailLanguage::SPECIALITY_OF)
                    ->where('shop_detail_languages.entity_type_id', EntityTypes::SHOP)
                    ->where('shop_detail_languages.language_id', $language_id);
            })
            ->leftjoin('users_detail', function ($join) {
                $join->on('shops.user_id', '=', 'users_detail.user_id');
            })
            ->join('category', function ($join) {
                $join->on('shops.category_id', '=', 'category.id')
                    ->whereNull('category.deleted_at');
            })
            ->whereIn('category.id',$categoryData)
            ->where(function ($query) use ($hiddenCategory) {
                if (!empty($hiddenCategory) && count($hiddenCategory)>0) {
                    $query->whereNotIn("category.id",$hiddenCategory);
                }
            })
            ->where(function ($query) use ($user) {
                if ($user) {
                    $query->whereRaw("shops.id NOT IN (select shop_id from shop_block_histories where user_id = {$user->id} AND is_block = 1)");
                }
            })
            ->whereRaw('shop_posts.id in (SELECT (select (sp.id) as id from shop_posts as sp where sp.deleted_at is NULL AND sp.shop_id = shops.id ORDER BY sp.post_order_date DESC, sp.created_at DESC LIMIT 1 ) as id FROM shops HAVING id is not null)')
            //->whereRaw('shop_posts.id in (select max(sp.id) from shop_posts as sp where sp.deleted_at is NULL  group by (sp.shop_id))')
            ->where('addresses.main_country', $main_country)
            ->where('shops.status_id', Status::ACTIVE)
            ->whereNull('shop_posts.deleted_at')
            ->whereNull('shops.deleted_at');

        $recentPortfolioQuery = $recentPortfolioQuery->where('category.category_type_id', CategoryTypes::SHOP);

        $recentPortfolioQuery = $recentPortfolioQuery->orderBy('shop_posts.post_order_date', 'desc')
            ->orderBy('shop_posts.created_at', 'desc')
            ->orderby('distance')
            ->select(
                'shop_posts.*',
                'shops.id as shop_id',
                DB::raw('IFNULL(shop_detail_languages.value, shops.speciality_of) as speciality_of'),
                DB::raw("IFNULL(CAST(ROUND(st_distance_sphere(POINT(addresses.longitude,addresses.latitude), POINT( $coordinate)) * .001,2) AS DECIMAL(21,2)) ,'') as shop_distance")
            )
            ->selectRaw("{$distance} AS distance")
            ->selectRaw("{$limitByPackage} AS priority")
            ->whereRaw("{$distance} <= {$limitByPackage}");

        if ($recentPortfolioQuery->count() > 0) {
            //return $this->filterQueryResults($recentPortfolioQuery , $ispaginate);
            return ['results' => $this->filterQueryResults($recentPortfolioQuery, $ispaginate, $per_page), 'type' => 'nearby'];
        } else {
            $newRecentPortfolioQuery = ShopPost::join('shops', 'shop_posts.shop_id', 'shops.id')
                ->leftjoin('addresses', function ($join) {
                    $join->on('shops.id', '=', 'addresses.entity_id')
                        ->where('addresses.entity_type_id', EntityTypes::SHOP);
                })
                ->leftjoin('shop_detail_languages', function ($join) use ($language_id) {
                    $join->on('shops.id', '=', 'shop_detail_languages.shop_id')
                        ->where('shop_detail_languages.key', ShopDetailLanguage::SPECIALITY_OF)
                        ->where('shop_detail_languages.entity_type_id', EntityTypes::SHOP)
                        ->where('shop_detail_languages.language_id', $language_id);
                })
                ->leftjoin('users_detail', function ($join) {
                    $join->on('shops.user_id', '=', 'users_detail.user_id');
                })
                ->join('category', function ($join) {
                    $join->on('shops.category_id', '=', 'category.id')
                        ->whereNull('category.deleted_at');
                })
                ->whereIn('category.id',$categoryData)
                ->where(function ($query) use ($hiddenCategory) {
                    if (!empty($hiddenCategory) && count($hiddenCategory)>0) {
                        $query->whereNotIn("category.id",$hiddenCategory);
                    }
                })
                ->where(function ($query) use ($user) {
                    if ($user) {
                        $query->whereRaw("shops.id NOT IN (select shop_id from shop_block_histories where user_id = {$user->id} AND is_block = 1)");
                    }
                })
                //->whereRaw('shop_posts.id in (select max(sp.id) from shop_posts as sp where sp.deleted_at is NULL  group by (sp.shop_id))')
                ->whereRaw('shop_posts.id in (SELECT (select (sp.id) as id from shop_posts as sp where sp.deleted_at is NULL AND sp.shop_id = shops.id ORDER BY sp.post_order_date DESC, sp.created_at DESC LIMIT 1 ) as id FROM shops HAVING id is not null)')
                ->where('addresses.main_country', $main_country)
                ->where('shops.status_id', Status::ACTIVE)
                ->whereNull('shop_posts.deleted_at')
                ->whereNull('shops.deleted_at')
                ->where('category.category_type_id', CategoryTypes::SHOP)
                ->orderBy('shop_posts.post_order_date', 'desc')
                ->orderBy('shop_posts.created_at', 'desc')
                ->orderby('distance')
                ->select(
                    'shop_posts.*',
                    'shops.id as shop_id',
                    DB::raw('IFNULL(shop_detail_languages.value, shops.speciality_of) as speciality_of'),
                    DB::raw("IFNULL(CAST(ROUND(st_distance_sphere(POINT(addresses.longitude,addresses.latitude), POINT( $coordinate)) * .001,2) AS DECIMAL(21,2)) ,'') as shop_distance")
                )
                ->selectRaw("{$distance} AS distance")
                ->selectRaw("{$limitByPackage} AS priority");

            return ['results' => $this->filterQueryResults($newRecentPortfolioQuery, $ispaginate, $per_page), 'type' => 'country'];
        }
    }

    public function filterQueryResults($query, $ispaginate, $per_page)
    {
        if ($ispaginate == true) {
            $recentPortfolio = $query->paginate($per_page, "*", "recent_portfolio_page");
            $shopCon = new \App\Http\Controllers\Api\ShopListController;
            $recentPostUpdated = $shopCon->shopDistanceFilter($recentPortfolio, 1);
        } else {
            $recentPortfolio = $query->limit(12)->get();
            $recentPostUpdated = $this->shopDistanceFilter($recentPortfolio, 1);
        }



        return $recentPostUpdated;
    }

    public static function shopDistanceFilter($shops, $is_post = 0)
    {
        $filteredShop = [];
        $paginateData = $shops->toArray();
        $user = Auth::user();
        foreach ($paginateData as $key => $shop) {
            //print_r($shop);
            $shop = (array)$shop;

            $defaultItem = [];

            /* if(isset($shop['video_thumbnail']) && !empty($shop['video_thumbnail'])){
                $shop['video_thumbnail'] = (!str_contains($shop['video_thumbnail'], 'amazonaws')) ? Storage::disk('s3')->url($shop['video_thumbnail']) : $shop['video_thumbnail'];
                $defaultItem[0]['video_thumbnail'] = (!str_contains($shop['video_thumbnail'], 'amazonaws')) ? Storage::disk('s3')->url($shop['video_thumbnail']) : $shop['video_thumbnail'];
            }else{
                $defaultItem[0]['video_thumbnail'] = '';
            }

            $defaultItem[0]['id'] = $shop['id'];
            $defaultItem[0]['type'] = $shop['type'];

            $defaultItem[0]['post_item'] = (!empty($shop['post_item']) && !str_contains($shop['post_item'], 'amazonaws')) ? Storage::disk('s3')->url($shop['post_item']) : $shop['post_item'];

            if($shop['is_multiple'] == true){
                $posts = MultipleShopPost::where('shop_posts_id',$shop['id'])->get();
                $shop['multiple_shop_posts'] = collect($defaultItem)->merge($posts)->values();
            }else{
                $shop['multiple_shop_posts'] = $defaultItem;
            } */

            /* $hash_tags = [];
            if(!empty($shop['id'])){
                $hash_tags = HashTag::join('hash_tag_mappings', function ($join) {
                    $join->on('hash_tag_mappings.hash_tag_id', '=', 'hash_tags.id')
                    ->where('hash_tag_mappings.entity_type_id', HashTag::SHOP_POST);
                })
                ->join('shop_posts', function ($join) {
                    $join->on('shop_posts.id', '=', 'hash_tag_mappings.entity_id');
                })
                ->where('hash_tag_mappings.entity_id',$shop['id'])
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

            $shop['hash_tags'] = $hash_tags; */

            if ($is_post == 1) {
                //$shopData = Shop::find($shop['shop_id']);
                $shopData = DB::table('shops')->whereId($shop['shop_id'])->first();
                /* $shop['business_link'] = $shopData->business_link ? $shopData->business_link : '';
                $shop['another_mobile'] = $shopData->another_mobile ? $shopData->another_mobile : '';
                $shop['shop_name'] = $shopData->shop_name ? $shopData->shop_name : '';
                $shop['main_name'] = $shopData->main_name ? $shopData->main_name : '';
                $shop['speciality_of'] = $shop['speciality_of'] ? $shop['speciality_of'] : '';

                $thumbnail = ShopImages::where('shop_id', $shopData->id)->where('shop_image_type',ShopImagesTypes::THUMB)->select(['id','image'])->first();
                $shop['shop_thumbnail'] = $thumbnail ? $thumbnail->image :  "";

                // Workplace images
                $worplace_images = ShopImages::where('shop_id', $shopData->id)->where('shop_image_type',ShopImagesTypes::WORKPLACE)->get(['id','image']);
                $images = [];
                if (empty($worplace_images)) {
                    $shop['workplace_images'] = $images;
                } else {
                    $shop['workplace_images'] = $worplace_images;
                }
                $shop['is_discount'] = ($shopData->is_discount && $shopData->is_discount == 1) ? true : false; */

                $user_id = $shopData->user_id;
            } else {
                $user_id = $shop['user_id'];
                // Workplace images
                /*  $worplace_images = ShopImages::where('shop_id', $shop['id'])->where('shop_image_type',ShopImagesTypes::WORKPLACE)->get(['id','image']);
                $images = [];
                if (empty($worplace_images)) {
                    $shop['workplace_images'] = $images;
                } else {
                    $shop['workplace_images'] = $worplace_images;
                } */
            }
            $shop['user_id'] = $user_id;

            /*  $followers = DB::table('shop_followers')->where('shop_id', $shop['id'])->where(function($q) use($user){
                if($user){
                    $q->where('user_id',$user->id);
                }
            })->count();
            $shop['is_follow'] = ($followers > 0 && $user) ? 1 : 0; */

            //$userdetail = UserDetail::where('user_id',$user_id)->first();
            // $planDetail = CreditPlans::where('entity_type_id',EntityTypes::SHOP)->where('package_plan_id',$userdetail->package_plan_id)->first();
            $rating = DB::table('reviews')->where('entity_type_id', EntityTypes::SHOP)->where('entity_id', $shop['id'])->avg('rating');

            // dd($planDetail->km);
            // $km = $planDetail ? $planDetail->km : 0;

            $shop['post_item'] = (!empty($shop['post_item']) && !str_contains($shop['post_item'], 'amazonaws')) ? Storage::disk('s3')->url($shop['post_item']) : $shop['post_item'];
            $shop['post_item'] = $shop['post_item'] ?? NULL;
            $shop['distance'] = number_format((float)$shop['distance'], 1, '.', '');

            // Get Address
            $emptyObject = new Address();
            $emptyObject->entity_type_id = EntityTypes::SHOP;
            $emptyObject->entity_id = $shop['id'];
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

            $address = Address::where('entity_type_id', EntityTypes::SHOP)->where('entity_id', $shop['shop_id'])->first();
            $shop['address'] = !empty($address) ? $address : $emptyObject;
            $shop['location'] = !empty($address) ? $address : $emptyObject;

            // Get Rating
            $shop['rating'] = $rating ? number_format($rating, 1) : "0";

            // Get Thumbnail Images
            $thumbnail = ShopImages::where('shop_id', $shop['id'])->where('shop_image_type', ShopImagesTypes::THUMB)->select(['id', 'image'])->first();
            $images = (object)[];
            if (empty($thumbnail)) {
                $shop['thumbnail_image'] = $images;
            } else {
                $shop['thumbnail_image'] = $thumbnail;
            }

            // Get main profile Images
            $main_profile_images = ShopImages::where('shop_id', $shop['id'])->where('shop_image_type', ShopImagesTypes::MAINPROFILE)->get(['id', 'image']);
            $images = [];
            if (empty($main_profile_images)) {
                $shop['main_profile_images'] = $images;
            } else {
                $shop['main_profile_images'] = $main_profile_images;
            }

            // portfolio image
            $portfolio_images = ShopPost::where('shop_id', $shop['id'])->orderBy('id', 'desc')->paginate(config('constant.post_pagination_count'), "*", "portfolio_images_page");

            $shop['portfolio_images'] = $portfolio_images;

            $savedHistoryq = DB::table('user_saved_history')->where('saved_history_type_id', SavedHistoryTypes::SHOP)->where('is_like', 1)->where('entity_id', $shop['id']);

            $count = $savedHistoryq->count();
            $shop['saved_count'] = $count;

            $savedCount = $savedHistoryq->where(function ($q) use ($user) {
                if ($user) {
                    $q->where('user_id', $user->id);
                }
            })->count();
            $shop['is_saved_in_history'] = ($savedCount > 0 && $user) ? true : false;

            $filteredShop[] = $shop;
        }
        $paginateData = array_values($filteredShop);
        return $paginateData;
    }
}
