<?php

namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\ReportClient;
use App\Models\ReportTypes;
use App\Models\BasicMentions;
use App\Models\EntityTypes;
use App\Models\UserEntityRelation;
use App\Models\ShopImagesTypes;
use App\Models\Post;
use App\Models\ShopImages;
use App\Models\UserDetail;
use App\Models\User;
use App\Models\Community;
use App\Models\Shop;
use App\Models\Hospital;
use App\Models\Notice;
use App\Models\Category;
use App\Models\CategoryTypes;
use App\Models\ReviewCommentReply;
use App\Models\ReviewComments;
use App\Models\Reviews;
use App\Models\CommunityComments;
use App\Models\CommunityCommentReply;
use App\Models\RequestedCustomer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use Log;
class ReportedClientController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:reported-client-list', ['only' => ['index','indexShop','indexCommunity','indexUser']]);
    }
/* ================ Hospital Code Start ======================= */
    public function index()
    {
        $title = 'Reported Client Hospital';     
        $basic_mentions = BasicMentions::pluck('value','name');
        $categoryList = Category::where('category_type_id',CategoryTypes::REPORT)->get();

        return view('admin.reported-client.index-hospital', compact('title','basic_mentions','categoryList'));
    }

    public function getJsonAllHospitalData(Request $request)
    {
        try {  
            Log::info('Start reported hospital list');
            $user = Auth::user();
            $columns = array(
                0 => 'id',
                1 => 'report_type_id',
                2 => 'report_item_category',
                3 => 'category_name',
                7 => 'status_count',
                8 => 'report_clients.created_at',
            );

            $limit = $request->input('length');
            $start = $request->input('start');
            $search = $request->input('search.value');
            $draw = $request->input('draw');
            $order = $columns[$request->input('order.0.column')];
            $dir = $request->input('order.0.dir');
            $adminTimezone = $this->getAdminUserTimezone();

            $query = ReportClient::leftJoin('category','category.id','report_clients.category_id')
                        ->leftJoin('users_detail','users_detail.user_id','report_clients.reported_user_id')
                                    ->whereIn('report_type_id',[ReportTypes::HOSPITAL,ReportTypes::HOSPITAL_PLACE])
                                    ->select(['report_clients.*','category.name as category', 'users_detail.mobile']);
            if($request->category){
                $query = $query->where('report_clients.category_id',$request->category);  
            }
                                    
            $totalData = $query->count();

            $totalFiltered = $totalData;

            if (!empty($search)) {
                $query = $query->where('category.name', 'LIKE', "%{$search}%");
                $totalFiltered = $query->count();
            }

            $reportedHospitals = $query->offset($start)
                ->limit($limit)
                //->orderBy('report_clients.id', 'desc')
                ->orderBy($order, $dir)
                ->get();
            
            if (!empty($reportedHospitals)) {
                foreach ($reportedHospitals as $value) {
                    //$value->reported_count = collect($reportedHospitals)->where('entity_id',$value['entity_id'])->sum('status_count');
                    $value->reported_count = DB::table('report_clients')->where('entity_id',$value['entity_id'])->where('report_type_id',$value['report_type_id'])->whereNull('deleted_at')->sum('status_count');
                }
            }
            if($order == 'status_count'){
                if($dir == 'asc'){
                    $reportedHospitals = collect($reportedHospitals)->sortBy('reported_count');
                }else{
                    $reportedHospitals = collect($reportedHospitals)->sortByDesc('reported_count');
                }
            }

            $data = array();
            if (!empty($reportedHospitals)) {
                foreach ($reportedHospitals as $value) {
                    $id = $value['id'];
                    $nestedData['checkbox'] = "<td><div class=\"custom-checkbox custom-control\">
                    <input type=\"checkbox\" data-checkboxes=\"mygroup\" class=\"custom-control-input checkbox_id check-hospital\" id=\"$id\" data-id=\"$id\" value=\"$id\" name=\"checkbox_id[]\"><label for=\"$id\" class=\"custom-control-label\">&nbsp;</label></div></td>";
                   
                    $nestedData['report_item_name'] = $value['report_item_name'];
                    $nestedData['mobile'] = $value['mobile'];
                    $nestedData['report_item_category'] = $value['report_item_category'];
                    $nestedData['category_name'] = $value['category_name'];                   
                    $nestedData['date'] = $this->formatDateTimeCountryWise($value['display_created_at'],$adminTimezone,'d-m-Y H:i');   
                    $nestedData['status'] = $value['reported_count'];   
                    $goRoute = route('admin.business-client.hospital.show', $value['entity_id']);
                    $goButton = "<a role='button' href='".$goRoute."' title='' data-original-title='Go' class='btn btn-primary' data-toggle='tooltip'>Go</a>";
                    $warningRoute = route('admin.reported-client.warning-user', $id);
                    $warningButton = "<a role='button' href='".$warningRoute."' title='' data-original-title='Warning' class='mx-1 btn btn-primary btn-sm' data-toggle='tooltip'>Warning</a>";
                    $deletePostButton = '';
                    if($value['report_type_id'] != ReportTypes::HOSPITAL_PLACE){
                        $deletePostButton = "<a role='button' href='javascript:void(0)' onclick='deletePost(" . $id . ")' title='' data-original-title='Delete Post' class='mx-1 btn btn-primary btn-sm' data-toggle='tooltip'>Delete Post</a>";
                    }

                    $deleteAllPostButton = "<a role='button' href='javascript:void(0)' onclick='deleteAllPost(" . $id . ")' title='' data-original-title='Delete All Post' class='mx-1 btn btn-primary btn-sm' data-toggle='tooltip'>Delete All Post</a>";
                    $deleteAccountButton = "<a role='button' href='javascript:void(0)' onclick='deleteUser(" . $id . ")' title='' data-original-title='Delete Account' class='mx-1 btn btn-primary btn-sm' data-toggle='tooltip'>Delete Account</a>";
                    $nestedData['actions'] = "<div class='d-flex'>$warningButton $deletePostButton $deleteAllPostButton $deleteAccountButton</div>";                    
                    $nestedData['go_actions'] = "<div class='d-flex'>$goButton</div>";     
                    $deleteButton = "<a href='javascript:void(0)' role='button' onclick='deleteReport(" . $id . ")' class='btn btn-danger' data-toggle='tooltip' data-original-title=" . Lang::get('general.delete') . "><i class='fa fa-trash'></i></button>";
                    $nestedData['delete_actions'] = $deleteButton;     
                    $data[] = $nestedData;
                }
            }
            $jsonData = array(
                "draw" => intval($draw),
                "recordsTotal" => intval($totalData),
                "recordsFiltered" => intval($totalFiltered),
                "data" => $data,
                "order" => $order,
            );
            Log::info('End reported hospital list');
            return response()->json($jsonData);
        } catch (\Exception $ex) {
            Log::info('Exception reported hospital list');
            Log::info($ex);
            return response()->json([]);
        }
    }    

/* ================ Hospital Code End ======================= */

    public function getAll()
    {
        $title = 'Reported Client';     

        $reportedClientCount = DB::table('report_clients')->whereNull('deleted_at')
                    ->where('is_admin_read',1)
                    ->update(['is_admin_read' => 0]);

        $basic_mentions = BasicMentions::pluck('value','name');
        $categoryList = Category::where('category_type_id',CategoryTypes::REPORT)->get();

        return view('admin.reported-client.index', compact('title','basic_mentions','categoryList'));
    }

    public function getJsonAllData(Request $request)
    {
        try {  
            Log::info('Start reported hospital list');
            $user = Auth::user();
            $columns = array(
                0 => 'id',
                1 => 'report_type_id',
                2 => 'report_item_category',
                3 => 'category_name',
                7 => 'status_count',
                8 => 'report_clients.created_at',
            );

            $limit = $request->input('length');
            $start = $request->input('start');
            $search = $request->input('search.value');
            $draw = $request->input('draw');
            $order = $columns[$request->input('order.0.column')];
            $dir = $request->input('order.0.dir');
            $adminTimezone = $this->getAdminUserTimezone();

            $deletedPosts = DB::table('report_clients')->join('community','community.id','report_clients.entity_id')
                ->whereNotNull('community.deleted_at')
                ->where('report_type_id',ReportTypes::COMMUNITY)
                ->select('report_clients.id')->get();

            $deletedReviewPosts = DB::table('report_clients')->join('reviews','reviews.id','report_clients.entity_id')
                ->whereNotNull('reviews.deleted_at')
                ->where('report_type_id',ReportTypes::REVIEWS)
                ->select('report_clients.id')->get();

            $allDeleteIds =  collect($deletedPosts)->merge(collect($deletedReviewPosts))->map(function ($item, $key) {
                    return $item->id;
                });

            $query = ReportClient::leftJoin('category','category.id','report_clients.category_id')
                ->leftJoin('users_detail','users_detail.user_id','report_clients.reported_user_id')
                ->whereNotIn('report_clients.id',$allDeleteIds)
                ->whereIn('report_clients.report_type_id',[ReportTypes::HOSPITAL, ReportTypes::SHOP, ReportTypes::SHOP_PORTFOLIO,  ReportTypes::REVIEWS, ReportTypes::SHOP_USER, ReportTypes::COMMUNITY, ReportTypes::SHOP_PLACE, ReportTypes::HOSPITAL_PLACE])
                ->select(['report_clients.*','category.name as category', 'users_detail.mobile']);
                

            if($request->category){
                $query = $query->where('report_clients.category_id',$request->category);  
            }
                                    
            $totalData = $query->count();

            $totalFiltered = $totalData;

            if (!empty($search)) {
                $query = $query->where('category.name', 'LIKE', "%{$search}%");
                $totalFiltered = $query->count();
            }

            $reportedHospitals = $query->offset($start)
                ->limit($limit)
               // ->orderBy('report_clients.id', 'desc')
                ->orderBy($order, $dir)
                ->get();

            if (!empty($reportedHospitals)) {
                foreach ($reportedHospitals as $value) {
                    $value->reported_count = DB::table('report_clients')->where('entity_id',$value['entity_id'])->where('report_type_id',$value['report_type_id'])->whereNull('deleted_at')->sum('status_count');
                }
            }
            if($order == 'status_count'){
                if($dir == 'asc'){
                    $reportedHospitals = collect($reportedHospitals)->sortBy('reported_count');
                }else{
                    $reportedHospitals = collect($reportedHospitals)->sortByDesc('reported_count');
                }
            }
            $data = array();
            if (!empty($reportedHospitals)) {
                foreach ($reportedHospitals as $value) {
                    $deletePostButton = $deleteAllPostButton = '';
                    $id = $value['id'];
                    $nestedData['checkbox'] = "<td><div class=\"custom-checkbox custom-control\">
                    <input type=\"checkbox\" data-checkboxes=\"mygroup\" class=\"custom-control-input checkbox_id check-hospital\" id=\"$id\" data-id=\"$id\" value=\"$id\" name=\"checkbox_id[]\"><label for=\"$id\" class=\"custom-control-label\">&nbsp;</label></div></td>";
                   
                    $nestedData['report_item_name'] = $value['report_item_name'];
                    $nestedData['mobile'] = $value['mobile'];
                    $nestedData['report_item_category'] = $value['report_item_category'];
                    $nestedData['category_name'] = $value['category_name'];                   
                    $nestedData['date'] = $this->formatDateTimeCountryWise($value['display_created_at'],$adminTimezone,'d-m-Y H:i');
                    $nestedData['status'] = $value['reported_count'];   
                    $goRoute = route('admin.business-client.hospital.show', $value['entity_id']);
                    $goButton = "<a role='button' href='".$goRoute."' title='' data-original-title='Go' class='btn btn-primary' data-toggle='tooltip'>Go</a>";
                    $warningRoute = route('admin.reported-client.warning-user', $id);

                    $warningButton = "<a role='button' href='".$warningRoute."' title='' data-original-title='Warning' class='mx-1 btn btn-primary btn-sm' data-toggle='tooltip'>Warning</a>";

                    if($value['report_type_id'] != ReportTypes::SHOP_USER){     
                        if($value['report_type_id'] != ReportTypes::SHOP && $value['report_type_id'] != ReportTypes::SHOP_PORTFOLIO && $value['report_type_id'] != ReportTypes::SHOP_PLACE && $value['report_type_id'] != ReportTypes::HOSPITAL_PLACE){                  
                            $deletePostButton = "<a role='button' href='javascript:void(0)' onclick='deletePost(" . $id . ")' title='' data-original-title='Delete Post' class='mx-1 btn btn-primary btn-sm' data-toggle='tooltip'>Delete Post</a>";
                        }
                        $deleteAllPostButton = "<a role='button' href='javascript:void(0)' onclick='deleteAllPost(" . $id . ")' title='' data-original-title='Delete All Post' class='mx-1 btn btn-primary btn-sm' data-toggle='tooltip'>Delete All Post</a>";
                    }
                    $deleteAccountButton = "<a role='button' href='javascript:void(0)' onclick='deleteUser(" . $id . ")' title='' data-original-title='Delete Account' class='mx-1 btn btn-primary btn-sm' data-toggle='tooltip'>Delete Account</a>";

                    $nestedData['actions'] = "<div class='d-flex'>$warningButton $deletePostButton $deleteAllPostButton $deleteAccountButton</div>";                    
                    $nestedData['go_actions'] = "<div class='d-flex'>$goButton</div>";     
                    $deleteButton = "<a href='javascript:void(0)' role='button' onclick='deleteReport(" . $id . ")' class='btn btn-danger' data-toggle='tooltip' data-original-title=" . Lang::get('general.delete') . "><i class='fa fa-trash'></i></button>";
                    $nestedData['delete_actions'] = $deleteButton;     
                    $data[] = $nestedData;
                }
            }
            $jsonData = array(
                "draw" => intval($draw),
                "recordsTotal" => intval($totalData),
                "recordsFiltered" => intval($totalFiltered),
                "data" => $data,
                "deletedPosts" => $allDeleteIds,
                "query" => $query->toSql(),
            );
            Log::info('End reported hospital list');
            return response()->json($jsonData);
        } catch (\Exception $ex) {
            Log::info('Exception reported hospital list');
            Log::info($ex);
            return response()->json([]);
        }
    }
/* ================ Shop Code Start ======================= */
    public function indexShop()
    {
        $title = "Reported Client Shop";
        $basic_mentions = BasicMentions::pluck('value','name');
        $categoryList = Category::where('category_type_id',CategoryTypes::REPORT)->get();
        
        return view('admin.reported-client.index-shop', compact('title','basic_mentions','categoryList'));
    }

    public function getJsonAllShopData(Request $request)
    {
        try {   
            Log::info('Start reported shop list');
            $columns = array(
                0 => 'id',
                1 => 'report_type_id',
                2 => 'report_item_category',
                3 => 'category_name',
                7 => 'status_count',
                8 => 'report_clients.created_at',
                9 => 'photo',
            );

            $limit = $request->input('length');
            $start = $request->input('start');
            $search = $request->input('search.value');
            $draw = $request->input('draw');
            $order = $columns[$request->input('order.0.column')];
            $dir = $request->input('order.0.dir');
            $adminTimezone = $this->getAdminUserTimezone();

            $query = ReportClient::leftJoin('category','category.id','report_clients.category_id')
                    ->leftJoin('users_detail','users_detail.user_id','report_clients.reported_user_id')
                                   ->where(function($q) {
                                        $q->where('report_type_id',ReportTypes::SHOP)
                                        ->orWhere('report_type_id',ReportTypes::SHOP_PORTFOLIO)
                                        ->orWhere('report_type_id',ReportTypes::SHOP_PLACE);
                                    })
                                    ->select(['report_clients.*','category.name as category','users_detail.mobile']);
            if($request->category){
                $query = $query->where('report_clients.category_id',$request->category);  
            }                       
            $totalData = $query->count();

            $totalFiltered = $totalData;

            if (!empty($search)) {
                $query = $query->where('category.name', 'LIKE', "%{$search}%");
                $totalFiltered = $query->count();
            }

            $reportedShops = $query->offset($start)
                ->limit($limit)
                //->orderBy('report_clients.id', 'desc')
                ->orderBy($order, $dir)
                ->get();

            if (!empty($reportedShops)) {
                foreach ($reportedShops as $value) {
                    $value->reported_count = DB::table('report_clients')->where('entity_id',$value['entity_id'])->where('report_type_id',$value['report_type_id'])->whereNull('deleted_at')->sum('status_count');
                }
            }
            if($order == 'status_count'){
                if($dir == 'asc'){
                    $reportedShops = collect($reportedShops)->sortBy('reported_count');
                }else{
                    $reportedShops = collect($reportedShops)->sortByDesc('reported_count');
                }
            }
            $data = array();
            if (!empty($reportedShops)) {
                foreach ($reportedShops as $key => $value) {
                    $id = $value['id'];
                    $nestedData['checkbox'] = "<td><div class=\"custom-checkbox custom-control\">
                    <input type=\"checkbox\" data-checkboxes=\"mygroup\" class=\"custom-control-input checkbox_id check-hospital\" id=\"$id\" data-id=\"$id\" value=\"$id\" name=\"checkbox_id[]\"><label for=\"$id\" class=\"custom-control-label\">&nbsp;</label></div></td>";
                   
                    $nestedData['report_item_name'] = $value['report_item_name'];
                    $nestedData['mobile'] = $value['mobile'];
                    $nestedData['report_item_category'] = $value['report_item_category'];
                    $nestedData['category_name'] = $value['category_name'];                   
                    $nestedData['date'] = $this->formatDateTimeCountryWise($value['display_created_at'],$adminTimezone,'d-m-Y H:i');
                    $nestedData['status'] = $value['reported_count'];   
                    $nestedData['image'] = '';
                    $goRoute = route('admin.business-client.shop.show', $value['entity_id']);
                    $deleteAllPostButton = '';
                    $deletePostButton = '';
                    if($value['report_type_id'] == ReportTypes::SHOP || $value['report_type_id'] == ReportTypes::SHOP_PLACE){
                        
                        $deletePostButton = '';
                       
                        $deleteAllPostButton = "<a role='button' href='javascript:void(0)' onclick='deleteAllPost(" . $id . ")' title='' data-original-title='Delete All Post' class='mx-1 btn btn-primary btn-sm' data-toggle='tooltip'>Delete All Post</a>";
                    }else if($value['report_type_id'] == ReportTypes::SHOP_PORTFOLIO){
                        $shopImage = ShopImages::find($value['entity_id']);
                        $nestedData['image'] = ''; 
                        if($shopImage){
                            $goRoute = route('admin.business-client.shop.show', $shopImage->shop_id);
                            $nestedData['image'] = '<img onclick="showImage(`'. $shopImage->image .'`)" src="'.$shopImage->image.'" alt="'.$value['report_item_name'].'" class="reported-client-images pointer m-1" width="50" height="50" />';     
                        }else{
                            $goRoute = '';
                            $nestedData['image'] = '';
                        }
                        // $nestedData['image'] = $shopImage->image;  
                                        
                        $deletePostButton = "<a role='button' href='javascript:void(0)' onclick='deletePost(" . $id . ")' title='' data-original-title='Delete Post' class='mx-1 btn btn-primary btn-sm' data-toggle='tooltip'>Delete Post</a>";
                        
                    }
                    $goButton = "<a role='button' href='".$goRoute."' title='' data-original-title='Go' class='btn btn-primary' data-toggle='tooltip'>Go</a>";
                    $warningRoute = route('admin.reported-client.warning-user', $id);
                    $warningButton = "<a role='button' href='".$warningRoute."' title='' data-original-title='Warning' class='mx-1 btn btn-primary btn-sm' data-toggle='tooltip'>Warning</a>";
                    $deleteAccountButton = "<a role='button' href='javascript:void(0)' onclick='deleteUser(" . $id . ")' title='' data-original-title='Delete Account' class='mx-1 btn btn-primary btn-sm' data-toggle='tooltip'>Delete Account</a>";
                    $nestedData['go_actions'] = $goButton;                    
                    $nestedData['actions'] = "<div class='d-flex'>$warningButton $deletePostButton $deleteAllPostButton $deleteAccountButton</div>";                    
                    $deleteButton = "<a href='javascript:void(0)' role='button' onclick='deleteReport(" . $id . ")' class='btn btn-danger' data-toggle='tooltip' data-original-title=" . Lang::get('general.delete') . "><i class='fa fa-trash'></i></button>";
                    $nestedData['delete_actions'] = $deleteButton;   
                    $data[] = $nestedData;
                }
            }
            $jsonData = array(
                "draw" => intval($draw),
                "recordsTotal" => intval($totalData),
                "recordsFiltered" => intval($totalFiltered),
                "data" => $data
            );
            Log::info('End reported shop list');
            return response()->json($jsonData);
        } catch (\Exception $ex) {
            Log::info('Exception reported shop list');
            Log::info($ex);
            return response()->json([]);
        }
    }  
    
/* ================ Shop Code End ======================= */ 

/* ================ Community Code Start ======================= */
    public function indexCommunity()
    {
        $title = "Reported Client Community";
        $basic_mentions = BasicMentions::pluck('value','name');
        $categoryList = Category::where('category_type_id',CategoryTypes::REPORT)->get();

        return view('admin.reported-client.index-community', compact('title','basic_mentions','categoryList'));
    }

    public function getJsonAllCommunityData(Request $request)
    {
        try {   
            Log::info('Start reported community list');
            $columns = array(
                0 => 'id',
                1 => 'report_type_id',
                2 => 'report_item_category',
                3 => 'category_name',
                7 => 'status_count',
                8 => 'report_clients.created_at',
            );

            $limit = $request->input('length');
            $start = $request->input('start');
            $search = $request->input('search.value');
            $draw = $request->input('draw');
            $order = $columns[$request->input('order.0.column')];
            $dir = $request->input('order.0.dir');
            $adminTimezone = $this->getAdminUserTimezone();

            $query = ReportClient::join('category','category.id','report_clients.category_id')
                                    ->join('community','community.id','report_clients.entity_id')
                                    ->leftJoin('users_detail','users_detail.user_id','report_clients.reported_user_id')
                                    ->whereNull('community.deleted_at')
                                    ->where('report_type_id',ReportTypes::COMMUNITY)
                                    ->select(['report_clients.*','category.name as category','users_detail.mobile']);

            if($request->category){
                $query = $query->where('report_clients.category_id',$request->category);  
            }
            $totalData = $query->count();

            $totalFiltered = $totalData;

            if (!empty($search)) {
                $query = $query->where('category.name', 'LIKE', "%{$search}%");
                $totalFiltered = $query->count();
            }

            $reportedShops = $query->offset($start)
                ->limit($limit)
               // ->orderBy('report_clients.id', 'desc')
                ->orderBy($order, $dir)
                ->get();

            if (!empty($reportedShops)) {
                foreach ($reportedShops as $value) {
                    $value->reported_count = DB::table('report_clients')->where('entity_id',$value['entity_id'])->where('report_type_id',$value['report_type_id'])->whereNull('deleted_at')->sum('status_count');
                }
            }
            if($order == 'status_count'){
                if($dir == 'asc'){
                    $reportedShops = collect($reportedShops)->sortBy('reported_count');
                }else{
                    $reportedShops = collect($reportedShops)->sortByDesc('reported_count');
                }
            }
            $data = array();
            if (!empty($reportedShops)) {
                foreach ($reportedShops as $value) {
                    $id = $value['id'];
                    $nestedData['checkbox'] = "<td><div class=\"custom-checkbox custom-control\">
                    <input type=\"checkbox\" data-checkboxes=\"mygroup\" class=\"custom-control-input checkbox_id check-community\" id=\"$id\" data-id=\"$id\" value=\"$id\" name=\"checkbox_id[]\"><label for=\"$id\" class=\"custom-control-label\">&nbsp;</label></div></td>";
                   
                    $nestedData['report_item_name'] = $value['report_item_name'];
                    $nestedData['mobile'] = $value['mobile'];
                    $nestedData['report_item_category'] = $value['report_item_category'];
                    $nestedData['category_name'] = $value['category_name'];                   
                    $nestedData['date'] = $this->formatDateTimeCountryWise($value['display_created_at'],$adminTimezone,'d-m-Y H:i');
                    $nestedData['status'] = $value['reported_count'];   
                    $goRoute = route('admin.reported-client.community.show', $value['entity_id']);
                    $goButton = "<a role='button' href='".$goRoute."' title='' data-original-title='Go' class='btn btn-primary' data-toggle='tooltip'>Go</a>";
                    $warningRoute = route('admin.reported-client.warning-user', $id);
                    $warningButton = "<a role='button' href='".$warningRoute."' title='' data-original-title='Warning' class='mx-1 btn btn-primary btn-sm' data-toggle='tooltip'>Warning</a>";
                    $deletePostRoute = route('admin.reported-client.delete-post', $id);
                    $deletePostButton = "<a role='button' href='javascript:void(0)' onclick='deletePost(" . $id . ")' title='' data-original-title='Delete Post' class='mx-1 btn btn-primary btn-sm' data-toggle='tooltip'>Delete Post</a>";
                    $deleteAllPostButton = "<a role='button' href='javascript:void(0)' onclick='deleteAllPost(" . $id . ")' title='' data-original-title='Delete All Post' class='mx-1 btn btn-primary btn-sm' data-toggle='tooltip'>Delete All Post</a>";
                    $deleteAccountButton = "<a role='button' href='javascript:void(0)' onclick='deleteUser(" . $id . ")' title='' data-original-title='Delete Account' class='mx-1 btn btn-primary btn-sm' data-toggle='tooltip'>Delete Account</a>";
                    $nestedData['actions'] = "<div class='d-flex'>$warningButton $deletePostButton $deleteAllPostButton $deleteAccountButton</div>";                    
                    $nestedData['go_actions'] = "<div class='d-flex'>$goButton</div>"; 
                    $deleteButton = "<a href='javascript:void(0)' role='button' onclick='deleteReport(" . $id . ")' class='btn btn-danger' data-toggle='tooltip' data-original-title=" . Lang::get('general.delete') . "><i class='fa fa-trash'></i></button>";
                    $nestedData['delete_actions'] = $deleteButton;    
                    $data[] = $nestedData;
                }
            }
            $jsonData = array(
                "draw" => intval($draw),
                "recordsTotal" => intval($totalData),
                "recordsFiltered" => intval($totalFiltered),
                "data" => $data
            );
            Log::info('End reported community list');
            return response()->json($jsonData);
        } catch (\Exception $ex) {
            Log::info('Exception reported community list');
            Log::info($ex);
            return response()->json([]);
        }
    } 
    
    public function showCommunity($id)
    {       
        $title = 'Community Detail';
        $community = Community::find($id);        
        return view('admin.reported-client.show-community', compact('title','community'));
    }
    
    /* ================ Community Code End ======================= */ 
    /* ================ Review Code Start ======================= */
    public function indexReview()
    {
        $title = "Reported Client Review";
        $basic_mentions = BasicMentions::pluck('value','name');
        $categoryList = Category::where('category_type_id',CategoryTypes::REPORT)->get();

        return view('admin.reported-client.index-review', compact('title','basic_mentions','categoryList'));
    }

    public function getJsonAllReviewData(Request $request)
    {
        try {   
            Log::info('Start reported Review list');
            $columns = array(
                0 => 'id',
                1 => 'report_type_id',
                2 => 'report_item_category',
                3 => 'category_name',
                7 => 'status_count',
                8 => 'report_clients.created_at',
            );

            $limit = $request->input('length');
            $start = $request->input('start');
            $search = $request->input('search.value');
            $draw = $request->input('draw');
            $order = $columns[$request->input('order.0.column')];
            $dir = $request->input('order.0.dir');
            $adminTimezone = $this->getAdminUserTimezone();

            $query = ReportClient::join('category','category.id','report_clients.category_id')
                                    ->join('reviews','reviews.id','report_clients.entity_id')
                                    ->leftJoin('users_detail','users_detail.user_id','report_clients.reported_user_id')
                                    ->whereNull('reviews.deleted_at')
                                    ->where('report_type_id',ReportTypes::REVIEWS)
                                    ->select(['report_clients.*','category.name as category','users_detail.mobile']);

            if($request->category){
                $query = $query->where('report_clients.category_id',$request->category);  
            }
            $totalData = $query->count();

            $totalFiltered = $totalData;

            if (!empty($search)) {
                $query = $query->where('category.name', 'LIKE', "%{$search}%");
                $totalFiltered = $query->count();
            }

            $reportedShops = $query->offset($start)
                ->limit($limit)
                //->orderBy('report_clients.id', 'desc')
                ->orderBy($order, $dir)
                ->get();

            if (!empty($reportedShops)) {
                foreach ($reportedShops as $value) {
                    $value->reported_count = DB::table('report_clients')->where('entity_id',$value['entity_id'])->where('report_type_id',$value['report_type_id'])->whereNull('deleted_at')->sum('status_count');
                }
            }
            if($order == 'status_count'){
                if($dir == 'asc'){
                    $reportedShops = collect($reportedShops)->sortBy('reported_count');
                }else{
                    $reportedShops = collect($reportedShops)->sortByDesc('reported_count');
                }
            }
            $data = array();
            if (!empty($reportedShops)) {
                foreach ($reportedShops as $value) {
                    $id = $value['id'];
                    $nestedData['checkbox'] = "<td><div class=\"custom-checkbox custom-control\">
                    <input type=\"checkbox\" data-checkboxes=\"mygroup\" class=\"custom-control-input checkbox_id check-community\" id=\"$id\" data-id=\"$id\" value=\"$id\" name=\"checkbox_id[]\"><label for=\"$id\" class=\"custom-control-label\">&nbsp;</label></div></td>";
                   
                    $nestedData['report_item_name'] = $value['report_item_name'];
                    $nestedData['mobile'] = $value['mobile'];
                    $nestedData['report_item_category'] = $value['report_item_category'];
                    $nestedData['category_name'] = $value['category_name'];                   
                    $nestedData['date'] = $this->formatDateTimeCountryWise($value['display_created_at'],$adminTimezone,'d-m-Y H:i');
                    $nestedData['status'] = $value['reported_count'];   
                    $goRoute = route('admin.reported-client.review.show', $value['entity_id']);
                    $goButton = "<a role='button' href='".$goRoute."' title='' data-original-title='Go' class='btn btn-primary' data-toggle='tooltip'>Go</a>";
                    $warningRoute = route('admin.reported-client.warning-user', $id);
                    $warningButton = "<a role='button' href='".$warningRoute."' title='' data-original-title='Warning' class='mx-1 btn btn-primary btn-sm' data-toggle='tooltip'>Warning</a>";
                    $deletePostRoute = route('admin.reported-client.delete-post', $id);
                    $deletePostButton = "<a role='button' href='javascript:void(0)' onclick='deletePost(" . $id . ")' title='' data-original-title='Delete Post' class='mx-1 btn btn-primary btn-sm' data-toggle='tooltip'>Delete Post</a>";
                    $deleteAllPostButton = "<a role='button' href='javascript:void(0)' onclick='deleteAllPost(" . $id . ")' title='' data-original-title='Delete All Post' class='mx-1 btn btn-primary btn-sm' data-toggle='tooltip'>Delete All Post</a>";
                    $deleteAccountButton = "<a role='button' href='javascript:void(0)' onclick='deleteUser(" . $id . ")' title='' data-original-title='Delete Account' class='mx-1 btn btn-primary btn-sm' data-toggle='tooltip'>Delete Account</a>";
                    $nestedData['actions'] = "<div class='d-flex'>$warningButton $deletePostButton $deleteAllPostButton $deleteAccountButton</div>";                    
                    $nestedData['go_actions'] = "<div class='d-flex'>$goButton</div>"; 
                    $deleteButton = "<a href='javascript:void(0)' role='button' onclick='deleteReport(" . $id . ")' class='btn btn-danger' data-toggle='tooltip' data-original-title=" . Lang::get('general.delete') . "><i class='fa fa-trash'></i></button>";
                    $nestedData['delete_actions'] = $deleteButton;    
                    $data[] = $nestedData;
                }
            }
            $jsonData = array(
                "draw" => intval($draw),
                "recordsTotal" => intval($totalData),
                "recordsFiltered" => intval($totalFiltered),
                "data" => $data
            );
            Log::info('End reported Review list');
            return response()->json($jsonData);
        } catch (\Exception $ex) {
            Log::info('Exception reported Review list');
            Log::info($ex);
            return response()->json([]);
        }
    } 
    
    public function showReview($id)
    {       
        $title = 'Review Detail';
        $review = Reviews::find($id);        
        return view('admin.reported-client.show-review', compact('title','review'));
    }
    
    /* ================ Review Code End ======================= */ 

    /* ================ User Code Start ======================= */
    public function indexUser()
    {
        $title = "Reported Client User";
        $basic_mentions = BasicMentions::pluck('value','name');
        $categoryList = Category::where('category_type_id',CategoryTypes::REPORT)->get();
        
        return view('admin.reported-client.index-user', compact('title','basic_mentions','categoryList'));
    }

    public function getJsonAllUserData(Request $request)
    {
        try {   
            Log::info('Start reported user list');
            $columns = array(
                0 => 'id',
                1 => 'report_type_id',
                4 => 'category_name',
                6 => 'status_count',
                7 => 'report_clients.created_at',
            );

            $limit = $request->input('length');
            $start = $request->input('start');
            $search = $request->input('search.value');
            $draw = $request->input('draw');
            $order = $columns[$request->input('order.0.column')];
            $dir = $request->input('order.0.dir');
            $adminTimezone = $this->getAdminUserTimezone();

            $query = ReportClient::join('category','category.id','report_clients.category_id')
                        ->leftJoin('users_detail','users_detail.user_id','report_clients.reported_user_id')
                                    ->whereIn('report_type_id',[ReportTypes::SHOP_USER])
                                    // ->whereIn('report_type_id',[ReportTypes::SHOP_USER,ReportTypes::COMMUNITY_COMMENT,ReportTypes::COMMUNITY_COMMENT_REPLY,ReportTypes::REVIEWS_COMMENT,ReportTypes::REVIEWS_COMMENT_REPLY])
                                    ->select(['report_clients.*','category.name as category', 'users_detail.mobile']);
            if($request->category){
                $query = $query->where('report_clients.category_id',$request->category);  
            }
            $totalData = $query->count();
            $totalFiltered = $totalData;

            if (!empty($search)) {
                $query = $query->where('category.name', 'LIKE', "%{$search}%");
                $totalFiltered = $query->count();
            }

            $reportedShops = $query->offset($start)
                ->limit($limit)
                //->orderBy('report_clients.id', 'desc')
                ->orderBy($order, $dir)
                ->get();

            if (!empty($reportedShops)) {
                foreach ($reportedShops as $value) {
                    $value->reported_count = DB::table('report_clients')->where('entity_id',$value['entity_id'])->where('report_type_id',$value['report_type_id'])->whereNull('deleted_at')->sum('status_count');
                }
            }
            if($order == 'status_count'){
                if($dir == 'asc'){
                    $reportedShops = collect($reportedShops)->sortBy('reported_count');
                }else{
                    $reportedShops = collect($reportedShops)->sortByDesc('reported_count');
                }
            }
            $data = array();
            if (!empty($reportedShops)) {
                foreach ($reportedShops as $value) {
                    $id = $value['id'];
                    $nestedData['checkbox'] = "<td><div class=\"custom-checkbox custom-control\">
                    <input type=\"checkbox\" data-checkboxes=\"mygroup\" class=\"custom-control-input checkbox_id check-user\" id=\"$id\" data-id=\"$id\" value=\"$id\" name=\"checkbox_id[]\"><label for=\"$id\" class=\"custom-control-label\">&nbsp;</label></div></td>";
                   
                    $nestedData['report_item_name'] = $value['report_item_name'];
                    $nestedData['mobile'] = $value['mobile'];
                    $nestedData['category_name'] = $value['category_name'];                   
                    $nestedData['date'] = $this->formatDateTimeCountryWise($value['display_created_at'],$adminTimezone,'d-m-Y H:i');
                    $nestedData['status'] = $value['reported_count'];   
                    $goRoute = route('admin.reported-client.user.show', $value['entity_id']);
                    $goButton = "<a role='button' href='".$goRoute."' title='' data-original-title='Go' class='btn btn-primary' data-toggle='tooltip'>Go</a>";
                    $warningRoute = route('admin.reported-client.warning-user', $id);
                    $warningButton = "<a role='button' href='".$warningRoute."' title='' data-original-title='Warning' class='mx-1 btn btn-primary btn-sm' data-toggle='tooltip'>Warning</a>";
                    // $deletePostButton = "<a role='button' href='javascript:void(0)' onclick='deletePost(" . $id . ")' title='' data-original-title='Delete Post' class='mx-1 btn btn-primary btn-sm' data-toggle='tooltip'>Delete Post</a>";
                    // $deleteAllPostButton = "<a role='button' href='javascript:void(0)' onclick='deleteAllPost(" . $id . ")' title='' data-original-title='Delete All Post' class='mx-1 btn btn-primary btn-sm' data-toggle='tooltip'>Delete All Post</a>";
                    $deleteAccountButton = "<a role='button' href='javascript:void(0)' onclick='deleteUser(" . $id . ")' title='' data-original-title='Delete Account' class='mx-1 btn btn-primary btn-sm' data-toggle='tooltip'>Delete Account</a>";
                    $nestedData['actions'] = "<div class='d-flex'>$warningButton  $deleteAccountButton</div>";                    
                    $nestedData['go_actions'] = $goButton;    
                    $deleteButton = "<a href='javascript:void(0)' role='button' onclick='deleteReport(" . $id . ")' class='btn btn-danger' data-toggle='tooltip' data-original-title=" . Lang::get('general.delete') . "><i class='fa fa-trash'></i></button>";
                    $nestedData['delete_actions'] = $deleteButton;  
                    $data[] = $nestedData;
                }
            }
            $jsonData = array(
                "draw" => intval($draw),
                "recordsTotal" => intval($totalData),
                "recordsFiltered" => intval($totalFiltered),
                "data" => $data
            );
            Log::info('End reported user list');
            return response()->json($jsonData);
        } catch (\Exception $ex) {
            Log::info('Exception reported user list');
            Log::info($ex);
            return response()->json([]);
        }
    }   

    public function showUser($id)
    {       
        $title = 'User Detail';
        $user = [];        
        return view('admin.reported-client.show-user', compact('title','user'));
    }
    
    /* ================ User Code End ======================= */ 

    public function warningMention(Request $request)
    {
        try {
            Log::info('Start Basic Mention ');
            DB::beginTransaction();
            $inputs = $request->all();            
            $data = [
                'reported_shop_warning_comment' => $inputs['shop_warning_comment'],
                'reported_hospital_warning_comment' => $inputs['hospital_warning_comment'],
                'reported_community_warning_comment' => $inputs['community_warning_comment'],
                'reported_review_warning_comment' => $inputs['shop_warning_comment'],
                'reported_shop_user_warning_comment' => $inputs['shop_user_warning_comment'],
            ];

            foreach($data as $key => $value){
                $rejectMentionText = BasicMentions::updateOrCreate(['name'=> $key],['value' => $value]);                
            }
            DB::commit();
            Log::info('End Basic Mention ' );
            return $this->sendSuccessResponse('Warning mention set successfully.', 200);
        } catch (\Exception $ex) {
            DB::rollBack();
            Log::info('Exception in Basic Mention ');
            Log::info($ex);
            return $this->sendFailedResponse('Unable to set basic mention.', 400);
        }
    }

    public function warningUser($id)
    {
        try {
            Log::info('Start warning user ');
            DB::beginTransaction();
            $report = ReportClient::find($id);   
            $report_type_id = $report ? $report->report_type_id : 0;    
            $entity_id = $report ? $report->entity_id : 0;    
            $userId = 0;
            if($report_type_id == ReportTypes::SHOP) {
                $user = UserEntityRelation::where('entity_type_id',EntityTypes::SHOP)
                                            ->where('entity_id',$entity_id)->first();
                $userId = !empty($user) ? $user->user_id : 0;
            }else if($report_type_id == ReportTypes::HOSPITAL){
                $user = UserEntityRelation::where('entity_type_id',EntityTypes::HOSPITAL)
                                            ->where('entity_id',$entity_id)->first();
                $userId = !empty($user) ? $user->user_id : 0;
            }else if($report_type_id == ReportTypes::SHOP_PORTFOLIO){
                $shopImage = ShopImages::find($entity_id); 
                $user = UserEntityRelation::where('entity_type_id',EntityTypes::SHOP)
                                            ->where('entity_id',$shopImage->shop_id)->first();
                $userId = !empty($user) ? $user->user_id : 0;
            }else if($report_type_id == ReportTypes::SHOP_USER){
                $data = UserDetail::where('user_id',$entity_id)->first(); 
                $userId = !empty($data) ? $data->user_id : 0;
            }else if($report_type_id == ReportTypes::COMMUNITY){
                $data = Community::find($entity_id); 
                $userId = !empty($data) ? $data->user_id : 0;
            }else if($report_type_id == ReportTypes::COMMUNITY_COMMENT){
                $data = CommunityComments::find($entity_id); 
                $userId = !empty($data) ? $data->user_id : 0;
            }else if($report_type_id == ReportTypes::COMMUNITY_COMMENT_REPLY){
                $data = CommunityCommentReply::find($entity_id); 
                $userId = !empty($data) ? $data->user_id : 0;
            }else if($report_type_id == ReportTypes::REVIEWS){
                $data = Reviews::find($entity_id); 
                $userId = !empty($data) ? $data->user_id : 0;
            }else if($report_type_id == ReportTypes::REVIEWS_COMMENT){
                $data = ReviewComments::find($entity_id); 
                $userId = !empty($data) ? $data->user_id : 0;
            }else if($report_type_id == ReportTypes::REVIEWS_COMMENT_REPLY){
                $data = ReviewCommentReply::find($entity_id); 
                $userId = !empty($data) ? $data->user_id : 0;
            }else if($report_type_id == ReportTypes::SHOP_PLACE || $report_type_id == ReportTypes::HOSPITAL_PLACE){
                $userId = $report->reported_user_id;
            }

            UserDetail::where('user_id',$userId)->update(['report_count' => DB::raw('report_count + 1')]);

            $report = ReportClient::where('id',$id)->delete();  
            DB::commit();
            Log::info('End warning user.' );
            notify()->success("Warning send successfully.", "Success", "topRight");
            return redirect()->route('admin.reported-client.index');
        } catch (\Exception $ex) {
            DB::rollBack();
            Log::info('Exception in warning user');
            Log::info($ex);
            notify()->error("Unable to send warning.", "Error", "topRight");
            return redirect()->route('admin.reported-client.index');    
        }
    }

    public function getPost($id)
    {
        return view('admin.reported-client.delete-post', compact('id'));
    }

    public function deletePost(Request $request,$id)
    { 
        try {
            Log::info('Start delete post ');
            DB::beginTransaction();
            $report = ReportClient::find($id); 
              
            $report_type_id = $report ? $report->report_type_id : 0;    
            $entity_id = $report ? $report->entity_id : 0;    
            $userId = 0;
            if($report_type_id == ReportTypes::SHOP) {
                // $user = UserEntityRelation::where('entity_type_id',EntityTypes::SHOP)
                //                             ->where('entity_id',$entity_id)->first();
                // $userId = !empty($user) ? $user->user_id : 0;
            }else if($report_type_id == ReportTypes::HOSPITAL){
                // $user = UserEntityRelation::where('entity_type_id',EntityTypes::HOSPITAL)
                //                             ->where('entity_id',$entity_id)->first();
                // $userId = !empty($user) ? $user->user_id : 0;
            }else if($report_type_id == ReportTypes::SHOP_PORTFOLIO){
                $shopImage = ShopImages::find($entity_id); 
                $user = UserEntityRelation::where('entity_type_id',EntityTypes::SHOP)
                                            ->where('entity_id',$shopImage->shop_id)->first();
                $userId = !empty($user) ? $user->user_id : 0;
                $deletePost = ShopImages::where('id',$entity_id)->delete(); 
            }else if($report_type_id == ReportTypes::COMMUNITY){
                $data = Community::find($entity_id); 
                $userId = !empty($data) ? $data->user_id : 0;
                $deletePost = Community::where('id',$entity_id)->delete(); 
            }else if($report_type_id == ReportTypes::COMMUNITY_COMMENT){
                $data = CommunityComments::find($entity_id); 
                $userId = !empty($data) ? $data->user_id : 0;
                $deletePost = CommunityComments::where('id',$entity_id)->delete(); 
            }else if($report_type_id == ReportTypes::COMMUNITY_COMMENT_REPLY){
                $data = CommunityCommentReply::find($entity_id); 
                $userId = !empty($data) ? $data->user_id : 0;
                $deletePost = CommunityCommentReply::where('id',$entity_id)->delete(); 
            }else if($report_type_id == ReportTypes::REVIEWS){
                $data = Reviews::find($entity_id); 
                $userId = !empty($data) ? $data->user_id : 0;
                $deletePost = Reviews::where('id',$entity_id)->delete(); 
            }else if($report_type_id == ReportTypes::REVIEWS_COMMENT){
                $data = ReviewComments::find($entity_id); 
                $userId = !empty($data) ? $data->user_id : 0;
                $deletePost = ReviewComments::where('id',$entity_id)->delete(); 
            }else if($report_type_id == ReportTypes::REVIEWS_COMMENT_REPLY){
                $data = ReviewCommentReply::find($entity_id); 
                $userId = !empty($data) ? $data->user_id : 0;
                $deletePost = ReviewCommentReply::where('id',$entity_id)->delete(); 
            }else if($report_type_id == ReportTypes::SHOP_PLACE || $report_type_id == ReportTypes::HOSPITAL_PLACE){
                $userId = $report->reported_user_id;
                
                $deletePost = RequestedCustomer::find($entity_id)->delete(); 
            }

            UserDetail::where('user_id',$userId)->update(['report_count' => DB::raw('report_count + 1')]);
            
            $report = ReportClient::where('id',$id)->delete();  
            DB::commit();
            Log::info('End delete post.' );
            notify()->success("Post deleted successfully.", "Success", "topRight");
            return redirect()->route('admin.reported-client.index');
        } catch (\Exception $ex) {
            DB::rollBack();
            Log::info('Exception in delete post');
            Log::info($ex);
            notify()->error("Unable to delete post.", "Error", "topRight");
            return redirect()->route('admin.reported-client.index');    
        }
    }

    public function getAllPost($id)
    {
        return view('admin.reported-client.delete-all-post', compact('id'));
    }

    public function deleteAllPost(Request $request,$id)
    { 
        try {
            Log::info('Start delete all post ');
            DB::beginTransaction();
            $report = ReportClient::find($id);   
            $report_type_id = $report ? $report->report_type_id : 0;    
            $entity_id = $report ? $report->entity_id : 0;    
            $userId = 0;
            if($report_type_id == ReportTypes::SHOP) {
                $user = UserEntityRelation::where('entity_type_id',EntityTypes::SHOP)
                                            ->where('entity_id',$entity_id)->first();
                $userId = !empty($user) ? $user->user_id : 0;
                $deleteAllPost = ShopImages::where('shop_id',$entity_id)->where('shop_image_type',ShopImagesTypes::PORTFOLIO)->delete();
            }else if($report_type_id == ReportTypes::HOSPITAL){
                $user = UserEntityRelation::where('entity_type_id',EntityTypes::HOSPITAL)
                                            ->where('entity_id',$entity_id)->first();
                $userId = !empty($user) ? $user->user_id : 0;
                $postIds = Post::where('hospital_id',$entity_id)->pluck('id')->toArray();
                $deleteAllPost = Post::where('hospital_id',$entity_id)->delete();
                Notice::whereIn('entity_id',$postIds)->where('entity_type_id',EntityTypes::HOSPITAL)->delete();
            }else if($report_type_id == ReportTypes::SHOP_PORTFOLIO){
                $shopImage = ShopImages::find($entity_id); 
                $user = UserEntityRelation::where('entity_type_id',EntityTypes::SHOP)
                                            ->where('entity_id',$shopImage->shop_id)->first();
                $userId = !empty($user) ? $user->user_id : 0;
                $deletePost = ShopImages::where('id',$entity_id)->delete(); 
            }else if($report_type_id == ReportTypes::COMMUNITY){
                $data = Community::find($entity_id); 
                $userId = !empty($data) ? $data->user_id : 0;
                $deletePost = Community::where('user_id',$userId)->delete(); 
            }else if($report_type_id == ReportTypes::COMMUNITY_COMMENT){
                $data = CommunityComments::find($entity_id); 
                $userId = !empty($data) ? $data->user_id : 0;
                $deletePost = CommunityComments::where('user_id',$userId)->delete(); 
            }else if($report_type_id == ReportTypes::COMMUNITY_COMMENT_REPLY){
                $data = CommunityCommentReply::find($entity_id); 
                $userId = !empty($data) ? $data->user_id : 0;
                $deletePost = CommunityCommentReply::where('user_id',$userId)->delete(); 
            }else if($report_type_id == ReportTypes::REVIEWS){
                $data = Reviews::find($entity_id); 
                $userId = !empty($data) ? $data->user_id : 0;
                $deletePost = Reviews::where('user_id',$userId)->delete(); 
            }else if($report_type_id == ReportTypes::REVIEWS_COMMENT){
                $data = ReviewComments::find($entity_id); 
                $userId = !empty($data) ? $data->user_id : 0;
                $deletePost = ReviewComments::where('user_id',$userId)->delete(); 
            }else if($report_type_id == ReportTypes::REVIEWS_COMMENT_REPLY){
                $data = ReviewCommentReply::find($entity_id); 
                $userId = !empty($data) ? $data->user_id : 0;
                $deletePost = ReviewCommentReply::where('user_id',$userId)->delete(); 
            }else if($report_type_id == ReportTypes::SHOP_PLACE || $report_type_id == ReportTypes::HOSPITAL_PLACE){
                $userId = $report->reported_user_id;
                
                $deletePost = RequestedCustomer::find($entity_id)->delete(); 
                    ReportClient::where('report_type_id',$report->report_type_id)
                        ->where('reported_user_id',$report->reported_user_id)
                        ->where('entity_id',$report->entity_id)
                        ->where('category_id',$report->category_id)
                        ->delete(); 
            }

            UserDetail::where('user_id',$userId)->update(['report_count' => DB::raw('report_count + 1')]);

            $report = ReportClient::where('id',$id)->delete();  
            DB::commit();
            Log::info('End delete all post.' );
            notify()->success("All post deleted successfully.", "Success", "topRight");
            return redirect()->route('admin.reported-client.index');
        } catch (\Exception $ex) {
            DB::rollBack();
            Log::info('Exception in delete all post');
            Log::info($ex);
            notify()->error("Unable to delete all post", "Error", "topRight");
            return redirect()->route('admin.reported-client.index');    
        }
    }
    public function getAccount($id)
    {
        return view('admin.reported-client.delete-account', compact('id'));
    }

    public function deleteAccount(Request $request,$id)
    { 
        try {
            Log::info('Start delete all post ');
            DB::beginTransaction();
            $report = ReportClient::find($id);   
            $report_type_id = $report ? $report->report_type_id : 0;    
            $entity_id = $report ? $report->entity_id : 0;    
            $userId = 0;
            if($report_type_id == ReportTypes::SHOP) {
                $user = UserEntityRelation::where('entity_type_id',EntityTypes::SHOP)
                                            ->where('entity_id',$entity_id)->first();
                $userId = !empty($user) ? $user->user_id : 0;
            }else if($report_type_id == ReportTypes::HOSPITAL){
                $user = UserEntityRelation::where('entity_type_id',EntityTypes::HOSPITAL)
                                            ->where('entity_id',$entity_id)->first();
                $userId = !empty($user) ? $user->user_id : 0;
            }else if($report_type_id == ReportTypes::SHOP_PORTFOLIO){
                $shopImage = ShopImages::find($entity_id); 
                $user = UserEntityRelation::where('entity_type_id',EntityTypes::SHOP)
                                            ->where('entity_id',$shopImage->shop_id)->first();
                $userId = !empty($user) ? $user->user_id : 0;
            }else if($report_type_id == ReportTypes::COMMUNITY){
                $data = Community::find($entity_id); 
                $userId = !empty($data) ? $data->user_id : 0;
            }else if($report_type_id == ReportTypes::COMMUNITY_COMMENT){
                $data = CommunityComments::find($entity_id); 
                $userId = !empty($data) ? $data->user_id : 0;
            }else if($report_type_id == ReportTypes::COMMUNITY_COMMENT_REPLY){
                $data = CommunityCommentReply::find($entity_id); 
                $userId = !empty($data) ? $data->user_id : 0;
            }else if($report_type_id == ReportTypes::REVIEWS){
                $data = Reviews::find($entity_id); 
                $userId = !empty($data) ? $data->user_id : 0;
            }else if($report_type_id == ReportTypes::REVIEWS_COMMENT){
                $data = ReviewComments::find($entity_id); 
                $userId = !empty($data) ? $data->user_id : 0;
            }else if($report_type_id == ReportTypes::REVIEWS_COMMENT_REPLY){
                $data = ReviewCommentReply::find($entity_id); 
                $userId = !empty($data) ? $data->user_id : 0;
            }else if($report_type_id == ReportTypes::SHOP_PLACE || $report_type_id == ReportTypes::HOSPITAL_PLACE){
                $userId = $report->reported_user_id;
            }

            
            $userRelation = UserEntityRelation::where('user_id',$userId)->get();
            foreach($userRelation as $ur){
                if($ur->entity_type_id == EntityTypes::SHOP){                    
                    Shop::where('id',$ur->entity_id)->delete();
                }else if($ur->entity_type_id == EntityTypes::HOSPITAL){
                    Hospital::where('id',$ur->entity_id)->delete();
                }
            }
            Community::where('user_id',$userId)->delete();
            UserDetail::where('user_id',$userId)->delete();
            $userRelation = UserEntityRelation::where('user_id',$userId)->delete();
            $deleteReport =  ReportClient::where('reported_user_id',$report->reported_user_id)->delete();
            User::where('id',$userId)->delete();

            $report = ReportClient::first();
            DB::commit();
            Log::info('End delete all post.' );
            notify()->success("All post deleted successfully.", "Success", "topRight");
            return redirect()->route('admin.reported-client.index');
        } catch (\Exception $ex) {
            DB::rollBack();
            Log::info('Exception in delete all post');
            Log::info($ex);
            notify()->error("Unable to delete all post", "Error", "topRight");
            return redirect()->route('admin.reported-client.index');    
        }
    }

    public function delete($id)
    {   
        return view('admin.reported-client.delete', compact('id'));
    }

    public function destroy($id)
    {
        try {
            Log::info('Report client delete code start.');
            DB::beginTransaction();
                ReportClient::where('id',$id)->delete();
            DB::commit();
            Log::info('Report client delete code end.');
            notify()->success("Report client deleted successfully", "Success", "topRight");
            return redirect()->route('admin.reported-client.index');
        } catch (\Exception $ex) {
            DB::rollBack();
            Log::info('Report client delete exception.');
            Log::info($ex);
            notify()->error("Failed to deleted report client", "Error", "topRight");
            return redirect()->route('admin.reported-client.index');        
        }
    }
    
   
}
