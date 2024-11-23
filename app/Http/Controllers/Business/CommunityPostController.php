<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Category;
use App\Models\CategoryTypes;
use App\Models\Status;
use App\Models\UserBlockHistory;
use App\Models\Community;
use App\Models\CommunityImages;
use Log;
use Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Lang;

class CommunityPostController extends Controller
{
    public function index()
    {
        $title = 'Community';
        $category = Category::where('category_type_id',CategoryTypes::COMMUNITY)
                                    ->where('parent_id',0)
                                    ->where('status_id',Status::ACTIVE)->get();

        $categorySelect = [];

        $categorySelect = $category->mapWithKeys(function ($item) {
            $childCategory = Category::select('id','name')
                ->where('status_id',Status::ACTIVE)
                ->where('parent_id', $item->id)
                ->where('category_type_id',CategoryTypes::COMMUNITY)
                ->get();
            $childCategory= collect($childCategory)->mapWithKeys(function ($value) {
                return [$value->id => $value->name];
            })->toArray();

            return [$item->name => $childCategory];
        });

        return view('business.community.index', compact('title','category','categorySelect'));
    }

    public function getJsonAllData(Request $request){

        $user = Auth::user();
        $columns = array(
            0 => 'community.title',
            1 => 'category.name',
            2 => 'users_detail.name',
            3 => 'community.views_count',
            4 => 'comments_count',
            5 => 'community.created_at',
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
            $main_country = $this->getAdminCountryCode();
            $subCategory = Category::where('category_type_id',CategoryTypes::COMMUNITY)
                ->where('parent_id',$filter)
                ->where('status_id',Status::ACTIVE)->pluck('id'); 

            $communityQuery = DB::table('community')
                ->leftJoin('users_detail','users_detail.user_id','community.user_id')
                ->leftJoin('category','category.id','community.category_id')
                ->select(
                    'community.id',
                    'community.title',
                    'community.views_count',
                    'community.created_at',
                    'users_detail.name as user_name',
                    'users_detail.gender as user_gender',
                    'community.category_id',
                    'category.name as category_name'
                )
                ->selectSub(function($q) {
                    $q->select( DB::raw('count(distinct(id)) as total'))->from('community_comments')->whereNull('community_comments.deleted_at')->whereRaw("`community_comments`.`community_id` = `community`.`id`");
                }, 'comments_count')
                ->whereIn('community.category_id',$subCategory)
                ->where('community.country_code',$main_country)
                ->whereNull('community.deleted_at');

            if($user){
                $getBlockedUser = UserBlockHistory::select('block_user_id')->where(['user_id'=>$user->id,'block_for' => UserBlockHistory::COMMUNITY_POST, 'is_block' => 1])->get()->toArray();
                $whoBlockedMe = UserBlockHistory::select('user_id')->where(['block_user_id'=>$user->id,'block_for' => UserBlockHistory::COMMUNITY_POST, 'is_block' => 1])->get()->toArray();

                $whoBlockedMeArray = array_column($whoBlockedMe,'user_id');
                $blockedUser = array_column($getBlockedUser, 'block_user_id');
                $block = array_unique(array_merge($blockedUser,$whoBlockedMeArray));

                $communityQuery = $communityQuery->whereNotIn('community.user_id',$block);

            }
            if (!empty($search)) {
                $communityQuery = $communityQuery->where(function($q) use ($search){
                    $q->where('title', 'LIKE', "%{$search}%");
                });                    
            }

            $totalData = count($communityQuery->get());
            $totalFiltered = $totalData;
        
            $communityPosts = $communityQuery->offset($start)
                    ->limit($limit)
                    ->orderBy($order, $dir)  
                    ->get();

            $count = 0;
            foreach($communityPosts as $post){
                $detail = route('business.community.details', [$post->id]);
                //$seeDetail = "<a role='button' href='$detail'  title='' data-original-title='Delete Account' class='mx-1 btn btn-primary btn-sm' data-toggle='tooltip'>See Detail</a>";
                $seeDetail = '';
                $data[$count]['title'] = $post->title;
                $data[$count]['category'] = $post->category_name;
                $data[$count]['user'] = $post->user_name;
                $data[$count]['views_count'] = $post->views_count;
                $data[$count]['comment_count'] = $post->comments_count;
                $data[$count]['time_ago'] = timeAgo($post->created_at, 4,$adminTimezone);
                $data[$count]['actions'] = "<div class='d-flex'>$seeDetail</div>";
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

    public function store(Request $request)
    {
        $user = Auth::user();
        $inputs = $request->all();
        $validator = Validator::make($request->all(), [            
            'title' => 'required',
            'category_id' => 'required',
            'description' => 'required',
            "main_language_image"    => "required|array",
        ],[
            'main_language_image.min' => 'The image field is required.'
        ]);        
        
        if ($validator->fails()) {
            return response()->json(["errors" => $validator->messages()->toArray()], 422);
        }

        try {
            DB::beginTransaction();
            $main_country = $this->getAdminCountryCode();
            $requestData = [
                'category_id' => $inputs['category_id'],
                'title' => $inputs['title'],
                'description' => $inputs['description'],
                'user_id' => $user->id,  
                'country_code' => $main_country                  
            ];

            $community = Community::create($requestData);   

            $communityFolder = config('constant.community').'/'.$community->id;                    
            
            if (!Storage::disk('s3')->exists($communityFolder)) {
                Storage::disk('s3')->makeDirectory($communityFolder);
            } 
            if(!empty($inputs['main_language_image'])){
                foreach($inputs['main_language_image'] as $image) {
                    $mainImage = Storage::disk('s3')->putFile($communityFolder, $image,'public');
                    $fileName = basename($mainImage);
                    $image_url = $communityFolder . '/' . $fileName;
                    $temp = [
                        'community_id' => $community->id,
                        'image' => $image_url
                    ];
                    CommunityImages::create($temp);
                }
            }
            DB::commit();
            return response()->json(["success" => true, "message" => "Community". trans("messages.insert-success")], 200);
        } catch (\Exception $e) {
            Log::info($e);
            DB::rollBack();
            return response()->json(["success" => false, "message" => "Community". trans("messages.insert-error")], 200);
        }
    }

    public function showDetails($id){
        $title = 'Community Detail';

        $community = Community::find($id);
        
        return view('business.community.show', compact('title','community'));
    }
    
}
