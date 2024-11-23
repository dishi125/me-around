<?php

namespace App\Http\Controllers\Api;

use Validator;
use Carbon\Carbon;
use App\Models\Shop;
use App\Models\Banner;
use App\Models\Status;
use App\Models\Address;
use App\Models\Country;
use App\Models\HashTag;
use App\Models\Category;
use App\Models\ShopPost;
use App\Models\ShopDetail;
use App\Models\ShopImages;
use App\Models\UserDetail;
use App\Models\CreditPlans;
use App\Models\EntityTypes;
use App\Models\PackagePlan;
use App\Models\PostLanguage;
use Illuminate\Http\Request;
use App\Models\CategoryTypes;
use App\Models\SearchHistory;
use App\Models\ShopImagesTypes;
use App\Models\MultipleShopPost;
use App\Models\SavedHistoryTypes;
use App\Models\ShopDetailLanguage;
use App\Models\UserEntityRelation;
use Illuminate\Support\Facades\DB;
use App\Models\LinkedSocialProfile;
use App\Models\SharedInstagramPost;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Models\RequestBookingStatus;
use App\Models\UserInstagramHistory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use App\Validators\ShopProfileValidator;
use App\Models\Config as ConfigModel;

class ShopController extends Controller
{

    private $shopProfileValidator;

    function __construct()
    {
        $this->shopProfileValidator = new ShopProfileValidator();
    }
    public function getAllShops(Request $request)
    {
        try {
            Log::info('Start code for get all shops');
            $inputs = $request->all();
            $category_id = isset($inputs['category_id']) ? $inputs['category_id'] : 0;
            $language_id = $inputs['language_id'] ?? PostLanguage::ENGLISH;
            $validation = $this->shopProfileValidator->validateGetShop($inputs);

            if ($validation->fails()) {
                Log::info('End code for get all shops');
                return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
            }

            $category = Category::find($category_id);

            $is_suggest_category = $category && $category->category_type_id == CategoryTypes::CUSTOM ? 1 : 0;

            $main_country = getCountryFromLatLong($inputs['latitude'], $inputs['longitude']);

            $returnData = [];

            $latitude = $inputs['latitude'] ?? '';
            $longitude = $inputs['longitude'] ?? '';

            $coordinate = $longitude . ',' . $latitude;

            $distance = "(6371 * acos(cos(radians(" . $inputs['latitude'] . "))
                     * cos(radians(addresses.latitude))
                     * cos(radians(addresses.longitude)
                    - radians(" . $inputs['longitude'] . "))
                    + sin(radians(" . $inputs['latitude'] . "))
                            * sin(radians(addresses.latitude))))";


            $shops = $this->getAllShopsCommonData($inputs, false, $language_id);

            $returnData['all_shops'] = $this->shopDistanceFilterNew($shops);
            $config = ConfigModel::where('key', ConfigModel::SHOW_RECENT_COMPLETED_SHOPS)->first();
            if(!empty($config) && $config->value == true){
                $returnData['recent_completed_shops'] = $this->shopRecentCompleted($main_country, $category_id, $distance, $is_suggest_category, false, $language_id);
            }else{
                $returnData['recent_completed_shops'] = null;
            }
            $returnData['banner_images'] = $this->shopBanners($main_country, $category_id);
            $returnData['recent_portfolio'] = $this->shopRecentUpdatedPost($main_country, $category_id, $distance, $is_suggest_category, $language_id, $coordinate);
            Log::info('End code get all shops');
            return $this->sendSuccessResponse(Lang::get('messages.shop.success'), 200, $returnData);
        } catch (\Exception $e) {
            Log::info('Exception in get all shops');
            Log::info($e);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function shopDistanceFilterNew($shops)
    {
        $paginateData = $shops->toArray();
        $user = Auth::user();

        foreach ($paginateData['data'] as $key => $shop) {
            $shop->distance = number_format((float)$shop->distance, 1, '.', '');
            $shop->is_discount = (bool)$shop->is_discount;

            if (property_exists($shop, 'city_name')) {
                $shop->address = ["city_name" => $shop->city_name];
            }

            if (property_exists($shop, 'thumbnail_image')) {
                $newThumbUrl = '';
                if($shop->thumbnail_image){
                    $fileName = basename($shop->thumbnail_image);
                    $newValue = str_replace($fileName,"thumb/$fileName",$shop->thumbnail_image);
                    if (!filter_var($newValue, FILTER_VALIDATE_URL)) {
                        $newThumbUrl = Storage::disk('s3')->url($newValue);
                    } else {
                        $newThumbUrl = $newValue;
                    }
                }

                $shop->thumbnail_image = ["thumb" => $newThumbUrl, "image" => Storage::disk('s3')->url($shop->thumbnail_image), "id" => $shop->thumbnail_image_id];
            } else {
                $shop->thumbnail_image = (object)[];
            }

            $shop->rating = property_exists($shop, 'rating') ? number_format($shop->rating, 1) : "0";

            if ($user) {
                $followers = DB::table('shop_followers')->where('shop_id', $shop->id)->where('user_id', $user->id)->count();
                $shop->is_follow = $followers > 0 ? 1 : 0;
            }
        }

        return $paginateData;
    }

    public function shopDistanceFilter($shops, $is_post = 0, $is_suggest_category = 0)
    {
        $filteredShop = [];
        $paginateData = $shops->toArray();
        $user = Auth::user();
        foreach ($paginateData['data'] as $key => $shop) {
            //print_r($shop);
            $shop = (array)$shop;

            if (isset($shop['video_thumbnail']) && !empty($shop['video_thumbnail'])) {
                //$shop['video_thumbnail'] = Storage::disk('s3')->url($shop['video_thumbnail']);
            }

            $defaultItem = [];

            if (isset($shop['video_thumbnail']) && !empty($shop['video_thumbnail'])) {
                $shop['video_thumbnail'] = (!str_contains($shop['video_thumbnail'], 'amazonaws')) ? Storage::disk('s3')->url($shop['video_thumbnail']) : $shop['video_thumbnail'];
                $defaultItem[0]['video_thumbnail'] = (!str_contains($shop['video_thumbnail'], 'amazonaws')) ? Storage::disk('s3')->url($shop['video_thumbnail']) : $shop['video_thumbnail'];
            } else {
                $defaultItem[0]['video_thumbnail'] = '';
            }

            $defaultItem[0]['id'] = $shop['id'];
            $defaultItem[0]['type'] = $shop['type'];

            $post_item = (!empty($shop['post_item']) && !str_contains($shop['post_item'], 'amazonaws')) ? Storage::disk('s3')->url($shop['post_item']) : NULL;
            $defaultItem[0]['post_item'] = $post_item;
            $defaultItem[0]['post_item_thumbnail'] = filterDataThumbnailUrl($post_item);

            if ($shop['is_multiple'] == true) {
                $posts = MultipleShopPost::where('shop_posts_id', $shop['id'])->get();
                $shop['multiple_shop_posts'] = collect($defaultItem)->merge($posts)->values();
            } else {
                $shop['multiple_shop_posts'] = $defaultItem;
            }

            $hash_tags = [];
            if (!empty($shop['id'])) {
                $hash_tags = HashTag::join('hash_tag_mappings', function ($join) {
                    $join->on('hash_tag_mappings.hash_tag_id', '=', 'hash_tags.id')
                        ->where('hash_tag_mappings.entity_type_id', HashTag::SHOP_POST);
                })
                    ->join('shop_posts', function ($join) {
                        $join->on('shop_posts.id', '=', 'hash_tag_mappings.entity_id');
                    })
                    ->where('hash_tag_mappings.entity_id', $shop['id'])
                    ->select(
                        'hash_tags.*',
                        DB::raw('COUNT(hash_tag_mappings.id) as total_posts'),
                        'shop_posts.id as post_id',
                        DB::raw('group_concat(shop_posts.id) as shop_posts')
                    )
                    ->orderBy('total_posts', 'DESC')
                    ->groupBy('hash_tags.id')
                    ->get();
            }

            $shop['hash_tags'] = $hash_tags;


            if ($is_post == 1) {
                $shopData = Shop::find($shop['shop_id']);

                $shop['business_link'] = $shopData->business_link ? $shopData->business_link : '';
                $shop['another_mobile'] = $shopData->another_mobile ? $shopData->another_mobile : '';
                $shop['booking_link'] = $shopData->booking_link ? $shopData->booking_link : '';

                $shop['shop_name'] = $shopData->shop_name ? $shopData->shop_name : '';
                $shop['main_name'] = $shopData->main_name ? $shopData->main_name : '';
                $shop['speciality_of'] = $shop['speciality_of'] ? $shop['speciality_of'] : '';

                $thumbnail = ShopImages::where('shop_id', $shopData['id'])->where('shop_image_type', ShopImagesTypes::THUMB)->select(['id', 'image'])->first();
                $shop['shop_thumbnail'] = $thumbnail ? $thumbnail->image :  "";

                // Workplace images
                $worplace_images = ShopImages::where('shop_id', $shopData['id'])->where('shop_image_type', ShopImagesTypes::WORKPLACE)->get(['id', 'image']);
                $images = [];
                if (empty($worplace_images)) {
                    $shop['workplace_images'] = $images;
                } else {
                    $shop['workplace_images'] = $worplace_images;
                }
                $shop['is_discount'] = ($shopData->is_discount && $shopData->is_discount == 1) ? true : false;

                $user_id = $shopData->user_id;
            } else {
                $user_id = $shop['user_id'];
                // Workplace images
                $worplace_images = ShopImages::where('shop_id', $shop['id'])->where('shop_image_type', ShopImagesTypes::WORKPLACE)->get(['id', 'image']);
                $images = [];
                if (empty($worplace_images)) {
                    $shop['workplace_images'] = $images;
                } else {
                    $shop['workplace_images'] = $worplace_images;
                }
            }
            $shop['user_id'] = $user_id;

            $followers = DB::table('shop_followers')->where('shop_id', $shop['id'])->where(function ($q) use ($user) {
                if ($user) {
                    $q->where('user_id', $user->id);
                }
            })->count();
            $shop['is_follow'] = ($followers > 0 && $user) ? 1 : 0;

            $userdetail = UserDetail::where('user_id', $user_id)->first();
            $planDetail = CreditPlans::where('entity_type_id', EntityTypes::SHOP)->where('package_plan_id', $userdetail->package_plan_id)->first();
            $rating = DB::table('reviews')->where('entity_type_id', EntityTypes::SHOP)->where('entity_id', $shop['id'])->avg('rating');

            // dd($planDetail->km);
            $km = $planDetail ? $planDetail->km : 0;

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

            $address = Address::where('entity_type_id', EntityTypes::SHOP)->where('entity_id', $shop['id'])->first();
            $shop['address'] = !empty($address) ? $address : $emptyObject;

            // Get Rating
            $shop['rating'] = $rating ? number_format($rating, 1) : "0";

            // Get Thumbnail Images
            $thumbnail = ShopImages::where('shop_id', $shop['id'])->where('shop_image_type', ShopImagesTypes::THUMB)->select(['id', 'image'])->first();
            $images = (object)[];
            if (empty($thumbnail)) {
                $shop['thumbnail_image'] = $images;
            } else {
                $newThumbUrl = '';
                if($thumbnail->image){
                    $fileName = basename($thumbnail->image);
                    $newValue = str_replace($fileName,"thumb/$fileName",$thumbnail->image);
                    if (!filter_var($newValue, FILTER_VALIDATE_URL)) {
                        $newThumbUrl = Storage::disk('s3')->url($newValue);
                    } else {
                        $newThumbUrl = $newValue;
                    }
                }
                $thumbnail->thumb = $newThumbUrl;
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

            $savedHistoryq = DB::table('user_saved_history')->where('is_like', 1)->where('saved_history_type_id', SavedHistoryTypes::SHOP)->where('entity_id', $shop['id']);

            $count = $savedHistoryq->count();
            $shop['saved_count'] = $count;

            $savedCount = $savedHistoryq->where(function ($q) use ($user) {
                if ($user) {
                    $q->where('user_id', $user->id);
                }
            })->count();
            $shop['is_saved_in_history'] = ($savedCount > 0 && $user) ? true : false;

            $filteredShop[] = $shop;
            /* if($is_suggest_category == 0) {
                if($km >= $shop['distance']) {
                    $filteredShop[] = $shop;
                } else {
                    unset($paginateData['data'][$key]);
                }
            }else {
                $filteredShop[] = $shop;
            } */
        }

        //  print_r($filteredShop);die;
        //  die('150');

        // dd(1);
        $paginateData['data'] = array_values($filteredShop);
        return $paginateData;
    }

    public function shopBanners($main_country, $category_id)
    {
        $banners = [];
        $bannerImagesQuery = Banner::join('banner_images', 'banners.id', '=', 'banner_images.banner_id')
            ->where('banners.entity_type_id', EntityTypes::SHOP)
            ->where('banners.section', 'home')
            ->whereNull('banners.deleted_at')
            ->whereNull('banner_images.deleted_at')
            ->where('banners.country_code', $main_country);

        if ($category_id != 0) {
            $bannerImagesQuery = $bannerImagesQuery->where('banners.category_id', $category_id);
        } else {
            $bannerImagesQuery = $bannerImagesQuery->where('banners.category_id', null);
        }

        $bannerImages = $bannerImagesQuery->orderBy('banner_images.order', 'desc')->orderBy('banner_images.id', 'desc')
            ->get('banner_images.*');

        foreach ($bannerImages as $banner) {
            $temp = [];
            $temp['image'] = Storage::disk('s3')->url($banner->image);
            $temp['link'] = $banner->link;
            $temp['slide_duration'] = $banner->slide_duration;
            $temp['order'] = $banner->order;
            $banners[] = $temp;
        }

        return $banners;
    }

    public function shopRecentCompleted($main_country, $category_id, $distance, $is_suggest_category = 0, $isOrderDistance = false, $language_id)
    {
        $user = Auth::user();
        $recentCompleted = [];
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

        $recentCompletedShopsQuery = DB::table('shops')->join('requested_customer', 'requested_customer.entity_id', 'shops.id')
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
            ->leftjoin('shop_images', function ($join) {
                $join->on('shops.id', '=', 'shop_images.shop_id')
                    ->where('shop_images.shop_image_type', ShopImagesTypes::THUMB)
                    ->whereNull('shop_images.deleted_at');
            })
            ->join('category', function ($join) {
                $join->on('shops.category_id', '=', 'category.id')
                    ->whereNull('category.deleted_at');
            })
            ->where(function($query) use ($user){
                if ($user) {
                    $query->whereRaw("shops.id NOT IN (select shop_id from shop_block_histories where user_id = {$user->id} AND is_block = 1)");
                }
            })
            ->where('addresses.main_country', $main_country)
            ->where('requested_customer.entity_type_id', EntityTypes::SHOP)
            ->where('requested_customer.request_booking_status_id', RequestBookingStatus::COMPLETE)
            ->whereNull('shops.deleted_at')
            ->groupBy('shops.id');

        if ($category_id != 0) {
            $recentCompletedShopsQuery = $recentCompletedShopsQuery->where('shops.category_id', $category_id);
        } else {
            $recentCompletedShopsQuery = $recentCompletedShopsQuery->where('category.category_type_id', CategoryTypes::SHOP);
        }

        $recentCompletedShopsQuery = $recentCompletedShopsQuery->groupBy('shops.id');

        if ($isOrderDistance === false) {
            $recentCompletedShopsQuery = $recentCompletedShopsQuery->orderBy('requested_customer.id', 'desc');
        }

        $recentCompletedShops = $recentCompletedShopsQuery->orderby('distance')
            ->select(
                'shops.id',
                'shops.is_discount',
                'shops.main_name',
                'shops.shop_name',
                //'shops.speciality_of',
                'shop_images.image as thumbnail_image',
                'shop_images.id as thumbnail_image_id',
                DB::raw('IFNULL(shop_detail_languages.value, shops.speciality_of) as speciality_of')
            )
            ->selectRaw("{$distance} AS distance")
            ->selectRaw("{$limitByPackage} AS priority")
            ->whereRaw("{$distance} <= {$limitByPackage}")
            ->paginate(config('constant.portfolio_pagination_count_shop'), "*", "recent_completed_shops_page");

        $recentCompleted = $this->shopDistanceFilterNew($recentCompletedShops, 0, $is_suggest_category);

        return $recentCompleted;
    }

    public function shopRecentUpdatedPost($main_country, $category_id, $distance, $is_suggest_category = 0, $language_id, $coordinate, $recent_portfolio_per_page = 9)
    {
        $user = Auth::user();
        $recentPostUpdated = [];
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
            ->where(function($query) use ($user){
                if ($user) {
                    $query->whereRaw("shops.id NOT IN (select shop_id from shop_block_histories where user_id = {$user->id} AND is_block = 1)");
                }
            })
            ->whereRaw('shop_posts.id in (select max(sp.id) from shop_posts as sp where sp.deleted_at is NULL  group by (sp.shop_id))')
            ->where('addresses.main_country', $main_country)
            ->where('shops.status_id', Status::ACTIVE)
            ->whereNull('shop_posts.deleted_at')
            ->whereNull('shops.deleted_at');

        if ($category_id != 0) {
            $recentPortfolioQuery = $recentPortfolioQuery->where('shops.category_id', $category_id);
        } else {
            $recentPortfolioQuery = $recentPortfolioQuery->where('category.category_type_id', CategoryTypes::SHOP);
        }

        $recentPortfolio = $recentPortfolioQuery->orderBy('shop_posts.created_at', 'desc')
            ->orderby('distance')
            ->select('shop_posts.*', 'shops.category_id', DB::raw('IFNULL(shop_detail_languages.value, shops.speciality_of) as speciality_of'), DB::raw("IFNULL( CAST(ROUND(st_distance_sphere(POINT(addresses.longitude,addresses.latitude), POINT( $coordinate)) * .001,2) AS DECIMAL(21,2)), '') as shop_distance"))
            ->selectRaw("{$distance} AS distance")
            ->selectRaw("{$limitByPackage} AS priority")
            ->whereRaw("{$distance} <= {$limitByPackage}")
            ->paginate($recent_portfolio_per_page, "*", "recent_portfolio_page");

        $recentPostUpdated = $this->shopDistanceFilter($recentPortfolio, 1, $is_suggest_category);

        return $recentPostUpdated;
    }

    public function getAllShopsDistance(Request $request)
    {
        try {
            Log::info('Start code for get all shops');
            $inputs = $request->all();
            $category_id = isset($inputs['category_id']) ? $inputs['category_id'] : 0;
            $language_id = $inputs['language_id'] ?? PostLanguage::ENGLISH;
            $validation = $this->shopProfileValidator->validateGetShop($inputs);

            if ($validation->fails()) {
                Log::info('End code for get all shops');
                return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
            }

            $category = Category::find($category_id);

            $is_suggest_category = $category && $category->category_type_id == CategoryTypes::CUSTOM ? 1 : 0;

            $main_country = getCountryFromLatLong($inputs['latitude'], $inputs['longitude']);

            $returnData = [];
            $latitude = $inputs['latitude'] ?? '';
            $longitude = $inputs['longitude'] ?? '';

            $coordinate = $longitude . ',' . $latitude;

            $distance = "(6371 * acos(cos(radians(" . $inputs['latitude'] . "))
                     * cos(radians(addresses.latitude))
                     * cos(radians(addresses.longitude)
            - radians(" . $inputs['longitude'] . "))
            + sin(radians(" . $inputs['latitude'] . "))
                     * sin(radians(addresses.latitude))))";

            $shops = $this->getAllShopsCommonData($inputs, true, $language_id);

            $returnData['all_shops'] = $this->shopDistanceFilterNew($shops);
            $config = ConfigModel::where('key', ConfigModel::SHOW_RECENT_COMPLETED_SHOPS)->first();
            if(!empty($config) && $config->value == true){
                $returnData['recent_completed_shops'] = $this->shopRecentCompleted($main_country, $category_id, $distance, $is_suggest_category, true, $language_id);
            }else{
                $returnData['recent_completed_shops'] = null;
            }
            $returnData['banner_images'] = $this->shopBanners($main_country, $category_id);
            $returnData['recent_portfolio'] = $this->shopRecentUpdatedPost($main_country, $category_id, $distance, $is_suggest_category, $language_id, $coordinate);
            Log::info('End code get all shops');
            return $this->sendSuccessResponse(Lang::get('messages.shop.success'), 200, $returnData);
        } catch (\Exception $e) {
            Log::info('Exception in get all shops');
            Log::info($e);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function getCategoryShops($id)
    {
        try {
            Log::info('Start code get all shops');
            $category = Category::find($id);
            if ($category) {
                $returnData = [];
                $shops = Shop::where('status_id', Status::ACTIVE)
                    ->where('category_id', $id)->get();

                $recentCompletedShops = Shop::join('requested_customer', 'requested_customer.entity_id', 'shops.id')
                    ->where('requested_customer.entity_type_id', EntityTypes::SHOP)
                    ->where('shops.category_id', $id)
                    ->where('shops.status_id', Status::ACTIVE)
                    ->where('requested_customer.request_booking_status_id', RequestBookingStatus::COMPLETE)
                    ->get('shops.*');

                $recent_portfolio = ShopPost::join('shops', 'shop_posts.shop_id', 'shops.id')
                    ->where('shops.category_id', $id)
                    ->where('shops.status_id', Status::ACTIVE)
                    ->orderBy('shop_posts.created_at', 'desc')
                    // ->limit(10)
                    ->get(['shop_posts.*']);

                $bannerImages = Banner::join('banner_images', 'banners.id', '=', 'banner_images.banner_id')
                    ->where('banners.entity_type_id', EntityTypes::SHOP)
                    ->where('banners.category_id', $id)
                    ->where('banners.section', 'home')
                    ->whereNull('banners.deleted_at')
                    ->whereNull('banner_images.deleted_at')
                    ->orderBy('banner_images.order', 'desc')
                    ->orderBy('banner_images.id', 'desc')
                    ->get('banner_images.*');

                $sliders = [];
                foreach ($bannerImages as $banner) {
                    $temp = [];
                    $temp['image'] = Storage::disk('s3')->url($banner->image);
                    $temp['link'] = $banner->link;
                    $temp['slide_duration'] = $banner->slide_duration;
                    $temp['order'] = $banner->order;
                    $sliders[] = $temp;
                }
                $returnData['all_shops'] = $shops;
                $config = ConfigModel::where('key', ConfigModel::SHOW_RECENT_COMPLETED_SHOPS)->first();
                if(!empty($config) && $config->value == true){
                    $returnData['recent_completed_shops'] = $recentCompletedShops;
                }else{
                    $returnData['recent_completed_shops'] = null;
                }
                $returnData['banner_images'] = $sliders;
                $returnData['recent_portfolio'] = $recent_portfolio;
                Log::info('End code get all shops');
                return $this->sendSuccessResponse(Lang::get('messages.shop.success'), 200, $returnData);
            } else {
                return $this->sendSuccessResponse(Lang::get('messages.category.empty'), 501);
            }
        } catch (\Exception $e) {
            Log::info('Exception in get all shops');
            Log::info($e);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }
    public function getShopDetail(Request $request, $id)
    {
        $inputs = $request->all();
        try {
            $language_id = $inputs['language_id'] ?? PostLanguage::ENGLISH;

            Log::info('Start code get shop detail');
            $shopExists = Shop::where('id', $id)->first();
            $latitude = $inputs['latitude'] ?? '';
            $longitude = $inputs['longitude'] ?? '';
            $coordinate = $longitude . ',' . $latitude;

            $per_page = $inputs['per_page'] ?? 9;
            Config::set('shop_detail_per_page',$per_page);
            if ($shopExists) {
                //$shops = Shop::where('id',$id)->first();

                $shopsQuery = Shop::leftjoin('addresses', function ($join) {
                    $join->on('shops.id', '=', 'addresses.entity_id')
                        ->where('addresses.entity_type_id', EntityTypes::SHOP);
                })
                    ->select('shops.*');

                if (isset($inputs['latitude']) && isset($inputs['longitude'])) {
                    $shopsQuery = $shopsQuery->addSelect(DB::raw("IFNULL( CAST(ROUND(st_distance_sphere(POINT(addresses.longitude,addresses.latitude), POINT( $coordinate)) * .001,2) AS DECIMAL(21,2)), '') as shop_distance"));
                }

                $shops = $shopsQuery->where('shops.id', $id)->first();

                $etcDetail = ShopDetail::where('shop_id', $shops->id)->whereIn('type', [ShopDetail::TYPE_CERTIFICATE, ShopDetail::TYPE_TOOLS_MATERIAL_INFO])->first();
                $isEtcDetails = (!empty($etcDetail)) ? true : false;

                $shops->is_qualification = $isEtcDetails;

                $shopSpecialityOf = $shops->shopLanguageDetails()->where('language_id', $language_id)->where('key', ShopDetailLanguage::SPECIALITY_OF)
                    ->where('entity_type_id', EntityTypes::SHOP)->first();

                if (!empty($shopSpecialityOf)) {
                    $shops->speciality_of = $shopSpecialityOf->value;
                }

                $data = [];
                Log::info('End code for the get shop detail');

                $postCollection = collect($shops->portfolio_images->getCollection())->map(function ($value) use ($shops) {
                    $value->shop_distance = $shops->shop_distance;
                    return $value;
                });
                $shops = $shops->toArray();
                $shops['portfolio_images']['data'] = $postCollection;

                if (!empty($shops)) {
                    return $this->sendSuccessResponse(Lang::get('messages.shop.edit-success'), 200, $shops);
                } else {
                    return $this->sendSuccessResponse(Lang::get('messages.shop.empty'), 402);
                }
            } else {
                return $this->sendSuccessResponse(Lang::get('messages.shop.empty'), 402);
            }
        } catch (\Exception $e) {
            Log::info('Exception in get shop detail');
            Log::info($e);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function instagramSharePost(Request $request)
    {
        try {

            Log::info('Start code for instagram share post');
            $user = Auth::user();
            if ($user) {
                DB::beginTransaction();
                $inputs = $request->all();
                $validation = $this->shopProfileValidator->validateInstagramSharePost($inputs);

                if ($validation->fails()) {
                    Log::info('End code for instagram share post');
                    return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
                }
                $shop = Shop::where('id', $inputs['shop_id'])->first();
                if ($shop) {
                    $userData = UserEntityRelation::where('entity_type_id', EntityTypes::SHOP)
                        ->where('entity_id', $shop->id)->first();
                    $data = [
                        'shop_id' => $shop->id,
                        'shop_image_id' => $inputs['shop_image_id'],
                        'shop_user_id' => $userData->user_id
                    ];

                    $addPost = SharedInstagramPost::create($data);

                    UserInstagramHistory::firstOrCreate(['user_id' => $userData->user_id]);
                    DB::commit();
                    Log::info('End code for instagram share post');
                    return $this->sendSuccessResponse(Lang::get('messages.shop.instagram-share-success'), 200, $addPost);
                } else {
                    return $this->sendSuccessResponse(Lang::get('messages.shop.empty'), 402);
                }
            } else {
                Log::info('End code for instagram share post');
                return $this->sendSuccessResponse(Lang::get('messages.user.token_expired'), 401);
            }
        } catch (\Exception $ex) {
            Log::info('Exception in the instagram share post');
            Log::info($ex);
            DB::rollback();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function getAuthShopDetail(Request $request, $id)
    {
        $inputs = $request->all();
        try {
            Log::info('Start code get shop detail');
            $user = Auth::user();
            if ($user) {
                $latitude = $inputs['latitude'] ?? '';
                $longitude = $inputs['longitude'] ?? '';
                $per_page = $inputs['per_page'] ?? 9;
                $coordinate = $longitude . ',' . $latitude;

                /* App::singleton('shop_detail_per_page', function() use ($per_page){
                    return $per_page;
                }); */

                Config::set('shop_detail_per_page',$per_page);

                $shopExistsQuery = Shop::leftjoin('addresses', function ($join) {
                    $join->on('shops.id', '=', 'addresses.entity_id')
                        ->where('addresses.entity_type_id', EntityTypes::SHOP);
                    })
                    ->leftjoin('linked_social_profiles','linked_social_profiles.shop_id','shops.id')
                    ->select(
                        'shops.*',
                        'linked_social_profiles.is_valid_token as is_valid_token',
                        DB::raw('IFNULL(linked_social_profiles.social_id, "") as is_connect')
                    );

                if (isset($inputs['latitude']) && isset($inputs['longitude'])) {
                    $shopExistsQuery = $shopExistsQuery->addSelect(DB::raw("IFNULL(CAST(ROUND(st_distance_sphere(POINT(addresses.longitude,addresses.latitude), POINT( $coordinate)) * .001,2) AS DECIMAL(21,2)) , '') as shop_distance"));
                }

                /*  echo $id;
                echo $shopExistsQuery->where('shops.id',$id)->toSql();
                exit; */

                $shopExists = $shopExistsQuery->where('shops.id', $id)->first();

                if ($shopExists) {
                    //$shops = Shop::where('id',$id)->first();
                    $etcDetail = ShopDetail::where('shop_id', $shopExists->id)->whereIn('type', [ShopDetail::TYPE_CERTIFICATE, ShopDetail::TYPE_TOOLS_MATERIAL_INFO])->first();
                    $isEtcDetails = (!empty($etcDetail)) ? true : false;

                    $shopExists->is_qualification = $isEtcDetails;
                    $shopExists->user_details = ['id' => $user->id, 'status_id' => $user->status_id, 'chat_status' => $user->chat_status, 'is_admin_access' => ($user->is_admin_access==1)?true:false];

                    $userInstaProfile = LinkedSocialProfile::where('user_id', $user->id)->where('shop_id', $id)->where('social_type', LinkedSocialProfile::Instagram)->first();
                    $shopExists->is_instagram_connect = (!empty($userInstaProfile) && !empty($userInstaProfile->social_id));
                    $shopExists->instagram_id = (!empty($userInstaProfile) && !empty($userInstaProfile->social_id)) ? $userInstaProfile->social_id : '';
                    $shopExists->insta_social_name = (!empty($userInstaProfile) && !empty($userInstaProfile->social_name)) ? $userInstaProfile->social_name : '';
                    $shopExists->is_valid_instagram_token = (!empty($userInstaProfile)) ? $userInstaProfile->is_valid_token : 1;

                    $postCollection = collect($shopExists->portfolio_images->getCollection())->map(function ($value) use ($shopExists,$user) {
                        $value->shop_distance = $shopExists->shop_distance;
                        $value->is_admin_access = ($user->is_admin_access==1)?true:false;
                        return $value;
                    });

                    if ($user->is_admin_access==1 && !empty($shopExists->is_connect)){
                        $shopExists->instagram_url = (!empty($userInstaProfile) && !empty($userInstaProfile->social_name)) ? "https://www.instagram.com/".$userInstaProfile->social_name : '';
                    }

                    $shopExists = $shopExists->toArray();
                    $shopExists['portfolio_images']['data'] = $postCollection;
                    return $this->sendSuccessResponse(Lang::get('messages.shop.edit-success'), 200, $shopExists);
                } else {
                    return $this->sendSuccessResponse(Lang::get('messages.shop.empty'), 402);
                }
            } else {
                Log::info('End code for the get shop list');
                return $this->sendSuccessResponse(Lang::get('messages.user.token_expired'), 401);
            }
        } catch (\Exception $e) {
            Log::info('Exception in get shop detail');
            Log::info($e);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function getInstaDetail(Request $request){
        $inputs = $request->all();
        try {
            $user = Auth::user();
            if ($user){
                $shopQuery = Shop::leftjoin('linked_social_profiles','linked_social_profiles.shop_id','shops.id')
                    ->where('shops.id',$inputs['shop_id'])
                    ->where('linked_social_profiles.social_type', LinkedSocialProfile::Instagram)
                    ->select(
                        'shops.*',
                        'linked_social_profiles.is_valid_token as is_valid_token',
                        DB::raw('IFNULL(linked_social_profiles.social_id, "") as is_connect'),
                        'linked_social_profiles.social_name'
                    )
                    ->first();

                $instagram_url = null;
                if ($user->is_admin_access==1 && !empty($shopQuery->is_connect)){
                    $instagram_url = (!empty($shopQuery) && !empty($shopQuery->social_name)) ? "https://www.instagram.com/".$shopQuery->social_name : null;
                }

                return $this->sendSuccessResponse("Instagram data", 200, $instagram_url);
            }
            else {
                return $this->sendSuccessResponse(Lang::get('messages.user.token_expired'), 401);
            }
        } catch (\Exception $e) {
            Log::info('Exception in get insta detail');
            Log::info($e);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function getAuthAllShopsDistance(Request $request)
    {
        try {
            Log::info('Start code for get all shops');
            $user = Auth::user();
            if ($user) {
                $inputs = $request->all();

                $category_id = isset($inputs['category_id']) ? $inputs['category_id'] : 0;
                $language_id = $inputs['language_id'] ?? PostLanguage::ENGLISH;
                $validation = $this->shopProfileValidator->validateGetShop($inputs);

                if ($validation->fails()) {
                    Log::info('End code for get all shops');
                    return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
                }
                // $category = Category::find($category_id);
                $category = DB::table('category')->select('category_type_id')->where('id', $category_id)->first();

                $is_suggest_category = $category && $category->category_type_id == CategoryTypes::CUSTOM ? 1 : 0;
                $latitude = $inputs['latitude'] ?? '';
                $longitude = $inputs['longitude'] ?? '';

                $coordinate = $longitude . ',' . $latitude;

                $main_country = getCountryFromLatLong($inputs['latitude'], $inputs['longitude']);
                $returnData = [];

                $distance = "(6371 * acos(cos(radians(" . $inputs['latitude'] . "))
                     * cos(radians(addresses.latitude))
                     * cos(radians(addresses.longitude)
                - radians(" . $inputs['longitude'] . "))
                + sin(radians(" . $inputs['latitude'] . "))
                     * sin(radians(addresses.latitude))))";

                $shops = $this->getAllShopsCommonData($inputs, true, $language_id);

                $returnData['all_shops'] = $this->shopDistanceFilterNew($shops);
                $config = ConfigModel::where('key', ConfigModel::SHOW_RECENT_COMPLETED_SHOPS)->first();
                if(!empty($config) && $config->value == true){
                    $returnData['recent_completed_shops'] = $this->shopRecentCompleted($main_country, $category_id, $distance, $is_suggest_category, true, $language_id);
                }else{
                    $returnData['recent_completed_shops'] = null;
                }
                $returnData['banner_images'] = $this->shopBanners($main_country, $category_id);
                $returnData['recent_portfolio'] = $this->shopRecentUpdatedPost($main_country, $category_id, $distance, $is_suggest_category, $language_id, $coordinate);
                Log::info('End code get all shops');
                return $this->sendSuccessResponse(Lang::get('messages.shop.success'), 200, $returnData);
            } else {
                Log::info('End code for the get shop list');
                return $this->sendSuccessResponse(Lang::get('messages.user.token_expired'), 401);
            }
        } catch (\Exception $e) {
            Log::info('Exception in get all shops');
            Log::info($e);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function getAuthAllShops(Request $request)
    {
        try {
            Log::info('Start code get all shops');
            $user = Auth::user();
            $inputs = $request->all();

            if ($user) {

                $category_id = isset($inputs['category_id']) ? $inputs['category_id'] : 0;
                $language_id = $inputs['language_id'] ?? PostLanguage::ENGLISH;
                $category = DB::table('category')->select('category_type_id')->where('id', $category_id)->first();

                $is_suggest_category = $category && $category->category_type_id == CategoryTypes::CUSTOM ? 1 : 0;

                $latitude = $inputs['latitude'] ?? '';
                $longitude = $inputs['longitude'] ?? '';

                $coordinate = $longitude . ',' . $latitude;
                $main_country = getCountryFromLatLong($inputs['latitude'], $inputs['longitude']);

                $distance = "(6371 * acos(cos(radians(" . $inputs['latitude'] . "))
                            * cos(radians(addresses.latitude))
                        * cos(radians(addresses.longitude)
                    - radians(" . $inputs['longitude'] . "))
                    + sin(radians(" . $inputs['latitude'] . "))
                        * sin(radians(addresses.latitude))))";

                $shops = $this->getAllShopsCommonData($inputs, false, $language_id);



                $returnData['all_shops'] = $this->shopDistanceFilterNew($shops);
                $config = ConfigModel::where('key', ConfigModel::SHOW_RECENT_COMPLETED_SHOPS)->first();
                if(!empty($config) && $config->value == true){
                    $returnData['recent_completed_shops'] = $this->shopRecentCompleted($main_country, $category_id, $distance, $is_suggest_category, false, $language_id);
                }else{
                    $returnData['recent_completed_shops'] = null;
                }
                $returnData['banner_images'] = $this->shopBanners($main_country, $category_id);
                $returnData['recent_portfolio'] = $this->shopRecentUpdatedPost($main_country, $category_id, $distance, $is_suggest_category, $language_id, $coordinate);
                $returnData['user_details'] = ['id' => $user->id, 'status_id' => $user->status_id, 'chat_status' => $user->chat_status];
                Log::info('End code get all shops');
                return $this->sendSuccessResponse(Lang::get('messages.shop.success'), 200, $returnData);
            } else {
                Log::info('End code for the get shop list');
                return $this->sendSuccessResponse(Lang::get('messages.user.token_expired'), 401);
            }
        } catch (\Exception $e) {
            Log::info('Exception in get all shops');
            Log::info($e);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function searchShop(Request $request)
    {
        try {
            Log::info('Start code for get all shops');
            $inputs = $request->all();
            $search = $inputs['keyword'];
            $language_id = $inputs['language_id'] ?? PostLanguage::ENGLISH;
            $validation = $this->shopProfileValidator->validateSearchShop($inputs);

            if ($validation->fails()) {
                Log::info('End code for get all shops');
                return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
            }

            $user = Auth::user();

            $returnData = [];
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

            $shopsPopularQuery = DB::table('shops')->leftjoin('addresses', function ($join) {
                $join->on('shops.id', '=', 'addresses.entity_id')
                    ->where('addresses.entity_type_id', EntityTypes::SHOP);
            })
                ->join('category', function ($join) {
                    $join->on('shops.category_id', '=', 'category.id')
                        ->whereNull('category.deleted_at');
                })
                ->leftjoin('shop_detail_languages', function ($join) use ($language_id) {
                    $join->on('shops.id', '=', 'shop_detail_languages.shop_id')
                        ->where('shop_detail_languages.key', ShopDetailLanguage::SPECIALITY_OF)
                        ->where('shop_detail_languages.entity_type_id', EntityTypes::SHOP)
                        ->where('shop_detail_languages.language_id', $language_id);
                })
                ->leftjoin('cities', function ($join) {
                    $join->on('addresses.city_id', '=', 'cities.id');
                })
                ->leftjoin('shop_images', function ($join) {
                    $join->on('shops.id', '=', 'shop_images.shop_id')
                        ->where('shop_images.shop_image_type', ShopImagesTypes::THUMB)
                        ->whereNull('shop_images.deleted_at');
                })
                ->leftjoin('users_detail', function ($join) {
                    $join->on('shops.user_id', '=', 'users_detail.user_id');
                })
                ->select(
                    'shops.id',
                    'shops.main_name',
                    'shops.shop_name',
                    //'shops.speciality_of',
                    'shops.is_discount',
                    'cities.name as city_name',
                    'shops.user_id as user_id',
                    'shop_images.image as thumbnail_image',
                    'shop_images.id as thumbnail_image_id',
                    DB::raw('IFNULL(shop_detail_languages.value, shops.speciality_of) as speciality_of')
                )
                ->selectSub(function ($q) {
                    $q->select(DB::raw('count(reviews.id) as count'))->from('reviews')->whereNull('reviews.deleted_at')->where('reviews.entity_type_id', EntityTypes::SHOP)->whereRaw("`reviews`.`entity_id` = `shops`.`id`");
                }, 'reviews_count')
                ->selectSub(function ($q) {
                    $q->select(DB::raw('count(shop_followers.id) as count'))->from('shop_followers')->whereNull('shop_followers.deleted_at')->whereRaw("`shop_followers`.`shop_id` = `shops`.`id`");
                }, 'shop_followers_count')
                ->selectSub(function ($q) {
                    $q->select(DB::raw('avg(reviews.rating) as rating'))->from('reviews')->whereNull('reviews.deleted_at')->where('reviews.entity_type_id', EntityTypes::SHOP)->whereRaw("`reviews`.`entity_id` = `shops`.`id`");
                }, 'rating')
                ->where('status_id', Status::ACTIVE);

            $shopsDistanceQuery = DB::table('shops')->leftjoin('addresses', function ($join) {
                $join->on('shops.id', '=', 'addresses.entity_id')
                    ->where('addresses.entity_type_id', EntityTypes::SHOP);
            })
                ->join('category', function ($join) {
                    $join->on('shops.category_id', '=', 'category.id')
                        ->whereNull('category.deleted_at');
                })
                ->leftjoin('shop_detail_languages', function ($join) use ($language_id) {
                    $join->on('shops.id', '=', 'shop_detail_languages.shop_id')
                        ->where('shop_detail_languages.key', ShopDetailLanguage::SPECIALITY_OF)
                        ->where('shop_detail_languages.entity_type_id', EntityTypes::SHOP)
                        ->where('shop_detail_languages.language_id', $language_id);
                })
                ->leftjoin('cities', function ($join) {
                    $join->on('addresses.city_id', '=', 'cities.id');
                })
                ->leftjoin('shop_images', function ($join) {
                    $join->on('shops.id', '=', 'shop_images.shop_id')
                        ->where('shop_images.shop_image_type', ShopImagesTypes::THUMB)
                        ->whereNull('shop_images.deleted_at');
                })
                ->leftjoin('users_detail', function ($join) {
                    $join->on('shops.user_id', '=', 'users_detail.user_id');
                })
                ->select(
                    'shops.id',
                    'shops.main_name',
                    'shops.shop_name',
                    //'shops.speciality_of',
                    'shops.is_discount',
                    'cities.name as city_name',
                    'shops.user_id as user_id',
                    'shop_images.image as thumbnail_image',
                    'shop_images.id as thumbnail_image_id',
                    DB::raw('IFNULL(shop_detail_languages.value, shops.speciality_of) as speciality_of')
                )
                ->selectSub(function ($q) {
                    $q->select(DB::raw('avg(reviews.rating) as rating'))->from('reviews')->whereNull('reviews.deleted_at')->where('reviews.entity_type_id', EntityTypes::SHOP)->whereRaw("`reviews`.`entity_id` = `shops`.`id`");
                }, 'rating')
                ->where('status_id', Status::ACTIVE);

            if (isset($search) && $search != "") {
                $shopsPopularQuery = $shopsPopularQuery->where(function ($q) use ($search) {
                    $q->where('shops.main_name', 'LIKE', "%{$search}%")
                        ->orWhere('shops.shop_name', 'LIKE', "%{$search}%")
                        ->orWhere('shops.speciality_of', 'LIKE', "%{$search}%");
                });

                $shopsDistanceQuery = $shopsDistanceQuery->where(function ($q) use ($search) {
                    $q->where('shops.main_name', 'LIKE', "%{$search}%")
                        ->orWhere('shops.shop_name', 'LIKE', "%{$search}%")
                        ->orWhere('shops.speciality_of', 'LIKE', "%{$search}%");
                });
            }

            $distance = "(6371 * acos(cos(radians(" . $inputs['latitude'] . "))
            * cos(radians(addresses.latitude))
            * cos(radians(addresses.longitude)
            - radians(" . $inputs['longitude'] . "))
            + sin(radians(" . $inputs['latitude'] . "))
            * sin(radians(addresses.latitude))))";

            $shopsPopular = $shopsPopularQuery->orderby('reviews_count', 'desc')
                ->orderby('shop_followers_count', 'desc')
                ->orderby('distance')
                ->selectRaw("{$distance} AS distance")
                ->selectRaw("{$limitByPackage} AS priority")
                ->whereRaw("{$distance} <= {$limitByPackage}")
                ->paginate(config('constant.pagination_count'), "*", "popular_page");

            $shopsDistance = $shopsDistanceQuery->orderby('distance')
                ->selectRaw("{$distance} AS distance")
                ->selectRaw("{$limitByPackage} AS priority")
                ->whereRaw("{$distance} <= {$limitByPackage}")
                ->paginate(config('constant.pagination_count'), "*", "distance_page");


            if ($user) {
                $searchData = SearchHistory::where('keyword', $search)->where('user_id', $user->id)->where('entity_type_id', EntityTypes::SHOP)->first();
                if ($searchData) {
                    SearchHistory::where('id', $searchData->id)->update([
                        'keyword' => $search,
                        'entity_type_id' => EntityTypes::SHOP,
                        'user_id' => $user->id
                    ]);
                } else {
                    SearchHistory::create([
                        'keyword' => $search,
                        'entity_type_id' => EntityTypes::SHOP,
                        'user_id' => $user->id
                    ]);
                }
            }

            $returnData['popular'] = $this->shopDistanceFilterNew($shopsPopular);
            $returnData['distance'] = $this->shopDistanceFilterNew($shopsDistance);
            Log::info('End code get all shops');
            return $this->sendSuccessResponse(Lang::get('messages.shop.success'), 200, $returnData);
        } catch (\Exception $e) {
            Log::info('Exception in get all shops');
            Log::info($e);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function getAllShopsCommonData($inputs, $isOrderDistance = false, $language_id)
    {
        $user = Auth::user();
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

        $category_id = isset($inputs['category_id']) ? $inputs['category_id'] : 0;

        $category = DB::table('category')->select('category_type_id')->where('id', $category_id)->first();

        $is_suggest_category = $category && $category->category_type_id == CategoryTypes::CUSTOM ? 1 : 0;
        $main_country = getCountryFromLatLong($inputs['latitude'], $inputs['longitude']);
        $returnData = [];

        $distance = "(6371 * acos(cos(radians(" . $inputs['latitude'] . "))
                    * cos(radians(addresses.latitude))
                * cos(radians(addresses.longitude)
            - radians(" . $inputs['longitude'] . "))
            + sin(radians(" . $inputs['latitude'] . "))
                * sin(radians(addresses.latitude))))";


        $limitByPackage = DB::raw('case when `shops`.`expose_distance` IS NOT NULL then `shops`.`expose_distance`
                when `users_detail`.package_plan_id = ' . PackagePlan::BRONZE . ' then ' . $bronzePlanKm . '
                when `users_detail`.package_plan_id = ' . PackagePlan::SILVER . ' then ' . $silverPlanKm . '
                when `users_detail`.package_plan_id = ' . PackagePlan::GOLD . ' then ' . $goldPlanKm . '
                when `users_detail`.package_plan_id = ' . PackagePlan::PLATINIUM . ' then ' . $platiniumPlanKm . '
                else 40 end ');

        $shopsQuery = DB::table('shops')->leftjoin('addresses', function ($join) {
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
            ->leftjoin('cities', function ($join) {
                $join->on('addresses.city_id', '=', 'cities.id');
            })
            ->leftjoin('shop_images', function ($join) {
                $join->on('shops.id', '=', 'shop_images.shop_id')
                    ->where('shop_images.shop_image_type', ShopImagesTypes::THUMB)
                    ->whereNull('shop_images.deleted_at');
            })
            ->join('category', function ($join) {
                $join->on('shops.category_id', '=', 'category.id')
                    ->whereNull('category.deleted_at');
            })
            ->select(
                'shops.id',
                'shops.main_name',
                'shops.shop_name',
                //'shops.speciality_of',
                'shops.is_discount',
                'cities.name as city_name',
                'shops.user_id as user_id',
                'shop_images.image as thumbnail_image',
                'shop_images.id as thumbnail_image_id',
                DB::raw('IFNULL(shop_detail_languages.value, shops.speciality_of) as speciality_of')
            )
            ->selectSub(function ($q) {
                $q->select(DB::raw('count(reviews.id) as count'))->from('reviews')->whereNull('reviews.deleted_at')->where('reviews.entity_type_id', EntityTypes::SHOP)->whereRaw("`reviews`.`entity_id` = `shops`.`id`");
            }, 'reviews_count')
            ->selectSub(function ($q) {
                $q->select(DB::raw('count(shop_followers.id) as count'))->from('shop_followers')->whereNull('shop_followers.deleted_at')->whereRaw("`shop_followers`.`shop_id` = `shops`.`id`");
            }, 'shop_followers_count')
            ->where(function($query) use ($user){
                if ($user) {
                    $query->whereRaw("shops.id NOT IN (select shop_id from shop_block_histories where user_id = {$user->id} AND is_block = 1)");
                }
            })
            ->where('addresses.main_country', $main_country)
            ->where('shops.status_id', Status::ACTIVE)
            ->whereNull('shops.deleted_at')
            ->groupBy('shops.id');

        if ($category_id != 0) {
            $shopsQuery = $shopsQuery->where('category_id', $category_id);
        } else {
            $shopsQuery = $shopsQuery->where('category.category_type_id', CategoryTypes::SHOP);
        }

        if ($isOrderDistance === false) {
            $shopsQuery = $shopsQuery->orderby('reviews_count', 'desc')
                ->orderby('shop_followers_count', 'desc');
        }
        $shops =  $shopsQuery->orderby('distance')
            ->selectRaw("{$distance} AS distance")
            ->selectRaw("{$limitByPackage} AS priority")
            ->whereRaw("{$distance} <= {$limitByPackage}")
            ->paginate(config('constant.portfolio_pagination_count_shop'), "*", "all_shops_page");

        return $shops;
    }

    public function loadPortfolio(Request $request, $id)
    {
        $inputs = $request->all();
        try {
            $shopPost = ShopPost::findOrFail($id);
            ShopPost::whereId($id)->update(['views_count' => DB::raw('views_count + 1')]);
            return $this->sendSuccessResponse(Lang::get('messages.shop.success'), 200, $shopPost);
        } catch (\Exception $e) {
            Log::info($e);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function latestShop(Request $request)
    {
        $user = Auth::user();
        $inputs = $request->all();
        try {
            if ($user) {
                $latitude = $inputs['latitude'] ?? '';
                $longitude = $inputs['longitude'] ?? '';
                $per_page = $inputs['per_page'] ?? 9;
                $coordinate = $longitude . ',' . $latitude;

                Config::set('shop_detail_per_page',$per_page);

                $shopQuery = Shop::leftjoin('addresses', function ($join) {
                    $join->on('shops.id', '=', 'addresses.entity_id')
                        ->where('addresses.entity_type_id', EntityTypes::SHOP);
                })
                    ->leftjoin('linked_social_profiles','linked_social_profiles.shop_id','shops.id')
                    ->select(
                        'shops.*',
                        'linked_social_profiles.is_valid_token as is_valid_token',
                        DB::raw('IFNULL(linked_social_profiles.social_id, "") as is_connect')
                    );

                if (isset($inputs['latitude']) && isset($inputs['longitude'])) {
                    $shopQuery = $shopQuery->addSelect(DB::raw("IFNULL(CAST(ROUND(st_distance_sphere(POINT(addresses.longitude,addresses.latitude), POINT( $coordinate)) * .001,2) AS DECIMAL(21,2)) , '') as shop_distance"));
                }

                $shopExists = $shopQuery->where('shops.user_id',$user->id)->orderBy('shops.created_at','DESC')->first();

                if ($shopExists) {
                    $shopExists->user_details = ['id' => $user->id, 'status_id' => $user->status_id, 'chat_status' => $user->chat_status, 'is_admin_access' => ($user->is_admin_access==1)?true:false, 'name' => $user->name];

                    $userInstaProfile = LinkedSocialProfile::where('user_id', $user->id)->where('shop_id',$shopExists->id)->where('social_type', LinkedSocialProfile::Instagram)->first();
                    $shopExists->insta_social_name = (!empty($userInstaProfile) && !empty($userInstaProfile->social_name)) ? $userInstaProfile->social_name : '';

                    $postCollection = collect($shopExists->portfolio_images->getCollection())->map(function ($value) use ($shopExists,$user) {
                        $value->shop_distance = $shopExists->shop_distance;
                        $value->is_admin_access = ($user->is_admin_access==1)?true:false;
                        return $value;
                    });

                    $shopExists = $shopExists->toArray();
                    $shopExists['portfolio_images']['data'] = $postCollection;
                    return $this->sendSuccessResponse(Lang::get('messages.shop.edit-success'), 200, $shopExists);
                } else {
                    return $this->sendSuccessResponse(Lang::get('messages.shop.empty'), 402);
                }
            } else {
                return $this->sendSuccessResponse(Lang::get('messages.user.token_expired'), 401);
            }
        } catch (\Exception $e) {
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

}
