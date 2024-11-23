<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\Category;
use App\Models\CategoryLanguage;
use App\Models\CategoryTypes;
use App\Models\CreditPlans;
use App\Models\EntityTypes;
use App\Models\HashTag;
use App\Models\LinkedSocialProfile;
use App\Models\PackagePlan;
use App\Models\SavedHistoryTypes;
use App\Models\Shop;
use App\Models\ShopFollowers;
use App\Models\ShopImages;
use App\Models\ShopImagesTypes;
use App\Models\ShopPost;
use App\Models\Status;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class TattooController extends Controller
{
    public function index(Request $request)
    {
        $inputs = $request->all();

        $validator = Validator::make($request->all(), [
            'latitude' => 'required',
            'longitude' => 'required',
            'category' => 'required',
        ], [], [
            'latitude' => 'Location',
            'longitude' => 'Location',
        ]);
        if ($validator->fails()) {
            return $this->sendCustomErrorMessage($validator->errors()->toArray(), 422);
        }

        try {
            $tags = HashTag::join('hash_tag_mappings', function ($join) {
                $join->on('hash_tag_mappings.hash_tag_id', '=', 'hash_tags.id')
                    ->where('hash_tag_mappings.entity_type_id', HashTag::SHOP_POST);
                })
                ->join('shop_posts', function ($join) {
                    $join->on('shop_posts.id', '=', 'hash_tag_mappings.entity_id');
                })
                ->join('shops', 'shop_posts.shop_id', 'shops.id')
                ->join('category', function ($join) {
                    $join->on('shops.category_id', '=', 'category.id')
                        ->whereNull('category.deleted_at');
                })
                ->where('category.name',$inputs['category'])
                ->whereNull('shops.deleted_at')
                ->where('shops.status_id', Status::ACTIVE)
                ->where('hash_tags.is_show', 1)
                ->select(
                    'hash_tags.id',
                    'hash_tags.tags',
                    DB::raw('COUNT(hash_tag_mappings.id) as total_posts'),
                    DB::raw('group_concat(shop_posts.id) as shop_posts')
                )
                ->orderBy('total_posts','DESC')
                ->groupBy('hash_tags.id')
                ->paginate(config('constant.tattoo_pagination_count'), "*", "all_hashtags_page");

            collect($tags->getCollection())->map(function ($value) {
                $postIds = explode(',',$value->shop_posts);
                $shopPost = DB::table('shop_posts')->leftjoin('user_saved_history', function ($join) {
                    $join->on('user_saved_history.entity_id', '=', 'shop_posts.id')
                        ->where('user_saved_history.saved_history_type_id',SavedHistoryTypes::SHOP)->where('user_saved_history.is_like',1);
                    })
                    ->join('shops', 'shop_posts.shop_id', 'shops.id')
                    ->join('category', function ($join) {
                        $join->on('shops.category_id', '=', 'category.id')
                            ->whereNull('category.deleted_at');
                    })
                    ->where('shops.status_id', Status::ACTIVE)
                    ->whereIn('shop_posts.id',$postIds)
                    ->select(
                        'shop_posts.id',
                        'shop_posts.type',
                        'shop_posts.video_thumbnail',
                        'shop_posts.post_item',
                        DB::raw('COUNT(user_saved_history.id) as total_saved')
                    )
                    ->orderBy('total_saved','DESC')
                    ->orderBy('shop_posts.created_at','DESC')
                    ->groupBy('shop_posts.id')
                    ->first();

                if($shopPost){
                    if($shopPost->type == 'video'){
                        $value->post_image = filterDataUrl($shopPost->video_thumbnail);
                    }else{
                        $value->post_image = filterDataUrl($shopPost->post_item);
                        $value->post_item_thumbnail = filterDataThumbnailUrl($shopPost->post_item);
                    }
                }else{
                    $value->post_image = '';
                }
                return $value;
            });
            $tags->makeHidden(['shop_posts']);

            $category_id = Category::where('name',$inputs['category'])->pluck('id')->first();

            $distance = "(6371 * acos(cos(radians(" . $inputs['latitude'] . "))
                        * cos(radians(addresses.latitude))
                    * cos(radians(addresses.longitude)
                - radians(" . $inputs['longitude'] . "))
                + sin(radians(" . $inputs['latitude'] . "))
                    * sin(radians(addresses.latitude))))";

            $all_shops = DB::table('shops')->leftjoin('shop_images', function ($join) {
                $join->on('shops.id', '=', 'shop_images.shop_id')
                    ->where('shop_images.shop_image_type', ShopImagesTypes::THUMB)
                    ->whereNull('shop_images.deleted_at');
                })
                ->leftjoin('addresses', function ($join) {
                    $join->on('shops.id', '=', 'addresses.entity_id')
                        ->where('addresses.entity_type_id', EntityTypes::SHOP);
                })
                ->where('shops.category_id',$category_id)
                ->where('shops.status_id', Status::ACTIVE)
                ->select(
                    'shops.id',
                    'shops.main_name',
                    'shops.shop_name',
                    'shops.speciality_of',
                    'shop_images.image as thumbnail_image'
                )
                ->groupBy('shops.id')
                ->orderby('distance')
                ->selectRaw("{$distance} AS distance")
                ->paginate(config('constant.tattoo_pagination_count'), "*", "all_shops_page");
            collect($all_shops->getCollection())->map(function ($value) {
                $value->thumbnail_image = ($value->thumbnail_image!=null) ? Storage::disk('s3')->url($value->thumbnail_image) : "";
                return $value;
            });

            $data['hashtags_data'] = $tags;
            $data['shops_data'] = $all_shops;
            return $this->sendSuccessResponse("Home page.", 200, $data);
        } catch (\Exception $e) {
            Log::info($e);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function hashtagsList(Request $request)
    {
        $inputs = $request->all();

        try {
            $tags = HashTag::join('hash_tag_mappings', function ($join) {
                $join->on('hash_tag_mappings.hash_tag_id', '=', 'hash_tags.id')
                    ->where('hash_tag_mappings.entity_type_id', HashTag::SHOP_POST);
                })
                ->join('shop_posts', function ($join) {
                    $join->on('shop_posts.id', '=', 'hash_tag_mappings.entity_id');
                })
                ->join('shops', 'shop_posts.shop_id', 'shops.id')
                ->join('category', function ($join) {
                    $join->on('shops.category_id', '=', 'category.id')
                        ->whereNull('category.deleted_at');
                })
                ->where('category.name',$inputs['category'])
                ->whereNull('shops.deleted_at')
                ->where('shops.status_id', Status::ACTIVE)
                ->where('hash_tags.is_show', 1)
                ->select(
                    'hash_tags.id',
                    'hash_tags.tags',
                    DB::raw('COUNT(hash_tag_mappings.id) as total_posts'),
                    DB::raw('group_concat(shop_posts.id) as shop_posts')
                )
                ->orderBy('total_posts','DESC')
                ->groupBy('hash_tags.id')
                ->paginate(config('constant.tattoo_pagination_count'), "*", "all_hashtags_page");

            collect($tags->getCollection())->map(function ($value) {
                $postIds = explode(',',$value->shop_posts);
                $shopPost = DB::table('shop_posts')->leftjoin('user_saved_history', function ($join) {
                    $join->on('user_saved_history.entity_id', '=', 'shop_posts.id')
                        ->where('user_saved_history.saved_history_type_id',SavedHistoryTypes::SHOP)->where('user_saved_history.is_like',1);
                })
                    ->join('shops', 'shop_posts.shop_id', 'shops.id')
                    ->join('category', function ($join) {
                        $join->on('shops.category_id', '=', 'category.id')
                            ->whereNull('category.deleted_at');
                    })
                    ->where('shops.status_id', Status::ACTIVE)
                    ->whereIn('shop_posts.id',$postIds)
                    ->select(
                        'shop_posts.id',
                        'shop_posts.type',
                        'shop_posts.video_thumbnail',
                        'shop_posts.post_item',
                        DB::raw('COUNT(user_saved_history.id) as total_saved')
                    )
                    ->orderBy('total_saved','DESC')
                    ->orderBy('shop_posts.created_at','DESC')
                    ->groupBy('shop_posts.id')
                    ->first();

                if($shopPost){
                    if($shopPost->type == 'video'){
                        $value->post_image = filterDataUrl($shopPost->video_thumbnail);
                    }else{
                        $value->post_image = filterDataUrl($shopPost->post_item);
                        $value->post_item_thumbnail = filterDataThumbnailUrl($shopPost->post_item);
                    }
                }else{
                    $value->post_image = '';
                }
                return $value;
            });
            $tags->makeHidden(['shop_posts']);

            return $this->sendSuccessResponse("Tattoo hashtags.", 200, $tags);
        } catch (\Exception $e) {
            Log::info($e);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function shopsList(Request $request)
    {
        $inputs = $request->all();

        $validator = Validator::make($request->all(), [
            'latitude' => 'required',
            'longitude' => 'required',
            'category' => 'required',
        ], [], [
            'latitude' => 'Location',
            'longitude' => 'Location',
        ]);
        if ($validator->fails()) {
            return $this->sendCustomErrorMessage($validator->errors()->toArray(), 422);
        }

        try {
            $category_id = Category::where('name',$inputs['category'])->pluck('id')->first();

            $distance = "(6371 * acos(cos(radians(" . $inputs['latitude'] . "))
                        * cos(radians(addresses.latitude))
                    * cos(radians(addresses.longitude)
                - radians(" . $inputs['longitude'] . "))
                + sin(radians(" . $inputs['latitude'] . "))
                    * sin(radians(addresses.latitude))))";

            $all_shops = DB::table('shops')->leftjoin('shop_images', function ($join) {
                $join->on('shops.id', '=', 'shop_images.shop_id')
                    ->where('shop_images.shop_image_type', ShopImagesTypes::THUMB)
                    ->whereNull('shop_images.deleted_at');
                })
                ->leftjoin('addresses', function ($join) {
                    $join->on('shops.id', '=', 'addresses.entity_id')
                        ->where('addresses.entity_type_id', EntityTypes::SHOP);
                })
                ->where('shops.category_id',$category_id)
                ->where('shops.status_id', Status::ACTIVE)
                ->select(
                    'shops.id',
                    'shops.main_name',
                    'shops.shop_name',
                    'shops.speciality_of',
                    'shop_images.image as thumbnail_image'
                )
                ->groupBy('shops.id')
                ->orderby('distance')
                ->selectRaw("{$distance} AS distance")
                ->paginate(config('constant.tattoo_pagination_count'), "*", "all_shops_page");
            collect($all_shops->getCollection())->map(function ($value) {
                $value->thumbnail_image = ($value->thumbnail_image!=null) ? Storage::disk('s3')->url($value->thumbnail_image) : "";
                return $value;
            });

            return $this->sendSuccessResponse("Tattoo shops list.", 200, $all_shops);
        } catch (\Exception $e) {
            Log::info($e);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function hashtagDetail(Request $request)
    {
        $inputs = $request->all();
        try {
            $validator = Validator::make($request->all(), [
                'tag_id' => 'required',
                'latitude' => 'required',
                'longitude' => 'required',
            ], [], [
                'tag_id' => 'Tag ID',
            ]);

            if ($validator->fails()) {
                return $this->sendCustomErrorMessage($validator->errors()->toArray(), 422);
            }

            $latitude = $inputs['latitude'] ?? '';
            $longitude = $inputs['longitude'] ?? '';
            $coordinate = $longitude.','.$latitude;

            $tagData = HashTag::find($inputs['tag_id']);
            $hashTagQuery = ShopPost::join('hash_tag_mappings','hash_tag_mappings.entity_id','shop_posts.id')
                ->leftjoin('shops','shop_posts.shop_id','shops.id')
                ->whereNull('shops.deleted_at')
                ->leftjoin('addresses', function ($join) {
                    $join->on('shops.id', '=', 'addresses.entity_id')
                        ->where('addresses.entity_type_id', EntityTypes::SHOP);
                })
                ->join('category', function ($join) {
                    $join->on('shops.category_id', '=', 'category.id')
                        ->whereNull('category.deleted_at');
                })
                ->where('shops.status_id', Status::ACTIVE)
                ->where('hash_tag_mappings.hash_tag_id',$inputs['tag_id'])
                ->where('hash_tag_mappings.entity_type_id',HashTag::SHOP_POST)
                ->select(
                    'shop_posts.*',
                    DB::raw("IFNULL( CAST(ROUND(st_distance_sphere(POINT(addresses.longitude,addresses.latitude), POINT( $coordinate)) * .001,2) AS DECIMAL(21,2)) , '') as shop_distance")
                );

            $hashTagQuery = $hashTagQuery->groupBy('shop_posts.id')
                ->orderBy('shop_posts.post_order_date','DESC')
                ->orderBy('shop_posts.created_at','DESC')
                ->paginate(config('constant.pagination_count'),"*","portfolio_page");

            $data['tags'] = $tagData->tags;
            $data['portfolio'] = $hashTagQuery;
            return $this->sendSuccessResponse(Lang::get('messages.hashtag.get'), 200, $data);
        } catch (\Exception $e) {
            Log::info($e);
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

            $validator = Validator::make($request->all(), [
                'latitude' => 'required',
                'longitude' => 'required',
                'category' => 'required',
            ], [], [
                'latitude' => 'Location',
                'longitude' => 'Location',
            ]);
            if ($validator->fails()) {
                return $this->sendCustomErrorMessage($validator->errors()->toArray(), 422);
            }

            $recentFollowPortfolio = ["data" => []];

            $main_country = getCountryFromLatLong($inputs['latitude'], $inputs['longitude']);
            $timezone = get_nearest_timezone($inputs['latitude'], $inputs['longitude'], $main_country);
            $category_id = Category::where('name',$inputs['category'])->pluck('id')->first();
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

                DB::commit();
                Log::info('End code for the get user following shops');
                return $this->sendSuccessResponse(Lang::get('messages.home.success'), 200, $recentFollowPortfolio);
            } else {
                $shopCon = new \App\Http\Controllers\Api\ShopController;
                $recentFollowPortfolio = $shopCon->shopRecentUpdatedPost($main_country, $category_id, $distance, $is_suggest_category, $language_id, $coordinate);

                return $this->sendSuccessResponse(Lang::get('messages.home.success'), 200, $recentFollowPortfolio);
            }
        } catch (\Exception $e) {
            Log::info('Exception in get user following shops');
            Log::info($e);
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function getUserProfile(Request $request)
    {
        $user = Auth::user();
        try {
            Log::info('Start code for the get user profile');
            $inputs = $request->all();

            if($user) {
                $userSocialProfile = LinkedSocialProfile::where('user_id',$user->id)->where('social_type',LinkedSocialProfile::Facebook)->first();
                $userAppleProfile = LinkedSocialProfile::where('user_id',$user->id)->where('social_type',LinkedSocialProfile::Apple)->first();
                $data = [
                    'id' => $user->id,
                    'name' => $user->name,
                    'phone_code' => $user->phone_code,
                    'mobile' => $user->mobile,
                    'avatar' => $user->avatar,
                    'gender' => $user->gender,
                    'is_character_as_profile' => $user->is_character_as_profile,
                    'user_applied_card' => getUserAppliedCard($user->id),
                    'is_facebook_connect' => (!empty($userSocialProfile) && !empty($userSocialProfile->social_id)),
                    'is_apple_connect' => (!empty($userAppleProfile) && !empty($userAppleProfile->social_id)),
                    'mbti' => $user->mbti,
                    'is_admin_access' => $user->is_admin_access,
                    'is_support_user' => $user->is_support_user,
                ];

                $shops = Shop::where('user_id',$user->id)
                    ->select('id','main_name','shop_name','category_id','status_id')
                    ->get();
                $shops->makeHidden(['category_id','category_name','category_icon','status_id','status_name','reviews','followers','is_follow','is_block','instagram_status','address', 'rating', 'work_complete', 'portfolio', 'reviews_list', 'main_profile_images', 'workplace_images', 'portfolio_images', 'best_portfolio', 'business_licence', 'identification_card']);
                $data['shops'] = $shops;

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

                Log::info('End code for the get user profile');
                return $this->sendSuccessResponse(Lang::get('messages.user-profile.success'), 200, $data);
            }
            else{
                Log::info('End code for the get user profile');
                return $this->sendSuccessResponse(Lang::get('messages.user.token_expired'), 401);
            }
        } catch (\Exception $e) {
            Log::info('Exception in the get user profile');
            Log::info($e);
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function categoryList(Request $request)
    {
        $inputs = $request->all();
        try {
            $language_id = $inputs['language_id'] ?? 4;

            $category = Category::where('status_id', Status::ACTIVE)
                ->where('category_type_id', EntityTypes::SHOP)
                ->where('parent_id', 0)
                ->whereIn('name', ['Tattoo','Eyebrow'])
                ->select('name', 'logo', 'id')
                ->orderBy('order', 'ASC')
                ->get();
            $category = $category->makeHidden(['sub_categories', 'parent_name', 'status_name', 'category_type_name']);

            $category = collect($category)->map(function ($item) use ($language_id) {
                $category_language = CategoryLanguage::where('category_id', $item->id)->where('post_language_id', $language_id)->first();
                $item->category_language_name = $category_language && $category_language->name != NULL ? $category_language->name : $item->name;
                return $item;
            });
            $category = $category->values();

            return $this->sendSuccessResponse(Lang::get('messages.category.success'), 200, $category);
        }catch (\Exception $e){
            Log::info($e);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

}
