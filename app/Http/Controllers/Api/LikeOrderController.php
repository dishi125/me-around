<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GeneralSettings;
use App\Models\ShopPost;
use App\Models\ShopPostLikes;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class LikeOrderController extends Controller
{
    public function todayPostReal(Request $request){
        $user = Auth::user();

        try {
            $timezone = $request->timezone ?? 'UTC';
            $startDate = Carbon::now()->timezone($timezone)->subDay()->format('Y-m-d');
            $endDate = Carbon::now()->timezone($timezone)->addDay()->format('Y-m-d');

            $query = ShopPost::join('shops as s', 's.id', 'shop_posts.shop_id')
                ->leftjoin('shop_post_likes', function ($join) use($user){
                    $join->on('shop_post_likes.shop_post_id', '=', 'shop_posts.id');
                })
                ->leftjoin('users', 'users.id', 's.user_id')
                ->whereNull('s.deleted_at')
                ->whereNotNull('shop_posts.insta_link')
                ->whereBetween('post_order_date', [$startDate, $endDate])
                ->where('s.count_days', '>', 0)
                ->select(
                    'shop_posts.*',
                    's.main_name',
                    's.shop_name',
                    'users.connect_instagram',
                    DB::raw('case when `shop_post_likes`.`id` IS NOT NULL then 1 else 0 end AS is_complete'),
                    's.id as shop_id'
                )
                ->groupBy('shop_posts.id')
                ->get();

            $data = $query->map(function ($item) use($timezone){
                $update_date = "";
                if(isset($item->post_order_date) && !empty($item->post_order_date)) {
                    $dateShow = Carbon::createFromFormat('Y-m-d H:i:s', Carbon::parse($item->post_order_date), "UTC")->setTimezone($timezone)->toDateTimeString();
                    $update_date = Carbon::parse($dateShow)->format('Y-m-d H:i:s');
                }
                $item->date = $update_date;

                $edited = "";
                $allLikes = ShopPostLikes::where('shop_id', $item->shop_id)->get();
                $lastLikeDate = $allLikes->max('created_at');
                if (isset($lastLikeDate)) {
                    $dateShow = Carbon::createFromFormat('Y-m-d H:i:s', Carbon::parse($lastLikeDate), "UTC")->setTimezone($timezone)->toDateTimeString();
                    $date = Carbon::parse($dateShow)->format('Y-m-d H:i:s');
                    $edited = $date;
                }
                $item->complete_date = $edited;

//                $item->images = $item->multiple_shop_posts;
                $post_item = $item->post_item ?? '';
                $video_thumbnail = $item->video_thumbnail ?? '';
                if(isset($video_thumbnail) && !empty($video_thumbnail)){
                    $default_image = $video_thumbnail;
                }else{
                    $default_image = filterDataThumbnailUrl($post_item);
                }
                $item->image = $default_image;

                return $item;
            });

            $data->makeHidden(['views_count','post_item','type','video_thumbnail','created_at','updated_at','is_multiple','is_admin_read','instagram_post_id','post_order_date','is_like_order_admin_read','multiple_shop_posts','shop_data','saved_count','is_saved_in_history','location','post_item_thumbnail','shop_thumbnail','workplace_images','is_follow','hash_tags','display_created_at','deeplink','display_updated_at']);

            return $this->sendSuccessResponse(Lang::get('messages.general.success'), 200, $data);
        } catch (\Exception $e) {
            Log::info($e);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

}
