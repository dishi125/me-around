<?php

namespace App\Http\Controllers\Insta;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\ChallengeDay;
use App\Models\ChallengeParticipatedUser;
use App\Models\ChallengeVerify;
use App\Models\EntityTypes;
use App\Models\LinkedSocialProfile;
use App\Models\Status;
use App\Models\UserEntityRelation;
use App\Util\Firebase;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    protected $firebase;

    public function __construct()
    {
        $this->firebase = new Firebase();
    }

    public function index(Request $request)
    {
        $title = 'All User';
        return view('insta.users.index', compact('title'));
    }

    public function getJsonAllData(Request $request)
    {
        $columns = array(
            0 => 'users_detail.name',
            1 => 'users.email',
            5 => 'users_detail.mobile',
            6 => 'users.created_at',
            7 => 'users.last_login',
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

            $userQuery = DB::table('users')->leftJoin('user_entity_relation', 'user_entity_relation.user_id', 'users.id')
                ->leftJoin('users_detail', 'users_detail.user_id', 'users.id')
                ->leftjoin('shops', function ($query) {
                    $query->on('users.id', '=', 'shops.user_id');
                })
                ->whereIN('user_entity_relation.entity_type_id', [EntityTypes::NORMALUSER, EntityTypes::HOSPITAL, EntityTypes::SHOP])
                ->whereNotNull('users.email')
                ->whereNull('users.deleted_at')
                ->whereIn('users.status_id', [Status::ACTIVE, Status::INACTIVE])
                ->where('users.app_type','insta')
                ->select(
                    'users.id',
                    'users_detail.name',
                    'users_detail.mobile',
                    'users.email',
                    'users.created_at as date',
                    'users.last_login as last_access',
                    'users.app_type',
                    'users.is_admin_access'
                )
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

            $userData = $userQuery->offset($start)
                ->limit($limit)
                ->orderBy($order, $dir)
                ->get();

            $count = 0;
            foreach ($userData as $user) {
                $style = ($user->is_admin_access == 1) ? "color:deeppink" : '';
                $data[$count]['name'] = "<div class='d-flex align-items-center'>
<p style='$style;margin: 0'>$user->name</p>
</div>";

                $data[$count]['email'] = $user->email;

                if (Auth::user()->hasRole('Sub Admin')) {
                    $data[$count]['phone'] = "";
                }
                else {
                    $data[$count]['phone'] = '<span class="copy_clipboard">' . $user->mobile . '</span>';
                }

                $data[$count]['signup'] = $this->formatDateTimeCountryWise($user->date, $adminTimezone);
                $data[$count]['last_access'] = $this->formatDateTimeCountryWise($user->last_access, $adminTimezone);

                $insta_connected_shop = DB::table('shops')
                    ->leftjoin('linked_social_profiles','linked_social_profiles.shop_id','shops.id')
                    ->leftjoin('linked_profile_histories', function ($join) {
                        $join->on('shops.id', '=', 'linked_profile_histories.shop_id');
                    })
                    ->whereNull('shops.deleted_at')
                    ->where('shops.user_id', $user->id)
                    ->where(function ($q){
                        $q->whereNotNull('linked_social_profiles.id')
                            ->orWhereNotNull('linked_profile_histories.id');
                    })
                    ->select(
                        'shops.id',
                        'linked_social_profiles.is_valid_token as is_valid_token',
                        DB::raw('IFNULL(linked_social_profiles.social_id, "") as is_connect')
                    )
                    ->orderBy('shops.created_at','DESC')
                    ->first();
                if($insta_connected_shop && !empty($insta_connected_shop->is_connect)){
                    if ($insta_connected_shop->is_valid_token==0){
                        $status = '<span class="badge" style="background-color: #fff700;">&nbsp;</span>';
                    }
                    else {
                        $status = '<span class="badge badge-success">&nbsp;</span>';
                    }
                }
                else {
                    $status = '<span class="badge badge-secondary">&nbsp;</span>';
                }

                $data[$count]['instagram'] = "$status";
                $data[$count]['tictok'] = "";
                $data[$count]['youtube'] = "";

                $count++;
            }

            $jsonData = array(
                "draw" => intval($draw),
                "recordsTotal" => intval($totalData),
                "recordsFiltered" => intval($totalFiltered),
                "data" => $data
            );
            return response()->json($jsonData);
        } catch (Exception $ex) {
            Log::info('Exception all user list');
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

}
