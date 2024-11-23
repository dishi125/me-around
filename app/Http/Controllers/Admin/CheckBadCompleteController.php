<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\EntityTypes;
use Illuminate\Support\Facades\DB;
use Log;
use App\Models\Status;
use App\Models\RequestBookingStatus;
use Carbon\Carbon;

class CheckBadCompleteController extends Controller
{
    public function index(Request $request)
    {
        $title = 'Check Bad Complete';
        DB::table('requested_customer')->where('is_admin_read',1)->update(['is_admin_read' => 0]);
        return view('admin.bad-complete.index', compact('title'));
    }

    public function getJsonAllData(Request $request){

        $columns = array(
            0 => 'main_name',
            2 => 'users_detail.name',
            5 => 'requested_customer.booking_date',
            4 => 'times',
            11 => 'action',
        );

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
            $times = DB::raw("count(requested_customer.user_id)");
            $completedQuery = DB::table('requested_customer')
                ->leftjoin('users_detail','users_detail.user_id', 'requested_customer.user_id')
                ->leftjoin('shops', function ($join) {
                    $join->on('shops.id', '=', 'requested_customer.entity_id')
                        ->where('requested_customer.entity_type_id', EntityTypes::SHOP);
                })
                ->leftjoin('posts', function ($join) {
                    $join->on('posts.id', '=', 'requested_customer.entity_id')
                        ->where('requested_customer.entity_type_id', EntityTypes::HOSPITAL);
                })
                ->leftjoin('hospitals', function ($join) {
                    $join->on('hospitals.id', '=', 'posts.hospital_id');
                })
                ->leftjoin('user_entity_relation', function ($join) {
                    $join->on('user_entity_relation.entity_id', '=', 'hospitals.id')
                        ->where('user_entity_relation.entity_type_id', EntityTypes::HOSPITAL);
                })
                ->select(
                    'requested_customer.id',
                    'users_detail.user_id as user_id',
                    'users_detail.name as username',
                    'users_detail.mobile as user_mobile',
                    \DB::raw('(CASE 
                    WHEN requested_customer.entity_type_id = 1 THEN  shops.main_name
                    WHEN requested_customer.entity_type_id = 2 THEN hospitals.main_name 
                    ELSE "" 
                    END) AS main_name'),
                    \DB::raw('(CASE 
                    WHEN requested_customer.entity_type_id = 1 THEN  shops.user_id
                    WHEN requested_customer.entity_type_id = 2 THEN user_entity_relation.user_id 
                    ELSE "" 
                    END) AS business_user_id'),
                    DB::raw("DATE_FORMAT(requested_customer.booking_date, '%Y-%m-%d') as date"),
                    'requested_customer.booking_date'
                )
                ->selectRaw("{$times} AS times")                             
                ->havingRaw("{$times} > 1")                
                ->whereNull('requested_customer.deleted_at')
                ->where('requested_customer.request_booking_status_id',RequestBookingStatus::COMPLETE)   
                ->groupBy('requested_customer.user_id','requested_customer.entity_id',DB::raw("DATE_FORMAT(requested_customer.booking_date, '%Y-%m-%d')"));

            
            if (!empty($search)) {
                $completedQuery = $completedQuery->where(function($q) use ($search){
                    $q->where('users_detail.name', 'LIKE', "%{$search}%")
                    ->orWhere('shops.main_name', 'LIKE', "%{$search}%")
                    ->orWhere('hospitals.main_name', 'LIKE', "%{$search}%")
                    ->orWhere('users_detail.mobile', 'LIKE', "%{$search}%");
                });
                
            }

            $totalData = count($completedQuery->get());
            $totalFiltered = $totalData;
            
            $userData = $completedQuery->offset($start)
                        ->limit($limit)
                        ->orderBy($order, $dir)  
                        ->get();

            
            $count = 0;
            foreach($userData as $complete){
                $businessUser = DB::table('users_detail')->where('id',$complete->business_user_id)->first();
                $deleteButton = "<a role='button' href='javascript:void(0)' onclick='deleteUser(" . $complete->user_id . ")' title='' data-original-title='Delete Account' class='mx-1 btn btn-primary btn-sm' data-toggle='tooltip'>Delete Customer</a>";
                $deleteBusinessButton = "<a role='button' href='javascript:void(0)' onclick='deleteUser(" . $complete->business_user_id . ")' title='' data-original-title='Delete Account' class='mx-1 btn btn-primary btn-sm' data-toggle='tooltip'>Delete Business</a>";
                
                $data[$count]['business_name'] = $complete->main_name;
                $data[$count]['phone'] = (!empty($businessUser)) ? $businessUser->mobile : '';
                $data[$count]['customer_name'] = $complete->username;
                $data[$count]['customer_phone'] = $complete->user_mobile;
                $data[$count]['complete_times'] = $complete->times;
                $data[$count]['booking_date'] = $this->formatDateTimeCountryWise($complete->booking_date,$adminTimezone, 'Y-m-d');
                $data[$count]['actions'] = "<div class='d-flex'>$deleteBusinessButton $deleteButton</div>";
                $count++;
            }

            $jsonData = array(
                "draw" => intval($draw),
                "recordsTotal" => intval($totalData),
                "recordsFiltered" => intval($totalFiltered),
                "data" => $data,
                "completedQuery" => $userData,
            );
            return response()->json($jsonData);
        } catch (\Exception $ex) {
            Log::info('Exception all hospital list');
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

    public function getJsonTwoWeekData(Request $request){

        $columns = array(
            0 => 'main_name',
            2 => 'username',
            5 => 'date',
            4 => 'times',
            11 => 'action',
        );

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
            
            $completedQuery = DB::table('requested_customer')
                ->leftjoin('users_detail','users_detail.user_id', 'requested_customer.user_id')
                ->leftjoin('shops', function ($join) {
                    $join->on('shops.id', '=', 'requested_customer.entity_id')
                        ->where('requested_customer.entity_type_id', EntityTypes::SHOP);
                })
                ->leftjoin('posts', function ($join) {
                    $join->on('posts.id', '=', 'requested_customer.entity_id')
                        ->where('requested_customer.entity_type_id', EntityTypes::HOSPITAL);
                })
                ->leftjoin('hospitals', function ($join) {
                    $join->on('hospitals.id', '=', 'posts.hospital_id');
                })
                ->leftjoin('user_entity_relation', function ($join) {
                    $join->on('user_entity_relation.entity_id', '=', 'hospitals.id')
                        ->where('user_entity_relation.entity_type_id', EntityTypes::HOSPITAL);
                })
                ->select(
                    'requested_customer.id',
                    'requested_customer.booking_date',
                    DB::raw("DATE_FORMAT(requested_customer.booking_date, '%Y-%m-%d') as date"),
                    'requested_customer.entity_type_id',
                    'requested_customer.entity_id',
                    'users_detail.user_id as user_id',
                    'users_detail.name as username',
                    'users_detail.mobile as user_mobile',
                    \DB::raw('(CASE 
                    WHEN requested_customer.entity_type_id = 1 THEN  shops.main_name
                    WHEN requested_customer.entity_type_id = 2 THEN hospitals.main_name 
                    ELSE "" 
                    END) AS main_name'),
                    \DB::raw('(CASE 
                    WHEN requested_customer.entity_type_id = 1 THEN  shops.user_id
                    WHEN requested_customer.entity_type_id = 2 THEN user_entity_relation.user_id 
                    ELSE "" 
                    END) AS business_user_id')
                )               
                ->whereNull('requested_customer.deleted_at')
                ->where('requested_customer.request_booking_status_id',RequestBookingStatus::COMPLETE)   
                ->orderBy('requested_customer.booking_date','ASC');

            if (!empty($search)) {
                $completedQuery = $completedQuery->where(function($q) use ($search){
                    $q->where('users_detail.name', 'LIKE', "%{$search}%")
                    ->orWhere('shops.main_name', 'LIKE', "%{$search}%")
                    ->orWhere('hospitals.main_name', 'LIKE', "%{$search}%")
                    ->orWhere('users_detail.mobile', 'LIKE', "%{$search}%");
                });
                
            }

            $completedQuery = $completedQuery->get();
            $completedResult = collect($completedQuery)->groupBy(['user_id','entity_type_id', 'entity_id']);

            $displayResult = [];
            foreach($completedResult as $userKey => $userData){
                
                foreach($userData as $typeKey => $entityType){
                    foreach($entityType as $entityKey => $entityData){
                        if(count($entityData) > 1){
                            foreach($entityData as $bookingKey => $bookingData){
                                if(isset($entityData[$bookingKey+1]) ){
                                    
                                    $currentDate = $bookingData->date;
                                    $nextBookingDate = $entityData[$bookingKey+1]->date;
                                    $nextWeekBookDate = Carbon::parse($currentDate)->addDays(14)->format('Y-m-d');
                                    
                                    if(Carbon::parse($nextBookingDate)->between($currentDate,$nextWeekBookDate)){
                                        $displayResult[$bookingData->user_id][$entityKey]['ids'][] = $bookingData->id;
                                        $displayResult[$bookingData->user_id][$entityKey]['ids'][] = $entityData[$bookingKey+1]->id;
                                    }
                                }
                            }
                        }
                    }
                }
            }

            $innerCount = 0;
            foreach($displayResult as $userDetail){
                foreach($userDetail as $entityDetail){
                    foreach($entityDetail as $bookingDetail){
                        $bookingIds = array_unique($bookingDetail);
                        $bookingId = $bookingIds[0];
                        $userDisplayData[$innerCount] = collect($completedQuery)->where('id',$bookingId)->first();
                        $userDisplayData[$innerCount]->times = count($bookingIds);
                        $userDisplayData[$innerCount]->dates = collect($completedQuery)->whereIn('id',$bookingIds)->pluck('booking_date')->map(function ($value) use($adminTimezone) {
                                return $this->formatDateTimeCountryWise($value,$adminTimezone, 'Y-m-d');
                            });
                        $innerCount++;
                    }
                }
            }
            
            
            if($dir == 'asc'){
                $userDisplayData = collect($userDisplayData)->sortBy($order);
            }else{
                $userDisplayData = collect($userDisplayData)->sortByDesc($order);
            }

            $totalData = count($userDisplayData);
            $totalFiltered = $totalData;

            $userDisplayData = collect($userDisplayData)->slice($start, $limit);
            
            $count = 0;
            foreach($userDisplayData as $complete){
                $businessUser = DB::table('users_detail')->where('id',$complete->business_user_id)->first();
                $deleteButton = "<a role='button' href='javascript:void(0)' onclick='deleteUser(" . $complete->user_id . ")' title='' data-original-title='Delete Account' class='mx-1 btn btn-primary btn-sm' data-toggle='tooltip'>Delete Customer</a>";
                $deleteBusinessButton = "<a role='button' href='javascript:void(0)' onclick='deleteUser(" . $complete->business_user_id . ")' title='' data-original-title='Delete Account' class='mx-1 btn btn-primary btn-sm' data-toggle='tooltip'>Delete Business</a>";
                $allDates = $complete->dates;

                $data[$count]['business_name'] = $complete->main_name;
                $data[$count]['phone'] = (!empty($businessUser)) ? $businessUser->mobile : '';
                $data[$count]['customer_name'] = $complete->username;
                $data[$count]['customer_phone'] = $complete->user_mobile;
                $data[$count]['complete_times'] = $complete->times;
                $data[$count]['booking_date'] = collect($allDates)->implode('<br/>');
                $data[$count]['actions'] = "<div class='d-flex'>$deleteBusinessButton $deleteButton</div>";
                $count++;
            }

            $jsonData = array(
                "draw" => intval($draw),
                "recordsTotal" => intval($totalData),
                "recordsFiltered" => intval($totalFiltered),
                "data" => $data,
                "displayResult" => $displayResult,
                "userData" => $userDisplayData,
                "completedQuery" => $completedResult,
            );
            return response()->json($jsonData);
        } catch (\Exception $ex) {
            Log::info('Exception all hospital list');
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
