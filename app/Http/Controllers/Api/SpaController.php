<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Config;
use App\Models\EntityTypes;
use App\Models\PostLanguage;
use App\Models\SavedHistoryTypes;
use App\Models\Shop;
use App\Models\ShopImagesTypes;
use App\Models\ShopInfo;
use App\Models\ShopPost;
use App\Models\ShopPriceCategory;
use App\Models\ShopPrices;
use App\Models\Status;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class SpaController extends Controller
{
    public function listShops(Request $request)
    {
        try {
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

            $category_id = Category::where('name','Spa')->pluck('id')->first();

            $distance = "(6371 * acos(cos(radians(" . $inputs['latitude'] . "))
                        * cos(radians(addresses.latitude))
                    * cos(radians(addresses.longitude)
                - radians(" . $inputs['longitude'] . "))
                + sin(radians(" . $inputs['latitude'] . "))
                    * sin(radians(addresses.latitude))))";

            $all_shops = Shop::leftjoin('shop_images', function ($join) {
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
                    'shops.another_mobile',
                    'shops.category_id',
                    'shops.status_id',
                    'shop_images.image as thumbnail_image',
                    'shop_images.id as thumbnail_image_id'
                );

            if(isset($inputs['search']) && $inputs['search']!=""){
                $all_shops = $all_shops->where(function ($q) use ($inputs) {
                    $q->where('shops.main_name', 'LIKE', "%{$inputs['search']}%")
                        ->orWhere('shops.shop_name', 'LIKE', "%{$inputs['search']}%");
                });
            }

            $all_shops = $all_shops->groupBy('shops.id')
                ->orderby('distance')
                ->selectRaw("{$distance} AS distance")
                ->paginate(config('constant.spa_shops_list_pagination_count'), "*", "all_shops_page");

            collect($all_shops->getCollection())->map(function ($value) {
                $shopPost = ShopPost::where('shop_id',$value->id)->select('id','shop_id','type','display_video','post_item','video_thumbnail','created_at','updated_at')->first();
                $value->shop_posts = $shopPost->multiple_shop_posts;

                $shop_price_cats = ShopPriceCategory::where('shop_id',$value->id)->pluck('id')->toArray();
                $shop_price = ShopPrices::whereIn('shop_price_category_id',$shop_price_cats)->where('main_price_display',1)->first();
                $value->price_category_name = isset($shop_price) ? $shop_price->shop_price_category_name : null;
                $value->price_item_name = isset($shop_price) ? $shop_price->name : null;
                $value->shop_price = isset($shop_price) ? $shop_price->price : null;

                return $value;
            });

            $all_shops->makeHidden(['category_id','status_id','thumbnail_image_id','category_icon','status_name','work_complete','portfolio','reviews','reviews_list','followers','main_profile_images','workplace_images','portfolio_images','is_follow','deeplink','is_block','instagram_status','address','rating','category_name']);

            return $this->sendSuccessResponse(Lang::get('messages.shop.success'), 200, $all_shops);
        } catch (\Exception $e) {
            Log::info($e);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function shopProfile(Request $request)
    {
        try {
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

            $distance = "(6371 * acos(cos(radians(" . $inputs['latitude'] . "))
                        * cos(radians(addresses.latitude))
                    * cos(radians(addresses.longitude)
                - radians(" . $inputs['longitude'] . "))
                + sin(radians(" . $inputs['latitude'] . "))
                    * sin(radians(addresses.latitude))))";

            $shop = Shop::leftjoin('shop_images', function ($join) {
                $join->on('shops.id', '=', 'shop_images.shop_id')
                    ->where('shop_images.shop_image_type', ShopImagesTypes::THUMB)
                    ->whereNull('shop_images.deleted_at');
            })
                ->leftjoin('addresses', function ($join) {
                    $join->on('shops.id', '=', 'addresses.entity_id')
                        ->where('addresses.entity_type_id', EntityTypes::SHOP);
                })
                ->where('shops.id',$inputs['shop_id'])
                ->where('shops.status_id', Status::ACTIVE)
                ->select(
                    'shops.id',
                    'shops.main_name',
                    'shops.shop_name',
                    'shops.speciality_of',
                    'shops.another_mobile',
                    'shops.category_id',
                    'shops.status_id',
                    'shop_images.image as thumbnail_image',
                    'shop_images.id as thumbnail_image_id'
                )
//                ->groupBy('shops.id')
//                ->orderby('distance')
                ->selectRaw("{$distance} AS distance")
                ->first();

            /*collect($shop)->map(function ($value) {
//                $shopPost = ShopPost::where('shop_id',$value->id)->select('id','shop_id','type','display_video','post_item','video_thumbnail','created_at','updated_at')->first();
//                $value->shop_posts = $shopPost->multiple_shop_posts;
//
//                $shop_price_cats = ShopPriceCategory::where('shop_id',$value->id)->pluck('id')->toArray();
//                $shop_price = ShopPrices::whereIn('shop_price_category_id',$shop_price_cats)->where('main_price_display',1)->first();
//                $value->price_category_name = isset($shop_price) ? $shop_price->shop_price_category_name : null;
//                $value->price_item_name = isset($shop_price) ? $shop_price->name : null;
//                $value->shop_price = isset($shop_price) ? $shop_price->price : null;

                return $value;
            });*/

            $shop->makeHidden(['category_id','status_id','thumbnail_image_id','thumbnail_image','category_icon','status_name','work_complete','portfolio','reviews','reviews_list','followers','workplace_images','is_follow','deeplink','is_block','instagram_status','rating','category_name']);

            return $this->sendSuccessResponse("Shop profile.", 200, $shop);
        } catch (\Exception $e) {
            Log::info($e);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function shopInfo(Request $request)
    {
        try {
            $inputs = $request->all();
            $shopInfo = ShopInfo::where('shop_id',$inputs['shop_id'])->select('title_1','title_2','title_3','title_4','title_5','title_6')->first();

            return $this->sendSuccessResponse("Shop Info.", 200, $shopInfo);
        } catch (\Exception $e) {
            Log::info($e);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function priceListShop(Request $request, $id)
    {
        $inputs = $request->all();
        try {
            $shop = Shop::find($id);
            if($shop){
                $shopItems = ShopPriceCategory::where('shop_id',$id)->get();
                return $this->sendSuccessResponse(Lang::get('messages.shop-price-category.list-success'), 200, $shopItems);
            }else {
                return $this->sendSuccessResponse(Lang::get('messages.shop.empty'), 402);
            }
        } catch (\Exception $e) {
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function shopPosts(Request $request)
    {
        try {
            $inputs = $request->all();
            $portfolio_images = ShopPost::where('shop_id', $inputs['shop_id'])->orderBy('id','desc')->paginate(config('constant.spa_posts_pagination_count'), "*", "portfolio_images_page");

            return $this->sendSuccessResponse("Shop posts.", 200, $portfolio_images);
        } catch (\Exception $e) {
            Log::info($e);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

}
