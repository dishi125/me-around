<?php

namespace App\Http\Controllers\Tattoocity;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\CategoryTypes;
use App\Models\EntityTypes;
use App\Models\LinkedSocialProfile;
use App\Models\RequestForm;
use App\Models\RequestFormStatus;
use App\Models\Shop;
use App\Models\ShopConnectLink;
use App\Models\Status;
use App\Models\UserDetail;
use App\Models\UserEntityRelation;
use App\Util\Firebase;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    protected $firebase;

    public function __construct()
    {
        $this->firebase = new Firebase();
//        $this->middleware('permission:user-list', ['only' => ['index']]);
    }

    public function index(Request $request)
    {
        $title = 'All User';

        $totalUsers = UserEntityRelation::join('users', 'users.id', 'user_entity_relation.user_id')->whereIN('user_entity_relation.entity_type_id', [EntityTypes::NORMALUSER, EntityTypes::HOSPITAL, EntityTypes::SHOP])->whereIn('users.status_id', [Status::ACTIVE, Status::INACTIVE])->whereNotNull('users.email')->distinct('user_id')->count('user_id');
        $totalShops = UserEntityRelation::where('entity_type_id', EntityTypes::SHOP)->distinct('user_entity_relation.user_id')->count('user_entity_relation.user_id');
        $totalHospitals = UserEntityRelation::where('entity_type_id', EntityTypes::HOSPITAL)->distinct('user_entity_relation.user_id')->count('user_entity_relation.user_id');

        $totalNormalUser = $totalUsers - ($totalShops + $totalHospitals);

        $category = Category::where('category.status_id', Status::ACTIVE)
            ->where('category.category_type_id', EntityTypes::SHOP)
            ->where('category.parent_id', 0)
            ->get();

        $unreadReferralCount = DB::table('users_detail')->join('users', 'users.id', 'users_detail.user_id')->whereNull('users.deleted_at')->whereNotNull('users_detail.recommended_by')->where('users_detail.is_referral_read', 1)->count();
        return view('tattoocity.users.index', compact('title', 'category', 'totalUsers', 'totalShops', 'totalHospitals', 'totalNormalUser', 'unreadReferralCount'));
    }

    public function getJsonAllData(Request $request)
    {
        $columns = array(
            0 => 'users_detail.name',
            1 => 'users.email',
            2 => 'users_detail.mobile',
            3 => 'users.created_at',
            4 => 'users.last_login',
        );

        $filter = $request->input('filter');
        $categoryFilterID = $request->input('category');
        $limit = $request->input('length');
        $start = $request->input('start');
        $search = $request->input('search.value');
        $draw = $request->input('draw');
        $order = $columns[$request->input('order.0.column')];
        $dir = $request->input('order.0.dir');

        $adminTimezone = $this->getAdminUserTimezone();
        $viewButton = '';
        $toBeShopButton = '';
        $loginUser = Auth::user();
        try {
            $data = [];

            $userQuery = DB::table('users')->leftJoin('user_entity_relation', 'user_entity_relation.user_id', 'users.id')
                ->leftJoin('users_detail', 'users_detail.user_id', 'users.id')
                ->leftjoin('shops', function ($query) use ($filter) {
                    $query->on('users.id', '=', 'shops.user_id');
                })
                ->leftjoin('user_cards', function ($query) use ($filter) {
                    $query->on('users.id', '=', 'user_cards.user_id')->where('user_cards.is_applied', 1);
                })
                ->leftjoin('hospitals', 'user_entity_relation.entity_id', 'hospitals.id')
                ->whereIN('user_entity_relation.entity_type_id', [EntityTypes::NORMALUSER, EntityTypes::HOSPITAL, EntityTypes::SHOP])
                ->whereNotNull('users.email')
                ->whereNull('users.deleted_at')
                ->whereIn('users.status_id', [Status::ACTIVE, Status::INACTIVE])
                ->where('users.app_type','tattoocity')
                ->select(
                    'users.id',
                    'users_detail.name',
                    'users_detail.level',
                    'users_detail.mobile',
                    'users_detail.recommended_by',
                    'users_detail.recommended_code',
                    'users.inquiry_phone',
                    'users.connect_instagram',
                    'users.email',
                    'users.is_admin_access',
                    'users.is_support_user',
                    'users.created_at as date',
                    'users.last_login as last_access',
                    'users.app_type',
                    DB::raw('IFNULL(user_cards.love_count, 0) as love_count'),
                    DB::raw('(SELECT group_concat(entity_type_id) from user_entity_relation WHERE user_id = users.id) as entity_types')
                )
                ->selectSub(function ($q) {
                    $q->select(DB::raw('count(id) as count'))->from('linked_social_profiles')->where('social_type', LinkedSocialProfile::Instagram)->whereRaw("`user_id` = `users`.`id`");
                }, 'linked_account_count')
                ->selectSub(function ($q) {
                    $q->select('ref.name as referred_by_name')->from('users_detail as ref')->join('users as ru', 'ru.id', 'ref.user_id')->whereNull('ru.deleted_at')->whereIn('ru.status_id', [Status::ACTIVE, Status::INACTIVE])->whereRaw("`ref`.`user_id` = `users_detail`.`recommended_by`");
                }, 'referred_by_name')
                ->groupBy('users.id');

            if (!empty($search)) {
                $userQuery = $userQuery->where(function ($q) use ($search) {
                    $q->where('users_detail.name', 'LIKE', "%{$search}%")
                        ->orWhere('users.email', 'LIKE', "%{$search}%")
                        ->orWhere('users.created_at', 'LIKE', "%{$search}%")
                        ->orWhere('shops.shop_name', 'LIKE', "%{$search}%")
                        ->orWhere('shops.main_name', 'LIKE', "%{$search}%")
                        ->orWhere('hospitals.main_name', 'LIKE', "%{$search}%")
                        ->orWhere('users_detail.mobile', 'LIKE', "%{$search}%")
                        ->orWhere('shops.another_mobile', 'LIKE', "%{$search}%");
                });
            }

            $userQuery = $userQuery->selectSub(function ($q) {
                $q->select(DB::raw('count(users_detail.id) as count'))->from('users_detail')->whereRaw("`users_detail`.`recommended_by` = `users`.`id`");
            }, 'referral_count');

            // Count Number
            $userQuery = $userQuery->selectSub(function ($q) {
                $q->select(DB::raw('count(users_detail.id) as count'))->from('users_detail')->where('users_detail.is_referral_read', 1)->whereRaw("`users_detail`.`recommended_by` = `users`.`id`");
            }, 'new_referral_count');

            $totalData = count($userQuery->get());
            $totalFiltered = $totalData;

            $userQuery = $userQuery->offset($start)->limit($limit);
            $userData = $userQuery->get();

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
