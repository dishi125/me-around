<?php

namespace App\Http\Controllers\Api;

use App\Models\RequestForm;
use App\Models\EntityTypes;
use App\Models\Status;
use App\Models\Hospital;
use App\Models\UserEntityRelation;
use App\Models\Address;
use App\Models\Banner;
use App\Models\HospitalImages;
use App\Models\Post;
use App\Models\HospitalDoctor;
use App\Models\UserDetail;
use App\Models\CreditPlans;
use App\Models\UserCredit;
use App\Models\Config;
use App\Models\Notice;
use App\Models\UserDevices;
use App\Validators\HospitalProfileValidator;
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
use App\Models\UserInstagramHistory;

class HospitalProfileController extends Controller
{
    private $hospitalProfileValidator;
    protected $firebase;

    function __construct()
    {
        $this->hospitalProfileValidator = new HospitalProfileValidator();
        $this->firebase = new Firebase();
    }

   
    public function getHospitalBusinessProfile(Request $request)
    {
        $user = Auth::user();
        try {
            Log::info('Start code for the get hospital list');   
            $inputs = $request->all(); 
            $validation = $this->hospitalProfileValidator->validateGetPost($inputs);
            
            if ($validation->fails()) {
                Log::info('End code for get all shops');
                return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
            }

            $main_country = getCountryFromLatLong($inputs['latitude'],$inputs['longitude']);
            $language_id = $inputs['language_id'] ?? 4;
            if($user) {   
                $query = Hospital::join('user_entity_relation', 'hospitals.id', '=', 'user_entity_relation.entity_id')
                ->where('user_entity_relation.entity_type_id',EntityTypes::HOSPITAL)
                ->where('user_entity_relation.user_id',$user->id);
                
                $hospitals = $query->select('hospitals.*')->get();
                $hospitalData = [];
                $sliders = [];
    
                if(!empty($hospitals)) {
                    foreach ($hospitals as $hospital) {
                        $temp = [];
                        $temp['main_name'] = $hospital->main_name;
                        $temp['category_id'] = $hospital->category_id;
                        $temp['category_name'] = $hospital->category_name;
                        $temp['category_icon'] = $hospital->category_icon;
                        $temp['status_id'] = $hospital->status_id;
                        $temp['status_name'] = $hospital->status_name;
                        $temp['reviews'] = $hospital->reviews;
                        $temp['work_complete'] = $hospital->work_complete;
                        $temp['activate_post'] = $hospital->activate_post;
                        $temp['user_id'] = $hospital->user_id;
                        $temp['id'] = $hospital->id;
                        array_push($hospitalData,$temp);
                    }

                    $bannerSliders = Banner::join('banner_images', 'banners.id', '=', 'banner_images.banner_id')
                                        ->where('banners.entity_type_id',EntityTypes::HOSPITAL)
                                        ->where('banners.country_code',$main_country)
                                        ->where('banners.section','profile')
                                        ->whereNull('banners.deleted_at')
                                        ->whereNull('banner_images.deleted_at')
                                        ->orderBy('banner_images.order','asc')
                                        ->get('banner_images.*');
                
                    foreach($bannerSliders as $banner){
                        $temp = [];
                        $temp['image'] = Storage::disk('s3')->url($banner->image);
                        $temp['link'] = $banner->link;
                        $temp['order'] = $banner->order;
                        $sliders[] = $temp;
                    }
                }  
                
                $data = [];
                $config = Config::where('key',Config::HOSPITAL_RECOMMEND_MONEY)->first();
                $hospital_recommended_coins = $config ? (int) filter_var($config->value, FILTER_SANITIZE_NUMBER_INT) : 0;

                $config1 = Config::where('key', Config::SNS_REWARD)->first();
                $instagram_reward = $config1 ? (int) filter_var($config1->value, FILTER_SANITIZE_NUMBER_INT) : 0;

                $configShowCoin = Config::where('key', Config::SHOW_COIN_INFO)->first();
                $show_coin_info = $configShowCoin ? (int) filter_var($configShowCoin->value, FILTER_SANITIZE_NUMBER_INT) : 0;

                Log::info('End code for the get hospital list');
                if(!empty($hospitals)) {                
                    $snsData = DB::table('user_intagram_history')->where('user_id',$user->id)->first();
                    $canRequestActive = true;
                    $buttonKey = "language_$language_id.request_reward";
                    $daysDiff = '';
                    if(!empty($snsData)){
                        $requestDate = $snsData->requested_at;
                        $configData = Config::where('key',Config::SPONSOR_POST_LIMIT)->first();
                        $subDays = (!empty($configData) && !empty($configData->value)) ? $configData->value : 0;
                        
                        $checkDate = Carbon::parse($requestDate)->addDays($subDays);
                        if(Carbon::now()->lt($checkDate)){
                            $daysDiff = $checkDate->diffInDays() + 1;
                            if($daysDiff == 1){
                                $daysDiff = $checkDate->diffInHours();
                                $buttonKey = "language_$language_id.request_reward_disable_hours";
                            }else{
                                $buttonKey = "language_$language_id.request_reward_disable_days";
                            }
                            $canRequestActive = false;
                        }
                    }

                    $requestButtonLabel = __("messages.$buttonKey",['ntime' => $daysDiff]);
                    $data['hospitals'] = $hospitalData;
                    $data['recommended_code'] = $user->recommended_code;
                    $data['package_plan_id'] = $user->package_plan_id;
                    $data['package_plan_name'] = $user->package_plan_name;
                    $data['total_credits'] = number_format((float)$user->user_credits);
                    $data['recommended_coins'] = number_format((float)$hospital_recommended_coins);
                    $data['instagram_reward_coins'] = number_format((float)$instagram_reward);
                    $data['sns_type'] = $user->sns_type;
                    $data['sns_link'] = $user->sns_link;
                    $data['can_request'] = $canRequestActive;
                    $data['request_button_label'] = $requestButtonLabel;
                    $data['show_coin_info'] = $show_coin_info;
                    $data['sliders'] = $sliders;
                    
                    return $this->sendSuccessResponse(Lang::get('messages.hospital.success'), 200, $data);
                }else{
                    return $this->sendSuccessResponse(Lang::get('messages.hospital.empty'), 501);
                }
            }
            else{
                Log::info('End code for the get hospital list');
                return $this->sendSuccessResponse(Lang::get('messages.user.token_expired'), 401);
            }
        } catch (\Exception $e) {
            Log::info('Exception in the get hospital list');
            Log::info($e);
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }    
    
    public function editHospitalProfile(Request $request,$id)
    {
        $user = Auth::user();
        try {
            Log::info('Start code for the get hospital list');     
            if($user) {

                $hospitalExists = Hospital::where('id',$id)->first();
                if($hospitalExists){
                    $hospital = Hospital::with(['address' => function($query) {
                        $query->where('entity_type_id', EntityTypes::HOSPITAL);
                    }])->where('id',$id)->first();
                    
                    $data = [];
                    Log::info('End code for the get hospital list');
                    if(!empty($hospital)) {   
                        return $this->sendSuccessResponse(Lang::get('messages.hospital.edit-success'), 200, $hospital);
                    }else{
                        return $this->sendSuccessResponse(Lang::get('messages.hospital.empty'), 501);
                    }
                }else{
                    return $this->sendSuccessResponse(Lang::get('messages.hospital.empty'), 402);
                }
            }  else{
                Log::info('End code for the get hospital list');
                return $this->sendSuccessResponse(Lang::get('messages.user.token_expired'), 401);
            }    

        } catch (\Exception $e) {
            Log::info('Exception in the get hospital list');
            Log::info($e);
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }
   
    public function updateHospitalBusinessProfile(Request $request , $id)
    {
        $user = Auth::user();
        $inputs = $request->all();
        try {
            Log::info('Start code for the update hospital list');   
            if($user){
                DB::beginTransaction();
                $hospitalExists = Hospital::find($id);
                if($hospitalExists){
                    $validation = $this->hospitalProfileValidator->validateUpdate($inputs);
                    if ($validation->fails()) {
                        Log::info('End code for update hospital');
                        return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
                    }
                    $requestData = [
                        'main_name' => $inputs['main_name'],
                        'description' => $inputs['description'],
                        'business_license_number' => $inputs['business_license_number'],
                    ];                    
        
                    if(!empty($inputs['country_id']) && !empty($inputs['state_id']) && !empty($inputs['city_id'])){
                        $location = $this->addCurrentLocation($inputs['country_id'], $inputs['state_id'], $inputs['city_id']);
                        $country_code = getCountryFromLatLong($inputs['latitude'],$inputs['longitude']);
                        if ($location) {
                            $address = Address::updateOrCreate(['entity_type_id' => EntityTypes::HOSPITAL, 'entity_id'=> $id],[
                                        'address' => $inputs['address'],
                                        'address2' => $inputs['address_detail'],
                                        'country_id' => $location['country']->id,
                                        'city_id' => $location['city']->id,
                                        'state_id' => $location['city']->state_id,
                                        'latitude' => $inputs['latitude'],
                                        'longitude' => $inputs['longitude'],
                                        'main_country' => $country_code,
                                        'entity_type_id' => EntityTypes::HOSPITAL,
                                        'entity_id'=> $id
                                    ]);
                        }
                    }        
                                      
                    $hospitalImages = [];
                    if(!empty($inputs['images'])){
                        $hospitalsFolder = config('constant.hospitals').'/'.$id;                     
                    
                        if (!Storage::exists($hospitalsFolder)) {
                            Storage::makeDirectory($hospitalsFolder);
                        }  
                        foreach($inputs['images'] as $image) {
                            $mainProfile = Storage::disk('s3')->putFile($hospitalsFolder, $image,'public');
                            $fileName = basename($mainProfile);
                            $image_url = $hospitalsFolder . '/' . $fileName;
                            $temp = [
                                'hospital_id' => $id,
                                'image' => $image_url
                            ];
                            array_push($hospitalImages,$temp);
                        }
                    }                        
        
                   $updateHospital = Hospital::where('id', $id)->update($requestData);

                   if(!empty($inputs['deleted_image'])){
                    foreach($inputs['deleted_image'] as $deleteImage) {
                       $image = DB::table('hospital_images')->whereId($deleteImage)->whereNull('deleted_at')->first();
                       if($image) {
                           Storage::disk('s3')->delete($image->image);
                           HospitalImages::where('id',$image->id)->delete();
                       }
                    }
                   }
                   if(count($hospitalImages) > 0){
                        // $deleteOld = HospitalImages::where('hospital_id',$id)->get();
                        // foreach($deleteOld as $file){
                        //     $image_url = Storage::delete($file->image_url);
                        // }   
                        // HospitalImages::where('hospital_id',$id)->delete();                    
                        foreach($hospitalImages as $val){
                            $addNew = HospitalImages::create($val);
                        }
                   }

                   $return = $this->checkHospitalStatus($id);

                   $returnData = Hospital::with(['address' => function($query) {
                                    $query->where('entity_type_id', EntityTypes::HOSPITAL);
                                }])->where('id',$id)->first();
                    DB::commit();
                   Log::info('End code for the update hospital');
                   return $this->sendSuccessResponse(Lang::get('messages.hospital.update-success'), 200, $returnData);
                }else{
                    Log::info('End code for the update hospital');
                    return $this->sendSuccessResponse(Lang::get('messages.hospital.empty'), 402);
                }
            }else{
                Log::info('End code for update hospital');
                return $this->sendSuccessResponse(Lang::get('messages.user.token_expired'), 401);
            }   
        } catch (\Exception $e) {
            Log::info('Exception in update hospital');
            Log::info($e);
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function hospitalPost(Request $request , $id)
    {
        $user = Auth::user();
        $inputs = $request->all();
        try {
            Log::info('Start code for the hospital posts');   
            if($user){
                DB::beginTransaction();
                $hospitalExists = Hospital::find($id);
                if($hospitalExists){
                    $userPlanPostCount = CreditPlans::where('entity_type_id', EntityTypes::HOSPITAL)->where('package_plan_id', $user->package_plan_id)->first();
                    $visiblePostCount = !empty($userPlanPostCount) ? $userPlanPostCount->no_of_posts : 0;
                    $activePosts = Post::where('hospital_id',$id)->where('status_id',Status::ACTIVE)->orderBy('created_at','asc')->get();
                    $readyPosts = Post::where('hospital_id',$id)->where('status_id',Status::FUTURE)->get();
                    $pendingPosts = Post::where('hospital_id',$id)->whereIn('status_id',[Status::PENDING, Status::INACTIVE, Status::EXPIRE])->get();

                    if(!empty($activePosts)){
                        foreach($activePosts as $key => $post){
                            $post->is_post_visible = ($key + 1 > $visiblePostCount) ? false : true;
                        }
                    }
                    $returnData = [
                        'vesible_post_count' => $visiblePostCount,
                        'is_upgrade_visible' => (count($activePosts) > $userPlanPostCount->no_of_posts) ? true : false,
                        'active_post' => $activePosts,
                        'ready_post' => $readyPosts,
                        'pending_post' => $pendingPosts,
                    ];

                    DB::commit();
                   Log::info('End code for the hospital posts');
                   return $this->sendSuccessResponse(Lang::get('messages.hospital.post-success'), 200, $returnData);
                }else{
                    Log::info('End code for the hospital posts');
                    return $this->sendSuccessResponse(Lang::get('messages.hospital.empty'), 402);
                }
            }else{
                Log::info('End code for hospital posts');
                return $this->sendSuccessResponse(Lang::get('messages.user.token_expired'), 401);
            }   
        } catch (\Exception $e) {
            Log::info('Exception in hospital posts');
            Log::info($e);
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function checkHospitalStatus($id) {

        $currentHospital = Hospital::with(['address' => function($query) {
            $query->where('entity_type_id', EntityTypes::HOSPITAL);
        }])->where('id',$id)->first();

        $hospitalDoctors = HospitalDoctor::where('hospital_id',$id)->count();

        $isHospitalImages = count($currentHospital->images) > 0 ? true : false;
        $isDescription = $currentHospital->description != NULL ? true : false;
        $isAddress = $currentHospital->address->address != NULL ? true : false;
        $isDoctors = $hospitalDoctors > 0 ? true : false;

        if($isHospitalImages && $isDescription && $isDoctors && $isAddress){
            Hospital::where('id',$id)->update(['status_id' => Status::ACTIVE,'deactivate_by_user' => 0]);
            Post::where('hospital_id',$id)->update(['status_id' => Status::ACTIVE]);
        }else {
            Hospital::where('id',$id)->update(['status_id' => Status::PENDING]);
            Post::where('hospital_id',$id)->update(['status_id' => Status::PENDING]);
        }
        return true;
    }

    public function statusDetail(Request $request)
    {
        try {
            
            Log::info('Start code for get hospital status');
            $user = Auth::user();
            if($user){
                DB::beginTransaction();
                $inputs = $request->all();
                $validation = $this->hospitalProfileValidator->validateGetStatus($inputs);
                
                if ($validation->fails()) {
                    Log::info('End code for get hospital status');
                    return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
                }   
                
                $currentHospital = Hospital::with(['address' => function($query) {
                    $query->where('entity_type_id', EntityTypes::HOSPITAL);
                }])->where('id',$inputs['hospital_id'])->first();

                if($currentHospital && $currentHospital->last_activate_deactivate != NULL) {
                    $lastActiveDate = new Carbon($currentHospital->last_activate_deactivate);
                    $lastActiveDate = $lastActiveDate->addDays(30);
                    $current_date = Carbon::now();
                    $can_activate_deactivate = $current_date->greaterThan($lastActiveDate) ? 1 : 0;
                }else {
                    $can_activate_deactivate = 1;
                }

        
                $hospitalDoctors = HospitalDoctor::where('hospital_id',$inputs['hospital_id'])->count();

                $user_detail = UserDetail::where('user_id', $currentHospital->user_id)->first();
                $creditPlan = CreditPlans::where('entity_type_id', EntityTypes::HOSPITAL)->where('package_plan_id', $user_detail->package_plan_id)->first();
                $minHospitalCredit = $creditPlan ? $creditPlan->amount : 0;
                $userCredits = UserCredit::where('user_id',$currentHospital->user_id)->first(); 
        
                $pendingData['interior_image'] = count($currentHospital->images) > 0 ? true : false;
                $pendingData['hospital_introduction'] = $currentHospital->description != NULL ? true : false;
                $pendingData['address_information'] = $currentHospital->address->address != NULL ? true : false;
                $pendingData['doctors_information'] = $hospitalDoctors > 0 ? true : false;

                $user_detail_new = DB::table('users_detail')->where('user_id', $user->id)->first();

                $deactivateData['not_enough_coin'] = ($userCredits->credits < $minHospitalCredit) ? true : false;
                $deactivateData['deactivated_by_you'] = $currentHospital->deactivate_by_user == 1 ? true : false;

                $data = [
                    'pending' => $pendingData,
                    'deactivate' => $deactivateData,
                    'can_activate_deactivate' => $can_activate_deactivate
                ];
                DB::commit();
                Log::info('End code for get hospital status');
                return $this->sendSuccessResponse(Lang::get('messages.hospital.status-success'), 200, $data);
            }else{
                Log::info('End code for get hospital status');
                return $this->sendSuccessResponse(Lang::get('messages.user.token_expired'), 401);
            } 
        } catch (\Exception $ex) {
            Log::info('Exception in the get hospital status');
            Log::info($ex);
            DB::rollback();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function statusChange(Request $request)
    {
        try {
            
            Log::info('Start code for change hospital status');
            $user = Auth::user();
            if($user){
                DB::beginTransaction();
                $inputs = $request->all();
                $validation = $this->hospitalProfileValidator->validateStatusChange($inputs);
                
                if ($validation->fails()) {
                    Log::info('End code for change hospital status');
                    return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
                }  
                $hospital = Hospital::find($inputs['hospital_id']);
                $devices = UserDevices::whereIn('user_id', [$user->id])->pluck('device_token')->toArray();
                $user_detail = UserDetail::where('user_id', $user->id)->first();
                $language_id = $user_detail->language_id;
                $title_msg = '';

                $notificationData = [
                    'id' => $hospital->id,
                    'main_name' => $hospital->main_name,
                    'category_id' => $hospital->category_id,
                    'category_name' => $hospital->category_name,
                    'category_icon' => $hospital->category_icon,
                ];
                 

                if($inputs['status_id'] == Status::UNHIDE) {
                    $key = Notice::PROFILE_UNHIDE.'_'.$language_id;
                    $format = __("notice.$key");
                    $notify_type = Notice::PROFILE_UNHIDE;
                    $notice = Notice::create([
                        'notify_type' => Notice::PROFILE_UNHIDE,
                        'user_id' => $user->id,
                        'to_user_id' => $user->id,
                        'entity_type_id' => EntityTypes::HOSPITAL,
                        'entity_id' => $hospital->id,
                        'sub_title' => $hospital->main_name,
                    ]); 
                    $return = $this->checkHospitalStatus($inputs['hospital_id']);
                }else {
                    $data = ['status_id' => $inputs['status_id']];
                    $data['deactivate_by_user'] = $inputs['status_id'] == Status::INACTIVE ? 1 : 0;
                    
                    if($inputs['status_id'] == Status::ACTIVE) {
                        $data['last_activate_deactivate'] = Carbon::now();
                        $key = Notice::PROFILE_ACTIVATE.'_'.$language_id;
                        $format = __("notice.$key");
                        $notify_type = Notice::PROFILE_ACTIVATE;
                        $notice = Notice::create([
                            'notify_type' => Notice::PROFILE_ACTIVATE,
                            'user_id' => $user->id,
                            'to_user_id' => $user->id,
                            'entity_type_id' => EntityTypes::HOSPITAL,
                            'entity_id' => $hospital->id,
                            'sub_title' => $hospital->main_name,
                        ]); 
                       $posts = Post::where('hospital_id',$inputs['hospital_id'])->whereIn('status_id',[Status::INACTIVE])->get();
                        foreach($posts as $post){
                            $fromDate = new Carbon($post->from_date);
                            $toDate = new Carbon($post->to_date);
                            $date1 = Carbon::now();
                            $currentDate = $date1->format('d-m-Y');
                            $fromDate1 = $fromDate->format('d-m-Y');
                            $toDate1 = $toDate->format('d-m-Y');
                            
                            $check = Carbon::now()->between($fromDate,$toDate);
                            
                            if($check || $currentDate == $fromDate1 || $currentDate == $toDate1) {
                                Post::where('id', $post->id)->update(['status_id' => Status::ACTIVE]) ;
                            }else if(!$fromDate->isPast() &&  !$toDate->isPast()) {
                                Post::where('id', $post->id)->update(['status_id' => Status::FUTURE]) ;
                            }else if ($fromDate->isPast() &&  $toDate->isPast()) {
                                Post::where('id', $post->id)->update(['status_id' => Status::EXPIRE]) ;
                            } 
                        }
                    }else {
                        if($inputs['status_id'] == Status::INACTIVE) {
                            $data['last_activate_deactivate'] = Carbon::now();
                            $key = Notice::PROFILE_DEACTIVATE.'_'.$language_id;
                            $format = __("notice.$key");
                            $notify_type = Notice::PROFILE_DEACTIVATE;
                            $notice = Notice::create([
                                'notify_type' => Notice::PROFILE_DEACTIVATE,
                                'user_id' => $user->id,
                                'to_user_id' => $user->id,
                                'entity_type_id' => EntityTypes::HOSPITAL,
                                'entity_id' => $hospital->id,
                                'sub_title' => $hospital->main_name,
                            ]);  
                        }elseif($inputs['status_id'] == Status::PENDING) {
                            $key = Notice::PROFILE_PENDING.'_'.$language_id;
                            $format = __("notice.$key");
                            $notify_type = Notice::PROFILE_PENDING;
                            $notice = Notice::create([
                                'notify_type' => Notice::PROFILE_PENDING,
                                'user_id' => $user->id,
                                'to_user_id' => $user->id,
                                'entity_type_id' => EntityTypes::HOSPITAL,
                                'entity_id' => $hospital->id,
                                'sub_title' => $hospital->main_name,
                            ]); 
                        }elseif($inputs['status_id'] == Status::HIDDEN) {
                            $key = Notice::PROFILE_HIDE.'_'.$language_id;
                            $format = __("notice.$key");
                            $notify_type = Notice::PROFILE_HIDE;
                            $notice = Notice::create([
                                'notify_type' => Notice::PROFILE_HIDE,
                                'user_id' => $user->id,
                                'to_user_id' => $user->id,
                                'entity_type_id' => EntityTypes::HOSPITAL,
                                'entity_id' => $hospital->id,
                                'sub_title' => $hospital->main_name,
                            ]); 
                        }
                        Post::where('hospital_id',$inputs['hospital_id'])->whereIn('status_id',[Status::ACTIVE,Status::FUTURE])->update(['status_id' => $inputs['status_id']]);
                    }

                    Hospital::where('id',$inputs['hospital_id'])->update($data);
                }

                $this->updateUserChatStatus();
                if (count($devices) > 0) {
                    $result = $this->sentPushNotification($devices,$title_msg, $format, $notificationData ,$notify_type, $hospital->id);                        
                }
                
                DB::commit();
                Log::info('End code for change hospital status');
                return $this->sendSuccessResponse(Lang::get('messages.hospital.status-change-success'), 200);
            }else{
                Log::info('End code for change hospital status');
                return $this->sendSuccessResponse(Lang::get('messages.user.token_expired'), 401);
            } 
        } catch (\Exception $ex) {
            Log::info('Exception in the change hospital status');
            Log::info($ex);
            DB::rollback();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }
   
}
