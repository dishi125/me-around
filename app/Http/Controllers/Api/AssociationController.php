<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Validators\AssociationValidator;
use Illuminate\Support\Facades\Lang;
use App\Models\Association;
use App\Models\AssociationUsers;
use App\Models\Category;
use App\Models\CategoryTypes;
use App\Models\CategoryLanguage;
use App\Models\Status;
use App\Models\Config;
use App\Models\AssociationMemberLogs;
use App\Models\AssociationCategory;
use App\Models\AssociationImage;
use App\Models\ReloadCoinRequest;
use App\Models\AssociationLikes;
use App\Models\AssociationCommunity;
use App\Models\AssociationCommunityComment;
use App\Models\UserDevices;
use App\Models\Community;
use App\Models\SavedHistoryTypes;
use App\Models\UserDetail;
use App\Models\Notice;
use App\Models\EntityTypes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use DB;
use Storage;
use Validator;
use App\Util\Firebase;
use Carbon\Carbon;

class AssociationController extends Controller
{

    private $associationValidator;
    protected $firebase;

    function __construct()
    {
        $this->associationValidator = new AssociationValidator();
        $this->firebase = new Firebase();

        if (array_key_exists('HTTP_AUTHORIZATION', $_SERVER)) {
            $this->middleware('jwt.verify');
        }

    }  

    public function getCommunityTabs(Request $request){
        $user = Auth::user();
        $inputs = $request->all();
        try {
            $associationsTabs = [];
            $communityCategory = Category::where('category_type_id',CategoryTypes::COMMUNITY)
                            ->where('parent_id',0)
                            ->where('status_id',Status::ACTIVE)
                            ->where('is_show',1)
                            ->get();

            $language_id = $inputs['language_id'] ?? 4;

            $communityCategory = collect($communityCategory)->map(function ($value) use ($language_id){
                $category_language = CategoryLanguage::where('category_id',$value->id)->where('post_language_id',$language_id)->first();
                $categoryName =  $category_language && $category_language->name ? $category_language->name : $value->name;
                return ['id' => $value->id, 'name' => $categoryName, 'is_access' => false, 'type' => 'category', 'category_type' => strtolower($value->name)];
            })->toArray();

            if($user){
                $associations = AssociationUsers::join('associations','associations.id','association_users.association_id')
                        ->where('association_users.user_id',$user->id)
                        ->whereIn('association_users.type',[AssociationUsers::PRESIDENT,AssociationUsers::MANAGER, AssociationUsers::MEMBER])
                        ->whereNull('associations.deleted_at')
                        ->select('association_users.type','associations.association_name','associations.id')
                        ->groupBy('associations.id')
                        ->get();

                $associationsTabs = collect($associations)->map(function ($value) use ($user) {
                    $isManager = ''; //$value->associationUsers()->where('user_id',$user->id)->whereIn('type',[AssociationUsers::PRESIDENT,AssociationUsers::MANAGER])->first();
                    $is_access = (in_array($value->type,[AssociationUsers::PRESIDENT,AssociationUsers::MANAGER])) ? true : false;

                    return ['id' => $value->id, 'name' => $value->association_name, 'is_access' => $is_access, 'type' => 'associations'];
                })->toArray();
            }

            $community_tabs = array_merge($associationsTabs, $communityCategory);

            return $this->sendSuccessResponse(Lang::get('messages.associations.get-success'), 200, compact('community_tabs')); 
        } catch (\Exception $e) {
            Log::info($e);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }
    
    public function getSubCategoryList(Request $request)
    {

        $inputs = $request->all();
        $user = Auth::user();
        try {
            $validation = $this->associationValidator->validateSubCategory($inputs);
            if ($validation->fails()) {
                return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
            }

            $category_id = $inputs['category_id'] ?? '';
            $language_id = $inputs['language_id'] ?? 4;
            $type = $inputs['type'] ?? '';

            $sub_category = [];
            if($type == 'category'){
                $subCategory = Category::where('category_type_id',CategoryTypes::COMMUNITY)
                    ->where('parent_id',$category_id)
                    ->where('status_id',Status::ACTIVE)
                    ->get(['id','name','category_type_id','parent_id','status_id','order']);

                foreach($subCategory as $category) {
                    $category_language = CategoryLanguage::where('category_id',$category->id)->where('post_language_id',$inputs['language_id'])->first();
                    $category['name'] = $category_language && $category_language->name != NULL ? $category_language->name : $category->name;
                }           
                
                $sub_category = collect($subCategory)->map(function ($value) {
                    return ['id' => $value->id, 'name' => $value->name, 'order' => $value->order ,'is_disabled' => false];
                })->toArray();

            }elseif($type == 'associations'){
                $association = Association::find($category_id);
                $category = $association->associationCategory;

                $sub_category = collect($category)->map(function ($value) use($association) {
                    $is_disabled =  ($association->user_type == AssociationUsers::MEMBER && $value->can_post == 1) ? true : false;

                    return ['id' => $value->id, 'name' => $value->name, 'is_disabled' => $is_disabled, 'order' => $value->order];
                })->toArray();
            }

            return $this->sendSuccessResponse(Lang::get('messages.associations.get-success'), 200, compact('sub_category')); 
        } catch (\Exception $e) {
                print_r($e->getMessage());
            Log::info($e);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function getAssociation(Request $request)
    {
        $user = Auth::user();
        $inputs = $request->all();
        try {
            $validation = $this->associationValidator->validateGetAssociation($inputs);
            if ($validation->fails()) {
                return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
            }

            $main_country = getCountryFromLatLong($inputs['latitude'],$inputs['longitude']);
            $search = $inputs['search'] ?? '';

            // Join Query

            $joinedAssociation = [];
            $join_associations = (object)[];

            if($user){
                $joinQuery = Association::join('association_users','association_users.association_id','associations.id')
                            ->join('countries','countries.id','associations.country_id')
                            ->where('association_users.user_id',$user->id)
                            ->where('countries.code',$main_country)
                            ->groupBy('associations.id');

                if(!empty($search)){
                    if (is_numeric($search) && strlen($search) == 4) {

                        $joinQuery = $joinQuery->whereIn('associations.id',function($q) use($search){
                            $q->select('id')->from('associations')->where('code', $search);
                        });
                        $joinQuery->OrWhere('associations.association_name','like','%'.$search.'%');                    
                    }

                    $joinQuery = $joinQuery->where('associations.association_name','like','%'.$search.'%');
                    
                }                 

                $joinQuery = $joinQuery->select('associations.*')
                        ->withCount('associationUsers')
                        ->orderBy('association_users_count','DESC');

                $join_associations = $joinQuery->paginate(config('constant.post_pagination_count'),"*","join_association_page");                   

                foreach($join_associations as $data){
                    $president = $data->associationUsers()->where('type',AssociationUsers::PRESIDENT)->first();
                    $data->president = (!empty($president) && !empty((array)$president->user_info)) ? $president->user_info->name : '';

                    $imageData = $data->associationImage()->get();
                    $imageFilter = collect($imageData)->filter(function ($value) {
                            return !empty($value->image_url);
                        })->first();
                    
                    $data->association_thumbnails = $imageFilter; //$data->associationImage()->first();
                    $data->association_posts_count = $data->associationCommunity()->count();
                    
                    $joinedAssociation[] = $data->id;
                }

            }


            // Non Join
            $query = Association::join('countries','countries.id','associations.country_id')
                        ->where('countries.code',$main_country)
                        ->whereNotIn('associations.id',$joinedAssociation)
                        ->groupBy('associations.id');

            if(!empty($search)){
                if (is_numeric($search) && strlen($search) == 4) {

                    $query = $query->whereIn('associations.id',function($q) use($search){
                        $q->select('id')->from('associations')->where('code', $search);
                    });
                    $query->OrWhere('associations.association_name','like','%'.$search.'%');                    
                }

                $query = $query->where('associations.type',Association::TYPE_PUBLIC);
                $query = $query->where('associations.association_name','like','%'.$search.'%');
                
            }else{
                $query = $query->where('associations.type',Association::TYPE_PUBLIC);
            }                 

            $query = $query->select('associations.*')
                    ->withCount('associationUsers')
                    ->orderBy('association_users_count','DESC');

            $associations = $query->paginate(config('constant.post_pagination_count'),"*","all_association_page");                   

            foreach($associations as $data){
                $president = $data->associationUsers()->where('type',AssociationUsers::PRESIDENT)->first();
                $data->president = (!empty($president) && !empty((array)$president->user_info) ) ? $president->user_info->name : '';

                $imageData = $data->associationImage()->get();
                $imageFilter = collect($imageData)->filter(function ($value) {
                        return !empty($value->image_url);
                    })->first();

                $data->association_thumbnails = $imageFilter; //$data->associationImage()->first();
                $data->association_posts_count = $data->associationCommunity()->count();
                
            }

            $recent_query = Association::join('countries','countries.id','associations.country_id')
                    ->where('countries.code',$main_country)
                    ->where('associations.type',Association::TYPE_PUBLIC)
                    ->select('associations.*')
                    ->withCount('associationUsers')
                    ->whereNotIn('associations.id',$joinedAssociation)
                    ->orderBy('associations.created_at','DESC');

            if(!empty($search)){
                if (is_numeric($search) && strlen($search) == 4) {

                    $recent_query = $recent_query->whereIn('associations.id',function($q) use($search){
                        $q->select('id')->from('associations')->where('code', $search);
                    });
                    $recent_query->OrWhere('associations.association_name','like','%'.$search.'%');                    
                }
                $recent_query = $recent_query->where('associations.association_name','like','%'.$search.'%');                
            }

            $recent_associations = $recent_query->paginate(config('constant.post_pagination_count'),"*","all_association_page");

            foreach($recent_associations as $data){
                $president = $data->associationUsers()->where('type',AssociationUsers::PRESIDENT)->first();
                $data->president = (!empty($president) && !empty((array)$president->user_info)) ? $president->user_info->name : '';

                $imageData = $data->associationImage()->get();
                $imageFilter = collect($imageData)->filter(function ($value) {
                        return !empty($value->image_url);
                    })->first();

                $data->association_thumbnails = $imageFilter; // $data->associationImage()->first();
                $data->association_posts_count = $data->associationCommunity()->count();
                
            }
            return $this->sendSuccessResponse(Lang::get('messages.associations.get-success'), 200, compact('join_associations','associations','recent_associations')); 

        } catch (\Exception $e) {
            Log::info($e);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function getAssociationDetailView($id,$searchType=NULL,$search=NULL){
        $user = Auth::user();
        $association = Association::find($id);

        if(!$association) return (object)[];
        $association->president = $association->associationUsers()->where('type',AssociationUsers::PRESIDENT)->first();

        $managers = $association->associationUsers();
        if(!empty($searchType) && !empty($search) && $searchType == AssociationUsers::MANAGER){
            $managers = $managers->leftjoin('users_detail as ud','ud.user_id','association_users.user_id')->where('ud.name','like','%'.$search.'%');
        }

        $association->managers = $managers->where('type',AssociationUsers::MANAGER)->paginate(config('constant.post_pagination_count'),"*","all_manager_page");

        $members = $association->associationUsers();
        if(!empty($searchType) && !empty($search) && $searchType == AssociationUsers::MEMBER){
            $members = $members->leftjoin('users_detail as ud','ud.user_id','association_users.user_id')->where('ud.name','like','%'.$search.'%');
        }

        $association->members = $members->where('is_kicked',0)->where('type',AssociationUsers::MEMBER)->paginate(config('constant.post_pagination_count'),"*","all_member_page");

        $kicked_members = $association->associationUsers();
        if(!empty($searchType) && !empty($search) && $searchType == AssociationUsers::KICKED_MEMBER){
            $kicked_members = $kicked_members->leftjoin('users_detail as ud','ud.user_id','association_users.user_id')->where('ud.name','like','%'.$search.'%');
        }

        $association->kicked_members = $kicked_members->where('is_kicked',1)->where('type',AssociationUsers::MEMBER)->paginate(config('constant.post_pagination_count'),"*","all_kicked_member_page");

        $association->members_count = $association->members->total();
        $association->managers_count = $association->managers->total();

        $imageData = $association->associationImage()->where('type',AssociationImage::MAIN_IMAGE)->get();
        $main_images = collect($imageData)->filter(function ($value) {
                return !empty($value->image_url);
            })->values();

        $association->main_images = $main_images; //$association->associationImage()->where('type',AssociationImage::MAIN_IMAGE)->get();
        $association->banner_images = $association->associationImage()->where('type',AssociationImage::BANNER_IMAGE)->get();
        $association->associationCategory;

        if($user){
            
            $isManager = $association->associationUsers()->where('user_id',$user->id)->whereIn('type',[AssociationUsers::PRESIDENT,AssociationUsers::MANAGER])->first();
            $association->is_remove_access = !empty($isManager) ? true : false;
            $association->user_id = $user->id;
        }else{
            $association->is_remove_access = false;
            $association->user_id = 0;
            $association->is_joined = false;
            $association->user_type = '';
        }

        $thisMonthSupporter = AssociationUsers::leftjoin('users_detail','users_detail.manager_id','association_users.user_id')
                ->leftjoin('reload_coins_request', function ($join) {
                    $join->on('reload_coins_request.user_id', '=', 'users_detail.user_id')
                        ->where('reload_coins_request.status',ReloadCoinRequest::GIVE_COIN)
                        ->whereMonth('reload_coins_request.created_at',Carbon::now()->startOfMonth()->month);
                })
                ->join('managers','managers.user_id','association_users.user_id')
                ->where('association_id',$id)
                ->where('type',AssociationUsers::SUPPORTER)
                ->select(
                    'managers.*',
                    \DB::raw('CAST(COALESCE(SUM(reload_coins_request.coin_amount),0) as UNSIGNED) as total_coin_amount')
                )
                ->groupBy('association_users.user_id')
                ->orderBy('total_coin_amount','DESC')
                ->paginate(config('constant.review_pagination_count'),"*","this_month_page");

        $lastMonthSupporter = AssociationUsers::leftjoin('users_detail','users_detail.manager_id','association_users.user_id')
            ->leftjoin('reload_coins_request', function ($join) {
                $join->on('reload_coins_request.user_id', '=', 'users_detail.user_id')
                    ->where('reload_coins_request.status',ReloadCoinRequest::GIVE_COIN)
                    ->whereBetween('reload_coins_request.created_at',[Carbon::now()->startOfMonth()->subMonth(1),Carbon::now()->startOfMonth()]);
            })
            ->join('managers','managers.user_id','association_users.user_id')
            ->where('association_id',$id)
            ->where('type',AssociationUsers::SUPPORTER)
            ->select(
                'managers.*',
                \DB::raw('CAST(COALESCE(SUM(reload_coins_request.coin_amount),0) as UNSIGNED) as total_coin_amount')
            )
            ->groupBy('association_users.user_id')
            ->orderBy('total_coin_amount','DESC')
            ->paginate(config('constant.review_pagination_count'),"*","last_month_page");

        $association->supporter_count = $association->associationUsers()->where('type',AssociationUsers::SUPPORTER)->count();
        $association->this_month_coin_total = collect($thisMonthSupporter->items())->sum('total_coin_amount');
        $association->last_month_coin_total = collect($lastMonthSupporter->items())->sum('total_coin_amount');
        $association->supporters = ['this_month_supporter' => $thisMonthSupporter,'last_month_supporter' => $lastMonthSupporter];

        return $association;
    }

    public function getAssociationCategoryList($association_id){
        $association = Association::find($association_id);
        return $category = $association->associationCategory;
    }

    public function getAssociationDetail(Request $request,$id){
        
        try {

            $inputs = $request->all();
            $searchType = !empty($inputs['search_type']) ? $inputs['search_type'] : NULL;
            $search = !empty($inputs['search']) ? $inputs['search'] : NULL;
            $association = $this->getAssociationDetailView($id,$searchType,$search);
            return $this->sendSuccessResponse(Lang::get('messages.associations.get-success'), 200, compact('association')); 
        } catch (\Exception $e) {
            print_r($e->getMessage());die;
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function joinAssociation(Request $request)
    {
        $inputs = $request->all();
        $user = Auth::user();
        try {
            $validation = $this->associationValidator->validateJoinAssociation($inputs);
            if ($validation->fails()) {
                return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
            }

            $association_id = $inputs['association_id'];
            $association = $this->getAssociationDetailView($association_id);

            $isKicked = AssociationUsers::where('association_id',$association_id)->where('user_id', $user->id)->where('is_kicked',1)->first();

            if(!empty($isKicked)){
                return $this->sendFailedResponse(Lang::get('messages.associations.join-error'), 400, compact('association')); 
            }
            // Check Time
            $isRecentJoin = AssociationMemberLogs::where('associations_id',$association_id)
                    ->where('user_id', $user->id)
                    ->where('removed_type',Association::SELF)
                    ->orderBy('created_at','DESC')
                    ->first();

            if(!empty($isRecentJoin)){
                $createdDate = $isRecentJoin->created_at;
                $config = Config::where('key',Config::REJOIN_TIME_LIMIT)->first();
                $limitHours = $config ? (int) filter_var($config->value, FILTER_SANITIZE_NUMBER_INT) : 0;
                $newCreatedAt = Carbon::parse($createdDate)->addHours($limitHours);

                if(Carbon::now()->lt($newCreatedAt)){
                    return $this->sendFailedResponse(Lang::get('messages.associations.join-limit-error'), 400, compact('association')); 
                }

            }
            // Check Time End
            $join = AssociationUsers::updateOrCreate([
                'association_id' => $association_id,
                'type' => AssociationUsers::MEMBER,
                'user_id' => $user->id
            ]);

            if ($join->wasRecentlyCreated === true) {
                $this->sendNoticeAndNotifications($association_id);
            }

            return $this->sendSuccessResponse(Lang::get('messages.associations.join-success'), 200, compact('association')); 

        } catch (\Exception $e) {
            Log::info($e);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function removeAssociationMember(Request $request)
    {
        $inputs = $request->all();
        $user = Auth::user();
        try {
            $validation = $this->associationValidator->validateRemoveAssociation($inputs);
            if ($validation->fails()) {
                return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
            }

            $association_id = $inputs['association_id'];
            $remove_type = $inputs['remove_type'];
            $type = $inputs['type'];
            $user_id = $inputs['user_id'] ?? '';

            DB::beginTransaction();

            if($remove_type == Association::SELF){
                $logArray = [
                    'associations_id' => $association_id,
                    'user_id' => $user->id,
                    'removed_by' => $user->id,
                    'removed_type' => $remove_type,
                    'created_at' => Carbon::now()
                ];
                AssociationUsers::where('association_id',$association_id)->where('user_id',$user->id)->where('type',$type)->delete();
            }elseif($remove_type == Association::REMOVE){
                $logArray = [
                    'associations_id' => $association_id,
                    'user_id' => $user_id,
                    'removed_by' => $user->id,
                    'removed_type' => ($type == AssociationUsers::SUPPORTER) ? $type : $remove_type,
                    'created_at' => Carbon::now()
                ];
                AssociationUsers::where('association_id',$association_id)->where('user_id',$user_id)->where('type',$type)->delete();
            }elseif($remove_type == Association::KICK){
                $logArray = [
                    'associations_id' => $association_id,
                    'user_id' => $user_id,
                    'removed_by' => $user->id,
                    'removed_type' => $remove_type,
                    'created_at' => Carbon::now()
                ];
                AssociationUsers::where('association_id',$association_id)->where('user_id',$user_id)->where('type',$type)->update(['is_kicked' => 1]);
            }

            if($logArray){
                AssociationMemberLogs::create($logArray);
            }

            DB::commit();
            $association = $this->getAssociationDetailView($association_id);
            return $this->sendSuccessResponse(Lang::get('messages.associations.remove-success'), 200, compact('association')); 
        } catch (\Exception $e) {
            Log::info($e);
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }
    
    
    public function manageAssociationCategory(Request $request)
    {
        $inputs = $request->all();
        $user = Auth::user();
        try {
            $validation = $this->associationValidator->validateAssociationCategory($inputs);
            if ($validation->fails()) {
                return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
            }

            $association_id = $inputs['association_id'];

            if(isset($inputs['id']) && !empty($inputs['id'])){
                AssociationCategory::where('id',$inputs['id'])
                ->update([
                    'associations_id' => $association_id,
                    'name' => $inputs['name'],
                    'order' => $inputs['order'] ?? 0,
                    'can_post' => $inputs['can_post'] ?? 0,
                ]);
            }else{
                AssociationCategory::create([
                    'associations_id' => $association_id,
                    'name' => $inputs['name'],
                    'order' => $inputs['order'] ?? 0,
                    'can_post' => $inputs['can_post'] ?? 0,
                ]);
            }

            $association = Association::find($association_id);
            $category = $association->associationCategory;
            if(isset($inputs['id']) && !empty($inputs['id'])){
                return $this->sendSuccessResponse(Lang::get('messages.associations.category-update'), 200, compact('category')); 
            }else{
                return $this->sendSuccessResponse(Lang::get('messages.associations.category-success'), 200, compact('category')); 
            }
        } catch (\Exception $e) {

            Log::info($e);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function updateCategoryStatus(Request $request)
    {
        $inputs = $request->all();
        $user = Auth::user();
        try {
            $validation = $this->associationValidator->validateUpdateStatusCategory($inputs);
            if ($validation->fails()) {
                return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
            }

            $is_category = AssociationCategory::where('id',$inputs['category_id'])->first();
            if($is_category){
                $association = Association::find($is_category->associations_id);

                if($association && ($association->user_type == AssociationUsers::MANAGER || $association->user_type == AssociationUsers::PRESIDENT)){
                    AssociationCategory::where('id',$inputs['category_id'])
                    ->update([
                        'is_hide' => $inputs['is_hide'] ?? 0,
                    ]);
                    $category = $association->associationCategory;
                    return $this->sendSuccessResponse(Lang::get('messages.associations.category-update'), 200, compact('category')); 
                }else{
                    return $this->sendSuccessResponse(Lang::get('messages.associations.hide-unhide-status-update-user'), 422);
                }
            }else{
                return $this->sendSuccessResponse(Lang::get('messages.associations.category-not-found'), 422); 
            }
            
        } catch (\Exception $e) {
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function deleteAssociationCategory(Request $request)
    {
        $inputs = $request->all();
        $user = Auth::user();
        try {
            $validation = $this->associationValidator->validateUpdateStatusCategory($inputs);
            if ($validation->fails()) {
                return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
            }

            $category = AssociationCategory::where('id',$inputs['category_id'])->first();

            if($category){
                $association = Association::find($category->associations_id);
                if($association && ($association->user_type == AssociationUsers::PRESIDENT || $association->user_type == AssociationUsers::MANAGER)){

                    $getCommunity = AssociationCommunity::where('associations_id',$category->associations_id)->get();

                    if($getCommunity){
                        foreach($getCommunity as $keyCommunity => $valCommunity){
                            $getImages = $valCommunity->images()->get();

                            if($getImages){
                                foreach($getImages as $keyImages => $valueImages){
                                   Storage::disk('s3')->delete($valueImages->image);
                                }
                            }
                            $deleteCommunityImages = $valCommunity->images()->delete();

                            $getComments = AssociationCommunityComment::where('community_id', $valCommunity->id)->get();

                            if($getComments){
                                foreach($getComments as $keyComment => $valueComment){
                                    $deleteLikes = $valueComment->likes()->delete();
                                }
                            }
                            $deleteComments = AssociationCommunityComment::where('community_id', $valCommunity->id)->delete();

                            $deleteCommunityLikes = $valCommunity->associationLike()->delete();
                        }
                    }

                    
                    $deleteCommunity = $category->associationCommunity()->delete();
                    $deleteCategory = $category->delete();

                    $category = $association->associationCategory;
                    return $this->sendSuccessResponse(Lang::get('messages.associations.category-deleted'), 200, compact('category')); 
                }else{
                    return $this->sendSuccessResponse(Lang::get('messages.associations.no-rights-to-delete-association-category'), 422);
                }
            }else{
                return $this->sendSuccessResponse(Lang::get('messages.associations.category-not-found'), 422); 
            }
            
        } catch (\Exception $e) {
            Log::info($e);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function makeMemberToManger(Request $request)
    {
        $inputs = $request->all();
        $user = Auth::user();
        try {
            $validation = $this->associationValidator->validateMakeManager($inputs);
            if ($validation->fails()) {
                return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
            }

            $association_id = $inputs['association_id'] ?? 0;
            $user_id = $inputs['user_id'] ?? 0;
            $isManager = AssociationUsers::where('association_id',$association_id)->where('user_id',$user->id)->whereIn('type',[AssociationUsers::PRESIDENT,AssociationUsers::MANAGER])->first();            

            if(empty($isManager)){
                return $this->sendFailedResponse(Lang::get('messages.associations.make-manager-error'), 400 ); 
            }

            $managerCount = AssociationUsers::where('association_id',$association_id)->where('type',AssociationUsers::MANAGER)->count();
            if($managerCount >= AssociationUsers::MANAGERCOUNT){
                return $this->sendFailedResponse(Lang::get('messages.associations.manager-count-error'), 400 ); 
            }

            AssociationUsers::where('association_id',$association_id)->where('user_id',$user_id)->where('type',AssociationUsers::MEMBER)->update(['type'=>AssociationUsers::MANAGER]);

            $association = $this->getAssociationDetailView($association_id);

            // Send Notification
            
                $devices = UserDevices::whereIn('user_id', [$user_id])->pluck('device_token')->toArray();
                $user_detail = UserDetail::where('user_id', $user_id)->first();
                $language_id = $user_detail->language_id;
                $key = "language_$language_id.".Notice::BECAME_MANAGER;
                $title_msg = __("messages.$key");
                $format = '';
                $notify_type = Notice::BECAME_MANAGER;

                Notice::create([
                    'notify_type' => $notify_type,
                    'user_id' => $user_id,
                    'to_user_id' => $user_id,
                    'entity_type_id' => EntityTypes::ASSOCIATION_COMMUNITY,
                    'entity_id' => $association_id,
                    'title' => $title_msg,
                    'sub_title' => $association->association_name,
                ]);
                if (count($devices) > 0) {
                    $result = $this->sentPushNotification($devices,$title_msg, $format, [] ,$notify_type, $association_id);                        
                }
            // Send Notification End


            return $this->sendSuccessResponse(Lang::get('messages.associations.manager-success'), 200, compact('association')); 

        } catch (\Exception $e) {
            Log::info($e);
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function saveAssociation(Request $request)
    {

        $inputs = $request->all();
        $user = Auth::user();
        try {

            $validation = $this->associationValidator->validateSaveAssociation($inputs);
            if ($validation->fails()) {
                return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
            }

            $association_id = $inputs['id'];
            $deleted_image = $inputs['deleted_image'] ?? [];
            $getAssociation = AssociationUsers::whereIn('type',[AssociationUsers::MANAGER,AssociationUsers::PRESIDENT])->where(['association_id' => $association_id,'user_id' => $user->id])->first();

            if($getAssociation){

                $data = ['association_name' => $inputs['association_name'],'description' => $inputs['description'] ?? null];
                Association::where('id',$association_id)->update($data);

                $associationFolder = config('constant.association').'/'.$association_id;         
                if (!Storage::disk('s3')->exists($associationFolder)) {
                    Storage::disk('s3')->makeDirectory($associationFolder);
                } 

                if(isset($inputs['main_image']) && !empty($inputs['main_image'])){
                    foreach($inputs['main_image'] as $image) {
                        if(is_file($image)){
                            $mainImage = Storage::disk('s3')->putFile($associationFolder, $image,'public');
                            $fileName = basename($mainImage);
                            $image_url = $associationFolder . '/' . $fileName;
                            $mainImageData = [
                                'associations_id' => $association_id,
                                'image' => $image_url,
                                'type' => AssociationImage::MAIN_IMAGE
                            ];
                            AssociationImage::create($mainImageData);
                        }
                    }
                }
                if(isset($inputs['banner_image']) && !empty($inputs['banner_image'])){
                    foreach($inputs['banner_image'] as $image) {
                        if(is_file($image)){
                            $mainImage = Storage::disk('s3')->putFile($associationFolder, $image,'public');
                            $fileName = basename($mainImage);
                            $image_url = $associationFolder . '/' . $fileName;
                            $bannerImageData = [
                                'associations_id' => $association_id,
                                'image' => $image_url,
                                'type' => AssociationImage::BANNER_IMAGE
                            ];
                            AssociationImage::create($bannerImageData);
                        }
                    }
                }
                if(isset($inputs['deleted_image']) && !empty($inputs['deleted_image'])){
                    foreach($inputs['deleted_image'] as $deleteImage) {
                        $image = DB::table('associations_image')->whereId($deleteImage)->first();
                        if($image) {
                            Storage::disk('s3')->delete($image->image);
                            AssociationImage::where('id',$image->id)->delete();
                        }
                    }
                } 
                $association = $this->getAssociationDetailView($association_id);
                return $this->sendSuccessResponse(Lang::get('messages.associations.update'), 200, compact('association')); 

            }else{
                return $this->sendSuccessResponse(Lang::get('messages.associations.no-rights-to-add-association'), 422); 
            }
            
        } catch (\Exception $e) {
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function saveLike(Request $request)
    {
        $inputs = $request->all();
        $user = Auth::user();
        try {
            $validation = $this->associationValidator->validateSaveAssociationLike($inputs);
            if ($validation->fails()) {
                return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
            }

            $type = $inputs['type'];
            $entity_id = $inputs['entity_id'];
            $id = $inputs['id'] ?? 0;

            $data = [
                'type' => $type,
                'entity_id' => $entity_id,
                'user_id' => $user->id
            ];

            if(!empty($id)){
                AssociationLikes::where($data)->delete();
                $message = Lang::get('messages.associations.association-dis-like');
            }else{
                AssociationLikes::create($data);
                $message = Lang::get('messages.associations.association-like');
            }          
            return $this->sendSuccessResponse($message, 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }


    public function sendNoticeAndNotifications($association_id){
        $association = Association::find($association_id);
        $userIds = $association->associationUsers()->whereIn('type',[AssociationUsers::PRESIDENT,AssociationUsers::MANAGER])->groupBy('user_id')->pluck('user_id')->toArray();
        $user = Auth::user();
        foreach($userIds as $uId){
            $devices = UserDevices::whereIn('user_id', [$uId])->pluck('device_token')->toArray();
            $user_detail = UserDetail::where('user_id', $uId)->first();
            $language_id = $user_detail->language_id;
            $key = "language_$language_id.".Notice::JOIN_ASSOCIATION;
            $title_msg = __("messages.$key", ['name' => $user->name]);
            $format = '';
            $notify_type = Notice::JOIN_ASSOCIATION;

            Notice::create([
                'notify_type' => Notice::JOIN_ASSOCIATION,
                'user_id' => $user->id,
                'to_user_id' => $uId,
                'entity_type_id' => EntityTypes::ASSOCIATION_COMMUNITY,
                'entity_id' => $association_id,
                'title' => $title_msg,
                'sub_title' => $association->association_name,
            ]);
            if (count($devices) > 0) {
                $result = $this->sentPushNotification($devices,$title_msg, $format, [] ,$notify_type, $association->id);                        
            }
        }
    }
    
    public function getSavedCommunityTabs(Request $request){
        $user = Auth::user();
        $inputs = $request->all();
        try {
            $associationsTabs = [];
            $language_id = $inputs['language_id'] ?? 4;

            $getSavedCommunityCategory = DB::table('community')->leftJoin('category','category.id','community.category_id')->join('user_saved_history', function ($join) use ($user) {
                $join->on('community.id', '=', 'user_saved_history.entity_id')
                ->where('user_saved_history.saved_history_type_id', SavedHistoryTypes::COMMUNITY);
            })
            ->where('user_saved_history.user_id',$user->id)
            ->where('user_saved_history.is_like',1)
            ->whereNull('user_saved_history.deleted_at')
            ->orderBy('category.id','asc')
            ->groupBy('category.parent_id')
            ->select('category.order','category.id','category.name','category.parent_id')->get();

            $communityCategory = collect($getSavedCommunityCategory)->map(function ($value) use ($language_id) {

                $getParentName = DB::table('category')->where('id',$value->parent_id)->first(['id','name']);

                $category_language = CategoryLanguage::where('category_id',$getParentName->id)->where('post_language_id',$language_id)->first();
                $categoryName =  $category_language && $category_language->name ? $category_language->name : $getParentName->name;

                return ['id' => $getParentName->id, 'name' => $categoryName, 'is_access' => false, 'type' => 'category', 'category_type' => strtolower($getParentName->name)];
            })->toArray();

            if($user){

                $associations = DB::table('association_communities')->leftJoin('association_users','association_users.association_id','association_communities.associations_id')->leftJoin('associations','associations.id','association_communities.associations_id')->join('user_saved_history', function ($join) use ($user) {
                                    $join->on('association_communities.id', '=', 'user_saved_history.entity_id')
                                        ->where('user_saved_history.saved_history_type_id', SavedHistoryTypes::ASSOCIATION_COMMUNITY);
                                })
                                ->where('user_saved_history.user_id',$user->id)
                                ->where('association_users.user_id',$user->id)
                                ->where('user_saved_history.is_like',1)
                                ->whereIn('association_users.type',[AssociationUsers::PRESIDENT,AssociationUsers::MANAGER, AssociationUsers::MEMBER])
                                ->whereNull('associations.deleted_at')
                                ->whereNull('user_saved_history.deleted_at')
                                ->orderBy('user_saved_history.created_at','desc')
                                ->groupBy('associations.id')
                                ->select('association_communities.category_id','association_communities.associations_id','association_users.type','associations.association_name','associations.id')->get()->toArray();

                $associationsTabs = collect($associations)->map(function ($value) use ($user) {
                    $isManager = ''; 
                    $is_access = (in_array($value->type,[AssociationUsers::PRESIDENT,AssociationUsers::MANAGER])) ? true : false;

                    return ['id' => $value->id, 'name' => $value->association_name, 'is_access' => $is_access, 'type' => 'associations'];
                })->toArray();
            }

            $community_tabs = array_merge($associationsTabs, $communityCategory);

            return $this->sendSuccessResponse(Lang::get('messages.associations.get-success'), 200, compact('community_tabs')); 
        } catch (\Exception $e) {
            print_r($e->getMessage());die;
            Log::info($e);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function getSavedCommunityData(Request $request){
        $user = Auth::user();
        try {
            $associationsTabs = [];
            $inputs = $request->all();
            $type = $inputs['type'] ?? NULL;

            $getCommunityDataQuery = Community::leftJoin('category','category.id','community.category_id')->join('user_saved_history', function ($join) use ($user) {
                $join->on('community.id', '=', 'user_saved_history.entity_id')
                ->where('user_saved_history.saved_history_type_id', SavedHistoryTypes::COMMUNITY);
            });

            if(!empty($inputs['id'])){
                $getCommunityDataQuery = $getCommunityDataQuery->where('category.parent_id',$inputs['id']);
            }

            $getCommunityDataQuery = $getCommunityDataQuery->where('user_saved_history.is_like',1)->where('user_saved_history.user_id',$user->id)
            ->whereNull('user_saved_history.deleted_at');
            $getCommunityData = $getCommunityDataQuery->select('community.id','community.title','community.category_id','community.user_id','community.views_count','community.created_at',DB::raw("'category' as type"),'user_saved_history.created_at as saved_time');

            $getAssociationCommunityDataQuery = AssociationCommunity::leftJoin('association_users','association_users.association_id','association_communities.associations_id')->leftJoin('associations','associations.id','association_communities.associations_id')->join('user_saved_history', function ($join) use ($user) {
                $join->on('association_communities.id', '=', 'user_saved_history.entity_id')
                ->where('user_saved_history.saved_history_type_id', SavedHistoryTypes::ASSOCIATION_COMMUNITY);
            });

            if(!empty($inputs['id'])){
                $getAssociationCommunityDataQuery = $getAssociationCommunityDataQuery->where('associations.id',$inputs['id']);
            }

            $getAssociationCommunityDataQuery = $getAssociationCommunityDataQuery->where('user_saved_history.user_id',$user->id)
            ->where('user_saved_history.is_like',1)
            ->where('association_users.user_id',$user->id)
            ->whereIn('association_users.type',[AssociationUsers::PRESIDENT,AssociationUsers::MANAGER, AssociationUsers::MEMBER])
            ->whereNull('associations.deleted_at')
            ->whereNull('user_saved_history.deleted_at')
           ;
            $getAssociationCommunityData = $getAssociationCommunityDataQuery->select('association_communities.id','association_communities.title','association_communities.category_id','association_communities.user_id','association_communities.views_count','association_communities.created_at',DB::raw("'associations' as type"),'user_saved_history.created_at as saved_time');

            $getData = [];
            if(!empty($type) && $type == 'category'){
                $getData = $getCommunityData->orderBy('saved_time','desc')->paginate(config('constant.post_pagination_count'),"*","community_page");

                $community = $getData;
                $result= $getData->makeHidden(['comments','images']);
                $community->data = $result;
               
            }else if(!empty($type) && $type == 'associations'){
                $community = $getAssociationCommunityData->orderBy('saved_time','desc')->paginate(config('constant.post_pagination_count'),"*","community_page");
            }else{

                $getDataQuery = $getCommunityData->union($getAssociationCommunityData);
                $getData = $getDataQuery->orderBy('saved_time','desc')->paginate(config('constant.post_pagination_count'),"*","community_page");
                

                $community = $getData;
                $result= $getData->makeHidden(['comments','images']);
                $community->data = $result;

            }

            return $this->sendSuccessResponse(Lang::get('messages.associations.get-success'), 200, compact('community')); 
        } catch (\Exception $e) {
           print_r($e->getMessage());die;
            Log::info($e);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }
}
