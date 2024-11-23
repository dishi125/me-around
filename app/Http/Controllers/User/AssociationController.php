<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Log;
use Storage;
use Validator;
use App\Models\Association;
use App\Models\AssociationUsers;
use App\Models\EntityTypes;
use App\Models\Status;
use App\Models\Country;
use App\Models\UserDevices;
use App\Models\Notice;
use App\Models\UserDetail;
use App\Models\AssociationImage;
use App\Models\AssociationCategory;
use App\Util\Firebase;

class AssociationController extends Controller
{
    protected $firebase;
    function __construct()
    {
        $this->firebase = new Firebase();
    }  

    public function index($header)
    {
        $title = "Association";
        return view('user.association.index', compact('title','header'));
    }

    public function getJsonData(Request $request)
    {
        $user = Auth::user();
        $columns = array(
            0 => 'associations.association_name',
            1 => 'president',
        );


        $header = $request->input('header');
        $country = $request->input('country');
        $limit = $request->input('length');
        $start = $request->input('start');
        $search = $request->input('search.value');
        $draw = $request->input('draw');
        $order = $columns[$request->input('order.0.column')];
        $dir = $request->input('order.0.dir');

        try {           
          

            $query = Association::leftJoin('association_users','association_users.association_id','associations.id')
                ->whereIn('association_users.type',[AssociationUsers::PRESIDENT,AssociationUsers::MANAGER])
                ->where('association_users.user_id',$user->id)
                ->groupBy('associations.id')
                ->select('associations.*');

            $totalData = $query->count();
            $totalFiltered = $totalData;

            /* if(!empty($country)){
                $query = $query->where('country_id', $country);
            } */

            if (!empty($search)) {
                $query = $query->where('associations.association_name', 'LIKE', "%{$search}%");
                $totalFiltered = $query->count();
            }


            $association = $query->offset($start)
                ->limit($limit)
                ->orderBy($order, $dir)
                ->get();

            $data = array();
            if (!empty($association)) {
                foreach ($association as $value) {

                    $id = $value->id;

                    if(!empty($header) && strpos($header, 'business') === 0){
                        $show = route('business.association.show', $id);      
                    }else{
                        $show = route('user.association.show', $id);         
                    }
                               
        
                    $nestedData['association_name'] = $value->association_name;
                    
                    $showButton =  "<a href='".$show."' class='btn btn-primary'><i class='fa fa-eye'></i></a>";

                    $nestedData['actions'] = "$showButton";
                    $data[] = $nestedData;
                }
            }
            $jsonData = array(
                "draw" => intval($draw),
                "recordsTotal" => intval($totalData),
                "recordsFiltered" => intval($totalFiltered),
                "data" => $data,
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


    public function show(Association $association,$header){

        $title = "Association";

        return view('user.association.show', compact('title','association','header'));
    }

    public function getUserJsonData(Request $request)
    {
        $user = Auth::user();
        $columns = array(
            0 => 'users_detail.name',
            1 => 'order'
        );

        $association_id = $request->input('association_id');
        $filter = $request->input('filter');
        $limit = $request->input('length');
        $start = $request->input('start');
        $search = $request->input('search.value');
        $draw = $request->input('draw');
        $order = $columns[$request->input('order.0.column')];
        $dir = $request->input('order.0.dir');


       
        $applyFilter = ($filter == 'member-kick') ? AssociationUsers::MEMBER : $filter;
        try {           
            $userQuery = AssociationUsers::leftjoin('users_detail','users_detail.user_id','association_users.user_id')
                ->leftjoin('user_entity_relation', function($query) {
                    $query->on('user_entity_relation.user_id','=','association_users.user_id')
                        ->whereIn('user_entity_relation.entity_type_id',  [EntityTypes::HOSPITAL, EntityTypes::SHOP]);
                })
                ->leftjoin('shops', function($query) {
                    $query->on('users_detail.user_id','=','shops.user_id')
                        ->whereRaw('shops.id IN (select MAX(a2.id) from shops as a2 group by a2.user_id)');
                })
                ->leftjoin('hospitals','user_entity_relation.entity_id','hospitals.id')
                ->leftjoin('addresses', function ($join) {
                    $join->on('user_entity_relation.entity_id', '=', 'addresses.entity_id')
                         ->whereIn('addresses.entity_type_id',  [EntityTypes::HOSPITAL, EntityTypes::SHOP]);
                })
                ->where('association_id',$association_id)
                ->where(function ($query) use ($applyFilter,$filter) {
                    if($filter == 'member-kick'){
                        $query->where('is_kicked',1);
                    }else{
                        $query->where('is_kicked',0);
                    }
                })
                ->where('type',$applyFilter)
                ->select(
                    'users_detail.name',
                    'users_detail.mobile',
                    \DB::raw('(CASE 
                        WHEN user_entity_relation.entity_type_id = 1 THEN  shops.main_name
                        WHEN user_entity_relation.entity_type_id = 2 THEN hospitals.main_name 
                        ELSE "" 
                    END) AS main_name'),
                    'addresses.*',
                    'association_users.*'
                )
                ->groupBy('association_users.user_id');
                
            $totalData = count($userQuery->get());
            $totalFiltered = $totalData;

            if (!empty($search)) {
                $userQuery = $userQuery->where('users_detail.name', 'LIKE', "%{$search}%");
                $totalFiltered = $userQuery->count();
            }

            $userData = $userQuery->offset($start)
                ->limit($limit)
                ->orderBy($order, $dir)
                ->get();

            $data = array();
 
            if (!empty($userData)) {
                foreach ($userData as $value) {

                    $id = $value->id;

                    $address = $value->address;
                    $address .= $value->address2 ? ','.$value->address2 : '';
                    $address .= $value->city_name ? ','.$value->city_name : '';
                    $address .= $value->state_name ? ','.$value->state_name : '';
                    $address .= $value->country_name ? ','.$value->country_name : '';

                    $nestedData['name'] = $value->name;
                    $nestedData['activate_name'] = $value->main_name;
                    $nestedData['address'] = $address;
                    $nestedData['phone'] = $value->mobile;
                    $nestedData['date'] = ($filter == 'member-kick') ? date('Y-m-d H:i:s',strtotime($value->updated_at)) : date('Y-m-d H:i:s',strtotime($value->created_at));
                    
                    $data[] = $nestedData;
                }
            }
            $jsonData = array(
                "draw" => intval($draw),
                "recordsTotal" => intval($totalData),
                "recordsFiltered" => intval($totalFiltered),
                "data" => $data,
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



}
