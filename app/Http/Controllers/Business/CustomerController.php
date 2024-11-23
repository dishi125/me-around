<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Log;
use Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\RequestedCustomer;
use App\Models\EntityTypes;
use App\Models\RequestBookingStatus;
use App\Models\Category;

class CustomerController extends Controller
{
    public function index()
    {
        $title = 'Customers';
        return view('business.customer.index', compact('title'));
    }

    public function getJsonData(Request $request){
        $user = Auth::user();
        $columns = array(
            0 => 'users.id',
            1 => 'user_name',
            2 => 'category_name',
            3 => 'category_image',
            4 => 'booking_date',
            5 => 'action',
        );

        $filter = !empty($request->input('filter')) ? $request->input('filter') : 'booked_user';
        $limit = $request->input('length');
        $start = $request->input('start');
        $search = $request->input('search.value');
        $draw = $request->input('draw');
        $order = $columns[$request->input('order.0.column')];
        $dir = $request->input('order.0.dir');
        
        $adminTimezone = $this->getAdminUserTimezone();
        $isShop = $user->entityType->contains('entity_type_id', EntityTypes::SHOP);
        $isHospital = $user->entityType->contains('entity_type_id', EntityTypes::HOSPITAL);

        try {
            $data = [];

            DB::enableQueryLog();
            if($isShop){
                $usersShop = [];
                foreach($user->entityType as $userEntity){
                    if($userEntity->entity_type_id == EntityTypes::SHOP){
                        $usersShop[] = $userEntity->entity_id;
                    }
                }
                $bookingUser = $bookingUserVisited = [];
                $notInUser = collect($bookingUser)->implode("','");

                if($filter == 'booked_user'){

                    $query = $this->getBookedShopUser($usersShop);

                }elseif($filter == 'visited_user'){

                    $query = $this->getVisitedShopUser($usersShop,$notInUser);

                }elseif($filter == 'completed_user'){

                    $getVisitedData = $this->getVisitedShopUser($usersShop,$notInUser);

                    foreach($getVisitedData->get() as $val){
                        $bookingUserVisited[] = $val->user_id.'_'.$val->entity_id;
                    }

                    $bookingUserMerge = array_merge($bookingUser,$bookingUserVisited);
                    $notInCompleteUser = collect($bookingUserMerge)->implode("','");

                    $query = $this->getCompletedShopUser($usersShop,$notInCompleteUser);
                }

            }else{

                // hospital users
                $usersHospitals = [];
                foreach($user->entityType as $userEntity){
                    if($userEntity->entity_type_id == EntityTypes::HOSPITAL){
                        $usersHospitals[] = $userEntity->entity_id;
                    }
                }

                $bookingUserHospital = $bookingUserVisitedHospital = [];
                $notInUserHospital = collect($bookingUserHospital)->implode("','");

                if($filter == 'booked_user'){

                    $query = $this->getBookedHospitalUser($usersHospitals =[]);

                }elseif($filter == 'visited_user'){
                    $query = $this->getVisitedHospitalUser($usersHospitals,$notInUserHospital);      

                }elseif($filter == 'completed_user'){

                    $getVisitedData = $this->getVisitedHospitalUser($usersHospitals,$notInUserHospital);

                    foreach($getVisitedData->get() as $val){
                        $bookingUserVisited[] = $val->user_id.'_'.$val->entity_id;
                    }

                    $bookingUserMergeHospital = array_merge($bookingUserHospital,$bookingUserVisitedHospital);
                    $notInCompleteUserHospital = collect($bookingUserMergeHospital)->implode("','");

                    $query = $this->getCompletedHospitalUser($usersHospitals,$notInCompleteUserHospital);
                    
                }

            }

            $totalData = count($query->get());
            $totalFiltered = $totalData;

            $customers = $query->offset($start)
            ->limit($limit)
            ->orderBy($order, $dir)  
            ->get();

            $count = 0;
            foreach($customers as $value){

                if($isShop){
                    $category = Category::find($value->category_id);
                    $categoryImage = ($category && $category->logo) ? "<image width=40 src='".$category->logo."'>" : NULL;
                    $categoryName = $category->name;

                }else{
                    $categoryName = $value->category_name;
                    $categoryImage = $value->category_logo;

                }

                $userImage = !empty($value->user_image) ? "<image width=40 src='".$value->user_image."'>" : NULL;

                $bookingDate = Carbon::createFromFormat('Y-m-d H:i:s',$value->booking_date, "UTC")->setTimezone($adminTimezone);
                
                $data[$count]['user_image'] = $userImage;
                $data[$count]['user_name'] = $value->user_name;
                $data[$count]['category_name'] = $categoryName;
                $data[$count]['category_image'] = $categoryImage;
                $data[$count]['booking_date'] = $bookingDate->toDateTimeString();
                $data[$count]['actions'] = "<div class='d-flex'></div>";
                $count++;
            }

           // print_r($data);die;
            $jsonData = array(
                "draw" => intval($draw),
                "recordsTotal" => intval($totalData),
                "recordsFiltered" => intval($totalFiltered),
                "data" => $data
            );

            return response()->json($jsonData);
        } catch (\Exception $ex) {
            print_r($ex->getMessage());die;
            $jsonData = array(
                "draw" => intval($draw),
                "recordsTotal" => 0,
                "recordsFiltered" => 0,
                "data" => []
            );
            return response()->json($jsonData);
        }
    }

    public function getBookedHospitalUser($usersHospitals =[]){

        $concatQuery = 'CONCAT(requested_customer.user_id, "_", requested_customer.entity_id)';

        $query = RequestedCustomer::join('posts','posts.id','=','requested_customer.entity_id')
        ->join('category','category.id','=','posts.category_id')
        ->join('users','users.id','=','requested_customer.user_id')
        ->whereNotNull('users.email')
        ->where('requested_customer.entity_type_id',EntityTypes::HOSPITAL)
        ->whereIn('posts.hospital_id', $usersHospitals)
        ->where('requested_customer.request_booking_status_id', RequestBookingStatus::BOOK)
        ->orderBy('requested_customer.created_at','desc')
        ->groupBy('requested_customer.user_id','requested_customer.entity_id')
        ->select('requested_customer.*','posts.title as title','posts.category_id','category.name as category_name', 'users.status_id', 'users.chat_status',
            \DB::raw('(CASE 
                WHEN category.logo != NULL THEN  category.logo
                ELSE "" 
                END) AS category_logo'))
        ->selectRaw("{$concatQuery} AS uniqe_records");

        return $query;
    }

    public function getVisitedShopUser($usersShop = [],$notInUser=[])
    {
        $concatQuery = 'CONCAT(requested_customer.user_id, "_", requested_customer.entity_id)';

        $query = RequestedCustomer::join('shops','shops.id','=','requested_customer.entity_id')
        ->join('category','category.id','=','shops.category_id')
        ->join('users','users.id','=','requested_customer.user_id')
        ->whereNotNull('users.email')
        ->where('requested_customer.entity_type_id',EntityTypes::SHOP)
        ->whereIn('shops.id', $usersShop)
        ->orderBy('requested_customer.booking_date','desc')
        ->groupBy('requested_customer.user_id','requested_customer.entity_id')
        ->where('requested_customer.request_booking_status_id', RequestBookingStatus::VISIT)
        ->select('requested_customer.*','requested_customer.user_id','requested_customer.entity_id','shops.shop_name as title','shops.category_id','users.status_id', 'users.chat_status')
        ->selectRaw("{$concatQuery} AS uniqe_records")
        ->whereRaw("{$concatQuery} NOT IN ('{$notInUser}')");

        return $query;
    }

    public function getCompletedShopUser($usersShop = [],$notInCompleteUser=[])
    {
        $concatQuery = 'CONCAT(requested_customer.user_id, "_", requested_customer.entity_id)';

        $query = RequestedCustomer::join('shops','shops.id','=','requested_customer.entity_id')
        ->join('category','category.id','=','shops.category_id')
        ->join('users','users.id','=','requested_customer.user_id')
        ->whereNotNull('users.email')
        ->where('requested_customer.entity_type_id',EntityTypes::SHOP)
                                        // ->where('shops.status_id', Status::ACTIVE)
        ->whereIn('shops.id', $usersShop)
        ->where('requested_customer.request_booking_status_id', RequestBookingStatus::COMPLETE)
        ->orderBy('requested_customer.updated_at','desc')
        ->groupBy('requested_customer.user_id','requested_customer.entity_id')
        ->select('requested_customer.*','shops.shop_name as title','shops.category_id','users.status_id', 'users.chat_status')
        ->selectRaw("{$concatQuery} AS uniqe_records")
        ->whereRaw("{$concatQuery} NOT IN ('{$notInCompleteUser}')")
        ->whereIn('requested_customer.id', function($q){
            $q->select(DB::raw('max(id)'))->from('requested_customer')->where('requested_customer.request_booking_status_id', RequestBookingStatus::COMPLETE)->groupBy('requested_customer.user_id','requested_customer.entity_id');
        });

        return $query;
    }

    public function getVisitedHospitalUser($usersHospitals = [],$notInUserHospital=''){

        $concatQuery = 'CONCAT(requested_customer.user_id, "_", requested_customer.entity_id)';

        $query = RequestedCustomer::
        join('posts','posts.id','=','requested_customer.entity_id')
        ->join('category','category.id','=','posts.category_id')
        ->join('users','users.id','=','requested_customer.user_id')
        ->whereNotNull('users.email')
        ->where('requested_customer.entity_type_id',EntityTypes::HOSPITAL)
                                        // ->where('posts.status_id', Status::ACTIVE)
        ->whereIn('posts.hospital_id', $usersHospitals)
        ->where('requested_customer.request_booking_status_id', RequestBookingStatus::VISIT)
        ->orderBy('requested_customer.booking_date','desc')
        ->groupBy('requested_customer.user_id','requested_customer.entity_id')
        ->select('requested_customer.*','posts.title as title','posts.category_id','category.name as category_name', 'users.status_id', 'users.chat_status',
            \DB::raw('(CASE 
                WHEN category.logo != NULL THEN  category.logo
                ELSE "" 
                END) AS category_logo'))
        ->selectRaw("{$concatQuery} AS uniqe_records")
        ->whereRaw("{$concatQuery} NOT IN ('{$notInUserHospital}')");

        return $query;
    }

    public function getCompletedHospitalUser($usersHospitals = [],$notInCompleteUserHospital){

        $concatQuery = 'CONCAT(requested_customer.user_id, "_", requested_customer.entity_id)';

        $query = RequestedCustomer::join('posts','posts.id','=','requested_customer.entity_id')
        ->join('category','category.id','=','posts.category_id')
        ->join('users','users.id','=','requested_customer.user_id')
        ->whereNotNull('users.email')
        ->where('requested_customer.entity_type_id',EntityTypes::HOSPITAL)
                    // ->where('posts.status_id', Status::ACTIVE)
        ->whereIn('posts.hospital_id', $usersHospitals)
        ->where('requested_customer.request_booking_status_id', RequestBookingStatus::COMPLETE)
        ->orderBy('requested_customer.created_at','desc')
        ->groupBy('requested_customer.user_id','requested_customer.entity_id')
        ->select('requested_customer.*','posts.title as title','posts.category_id','category.name as category_name', 'users.status_id', 'users.chat_status',
            \DB::raw('(CASE 
                WHEN category.logo != NULL THEN  category.logo
                ELSE "" 
                END) AS category_logo'))
        ->selectRaw("{$concatQuery} AS uniqe_records")
        ->whereRaw("{$concatQuery} NOT IN ('{$notInCompleteUserHospital}')")
        ->whereIn('requested_customer.id', function($q){
            $q->select(DB::raw('max(id)'))->from('requested_customer')->where('requested_customer.request_booking_status_id', RequestBookingStatus::COMPLETE)->groupBy('requested_customer.user_id','requested_customer.entity_id');
        });

        return $query;
    }


}
