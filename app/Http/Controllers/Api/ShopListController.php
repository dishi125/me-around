<?php

namespace App\Http\Controllers\Api;

use App\Models\Category;
use App\Models\CategoryLanguage;
use App\Models\Shop;
use App\Models\Banner;
use App\Models\Config;
use App\Models\Status;
use App\Models\Address;
use App\Models\HashTag;
use App\Models\ShopPost;
use App\Models\ShopImages;
use App\Models\UserDetail;
use App\Models\CreditPlans;
use App\Models\EntityTypes;
use App\Models\PackagePlan;
use App\Models\PostLanguage;
use App\Models\UserHiddenCategory;
use Illuminate\Http\Request;
use App\Models\CategoryTypes;
use App\Models\ShopImagesTypes;
use App\Models\MultipleShopPost;
use App\Models\SavedHistoryTypes;
use App\Models\ShopDetailLanguage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Models\RequestBookingStatus;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ShopListController extends Controller
{
    public function index(Request $request)
    {
        try {
            $user = Auth::user();
            $inputs = $request->all();

            $validator = Validator::make($request->all(), [
                'latitude' => 'required',
                'longitude' => 'required',
            ], [], [
                'latitude' => 'Location',
                'longitude' => 'Location',
            ]);
            if ($validator->fails()) {
                return $this->sendCustomErrorMessage($validator->errors()->toArray(), 422);
            }

            $display = $inputs['display'] ?? 'all';
            $main_country = $inputs['country'] ?? "KR";
            $category_id = isset($inputs['category_id']) ? $inputs['category_id'] : 0;
            $language_id = $inputs['language_id'] ?? PostLanguage::ENGLISH;
            $search = $inputs['search'] ?? '';

            $distance = "(6371 * acos(cos(radians(" . $inputs['latitude'] . "))
                            * cos(radians(addresses.latitude))
                        * cos(radians(addresses.longitude)
                    - radians(" . $inputs['longitude'] . "))
                    + sin(radians(" . $inputs['latitude'] . "))
                        * sin(radians(addresses.latitude))))";


            $returnData['all_shops'] = (object)[];
            $returnData['recent_completed_shops'] = null;
            $returnData['banner_images'] = [];
            $returnData['recent_portfolio'] = (object)[];
            $returnData['user_details'] = (object)[];

            $viewOrder = (isset($inputs['type']) && !empty($inputs['type']) && $inputs['type'] == 'distance') ? true : false;

            if ($display == 'all' || $display == 'all_shops') {
                $shops = $this->getAllShopsCommonData($inputs, $viewOrder, $language_id, $search);
                $returnData['all_shops'] = $this->shopDistanceFilterNew($shops);
            }

            if ($display == 'all' || $display == 'recent_completed_shops') {
                $config = Config::where('key', Config::SHOW_RECENT_COMPLETED_SHOPS)->first();
                if(!empty($config) && $config->value == true){
                    $returnData['recent_completed_shops'] = $this->shopRecentCompleted($main_country, $category_id, $distance, $viewOrder, $language_id, $inputs);
                }else{
                    $returnData['recent_completed_shops'] = null;
                }
            }

            if ($display == 'all') {
                $returnData['banner_images'] = $this->shopBanners($main_country, $category_id);
            }

            if ($display == 'all' || $display == 'recent_portfolio') {
                $returnData['recent_portfolio'] = $this->shopRecentUpdatedPost($main_country, $category_id, $distance, $language_id, $inputs);
            }

            $returnData['user_details'] = ($user) ? ['id' => $user->id, 'status_id' => $user->status_id, 'chat_status' => $user->chat_status] : (object)[];
            return $this->sendSuccessResponse(Lang::get('messages.shop.success'), 200, $returnData);
        } catch (\Exception $e) {
            Log::info($e);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function listAllShops(Request $request){
        try {
            $user = Auth::user();
            $inputs = $request->all();

            $validator = Validator::make($request->all(), [
                'latitude' => 'required',
                'longitude' => 'required',
            ], [], [
                'latitude' => 'Location',
                'longitude' => 'Location',
            ]);
            if ($validator->fails()) {
                return $this->sendCustomErrorMessage($validator->errors()->toArray(), 422);
            }

            $display = $inputs['display'] ?? 'all';
            $main_country = $inputs['country'] ?? "KR";
            $category_id = isset($inputs['category_id']) ? $inputs['category_id'] : 0;
            $language_id = $inputs['language_id'] ?? PostLanguage::ENGLISH;
            $search = $inputs['search'] ?? '';

            $distance = "(6371 * acos(cos(radians(" . $inputs['latitude'] . "))
                            * cos(radians(addresses.latitude))
                        * cos(radians(addresses.longitude)
                    - radians(" . $inputs['longitude'] . "))
                    + sin(radians(" . $inputs['latitude'] . "))
                        * sin(radians(addresses.latitude))))";


            $returnData['all_shops'] = (object)[];
//            $returnData['recent_completed_shops'] = null;
//            $returnData['banner_images'] = [];
//            $returnData['recent_portfolio'] = (object)[];
//            $returnData['user_details'] = (object)[];

            $viewOrder = (isset($inputs['type']) && !empty($inputs['type']) && $inputs['type'] == 'distance') ? true : false;

            if ($display == 'all' || $display == 'all_shops') {
                $shops = $this->getAllShopsCommonData($inputs, $viewOrder, $language_id, $search);
                $returnData['all_shops'] = $this->shopDistanceFilterNew($shops);
            }

            /*if ($display == 'all' || $display == 'recent_completed_shops') {
                $config = Config::where('key', Config::SHOW_RECENT_COMPLETED_SHOPS)->first();
                if(!empty($config) && $config->value == true){
                    $returnData['recent_completed_shops'] = $this->shopRecentCompleted($main_country, $category_id, $distance, $viewOrder, $language_id, $inputs);
                }else{
                    $returnData['recent_completed_shops'] = null;
                }
            }

            if ($display == 'all') {
                $returnData['banner_images'] = $this->shopBanners($main_country, $category_id);
            }

            if ($display == 'all' || $display == 'recent_portfolio') {
                $returnData['recent_portfolio'] = $this->shopRecentUpdatedPost($main_country, $category_id, $distance, $language_id, $inputs);
            }

            $returnData['user_details'] = ($user) ? ['id' => $user->id, 'status_id' => $user->status_id, 'chat_status' => $user->chat_status] : (object)[];*/
            return $this->sendSuccessResponse(Lang::get('messages.shop.success'), 200, $returnData);
        } catch (\Exception $e) {
            Log::info($e);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function getAllSearchResults(Request $request)
    {
        try {
            $user = Auth::user();
            $inputs = $request->all();

            $validator = Validator::make($request->all(), [
                'latitude' => 'required',
                'longitude' => 'required',
            ], [], [
                'latitude' => 'Location',
                'longitude' => 'Location',
            ]);
            if ($validator->fails()) {
                return $this->sendCustomErrorMessage($validator->errors()->toArray(), 422);
            }

            $display = $inputs['display'] ?? 'all';
            $main_country = $inputs['country'] ?? "KR";
            $language_id = $inputs['language_id'] ?? PostLanguage::ENGLISH;
            $search = $inputs['search'] ?? '';
            $viewOrder = (isset($inputs['type']) && !empty($inputs['type']) && $inputs['type'] == 'distance') ? true : false;

            $shops = $this->getAllShopsCommonData($inputs, $viewOrder, $language_id, $search);

            $hospitalController = new \App\Http\Controllers\Api\HospitalController;

            $tagsQuery = HashTag::join('hash_tag_mappings', function ($join) {
                $join->on('hash_tag_mappings.hash_tag_id', '=', 'hash_tags.id')
                    ->where('hash_tag_mappings.entity_type_id', HashTag::SHOP_POST);
            })
                ->join('shop_posts', function ($join) {
                    $join->on('shop_posts.id', '=', 'hash_tag_mappings.entity_id');
                })
                ->join('shops', 'shop_posts.shop_id', 'shops.id')
                ->where(function ($query) use ($user) {
                    if ($user) {
                        $query->whereRaw("shops.id NOT IN (select shop_id from shop_block_histories where user_id = {$user->id} AND is_block = 1)");
                    }
                })
                ->select(
                    'hash_tags.*',
                    DB::raw('COUNT(hash_tag_mappings.id) as total_posts'),
                    'shop_posts.id as post_id',
                    DB::raw('group_concat(shop_posts.id) as shop_posts')
                );
            if ($search) {
                $tagsQuery = $tagsQuery->where('hash_tags.tags', 'LIKE', "$search%");
            }
            $tags = $tagsQuery->orderBy('total_posts', 'DESC')
                ->groupBy('hash_tags.id')
                ->paginate(config('constant.post_pagination_count'), "*", "all_hashtag_page");


            collect($tags->getCollection())->map(function ($value) use ($user) {
                $postIds = explode(',', $value->shop_posts);
                $shopPost = DB::table('shop_posts')->leftjoin('user_saved_history', function ($join) {
                    $join->on('user_saved_history.entity_id', '=', 'shop_posts.id')
                        ->where('user_saved_history.saved_history_type_id', SavedHistoryTypes::SHOP)->where('user_saved_history.is_like', 1);
                })
                    ->join('shops', 'shop_posts.shop_id', 'shops.id')
                    ->where(function ($query) use ($user) {
                        if ($user) {
                            $query->whereRaw("shops.id NOT IN (select shop_id from shop_block_histories where user_id = {$user->id} AND is_block = 1)");
                        }
                    })
                    ->whereIn('shop_posts.id', $postIds)
                    ->select(
                        'shop_posts.*',
                        DB::raw('COUNT(user_saved_history.id) as total_saved')
                    )
                    ->orderBy('total_saved', 'DESC')
                    ->orderBy('shop_posts.created_at', 'DESC')
                    ->groupBy('shop_posts.id')
                    ->first();

                if ($shopPost) {
                    $value->shop_max_id = $shopPost->id;
                    if ($shopPost->type == 'video') {
                        $value->post_image = filterDataUrl($shopPost->video_thumbnail);
                    } else {
                        $value->post_image = filterDataUrl($shopPost->post_item);
                        $value->post_item_thumbnail = filterDataThumbnailUrl($shopPost->post_item);
                    }
                } else {
                    $value->post_image = '';
                }
                return $value;
            });

            $returnData['tags'] = $tags;
            $returnData['hospital'] = $hospitalController->getHospitalFuncation($inputs, $viewOrder);
            $returnData['all_shops'] = $this->shopDistanceFilterNew($shops);
            return $this->sendSuccessResponse(Lang::get('messages.shop.success'), 200, $returnData);
        } catch (\Exception $e) {
            Log::info($e);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function getAllShopsCommonData($inputs, $isOrderDistance = false, $language_id, $search = '', $category_ids = [])
    {
        $user = Auth::user();
        $latitude = $inputs['latitude'] ?? '';
        $longitude = $inputs['longitude'] ?? '';

        $coordinate = $longitude . ',' . $latitude;

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

        //$main_country = getCountryFromLatLong($inputs['latitude'], $inputs['longitude']);
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

        $shopsQuery = DB::table('shops');
        if (isset($inputs['search_type']) && $inputs['search_type']=='following'){
            $shopsQuery = $shopsQuery->join('shop_followers', function ($join) use($user){
                $join->on('shops.id', '=', 'shop_followers.shop_id')
                    ->where('shop_followers.user_id', $user->id)
                    ->whereNull('shop_followers.deleted_at');
            });
        }
        $shopsQuery = $shopsQuery->leftjoin('addresses', function ($join) {
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
                DB::raw('IFNULL(shop_detail_languages.value, shops.speciality_of) as speciality_of'),
                DB::raw("IFNULL(CAST(ROUND(st_distance_sphere(POINT(addresses.longitude,addresses.latitude), POINT( $coordinate)) * .001,2) AS DECIMAL(21,2)) ,'') as shop_distance"),
                'users_detail.name'
            )
            ->selectSub(function ($q) {
                $q->select(DB::raw('count(reviews.id) as count'))->from('reviews')->whereNull('reviews.deleted_at')->where('reviews.entity_type_id', EntityTypes::SHOP)->whereRaw("`reviews`.`entity_id` = `shops`.`id`");
            }, 'reviews_count')
            ->selectSub(function ($q) {
                $q->select(DB::raw('count(shop_followers.id) as count'))->from('shop_followers')->whereNull('shop_followers.deleted_at')->whereRaw("`shop_followers`.`shop_id` = `shops`.`id`");
            }, 'shop_followers_count')
            ->where(function ($query) use ($user) {
                if ($user) {
                    $query->whereRaw("shops.id NOT IN (select shop_id from shop_block_histories where user_id = {$user->id} AND is_block = 1)");
                }
            })
            //->where('addresses.main_country', $main_country)
            ->where('shops.status_id', Status::ACTIVE)
            ->whereNull('shops.deleted_at')
            ->groupBy('shops.id');

        if (empty($category_ids) && $category_id != 0) {
            $shopsQuery = $shopsQuery->where('category_id', $category_id);
        }
        elseif (!empty($category_ids)){
            $shopsQuery = $shopsQuery->whereIn('category_id', $category_ids);
        }
        else {
            $shopsQuery = $shopsQuery->where('category.category_type_id', CategoryTypes::SHOP);
        }

        if (!empty($search)) {
            $shopsQuery = $shopsQuery->where(function ($q) use ($search) {
                $q->where('shops.main_name', 'LIKE', "%{$search}%")
                    ->orWhere('shops.shop_name', 'LIKE', "%{$search}%");
                    //->orWhere('users_detail.name', 'LIKE', "%{$search}%");
            });
        }

        if ($isOrderDistance === false) {
            $shopsQuery = $shopsQuery->orderby('reviews_count', 'desc')
                ->orderby('shop_followers_count', 'desc');
        }
        $shopsQuery =  $shopsQuery->orderby('distance')
            ->selectRaw("{$distance} AS distance")
            ->selectRaw("{$limitByPackage} AS priority");

        if (empty($search)) {
            $shopsQuery =  $shopsQuery->whereRaw("{$distance} <= {$limitByPackage}");
        }

        $shops =  $shopsQuery->paginate(config('constant.portfolio_pagination_count_shop'), "*", "all_shops_page");

        return $shops;
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

    public function shopRecentCompleted($main_country, $category_id, $distance, $isOrderDistance = false, $language_id, $inputs)
    {
        $user = Auth::user();
        $latitude = $inputs['latitude'] ?? '';
        $longitude = $inputs['longitude'] ?? '';

        $coordinate = $longitude . ',' . $latitude;

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
            ->where(function ($query) use ($user) {
                if ($user) {
                    $query->whereRaw("shops.id NOT IN (select shop_id from shop_block_histories where user_id = {$user->id} AND is_block = 1)");
                }
            })
            ->where('addresses.main_country', $main_country)
            ->where('requested_customer.entity_type_id', EntityTypes::SHOP)
            ->where('shops.status_id', Status::ACTIVE)
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
                DB::raw('IFNULL(shop_detail_languages.value, shops.speciality_of) as speciality_of'),
                DB::raw("IFNULL(CAST(ROUND(st_distance_sphere(POINT(addresses.longitude,addresses.latitude), POINT( $coordinate)) * .001,2) AS DECIMAL(21,2)) ,'') as shop_distance")
            )
            ->selectRaw("{$distance} AS distance")
            ->selectRaw("{$limitByPackage} AS priority")
            ->whereRaw("{$distance} <= {$limitByPackage}")
            ->paginate(config('constant.portfolio_pagination_count_shop'), "*", "recent_completed_shops_page");

        $recentCompleted = $this->shopDistanceFilterNew($recentCompletedShops);

        return $recentCompleted;
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

    public function shopRecentUpdatedPost($main_country, $category_id, $distance, $language_id, $inputs)
    {
        $user = Auth::user();
        $latitude = $inputs['latitude'] ?? '';
        $longitude = $inputs['longitude'] ?? '';

        $coordinate = $longitude . ',' . $latitude;

        $per_page = $inputs['per_page'] ?? config('constant.portfolio_pagination_count_shop');
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
            ->whereNull('shops.deleted_at');

        if ($category_id != 0) {
            $recentPortfolioQuery = $recentPortfolioQuery->where('shops.category_id', $category_id);
        } else {
            $recentPortfolioQuery = $recentPortfolioQuery->where('category.category_type_id', CategoryTypes::SHOP);
        }

        $recentPortfolio = $recentPortfolioQuery->orderBy('shop_posts.post_order_date', 'desc')->orderBy('shop_posts.created_at', 'desc')
            ->orderby('distance')
            ->select(
                'shop_posts.*',
                'shops.id as shop_id',
                'shops.business_link',
                'shops.booking_link',
                'shops.another_mobile',
                DB::raw('IFNULL(shop_detail_languages.value, shops.speciality_of) as speciality_of'),
                DB::raw("IFNULL(CAST(ROUND(st_distance_sphere(POINT(addresses.longitude,addresses.latitude), POINT( $coordinate)) * .001,2) AS DECIMAL(21,2)) ,'') as shop_distance")
            )
            ->selectRaw("{$distance} AS distance")
            ->selectRaw("{$limitByPackage} AS priority")
            ->whereRaw("{$distance} <= {$limitByPackage}")
            ->paginate($per_page, "*", "recent_portfolio_page");

        $recentPostUpdated = $this->shopDistanceFilter($recentPortfolio, 1);

        return $recentPostUpdated;
    }

    public function shopDistanceFilter($shops, $is_post = 0)
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

            /* if(isset($shop['video_thumbnail']) && !empty($shop['video_thumbnail'])){
                $shop['video_thumbnail'] = (!str_contains($shop['video_thumbnail'], 'amazonaws')) ? Storage::disk('s3')->url($shop['video_thumbnail']): $shop['video_thumbnail'];
                $defaultItem[0]['video_thumbnail'] = $shop['video_thumbnail'];
            }else{
                $defaultItem[0]['video_thumbnail'] = '';
            }

            $defaultItem[0]['id'] = $shop['id'];
            $defaultItem[0]['type'] = $shop['type'];

            $defaultItem[0]['post_item'] = (!empty($shop['post_item']) && !str_contains($shop['post_item'], 'amazonaws')) ? Storage::disk('s3')->url($shop['post_item']) : $shop['post_item'];
            $defaultItem[0]['post_item'] = !empty($shop['post_item']) ?? NULL;

            if($shop['is_multiple'] == true){
                $posts = MultipleShopPost::where('shop_posts_id',$shop['id'])->get();
                $shop['multiple_shop_posts'] = collect($defaultItem)->merge($posts)->values();
            }else{
                $shop['multiple_shop_posts'] = $defaultItem;
            }

            $hash_tags = [];
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

            $shop['hash_tags'] = $hash_tags;
            */
            if ($is_post == 1) {
                //$shopData = Shop::find($shop['shop_id']);
                $shopData = DB::table('shops')->whereId($shop['shop_id'])->first();
                /*$shop['shop_name'] = $shopData->shop_name ? $shopData->shop_name : '';
                $shop['main_name'] = $shopData->main_name ? $shopData->main_name : '';
                $shop['speciality_of'] = $shop['speciality_of'] ? $shop['speciality_of'] : '';

                $thumbnail = ShopImages::where('shop_id', $shopData->id)->where('shop_image_type', ShopImagesTypes::THUMB)->select(['id', 'image'])->first();
                $shop['shop_thumbnail'] = $thumbnail ? $thumbnail->image :  ""; */

                // Workplace images
                /*  $worplace_images = ShopImages::where('shop_id', $shopData->id)->where('shop_image_type', ShopImagesTypes::WORKPLACE)->get(['id', 'image']);
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
                /*  $worplace_images = ShopImages::where('shop_id', $shop['id'])->where('shop_image_type', ShopImagesTypes::WORKPLACE)->get(['id', 'image']);
                $images = [];
                if (empty($worplace_images)) {
                    $shop['workplace_images'] = $images;
                } else {
                    $shop['workplace_images'] = $worplace_images;
                } */
            }
            $shop['user_id'] = $user_id;

            /* $followers = DB::table('shop_followers')->where('shop_id', $shop['id'])->where(function ($q) use ($user) {
                if ($user) {
                    $q->where('user_id', $user->id);
                }
            })->count();
            $shop['is_follow'] = $followers > 0 ? 1 : 0; */

            //$userdetail = UserDetail::where('user_id',$user_id)->first();
            // $planDetail = CreditPlans::where('entity_type_id',EntityTypes::SHOP)->where('package_plan_id',$userdetail->package_plan_id)->first();
            $rating = DB::table('reviews')->where('entity_type_id', EntityTypes::SHOP)->where('entity_id', $shop['id'])->avg('rating');

            // dd($planDetail->km);
            // $km = $planDetail ? $planDetail->km : 0;

            /*  $shop['post_item'] = (!empty($shop['post_item']) && !str_contains($shop['post_item'], 'amazonaws')) ? Storage::disk('s3')->url($shop['post_item']) : $shop['post_item'];
            $shop['post_item'] = !empty($shop['post_item']) ?? NULL; */
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
            if (count($main_profile_images) == 0) {
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
            $shop['is_saved_in_history'] = $savedCount > 0 ? true : false;

            $filteredShop[] = $shop;
        }
        $paginateData['data'] = array_values($filteredShop);
        return $paginateData;
    }

    public function getShopDetails(Request $request)
    {
        try {
            $inputs = $request->all();

            $validator = Validator::make($request->all(), [
                'user_id' => 'required',
            ], [], [
                'user_id' => 'User ID',
            ]);
            if ($validator->fails()) {
                return $this->sendCustomErrorMessage($validator->errors()->toArray(), 422);
            }


            $query = Shop::join('user_entity_relation', 'shops.id', '=', 'user_entity_relation.entity_id')
                ->where('user_entity_relation.entity_type_id', EntityTypes::SHOP)
                ->where('user_entity_relation.user_id', $inputs['user_id']);

            $shops = $query->select('shops.*')->get();
            $shops->makeHidden(['rating', 'work_complete', 'portfolio', 'reviews_list', 'main_profile_images', 'workplace_images', 'portfolio_images', 'best_portfolio', 'business_licence', 'identification_card']);
            $shopData = [];

            if (!empty($shops)) {
                foreach ($shops as $shop) {
                    $temp = [];
                    $temp['shop_name'] = $shop->shop_name;
                    $temp['main_name'] = $shop->main_name;
                    $temp['category_id'] = $shop->category_id;
                    $temp['category_name'] = $shop->category_name;
                    $temp['category_icon'] = $shop->category_icon;
                    $temp['status_id'] = $shop->status_id;
                    $temp['status_name'] = $shop->status_name;
                    $temp['reviews'] = $shop->reviews;
                    $temp['followers'] = $shop->followers;
                    $temp['work_complete'] = $shop->work_complete;
                    $temp['portfolio'] = $shop->portfolio;
                    $temp['is_follow'] = $shop->is_follow;
                    $temp['thumbnail_image'] = $shop->thumbnail_image;
                    $temp['speciality_of'] = $shop->speciality_of;
                    $temp['address'] = $shop->address;
                    $temp['id'] = $shop->id;
                    array_push($shopData, $temp);
                }
            }

            $userDetails = UserDetail::where('user_id', $inputs['user_id'])->first();
            $data['shops'] = $shopData;
            $data['username'] = $userDetails->name ?? '';

            return $this->sendSuccessResponse(Lang::get('messages.shop.success'), 200, $data);
        } catch (\Exception $e) {
            Log::info($e);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function mapPageList(Request $request)
    {
        try {
            $user = Auth::user();
            $inputs = $request->all();

            $validator = Validator::make($request->all(), [
                'latitude' => 'required',
                'longitude' => 'required',
            ], [], [
                'latitude' => 'Location',
                'longitude' => 'Location',
            ]);
            if ($validator->fails()) {
                return $this->sendCustomErrorMessage($validator->errors()->toArray(), 422);
            }

            $display = $inputs['display'] ?? 'all';
            $main_country = $inputs['country'] ?? "KR";
            $language_id = $inputs['language_id'] ?? PostLanguage::ENGLISH;
            $search = $inputs['search'] ?? '';

            //Categories list - start
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
            $query = Category::where('category.status_id', Status::ACTIVE)
                ->where('category.category_type_id', EntityTypes::SHOP)
                ->where('category.parent_id', 0)
                ->where(function ($query) use ($hiddenCategory) {
                    if (!empty($hiddenCategory)) {
                        $query->whereNotIn("category.id",$hiddenCategory);
                    }
                });
            if (!empty($main_country)) {
                $query = $query->join('category_settings', 'category_settings.category_id', 'category.id')
                    ->where('category_settings.country_code', $main_country)
                    ->where('category_settings.is_show',1)
                    ->orderBy('category_settings.order', 'ASC');
                $query = $query->select('category.name', 'category.logo', 'category.id', 'category_settings.is_show', 'category_settings.order', 'category_settings.menu_key', 'category_settings.is_hidden');
            } else {
                $query = $query->select('category.name', 'category.logo', 'category.id', 'category.is_show', 'category.order', 'category.menu_key', 'category.is_hidden')
                    ->where('category.is_show',1)
                    ->orderBy('category.order', 'ASC');
            }
            $filterCategory = $query->get();
            if (!count($filterCategory)) {
                $filterCategory = Category::where('status_id', Status::ACTIVE)
                    ->where('category_type_id', EntityTypes::SHOP)
                    ->where('parent_id', 0)
                    ->where('category.is_show',1)
                    ->where(function ($query) use ($hiddenCategory) {
                        if (!empty($hiddenCategory)) {
                            $query->whereNotIn("category.id",$hiddenCategory);
                        }
                    })
                    ->select('name', 'logo', 'id', 'is_show', 'order','menu_key', 'is_hidden')
                    ->orderBy('order', 'ASC');
                $filterCategory = $filterCategory->get();
            }
            $filterCategory = $filterCategory->makeHidden(['sub_categories', 'parent_name', 'status_name', 'category_type_name']);
            $filterCategory = collect($filterCategory)->map(function ($item) use ($language_id) {
                $category_language = CategoryLanguage::where('category_id', $item->id)->where('post_language_id', $language_id)->first();
                $item->category_language_name = $category_language && $category_language->name != NULL ? $category_language->name : $item->name;
                return $item;
            });
            $returnData['category'] = $filterCategory;
            //Categories list - end

            $distance = "(6371 * acos(cos(radians(" . $inputs['latitude'] . "))
                            * cos(radians(addresses.latitude))
                        * cos(radians(addresses.longitude)
                    - radians(" . $inputs['longitude'] . "))
                    + sin(radians(" . $inputs['latitude'] . "))
                        * sin(radians(addresses.latitude))))";

            $returnData['all_shops'] = (object)[];

            $viewOrder = (isset($inputs['type']) && !empty($inputs['type']) && $inputs['type'] == 'distance') ? true : false;

            if ($display == 'all' || $display == 'all_shops') {
                $categories = $filterCategory->toArray();
                $categoryIds = array_column($categories,'id');
                $shops = $this->getAllShopsCommonData($inputs, $viewOrder, $language_id, $search, $categoryIds);
                $returnData['all_shops'] = $this->shopDistanceFilterNew($shops);
            }

            return $this->sendSuccessResponse(Lang::get('messages.shop.success'), 200, $returnData);
        } catch (\Exception $e) {
            Log::info($e);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

}
