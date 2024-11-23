<?php

namespace App\Http\Controllers\Api;

use DateTime;
use Validator;
use Carbon\Carbon;
use App\Models\Post;
use App\Models\Shop;
use App\Models\Config;
use App\Models\Notice;
use App\Models\Status;
use App\Util\Firebase;
use App\Models\Address;
use App\Models\Category;
use App\Models\Hospital;
use App\Models\UserCredit;
use App\Models\UserDetail;
use App\Models\ActivityLog;
use App\Models\CreditPlans;
use App\Models\EntityTypes;
use App\Models\UserDevices;
use Illuminate\Http\Request;
use App\Models\CompletedCustomer;
use App\Models\RequestedCustomer;
use App\Models\UserCreditHistory;
use App\Models\CustomerAttachment;
use App\Models\UserEntityRelation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Models\RequestBookingStatus;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Mail;
use App\Models\CompleteCustomerDetails;
use Illuminate\Support\Facades\Storage;
use App\Validators\RequestServiceValidator;


class RequestToAskController extends Controller
{
    private $requestServiceValidator;
    protected $firebase;

    function __construct()
    {
        $this->requestServiceValidator = new RequestServiceValidator();
        $this->firebase = new Firebase();
    }   
   
    public function requestService(Request $request)
    {        
        try {
            Log::info('Start code for add request service');   
            $inputs = $request->all();
            // $bookingDate = Carbon::createFromFormat('Y-m-d', $inputs['booking_date'], env('SERVER_TIMEZONE', 'Asia/Kolkata'))->setTimezone('UTC');
            // $bookingTime = Carbon::createFromFormat('H:i', $inputs['booking_date'], env('SERVER_TIMEZONE', 'Asia/Kolkata'))->setTimezone('UTC');
            $user = Auth::user();
            if($user){
                DB::beginTransaction();
                $validation = $this->requestServiceValidator->validateStore($inputs);
                if ($validation->fails()) {
                    Log::info('End code for add request service');
                    return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
                }
                $main_country = getCountryFromLatLong($inputs['latitude'],$inputs['longitude']);
                $timezone = get_nearest_timezone($inputs['latitude'],$inputs['longitude'],$main_country);

                $bookingDate = Carbon::createFromFormat('Y-m-d H:i:s', $inputs['booking_date'], $timezone)->setTimezone('UTC');
                if($inputs['entity_type_id'] == EntityTypes::SHOP){
                    $service = Shop::find($inputs['entity_id']);
                    $event_name = $service->main_name .'/'.$service->shop_name;
                    $event_user_id = $service->user_id;
                }else {
                    $service = Post::find($inputs['entity_id']);
                    $event_name = $service->hospital_name;
                    $event_user_id = $service->user_id;
                }
                if($service){
                    
                    $data = $where = [
                        'entity_type_id' => $inputs['entity_type_id'],
                        'entity_id' => $inputs['entity_id'],
                        'user_id' => $inputs['user_id'],
                        'request_booking_status_id' => RequestBookingStatus::TALK
                    ];   

                    $data['booking_date'] = $bookingDate;
                    $data['request_booking_status_id'] = RequestBookingStatus::BOOK;                  
                    $requestCustomerData = RequestedCustomer::where('entity_type_id',$inputs['entity_type_id'])
                                                        ->where('entity_id',$inputs['entity_id'])
                                                        ->where('user_id',$inputs['user_id'])
                                                        ->where('request_booking_status_id',RequestBookingStatus::TALK)->orderBy('id','desc')->first();
                    if($requestCustomerData) {
                        $requestCustomerUpdate = RequestedCustomer::where('id',$requestCustomerData->id)->update($data); 
                        $dataId = $requestCustomerData->id;
                    }  else {
                        $requestCustomerCreate = RequestedCustomer::create($data); 
                        $dataId = $requestCustomerCreate->id;
                    }   
                    $requestCustomer = RequestedCustomer::find($dataId);                          
                    $country = '';
                    if($inputs['entity_type_id'] == EntityTypes::SHOP) {
                        $address = Address::where('entity_type_id',$inputs['entity_type_id'])
                                            ->where('entity_id',$inputs['entity_id'])->first();
                        $country = $address ? $address->main_country : '';

                    }else if ($inputs['entity_type_id'] == EntityTypes::HOSPITAL) {
                        $post = Post::find($inputs['entity_id']);
                        $hospital_id = $post ? $post->hospital_id : null;
                        $address = Address::where('entity_type_id',$inputs['entity_type_id'])
                                            ->where('entity_id',$hospital_id)->first();
                        $country = $address ? $address->main_country : '';
                    }

                    ActivityLog::create([
                        'entity_type_id' => $inputs['entity_type_id'],
                        'entity_id' => $inputs['entity_id'],
                        'user_id' => $inputs['user_id'],
                        'country' => $country,
                        'request_booking_status_id' => RequestBookingStatus::BOOK,
                    ]);                     
                    
                    $notificationData = [];
                    if($requestCustomer && $requestCustomer->entity_type_id == EntityTypes::SHOP) {
                        $notificationData = RequestedCustomer::join('shops','shops.id','=','requested_customer.entity_id')
                        ->join('category','category.id','=','shops.category_id')
                        ->where('requested_customer.entity_type_id',EntityTypes::SHOP)
                        ->where('requested_customer.id',$requestCustomer->id)
                        ->select(['requested_customer.*','shops.main_name','shops.category_id'])->first()->toArray();
                    }else {
                        $notificationData = RequestedCustomer::join('posts','posts.id','=','requested_customer.entity_id')
                                        ->join('hospitals','hospitals.id','=','posts.hospital_id')
                                        ->join('category','category.id','=','posts.category_id')
                                        ->where('requested_customer.entity_type_id',EntityTypes::HOSPITAL)
                                        ->where('requested_customer.id',$requestCustomer->id)
                                        ->select(['requested_customer.*','hospitals.main_name','posts.id as post_id','posts.category_id','category.name as category_name'])->first()->toArray();
                    }

                    $userIds = [$inputs['user_id'],$event_user_id];

                    foreach($userIds as $uId){
                        $devices = UserDevices::whereIn('user_id', [$uId])->pluck('device_token')->toArray();
                        $user_detail = UserDetail::where('user_id', $uId)->first();
                        $language_id = $user_detail->language_id;
                        $key = Notice::BOOKING.'_'.$language_id;
                        $format = __("notice.$key", ['name' => $event_name]);
                        $title_msg = '';
                        $notify_type = 'book_service';

                        $notice = Notice::create([
                            'notify_type' => Notice::BOOKING,
                            'user_id' => $uId,
                            'to_user_id' => $uId,
                            'entity_type_id' => $requestCustomer->entity_type_id,
                            'entity_id' => $requestCustomer->id,
                            'title' => $notificationData['main_name'],
                            'sub_title' => $requestCustomer->booking_date,
                        ]);
                        if (count($devices) > 0) {
                            $result = $this->sentPushNotification($devices,$title_msg, $format, $notificationData ,$notify_type, $service->id);                        
                        }
                    }
                    
                    DB::commit();
                   Log::info('End code for the add request service');
                   return $this->sendSuccessResponse(Lang::get('messages.request-service.add-success'), 200);
                }else{
                    Log::info('End code for the add request service');
                    $message = $inputs['entity_type_id'] == EntityTypes::SHOP ? Lang::get('messages.shop.empty') : Lang::get('messages.post.empty');
                    return $this->sendSuccessResponse($message, 402);
                }
            }else{
                Log::info('End code for add request service');
                return $this->sendSuccessResponse(Lang::get('messages.user.token_expired'), 401);
            }   
        } catch (\Exception $e) {
            Log::info('Exception in add request service');
            Log::info($e);
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function completeService(Request $request,$id)
    {        
        try {
            Log::info('Start code for complete service');   
            $user = Auth::user();
            if($user){
                DB::beginTransaction();
                $service = RequestedCustomer::find($id);
                if($service){
                    
                    $requestCustomer = RequestedCustomer::where('id',$id)->update(['request_booking_status_id' => RequestBookingStatus::COMPLETE,'show_in_home' => 1]);   
                    $country = '';
                    if($service->entity_type_id == EntityTypes::SHOP) {
                        $address = Address::where('entity_type_id',$service->entity_type_id)
                                            ->where('entity_id',$service->entity_id)->first();
                        $country = $address ? $address->main_country : '';

                    }else if ($service->entity_type_id == EntityTypes::HOSPITAL) {
                        $post = Post::find($service->entity_id);
                        $hospital_id = $post ? $post->hospital_id : null;
                        $address = Address::where('entity_type_id',$service->entity_type_id)
                                            ->where('entity_id',$hospital_id)->first();
                        $country = $address ? $address->main_country : '';
                    }

                    ActivityLog::create([
                        'entity_type_id' => $service->entity_type_id,
                        'entity_id' => $service->entity_id,
                        'user_id' => $service->user_id,
                        'country' => $country,
                        'request_booking_status_id' => RequestBookingStatus::COMPLETE,
                    ]); 
                    $service = RequestedCustomer::find($id);
                   DB::commit();
                   Log::info('End code for the complete service');
                   return $this->sendSuccessResponse(Lang::get('messages.request-service.complete-success'), 200,$service);
                }else{
                    Log::info('End code for the complete service');
                    return $this->sendSuccessResponse(Lang::get('messages.request-service.empty'), 402);
                }
            }else{
                Log::info('End code for complete service');
                return $this->sendSuccessResponse(Lang::get('messages.user.token_expired'), 401);
            }   
        } catch (\Exception $e) {
            Log::info('Exception in complete service');
            Log::info($e);
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }
    public function noShowService(Request $request,$id)
    {        
        try {
            Log::info('Start code for noshow service');   
            $user = Auth::user();
            if($user){
                DB::beginTransaction();
                $service = RequestedCustomer::find($id);
                if($service){
                    
                    $requestCustomer = RequestedCustomer::where('id',$id)->update(['request_booking_status_id' => RequestBookingStatus::NOSHOW]);   
                    $country = '';
                    if($service->entity_type_id == EntityTypes::SHOP) {
                        $address = Address::where('entity_type_id',$service->entity_type_id)
                                            ->where('entity_id',$service->entity_id)->first();
                        $country = $address ? $address->main_country : '';

                    }else if ($service->entity_type_id == EntityTypes::HOSPITAL) {
                        $post = Post::find($service->entity_id);
                        $hospital_id = $post ? $post->hospital_id : null;
                        $address = Address::where('entity_type_id',$service->entity_type_id)
                                            ->where('entity_id',$hospital_id)->first();
                        $country = $address ? $address->main_country : '';
                    }

                    $requestCustomer = RequestedCustomer::find($id);
                    $notificationData = [];
                    if($requestCustomer && $requestCustomer->entity_type_id == EntityTypes::SHOP) {
                        $notificationData = RequestedCustomer::join('shops','shops.id','=','requested_customer.entity_id')
                        ->join('category','category.id','=','shops.category_id')
                        ->where('requested_customer.entity_type_id',EntityTypes::SHOP)
                        ->where('requested_customer.id',$requestCustomer->id)
                        ->select(['requested_customer.*','shops.main_name','shops.category_id'])->first()->toArray();
                    }else {
                        $notificationData = RequestedCustomer::join('posts','posts.id','=','requested_customer.entity_id')
                                        ->join('hospitals','hospitals.id','=','posts.hospital_id')
                                        ->join('category','category.id','=','posts.category_id')
                                        ->where('requested_customer.entity_type_id',EntityTypes::HOSPITAL)
                                        ->where('requested_customer.id',$requestCustomer->id)
                                        ->select(['requested_customer.*','hospitals.main_name','posts.id as post_id','posts.category_id','category.name as category_name'])->first()->toArray();
                    }    

                    $userIds = [$service->user_id];

                    $devices = UserDevices::whereIn('user_id', [$userIds])->pluck('device_token')->toArray();
                    $user_detail = UserDetail::where('user_id', $service->user_id)->first();
                    $language_id = $user_detail->language_id;
                    $key = Notice::NOSHOW.'_'.$language_id;
                    $format = __("notice.$key");
                    $title_msg = '';
                    $notify_type = Notice::NOSHOW;

                    $notice = Notice::create([
                        'notify_type' => Notice::NOSHOW,
                        'user_id' => $service->user_id,
                        'to_user_id' => $service->user_id,
                        'entity_type_id' => $service->entity_type_id,
                        'entity_id' => $service->id,
                        'title' => $service->main_name,
                    ]);
                    if (count($devices) > 0) {
                        $result = $this->sentPushNotification($devices,$title_msg, $format, $notificationData ,$notify_type, $service->id);                        
                    }
                    
                    ActivityLog::create([
                        'entity_type_id' => $service->entity_type_id,
                        'entity_id' => $service->entity_id,
                        'user_id' => $service->user_id,
                        'country' => $country,
                        'request_booking_status_id' => RequestBookingStatus::NOSHOW,
                    ]); 
                    $service = RequestedCustomer::find($id);
                   DB::commit();
                   Log::info('End code for the noshow service');
                   return $this->sendSuccessResponse(Lang::get('messages.request-service.noshow-success'), 200,$service);
                }else{
                    Log::info('End code for the noshow service');
                    return $this->sendSuccessResponse(Lang::get('messages.request-service.empty'), 402);
                }
            }else{
                Log::info('End code for noshow service');
                return $this->sendSuccessResponse(Lang::get('messages.user.token_expired'), 401);
            }   
        } catch (\Exception $e) {
            Log::info('Exception in noshow service');
            Log::info($e);
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function cancelService(Request $request,$id)
    {        
        try {
            Log::info('Start code for cancel service');   
            $inputs = $request->all();
            $user = Auth::user();
            if($user){
                DB::beginTransaction();
                $validation = $this->requestServiceValidator->validateCancel($inputs);
                if ($validation->fails()) {
                    Log::info('End code for cancel service');
                    return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
                }
                $service = RequestedCustomer::find($id);
                
                if($service){
                    $updateData = [
                        'request_booking_status_id' => RequestBookingStatus::CANCEL, 
                        'is_cancelled_by_shop' => $inputs['user_cancelled'] == 1 ? 0 : 1
                    ];
                    if(isset($inputs['reason'])) {
                        $updateData['comment'] = $inputs['reason'];
                    }
                    RequestedCustomer::where('id',$id)->update($updateData);   
                    $country = '';
                    if($service->entity_type_id == EntityTypes::SHOP) {
                        $address = Address::where('entity_type_id',$service->entity_type_id)
                                            ->where('entity_id',$service->entity_id)->first();
                        $country = $address ? $address->main_country : '';

                    }else if ($service->entity_type_id == EntityTypes::HOSPITAL) {
                        $post = Post::find($service->entity_id);
                        $hospital_id = $post ? $post->hospital_id : null;
                        $address = Address::where('entity_type_id',$service->entity_type_id)
                                            ->where('entity_id',$hospital_id)->first();
                        $country = $address ? $address->main_country : '';
                    }
                    ActivityLog::create([
                        'entity_type_id' => $service->entity_type_id,
                        'entity_id' => $service->entity_id,
                        'user_id' => $service->user_id,
                        'country' => $country,
                        'request_booking_status_id' => RequestBookingStatus::CANCEL,
                        'is_cancelled_by_shop' => $inputs['user_cancelled'] == 1 ? 0 : 1
                    ]);
                    $requestCustomer = RequestedCustomer::find($id);
                    if($requestCustomer->entity_type_id == EntityTypes::SHOP){
                        $service1 = Shop::find($requestCustomer->entity_id);
                        $event_name = $service1->main_name.'/'.$service1->shop_name;
                        $entityId = $service1->id;
                        $event_user_id = $service1->user_id;
                    }else {
                        $service1 = Post::find($requestCustomer->entity_id);
                        $event_name = $service1->hospital_name;
                        $entityId = $service1->hospital_id;
                        $event_user_id = $service1->user_id;
                    }
                    DB::commit();
                    
                    if($inputs['user_cancelled'] == 1){
                        $getUser = UserEntityRelation::where('entity_type_id',$service['entity_type_id'])
                                                    ->where('entity_id',$entityId)->first();
                        $user_id = $getUser ? $getUser->user_id : 0;
                    }else {
                        $user_id = $service->user_id;
                    }

                    $notificationData = [];
                    if($requestCustomer && $requestCustomer->entity_type_id == EntityTypes::SHOP) {
                        $notificationData = RequestedCustomer::join('shops','shops.id','=','requested_customer.entity_id')
                        ->join('category','category.id','=','shops.category_id')
                        ->where('requested_customer.entity_type_id',EntityTypes::SHOP)
                        ->where('requested_customer.id',$requestCustomer->id)
                        ->select(['requested_customer.*','shops.main_name','shops.category_id'])->first()->toArray();
                    }else {
                        $notificationData = RequestedCustomer::join('posts','posts.id','=','requested_customer.entity_id')
                                        ->join('hospitals','hospitals.id','=','posts.hospital_id')
                                        ->join('category','category.id','=','posts.category_id')
                                        ->where('requested_customer.entity_type_id',EntityTypes::HOSPITAL)
                                        ->where('requested_customer.id',$requestCustomer->id)
                                        ->select(['requested_customer.*','hospitals.main_name','posts.id as post_id','posts.category_id','category.name as category_name'])->first()->toArray();
                    }                    
                    $notificationData['user_cancelled'] = $inputs['user_cancelled'];
                    if($inputs['user_cancelled'] == 1){
                        $userIds = [$event_user_id];
                    }else {
                        $userIds = [$user_id];
                    }

                    foreach($userIds as $uId){
                        $devices = UserDevices::whereIn('user_id', [$uId])->pluck('device_token')->toArray();
                        $user_detail = UserDetail::where('user_id', $uId)->first();
                        $language_id = $user_detail->language_id;
                        $key = Notice::BOOKING_CANCEL.'_'.$language_id;
                        $format = __("notice.$key", ['name' => $event_name]);
                        $title_msg = '';
                        $notify_type = 'cancel_service';

                        $notice = Notice::create([
                            'notify_type' => Notice::BOOKING_CANCEL,
                            'user_id' => $uId,
                            'to_user_id' => $uId,
                            'entity_type_id' => $requestCustomer->entity_type_id,
                            'entity_id' => $requestCustomer->id,
                            'title' => $notificationData['main_name'],
                            'sub_title' => $requestCustomer->booking_date,
                        ]);
                        if (count($devices) > 0) {
                            if($service->request_booking_status_id != RequestBookingStatus::VISIT) {                                
                                $result = $this->sentPushNotification($devices,$title_msg, $format, $notificationData ,$notify_type, $service->id);                        
                            }
                        }
                    }                    
                    
                   
                   Log::info('End code for the cancel service');
                   return $this->sendSuccessResponse(Lang::get('messages.request-service.cancel-success'), 200,$requestCustomer);
                }else{
                    Log::info('End code for the cancel service');
                    return $this->sendSuccessResponse(Lang::get('messages.request-service.empty'), 402);
                }
            }else{
                Log::info('End code for cancel service');
                return $this->sendSuccessResponse(Lang::get('messages.user.token_expired'), 401);
            }   
        } catch (\Exception $e) {
            Log::info('Exception in cancel service');
            Log::info($e);
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function completedServiceMemo(Request $request,$id)
    {        
        try {
            Log::info('Start code for complete service memo');  
            $inputs = $request->all(); 
            $user = Auth::user();
            if($user){
                DB::beginTransaction();
                $service = RequestedCustomer::find($id);
                if($service){
                    $validation = $this->requestServiceValidator->validateCompleteMemo($inputs);
                    if ($validation->fails()) {
                        Log::info('End code for the complete service memo');
                        return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
                    }

                    $revenueValue = str_replace(",","",$inputs['revenue']);
                    $insertCustomer = CompletedCustomer::create([
                        'user_id' => $user->id,
                        'requested_customer_id' => $service->id,
                        'revenue' => (int)$revenueValue,
                        'customer_memo' => $inputs['comment'],
                        'date' => $service->booking_date
                    ]);
                    
                    if(isset($inputs['images']) && !empty($inputs['images'])){
                        $customerFolder = config('constant.customer_memo');
                        foreach($inputs['images'] as $imageFile) {
                            if(is_file($imageFile)){
                                $mainImage = Storage::disk('s3')->putFile($customerFolder, $imageFile,'public');
                                $fileName = basename($mainImage);
                                $image_url = $customerFolder . '/' . $fileName;
        
                                CustomerAttachment::create([
                                    'entity_id'=> $insertCustomer->id,
                                    'type' => CustomerAttachment::INSIDE,
                                    'image' => $image_url
                                ]);
                            }
                        }
                    }
                   DB::commit();
                   Log::info('End code for the complete service memo');
                   return $this->sendSuccessResponse(Lang::get('messages.request-service.memo-success'), 200);
                }else{
                    Log::info('End code for the complete service memo');
                    return $this->sendSuccessResponse(Lang::get('messages.request-service.empty'), 402);
                }
            }else{
                Log::info('End code for complete service memo');
                return $this->sendSuccessResponse(Lang::get('messages.user.token_expired'), 401);
            }   
        } catch (\Exception $e) {
            Log::info('Exception in complete service memo');
            Log::info($e);
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function getYearRevenue(Request $request)
    {        
        try {
            Log::info('Start code for add request service');   
            $inputs = $request->all();
            $user = Auth::user();
            if($user){
                DB::beginTransaction();
                $validation = $this->requestServiceValidator->validateYearRevenue($inputs);
                if ($validation->fails()) {
                    Log::info('End code for add request service');
                    return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
                }

                $yearAddedData = CompleteCustomerDetails::select('date', DB::raw("YEAR(date) as year"),DB::raw("(sum(revenue)) as yearly_revenue"),DB::raw("(count(*)) as yearly_work_completed"))
                    ->whereYear('date', $inputs['year'])
                    ->where('user_id',$user->id)
                    ->groupBy('year');

                $yearData = CompletedCustomer::select('date', DB::raw("YEAR(date) as year"),DB::raw("(sum(revenue)) as yearly_revenue"),DB::raw("(count(*)) as yearly_work_completed"))
                    ->whereYear('date', $inputs['year'])
                    ->where('user_id',$user->id)
                    ->groupBy('year')
                    ->union($yearAddedData)
                    ->get();

                $monthlyAddedData = CompleteCustomerDetails::select('date', DB::raw("MONTH(date) as month"),DB::raw("(sum(revenue)) as montly_revenue"),DB::raw("(count(*)) as montly_work_completed"))
                    ->whereYear('date', $inputs['year'])
                    ->where('user_id',$user->id)
                    ->groupBy('month');

                $monthlyDataResult = CompletedCustomer::select('date', DB::raw("MONTH(date) as month"),DB::raw("(sum(revenue)) as montly_revenue"),DB::raw("(count(*)) as montly_work_completed"))
                    ->whereYear('date', $inputs['year'])
                    ->where('user_id',$user->id)
                    ->union($monthlyAddedData)
                    ->groupBy('month')
                    ->get()->toArray();

                $monthlyDataResult = collect($monthlyDataResult)->groupBy('month')->toArray();
                $monthlyDataResponse =  $monthData = [];
                foreach($monthlyDataResult as $month => $data){
                    $monthData['month'] = $month;
                    if(count($data) > 1){
                        $monthData['montly_revenue'] = collect($data)->sum('montly_revenue');
                        $monthData['montly_work_completed'] = collect($data)->sum('montly_work_completed');
                    }else{
                        $monthData =  collect($data)->first();
                    }
                    $monthlyDataResponse[$month] = $monthData; 
                }

                $monthlyData = collect($monthlyDataResponse)->values()->toArray();
                
                for ($i = 1; $i <= 12 ; $i++) { 
                    $key = array_search($i, array_column($monthlyData, 'month'));
                    if($key === false) {
                        $temp = [];
                        $temp['month'] = $i;
                        $temp['montly_revenue'] = "0.00";
                        $temp['montly_work_completed'] = 0;
                        $monthlyData[] = $temp;
                    }                    
                }
                $temp1 = [];
                foreach ($monthlyData as $key => $value) {
                    $monthlyData[$key]['montly_revenue'] =number_format($value['montly_revenue'],0);

                    $temp1[] = $value['month'];
                }
                array_multisort($temp1, SORT_ASC, $monthlyData);
                $returnData = [
                    'year' => $inputs['year'],
                    'yearly_revenue' => count($yearData) > 0 ? number_format($yearData[0]->yearly_revenue) : "0",
                    'yearly_work_completed' => count($yearData) > 0 ? $yearData[0]->yearly_work_completed : 0,
                    'monthly_data' => $monthlyData
                ];
                   
                DB::commit();
                Log::info('End code for the add request service');
                return $this->sendSuccessResponse(Lang::get('messages.request-service.get-revenue'), 200,$returnData);
                
            }else{
                Log::info('End code for add request service');
                return $this->sendSuccessResponse(Lang::get('messages.user.token_expired'), 401);
            }   
        } catch (\Exception $e) {
            Log::info('Exception in add request service');
            Log::info($e);
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function getMonthRevenue(Request $request)
    {        
        try {
            Log::info('Start code for add request service');   
            $inputs = $request->all();
            $user = Auth::user();
            if($user){
                DB::beginTransaction();
                $validation = $this->requestServiceValidator->validateMonthRevenue($inputs);
                if ($validation->fails()) {
                    Log::info('End code for add request service');
                    return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
                }
                $returnData = [];
                $isShop = $user->entityType->contains('entity_type_id', EntityTypes::SHOP);
                $isHospital = $user->entityType->contains('entity_type_id', EntityTypes::HOSPITAL);

                /* ToDo un comment */
                $addCustomers = CompleteCustomerDetails::join('customer_lists','customer_lists.id','complete_customer_details.customer_id')
                    ->whereMonth('complete_customer_details.date', $inputs['month'])
                    ->whereYear('complete_customer_details.date', $inputs['year'])
                    ->where('complete_customer_details.user_id',$user->id)
                    ->select('complete_customer_details.*','customer_lists.customer_name as user_name','customer_lists.customer_phone as user_phone')
                    ->get();

                $defaultImage = asset('img/avatar/avatar-1.png');
                foreach($addCustomers as $customer){
                    $customer->user_image = $defaultImage;
                    $customer->customer_type = 'static';
                }

                if($isShop) {
                    $returnData = RequestedCustomer::join('shops','shops.id','=','requested_customer.entity_id')
                                    ->join('category','category.id','=','shops.category_id')
                                    ->join('completed_customer','completed_customer.requested_customer_id','=','requested_customer.id')
                                    ->where('requested_customer.entity_type_id',EntityTypes::SHOP)
                                    // ->where('shops.status_id', Status::ACTIVE)
                                    ->whereMonth('completed_customer.date', $inputs['month'])
                                    ->whereYear('completed_customer.date', $inputs['year'])
                                    ->where('completed_customer.user_id',$user->id)
                                    ->where('requested_customer.request_booking_status_id', RequestBookingStatus::COMPLETE)
                                    ->orderBy('requested_customer.created_at','desc')
                                    ->get(['requested_customer.*','shops.shop_name','shops.category_id']);
                                    foreach($returnData as $d){
                                        $category = Category::find($d->category_id);
                                        $d['category_name'] = $category->name;
                                        $d['category_logo'] = $category->logo;
                                    }
                }                
                if($isHospital) {
                    $returnData = RequestedCustomer::join('posts','posts.id','=','requested_customer.entity_id')
                                    ->join('category','category.id','=','posts.category_id')
                                    ->join('hospitals','hospitals.id','=','posts.hospital_id')
                                    ->join('completed_customer','completed_customer.requested_customer_id','=','requested_customer.id')
                                    ->where('requested_customer.entity_type_id',EntityTypes::HOSPITAL)
                                    // ->where('hospitals.status_id', Status::ACTIVE)
                                    ->whereMonth('completed_customer.date', $inputs['month'])
                                    ->where('completed_customer.user_id',$user->id)
                                    ->where('requested_customer.request_booking_status_id', RequestBookingStatus::COMPLETE)
                                    ->orderBy('requested_customer.created_at','desc')
                                    ->get(['requested_customer.*','hospitals.main_name','posts.category_id','category.name']);
                                    
                }        
                foreach($returnData as $customer){
                    $customer->customer_type = 'booked';
                }

                $returnData = $returnData->merge($addCustomers)->sortByDesc('created_at')->values();
                $data['revenue_data'] = $returnData;  
                DB::commit();
                Log::info('End code for the add request service');
                return $this->sendSuccessResponse(Lang::get('messages.request-service.get-revenue'), 200,$data);
                
            }else{
                Log::info('End code for add request service');
                return $this->sendSuccessResponse(Lang::get('messages.user.token_expired'), 401);
            }   
        } catch (\Exception $e) {
            Log::info('Exception in add request service');
            Log::info($e);
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function getUserRevenue(Request $request)
    {        
        try {
            Log::info('Start code for add request service');   
            $inputs = $request->all();
            $user = Auth::user();
            if($user){
                DB::beginTransaction();
                $validation = $this->requestServiceValidator->validateUserRevenue($inputs);
                if ($validation->fails()) {
                    Log::info('End code for add request service');
                    return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
                }
                $returnData = [];
                
                if($inputs['entity_type_id'] == EntityTypes::SHOP) {
                    $returnData = CompletedCustomer::join('requested_customer','completed_customer.requested_customer_id','=','requested_customer.id')
                                    ->join('shops','shops.id','=','requested_customer.entity_id')
                                    ->join('category','category.id','=','shops.category_id')                                    
                                    ->leftjoin('users_detail','users_detail.user_id','=','requested_customer.user_id')                                    
                                    ->where('requested_customer.entity_type_id',EntityTypes::SHOP)
                                    // ->where('shops.status_id', Status::ACTIVE)
                                    ->where('requested_customer.user_id',$inputs['user_id'])
                                    ->where('requested_customer.entity_id',$inputs['entity_id'])
                                    ->where('requested_customer.request_booking_status_id', RequestBookingStatus::COMPLETE)
                                    ->orderBy('requested_customer.created_at','desc')
                                    ->with('images')
                                    ->select('shops.category_id','completed_customer.*','users_detail.name as user_name')
                                    ->paginate(config('constant.pagination_count'),"*","revenue_data_page");
                                    foreach($returnData as $d){
                                        $category = Category::find($d->category_id);
                                        $d['category_name'] = $category->name;
                                        $d['category_logo'] = $category->logo;
                                        $d->revenue = number_format((float)$d->revenue,0);
                                    }
                }                
                if($inputs['entity_type_id'] == EntityTypes::HOSPITAL) {
                    $returnData = CompletedCustomer::join('requested_customer','completed_customer.requested_customer_id','=','requested_customer.id')
                                    ->join('posts','posts.id','=','requested_customer.entity_id')
                                    ->join('category','category.id','=','posts.category_id')
                                    ->join('hospitals','hospitals.id','=','posts.hospital_id')
                                    ->leftjoin('users_detail','users_detail.user_id','=','requested_customer.user_id')
                                    ->where('requested_customer.entity_type_id',EntityTypes::HOSPITAL)
                                    // ->where('hospitals.status_id', Status::ACTIVE)
                                    // ->whereMonth('completed_customer.date', $inputs['month'])
                                    ->where('requested_customer.user_id',$inputs['user_id'])
                                    ->where('requested_customer.entity_id',$inputs['entity_id'])
                                    ->where('requested_customer.request_booking_status_id', RequestBookingStatus::COMPLETE)
                                    ->orderBy('requested_customer.created_at','desc')
                                    ->with('images')
                                    ->select('category.name','category.id as category_id','completed_customer.*','users_detail.name as user_name')
                                    ->paginate(config('constant.pagination_count'),"*","revenue_data_page");
                                    foreach($returnData as $d){
                                        $category = Category::find($d->category_id);
                                        $d['category_logo'] = $category && $category->logo ? $category->logo : "";
                                        $d->revenue = number_format((float)$d->revenue,0);
                                    }
                                    
                }     

                $data['revenue_data'] = $returnData;
                DB::commit();
                Log::info('End code for the add request service');
                return $this->sendSuccessResponse(Lang::get('messages.request-service.get-revenue'), 200,$data);
                
            }else{
                Log::info('End code for add request service');
                return $this->sendSuccessResponse(Lang::get('messages.user.token_expired'), 401);
            }   
        } catch (\Exception $e) {
            Log::info('Exception in add request service');
            Log::info($e);
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function dismissService(Request $request,$id)
    {        
        try {
            Log::info('Start code for dismiss service');   
            $user = Auth::user();
            if($user){
                DB::beginTransaction();
                $service = RequestedCustomer::find($id);
                if($service){
                    
                    $requestCustomer = RequestedCustomer::where('id',$id)->update(['show_in_home' => 2]);   
                    $service = RequestedCustomer::find($id);
                   DB::commit();
                   Log::info('End code for the dismiss service');
                   return $this->sendSuccessResponse(Lang::get('messages.request-service.dismiss-success'), 200,$service);
                }else{
                    Log::info('End code for the dismiss service');
                    return $this->sendSuccessResponse(Lang::get('messages.request-service.empty'), 402);
                }
            }else{
                Log::info('End code for dismiss service');
                return $this->sendSuccessResponse(Lang::get('messages.user.token_expired'), 401);
            }   
        } catch (\Exception $e) {
            Log::info('Exception in dismiss service');
            Log::info($e);
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function changeServiceDate(Request $request,$id)
    {        
        try {
            Log::info('Start code for change service date');   
            $user = Auth::user();
            $inputs = $request->all();
            if($user){
                DB::beginTransaction();
                $service = RequestedCustomer::find($id);
                if($service){
                    $validation = $this->requestServiceValidator->validateChangeDate($inputs);
                    if ($validation->fails()) {
                        Log::info('End code for change service date');
                        return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
                    }
                    $main_country = getCountryFromLatLong($inputs['latitude'],$inputs['longitude']);
                    $timezone = get_nearest_timezone($inputs['latitude'],$inputs['longitude'],$main_country);

                    $bookingDate = Carbon::createFromFormat('Y-m-d H:i:s', $inputs['booking_date'], $timezone)->setTimezone('UTC');
                    $requestCustomer = RequestedCustomer::where('id',$id)->update(['request_booking_status_id' => RequestBookingStatus::BOOK,'booking_date' => $bookingDate]);   
                    $country = '';
                    if($service->entity_type_id == EntityTypes::SHOP) {
                        $address = Address::where('entity_type_id',$service->entity_type_id)
                                            ->where('entity_id',$service->entity_id)->first();
                        $country = $address ? $address->main_country : '';

                    }else if ($service->entity_type_id == EntityTypes::HOSPITAL) {
                        $post = Post::find($service->entity_id);
                        $hospital_id = $post ? $post->hospital_id : null;
                        $address = Address::where('entity_type_id',$service->entity_type_id)
                                            ->where('entity_id',$hospital_id)->first();
                        $country = $address ? $address->main_country : '';
                    }
                    ActivityLog::create([
                        'entity_type_id' => $service->entity_type_id,
                        'entity_id' => $service->entity_id,
                        'user_id' => $service->user_id,
                        'country' => $country,
                        'request_booking_status_id' => RequestBookingStatus::BOOK,
                    ]);
                    $service = RequestedCustomer::find($id);
                   DB::commit();
                   Log::info('End code for the change service date');
                   return $this->sendSuccessResponse(Lang::get('messages.request-service.change-date-success'), 200,$service);
                }else{
                    Log::info('End code for the change service date');
                    return $this->sendSuccessResponse(Lang::get('messages.request-service.empty'), 402);
                }
            }else{
                Log::info('End code for change service date');
                return $this->sendSuccessResponse(Lang::get('messages.user.token_expired'), 401);
            }   
        } catch (\Exception $e) {
            Log::info('Exception in change service date');
            Log::info($e);
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function getCompletedService(Request $request)
    {        
        try {
            Log::info('Start code for get completed service');   
            $user = Auth::user();
            $inputs = $request->all();
            if($user){
                DB::beginTransaction();
                $completedShop = RequestedCustomer::join('requested_customer as a', function($query) use ($user) {
                                                        $query->on('a.id','=','requested_customer.id')
                                                        ->whereRaw('a.id IN (select MAX(a2.id) from requested_customer as a2 where a2.request_booking_status_id = '.RequestBookingStatus::COMPLETE.' and a2.entity_type_id = '.EntityTypes::SHOP.' and a2.user_id = '.$user->id.' group by a2.entity_id)');
                                                    })
                                                    ->where('requested_customer.user_id',$user->id)
                                                    ->where('requested_customer.request_booking_status_id',RequestBookingStatus::COMPLETE)
                                                    ->where('requested_customer.entity_type_id',EntityTypes::SHOP)
                                                    // ->whereIn('requested_customer.show_in_home',[1,2])
                                                    ->orderBy('requested_customer.id','desc')
                                                    ->get();

                $completedHospital = RequestedCustomer::join('requested_customer as a', function($query) use ($user) {
                                                        $query->on('a.id','=','requested_customer.id')
                                                        ->whereRaw('a.id IN (select MAX(a2.id) from requested_customer as a2 where a2.request_booking_status_id = '.RequestBookingStatus::COMPLETE.' and a2.entity_type_id = '.EntityTypes::HOSPITAL.' and a2.user_id = '.$user->id.' group by a2.entity_id)');
                                                    })
                                                    ->where('requested_customer.user_id',$user->id)
                                                    ->where('requested_customer.request_booking_status_id',RequestBookingStatus::COMPLETE)
                                                    ->where('requested_customer.entity_type_id',EntityTypes::HOSPITAL)
                                                    // ->whereIn('requested_customer.show_in_home',[1,2])
                                                    ->orderBy('requested_customer.id','desc')
                                                    ->get();

                $completedShopData = $completedHospitalData = [];
                
                foreach($completedShop as $shop) {
                    if($shop->review_done == 0){
                        $data = Shop::find($shop->entity_id);
                        $shop['speciality_of'] =$data->speciality_of;
                        $shop['address'] =$data->address;
                        $completedShopData[] = $shop;
                    }
                }

                foreach($completedHospital as $hospital) {
                    if($hospital->review_done == 0){
                        $data = Post::find($hospital->entity_id);
                        $hospitalData = Hospital::with(['address' => function($query) {
                            $query->where('entity_type_id', EntityTypes::HOSPITAL);
                        }])->find($data->hospital_id);
                        $hospital['sub_title'] =$data->sub_title;
                        $hospital['address'] =$hospitalData->address;
                        $completedHospitalData[] = $hospital;
                    }
                }
                $data = [
                    'completed_shop' => $completedShopData,
                    'completed_hospital' => $completedHospitalData,
                ];
                Log::info('End code for the get completed service');
                return $this->sendSuccessResponse(Lang::get('messages.request-service.change-date-success'), 200,$data);
            }else{
                Log::info('End code for get completed service');
                return $this->sendSuccessResponse(Lang::get('messages.user.token_expired'), 401);
            }   
        } catch (\Exception $e) {
            Log::info('Exception in get completed service');
            Log::info($e);
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function creditDeduct(Request $request)
    {        
        try {
            Log::info('Start code for credit deduct');   
            $inputs = $request->all();
            $user = Auth::user();
            if($user){
                DB::beginTransaction();
                $validation = $this->requestServiceValidator->validateCreditDeduct($inputs);
                if ($validation->fails()) {
                    Log::info('End code for credit deduct');
                    return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
                }
                if($inputs['entity_type_id'] == EntityTypes::SHOP) {
                    $shop = Shop::find($inputs['entity_id']);
                    $user_id = $shop->user_id;
                    $user_detail = UserDetail::where('user_id',$user_id)->first();
                    $creditPlans = CreditPlans::where('entity_type_id', EntityTypes::SHOP)->where('package_plan_id',$user_detail->package_plan_id)->first();
                    $booked_user_id = $shop->user_id == $inputs['from_user_id'] ? $inputs['to_user_id'] : $inputs['from_user_id'];
                    $event_name = $shop->shop_name;
                }else {
                    $post = Post::find($inputs['entity_id']);
                    $hospital = Hospital::find($post->hospital_id);
                    $userRelation = UserEntityRelation::where('entity_type_id',EntityTypes::HOSPITAL)->where('entity_id',$hospital->id)->first();
                    $user_id = $userRelation->user_id;
                    $user_detail = UserDetail::where('user_id',$user_id)->first();
                    $creditPlans = CreditPlans::where('entity_type_id', EntityTypes::HOSPITAL)->where('package_plan_id',$user_detail->package_plan_id)->first();
                    $booked_user_id = $hospital->user_id == $inputs['from_user_id'] ? $inputs['to_user_id'] : $inputs['from_user_id'];
                    $event_name = $post->title;
                }
                $config = Config::where('key',Config::DEDUCT_AGAIN_AFTER_FIRST_DEDUCT)->first();
                $no_of_week = $config ? (int) filter_var($config->value, FILTER_SANITIZE_NUMBER_INT) : 4;
                $credit_history = UserCreditHistory::where('user_id',$user_id)
                                                    ->where('booked_user_id',$booked_user_id)
                                                    ->where('transaction','debit')
                                                    ->whereDate('created_at','>=', Carbon::now()->subWeeks($no_of_week))->count();
                if($credit_history == 0) {
                    $userCredits = UserCredit::where('user_id',$user_id)->first();   
                    $old_credit = $userCredits->credits;
                    
                    $new_credit = $creditPlans ? $creditPlans->deduct_rate : 0;
                    $total_credit = $old_credit - $new_credit;
                    if($new_credit && $new_credit > 0) {
                        $userCredits = UserCredit::where('user_id',$user_id)->update(['credits' => $total_credit]); 
                        UserCreditHistory::create([
                            'booked_user_id' => $booked_user_id,
                            'user_id' => $user_id,
                            'amount' => $new_credit,
                            'total_amount' => $total_credit,
                            'transaction' => 'debit',
                            'type' => UserCreditHistory::CHATING
                        ]);

                        $devices = UserDevices::whereIn('user_id', [$user_id])->pluck('device_token')->toArray();
                        $user_detail = UserDetail::where('user_id', $user_id)->first();
                        $language_id = $user_detail->language_id;
                        $title_msg = '';
                        $key = Notice::INQUIRY_COIN_DEDUCT.'_'.$language_id;
                        $format = __("notice.$key");
                        $notify_type = Notice::INQUIRY_COIN_DEDUCT;
                        $notice = Notice::create([
                            'notify_type' => Notice::INQUIRY_COIN_DEDUCT,
                            'user_id' => $user->id,
                            'to_user_id' => $user_id,
                            'title' => $event_name,
                            'sub_title' => number_format((float)$new_credit),
                        ]);  

                        $notificationData = [
                            'id' => $user_id,
                            'credits' => number_format((float)$new_credit),
                        ];

                        if (count($devices) > 0) {
                            $result = $this->sentPushNotification($devices,$title_msg, $format, $notificationData ,$notify_type, $user_id);                        
                        }
                    }
                    
                }
                DB::commit();
                Log::info('End code for credit deduct');
                return $this->sendSuccessResponse(Lang::get('messages.request-service.credit-deduct'), 200,[]);
                
            }else{
                Log::info('End code for credit deduct');
                return $this->sendSuccessResponse(Lang::get('messages.user.token_expired'), 401);
            }   
        } catch (\Exception $e) {
            Log::info('Exception in credit deduct');
            Log::info($e);
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }
}
