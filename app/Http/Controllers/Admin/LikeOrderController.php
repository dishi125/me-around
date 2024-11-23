<?php

namespace App\Http\Controllers\Admin;

use App\Models\EntityTypes;
use App\Models\LinkedSocialProfile;
use App\Models\RequestForm;
use App\Models\RequestFormStatus;
use App\Models\Status;
use Auth;
use Carbon\Carbon;
use App\Models\ShopPost;
use Illuminate\Http\Request;
use App\Models\ShopPostLikes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use App\Models\Shop;

class LikeOrderController extends Controller
{
    public function index(Request $request)
    {
        $title = 'Like Order List';
        DB::table('shop_posts')->where('is_like_order_admin_read', 1)->update(['is_like_order_admin_read' => 0]);

        return view('admin.like-order.index', compact('title'));
    }

    public function getJsonData(Request $request)
    {
        $columns = array(
            0 => 's.main_name',
            1 => 's.main_name',
            2 => 'shop_posts.post_order_date',
            3 => 'shop_posts.post_order_date',
            6 => 'shop_posts.post_order_date',
        );
        DB::enableQueryLog();
        $filter = $request->input('filter');
        $limit = $request->input('length');
        $start = $request->input('start');
        $search = $request->input('search.value');
        $draw = $request->input('draw');
        $order = $columns[$request->input('order.0.column')];
        $dir = $request->input('order.0.dir');
        $adminTimezone = $this->getAdminUserTimezone();

        try {
            $data = [];
            $user = Auth::user();
            $query = ShopPost::join('shops as s', 's.id', 'shop_posts.shop_id')
                ->leftjoin('shop_post_likes', function ($join) use ($user) {
                    $join->on('shop_post_likes.shop_post_id', '=', 'shop_posts.id');
                    //->where('shop_post_likes.user_id',$user->id);
                })
                ->leftjoin('users', 'users.id', 's.user_id')
                ->whereNull('s.deleted_at')
                ->whereNotNull('shop_posts.insta_link')
                ->select(
                    'shop_posts.*',
                    's.main_name',
                    's.shop_name',
                    's.mobile',
                    'users.connect_instagram',
                    DB::raw('case when `shop_post_likes`.`id` IS NOT NULL then 1 else 0 end AS is_liked'),
                    's.count_days',
                    's.is_regular_service',
                    's.id as shop_id',
                    's.last_count_updated_at'
                )
                ->groupBy('shop_posts.id');

            if ($filter != 'all' && $filter != 'expired-user') {
                $startDate = Carbon::now()->timezone($adminTimezone)->subDay()->format('Y-m-d');
                $endDate = Carbon::now()->timezone($adminTimezone)->addDay()->format('Y-m-d');
                $query = $query->whereBetween('post_order_date', [$startDate, $endDate]);

                if ($filter == 'today-real') {
                    $query = $query->where('s.count_days', '>', 0);
                }
            }

            if ($filter == 'expired-user') {
                $query = $query->where('s.count_days', 0);
                $query = $query->whereNotNull('s.last_count_updated_at');
            }

            if (!empty($search)) {
                $query = $query->where(function ($q) use ($search) {
                    $q->where('s.main_name', 'LIKE', "%{$search}%")
                        ->orWhere('s.shop_name', 'LIKE', "%{$search}%")
                        ->orWhere('s.mobile', 'LIKE', "%{$search}%")
                        ->orWhere('shop_posts.description', 'LIKE', "%{$search}%")
                        ->orWhere('shop_posts.insta_link', 'LIKE', "%{$search}%");
                });
            }
            $totalData = count($query->get());
            $totalFiltered = $totalData;
            $postData = $query->offset($start)
                ->limit($limit)
                ->orderBy($order, $dir)
                ->get();

            $count = 0;

            foreach ($postData as $value) {
                $id = $value->id;
                $images = $edited = '';
                $allLikes = ShopPostLikes::where('shop_id', $value->shop_id)->get();
                $lastLikeDate = ShopPostLikes::where('shop_id', $value->shop_id)->where('shop_post_id', $value->id)->get()->max('created_at');
                if ($lastLikeDate) {
                    $edited = "<div style='color:green;'>" . $this->formatDateTimeCountryWise($lastLikeDate, $adminTimezone, 'Y-m-d H:i:s') . "</div>";
                }

                $buttonLink = route('admin.business-client.shop.show', $value->shop_id);
                $viewButton = "<a role='button' href='$buttonLink' class='btn btn-primary btn-sm '><i class='fas fa-eye mt-1'></i></a>";

                if ($value->multiple_shop_posts) {
                    foreach ($value->multiple_shop_posts as $postImage) {
                        if ($postImage['type'] == 'image') {
                            //$url = Storage::disk('s3')->url($postImage['post_item']);
                            $url = (!str_contains($postImage['post_item'], 'amazonaws')) ? Storage::disk('s3')->url($postImage['post_item']) : $postImage['post_item'];
                            $images .= ($postImage['post_item']) ? '<img onclick="showImage(`' . $url . '`)" src="' . $url . '" alt="' . $postImage['id'] . '" class="reported-client-images pointer m-1" width="50" height="50" />' : '';
                        } else {
                            //$url = Storage::disk('s3')->url($postImage['video_thumbnail']);
                            //$thumbImage = Storage::disk('s3')->url($postImage['post_item']);

                            $url = (!str_contains($postImage['video_thumbnail'], 'amazonaws')) ? Storage::disk('s3')->url($postImage['video_thumbnail']) : $postImage['video_thumbnail'];
                            $thumbImage = (isset($postImage['post_item']) && !str_contains($postImage['post_item'], 'amazonaws')) ? Storage::disk('s3')->url($postImage['post_item']) : $postImage['post_item'];
                            $images .= ($postImage['video_thumbnail']) ? '<img onclick="showImage(`' . $thumbImage . '`)" src="' . $url . '" alt="' . $postImage['id'] . '" class="reported-client-images pointer m-1" width="50" height="50" />' : '';
                        }
                    }
                }

                $dateDetails = $this->formatDateTimeCountryWise($value->post_order_date, $adminTimezone, 'Y-m-d H:i:s') . $edited;

                $data[$count]['business_profile'] = $viewButton;

                $connect_instagram = ($value->connect_instagram == true) ? "<img class='ml-2 small-list-icon' src='" . asset('img/connect_instagram.png') . "' />" : '';
                $phoneData =  "<div class='pointer' onclick='copyTextLink(`" . $value->mobile . "`,`Phone Number`);'>" . $value->mobile . " $connect_instagram</div>";

                $data[$count]['business_name'] = "<div>" . $value->shop_name . "</div>" . $value->main_name . $phoneData;
                $data[$count]['description'] = html_entity_decode($value->description);
                $data[$count]['update_date'] = $dateDetails;
                $data[$count]['images'] = $images;

                $service_checked = "";
                if ($value->is_regular_service) {
                    $service_checked = "checked";
                }

                $instaDate = Carbon::now()->addDays($value->count_days)->format('Y-m-d');
                if ($value->count_days == 0 && $value->last_count_updated_at != NULL) {
                    $instaDate = Carbon::parse($value->last_count_updated_at)->format('Y-m-d');
                }

                $service = '<input id="regular_service" ' . $service_checked . ' type="checkbox" name="regular_service" value="1" class="form-check-input ml-0 " disabled /><label class="ml-4 pl-1 pt-2">Regular Service</label>';

                $data[$count]['service'] = "<div class='update_service' id='" . $value->shop_id . "' onclick='instagramServicePopup(`" . $value->shop_id . "`);' ><div class='count_days'>" . $value->count_days . "</div><div class='expiry_date'>" . $instaDate . "</div><div class='service'>" . $service . "</div></div>";

                /* print_r($allLikes->toArray());
                echo Carbon::now()->format('Y-m-d'); exit; */
                $isAdminCount = $allLikes->filter(function ($item) use ($user,$id) {
                    return ($item->user_id == $user->id && $item->shop_post_id == $id);
                })->count();

                $todayCount = $allLikes->filter(function ($item) {
                    return (Carbon::now()->isSameDay($item->created_at));
                })->count();

                $monthCount = $allLikes->filter(function ($item) {
                    return (Carbon::parse($item->created_at)->between(Carbon::now()->startOfMonth(), Carbon::now()));
                })->count();

                $countData = "Today: $todayCount, Month: $monthCount, Total: " . count($allLikes);
                $color = "#000";
                if ($isAdminCount) {
                    $color = "green";
                }
                $data[$count]['insta_link'] = "<div>$countData </div> <a style='color:$color;' href='javascript:void(0);' onclick='giveLikeToPost(`" . $value->insta_link . "`,`" . $value->id . "`,`". $value->shop_id ."`)'>" . $value->insta_link . "</a> <div>$dateDetails</div> ";
                $count++;
            }

            $jsonData = array(
                "draw" => intval($draw),
                "recordsTotal" => intval($totalData),
                "recordsFiltered" => intval($totalFiltered),
                "data" => $data
            );

            return response()->json($jsonData);
        } catch (\Exception $ex) {
            Log::info($ex);
            $jsonData = array(
                "draw" => intval($draw),
                "recordsTotal" => 0,
                "recordsFiltered" => 0,
                "data" => []
            );
            return response()->json($jsonData);
        }
    }

    public function getJsonDataExpired(Request $request)
    {
        $columns = array(
            0 => 'users_detail.name',
            1 => 'users.email',
            2 => 'users_detail.mobile',
            3 => 'users.created_at',
            4 => 'users.last_login',
        );

        $limit = $request->input('length');
        $start = $request->input('start');
        $search = $request->input('search.value');
        $draw = $request->input('draw');
        $order = $columns[$request->input('order.0.column')];
        $dir = $request->input('order.0.dir');

        $adminTimezone = $this->getAdminUserTimezone();
        try {
            $data = [];

            $userQuery = ShopPost::join('shops as s', 's.id', 'shop_posts.shop_id')
                ->leftjoin('shop_post_likes', function ($join){
                    $join->on('shop_post_likes.shop_post_id', '=', 'shop_posts.id');
                })
                ->join('users', 'users.id', 's.user_id')
                ->leftJoin('users_detail', 'users_detail.user_id', 'users.id')
                ->whereNull('s.deleted_at')
                ->whereNotNull('shop_posts.insta_link')
                ->where('s.count_days', 0)
                ->whereNotNull('s.last_count_updated_at')
                ->select(
                    'shop_posts.id',
                    'users.id',
                    'users_detail.name',
                    'users_detail.level',
                    'users_detail.mobile',
                    'users_detail.recommended_by',
                    'users.inquiry_phone',
                    'users.connect_instagram',
                    'users.email',
                    'users.is_admin_access',
                    'users.is_support_user',
                    'users.created_at as date',
                    'users.last_login as last_access'
                )
                ->selectSub(function ($q) {
                    $q->select(DB::raw('count(id) as count'))->from('linked_social_profiles')->where('social_type', LinkedSocialProfile::Instagram)->whereRaw("`user_id` = `users`.`id`");
                }, 'linked_account_count')
                ->selectSub(function ($q) {
                    $q->select('ref.name as referred_by_name')->from('users_detail as ref')->join('users as ru', 'ru.id', 'ref.user_id')->whereNull('ru.deleted_at')->whereIn('ru.status_id', [Status::ACTIVE, Status::INACTIVE])->whereRaw("`ref`.`user_id` = `users_detail`.`recommended_by`");
                }, 'referred_by_name')
//                ->groupBy('shop_posts.id')
                ->groupBy('users.id');

            if (!empty($search)) {
                $userQuery = $userQuery->where(function ($q) use ($search) {
                    $q->where('users_detail.name', 'LIKE', "%{$search}%")
                        ->orWhere('users.email', 'LIKE', "%{$search}%")
                        ->orWhere('users.created_at', 'LIKE', "%{$search}%")
                        ->orWhere('users_detail.mobile', 'LIKE', "%{$search}%");
                });
            }

            $totalData = count($userQuery->get());
            $totalFiltered = $totalData;

            $userQuery = $userQuery->offset($start)->limit($limit);
            $userData = $userQuery->get();

            $count = 0;
            foreach ($userData as $user) {
                $data[$count]['name'] = "<p style='margin: 0'>$user->name</p>";
                $data[$count]['email'] = $user->email;
                $data[$count]['phone'] = $user->mobile;
                $data[$count]['signup'] = $this->formatDateTimeCountryWise($user->date, $adminTimezone);
                $data[$count]['last_access'] = $this->formatDateTimeCountryWise($user->last_access, $adminTimezone);
                $count++;
            }

            $jsonData = array(
                "draw" => intval($draw),
                "recordsTotal" => intval($totalData),
                "recordsFiltered" => intval($totalFiltered),
                "data" => $data
            );
            return response()->json($jsonData);
        } catch (\Exception $ex) {
            Log::info('Exception user list');
            Log::info($ex);
            $jsonData = array(
                "draw" => intval($draw),
                "recordsTotal" => 0,
                "recordsFiltered" => 0,
                "data" => []
            );
            return response()->json($jsonData);
        }
    }

    public function instagramServicePopup(Shop $shop)
    {
        $title = "Instagrma service";
        return view('admin.like-order.instagram_service', compact('title', 'shop'));
    }

    public function giveLike(Request $request)
    {
        $input = $request->all();
        try {
            $user = Auth::user();
            $post_id = $input['post_id'];
            $shop_id = $input['shop_id'];
            $isExist = ShopPostLikes::where('user_id', $user->id)->where('shop_post_id', $post_id)->where('shop_id',$shop_id)->first();
            if ($isExist) {
                $isExist->delete();
            } else {
                ShopPostLikes::firstOrCreate(['user_id' => $user->id, 'shop_post_id' => $post_id, 'shop_id' => $shop_id]);
            }
            $jsonData = array(
                'success' => true,
                'message' => '',
            );
        } catch (\Throwable $th) {
            $jsonData = array(
                'success' => false,
                'message' => ''
            );
        }
        return response()->json($jsonData);
    }
}
