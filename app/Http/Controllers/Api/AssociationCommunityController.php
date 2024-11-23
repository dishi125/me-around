<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use DB;
use Illuminate\Support\Facades\Lang;
use App\Validators\AssociationCommunityValidator;
use App\Models\AssociationCategory;
use App\Models\AssociationCommunity;
use App\Models\AssociationCommunityImage;
use App\Models\AssociationUsers;
use App\Models\Association;
use App\Models\SearchHistory;
use App\Models\AssociationImage;
use App\Models\EntityTypes;
use App\Models\AssociationLikes;
use App\Models\UserBlockHistory;
use App\Models\AssociationCommunityComment;
use App\Models\Notice;
use App\Models\UserDetail;
use App\Models\UserDevices;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use App\Util\Firebase;

class AssociationCommunityController extends Controller
{

    private $associationCommunityValidator;
    protected $firebase;
    function __construct()
    {
        $this->associationCommunityValidator = new AssociationCommunityValidator();
        $this->firebase = new Firebase();
    }

    public function updateCommunity(Request $request, $id){
        $user = Auth::user();
        $inputs = $request->all();
        DB::beginTransaction();
        try {
            $validation = $this->associationCommunityValidator->validateStore($inputs);
            if ($validation->fails()) {
                return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
            }

            $requestData = [
                'title' => $inputs['title'],
                'description' => $inputs['description'],
                'is_pin' => $inputs['is_pin'] ?? 0,
            ];

            $community = AssociationCommunity::whereId($id)->update($requestData);

            $communityFolder = config('constant.association_community').'/'.$community->id;
            if(!empty($inputs['images'])){
                foreach($inputs['images'] as $image) {
                    $mainImage = Storage::disk('s3')->putFile($communityFolder, $image,'public');
                    $fileName = basename($mainImage);
                    $image_url = $communityFolder . '/' . $fileName;
                    $temp = [
                        'community_id' => $id,
                        'image' => $image_url
                    ];
                    $addNew = AssociationCommunityImage::create($temp);
                }
            }

            if(isset($inputs['delete_images']) && !empty($inputs['delete_images'])){
                $deleteImages = DB::table('association_community_images')->whereIn('id',$inputs['delete_images'])->get();
                foreach($deleteImages as $data){
                    if($data->image){
                        Storage::disk('s3')->delete($data->image);
                    }
                }
                AssociationCommunityImage::whereIn('id',$inputs['delete_images'])->delete();
            }

            DB::commit();
            return $this->sendSuccessResponse(Lang::get('messages.community.add-success'), 200, $community);

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
            DB::beginTransaction();
            $validation = $this->associationCommunityValidator->validateStore($inputs);
            if ($validation->fails()) {
                return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
            }

            $category = AssociationCategory::where('id',$inputs['category_id'])->first(); // can post
            if($category && $category->can_post == 1){
                $association = AssociationUsers::whereIn('type',[AssociationUsers::MANAGER,AssociationUsers::PRESIDENT])->where(['association_id' => $inputs['association_id'],'user_id' => $user->id])->first();

                if(empty($association)){

                    return $this->sendSuccessResponse(Lang::get('messages.community.no-right-to-add-community'), 422);
                }
            }

            $main_country = getCountryFromLatLong($inputs['latitude'],$inputs['longitude']);
            $requestData = [
                'associations_id' => $inputs['association_id'],
                'category_id' => $inputs['category_id'] ?? null,
                'title' => $inputs['title'],
                'description' => $inputs['description'],
                'user_id' => $user->id,
                'country_code' => $main_country,
                'is_pin' => $inputs['is_pin'],
            ];

            $community = AssociationCommunity::create($requestData);
            $communityFolder = config('constant.association_community').'/'.$community->id;

            if (!Storage::disk('s3')->exists($communityFolder)) {
                Storage::disk('s3')->makeDirectory($communityFolder);
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
                    $addNew = AssociationCommunityImage::create($temp);
                }
            }

            if($community && !empty($inputs['is_pin'])){
                $getUsers = AssociationUsers::where('association_id',$inputs['association_id'])->whereIn('type',[AssociationUsers::MANAGER,AssociationUsers::MEMBER])->pluck('user_id')->toArray();
                $manager_member_users = array_unique($getUsers);

                if (($key = array_search($user->id, $manager_member_users)) !== false) {
                    unset($manager_member_users[$key]);
                }

                if($manager_member_users){
                    foreach($manager_member_users as $keyuser => $valueuser){
                        $notice = Notice::create([
                            'notify_type' => Notice::ASSOCIATION_COMMUNITY_POST,
                            'user_id' => $user->id,
                            'to_user_id' => $valueuser,
                            'entity_type_id' => EntityTypes::ASSOCIATION_COMMUNITY,
                            'entity_id' => $community->id,
                            'title' => $community->title,
                            'sub_title' => NULL
                        ]);

                        $user_detail = UserDetail::where('user_id', $valueuser)->first();
                        $language_id = $user_detail->language_id;
                        $key = Notice::ASSOCIATION_COMMUNITY_POST.'_'.$language_id;

                        $userIds = [$valueuser];

                        $format = $community->association->association_name;
                        $devices = UserDevices::whereIn('user_id', $userIds)->pluck('device_token')->toArray();
                        $title_msg = __("notice.$key",['association_name'=>$community->association->association_name]);
                        $notify_type = Notice::ASSOCIATION_COMMUNITY_POST;

                        $notificationData = [
                            'id' => $community->id,
                            'title' => $community->title,
                            'assocation_name' => $community->association->association_name,
                        ];

                        if (count($devices) > 0) {
                            $result = $this->sentPushNotification($devices,$title_msg, $format, $notificationData ,$notify_type,$community->id);
                        }
                    }
                }
            }

            DB::commit();
            return $this->sendSuccessResponse(Lang::get('messages.community.add-success'), 200, $community);
        } catch (\Exception $e) {
            Log::info($e);
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }

    }

    public function editCommunity(Request $request,$id)
    {
        $user = Auth::user();
        $inputs = $request->all();
        try {
            DB::beginTransaction();
            $validation = $this->associationCommunityValidator->validateStore($inputs);
            if ($validation->fails()) {
                return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
            }

            $category = AssociationCategory::where('id',$inputs['category_id'])->first(); // can post
           /*  if($category && $category->can_post == 1){
                $association = AssociationUsers::whereIn('type',[AssociationUsers::MANAGER,AssociationUsers::PRESIDENT])->where(['association_id' => $inputs['association_id'],'user_id' => $user->id])->first();

                if(empty($association)){
                    return $this->sendSuccessResponse(Lang::get('messages.community.no-right-to-add-community'), 422);
                }
            } */

            $main_country = getCountryFromLatLong($inputs['latitude'],$inputs['longitude']);
            $requestData = [
                'category_id' => $inputs['category_id'] ?? null,
                'title' => $inputs['title'],
                'description' => $inputs['description'],
                'user_id' => $user->id,
                'country_code' => $main_country,
                'is_pin' => $inputs['is_pin'],
            ];

            $community = AssociationCommunity::where('id',$id)->update($requestData);
            $communityFolder = config('constant.association_community').'/'.$id;

            if (!Storage::disk('s3')->exists($communityFolder)) {
                Storage::disk('s3')->makeDirectory($communityFolder);
            }

            if(!empty($inputs['images'])){
                foreach($inputs['images'] as $image) {
                    $mainImage = Storage::disk('s3')->putFile($communityFolder, $image,'public');
                    $fileName = basename($mainImage);
                    $image_url = $communityFolder . '/' . $fileName;
                    $temp = [
                        'community_id' => $id,
                        'image' => $image_url
                    ];
                    $addNew = AssociationCommunityImage::create($temp);
                }
            }

            if(isset($inputs['delete_images']) && !empty($inputs['delete_images'])){
                $deleteImages = DB::table('association_community_images')->whereIn('id',$inputs['delete_images'])->get();
                foreach($deleteImages as $data){
                    if($data->image){
                        Storage::disk('s3')->delete($data->image);
                    }
                }
                AssociationCommunityImage::whereIn('id',$inputs['delete_images'])->delete();
            }

            /* if($community && !empty($inputs['is_pin'])){
                $getUsers = AssociationUsers::where('association_id',$inputs['association_id'])->whereIn('type',[AssociationUsers::MANAGER,AssociationUsers::MEMBER])->pluck('user_id')->toArray();
                $manager_member_users = array_unique($getUsers);

                if (($key = array_search($user->id, $manager_member_users)) !== false) {
                    unset($manager_member_users[$key]);
                }

                if($manager_member_users){
                    foreach($manager_member_users as $keyuser => $valueuser){
                        $notice = Notice::create([
                            'notify_type' => Notice::ASSOCIATION_COMMUNITY_POST,
                            'user_id' => $user->id,
                            'to_user_id' => $valueuser,
                            'entity_type_id' => EntityTypes::ASSOCIATION_COMMUNITY,
                            'entity_id' => $community->id,
                            'title' => $community->title,
                            'sub_title' => NULL
                        ]);

                        $user_detail = UserDetail::where('user_id', $valueuser)->first();
                        $language_id = $user_detail->language_id;
                        $key = Notice::ASSOCIATION_COMMUNITY_POST.'_'.$language_id;

                        $userIds = [$valueuser];

                        $format = $community->association->association_name;
                        $devices = UserDevices::whereIn('user_id', $userIds)->pluck('device_token')->toArray();
                        $title_msg = __("notice.$key",['association_name'=>$community->association->association_name]);
                        $notify_type = Notice::ASSOCIATION_COMMUNITY_POST;

                        $notificationData = [
                            'id' => $community->id,
                            'title' => $community->title,
                            'assocation_name' => $community->association->association_name,
                        ];

                        if (count($devices) > 0) {
                            $result = $this->sentPushNotification($devices,$title_msg, $format, $notificationData ,$notify_type,$community->id);
                        }
                    }
                }
            } */

            $community = AssociationCommunity::where('id',$id)->first();
            DB::commit();
            return $this->sendSuccessResponse(Lang::get('messages.community.edit-success'), 200, $community);
        } catch (\Exception $e) {
            Log::info($e);
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }

    }

    public function getAssociationCommunity(Request $request)
    {
        $user = Auth::user();
        $inputs = $request->all();
        try {
            //DB::beginTransaction();
            $validation = $this->associationCommunityValidator->validateGetCommunity($inputs);
            if ($validation->fails()) {
                return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
            }

            $main_country = getCountryFromLatLong($inputs['latitude'],$inputs['longitude']);
            if(isset($inputs['latitude']) && isset($inputs['longitude'])) {
                $timezone = get_nearest_timezone($inputs['latitude'],$inputs['longitude'],$main_country);
            }else {
                $timezone = '';
            }

            $association_id = $inputs['category_id'];
            $search = $inputs['search_keyword'] ?? '';
            $language_id = $inputs['language_id'] ?? 4;
            $sub_category_id = $inputs['sub_category_id'] ?? '';

            if($user && $search != '') {

                SearchHistory::updateOrCreate([
                    'keyword' => $search,
                    'entity_type_id' => EntityTypes::COMMUNITY,
                    'category_id' => $association_id,
                    'user_id' => $user->id
                ]);

            }

            $association = Association::find($association_id);
            if(empty($association)){
                return $this->sendSuccessResponse(Lang::get('messages.community.get-success'), 200,[]);
            }

            $blockUsers = UserBlockHistory::where(function($q) use ($user){
                            $q->where('user_id',$user->id)->orWhere('block_user_id',$user->id);
                        })->where('block_for',UserBlockHistory::ASSOCIATION_COMMUNITY_POST)
                        ->get();

            $blockUsersId = [];

            foreach($blockUsers as $value){
                if($user->id != $value->user_id){
                    array_push($blockUsersId,$value->user_id);
                }else{
                    array_push($blockUsersId,$value->block_user_id);
                }
            }

            $hideCategories = $association->associationCategory()->where('is_hide',1)->pluck('id');
            $communityComman  = $association->associationCommunity()
                ->withCount('comments')
                ->with('user_detail:id,user_id,name,language_id,gender')
                ->with('category')
                ->whereNotIn('category_id',$hideCategories)
                ->whereNotIn('user_id',$blockUsersId)
                ->where('country_code',$main_country)
                ->where(function($q) use ($search){
                    if(!empty($search)){
                        $q->where('title', 'like', '%' . $search . '%');
                    }
                })
                ->where(function($q) use ($sub_category_id){
                    if(!empty($sub_category_id)){
                        $q->where('category_id', $sub_category_id);
                    }
                })
                ->orderBy('is_pin','DESC')->orderBy('created_at','DESC');


            $community = $association->associationCommunity()
                ->withCount('comments')
                ->with('user_detail:id,user_id,name,language_id,gender')
                ->with('category')
                ->whereNotIn('category_id',$hideCategories)
                ->whereNotIn('user_id',$blockUsersId)
                ->where('country_code',$main_country)
                ->where(function($q) use ($search){
                    if(!empty($search)){
                        $q->where('title', 'like', '%' . $search . '%');
                    }
                })
                ->where(function($q) use ($sub_category_id){
                    if(!empty($sub_category_id)){
                        $q->where('category_id', $sub_category_id);
                    }
                })
                ->orderBy('is_pin','DESC')
                ->orderBy('comments_count','DESC')
                ->paginate(config('constant.pagination_count'),"*","popular_list_page");

            $communityRecent = $communityComman->paginate(config('constant.pagination_count'),"*","recent_page");

            $communityBest = $communityComman->whereDate('created_at', '=', Carbon::now())
                ->paginate(config('constant.pagination_count'),"*","best_association_community_page");



            $bannerImage = $association->associationImage()->where('type',AssociationImage::BANNER_IMAGE)->get();
            $bannerImage = collect($bannerImage)->map(function($value){
                return ['image' => $value->image_url, 'link' => '', 'slide_duration' => 5 , 'order' => 0];
            });

            $data['popular_list'] = $this->timeFilter(collect($community)->toArray(),$language_id,$timezone);
            $data['recent_list']['best'] = $this->timeFilter(collect($communityBest)->toArray(),$language_id,$timezone);
            $data['recent_list']['recent'] = $this->timeFilter(collect($communityRecent)->toArray(),$language_id,$timezone);
            $data['banner_images'] = $bannerImage;
            return $this->sendSuccessResponse(Lang::get('messages.community.get-success'), 200,$data);
        } catch (\Exception $e) {
            Log::info($e);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function getCommunityDetail(Request $request,$id)
    {
        $user = Auth::user();
        try {
            $inputs = $request->all();
            $validation = $this->associationCommunityValidator->validateDetail($inputs);
            if ($validation->fails()) {
                return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
            }

            $communityExists = AssociationCommunity::find($id);
            if($communityExists){
                DB::beginTransaction();
                AssociationCommunity::where('id',$id)->update(['views_count' => DB::raw('views_count + 1')]);

                $language_id = $inputs['language_id'] ?? 4;
                if(isset($inputs['latitude']) && isset($inputs['longitude'])) {
                    $main_country = getCountryFromLatLong($inputs['latitude'],$inputs['longitude']);
                    $timezone = get_nearest_timezone($inputs['latitude'],$inputs['longitude'],$main_country);
                }else {
                    $timezone = '';
                }

                if(!empty($communityExists->user_detail)){
                    $communityExists->user_name = $communityExists->user_detail->name;
                    $communityExists->user_avatar = $communityExists->user_detail->avatar;
                    $communityExists->user_gender = $communityExists->user_detail->gender;
                    $communityExists->is_character_as_profile = $communityExists->user_detail->is_character_as_profile;

                }else{
                    $communityExists->user_name = '';
                    $communityExists->user_avatar = '';
                    $communityExists->user_gender = '';
                    $communityExists->is_character_as_profile = 0;
                }
                $communityExists->user_applied_card = getUserAppliedCard($communityExists->user_id);

                if(!empty($communityExists->category)){
                    $communityExists->category_name = $communityExists->category->name;;
                }else{
                    $communityExists->category_name = '';
                }

                $communityExists->time_difference = timeAgo($communityExists->created_at, $language_id,$timezone);
                $communityExists->comments_count = $communityExists->comments()->count();
                $communityExists->comments = $communityExists->comments()->with('comments_reply')->withCount('likes')->paginate(config('constant.pagination_count'),"*","comments_page");
                $communityExists->comments = $this->commentFilter($communityExists->comments->toArray(),$language_id,$timezone);

                $communityExists->images;
                $communityExists->parent_category_name = $communityExists->association->association_name;


                $communityExists->unsetRelation('association');
                $communityExists->unsetRelation('user_detail');
                $communityExists->unsetRelation('category');


                /* Static Fields */
                $communityExists->likes_count = $communityExists->associationLike()->where(['type'=> AssociationLikes::TYPE_ASSOCIATION_COMMUNITY])->count();
                $communityExists->is_saved_in_history = false;
                $communityExists->is_reported = false;
                $communityExists->is_liked = ($communityExists->associationLike()->where(['user_id'=> $user->id, 'type' => AssociationLikes::TYPE_ASSOCIATION_COMMUNITY])->count() > 0) ? true : false;

                DB::commit();
                return $this->sendSuccessResponse(Lang::get('messages.community.get-success'), 200,$communityExists);
            }else{
                return $this->sendSuccessResponse(Lang::get('messages.community.empty'), 402);
            }

        } catch (\Exception $e) {
            Log::info($e);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function commentTimeFilterNew($valueData, $language_id,$timezone) {
        $filteredData = array();
        foreach($valueData as $key => $value) {
            $value['comment_time'] = timeAgo($value['created_at'], $language_id,$timezone);
            $filteredData[$key] = $value;
            $filteredData[$key]['comments_reply'] = $this->commentTimeFilterNew($value['comments_reply'], $language_id,$timezone);
        }
        return $filteredData;
    }

    public function commentFilter($paginateData,$language_id = 4,$timezone = "") {
        $filteredData = [];
        $filteredData = $this->commentTimeFilterNew($paginateData['data'], $language_id,$timezone);
        $paginateData['data'] = array_values($filteredData);
        return $paginateData;
    }

    public function timeFilter($paginateData,$language_id = 4,$timezone = "") {
        $filteredData = [];
        foreach($paginateData['data'] as $key => $value) {
            $value = is_array($value) ? (object)$value : $value;
            $value->time_difference = timeAgo($value->created_at, $language_id,$timezone);
            if(property_exists($value, 'user_detail')){
                $value->user_name = $value->user_detail['name'] ?? '';
                $value->user_gender = $value->user_detail['gender'] ?? '';
            }else{
                $value->user_name = '';
                $value->user_gender = '';
            }
            if(property_exists($value, 'category') && !empty($value->category)){
                $value->category_name = $value->category['name'] ?? '';
            }else{
                $value->category_name = '';
            }
            $filteredData[] = $value;
        }

        $paginateData['data'] = array_values($filteredData);
        return $paginateData;
    }

    public function deleteCommunity($id)
    {
        try {
            DB::beginTransaction();

            $community = AssociationCommunity::find($id);
            $getImages = $community->images()->get();

            if($getImages){
                foreach($getImages as $keyImages => $valueImages){
                    Storage::disk('s3')->delete($valueImages->image);
                }
            }
            $community->images()->delete();

            $getComments = AssociationCommunityComment::where('community_id', $community->id)->get();

            if($getComments){
                foreach($getComments as $keyComment => $valueComment){
                    $valueComment->likes()->delete();
                    $valueComment->delete();
                }
            }

            $community->delete();

            DB::commit();
            return $this->sendSuccessResponse(Lang::get('messages.community.delete-success'), 200,[]);
        } catch (\Exception $e) {
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

}
