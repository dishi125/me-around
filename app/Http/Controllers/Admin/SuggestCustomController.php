<?php

namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\Status;
use App\Models\User;
use App\Models\EntityTypes;
use App\Models\UserEntityRelation;
use App\Models\Shop;
use App\Models\UserCredit;
use App\Models\UserCreditHistory;
use App\Models\UserDetail;
use App\Models\CategoryTypes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Log;
class SuggestCustomController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:suggest-custom-list', ['only' => ['index']]);
    }
/* ================ Suggest Custom Code Start ======================= */
    public function index()
    {
        $title = 'Suggest Custom';
        $totalUsers = UserEntityRelation::distinct('user_id')->count('user_id');;
        $totalShops = UserEntityRelation::where('entity_type_id',EntityTypes::SHOP)->distinct('user_id')->count('user_id');
        $totalHospitals = UserEntityRelation::where('entity_type_id',EntityTypes::HOSPITAL)->distinct('user_id')->count('user_id');
        $totalClients = $totalHospitals + $totalShops;
        $lastMonthIncome = 234000;
        $currentMonthIncome = 234000;
        return view('admin.suggest-custom.index', compact('title', 'totalUsers','totalShops','totalHospitals','totalClients','lastMonthIncome','currentMonthIncome'));
    }

    public function getJsonAllData(Request $request)
    {
        try {   
            Log::info('Start all suggest custom list');
            $user = Auth::user();
            $columns = array(
                0 => 'users.id',
                1 => 'shops.main_name',
                2 => 'users_detail.name',
                3 => 'countries.name',
                4 => 'users_detail.mobile',
                5 => 'user_credits.credits',
                6 => 'managers.name',
                7 => 'shops.created_at',
                8 => 'users.status_id',
                9 => 'log',
                10 => 'action',
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
                //->join('shops','shops.id','user_entity_relation.entity_id')
                ->join('shops', function($query) {
                    $query->on('users.id','=','shops.user_id')
                    ->whereRaw('shops.id IN (select MAX(a2.id) from shops as a2 group by a2.user_id)');
                })
                ->join('category','category.id','shops.category_id')                
                ->where('category.category_type_id', CategoryTypes::CUSTOM)
                ->leftjoin('managers','managers.user_id','users.id')
                ->where('user_entity_relation.entity_type_id', EntityTypes::SHOP)
                ->whereIn('users.status_id', [Status::ACTIVE, Status::INACTIVE])
                ->select(
                    'users.id',
                    'users.status_id',
                    'shops.created_at',
                    'users_detail.name as user_name',
                    'users_detail.mobile',
                    'user_credits.credits',
                    'countries.name as country_name',
                    'managers.name as manager_name',
                    'shops.main_name AS main_name',
                    'shops.shop_name AS sub_name'
                );
            $totalData = $query->count();
            $totalFiltered = $totalData;

            if (!empty($search)) {
                $query = $query->where(function($q) use ($search){
                    $q->where('users_detail.name', 'LIKE', "%{$search}%")
                    ->orWhere('countries.name', 'LIKE', "%{$search}%")
                    ->orWhere('users_detail.mobile', 'LIKE', "%{$search}%")
                    ->orWhere('shops.created_at', 'LIKE', "%{$search}%")
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
                    $business_name = $value['main_name'] != "" ? $value['main_name']."/" : "";
                    $business_name .= $value['sub_name'];

                    $view = route('admin.business-client.shop.show', $id);
                    $nestedData['checkbox'] = "<td><div class=\"custom-checkbox custom-control\">
                    <input type=\"checkbox\" data-checkboxes=\"mygroup\" class=\"custom-control-input checkbox_id check-all-shop\" id=\"$id\" data-id=\"$id\" value=\"$id\" name=\"checkbox_id[]\"><label for=\"$id\" class=\"custom-control-label\">&nbsp;</label></div></td>";
                   
                    $credits = number_format($value['credits']);
                    $nestedData['name'] = $value['user_name'];
                    $nestedData['business_name'] = $business_name;
                    $nestedData['mobile'] = $value['mobile'];
                    $nestedData['address'] = $value['country_name'];
                    $nestedData['credits'] = $credits;
                    $nestedData['join_by'] = $value['manager_name'];
                    $nestedData['date'] = $this->formatDateTimeCountryWise($value['display_created_at'],$adminTimezone,'d-m-Y H:i');
                    
                    if ($value['status_id'] == Status::ACTIVE) {
                        $nestedData['status'] = '<span class="badge badge-success">'. Status::find($value['status_id'])->name . '</span>';
                    } else {
                        $nestedData['status'] = '<span class="badge badge-secondary">'. Status::find($value['status_id'])->name . '</span>';
                    }    
                    $seeButton = "<a role='button' href='javascript:void(0)' onclick='viewLogs(" . $id . ")' title='' data-original-title='View' class='btn btn-primary btn-sm' data-toggle='tooltip'>see</a>";
                    $nestedData['credit_purchase_log'] = $seeButton;                
                    $viewButton = "<a role='button' href='javascript:void(0)' onclick='viewProfile(" . $id . ")'  title='' data-original-title='View' class='btn btn-primary btn-sm' data-toggle='tooltip'><i class='fas fa-eye'></i></a>";

                    $editButton = $user->hasRole('Admin') ? "<a href='javascript:void(0)' role='button' onclick='editCredits(" . $id . ")' class='btn btn-primary btn-sm mx-1' data-toggle='tooltip' data-original-title='Edit'><i class='fa fa-edit'></a>" : "";
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
            Log::info('End all suggest custom list');
            return response()->json($jsonData);
        } catch (\Exception $ex) {
            Log::info('Exception all suggest custom list');
            Log::info($ex);
            return response()->json([]);
        }
    }
    public function getJsonActiveData(Request $request)
    {
        try {   
            Log::info('Start all suggest custom list');
            
            $columns = array(
                0 => 'users.id',
                1 => 'shops.main_name',
                2 => 'users_detail.name',
                3 => 'countries.name',
                4 => 'users_detail.mobile',
                5 => 'user_credits.credits',
                6 => 'managers.name',
                7 => 'shops.created_at',
                8 => 'users.status_id',
                9 => 'log',
                10 => 'action',
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
                ->leftjoin('managers','managers.user_id','users.id')
                ->join('shops', function($query) {
                    $query->on('users.id','=','shops.user_id')
                    ->whereRaw('shops.id IN (select MAX(a2.id) from shops as a2 group by a2.user_id)');
                })
                ->join('category','category.id','shops.category_id')                
                ->where('category.category_type_id', CategoryTypes::CUSTOM)
                ->where('user_entity_relation.entity_type_id', EntityTypes::SHOP)
                ->where('users.status_id', Status::ACTIVE)
                ->select(
                    'users.id',
                    'users.status_id',
                    'shops.created_at',
                    'users_detail.name as user_name',
                    'users_detail.mobile',
                    'user_credits.credits',
                    'countries.name as country_name',
                    'managers.name as manager_name',
                    'shops.main_name AS main_name',
                    'shops.shop_name AS sub_name'
                );
            $totalData = $query->count();
            $totalFiltered = $totalData;

            if (!empty($search)) {
                $query = $query->where(function($q) use ($search){
                    $q->where('users_detail.name', 'LIKE', "%{$search}%")
                        ->orWhere('countries.name', 'LIKE', "%{$search}%")
                        ->orWhere('users_detail.mobile', 'LIKE', "%{$search}%")
                        ->orWhere('shops.created_at', 'LIKE', "%{$search}%")
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
                    $business_name = $value['main_name'] != "" ? $value['main_name']."/" : "";
                    $business_name .= $value['sub_name'];

                    $view = route('admin.business-client.shop.show', $id);
                    $nestedData['checkbox'] = "<td><div class=\"custom-checkbox custom-control\">
                    <input type=\"checkbox\" data-checkboxes=\"mygroup\" class=\"custom-control-input checkbox_id check-active-shop\" id=\"$id\" data-id=\"$id\" value=\"$id\" name=\"checkbox_id[]\"><label for=\"$id\" class=\"custom-control-label\">&nbsp;</label></div></td>";
                   
                    $nestedData['name'] = $value['user_name'];
                    $nestedData['business_name'] = $business_name;
                    $nestedData['mobile'] = $value['mobile'];
                    $nestedData['address'] = $value['country_name'];
                    $nestedData['credits'] = number_format($value['credits']);
                    $nestedData['join_by'] = $value['manager_name'];
                    $nestedData['date'] = $this->formatDateTimeCountryWise($value['display_created_at'],$adminTimezone,'d-m-Y H:i');
                    
                    if ($value['status_id'] == Status::ACTIVE) {
                        $nestedData['status'] = '<span class="badge badge-success">'. Status::find($value['status_id'])->name . '</span>';
                    } else {
                        $nestedData['status'] = '<span class="badge badge-secondary">'. Status::find($value['status_id'])->name . '</span>';
                    }    
                    $seeButton = "<a role='button' href='javascript:void(0)' onclick='viewLogs(" . $id . ")' title='' data-original-title='View' class='btn btn-primary btn-sm' data-toggle='tooltip'>see</a>";
                    $nestedData['credit_purchase_log'] = $seeButton;                
                    $viewButton = "<a role='button' href='javascript:void(0)' onclick='viewProfile(" . $id . ")'  title='' data-original-title='View' class='btn btn-primary btn-sm' data-toggle='tooltip'><i class='fas fa-eye'></i></a>";
                    $editButton = "<a href='javascript:void(0)' role='button' onclick='editCredits(" . $id . ")' class='btn btn-primary btn-sm mx-1' data-toggle='tooltip' data-original-title='Edit'><i class='fa fa-edit'></a>";
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
            Log::info('End all suggest custom list');
            return response()->json($jsonData);
        } catch (\Exception $ex) {
            Log::info('Exception all suggest custom list');
            Log::info($ex);
            return response()->json([]);
        }
    }
    public function getJsonInactiveData(Request $request)
    {
        try {   
            Log::info('Start all suggest custom list');
            
            $columns = array(
                0 => 'users.id',
                1 => 'shops.main_name',
                2 => 'users_detail.name',
                3 => 'countries.name',
                4 => 'users_detail.mobile',
                5 => 'user_credits.credits',
                6 => 'managers.name',
                7 => 'shops.created_at',
                8 => 'users.status_id',
                9 => 'log',
                10 => 'action',
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
                ->leftjoin('managers','managers.user_id','users.id')
                ->join('shops', function($query) {
                    $query->on('users.id','=','shops.user_id')
                    ->whereRaw('shops.id IN (select MAX(a2.id) from shops as a2 group by a2.user_id)');
                })
                ->join('category','category.id','shops.category_id')                
                ->where('category.category_type_id', CategoryTypes::CUSTOM)
                ->where('user_entity_relation.entity_type_id', EntityTypes::SHOP)
                ->where('users.status_id', Status::INACTIVE)
                ->select(
                    'users.id',
                    'users.status_id',
                    'shops.created_at',
                    'users_detail.name as user_name',
                    'users_detail.mobile',
                    'user_credits.credits',
                    'countries.name as country_name',
                    'managers.name as manager_name',
                    'shops.main_name AS main_name',
                    'shops.shop_name AS sub_name'
                );
            $totalData = $query->count();
            $totalFiltered = $totalData;

            if (!empty($search)) {
                $query = $query->where(function($q) use ($search){
                    $q->where('users_detail.name', 'LIKE', "%{$search}%")
                        ->orWhere('countries.name', 'LIKE', "%{$search}%")
                        ->orWhere('users_detail.mobile', 'LIKE', "%{$search}%")
                        ->orWhere('shops.created_at', 'LIKE', "%{$search}%")
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
                    $business_name = $value['main_name'] != "" ? $value['main_name']."/" : "";
                    $business_name .= $value['sub_name'];
                    
                    $view = route('admin.business-client.shop.show', $id);
                    $nestedData['checkbox'] = "<td><div class=\"custom-checkbox custom-control\">
                    <input type=\"checkbox\" data-checkboxes=\"mygroup\" class=\"custom-control-input checkbox_id check-active-shop\" id=\"$id\" data-id=\"$id\" value=\"$id\" name=\"checkbox_id[]\"><label for=\"$id\" class=\"custom-control-label\">&nbsp;</label></div></td>";
                   
                    $nestedData['name'] = $value['user_name'];
                    $nestedData['business_name'] = $business_name;
                    $nestedData['mobile'] = $value['mobile'];
                    $nestedData['address'] = $value['country_name'];
                    $nestedData['credits'] = number_format($value['credits']);
                    $nestedData['join_by'] = $value['manager_name'];
                    $nestedData['date'] = $this->formatDateTimeCountryWise($value['display_created_at'],$adminTimezone,'d-m-Y H:i');
                    
                    if ($value['status_id'] == Status::ACTIVE) {
                        $nestedData['status'] = '<span class="badge badge-success">'. Status::find($value['status_id'])->name . '</span>';
                    } else {
                        $nestedData['status'] = '<span class="badge badge-secondary">'. Status::find($value['status_id'])->name . '</span>';
                    }    
                    //$seeButton = "<a role='button' href='javascript:void(0)' title='' data-original-title='View' class='btn btn-primary btn-sm' data-toggle='tooltip'>see</a>";
                    $seeButton = "<a role='button' href='javascript:void(0)' onclick='viewLogs(" . $id . ")' title='' data-original-title='View' class='btn btn-primary btn-sm' data-toggle='tooltip'>see</a>";
                    $nestedData['credit_purchase_log'] = $seeButton;                
                    $viewButton = "<a role='button' href='javascript:void(0)' onclick='viewProfile(" . $id . ")'  title='' data-original-title='View' class='btn btn-primary btn-sm' data-toggle='tooltip'><i class='fas fa-eye'></i></a>";
                    $editButton = "<a href='javascript:void(0)' role='button' onclick='editCredits(" . $id . ")' class='btn btn-primary btn-sm mx-1' data-toggle='tooltip' data-original-title='Edit'><i class='fa fa-edit'></a>";
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
            Log::info('End all suggest custom list');
            return response()->json($jsonData);
        } catch (\Exception $ex) {
            Log::info('Exception all suggest custom list');
            Log::info($ex);
            return response()->json([]);
        }
    }

    public function show($id)
    {
        $title = 'Shop Client Detail';
        $shop = Shop::find($id);        
        return view('admin.business-client.show-shop', compact('title','shop'));
    }

/* ================ Suggest Custom Code End ======================= */


    public function editCredits($id) {       
        return view('admin.suggest-custom.edit-credits');
    }
    public function viewProfile($id) {       
        $shops = UserEntityRelation::join('shops','shops.id','user_entity_relation.entity_id')
        ->join('category','category.id','shops.category_id')
        ->where('category.category_type_id', CategoryTypes::CUSTOM)
        ->where('shops.user_id',$id)->where('entity_type_id',EntityTypes::SHOP)
        ->select('shops.id','category.name')->get();
        return view('admin.suggest-custom.check-profile',compact('shops'));
    }
}
