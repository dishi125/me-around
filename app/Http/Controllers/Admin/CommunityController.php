<?php

namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use App\Models\Community;
use Illuminate\Support\Facades\DB;
use Log;
use App\Models\UserEntityRelation;
use App\Models\EntityTypes;
use App\Models\Shop;
use App\Models\Hospital;
use App\Models\UserDetail;
use App\Models\ReportClient;
use App\Models\Notice;
use App\Models\User;
use App\Models\Category;
use App\Models\CategoryTypes;
use App\Models\Status;

class CommunityController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:community-list', ['only' => ['index']]);
    }

    /* ================= Community Post code start ======================== */

    public function index()
    {
        $title = "Community List";

        $communityCategory = Category::where('category_type_id', CategoryTypes::COMMUNITY)
            ->where('parent_id', 0)
            ->where('status_id', Status::ACTIVE)
            ->get();
        
        return view('admin.community.index', compact('title','communityCategory'));

    }

    public function getJsonData(Request $request){

        $columns = array(
            0 => 'users_detail.name',
            1 => 'users.email',
            2 => 'users_detail.mobile',
            3 => 'community.updated_at',
            4 => 'community.title',
            5 => 'action',
        );

        $filter = $request->input('filter');
        $limit = $request->input('length');
        $start = $request->input('start');
        $search = $request->input('search.value');
        $draw = $request->input('draw');
        $order = $columns[$request->input('order.0.column')];
        $dir = $request->input('order.0.dir');
        $adminTimezone = $this->getAdminUserTimezone();

        DB::enableQueryLog();
        try {
            $data = [];

            $subCategory = [];


            $communityQuery = Community::select('community.id','community.description','community.title','community.user_id','community.updated_at','users.email','users_detail.name', 'users_detail.mobile');

            $communityQuery = $communityQuery->leftJoin('users','users.id','community.user_id');
            $communityQuery = $communityQuery->leftjoin('users_detail','users_detail.user_id','community.user_id');
            
            if($filter != 'all'){
                $subCategory = Category::where('category_type_id', CategoryTypes::COMMUNITY)
                        ->where('parent_id', $filter)
                        ->where('status_id', Status::ACTIVE)->pluck('id');
                $communityQuery = $communityQuery->whereIn('community.category_id', $subCategory);
            }

            if (!empty($search)) {
                $communityQuery = $communityQuery->where(function($q) use ($search){
                    $q->where('community.title', 'LIKE', "%{$search}%")
                    ->orWhere('community.description', 'LIKE', "%{$search}%")
                    ->orWhere('users_detail.mobile', 'LIKE', "%{$search}%");
                });
                
            }
            $totalData = count($communityQuery->get());
            $totalFiltered = $totalData;
            
            $communityData = $communityQuery->offset($start)
                    ->limit($limit)
                    ->orderBy($order, $dir)  
                    ->get();

            $count = 0;

            foreach($communityData as $communityVal){
                $show = route('admin.community.show', [$communityVal->id]);

                $deleteUserButton = "<a role='button' href='javascript:void(0)' onclick='deleteUser(" . $communityVal->user_id . ")' title='' data-original-title='Delete Account' class='mx-1 btn btn-primary btn-sm' data-toggle='tooltip'>Delete Account</a>";

                $deleteButton = "<a role='button' href='javascript:void(0)' onclick='deleteCommunity(" . $communityVal->id . ")' title='' data-original-title='Delete' class='mx-1 btn btn-primary btn-sm' data-toggle='tooltip'>Delete</a>";


                $viewButton = "<a role='button' href='".$show."' title='' data-original-title='View' class='mx-1 btn btn-primary btn-sm' data-toggle='tooltip'>View</a>";

                //env('MEAROUND_LIVE_LINK')$optionFolder = config('constant.weddingSettings');
                $webLink = env('MEAROUND_LIVE_LINK') ? env('MEAROUND_LIVE_LINK') : 'http://www.mearound.kr/';
                $link = $webLink.config('constant.community_detail')."/".$communityVal->id;
               
                $copyIcon = '<a href="javascript:void(0);" onClick="copyTextLink(`'.$link.'`)" class="btn-sm pt-1 pb-2 mx-1 btn btn-primary"><i class="fas fa-copy"></i></a>';

                $data[$count]['name'] = $communityVal->name;
                $data[$count]['email'] = $communityVal->email;
                $data[$count]['mobile'] = $communityVal->mobile;
                $data[$count]['updated_date'] = $this->formatDateTimeCountryWise($communityVal->updated_at,$adminTimezone);
                $data[$count]['post_title'] = $communityVal->title;
                $data[$count]['actions'] = "<div class='d-flex align-items-center'>$deleteButton $deleteUserButton $viewButton $copyIcon</div>";
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
            Log::info('Exception in Community list');
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

    public function delete($id)
    {   
        return view('admin.community.delete', compact('id'));
    }

    public function deleteCommunityUser($id)
    {   
        return view('admin.community.delete-user', compact('id'));
    }

    public function deleteCommunity(Request $request) {   

        try {
            DB::beginTransaction();
            Log::info('Delete community code start.');
            $inputs = $request->all();
            Community::where('id',$inputs['id'])->delete();
            
            DB::commit();
            Log::info('Delete community code end.');
            return $this->sendSuccessResponse('Community deleted successfully.', 200);
        } catch (\Exception $ex) {
            DB::rollBack();
            Log::info('Delete community exception.');
            Log::info($ex);
            return $this->sendFailedResponse('Failed to delete community.', 400);
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
            Community::where('user_id',$inputs['userId'])->delete();
            Notice::where('user_id',$inputs['userId'])->delete();
            ReportClient::where('reported_user_id',$inputs['userId'])->delete();
            UserEntityRelation::where('user_id',$inputs['userId'])->delete(); 
            UserDetail::where('user_id',$inputs['userId'])->delete(); 
            User::where('id',$inputs['userId'])->delete();  

            DB::commit();
            Log::info('Delete user code end.');
            return $this->sendSuccessResponse('Community user deleted successfully.', 200);
        } catch (\Exception $ex) {
            DB::rollBack();
            Log::info('Delete user code exception.');
            Log::info($ex);
            return $this->sendFailedResponse('Failed to delete user.', 400);
        }
    }

    public function show($id)
    {       
        $title = 'Community Detail';
        $community = Community::find($id);      
        return view('admin.community.show', compact('title','community'));
    }


}
