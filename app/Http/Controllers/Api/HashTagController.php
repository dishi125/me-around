<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Validators\UserValidator;
use Illuminate\Http\Request;
use App\Models\HashTag;
use App\Models\HashTagMapping;
use App\Models\ShopPost;
use App\Models\SavedHistoryTypes;
use Validator;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Log;
use DB;
use App\Models\CreditPlans;
use App\Models\PackagePlan;
use App\Models\EntityTypes;
use App\Models\Status;
use Auth;

class HashTagController extends Controller
{
    public function __construct()
    {
        $this->userValidator = new UserValidator();
    }
    public function index(Request $request)
    {
        $inputs = $request->all();
       /* $validator = Validator::make($request->all(), [
            'tag' => 'required',
        ], [], [
            'tag' => 'Tag',
        ]);

        if ($validator->fails()) {
            return $this->sendCustomErrorMessage($validator->errors()->toArray(), 422);
        }*/

        $language_id = $request->has('language_id') ? $inputs['language_id'] : 4;
        $validation = $this->userValidator->validateTags($inputs,$language_id);
        if ($validation->fails()) {
            return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
        }

        $tag = $inputs['tag'];
        $tags = HashTag::where('tags','LIKE',"$tag%")->pluck('tags');
        return $this->sendSuccessResponse(Lang::get('messages.hashtag.get'), 200, $tags);
    }

    public function listHashTag(Request $request)
    {
        $inputs = $request->all();
        try {

            $language_id = $request->has('language_id') ? $inputs['language_id'] : 4;
            $validation = $this->userValidator->validateTags($inputs,$language_id);
            if ($validation->fails()) {
                return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
            }
            $user = Auth::user();

            $tag = $inputs['tag'];
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
                        ->where(function($query) use ($user){
                            if ($user) {
                                $query->whereRaw("shops.id NOT IN (select shop_id from shop_block_histories where user_id = {$user->id} AND is_block = 1)");
                            }
                        })
                        ->where('shops.status_id', Status::ACTIVE)
                        ->where('hash_tags.tags','LIKE',"$tag%")
                        ->select(
                            'hash_tags.*',
                            DB::raw('COUNT(hash_tag_mappings.id) as total_posts'),
                            'shop_posts.id as post_id',
                            DB::raw('group_concat(shop_posts.id) as shop_posts')
                        )
                        ->orderBy('total_posts','DESC')
                        ->groupBy('hash_tags.id')
                        ->limit(20)
                        ->get();

                collect($tags)->map(function ($value) use ($user) {
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
                                    ->where(function($query) use ($user){
                                        if ($user) {
                                            $query->whereRaw("shops.id NOT IN (select shop_id from shop_block_histories where user_id = {$user->id} AND is_block = 1)");
                                        }
                                    })
                                    ->where('shops.status_id', Status::ACTIVE)
                                    ->whereIn('shop_posts.id',$postIds)
                                    ->select(
                                        'shop_posts.*',
                                        DB::raw('COUNT(user_saved_history.id) as total_saved')
                                    )
                                    ->orderBy('total_saved','DESC')
                                    ->orderBy('shop_posts.created_at','DESC')
                                    ->groupBy('shop_posts.id')
                                    ->first();

                    if($shopPost){
                        $value->shop_max_id = $shopPost->id;
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

            return $this->sendSuccessResponse(Lang::get('messages.hashtag.get'), 200, $tags);

        } catch (\Exception $e) {
            Log::info($e);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function hashTagDetail(Request $request)
    {
        $inputs = $request->all();
        $user = Auth::user();
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

            $order = $inputs['order'] ?? 'popular';
            $screen = $inputs['screen'] ?? 'allpost';
            $latitude = $inputs['latitude'] ?? '';
            $longitude = $inputs['longitude'] ?? '';
            $per_page = $inputs['per_page'] ?? 8;
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
                            ->where(function($query) use ($user){
                                if ($user) {
                                    $query->whereRaw("shops.id NOT IN (select shop_id from shop_block_histories where user_id = {$user->id} AND is_block = 1)");
                                }
                            })
                            ->where('shops.status_id', Status::ACTIVE)
                            ->where('hash_tag_mappings.hash_tag_id',$inputs['tag_id'])
                            ->where('hash_tag_mappings.entity_type_id',HashTag::SHOP_POST)
                            ->select(
                                'shop_posts.*',
                                DB::raw("IFNULL( CAST(ROUND(st_distance_sphere(POINT(addresses.longitude,addresses.latitude), POINT( $coordinate)) * .001,2) AS DECIMAL(21,2)) , '') as shop_distance")
                            );

            if($order == 'popular'){
                $hashTagQuery = $hashTagQuery->leftjoin('user_saved_history', function ($join) {
                    $join->on('user_saved_history.entity_id', '=', 'shop_posts.id')
                        ->where('user_saved_history.saved_history_type_id',SavedHistoryTypes::SHOP)->where('user_saved_history.is_like',1);
                })
                ->addSelect(DB::raw('COUNT(user_saved_history.id) as total_saved'))
                ->orderBy('total_saved','DESC');
            }

            // MearoundTab

            if($screen == 'mearound'){
                $creditPlans = CreditPlans::where('entity_type_id', EntityTypes::SHOP)->get();
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

                $distance = "(6371 * acos(cos(radians(" . $inputs['latitude'] . "))
                            * cos(radians(addresses.latitude))
                        * cos(radians(addresses.longitude)
                    - radians(" . $inputs['longitude'] . "))
                    + sin(radians(" . $inputs['latitude'] . "))
                        * sin(radians(addresses.latitude))))";

                $limitByPackage = DB::raw('case when `shops`.`expose_distance` IS NOT NULL then `shops`.`expose_distance`
                    when `users_detail`.package_plan_id = '. PackagePlan::BRONZE .' then '.$bronzePlanKm.'
                    when `users_detail`.package_plan_id = '. PackagePlan::SILVER .' then '.$silverPlanKm.'
                    when `users_detail`.package_plan_id = '. PackagePlan::GOLD .' then '.$goldPlanKm.'
                    when `users_detail`.package_plan_id = '. PackagePlan::PLATINIUM .' then '.$platiniumPlanKm.'
                    else 40 end ');

                $hashTagQuery = $hashTagQuery
                    ->leftjoin('users_detail', function ($join) {
                        $join->on('shops.user_id', '=', 'users_detail.user_id');
                    })
                    ->where('shops.status_id',Status::ACTIVE)
                    ->selectRaw("{$distance} AS distance")
                    ->selectRaw("{$limitByPackage} AS priority")
                    ->whereRaw("{$distance} <= {$limitByPackage}");
            }
            // MearoundTab

            $hashTagQuery = $hashTagQuery->groupBy('shop_posts.id')
                ->orderBy('shop_posts.post_order_date','DESC')
                ->orderBy('shop_posts.created_at','DESC')
                ->paginate($per_page,"*","portfolio_page");

            $data['tags'] = $tagData->tags;
            $data['portfolio'] = $hashTagQuery;
            return $this->sendSuccessResponse(Lang::get('messages.hashtag.get'), 200, $data);
        } catch (\Exception $e) {
            Log::info($e);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

}
