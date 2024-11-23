<?php

namespace App\Http\Controllers\Api;

use App\Models\AdminMessage;
use App\Models\Shop;
use App\Models\UserDetail;
use Log;
use Exception;
use Carbon\Carbon;
use App\Models\Status;
use App\Models\Address;
use App\Models\ShopPost;
use App\Models\UserCards;
use App\Models\ShopImages;
use App\Models\EntityTypes;
use App\Models\PostLanguage;
use Illuminate\Http\Request;
use App\Models\CategoryTypes;
use App\Models\GifticonDetail;
use App\Models\ShopImagesTypes;
use App\Models\RequestedCustomer;
use App\Models\SavedHistoryTypes;
use Illuminate\Http\JsonResponse;
use App\Models\ShopDetailLanguage;
use App\Models\UserHiddenCategory;
use Illuminate\Support\Facades\DB;
use App\Models\LinkedSocialProfile;
use App\Http\Controllers\Controller;
use App\Models\RequestBookingStatus;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Lang;
use App\Validators\ShopProfileValidator;

class NewHomepageController extends Controller
{
    private $homeValidator;

    function __construct()
    {
        $this->homeValidator = new ShopProfileValidator();
    }

    public function index(Request $request): JsonResponse
    {
        $inputs = $request->all();

        try {
            $validation = $this->homeValidator->validateGetShop($inputs);

            if ($validation->fails()) {
                return $this->sendCustomErrorMessage($validation->errors()->toArray());
            }
            $data = [];
            $language_id = $inputs['language_id'] ?? PostLanguage::ENGLISH;
            $country = $inputs['country'] ?? 'KR'; //getCountryFromLatLong($inputs['latitude'], $inputs['longitude']);
            $distance = getDistanceQuery($inputs['latitude'], $inputs['longitude']);
            $limitByPackage = getLimitPackageByQuery(EntityTypes::SHOP);
            $latitude = $inputs['latitude'] ?? '';
            $longitude = $inputs['longitude'] ?? '';
            $per_page = $inputs['per_page'] ?? 6;
            $user_id = $inputs['user_id'] ?? null;
            $menu_key = $inputs['menu_key'] ?? 'all';

            $coordinate = $longitude . ',' . $latitude;

            $recentFollowData = $this->loadRecentPortfolio($language_id, $country, $distance, $limitByPackage, false, $coordinate, $per_page,$user_id,UserHiddenCategory::NONLOGIN,$menu_key);

            $likedData = $this->loadLikedPosts($limitByPackage, $distance, $country, $coordinate, $per_page,$user_id,UserHiddenCategory::NONLOGIN,$menu_key);
            // Load Banner
            $data['banner_images'] = loadBannerImages($country);

            $data['recent_following_shop_portfolio'] = $recentFollowData['results'];
            $data['recent_following_type'] = $recentFollowData['type'];

            $data['recent_like_post'] = $likedData['results'];
            $data['recent_like_post_type'] = $likedData['type'];

            $data['user_applied_card_detail'] = getDefaultCard();
            $data['is_new_gifticon'] = false;
            $data['is_valid_instagram_token'] = 1;

            $mbti = UserDetail::where('user_id',$user_id)->pluck('mbti')->first();
            $data['mbti'] = $mbti ?? null;

            return $this->sendSuccessResponse(Lang::get('messages.home.success'), 200, $data);
        } catch (Exception $e) {
            Log::info($e);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'));
        }
    }

    public function loadRecentPortfolio($language_id, $country, $distance, $limitByPackage, $paginate = false, $coordinate, $per_page,$user_id,$user_type = UserHiddenCategory::LOGIN, $menu_key = 'all')
    {
        $categoryData = DB::table('category_settings')
            ->join('category','category.id','category_settings.category_id')
            ->whereNull('category.deleted_at')
            ->where('category_settings.is_show',1)
            ->where('category_settings.country_code',$country);
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

        $hiddenCategory = [];
        if(!empty($user_id)){
            $hiddenCategory = UserHiddenCategory::where('user_id',$user_id)->where('user_type',$user_type)->pluck('category_id')->toArray();
        }

        $user = Auth::user();
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
                if (!empty($hiddenCategory)) {
                    $query->whereNotIn("category.id",$hiddenCategory);
                }
            });
            //->join('category', 'category.id', 'shops.category_id');

        if($user){
            $recentPortfolioQuery = $recentPortfolioQuery->whereRaw("shops.id NOT IN (select shop_id from shop_block_histories where user_id = {$user->id} AND is_block = 1)");
        }

        $recentPortfolioQuery = $recentPortfolioQuery
            //->whereRaw('shop_posts.id in (select max(sp.id) from shop_posts as sp where sp.deleted_at is NULL  group by (sp.shop_id))')
            ->whereRaw('shop_posts.id in (SELECT (select (sp.id) as id from shop_posts as sp where sp.deleted_at is NULL AND sp.shop_id = shops.id ORDER BY sp.post_order_date DESC, sp.created_at DESC LIMIT 1 ) as id FROM shops HAVING id is not null)')
            // ->where('addresses.main_country', $country)
            ->where('addresses.main_country', $country)
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
            ->selectRaw("{$limitByPackage} AS priority")
            ->whereRaw("{$distance} <= {$limitByPackage}");

        if ($recentPortfolioQuery->count() > 0) {
            return ['results' => $this->filterQueryResults($recentPortfolioQuery, $paginate, $per_page), 'type' => 'nearby'];
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
                    if (!empty($hiddenCategory)) {
                        $query->whereNotIn("category.id",$hiddenCategory);
                    }
                });
                //->join('category', 'category.id', 'shops.category_id');

                if($user){
                    $newRecentPortfolioQuery = $newRecentPortfolioQuery->whereRaw("shops.id NOT IN (select shop_id from shop_block_histories where user_id = {$user->id} AND is_block = 1)");
                }

                $newRecentPortfolioQuery = $newRecentPortfolioQuery
                // ->whereRaw('shop_posts.id in (select max(sp.id) from shop_posts as sp where sp.deleted_at is NULL  group by (sp.shop_id))')
                ->whereRaw('shop_posts.id in (SELECT (select (sp.id) as id from shop_posts as sp where sp.deleted_at is NULL AND sp.shop_id = shops.id ORDER BY sp.post_order_date DESC, sp.created_at DESC LIMIT 1 ) as id FROM shops HAVING id is not null)')
                ->where('addresses.main_country', $country)
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

            return ['results' => $this->filterQueryResults($newRecentPortfolioQuery, $paginate, $per_page), 'type' => 'country'];
        }
    }

    public function filterQueryResults($query, $paginate, $per_page): array
    {
        if ($paginate == true) {
            //$recentPortfolio = $query->paginate(config('constant.pagination_count'), "*", "recent_portfolio_page");
            $recentPortfolio = $query->paginate($per_page, "*", "recent_portfolio_page");
            $recentPostUpdated = $this->shopDistanceFilter($recentPortfolio, true);
        } else {
            $recentPortfolio = $query->limit($per_page)->get();
            $recentPostUpdated = $this->shopDistanceFilter($recentPortfolio, false);
        }
        return $recentPostUpdated;
    }

    public function shopDistanceFilter($shops, $paginate = false): array
    {
        $filteredShop = [];
        $paginateData = $shops->toArray();
        $user = Auth::user();
        if ($paginate == true) {
            $newPageData = $paginateData['data'];
        } else {
            $newPageData = $paginateData;
        }

        foreach ($newPageData as $key => $shop) {
            $shop = (array)$shop;
            $shop['speciality_of'] = $shop['speciality_of'] ?? '';
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
            $rating = DB::table('reviews')->where('entity_type_id', EntityTypes::SHOP)->where('entity_id', $shop['id'])->avg('rating');
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

            $savedHistory = DB::table('user_saved_history')->where('is_like', 1)->where('saved_history_type_id', SavedHistoryTypes::SHOP)->where('entity_id', $shop['id']);

            $count = $savedHistory->count();
            $shop['saved_count'] = $count;


            if ($user) {
                $savedCount = $savedHistory->where(function ($q) use ($user) {
                    if ($user) {
                        $q->where('user_id', $user->id);
                    }
                })->count();
            } else {
                $savedCount = 0;
            }

            $shop['is_saved_in_history'] = $savedCount > 0;

            $filteredShop[] = $shop;
        }

        if ($paginate == true) {
            $paginateData['data'] = array_values($filteredShop);
        } else {
            $paginateData = array_values($filteredShop);
        }

        return $paginateData;
    }

    public function loadLikedPosts($limitByPackage, $distance, $country, $coordinate, $per_page,$user_id,$user_type = UserHiddenCategory::LOGIN, $menu_key = 'all')
    {
        $categoryData = DB::table('category_settings')
            ->join('category','category.id','category_settings.category_id')
            ->whereNull('category.deleted_at')
            ->where('category_settings.is_show',1)
            ->where('category_settings.country_code',$country);
        /* if($menu_key != 'all'){
            $categoryData = $categoryData->where('category_settings.menu_key',$menu_key);
        } */
        //$categoryData = $categoryData->pluck('category_settings.category_id');
        $categoryData = $categoryData->select('category_settings.category_id as id','category_settings.menu_key')->get();

        if(count($categoryData) == 0){
            $categoryData = DB::table('category')
            ->whereNull('category.deleted_at')
            ->where('category.is_show',1)
            ->where('category.category_type_id',1);

            /* if($menu_key != 'all'){
                $categoryData = $categoryData->where('category.menu_key',$menu_key);
            } */

            //$categoryData = $categoryData->pluck('category.id');
            $categoryData = $categoryData->select('category.id','category.menu_key')->get();
        }

        if($menu_key != 'all'){
            $categoryData = $categoryData->where('menu_key',$menu_key)->pluck('id');
        }else{
            $categoryData = $categoryData->pluck('id');
        }

        $hiddenCategory = [];
        if(!empty($user_id)){
            $hiddenCategory = UserHiddenCategory::where('user_id',$user_id)->where('user_type',$user_type)->pluck('category_id')->toArray();
        }

        $user = Auth::user();
        $shopPost = ShopPost::join('shops', 'shop_posts.shop_id', 'shops.id')
            ->leftjoin('addresses', function ($join) {
                $join->on('shops.id', '=', 'addresses.entity_id')
                    ->where('addresses.entity_type_id', EntityTypes::SHOP);
            })->leftjoin('user_saved_history', function ($join) {
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
            });

        //if($user){
            //$shopPost = $shopPost->whereRaw("shops.id NOT IN (select shop_id from shop_block_histories where user_id = {$user->id} AND is_block = 1)");
            $shopPost = $shopPost->where(function($query) use ($user){
                if ($user) {
                    $query->whereRaw("shops.id NOT IN (select shop_id from shop_block_histories where user_id = {$user->id} AND is_block = 1)");
                }
            });
        //}

        $shopPost = $shopPost->whereNull('user_saved_history.deleted_at')
            ->where('shops.status_id', Status::ACTIVE)
            ->whereRaw('shop_posts.id in (SELECT (select (sp.id) as id from shop_posts as sp JOIN user_saved_history on user_saved_history.entity_id = sp.id where user_saved_history.saved_history_type_id = 1 AND user_saved_history.is_like = 1 AND  sp.deleted_at is NULL AND sp.shop_id = shops.id ORDER BY user_saved_history.updated_at DESC LIMIT 1 ) as id FROM shops HAVING id is not null)')
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
            ->whereRaw("{$distance} <= {$limitByPackage}")
            ->where('user_saved_history.is_like', 1);

        if ($shopPost->count() > 0) {
            $shopPost = $shopPost->paginate($per_page, "*", "shop_page");
            return ['results' => $shopPost, 'type' => 'nearby'];
        } else {
            $shopPost = ShopPost::join('shops', 'shop_posts.shop_id', 'shops.id')
                ->leftjoin('addresses', function ($join) {
                    $join->on('shops.id', '=', 'addresses.entity_id')
                        ->where('addresses.entity_type_id', EntityTypes::SHOP);
                })->leftjoin('user_saved_history', function ($join) {
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
                });

                if($user){
                    $shopPost = $shopPost->whereRaw("shops.id NOT IN (select shop_id from shop_block_histories where user_id = {$user->id} AND is_block = 1)");
                }

                $shopPost = $shopPost->where('addresses.main_country', $country)
                ->whereRaw('shop_posts.id in (SELECT (select (sp.id) as id from shop_posts as sp JOIN user_saved_history on user_saved_history.entity_id = sp.id where user_saved_history.saved_history_type_id = 1 AND user_saved_history.is_like = 1 AND  sp.deleted_at is NULL AND sp.shop_id = shops.id ORDER BY user_saved_history.updated_at DESC LIMIT 1 ) as id FROM shops HAVING id is not null)')
                ->where('shops.status_id', Status::ACTIVE)
                ->whereNull('user_saved_history.deleted_at')
                ->orderBy('user_saved_history.created_at', 'desc')
                ->groupBy('shop_posts.id')
                ->select('shop_posts.*', DB::raw("IFNULL( CAST(ROUND(st_distance_sphere(POINT(addresses.longitude,addresses.latitude), POINT( $coordinate)) * .001,2) AS DECIMAL(21,2)) , '') as shop_distance"))
                ->selectRaw("{$limitByPackage} AS priority")
                ->where('user_saved_history.is_like', 1);
            $shopPost = $shopPost->paginate($per_page, "*", "shop_page");

            return ['results' => $shopPost, 'type' => 'country'];
        }
    }

    public function indexLogin(Request $request): JsonResponse
    {
        $inputs = $request->all();
        $user = Auth::user();
        try {
            $validation = $this->homeValidator->validateGetShop($inputs);
            if ($validation->fails()) {
                return $this->sendCustomErrorMessage($validation->errors()->toArray());
            }

            $user = Auth::user();
            $user_id = $user->id;
            $data = [];

            $country = $inputs['country'] ?? 'KR'; //getCountryFromLatLong($inputs['latitude'], $inputs['longitude']);
            $timezone = $inputs['timezone'] ?? 'UTC'; //get_nearest_timezone($inputs['latitude'], $inputs['longitude'], $country);
            $distance = getDistanceQuery($inputs['latitude'], $inputs['longitude']);
            $limitByPackage = getLimitPackageByQuery(EntityTypes::SHOP);
            $language_id = $inputs['language_id'] ?? PostLanguage::ENGLISH;
            $latitude = $inputs['latitude'] ?? '';
            $longitude = $inputs['longitude'] ?? '';
            $per_page = $inputs['per_page'] ?? 6;
            $following_per_page = $inputs['following_per_page'] ?? 9;
            $menu_key = $inputs['menu_key'] ?? 'all';

            $coordinate = $longitude . ',' . $latitude;

            $data['banner_images'] = loadBannerImages($country);

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
                        WHEN requested_customer.entity_type_id = 1 THEN  shops.id
                        WHEN requested_customer.entity_type_id = 2 THEN hospitals.id
                        ELSE ""
                        END) AS hospitals_id'),
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
                    $entityId = $booking['hospitals_id'];
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
                        $seconds = $totalDuration;
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

            $followingShops = DB::table('shops')->join('shop_followers', function ($join) use ($user) {
                    $join->on('shops.id', '=', 'shop_followers.shop_id')
                        ->where('shop_followers.user_id', $user->id);
                })
                ->join('category', function ($join) {
                    $join->on('shops.category_id', '=', 'category.id')
                        ->whereNull('category.deleted_at');
                })
                ->whereRaw("shops.id NOT IN (select shop_id from shop_block_histories where user_id = {$user->id} AND is_block = 1)")
                ->whereNull('shops.deleted_at')
                ->where('shops.status_id', Status::ACTIVE)
                ->groupBy('shops.id')
                ->get('shops.*')->count();

            $data['following_shop_count'] = $followingShops;

            if ($followingShops > 0) {

                $data['following_shop_post'] = ShopPost::join('shops', 'shop_posts.shop_id', 'shops.id')
                    ->join('shop_followers', function ($join) use ($user, $followingShops) {
                        $join->on('shops.id', '=', 'shop_followers.shop_id')
                            ->where('shop_followers.user_id', $user->id);
                    })
                    ->leftjoin('addresses', function ($join) {
                        $join->on('shops.id', '=', 'addresses.entity_id')
                            ->where('addresses.entity_type_id', EntityTypes::SHOP);
                    })
                    ->join('category', function ($join) {
                        $join->on('shops.category_id', '=', 'category.id')
                            ->whereNull('category.deleted_at');
                    })
                    ->whereRaw("shops.id NOT IN (select shop_id from shop_block_histories where user_id = {$user->id} AND is_block = 1)")
                    ->where('shops.status_id', Status::ACTIVE)
                    ->orderBy('shop_posts.post_order_date', 'desc')
                    ->orderBy('shop_posts.created_at', 'desc')
                    //->limit(12)
                    ->groupBy('shop_posts.id')
                    ->select(
                        'shop_posts.*',
                        DB::raw("IFNULL( CAST(ROUND(st_distance_sphere(POINT(addresses.longitude,addresses.latitude), POINT( $coordinate)) * .001,2) AS DECIMAL(21,2)), '') as shop_distance")
                    )
                    //->get();
                    ->paginate($following_per_page, "*", "following_shop_post_page");
            }

            $recentFollowData = $this->loadRecentPortfolio($language_id, $country, $distance, $limitByPackage, false, $coordinate, $per_page,$user_id,UserHiddenCategory::LOGIN,$menu_key);
            $data['recent_following_shop_portfolio'] = $recentFollowData['results'];
            $data['recent_following_type'] = $recentFollowData['type'];

            $data['user_details'] = ['id' => $user->id, 'status_id' => $user->status_id, 'chat_status' => $user->chat_status];

            $applied_card = getUserAppliedCard($user->id);
            $data['user_applied_card_detail'] = $applied_card ?? getDefaultCard();

            $likedData = $this->loadLikedPosts($limitByPackage, $distance, $country, $coordinate, $per_page, $user_id,UserHiddenCategory::LOGIN,$menu_key);
            $data['recent_like_post'] = $likedData['results'];
            $data['recent_like_post_type'] = $likedData['type'];

            $totalGifticonCount = GifticonDetail::where('user_id',$user->id)->where('is_new',1)->count();
            $data['is_new_gifticon'] = ($totalGifticonCount > 0);

            $shops = DB::table('shops')->where('user_id',$user->id)->whereNull('deleted_at')->pluck('id');
            $userInstaProfile = LinkedSocialProfile::where('user_id', $user->id)->whereIn('shop_id', $shops)->where('social_type', LinkedSocialProfile::Instagram)->get();

            $is_valid_instagram_token = 1;
            $invalid_insta_profile_id = null;
            if(!empty($userInstaProfile)){
                foreach ($userInstaProfile as $key => $value) {
                    if($value->is_valid_token == 0){
                        $is_valid_instagram_token = 0;
                        $invalid_insta_profile_id = $value->shop_id;
                    }
                }
            }

            $startDate = Carbon::now()->timezone($timezone)->subDay()->format('Y-m-d');
            $endDate = Carbon::now()->timezone($timezone)->addDay()->format('Y-m-d');
            $likeOrderRealCount = DB::table('shop_posts')->join('shops as s','s.id','shop_posts.shop_id')
                ->whereNull('s.deleted_at')
                ->whereNull('shop_posts.deleted_at')
                ->whereNotNull('shop_posts.insta_link')
                ->where('shop_posts.is_like_order_admin_read',1)
                ->where('s.count_days','>',0)
                ->select('shop_posts.id')
                ->groupBy('shop_posts.id')
                ->whereBetween('post_order_date',[$startDate,$endDate])
                ->get();

            $data['is_valid_instagram_token'] = $is_valid_instagram_token;
            $data['invalid_instagram_profile_id'] = $invalid_insta_profile_id;
            $data['is_admin_access'] = ($user->is_admin_access==1)?true:false;
            $data['like_order_real_count'] = count($likeOrderRealCount);

            $mbti = UserDetail::where('user_id',$user->id)->pluck('mbti')->first();
            $data['mbti'] = $mbti ?? null;

            //for admin chat room
            if ($user->is_admin_access != 1){
                $adminQuery = AdminMessage::whereRaw("admin_messages.type='text' and (admin_messages.from_user = " . $user->id . " OR admin_messages.to_user = " . $user->id . ")")
                    ->orderBy('admin_messages.created_at','DESC')
                    ->first();

                $shop_profile = Shop::where('user_id',$user->id)->orderBy('created_at','desc')->select(['main_name','shop_name'])->first();

                if(!empty($adminQuery)){
                    $adminQuery->is_admin_chat = true;
                    $adminQuery->from_user_id = $user->id;
                    $adminQuery->to_user_id = 0;
                    $adminQuery->main_name = (!empty($shop_profile)) ? $shop_profile->main_name : null;
                    $adminQuery->sub_name = (!empty($shop_profile)) ? $shop_profile->shop_name : null;
                    $adminQuery->image = asset('img/logo.png');
                    $adminQuery->time_difference = $adminQuery ? timeAgo($adminQuery->created_at, $language_id, $timezone)  : "";
                    $adminChat = $adminQuery;
                }else{
                    $adminChat = [
                        'is_admin_chat' => true,
                        'from_user_id' => $user->id,
                        'to_user_id' => 0,
                        'main_name' => (!empty($shop_profile)) ? $shop_profile->main_name : null,
                        'sub_name' => (!empty($shop_profile)) ? $shop_profile->shop_name : null,
                        'message' => "",
                        'image' => asset('img/logo.png'),
                        'time_difference' => ""
                    ];
                }

                $data['admin_chat'] = $adminChat;
            }

            return $this->sendSuccessResponse(Lang::get('messages.home.success'), 200, $data);
        } catch (Exception $e) {
            Log::info($e);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'));
        }
    }

    public function insertPostsDataDummy(Request $request)
    {
        $inputs = $request->all();
        try {
            $updateData = array(
                'shop_id' => $inputs['shop_id'] ?? '',
                'instagram_post_id' => $inputs['instagram_post_id'] ?? '',
                'type' => $inputs['type'] ?? 'image',
                'post_item' => $inputs['post_item'] ?? '',
            );
            $insertData = array(
                'type' => $inputs['type'] ?? 'image',
                'post_item' => $inputs['post_item'] ?? '',
            );
            $data = ShopPost::create($updateData);
            //$data = ShopPost::create($updateData,$insertData);

            return $this->sendSuccessResponse(Lang::get('messages.messages.success'), 200, $data);
        } catch (\Throwable $th) {
            print_r($th->getMessage());
        }
    }

    public function removeDuplicatePostsData($isapi = 'no')
    {
        try {
            Log::info("Inside Duplicate");
            $duplicates = DB::table('shop_posts')
                ->select('id', 'shop_id', 'instagram_post_id')
                ->whereIn('id', function ($q){
                            $q->select('id')
                            ->from('shop_posts')
                            ->whereNotNull('instagram_post_id')
                            ->whereNull('deleted_at')
                            ->groupBy('shop_id', 'instagram_post_id','post_item')
                            ->havingRaw('COUNT(*) > 1');
                })
                ->whereNotNull('instagram_post_id')
                ->whereNull('deleted_at')
                ->limit(100)
                ->get();

            if($isapi == 'yes'){
                Log::info("Inside Duplicate API");
                return $this->sendSuccessResponse(Lang::get('messages.messages.success'), 200, $duplicates);
            }else{
                Log::info("Inside Duplicate Cron");
                if(count($duplicates)){
                    foreach($duplicates as $post){
                        ShopPost::whereId($post->id)->delete();
                    }
                }
            }

        } catch (\Throwable $th) {
            print_r($th->getMessage());
        }
    }
}
