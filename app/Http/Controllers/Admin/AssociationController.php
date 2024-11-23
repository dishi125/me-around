<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Log;
use Storage;
use Validator;
use App\Models\Association;
use App\Models\AssociationCommunity;
use App\Models\AssociationCommunityComment;
use App\Models\AssociationCommunityImage;
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
        $this->middleware('permission:association-list', ['only' => ['index']]);
        $this->firebase = new Firebase();
    }

    public function index()
    {
        $title = "Association";
        $countries = Country::WhereNotNull('code')->where('is_show',1)->get();

        $countries = collect($countries)->mapWithKeys(function ($value) {
            return [$value->id => $value->name];
        })->toArray();
        $countryData = array_merge(['0'=>'All'],$countries);

        return view('admin.association.index', compact('title','countryData'));
    }

    /**
    * Show the form for creating a new resource.
    *
    * @return \Illuminate\Http\Response
    */
    public function form($id = '')
    {
        $title = (!empty($id)) ? "Edit Association" : "Add Association";
        $associationData = $managers = $members = $images = [];
        $president = '';

        if(!empty($id)){
            $associationData = Association::where('id',$id)->first();

            $managers = DB::table('association_users')->leftjoin('users_detail','users_detail.user_id','association_users.user_id')->where(['association_id' => $id,'type' => AssociationUsers::MANAGER])->pluck('association_users.user_id')->toArray();

            $members = DB::table('association_users')->leftjoin('users_detail','users_detail.user_id','association_users.user_id')->where(['association_id' => $id,'type' => AssociationUsers::MEMBER])->pluck('association_users.user_id')->toArray();

            $president = DB::table('association_users')->leftjoin('users_detail','users_detail.user_id','association_users.user_id')->where(['association_id' => $id,'type' => AssociationUsers::PRESIDENT])->pluck('association_users.user_id');

            $images = AssociationImage::select('id','image')->where('associations_id',$id)->get();


        }

        $allUser = DB::table('users')->leftJoin('user_entity_relation','user_entity_relation.user_id','users.id')
        ->leftJoin('users_detail','users_detail.user_id','users.id')
        ->leftJoin('managers','managers.user_id','users.id')
        ->whereIN('user_entity_relation.entity_type_id', [EntityTypes::NORMALUSER, EntityTypes::HOSPITAL, EntityTypes::SHOP])
        ->whereNotNull('users.email')
        ->whereNull('users.deleted_at')
        ->whereIn('users.status_id', [Status::ACTIVE, Status::INACTIVE])
        ->select(
            'users.id',
            DB::raw("CONCAT(managers.name,'-',users_detail.mobile) AS full_name_old"),
            DB::raw('(CASE
                WHEN user_entity_relation.entity_type_id IN (1,2,3) THEN CONCAT(users_detail.name,"-",users_detail.mobile)
                WHEN user_entity_relation.entity_type_id IN (4,5,6) THEN CONCAT(managers.name,"-",IFNULL(managers.mobile,""))
                ELSE ""
            END) AS full_name')
        )
        ->groupBy('users.id')->pluck('full_name','users.id');

        $countries = Country::WhereNotNull('code')->where('is_show',1)->get();
        $countries= collect($countries)->mapWithKeys(function ($value) {
            return [$value->id => $value->name];
        })->toArray();

        return view('admin.association.form', compact('title','allUser','countries','associationData','managers','members','president','images'));
    }

    public function manageAssociation($id = '')
    {
        $title = (!empty($id)) ? "Edit Association" : "Add Association";
        $associationData = $managers = $members = $supporter = $images = [];
        $president = '';

        if(!empty($id)){
            $associationData = Association::where('id',$id)->first();

            $managers = DB::table('association_users')->leftjoin('users_detail','users_detail.user_id','association_users.user_id')->where(['association_id' => $id,'type' => AssociationUsers::MANAGER])->pluck('association_users.user_id')->toArray();

            $members = DB::table('association_users')->leftjoin('users_detail','users_detail.user_id','association_users.user_id')->where(['association_id' => $id,'type' => AssociationUsers::MEMBER])->pluck('association_users.user_id')->toArray();

            $supporter = DB::table('association_users')->leftjoin('users_detail','users_detail.user_id','association_users.user_id')->where(['association_id' => $id,'type' => AssociationUsers::SUPPORTER])->pluck('association_users.user_id')->toArray();

            $president = DB::table('association_users')->leftjoin('users_detail','users_detail.user_id','association_users.user_id')->where(['association_id' => $id,'type' => AssociationUsers::PRESIDENT])->pluck('association_users.user_id');

            $images = AssociationImage::select('id','image')->where('associations_id',$id)->get();


        }

        $allUser = DB::table('users')->leftJoin('user_entity_relation','user_entity_relation.user_id','users.id')
        ->leftJoin('users_detail','users_detail.user_id','users.id')
        ->leftJoin('managers','managers.user_id','users.id')
        ->whereIN('user_entity_relation.entity_type_id', [EntityTypes::NORMALUSER, EntityTypes::HOSPITAL, EntityTypes::SHOP])
        ->whereNotNull('users.email')
        ->whereNull('users.deleted_at')
        ->whereIn('users.status_id', [Status::ACTIVE, Status::INACTIVE])
        ->select(
            'users.id',
            DB::raw("CONCAT(managers.name,'-',users_detail.mobile) AS full_name_old"),
            DB::raw('(CASE
                WHEN user_entity_relation.entity_type_id IN (1,2,3) THEN CONCAT(users_detail.name,"-",users_detail.mobile)
                WHEN user_entity_relation.entity_type_id IN (4,5,6) THEN CONCAT(managers.name,"-",IFNULL(managers.mobile,""))
                ELSE ""
            END) AS full_name')
        )
        ->groupBy('users.id')->pluck('full_name','users.id');


        $allSupperters = DB::table('users')->leftJoin('user_entity_relation','user_entity_relation.user_id','users.id')
            ->leftJoin('users_detail','users_detail.user_id','users.id')
            ->leftJoin('managers','managers.user_id','users.id')
            ->whereIN('user_entity_relation.entity_type_id', [EntityTypes::ADMIN, EntityTypes::MANAGER, EntityTypes::SUBMANAGER])
            ->whereNotNull('users.email')
            ->whereNull('users.deleted_at')
            ->whereIn('users.status_id', [Status::ACTIVE, Status::INACTIVE])
            ->select(
                'users.id',
                DB::raw("CONCAT(managers.name,'-',users_detail.mobile) AS full_name_old"),
                DB::raw('(CASE
                    WHEN user_entity_relation.entity_type_id IN (1,2,3) THEN CONCAT(users_detail.name,"-",users_detail.mobile)
                    WHEN user_entity_relation.entity_type_id IN (4,5,6) THEN CONCAT(managers.name,"-",IFNULL(managers.mobile,""))
                    ELSE ""
                END) AS full_name')
            )
            ->groupBy('users.id')->pluck('full_name','users.id');

        $countries = Country::WhereNotNull('code')->where('is_show',1)->get();
        $countries= collect($countries)->mapWithKeys(function ($value) {
            return [$value->id => $value->name];
        })->toArray();

        return view('admin.association.manage', compact('title','allUser','countries','associationData','managers','members','president','images', 'supporter','allSupperters'));
    }


    public function saveAssociates(Request $request)
    {
        DB::beginTransaction();
        $inputs = $request->all();
        $message = '';
        $code = !empty($inputs['association_code']) ? $inputs['association_code'] : '';
        $associationId = !empty($inputs['association_id']) ? $inputs['association_id'] : '';

        $validator = Validator::make($request->all(), [
            'country' => 'required',
            'association_name' => 'required',
            'president' => 'required',
            'manager' => 'required',
            'member' => 'required',
            "main_language_image"    => "required|array",
            'association_code' => "required|integer|digits:4|unique:associations,code,".$associationId,
            'type' => 'required'
        ], [], [
            'association_name' => 'Association Name',
            'president' => 'President',
            'manager' => 'Manager',
            'member' => 'Member',
            'main_language_image' => 'Image',
            'association_code' => 'Code',
            'type' => 'Type'
        ]);
        if ($validator->fails()) {
            return response()->json(array(
                'success' => false,
                'errors' => $validator->getMessageBag()->toArray()

            ), 400);
        }

        try {
            $president = $inputs['president'];
            $manager = $inputs['manager'] ?? [];
            $member = $inputs['member'] ?? [];
            $supporter = $inputs['supporter'] ?? [];
            $type = $inputs['type'];

            $oldPresident = $oldManagers = $oldMember = $oldSupporter = [];

            $data = [
                "association_name" => $inputs['association_name'],
                "country_id" => $inputs['country'],
                "code" => $inputs['association_code'],
                "type" => $type,
                "description" => $inputs['description'] ?? null
            ];


            if(!empty($associationId)){
                $association = Association::where('id',$associationId)->update($data);

                $oldPresident = AssociationUsers::where('association_id',$associationId)->where('type', AssociationUsers::PRESIDENT)->pluck('user_id')->toArray();
                $oldManagers = AssociationUsers::where('association_id',$associationId)->where('type', AssociationUsers::MANAGER)->pluck('user_id')->toArray();
                $oldMember = AssociationUsers::where('association_id',$associationId)->where('type', AssociationUsers::MEMBER)->pluck('user_id')->toArray();
                $oldSupporter = AssociationUsers::where('association_id',$associationId)->where('type', AssociationUsers::SUPPORTER)->pluck('user_id')->toArray();

                $message = 'Association updated successfully';
            }else{
                $association = Association::create($data);
                $associationId = $association->id;
                $message = 'Association added successfully';
            }


            $diffPresident = array_diff([$president],$oldPresident);
            $diffManager = array_diff($manager,$oldManagers);
            $diffMember = array_diff($member,$oldMember);
            $diffSupporter = array_diff($supporter,$oldSupporter);

            //Removed
            $removedManager = array_diff($oldManagers, $manager);
            $removedMember = array_diff($oldMember, $member);
            $removedSupporter = array_diff($oldSupporter, $supporter);


            if(!empty($associationId)){

                if(!empty($president)){

                    AssociationUsers::where([
                        'association_id' => $associationId,
                        'type' => AssociationUsers::PRESIDENT,
                    ])->delete();

                    $presidentData = [
                        'association_id' => $associationId,
                        'type' => AssociationUsers::PRESIDENT,
                        'user_id' => $president
                    ];

                    AssociationUsers::create($presidentData);
                }

                if(!empty($diffManager)){
                    AssociationUsers::where( 'association_id' , $associationId)
                        ->where('type' , AssociationUsers::MANAGER)
                        ->whereIn('user_id',$removedManager)
                        ->delete();
                }

                if(!empty($diffManager)){

                    foreach($diffManager as $mKey => $mVal){
                        $managerData = [
                            'association_id' => $associationId,
                            'type' => AssociationUsers::MANAGER,
                            'user_id' => $mVal
                        ];
                        AssociationUsers::create($managerData);
                    }
                }

                if(!empty($removedMember)){
                    AssociationUsers::where( 'association_id' , $associationId)
                        ->where('type' , AssociationUsers::MEMBER)
                        ->whereIn('user_id',$removedMember)
                        ->delete();
                }
                if(!empty($diffMember)){

                    foreach($diffMember as $meKey => $meVal){
                        $memberData = [
                            'association_id' => $associationId,
                            'type' => AssociationUsers::MEMBER,
                            'user_id' => $meVal
                        ];
                        AssociationUsers::create($memberData);
                    }
                }


                if(!empty($removedSupporter)){
                    AssociationUsers::where( 'association_id' , $associationId)
                    ->where('type' , AssociationUsers::SUPPORTER)
                    ->whereIn('user_id',$removedSupporter)
                    ->delete();
                }

                if(!empty($diffSupporter)){

                    foreach($diffSupporter as $meKey => $meVal){
                        $memberData = [
                            'association_id' => $associationId,
                            'type' => AssociationUsers::SUPPORTER,
                            'user_id' => $meVal
                        ];
                        AssociationUsers::create($memberData);
                    }
                }

                $associationFolder = config('constant.association').'/'.$associationId;

                if (!Storage::disk('s3')->exists($associationFolder)) {
                    Storage::disk('s3')->makeDirectory($associationFolder);
                }

                if(!empty($inputs['main_language_image'])){
                    foreach($inputs['main_language_image'] as $image) {
                        if(is_file($image)){
                            $mainImage = Storage::disk('s3')->putFile($associationFolder, $image,'public');
                            $fileName = basename($mainImage);
                            $image_url = $associationFolder . '/' . $fileName;
                            $temp = [
                                'associations_id' => $associationId,
                                'image' => $image_url
                            ];
                            AssociationImage::create($temp);
                        }
                    }
                }
            }


            // Send Notification

            if(!empty($diffPresident)){
                $this->sendNotificationToManagerAndPresident($diffPresident,Notice::BECAME_PRESIDENT,$associationId,$inputs['association_name']);
            }

            if(!empty($diffManager)){
                $this->sendNotificationToManagerAndPresident($diffManager,Notice::BECAME_MANAGER,$associationId,$inputs['association_name']);
            }


            // Send Notification End

            DB::commit();

            $jsonData = array(
                'success' => true,
                'message' => $message,
            );
        } catch (\Exception $e) {
            Log::info($e);
            DB::rollBack();
            $jsonData = array(
                'success' => false,
                'message' => ''
            );
        }

        return response()->json($jsonData);
    }

    public function sendNotificationToManagerAndPresident($userIds,$noticeKey,$association_id,$association_name){
        foreach($userIds as $uId){
            $devices = UserDevices::whereIn('user_id', [$uId])->pluck('device_token')->toArray();
            $user_detail = UserDetail::where('user_id', $uId)->first();
            $language_id = $user_detail->language_id ?? 4;
            $key = "language_$language_id.".$noticeKey;
            $title_msg = __("messages.$key");
            $format = '';
            $notify_type = $noticeKey;

            Notice::create([
                'notify_type' => $notify_type,
                'user_id' => $uId,
                'to_user_id' => $uId,
                'entity_type_id' => EntityTypes::ASSOCIATION_COMMUNITY,
                'entity_id' => $association_id,
                'title' => $title_msg,
                'sub_title' => $association_name,
            ]);
            if (count($devices) > 0) {
                $result = $this->sentPushNotification($devices,$title_msg, $format, [] ,$notify_type, $association_id);
            }
        }
    }

    public function getJsonData(Request $request)
    {
        $user = Auth::user();
        $columns = array(
            0 => 'association_name',
            1 => 'president',
            2 => 'manager',
            3 => 'member'
        );


        $country = $request->input('country');
        $limit = $request->input('length');
        $start = $request->input('start');
        $search = $request->input('search.value');
        $draw = $request->input('draw');
        $order = $columns[$request->input('order.0.column')];
        $dir = $request->input('order.0.dir');

        try {


            $query = Association::leftJoin('association_users as au','au.id','associations.id')->select('associations.*');

            $totalData = $query->count();
            $totalFiltered = $totalData;

            if(!empty($country)){
                $query = $query->where('country_id', $country);
            }

            if (!empty($search)) {
                $query = $query->where('association_name', 'LIKE', "%{$search}%");
                $totalFiltered = $query->count();
            }

            $association = $query->offset($start)
                ->limit($limit)
                ->orderBy($order, $dir)
                ->get();

            $data = array();
            DB::enableQueryLog();
            if (!empty($association)) {
                foreach ($association as $value) {

                    $id = $value->id;
                    $delete = route('admin.association.show', $id);
                    $show = route('admin.association.show', $id);
                    $manage = route('admin.association.manage', $id);

                    $getManagers = AssociationUsers::select('users_detail.name')->leftjoin('users_detail','users_detail.user_id','association_users.user_id')->where(['association_id' => $id,'type' => AssociationUsers::MANAGER])->get()->toArray();
                    $managers = implode(',',array_column($getManagers,'name'));

                    $members = AssociationUsers::leftjoin('users_detail','users_detail.user_id','association_users.user_id')->where(['association_id' => $id,'type' => AssociationUsers::MEMBER])->count();

                    $getPresident = AssociationUsers::select('users_detail.name')->leftjoin('users_detail','users_detail.user_id','association_users.user_id')->where(['association_id' => $id,'type' => AssociationUsers::PRESIDENT])->get()->toArray();
                    $president = implode(',',array_column($getPresident,'name'));


                    $nestedData['association_name'] = $value->association_name;
                    $nestedData['president'] = $president;
                    $nestedData['manager'] = $managers;
                    $nestedData['member'] = $members;
                    $nestedData['status'] = ucfirst($value->type);

                    $editButton =  "<a href='$manage' class='btn btn-primary'><i class='fa fa-edit'></i></a>";

                    $showButton =  "<a href='".$show."' class='btn btn-primary'><i class='fa fa-eye'></i></a>";

                    $deleteButton =  "<a href='javascript:void(0);' onClick='deleteAssociationConfirmation(".$id.")' class='btn btn-primary'><i class='fas fa-trash-alt'></i></i></a>";

                    $nestedData['actions'] = "$editButton $showButton $deleteButton";
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

            $jsonData = array(
                "draw" => intval($draw),
                "recordsTotal" => 0,
                "recordsFiltered" => 0,
                "data" => []
            );
            return response()->json($jsonData);
        }
    }

    public function getDeleteAssociation($id){
        return view('admin.association.delete-account', compact('id'));
    }

    public function deleteAssociation(Request $request){
        $inputs = $request->all();
        $association_id = $inputs['association_id'];
        DB::beginTransaction();

        try{

            AssociationUsers::where('association_id',$association_id)->delete();
            $associationCommunity = AssociationCommunity::where('associations_id',$association_id)->get();

            foreach($associationCommunity as $community){
                AssociationCommunityComment::where('community_id',$community->id)->delete();
                AssociationCommunityImage::where('community_id',$community->id)->delete();
                $community->delete();
            }

            $images = AssociationImage::where('associations_id',$association_id)->get();
            foreach($images as $img){
                Storage::disk('s3')->delete($img->image);
                $img->delete();
            }
            $association = Association::whereId($association_id)->delete();

            DB::commit();
            $jsonData = array(
                'success' => true,
                'message' => 'Association deleted successfully.',
            );
            return response()->json($jsonData);
        } catch (\Exception $e) {
            DB::rollBack();
            $jsonData = array(
                'success' => false,
                'message' => 'Error in Association delete',
            );
            return response()->json($jsonData);
        }
    }

    public function removeImage(Request $request){
        $inputs = $request->all();
        $imageid = $inputs['imageid'] ?? '';

        if(!empty($imageid)){
            $image = AssociationImage::whereId($imageid)->first();
            if($image){
                Storage::disk('s3')->delete($image->image_path);
                AssociationImage::where('id',$image->id)->delete();
            }
        }
    }

    public function show(Association $association){

        $title = "View";

        $getManagers = AssociationUsers::select('users_detail.name')->leftjoin('users_detail','users_detail.user_id','association_users.user_id')->where(['association_id' => $association->id,'type' => AssociationUsers::MANAGER])->get()->toArray();
        $managers = implode(',',array_column($getManagers,'name'));

        $getMembers = AssociationUsers::leftjoin('users_detail','users_detail.user_id','association_users.user_id')->where(['association_id' => $association->id,'type' => AssociationUsers::MEMBER])->get()->toArray();
        $members = implode(',',array_column($getMembers,'name'));

        $getPresident = AssociationUsers::select('users_detail.name')->leftjoin('users_detail','users_detail.user_id','association_users.user_id')->where(['association_id' => $association->id,'type' => AssociationUsers::PRESIDENT])->get()->toArray();
        $president = implode(',',array_column($getPresident,'name'));

        $getSupporter = AssociationUsers::select('managers.name')->leftjoin('managers','managers.user_id','association_users.user_id')->where(['association_id' => $association->id,'type' => AssociationUsers::SUPPORTER])->get();
        $supporter = collect($getSupporter)->implode('name', ', ');

        return view('admin.association.show', compact('title','association','managers','members','president','supporter'));
    }

    public function getCatrgoryJsonData(Request $request)
    {
        $user = Auth::user();
        $columns = array(
            0 => 'name',
            1 => 'order'
        );

        $association_id = $request->input('association_id');
        $limit = $request->input('length');
        $start = $request->input('start');
        $search = $request->input('search.value');
        $draw = $request->input('draw');
        $order = $columns[$request->input('order.0.column')];
        $dir = $request->input('order.0.dir');

        try {
            $query = AssociationCategory::where('associations_id',$association_id);

            $totalData = $query->count();
            $totalFiltered = $totalData;

            if (!empty($search)) {
                $query = $query->where('name', 'LIKE', "%{$search}%");
                $totalFiltered = $query->count();
            }

            $category = $query->offset($start)
            ->limit($limit)
            ->orderBy($order, $dir)
            ->get();

            $data = array();

            if (!empty($category)) {
                foreach ($category as $value) {

                    $id = $value->id;
                    $nestedData['name'] = $value->name;
                    $nestedData['order'] = $value->order;

                    $editButton =  "<a href='javascript:void(0)' onclick='associateCategoryForm($association_id,$id)' class='btn btn-primary'><i class='fa fa-edit'></i></a>";

                    $deleteButton = "<a href='javascript:void(0)' role='button' onclick='deleteAssociationCategory(" . $id . ")' class='btn btn-danger' data-toggle='tooltip' data-original-title=" . Lang::get('general.delete') . "><i class='fa fa-trash'></button>";

                    $nestedData['actions'] = "$editButton $deleteButton";
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

            $jsonData = array(
                "draw" => intval($draw),
                "recordsTotal" => 0,
                "recordsFiltered" => 0,
                "data" => []
            );
            return response()->json($jsonData);
        }
    }

    /**
    * Show the form for creating a new resource.
    *
    * @return \Illuminate\Http\Response
    */
    public function categoryForm($association,$id = '')
    {
        $title = (!empty($id)) ? 'Edit Association Category' : 'Add Association Category';
        $data = '';

        if(!empty($id)){
            $data = AssociationCategory::find($id);
        }

        return view('admin.association.category-form', compact('title','association','data'));
    }

    public function saveAssociatesCategory(Request $request)
    {
        DB::beginTransaction();
        $inputs = $request->all();
        $message = '';

        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'order' => 'required',
        ], [], [
            'name' => 'Name',
            'order' => 'Order',
        ]);
        if ($validator->fails()) {
            return response()->json(array(
                'success' => false,
                'errors' => $validator->getMessageBag()->toArray()

            ), 400);
        }

        try {

            $id = $inputs['category_id'] ?? NULL;
            $association_id = $inputs['association_id'] ?? NULL;
            $data = [
                "name" => $inputs['name'],
                "order" => $inputs['order'],
                "can_post" => isset($request->can_post) ? 1 : 0
            ];

            if(!empty($id)){
                $association = AssociationCategory::where('id',$id)->update($data);
                $message = 'Association category updated successfully';
            }else{

                $data["associations_id"] = $association_id;
                $association = AssociationCategory::create($data);
                $message = 'Association category added successfully';
            }

            DB::commit();
            $jsonData = array(
                'success' => true,
                'message' => $message,
            );
        } catch (\Exception $e) {
            DB::rollBack();
            $jsonData = array(
                'success' => false,
                'message' => ''
            );
        }

        return response()->json($jsonData);
    }

    public function getDeleteCategory($id)
    {
        $title = "Association Category";
        return view('admin.association.delete', compact('id','title'));
    }

    public function destroyCategory($id)
    {
        AssociationCategory::where('id',$id)->delete();
        DB::commit();
        $jsonData = [
            'status_code' => 200,
            'message' => trans("messages.associations.category-deleted")
        ];
        return response()->json($jsonData);

    }






}
