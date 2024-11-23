<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CreditPlans;
use App\Models\EntityTypes;
use App\Models\PackagePlan;
use App\Models\SavedHistoryTypes;
use App\Models\ShopPost;
use App\Models\ShopPostComment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Log;

class ShopPostController extends Controller
{
    public function allPosts(Request $request){
        try {
            $inputs = $request->all();
            $latitude = $inputs['latitude'] ?? '';
            $longitude = $inputs['longitude'] ?? '';
            $coordinate = $longitude . ',' . $latitude;
            $distance = "(6371 * acos(cos(radians(" . $inputs['latitude'] . "))
                                * cos(radians(addresses.latitude))
                            * cos(radians(addresses.longitude)
            - radians(" . $inputs['longitude'] . "))
            + sin(radians(" . $inputs['latitude'] . "))
                            * sin(radians(addresses.latitude))))";

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
            $limitByPackage = DB::raw('case when `s`.`expose_distance` IS NOT NULL then `s`.`expose_distance`
                when `users_detail`.package_plan_id = ' . PackagePlan::BRONZE . ' then ' . $bronzePlanKm . '
                when `users_detail`.package_plan_id = ' . PackagePlan::SILVER . ' then ' . $silverPlanKm . '
                when `users_detail`.package_plan_id = ' . PackagePlan::GOLD . ' then ' . $goldPlanKm . '
                when `users_detail`.package_plan_id = ' . PackagePlan::PLATINIUM . ' then ' . $platiniumPlanKm . '
                else 40 end ');

            $query = ShopPost::leftJoin('shops as s', 's.id', 'shop_posts.shop_id')
                ->leftjoin('user_saved_history', function ($join) {
                    $join->on('shop_posts.id', '=', 'user_saved_history.entity_id')->where('user_saved_history.saved_history_type_id', SavedHistoryTypes::SHOP);
                })
                ->leftjoin('addresses', function ($join) {
                    $join->on('s.id', '=', 'addresses.entity_id')->where('addresses.entity_type_id', EntityTypes::SHOP);
                })
                ->leftjoin('users_detail', function ($join) {
                    $join->on('s.user_id', '=', 'users_detail.user_id');
                })
                ->whereNull('s.deleted_at')
                ->select(
                'shop_posts.*',
//                's.main_name',
                's.mobile',
                's.count_days',
                's.is_regular_service',
                's.id as shop_id',
                'user_saved_history.id as saved_id',
                'user_saved_history.created_at as saved_created',
                DB::raw("IFNULL( CAST(ROUND(st_distance_sphere(POINT(addresses.longitude,addresses.latitude), POINT( $coordinate)) * .001,2) AS DECIMAL(21,2)) , '') as shop_distance")
                )
                ->selectRaw("{$distance} AS distance")
                ->selectRaw("{$limitByPackage} AS priority")
                ->orderBy('shop_posts.updated_at','DESC')
                ->paginate(config('constant.post_pagination_count'), "*", "all_shop_posts");

//            $query->makeHidden([]);
//            dd($query->toArray());
            return $this->sendSuccessResponse(Lang::get('messages.shop.shop-post-list'), 200, $query);
        } catch (\Exception $e) {
            Log::info($e);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function addComment(Request $request){
        $inputs = $request->all();
        $user = Auth::user();
        try {
           DB::beginTransaction();

           $comment = ShopPostComment::create([
               'shop_post_id' => $inputs['post_id'],
               'user_id' => $user->id,
               'comment' => $inputs['comment'],
           ]);

           DB::commit();
           return $this->sendSuccessResponse("Comment added.", 200);
        } catch (\Exception $e) {
            Log::info($e);
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

}
