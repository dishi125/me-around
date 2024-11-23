<?php

namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use App\Models\Status;
use App\Models\User;
use App\Models\EntityTypes;
use App\Models\UserEntityRelation;
use App\Models\Hospital;
use App\Models\Shop;
use App\Models\UserCredit;
use App\Models\UserCreditHistory;
use App\Models\UserDetail;
use App\Models\CategoryTypes;
use App\Models\Reviews;
use App\Models\Manager;
use App\Models\Config;
use App\Models\ReloadCoinRequest;
use App\Models\ManagerActivityLogs;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Log;
use Carbon\Carbon;

class MyBusinessClientController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:my-business-client-list', ['only' => ['index','indexShop']]);
    }

    /* ================ Hospital Code Start ======================= */
    public function index(Request $request)
    {
        $user = Auth::user();
        $manager = Manager::where('user_id',$user->id)->first();
        $manager_id = $manager ? $manager->id : 0;
        $title = 'All Client';
        $totalUsers = UserEntityRelation::join('users','users.id','user_entity_relation.user_id')->whereNotNull('users.email')->distinct('user_id')->count('user_id');
        $totalShopsQuery = UserEntityRelation::join('users_detail','users_detail.user_id','user_entity_relation.user_id');
        $totalHospitalsQuery = UserEntityRelation::join('users_detail','users_detail.user_id','user_entity_relation.user_id');
        $lastMonthIncomeQuery = ReloadCoinRequest::join('users_detail','users_detail.user_id','reload_coins_request.user_id');
        $totalIncomeQuery = ReloadCoinRequest::join('users_detail','users_detail.user_id','reload_coins_request.user_id');
        $currentMonthIncomeQuery = ReloadCoinRequest::join('users_detail','users_detail.user_id','reload_coins_request.user_id');

        if($manager_id && $manager_id != 0) {
            $totalShopsQuery = $totalShopsQuery->where('users_detail.manager_id',$manager_id);
            $totalHospitalsQuery = $totalHospitalsQuery->where('users_detail.manager_id',$manager_id);
            $lastMonthIncomeQuery = $lastMonthIncomeQuery->where('users_detail.manager_id',$manager_id);
            $totalIncomeQuery = $totalIncomeQuery->where('users_detail.manager_id',$manager_id);
            $currentMonthIncomeQuery = $currentMonthIncomeQuery->where('users_detail.manager_id',$manager_id);
        }
        
        $totalShops = $totalShopsQuery->where('entity_type_id',EntityTypes::SHOP)->distinct('user_entity_relation.user_id')->count('user_entity_relation.user_id');
        $totalHospitals = $totalHospitalsQuery->where('entity_type_id',EntityTypes::HOSPITAL)->distinct('user_entity_relation.user_id')->count('user_entity_relation.user_id');
        $totalClients = UserEntityRelation::whereIn('entity_type_id',[EntityTypes::HOSPITAL,EntityTypes::SHOP])->distinct('user_id')->count('user_id');
        $dateS = Carbon::now()->startOfMonth()->subMonth(1);
        $dateE = Carbon::now()->startOfMonth();
        $lastMonthIncome = $lastMonthIncomeQuery->where('status',ReloadCoinRequest::GIVE_COIN)->whereBetween('reload_coins_request.created_at',[$dateS,$dateE])
                                ->sum('reload_coins_request.coin_amount');
        $lastMonthIncome = number_format($lastMonthIncome,0);
        $totalIncome = $totalIncomeQuery->where('status',ReloadCoinRequest::GIVE_COIN)
                                ->sum('reload_coins_request.coin_amount');
        $totalIncome = number_format($totalIncome,0);
        $currentMonthIncome = $currentMonthIncomeQuery->where('status',ReloadCoinRequest::GIVE_COIN)->whereMonth('reload_coins_request.created_at',$dateE->month)
                                ->sum('reload_coins_request.coin_amount');
        $currentMonthIncome = number_format($currentMonthIncome,0);
        return view('admin.my-business-client.index', compact('manager_id','totalIncome','title','totalUsers','totalShops','totalHospitals','totalClients','lastMonthIncome','currentMonthIncome'));
    }

    public function getJsonAllData(Request $request)
    {
        try {   
            $user = Auth::user();
            $manager = Manager::where('user_id',$user->id)->first();
            $manager_id = $manager ? $manager->id : 0;
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
                9 => 'users.status_id',
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

            $query = User::join('user_entity_relation','user_entity_relation.user_id','users.id')
                ->join('users_detail','users_detail.user_id','users.id')
                ->join('user_credits','user_credits.user_id','users.id')
                ->join('countries','users_detail.country_id','countries.id')
                ->leftjoin('shops', function($query) {
                    $query->on('users.id','=','shops.user_id')
                    ->whereRaw('shops.id IN (select MAX(a2.id) from shops as a2 group by a2.user_id)');
                })
                ->leftjoin('hospitals','user_entity_relation.entity_id','hospitals.id')
                ->leftjoin('reviews', function ($join) {
                    $join->on('hospitals.id', '=', 'reviews.entity_id')
                         ->where('reviews.entity_type_id',  EntityTypes::HOSPITAL);
                })
                ->leftjoin('addresses', function ($join) {
                    $join->on('user_entity_relation.entity_id', '=', 'addresses.entity_id')
                         ->whereIn('addresses.entity_type_id',  [EntityTypes::HOSPITAL, EntityTypes::SHOP]);
                })
                ->leftjoin('managers','managers.id','users_detail.manager_id')
                ->whereIN('user_entity_relation.entity_type_id', [EntityTypes::HOSPITAL, EntityTypes::SHOP])
                ->whereIn('users.status_id', [Status::ACTIVE, Status::INACTIVE]);
            if($manager_id && $manager_id != 0){
                $query = $query->where('managers.id',$manager_id);
            }
            $query = $query->select(
                    'addresses.*',
                    'users.id',
                    'user_entity_relation.entity_type_id',
                    'users.status_id',
                    'users.created_at',
                    'users_detail.name as user_name',
                    'users_detail.mobile',
                    'user_credits.credits',
                    \DB::raw('(CASE 
                    WHEN user_entity_relation.entity_type_id = 1 THEN  shops.business_license_number
                    WHEN user_entity_relation.entity_type_id = 2 THEN hospitals.business_license_number 
                    ELSE "" 
                    END) AS business_license_number'),
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
                    'managers.name as manager_name'
                ) ->groupBy('users.id');
            $totalData = count($query->get());
            $totalFiltered = $totalData;

            if (!empty($search)) {
                $query = $query->where(function($q) use ($search){
                    $q->where('users_detail.name', 'LIKE', "%{$search}%")
                    ->orWhere('countries.name', 'LIKE', "%{$search}%")
                    ->orWhere('users_detail.mobile', 'LIKE', "%{$search}%")
                    ->orWhere('users.created_at', 'LIKE', "%{$search}%")
                    // ->orWhere('user_credits.credits', 'LIKE', "%{$search}%")
                    ->orWhere('managers.name', 'LIKE', "%{$search}%");
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
                    $id = $value['id'];
                    if($value['entity_type_id'] == EntityTypes::HOSPITAL) {
                        $view = route('admin.my-business-client.hospital.show', $id);
                        $viewButton = "<a role='button' href='javascript:void(0)' onclick='viewProfile(" . $id . ")'  title='' data-original-title='View' class='btn btn-primary btn-sm' data-toggle='tooltip'><i class='fas fa-eye'></i></a>";
                        $nestedData['avg_rating'] = $value['avg_rating'];
                        $business_name = $value['main_name'];
                    }else {
                        $view = route('admin.my-business-client.shop.show', $id);
                        $viewButton = "<a role='button' href='javascript:void(0)' onclick='viewShopProfile(" . $id . ")'  title='' data-original-title='View' class='btn btn-primary btn-sm' data-toggle='tooltip'><i class='fas fa-eye'></i></a>";
                        $reviews = Reviews::join('shops','shops.id', 'reviews.entity_id')
                        ->where('reviews.entity_type_id',EntityTypes::SHOP)
                        ->where('shops.user_id',$value['id'])
                        ->select('reviews.*',DB::raw('round(AVG(reviews.rating),1) as avg_rating'))->get();
                        $nestedData['avg_rating'] = $reviews[0]->avg_rating;
                        $business_name = $value['main_name'] != "" ? $value['main_name']."/" : "";
                        $business_name .= $value['sub_name'];
                    }
                    $nestedData['checkbox'] = "<td><div class=\"custom-checkbox custom-control\">
                    <input type=\"checkbox\" data-checkboxes=\"mygroup\" class=\"custom-control-input checkbox_id check-all-hospital\" id=\"$id\" data-id=\"$id\" value=\"$id\" name=\"checkbox_id[]\"><label for=\"$id\" class=\"custom-control-label\">&nbsp;</label></div></td>";
                    $address = $value['address'];
                    $address .= $value['address2'] ? ','.$value['address2'] : '';
                    $address .= $value['city_name'] ? ','.$value['city_name'] : '';
                    $address .= $value['state_name'] ? ','.$value['state_name'] : '';
                    $address .= $value['country_name'] ? ','.$value['country_name'] : '';
                    // $nestedData['name'] = $value['user_name'];
                                         
                    $nestedData['name'] = $business_name;
                    $nestedData['mobile'] = $value['mobile'];
                    $nestedData['address'] = $address;
                    $purchasedCredits = UserCreditHistory::where('user_id',$id)->where('type',UserCreditHistory::RELOAD)->sum('amount');
                    $nestedData['credits'] = number_format($purchasedCredits,0);
                    $nestedData['business_license_number'] = $value['business_license_number'];
                    $nestedData['join_by'] = $value['manager_name'];
                    // $nestedData['date'] = $value['created_at'];
                    $nestedData['date'] = $this->formatDateTimeCountryWise($value['business_created_date'],$adminTimezone, 'd-m-Y H:i');
                    
                    if ($value['status_id'] == Status::ACTIVE) {
                        $nestedData['status'] = '<span class="badge badge-success">'. Status::find($value['status_id'])->name . '</span>';
                    } else {
                        $nestedData['status'] = '<span class="badge badge-secondary">'. Status::find($value['status_id'])->name . '</span>';
                    }    
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
                "data" => $data,
                "hospitals" => $hospitals
            );
            Log::info('End all hospital list');
            return response()->json($jsonData);
        } catch (\Exception $ex) {
            Log::info('Exception all hospital list');
            Log::info($ex);
            return response()->json([]);
        }
    }
    public function getJsonActiveData(Request $request)
    {
        try { 
            $user = Auth::user();
            $manager = Manager::where('user_id',$user->id)->first();
            $manager_id = $manager ? $manager->id : 0;  
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
                9 => 'users.status_id',
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

            $query = User::join('user_entity_relation','user_entity_relation.user_id','users.id')
                ->join('users_detail','users_detail.user_id','users.id')
                ->join('user_credits','user_credits.user_id','users.id')
                ->join('countries','users_detail.country_id','countries.id')
                ->leftjoin('shops', function($query) {
                    $query->on('users.id','=','shops.user_id')
                    ->whereRaw('shops.id IN (select MAX(a2.id) from shops as a2 group by a2.user_id)');
                })
                ->leftjoin('hospitals','user_entity_relation.entity_id','hospitals.id')
                ->leftjoin('reviews', function ($join) {
                    $join->on('hospitals.id', '=', 'reviews.entity_id')
                         ->where('reviews.entity_type_id',  EntityTypes::HOSPITAL);
                })
                ->leftjoin('addresses', function ($join) {
                    $join->on('user_entity_relation.entity_id', '=', 'addresses.entity_id')
                         ->whereIn('addresses.entity_type_id',  [EntityTypes::HOSPITAL, EntityTypes::SHOP]);
                })
                ->leftjoin('managers','managers.id','users_detail.manager_id')
                ->whereIN('user_entity_relation.entity_type_id', [EntityTypes::HOSPITAL, EntityTypes::SHOP])
                ->whereIn('users.status_id', [Status::ACTIVE]);
            if($manager_id && $manager_id != 0){
                $query = $query->where('managers.id',$manager_id);
            }
            $query = $query->select(
                    'addresses.*',
                    'users.id',
                    'user_entity_relation.entity_type_id',
                    'users.status_id',
                    'users.created_at',
                    'users_detail.name as user_name',
                    'users_detail.mobile',
                    'user_credits.credits',
                    \DB::raw('(CASE 
                    WHEN user_entity_relation.entity_type_id = 1 THEN  shops.business_license_number
                    WHEN user_entity_relation.entity_type_id = 2 THEN hospitals.business_license_number 
                    ELSE "" 
                    END) AS business_license_number'),
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
                    'managers.name as manager_name'
                ) ->groupBy('users.id');
            $totalData = count($query->get());
            $totalFiltered = $totalData;

            if (!empty($search)) {
                $query = $query->where(function($q) use ($search){
                    $q->where('users_detail.name', 'LIKE', "%{$search}%")
                    ->orWhere('countries.name', 'LIKE', "%{$search}%")
                    ->orWhere('users_detail.mobile', 'LIKE', "%{$search}%")
                    ->orWhere('users.created_at', 'LIKE', "%{$search}%")
                    ->orWhere('user_credits.credits', 'LIKE', "%{$search}%")
                    ->orWhere('managers.name', 'LIKE', "%{$search}%");
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
                    $id = $value['id'];
                    if($value['entity_type_id'] == EntityTypes::HOSPITAL) {
                        $view = route('admin.my-business-client.hospital.show', $id);
                        $viewButton = "<a role='button' href='javascript:void(0)' onclick='viewProfile(" . $id . ")'  title='' data-original-title='View' class='btn btn-primary btn-sm' data-toggle='tooltip'><i class='fas fa-eye'></i></a>";
                        $nestedData['avg_rating'] = $value['avg_rating'];
                        $business_name = $value['main_name'];
                    }else {
                        $view = route('admin.my-business-client.shop.show', $id);
                        $viewButton = "<a role='button' href='javascript:void(0)' onclick='viewShopProfile(" . $id . ")'  title='' data-original-title='View' class='btn btn-primary btn-sm' data-toggle='tooltip'><i class='fas fa-eye'></i></a>";
                        $reviews = Reviews::join('shops','shops.id', 'reviews.entity_id')
                        ->where('reviews.entity_type_id',EntityTypes::SHOP)
                        ->where('shops.user_id',$value['id'])
                        ->select('reviews.*',DB::raw('round(AVG(reviews.rating),1) as avg_rating'))->get();
                        $nestedData['avg_rating'] = $reviews[0]->avg_rating;
                        $business_name = $value['main_name'] != "" ? $value['main_name']."/" : "";
                        $business_name .= $value['sub_name'];
                    }
                    $nestedData['checkbox'] = "<td><div class=\"custom-checkbox custom-control\">
                    <input type=\"checkbox\" data-checkboxes=\"mygroup\" class=\"custom-control-input checkbox_id check-all-hospital\" id=\"$id\" data-id=\"$id\" value=\"$id\" name=\"checkbox_id[]\"><label for=\"$id\" class=\"custom-control-label\">&nbsp;</label></div></td>";
                    $address = $value['address'];
                    $address .= $value['address2'] ? ','.$value['address2'] : '';
                    $address .= $value['city_name'] ? ','.$value['city_name'] : '';
                    $address .= $value['state_name'] ? ','.$value['state_name'] : '';
                    $address .= $value['country_name'] ? ','.$value['country_name'] : '';
                    // $nestedData['name'] = $value['user_name'];
                    $nestedData['name'] = $business_name;
                    $nestedData['mobile'] = $value['mobile'];
                    $nestedData['address'] = $address;
                    $purchasedCredits = UserCreditHistory::where('user_id',$id)->where('type',UserCreditHistory::RELOAD)->sum('amount');
                    $nestedData['credits'] = number_format($purchasedCredits,0);
                    $nestedData['business_license_number'] = $value['business_license_number'];
                    $nestedData['join_by'] = $value['manager_name'];
                    // $nestedData['date'] = $value['created_at'];
                    $nestedData['date'] = $this->formatDateTimeCountryWise($value['business_created_date'],$adminTimezone, 'd-m-Y H:i');
                    
                    if ($value['status_id'] == Status::ACTIVE) {
                        $nestedData['status'] = '<span class="badge badge-success">'. Status::find($value['status_id'])->name . '</span>';
                    } else {
                        $nestedData['status'] = '<span class="badge badge-secondary">'. Status::find($value['status_id'])->name . '</span>';
                    }    
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
    public function getJsonInActiveData(Request $request)
    {
        try {   
            $user = Auth::user();
            $manager = Manager::where('user_id',$user->id)->first();
            $manager_id = $manager ? $manager->id : 0;
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
                9 => 'users.status_id',
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

            $query = User::join('user_entity_relation','user_entity_relation.user_id','users.id')
                ->join('users_detail','users_detail.user_id','users.id')
                ->join('user_credits','user_credits.user_id','users.id')
                ->join('countries','users_detail.country_id','countries.id')
                ->leftjoin('shops', function($query) {
                    $query->on('users.id','=','shops.user_id')
                    ->whereRaw('shops.id IN (select MAX(a2.id) from shops as a2 group by a2.user_id)');
                })
                ->leftjoin('hospitals','user_entity_relation.entity_id','hospitals.id')
                ->leftjoin('reviews', function ($join) {
                    $join->on('hospitals.id', '=', 'reviews.entity_id')
                         ->where('reviews.entity_type_id',  EntityTypes::HOSPITAL);
                })
                ->leftjoin('addresses', function ($join) {
                    $join->on('user_entity_relation.entity_id', '=', 'addresses.entity_id')
                         ->whereIn('addresses.entity_type_id',  [EntityTypes::HOSPITAL, EntityTypes::SHOP]);
                })
                ->leftjoin('managers','managers.id','users_detail.manager_id')
                ->whereIN('user_entity_relation.entity_type_id', [EntityTypes::HOSPITAL, EntityTypes::SHOP])
                ->whereIn('users.status_id', [ Status::INACTIVE]);
            if($manager_id && $manager_id != 0){
                $query = $query->where('managers.id',$manager_id);
            }
            $query = $query->select(
                    'addresses.*',
                    'users.id',
                    'user_entity_relation.entity_type_id',
                    'users.status_id',
                    'users.created_at',
                    'users_detail.name as user_name',
                    'users_detail.mobile',
                    'user_credits.credits',
                    \DB::raw('(CASE 
                    WHEN user_entity_relation.entity_type_id = 1 THEN  shops.business_license_number
                    WHEN user_entity_relation.entity_type_id = 2 THEN hospitals.business_license_number 
                    ELSE "" 
                    END) AS business_license_number'),
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
                    'managers.name as manager_name'
                ) ->groupBy('users.id');
            $totalData = count($query->get());
            $totalFiltered = $totalData;

            if (!empty($search)) {
                $query = $query->where(function($q) use ($search){
                    $q->where('users_detail.name', 'LIKE', "%{$search}%")
                    ->orWhere('countries.name', 'LIKE', "%{$search}%")
                    ->orWhere('users_detail.mobile', 'LIKE', "%{$search}%")
                    ->orWhere('users.created_at', 'LIKE', "%{$search}%")
                    ->orWhere('user_credits.credits', 'LIKE', "%{$search}%")
                    ->orWhere('managers.name', 'LIKE', "%{$search}%");
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
                    $id = $value['id'];
                    if($value['entity_type_id'] == EntityTypes::HOSPITAL) {
                        $view = route('admin.my-business-client.hospital.show', $id);
                        $viewButton = "<a role='button' href='javascript:void(0)' onclick='viewProfile(" . $id . ")'  title='' data-original-title='View' class='btn btn-primary btn-sm' data-toggle='tooltip'><i class='fas fa-eye'></i></a>";
                        $nestedData['avg_rating'] = $value['avg_rating'];
                        $business_name = $value['main_name'];
                    }else {
                        $view = route('admin.my-business-client.shop.show', $id);
                        $viewButton = "<a role='button' href='javascript:void(0)' onclick='viewShopProfile(" . $id . ")'  title='' data-original-title='View' class='btn btn-primary btn-sm' data-toggle='tooltip'><i class='fas fa-eye'></i></a>";
                        $reviews = Reviews::join('shops','shops.id', 'reviews.entity_id')
                        ->where('reviews.entity_type_id',EntityTypes::SHOP)
                        ->where('shops.user_id',$value['id'])
                        ->select('reviews.*',DB::raw('round(AVG(reviews.rating),1) as avg_rating'))->get();
                        $nestedData['avg_rating'] = $reviews[0]->avg_rating;
                        $business_name = $value['main_name'] != "" ? $value['main_name']."/" : "";
                        $business_name .= $value['sub_name'];
                    }
                    $nestedData['checkbox'] = "<td><div class=\"custom-checkbox custom-control\">
                    <input type=\"checkbox\" data-checkboxes=\"mygroup\" class=\"custom-control-input checkbox_id check-all-hospital\" id=\"$id\" data-id=\"$id\" value=\"$id\" name=\"checkbox_id[]\"><label for=\"$id\" class=\"custom-control-label\">&nbsp;</label></div></td>";
                    $address = $value['address'];
                    $address .= $value['address2'] ? ','.$value['address2'] : '';
                    $address .= $value['city_name'] ? ','.$value['city_name'] : '';
                    $address .= $value['state_name'] ? ','.$value['state_name'] : '';
                    $address .= $value['country_name'] ? ','.$value['country_name'] : '';
                    // $nestedData['name'] = $value['user_name'];
                    $nestedData['name'] = $business_name;
                    $nestedData['mobile'] = $value['mobile'];
                    $nestedData['address'] = $address;
                    $purchasedCredits = UserCreditHistory::where('user_id',$id)->where('type',UserCreditHistory::RELOAD)->sum('amount');
                    $nestedData['credits'] = number_format($purchasedCredits,0);
                    $nestedData['business_license_number'] = $value['business_license_number'];
                    $nestedData['join_by'] = $value['manager_name'];
                    // $nestedData['date'] = $value['created_at'];
                    $nestedData['date'] = $this->formatDateTimeCountryWise($value['business_created_date'],$adminTimezone, 'd-m-Y H:i');
                    
                    if ($value['status_id'] == Status::ACTIVE) {
                        $nestedData['status'] = '<span class="badge badge-success">'. Status::find($value['status_id'])->name . '</span>';
                    } else {
                        $nestedData['status'] = '<span class="badge badge-secondary">'. Status::find($value['status_id'])->name . '</span>';
                    }    
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

/* ================ Hospital Code Start ======================= */
    public function indexHospital(Request $request)
    {
        $user = Auth::user();
        $manager = Manager::where('user_id',$user->id)->first();
        $manager_id = $manager ? $manager->id : 0;
        $title = 'Hospital Client';
        $totalUsers = UserEntityRelation::distinct('user_id')->count('user_id');;
        $totalShopsQuery = UserEntityRelation::join('users_detail','users_detail.user_id','user_entity_relation.user_id');
        $totalHospitalsQuery = UserEntityRelation::join('users_detail','users_detail.user_id','user_entity_relation.user_id');
        $totalIncomeQuery = ReloadCoinRequest::join('users_detail','users_detail.user_id','reload_coins_request.user_id');
        $lastMonthIncomeQuery = ReloadCoinRequest::join('users_detail','users_detail.user_id','reload_coins_request.user_id');
        
        $currentMonthIncomeQuery = ReloadCoinRequest::join('users_detail','users_detail.user_id','reload_coins_request.user_id');
        
        if($manager_id && $manager_id != 0) {
            $totalShopsQuery = $totalShopsQuery->where('users_detail.manager_id',$manager_id);
            $totalHospitalsQuery = $totalHospitalsQuery->where('users_detail.manager_id',$manager_id);
            $lastMonthIncomeQuery = $lastMonthIncomeQuery->where('users_detail.manager_id',$manager_id);
            $totalIncomeQuery = $lastMonthIncomeQuery->where('users_detail.manager_id',$manager_id);
            $currentMonthIncomeQuery = $currentMonthIncomeQuery->where('users_detail.manager_id',$manager_id);
        }
        
        $totalShops = $totalShopsQuery->where('entity_type_id',EntityTypes::SHOP)->distinct('user_entity_relation.user_id')->count('user_entity_relation.user_id');
        $totalHospitals = $totalHospitalsQuery->where('entity_type_id',EntityTypes::HOSPITAL)->distinct('user_entity_relation.user_id')->count('user_entity_relation.user_id');
        $totalClients = UserEntityRelation::whereIn('entity_type_id',[EntityTypes::HOSPITAL,EntityTypes::SHOP])->distinct('user_id')->count('user_id');
        $dateS = Carbon::now()->startOfMonth()->subMonth(1);
        $dateE = Carbon::now()->startOfMonth();
        $lastMonthIncome = $lastMonthIncomeQuery->where('status',ReloadCoinRequest::GIVE_COIN)->whereBetween('reload_coins_request.created_at',[$dateS,$dateE])
        ->sum('reload_coins_request.coin_amount');
        $lastMonthIncome = number_format($lastMonthIncome,0);
        $totalIncome = $totalIncomeQuery->where('status',ReloadCoinRequest::GIVE_COIN)
        ->sum('reload_coins_request.coin_amount');
        $totalIncome = number_format($totalIncome,0);
        $currentMonthIncome = $currentMonthIncomeQuery->where('status',ReloadCoinRequest::GIVE_COIN)->whereMonth('reload_coins_request.created_at',$dateE->month)
        ->sum('reload_coins_request.coin_amount');
        $currentMonthIncome = number_format($currentMonthIncome,0);
        return view('admin.my-business-client.index-hospital', compact('manager_id','title','totalUsers','totalShops','totalHospitals','totalClients','totalIncome','lastMonthIncome','currentMonthIncome'));
    }

    public function getJsonAllHospitalData(Request $request)
    {
        try {   
            Log::info('Start all hospital list');
            $user = Auth::user();
            $manager = Manager::where('user_id',$user->id)->first();
            $manager_id = $manager ? $manager->id : 0;

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
                9 => 'users.status_id',
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

            $query = User::join('user_entity_relation','user_entity_relation.user_id','users.id')
                ->join('users_detail','users_detail.user_id','users.id')
                ->join('user_credits','user_credits.user_id','users.id')
                ->join('countries','users_detail.country_id','countries.id')
                ->join('hospitals','user_entity_relation.entity_id','hospitals.id')
                ->leftjoin('reviews', function ($join) {
                    $join->on('hospitals.id', '=', 'reviews.entity_id')
                         ->where('reviews.entity_type_id', EntityTypes::HOSPITAL);
                })
                ->join('addresses', function ($join) {
                    $join->on('hospitals.id', '=', 'addresses.entity_id')
                         ->where('addresses.entity_type_id', EntityTypes::HOSPITAL);
                })
                ->leftjoin('managers','managers.id','users_detail.manager_id')
                ->where('user_entity_relation.entity_type_id', EntityTypes::HOSPITAL)
                ->whereIn('users.status_id', [Status::ACTIVE, Status::INACTIVE]);
            if($manager_id && $manager_id != 0){
                $query = $query->where('managers.id',$manager_id);
            }
            $query = $query->select(
                    'addresses.*',
                    'users.id',
                    'users.status_id',
                    'users.created_at',
                    'users_detail.name as user_name',
                    'users_detail.mobile',
                    'user_credits.credits',
                    'hospitals.business_license_number',
                    'hospitals.main_name',
                    'hospitals.created_at as business_created_date',
                    // 'countries.name as country_name',
                    'managers.name as manager_name',
                    DB::raw('round(AVG(reviews.rating),1) as avg_rating')
                );
            $totalData = $query->count();
            $totalFiltered = $totalData;

            if (!empty($search)) {
                $query = $query->where(function($q) use ($search){
                    $q->where('users_detail.name', 'LIKE', "%{$search}%")
                    ->orWhere('countries.name', 'LIKE', "%{$search}%")
                    ->orWhere('users_detail.mobile', 'LIKE', "%{$search}%")
                    ->orWhere('users.created_at', 'LIKE', "%{$search}%")
                    ->orWhere('user_credits.credits', 'LIKE', "%{$search}%")
                    ->orWhere('managers.name', 'LIKE', "%{$search}%");
                });
                
                $totalFiltered = $query->count();
            }

            $hospitals = $query->offset($start)
                ->limit($limit)
                ->orderBy($order, $dir)
                ->groupBy('users.id')
                ->get();
            // dd($hospitals);
            $data = array();
            if (!empty($hospitals)) {
                foreach ($hospitals as $value) {
                    $id = $value['id'];
                    $view = route('admin.my-business-client.hospital.show', $id);
                    $nestedData['checkbox'] = "<td><div class=\"custom-checkbox custom-control\">
                    <input type=\"checkbox\" data-checkboxes=\"mygroup\" class=\"custom-control-input checkbox_id check-all-hospital\" id=\"$id\" data-id=\"$id\" value=\"$id\" name=\"checkbox_id[]\"><label for=\"$id\" class=\"custom-control-label\">&nbsp;</label></div></td>";
                    $address = $value['address'];
                    $address .= $value['address2'] ? ','.$value['address2'] : '';
                    $address .= $value['city_name'] ? ','.$value['city_name'] : '';
                    $address .= $value['state_name'] ? ','.$value['state_name'] : '';
                    $address .= $value['country_name'] ? ','.$value['country_name'] : '';
                    // $nestedData['name'] = $value['user_name'];
                    $nestedData['name'] = $value['main_name'];
                    $nestedData['mobile'] = $value['mobile'];
                    $nestedData['address'] = $address;
                    $purchasedCredits = UserCreditHistory::where('user_id',$id)->where('type',UserCreditHistory::RELOAD)->sum('amount');
                    $nestedData['credits'] = number_format($purchasedCredits,0);
                    $nestedData['business_license_number'] = $value['business_license_number'];
                    $nestedData['avg_rating'] = $value['avg_rating'];
                    $nestedData['join_by'] = $value['manager_name'];
                    // $nestedData['date'] = $value['created_at'];
                    $nestedData['date'] = $this->formatDateTimeCountryWise($value['business_created_date'],$adminTimezone, 'd-m-Y H:i');
                    
                    if ($value['status_id'] == Status::ACTIVE) {
                        $nestedData['status'] = '<span class="badge badge-success">'. Status::find($value['status_id'])->name . '</span>';
                    } else {
                        $nestedData['status'] = '<span class="badge badge-secondary">'. Status::find($value['status_id'])->name . '</span>';
                    }    
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
    public function getJsonActiveHospitalData(Request $request)
    {
        try {   
            Log::info('Start all hospital list');
            $user = Auth::user();
            $manager = Manager::where('user_id',$user->id)->first();
            $manager_id = $manager ? $manager->id : 0;

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
                9 => 'users.status_id',
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

            $query = User::join('user_entity_relation','user_entity_relation.user_id','users.id')
                ->join('users_detail','users_detail.user_id','users.id')
                ->join('user_credits','user_credits.user_id','users.id')
                ->join('hospitals','user_entity_relation.entity_id','hospitals.id')
                ->leftjoin('reviews', function ($join) {
                    $join->on('hospitals.id', '=', 'reviews.entity_id')
                         ->where('reviews.entity_type_id', EntityTypes::HOSPITAL);
                })
                ->join('countries','users_detail.country_id','countries.id')
                ->leftjoin('managers','managers.id','users_detail.manager_id')
                ->join('addresses', function ($join) {
                    $join->on('hospitals.id', '=', 'addresses.entity_id')
                         ->where('addresses.entity_type_id', EntityTypes::HOSPITAL);
                })
                ->where('user_entity_relation.entity_type_id', EntityTypes::HOSPITAL)
                ->where('users.status_id', Status::ACTIVE);

            if($manager_id && $manager_id != 0){
                $query = $query->where('managers.id',$manager_id);
            }
            $query = $query->select(
                    'addresses.*',
                    'users.id',
                    'users.status_id',
                    'users.created_at',
                    'users_detail.name as user_name',
                    'users_detail.mobile',
                    'user_credits.credits',
                    'hospitals.business_license_number',
                    'hospitals.main_name',
                    'hospitals.created_at as business_created_date',
                    'countries.name as country_name',
                    'managers.name as manager_name',
                    DB::raw('round(AVG(reviews.rating),1) as avg_rating')
                );
            $totalData = $query->count();
            $totalFiltered = $totalData;

            if (!empty($search)) {
                $query = $query->where(function($q) use ($search){
                    $q->where('users_detail.name', 'LIKE', "%{$search}%")
                        ->orWhere('countries.name', 'LIKE', "%{$search}%")
                        ->orWhere('users_detail.mobile', 'LIKE', "%{$search}%")
                        ->orWhere('users.created_at', 'LIKE', "%{$search}%")
                        ->orWhere('user_credits.credits', 'LIKE', "%{$search}%")
                        ->orWhere('managers.name', 'LIKE', "%{$search}%");
                });
                $totalFiltered = $query->count();
            }

            $hospitals = $query->offset($start)
                ->limit($limit)
                ->orderBy($order, $dir)
                ->groupBy('users.id')
                ->get();
            $data = array();
            if (!empty($hospitals)) {
                foreach ($hospitals as $value) {
                    $id = $value['id'];
                    $view = route('admin.my-business-client.hospital.show', $id);
                    $nestedData['checkbox'] = "<td><div class=\"custom-checkbox custom-control\">
                    <input type=\"checkbox\" data-checkboxes=\"mygroup\" class=\"custom-control-input checkbox_id check-active-hospital\" id=\"$id\" data-id=\"$id\" value=\"$id\" name=\"checkbox_id[]\"><label for=\"$id\" class=\"custom-control-label\">&nbsp;</label></div></td>";
                    $address = $value['address'];
                    $address .= $value['address2'] ? ','.$value['address2'] : '';
                    $address .= $value['city_name'] ? ','.$value['city_name'] : '';
                    $address .= $value['state_name'] ? ','.$value['state_name'] : '';
                    $address .= $value['country_name'] ? ','.$value['country_name'] : '';
                    // $nestedData['name'] = $value['user_name'];
                    $nestedData['name'] = $value['main_name'];
                    $nestedData['mobile'] = $value['mobile'];
                    $nestedData['address'] = $address;
                    $purchasedCredits = UserCreditHistory::where('user_id',$id)->where('type',UserCreditHistory::RELOAD)->sum('amount');
                    $nestedData['credits'] = number_format($purchasedCredits,0);
                    $nestedData['business_license_number'] = $value['business_license_number'];
                    $nestedData['avg_rating'] = $value['avg_rating'];
                    $nestedData['join_by'] = $value['manager_name'];
                    // $nestedData['date'] = $value['created_at'];
                    $nestedData['date'] = $this->formatDateTimeCountryWise($value['business_created_date'],$adminTimezone, 'd-m-Y H:i');
                    
                    if ($value['status_id'] == Status::ACTIVE) {
                        $nestedData['status'] = '<span class="badge badge-success">'. Status::find($value['status_id'])->name . '</span>';
                    } else {
                        $nestedData['status'] = '<span class="badge badge-secondary">'. Status::find($value['status_id'])->name . '</span>';
                    }    
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
    public function getJsonInactiveHospitalData(Request $request)
    {
        try {   
            Log::info('Start all hospital list');
            $user = Auth::user();
            $manager = Manager::where('user_id',$user->id)->first();
            $manager_id = $manager ? $manager->id : 0;

            $columns = array(
                0 => 'users.id',
                1 => 'users_detail.name',
                2 => 'countries.name',
                3 => 'users_detail.mobile',
                4 => 'user_credits.credits',
                5 => 'managers.name',
                6 => 'users.created_at',
                7 => 'avg_rating',
                8 => 'hospitals.created_at as business_license_number',
                9 => 'users.status_id',
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

            $query = User::join('user_entity_relation','user_entity_relation.user_id','users.id')
                ->join('users_detail','users_detail.user_id','users.id')
                ->join('user_credits','user_credits.user_id','users.id')
                ->join('countries','users_detail.country_id','countries.id')
                ->join('hospitals','user_entity_relation.entity_id','hospitals.id')
                ->leftjoin('reviews', function ($join) {
                    $join->on('hospitals.id', '=', 'reviews.entity_id')
                         ->where('reviews.entity_type_id', EntityTypes::HOSPITAL);
                })
                ->join('managers','managers.user_id','users.id')
                ->join('addresses', function ($join) {
                    $join->on('hospitals.id', '=', 'addresses.entity_id')
                         ->where('addresses.entity_type_id', EntityTypes::HOSPITAL);
                })
                ->where('user_entity_relation.entity_type_id', EntityTypes::HOSPITAL)
                ->where('users.status_id', Status::INACTIVE);
            if($manager_id && $manager_id != 0){
                $query = $query->where('managers.id',$manager_id);
            }
            $query = $query->select(
                    'addresses.*',
                    'users.id',
                    'users.status_id',
                    'users.created_at',
                    'users_detail.name as user_name',
                    'users_detail.mobile',
                    'user_credits.credits',
                    'hospitals.business_license_number',
                    'hospitals.main_name',
                    'hospitals.business_created_date',
                    'countries.name as country_name',
                    'managers.name as manager_name',
                    DB::raw('round(AVG(reviews.rating),1) as avg_rating')
                );
            $totalData = $query->count();
            $totalFiltered = $totalData;

            if (!empty($search)) {
                $query = $query->where(function($q) use ($search){
                    $q->where('users_detail.name', 'LIKE', "%{$search}%")
                        ->orWhere('countries.name', 'LIKE', "%{$search}%")
                        ->orWhere('users_detail.mobile', 'LIKE', "%{$search}%")
                        ->orWhere('users.created_at', 'LIKE', "%{$search}%")
                        ->orWhere('user_credits.credits', 'LIKE', "%{$search}%")
                        ->orWhere('managers.name', 'LIKE', "%{$search}%");
                });
                $totalFiltered = $query->count();
            }

            $hospitals = $query->offset($start)
                ->limit($limit)
                ->orderBy($order, $dir)
                ->groupBy('users.id')
                ->get();
            $data = array();
            if (!empty($hospitals)) {
                foreach ($hospitals as $value) {
                    $id = $value['id'];
                    $view = route('admin.my-business-client.hospital.show', $id);
                    $nestedData['checkbox'] = "<td><div class=\"custom-checkbox custom-control\">
                    <input type=\"checkbox\" data-checkboxes=\"mygroup\" class=\"custom-control-input checkbox_id check-inactive-hospital\" id=\"$id\" data-id=\"$id\" value=\"$id\" name=\"checkbox_id[]\"><label for=\"$id\" class=\"custom-control-label\">&nbsp;</label></div></td>";
                    $address = $value['address'];
                    $address .= $value['address2'] ? ','.$value['address2'] : '';
                    $address .= $value['city_name'] ? ','.$value['city_name'] : '';
                    $address .= $value['state_name'] ? ','.$value['state_name'] : '';
                    $address .= $value['country_name'] ? ','.$value['country_name'] : '';
                    // $nestedData['name'] = $value['user_name'];
                    $nestedData['name'] = $value['main_name'];
                    $nestedData['mobile'] = $value['mobile'];
                    $nestedData['address'] = $address;
                    $purchasedCredits = UserCreditHistory::where('user_id',$id)->where('type',UserCreditHistory::RELOAD)->sum('amount');
                    $nestedData['credits'] = number_format($purchasedCredits,0);
                    $nestedData['business_license_number'] = $value['business_license_number'];
                    $nestedData['avg_rating'] = $value['avg_rating'];
                    $nestedData['join_by'] = $value['manager_name'];
                    // $nestedData['date'] = $value['created_at'];
                    $nestedData['date'] = $this->formatDateTimeCountryWise($value['business_created_date'],$adminTimezone, 'd-m-Y H:i');
                    
                    if ($value['status_id'] == Status::ACTIVE) {
                        $nestedData['status'] = '<span class="badge badge-success">'. $value['status_id'] . '</span>';
                    } else {
                        $nestedData['status'] = '<span class="badge badge-secondary">'. $value['status_id'] . '</span>';
                    }    
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

    public function show($id)
    {
        $title = 'Hospital Client Detail';
        $hospital = Hospital::find($id);
    
        return view('admin.my-business-client.show', compact('title','hospital'));
    }

    public function viewLogs($id) {    
        $dataCredit = UserCreditHistory::where('user_id',$id)->where('transaction','credit')->orderBy('created_at', 'DESC')->get();
        $dataDebit = UserCreditHistory::where('user_id',$id)->where('transaction','debit')->orderBy('created_at', 'DESC')->get();
        return view('admin.my-business-client.credit-log',compact('dataCredit','dataDebit'));
    }
    public function viewHospitalProfile($id) {    
        $hospitals = Hospital::join('user_entity_relation','hospitals.id','user_entity_relation.entity_id')
        ->leftjoin('category','category.id','hospitals.category_id')
        ->leftjoin('reviews', function ($join) {
            $join->on('hospitals.id', '=', 'reviews.entity_id')
                 ->where('reviews.entity_type_id', EntityTypes::HOSPITAL);
        })
        ->where('user_entity_relation.user_id',$id)->where('user_entity_relation.entity_type_id',EntityTypes::HOSPITAL)
        ->groupby('hospitals.id')
        ->select('hospitals.*','category.name as category',DB::raw('round(AVG(reviews.rating),1) as avg_rating'))->get();
        // dd($hospitals);
        return view('admin.my-business-client.check-profile',compact('hospitals'));
    }

/* ================ Hospital Code End ======================= */

/* ================ Shop Code Start ======================= */
    public function indexShop(Request $request)
    {
        $title = "Shop Client";
        $user = Auth::user();
        $manager = Manager::where('user_id',$user->id)->first();
        $manager_id = $manager ? $manager->id : 0;
        
        $totalUsers = UserEntityRelation::distinct('user_id')->count('user_id');;
        $totalShopsQuery = UserEntityRelation::join('users_detail','users_detail.user_id','user_entity_relation.user_id');
        $totalHospitalsQuery = UserEntityRelation::join('users_detail','users_detail.user_id','user_entity_relation.user_id');
        $lastMonthIncomeQuery = ReloadCoinRequest::join('users_detail','users_detail.user_id','reload_coins_request.user_id');
        $totalIncomeQuery = ReloadCoinRequest::join('users_detail','users_detail.user_id','reload_coins_request.user_id');
        $currentMonthIncomeQuery = ReloadCoinRequest::join('users_detail','users_detail.user_id','reload_coins_request.user_id');

        if($manager_id && $manager_id != 0) {
            $totalShopsQuery = $totalShopsQuery->where('users_detail.manager_id',$manager_id);
            $totalHospitalsQuery = $totalHospitalsQuery->where('users_detail.manager_id',$manager_id);
            $lastMonthIncomeQuery = $lastMonthIncomeQuery->where('users_detail.manager_id',$manager_id);
            $totalIncomeQuery = $totalIncomeQuery->where('users_detail.manager_id',$manager_id);
            $currentMonthIncomeQuery = $currentMonthIncomeQuery->where('users_detail.manager_id',$manager_id);
        }
        
        $totalShops = $totalShopsQuery->where('entity_type_id',EntityTypes::SHOP)->distinct('user_entity_relation.user_id')->count('user_entity_relation.user_id');
        $totalHospitals = $totalHospitalsQuery->where('entity_type_id',EntityTypes::HOSPITAL)->distinct('user_entity_relation.user_id')->count('user_entity_relation.user_id');
        $totalClients = UserEntityRelation::whereIn('entity_type_id',[EntityTypes::HOSPITAL,EntityTypes::SHOP])->distinct('user_id')->count('user_id');
        $dateS = Carbon::now()->startOfMonth()->subMonth(1);
        $dateE = Carbon::now()->startOfMonth();
        $lastMonthIncome = $lastMonthIncomeQuery->where('status',ReloadCoinRequest::GIVE_COIN)->whereBetween('reload_coins_request.created_at',[$dateS,$dateE])
                                ->sum('reload_coins_request.coin_amount');
        $lastMonthIncome = number_format($lastMonthIncome,0);
        $totalIncome = $totalIncomeQuery->where('status',ReloadCoinRequest::GIVE_COIN)
                                ->sum('reload_coins_request.coin_amount');
        $totalIncome = number_format($totalIncome,0);
        $currentMonthIncome = $currentMonthIncomeQuery->where('status',ReloadCoinRequest::GIVE_COIN)->whereMonth('reload_coins_request.created_at',$dateE->month)
                                ->sum('reload_coins_request.coin_amount');
        $currentMonthIncome = number_format($currentMonthIncome,0);
        return view('admin.my-business-client.index-shop', compact('manager_id','title','totalIncome', 'totalUsers','totalShops','totalHospitals','totalClients', 'lastMonthIncome', 'currentMonthIncome'));
    }

    public function getJsonAllShopData(Request $request)
    {
        try {   
            Log::info('Start all shop list');
            $user = Auth::user();
            $manager = Manager::where('user_id',$user->id)->first();
            $manager_id = $manager ? $manager->id : 0;

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
                9 => 'users.status_id',
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

            $query = User::join('users_detail','users_detail.user_id','users.id')
                ->join('user_credits','user_credits.user_id','users.id')
                ->join('countries','users_detail.country_id','countries.id')
                ->leftjoin('managers','managers.id','users_detail.manager_id')
                ->join('shops', function($query) {
                    $query->on('users.id','=','shops.user_id')
                    ->whereRaw('shops.id IN (select MAX(a2.id) from shops as a2 group by a2.user_id)');
                })
                ->leftjoin('addresses', function ($join) {
                    $join->on('shops.id', '=', 'addresses.entity_id')                         
                         ->where('addresses.entity_type_id', EntityTypes::SHOP);
                })
                ->whereIn('users.status_id', [Status::ACTIVE, Status::INACTIVE]);

            if($manager_id && $manager_id != 0){
                $query = $query->where('managers.id',$manager_id);
            }
            $query = $query->select(
                    'addresses.*',
                    'shops.id as address_id',
                    'users.id',
                    'users.status_id',
                    'users.created_at',
                    'users_detail.name as user_name',
                    'users_detail.mobile',
                    'user_credits.credits',
                    'shops.business_license_number',
                    'shops.main_name',
                    'shops.shop_name',
                    'shops.created_at as business_created_date',
                    // 'countries.name as country_name',
                    'managers.name as manager_name'
                );
        
            $totalData = $query->count();
            $totalFiltered = $totalData;

            if (!empty($search)) {
                $query = $query->where(function($q) use ($search){
                    $q->where('users_detail.name', 'LIKE', "%{$search}%")
                        ->orWhere('countries.name', 'LIKE', "%{$search}%")
                        ->orWhere('users_detail.mobile', 'LIKE', "%{$search}%")
                        ->orWhere('users.created_at', 'LIKE', "%{$search}%")
                        ->orWhere('user_credits.credits', 'LIKE', "%{$search}%")
                        ->orWhere('managers.name', 'LIKE', "%{$search}%");
                });
                $totalFiltered = $query->count();
            }

            $shops = $query->offset($start)
                ->limit($limit)
                ->orderBy($order, $dir)
                ->groupBy('users.id')
                ->get();

            $data = array();
            if (!empty($shops)) {
                foreach ($shops as $value) {
                    $id = $value['id'];
                    $view = route('admin.my-business-client.shop.show', $id);
                    $nestedData['checkbox'] = "<td><div class=\"custom-checkbox custom-control\">
                    <input type=\"checkbox\" data-checkboxes=\"mygroup\" class=\"custom-control-input checkbox_id check-all-shop\" id=\"$id\" data-id=\"$id\" value=\"$id\" name=\"checkbox_id[]\"><label for=\"$id\" class=\"custom-control-label\">&nbsp;</label></div></td>";
                    $address = $value['address'];
                    $address .= $value['address2'] ? ','.$value['address2'] : '';
                    $address .= $value['city_name'] ? ','.$value['city_name'] : '';
                    $address .= $value['state_name'] ? ','.$value['state_name'] : '';
                    $address .= $value['country_name'] ? ','.$value['country_name'] : '';
                    // $nestedData['name'] = $value['user_name'];
                    $business_name = $value['main_name'] != "" ? $value['main_name']."/" : "";
                    $business_name .= $value['shop_name'];
                    $nestedData['name'] = $business_name;
                    $nestedData['mobile'] = $value['mobile'];
                    $nestedData['address'] = $address;
                    $purchasedCredits = UserCreditHistory::where('user_id',$id)->where('type',UserCreditHistory::RELOAD)->sum('amount');
                    $nestedData['credits'] = number_format($purchasedCredits,0);
                    $nestedData['join_by'] = $value['manager_name'];
                    $nestedData['manager_name'] = $value['manager_name'];
                    $nestedData['business_license_number'] = $value['business_license_number'];
                    // $nestedData['date'] = $value['created_at'];
                    $nestedData['date'] = $this->formatDateTimeCountryWise($value['business_created_date'],$adminTimezone, 'd-m-Y H:i');

                    $reviews = Reviews::join('shops','shops.id', 'reviews.entity_id')
                                        ->where('reviews.entity_type_id',EntityTypes::SHOP)
                                        ->where('shops.user_id',$value['id'])
                                        ->select('reviews.*',DB::raw('round(AVG(reviews.rating),1) as avg_rating'))->get();
                    $nestedData['avg_rating'] = $reviews[0]->avg_rating;
                    
                    if ($value['status_id'] == Status::ACTIVE) {
                        $nestedData['status'] = '<span class="badge badge-success">'. Status::find($value['status_id'])->name . '</span>';
                    } else {
                        $nestedData['status'] = '<span class="badge badge-secondary">'. Status::find($value['status_id'])->name . '</span>';
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
            Log::info('Exception all shop list');
            Log::info($ex);
            return response()->json([]);
        }
    }
    public function getJsonActiveShopData(Request $request)
    {
        try {   
            Log::info('Start active shop list');
            $user = Auth::user();
            $manager = Manager::where('user_id',$user->id)->first();
            $manager_id = $manager ? $manager->id : 0;

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
                9 => 'users.status_id',
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

            $query = User::join('users_detail','users_detail.user_id','users.id')
                ->join('user_credits','user_credits.user_id','users.id')
                ->join('countries','users_detail.country_id','countries.id')
                // ->leftjoin('managers','managers.id','users_detail.manager_id')
                ->leftjoin('managers','managers.id','users_detail.manager_id')
                ->join('shops', function($query) {
                    $query->on('users.id','=','shops.user_id')
                    ->where('shops.status_id', Status::ACTIVE)
                    ->whereRaw('shops.id IN (select MAX(a2.id) from shops as a2 group by a2.user_id)');
                })
                ->leftjoin('addresses', function ($join) {
                    $join->on('shops.id', '=', 'addresses.entity_id')
                         ->where('shops.status_id', Status::ACTIVE)
                         ->where('addresses.entity_type_id', EntityTypes::SHOP);
                })
                ->where('users.status_id', Status::ACTIVE);

            if($manager_id && $manager_id != 0){
                $query = $query->where('managers.id',$manager_id);
            }
            $query = $query->select(
                    'addresses.*',
                    'users.id',
                    'users.status_id',
                    'users.created_at',
                    'users_detail.name as user_name',
                    'users_detail.mobile',
                    'user_credits.credits',
                    'shops.business_license_number',
                    'shops.main_name',
                    'shops.shop_name',
                    'shops.created_at as business_created_date',
                    'countries.name as country_name',
                    'managers.name as manager_name'
                );
            $totalData = $query->count();
            $totalFiltered = $totalData;

            if (!empty($search)) {
                $query = $query->where(function($q) use ($search){
                    $q->where('users_detail.name', 'LIKE', "%{$search}%")
                        ->orWhere('countries.name', 'LIKE', "%{$search}%")
                        ->orWhere('users_detail.mobile', 'LIKE', "%{$search}%")
                        ->orWhere('users.created_at', 'LIKE', "%{$search}%")
                        ->orWhere('user_credits.credits', 'LIKE', "%{$search}%")
                        ->orWhere('managers.name', 'LIKE', "%{$search}%");
                });
                $totalFiltered = $query->count();
            }

            $shops = $query->offset($start)
                ->limit($limit)
                ->orderBy($order, $dir)
                ->groupBy('users.id')
                ->get();
            $data = array();
            if (!empty($shops)) {
                foreach ($shops as $value) {
                    // dd(($value));
                    $id = $value['id'];
                    $view = route('admin.my-business-client.shop.show', $id);
                    $nestedData['checkbox'] = "<td><div class=\"custom-checkbox custom-control\">
                    <input type=\"checkbox\" data-checkboxes=\"mygroup\" class=\"custom-control-input checkbox_id check-active-shop\" id=\"$id\" data-id=\"$id\" value=\"$id\" name=\"checkbox_id[]\"><label for=\"$id\" class=\"custom-control-label\">&nbsp;</label></div></td>";
                    $address = $value['address'];
                    $address .= $value['address2'] ? ','.$value['address2'] : '';
                    $address .= $value['city_name'] ? ','.$value['city_name'] : '';
                    $address .= $value['state_name'] ? ','.$value['state_name'] : '';
                    $address .= $value['country_name'] ? ','.$value['country_name'] : '';
                    // $nestedData['name'] = $value['user_name'];
                    $nestedData['business_license_number'] = $value['business_license_number'];
                    $business_name = $value['main_name'] != "" ? $value['main_name']."/" : "";
                    $business_name .= $value['shop_name'];
                    $nestedData['name'] = $business_name;
                    $nestedData['mobile'] = $value['mobile'];
                    $nestedData['address'] = $address;
                    $purchasedCredits = UserCreditHistory::where('user_id',$id)->where('type',UserCreditHistory::RELOAD)->sum('amount');
                    $nestedData['credits'] = number_format($purchasedCredits,0);
                    $nestedData['join_by'] = $value['manager_name'];
                    
                    // $nestedData['date'] = $value['created_at'];
                    $nestedData['date'] = $this->formatDateTimeCountryWise($value['business_created_date'],$adminTimezone, 'd-m-Y H:i');
                    $reviews = Reviews::join('shops','shops.id', 'reviews.entity_id')
                                        ->where('reviews.entity_type_id',EntityTypes::SHOP)
                                        ->where('shops.user_id',$value['id'])
                                        ->select('reviews.*',DB::raw('round(AVG(reviews.rating),1) as avg_rating'))->get();
                    $nestedData['avg_rating'] = $reviews[0]->avg_rating;
                    
                    if ($value['status_id'] == Status::ACTIVE) {
                        $nestedData['status'] = '<span class="badge badge-success">'. Status::find($value['status_id'])->name . '</span>';
                    } else {
                        $nestedData['status'] = '<span class="badge badge-secondary">'. Status::find($value['status_id'])->name . '</span>';
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
            // dd($data);
            $jsonData = array(
                "draw" => intval($draw),
                "recordsTotal" => intval($totalData),
                "recordsFiltered" => intval($totalFiltered),
                "data" => $data
            );
            Log::info('End active shop list');
            return response()->json($jsonData);
        } catch (\Exception $ex) {
            Log::info('Exception active shop list');
            Log::info($ex);
            return response()->json([]);
        }
    }
    public function getJsonInactiveShopData(Request $request)
    {
        try {   
            Log::info('Start inactive shop list');
            $user = Auth::user();
            $manager = Manager::where('user_id',$user->id)->first();
            $manager_id = $manager ? $manager->id : 0;

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
                9 => 'users.status_id',
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
            
            $query = User::join('users_detail','users_detail.user_id','users.id')
                ->join('user_credits','user_credits.user_id','users.id')
                ->join('countries','users_detail.country_id','countries.id')
                ->leftjoin('managers','managers.id','users_detail.manager_id')
                ->join('shops', function($query) {
                    $query->on('users.id','=','shops.user_id')
                    ->where('shops.status_id', Status::INACTIVE)
                    ->whereRaw('shops.id IN (select MAX(a2.id) from shops as a2 group by a2.user_id)');
                })
                ->leftjoin('addresses', function ($join) {
                    $join->on('shops.id', '=', 'addresses.entity_id')
                        ->where('shops.status_id', Status::INACTIVE)
                         ->where('addresses.entity_type_id', EntityTypes::SHOP);
                })
                ->where('users.status_id', Status::INACTIVE);

            if($manager_id && $manager_id != 0){
                $query = $query->where('managers.id',$manager_id);
            }
            $query = $query->select(
                    'addresses.*',
                    'users.id',
                    'users.status_id',
                    'users.created_at',
                    'users_detail.name as user_name',
                    'users_detail.mobile',
                    'user_credits.credits',
                    'shops.business_license_number',
                    'shops.main_name',
                    'shops.shop_name',
                    'shops.created_at as business_created_date',
                    'countries.name as country_name',
                    'managers.name as manager_name'
                );
            $totalData = $query->count();
            $totalFiltered = $totalData;

            if (!empty($search)) {
                $query = $query->where(function($q) use ($search){
                    $q->where('users_detail.name', 'LIKE', "%{$search}%")
                        ->orWhere('countries.name', 'LIKE', "%{$search}%")
                        ->orWhere('users_detail.mobile', 'LIKE', "%{$search}%")
                        ->orWhere('users.created_at', 'LIKE', "%{$search}%")
                        ->orWhere('user_credits.credits', 'LIKE', "%{$search}%")
                        ->orWhere('managers.name', 'LIKE', "%{$search}%");
                });
                $totalFiltered = $query->count();
            }

            $shops = $query->offset($start)
                ->limit($limit)
                ->orderBy($order, $dir)
                ->groupBy('users.id')
                ->get();
            $data = array();
            if (!empty($shops)) {
                foreach ($shops as $value) {
                    $id = $value['id'];
                    $view = route('admin.my-business-client.shop.show', $id);
                    $nestedData['checkbox'] = "<td><div class=\"custom-checkbox custom-control\">
                    <input type=\"checkbox\" data-checkboxes=\"mygroup\" class=\"custom-control-input checkbox_id check-inactive-shop\" id=\"$id\" data-id=\"$id\" value=\"$id\" name=\"checkbox_id[]\"><label for=\"$id\" class=\"custom-control-label\">&nbsp;</label></div></td>";
                    $address = $value['address'];
                    $address .= $value['address2'] ? ','.$value['address2'] : '';
                    $address .= $value['city_name'] ? ','.$value['city_name'] : '';
                    $address .= $value['state_name'] ? ','.$value['state_name'] : '';
                    $address .= $value['country_name'] ? ','.$value['country_name'] : '';
                    // $nestedData['name'] = $value['user_name'];
                    $business_name = $value['main_name'] != "" ? $value['main_name']."/" : "";
                    $business_name .= $value['shop_name'];
                    $nestedData['name'] = $business_name;
                    $nestedData['mobile'] = $value['mobile'];
                    $nestedData['address'] = $address;
                    $purchasedCredits = UserCreditHistory::where('user_id',$id)->where('type',UserCreditHistory::RELOAD)->sum('amount');
                    $nestedData['credits'] = number_format($purchasedCredits,0);
                    $nestedData['join_by'] = $value['manager_name'];
                    // $nestedData['date'] = $value['created_at'];
                    $nestedData['date'] = $this->formatDateTimeCountryWise($value['business_created_date'],$adminTimezone, 'd-m-Y H:i');
                    $reviews = Reviews::join('shops','shops.id', 'reviews.entity_id')
                                        ->where('reviews.entity_type_id',EntityTypes::SHOP)
                                        ->where('shops.user_id',$value['id'])
                                        ->select('reviews.*',DB::raw('round(AVG(reviews.rating),1) as avg_rating'))->get();
                    $nestedData['avg_rating'] = $reviews[0]->avg_rating;
                    
                    if ($value['status_id'] == Status::ACTIVE) {
                        $nestedData['status'] = '<span class="badge badge-success">'. Status::find($value['status_id'])->name . '</span>';
                    } else {
                        $nestedData['status'] = '<span class="badge badge-secondary">'. Status::find($value['status_id'])->name . '</span>';
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
            Log::info('End inactive shop list');
            return response()->json($jsonData);
        } catch (\Exception $ex) {
            Log::info('Exception inactive shop list');
            Log::info($ex);
            return response()->json([]);
        }
    }
    
    public function showShop($id)
    {       
        $title = 'Shop Client Detail';
        $shop = Shop::find($id);
        $userDetail = UserDetail::where('user_id',$shop->user_id)->first();
        $shop->sns_link = !empty($userDetail) ? $userDetail->sns_link : '';
        $shop->sns_type = !empty($userDetail) ? $userDetail->sns_type : '';
        return view('admin.my-business-client.show-shop', compact('title','shop'));
    }

    public function viewShopProfile($id) {            

        $shops = Shop::join('category','category.id','shops.category_id')
                        ->leftjoin('reviews', function ($join) {
                            $join->on('shops.id', '=', 'reviews.entity_id')
                                ->where('reviews.entity_type_id', EntityTypes::SHOP);
                        })
                       ->whereIn('category.category_type_id', [CategoryTypes::SHOP, CategoryTypes::CUSTOM])
                       ->where('shops.user_id',$id)
                       ->groupby('shops.id')
                       ->select('shops.*','category.name as category',DB::raw('round(AVG(reviews.rating),1) as avg_rating'))->get();
        return view('admin.my-business-client.check-shop-profile',compact('shops'));
    }

    
    /* ================ Shop Code End ======================= */ 

    public function editCredits($id) {  
        $userCredits = UserCredit::where('user_id',$id)->first();

        return view('admin.my-business-client.edit-credits',compact('userCredits'));
    }
    public function updateCredits(Request $request) {   

        try {
            DB::beginTransaction();
            Log::info('Credit add code start.');
            $inputs = $request->all();
            $userCredits = UserCredit::where('user_id',$inputs['userId'])->first();              
            
            $old_credit = $userCredits->credits;
            $new_credit = $inputs['credits'];
            $total_credit = $old_credit + $new_credit;
            $userCredits = UserCredit::where('user_id',$inputs['userId'])->update(['credits' => $total_credit]); 
            UserCreditHistory::create([
                'user_id' => $inputs['userId'],
                'amount' => $inputs['credits'],
                'total_amount' => $total_credit,
                'transaction' => 'credit',
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

    public function deleteBusinessProfile(Request $request) {   

        try {
            DB::beginTransaction();
            Log::info('Delete business profile code start.');
            $inputs = $request->all();
            $businessProfiles = UserEntityRelation::whereIn('entity_type_id', [EntityTypes::SHOP, EntityTypes::HOSPITAL])
                                ->where('user_id',$inputs['userId'])->get();              
            
            foreach($businessProfiles as $profile){
                if($profile->entity_type_id == EntityTypes::SHOP){
                    Shop::where('id',$profile->entity_id)->delete();
                }
                if($profile->entity_type_id == EntityTypes::HOSPITAL){
                    Hospital::where('id',$profile->entity_id)->delete();
                }
            }
            UserEntityRelation::whereIn('entity_type_id', [EntityTypes::SHOP, EntityTypes::HOSPITAL])
                                ->where('user_id',$inputs['userId'])->delete();
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
    public function deleteUser(Request $request) {   

        try {
            DB::beginTransaction();
            Log::info('Delete user code start.');
            $inputs = $request->all();
            $businessProfiles = UserEntityRelation::where('user_id',$inputs['userId'])->get();              
            
            foreach($businessProfiles as $profile){
                if($profile->entity_type_id == EntityTypes::SHOP){
                    Shop::where('id',$profile->entity_id)->delete();
                }
                if($profile->entity_type_id == EntityTypes::HOSPITAL){
                    Hospital::where('id',$profile->entity_id)->delete();
                }
            }
            UserEntityRelation::where('user_id',$inputs['userId'])->delete(); 
            UserDetail::where('user_id',$inputs['userId'])->delete(); 
            User::where('id',$inputs['userId'])->delete(); 

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
}
