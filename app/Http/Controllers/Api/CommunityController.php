<?php

namespace App\Http\Controllers\Api;

use App\Models\Community;
use App\Models\CommunityImages;
use App\Models\Category;
use App\Models\CategoryTypes;
use App\Models\Status;
use App\Models\CommunityLikes;
use App\Models\CommunityComments;
use App\Models\CommunityCommentLikes;
use App\Models\CommunityCommentReply;
use App\Models\CommunityCommentReplyLikes;
use App\Models\Banner;
use App\Models\SearchHistory;
use App\Models\EntityTypes;
use App\Models\Notice;
use App\Models\UserDetail;
use App\Models\UserDevices;
use App\Models\CategoryLanguage;
use App\Models\UserBlockHistory;
use App\Models\UserPoints;
use App\Validators\CommunityValidator;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Validator;
use Carbon\Carbon;
use App\Util\Firebase;


class CommunityController extends Controller
{
    private $communityValidator;
    protected $firebase;

    function __construct()
    {
        $this->communityValidator = new CommunityValidator();
        $this->firebase = new Firebase();
    }   
   
    public function updateCommunity(Request $request,$id){
        $user = Auth::user();
        $inputs = $request->all();
        
        DB::beginTransaction();
        try {
            $requestData = [
                'title' => $inputs['title'],
                'description' => $inputs['description']
            ];                    

            $community = Community::whereId($id)->update($requestData);   
            $communityFolder = config('constant.community').'/'.$id;
            if(!empty($inputs['images'])){
                foreach($inputs['images'] as $image) {
                    $mainImage = Storage::disk('s3')->putFile($communityFolder, $image,'public');
                    $fileName = basename($mainImage);
                    $image_url = $communityFolder . '/' . $fileName;
                    $temp = [
                        'community_id' => $id,
                        'image' => $image_url
                    ];
                    $addNew = CommunityImages::create($temp);
                }
            }        
            
            if(isset($inputs['delete_images']) && !empty($inputs['delete_images'])){
                $deleteImages = DB::table('community_images')->whereIn('id',$inputs['delete_images'])->get();
                foreach($deleteImages as $data){
                    if($data->image){
                        Storage::disk('s3')->delete($data->image);
                    }
                }
                CommunityImages::whereIn('id',$inputs['delete_images'])->delete();
            }

            DB::commit();
            $community = Community::find($id);
            return $this->sendSuccessResponse(Lang::get('messages.community.edit-success'), 200, $community);
        } catch (\Exception $e) {
            Log::info($e);
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }
    public function addCommunity(Request $request)
    {
        $user = Auth::user();
        $inputs = $request->all();
        try {
            Log::info('Start code for add community');   
            if($user){
                DB::beginTransaction();
                $validation = $this->communityValidator->validateStore($inputs);
                if ($validation->fails()) {
                    Log::info('End code for add community');
                    return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
                }
                $main_country = getCountryFromLatLong($inputs['latitude'],$inputs['longitude']);
                $requestData = [
                    'category_id' => $inputs['category_id'],
                    'title' => $inputs['title'],
                    'description' => $inputs['description'],
                    'user_id' => $user->id,  
                    'country_code' => $main_country                  
                ];                    
    
                $community = Community::create($requestData);   

                $communityFolder = config('constant.community').'/'.$community->id;                    
                
                if (!Storage::exists($communityFolder)) {
                    Storage::makeDirectory($communityFolder);
                } 
                    
                
                if(!empty($inputs['images'])){
                    foreach($inputs['images'] as $image) {
                        $mainImage = Storage::disk('s3')->putFile($communityFolder, $image,'public');
                        $fileName = basename($mainImage);
                        $image_url = $communityFolder . '/' . $fileName;
                        $temp = [
                            'community_id' => $community->id,
                            'image' => $image_url
                        ];
                        $addNew = CommunityImages::create($temp);
                    }
                }

                $isAvailable = UserPoints::where(['user_id' => $user->id,'entity_type' => UserPoints::UPLOAD_COMMUNITY_POST,'entity_created_by_id' => $user->id])->whereDate('created_at',Carbon::now()->format('Y-m-d'))->first();

                if(empty($isAvailable)){
                  
                    UserPoints::create([
                        'user_id' => $user->id,
                        'entity_type' => UserPoints::UPLOAD_COMMUNITY_POST,
                        'entity_id' => $community->id,
                        'entity_created_by_id' => $user->id,
                        'points' => UserPoints::UPLOAD_COMMUNITY_POST_POINT]); 

                    // Send Push notification start
                    $notice = Notice::create([
                        'notify_type' => Notice::UPLOAD_COMMUNITY_POST,
                        'user_id' => $user->id,
                        'to_user_id' => $user->id,
                        'entity_type_id' => EntityTypes::COMMUNITY,
                        'entity_id' => $community->id,
                        'title' => '+'.UserPoints::UPLOAD_COMMUNITY_POST_POINT.'exp',
                        'sub_title' => '',
                        'is_aninomity' => 0
                    ]);

                    $user_detail = UserDetail::where('user_id', $user->id)->first();
                    $language_id = $user_detail->language_id;
                    $key = Notice::UPLOAD_COMMUNITY_POST.'_'.$language_id;
                    $userIds = [$user->id];

                    $format = '+'.UserPoints::UPLOAD_COMMUNITY_POST_POINT.'exp';
                    $devices = UserDevices::whereIn('user_id', $userIds)->pluck('device_token')->toArray();

                    $title_msg = __("notice.$key");
                    $notify_type = Notice::UPLOAD_COMMUNITY_POST;

                    $notificationData = [
                        'id' => $community->id,
                        'user_id' => $user->id,
                        'title' => $community->title,
                    ];
                    if (count($devices) > 0) {
                        $result = $this->sentPushNotification($devices,$title_msg, $format, $notificationData ,$notify_type);                        
                    }
                            // Send Push notification end
                }       
                
                DB::commit();
                Log::info('End code for the add community');
                return $this->sendSuccessResponse(Lang::get('messages.community.add-success'), 200, $community);
                
            }else{
                Log::info('End code for add community');
                return $this->sendSuccessResponse(Lang::get('messages.user.token_expired'), 401);
            }   
        } catch (\Exception $e) {
            Log::info('Exception in add community');
            Log::info($e);
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function getCommunity(Request $request)
    {
        try {
            Log::info('Start code get community');  
            $inputs = $request->all();   
            
                DB::beginTransaction();
                $validation = $this->communityValidator->validateGetCommunity($inputs);
                if ($validation->fails()) {
                    Log::info('End code for get community');
                    return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
                }
                $search = isset($inputs['search_keyword']) ? $inputs['search_keyword'] : '';
                $language_id = isset($inputs['language_id']) ? $inputs['language_id'] : 4;
                if(isset($inputs['latitude']) && isset($inputs['longitude'])) {
                    $main_country = getCountryFromLatLong($inputs['latitude'],$inputs['longitude']);
                    $timezone = get_nearest_timezone($inputs['latitude'],$inputs['longitude'],$main_country);  
                }else {
                    $timezone = '';
                }

                $user = Auth::user();
                   
                $subCategory = Category::where('category_type_id',CategoryTypes::COMMUNITY)
                ->where('parent_id',$inputs['category_id'])
                ->where('status_id',Status::ACTIVE)->pluck('id');               
                
                $communityQueryPopular = DB::table('community')
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

                $communityQueryBest = DB::table('community')
                    ->leftJoin('users_detail','users_detail.user_id','community.user_id')
                    ->leftJoin('category','category.id','community.category_id')
                    ->select(
                        'community.id',
                        'community.title',
                        'community.views_count',
                        'community.created_at',
                        'users_detail.name as user_name',
                        'community.category_id',
                        'category.name as category_name'
                    )
                    ->selectSub(function($q) {
                        $q->select( DB::raw('count(distinct(id)) as total'))->from('community_comments')->whereNull('community_comments.deleted_at')->whereRaw("`community_comments`.`community_id` = `community`.`id`");
                    }, 'comments_count')
                    ->whereIn('community.category_id',$subCategory)
                    ->where('community.country_code',$main_country)
                    ->whereDate('community.created_at', '=', Carbon::now())
                    ->whereNull('community.deleted_at');

                $communityQueryRecent = DB::table('community')
                    ->leftJoin('users_detail','users_detail.user_id','community.user_id')
                    ->leftJoin('category','category.id','community.category_id')
                    ->select(
                        'community.id',
                        'community.title',
                        'community.views_count',
                        'community.created_at',
                        'users_detail.name as user_name',
                        'community.category_id',
                        'category.name as category_name'
                    )
                    ->selectSub(function($q) {
                        $q->select( DB::raw('count(distinct(id)) as total'))->from('community_comments')->whereNull('community_comments.deleted_at')->whereRaw("`community_comments`.`community_id` = `community`.`id`");
                    }, 'comments_count')
                    ->whereIn('category_id',$subCategory)
                    ->where('country_code',$main_country)
                    ->whereNull('community.deleted_at');

                if(!empty($inputs['sub_category_id'])){
                    $communityQueryPopular = $communityQueryPopular->where('category_id',$inputs['sub_category_id']);
                    $communityQueryBest = $communityQueryBest->where('category_id',$inputs['sub_category_id']);
                    $communityQueryRecent = $communityQueryRecent->where('category_id',$inputs['sub_category_id']);
                }

                if($user){
                   $getBlockedUser = UserBlockHistory::select('block_user_id')->where(['user_id'=>$user->id,'block_for' => UserBlockHistory::COMMUNITY_POST, 'is_block' => 1])->get()->toArray();
                   $whoBlockedMe = UserBlockHistory::select('user_id')->where(['block_user_id'=>$user->id,'block_for' => UserBlockHistory::COMMUNITY_POST, 'is_block' => 1])->get()->toArray();

                   $whoBlockedMeArray = array_column($whoBlockedMe,'user_id');
                   $blockedUser = array_column($getBlockedUser, 'block_user_id');
                   $block = array_unique(array_merge($blockedUser,$whoBlockedMeArray));

                   $communityQueryPopular = $communityQueryPopular->whereNotIn('community.user_id',$block);

                   $communityQueryBest = $communityQueryBest->whereNotIn('community.user_id',$block);

                   $communityQueryRecent = $communityQueryRecent->whereNotIn('community.user_id',$block);

               }

                if($search != ''){
                    $communityQueryPopular = $communityQueryPopular->where('title','LIKE', "%{$search}%");
                    $communityQueryBest = $communityQueryBest->where('title','LIKE', "%{$search}%");
                    $communityQueryRecent = $communityQueryRecent->where('title','LIKE', "%{$search}%");
                }

                $communityPopular = $communityQueryPopular->orderBy('comments_count','desc')
                                    ->paginate(config('constant.pagination_count'),"*","popular_list_page");

               

                $communityBest = $communityQueryBest->orderBy('comments_count','desc')
                                    ->paginate(config('constant.pagination_count'),"*","best_page");

                $communityRecent = $communityQueryRecent->orderBy('id','desc')->paginate(config('constant.pagination_count'),"*","recent_page");
               // $query = DB::getQueryLog();
//print_r($query);die;


                $bannerImages = Banner::join('banner_images', 'banners.id', '=', 'banner_images.banner_id')
                                    ->where('banners.entity_type_id',EntityTypes::COMMUNITY)
                                    ->where('banners.section','home')
                                    ->whereNull('banners.deleted_at')
                                    ->whereNull('banner_images.deleted_at')
                                    ->where('banners.country_code',$main_country)
                                    ->where('banners.category_id',$inputs['category_id'])
                                    ->orderBy('banner_images.order','desc')->orderBy('banner_images.id','desc')
                                    ->get('banner_images.*');

                if($user && $search != '') {

                    $searchData = SearchHistory::where('keyword',$search)->where('user_id',$user->id)->where('category_id',$inputs['category_id'])->where('entity_type_id',EntityTypes::COMMUNITY)->first();
                    if($searchData) {
                        SearchHistory::where('id',$searchData->id)->update([
                            'keyword' => $search, 
                            'entity_type_id' => EntityTypes::COMMUNITY,                       
                            'category_id' => $inputs['category_id'],
                            'user_id' => $user->id 
                        ]);
                    }else{
                        SearchHistory::create([
                            'keyword' => $search, 
                            'entity_type_id' => EntityTypes::COMMUNITY,   
                            'category_id' => $inputs['category_id'],                    
                            'user_id' => $user->id 
                        ]);
                    }
                    
                }
                $sliders = [];
                foreach($bannerImages as $banner){
                    $temp = [];
                    $temp['image'] = Storage::disk('s3')->url($banner->image);
                    $temp['link'] = $banner->link;
                    $temp['slide_duration'] = $banner->slide_duration;
                    $temp['order'] = $banner->order;
                    $sliders[] = $temp;
                }
                $data['popular_list'] = $this->timeLanguageFilterNew(collect($communityPopular)->toArray(),$language_id,$timezone);
                $data['recent_list']['best'] = $this->timeLanguageFilterNew(collect($communityBest)->toArray(),$language_id,$timezone);
                $data['recent_list']['recent'] = $this->timeLanguageFilterNew(collect($communityRecent)->toArray(),$language_id,$timezone);
                $data['banner_images'] = $sliders;
               
                DB::commit();
                Log::info('End code for the get community');
            return $this->sendSuccessResponse(Lang::get('messages.community.get-success'), 200,$data);
            
        } catch (\Exception $e) {
            print_r($e->getMessage());die;
            Log::info('Exception in get community');
            Log::info($e);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    } 

    public function getCommunityDetail(Request $request,$id)
    {       
        try {
            Log::info('Start code get community detail');  
            $inputs = $request->all(); 
            $communityExists = Community::find($id);
            if($communityExists){
                DB::beginTransaction(); 
                $validation = Validator::make($request->all(), [
                    'latitude' => 'required',
                    'longitude' => 'required',
                ], [], [
                    'latitude' => 'Latitude',
                    'longitude' => 'Longitude',
                ]);
    
                if ($validation->fails()) {
                    Log::info('End code for get community detail');
                    return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
                }
                $language_id = isset($inputs['language_id']) ? $inputs['language_id'] : 4;
                if(isset($inputs['latitude']) && isset($inputs['longitude'])) {
                    $main_country = getCountryFromLatLong($inputs['latitude'],$inputs['longitude']);
                    $timezone = get_nearest_timezone($inputs['latitude'],$inputs['longitude'],$main_country);  
                }else {
                    $timezone = '';
                }  
                Community::where('id',$id)->update(['views_count' => DB::raw('views_count + 1')]);
                $data = Community::where('id',$id)->first();
                $returnData = $data->toArray(); 
                $returnData['time_difference'] = timeAgo($returnData['created_at'], $language_id,$timezone);

                $returnData['comments'] = $this->commentTimeFilter($returnData['comments'],$language_id,$timezone);
                                
                DB::commit();
                Log::info('End code for the get community detail');
                return $this->sendSuccessResponse(Lang::get('messages.community.get-success'), 200,$returnData);
            }else{
                return $this->sendSuccessResponse(Lang::get('messages.community.empty'), 402);
            }
            
        } catch (\Exception $e) {
            Log::info('Exception in get community detail');
            Log::info($e);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    } 

    public function getCommunityCategories(Request $request)
    {       
        try {
            Log::info('Start code get community category');  
            $inputs = $request->all();            
           
                DB::beginTransaction();
                $categories = [];
                $parentCategory = Category::where('category_type_id',CategoryTypes::COMMUNITY)
                                    ->where('parent_id',0)
                                    ->where('status_id',Status::ACTIVE)->get();
                foreach($parentCategory as $pc){
                    $subCategory = Category::where('category_type_id',CategoryTypes::COMMUNITY)
                    ->where('parent_id',$pc->id)
                    ->where('status_id',Status::ACTIVE)->get(['id','name','category_type_id','parent_id','status_id']);

                    if(isset($inputs['language_id']) && !empty($inputs['language_id'])){
                        foreach($subCategory as $category) {
                            $category_language = CategoryLanguage::where('category_id',$category->id)->where('post_language_id',$inputs['language_id'])->first();
                            $category['name'] = $category_language && $category_language->name != NULL ? $category_language->name : $category->name;
                        }
                    }
                    $categories[] = [
                        'id' => $pc->id,
                        'name' => $pc->name,
                        'sub_category' => $subCategory
                    ];
                }
               
                DB::commit();
                Log::info('End code for the get community category');
            return $this->sendSuccessResponse(Lang::get('messages.community.category-success'), 200,compact('categories'));
            
        } catch (\Exception $e) {
            Log::info('Exception in get community category');
            Log::info($e);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function likeCommunity($id)
    {       
        try {
            Log::info('Start code like community');  
            $user = Auth::user();
            if($user){
                $community = Community::find($id);
                if($community){
                    DB::beginTransaction();                    
                    $data = [
                        'user_id' => $user->id,
                        'community_id' => $id,                    
                    ];
                    $communityLike = CommunityLikes::create($data);
                    $community = Community::find($id);

                    $notice = Notice::create([
                        'notify_type' => Notice::COMMUNITY_POST_LIKE,
                        'user_id' => $user->id,
                        'to_user_id' => $community->user_id,
                        'entity_type_id' => EntityTypes::COMMUNITY,
                        'entity_id' => $community->id,
                        'title' => $community->title,
                        // 'sub_title' => $inputs['comment']
                    ]);

                    UserPoints::updateOrCreate([
                        'user_id' => $community->user_id,
                        'entity_type' => UserPoints::LIKE_COMMUNITY_OR_REVIEW_POST,
                        'entity_created_by_id' => $user->id,
                    ],['entity_id' => $id, 'points' => UserPoints::LIKE_COMMUNITY_OR_REVIEW_POST_POINT]);

                   
                    $isNotice = Notice::where(['notify_type' => Notice::LIKE_COMMUNITY_OR_REVIEW_POST,'user_id' => $user->id,'to_user_id' => $community->user_id])->whereDate('created_at',Carbon::now()->format('Y-m-d'))->count();

                    if($isNotice == 0){
                    // Send Push notification start
                        $user_detail = UserDetail::where('user_id', $community->user_id)->first();
                        $language_id = $user_detail->language_id;

                        $nextCardLevel = getUserNextAwailLevel($community->user_id,$user_detail->level);
                        $next_level_key = "language_$language_id.next_level_card";
                        $next_level_msg = __("messages.$next_level_key", ['level' => $nextCardLevel]);

                        $notice = Notice::create([
                            'notify_type' => Notice::LIKE_COMMUNITY_OR_REVIEW_POST,
                            'user_id' => $user->id,
                            'to_user_id' => $community->user_id,
                            'entity_type_id' => EntityTypes::COMMUNITY,
                            'entity_id' => $community->id,
                            'title' => '+'.UserPoints::LIKE_COMMUNITY_OR_REVIEW_POST_POINT.'exp',
                            'sub_title' => $nextCardLevel,
                            'is_aninomity' => 0
                        ]);

                        $key = Notice::LIKE_COMMUNITY_OR_REVIEW_POST.'_'.$language_id;

                        $format =  '+'.UserPoints::LIKE_COMMUNITY_OR_REVIEW_POST_POINT.'exp \n'.$next_level_msg;
                        $devices = UserDevices::whereIn('user_id', [$community->user_id])->pluck('device_token')->toArray();

                        $title_msg = __("notice.$key");
                        $notify_type = Notice::LIKE_COMMUNITY_OR_REVIEW_POST;

                        $notificationData = [
                            'id' => $community->id,
                            'user_id' => $community->user_id,
                            'title' => $title_msg,
                        ];

                        //print_r($notificationData);die;
                        if (count($devices) > 0) {
                            $result = $this->sentPushNotification($devices,$title_msg, $format, $notificationData ,$notify_type);                        
                        }
                            // Send Push notification end
                    }

                    $user_detail = UserDetail::where('user_id', $community->user_id)->first();
                    $language_id = $user_detail->language_id;
                    $key = Notice::COMMUNITY_POST_LIKE.'_'.$language_id;
                    
                    $userIds = [$community->user_id];
                    
                    $format = __("notice.$key", ['username' => $user->name]);
                    $devices = UserDevices::whereIn('user_id', $userIds)->pluck('device_token')->toArray();
                    $title_msg = '';
                    $notify_type = Notice::COMMUNITY_POST_LIKE;
                    
                    $notificationData = [
                        'id' => $community->id,
                        'user_id' => $community->user_id,
                        'category_id' => $community->category_id,
                        'category_name' => $community->category_name,
                        'title' => $community->title,
                        'description' => $community->description,
                    ];
                    if (count($devices) > 0) {
                        $result = $this->sentPushNotification($devices,$title_msg, $format, $notificationData ,$notify_type,$community->id);                        
                    }

                    DB::commit();
                    Log::info('End code for the like community');
                    return $this->sendSuccessResponse(Lang::get('messages.community.like-success'), 200,$communityLike);
                }else{
                    return $this->sendSuccessResponse(Lang::get('messages.community.empty'), 402);
                }
            }else{
                Log::info('End code for like community');
                return $this->sendSuccessResponse(Lang::get('messages.user.token_expired'), 401);
            }  
        } catch (\Exception $e) {
            Log::info('Exception in like community');
            Log::info($e);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    } 

    public function unlikeCommunity($id)
    {       
        try {
            Log::info('Start code unlike community');  
            $user = Auth::user();
            if($user){
                $community = Community::find($id);
                if($community){
                    DB::beginTransaction();                    
                    $data = [
                        'user_id' => $user->id,
                        'community_id' => $id,                    
                    ];
                    $communityunLike = CommunityLikes::where('user_id',$user->id)->where('community_id',$id)->forcedelete();

                    UserPoints::where([
                        'user_id' => $user->id,
                        'entity_type' => UserPoints::LIKE_COMMUNITY_POST,
                        'entity_id' => $id,
                        'entity_created_by_id' => $community->user_id,
                    ])->delete();

                    DB::commit();
                    Log::info('End code for the unlike community');
                    return $this->sendSuccessResponse(Lang::get('messages.community.unlike-success'), 200);
                }else{
                    return $this->sendSuccessResponse(Lang::get('messages.community.empty'), 402);
                }
            }else{
                Log::info('End code for unlike community');
                return $this->sendSuccessResponse(Lang::get('messages.user.token_expired'), 401);
            }  
        } catch (\Exception $e) {
            Log::info('Exception in unlike community');
            Log::info($e);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    } 

    public function commentCommunity(Request $request, $id)
    {       
        try {
            Log::info('Start code comment community');  
            $user = Auth::user();
            $inputs = $request->all();
            if($user){
                $review = Community::find($id);
                $is_aninomity = !empty($inputs['is_aninomity']) ? $inputs['is_aninomity'] : 0;

                if($review){
                    DB::beginTransaction();    
                    $validation = $this->communityValidator->validateCommunityComment($inputs);
                    if ($validation->fails()) {
                        Log::info('End code for comment community');
                        return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
                    }     
                    
                    /* $users_detail = DB::table('community')->join('users_detail','users_detail.user_id','community.user_id')
                            ->whereNull('community.deleted_at')
                            ->where('community.id',$id)
                            ->select('users_detail.*')
                            ->first();

                    $is_unread = 0;
                    if($users_detail && $users_detail->is_outside){
                        $is_unread = 1;
                    } */
                    
                    $data = [
                        'user_id' => $user->id,
                        'community_id' => $id,   
                        'comment' => $inputs['comment'],
                        'is_admin_read' => 1        
                    ];
                    $comment = CommunityComments::create($data);
                    $community = Community::find($id);

                    if($user->id != $community->user_id){
                    
                        $isAvailable = UserPoints::where(['user_id' => $user->id,'entity_type' => UserPoints::COMMENT_ON_COMMUNITY_POST,'entity_created_by_id' => $user->id])->whereDate('created_at',Carbon::now()->format('Y-m-d'))->first();

                        if(empty($isAvailable)){

                            UserPoints::create([
                                'user_id' => $user->id,
                                'entity_type' => UserPoints::COMMENT_ON_COMMUNITY_POST,
                                'entity_id' => $comment->id,
                                'entity_created_by_id' => $user->id,
                                'points' => UserPoints::COMMENT_ON_COMMUNITY_POST_POINT]); 

                             // Send Push notification start
                            $notice = Notice::create([
                                'notify_type' => Notice::COMMENT_ON_COMMUNITY_POST,
                                'user_id' => $user->id,
                                'to_user_id' => $user->id,
                                'entity_type_id' => EntityTypes::COMMUNITY,
                                'entity_id' => $community->id,
                                'title' => '+'.UserPoints::COMMENT_ON_COMMUNITY_POST_POINT.'exp',
                                'sub_title' => '',
                                'is_aninomity' => 0
                            ]);

                            $user_detail = UserDetail::where('user_id', $user->id)->first();
                            $language_id = $user_detail->language_id;
                            $key = Notice::COMMENT_ON_COMMUNITY_POST.'_'.$language_id;
                            $userIds = [$user->id];

                            $format = '+'.UserPoints::COMMENT_ON_COMMUNITY_POST_POINT.'exp';
                            $devices = UserDevices::whereIn('user_id', $userIds)->pluck('device_token')->toArray();

                            $title_msg = __("notice.$key");
                            $notify_type = Notice::COMMENT_ON_COMMUNITY_POST;

                            $notificationData = [
                                'id' => $community->id,
                                'user_id' => $user->id,
                                'title' => $title_msg,
                            ];
                            if (count($devices) > 0) {
                                $result = $this->sentPushNotification($devices,$title_msg, $format, $notificationData ,$notify_type);                        
                            }
                            // Send Push notification end
                        }

                        $notice = Notice::create([
                            'notify_type' => Notice::COMMUNITY_POST_COMMENT,
                            'user_id' => $user->id,
                            'to_user_id' => $community->user_id,
                            'entity_type_id' => EntityTypes::COMMUNITY,
                            'entity_id' => $community->id,
                            'title' => $community->title,
                            'sub_title' => $inputs['comment'],
                            'is_aninomity' => $is_aninomity
                        ]);

                        $user_detail = UserDetail::where('user_id', $community->user_id)->first();
                        $language_id = $user_detail->language_id;
                        $key = Notice::COMMUNITY_POST_COMMENT.'_'.$language_id;

                        $userIds = [$community->user_id];

                        $userName = ($is_aninomity == 1) ? substr($user->name,0,1) : $user->name;

                        $format = __("notice.$key", ['username' => $userName]);
                        $devices = UserDevices::whereIn('user_id', $userIds)->pluck('device_token')->toArray();
                        $title_msg = '';
                        $notify_type = 'notices';

                        $notificationData = $community->toArray();
                        if (count($devices) > 0) {
                            $result = $this->sentPushNotification($devices,$title_msg, $format, [] ,$notify_type);                        
                        }
                    }
                    
                    DB::commit();
                    Log::info('End code for the comment community');
                    return $this->sendSuccessResponse(Lang::get('messages.community.comment-success'), 200,$comment);
                }else{
                    return $this->sendSuccessResponse(Lang::get('messages.community.empty'), 402);
                }
            }else{
                Log::info('End code for comment community');
                return $this->sendSuccessResponse(Lang::get('messages.user.token_expired'), 401);
            }  
        } catch (\Exception $e) {
            print_r($e->getMessage());die;
            Log::info('Exception in comment community');
            Log::info($e);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    } 

    public function communityCommentReply(Request $request, $id)
    {       
        try {
            Log::info('Start code comment community reply');  
            $user = Auth::user();
            $inputs = $request->all();
            if($user){
                $community = CommunityComments::find($id);
                $is_aninomity = !empty($inputs['is_aninomity']) ? $inputs['is_aninomity'] : 0;

                if($community){
                    DB::beginTransaction();    
                    $validation = $this->communityValidator->validateCommunityComment($inputs);
                    if ($validation->fails()) {
                        Log::info('End code for comment community reply');
                        return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
                    }  
                    
                    /* $users_detail = DB::table('community_comments')->join('users_detail','users_detail.user_id','community_comments.user_id')
                            ->whereNull('community_comments.deleted_at')
                            ->where('community_comments.id',$id)
                            ->select('users_detail.*')
                            ->first();

                    $is_reply_unread = 0;
                    if($users_detail && $users_detail->is_outside){
                        $is_reply_unread = 1;
                    } */
                    
                    $data = [
                        'user_id' => $user->id,
                        'community_comment_id' => $id,   
                        'comment' => $inputs['comment'],
                        'reply_parent_id' => $request->has('main_comment_reply_id') && $inputs['main_comment_reply_id'] != 0 ? $inputs['main_comment_reply_id'] : null,
                        'is_admin_read' => 1
                    ];


                    $communityLike = CommunityCommentReply::create($data);
                    $community = CommunityComments::find($id);
                   // print_r($community);die;

                    // Send Notification and notice to parent
                    if($request->has('main_comment_reply_id') && $inputs['main_comment_reply_id'] != 0){
                        $parentCommentId = $inputs['main_comment_reply_id'];

                        $parentComment = CommunityCommentReply::where(['id' => $parentCommentId])->first();

                        if($user->id != $parentComment->user_id){

                            Notice::create([
                                'notify_type' => Notice::COMMUNITY_REPLY_COMMENT,
                                'user_id' => $user->id,
                                'to_user_id' => $parentComment->user_id,
                                'entity_type_id' => EntityTypes::COMMUNITY,
                                'entity_id' => $id,
                                'sub_title' => $inputs['comment'],
                                'is_aninomity' => $is_aninomity
                            ]);

                            $user_detail = UserDetail::where('user_id', $parentComment->user_id)->first();

                            $language_id = $user_detail->language_id;
                            $key = Notice::COMMUNITY_REPLY_COMMENT.'_'.$language_id;

                            $userIds = [$parentComment->user_id];
                            $userName = ($is_aninomity == 1) ? substr($user->name,0,1) : $user->name;

                            $format = __("notice.$key", ['username' => $userName]);
                            $devices = UserDevices::whereIn('user_id', $userIds)->pluck('device_token')->toArray();
                            $title_msg = '';
                            $notify_type = 'notices';

                            $notificationData = $community->toArray();
                            if (count($devices) > 0) {
                                $result = $this->sentPushNotification($devices,$title_msg, $format, [] ,$notify_type);                        
                            }

                        }

                    }

                    // Send Notification and notice to Main parent Comment
                    if(empty($inputs['main_comment_reply_id'])){
                        if($user->id != $community->user_id){
                            Notice::create([
                                'notify_type' => Notice::COMMUNITY_REPLY_COMMENT,
                                'user_id' => $user->id,
                                'to_user_id' => $community->user_id,
                                'entity_type_id' => EntityTypes::COMMUNITY,
                                'entity_id' => $community->community_id,
                                'sub_title' => $inputs['comment'],
                                'is_aninomity' => $is_aninomity
                            ]);

                            $user_detail = UserDetail::where('user_id', $community->user_id)->first();
                            $language_id = $user_detail->language_id;
                            $key = Notice::COMMUNITY_REPLY_COMMENT.'_'.$language_id;

                            $userIds = [$community->user_id];

                            $userName = ($is_aninomity == 1) ? substr($user->name,0,1) : $user->name;

                            $format = __("notice.$key", ['username' => $userName]);

                           // $format = __("notice.$key", ['username' => $user->name]);
                            $devices = UserDevices::whereIn('user_id', $userIds)->pluck('device_token')->toArray();
                            $title_msg = '';
                            $notify_type = 'notices';

                            $notificationData = $community->toArray();
                            if (count($devices) > 0) {
                                $result = $this->sentPushNotification($devices,$title_msg, $format, [] ,$notify_type);                        
                            }
                        }
                    }
                    

                    DB::commit();
                    Log::info('End code for the comment community reply');
                    return $this->sendSuccessResponse(Lang::get('messages.community.comment-success'), 200,$communityLike);
                }else{
                    return $this->sendSuccessResponse(Lang::get('messages.community.comment-empty'), 402);
                }
            }else{
                Log::info('End code for comment community reply');
                return $this->sendSuccessResponse(Lang::get('messages.user.token_expired'), 401);
            }  
        } catch (\Exception $e) {
            print_r($e->getMessage());die;
            Log::info('Exception in comment community reply');
            Log::info($e);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    } 
    
    public function likeCommunityComment($id)
    {       
        try {
            Log::info('Start code like community comment');  
            $user = Auth::user();
            if($user){
                $communityComment = CommunityComments::find($id);
                $communityLike = CommunityCommentLikes::where(['user_id' => $user->id,'community_comment_id' => $id])->first();

                if($communityComment){
                    DB::beginTransaction();                    
                    $data = [
                        'user_id' => $user->id,
                        'community_comment_id' => $id,                    
                    ];
                    if(empty($communityLike)){
                        $communityLike = CommunityCommentLikes::create($data);
                    }
                    
                    $community = CommunityComments::find($id);
                    DB::commit();
                    Log::info('End code for the like community comment');
                    return $this->sendSuccessResponse(Lang::get('messages.community.comment-like-success'), 200,$communityLike);
                }else{
                    return $this->sendSuccessResponse(Lang::get('messages.community.comment-empty'), 402);
                }
            }else{
                Log::info('End code for like community comment');
                return $this->sendSuccessResponse(Lang::get('messages.user.token_expired'), 401);
            }  
        } catch (\Exception $e) {
            Log::info('Exception in like community comment');
            Log::info($e);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    } 

    public function deleteCommunityComment($id)
    {       
        try {
            Log::info('Start code delete community comment');  
            $user = Auth::user();
            if($user){
                $reviewComment = CommunityComments::find($id);
                if($reviewComment){
                    DB::beginTransaction();
                    $review = CommunityComments::where('id',$id)->delete();
                    DB::commit();
                    Log::info('End code for the delete community comment');
                    return $this->sendSuccessResponse(Lang::get('messages.community.comment-delete-success'), 200,[]);
                }else{
                    return $this->sendSuccessResponse(Lang::get('messages.community.comment-empty'), 402);
                }
            }else{
                Log::info('End code for delete community comment');
                return $this->sendSuccessResponse(Lang::get('messages.user.token_expired'), 401);
            }  
        } catch (\Exception $e) {
            Log::info('Exception in delete community comment');
            Log::info($e);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    } 

    public function communityCommentReplyLike(Request $request, $id)
    {       
        try {
            Log::info('Start code comment community reply like');  
            $user = Auth::user();
            $inputs = $request->all();
            if($user){
                $reviewCommentReply = CommunityCommentReply::find($id);
                if($reviewCommentReply){
                    DB::beginTransaction();  
                    $data = [
                        'user_id' => $user->id,
                        'review_comment_reply_id' => $id,                    
                    ];
                    $reviewLike = CommunityCommentReplyLikes::create($data);
                    $return = CommunityCommentReply::find($id);
                    DB::commit();
                    Log::info('End code for the comment community reply like');
                    return $this->sendSuccessResponse(Lang::get('messages.community.like-success'), 200,$reviewLike);
                }else{
                    return $this->sendSuccessResponse(Lang::get('messages.community.comment-empty'), 402);
                }
            }else{
                Log::info('End code for comment community reply like');
                return $this->sendSuccessResponse(Lang::get('messages.user.token_expired'), 401);
            }  
        } catch (\Exception $e) {
            Log::info('Exception in comment community reply like');
            Log::info($e);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    } 
    public function communityCommentReplyDelete(Request $request, $id)
    {       
        try {
            Log::info('Start code comment community reply delete');  
            $user = Auth::user();
            $inputs = $request->all();
            if($user){
                $reviewCommentReply = CommunityCommentReply::find($id);
                if($reviewCommentReply){
                    DB::beginTransaction();  
                    $reviewReply = CommunityCommentReply::where('id',$id)->delete();
                    DB::commit();
                    Log::info('End code for the comment community reply delete');
                    return $this->sendSuccessResponse(Lang::get('messages.community.comment-delete-success'), 200,[]);
                }else{
                    return $this->sendSuccessResponse(Lang::get('messages.community.comment-empty'), 402);
                }
            }else{
                Log::info('End code for comment community reply delete');
                return $this->sendSuccessResponse(Lang::get('messages.user.token_expired'), 401);
            }  
        } catch (\Exception $e) {
            Log::info('Exception in comment community reply delete');
            Log::info($e);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    } 
    
    public function updateCommunityComment(Request $request,$id)
    {       
        try {
            Log::info('Start code edit community comment');  
            $inputs = $request->all();
            $user = Auth::user();
            if($user){
                $reviewComment = CommunityComments::find($id);
                if($reviewComment){
                    DB::beginTransaction();  
                    $validation = $this->communityValidator->validateCommunityComment($inputs);
                    if ($validation->fails()) {
                        Log::info('End code for comment review');
                        return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
                    } 
                    $review = CommunityComments::where('id',$id)->update(['comment' => $inputs['comment']]);
                    $reviewComment = CommunityComments::find($id);
                    DB::commit();
                    Log::info('End code for the edit community comment');
                    return $this->sendSuccessResponse(Lang::get('messages.community.comment-edit-success'), 200,$reviewComment);
                }else{
                    return $this->sendSuccessResponse(Lang::get('messages.community.comment-empty'), 402);
                }
            }else{
                Log::info('End code for edit community comment');
                return $this->sendSuccessResponse(Lang::get('messages.user.token_expired'), 401);
            }  
        } catch (\Exception $e) {
            Log::info('Exception in edit community comment');
            Log::info($e);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    } 

    public function updateCommunityCommentReply(Request $request, $id)
    {       
        try {
            Log::info('Start code comment community reply update');  
            $user = Auth::user();
            $inputs = $request->all();
            if($user){
                $reviewCommentReply = CommunityCommentReply::find($id);
                if($reviewCommentReply){
                    DB::beginTransaction();  
                    $reviewReply = CommunityCommentReply::where('id',$id)->update(['comment' => $inputs['comment']]);
                    DB::commit();
                    $reviewCommentReply = CommunityCommentReply::find($id);
                    Log::info('End code for the comment community reply update');
                    return $this->sendSuccessResponse(Lang::get('messages.community.comment-edit-success'), 200,$reviewCommentReply);
                }else{
                    return $this->sendSuccessResponse(Lang::get('messages.community.comment-empty'), 402);
                }
            }else{
                Log::info('End code for comment community reply update');
                return $this->sendSuccessResponse(Lang::get('messages.user.token_expired'), 401);
            }  
        } catch (\Exception $e) {
            Log::info('Exception in comment community reply update');
            Log::info($e);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    } 

    public function deleteCommunity($id)
    {       
        try {
            Log::info('Start code delete community');  
            $user = Auth::user();
            if($user){
                $reviewComment = Community::find($id);
                if($reviewComment){
                    DB::beginTransaction();  

                    UserPoints::where([
                        'entity_type' => UserPoints::UPLOAD_COMMUNITY_POST,
                        'entity_id' => $id,
                    ])->delete(); 

                    $review = Community::where('id',$id)->delete();
                    DB::commit();
                    Log::info('End code for the delete community');
                    return $this->sendSuccessResponse(Lang::get('messages.community.delete-success'), 200,[]);
                }else{
                    return $this->sendSuccessResponse(Lang::get('messages.community.comment-empty'), 402);
                }
            }else{
                Log::info('End code for delete community');
                return $this->sendSuccessResponse(Lang::get('messages.user.token_expired'), 401);
            }  
        } catch (\Exception $e) {
            Log::info('Exception in delete community');
            Log::info($e);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    } 
}
