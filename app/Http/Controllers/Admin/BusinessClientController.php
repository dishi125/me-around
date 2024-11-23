<?php

namespace App\Http\Controllers\Admin;

use App\Models\Category;
use App\Models\InstagramCategory;
use App\Models\InstagramCategoryOption;
use App\Models\InstagramLog;
use App\Models\InstagramSubscribedPlan;
use App\Models\InstaImportantSetting;
use App\Models\LinkedProfileHistory;
use App\Models\MenuSetting;
use App\Models\PackagePlan;
use App\Models\PostLanguage;
use App\Models\ShopInfo;
use App\Util\Firebase;
use Log;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;
use Validator;
use Carbon\Carbon;
use FFMpeg\FFMpeg;
use App\Models\Post;
use App\Models\Shop;
use App\Models\User;
use App\Models\Config;
use App\Models\Notice;
use App\Models\Status;
use App\Models\Address;
use App\Models\HashTag;
use App\Models\Manager;
use App\Models\Reviews;
use App\Models\Hospital;
use App\Models\ShopPost;
use App\Models\ShopDetail;
use App\Models\ShopImages;
use App\Models\ShopPrices;
use App\Models\UserCredit;
use App\Models\UserDetail;
use App\Models\UserPoints;
use App\Models\CreditPlans;
use App\Models\EntityTypes;
use App\Models\UserDevices;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\CategoryTypes;
use App\Models\HospitalImages;
use App\Models\ShopConnectLink;
use App\Models\ShopImagesTypes;
use App\Models\ShopPriceImages;
use App\Models\MultipleShopPost;
use App\Models\ReloadCoinRequest;
use App\Models\ShopPriceCategory;
use App\Models\UserCreditHistory;
use App\Models\UserEntityRelation;
use App\Models\LinkedSocialProfile;
use App\Models\ManagerActivityLogs;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Crypt;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Storage;
use Lakshmaji\Thumbnail\Facade\Thumbnail;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Facades\DB;

class BusinessClientController extends Controller
{
    public $shopNameConcat = '';
    public $myBusinessPage = '';
    protected $firebase;

    public function __construct()
    {
        $this->firebase = new Firebase();
        $this->middleware('permission:business-client-list', ['only' => ['index', 'indexShop']]);
        $this->shopNameConcat = \DB::raw("group_concat(
            DISTINCT CONCAT(IFNULL(shops.main_name,''), IF(shops.main_name IS NULL,'','/') , shops.shop_name)
            ORDER BY shops.id DESC
            SEPARATOR '|'
          ) as business_name_group");
        $this->myBusinessPage = 'my-business-client';
    }

    /* ================ Hospital Code Start ======================= */
    public function index(Request $request)
    {
        $manager_id = $request->has('manager_id') ? $request->manager_id : 0;
        $title = 'All Client';
        $totalUsers = UserEntityRelation::join('users', 'users.id', 'user_entity_relation.user_id')->whereNotNull('users.email')->distinct('user_id')->count('user_id');;
        $totalShopsQuery = UserEntityRelation::join('users_detail', 'users_detail.user_id', 'user_entity_relation.user_id');
        $totalHospitalsQuery = UserEntityRelation::join('users_detail', 'users_detail.user_id', 'user_entity_relation.user_id');
        $lastMonthIncomeQuery = ReloadCoinRequest::join('users_detail', 'users_detail.user_id', 'reload_coins_request.user_id');
        $totalIncomeQuery = ReloadCoinRequest::join('users_detail', 'users_detail.user_id', 'reload_coins_request.user_id');
        $currentMonthIncomeQuery = ReloadCoinRequest::join('users_detail', 'users_detail.user_id', 'reload_coins_request.user_id');

        if ($manager_id && $manager_id != 0) {
            $totalShopsQuery = $totalShopsQuery->where('users_detail.manager_id', $manager_id);
            $totalHospitalsQuery = $totalHospitalsQuery->where('users_detail.manager_id', $manager_id);
            $lastMonthIncomeQuery = $lastMonthIncomeQuery->where('users_detail.manager_id', $manager_id);
            $totalIncomeQuery = $totalIncomeQuery->where('users_detail.manager_id', $manager_id);
            $currentMonthIncomeQuery = $currentMonthIncomeQuery->where('users_detail.manager_id', $manager_id);
        }

        $totalShops = $totalShopsQuery->where('entity_type_id', EntityTypes::SHOP)->distinct('user_entity_relation.user_id')->count('user_entity_relation.user_id');
        $totalHospitals = $totalHospitalsQuery->where('entity_type_id', EntityTypes::HOSPITAL)->distinct('user_entity_relation.user_id')->count('user_entity_relation.user_id');
        $totalClients = UserEntityRelation::whereIn('entity_type_id', [EntityTypes::HOSPITAL, EntityTypes::SHOP])->distinct('user_id')->count('user_id');
        $dateS = Carbon::now()->startOfMonth()->subMonth(1);
        $dateE = Carbon::now()->startOfMonth();
        $lastMonthIncome = $lastMonthIncomeQuery->where('status', ReloadCoinRequest::GIVE_COIN)->whereBetween('reload_coins_request.created_at', [$dateS, $dateE])
            ->sum('reload_coins_request.coin_amount');
        $lastMonthIncome = number_format($lastMonthIncome, 0);
        $totalIncome = $totalIncomeQuery->where('status', ReloadCoinRequest::GIVE_COIN)
            ->sum('reload_coins_request.coin_amount');
        $totalIncome = number_format($totalIncome, 0);
        $currentMonthIncome = $currentMonthIncomeQuery->where('status', ReloadCoinRequest::GIVE_COIN)->whereMonth('reload_coins_request.created_at', $dateE->month)
            ->sum('reload_coins_request.coin_amount');
        $currentMonthIncome = number_format($currentMonthIncome, 0);
        return view('admin.business-client.index', compact('manager_id', 'totalIncome', 'title', 'totalUsers', 'totalShops', 'totalHospitals', 'totalClients', 'lastMonthIncome', 'currentMonthIncome'));
    }

    public function getJsonAllData(Request $request, $manager_id = 0)
    {
        $currentURL =  request()->segment(2);
        if ($currentURL == $this->myBusinessPage) {
            $user = Auth::user();
            $manager = Manager::where('user_id', $user->id)->first();
            $manager_id = $manager ? $manager->id : 0;
        }
        try {

            Log::info('Start all hospital list');
            $columns = array(
                0 => 'users.id',
                1 => 'users_detail.name',
                2 => 'countries.name',
                3 => 'users_detail.mobile',
                4 => 'user_credits.credits',
                5 => 'managers.name',
                6 => 'users.created_at',
                7 => 'avg_rating',
                8 => 'business_license_number',
                9 => 'is_user_active',
                12 => 'log',
                11 => 'action',
            );
            $limit = $request->input('length');
            $start = $request->input('start');
            $search = $request->input('search.value');
            $draw = $request->input('draw');
            $order = $columns[$request->input('order.0.column')];
            $dir = $request->input('order.0.dir');
            $adminTimezone = $this->getAdminUserTimezone();

            $shopCountQuery = '(CASE
                WHEN user_entity_relation.entity_type_id = 1 THEN  count(DISTINCT shops.id)
                ELSE "0"
                END)';

            $shopHospitalCountAmount = '(CASE
                WHEN user_entity_relation.entity_type_id = 1 THEN  count(DISTINCT shops.id)
                WHEN user_entity_relation.entity_type_id = 2 THEN count(DISTINCT hospitals.id)
                ELSE ""
                END) * credit_plans.amount ';

            $query = User::join('user_entity_relation', 'user_entity_relation.user_id', 'users.id')
                ->join('users_detail', 'users_detail.user_id', 'users.id')
                ->join('user_credits', 'user_credits.user_id', 'users.id')
                ->join('countries', 'users_detail.country_id', 'countries.id')
                ->leftjoin('credit_plans', function ($query) {
                    $query->on('credit_plans.package_plan_id', '=', 'users_detail.package_plan_id')
                        ->whereRaw('credit_plans.entity_type_id = user_entity_relation.entity_type_id');
                })
                ->leftjoin('shops', function ($query) {
                    $query->on('users.id', '=', 'shops.user_id')->whereNull('shops.deleted_at');
                    //->whereRaw('shops.id IN (select MAX(a2.id) from shops as a2 group by a2.user_id)');
                })
                ->leftjoin('linked_social_profiles', 'shops.id', 'linked_social_profiles.shop_id')
                ->leftjoin('hospitals', 'user_entity_relation.entity_id', 'hospitals.id')
                ->leftjoin('reviews', function ($join) {
                    $join->on('hospitals.id', '=', 'reviews.entity_id')
                        ->where('reviews.entity_type_id',  EntityTypes::HOSPITAL);
                })
                ->leftjoin('addresses', function ($join) {
                    $join->on('user_entity_relation.entity_id', '=', 'addresses.entity_id')
                        ->whereIn('addresses.entity_type_id',  [EntityTypes::HOSPITAL, EntityTypes::SHOP]);
                })
                ->leftjoin('managers', 'managers.id', 'users_detail.manager_id')
                ->whereIN('user_entity_relation.entity_type_id', [EntityTypes::HOSPITAL, EntityTypes::SHOP])
                ->whereIn('users.status_id', [Status::ACTIVE, Status::INACTIVE]);
            if ($manager_id && $manager_id != 0) {
                $query = $query->where('managers.id', $manager_id);
            }
            $query = $query->select(
                'addresses.*',
                'users.id',
                'users.id as user_id',
                'user_entity_relation.entity_type_id',
                'users.status_id',
                'users.created_at',
                'users.email',
                'users_detail.name as user_name',
                'users_detail.mobile',
                'user_credits.credits',
                \DB::raw('(CASE
                    WHEN user_entity_relation.entity_type_id = 1 THEN  shops.uuid
                    ELSE ""
                    END) AS shop_uuid'),
                \DB::raw('(CASE
                    WHEN user_entity_relation.entity_type_id = 1 THEN  shops.business_license_number
                    WHEN user_entity_relation.entity_type_id = 2 THEN hospitals.business_license_number
                    ELSE ""
                    END) AS business_license_number'),
                \DB::raw('(CASE
                    WHEN user_entity_relation.entity_type_id = 1 THEN  shops.email
                    WHEN user_entity_relation.entity_type_id = 2 THEN hospitals.email
                    ELSE ""
                    END) AS email_address'),
                \DB::raw('(CASE
                    WHEN user_entity_relation.entity_type_id = 1 THEN  shops.main_name
                    WHEN user_entity_relation.entity_type_id = 2 THEN hospitals.main_name
                    ELSE ""
                    END) AS main_name'),
                \DB::raw('(CASE
                    WHEN user_entity_relation.entity_type_id = 1 THEN  shops.shop_name
                    ELSE ""
                    END) AS sub_name'),
                \DB::raw('(CASE
                    WHEN user_entity_relation.entity_type_id = 1 THEN  shops.created_at
                    WHEN user_entity_relation.entity_type_id = 2 THEN hospitals.created_at
                    ELSE ""
                    END) AS business_created_date'),
                // 'countries.name as country_name',
                DB::raw('round(AVG(reviews.rating),1) as avg_rating'),
                DB::raw('group_concat(DISTINCT user_entity_relation.entity_type_id) as entity_type_id_array'),
                'managers.name as manager_name',
                'credit_plans.amount',
                DB::raw('group_concat(DISTINCT linked_social_profiles.social_name) as social_names'),
                $this->shopNameConcat
            )
                ->selectRaw("{$shopCountQuery} AS total_shop_count")
                ->selectRaw("{$shopHospitalCountAmount} AS total_plan_amount")
                ->selectRaw("(CASE WHEN {$shopHospitalCountAmount} <= user_credits.credits THEN  1
                    ELSE 0
                    END) AS is_user_active")
                ->groupBy('users.id');
            $totalData = count($query->get());
            $totalFiltered = $totalData;

            if (!empty($search)) {
                $query = $query->where(function ($q) use ($search) {
                    $q->where('users_detail.name', 'LIKE', "%{$search}%")
                        ->orWhere('countries.name', 'LIKE', "%{$search}%")
                        ->orWhere('users_detail.mobile', 'LIKE', "%{$search}%")
                        ->orWhere('users.created_at', 'LIKE', "%{$search}%")
                        ->orWhere('users.email', 'LIKE', "%{$search}%")
                        ->orWhere('user_credits.credits', 'LIKE', "%{$search}%")
                        ->orWhere('managers.name', 'LIKE', "%{$search}%")
                        ->orWhere('shops.shop_name', 'LIKE', "%{$search}%")
                        ->orWhere('shops.main_name', 'LIKE', "%{$search}%")
                        ->orWhere('hospitals.main_name', 'LIKE', "%{$search}%")
                        ->orWhere('shops.speciality_of', 'LIKE', "%{$search}%")
                        ->orWhere('linked_social_profiles.social_name', 'LIKE', "%{$search}%");
                });

                $totalFiltered = $query->count();
            }

            $hospitals = $query
                ->offset($start)
                ->limit($limit)
                ->orderBy($order, $dir)
                ->get();
            $data = array();
            if (!empty($hospitals)) {
                foreach ($hospitals as $value) {
                    $shopViewLink = '';
                    $id = $value['id'];

                    $shopViewLink = $this->getViewProfileURL($value);

                    $entity_type_id_array = explode(",", $value['entity_type_id_array']);
                    $entity_type_id_array = collect($entity_type_id_array)->unique()->values()->toArray();

                    if (in_array(EntityTypes::HOSPITAL, $entity_type_id_array)) {
                        $view = route('admin.business-client.hospital.show', $id);
                        $viewButton = "<a role='button' href='javascript:void(0)' onclick='viewProfile(" . $id . ")'  title='' data-original-title='View' class='btn btn-primary btn-sm' data-toggle='tooltip'><i class='fas fa-eye'></i></a>";
                        $nestedData['avg_rating'] = $value['avg_rating'];
                        $business_name = $value['main_name'];
                    } else {
                        $view = route('admin.business-client.shop.show', $id);
                        $viewButton = "<a role='button' href='javascript:void(0)' onclick='viewShopProfile(" . $id . ")'  title='' data-original-title='View' class='btn btn-primary btn-sm' data-toggle='tooltip'><i class='fas fa-eye'></i></a>";
                        $reviews = Reviews::join('shops', 'shops.id', 'reviews.entity_id')
                            ->where('reviews.entity_type_id', EntityTypes::SHOP)
                            ->where('shops.user_id', $value['id'])
                            ->select('reviews.*', DB::raw('round(AVG(reviews.rating),1) as avg_rating'))->get();
                        $nestedData['avg_rating'] = $reviews[0]->avg_rating;
                        $business_name = $value['main_name'] != "" ? $value['main_name'] . "/" : "";
                        $business_name .= $value['sub_name'];
                        $business_name = $this->displayBusinessName($value['business_name_group'], $business_name);
                    }
                    $nestedData['checkbox'] = "<td><div class=\"custom-checkbox custom-control\">
                    <input type=\"checkbox\" data-checkboxes=\"mygroup\" class=\"custom-control-input checkbox_id check-all-hospital\" id=\"$id\" data-id=\"$id\" value=\"$id\" name=\"checkbox_id[]\"><label for=\"$id\" class=\"custom-control-label\">&nbsp;</label></div></td>";
                    $address = $value['address'];
                    $address .= $value['address2'] ? ',' . $value['address2'] : '';
                    $address .= $value['city_name'] ? ',' . $value['city_name'] : '';
                    $address .= $value['state_name'] ? ',' . $value['state_name'] : '';
                    $address .= $value['country_name'] ? ',' . $value['country_name'] : '';
                    // $nestedData['name'] = $value['user_name'];
                    $displayEmail = $value['email'] ?? NULL;
                    $nestedData['name'] = $business_name . "|" . $displayEmail;
                    $nestedData['mobile'] = $value['mobile'];
                    $nestedData['address'] = $address;
                    $purchasedCredits = UserCreditHistory::where('user_id', $id)->where('type', UserCreditHistory::RELOAD)->sum('amount');
                    $nestedData['credits'] = number_format($purchasedCredits, 0) . "|" . number_format($value['credits'], 0);
                    $nestedData['business_license_number'] = $value['business_license_number'];
                    $nestedData['join_by'] = $value['manager_name'];
                    // $nestedData['date'] = $value['created_at'];
                    $nestedData['date'] = $this->formatDateTimeCountryWise($value['business_created_date'], $adminTimezone, 'd-m-Y H:i');

                    if ($value['is_user_active'] == true) {
                        $nestedData['status'] = '<span class="badge badge-success">&nbsp;</span>';
                    } else {
                        $nestedData['status'] = '<span class="badge badge-secondary">&nbsp;</span>';
                    }

                    if (in_array(EntityTypes::HOSPITAL, $entity_type_id_array)) {
                        $hospitalPendingcount = DB::table('user_entity_relation')->leftjoin('hospitals', 'user_entity_relation.entity_id', 'hospitals.id')
                            ->where('user_entity_relation.entity_type_id', EntityTypes::HOSPITAL)->where('user_entity_relation.user_id', $value['user_id'])
                            ->where('hospitals.status_id', Status::PENDING)->count();

                        if (!empty($hospitalPendingcount)) {
                            $nestedData['status'] = '<span class="badge" style="background-color: #fff700;">&nbsp;</span>';
                        }

                        $nestedData['status'] .= $this->isOutsideHospital($value['user_id']);
                    } else {

                        $shopCount = DB::table('shops')->where('user_id', $value['user_id'])->count();
                        $shopPendingCount = DB::table('shops')->where('user_id', $value['user_id'])->where('status_id', Status::PENDING)->count();

                        if ($shopCount == $shopPendingCount) {
                            $nestedData['status'] = '<span class="badge" style="background-color: #fff700;">&nbsp;</span>';
                        }

                        $nestedData['status'] .= $this->isOutsideShop($value['user_id']);
                    }

                    //if ($value['credits'] >= $value['total_plan_amount']) {
                    $seeButton = "<a role='button' href='javascript:void(0)' onclick='viewLogs(" . $id . ")' title='' data-original-title='View' class='btn btn-primary btn-sm' data-toggle='tooltip'>see</a>";
                    $nestedData['credit_purchase_log'] = $seeButton;

                    $nestedData['referral'] = $this->getReferralDetail($value['user_id']);
                    $nestedData['shop_profile'] = $shopViewLink . "<br/>" . str_replace(',', '<br/>', $value['social_names']);

                    $user = Auth::user();
                    $editButton =  $user->hasRole('Admin') ? "<a href='javascript:void(0)' role='button' onclick='editCredits(" . $id . ")' class='btn btn-primary btn-sm mx-1' data-toggle='tooltip' data-original-title='Edit'><i class='fa fa-edit'></a>" : "";
                    $nestedData['actions'] = "<div class='d-flex'>$viewButton $editButton</div>";
                    $data[] = $nestedData;
                }
            }
            $jsonData = array(
                "draw" => intval($draw),
                "recordsTotal" => intval($totalData),
                "recordsFiltered" => intval($totalFiltered),
                "data" => $data
            );
            Log::info('End all hospital list');
            return response()->json($jsonData);
        } catch (\Exception $ex) {
            Log::info('Exception all hospital list');
            Log::info($ex);
            return response()->json([]);
        }
    }
    public function getJsonActiveData(Request $request, $manager_id = 0)
    {
        $currentURL =  request()->segment(2);
        if ($currentURL == $this->myBusinessPage) {
            $user = Auth::user();
            $manager = Manager::where('user_id', $user->id)->first();
            $manager_id = $manager ? $manager->id : 0;
        }
        try {
            Log::info('Start all hospital list');
            $columns = array(
                0 => 'users.id',
                1 => 'users_detail.name',
                2 => 'countries.name',
                3 => 'users_detail.mobile',
                4 => 'user_credits.credits',
                5 => 'managers.name',
                6 => 'users.created_at',
                7 => 'avg_rating',
                8 => 'business_license_number',
                9 => 'is_user_active',
                10 => 'log',
                11 => 'action',
            );
            $limit = $request->input('length');
            $start = $request->input('start');
            $search = $request->input('search.value');
            $draw = $request->input('draw');
            $order = $columns[$request->input('order.0.column')];
            $dir = $request->input('order.0.dir');
            $adminTimezone = $this->getAdminUserTimezone();

            $shopCountQuery = '(CASE
                WHEN user_entity_relation.entity_type_id = 1 THEN  count(DISTINCT shops.id)
                ELSE "0"
                END)';

            $shopHospitalCountAmount = '(CASE
                WHEN user_entity_relation.entity_type_id = 1 THEN  count(DISTINCT shops.id)
                WHEN user_entity_relation.entity_type_id = 2 THEN count(DISTINCT hospitals.id)
                ELSE ""
                END) * credit_plans.amount ';

            $query = User::join('user_entity_relation', 'user_entity_relation.user_id', 'users.id')
                ->join('users_detail', 'users_detail.user_id', 'users.id')
                ->join('user_credits', 'user_credits.user_id', 'users.id')
                ->join('countries', 'users_detail.country_id', 'countries.id')
                ->leftjoin('credit_plans', function ($query) {
                    $query->on('credit_plans.package_plan_id', '=', 'users_detail.package_plan_id')
                        ->whereRaw('credit_plans.entity_type_id = user_entity_relation.entity_type_id');
                })
                ->leftjoin('shops', function ($query) {
                    $query->on('users.id', '=', 'shops.user_id')
                        ->whereNull('shops.deleted_at')
                        ->where('shops.status_id', Status::ACTIVE);
                    //->whereRaw('shops.id IN (select MAX(a2.id) from shops as a2 group by a2.user_id)');
                })
                ->leftjoin('linked_social_profiles', 'shops.id', 'linked_social_profiles.shop_id')
                ->leftjoin('hospitals', function ($query) {
                    $query->on('user_entity_relation.entity_id', '=', 'hospitals.id')
                        ->where('hospitals.status_id', Status::ACTIVE);
                })
                //->leftjoin('hospitals','user_entity_relation.entity_id','hospitals.id')
                ->leftjoin('reviews', function ($join) {
                    $join->on('hospitals.id', '=', 'reviews.entity_id')
                        ->where('reviews.entity_type_id',  EntityTypes::HOSPITAL);
                })
                ->leftjoin('addresses', function ($join) {
                    $join->on('user_entity_relation.entity_id', '=', 'addresses.entity_id')
                        ->whereIn('addresses.entity_type_id',  [EntityTypes::HOSPITAL, EntityTypes::SHOP]);
                })
                ->leftjoin('managers', 'managers.id', 'users_detail.manager_id')
                ->whereIN('user_entity_relation.entity_type_id', [EntityTypes::HOSPITAL, EntityTypes::SHOP])
                /* ->where(function($q){
                    $q->where('shops.status_id', Status::ACTIVE)
                        ->orWhere('hospitals.status_id', Status::ACTIVE);
                }) */
                ->whereIn('users.status_id', [Status::ACTIVE]);
            if ($manager_id && $manager_id != 0) {
                $query = $query->where('managers.id', $manager_id);
            }
            $query = $query->select(
                'addresses.*',
                'users.id',
                'users.id as user_id',
                'user_entity_relation.entity_type_id',
                'users.status_id',
                'users.created_at',
                'users.email',
                'users_detail.name as user_name',
                'users_detail.mobile',
                'user_credits.credits',
                \DB::raw('(CASE
                    WHEN user_entity_relation.entity_type_id = 1 THEN  shops.uuid
                    ELSE ""
                    END) AS shop_uuid'),
                \DB::raw('(CASE
                    WHEN user_entity_relation.entity_type_id = 1 THEN  shops.business_license_number
                    WHEN user_entity_relation.entity_type_id = 2 THEN hospitals.business_license_number
                    ELSE ""
                    END) AS business_license_number'),
                \DB::raw('(CASE
                    WHEN user_entity_relation.entity_type_id = 1 THEN  shops.email
                    WHEN user_entity_relation.entity_type_id = 2 THEN hospitals.email
                    ELSE ""
                    END) AS email_address'),
                \DB::raw('(CASE
                    WHEN user_entity_relation.entity_type_id = 1 THEN  shops.main_name
                    WHEN user_entity_relation.entity_type_id = 2 THEN hospitals.main_name
                    ELSE ""
                    END) AS main_name'),
                \DB::raw('(CASE
                    WHEN user_entity_relation.entity_type_id = 1 THEN  shops.shop_name
                    ELSE ""
                    END) AS sub_name'),
                \DB::raw('(CASE
                    WHEN user_entity_relation.entity_type_id = 1 THEN  shops.created_at
                    WHEN user_entity_relation.entity_type_id = 2 THEN hospitals.created_at
                    ELSE ""
                    END) AS business_created_date'),
                // 'countries.name as country_name',
                DB::raw('round(AVG(reviews.rating),1) as avg_rating'),
                DB::raw('group_concat(user_entity_relation.entity_type_id) as entity_type_id_array'),
                'managers.name as manager_name',
                'credit_plans.amount',
                DB::raw('group_concat(DISTINCT linked_social_profiles.social_name) as social_names'),
                $this->shopNameConcat
            )
                ->selectRaw("{$shopCountQuery} AS total_shop_count")
                ->selectRaw("{$shopHospitalCountAmount} AS total_plan_amount")
                ->selectRaw("(CASE WHEN {$shopHospitalCountAmount} <= user_credits.credits THEN  1
                    ELSE 0
                    END) AS is_user_active")
                ->groupBy('users.id');

            if (!empty($search)) {
                $query = $query->where(function ($q) use ($search) {
                    $q->where('users_detail.name', 'LIKE', "%{$search}%")
                        ->orWhere('countries.name', 'LIKE', "%{$search}%")
                        ->orWhere('users_detail.mobile', 'LIKE', "%{$search}%")
                        ->orWhere('users.created_at', 'LIKE', "%{$search}%")
                        ->orWhere('user_credits.credits', 'LIKE', "%{$search}%")
                        ->orWhere('managers.name', 'LIKE', "%{$search}%")
                        ->orWhere('shops.shop_name', 'LIKE', "%{$search}%")
                        ->orWhere('shops.main_name', 'LIKE', "%{$search}%")
                        ->orWhere('hospitals.main_name', 'LIKE', "%{$search}%")
                        ->orWhere('linked_social_profiles.social_name', 'LIKE', "%{$search}%");
                });

                $totalFiltered = $query->count();
            }

            $query = $query->havingRaw("{$shopHospitalCountAmount} <= user_credits.credits");

            $totalData = count($query->get());
            $totalFiltered = $totalData;

            $hospitals = $query
                ->offset($start)
                ->limit($limit)
                ->orderBy($order, $dir)
                ->get();
            $data = array();
            if (!empty($hospitals)) {
                foreach ($hospitals as $value) {
                    $id = $value['id'];
                    $shopViewLink = $this->getViewProfileURL($value);

                    $entity_type_id_array = explode(",", $value['entity_type_id_array']);
                    $entity_type_id_array = collect($entity_type_id_array)->unique()->values()->toArray();

                    if (in_array(EntityTypes::HOSPITAL, $entity_type_id_array)) {
                        $view = route('admin.business-client.hospital.show', $id);
                        $viewButton = "<a role='button' href='javascript:void(0)' onclick='viewProfile(" . $id . ")'  title='' data-original-title='View' class='btn btn-primary btn-sm' data-toggle='tooltip'><i class='fas fa-eye'></i></a>";
                        $nestedData['avg_rating'] = $value['avg_rating'];
                        $business_name = $value['main_name'];
                    } else {
                        $view = route('admin.business-client.shop.show', $id);
                        $viewButton = "<a role='button' href='javascript:void(0)' onclick='viewShopProfile(" . $id . ")'  title='' data-original-title='View' class='btn btn-primary btn-sm' data-toggle='tooltip'><i class='fas fa-eye'></i></a>";
                        $reviews = Reviews::join('shops', 'shops.id', 'reviews.entity_id')
                            ->where('reviews.entity_type_id', EntityTypes::SHOP)
                            ->where('shops.user_id', $value['id'])
                            ->select('reviews.*', DB::raw('round(AVG(reviews.rating),1) as avg_rating'))->get();
                        $nestedData['avg_rating'] = $reviews[0]->avg_rating;
                        $business_name = $value['main_name'] != "" ? $value['main_name'] . "/" : "";
                        $business_name .= $value['sub_name'];
                        $business_name = $this->displayBusinessName($value['business_name_group'], $business_name);
                    }
                    $nestedData['checkbox'] = "<td><div class=\"custom-checkbox custom-control\">
                    <input type=\"checkbox\" data-checkboxes=\"mygroup\" class=\"custom-control-input checkbox_id check-all-hospital\" id=\"$id\" data-id=\"$id\" value=\"$id\" name=\"checkbox_id[]\"><label for=\"$id\" class=\"custom-control-label\">&nbsp;</label></div></td>";
                    $address = $value['address'];
                    $address .= $value['address2'] ? ',' . $value['address2'] : '';
                    $address .= $value['city_name'] ? ',' . $value['city_name'] : '';
                    $address .= $value['state_name'] ? ',' . $value['state_name'] : '';
                    $address .= $value['country_name'] ? ',' . $value['country_name'] : '';
                    // $nestedData['name'] = $value['user_name'];
                    $displayEmail = $value['email_address'] ?? $value['email'];
                    $nestedData['name'] = $business_name . "|" . $displayEmail;
                    $nestedData['mobile'] = $value['mobile'];
                    $nestedData['address'] = $address;
                    $purchasedCredits = UserCreditHistory::where('user_id', $id)->where('type', UserCreditHistory::RELOAD)->sum('amount');
                    $nestedData['credits'] = number_format($purchasedCredits, 0) . "|" . number_format($value['credits'], 0);
                    $nestedData['business_license_number'] = $value['business_license_number'];
                    $nestedData['join_by'] = $value['manager_name'];
                    // $nestedData['date'] = $value['created_at'];
                    $nestedData['date'] = $this->formatDateTimeCountryWise($value['business_created_date'], $adminTimezone, 'd-m-Y H:i');

                    $nestedData['status'] = '<span class="badge badge-success">&nbsp;</span>';
                    /* if ($value['is_user_active'] == true) {
                    } else {
                        $nestedData['status'] = '<span class="badge badge-secondary">&nbsp;</span>';
                    }     */

                    if (in_array(EntityTypes::HOSPITAL, $entity_type_id_array)) {
                        $nestedData['status'] .= $this->isOutsideHospital($value['user_id']);
                    } else {
                        $nestedData['status'] .= $this->isOutsideShop($value['user_id']);
                    }

                    $nestedData['referral'] = $this->getReferralDetail($value['user_id']);
                    $nestedData['shop_profile'] = $shopViewLink . "<br/>" . str_replace(',', '<br/>', $value['social_names']);

                    $seeButton = "<a role='button' href='javascript:void(0)' onclick='viewLogs(" . $id . ")' title='' data-original-title='View' class='btn btn-primary btn-sm' data-toggle='tooltip'>see</a>";
                    $nestedData['credit_purchase_log'] = $seeButton;

                    $user = Auth::user();
                    $editButton =  $user->hasRole('Admin') ? "<a href='javascript:void(0)' role='button' onclick='editCredits(" . $id . ")' class='btn btn-primary btn-sm mx-1' data-toggle='tooltip' data-original-title='Edit'><i class='fa fa-edit'></a>" : "";
                    $nestedData['actions'] = "<div class='d-flex'>$viewButton $editButton</div>";
                    $data[] = $nestedData;
                }
            }
            $jsonData = array(
                "draw" => intval($draw),
                "recordsTotal" => intval($totalData),
                "recordsFiltered" => intval($totalFiltered),
                "data" => $data
            );
            Log::info('End all hospital list');
            return response()->json($jsonData);
        } catch (\Exception $ex) {
            Log::info('Exception all hospital list');
            Log::info($ex);
            return response()->json([]);
        }
    }
    public function getJsonInActiveData(Request $request, $manager_id = 0)
    {
        $currentURL =  request()->segment(2);
        if ($currentURL == $this->myBusinessPage) {
            $user = Auth::user();
            $manager = Manager::where('user_id', $user->id)->first();
            $manager_id = $manager ? $manager->id : 0;
        }
        try {
            Log::info('Start all hospital list');
            $columns = array(
                0 => 'users.id',
                1 => 'users_detail.name',
                2 => 'countries.name',
                3 => 'users_detail.mobile',
                4 => 'user_credits.credits',
                5 => 'managers.name',
                6 => 'users.created_at',
                7 => 'avg_rating',
                8 => 'business_license_number',
                9 => 'is_user_active',
                10 => 'log',
                11 => 'action',
            );
            $limit = $request->input('length');
            $start = $request->input('start');
            $search = $request->input('search.value');
            $draw = $request->input('draw');
            $order = $columns[$request->input('order.0.column')];
            $dir = $request->input('order.0.dir');
            $adminTimezone = $this->getAdminUserTimezone();

            $shopCountQuery = '(CASE
                WHEN user_entity_relation.entity_type_id = 1 THEN  count(DISTINCT shops.id)
                ELSE "0"
                END)';

            $shopHospitalCountAmount = '(CASE
                WHEN user_entity_relation.entity_type_id = 1 THEN  count(DISTINCT shops.id)
                WHEN user_entity_relation.entity_type_id = 2 THEN count(DISTINCT hospitals.id)
                ELSE ""
                END) * credit_plans.amount ';

            $query = User::join('user_entity_relation', 'user_entity_relation.user_id', 'users.id')
                ->join('users_detail', 'users_detail.user_id', 'users.id')
                ->join('user_credits', 'user_credits.user_id', 'users.id')
                ->join('countries', 'users_detail.country_id', 'countries.id')
                ->leftjoin('shops', function ($query) {
                    $query->on('users.id', '=', 'shops.user_id')
                        ->whereNull('shops.deleted_at')
                        ->where('shops.status_id', Status::INACTIVE);
                    //->whereRaw('shops.id IN (select MAX(a2.id) from shops as a2 group by a2.user_id)');
                })
                ->leftjoin('linked_social_profiles', 'shops.id', 'linked_social_profiles.shop_id')
                ->leftjoin('credit_plans', function ($query) {
                    $query->on('credit_plans.package_plan_id', '=', 'users_detail.package_plan_id')
                        ->whereRaw('credit_plans.entity_type_id = user_entity_relation.entity_type_id');
                })
                ->leftjoin('hospitals', function ($query) {
                    $query->on('user_entity_relation.entity_id', '=', 'hospitals.id')
                        ->where('hospitals.status_id', Status::INACTIVE);
                })
                ->leftjoin('reviews', function ($join) {
                    $join->on('hospitals.id', '=', 'reviews.entity_id')
                        ->where('reviews.entity_type_id',  EntityTypes::HOSPITAL);
                })
                ->leftjoin('addresses', function ($join) {
                    $join->on('user_entity_relation.entity_id', '=', 'addresses.entity_id')
                        ->whereIn('addresses.entity_type_id',  [EntityTypes::HOSPITAL, EntityTypes::SHOP]);
                })
                ->leftjoin('managers', 'managers.id', 'users_detail.manager_id')
                ->whereIN('user_entity_relation.entity_type_id', [EntityTypes::HOSPITAL, EntityTypes::SHOP])
                /* ->where(function($q){
                    $q->where('shops.status_id', Status::INACTIVE)
                        ->orWhere('hospitals.status_id', Status::INACTIVE);
                }) */
                ->whereIn('users.status_id', [Status::INACTIVE, Status::ACTIVE]);
            if ($manager_id && $manager_id != 0) {
                $query = $query->where('managers.id', $manager_id);
            }
            $query = $query->select(
                'addresses.*',
                'users.id',
                'users.id as user_id',
                'user_entity_relation.entity_type_id',
                'users.status_id',
                'users.created_at',
                'users.email',
                'users_detail.name as user_name',
                'users_detail.mobile',
                'user_credits.credits',
                \DB::raw('(CASE
                    WHEN user_entity_relation.entity_type_id = 1 THEN  shops.uuid
                    ELSE ""
                    END) AS shop_uuid'),
                \DB::raw('(CASE
                    WHEN user_entity_relation.entity_type_id = 1 THEN  shops.business_license_number
                    WHEN user_entity_relation.entity_type_id = 2 THEN hospitals.business_license_number
                    ELSE ""
                    END) AS business_license_number'),
                \DB::raw('(CASE
                    WHEN user_entity_relation.entity_type_id = 1 THEN  shops.email
                    WHEN user_entity_relation.entity_type_id = 2 THEN hospitals.email
                    ELSE ""
                    END) AS email_address'),
                \DB::raw('(CASE
                    WHEN user_entity_relation.entity_type_id = 1 THEN  shops.main_name
                    WHEN user_entity_relation.entity_type_id = 2 THEN hospitals.main_name
                    ELSE ""
                    END) AS main_name'),
                \DB::raw('(CASE
                    WHEN user_entity_relation.entity_type_id = 1 THEN  shops.shop_name
                    ELSE ""
                    END) AS sub_name'),
                \DB::raw('(CASE
                    WHEN user_entity_relation.entity_type_id = 1 THEN  shops.created_at
                    WHEN user_entity_relation.entity_type_id = 2 THEN hospitals.created_at
                    ELSE ""
                    END) AS business_created_date'),
                // 'countries.name as country_name',
                DB::raw('round(AVG(reviews.rating),1) as avg_rating'),
                DB::raw('group_concat(user_entity_relation.entity_type_id) as entity_type_id_array'),
                'managers.name as manager_name',
                'credit_plans.amount',
                DB::raw('group_concat(DISTINCT linked_social_profiles.social_name) as social_names'),
                $this->shopNameConcat
            )
                ->selectRaw("{$shopCountQuery} AS total_shop_count")
                ->selectRaw("{$shopHospitalCountAmount} AS total_plan_amount")
                ->selectRaw("(CASE WHEN {$shopHospitalCountAmount} <= user_credits.credits THEN  1
                    ELSE 0
                    END) AS is_user_active")
                ->groupBy('users.id');

            if (!empty($search)) {
                $query = $query->where(function ($q) use ($search) {
                    $q->where('users_detail.name', 'LIKE', "%{$search}%")
                        ->orWhere('countries.name', 'LIKE', "%{$search}%")
                        ->orWhere('users_detail.mobile', 'LIKE', "%{$search}%")
                        ->orWhere('users.created_at', 'LIKE', "%{$search}%")
                        ->orWhere('user_credits.credits', 'LIKE', "%{$search}%")
                        ->orWhere('managers.name', 'LIKE', "%{$search}%")
                        ->orWhere('shops.shop_name', 'LIKE', "%{$search}%")
                        ->orWhere('shops.main_name', 'LIKE', "%{$search}%")
                        ->orWhere('hospitals.main_name', 'LIKE', "%{$search}%")
                        ->orWhere('linked_social_profiles.social_name', 'LIKE', "%{$search}%");
                });

                $totalFiltered = $query->count();
            }

            $query = $query->havingRaw("{$shopHospitalCountAmount} > user_credits.credits");

            $totalData = count($query->get());
            $totalFiltered = $totalData;

            $hospitals = $query
                ->offset($start)
                ->limit($limit)
                ->orderBy($order, $dir)
                ->get();
            $data = array();
            if (!empty($hospitals)) {
                foreach ($hospitals as $value) {
                    $id = $value['id'];
                    $shopViewLink = $this->getViewProfileURL($value);
                    $entity_type_id_array = explode(",", $value['entity_type_id_array']);
                    $entity_type_id_array = collect($entity_type_id_array)->unique()->values()->toArray();

                    if (in_array(EntityTypes::HOSPITAL, $entity_type_id_array)) {
                        $view = route('admin.business-client.hospital.show', $id);
                        $viewButton = "<a role='button' href='javascript:void(0)' onclick='viewProfile(" . $id . ")'  title='' data-original-title='View' class='btn btn-primary btn-sm' data-toggle='tooltip'><i class='fas fa-eye'></i></a>";
                        $nestedData['avg_rating'] = $value['avg_rating'];
                        $business_name = $value['main_name'];
                    } else {
                        $view = route('admin.business-client.shop.show', $id);
                        $viewButton = "<a role='button' href='javascript:void(0)' onclick='viewShopProfile(" . $id . ")'  title='' data-original-title='View' class='btn btn-primary btn-sm' data-toggle='tooltip'><i class='fas fa-eye'></i></a>";
                        $reviews = Reviews::join('shops', 'shops.id', 'reviews.entity_id')
                            ->where('reviews.entity_type_id', EntityTypes::SHOP)
                            ->where('shops.user_id', $value['id'])
                            ->select('reviews.*', DB::raw('round(AVG(reviews.rating),1) as avg_rating'))->get();
                        $nestedData['avg_rating'] = $reviews[0]->avg_rating;
                        $business_name = $value['main_name'] != "" ? $value['main_name'] . "/" : "";
                        $business_name .= $value['sub_name'];
                        $business_name = $this->displayBusinessName($value['business_name_group'], $business_name);
                    }
                    $nestedData['checkbox'] = "<td><div class=\"custom-checkbox custom-control\">
                    <input type=\"checkbox\" data-checkboxes=\"mygroup\" class=\"custom-control-input checkbox_id check-all-hospital\" id=\"$id\" data-id=\"$id\" value=\"$id\" name=\"checkbox_id[]\"><label for=\"$id\" class=\"custom-control-label\">&nbsp;</label></div></td>";
                    $address = $value['address'];
                    $address .= $value['address2'] ? ',' . $value['address2'] : '';
                    $address .= $value['city_name'] ? ',' . $value['city_name'] : '';
                    $address .= $value['state_name'] ? ',' . $value['state_name'] : '';
                    $address .= $value['country_name'] ? ',' . $value['country_name'] : '';
                    // $nestedData['name'] = $value['user_name'];

                    $displayEmail = $value['email_address'] ?? $value['email'];
                    $nestedData['name'] = $business_name . "|" . $displayEmail;
                    $nestedData['mobile'] = $value['mobile'];
                    $nestedData['address'] = $address;
                    $purchasedCredits = UserCreditHistory::where('user_id', $id)->where('type', UserCreditHistory::RELOAD)->sum('amount');
                    $nestedData['credits'] = number_format($purchasedCredits, 0) . "|" . number_format($value['credits'], 0);
                    $nestedData['business_license_number'] = $value['business_license_number'];
                    $nestedData['join_by'] = $value['manager_name'];
                    // $nestedData['date'] = $value['created_at'];
                    $nestedData['date'] = $this->formatDateTimeCountryWise($value['business_created_date'], $adminTimezone, 'd-m-Y H:i');

                    /* if ($value['is_user_active'] == true) {
                        $nestedData['status'] = '<span class="badge badge-success">&nbsp;</span>';
                    } else {
                    } */
                    $nestedData['status'] = '<span class="badge badge-secondary">&nbsp;</span>';
                    $seeButton = "<a role='button' href='javascript:void(0)' onclick='viewLogs(" . $id . ")' title='' data-original-title='View' class='btn btn-primary btn-sm' data-toggle='tooltip'>see</a>";
                    $nestedData['credit_purchase_log'] = $seeButton;

                    if (in_array(EntityTypes::HOSPITAL, $entity_type_id_array)) {
                        $nestedData['status'] .= $this->isOutsideHospital($value['user_id']);
                    } else {
                        $nestedData['status'] .= $this->isOutsideShop($value['user_id']);
                    }

                    $nestedData['referral'] = $this->getReferralDetail($value['user_id']);
                    $nestedData['shop_profile'] = $shopViewLink . "<br/>" . str_replace(',', '<br/>', $value['social_names']);

                    $user = Auth::user();
                    $editButton =  $user->hasRole('Admin') ? "<a href='javascript:void(0)' role='button' onclick='editCredits(" . $id . ")' class='btn btn-primary btn-sm mx-1' data-toggle='tooltip' data-original-title='Edit'><i class='fa fa-edit'></a>" : "";
                    $nestedData['actions'] = "<div class='d-flex'>$viewButton $editButton</div>";
                    $data[] = $nestedData;
                }
            }
            $jsonData = array(
                "draw" => intval($draw),
                "recordsTotal" => intval($totalData),
                "recordsFiltered" => intval($totalFiltered),
                "data" => $data
            );
            Log::info('End all hospital list');
            return response()->json($jsonData);
        } catch (\Exception $ex) {
            Log::info('Exception all hospital list');
            Log::info($ex);
            return response()->json([]);
        }
    }

    public function getJsonPendingData(Request $request, $manager_id = 0)
    {
        $currentURL =  request()->segment(2);
        if ($currentURL == $this->myBusinessPage) {
            $user = Auth::user();
            $manager = Manager::where('user_id', $user->id)->first();
            $manager_id = $manager ? $manager->id : 0;
        }
        try {
            Log::info('Start all hospital list');
            $columns = array(
                0 => 'users.id',
                1 => 'users_detail.name',
                2 => 'countries.name',
                3 => 'users_detail.mobile',
                4 => 'user_credits.credits',
                5 => 'managers.name',
                6 => 'users.created_at',
                7 => 'avg_rating',
                8 => 'business_license_number',
                9 => 'is_user_active',
                10 => 'log',
                11 => 'action',
            );
            $limit = $request->input('length');
            $start = $request->input('start');
            $search = $request->input('search.value');
            $draw = $request->input('draw');
            $order = $columns[$request->input('order.0.column')];
            $dir = $request->input('order.0.dir');
            $adminTimezone = $this->getAdminUserTimezone();

            $shopCountQuery = '(CASE
                WHEN user_entity_relation.entity_type_id = 1 THEN  count(DISTINCT shops.id)
                ELSE "0"
                END)';

            $shopHospitalCountAmount = '(CASE
                WHEN user_entity_relation.entity_type_id = 1 THEN  count(DISTINCT shops.id)
                WHEN user_entity_relation.entity_type_id = 2 THEN count(DISTINCT hospitals.id)
                ELSE ""
                END) * credit_plans.amount ';

            $query = User::join('user_entity_relation', 'user_entity_relation.user_id', 'users.id')
                ->join('users_detail', 'users_detail.user_id', 'users.id')
                ->join('user_credits', 'user_credits.user_id', 'users.id')
                ->join('countries', 'users_detail.country_id', 'countries.id')
                ->leftjoin('shops', function ($query) {
                    $query->on('users.id', '=', 'shops.user_id')
                        ->whereNull('shops.deleted_at')
                        ->where('shops.status_id', Status::PENDING);
                    //->whereRaw('shops.id IN (select MAX(a2.id) from shops as a2 group by a2.user_id)');
                })
                ->leftjoin('linked_social_profiles', 'shops.id', 'linked_social_profiles.shop_id')
                ->leftjoin('credit_plans', function ($query) {
                    $query->on('credit_plans.package_plan_id', '=', 'users_detail.package_plan_id')
                        ->whereRaw('credit_plans.entity_type_id = user_entity_relation.entity_type_id');
                })
                ->leftjoin('hospitals', function ($query) {
                    $query->on('user_entity_relation.entity_id', '=', 'hospitals.id')
                        ->where('hospitals.status_id', Status::PENDING);
                })
                ->leftjoin('reviews', function ($join) {
                    $join->on('hospitals.id', '=', 'reviews.entity_id')
                        ->where('reviews.entity_type_id',  EntityTypes::HOSPITAL);
                })
                ->leftjoin('addresses', function ($join) {
                    $join->on('user_entity_relation.entity_id', '=', 'addresses.entity_id')
                        ->whereIn('addresses.entity_type_id',  [EntityTypes::HOSPITAL, EntityTypes::SHOP]);
                })
                ->leftjoin('managers', 'managers.id', 'users_detail.manager_id')
                ->whereIN('user_entity_relation.entity_type_id', [EntityTypes::HOSPITAL, EntityTypes::SHOP])
                ->where(function ($q) {
                    $q->where('shops.status_id', Status::PENDING)
                        ->orWhere('hospitals.status_id', Status::PENDING);
                })
                ->whereIn('users.status_id', [Status::INACTIVE, Status::ACTIVE]);
            if ($manager_id && $manager_id != 0) {
                $query = $query->where('managers.id', $manager_id);
            }
            $query = $query->select(
                'addresses.*',
                'users.id',
                'users.id as user_id',
                'user_entity_relation.entity_type_id',
                'users.status_id',
                'users.created_at',
                'users.email',
                'users_detail.name as user_name',
                'users_detail.mobile',
                'user_credits.credits',
                \DB::raw('(CASE
                    WHEN user_entity_relation.entity_type_id = 1 THEN  shops.uuid
                    ELSE ""
                    END) AS shop_uuid'),
                \DB::raw('(CASE
                    WHEN user_entity_relation.entity_type_id = 1 THEN  shops.business_license_number
                    WHEN user_entity_relation.entity_type_id = 2 THEN hospitals.business_license_number
                    ELSE ""
                    END) AS business_license_number'),
                \DB::raw('(CASE
                    WHEN user_entity_relation.entity_type_id = 1 THEN  shops.email
                    WHEN user_entity_relation.entity_type_id = 2 THEN hospitals.email
                    ELSE ""
                    END) AS email_address'),
                \DB::raw('(CASE
                    WHEN user_entity_relation.entity_type_id = 1 THEN  shops.main_name
                    WHEN user_entity_relation.entity_type_id = 2 THEN hospitals.main_name
                    ELSE ""
                    END) AS main_name'),
                \DB::raw('(CASE
                    WHEN user_entity_relation.entity_type_id = 1 THEN  shops.shop_name
                    ELSE ""
                    END) AS sub_name'),
                \DB::raw('(CASE
                    WHEN user_entity_relation.entity_type_id = 1 THEN  shops.created_at
                    WHEN user_entity_relation.entity_type_id = 2 THEN hospitals.created_at
                    ELSE ""
                    END) AS business_created_date'),
                // 'countries.name as country_name',
                DB::raw('round(AVG(reviews.rating),1) as avg_rating'),
                DB::raw('group_concat(user_entity_relation.entity_type_id) as entity_type_id_array'),
                'managers.name as manager_name',
                'credit_plans.amount',
                DB::raw('group_concat(DISTINCT linked_social_profiles.social_name) as social_names'),
                $this->shopNameConcat
            )
                ->selectRaw("{$shopCountQuery} AS total_shop_count")
                ->selectRaw("{$shopHospitalCountAmount} AS total_plan_amount")
                ->selectRaw("(CASE WHEN {$shopHospitalCountAmount} <= user_credits.credits THEN  1
                    ELSE 0
                    END) AS is_user_active")
                ->groupBy('users.id');

            if (!empty($search)) {
                $query = $query->where(function ($q) use ($search) {
                    $q->where('users_detail.name', 'LIKE', "%{$search}%")
                        ->orWhere('countries.name', 'LIKE', "%{$search}%")
                        ->orWhere('users_detail.mobile', 'LIKE', "%{$search}%")
                        ->orWhere('users.created_at', 'LIKE', "%{$search}%")
                        ->orWhere('user_credits.credits', 'LIKE', "%{$search}%")
                        ->orWhere('managers.name', 'LIKE', "%{$search}%")
                        ->orWhere('shops.shop_name', 'LIKE', "%{$search}%")
                        ->orWhere('shops.main_name', 'LIKE', "%{$search}%")
                        ->orWhere('hospitals.main_name', 'LIKE', "%{$search}%")
                        ->orWhere('linked_social_profiles.social_name', 'LIKE', "%{$search}%");
                });

                $totalFiltered = $query->count();
            }

            // $query = $query->havingRaw("{$shopHospitalCountAmount} > user_credits.credits");

            $totalData = count($query->get());
            $totalFiltered = $totalData;

            $hospitals = $query
                ->offset($start)
                ->limit($limit)
                ->orderBy($order, $dir)
                ->get();
            $data = array();
            if (!empty($hospitals)) {
                foreach ($hospitals as $value) {
                    $id = $value['id'];
                    $shopViewLink = $this->getViewProfileURL($value);
                    $entity_type_id_array = explode(",", $value['entity_type_id_array']);
                    $entity_type_id_array = collect($entity_type_id_array)->unique()->values()->toArray();

                    if (in_array(EntityTypes::HOSPITAL, $entity_type_id_array)) {
                        $view = route('admin.business-client.hospital.show', $id);
                        $viewButton = "<a role='button' href='javascript:void(0)' onclick='viewProfile(" . $id . ")'  title='' data-original-title='View' class='btn btn-primary btn-sm' data-toggle='tooltip'><i class='fas fa-eye'></i></a>";
                        $nestedData['avg_rating'] = $value['avg_rating'];
                        $business_name = $value['main_name'];
                    } else {
                        $view = route('admin.business-client.shop.show', $id);
                        $viewButton = "<a role='button' href='javascript:void(0)' onclick='viewShopProfile(" . $id . ")'  title='' data-original-title='View' class='btn btn-primary btn-sm' data-toggle='tooltip'><i class='fas fa-eye'></i></a>";
                        $reviews = Reviews::join('shops', 'shops.id', 'reviews.entity_id')
                            ->where('reviews.entity_type_id', EntityTypes::SHOP)
                            ->where('shops.user_id', $value['id'])
                            ->select('reviews.*', DB::raw('round(AVG(reviews.rating),1) as avg_rating'))->get();
                        $nestedData['avg_rating'] = $reviews[0]->avg_rating;
                        $business_name = $value['main_name'] != "" ? $value['main_name'] . "/" : "";
                        $business_name .= $value['sub_name'];
                        $business_name = $this->displayBusinessName($value['business_name_group'], $business_name);
                    }
                    $nestedData['checkbox'] = "<td><div class=\"custom-checkbox custom-control\">
                    <input type=\"checkbox\" data-checkboxes=\"mygroup\" class=\"custom-control-input checkbox_id check-all-hospital\" id=\"$id\" data-id=\"$id\" value=\"$id\" name=\"checkbox_id[]\"><label for=\"$id\" class=\"custom-control-label\">&nbsp;</label></div></td>";
                    $address = $value['address'];
                    $address .= $value['address2'] ? ',' . $value['address2'] : '';
                    $address .= $value['city_name'] ? ',' . $value['city_name'] : '';
                    $address .= $value['state_name'] ? ',' . $value['state_name'] : '';
                    $address .= $value['country_name'] ? ',' . $value['country_name'] : '';
                    // $nestedData['name'] = $value['user_name'];

                    $displayEmail = $value['email_address'] ?? $value['email'];
                    $nestedData['name'] = $business_name . "|" . $displayEmail;
                    $nestedData['mobile'] = $value['mobile'];
                    $nestedData['address'] = $address;
                    $purchasedCredits = UserCreditHistory::where('user_id', $id)->where('type', UserCreditHistory::RELOAD)->sum('amount');
                    $nestedData['credits'] = number_format($purchasedCredits, 0) . "|" . number_format($value['credits'], 0);
                    $nestedData['business_license_number'] = $value['business_license_number'];
                    $nestedData['join_by'] = $value['manager_name'];
                    // $nestedData['date'] = $value['created_at'];
                    $nestedData['date'] = $this->formatDateTimeCountryWise($value['business_created_date'], $adminTimezone, 'd-m-Y H:i');

                    //if ($value['is_user_active'] == true) {
                    $nestedData['status'] = '<span class="badge" style="background-color: #fff700;">&nbsp;</span>';
                    /* } else {
                        $nestedData['status'] = '<span class="badge badge-secondary">&nbsp;</span>';
                    }  */
                    $seeButton = "<a role='button' href='javascript:void(0)' onclick='viewLogs(" . $id . ")' title='' data-original-title='View' class='btn btn-primary btn-sm' data-toggle='tooltip'>see</a>";
                    $nestedData['credit_purchase_log'] = $seeButton;

                    if (in_array(EntityTypes::HOSPITAL, $entity_type_id_array)) {
                        $nestedData['status'] .= $this->isOutsideHospital($value['user_id']);
                    } else {
                        $nestedData['status'] .= $this->isOutsideShop($value['user_id']);
                    }
                    $nestedData['referral'] = $this->getReferralDetail($value['user_id']);
                    $nestedData['shop_profile'] = $shopViewLink . "<br/>" . str_replace(',', '<br/>', $value['social_names']);

                    $user = Auth::user();
                    $editButton =  $user->hasRole('Admin') ? "<a href='javascript:void(0)' role='button' onclick='editCredits(" . $id . ")' class='btn btn-primary btn-sm mx-1' data-toggle='tooltip' data-original-title='Edit'><i class='fa fa-edit'></a>" : "";
                    $nestedData['actions'] = "<div class='d-flex'>$viewButton $editButton</div>";
                    $data[] = $nestedData;
                }
            }
            $jsonData = array(
                "draw" => intval($draw),
                "recordsTotal" => intval($totalData),
                "recordsFiltered" => intval($totalFiltered),
                "data" => $data
            );
            Log::info('End all hospital list');
            return response()->json($jsonData);
        } catch (\Exception $ex) {
            Log::info('Exception all hospital list');
            Log::info($ex);
            return response()->json([]);
        }
    }


    /* ================ Hospital Code Start ======================= */
    public function indexHospital(Request $request)
    {
        $manager_id = $request->has('manager_id') ? $request->manager_id : 0;
        $title = 'Hospital Client';
        $totalUsers = UserEntityRelation::join('users', 'users.id', 'user_entity_relation.user_id')->whereNotNull('users.email')->distinct('user_id')->count('user_id');;
        $totalShopsQuery = UserEntityRelation::join('users_detail', 'users_detail.user_id', 'user_entity_relation.user_id');
        $totalHospitalsQuery = UserEntityRelation::join('users_detail', 'users_detail.user_id', 'user_entity_relation.user_id');
        $lastMonthIncomeQuery = ReloadCoinRequest::join('users_detail', 'users_detail.user_id', 'reload_coins_request.user_id');
        $totalIncomeQuery = ReloadCoinRequest::join('users_detail', 'users_detail.user_id', 'reload_coins_request.user_id');
        $currentMonthIncomeQuery = ReloadCoinRequest::join('users_detail', 'users_detail.user_id', 'reload_coins_request.user_id');

        if ($manager_id && $manager_id != 0) {
            $totalShopsQuery = $totalShopsQuery->where('users_detail.manager_id', $manager_id);
            $totalHospitalsQuery = $totalHospitalsQuery->where('users_detail.manager_id', $manager_id);
            $lastMonthIncomeQuery = $lastMonthIncomeQuery->where('users_detail.manager_id', $manager_id);
            $totalIncomeQuery = $totalIncomeQuery->where('users_detail.manager_id', $manager_id);
            $currentMonthIncomeQuery = $currentMonthIncomeQuery->where('users_detail.manager_id', $manager_id);
        }

        $totalShops = $totalShopsQuery->where('entity_type_id', EntityTypes::SHOP)->distinct('user_entity_relation.user_id')->count('user_entity_relation.user_id');
        $totalHospitals = $totalHospitalsQuery->where('entity_type_id', EntityTypes::HOSPITAL)->distinct('user_entity_relation.user_id')->count('user_entity_relation.user_id');
        $totalClients = UserEntityRelation::whereIn('entity_type_id', [EntityTypes::HOSPITAL, EntityTypes::SHOP])->distinct('user_id')->count('user_id');
        $dateS = Carbon::now()->startOfMonth()->subMonth(1);
        $dateE = Carbon::now()->startOfMonth();
        $lastMonthIncome = $lastMonthIncomeQuery->where('status', ReloadCoinRequest::GIVE_COIN)->whereBetween('reload_coins_request.created_at', [$dateS, $dateE])
            ->sum('reload_coins_request.coin_amount');
        $lastMonthIncome = number_format($lastMonthIncome, 0);
        $totalIncome = $totalIncomeQuery->where('status', ReloadCoinRequest::GIVE_COIN)
            ->sum('reload_coins_request.coin_amount');
        $totalIncome = number_format($totalIncome, 0);
        $currentMonthIncome = $currentMonthIncomeQuery->where('status', ReloadCoinRequest::GIVE_COIN)->whereMonth('reload_coins_request.created_at', $dateE->month)
            ->sum('reload_coins_request.coin_amount');
        $currentMonthIncome = number_format($currentMonthIncome, 0);
        return view('admin.business-client.index-hospital', compact('manager_id', 'totalIncome', 'title', 'totalUsers', 'totalShops', 'totalHospitals', 'totalClients', 'lastMonthIncome', 'currentMonthIncome'));
    }

    public function getJsonAllHospitalData(Request $request, $manager_id = 0)
    {
        $currentURL =  request()->segment(2);
        if ($currentURL == $this->myBusinessPage) {
            $user = Auth::user();
            $manager = Manager::where('user_id', $user->id)->first();
            $manager_id = $manager ? $manager->id : 0;
        }
        try {
            Log::info('Start all hospital list');
            $columns = array(
                0 => 'users.id',
                1 => 'users_detail.name',
                2 => 'countries.name',
                3 => 'users_detail.mobile',
                4 => 'user_credits.credits',
                5 => 'managers.name',
                6 => 'users.created_at',
                7 => 'avg_rating',
                8 => 'hospitals.business_license_number',
                9 => 'is_user_active',
                10 => 'log',
                11 => 'action',
            );
            $limit = $request->input('length');
            $start = $request->input('start');
            $search = $request->input('search.value');
            $draw = $request->input('draw');
            $order = $columns[$request->input('order.0.column')];
            $dir = $request->input('order.0.dir');
            $adminTimezone = $this->getAdminUserTimezone();

            $shopHospitalCountAmount = 'count(DISTINCT hospitals.id) * credit_plans.amount ';

            $query = User::join('user_entity_relation', 'user_entity_relation.user_id', 'users.id')
                ->join('users_detail', 'users_detail.user_id', 'users.id')
                ->join('user_credits', 'user_credits.user_id', 'users.id')
                ->join('countries', 'users_detail.country_id', 'countries.id')
                ->join('hospitals', 'user_entity_relation.entity_id', 'hospitals.id')
                ->leftjoin('credit_plans', function ($query) {
                    $query->on('credit_plans.package_plan_id', '=', 'users_detail.package_plan_id')
                        ->where('credit_plans.entity_type_id', EntityTypes::HOSPITAL);
                })
                ->leftjoin('reviews', function ($join) {
                    $join->on('hospitals.id', '=', 'reviews.entity_id')
                        ->where('reviews.entity_type_id', EntityTypes::HOSPITAL);
                })
                ->join('addresses', function ($join) {
                    $join->on('hospitals.id', '=', 'addresses.entity_id')
                        ->where('addresses.entity_type_id', EntityTypes::HOSPITAL);
                })
                ->leftjoin('managers', 'managers.id', 'users_detail.manager_id')
                ->where('user_entity_relation.entity_type_id', EntityTypes::HOSPITAL)
                ->whereIn('users.status_id', [Status::ACTIVE, Status::INACTIVE]);
            if ($manager_id && $manager_id != 0) {
                $query = $query->where('managers.id', $manager_id);
            }
            $query = $query->select(
                'addresses.*',
                'users.id',
                'users.id as user_id',
                'users.status_id',
                'users.created_at',
                'users.email',
                'users_detail.name as user_name',
                'users_detail.mobile',
                'user_credits.credits',
                'hospitals.business_license_number',
                'hospitals.main_name',
                'hospitals.email as email_address',
                'hospitals.created_at as business_created_date',
                // 'countries.name as country_name',
                'managers.name as manager_name',
                DB::raw('round(AVG(reviews.rating),1) as avg_rating')
            )
                ->selectRaw("{$shopHospitalCountAmount} AS total_plan_amount")
                ->selectRaw("(CASE WHEN {$shopHospitalCountAmount} <= user_credits.credits THEN  1
                    ELSE 0
                    END) AS is_user_active")
                ->groupBy('users.id');

            if (!empty($search)) {
                $query = $query->where(function ($q) use ($search) {
                    $q->where('users_detail.name', 'LIKE', "%{$search}%")
                        ->orWhere('countries.name', 'LIKE', "%{$search}%")
                        ->orWhere('users_detail.mobile', 'LIKE', "%{$search}%")
                        ->orWhere('users.created_at', 'LIKE', "%{$search}%")
                        ->orWhere('user_credits.credits', 'LIKE', "%{$search}%")
                        ->orWhere('managers.name', 'LIKE', "%{$search}%")
                        ->orWhere('hospitals.main_name', 'LIKE', "%{$search}%");
                });

                $totalFiltered = $query->count();
            }

            $totalData = count($query->get());
            $totalFiltered = $totalData;

            $hospitals = $query->offset($start)
                ->limit($limit)
                ->orderBy($order, $dir)
                ->get();
            // dd($hospitals);
            $data = array();
            if (!empty($hospitals)) {
                foreach ($hospitals as $value) {
                    $id = $value['id'];
                    $view = route('admin.business-client.hospital.show', $id);
                    $nestedData['checkbox'] = "<td><div class=\"custom-checkbox custom-control\">
                    <input type=\"checkbox\" data-checkboxes=\"mygroup\" class=\"custom-control-input checkbox_id check-all-hospital\" id=\"$id\" data-id=\"$id\" value=\"$id\" name=\"checkbox_id[]\"><label for=\"$id\" class=\"custom-control-label\">&nbsp;</label></div></td>";
                    $address = $value['address'];
                    $address .= $value['address2'] ? ',' . $value['address2'] : '';
                    $address .= $value['city_name'] ? ',' . $value['city_name'] : '';
                    $address .= $value['state_name'] ? ',' . $value['state_name'] : '';
                    $address .= $value['country_name'] ? ',' . $value['country_name'] : '';
                    // $nestedData['name'] = $value['user_name'];
                    $displayEmail = $value['email_address'] ?? $value['email'];
                    $nestedData['name'] = $value['main_name'] . "|" . $displayEmail;
                    $nestedData['mobile'] = $value['mobile'];
                    $nestedData['address'] = $address;
                    $purchasedCredits = UserCreditHistory::where('user_id', $id)->where('type', UserCreditHistory::RELOAD)->sum('amount');
                    $nestedData['credits'] = number_format($purchasedCredits, 0) . "|" . number_format($value['credits'], 0);
                    $nestedData['business_license_number'] = $value['business_license_number'];
                    $nestedData['avg_rating'] = $value['avg_rating'];
                    $nestedData['join_by'] = $value['manager_name'];
                    // $nestedData['date'] = $value['created_at'];
                    $nestedData['date'] = $this->formatDateTimeCountryWise($value['business_created_date'], $adminTimezone, 'd-m-Y H:i');

                    if ($value['is_user_active'] == true) {
                        $nestedData['status'] = '<span class="badge badge-success">&nbsp;</span>';
                    } else {
                        $nestedData['status'] = '<span class="badge badge-secondary">&nbsp;</span>';
                    }

                    $hospitalPendingcount = DB::table('user_entity_relation')->leftjoin('hospitals', 'user_entity_relation.entity_id', 'hospitals.id')
                        ->where('user_entity_relation.entity_type_id', EntityTypes::HOSPITAL)->where('user_entity_relation.user_id', $value['user_id'])
                        ->where('hospitals.status_id', Status::PENDING)->count();

                    if (!empty($hospitalPendingcount)) {
                        $nestedData['status'] = '<span class="badge" style="background-color: #fff700;">&nbsp;</span>';
                    }

                    $nestedData['status'] .= $this->isOutsideHospital($value['user_id']);
                    $nestedData['referral'] = $this->getReferralDetail($value['user_id']);
                    $nestedData['shop_profile'] = '';

                    $seeButton = "<a role='button' href='javascript:void(0)' onclick='viewLogs(" . $id . ")' title='' data-original-title='View' class='btn btn-primary btn-sm' data-toggle='tooltip'>see</a>";
                    $nestedData['credit_purchase_log'] = $seeButton;
                    $viewButton = "<a role='button' href='javascript:void(0)' onclick='viewProfile(" . $id . ")'  title='' data-original-title='View' class='btn btn-primary btn-sm' data-toggle='tooltip'><i class='fas fa-eye'></i></a>";
                    $user = Auth::user();
                    $editButton =  $user->hasRole('Admin') ? "<a href='javascript:void(0)' role='button' onclick='editCredits(" . $id . ")' class='btn btn-primary btn-sm mx-1' data-toggle='tooltip' data-original-title='Edit'><i class='fa fa-edit'></a>" : "";
                    $nestedData['actions'] = "<div class='d-flex'>$viewButton $editButton</div>";
                    $data[] = $nestedData;
                }
            }
            $jsonData = array(
                "draw" => intval($draw),
                "recordsTotal" => intval($totalData),
                "recordsFiltered" => intval($totalFiltered),
                "data" => $data
            );
            Log::info('End all hospital list');
            return response()->json($jsonData);
        } catch (\Exception $ex) {
            Log::info('Exception all hospital list');
            Log::info($ex);
            return response()->json([]);
        }
    }
    public function getJsonActiveHospitalData(Request $request, $manager_id = 0)
    {
        $currentURL =  request()->segment(2);
        if ($currentURL == $this->myBusinessPage) {
            $user = Auth::user();
            $manager = Manager::where('user_id', $user->id)->first();
            $manager_id = $manager ? $manager->id : 0;
        }
        try {
            Log::info('Start all hospital list');

            $columns = array(
                0 => 'users.id',
                1 => 'users_detail.name',
                2 => 'countries.name',
                3 => 'users_detail.mobile',
                4 => 'user_credits.credits',
                5 => 'managers.name',
                6 => 'users.created_at',
                7 => 'avg_rating',
                8 => 'hospitals.business_license_number',
                9 => 'is_user_active',
                10 => 'log',
                11 => 'action',
            );
            $limit = $request->input('length');
            $start = $request->input('start');
            $search = $request->input('search.value');
            $draw = $request->input('draw');
            $order = $columns[$request->input('order.0.column')];
            $dir = $request->input('order.0.dir');
            $adminTimezone = $this->getAdminUserTimezone();

            $shopHospitalCountAmount = 'count(DISTINCT hospitals.id) * credit_plans.amount ';

            $query = User::join('user_entity_relation', 'user_entity_relation.user_id', 'users.id')
                ->join('users_detail', 'users_detail.user_id', 'users.id')
                ->join('user_credits', 'user_credits.user_id', 'users.id')
                ->join('hospitals', 'user_entity_relation.entity_id', 'hospitals.id')
                ->leftjoin('credit_plans', function ($query) {
                    $query->on('credit_plans.package_plan_id', '=', 'users_detail.package_plan_id')
                        ->where('credit_plans.entity_type_id', EntityTypes::HOSPITAL);
                })
                ->leftjoin('reviews', function ($join) {
                    $join->on('hospitals.id', '=', 'reviews.entity_id')
                        ->where('reviews.entity_type_id', EntityTypes::HOSPITAL);
                })
                ->join('countries', 'users_detail.country_id', 'countries.id')
                ->leftjoin('managers', 'managers.id', 'users_detail.manager_id')
                ->join('addresses', function ($join) {
                    $join->on('hospitals.id', '=', 'addresses.entity_id')
                        ->where('addresses.entity_type_id', EntityTypes::HOSPITAL);
                })
                ->where('hospitals.status_id', Status::ACTIVE)
                ->where('user_entity_relation.entity_type_id', EntityTypes::HOSPITAL)
                ->whereIn('users.status_id', [Status::ACTIVE, Status::INACTIVE]);

            if ($manager_id && $manager_id != 0) {
                $query = $query->where('managers.id', $manager_id);
            }
            $query = $query->select(
                'addresses.*',
                'users.id',
                'users.id as user_id',
                'users.status_id',
                'users.created_at',
                'users.email',
                'users_detail.name as user_name',
                'users_detail.mobile',
                'user_credits.credits',
                'hospitals.business_license_number',
                'hospitals.main_name',
                'hospitals.email as email_address',
                'hospitals.created_at as business_created_date',
                'countries.name as country_name',
                'managers.name as manager_name',
                'credit_plans.amount',
                DB::raw('round(AVG(reviews.rating),1) as avg_rating')
            )
                ->selectRaw("{$shopHospitalCountAmount} AS total_plan_amount")
                ->selectRaw("(CASE WHEN {$shopHospitalCountAmount} <= user_credits.credits THEN  1
                    ELSE 0
                    END) AS is_user_active")
                ->groupBy('users.id');


            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('users_detail.name', 'LIKE', "%{$search}%")
                        ->orWhere('countries.name', 'LIKE', "%{$search}%")
                        ->orWhere('users_detail.mobile', 'LIKE', "%{$search}%")
                        ->orWhere('users.created_at', 'LIKE', "%{$search}%")
                        ->orWhere('user_credits.credits', 'LIKE', "%{$search}%")
                        ->orWhere('managers.name', 'LIKE', "%{$search}%")
                        ->orWhere('hospitals.main_name', 'LIKE', "%{$search}%");
                });
                //$totalFiltered = $query->count();
            }

            $query = $query->havingRaw("{$shopHospitalCountAmount} <= user_credits.credits");

            $totalData = count($query->get());
            $totalFiltered = $totalData;

            $hospitals = $query->offset($start)
                ->limit($limit)
                ->orderBy($order, $dir)
                ->get();


            $data = array();
            if (!empty($hospitals)) {
                foreach ($hospitals as $value) {
                    $id = $value['id'];
                    $view = route('admin.business-client.hospital.show', $id);
                    $nestedData['checkbox'] = "<td><div class=\"custom-checkbox custom-control\">
                    <input type=\"checkbox\" data-checkboxes=\"mygroup\" class=\"custom-control-input checkbox_id check-active-hospital\" id=\"$id\" data-id=\"$id\" value=\"$id\" name=\"checkbox_id[]\"><label for=\"$id\" class=\"custom-control-label\">&nbsp;</label></div></td>";
                    $address = $value['address'];
                    $address .= $value['address2'] ? ',' . $value['address2'] : '';
                    $address .= $value['city_name'] ? ',' . $value['city_name'] : '';
                    $address .= $value['state_name'] ? ',' . $value['state_name'] : '';
                    $address .= $value['country_name'] ? ',' . $value['country_name'] : '';
                    // $nestedData['name'] = $value['user_name'];
                    $displayEmail = $value['email_address'] ?? $value['email'];
                    $nestedData['name'] = $value['main_name'] . "|" . $displayEmail;
                    $nestedData['mobile'] = $value['mobile'];
                    $nestedData['address'] = $address;
                    $purchasedCredits = UserCreditHistory::where('user_id', $id)->where('type', UserCreditHistory::RELOAD)->sum('amount');
                    $nestedData['credits'] = number_format($purchasedCredits, 0) . "|" . number_format($value['credits'], 0);
                    $nestedData['business_license_number'] = $value['business_license_number'];
                    $nestedData['avg_rating'] = $value['avg_rating'];
                    $nestedData['join_by'] = $value['manager_name'];
                    // $nestedData['date'] = $value['created_at'];
                    $nestedData['date'] = $this->formatDateTimeCountryWise($value['business_created_date'], $adminTimezone, 'd-m-Y H:i');

                    $nestedData['status'] = '<span class="badge badge-success">&nbsp;</span>';
                    /* if ($value['status_id'] == Status::ACTIVE) {
                    } else {
                        $nestedData['status'] = '<span class="badge badge-secondary">&nbsp;</span>';
                    } */

                    $nestedData['status'] .= $this->isOutsideHospital($value['user_id']);
                    $nestedData['referral'] = $this->getReferralDetail($value['user_id']);
                    $nestedData['shop_profile'] = '';

                    $seeButton = "<a role='button' href='javascript:void(0)' onclick='viewLogs(" . $id . ")' title='' data-original-title='View' class='btn btn-primary btn-sm' data-toggle='tooltip'>see</a>";
                    $nestedData['credit_purchase_log'] = $seeButton;
                    $viewButton = "<a role='button' href='javascript:void(0)' onclick='viewProfile(" . $id . ")'  title='' data-original-title='View' class='btn btn-primary btn-sm' data-toggle='tooltip'><i class='fas fa-eye'></i></a>";
                    $user = Auth::user();
                    $editButton =  $user->hasRole('Admin') ? "<a href='javascript:void(0)' role='button' onclick='editCredits(" . $id . ")' class='btn btn-primary btn-sm mx-1' data-toggle='tooltip' data-original-title='Edit'><i class='fa fa-edit'></a>" : "";
                    $nestedData['actions'] = "<div class='d-flex'>$viewButton $editButton</div>";
                    $data[] = $nestedData;
                }
            }
            $jsonData = array(
                "draw" => intval($draw),
                "recordsTotal" => intval($totalData),
                "recordsFiltered" => intval($totalFiltered),
                "data" => $data
            );
            Log::info('End all hospital list');
            return response()->json($jsonData);
        } catch (\Exception $ex) {
            Log::info('Exception all hospital list');
            Log::info($ex);
            return response()->json([]);
        }
    }
    public function getJsonInactiveHospitalData(Request $request, $manager_id = 0)
    {
        $currentURL =  request()->segment(2);
        if ($currentURL == $this->myBusinessPage) {
            $user = Auth::user();
            $manager = Manager::where('user_id', $user->id)->first();
            $manager_id = $manager ? $manager->id : 0;
        }
        try {
            Log::info('Start all hospital list');

            $columns = array(
                0 => 'users.id',
                1 => 'users_detail.name',
                2 => 'countries.name',
                3 => 'users_detail.mobile',
                4 => 'user_credits.credits',
                5 => 'managers.name',
                6 => 'users.created_at',
                7 => 'avg_rating',
                8 => 'hospitals.business_license_number',
                9 => 'is_user_active',
                10 => 'log',
                11 => 'action',
            );
            $limit = $request->input('length');
            $start = $request->input('start');
            $search = $request->input('search.value');
            $draw = $request->input('draw');
            $order = $columns[$request->input('order.0.column')];
            $dir = $request->input('order.0.dir');
            $adminTimezone = $this->getAdminUserTimezone();

            $shopHospitalCountAmount = 'count(DISTINCT hospitals.id) * credit_plans.amount ';

            $query = User::join('user_entity_relation', 'user_entity_relation.user_id', 'users.id')
                ->join('users_detail', 'users_detail.user_id', 'users.id')
                ->join('user_credits', 'user_credits.user_id', 'users.id')
                ->join('countries', 'users_detail.country_id', 'countries.id')
                ->join('hospitals', 'user_entity_relation.entity_id', 'hospitals.id')
                ->leftjoin('credit_plans', function ($query) {
                    $query->on('credit_plans.package_plan_id', '=', 'users_detail.package_plan_id')
                        ->where('credit_plans.entity_type_id', EntityTypes::HOSPITAL);
                })
                ->leftjoin('reviews', function ($join) {
                    $join->on('hospitals.id', '=', 'reviews.entity_id')
                        ->where('reviews.entity_type_id', EntityTypes::HOSPITAL);
                })
                ->leftjoin('managers', 'managers.user_id', 'users.id')
                ->join('addresses', function ($join) {
                    $join->on('hospitals.id', '=', 'addresses.entity_id')
                        ->where('addresses.entity_type_id', EntityTypes::HOSPITAL);
                })
                ->where('hospitals.status_id', Status::INACTIVE)
                ->where('user_entity_relation.entity_type_id', EntityTypes::HOSPITAL)
                ->whereIn('users.status_id', [Status::ACTIVE, Status::INACTIVE]);
            if ($manager_id && $manager_id != 0) {
                $query = $query->where('managers.id', $manager_id);
            }
            $query = $query->select(
                'addresses.*',
                'users.id',
                'users.id as user_id',
                'users.status_id',
                'users.created_at',
                'users.email',
                'users_detail.name as user_name',
                'users_detail.mobile',
                'user_credits.credits',
                'hospitals.business_license_number',
                'hospitals.main_name',
                'hospitals.email as email_address',
                'hospitals.created_at as business_created_date',
                'countries.name as country_name',
                'managers.name as manager_name',
                'credit_plans.amount',
                DB::raw('round(AVG(reviews.rating),1) as avg_rating')
            )
                ->selectRaw("{$shopHospitalCountAmount} AS total_plan_amount")
                ->selectRaw("(CASE WHEN {$shopHospitalCountAmount} <= user_credits.credits THEN  1
                    ELSE 0
                    END) AS is_user_active")
                ->groupBy('users.id');

            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('users_detail.name', 'LIKE', "%{$search}%")
                        ->orWhere('countries.name', 'LIKE', "%{$search}%")
                        ->orWhere('users_detail.mobile', 'LIKE', "%{$search}%")
                        ->orWhere('users.created_at', 'LIKE', "%{$search}%")
                        ->orWhere('user_credits.credits', 'LIKE', "%{$search}%")
                        ->orWhere('managers.name', 'LIKE', "%{$search}%")
                        ->orWhere('hospitals.main_name', 'LIKE', "%{$search}%");
                });
                $totalFiltered = $query->count();
            }

            $query = $query->havingRaw("{$shopHospitalCountAmount} > user_credits.credits");

            $totalData = count($query->get());
            $totalFiltered = $totalData;

            $hospitals = $query->offset($start)
                ->limit($limit)
                ->orderBy($order, $dir)
                ->get();
            $data = array();
            if (!empty($hospitals)) {
                foreach ($hospitals as $value) {
                    $id = $value['id'];
                    $view = route('admin.business-client.hospital.show', $id);
                    $nestedData['checkbox'] = "<td><div class=\"custom-checkbox custom-control\">
                    <input type=\"checkbox\" data-checkboxes=\"mygroup\" class=\"custom-control-input checkbox_id check-inactive-hospital\" id=\"$id\" data-id=\"$id\" value=\"$id\" name=\"checkbox_id[]\"><label for=\"$id\" class=\"custom-control-label\">&nbsp;</label></div></td>";
                    $address = $value['address'];
                    $address .= $value['address2'] ? ',' . $value['address2'] : '';
                    $address .= $value['city_name'] ? ',' . $value['city_name'] : '';
                    $address .= $value['state_name'] ? ',' . $value['state_name'] : '';
                    $address .= $value['country_name'] ? ',' . $value['country_name'] : '';
                    // $nestedData['name'] = $value['user_name'];
                    $displayEmail = $value['email_address'] ?? $value['email'];
                    $nestedData['name'] = $value['main_name'] . "|" . $displayEmail;
                    $nestedData['mobile'] = $value['mobile'];
                    $nestedData['address'] = $address;
                    $purchasedCredits = UserCreditHistory::where('user_id', $id)->where('type', UserCreditHistory::RELOAD)->sum('amount');
                    $nestedData['credits'] = number_format($purchasedCredits, 0) . "|" . number_format($value['credits'], 0);
                    $nestedData['business_license_number'] = $value['business_license_number'];
                    $nestedData['avg_rating'] = $value['avg_rating'];
                    $nestedData['join_by'] = $value['manager_name'];
                    // $nestedData['date'] = $value['created_at'];
                    $nestedData['date'] = $this->formatDateTimeCountryWise($value['business_created_date'], $adminTimezone, 'd-m-Y H:i');

                    /* if ($value['is_user_active'] == true) {
                        $nestedData['status'] = '<span class="badge badge-success">&nbsp;</span>';
                    } else {
                    } */
                    $nestedData['status'] = '<span class="badge badge-secondary">&nbsp;</span>';

                    $nestedData['status'] .= $this->isOutsideHospital($value['user_id']);
                    $nestedData['referral'] = $this->getReferralDetail($value['user_id']);
                    $nestedData['shop_profile'] = '';

                    $seeButton = "<a role='button' href='javascript:void(0)' onclick='viewLogs(" . $id . ")' title='' data-original-title='View' class='btn btn-primary btn-sm' data-toggle='tooltip'>see</a>";
                    $nestedData['credit_purchase_log'] = $seeButton;
                    $viewButton = "<a role='button' href='javascript:void(0)' onclick='viewProfile(" . $id . ")'  title='' data-original-title='View' class='btn btn-primary btn-sm' data-toggle='tooltip'><i class='fas fa-eye'></i></a>";
                    $user = Auth::user();
                    $editButton =  $user->hasRole('Admin') ? "<a href='javascript:void(0)' role='button' onclick='editCredits(" . $id . ")' class='btn btn-primary btn-sm mx-1' data-toggle='tooltip' data-original-title='Edit'><i class='fa fa-edit'></a>" : "";
                    $nestedData['actions'] = "<div class='d-flex'>$viewButton $editButton</div>";
                    $data[] = $nestedData;
                }
            }
            $jsonData = array(
                "draw" => intval($draw),
                "recordsTotal" => intval($totalData),
                "recordsFiltered" => intval($totalFiltered),
                "data" => $data,
                'hospitals' => $hospitals
            );
            Log::info('End all hospital list');
            return response()->json($jsonData);
        } catch (\Exception $ex) {
            Log::info('Exception all hospital list');
            Log::info($ex);
            return response()->json([]);
        }
    }

    public function getJsonPendingHospitalData(Request $request, $manager_id = 0)
    {
        $currentURL =  request()->segment(2);
        if ($currentURL == $this->myBusinessPage) {
            $user = Auth::user();
            $manager = Manager::where('user_id', $user->id)->first();
            $manager_id = $manager ? $manager->id : 0;
        }
        try {
            Log::info('Start all hospital list');

            $columns = array(
                0 => 'users.id',
                1 => 'users_detail.name',
                2 => 'countries.name',
                3 => 'users_detail.mobile',
                4 => 'user_credits.credits',
                5 => 'managers.name',
                6 => 'users.created_at',
                7 => 'avg_rating',
                8 => 'hospitals.business_license_number',
                9 => 'is_user_active',
                10 => 'log',
                11 => 'action',
            );
            $limit = $request->input('length');
            $start = $request->input('start');
            $search = $request->input('search.value');
            $draw = $request->input('draw');
            $order = $columns[$request->input('order.0.column')];
            $dir = $request->input('order.0.dir');
            $adminTimezone = $this->getAdminUserTimezone();

            $shopHospitalCountAmount = 'count(DISTINCT hospitals.id) * credit_plans.amount ';

            $query = User::join('user_entity_relation', 'user_entity_relation.user_id', 'users.id')
                ->join('users_detail', 'users_detail.user_id', 'users.id')
                ->join('user_credits', 'user_credits.user_id', 'users.id')
                ->join('countries', 'users_detail.country_id', 'countries.id')
                ->join('hospitals', 'user_entity_relation.entity_id', 'hospitals.id')
                ->leftjoin('credit_plans', function ($query) {
                    $query->on('credit_plans.package_plan_id', '=', 'users_detail.package_plan_id')
                        ->where('credit_plans.entity_type_id', EntityTypes::HOSPITAL);
                })
                ->leftjoin('reviews', function ($join) {
                    $join->on('hospitals.id', '=', 'reviews.entity_id')
                        ->where('reviews.entity_type_id', EntityTypes::HOSPITAL);
                })
                ->leftjoin('managers', 'managers.user_id', 'users.id')
                ->join('addresses', function ($join) {
                    $join->on('hospitals.id', '=', 'addresses.entity_id')
                        ->where('addresses.entity_type_id', EntityTypes::HOSPITAL);
                })
                ->where('hospitals.status_id', Status::PENDING)
                ->where('user_entity_relation.entity_type_id', EntityTypes::HOSPITAL)
                ->whereIn('users.status_id', [Status::ACTIVE, Status::INACTIVE]);
            if ($manager_id && $manager_id != 0) {
                $query = $query->where('managers.id', $manager_id);
            }
            $query = $query->select(
                'addresses.*',
                'users.id',
                'users.id as user_id',
                'users.status_id',
                'users.created_at',
                'users.email',
                'users_detail.name as user_name',
                'users_detail.mobile',
                'user_credits.credits',
                'hospitals.business_license_number',
                'hospitals.main_name',
                'hospitals.email as email_address',
                'hospitals.created_at as business_created_date',
                'countries.name as country_name',
                'managers.name as manager_name',
                'credit_plans.amount',
                DB::raw('round(AVG(reviews.rating),1) as avg_rating')
            )
                ->selectRaw("{$shopHospitalCountAmount} AS total_plan_amount")
                ->selectRaw("(CASE WHEN {$shopHospitalCountAmount} <= user_credits.credits THEN  1
                    ELSE 0
                    END) AS is_user_active")
                ->groupBy('users.id');

            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('users_detail.name', 'LIKE', "%{$search}%")
                        ->orWhere('countries.name', 'LIKE', "%{$search}%")
                        ->orWhere('users_detail.mobile', 'LIKE', "%{$search}%")
                        ->orWhere('users.created_at', 'LIKE', "%{$search}%")
                        ->orWhere('user_credits.credits', 'LIKE', "%{$search}%")
                        ->orWhere('managers.name', 'LIKE', "%{$search}%")
                        ->orWhere('hospitals.main_name', 'LIKE', "%{$search}%");
                });
                $totalFiltered = $query->count();
            }

            $query = $query->havingRaw("{$shopHospitalCountAmount} > user_credits.credits");

            $totalData = count($query->get());
            $totalFiltered = $totalData;

            $hospitals = $query->offset($start)
                ->limit($limit)
                ->orderBy($order, $dir)
                ->get();
            $data = array();
            if (!empty($hospitals)) {
                foreach ($hospitals as $value) {
                    $id = $value['id'];
                    $view = route('admin.business-client.hospital.show', $id);
                    $nestedData['checkbox'] = "<td><div class=\"custom-checkbox custom-control\">
                    <input type=\"checkbox\" data-checkboxes=\"mygroup\" class=\"custom-control-input checkbox_id check-inactive-hospital\" id=\"$id\" data-id=\"$id\" value=\"$id\" name=\"checkbox_id[]\"><label for=\"$id\" class=\"custom-control-label\">&nbsp;</label></div></td>";
                    $address = $value['address'];
                    $address .= $value['address2'] ? ',' . $value['address2'] : '';
                    $address .= $value['city_name'] ? ',' . $value['city_name'] : '';
                    $address .= $value['state_name'] ? ',' . $value['state_name'] : '';
                    $address .= $value['country_name'] ? ',' . $value['country_name'] : '';
                    // $nestedData['name'] = $value['user_name'];
                    $displayEmail = $value['email_address'] ?? $value['email'];
                    $nestedData['name'] = $value['main_name'] . "|" . $displayEmail;
                    $nestedData['mobile'] = $value['mobile'];
                    $nestedData['address'] = $address;
                    $purchasedCredits = UserCreditHistory::where('user_id', $id)->where('type', UserCreditHistory::RELOAD)->sum('amount');
                    $nestedData['credits'] = number_format($purchasedCredits, 0) . "|" . number_format($value['credits'], 0);
                    $nestedData['business_license_number'] = $value['business_license_number'];
                    $nestedData['avg_rating'] = $value['avg_rating'];
                    $nestedData['join_by'] = $value['manager_name'];
                    // $nestedData['date'] = $value['created_at'];
                    $nestedData['date'] = $this->formatDateTimeCountryWise($value['business_created_date'], $adminTimezone, 'd-m-Y H:i');

                    /* if ($value['is_user_active'] == true) {
                        $nestedData['status'] = '<span class="badge badge-success">&nbsp;</span>';
                    } else {
                    } */
                    $nestedData['status'] = '<span class="badge" style="background-color: #fff700;">&nbsp;</span>';

                    $nestedData['status'] .= $this->isOutsideHospital($value['user_id']);
                    $nestedData['referral'] = $this->getReferralDetail($value['user_id']);
                    $nestedData['shop_profile'] = '';

                    $seeButton = "<a role='button' href='javascript:void(0)' onclick='viewLogs(" . $id . ")' title='' data-original-title='View' class='btn btn-primary btn-sm' data-toggle='tooltip'>see</a>";
                    $nestedData['credit_purchase_log'] = $seeButton;
                    $viewButton = "<a role='button' href='javascript:void(0)' onclick='viewProfile(" . $id . ")'  title='' data-original-title='View' class='btn btn-primary btn-sm' data-toggle='tooltip'><i class='fas fa-eye'></i></a>";
                    $user = Auth::user();
                    $editButton =  $user->hasRole('Admin') ? "<a href='javascript:void(0)' role='button' onclick='editCredits(" . $id . ")' class='btn btn-primary btn-sm mx-1' data-toggle='tooltip' data-original-title='Edit'><i class='fa fa-edit'></a>" : "";
                    $nestedData['actions'] = "<div class='d-flex'>$viewButton $editButton</div>";
                    $data[] = $nestedData;
                }
            }
            $jsonData = array(
                "draw" => intval($draw),
                "recordsTotal" => intval($totalData),
                "recordsFiltered" => intval($totalFiltered),
                "data" => $data,
                'hospitals' => $hospitals
            );
            Log::info('End all hospital list');
            return response()->json($jsonData);
        } catch (\Exception $ex) {
            Log::info('Exception all hospital list');
            Log::info($ex);
            return response()->json([]);
        }
    }

    public function show($id)
    {
        $title = 'Hospital Client Detail';
        $hospital = Hospital::find($id);
        $userDetail = UserDetail::where('user_id', $hospital->user_id)->first();
        $shop_user = User::where('id', $hospital->user_id)->first();

        $manager_id = $userDetail->manager_id ?? '';
        $recommended_code = $manager_name = $manager_email = '';
        if (!empty($manager_id)) {
            $manager = Manager::where('id', $manager_id)->first();
            $recommended_code = $manager ? $manager->recommended_code : '';
            $manager_name = $manager ? $manager->name : '';
            if ($manager) {
                $managerData = DB::table('users')->where('id', $manager->user_id)->first();
                $manager_email = $managerData ? $managerData->email : '';
            }
        }

        $activePosts = Post::where('hospital_id', $id)->where('status_id', Status::ACTIVE)->orderBy('created_at', 'asc')->get();
        $readyPosts = Post::where('hospital_id', $id)->where('status_id', Status::FUTURE)->get();
        $pendingPosts = Post::where('hospital_id', $id)->whereIn('status_id', [Status::PENDING, Status::INACTIVE, Status::EXPIRE])->get();

        $active_hospital_count = UserEntityRelation::join('hospitals', 'hospitals.id', 'user_entity_relation.entity_id')
            ->where('entity_type_id', EntityTypes::HOSPITAL)
            ->where('user_entity_relation.user_id', $hospital->user_id)
            ->whereIn('hospitals.status_id', [Status::ACTIVE, Status::PENDING])
            ->count();
        $userHospital = UserEntityRelation::where('entity_type_id', EntityTypes::HOSPITAL)->where('user_id', $hospital->user_id)->first();
        $currentHospital = Hospital::with(['address' => function ($query) {
            $query->where('entity_type_id', EntityTypes::HOSPITAL);
        }])->where('id', $userHospital->entity_id)->first();

        $creditPlan = CreditPlans::where('entity_type_id', EntityTypes::HOSPITAL)->where('package_plan_id', $userDetail->package_plan_id)->first();
        $minHospitalCredit = $creditPlan ? $creditPlan->amount : 0;
        $userCredits = UserCredit::where('user_id', $currentHospital->user_id)->first();

        $not_enough_coin = $userCredits->credits <= $minHospitalCredit ? true : false;
        $deactivated_by_you = $currentHospital->deactivate_by_user == 1 ? true : false;
        $plan_expire_date_next_amount = "-" . number_format($active_hospital_count * $minHospitalCredit, 0);

        if ($not_enough_coin) {
            $plan_expire_date_next = 'Expired';
        } else {
            $plan_expire_date_next = $userDetail->plan_expire_date;
        }

        $plan_expire_date_next1 = new Carbon($userDetail->plan_expire_date);
        $plan_expire_date_next1_amount = new Carbon($userDetail->plan_expire_date);

        $checkCurrentDate = $plan_expire_date_next1_amount->subDays(30);
        $checkStartDate = Carbon::parse($checkCurrentDate)->subDay();
        $checkEndDate = Carbon::parse($checkCurrentDate)->addDay();

        $plan_expire_date_amount = UserCreditHistory::where('user_id', $hospital->user_id)->where('transaction', 'debit')->where('type', UserCreditHistory::REGULAR)->whereBetween('created_at', [$checkStartDate, $checkEndDate])->sum('amount');
        $plan_expire_date = $plan_expire_date_next1->subDays(30)->format('M d');

        $plan_expire_date_amount = "-" . number_format($plan_expire_date_amount, 0);

        $hospital->plan_expire_date = $plan_expire_date;
        $hospital->plan_expire_date_amount = $plan_expire_date_amount;
        $hospital->plan_expire_date_next = $plan_expire_date_next;
        $hospital->plan_expire_date_next_amount = $plan_expire_date_next_amount;

        return view('admin.business-client.show', compact('title', 'hospital', 'activePosts', 'readyPosts', 'pendingPosts', 'userDetail', 'shop_user', 'manager_name', 'manager_email', 'recommended_code'));
    }

    public function viewLogs($id)
    {
        $dataCredit = UserCreditHistory::where('user_id', $id)->where('transaction', 'credit')->orderBy('created_at', 'DESC')->get();
        $dataDebit = UserCreditHistory::where('user_id', $id)->where('transaction', 'debit')->orderBy('created_at', 'DESC')->get();
        // dd($dataCredit,$dataDebit);
        $adminTimezone = $this->getAdminUserTimezone();
        return view('admin.business-client.credit-log', compact('dataCredit', 'dataDebit', 'adminTimezone'));
    }
    public function viewHospitalProfile($id)
    {
        // $hospitals = UserEntityRelation::join('hospitals','hospitals.id','user_entity_relation.entity_id')
        // ->join('category','category.id','hospitals.category_id')
        // ->leftjoin('reviews', function ($join) {
        //     $join->on('hospitals.id', '=', 'reviews.entity_id')
        //          ->where('reviews.entity_type_id', EntityTypes::HOSPITAL);
        // })
        // ->where('user_entity_relation.user_id',$id)->where('user_entity_relation.entity_type_id',EntityTypes::HOSPITAL)
        // ->groupby('hospitals.id')
        // ->select('hospitals.*','category.name as category',DB::raw('round(AVG(reviews.rating),1) as avg_rating'))->get();
        $hospitals = Hospital::join('user_entity_relation', 'hospitals.id', 'user_entity_relation.entity_id')
            ->leftjoin('category', 'category.id', 'hospitals.category_id')
            ->leftjoin('reviews', function ($join) {
                $join->on('hospitals.id', '=', 'reviews.entity_id')
                    ->where('reviews.entity_type_id', EntityTypes::HOSPITAL);
            })
            ->where('user_entity_relation.user_id', $id)->where('user_entity_relation.entity_type_id', EntityTypes::HOSPITAL)
            ->groupby('hospitals.id')
            ->select('hospitals.*', 'category.name as category', DB::raw('round(AVG(reviews.rating),1) as avg_rating'))->get();
        // dd($hospitals);
        return view('admin.business-client.check-profile', compact('hospitals'));
    }

    /* ================ Hospital Code End ======================= */

    /* ================ Shop Code Start ======================= */
    public function indexShop(Request $request)
    {
        $title = "Shop Client";
        $manager_id = $request->has('manager_id') ? $request->manager_id : 0;

        $totalUsers = UserEntityRelation::join('users', 'users.id', 'user_entity_relation.user_id')->whereNotNull('users.email')->distinct('user_id')->count('user_id');;
        $totalShopsQuery = UserEntityRelation::join('users_detail', 'users_detail.user_id', 'user_entity_relation.user_id');
        $totalHospitalsQuery = UserEntityRelation::join('users_detail', 'users_detail.user_id', 'user_entity_relation.user_id');
        $lastMonthIncomeQuery = ReloadCoinRequest::join('users_detail', 'users_detail.user_id', 'reload_coins_request.user_id');
        $totalIncomeQuery = ReloadCoinRequest::join('users_detail', 'users_detail.user_id', 'reload_coins_request.user_id');
        $currentMonthIncomeQuery = ReloadCoinRequest::join('users_detail', 'users_detail.user_id', 'reload_coins_request.user_id');

        if ($manager_id && $manager_id != 0) {
            $totalShopsQuery = $totalShopsQuery->where('users_detail.manager_id', $manager_id);
            $totalHospitalsQuery = $totalHospitalsQuery->where('users_detail.manager_id', $manager_id);
            $lastMonthIncomeQuery = $lastMonthIncomeQuery->where('users_detail.manager_id', $manager_id);
            $totalIncomeQuery = $totalIncomeQuery->where('users_detail.manager_id', $manager_id);
            $currentMonthIncomeQuery = $currentMonthIncomeQuery->where('users_detail.manager_id', $manager_id);
        }

        $totalShops = $totalShopsQuery->where('entity_type_id', EntityTypes::SHOP)->distinct('user_entity_relation.user_id')->count('user_entity_relation.user_id');
        $totalHospitals = $totalHospitalsQuery->where('entity_type_id', EntityTypes::HOSPITAL)->distinct('user_entity_relation.user_id')->count('user_entity_relation.user_id');
        $totalClients = UserEntityRelation::whereIn('entity_type_id', [EntityTypes::HOSPITAL, EntityTypes::SHOP])->distinct('user_id')->count('user_id');
        $dateS = Carbon::now()->startOfMonth()->subMonth(1);
        $dateE = Carbon::now()->startOfMonth();
        $lastMonthIncome = $lastMonthIncomeQuery->where('status', ReloadCoinRequest::GIVE_COIN)->whereBetween('reload_coins_request.created_at', [$dateS, $dateE])
            ->sum('reload_coins_request.coin_amount');
        $lastMonthIncome = number_format($lastMonthIncome, 0);
        $totalIncome = $totalIncomeQuery->where('status', ReloadCoinRequest::GIVE_COIN)
            ->sum('reload_coins_request.coin_amount');
        $totalIncome = number_format($totalIncome, 0);
        $currentMonthIncome = $currentMonthIncomeQuery->where('status', ReloadCoinRequest::GIVE_COIN)->whereMonth('reload_coins_request.created_at', $dateE->month)
            ->sum('reload_coins_request.coin_amount');
        $currentMonthIncome = number_format($currentMonthIncome, 0);
        return view('admin.business-client.index-shop', compact('manager_id', 'totalIncome', 'title', 'totalUsers', 'totalShops', 'totalHospitals', 'totalClients', 'lastMonthIncome', 'currentMonthIncome'));
    }

    public function getJsonAllShopData(Request $request, $manager_id = 0)
    {
        $currentURL =  request()->segment(2);
        if ($currentURL == $this->myBusinessPage) {
            $user = Auth::user();
            $manager = Manager::where('user_id', $user->id)->first();
            $manager_id = $manager ? $manager->id : 0;
        }
        try {
            Log::info('Start all shop list');

            $columns = array(
                0 => 'users.id',
                1 => 'users_detail.name',
                2 => 'countries.name',
                3 => 'users_detail.mobile',
                4 => 'user_credits.credits',
                5 => 'managers.name',
                6 => 'users.created_at',
                7 => 'rating',
                8 => 'shops.business_license_number',
                9 => 'is_user_active',
                10 => 'log',
                12 => 'action',
            );
            $limit = $request->input('length');
            $start = $request->input('start');
            $search = $request->input('search.value');
            $draw = $request->input('draw');
            $order = $columns[$request->input('order.0.column')];
            $dir = $request->input('order.0.dir');
            $adminTimezone = $this->getAdminUserTimezone();

            // Comman Query change where only
            $query = $this->commanShopQueryFunction($search, $manager_id, 'all');

            $totalData = count($query->get());
            $totalFiltered = $totalData;

            $shops = $query->offset($start)
                ->limit($limit)
                ->orderBy($order, $dir)
                ->get();

            $data = array();
            if (!empty($shops)) {
                foreach ($shops as $value) {
                    $id = $value['id'];
                    $shopViewLink = $this->getViewProfileURL($value);

                    $view = route('admin.business-client.shop.show', $id);
                    $nestedData['checkbox'] = "<td><div class=\"custom-checkbox custom-control\">
                    <input type=\"checkbox\" data-checkboxes=\"mygroup\" class=\"custom-control-input checkbox_id check-all-shop\" id=\"$id\" data-id=\"$id\" value=\"$id\" name=\"checkbox_id[]\"><label for=\"$id\" class=\"custom-control-label\">&nbsp;</label></div></td>";
                    $address = $value['address'];
                    $address .= $value['address2'] ? ',' . $value['address2'] : '';
                    $address .= $value['city_name'] ? ',' . $value['city_name'] : '';
                    $address .= $value['state_name'] ? ',' . $value['state_name'] : '';
                    $address .= $value['country_name'] ? ',' . $value['country_name'] : '';
                    // $nestedData['name'] = $value['user_name'];
                    $business_name = $value['main_name'] != "" ? $value['main_name'] . "/" : "";
                    $business_name .= $value['shop_name'];
                    $displayEmail = $value['email_address'] ?? $value['email'];
                    $nestedData['name'] = $this->displayBusinessName($value['business_name_group'], $business_name) . "|" . $displayEmail;
                    $nestedData['mobile'] = $value['mobile'];
                    $nestedData['address'] = $address;
                    $purchasedCredits = UserCreditHistory::where('user_id', $id)->where('type', UserCreditHistory::RELOAD)->sum('amount');
                    $nestedData['credits'] = number_format($purchasedCredits, 0) . "|" . number_format($value['credits'], 0);
                    $nestedData['join_by'] = $value['manager_name'];
                    $nestedData['manager_name'] = $value['manager_name'];
                    $nestedData['business_license_number'] = $value['business_license_number'];
                    // $nestedData['date'] = $value['created_at'];
                    $nestedData['date'] = $this->formatDateTimeCountryWise($value['business_created_date'], $adminTimezone, 'd-m-Y H:i');
                    $reviews = Reviews::join('shops', 'shops.id', 'reviews.entity_id')
                        ->where('reviews.entity_type_id', EntityTypes::SHOP)
                        ->where('shops.user_id', $value['id'])
                        ->select('reviews.*', DB::raw('round(AVG(reviews.rating),1) as avg_rating'))->get();
                    $nestedData['avg_rating'] = $reviews[0]->avg_rating;

                    if ($value['is_user_active'] == true) {
                        $nestedData['status'] = '<span class="badge badge-success">&nbsp;</span>';
                    } else {
                        $nestedData['status'] = '<span class="badge badge-secondary">&nbsp;</span>';
                    }
                    $nestedData['referral'] = $this->getReferralDetail($value['user_id']);
                    $nestedData['shop_profile'] = $shopViewLink . "<br/>" . str_replace(',', '<br/>', $value['social_names']);

                    $shopCount = DB::table('shops')->where('user_id', $value['user_id'])->count();
                    $shopPendingCount = DB::table('shops')->where('user_id', $value['user_id'])->where('status_id', Status::PENDING)->count();

                    if ($shopCount == $shopPendingCount) {
                        $nestedData['status'] = '<span class="badge" style="background-color: #fff700;">&nbsp;</span>';
                    }

                    $nestedData['status'] .= $this->isOutsideShop($value['user_id']);

                    $seeButton = "<a role='button' href='javascript:void(0)' onclick='viewLogs(" . $id . ")' title='' data-original-title='View' class='btn btn-primary btn-sm' data-toggle='tooltip'>see</a>";
                    $nestedData['credit_purchase_log'] = $seeButton;
                    $viewButton = "<a role='button' href='javascript:void(0)' onclick='viewShopProfile(" . $id . ")'  title='' data-original-title='View' class='btn btn-primary btn-sm' data-toggle='tooltip'><i class='fas fa-eye'></i></a>";
                    $user = Auth::user();
                    $editButton =  $user->hasRole('Admin') ? "<a href='javascript:void(0)' role='button' onclick='editCredits(" . $id . ")' class='btn btn-primary btn-sm mx-1' data-toggle='tooltip' data-original-title='Edit'><i class='fa fa-edit'></a>" : "";
                    $nestedData['actions'] = "<div class='d-flex'>$viewButton $editButton</div>";
                    $data[] = $nestedData;
                }
            }

            $jsonData = array(
                "draw" => intval($draw),
                "recordsTotal" => intval($totalData),
                "recordsFiltered" => intval($totalFiltered),
                "data" => $data
            );
            Log::info('End all shop list');
            return response()->json($jsonData);
        } catch (\Exception $ex) {
            Log::info('Exception all shop list');
            Log::info($ex);
            return response()->json([]);
        }
    }
    public function getJsonActiveShopData(Request $request, $manager_id = 0)
    {
        $currentURL =  request()->segment(2);
        if ($currentURL == $this->myBusinessPage) {
            $user = Auth::user();
            $manager = Manager::where('user_id', $user->id)->first();
            $manager_id = $manager ? $manager->id : 0;
        }
        try {
            Log::info('Start active shop list');

            $columns = array(
                0 => 'users.id',
                1 => 'users_detail.name',
                2 => 'countries.name',
                3 => 'users_detail.mobile',
                4 => 'user_credits.credits',
                5 => 'managers.name',
                6 => 'users.created_at',
                7 => 'rating',
                8 => 'shops.business_license_number',
                9 => 'is_user_active',
                10 => 'log',
                12 => 'action',
            );
            $limit = $request->input('length');
            $start = $request->input('start');
            $search = $request->input('search.value');
            $draw = $request->input('draw');
            $order = $columns[$request->input('order.0.column')];
            $dir = $request->input('order.0.dir');
            $adminTimezone = $this->getAdminUserTimezone();

            $query = $this->commanShopQueryFunction($search, $manager_id, 'active');

            $totalData = count($query->get());
            $totalFiltered = $totalData;

            $shops = $query->offset($start)
                ->limit($limit)
                ->orderBy($order, $dir)
                ->get();

            $data = array();
            if (!empty($shops)) {
                foreach ($shops as $value) {
                    // dd(($value));
                    $id = $value['id'];
                    $shopViewLink = $this->getViewProfileURL($value);

                    $view = route('admin.business-client.shop.show', $id);
                    $nestedData['checkbox'] = "<td><div class=\"custom-checkbox custom-control\">
                    <input type=\"checkbox\" data-checkboxes=\"mygroup\" class=\"custom-control-input checkbox_id check-active-shop\" id=\"$id\" data-id=\"$id\" value=\"$id\" name=\"checkbox_id[]\"><label for=\"$id\" class=\"custom-control-label\">&nbsp;</label></div></td>";
                    $address = $value['address'];
                    $address .= $value['address2'] ? ',' . $value['address2'] : '';
                    $address .= $value['city_name'] ? ',' . $value['city_name'] : '';
                    $address .= $value['state_name'] ? ',' . $value['state_name'] : '';
                    $address .= $value['country_name'] ? ',' . $value['country_name'] : '';
                    // $nestedData['name'] = $value['user_name'];
                    $business_name = $value['main_name'] != "" ? $value['main_name'] . "/" : "";
                    $business_name .= $value['shop_name'];

                    $displayEmail = $value['email_address'] ?? $value['email'];
                    $nestedData['name'] = $this->displayBusinessName($value['business_name_group'], $business_name) . "|" . $displayEmail;
                    $nestedData['mobile'] = $value['mobile'];
                    $nestedData['address'] = $address;
                    $purchasedCredits = UserCreditHistory::where('user_id', $id)->where('type', UserCreditHistory::RELOAD)->sum('amount');
                    $nestedData['credits'] = number_format($purchasedCredits, 0) . "|" . number_format($value['credits'], 0);
                    $nestedData['join_by'] = $value['manager_name'];
                    $nestedData['business_license_number'] = $value['business_license_number'];

                    // $nestedData['date'] = $value['created_at'];
                    $nestedData['date'] = $this->formatDateTimeCountryWise($value['business_created_date'], $adminTimezone, 'd-m-Y H:i');
                    $reviews = Reviews::join('shops', 'shops.id', 'reviews.entity_id')
                        ->where('reviews.entity_type_id', EntityTypes::SHOP)
                        ->where('shops.user_id', $value['id'])
                        ->select('reviews.*', DB::raw('round(AVG(reviews.rating),1) as avg_rating'))->get();
                    $nestedData['avg_rating'] = $reviews[0]->avg_rating;

                    $nestedData['status'] = '<span class="badge badge-success">&nbsp;</span>';
                    /* if ($value['is_user_active'] == true) {
                    } else {
                        $nestedData['status'] = '<span class="badge badge-secondary">&nbsp;</span>';
                    } */

                    $nestedData['status'] .= $this->isOutsideShop($value['user_id']);
                    $nestedData['referral'] = $this->getReferralDetail($value['user_id']);
                    $nestedData['shop_profile'] = $shopViewLink . "<br/>" . str_replace(',', '<br/>', $value['social_names']);

                    $seeButton = "<a role='button' href='javascript:void(0)' onclick='viewLogs(" . $id . ")' title='' data-original-title='View' class='btn btn-primary btn-sm' data-toggle='tooltip'>see</a>";
                    $nestedData['credit_purchase_log'] = $seeButton;
                    $viewButton = "<a role='button' href='javascript:void(0)' onclick='viewShopProfile(" . $id . ")'  title='' data-original-title='View' class='btn btn-primary btn-sm' data-toggle='tooltip'><i class='fas fa-eye'></i></a>";
                    $user = Auth::user();
                    $editButton =  $user->hasRole('Admin') ? "<a href='javascript:void(0)' role='button' onclick='editCredits(" . $id . ")' class='btn btn-primary btn-sm mx-1' data-toggle='tooltip' data-original-title='Edit'><i class='fa fa-edit'></a>" : "";
                    $nestedData['actions'] = "<div class='d-flex'>$viewButton $editButton</div>";
                    $data[] = $nestedData;
                }
            }
            // dd($data);
            $jsonData = array(
                "draw" => intval($draw),
                "recordsTotal" => intval($totalData),
                "recordsFiltered" => intval($totalFiltered),
                "data" => $data,
                "shops" => $shops
            );
            Log::info('End active shop list');
            return response()->json($jsonData);
        } catch (\Exception $ex) {
            Log::info('Exception active shop list');
            Log::info($ex);
            return response()->json([]);
        }
    }
    public function getJsonInactiveShopData(Request $request, $manager_id = 0)
    {
        $currentURL =  request()->segment(2);
        if ($currentURL == $this->myBusinessPage) {
            $user = Auth::user();
            $manager = Manager::where('user_id', $user->id)->first();
            $manager_id = $manager ? $manager->id : 0;
        }
        try {
            Log::info('Start inactive shop list');

            $columns = array(
                0 => 'users.id',
                1 => 'users_detail.name',
                2 => 'countries.name',
                3 => 'users_detail.mobile',
                4 => 'user_credits.credits',
                5 => 'managers.name',
                6 => 'users.created_at',
                7 => 'rating',
                8 => 'shops.business_license_number',
                9 => 'is_user_active',
                10 => 'log',
                12 => 'action',
            );
            $limit = $request->input('length');
            $start = $request->input('start');
            $search = $request->input('search.value');
            $draw = $request->input('draw');
            $order = $columns[$request->input('order.0.column')];
            $dir = $request->input('order.0.dir');
            $adminTimezone = $this->getAdminUserTimezone();

            $query = $this->commanShopQueryFunction($search, $manager_id, 'inactive');

            $totalData = count($query->get());
            $totalFiltered = $totalData;

            $shops = $query->offset($start)
                ->limit($limit)
                ->orderBy($order, $dir)
                ->get();
            $data = array();
            if (!empty($shops)) {
                foreach ($shops as $value) {
                    $id = $value['id'];
                    $shopViewLink = $this->getViewProfileURL($value);

                    $view = route('admin.business-client.shop.show', $id);
                    $nestedData['checkbox'] = "<td><div class=\"custom-checkbox custom-control\">
                    <input type=\"checkbox\" data-checkboxes=\"mygroup\" class=\"custom-control-input checkbox_id check-inactive-shop\" id=\"$id\" data-id=\"$id\" value=\"$id\" name=\"checkbox_id[]\"><label for=\"$id\" class=\"custom-control-label\">&nbsp;</label></div></td>";
                    $address = $value['address'];
                    $address .= $value['address2'] ? ',' . $value['address2'] : '';
                    $address .= $value['city_name'] ? ',' . $value['city_name'] : '';
                    $address .= $value['state_name'] ? ',' . $value['state_name'] : '';
                    $address .= $value['country_name'] ? ',' . $value['country_name'] : '';
                    // $nestedData['name'] = $value['user_name'];
                    $business_name = $value['main_name'] != "" ? $value['main_name'] . "/" : "";
                    $business_name .= $value['shop_name'];

                    $displayEmail = $value['email_address'] ?? $value['email'];
                    $nestedData['name'] = $this->displayBusinessName($value['business_name_group'], $business_name) . "|" . $displayEmail;
                    $nestedData['mobile'] = $value['mobile'];
                    $nestedData['address'] = $address;
                    $purchasedCredits = UserCreditHistory::where('user_id', $id)->where('type', UserCreditHistory::RELOAD)->sum('amount');
                    $nestedData['credits'] = number_format($purchasedCredits, 0) . "|" . number_format($value['credits'], 0);
                    $nestedData['join_by'] = $value['manager_name'];
                    // $nestedData['date'] = $value['created_at'];
                    $nestedData['date'] = $this->formatDateTimeCountryWise($value['business_created_date'], $adminTimezone, 'd-m-Y H:i');
                    $reviews = Reviews::join('shops', 'shops.id', 'reviews.entity_id')
                        ->where('reviews.entity_type_id', EntityTypes::SHOP)
                        ->where('shops.user_id', $value['id'])
                        ->select('reviews.*', DB::raw('round(AVG(reviews.rating),1) as avg_rating'))->get();
                    $nestedData['avg_rating'] = $reviews[0]->avg_rating;
                    $nestedData['business_license_number'] = $value['business_license_number'];

                    $nestedData['status'] = '<span class="badge badge-secondary">&nbsp;</span>';

                    $nestedData['status'] .= $this->isOutsideShop($value['user_id']);
                    $nestedData['referral'] = $this->getReferralDetail($value['user_id']);
                    $nestedData['shop_profile'] = $shopViewLink . "<br/>" . str_replace(',', '<br/>', $value['social_names']);

                    $seeButton = "<a role='button' href='javascript:void(0)' onclick='viewLogs(" . $id . ")' title='' data-original-title='View' class='btn btn-primary btn-sm' data-toggle='tooltip'>see</a>";
                    $nestedData['credit_purchase_log'] = $seeButton;
                    $viewButton = "<a role='button' href='javascript:void(0)' onclick='viewShopProfile(" . $id . ")'  title='' data-original-title='View' class='btn btn-primary btn-sm' data-toggle='tooltip'><i class='fas fa-eye'></i></a>";
                    $user = Auth::user();
                    $editButton =  $user->hasRole('Admin') ? "<a href='javascript:void(0)' role='button' onclick='editCredits(" . $id . ")' class='btn btn-primary btn-sm mx-1' data-toggle='tooltip' data-original-title='Edit'><i class='fa fa-edit'></a>" : "";
                    $nestedData['actions'] = "<div class='d-flex'>$viewButton $editButton</div>";
                    $data[] = $nestedData;
                }
            }
            $jsonData = array(
                "draw" => intval($draw),
                "recordsTotal" => intval($totalData),
                "recordsFiltered" => intval($totalFiltered),
                "data" => $data
            );
            Log::info('End inactive shop list');
            return response()->json($jsonData);
        } catch (\Exception $ex) {
            Log::info('Exception inactive shop list');
            Log::info($ex);
            return response()->json([]);
        }
    }


    public function getJsonPendingShopData(Request $request, $manager_id = 0)
    {
        $currentURL =  request()->segment(2);
        if ($currentURL == $this->myBusinessPage) {
            $user = Auth::user();
            $manager = Manager::where('user_id', $user->id)->first();
            $manager_id = $manager ? $manager->id : 0;
        }
        try {
            Log::info('Start inactive shop list');

            $columns = array(
                0 => 'users.id',
                1 => 'users_detail.name',
                2 => 'countries.name',
                3 => 'users_detail.mobile',
                4 => 'user_credits.credits',
                5 => 'managers.name',
                6 => 'users.created_at',
                7 => 'rating',
                8 => 'shops.business_license_number',
                9 => 'is_user_active',
                10 => 'log',
                12 => 'action',
            );
            $limit = $request->input('length');
            $start = $request->input('start');
            $search = $request->input('search.value');
            $draw = $request->input('draw');
            $order = $columns[$request->input('order.0.column')];
            $dir = $request->input('order.0.dir');
            $adminTimezone = $this->getAdminUserTimezone();

            $query = $this->commanShopQueryFunction($search, $manager_id, 'pending');

            $query = $query->where('shops.status_id', Status::PENDING);

            $totalData = count($query->get());
            $totalFiltered = $totalData;

            $shops = $query->offset($start)
                ->limit($limit)
                ->orderBy($order, $dir)
                ->get();
            $data = array();
            if (!empty($shops)) {
                foreach ($shops as $value) {
                    $id = $value['id'];
                    $shopViewLink = $this->getViewProfileURL($value);

                    $view = route('admin.business-client.shop.show', $id);
                    $nestedData['checkbox'] = "<td><div class=\"custom-checkbox custom-control\">
                    <input type=\"checkbox\" data-checkboxes=\"mygroup\" class=\"custom-control-input checkbox_id check-inactive-shop\" id=\"$id\" data-id=\"$id\" value=\"$id\" name=\"checkbox_id[]\"><label for=\"$id\" class=\"custom-control-label\">&nbsp;</label></div></td>";
                    $address = $value['address'];
                    $address .= $value['address2'] ? ',' . $value['address2'] : '';
                    $address .= $value['city_name'] ? ',' . $value['city_name'] : '';
                    $address .= $value['state_name'] ? ',' . $value['state_name'] : '';
                    $address .= $value['country_name'] ? ',' . $value['country_name'] : '';
                    // $nestedData['name'] = $value['user_name'];
                    $business_name = $value['main_name'] != "" ? $value['main_name'] . "/" : "";
                    $business_name .= $value['shop_name'];

                    $displayEmail = $value['email_address'] ?? $value['email'];
                    $nestedData['name'] = $this->displayBusinessName($value['business_name_group'], $business_name) . "|" . $displayEmail;
                    $nestedData['mobile'] = $value['mobile'];
                    $nestedData['address'] = $address;
                    $purchasedCredits = UserCreditHistory::where('user_id', $id)->where('type', UserCreditHistory::RELOAD)->sum('amount');
                    $nestedData['credits'] = number_format($purchasedCredits, 0) . "|" . number_format($value['credits'], 0);
                    $nestedData['join_by'] = $value['manager_name'];
                    // $nestedData['date'] = $value['created_at'];
                    $nestedData['date'] = $this->formatDateTimeCountryWise($value['business_created_date'], $adminTimezone, 'd-m-Y H:i');
                    $reviews = Reviews::join('shops', 'shops.id', 'reviews.entity_id')
                        ->where('reviews.entity_type_id', EntityTypes::SHOP)
                        ->where('shops.user_id', $value['id'])
                        ->select('reviews.*', DB::raw('round(AVG(reviews.rating),1) as avg_rating'))->get();
                    $nestedData['avg_rating'] = $reviews[0]->avg_rating;
                    $nestedData['business_license_number'] = $value['business_license_number'];

                    $nestedData['status'] = '<span class="badge" style="background-color: #fff700;">&nbsp;</span>';

                    $nestedData['status'] .= $this->isOutsideShop($value['user_id']);
                    $nestedData['referral'] = $this->getReferralDetail($value['user_id']);
                    $nestedData['shop_profile'] = $shopViewLink . "<br/>" . str_replace(',', '<br/>', $value['social_names']);

                    $seeButton = "<a role='button' href='javascript:void(0)' onclick='viewLogs(" . $id . ")' title='' data-original-title='View' class='btn btn-primary btn-sm' data-toggle='tooltip'>see</a>";
                    $nestedData['credit_purchase_log'] = $seeButton;
                    $viewButton = "<a role='button' href='javascript:void(0)' onclick='viewShopProfile(" . $id . ")'  title='' data-original-title='View' class='btn btn-primary btn-sm' data-toggle='tooltip'><i class='fas fa-eye'></i></a>";
                    $user = Auth::user();
                    $editButton =  $user->hasRole('Admin') ? "<a href='javascript:void(0)' role='button' onclick='editCredits(" . $id . ")' class='btn btn-primary btn-sm mx-1' data-toggle='tooltip' data-original-title='Edit'><i class='fa fa-edit'></a>" : "";
                    $nestedData['actions'] = "<div class='d-flex'>$viewButton $editButton</div>";
                    $data[] = $nestedData;
                }
            }
            $jsonData = array(
                "draw" => intval($draw),
                "recordsTotal" => intval($totalData),
                "recordsFiltered" => intval($totalFiltered),
                "data" => $data
            );
            Log::info('End inactive shop list');
            return response()->json($jsonData);
        } catch (\Exception $ex) {
            Log::info('Exception inactive shop list');
            Log::info($ex);
            return response()->json([]);
        }
    }

    public function saveSupporter(Request $request)
    {
        $inputs = $request->all();

        $supporter_code = $inputs['supporter_code'] ?? '';
        $user_id = $inputs['user_id'] ?? '';

        if (!empty($user_id)) {
            if (!empty($supporter_code)) {
                $manager = Manager::where('recommended_code', $supporter_code)->first();
                if (!empty($manager)) {
                    UserDetail::where('user_id', $user_id)->update(['manager_id' => $manager->id]);
                    $success = true;
                    $message = "Supporter " . trans("messages.update-success");
                } else {
                    $success = false;
                    $message = "Supporter Not found.";
                }
            } else {
                $success = false;
                $message = "Supporter Code is required.";
            }
        } else {
            $success = false;
            $message = "Supporter " . trans("messages.update-error");
        }

        $jsonData = array(
            'success' => $success,
            'message' => $message,
        );
        return response()->json($jsonData);
    }

    public function showShop($id)
    {
        $title = 'Shop Client Detail';
        $shop = Shop::findOrFail($id);
        $userDetail = UserDetail::leftjoin('credit_plans', function ($query) {
            $query->on('credit_plans.package_plan_id', '=', 'users_detail.package_plan_id')
                ->where('credit_plans.entity_type_id', EntityTypes::SHOP);
        })
            ->where('user_id', $shop->user_id)
            ->select('users_detail.*', 'credit_plans.km')
            ->first();
        $shop_user = User::where('id', $shop->user_id)->first();

        $manager_id = $userDetail->manager_id ?? '';
        $recommended_code = $manager_name = $manager_email = '';
        if (!empty($manager_id)) {
            $manager = Manager::where('id', $manager_id)->first();
            $recommended_code = $manager ? $manager->recommended_code : '';
            $manager_name = $manager ? $manager->name : '';
            if ($manager) {
                $managerData = DB::table('users')->where('id', $manager->user_id)->first();
                $manager_email = $managerData ? $managerData->email : '';
            }
        }

        $first_plan = CreditPlans::where('entity_type_id', EntityTypes::SHOP)->orderBy('entity_type_id', 'DESC')->pluck('km')->first();
        $all_plans = CreditPlans::where('entity_type_id', EntityTypes::SHOP)->orderBy('entity_type_id', 'DESC')->get();

        $shop->first_plan = $first_plan;
        $shop->sns_link = !empty($userDetail) ? $userDetail->sns_link : '';
        $shop->sns_type = !empty($userDetail) ? $userDetail->sns_type : '';

        $shopPriceCategory = ShopPriceCategory::where('shop_id', $id)->get();
        $shop->shopPriceCategory = $shopPriceCategory;

        /* shop coin detail */
        $shop_count = UserEntityRelation::where('entity_type_id', EntityTypes::SHOP)->where('user_id', $shop->user_id)->count();
        $deactivated_by_you = $not_enough_coin = false;
        $is_plan_update = 1;
        if ($shop_count) {
            $active_shop_count = UserEntityRelation::join('shops', 'shops.id', 'user_entity_relation.entity_id')
                ->where('entity_type_id', EntityTypes::SHOP)
                ->where('user_entity_relation.user_id', $shop->user_id)
                ->whereIn('shops.status_id', [Status::ACTIVE, Status::PENDING])
                ->count();
            $total_user_shops = Shop::where('deactivate_by_user', 0)->where('user_id', $shop->user_id)->count();
            // dd($total_user_shops);
            $creditPlan = CreditPlans::where('entity_type_id', EntityTypes::SHOP)->where('package_plan_id', $userDetail->package_plan_id)->first();
            $userCredits = UserCredit::where('user_id', $shop->user_id)->first();
            $defaultCredit = $creditPlan ? $creditPlan->amount : 0;
            $minShopCredit = $defaultCredit * $total_user_shops;
            $userShop = UserEntityRelation::where('user_id', $shop->user_id)->where('entity_type_id', EntityTypes::SHOP)->pluck('entity_id');
            $currentShop = Shop::whereIn('status_id', [Status::ACTIVE, Status::PENDING])->whereIn('id', $userShop)->count();
            $deactivated_by_you = $currentShop == 0 ? true : false;
            $not_enough_coin = $userCredits->credits >= $minShopCredit ? false : true;
            $plan_expire_date_next_amount = "-" . number_format($active_shop_count * $defaultCredit, 0);
        }

        $plans = CreditPlans::where('entity_type_id', EntityTypes::SHOP)->get();
        if ($not_enough_coin) {
            $plan_expire_date_next = 'Expired';
        } else {
            $plan_expire_date_next = $userDetail->plan_expire_date;
        }

        $plan_expire_date_next1 = new Carbon($userDetail->plan_expire_date);
        $plan_expire_date_next1_amount = new Carbon($userDetail->plan_expire_date);

        $checkCurrentDate = $plan_expire_date_next1_amount->subDays(30);
        $checkStartDate = Carbon::parse($checkCurrentDate)->subDay();
        $checkEndDate = Carbon::parse($checkCurrentDate)->addDay();

        $plan_expire_date_amount = UserCreditHistory::where('user_id', $shop->user_id)->where('transaction', 'debit')->where('type', UserCreditHistory::REGULAR)->whereBetween('created_at', [$checkStartDate, $checkEndDate])->sum('amount');
        $plan_expire_date = $plan_expire_date_next1->subDays(30)->format('M d');

        $plan_expire_date_amount = "-" . number_format($plan_expire_date_amount, 0);


        $shop->plan_expire_date = $plan_expire_date;
        $shop->plan_expire_date_amount = $plan_expire_date_amount;
        $shop->plan_expire_date_next = $plan_expire_date_next;
        $shop->plan_expire_date_next_amount = $plan_expire_date_next_amount;

        $instaData = LinkedSocialProfile::where('user_id', $shop->user_id)->where('shop_id', $id)->where('social_type', LinkedSocialProfile::Instagram)->first();

        $all_status = Status::all();
        $shopCategory = DB::table('category')
            ->leftjoin('category_settings', function ($join) {
                $join->on('category_settings.category_id', '=', 'category.id')
                    ->where('category_settings.country_code', 'KR');
            })
            ->select('category.*')
            ->whereIn('category.category_type_id', [CategoryTypes::SHOP])
            ->whereNull('category.deleted_at')
            ->orderBy('category_settings.order', 'ASC')
            ->get();

        // Shop Insta Connect Link
        $shopConnect = ShopConnectLink::firstOrCreate([
            'shop_id' => $id,
            'is_expired' => 0
        ]);

        $shopConnectCopy = "$shopConnect->id|$id|$shop->user_id|" . Carbon::parse($shopConnect->created_at)->timestamp;
        $shopConnectCopyLink = route('social.profile.connect', ['code' => Crypt::encrypt($shopConnectCopy)]);

        $insta_plans_categorywise = InstagramCategory::with('categoryoption')->orderBy('order','ASC')->get();
        $subscribed_plans = InstagramSubscribedPlan::where('user_id',$shop->user_id)->pluck('instagram_category_option_id')->toArray();

        $mbti_options = array('I don\'t know','ISTJ','ESTJ','ISFJ','ENFP','ESFJ','INFP','ISFP','INTJ','ESFP','ISTP','ESTP','INTP','ENTJ','INTJ','ENTP','ENFJ');

        $adminTimezone = $this->getAdminUserTimezone();
        $signup_date = $this->formatDateTimeCountryWise($shop_user->created_at, $adminTimezone);

        $shop_info = ShopInfo::where('shop_id',$shop->id)->first();
        return view('admin.business-client.show-shop', compact('title','all_status','shopCategory','shop', 'recommended_code','manager_name', 'manager_email','shop_user','userDetail','instaData','shopConnectCopyLink', 'all_plans', 'insta_plans_categorywise', 'subscribed_plans', 'mbti_options', 'signup_date','shop_info'));
    }

    public function address_detail(){
        return view('admin.business-client.address-popup');
    }

    public function updateInstaServiceShop(Request $request, $id)
    {
        $inputs = $request->all();
        try {
            $count_day = $inputs['count_day'] ?? 0;
            $regular_service = $inputs['regular_service'] ?? 0;

            Shop::whereId($id)->update(['count_days' => $count_day, 'is_regular_service' => $regular_service]);

            foreach ($inputs['insta_plan'] as $category=>$option){
                $delete_old_plan = InstagramSubscribedPlan::where('user_id', $inputs['user_id'])->where('instagram_category_id', $category)->delete();
                InstagramSubscribedPlan::create([
                    'user_id' => $inputs['user_id'],
                    'instagram_category_id' => $category,
                    'instagram_category_option_id' => $option,
                ]);
            }

            $jsonData = [
                'status_code' => 200,
                'success' => true,
                'message' => trans("messages.shop.save-detail"),
            ];
            return response()->json($jsonData);
        } catch (\Throwable $th) {
            $jsonData = [
                'status_code' => 400,
                'success' => false,
                'message' => trans("messages.shop.not-save-detail"),
            ];
            return response()->json($jsonData);
        }
    }

    public function updateShopStatus(Request $request, $id)
    {
        $success = true;
        $inputs = $request->all();
        $status = $inputs['status'] ?? '';
        if ($id && !empty($status)) {
            Shop::where('id', $id)->update(['status_id' => $status]);
        }
        $jsonData = array(
            'success' => $success,
            'message' => "Status " . trans("messages.update-success")
        );
        return response()->json($jsonData);
    }

    public function DisconnectInstagram(Request $request)
    {
        DB::beginTransaction();
        $inputs = $request->all();
        try {
            if (isset($inputs['insta_id']) && !empty($inputs['insta_id'])) {
                $insta_profile = LinkedSocialProfile::where('id', $inputs['insta_id'])->first();
                if (!empty($insta_profile)){
                    LinkedProfileHistory::updateOrCreate([
                        'shop_id' => $insta_profile->shop_id,
                        'social_id' => $insta_profile->social_id,
                        'social_name' => $insta_profile->social_name,
                    ], [
                        'last_disconnected_date' => Carbon::now()
                    ]);
                    InstagramLog::create([
                        "social_id" =>$insta_profile->social_id,
                        "user_id" =>$insta_profile->user_id,
                        "shop_id" =>$insta_profile->shop_id,
                        "social_name" =>$insta_profile->social_name,
                        "status" =>InstagramLog::DISCONNECTED,
                    ]);
                    LinkedSocialProfile::where('id', $inputs['insta_id'])->delete();
                }
                DB::commit();
            }
            notify()->success("Instagram Disconnected successfully", "Success", "topRight");
            return redirect()->back();
        } catch (\Exception $ex) {
            DB::rollBack();
            Log::info($ex);

            notify()->error("in Disconnect Instagram", "Error", "topRight");
            return redirect()->back();
        }
    }

    public function updateHospital(Request $request)
    {
        $inputs = $request->all();

        DB::beginTransaction();
        try {

            $hospital_id = $inputs['hospital_id'];
            $main_name = $inputs['main_name'];
            $email = $inputs['email'];
            $mobile = $inputs['mobile'];
            $description = $inputs['description'];
            $business_link = $inputs['business_link'] ?? NULL;

            Hospital::where('id', $hospital_id)->update(['main_name' => $main_name, 'email' => $email, 'mobile' => $mobile, 'description' => $description, 'business_link' => $business_link]);

            if (!empty($inputs['state_name']) && !empty($inputs['country_name'])) {

                $cityname = !empty($inputs['city_name']) ? $inputs['city_name'] : $inputs['state_name'];
                $main_country = getCountryFromLatLong($inputs['latitude'], $inputs['longitude']);
                $location = $this->addCurrentLocation($inputs['country_name'], $inputs['state_name'], $cityname);

                $country_code = getCountryFromLatLong($inputs['latitude'], $inputs['longitude']);
                if ($location) {
                    $address = Address::updateOrCreate(['entity_type_id' => EntityTypes::HOSPITAL, 'entity_id' => $hospital_id], [
                        'address' => $inputs['address'],
                        'address2' => $inputs['address_detail'] ?? NULL,
                        'country_id' => !empty($location['country']->id) ? $location['country']->id : NULL,
                        'city_id' => $location['city']->id,
                        'state_id' => $location['city']->state_id,
                        'latitude' => $inputs['latitude'],
                        'longitude' => $inputs['longitude'],
                        'entity_type_id' => EntityTypes::HOSPITAL,
                        'main_country' => $country_code,
                        'entity_id' => $hospital_id,
                        'main_country' => $main_country,
                    ]);
                }
            } else {

                $jsonData = [
                    'status_code' => 400,
                    'message' => "Please enter proper address.",
                ];
                return response()->json($jsonData);
            }

            DB::commit();
            $jsonData = [
                'status_code' => 200,
                'message' => trans("messages.hospital.update-success"),
                'url' => '$url'
            ];
            return response()->json($jsonData);
        } catch (\Exception $ex) {
            DB::rollBack();
            Log::info($ex);
            $jsonData = [
                'status_code' => 400,
                'message' => trans("messages.hospital.not-save-detail"),
            ];
            return response()->json($jsonData);
        }
    }
    public function updateShop(Request $request)
    {
        try {
            DB::beginTransaction();
            $inputs = $request->all();
            $shop_id = $inputs['shop_id'];
            $main_name = $inputs['main_name'];
            $shop_name = $inputs['shop_name'];
            $speciality_of = $inputs['speciality_of'];
            $business_link = (isset($inputs['chat_option']) && $inputs['chat_option'] == 1) ? $inputs['business_link'] : NULL;
            $booking_link = (isset($inputs['naver_link']) && $inputs['naver_link'] == 'yes') ? $inputs['booking_link'] : NULL;
            $chat_option =  $inputs['chat_option'] ?? 0;
            $show_price =  $inputs['show_price'] ?? 0;
            $show_address =  $inputs['show_address'] ?? 0;
            $expose_distance =  $inputs['expose_distance'] ?? null;
            //$chat_option = $inputs['chat_option'] ?? 0;
            //$booking_link = $inputs['booking_link'] ?? null;
            $another_mobile = (isset($inputs['another']) && $inputs['another'] == 'yes') ? $inputs['another_mobile'] : NULL;

            if (isset($inputs['another_mobile']) && !empty($inputs['another_mobile'])) {
                $exists_shops = Shop::where('id', '!=', $shop_id)->where('another_mobile', $another_mobile)->get();
                if (count($exists_shops) > 0) {
                    $modal_html = view('admin.business-client.exist-shops', compact('exists_shops'))->render();
                    $jsonData = [
                        'status_code' => 400,
                        //                        'message' => "Please enter valid phone number.",
                        'modal' => $modal_html
                    ];
                    return response()->json($jsonData);
                }
            }

            if(isset($inputs['user_mbti'])){
                $shop = DB::table('shops')->whereId($shop_id)->first();
                UserDetail::where('user_id', $shop->user_id)->update(['mbti' => trim($inputs['user_mbti'])]);
            }

            Shop::where('id', $shop_id)->update(['main_name' => $main_name, 'shop_name' => $shop_name, 'speciality_of' => $speciality_of, 'business_link' => $business_link, 'booking_link' => $booking_link, 'another_mobile' => $another_mobile, 'chat_option' => $chat_option, 'show_price' => $show_price, 'show_address' => $show_address, 'expose_distance' => $expose_distance]);

            if (!empty($inputs['state_name']) && !empty($inputs['country_name'])) {

                $cityname = !empty($inputs['city_name']) ? $inputs['city_name'] : $inputs['state_name'];
                $main_country = getCountryFromLatLong($inputs['latitude'], $inputs['longitude']);
                $location = $this->addCurrentLocation($inputs['country_name'], $inputs['state_name'], $cityname);

                $country_code = getCountryFromLatLong($inputs['latitude'], $inputs['longitude']);
                if ($location) {
                    $address = Address::updateOrCreate(['entity_type_id' => EntityTypes::SHOP, 'entity_id' => $shop_id], [
                        'address' => $inputs['address'],
                        'address2' => $inputs['address_detail'] ?? NULL,
                        'country_id' => !empty($location['country']->id) ? $location['country']->id : NULL,
                        'city_id' => $location['city']->id,
                        'state_id' => $location['city']->state_id,
                        'latitude' => $inputs['latitude'],
                        'longitude' => $inputs['longitude'],
                        'entity_type_id' => EntityTypes::SHOP,
                        'main_country' => $country_code,
                        'entity_id' => $shop_id,
                        'main_country' => $main_country,
                    ]);
                }
            } else {

                $jsonData = [
                    'status_code' => 400,
                    'message' => "Please enter proper address.",
                ];
                return response()->json($jsonData);
            }

            $shopsFolder = config('constant.shops') . '/' . $shop_id;

            if (!Storage::exists($shopsFolder)) {
                Storage::makeDirectory($shopsFolder);
            }


            $isReload = false;
            if (isset($inputs['credit_plan'])) {
                $shop = DB::table('shops')->whereId($shop_id)->first();
                $checkdataUser = UserDetail::where('user_id', $shop->user_id)->first();

                $checkdataUser->package_plan_id = $inputs['credit_plan'];

                $checkdataUser->save();

                //->update(['package_plan_id'=>$inputs['credit_plan']]);

                if ($checkdataUser->wasChanged()) {
                    $isReload = true;
                }
            }

            $thumb = DB::table('shop_images')->whereNull('deleted_at')->where('shop_image_type', ShopImagesTypes::THUMB)->where('shop_id', $shop_id)->first();
            $url = !empty($thumb) ? Storage::disk('s3')->url($thumb->image) : '';

            if (!empty($inputs['thumbnail_image'])) {
                if ($request->hasFile('thumbnail_image')) {


                    if (!empty($thumb)) {
                        Storage::disk('s3')->delete($thumb->image);
                        ShopImages::where('id', $thumb->id)->delete();
                    }
                    $thumbnail_image = Storage::disk('s3')->putFile($shopsFolder, $request->file('thumbnail_image'), 'public');
                    $fileName = basename($thumbnail_image);
                    $finalImage = $shopsFolder . '/' . $fileName;
                    ShopImages::create(['shop_id' => $shop_id, 'shop_image_type' => ShopImagesTypes::THUMB, 'image' => $finalImage]);
                    $url = Storage::disk('s3')->url($finalImage);

                    $newThumb = Image::make($request->file('thumbnail_image'))->resize(200, 200, function ($constraint) {
                        $constraint->aspectRatio();
                    })->encode(null, 90);
                    Storage::disk('s3')->put($shopsFolder . '/thumb/' . $fileName,  $newThumb->stream(), 'public');
                }
            }

            $profileController = new \App\Http\Controllers\Api\ShopProfileController;
            $profileController->checkShopStatus($shop_id);
            DB::commit();
            $jsonData = [
                'status_code' => 200,
                'message' => trans("messages.shop.save-detail"),
                'url' => $url,
                'is_reload' => $isReload
            ];
            return response()->json($jsonData);
        } catch (\Exception $ex) {
            DB::rollBack();
            $jsonData = [
                'status_code' => 400,
                'message' => trans("messages.shop.not-save-detail"),
                'is_reload' => false
            ];
            return response()->json($jsonData);
        }
    }

    public function uploadHospitalImages(Request $request)
    {
        try {
            DB::beginTransaction();
            $inputs = $request->all();

            $hospital_id = $inputs['hospital_id'];
            $main_name = $inputs['main_name'];

            $hospitalsFolder = config('constant.hospitals') . '/' . $hospital_id;

            if (!Storage::exists($hospitalsFolder)) {
                Storage::makeDirectory($hospitalsFolder);
            }

            $shopMainProfileImages = [];
            $uploadedFilesHtml = '';
            if (!empty($inputs['files'])) {
                foreach ($inputs['files'] as $image) {
                    $mainProfile = Storage::disk('s3')->putFile($hospitalsFolder, $image, 'public');
                    $fileName = basename($mainProfile);
                    $image_url = $hospitalsFolder . '/' . $fileName;
                    $data = [
                        'hospital_id' => $hospital_id,
                        'image' => $image_url
                    ];
                    $addNew = HospitalImages::create($data);
                    $uploadedFilesHtml .= '<div style="display:inline-grid;cursor: pointer;" id="image_' . $addNew->id . '"><div class="gallery-item" data-image="' . Storage::disk('s3')->url($image_url) . '" data-title="' . $main_name . '" href="' . Storage::disk('s3')->url($image_url) . '" title="' . $main_name . '" style="background-image:url(' . Storage::disk('s3')->url($image_url) . ');" ></div><a class="deleteImages float-right text-danger pb-2 pl-3" type="" id="' . $addNew->id . '"><strong>Delete</strong></a></div>';
                }
            }
            DB::commit();
            $jsonData = [
                'status_code' => 200,
                'message' => trans("messages.hospital.update-success"),
                'uploadedFilesHtml' => $uploadedFilesHtml
            ];
            return response()->json($jsonData);
        } catch (\Exception $ex) {
            Log::info($ex);
            DB::rollBack();
            $jsonData = [
                'status_code' => 400,
                'message' => trans("messages.hospital.not-save-detail"),
            ];
            return response()->json($jsonData);
        }
    }
    public function uploadShopImages(Request $request)
    {
        try {
            DB::beginTransaction();
            $inputs = $request->all();

            $shop_id = $inputs['shop_id'];
            $main_name = $inputs['main_name'];
            $type = $inputs['type'];

            $shopsFolder = config('constant.shops') . '/' . $shop_id;

            if (!Storage::exists($shopsFolder)) {
                Storage::makeDirectory($shopsFolder);
            }

            $shopMainProfileImages = [];
            $uploadedFilesHtml = '';
            if (!empty($inputs['files'])) {
                foreach ($inputs['files'] as $mainProfileImage) {
                    $mainProfile = Storage::disk('s3')->putFile($shopsFolder, $mainProfileImage, 'public');
                    $fileName = basename($mainProfile);
                    $image_url = $shopsFolder . '/' . $fileName;
                    $data = [
                        'shop_id' => $shop_id,
                        'shop_image_type' => ($type == 'main_profile') ? ShopImagesTypes::MAINPROFILE : ShopImagesTypes::WORKPLACE,
                        'image' => $image_url
                    ];
                    $addNew = ShopImages::create($data);
                    $uploadedFilesHtml .= '<div style="display:inline-grid;cursor: pointer;" id="image_' . $addNew->id . '"><div class="gallery-item" data-image="' . Storage::disk('s3')->url($image_url) . '" data-title="' . $main_name . '" href="' . Storage::disk('s3')->url($image_url) . '" title="' . $main_name . '" style="background-image:url(' . Storage::disk('s3')->url($image_url) . ');" ></div><a class="deleteImages float-right text-danger pb-2 pl-3" type="' . $type . '" id="' . $addNew->id . '"><strong>Delete</strong></a></div>';

                    $newThumb = Image::make($mainProfileImage)->resize(200, 200, function ($constraint) {
                        $constraint->aspectRatio();
                    })->encode(null, 90);
                    Storage::disk('s3')->put($shopsFolder . '/thumb/' . $fileName,  $newThumb->stream(), 'public');
                }
            }

            $profileController = new \App\Http\Controllers\Api\ShopProfileController;
            $profileController->checkShopStatus($shop_id);
            DB::commit();
            $jsonData = [
                'status_code' => 200,
                'message' => trans("messages.shop.save-detail"),
                'uploadedFilesHtml' => $uploadedFilesHtml
            ];
            return response()->json($jsonData);
        } catch (\Exception $ex) {

            DB::rollBack();
            $jsonData = [
                'status_code' => 400,
                'message' => trans("messages.shop.not-save-detail"),
            ];
            return response()->json($jsonData);
        }
    }

    public function deleteHospitalImages(Request $request)
    {
        try {
            DB::beginTransaction();
            $inputs = $request->all();

            $id = $inputs['id'];
            $image = DB::table('hospital_images')->whereId($id)->first();
            $image_url = Storage::disk('s3')->delete($image->image);
            $deleteImage = HospitalImages::where('id', $id)->delete();

            DB::commit();
            $jsonData = [
                'status_code' => 200,
                'message' => trans("messages.hospital.update-success"),
            ];
            return response()->json($jsonData);
        } catch (\Exception $ex) {
            DB::rollBack();
            $jsonData = [
                'status_code' => 400,
                'message' => trans("messages.hospital.not-save-detail"),
            ];
            return response()->json($jsonData);
        }
    }
    public function deleteShopImages(Request $request)
    {
        try {
            DB::beginTransaction();
            $inputs = $request->all();

            $id = $inputs['id'];
            $type = $inputs['type'];
            $image = ShopImages::find($id);
            $image_url = Storage::delete($image->image);
            $deleteImage = ShopImages::where('id', $id)->delete();

            $profileController = new \App\Http\Controllers\Api\ShopProfileController;
            // $profileController->checkShopStatus($shop_id);
            DB::commit();
            $jsonData = [
                'status_code' => 200,
                'message' => trans("messages.shop.save-detail")
            ];
            return response()->json($jsonData);
        } catch (\Exception $ex) {
            DB::rollBack();
            $jsonData = [
                'status_code' => 400,
                'message' => trans("messages.shop.not-save-detail"),
            ];
            return response()->json($jsonData);
        }
    }

    public function viewShopProfile($id)
    {

        $shopCategory = DB::table('category')
            ->leftjoin('category_settings', function ($join) {
                $join->on('category_settings.category_id', '=', 'category.id')
                    ->where('category_settings.country_code', 'KR');
            })
            ->select('category.*')
            ->whereIn('category.category_type_id', [CategoryTypes::SHOP])
            ->whereNull('category.deleted_at')
            ->orderBy('category_settings.order', 'ASC')
            ->get();
        $shopCategory = collect($shopCategory)->mapWithKeys(function ($value) {
            return [$value->id => $value->name];
        })->toArray();

        $shopCustomCategory = $catSuggest = DB::table('category')->whereIn('category_type_id', [CategoryTypes::CUSTOM])->whereNull('deleted_at')->get();
        $shopCustomCategory = collect($shopCustomCategory)->mapWithKeys(function ($value) {
            return [$value->id => $value->name];
        })->toArray();
        $checkCustomCategory = collect($catSuggest)->map(function ($value) {
            return $value->id;
        })->toArray();

        $shops = Shop::leftjoin('category', 'category.id', 'shops.category_id')
            ->leftjoin('reviews', function ($join) {
                $join->on('shops.id', '=', 'reviews.entity_id')
                    ->where('reviews.entity_type_id', EntityTypes::SHOP);
            })
            ->whereIn('category.category_type_id', [CategoryTypes::SHOP, CategoryTypes::CUSTOM])
            ->where('shops.user_id', $id)
            ->groupby('shops.id')
            ->select('shops.*', 'category.name as category, category.category_type_id', DB::raw('round(AVG(reviews.rating),1) as avg_rating'))->get();
        return view('admin.business-client.check-shop-profile', compact('shops', 'shopCategory', 'shopCustomCategory', 'checkCustomCategory', 'id'));
    }


    /* ================ Shop Code End ======================= */

    public function editCredits($id)
    {
        $userCredits = UserCredit::where('user_id', $id)
            ->select('user_credits.*')
            ->first();

        $users_detail = UserDetail::join('users', function ($query) {
                $query->on('users.id', '=', 'users_detail.user_id');
            })
            ->where('users_detail.user_id',$id)
            ->select(['users_detail.is_increase_love_count_daily','users_detail.increase_love_count','users_detail.user_id','users.is_admin_access', 'users.is_support_user'])
            ->first();

        return view('admin.business-client.edit-credits', compact('userCredits','users_detail'));
    }

    public function editAccess(Request $request)
    {
        try {
            User::where('id', $request->user_id)->update(['is_admin_access' => $request->is_admin_access]);

            $jsonData = [
                'success' => true,
                'message' => "Admin access updated successfully.",
            ];
            return response()->json($jsonData);
        } catch (\Exception $ex) {
            $jsonData = [
                'success' => false,
                'message' => "Failed to update admin access!!",
            ];
            return response()->json($jsonData);
        }
    }

    public function editSupport(Request $request)
    {
        try {
            User::where('id', $request->user_id)->update(['is_support_user' => $request->is_support_user]);
            if ($request->is_support_user==0){
                UserDetail::where('user_id',$request->user_id)->update(['supporter_type'=>null]);
            }

            $jsonData = [
                'success' => true,
                'message' => "Support access updated successfully.",
            ];
            return response()->json($jsonData);
        } catch (\Exception $ex) {
            $jsonData = [
                'success' => false,
                'message' => "Failed to update support access!!",
            ];
            return response()->json($jsonData);
        }
    }

    public function editSupportType(Request $request)
    {
        try {
            DB::beginTransaction();

            $user = DB::table('users')
                ->where('id',$request->user_id)
                ->where('is_support_user',1)
                ->whereNull('deleted_at')
                ->first();
            if ($user){
                UserDetail::where('user_id',$request->user_id)->update(['supporter_type'=>$request->supporter_option]);
                $jsonData = [
                    'success' => true,
                    'message' => "Supporter updated successfully.",
                ];
            }
            else {
                $jsonData = [
                    'success' => false,
                    'message' => "Failed to update supporter!!",
                ];
            }

            DB::commit();
            return response()->json($jsonData);
        } catch (\Exception $ex) {
            DB::rollBack();
            $jsonData = [
                'success' => false,
                'message' => "Failed to update supporter!!",
            ];
            return response()->json($jsonData);
        }
    }

    public function updateCredits(Request $request)
    {

        try {
            DB::beginTransaction();
            Log::info('Credit add code start.');
            $inputs = $request->all();
            $userCredits = UserCredit::where('user_id', $inputs['userId'])->first();

            $old_credit = $userCredits->credits;
            $new_credit = $inputs['credits'];
            $total_credit = $old_credit + $new_credit;
            $userCredits = UserCredit::where('user_id', $inputs['userId'])->update(['credits' => $total_credit]);
            UserCreditHistory::create([
                'user_id' => $inputs['userId'],
                'amount' => $inputs['credits'],
                'transaction' => 'credit',
                'total_amount' => $total_credit,
                'type' => UserCreditHistory::DEFAULT
            ]);

            $logData = [
                'activity_type' => ManagerActivityLogs::UPDATE_COIN,
                'user_id' => auth()->user()->id,
                'value' => $inputs['credits'],
                'entity_id' => $inputs['userId'],
            ];
            $this->addManagerActivityLogs($logData);

            DB::commit();
            Log::info('Credit add code end.');
            return $this->sendSuccessResponse('Credit add successfully.', 200);
        } catch (\Exception $ex) {
            DB::rollBack();
            Log::info('Credit add code exception.');
            Log::info($ex);
            return $this->sendFailedResponse('Failed to add credits.', 400);
        }
    }

    public function editIncreaseLoveCount(Request $request)
    {
        try {
            UserDetail::where('user_id', $request->user_id)->update(['is_increase_love_count_daily' => $request->is_increase_love_count_daily]);

            $jsonData = [
                'success' => true,
                'message' => "Get Love Amount Daily updated successfully.",
            ];
            return response()->json($jsonData);
        } catch (\Exception $ex) {
            $jsonData = [
                'success' => false,
                'message' => "Failed to update Get Love Amount Daily!!",
            ];
            return response()->json($jsonData);
        }
    }

    public function updateDailyLoveCount(Request $request)
    {
        try {
            DB::beginTransaction();
            $inputs = $request->all();
            UserDetail::where('user_id',$inputs['userId'])->update(['increase_love_count' => $inputs['daily_love_count']]);

            DB::commit();
            return $this->sendSuccessResponse('Daily love amount updated successfully.', 200);
        } catch (\Exception $ex) {
            DB::rollBack();
            Log::info($ex);
            return $this->sendFailedResponse('Failed to edit daily love amount.', 400);
        }
    }

    public function deleteBusinessProfile(Request $request)
    {

        try {
            DB::beginTransaction();
            Log::info('Delete business profile code start.');
            $inputs = $request->all();
            $businessProfiles = UserEntityRelation::whereIn('entity_type_id', [EntityTypes::SHOP, EntityTypes::HOSPITAL])
                ->where('user_id', $inputs['userId'])->get();

            foreach ($businessProfiles as $profile) {
                if ($profile->entity_type_id == EntityTypes::SHOP) {
                    Shop::where('id', $profile->entity_id)->delete();
                }
                if ($profile->entity_type_id == EntityTypes::HOSPITAL) {
                    Hospital::where('id', $profile->entity_id)->delete();
                }
            }
            UserEntityRelation::whereIn('entity_type_id', [EntityTypes::SHOP, EntityTypes::HOSPITAL])
                ->where('user_id', $inputs['userId'])->delete();
            DB::commit();
            Log::info('Delete business profile code end.');
            return $this->sendSuccessResponse('Business profile deleted successfully.', 200);
        } catch (\Exception $ex) {
            DB::rollBack();
            Log::info('Delete business profile code exception.');
            Log::info($ex);
            return $this->sendFailedResponse('Failed to delete business profile.', 400);
        }
    }
    public function deleteUser(Request $request)
    {

        try {
            DB::beginTransaction();
            Log::info('Delete user code start.');
            $inputs = $request->all();
            $businessProfiles = UserEntityRelation::where('user_id', $inputs['userId'])->get();

            foreach ($businessProfiles as $profile) {
                if ($profile->entity_type_id == EntityTypes::SHOP) {
                    Shop::where('id', $profile->entity_id)->delete();
                }
                if ($profile->entity_type_id == EntityTypes::HOSPITAL) {
                    Hospital::where('id', $profile->entity_id)->delete();
                }
            }
            UserEntityRelation::where('user_id', $inputs['userId'])->delete();
            UserDetail::where('user_id', $inputs['userId'])->delete();
            User::where('id', $inputs['userId'])->delete();

            DB::commit();
            Log::info('Delete user code end.');
            return $this->sendSuccessResponse('user deleted successfully.', 200);
        } catch (\Exception $ex) {
            DB::rollBack();
            Log::info('Delete user code exception.');
            Log::info($ex);
            return $this->sendFailedResponse('Failed to delete user.', 400);
        }
    }

    public function displayBusinessName($business_name_group, $business_name)
    {
        $business_group = explode("|", $business_name_group);
        $business_group = collect($business_group)->unique()->values()->toArray();

        return !empty($business_group) ? $business_group[0] : $business_name;
    }

    public function commanShopQueryFunction($search, $manager_id, $type = 'all')
    {

        $shopHospitalCountAmount = 'count(DISTINCT shops.id) * credit_plans.amount ';

        $query = User::join('users_detail', 'users_detail.user_id', 'users.id')
            ->join('user_credits', 'user_credits.user_id', 'users.id')
            ->join('countries', 'users_detail.country_id', 'countries.id')
            ->leftjoin('managers', 'managers.id', 'users_detail.manager_id')
            ->leftjoin('credit_plans', function ($query) {
                $query->on('credit_plans.package_plan_id', '=', 'users_detail.package_plan_id')
                    ->where('credit_plans.entity_type_id', EntityTypes::SHOP);
            })
            ->join('shops', function ($query) use ($type) {
                $query->on('users.id', '=', 'shops.user_id')
                    ->where(function ($query) use ($type) {
                        if ($type == 'active') {
                            $query->where('shops.status_id', Status::ACTIVE);
                        } elseif ($type == 'inactive') {
                            $query->where('shops.status_id', Status::INACTIVE);
                        } elseif ($type == 'pending') {
                            $query->where('shops.status_id', Status::PENDING);
                        }
                    });
                //->whereRaw('shops.id IN (select MAX(a2.id) from shops as a2 group by a2.user_id)');
            })
            ->leftjoin('linked_social_profiles', 'shops.id', 'linked_social_profiles.shop_id')
            ->whereNull('shops.deleted_at')
            ->leftjoin('addresses', function ($join) {
                $join->on('shops.id', '=', 'addresses.entity_id')
                    ->where('addresses.entity_type_id', EntityTypes::SHOP);
            })
            ->whereIn('users.status_id', [Status::ACTIVE, Status::INACTIVE]);
        /* ->where(function($query) use ($type){
                switch($type) {
                    case 'active':
                        $query->where('users.status_id', Status::ACTIVE);
                      break;
                    case 'inactive':
                        $query->where('users.status_id', Status::INACTIVE);
                      break;
                    default:
                        $query->whereIn('users.status_id', [Status::ACTIVE, Status::INACTIVE]);
                }
            });  */

        if ($manager_id && $manager_id != 0) {
            $query = $query->where('managers.id', $manager_id);
        }
        $query = $query->select(
            'addresses.*',
            'shops.id as address_id',
            'users.id',
            'users.id as user_id',
            'users.status_id',
            'users.created_at',
            'users.email',
            'users_detail.name as user_name',
            'users_detail.mobile',
            'user_credits.credits',
            'shops.business_license_number',
            'shops.main_name',
            'shops.shop_name',
            'shops.email as email_address',
            'shops.created_at as business_created_date',
            // 'countries.name as country_name',
            'managers.name as manager_name',
            'credit_plans.amount',
            'shops.uuid as shop_uuid',
            DB::raw('group_concat(DISTINCT linked_social_profiles.social_name) as social_names'),
            $this->shopNameConcat
        )
            ->selectRaw("count(DISTINCT shops.id) AS total_shop_count")
            ->selectRaw("{$shopHospitalCountAmount} AS total_plan_amount")
            ->selectRaw("(CASE WHEN {$shopHospitalCountAmount} <= user_credits.credits THEN  1
                ELSE 0
                END) AS is_user_active");

        $query = $query->groupBy('users.id');

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('users_detail.name', 'LIKE', "%{$search}%")
                    ->orWhere('countries.name', 'LIKE', "%{$search}%")
                    ->orWhere('users_detail.mobile', 'LIKE', "%{$search}%")
                    ->orWhere('users.created_at', 'LIKE', "%{$search}%")
                    ->orWhere('user_credits.credits', 'LIKE', "%{$search}%")
                    ->orWhere('managers.name', 'LIKE', "%{$search}%")
                    ->orWhere('shops.shop_name', 'LIKE', "%{$search}%")
                    ->orWhere('shops.main_name', 'LIKE', "%{$search}%")
                    ->orWhere('linked_social_profiles.social_name', 'LIKE', "%{$search}%");
            });
        }

        switch ($type) {
            case 'active':
                $query->havingRaw("{$shopHospitalCountAmount} <= user_credits.credits");
                break;
            case 'inactive':
                $query->havingRaw("{$shopHospitalCountAmount} > user_credits.credits");
                break;
        }

        return $query;
    }

    public function reloadCoinUser(Request $request, $id)
    {
        $inputs = $request->all();
        try {
            $new_credit =  $inputs['reload_amount'] ?? 0;

            $userCredits = UserCredit::where('user_id', $id)->first();

            $old_credit = $userCredits->credits;
            $total_credit = $old_credit + $new_credit;
            $userCredits = UserCredit::where('user_id', $id)->update(['credits' => $total_credit]);
            UserCreditHistory::create([
                'user_id' => $id,
                'amount' => $new_credit,
                'transaction' => 'credit',
                'total_amount' => $total_credit,
                'type' => UserCreditHistory::RELOAD
            ]);

            return $this->sendSuccessResponse('Reload coin successfully.', 200);
        } catch (\Exception $ex) {
            Log::info($ex);
            return $this->sendFailedResponse('Reload coin Failed.', 201);
        }
    }


    public function indexOutsideUser(Request $request)
    {
        $title = "Outside business";
        return view('admin.outside-users.index-shop', compact('title'));
    }

    public function getJsonAllOutsideUserShopData(Request $request)
    {
        try {
            Log::info('Start all shop list');

            $columns = array(
                0 => 'users.id',
                1 => 'users_detail.name',
                2 => 'countries.name',
                3 => 'users_detail.mobile',
                4 => 'user_credits.credits',
                5 => 'managers.name',
                6 => 'users.created_at',
                7 => 'rating',
                8 => 'shops.business_license_number',
                9 => 'is_user_active',
                10 => 'log',
                12 => 'action',
            );
            $limit = $request->input('length');
            $start = $request->input('start');
            $search = $request->input('search.value');
            $draw = $request->input('draw');
            $order = $columns[$request->input('order.0.column')];
            $dir = $request->input('order.0.dir');
            $adminTimezone = $this->getAdminUserTimezone();

            // Comman Query change where only
            $shopHospitalCountAmount = 'count(DISTINCT shops.id) * credit_plans.amount ';

            $query = User::join('users_detail', 'users_detail.user_id', 'users.id')
                ->join('user_credits', 'user_credits.user_id', 'users.id')
                ->leftjoin('countries', 'users_detail.country_id', 'countries.id')
                ->leftjoin('managers', 'managers.id', 'users_detail.manager_id')
                ->leftjoin('credit_plans', function ($query) {
                    $query->on('credit_plans.package_plan_id', '=', 'users_detail.package_plan_id')
                        ->where('credit_plans.entity_type_id', EntityTypes::SHOP);
                })
                ->join('shops', function ($query) {
                    $query->on('users.id', '=', 'shops.user_id');
                })
                ->leftjoin('addresses', function ($join) {
                    $join->on('shops.id', '=', 'addresses.entity_id')
                        ->where('addresses.entity_type_id', EntityTypes::SHOP);
                })
                ->whereNotNull('shops.business_link')
                ->where('shops.business_link', '!=', '')
                ->whereIn('users.status_id', [Status::ACTIVE, Status::INACTIVE]);

            $query = $query->select(
                'addresses.*',
                'shops.id as address_id',
                'users.id',
                'users.status_id',
                'users.created_at',
                'users.email',
                'users_detail.name as user_name',
                'users_detail.mobile',
                'user_credits.credits',
                'shops.business_license_number',
                'shops.main_name',
                'shops.shop_name',
                'shops.email as email_address',
                'shops.created_at as business_created_date',
                'shops.status_id as shop_status',
                // 'countries.name as country_name',
                'managers.name as manager_name',
                'credit_plans.amount',
                $this->shopNameConcat
            )
                ->selectRaw("{$shopHospitalCountAmount} AS total_plan_amount")
                ->selectRaw("(CASE WHEN {$shopHospitalCountAmount} <= user_credits.credits THEN  1
                        ELSE 0
                        END) AS is_user_active");

            $query = $query->groupBy('users.id');

            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('users_detail.name', 'LIKE', "%{$search}%")
                        ->orWhere('countries.name', 'LIKE', "%{$search}%")
                        ->orWhere('users_detail.mobile', 'LIKE', "%{$search}%")
                        ->orWhere('users.created_at', 'LIKE', "%{$search}%")
                        ->orWhere('user_credits.credits', 'LIKE', "%{$search}%")
                        ->orWhere('managers.name', 'LIKE', "%{$search}%")
                        ->orWhere('shops.shop_name', 'LIKE', "%{$search}%")
                        ->orWhere('shops.main_name', 'LIKE', "%{$search}%");
                });
            }

            $totalData = count($query->get());
            $totalFiltered = $totalData;

            $shops = $query->offset($start)
                ->limit($limit)
                ->orderBy($order, $dir)
                ->get();

            $data = array();
            if (!empty($shops)) {
                foreach ($shops as $value) {
                    $id = $value['id'];
                    $view = route('admin.business-client.shop.show', $id);
                    $nestedData['checkbox'] = "<td><div class=\"custom-checkbox custom-control\">
                    <input type=\"checkbox\" data-checkboxes=\"mygroup\" class=\"custom-control-input checkbox_id check-all-shop\" id=\"$id\" data-id=\"$id\" value=\"$id\" name=\"checkbox_id[]\"><label for=\"$id\" class=\"custom-control-label\">&nbsp;</label></div></td>";
                    $address = $value['address'];
                    $address .= $value['address2'] ? ',' . $value['address2'] : '';
                    $address .= $value['city_name'] ? ',' . $value['city_name'] : '';
                    $address .= $value['state_name'] ? ',' . $value['state_name'] : '';
                    $address .= $value['country_name'] ? ',' . $value['country_name'] : '';
                    // $nestedData['name'] = $value['user_name'];
                    $business_name = $value['main_name'] != "" ? $value['main_name'] . "/" : "";
                    $business_name .= $value['shop_name'];
                    $displayEmail = $value['email'] ?? $value['email'];
                    $nestedData['name'] = $this->displayBusinessName($value['business_name_group'], $business_name) . "|" . $displayEmail;
                    $nestedData['mobile'] = $value['mobile'];
                    $nestedData['address'] = $address;
                    $purchasedCredits = UserCreditHistory::where('user_id', $id)->where('type', UserCreditHistory::RELOAD)->sum('amount');
                    $nestedData['credits'] = number_format($purchasedCredits, 0) . "|" . number_format($value['credits'], 0);
                    $nestedData['join_by'] = $value['manager_name'];
                    $nestedData['manager_name'] = $value['manager_name'];
                    $nestedData['business_license_number'] = $value['business_license_number'];
                    // $nestedData['date'] = $value['created_at'];
                    $nestedData['date'] = $this->formatDateTimeCountryWise($value['business_created_date'], $adminTimezone, 'd-m-Y H:i');
                    $reviews = Reviews::join('shops', 'shops.id', 'reviews.entity_id')
                        ->where('reviews.entity_type_id', EntityTypes::SHOP)
                        ->where('shops.user_id', $value['id'])
                        ->select('reviews.*', DB::raw('round(AVG(reviews.rating),1) as avg_rating'))->get();
                    $nestedData['avg_rating'] = $reviews[0]->avg_rating;

                    /*
                    if ($value['is_user_active'] == true) {
                        $nestedData['status'] = '<span class="badge badge-success">&nbsp;</span>';
                    } else {
                        $nestedData['status'] = '<span class="badge badge-secondary">&nbsp;</span>';
                    }
                    */

                    if ($value->shop_status == Status::ACTIVE) {
                        $nestedData['status'] = '<span class="badge badge-success">&nbsp;</span>';
                    } elseif ($value->shop_status == Status::PENDING) {
                        $nestedData['status'] = '<span class="badge" style="background-color: #fff700;">&nbsp;</span>';
                    } else {
                        $nestedData['status'] = '<span class="badge badge-secondary">&nbsp;</span>';
                    }

                    $seeButton = "<a role='button' href='javascript:void(0)' onclick='viewLogs(" . $id . ")' title='' data-original-title='View' class='btn btn-primary btn-sm' data-toggle='tooltip'>see</a>";
                    $nestedData['credit_purchase_log'] = $seeButton;
                    $viewButton = "<a role='button' href='javascript:void(0)' onclick='viewShopProfile(" . $id . ")'  title='' data-original-title='View' class='btn btn-primary btn-sm' data-toggle='tooltip'><i class='fas fa-eye'></i></a>";
                    $user = Auth::user();
                    $editButton =  $user->hasRole('Admin') ? "<a href='javascript:void(0)' role='button' onclick='editCredits(" . $id . ")' class='btn btn-primary btn-sm mx-1' data-toggle='tooltip' data-original-title='Edit'><i class='fa fa-edit'></a>" : "";
                    $nestedData['actions'] = "<div class='d-flex'>$viewButton $editButton</div>";
                    $data[] = $nestedData;
                }
            }

            $jsonData = array(
                "draw" => intval($draw),
                "recordsTotal" => intval($totalData),
                "recordsFiltered" => intval($totalFiltered),
                "data" => $data
            );
            Log::info('End all shop list');
            return response()->json($jsonData);
        } catch (\Exception $ex) {
            print_r($ex->getMessage());
            die;
            Log::info('Exception all shop list');
            Log::info($ex);
            return response()->json([]);
        }
    }

    public function removeShopPost($id)
    {
        $shoppost = DB::table('shop_posts')->whereId($id)->first();

        Storage::disk('s3')->delete($shoppost->post_item);
        if ($shoppost->type == 'video') {
            Storage::disk('s3')->delete($shoppost->video_thumbnail);
        }

        if ($shoppost->is_multiple == true) {
            $images = DB::table('multiple_shop_posts')->where('shop_posts_id', $id)->get();
            foreach ($images as $data) {
                Storage::disk('s3')->delete($data->post_item);
                if ($data->type == 'video') {
                    Storage::disk('s3')->delete($data->video_thumbnail);
                }
            }
            MultipleShopPost::where('shop_posts_id', $id)->delete();
        }
        ShopPost::whereId($id)->delete();
        notify()->success("Portfolio successfully deleted", "Success", "topRight");
        return redirect()->route('admin.business-client.shop.show', [$shoppost->shop_id]);
    }

    public function removeAllShopPostImage(Request $request)
    {
        $inputs = $request->all();

        $deleteIds = $inputs['ids'];
        if (!empty($deleteIds)) {
            $shoppost = DB::table('shop_posts')->whereIn('id', $deleteIds)->get();

            foreach ($shoppost as $posts) {
                Storage::disk('s3')->delete($posts->post_item);
                if ($posts->type == 'video') {
                    Storage::disk('s3')->delete($posts->video_thumbnail);
                }

                if ($posts->is_multiple == true) {
                    $images = DB::table('multiple_shop_posts')->where('shop_posts_id', $posts->id)->get();
                    foreach ($images as $data) {
                        Storage::disk('s3')->delete($data->post_item);
                        if ($data->type == 'video') {
                            Storage::disk('s3')->delete($data->video_thumbnail);
                        }
                    }
                    MultipleShopPost::where('shop_posts_id', $posts->id)->delete();
                }
                ShopPost::whereId($posts->id)->delete();
            }
        }

        $jsonData = array(
            'success' => true,
            'message' => 'Portfolio Posts deleted successfully',
            'redirect' => route('admin.business-client.get.shop.post')
        );
        return response()->json($jsonData);
    }

    public function removeShopPostImage(Request $request)
    {
        $inputs = $request->all();
        $imageid = $inputs['imageid'] ?? '';

        if (!empty($imageid)) {
            $image = DB::table('multiple_shop_posts')->whereId($imageid)->first();
            if ($image) {
                Storage::disk('s3')->delete($image->post_item);
                MultipleShopPost::where('id', $image->id)->delete();
            }
        }
    }

    public function editShopPost(Request $request, $id, $from = '')
    {
        $title = "Edit Portfolio";
        ShopPost::findOrFail($id);
        $shoppost = ShopPost::whereId($id)->first()->toArray();
        $shop = DB::table('shops')->whereId($shoppost['shop_id'])->first();
        $instaData = LinkedSocialProfile::where('user_id', $shop->user_id)->where('shop_id', $shoppost['shop_id'])->where('social_type', LinkedSocialProfile::Instagram)->first();
        $role = "";
        if (Auth::user()->hasRole("Sub Admin")){
            $role = "Sub Admin";
        }

        return view('admin.business-client.edit-shoppost', compact('title', 'shoppost', 'from', 'shop', 'instaData','role'));
    }

    public function updateShopPost(Request $request, $id)
    {
        $inputs = $request->all();
        $shoppost = ShopPost::find($id);
        $shopsFolder = config('constant.shops') . "/posts/" . $id;

        try {
            $from = $inputs['from'] ?? '';
            $description = $inputs['description'] ?? '';
            $image_count = $inputs['image_count'] ?? 1;
            $is_multiple = $image_count > 1 ? 1 : 0;
            ShopPost::where('id', $id)->update(['description' => $description, 'is_multiple' => $is_multiple]);
            saveHashTagDetails($description, $id, HashTag::SHOP_POST);

            if (!empty($inputs['main_language_image'])) {
                foreach ($inputs['main_language_image'] as $image) {
                    if (is_file($image)) {
                        $data = [
                            'type' => 'image',
                        ];

                        $postImage = Storage::disk('s3')->putFile($shopsFolder, $image, 'public');
                        $fileName = basename($postImage);
                        $image_url = $shopsFolder . '/' . $fileName;
                        $data['post_item'] =  $image_url;
                        $data['shop_posts_id'] = $id;

                        $newThumb = Image::make($image)->resize(200, 200, function ($constraint) {
                            $constraint->aspectRatio();
                        })->encode(null, 90);
                        Storage::disk('s3')->put($shopsFolder . '/thumb/' . $fileName,  $newThumb->stream(), 'public');

                        MultipleShopPost::create($data);
                    }
                }
            }

            if (!empty($from) && $from == 'postlist') {
                $redirectURL = route('admin.business-client.get.shop.post');
            } else {
                $redirectURL = route('admin.business-client.shop.show', [$shoppost->shop_id]);
            }
            $jsonData = array(
                'success' => true,
                'message' => 'Portfolio updated successfully',
                'redirect' => $redirectURL
            );
            return response()->json($jsonData);
        } catch (\Exception $e) {
            $jsonData = array(
                'success' => false,
                'message' => 'Error in Portfolio update',
            );
            return response()->json($jsonData);
        }
    }

    public function createShopPost(Request $request, $id)
    {
        $title = "Create Portfolio";
        return view('admin.business-client.create-shoppost', compact('title', 'id'));
    }

    public function storeShopPost(Request $request, $id)
    {
        $inputs = $request->all();
        $description = $inputs['description'] ?? '';
        $shopsFolder = config('constant.shops') . "/posts/" . $id;
        $type = $inputs['type'] ?? 'image';

        if ($type == 'image') {
            $validator = Validator::make($request->all(), [
                "main_language_image" => "required|array",
            ], [], [
                'main_language_image' => 'Image',
            ]);
        } else {
            $validator = Validator::make($request->all(), [
                'post_item' => 'required',
            ], [], [
                'post_item' => 'Video',
            ]);
        }
        if ($validator->fails()) {
            return response()->json(array(
                'success' => false,
                'errors' => $validator->getMessageBag()->toArray()

            ), 400);
        }

        $insta_type = User::join('shops', 'shops.user_id', 'users.id')
                ->where('shops.id',$id)
                ->pluck('users.insta_type')
                ->first();
        $remain_download_insta = null;
        if ($insta_type=="pro"){
            $remain_download_insta = null;
        }
        elseif ($insta_type=="free"){
            $default_limit = InstaImportantSetting::where('field','Default download')->pluck('value')->first();
            $remain_download_insta = ($default_limit) ? (int)$default_limit : 10;
        }

        $addedPosts = [];
        if ($type == 'image') {
            if (!empty($inputs['main_language_image'])) {
                $addPost = (object)[];
                foreach ($inputs['main_language_image'] as $fileKey => $fileData) {
                    $data = [];
                    $data = [
                        'type' => $type,
                    ];

                    if ($fileKey == 0) {
                        $data['shop_id'] = $id;
                        $data['description'] = $description;
                        $data['is_multiple'] = count($inputs['main_language_image']) > 1 ? 1 : 0;
                    }

                    if (is_file($fileData)) {
                        $postImage = Storage::disk('s3')->putFile($shopsFolder, $fileData, 'public');
                        $fileName = basename($postImage);
                        $image_url = $shopsFolder . '/' . $fileName;
                        $data['post_item'] =  $image_url;
                    }

                    $newThumb = Image::make($fileData)->resize(200, 200, function ($constraint) {
                        $constraint->aspectRatio();
                    })->encode(null, 90);
                    Storage::disk('s3')->put($shopsFolder . '/thumb/' . $fileName,  $newThumb->stream(), 'public');
                    if ($fileKey == 0) {
                        $data['post_order_date'] = Carbon::now();
                        $data['remain_download_insta'] = $remain_download_insta;
                        $addPost = ShopPost::create($data);
                        $addedPosts[] = $addPost->id;
                        saveHashTagDetails($description, $addPost->id, HashTag::SHOP_POST);
                    } else {
                        $data['shop_posts_id'] = $addPost->id;
                        MultipleShopPost::create($data);
                    }
                }
            }
        }
        else {
            $data = [
                'shop_id' => $id,
                'type' => $type,
                'description' => $description,
            ];

            if ($request->hasFile('post_item')) {
                $postImage = Storage::disk('s3')->putFile($shopsFolder, $request->file('post_item'), 'public');
                $fileName = basename($postImage);
                $image_url = $shopsFolder . '/' . $fileName;
                $data['post_item'] =  $image_url;
            }

            if ($type == 'video' && $request->hasFile('video_thumbnail')) {
                $postThumbImage = Storage::disk('s3')->putFile($shopsFolder, $request->file('video_thumbnail'), 'public');
                $fileThumbName = basename($postThumbImage);
                $image_thumb_url = $shopsFolder . '/' . $fileThumbName;
                $data['video_thumbnail'] =  $image_thumb_url;
            }

            $data['post_order_date'] = Carbon::now();
            $data['remain_download_insta'] = $remain_download_insta;
            $addPost = ShopPost::create($data);
            saveHashTagDetails($description, $addPost->id, HashTag::SHOP_POST);
        }

        $shopUser = Shop::where('id', $id)->first();
        if (!empty($shopUser)) {
            $user_id = !empty($shopUser) ? $shopUser->user_id : 0;
            // points added onece per day
            $isAvailable = UserPoints::where(['user_id' => $user_id, 'entity_type' => UserPoints::UPLOAD_SHOP_POST, 'entity_created_by_id' => $user_id])->whereDate('created_at', Carbon::now()->format('Y-m-d'))->first();

            if (empty($isAvailable)) {
                UserPoints::create([
                    'user_id' => $user_id,
                    'entity_type' => UserPoints::UPLOAD_SHOP_POST,
                    'entity_id' => $addPost->id,
                    'entity_created_by_id' => $user_id,
                    'points' => UserPoints::UPLOAD_SHOP_POST_POINT
                ]);

                // Send Push notification start
                $notice = Notice::create([
                    'notify_type' => Notice::UPLOAD_SHOP_POST,
                    'user_id' => $user_id,
                    'to_user_id' => $user_id,
                    'entity_type_id' => EntityTypes::SHOP_POST,
                    'entity_id' => $addPost->id,
                    'title' => $addPost->description,
                    'sub_title' => $addPost->description,
                    'is_aninomity' => 0
                ]);

                $user_detail = UserDetail::where('user_id', $user_id)->first();
                $language_id = $user_detail->language_id;
                $key = Notice::UPLOAD_SHOP_POST . '_' . $language_id;
                $userIds = [$user_id];

                $format = __("notice.$key");
                $devices = UserDevices::whereIn('user_id', $userIds)->pluck('device_token')->toArray();

                $title_msg = __("notice.$key");
                $notify_type = Notice::UPLOAD_SHOP_POST;

                $notificationData = [
                    'id' => $addPost->id,
                    'user_id' => $user_id,
                    'title' => $title_msg,
                ];
                if (count($devices) > 0) {
                    $result = $this->sentPushNotification($devices, $title_msg, $format, $notificationData, $notify_type);
                }
                // Send Push notification end
            }
        }

        $profileController = new \App\Http\Controllers\Api\ShopProfileController;
        $profileController->checkShopStatus($id);

        /* notify()->success("Portfolio created successfully", "Success", "topRight");
        return redirect()->route('admin.business-client.shop.show',[$id]); */
        $jsonData = array(
            'success' => true,
            'message' => 'Portfolio created successfully',
            'redirect' => route('admin.business-client.shop.show', [$id])
        );
        return response()->json($jsonData);
    }

    public function indexShopPost(Request $request,$hashtag_id="")
    {
        $title = "Shop Posts";
        if ($hashtag_id=="") {
            DB::table('shop_posts')->where('is_admin_read', 1)->update(['is_admin_read' => 0]);
        }

        $query = Category::where('category.status_id', Status::ACTIVE)
            ->where('category.category_type_id', EntityTypes::SHOP)
            ->where('category.parent_id', 0)
            ->join('category_settings', 'category_settings.category_id', 'category.id')
            ->where('category_settings.country_code', 'KR')
            ->where('category_settings.is_show',1)
            ->select('category.id', 'category.name','category_settings.menu_key')
            ->orderBy('category_settings.order', 'ASC')->get();
        $categories = collect($query)->groupBy('menu_key');

        $menuItem = MenuSetting::select("*")->where('country_code', 'KR')->whereIn('menu_key',MenuSetting::MENU_CARD_LIST)->get();
//        dd($result->toArray(),$menuItem->toArray());
        return view('admin.business-client.index-shop-post', compact('title','categories', 'menuItem','hashtag_id'));
    }

    public function getJsonShopPostData(Request $request)
    {
        $columns = array(
            0 => 's.main_name',
            1 => 's.mobile',
            4 => 'shop_posts.updated_at',
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
        $chk_filters = $request->input('filters');
        $latitude = $request->input('latitude');
        $longitude = $request->input('longitude');
        $distance = $request->input('distance');
        $filter_date = $request->input('filter_date');
        $hashtag_id = $request->input('hashtag_id');

        try {
            $data = [];
            $query = ShopPost::Join('shops as s', 's.id', 'shop_posts.shop_id');

            if ($latitude!="" && $longitude!="" && $distance!=""){
                $query = $query->join('addresses', function ($join) {
                    $join->on('s.id', '=', 'addresses.entity_id')
                        ->where('addresses.entity_type_id', EntityTypes::SHOP);
                });
            }
            else {
                $query = $query->leftjoin('addresses', function ($join) {
                    $join->on('s.id', '=', 'addresses.entity_id')
                        ->where('addresses.entity_type_id', EntityTypes::SHOP);
                });
            }

            if ($filter_date!=""){
                $query = $query->whereDate('shop_posts.created_at', '=', $filter_date);
            }

            if ($hashtag_id!=""){
                $query = $query->join('hash_tag_mappings', function ($join) {
                    $join->on('shop_posts.id', '=', 'hash_tag_mappings.entity_id')
                        ->where('hash_tag_mappings.entity_type_id', HashTag::SHOP_POST);
                    })
                    ->join('hash_tags', function ($join) {
                        $join->on('hash_tag_mappings.hash_tag_id', '=', 'hash_tags.id')
                            ->where('hash_tag_mappings.entity_type_id', HashTag::SHOP_POST);
                    })
                    ->where('hash_tags.id',$hashtag_id);
            }

            $query = $query->whereNull('s.deleted_at')
                ->select(
                'shop_posts.*',
                's.main_name',
                's.shop_name',
                's.mobile',
                's.count_days',
                's.is_regular_service',
//                's.id as shop_id',
                'addresses.address',
                's.business_link',
                's.booking_link',
                'addresses.latitude',
                'addresses.longitude',
                's.user_id'
                );

            if (!in_array('all',$chk_filters)){
                $categories_filter = array_diff($chk_filters, ['all','image','video']);
                if (count($categories_filter)>0){
                    $query = $query->whereIn('s.category_id',$categories_filter);
                }
            }

            if (in_array('image',$chk_filters) && !in_array('video',$chk_filters)){
                $query = $query->where(function ($query1) {
                    $query1->orWhere('shop_posts.type', '=', 'image')
                        ->orWhereHas('multiple_posts', function($q) {
                            $q->where('type', '=', "image");
                        });
                });
            }
            else if (in_array('video',$chk_filters) && !in_array('image',$chk_filters)){
                $query = $query->where(function ($query1) {
                    $query1->orWhere('shop_posts.type', '=', 'video')
                        ->orWhereHas('multiple_posts', function($q) {
                            $q->where('type', '=', "video");
                        });
                });
            }
            else if (in_array('only-video',$chk_filters)){
                $query = $query->where('shop_posts.type', '=', 'video')
                    ->whereHas('multiple_posts', function($q) {
                        $q->where('type', '=', "video");
                    })
                    ->where(function ($q) {
                        $q->whereNotExists(function ($query) {
                            $query->select(DB::raw(1))
                                ->from('multiple_shop_posts')
                                ->whereColumn('shop_posts.id', '=', 'multiple_shop_posts.shop_posts_id')
                                ->where('type', '!=', 'video');
                        });
                    });
            }

            if ($latitude!="" && $longitude!="" && $distance!=""){
                $query = $query->where(function ($query) use($distance,$latitude,$longitude){
                        $query->whereRaw("(6371 * acos(cos(radians($latitude)) * cos(radians(addresses.latitude)) * cos(radians(addresses.longitude) - radians($longitude)) + sin(radians($latitude)) * sin(radians(addresses.latitude)))) <= ?", [$distance]);
                    });
//                    ->having('distance', '<=', $distance);
            }

            if (!empty($search)) {
                $query = $query->where(function ($q) use ($search) {
                    $q->where('s.main_name', 'LIKE', "%{$search}%")
                        ->orWhere('s.mobile', 'LIKE', "%{$search}%")
                        ->orWhere('shop_posts.description', 'LIKE', "%{$search}%")
                        ->orWhere('addresses.address', 'LIKE', "%{$search}%");
                });
            }

            $totalData = $query->count();
            $totalFiltered = $totalData;

            $postData = $query->offset($start)
                ->limit($limit)
                ->orderBy($order, $dir)
                ->get();
//            dd($postData->toArray());

            $count = 0;
            foreach ($postData as $value) {
                $id = $value->id;
                $images = $edited = '';
                $post_urls = [];
                $instaData = LinkedSocialProfile::where('user_id', $value->user_id)->where('shop_id', $value->shop_id)->where('social_type', LinkedSocialProfile::Instagram)->first();
                $social_name = "";
                if (!empty($instaData->social_name)){
                    $social_name = $instaData->social_name;
                }

                $linked_social_profiles = LinkedSocialProfile::where('shop_id',$value->shop_id)->first();
                if (!empty($linked_social_profiles)){
                    if ($linked_social_profiles->is_valid_token == 0){
                        $instagram_status = '<span class="badge" style="background-color: #fff700;">&nbsp;</span>';
                    }
                    else {
                        $instagram_status = '<span class="badge badge-success">&nbsp;</span>';
                    }
                }
                else {
                    $instagram_status = '<span class="badge badge-secondary">&nbsp;</span>';
                }

                if (!Carbon::parse($value->display_updated_at)->isSameDay(Carbon::parse($value->display_created_at))) {
                    //$edited = "<div class='edited-dots'>$value->display_updated_at</div>";
                    $edited = "<div class='edited-dots'>" . $this->formatDateTimeCountryWise($value->display_updated_at, $adminTimezone, 'd-m-Y H:i') . "</div>";
                }

                if ($value->multiple_shop_posts) {
                    foreach ($value->multiple_shop_posts as $postImage) {
                        if ($postImage['type'] == 'image') {
                            //$url = Storage::disk('s3')->url($postImage['post_item']);
                            $url = (!str_contains($postImage['post_item'], 'amazonaws')) ? Storage::disk('s3')->url($postImage['post_item']) : $postImage['post_item'];
//                            $post_urls[] = $postImage['post_item'];
                            $post_urls[] = $url;
                            $images .= ($postImage['post_item']) ? '<img onclick="showImage(`' . $url . '`,`'.$value->main_name.'`,`'.$value->shop_name.'`,`'.$value->business_link.'`,`'.$social_name.'`)" src="' . $url . '" alt="' . $postImage['id'] . '" class="reported-client-images pointer m-1" width="50" height="50" />' : '';
                        } else {
                            //$url = Storage::disk('s3')->url($postImage['video_thumbnail']);
                            //$thumbImage = Storage::disk('s3')->url($postImage['post_item']);

                            $url = (!str_contains($postImage['video_thumbnail'], 'amazonaws')) ? Storage::disk('s3')->url($postImage['video_thumbnail']) : $postImage['video_thumbnail'];
                            $thumbImage = ($postImage['post_item']!=null && !str_contains($postImage['post_item'], 'amazonaws')) ? Storage::disk('s3')->url($postImage['post_item']) : $postImage['post_item'];
//                            $post_urls[] = $postImage['post_item'];
                            $post_urls[] = $thumbImage;
                            $images .= ($postImage['video_thumbnail']) ? '<img onclick="showImage(`' . $thumbImage . '`,`'.$value->main_name.'`,`'.$value->shop_name.'`,`'.$value->business_link.'`,`'.$social_name.'`)" src="' . $url . '" alt="' . $postImage['id'] . '" class="reported-client-images pointer m-1" width="50" height="50" style="border: 2px solid #555;padding: 2px;"/>' : '';
                        }
                    }
                }

                $editLinkURL = route('admin.business-client.shoppost.edit', ['id' => $id, 'from' => 'postlist']);
                $editButton = "<a role='button' href='$editLinkURL' title='' data-original-title='View' class='mr-2 btn btn-primary btn-sm ' data-toggle='tooltip'><i class='fas fa-edit mt-1'></i></a>";

                $profileLink = route('admin.business-client.shop.show', [$value->shop_id]);
                $viewButton = "<a role='button' href='$profileLink' title='' data-original-title='View' class='mr-2 btn btn-primary btn-sm ' data-toggle='tooltip'><i class='fas fa-eye mt-1'></i></a>";
                $deleteButton = "<a href='javascript:void(0)' role='button' onclick='deleteShopPost(" . $id . ")' class='btn btn-danger' data-toggle='tooltip' data-original-title=" . Lang::get('general.delete') . "><i class='fa fa-trash'></a>";
                $jsonEncodedUrls = htmlspecialchars(json_encode($post_urls), ENT_QUOTES, 'UTF-8');
                $downloadButton = '<a role="button" onclick="downloadPosts('.$jsonEncodedUrls.')" title="" data-original-title="Download" class="mr-2 btn btn-primary btn-sm" data-toggle="tooltip"><i class="fas fa-download mt-1"></i></a>';

                $data[$count]['checkbox'] = "<div class=\"custom-checkbox custom-control\">
                    <input type=\"checkbox\" data-checkboxes=\"mygroup\" class=\"custom-control-input checkbox_id check-all-hospital\" id=\"delete_$id\" data-id=\"$id\" value=\"$id\" name=\"checkbox_id[]\" shop-id=\"$value->shop_id\"><label for=\"delete_$id\" class=\"custom-control-label\">&nbsp;</label></div>";

                $link_btns = "<div class='d-flex'>";
                if ($value->business_link!="") {
                    $link_btns .= '<a href="javascript:void(0);" onClick="copyTextLink(`' . $value->business_link . '`,\'Business link\')" class="mr-2 btn btn-primary">Open link</a>';
                }
                if($value->booking_link!=null) {
                    $link_btns .= '<a href="javascript:void(0);" onClick="copyTextLink(`' . $value->booking_link . '`,\'Booking link\')" class="mr-2 btn btn-primary">Naver link</a>';
                }
                if(!empty($instaData->social_name) && $instaData->social_name!=null) {
                    $link_btns .= '<a href="https://www.instagram.com/'.$instaData->social_name.'" class="mr-2 btn btn-primary" target="_blank">'.$instaData->social_name.'</a>';
                }
                $link_btns .= $instagram_status;
                $link_btns .= "</div>";
                $names = "";
                if ($value->main_name!=null && $value->shop_name!=null){
                    $names .= '<span onclick="copyTextLink(`'.$value->main_name.'`,`'.$value->main_name.'`)" style="cursor:pointer;">'.$value->main_name.'</span>';
                    $names .= '/';
                    $names .= '<span onclick="copyTextLink(`'.$value->shop_name.'`,`'.$value->shop_name.'`)" style="cursor:pointer;">'.$value->shop_name.'</span>';
                }
                else if ($value->main_name!=null){
                    $names .= '<span onclick="copyTextLink(`'.$value->main_name.'`,`'.$value->main_name.'`)" style="cursor:pointer;">'.$value->main_name.'</span>';
                }
                else if ($value->shop_name!=null){
                    $names .= '<span onclick="copyTextLink(`'.$value->shop_name.'`,`'.$value->shop_name.'`)" style="cursor:pointer;">'.$value->shop_name.'</span>';
                }
                $data[$count]['business_name'] = $names.$link_btns;
                $data[$count]['business_phone'] =  $value->mobile;

                $decodedText = html_entity_decode($value->description);
                $words = preg_split('/\s+/', $decodedText);
                $limitedText = implode(' ', array_slice($words, 0, 20));
                $remainingText = implode(' ', array_slice($words, 20));
                $description = $limitedText;
                if(!empty($remainingText)){
                    $description .= '<span id="see-more-'.$value->id.'" style="display: none;">'.$remainingText.'</span><a id="see-more-link-'.$value->id.'" onclick="toggleSeeMore('.$value->id.')" style="color: #007bff;cursor: pointer">See More</a>';
                }
                $data[$count]['description'] = $description;
                $data[$count]['update_date'] = $this->formatDateTimeCountryWise($value->display_created_at, $adminTimezone, 'd-m-Y H:i') . $edited;

                $service_checked = "";
                if ($value->is_regular_service) {
                    $service_checked = "checked";
                }

                $service = '<input id="regular_service" ' . $service_checked . ' type="checkbox" name="regular_service" value="1" class="form-check-input ml-0 " disabled /><label class="ml-4 pl-1 pt-2">Regular Service</label>';

                $instaDate = Carbon::now()->addDays($value->count_days)->format('Y-m-d');
                if ($value->count_days == 0 && $value->last_count_updated_at != NULL) {
                    $instaDate = Carbon::parse($value->last_count_updated_at)->format('Y-m-d');
                }

                $data[$count]['service'] = "<div class='update_service' id='" . $value->shop_id . "' onclick='instagramServicePopup(`" . $value->shop_id . "`);' ><div class='count_days'>" . $value->count_days . "</div><div class='expiry_date'>" . $instaDate . "</div><div class='service'>" . $service . "</div></div>";

                //$data[$count]['update_date'] = Carbon::parse($value->display_created_at)->format('d-m-Y H:i'). $edited;
                $data[$count]['images'] = $images;
                $data[$count]['actions'] = "<div class='d-flex'>$downloadButton $editButton $viewButton $deleteButton</div>";
                $data[$count]['address'] = $value->address;

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
            $jsonData = array(
                "draw" => intval($draw),
                "recordsTotal" => 0,
                "recordsFiltered" => 0,
                "data" => []
            );
            return response()->json($jsonData);
        }
    }

    public function ShopPostUrl(Request $request){
        $inputs = $request->all();
        $shopPostIds = $inputs['ids'];
        $final_data = [];

        if (!empty($shopPostIds)) {
            foreach ($shopPostIds as $key=>$shopPostId){
                $shop_post = ShopPost::find($shopPostId);
                if ($shop_post->multiple_shop_posts) {
                    foreach ($shop_post->multiple_shop_posts as $postImage) {
                        if ($postImage['type'] == 'image') {
                            $url = (!str_contains($postImage['post_item'], 'amazonaws')) ? Storage::disk('s3')->url($postImage['post_item']) : $postImage['post_item'];
                            $post_urls['row_no'] = $key+1;
                            $post_urls['value'] = $url;
                        } else {
                            $thumbImage = ($postImage['post_item']!=null && !str_contains($postImage['post_item'], 'amazonaws')) ? Storage::disk('s3')->url($postImage['post_item']) : $postImage['post_item'];
                            $post_urls['row_no'] = $key+1;
                            $post_urls['value'] = $thumbImage;
                        }
                        array_push($final_data,$post_urls);
                    }
                }
            }
        }

//        $jsonEncodedUrls = htmlspecialchars(json_encode($post_urls), ENT_QUOTES, 'UTF-8');
        $jsonData = array(
            'success' => true,
            'urls' => $final_data
        );
        return response()->json($jsonData);
    }

    public function downloadShopPost(Request $request)
    {
        $url_data = json_encode($request->all(),true);
        $url_data = json_decode($url_data,true);
        $urls_arr =  $url_data['urls'];

        try {
            $zip = new \ZipArchive();
            $zipPath = public_path('files.zip');

            if ($zip->open($zipPath, \ZipArchive::CREATE) === true) {
                // Perform operations on the zip file
                $zip->addFile(asset('img/banner.jpg'), 'image_1.jpg');
                $zip->close();
                echo asset('img/banner.jpg');
                echo "Zip file created successfully: ".$zip->status;
            } else {
                echo "Failed to create zip file. Error code: " . $zip->status;
            }
        } catch (\Exception $e) {
            echo "Error creating zip file: " . $e->getMessage();
        }
        die();

        // Create a zip archive
        $zip = new \ZipArchive();
        $zipFileName = 'files.zip';
        if ($zip->open(public_path($zipFileName), \ZipArchive::CREATE) === TRUE)
        {
            foreach ($urls_arr as $key => $filePath) {
                if ($key!=0) {
                    $path = parse_url($filePath, PHP_URL_PATH);
                    $parts = explode('/uploads/', $path);
                    $filePath = 'uploads/' . $parts[1];
                }

                if (Storage::disk('s3')->exists($filePath)) {
                    $full_file_url = Storage::disk('s3')->url($filePath);
                    $zip->addFile($full_file_url, basename($full_file_url));
                } else {
                    // File does not exist in the S3 bucket
                    return 'File not found: ' . $filePath;
                }
            }

            $zip->close();
        }
        else {
            // Opening the ZIP file failed
            echo 'Error: ' . $zip->getStatusString();
        }
        // Add files to the zip archive
        /*foreach ($urls_arr as $filePath) {
            if (Storage::disk('s3')->exists($filePath)) {
                // File exists in the S3 bucket
                $full_file_url = Storage::disk('s3')->url($filePath);
                $zip->addFile($full_file_url, basename($full_file_url));
            } else {
                // File does not exist in the S3 bucket
                return 'File not found: ' . $filePath;
            }
        }
        $zip->close();*/

        /*if (file_exists($zipFileName)) {
            // Perform your ZipArchive operations here
        } else {
            throw new FileNotFoundException("The file '$zipFileName' does not exist.");
        }*/
        // Download the zip file
        return response()->download(public_path($zipFileName));
    }

    public function proxyImage(Request $request){
        try {
            $filePath = $request->query('url');
            $path = parse_url($filePath, PHP_URL_PATH);
            $parts = explode('/uploads/', $path);
            $filePath = 'uploads/' . $parts[1];
            $client = Storage::disk('s3')->getDriver()->getAdapter()->getClient();
            $result = $client->headObject([
                'Bucket' => config('filesystems.disks.s3.bucket'),
                'Key' => $filePath,
            ]);
            if (Storage::disk('s3')->exists($filePath)) {
                $full_file = Storage::disk('s3')->get($filePath);
            }
            else {
                return response('', 400);
            }

            $headers = [
                'Content-Type' => $result['ContentType'],
            ];
            return response($full_file, 200, $headers);
        }
        catch (\Exception $e) {
            return response('', 400);
        }
    }

    public function deleteShopPost($id)
    {
        $title = "Shop Post";
        $type = "shop_post";
        return view('admin.business-client.delete', compact('id', 'title', 'type'));
    }

    public function destroyShopPost($id)
    {
        $shopPost = ShopPost::where('id', $id)->first();
        if ($shopPost) {
            $shop_id = $shopPost->shop_id;
            if ($shopPost->post_item) {

                if ($shopPost->is_multiple == 1) {
                    $getMultiShop = MultipleShopPost::where('shop_posts_id', $shopPost->id)->get();
                    if ($getMultiShop) {
                        foreach ($getMultiShop as $shopKey => $shopValue) {
                            $pos = strpos($shopValue->post_item, '/uploads');
                            $path = substr($shopValue->post_item, $pos);
                            Storage::delete($path);
                        }
                        MultipleShopPost::where('shop_posts_id', $shopPost->id)->delete();
                    }
                }
                $pos = strpos($shopPost->post_item, '/uploads');
                $path = substr($shopPost->post_item, $pos);
                Storage::delete($path);
            }
            $shopPost = ShopPost::where('id', $id)->delete();
        }
        DB::commit();
        $jsonData = [
            'status_code' => 200,
            'message' => trans("messages.shop.post-delete-success")
        ];
        return response()->json($jsonData);
    }

    public function viewShopPriceCategory($id, $cat_id = NULL)
    {
        $title = '';
        if (!empty($cat_id)) {
            $getTitle = ShopPriceCategory::where('id', $cat_id)->first('name');
            $title = !empty($getTitle) ? $getTitle->name : '';
        }
        return view('admin.business-client.shop-price-category', compact('id', 'cat_id', 'title'));
    }

    public function viewShopPrice($id, $cat_id, $price_id = NULL)
    {
        $priceData = NULL;
        if (!empty($price_id)) {
            $priceData = ShopPrices::with('images')->where('id', $price_id)->first();
        }
        $fileChecks = [
            'video/x-flv',
            'video/mp4',
            'application/x-mpegURL',
            'video/MP2T',
            'video/3gpp',
            'video/quicktime',
            'video/x-msvideo',
            'video/x-ms-wmv',
            'video/x-matroska'
        ];
        return view('admin.business-client.shop-price', compact('id', 'cat_id', 'priceData', 'fileChecks'));
    }

    public function priceremoveImage(Request $request)
    {
        $inputs = $request->all();
        $imageid = $inputs['imageid'] ?? '';

        if (!empty($imageid)) {
            $image = ShopPriceImages::whereId($imageid)->first();
            if ($image) {
                Storage::disk('s3')->delete($image->image);
                if (!empty($image->thumb_url)) {
                    Storage::disk('s3')->delete($image->thumb_url);
                }
                ShopPriceImages::where('id', $image->id)->delete();
            }
        }
    }

    public function saveShopPriceCategory(Request $request)
    {

        try {
            DB::beginTransaction();
            Log::info('Start code for the add currency');
            $inputs = $request->all();
            $user = Auth::user();

            $validator = Validator::make($request->all(), [
                'shop_id' => 'required',
                'title' => 'required',
            ], [], [
                'shop_id' => 'Shop Id',
                'title' => 'Title',
            ]);

            if ($validator->fails()) {
                return response()->json(array(
                    'response' => false,
                    'message' => implode('<br/>', $validator->errors()->all())
                ));
            }
            $cat_id = !empty($inputs['cat_id']) ? $inputs['cat_id'] : 0;
            $shop = Shop::find($inputs['shop_id']);
            if ($shop) {

                if (!empty($cat_id)) {
                    $shopItem = ShopPriceCategory::where('id', $cat_id)->update(['name' => $inputs['title']]);
                    DB::commit();
                    $jsonData = [
                        'response' => true,
                        'message' => trans("messages.shop-price-category.update-success"),
                        'is_edit' => true,
                        'cat_id' => $cat_id,
                        'html' => '',
                    ];
                } else {
                    $data = [
                        'shop_id' => $inputs['shop_id'],
                        'name' => $inputs['title'],
                    ];
                    $shopItem = ShopPriceCategory::create($data);
                    DB::commit();

                    $html = '<li class="list-group-item" id="list_' . $shopItem->id . '">';
                    $html .= '<span class="name">' . $inputs['title'] . '</span>';
                    $html .= '<a href="javascript:void(0)" onclick="viewShopPrice(' . $inputs['shop_id'] . ',' . $shopItem->id . ',0)" class="btn btn-primary btn-sm float-right rounded mr-1" >Add Item</a>';
                    $html .= '<a href="javascript:void(0)" onclick="deleteShopPrice(' . $shopItem->id . ',`shop_category`)" class="btn btn-danger btn-sm float-right rounded mr-1" >Delete</a>';
                    $html .= '<a href="javascript:void(0)" onclick="viewShopPriceCategory(' . $inputs['shop_id'] . ',' . $shopItem->id . ')" class="btn btn-primary btn-sm float-right rounded mr-1" >Edit</a>';
                    $html .= '<ul class="list-group shopPriceBlock mt-3" id="shop_item_' . $shopItem->id . '">';
                    $html .= '</li>';

                    $jsonData = [
                        'response' => true,
                        'message' => trans("messages.shop-price-category.add-success"),
                        'is_edit' => false,
                        'cat_id' => $cat_id,
                        'html' => $html,
                    ];
                }
                return response()->json($jsonData);
            } else {
                return response()->json(array(
                    'response' => false,
                    'message' => Lang::get('messages.shop.empty')
                ));
            }
        } catch (\Exception $e) {
            return response()->json(array(
                'response' => false,
                'message' => trans("messages.save-error")

            ), 400);
        }
    }

    public function saveShopPrice(Request $request)
    {
        try {
            DB::beginTransaction();
            Log::info('Start code for the add currency');
            $inputs = $request->all();
            $user = Auth::user();

            $validator = Validator::make($request->all(), [
                'name' => 'required',
                'price' => 'required',
            ], [], [
                'name' => 'Name',
                'price' => 'Price',
            ]);

            if ($validator->fails()) {
                return response()->json(array(
                    'response' => false,
                    'message' => implode('<br/>', $validator->errors()->all())
                ));
            }

            $shopItemCatogory = ShopPriceCategory::find($inputs['cat_id']);
            if ($shopItemCatogory) {
                if(isset($inputs['main_price_display']) && $inputs['main_price_display']==1){
                    $shop_price_cats = ShopPriceCategory::where('shop_id',$shopItemCatogory->shop_id)->pluck('id')->toArray();
                    ShopPrices::whereIn('shop_price_category_id',$shop_price_cats)->update([
                        'main_price_display' => 0
                    ]);
                }

                $data = [
                    'shop_price_category_id' => $inputs['cat_id'],
                    'name' => $inputs['name'],
                    'price' => str_replace(',','',$inputs['price']),
                    'discount' => isset($inputs['discount_price']) ? str_replace(',','',$inputs['discount_price']) : 0,
                    'main_price_display' => (isset($inputs['main_price_display']) && $inputs['main_price_display']==1) ? 1 : 0,
                ];

                $shopsPriceFolder = config('constant.shops_price');


                if (isset($inputs['price_id']) && !empty($inputs['price_id'])) {
                    $shopItemID = $inputs['price_id'];
                    ShopPrices::where('id', $inputs['price_id'])->update($data);
                    $responseData = array(
                        'response' => true,
                        'message' => Lang::get('messages.shop-price.update-success'),
                        'is_edit' => true,
                        'html' => ''
                    );
                } else {
                    $shopItem = ShopPrices::create($data);
                    $shopItemID = $shopItem->id;
                    $html = '';
                    $html .= '<li class="list-group-item" id="shop_price_' . $shopItem->id . '">';
                    $html .= '<span class="name">' . $inputs['name'] . '</span><br/>';
                    $html .= '<span class="price">' . $inputs['price'] . '</span>';
                    $html .= '<div class="float-right"><span class="display_price_image"></span>';
                    $html .= '<a href="javascript:void(0)" onclick="viewShopPrice(' . $shopItemCatogory->shop_id . ',' . $inputs['cat_id'] . ',' . $shopItem->id . ')" class="btn btn-primary btn-sm  rounded mr-1" >Edit</a>';
                    $html .= '<a href="javascript:void(0)" onclick="deleteShopPrice(' . $shopItem->id . ',`shop_price`)" class="btn btn-danger btn-sm  rounded mr-1" >Delete</a>';
                    $html .= '</div>';
                    $html .= '</li>';

                    $responseData = array(
                        'response' => true,
                        'message' => Lang::get('messages.shop-price.update-success'),
                        'is_edit' => false,
                        'html' => $html,
                        'shop_item_id' => $shopItemID
                    );
                }



                if (!empty($inputs['main_images'])) {
                    foreach ($inputs['main_images'] as $image) {
                        $is_video = false;
                        if (is_file($image)) {
                            $thumbImageUrl = '';
                            $fileType = $image->getMimeType();

                            $fileChecks = [
                                'video/x-flv',
                                'video/mp4',
                                'application/x-mpegURL',
                                'video/MP2T',
                                'video/3gpp',
                                'video/quicktime',
                                'video/x-msvideo',
                                'video/x-ms-wmv',
                                'video/x-matroska'
                            ];
                            if (in_array($fileType, $fileChecks)) {
                                $thumbnail_image  = "thumb.jpg";
                                $thumbnail_image_path = $shopsPriceFolder . '/' . $thumbnail_image;

                                if (!Storage::exists($shopsPriceFolder)) {
                                    Storage::makeDirectory($shopsPriceFolder);
                                }

                                if (!file_exists($thumbnail_image_path)) {
                                    Storage::put($thumbnail_image_path, '');
                                }

                                $thumbnail_image_path = Storage::path($thumbnail_image_path);
                                /* $ffmpeg = FFMpeg::create(
                                    array(
                                        'ffmpeg.binaries'  => exec('which ffmpeg'),
                                        'ffprobe.binaries' => exec('which ffprobe'),
                                        'timeout'          => env('FFMPEG_TIMEOUT'),
                                        'ffmpeg.threads'   => env('FFMPEG_THREADS'),
                                    )
                                ); */

                                $ffmpeg   = FFMpeg::create();
                                $video        = $ffmpeg->open($image);

                                /* $video
                                    ->filters()
                                    ->resize(new \FFMpeg\Coordinate\Dimension(200, 200))
                                    ->synchronize(); */
                                $video
                                    ->frame(\FFMpeg\Coordinate\TimeCode::fromSeconds(2))
                                    ->save($thumbnail_image_path);

                                $unique_name = md5($thumbnail_image . time()) . '.jpg';
                                $thumbFilePath = $shopsPriceFolder . '/' . $unique_name;
                                $isUpload = Storage::disk('s3')->put($thumbFilePath, fopen($thumbnail_image_path, 'r+'), 'public');
                                if ($isUpload) {
                                    $thumbImageUrl = $thumbFilePath;
                                }

                                $is_video = true;
                            }
                            $mainImage = Storage::disk('s3')->putFile($shopsPriceFolder, $image, 'public');
                            $fileName = basename($mainImage);
                            $image_url = $shopsPriceFolder . '/' . $fileName;
                            $imagesUpload[] = $image_url;

                            if (empty($thumbImageUrl)) {
                                $is_video = false;
                            }

                            ShopPriceImages::create([
                                'shop_price_id' => $shopItemID,
                                'image' => $image_url,
                                'thumb_url' => $thumbImageUrl,
                                'order' => floor(microtime(true) * 1000),
                            ]);
                        }
                    }
                }

                $shopItem = ShopPrices::with('images')->whereId($shopItemID)->first();

                if ($shopItem->images()->count()) {
                    $item = $shopItem->images()->first();
                    if (\App\Http\Controllers\Controller::get_image_mime_type($item->image_url)) {
                        $display_file = $item->thumb_image;
                    } else {
                        $display_file = $item->image_url;
                    }
                } else {
                    $display_file = '';
                }
                $responseData['display_file'] = $display_file;
                DB::commit();
                return response()->json($responseData, 200);
            } else {
                return response()->json(array(
                    'response' => false,
                    'message' => Lang::get('messages.shop-price-category.empty')
                ));
            }
        } catch (\Exception $e) {
            Log::info($e);
            return response()->json(array(
                'response' => false,
                'message' => trans("messages.save-error")
            ), 400);
        }
    }

    public function deleteShopPrice($id, $type)
    {
        $title = 'Shop Price';
        if ($type == "shop_category") {
            $title = 'Shop Category';
        }
        return view('admin.business-client.delete', compact('id', 'type', 'title'));
    }

    public function destroyShopPrice($id, $type)
    {
        try {

            if (!empty($id) && !empty($type)) {

                if ($type == 'shop_price') {
                    ShopPrices::where('id', $id)->delete();
                    return response()->json(array(
                        'response' => true,
                        'message' => "Shop price deleted successfully."

                    ));
                }

                if ($type == 'shop_category') {

                    $shopItemCategory = ShopPriceCategory::find($id);
                    if ($shopItemCategory) {
                        $shopItems = ShopPrices::where('shop_price_category_id', $id)->delete();
                        $shopItemDelete = ShopPriceCategory::where('id', $id)->delete();
                    }
                    return response()->json(array(
                        'response' => true,
                        'message' => "Shop price category deleted successfully."

                    ));
                }
            } else {
                return response()->json(array(
                    'response' => false,
                    'message' => "All parameters are required."

                ));
            }
        } catch (\Exception $e) {
            return response()->json(array(
                'response' => false,
                'message' => trans("messages.save-error")

            ), 400);
        }
    }

    public function deleteShop(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            UserEntityRelation::where('entity_type_id', EntityTypes::SHOP)
                ->where('entity_id', $id)
                ->delete();

            $shoppost = DB::table('shop_posts')->where('shop_id', $id)->get();

            foreach ($shoppost as $posts) {
                Storage::disk('s3')->delete($posts->post_item);
                if ($shoppost->type == 'video') {
                    Storage::disk('s3')->delete($posts->video_thumbnail);
                }

                if ($posts->is_multiple == true) {
                    $images = DB::table('multiple_shop_posts')->where('shop_posts_id', $posts->id)->get();
                    foreach ($images as $data) {
                        Storage::disk('s3')->delete($data->post_item);
                        if ($data->type == 'video') {
                            Storage::disk('s3')->delete($data->video_thumbnail);
                        }
                    }
                    MultipleShopPost::where('shop_posts_id', $posts->id)->delete();
                }
            }
            ShopPost::where('shop_id', $id)->delete();
            ShopDetail::where('shop_id', $id)->delete();
            Shop::where('id', $id)->delete();


            DB::commit();
            $jsonData = array(
                'success' => true,
                'message' => 'Shop deleted successfully',
            );
            return response()->json($jsonData);
        } catch (\Exception $e) {
            DB::rollback();
            $jsonData = array(
                'success' => false,
                'message' => 'Error in Shop delete',
            );
            return response()->json($jsonData);
        }
    }


    function isOutsideShop($user_id)
    {
        $shopsData = DB::table('shops')->whereNull('deleted_at')->where('user_id', $user_id)->get();
        $isOutsideBusiness = false;
        if ($shopsData) {
            foreach ($shopsData as $key => $value) {
                if ($value->business_link != '') {
                    $isOutsideBusiness = true;
                }
            }
        }
        if ($isOutsideBusiness == true) {
            return '<i class="fas fa-star" style="font-size: 25px; color: #fff700; -webkit-text-stroke-width: 1px; -webkit-text-stroke-color: #43425d;"></i>';
        } else {
            return '';
        }
    }

    function isOutsideHospital($user_id)
    {
        $hospitalData = DB::table('hospitals')->join('user_entity_relation', 'user_entity_relation.entity_id', 'hospitals.id')
            ->where('user_entity_relation.user_id', $user_id)
            ->where('user_entity_relation.entity_type_id', EntityTypes::HOSPITAL)
            ->select('hospitals.*')
            ->first();
        if ($hospitalData && $hospitalData->business_link != '') {
            return '<i class="fas fa-star" style="font-size: 25px; color: #fff700; -webkit-text-stroke-width: 1px; -webkit-text-stroke-color: #43425d;"></i>';
        } else {
            return '';
        }
    }

    public function giveAllCredits(Request $request)
    {
        $inputs = $request->all();
        $type = $inputs['type'];

        return view('admin.business-client.give-credits', compact('type'));
    }

    public function updateAllUserCredits(Request $request)
    {
        $inputs = $request->all();

        $give_type = $inputs['give_type'];
        $credits = $inputs['credits'];

        if ($give_type == 'outside') {
            // Shop
            $shopUser = User::join('users_detail', 'users_detail.user_id', 'users.id')
                ->join('shops', function ($query) {
                    $query->on('users.id', '=', 'shops.user_id');
                })
                ->whereNotNull('shops.business_link')
                ->where('shops.business_link', '!=', '')
                ->whereIn('users.status_id', [Status::ACTIVE, Status::INACTIVE])
                ->groupBy('users.id')
                ->select('users.id')
                ->get();

            foreach ($shopUser as $user) {
                $this->giveCoin($user->id, $credits);
            }

            // Hospital
            $query = User::join('user_entity_relation', 'user_entity_relation.user_id', 'users.id')
                ->join('hospitals', 'user_entity_relation.entity_id', 'hospitals.id')
                ->whereIN('user_entity_relation.entity_type_id', [EntityTypes::HOSPITAL])
                ->whereIn('users.status_id', [Status::ACTIVE, Status::INACTIVE])
                ->whereNotNull('hospitals.business_link')
                ->where('hospitals.business_link', '!=', '')
                ->groupBy('users.id')
                ->select('users.id')
                ->get();

            foreach ($query as $user) {
                $this->giveCoin($user->id, $credits);
            }
        } elseif ($give_type == 'all') {

            $query = User::join('user_entity_relation', 'user_entity_relation.user_id', 'users.id')
                ->whereIN('user_entity_relation.entity_type_id', [EntityTypes::HOSPITAL, EntityTypes::SHOP])
                ->whereIn('users.status_id', [Status::ACTIVE, Status::INACTIVE])
                ->groupBy('users.id')
                ->select('users.id')
                ->get();

            foreach ($query as $user) {
                $this->giveCoin($user->id, $credits);
            }
        } elseif ($give_type == 'hospital') {

            $query = User::join('user_entity_relation', 'user_entity_relation.user_id', 'users.id')
                ->join('hospitals', 'user_entity_relation.entity_id', 'hospitals.id')
                ->whereIN('user_entity_relation.entity_type_id', [EntityTypes::HOSPITAL])
                ->whereIn('users.status_id', [Status::ACTIVE, Status::INACTIVE])
                ->groupBy('users.id')
                ->select('users.id')
                ->get();

            foreach ($query as $user) {
                $this->giveCoin($user->id, $credits);
            }
        } elseif ($give_type == 'shop') {

            $query = User::join('user_entity_relation', 'user_entity_relation.user_id', 'users.id')
                ->whereIN('user_entity_relation.entity_type_id', [EntityTypes::SHOP])
                ->whereIn('users.status_id', [Status::ACTIVE, Status::INACTIVE])
                ->groupBy('users.id')
                ->select('users.id')
                ->get();

            foreach ($query as $user) {
                $this->giveCoin($user->id, $credits);
            }
        }

        return $this->sendSuccessResponse('Credit updated successfully.', 200);
    }

    public function giveCoin($userID, $credits)
    {
        $userCredits = UserCredit::where('user_id', $userID)->first();

        $old_credit = $userCredits->credits;
        $new_credit = $credits;
        $total_credit = $old_credit + $new_credit;
        $userCredits = UserCredit::where('user_id', $userID)->update(['credits' => $total_credit]);
        UserCreditHistory::create([
            'user_id' => $userID,
            'amount' => $credits,
            'transaction' => 'credit',
            'total_amount' => $total_credit,
            'type' => UserCreditHistory::DEFAULT
        ]);
    }

    public function getTextDescription(Request $request)
    {
        $inputs = $request->all();
        $remove_text =  $inputs['remove_text'] ?? '';
        $returnHtml = '';
        $matchedPosts = ShopPost::whereNull('deleted_at')->where('description', 'LIKE', "%$remove_text%")->get();
        $matchedPosts = $matchedPosts->makeHidden(['hash_tags', 'location', 'multiple_shop_posts', 'workplace_images']);

        if (!empty($matchedPosts->toArray()) && !empty($remove_text)) {
            foreach ($matchedPosts as $post) {
                $checkBox = "<input class='form-control' style='height: 25px;' type='checkbox' name='remove_id[]' value='$post->id' />";
                $returnHtml .= "<tr>";
                $returnHtml .= "<td>$checkBox</td>";
                $returnHtml .= "<td>{$post->shop_data->main_name}</td>";
                $returnHtml .= "<td>$post->description</td>";
                $returnHtml .= "</tr>";
            }
        } elseif (empty($remove_text)) {
            $returnHtml .= "<tr>";
            $returnHtml .= "<td colspan='3'>Please type any keyword</td>";
            $returnHtml .= "</tr>";
        } else {
            $returnHtml .= "<tr>";
            $returnHtml .= "<td colspan='3'>No Post Found</td>";
            $returnHtml .= "</tr>";
        }
        $jsonData = array(
            'success' => true,
            'message' => 'Successfully removed text from the posts',
            'html' => $returnHtml
        );
        return response()->json($jsonData);
    }
    public function removeTextDescription(Request $request)
    {
        $inputs = $request->all();
        $remove_text =  $inputs['remove_text'] ?? '';
        $ids =  $inputs['ids'] ?? [];
        $matchedPosts = '';
        $isUpdate = false;
        $jsonData = [];
        try {
            if (!empty($remove_text)) {
                //$remove_text = " $remove_text ";
                $matchedPosts = DB::table('shop_posts')->whereNull('deleted_at')->whereIn('id', $ids)->get();

                if (!empty($matchedPosts->toArray())) {
                    foreach ($matchedPosts as $post) {
                        $postDescription = $post->description;
                        $updatedDescription = str_replace($remove_text, ' ', $postDescription);
                        //echo $updatedDescription = preg_replace("/\b".$remove_text."\b/", '', $postDescription);
                        //echo $updatedDescription = preg_replace("/".$remove_text."\b/i", '', $postDescription);

                        $isUpdate = DB::table('shop_posts')->where('id', $post->id)->update(['description' => $updatedDescription]);
                    }
                    $jsonData = array(
                        'success' => true,
                        'message' => 'Successfully removed text from the posts',
                        'matchedPosts' => $matchedPosts
                    );
                } else {
                    $jsonData = array(
                        'success' => false,
                        'message' => 'Not found any text from posts',
                        'matchedPosts' => $matchedPosts
                    );
                }
                return response()->json($jsonData);
            }
        } catch (\Exception $e) {
            $jsonData = array(
                'success' => false,
                'message' => 'Not found any text from posts',
                'matchedPosts' => $matchedPosts
            );
        }
    }

    public function viewReferralDetail(Request $request, $id)
    {
        $title = 'View Referral Users';
        $users = UserDetail::join('users', 'users.id', 'users_detail.user_id')->where('users_detail.recommended_by', $id)->get();
        return view('admin.business-client.referral-index', compact('title', 'users'));
    }
    public function getReferralDetail($user_id)
    {
        $referralCount = UserDetail::where('recommended_by', $user_id)->count();
        if ($referralCount < 1) return '-';

        $link = route('admin.business-client.referral.detail', $user_id);
        return "<a role='button' href='$link'  title='' data-original-title='View' class='btn btn-primary btn-sm' data-toggle='tooltip'>$referralCount <i class='fas fa-eye'></i></a>";
    }

    public function getViewProfileURL($value)
    {
        $shopViewLink = '';
        if ($value['total_shop_count'] == 1 && !empty($value['shop_uuid'])) {
            $ViewLink = route('shop.view', $value['shop_uuid']);
            //$shopViewLink = "<a role='button' target='_blank' href='$ViewLink' class='btn btn-primary btn-sm mr-1'><i class='fas fa-eye'></i></a>";
            $shopViewLink = '<a href="javascript:void(0);" onClick="copyTextLink(`' . $ViewLink . '`)" class="btn-sm mx-1 btn btn-primary"><i class="fas fa-copy"></i></a>';
        } elseif ($value['total_shop_count'] > 1) {
            $shopViewLink = '<a href="javascript:void(0);" onClick="openCopyLinkPopup(' . $value['id'] . ');" class="btn-sm mx-1 btn btn-primary"><i class="fas fa-copy"></i></a>';
        }
        return $shopViewLink;
    }

    public function viewShopProfileLink($id)
    {

        $shops = Shop::join('category', 'category.id', 'shops.category_id')
            ->whereIn('category.category_type_id', [CategoryTypes::SHOP, CategoryTypes::CUSTOM])
            ->where('shops.user_id', $id)
            ->groupby('shops.id')
            ->select('shops.*', 'category.name as category, category.category_type_id')->get();
        return view('admin.business-client.check-shop-profile-link', compact('shops'));
    }

    public function viewFrontShop($uuid)
    {
        $title = 'Shop';
        $shopData = Shop::where('uuid', $uuid)->first();
        /*  print_r($shopData->toArray());
        exit; */
        // $shopData = $shopData->toArray();
        return view("shop.shop-view", compact('shopData', 'title', 'uuid'));
    }

    public function updateUuid()
    {
        $update = Shop::whereNull('deleted_at')->where('uuid', '=', '')->get();
        foreach ($update as $item) {
            $item->update(['uuid' => (string) Str::uuid()]);
        }
    }

    public function get_shop_price(Request $request)
    {
        $shop_price_images = ShopPriceImages::where('shop_price_id', $request->shopPriceId)->orderBy('order', 'DESC')->get();

        return response()->json(['status' => 1, 'data' => $shop_price_images]);
    }

    public function sendNotification050(Request $request){
        DB::beginTransaction();
        try {
            // Send push notification to user
            $devices = UserDevices::where('user_id', $request->userId)->pluck('device_token')->toArray();
            $language_id = UserDetail::where('user_id', $request->userId)->pluck('language_id')->first();
            if ($language_id == PostLanguage::ENGLISH) {
                $format = 'You have been provided with a new 050 number. "' . $request->phone_number . '"';
            } else if ($language_id == PostLanguage::KOREAN) {
                $format = '새로운 050 번호를 제공받았습니다. "' . $request->phone_number . '"';
            } else if ($language_id == PostLanguage::JAPANESE) {
                $format = '新しい050番号が提供されました。 「' . $request->phone_number . '」';
            }

            $title_msg = '';
            $notify_type = Notice::NUMBER050;

            if (count($devices) > 0) {
                $result = $this->sentPushNotification($devices, $title_msg, $format, [], $notify_type);
            }

            DB::commit();
            return response()->json(array('response' => true, 'message' => "Notification sent successfully."));
        } catch (\Exception $e) {
            Log::info($e);
            DB::rollBack();
            return response()->json(array('response' => false, 'message' => "Something went wrong!!"));
        }
    }

    public function getShopsInCircle(Request $request)
    {
        $latitude = $request->input('latitude');
        $longitude = $request->input('longitude');
        $distance = $request->input('distance');

        // Perform the query to fetch the shops within the circle
        $shops = Shop::join('addresses', function ($join) {
            $join->on('shops.id', '=', 'addresses.entity_id')
                ->where('addresses.entity_type_id', EntityTypes::SHOP);
            })
            ->select('shops.*','addresses.latitude','addresses.longitude')
            ->selectRaw(
                "(6371 * acos(cos(radians($latitude)) * cos(radians(addresses.latitude)) * cos(radians(addresses.longitude) - radians($longitude)) + sin(radians($latitude)) * sin(radians(addresses.latitude)))) AS distance"
            )
            ->having('distance', '<=', $distance)
            ->get();

        return response()->json($shops);
    }

    public function saveShop(Request $request)
    {
        DB::beginTransaction();
        $inputs = $request->all();
        $message = '';

        try {
            $category = Category::where('category_type_id', CategoryTypes::SHOP)->first();
            $business_category_id = $category->id;
            $user = User::find($inputs['user_id']);

            $shop = Shop::create([
                'email' => $user->email ?? NULL,
                'mobile' => $user->mobile,
                'shop_name' => NULL,
                'best_portfolio' => NULL,
                'business_licence' => NULL,
                'identification_card' => NULL,
                'business_license_number' => '',
                'status_id' => Status::PENDING,
                'category_id' => $business_category_id,
                'user_id' => $user->id,
                'manager_id' => '',
                'uuid' => (string) Str::uuid(),
                'credit_deduct_date' => Carbon::now()->toDateString()
            ]);
            $entity_id = $shop->id;
            $config = Config::where('key', Config::BECAME_SHOP)->first();
            $userLangDetail = UserDetail::where('user_id',$user->id)->first();
            syncGlobalPriceSettings($entity_id,$userLangDetail->language_id ?? 4);

            UserDetail::where('user_id',$user->id)->update([
                'package_plan_id' => PackagePlan::BRONZE,
                'plan_expire_date' => Carbon::now()->addDays(30),
                'last_plan_update' => Carbon::now()
            ]);

            UserEntityRelation::create([
                'user_id' => $user->id,
                'entity_type_id' => EntityTypes::SHOP,
                'entity_id' => $entity_id,
            ]);

            $defaultCredit = $config ? (int) filter_var($config->value, FILTER_SANITIZE_NUMBER_INT) : 150000;
            $credit = UserCredit::updateOrCreate([
                'user_id' => $user->id,
                'credits' => DB::raw("credits + $defaultCredit")
            ]);

            DB::commit();
            $jsonData = array(
                'success' => true,
                'message' => "Shop profile added successfully.",
            );
        }
        catch (\Exception $e) {
            DB::rollBack();
            $jsonData = array(
                'success' => false,
                'message' => 'Failed to add shop profile'
            );
        }
        return response()->json($jsonData);
    }

    public function generateTextFile(Request $request){
        try {
            $inputs = $request->all();
            $shopIds = $inputs['shop_ids'];
//    $uniqueShopIds = array_unique($shopIds, SORT_REGULAR);
            $filename = date('Y_m_d') . '_' . time() . '.txt';
            $path = public_path() . "/text_files/" . $filename;

            $content = "";
            $i = 1;
            foreach ($shopIds as $shopId) {
                $shop = \App\Models\Shop::where('id', $shopId)->select(['main_name', 'shop_name', 'user_id', 'chat_option', 'business_link', 'booking_link'])->first();
                $instaData = LinkedSocialProfile::where('user_id', $shop->user_id)->where('shop_id', $shopId)->where('social_type', LinkedSocialProfile::Instagram)->first();
                $insta_url = "";
                if (!empty($instaData->social_name) && $instaData->social_name != null) {
                    $insta_url = 'https://www.instagram.com/' . $instaData->social_name;
                }

                $content .= "\n".$i.". ".$shop->shop_name."에 있는 $shop->main_name\n\n인스타그램 주소\nInstagram link of above profile: $insta_url";
                if ($shop->chat_option == 1){
                    $content .= "\n\n오픈 채팅\nlink if show: $shop->business_link";
                }
                if ($shop->booking_link != ''){
                    $content .= "\n\n네이버 예약\nlink if show: $shop->booking_link";
                }
                $content .= "\n\n\n";
                $i++;
            }
            $content .= "더 많은 장소와 포트폴리오는 “미어라운드”앱에서 확인하실 수 있습니다.\nApp Download 👇\nhttps://app.mearoundapp.com/me-talk/deeplink";

            // Create the file and write content
            file_put_contents($path, $content);

            $jsonData = array(
                'success' => true,
                'url' => asset('text_files/' . $filename),
            );
            return response()->json($jsonData);
        }
        catch (\Exception $e){
            $jsonData = array(
                'success' => false,
                'url' => '',
            );
            return response()->json($jsonData);
        }
    }

    public function editDisplayVideo(Request $request)
    {
        DB::beginTransaction();
        $inputs = $request->all();
        try {
            if ($inputs['post_in']=="shop_posts"){
                ShopPost::where('id',$inputs['post_id'])->update([
                   'display_video' => $inputs['display_video']
                ]);
            }
            elseif ($inputs['post_in']=="multiple_shop_posts"){
                MultipleShopPost::where('id',$inputs['post_id'])->update([
                    'display_video' => $inputs['display_video']
                ]);
            }

            DB::commit();
            $jsonData = [
                'success' => true,
                'message' => "Display video updated successfully.",
            ];
            return response()->json($jsonData);
        } catch (\Exception $ex) {
            DB::rollBack();
            $jsonData = [
                'success' => false,
                'message' => "Failed to update display video!!",
            ];
            return response()->json($jsonData);
        }
    }

    public function saveInfo(Request $request){
        try{
            $inputs = $request->all();
            ShopInfo::updateOrCreate([
                'shop_id' => $inputs['shop_id'],
            ],[
                'title_1' => $inputs['title_1'],
                'title_2' => $inputs['title_2'],
                'title_3' => $inputs['title_3'],
                'title_4' => $inputs['title_4'],
                'title_5' => $inputs['title_5'],
                'title_6' => $inputs['title_6'],
            ]);

            return response()->json(array('success' => true));
        }catch(\Exception $e){
            Log::info("$e");
            return response()->json(array('success' => false));
        }
    }

}
