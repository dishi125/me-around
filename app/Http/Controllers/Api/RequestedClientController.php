<?php

namespace App\Http\Controllers\Api;

use App\Models\RequestForm;
use App\Models\EntityTypes;
use App\Models\Status;
use App\Models\RequestFormStatus;
use App\Models\Manager;
use App\Models\UserDetail;
use App\Models\UserDevices;
use App\Models\PackagePlan;
use App\Models\Hospital;
use App\Models\Config;
use App\Models\Shop;
use App\Models\City;
use App\Models\UserEntityRelation;
use App\Models\ManagerActivityLogs;
use App\Models\User;
use App\Models\UserCredit;
use App\Models\UserCreditHistory;
use App\Models\Address;
use App\Models\Notice;
use App\Models\CategoryTypes;
use App\Models\Category;
use App\Models\ShopPost;
use App\Models\ShopPrices;
use App\Validators\RequestFormValidator;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use App\Mail\CommonMail;
use App\Util\Firebase;
use Validator;
use Illuminate\Support\Str;

class RequestedClientController extends Controller
{
    private $requestFormValidator;
    protected $firebase;
    function __construct()
    {
        $this->requestFormValidator = new RequestFormValidator();
        $this->firebase = new Firebase();
    }

   
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        try {
            Log::info('Start code for add form request');
            DB::beginTransaction();
            $inputs = $request->all();

            $user = Auth::user();
            $inputs['user_id'] = $user->id;
            
            $validation = $this->requestFormValidator->validateStore($inputs);
            
            if ($validation->fails()) {
                Log::info('End code for add form request');
                return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
            }
            
            $city_id = $inputs['city_id'] ?? '';
            if(isset($inputs['country_id']) && isset($inputs['state_id'])){
                $location = $this->addCurrentLocation($inputs['country_id'], $inputs['state_id'], $city_id);
            }
            $main_country = getCountryFromLatLong($inputs['latitude'],$inputs['longitude']);
            $recommend_code = $request->has('recommend_code') ? $inputs['recommend_code'] : '';
            $language_id = $request->has('language_id') ? $inputs['language_id'] : 4;
            $manager = Manager::where('recommended_code',$recommend_code)->first();
            $manager_id = $manager ? $manager->id : 0;
            $request_status_id = $manager ? RequestFormStatus::CONFIRM : RequestFormStatus::PENDING;

            if(!empty($recommend_code) && empty($manager)){
                $key = "messages.language_$language_id.supported_code_expired";
                return $this->sendFailedResponse(Lang::get($key), 422);
            }

            $requestData = [
                'user_id' => $user->id,
                'entity_type_id' => $inputs['entity_type_id'],
                'category_id' => $inputs['category_id'] ?? null,
                'name' => $inputs['name'],
                'address' => $inputs['address'],
                'address2' => $request->has('address_detail') ? $inputs['address_detail'] : NULL,
                'country_id' => (isset($inputs['country_id']) && isset($inputs['state_id'])) ? $location['country']->id : null,
                'city_id' => (isset($inputs['city_id']) ) ? $location['city']->id : null,
                'latitude' => $inputs['latitude'],
                'longitude' => $inputs['longitude'],
                'main_country' => $main_country,
                'business_license_number' => $inputs['business_license_number'] ?? '',
                'request_status_id' => $request_status_id,
                'email' => $inputs['email'] ?? '',
                'request_count' => DB::raw('request_count + 1'),
                'manager_id' => $manager_id,
                // 'recommend_code' => $request->has('recommend_code') ? $inputs['recommend_code'] : ''
            ];

            $path = config('constant.requested-client');  
            
            if (!Storage::exists($path)) {
                Storage::makeDirectory($path);
            }
            if ($request->hasFile('business_licence')) {
                $business_licence = Storage::disk('s3')->putFile($path, $request->file('business_licence'),'public');
                $fileName = basename($business_licence);
                $requestData['business_licence'] = $path . '/' . $fileName;
            }
            
            if ($inputs['entity_type_id'] == EntityTypes::HOSPITAL) {
                if ($request->hasFile('interior_photo')) {
                    $interior_photo = Storage::disk('s3')->putFile($path, $request->file('interior_photo'),'public');
                    $fileName = basename($interior_photo);
                    $requestData['interior_photo'] = $path . '/' . $fileName;
                }
            }
            

            if ($inputs['entity_type_id'] == EntityTypes::SHOP) {
                if ($request->hasFile('identification_card')) {
                    $identification_card = Storage::disk('s3')->putFile($path, $request->file('identification_card'),'public');
                    $fileName = basename($identification_card);
                    $requestData['identification_card'] = $path . '/' . $fileName;
                }

                if ($request->hasFile('best_portfolio')) {
                    $best_portfolio = Storage::disk('s3')->putFile($path, $request->file('best_portfolio'),'public');
                    $fileName = basename($best_portfolio);
                    $requestData['best_portfolio'] = $path . '/' . $fileName;
                }
            }
            if($manager) {
                $this->approveRequest($requestData,$user);
            }
            // $request_form = RequestForm::updateOrCreate(['user_id' => $user->id,
            // 'entity_type_id' => $inputs['entity_type_id']],$requestData);
            $request_form = RequestForm::create($requestData);

            $config = Config::where('key',Config::REQUEST_CLIENT_REPORT_SNS_REWARD_EMAIL)->first();
            if($config) {
                $userData = [];
                $userData['email_body'] = "<p><b>Business Name: </b>".$request_form->name."</p>";
                $userData['email_body'] .= "<p><b>Type of Business: </b>".$request_form->category_name."</p>";
                $userData['email_body'] .= "<p><b>Address: </b>".$request_form->address."</p>";
                $userData['email_body'] .= "<p><b>City: </b>".$request_form->city_name."</p>";
                $userData['email_body'] .= "<p><b>Phone Number: </b>".$request_form->mobile."</p>";
                $userData['email_body'] .= "<p><b>Email: </b>".$request_form->email."</p>";
                $userData['email_body'] .= "<p><b>Business Licence Number: </b>".$request_form->business_license_number."</p>";
                $userData['title'] = 'Requested Client';
                $userData['subject'] = 'Requested Client';
                $userData['username'] = 'Admin';
                if($config->value) {
                    Mail::to($config->value)->send(new CommonMail($userData));
                }
            }
            DB::commit();

            $user_hospital_count = UserEntityRelation::where('entity_type_id', EntityTypes::HOSPITAL)->where('user_id',$user->id)->count();
            $user_shop_count = UserEntityRelation::where('entity_type_id', EntityTypes::SHOP)->where('user_id',$user->id)->count();

            Log::info('End code for add form request');
            return $this->sendSuccessResponse(Lang::get('messages.form-request.success'), 200, compact('request_form','user_hospital_count','user_shop_count'));
        } catch (\Exception $ex) {
            DB::rollBack();
            Log::info('Exception in the add form request');
            Log::info($ex);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function approveRequest($data,$user)
    {
        $dt = Carbon::now();
        $category = Category::find($data['category_id']);

        $plan = $category && $category->category_type_id == CategoryTypes::CUSTOM ? PackagePlan::PLATINIUM : PackagePlan::BRONZE;
        $userDetail = UserDetail::where('user_id',$user->id)->update(['package_plan_id' => $plan,'manager_id' => $data['manager_id'],'plan_expire_date' => Carbon::now()->addDays(30)]);
        $userLangDetail = UserDetail::where('user_id',$user->id)->first();
        if($data['entity_type_id'] == EntityTypes::HOSPITAL) {
            $hospital = Hospital::create([
                'email' => $data['email'] ?? NULL,
                'mobile' => $user->mobile,
                'main_name' => $data['name'] ?? NULL,
                'business_licence' => $data['business_licence'] ?? NULL,
                'interior_photo' => $data['interior_photo'] ?? NULL,
                'business_license_number' => $data['business_license_number'] ?? NULL,
                'status_id' => Status::ACTIVE,
                'category_id' => $data['category_id'] ?? null,
                'manager_id' => $data['manager_id'] ?? NULL,
                'credit_deduct_date' => $dt->toDateString()
            ]); 
            $entity_id = $hospital->id;
            $config = Config::where('key', Config::BECAME_HOSPITAL)->first();
        }else{
               
            $shop = Shop::create([
                'email' => $data['email'] ?? NULL,
                'mobile' => $user->mobile,
                'shop_name' => $data['name'] ?? NULL,
                'best_portfolio' => $data['best_portfolio'] ?? NULL,
                'business_licence' => $data['business_licence'] ?? NULL,
                'identification_card' => $data['identification_card'] ?? NULL,
                'business_license_number' => $data['business_license_number'] ?? NULL,
                'status_id' => Status::ACTIVE,
                'category_id' => $data['category_id'] ?? NULL,
                'user_id' => $data['user_id'],
                'manager_id' => $data['manager_id'] ?? NULL,
                'uuid' => (string) Str::uuid(),
                'credit_deduct_date' => $dt->toDateString()
            ]);
            $entity_id = $shop->id;
            $config = Config::where('key', Config::BECAME_SHOP)->first();
            syncGlobalPriceSettings($entity_id,$userLangDetail->language_id ?? 4);
        }
        $this->updateUserChatStatus();
        
        UserEntityRelation::create([
            'user_id' => $data['user_id'],
            'entity_type_id' => $data['entity_type_id'],
            'entity_id' => $entity_id,
        ]);

        $city = City::where('id', $data['city_id'])->first();
        if ($city) {
            $address = Address::create([
                'entity_type_id' => $data['entity_type_id'],
                'entity_id' => $entity_id,
                'address' => $data['address'],
                'latitude' => $data['latitude'],
                'longitude' => $data['longitude'],
                'country_id' => $data['country_id'],
                'main_country' => $data['main_country'],
                'state_id' => $city->state_id,
                'city_id' => $data['city_id'],
                'main_address' => Status::ACTIVE,
            ]);
        }

        
        $defaultCredit = $config ? (int) filter_var($config->value, FILTER_SANITIZE_NUMBER_INT) : 150000;
        $credit = UserCredit::updateOrCreate([
            'user_id' => $data['user_id'],                        
            'credits' => DB::raw("credits + $defaultCredit")
        ]);

        $creditHistory = UserCreditHistory::create([
            'user_id' => $data['user_id'],                        
            'amount' => $defaultCredit,
            'total_amount' => $defaultCredit,
            'transaction' => 'credit',            
            'type' => UserCreditHistory::DEFAULT
        ]);

        $notice = Notice::create([
            'notify_type' => Notice::BECAME_BUSINESS_USER,
            'user_id' => $data['user_id'],
            'to_user_id' => $data['user_id'],
            'entity_type_id' => $data['entity_type_id'],
            'entity_id' => $entity_id,
            'is_aninomity' => 0
        ]);

        $supporterNotice = Notice::create([
            'notify_type' => Notice::ADDED_AS_CLIENT,
            'user_id' => $data['user_id'],
            'to_user_id' => $data['manager_id'],
            'entity_type_id' => $data['entity_type_id'],
            'entity_id' => $entity_id,
            'is_aninomity' => 0
        ]);

        // Send push notification to supporter
        $devices = UserDevices::where('user_id', $data['manager_id'])->pluck('device_token')->toArray();
        $language_id = 4;
        $key = Notice::ADDED_AS_CLIENT.'_'.$language_id;
        $format =  __("notice.$key",['username' => $supporterNotice->user_name]);

        $title_msg = '';
        $notify_type = Notice::ADDED_AS_CLIENT;
        $notificationData = $data;

        if (count($devices) > 0) {
            $result = $this->sentPushNotification($devices,$title_msg, $format, $notificationData ,$notify_type, $entity_id);                        
        }

        if($data['entity_type_id'] == EntityTypes::SHOP && $category && $category->category_type_id != CategoryTypes::CUSTOM) {
            $config1 = Config::where('key', Config::SHOP_PROFILE_ADD_PRICE)->first();
            $defaultCredit1 = $config1 ? (int) filter_var($config1->value, FILTER_SANITIZE_NUMBER_INT) : 0;
            $userCredits = UserCredit::where('user_id',$data['user_id'])->first();   
            $old_credit = $userCredits->credits;
            $total_credit = $old_credit - $defaultCredit1;
            if($defaultCredit1 && $defaultCredit1 > 0) {
                $userCredits = UserCredit::where('user_id',$data['user_id'])->update(['credits' => $total_credit]); 
                UserCreditHistory::create([
                    'user_id' => $data['user_id'],
                    'amount' => $defaultCredit1,
                    'total_amount' => $total_credit,
                    'transaction' => 'debit',
                    'type' => UserCreditHistory::REGULAR
                ]);
            }
        }
    }

    public function getRequestedClient(Request $request)
    {
        try {
            DB::beginTransaction();
            $inputs = $request->all();

            $user = Auth::user();
            $search = $inputs['search'] ?? NULL;

            $query = RequestForm::leftjoin('category','category.id','request_forms.category_id')
            ->leftjoin('cities','cities.id','request_forms.city_id')
            ->whereIn('entity_type_id', [EntityTypes::HOSPITAL,EntityTypes::SHOP])
            ->where('request_status_id', RequestFormStatus::PENDING)
            ->select('request_forms.*','category.name as category_name','cities.name as city_name');

            if(!empty($search)){
                $query = $query->where(function($q) use ($search){
                    $q->where('request_forms.name', 'LIKE', "%{$search}%")
                    ->orWhere('category.name', 'LIKE', "%{$search}%")
                    ->orWhere('request_forms.email', 'LIKE', "%{$search}%")
                    ->orWhere('request_forms.address', 'LIKE', "%{$search}%")
                    ->orWhere('cities.name', 'LIKE', "%{$search}%");
                });
            }

            $request_client = $query->paginate(config('constant.pagination_count'),"*","requested_client_page");
            return $this->sendSuccessResponse(Lang::get('messages.form-request.get-success'), 200, compact('request_client'));
        } catch (\Exception $ex) {
            DB::rollBack();
            Log::info($ex);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function requestedClientResponse(Request $request)
    {
        try {
            DB::beginTransaction();
            $inputs = $request->all();
            $user = Auth::user();

            $validator = Validator::make($inputs, [
                'response' => 'required',
                'id' => 'required',
            ], [], [
                'response' => 'response',
                'id' => 'ID',
            ]);
            if ($validator->fails()) {
                return $this->sendCustomErrorMessage($validator->errors()->toArray(), 422);
            }
            $response = $inputs['response']; // confirm / reject
            $id = $inputs['id'];

            if($id){

                $requestForm = RequestForm::find($id);
                $entityType = $requestForm->entity_type_name;
                $entityTypeID = $requestForm->entity_type_id;
                $request_id = $requestForm->id;
                $city_id = $requestForm->city_id;

                if($response && $response == 'confirm'){
                    // confirm
                    if ($entityTypeID == EntityTypes::HOSPITAL) {
                        $dt = Carbon::now();
                    // Hospital
                        $user = User::find($requestForm->user_id);
                        $userDetail = UserDetail::where('user_id',$requestForm->user_id)->update(['package_plan_id' => PackagePlan::BRONZE,'plan_expire_date' => Carbon::now()->addDays(30)]);
                        $hospital = Hospital::create([
                            'email' => $requestForm->email,
                            'mobile' => $user->mobile,
                            'main_name' => $requestForm->name,
                            'business_licence' => $requestForm->business_licence,
                            'interior_photo' => $requestForm->interior_photo,
                            'business_license_number' => $requestForm->business_license_number,
                            'status_id' => Status::ACTIVE,
                            'category_id' => $requestForm->category_id,
                            'credit_deduct_date' => $dt->toDateString()
                        ]);                   

                    // UserEntityType
                        UserEntityRelation::create([
                            'user_id' => $requestForm->user_id,
                            'entity_type_id' => EntityTypes::HOSPITAL,
                            'entity_id' => $hospital->id
                        ]);

                    // Hospital Address
                        $city = City::where('id', $city_id)->first();
                        if ($city) {
                            $address = Address::create([
                                'entity_type_id' => EntityTypes::HOSPITAL,
                                'entity_id' => $hospital->id,
                                'address' => $requestForm->address,
                                'latitude' => $requestForm->latitude,
                                'longitude' => $requestForm->longitude,
                                'country_id' => $requestForm->country_id,
                                'main_country' => $requestForm->main_country,
                                'state_id' => $city->state_id,
                                'city_id' => $city_id,
                                'main_address' => Status::ACTIVE,
                            ]);
                        }

                    // Hospital Credit - 1500
                        $config = Config::where('key', Config::BECAME_HOSPITAL)->first();
                        $defaultCredit = $config ? (int) filter_var($config->value, FILTER_SANITIZE_NUMBER_INT) : 150000;
                        $credit = UserCredit::updateOrCreate([
                            'user_id' => $requestForm->user_id,                        
                            'credits' => DB::raw("credits + $defaultCredit") 
                        ]);

                        $creditHistory = UserCreditHistory::create([
                            'user_id' => $requestForm->user_id,                        
                            'amount' => $defaultCredit,
                            'total_amount' => $defaultCredit,
                            'transaction' => 'credit',
                            'type' => UserCreditHistory::DEFAULT
                        ]);

                        $entity_id = $hospital->id;

                    } else if ($entityTypeID == EntityTypes::SHOP) {

                    // Shop
                        $category = Category::find($requestForm->category_id);

                        $plan = $category && $category->category_type_id == CategoryTypes::CUSTOM ? PackagePlan::PLATINIUM : PackagePlan::BRONZE;
                        $dt = Carbon::now();                    
                        $user = User::find($requestForm->user_id);
                        $userDetail = UserDetail::where('user_id',$requestForm->user_id)->update(['package_plan_id' => $plan,'plan_expire_date' => Carbon::now()->addDays(30)]);
                        $userLangDetail = UserDetail::where('user_id',$requestForm->user_id)->first();
                        $shop = Shop::create([
                            'email' => $requestForm->email,
                            'mobile' => $user->mobile,
                            'shop_name' => $requestForm->name,
                            'best_portfolio' => $requestForm->best_portfolio,
                            'business_licence' => $requestForm->business_licence,
                            'identification_card' => $requestForm->identification_card,
                            'business_license_number' => $requestForm->business_license_number,
                            'status_id' => Status::ACTIVE,
                            'category_id' => $requestForm->category_id,
                            'user_id' => $requestForm->user_id,
                            'uuid' => (string) Str::uuid(),
                            'credit_deduct_date' => $dt->toDateString()
                        ]);

                        syncGlobalPriceSettings($shop->id,$userLangDetail->language_id ?? 4);
                        UserEntityRelation::create([
                            'user_id' => $requestForm->user_id,
                            'entity_type_id' => EntityTypes::SHOP,
                            'entity_id' => $shop->id
                        ]);

                    // Shop Address
                        $city = City::where('id', $city_id)->first();
                        if ($city) {
                            $address = Address::create([
                                'entity_type_id' => EntityTypes::SHOP,
                                'entity_id' => $shop->id,
                                'address' => $requestForm->address,
                                'latitude' => $requestForm->latitude,
                                'longitude' => $requestForm->longitude,
                                'country_id' => $requestForm->country_id,
                                'main_country' => $requestForm->main_country,
                                'state_id' => $city->state_id,
                                'city_id' => $city_id,
                                'main_address' => Status::ACTIVE,
                            ]);
                        }

                    // Shop Credit - 1500
                        $config = Config::where('key', Config::BECAME_SHOP)->first();
                        $defaultCredit = $config ? (int) filter_var($config->value, FILTER_SANITIZE_NUMBER_INT) : 150000;
                        $credit = UserCredit::updateOrcreate([
                            'user_id' => $requestForm->user_id,                        
                            'credits' => DB::raw("credits + $defaultCredit") 
                        ]);

                        $creditHistory = UserCreditHistory::create([
                            'user_id' => $requestForm->user_id,                        
                            'amount' => $defaultCredit,
                            'total_amount' => $defaultCredit,
                            'transaction' => 'credit',
                            'type' => UserCreditHistory::DEFAULT
                        ]);

                        $currentShop = $this->checkShopStatus($shop->id);
                        $entity_id = $shop->id;
                    }

                    $config = Config::where('key', Config::SHOP_PROFILE_ADD_PRICE)->first();
                    $defaultCredit = $config ? (int) filter_var($config->value, FILTER_SANITIZE_NUMBER_INT) : 0;
                    $userCredits = UserCredit::where('user_id',$requestForm->user_id)->first();   
                    $old_credit = $userCredits->credits;
                    $total_credit = $old_credit - $defaultCredit;
                    if($defaultCredit && $defaultCredit > 0 && $category && $category->category_type_id != CategoryTypes::CUSTOM) {
                        $userCredits = UserCredit::where('user_id',$requestForm->user_id)->update(['credits' => $total_credit]); 
                        UserCreditHistory::create([
                            'user_id' => $requestForm->user_id,
                            'amount' => $defaultCredit,
                            'total_amount' => $total_credit,
                            'transaction' => 'debit',
                            'type' => UserCreditHistory::DEFAULT
                        ]);
                    }

                    $notice = Notice::create([
                        'notify_type' => Notice::BECAME_BUSINESS_USER,
                        'user_id' => $requestForm->user_id,
                        'to_user_id' => $requestForm->user_id,
                        'entity_type_id' => $entityTypeID,
                        'entity_id' => $entity_id,
                    ]);

                // Update Request Form Status
                    RequestForm::where('id', $request_id)->update(['request_status_id' => RequestFormStatus::CONFIRM]);
                    $user = User::where('email',$requestForm->email)->first();
                    if($user) {
                        $user->email_body = "Your request for ". $requestForm->name ." has been confirmed.";
                        $user->title = 'Request Confirmed';
                        $user->subject = 'Request Confirmed';
                    // Mail::to($user->email)->send(new CommonMail($user));
                    }

                    $logData = [
                        'activity_type' => ManagerActivityLogs::BUSINESS_REQUEST_CONFIRM,
                        'user_id' => auth()->user()->id,
                        'value' => Lang::get('messages.manager_activity.request_confirm'),
                        'entity_id' => $requestForm->user_id,
                    ];
                    DB::commit();
                    $this->addManagerActivityLogs($logData);
                    return $this->sendSuccessResponse(Lang::get('messages.form-request.request-confirm'), 200);

                }else{
                    // reject

                    // Update Request Form Status
                    RequestForm::where('id', $request_id)->update(['request_status_id' => RequestFormStatus::REJECT]);
                    $user = User::find($requestForm->user_id)->first();
                    if($user) {
                        $user->email_body = "Your request for ". $requestForm->name ." has been rejected.";
                        $user->title = 'Request Rejected';
                        $user->subject = 'Request Rejected';
                    // Mail::to($user->email)->send(new CommonMail($user));
                    }

                    $logData = [
                        'activity_type' => ManagerActivityLogs::BUSINESS_REQUEST_REJECT,
                        'user_id' => auth()->user()->id,
                        'value' => Lang::get('messages.manager_activity.request_reject'),
                        'entity_id' => $requestForm->user_id,
                    ];
                    $this->addManagerActivityLogs($logData);
                    DB::commit();
                    return $this->sendSuccessResponse(Lang::get('messages.form-request.request-rejected'), 200);
                }

            }
            
        } catch (\Exception $ex) {
            DB::rollBack();
            Log::info($ex);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function checkShopStatus($id) {

        $currentShop = Shop::where('id',$id)->first();

        $shopPrices = 1; 
        /* ShopPrices::join('shop_price_category','shop_price_category.id','shop_prices.shop_price_category_id')
        ->where('shop_price_category.shop_id',$id)->count(); */

        $shopPosts = ShopPost::where('shop_id',$id)->count();
        
        $isShopPost = $shopPosts >= 3  ? true : false;
        $isThumbnail = !empty($currentShop->thumbnail_image) > 0 ? true : false;
        $isWokplace = count($currentShop->workplace_images) > 0 ? true : false;
        $isMainProfile = count($currentShop->main_profile_images) > 0 ? true : false;
        $isAddress = $currentShop->address->address != NULL ? true : false;
        $isShopPrices = $shopPrices > 0 ? true : false;
        $isMainName = $currentShop->main_name != NULL ? true : false;
        $isShopName = $currentShop->shop_name != NULL ? true : false;
        $isSpecialityOf = $currentShop->speciality_of != NULL ? true : false;

        if($isShopPost && $isThumbnail && $isWokplace && $isMainProfile && $isAddress && $isMainName && $isShopName && $isSpecialityOf){
            Shop::where('id',$id)->update(['status_id' => Status::ACTIVE,'deactivate_by_user' => 0]);
        }else {
            Shop::where('id',$id)->update(['status_id' => Status::PENDING]);
        }
        return true;
    }






}
