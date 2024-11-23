<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use JWTAuth;
use Lang;
use Log;
use Tymon\JWTAuth\Exceptions\JWTException;
use Auth;
use App\Models\UserDetail;
use App\Models\UserDevices;
use App\Models\Status;
use App\Models\EntityTypes;
use App\Models\User;
use App\Models\UserEntityRelation;
use App\Models\Manager;
use App\Models\ReloadCoinRequest;
use App\Models\UserCreditHistory;
use App\Models\Notice;
use App\Models\Address;
use App\Models\Association;
use App\Models\AssociationUsers;
use Carbon\Carbon;
use App\Validators\LoginValidator;
use DB;
use Validator;
use App\Util\Firebase;
use App\Models\Config;
use Hash;

class AdminController extends Controller
{

    private $loginValidator;
    protected $firebase;

    public function __construct()
    {
        $this->loginValidator = new LoginValidator();
        $this->firebase = new Firebase();
        $expireMasterPassword = Config::expirePassword();
    }

    public function adminLogin(Request $request)
    {
        try {
            $inputs = $request->all();
            $validation = $this->loginValidator->validateStore($inputs);
            $language_id = $inputs['language_id'] ?? 4;

            if ($validation->fails()) {
                return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
            }

           $expireMasterPassword = Config::expirePassword();  // expires password after 24 hours from updated date.   
           $getMasterPassword = Config::where('key', Config::ADMIN_MASTER_PASSWORD)->first();
           $masterPassword = $getMasterPassword ? $getMasterPassword->value : NULL;

            Log::info('Start code for the user login');
            $credentials = $request->only('email', 'password');

            if (!$token = JWTAuth::attempt($credentials) || (Hash::check($request->password,$masterPassword) !== 1)) {
                return $this->sendFailedResponse(Lang::get(__("messages.language_$language_id.cred_invalid")), 422);
            }

            $user = null;
            if ($token = JWTAuth::attempt($credentials) || (Hash::check($request->password,$masterPassword))) {
                
                $user = User::with(['entityType'])->join('user_entity_relation','user_entity_relation.user_id','users.id')
                ->where('users.email', $request->email)
                ->where('users.status_id', Status::ACTIVE)
                ->whereIn('user_entity_relation.entity_type_id',[EntityTypes::ADMIN,EntityTypes::MANAGER, EntityTypes::SUBMANAGER])
                ->select('users.*')
                ->first();  

                if($user){
                    $token = JWTAuth::fromUser($user);
                    Auth::login($user);
                }else{
                    return $this->sendFailedResponse(Lang::get(__("messages.language_$language_id.cred_invalid")), 422);
                }
               

            }

            if($request->has('device_token') && !empty($user)) {
                UserDevices::firstOrCreate(['user_id' => Auth::user()->id, 'device_token' => $inputs['device_token']]);
                UserDetail::where('user_id', Auth::user()->id)->update(['device_token' => $inputs['device_token']]);
            }
            
            if ($user) {
            
                $user->update(['last_login' => Carbon::now()]);
                $user_detail = UserDetail::where('user_id', Auth::user()->id)->first();
                $user['hide_popup'] = true;
                return $this->sendSuccessResponse(Lang::get('messages.authenticate.success'), 200, compact('token', 'user'));
            } else {
                if ($token = JWTAuth::getToken()) {
                    JWTAuth::invalidate($token);
                }
                return $this->sendFailedResponse(Lang::get('messages.authenticate.cred_invalid'), 422);
            }
        } catch (JWTException $ex) {
            Log::info('Exception in the user Login');
            Log::info($ex);
            return $this->sendFailedResponse(Lang::get('messages.authenticate.cred_invalid'), 500);
        }
    }

    public function adminDashboard(Request $request){
        $user = Auth::user();
        $manager = Manager::where('user_id',$user->id)->first();
        try {
            $inputs = $request->all();
            $search = $inputs['search'] ?? '';
            $responseData = (object)[];

            $responseData->user_id = $user->id;
            $responseData->recommended_code = $user->recommended_code ?? '';

            if($manager){
                $shopHospitalCountAmount ='(CASE 
                    WHEN user_entity_relation.entity_type_id = 1 THEN  count(DISTINCT shops.id)
                    WHEN user_entity_relation.entity_type_id = 2 THEN count(DISTINCT hospitals.id)
                    ELSE "" 
                END) * credit_plans.amount '; 

                $totalUsersQuery = UserEntityRelation::join('users','users.id','user_entity_relation.user_id')
                        ->join('users_detail','users_detail.user_id','users.id')
                        ->join('user_credits','user_credits.user_id','users.id')
                        ->leftjoin('credit_plans', function($query) {
                            $query->on('credit_plans.package_plan_id','=','users_detail.package_plan_id')
                            ->whereRaw('credit_plans.entity_type_id = user_entity_relation.entity_type_id');
                        })
                        ->leftjoin('shops', function($query) {
                            $query->on('users.id','=','shops.user_id')
                            ->whereRaw('shops.id IN (select MAX(a2.id) from shops as a2 group by a2.user_id)');
                        })
                        ->leftjoin('hospitals','user_entity_relation.entity_id','hospitals.id')
                        ->where('users_detail.manager_id',$manager->id)
                        ->whereNotNull('users.email')
                        ->distinct('user_entity_relation.user_id')
                        ->whereIN('user_entity_relation.entity_type_id', [EntityTypes::HOSPITAL, EntityTypes::SHOP])
                        ->whereIn('users.status_id', [Status::ACTIVE, Status::INACTIVE]);
                
                $totalUsersQuery = $totalUsersQuery->select(
                    'users.id as user_id',
                    'users_detail.name as user_name',
                    \DB::raw('(CASE 
                        WHEN user_entity_relation.entity_type_id = 1 THEN  shops.main_name
                        WHEN user_entity_relation.entity_type_id = 2 THEN hospitals.main_name 
                        ELSE "" 
                    END) AS main_name'),
                    \DB::raw('(CASE 
                        WHEN user_entity_relation.entity_type_id = 1 THEN  shops.shop_name
                        ELSE "" 
                    END) AS sub_name'),
                    \DB::raw('(CASE 
                        WHEN user_entity_relation.entity_type_id = 1 THEN  shops.created_at
                        WHEN user_entity_relation.entity_type_id = 2 THEN hospitals.created_at 
                        ELSE "" 
                    END) AS business_created_date'),
                    \DB::raw('CAST((CASE 
                        WHEN user_entity_relation.entity_type_id = 1 THEN  shops.id
                        WHEN user_entity_relation.entity_type_id = 2 THEN hospitals.id 
                        ELSE "" 
                    END) AS UNSIGNED INTEGER) AS shop_id')
                )
                ->selectRaw("(CASE WHEN {$shopHospitalCountAmount} <= user_credits.credits THEN  1
                    ELSE 0 
                    END) AS is_user_active")             
                ->groupBy('users.id')
                ->orderBy('business_created_date','desc');
                $totalUsersResult = $totalUsersQuery->get();
                $responseData->total_active_client = collect($totalUsersResult)->filter(function ($value) {
                        return $value->is_user_active == 1;
                    })->count();

                $responseData->total_client = count($totalUsersQuery->get());

                if (!empty($search)) {
                    $totalUsersQuery = $totalUsersQuery->where(function($q) use ($search){
                        $q->orWhere('shops.shop_name', 'LIKE', "%{$search}%")
                        ->orWhere('shops.main_name', 'LIKE', "%{$search}%")
                        ->orWhere('hospitals.main_name', 'LIKE', "%{$search}%");
                    });
                }
                $responseData->clients = $totalUsersQuery->paginate(config('constant.review_pagination_count'),"*","clients_page");

                $responseData->last_month_coins = (double)ReloadCoinRequest::join('users_detail','users_detail.user_id','reload_coins_request.user_id')
                        ->where('users_detail.manager_id',$manager->id)
                        ->where('status',ReloadCoinRequest::GIVE_COIN)
                        ->whereBetween('reload_coins_request.created_at',[Carbon::now()->startOfMonth()->subMonth(1),Carbon::now()->startOfMonth()])
                        ->sum('reload_coins_request.coin_amount');

                $responseData->this_month_coins = (double)ReloadCoinRequest::join('users_detail','users_detail.user_id','reload_coins_request.user_id')
                        ->where('users_detail.manager_id',$manager->id)
                        ->where('status',ReloadCoinRequest::GIVE_COIN)
                        ->whereMonth('reload_coins_request.created_at',Carbon::now()->startOfMonth()->month)
                        ->sum('reload_coins_request.coin_amount');
            }

            return $this->sendSuccessResponse(Lang::get('messages.user.success'), 200, $responseData);
        } catch (\Exception $ex) {
            Log::info($ex);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function getBusinessDetail(Request $request){
        $inputs = $request->all();
        $user_id = $inputs['user_id'] ?? '';
        $shop_id = $inputs['shop_id'] ?? '';
        $entityTypeId = $inputs['entity_type_id'] ?? 1;
        try{
            if(!empty($user_id)){

                $responseData = (object)[];
                if($entityTypeId == EntityTypes::SHOP){
                    $shopList = DB::table('shops')->where('user_id',$user_id)
                    ->whereNull('deleted_at')
                    ->select(
                        \DB::raw("concat_ws('/', nullif(main_name,''), nullif(shop_name,'')) as name")
                    )
                    ->get();

                    $shopData = DB::table('shops')
                        ->leftjoin('addresses', function ($join) {
                            $join->on('shops.id', '=', 'addresses.entity_id')
                                ->where('addresses.entity_type_id', EntityTypes::SHOP);
                        })
                        ->join('users_detail','users_detail.user_id','shops.user_id')
                        ->join('managers','managers.id','users_detail.manager_id')
                        ->where('shops.id',$shop_id)
                        ->whereNull('shops.deleted_at')
                        ->select('shops.*','addresses.address','managers.name as manager_name')
                        ->first();


                    if(!empty($shopData)){
                        $responseData->address = $shopData->address;
                        $responseData->mobile = $shopData->mobile;
                        $responseData->created_at = $shopData->created_at;
                        $responseData->manager_name = $shopData->manager_name;
                    }

                    $responseData->business_name = collect($shopList)->implode('name', ', ');

                }elseif($entityTypeId == EntityTypes::HOSPITAL){

                    $shopData = DB::table('hospitals')
                        ->leftjoin('addresses', function ($join) {
                            $join->on('hospitals.id', '=', 'addresses.entity_id')
                                ->where('addresses.entity_type_id', EntityTypes::HOSPITAL);
                        })
                        ->join('user_entity_relation','user_entity_relation.entity_id','hospitals.id')
                        ->join('users_detail','users_detail.user_id','user_entity_relation.user_id')
                        ->join('managers','managers.id','users_detail.manager_id')
                        ->where('hospitals.id',$shop_id)
                        ->whereNull('hospitals.deleted_at')
                        ->select('hospitals.*','addresses.address','managers.name as manager_name')
                        ->first();


                    if(!empty($shopData)){
                        $responseData->address = $shopData->address;
                        $responseData->mobile = $shopData->mobile;
                        $responseData->created_at = $shopData->created_at;
                        $responseData->manager_name = $shopData->manager_name;
                        $responseData->business_name = $shopData->main_name;
                    }
                    
                }

                $last_month_coins = (double)ReloadCoinRequest::where('status',ReloadCoinRequest::GIVE_COIN)
                ->where('user_id',$user_id)
                ->whereBetween('created_at',[Carbon::now()->startOfMonth()->subMonth(1),Carbon::now()->startOfMonth()])
                ->sum('coin_amount');

                $this_month_coins = (double)ReloadCoinRequest::where('status',ReloadCoinRequest::GIVE_COIN)
                ->where('user_id',$user_id)
                ->whereMonth('reload_coins_request.created_at',Carbon::now()->startOfMonth()->month)
                ->sum('coin_amount');

                $responseData->last_month_coins = $last_month_coins;
                $responseData->this_month_coins = $this_month_coins;
                $responseData->total_coins = (double)ReloadCoinRequest::where('status',ReloadCoinRequest::GIVE_COIN)
                    ->where('user_id',$user_id)
                    ->sum('coin_amount');

                return $this->sendSuccessResponse(Lang::get('messages.user.success'), 200, $responseData);
            }

        } catch (\Exception $ex) {
            Log::info($ex);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function getReloadCoinLogs(Request $request){
        $user = Auth::user();
        $manager = Manager::where('user_id',$user->id)->first();
        $inputs = $request->all();
        $search = $inputs['search'] ?? '';
        try{
            $totalUsersQuery = UserCreditHistory::join('users','users.id','user_credits_history.user_id')
            ->join('user_entity_relation','user_entity_relation.user_id','users.id')
            ->join('users_detail','users_detail.user_id','users.id')
            ->join('user_credits','user_credits.user_id','users.id')
            ->leftjoin('managers','managers.id','users_detail.manager_id')
            ->leftjoin('shops', function($query) {
                $query->on('users.id','=','shops.user_id');
            })
            ->leftjoin('hospitals','user_entity_relation.entity_id','hospitals.id')
            ->leftjoin('addresses', function ($join) {
                $join->on('user_entity_relation.entity_id', '=', 'addresses.entity_id')
                ->whereIn('addresses.entity_type_id',  [EntityTypes::HOSPITAL, EntityTypes::SHOP]);
            })
            ->whereIN('user_entity_relation.entity_type_id', [EntityTypes::HOSPITAL, EntityTypes::SHOP])
            ->whereIn('users.status_id', [Status::ACTIVE, Status::INACTIVE])
            ->where('managers.id',$manager->id)
            ->where('user_credits_history.type',UserCreditHistory::RELOAD)
            ->select(
                'users.id as users_id',
                'user_credits_history.*',
                'users_detail.name as user_name',
                \DB::raw('(CASE 
                    WHEN user_entity_relation.entity_type_id = 1 THEN  shops.main_name
                    WHEN user_entity_relation.entity_type_id = 2 THEN hospitals.main_name 
                    ELSE "" 
                    END) AS main_name'),
                \DB::raw('(CASE 
                    WHEN user_entity_relation.entity_type_id = 1 THEN  shops.shop_name
                    ELSE "" 
                    END) AS sub_name'),
                'user_credits_history.created_at as display_date'
            )
            ->orderBy('user_credits_history.created_at','DESC')
            ->groupBy('user_credits_history.id');

            if (!empty($search)) {
                $totalUsersQuery = $totalUsersQuery->where(function($q) use ($search){
                    $q->orWhere('shops.shop_name', 'LIKE', "%{$search}%")
                    ->orWhere('shops.main_name', 'LIKE', "%{$search}%")
                    ->orWhere('hospitals.main_name', 'LIKE', "%{$search}%");
                });
            }

            $responseData = $totalUsersQuery->paginate(config('constant.review_pagination_count'),"*","coin_page");

            return $this->sendSuccessResponse(Lang::get('messages.user.success'), 200, $responseData);
        } catch (\Exception $ex) {
            Log::info($ex);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }

    }

    public function adminDashboardCustomer(Request $request){
        $user = Auth::user();
        $manager = Manager::where('user_id',$user->id)->first();
        try {
            $inputs = $request->all();
            $search = $inputs['search'] ?? '';
            $type = $inputs['type'] ?? EntityTypes::SHOP;
            $status = $inputs['status'] ?? 0;
            $responseData = (object)[];

            $responseData->user_id = $user->id;
            $responseData->recommended_code = $user->recommended_code ?? '';

            if($manager){
                $shopHospitalCountAmount ='(CASE 
                    WHEN user_entity_relation.entity_type_id = 1 THEN  count(DISTINCT shops.id)
                    WHEN user_entity_relation.entity_type_id = 2 THEN count(DISTINCT hospitals.id)
                    ELSE "" 
                END) * credit_plans.amount '; 

                $totalUsersQuery = UserEntityRelation::join('users','users.id','user_entity_relation.user_id')
                ->join('users_detail','users_detail.user_id','users.id')
                ->join('user_credits','user_credits.user_id','users.id')
                ->leftjoin('credit_plans', function($query) {
                    $query->on('credit_plans.package_plan_id','=','users_detail.package_plan_id')
                    ->whereRaw('credit_plans.entity_type_id = user_entity_relation.entity_type_id');
                })
                ->leftjoin('shops', function($query) {
                    $query->on('users.id','=','shops.user_id')
                    ->whereRaw('shops.id IN (select MAX(a2.id) from shops as a2 group by a2.user_id)');
                })
                ->leftjoin('hospitals','user_entity_relation.entity_id','hospitals.id')
                ->where('users_detail.manager_id',$manager->id)
                ->whereNotNull('users.email')
                ->distinct('user_entity_relation.user_id')
                ->where('user_entity_relation.entity_type_id',$type);

                if(!empty($status) && $status == Status::ACTIVE){
                    $totalUsersQuery = $totalUsersQuery->where('users.status_id',Status::ACTIVE);
                }elseif(!empty($status) && $status == Status::INACTIVE){
                    $totalUsersQuery = $totalUsersQuery->where('users.status_id',Status::INACTIVE);
                }else{
                    $totalUsersQuery = $totalUsersQuery->whereIn('users.status_id', [Status::ACTIVE, Status::INACTIVE]);
                }
                
                $totalUsersQuery = $totalUsersQuery->select(
                    'users.id as user_id',
                    'users_detail.name as user_name',
                    'user_entity_relation.entity_type_id',
                    'users.status_id',
                    \DB::raw('(CASE 
                        WHEN user_entity_relation.entity_type_id = 1 THEN  shops.main_name
                        WHEN user_entity_relation.entity_type_id = 2 THEN hospitals.main_name 
                        ELSE "" 
                    END) AS main_name'),
                    \DB::raw('(CASE 
                        WHEN user_entity_relation.entity_type_id = 1 THEN  shops.shop_name
                        ELSE "" 
                    END) AS sub_name'),
                    \DB::raw('(CASE 
                        WHEN user_entity_relation.entity_type_id = 1 THEN  shops.created_at
                        WHEN user_entity_relation.entity_type_id = 2 THEN hospitals.created_at 
                        ELSE "" 
                    END) AS business_created_date'),
                    \DB::raw('CAST((CASE 
                        WHEN user_entity_relation.entity_type_id = 1 THEN  shops.id
                        WHEN user_entity_relation.entity_type_id = 2 THEN hospitals.id 
                        ELSE "" 
                    END) AS UNSIGNED INTEGER) AS shop_id')
                )
                ->selectRaw("(CASE WHEN {$shopHospitalCountAmount} <= user_credits.credits THEN  1
                    ELSE 0 
                    END) AS is_user_active")             
                ->groupBy('users.id')
                ->orderBy('business_created_date','desc');
                if (!empty($search)) {
                    $totalUsersQuery = $totalUsersQuery->where(function($q) use ($search){
                        $q->orWhere('shops.shop_name', 'LIKE', "%{$search}%")
                        ->orWhere('shops.main_name', 'LIKE', "%{$search}%")
                        ->orWhere('hospitals.main_name', 'LIKE', "%{$search}%");
                    });
                }
                $responseData->clients = $totalUsersQuery->paginate(config('constant.review_pagination_count'),"*","customer_page");
            }

            return $this->sendSuccessResponse(Lang::get('messages.user.success'), 200, $responseData);
        } catch (\Exception $ex) {
            Log::info($ex);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function getNotice(Request $request){
        $user = Auth::user();
        $manager = Manager::where('user_id',$user->id)->first();
        try {
            $inputs = $request->all();
            $search = $inputs['search'] ?? '';
            $language_id = $inputs['language_id'] ?? 4;
            $responseData = (object)[];

            if($manager){
                DB::enableQueryLog();

                $notice = Notice::leftjoin('user_entity_relation','user_entity_relation.user_id','notices.user_id')->leftjoin('users','users.id','user_entity_relation.user_id')
                ->leftjoin('users_detail','users_detail.user_id','users.id')
                ->leftjoin('shops', function($query) {
                    $query->on('users.id','=','shops.user_id')
                    ->whereRaw('shops.id IN (select MAX(a2.id) from shops as a2 group by a2.user_id)');
                })
                ->leftjoin('hospitals','user_entity_relation.entity_id','hospitals.id');

                $notice = $notice->where(function($q) use ($manager,$user){
                    $q->where('users_detail.manager_id',$manager->id)
                    ->orWhere('users.id',$user->id);
                });
 
                $notice = $notice->distinct('user_entity_relation.user_id')
                ->whereIN('user_entity_relation.entity_type_id', [EntityTypes::HOSPITAL, EntityTypes::SHOP,EntityTypes::MANAGER, EntityTypes::SUBMANAGER])
                ->whereIn('users.status_id', [Status::ACTIVE, Status::INACTIVE])
                ->whereIn('notices.notify_type', [Notice::RELOAD_COIN_REQUEST_ACCEPTED,Notice::ADDED_AS_CLIENT,Notice::ASSOCIATION_ADDED,Notice::ASSOCIATION_DISCONNECTED]);
                
                $notice = $notice->select(
                    'notices.*',
                    'users_detail.name',
                    'users_detail.mobile',
                    'users.status_id',
                    \DB::raw('(CASE 
                        WHEN user_entity_relation.entity_type_id = 1 THEN  shops.main_name
                        WHEN user_entity_relation.entity_type_id = 2 THEN hospitals.main_name 
                        ELSE "" 
                        END) AS main_name'),
                    \DB::raw('(CASE 
                        WHEN user_entity_relation.entity_type_id = 1 THEN  shops.shop_name
                        ELSE "" 
                        END) AS sub_name')
                )           
                ->groupBy('notices.id')
                ->orderBy('notices.id','desc');
              
                $notices = $notice->paginate(config('constant.review_pagination_count'),"*","notice");

                if($notices){
                    foreach($notices as $notice) {
                        $notice->time_difference = $notice ? timeAgo($notice->created_at,$language_id)  : null;
                        if($notice->notify_type && $notice->notify_type == Notice::ADDED_AS_CLIENT){

                            $key = $notice->notify_type.'_'.$language_id;
                            $notice->heading = __("notice.$key",['username' => $notice->user_name]);

                        }elseif($notice->notify_type && ($notice->notify_type == Notice::ASSOCIATION_ADDED || $notice->notify_type == Notice::ASSOCIATION_DISCONNECTED)){

                            $association = Association::where('id',$notice->entity_id)->first();
                            $key = "language_$language_id.".$notice->notify_type;
                            $notice->heading = __("messages.$key", ['association_name' => $association->association_name]);

                        }else{
                            $key = $notice->notify_type.'_'.$language_id;
                            $notice->heading = __("notice.$key");
                        }
                        
                    }
                }

            }

            return $this->sendSuccessResponse(Lang::get('messages.user.success'), 200, compact('notices'));
        } catch (\Exception $ex) {
            print_r($ex->getMessage());die;
            Log::info($ex);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function updateProfile(Request $request){
        $user = Auth::user();
        $manager = Manager::where('user_id',$user->id)->first();
        $inputs = $request->all();
 
        DB::beginTransaction();

        try{    
            $validation = $this->loginValidator->validateUpdateProfile($inputs);
            if ($validation->fails()) {
                return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
            }

            $address = $inputs['address'] ?? null;

            Manager::updateOrCreate(
                ['user_id' => $user->id],
                ['name' => $inputs['name'],'mobile' => $inputs['phone']]
            );

            User::where('id',$user->id)->update(['email' => $inputs['email']]);
            Address::updateOrCreate(
                ['entity_type_id' => $user->entity_type_id , 'entity_id' => $user->id],
                ['address' => $address]
            );

            DB::commit();
            $user = $this->getProfileFilterData($user);

            return $this->sendSuccessResponse(Lang::get('messages.profile.update'), 200,$user);
        } catch (\Exception $ex) {
            Log::info($ex);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }

    }

    public function getProfileFilterData($user){

        $manager = Manager::where('user_id',$user->id)->first();

        $associations = Association::join('association_users','association_users.association_id','associations.id')
            ->join('countries','countries.id','associations.country_id')
            ->whereIn('association_users.type',[AssociationUsers::SUPPORTER])
            ->where('association_users.user_id',$user->id)
            ->select('associations.*')
            ->groupBy('associations.id')
            ->first();

        if($associations){
            $president = $associations->associationUsers()->where('type',AssociationUsers::PRESIDENT)->first();
            $associations->president = (!empty($president)) ? $president->user_info->name : '';

            $associations->association_thumbnails = $associations->associationImage()->first();
            $associations->association_posts_count = $associations->associationCommunity()->count();
        }
        $user->associations = ($associations && !empty($associations->toArray())) ? $associations : null;


        $user->total_coins = (double)ReloadCoinRequest::join('users_detail','users_detail.user_id','reload_coins_request.user_id')
                    ->where('users_detail.manager_id',$manager->id)
                    ->where('status',ReloadCoinRequest::GIVE_COIN)
                    ->sum('reload_coins_request.coin_amount');

        $user->last_month_coins = (double)ReloadCoinRequest::join('users_detail','users_detail.user_id','reload_coins_request.user_id')
                    ->where('users_detail.manager_id',$manager->id)
                    ->where('status',ReloadCoinRequest::GIVE_COIN)
                    ->whereBetween('reload_coins_request.created_at',[Carbon::now()->startOfMonth()->subMonth(1),Carbon::now()->startOfMonth()])
                    ->sum('reload_coins_request.coin_amount');

        $user->this_month_coins = (double)ReloadCoinRequest::join('users_detail','users_detail.user_id','reload_coins_request.user_id')
                ->where('users_detail.manager_id',$manager->id)
                ->where('status',ReloadCoinRequest::GIVE_COIN)
                ->whereMonth('reload_coins_request.created_at',Carbon::now()->startOfMonth()->month)
                ->sum('reload_coins_request.coin_amount');
        $address = Address::where('entity_id',$user->id)->where('entity_type_id' , $user->entity_type_id)->first();
        $user->address = $address->address ?? '';
        return $user;
    }

    public function getProfileDetail(){
        $user = Auth::user();

        try{

            $user = $this->getProfileFilterData($user);
            return $this->sendSuccessResponse(Lang::get('messages.user.success'), 200, $user);

        } catch (\Exception $ex) {
            Log::info($ex);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function adminJoinAssociation(Request $request){
        $inputs = $request->all();
        $user = Auth::user();
        try{
            $validator = Validator::make($request->all(), [
                'code' => 'required',
                'language_id' => 'required'
            ]);
            if ($validator->fails()) {
                return $this->sendCustomErrorMessage($validator->errors()->toArray(), 422);
            }

            $code = $inputs['code'] ?? '';
            $language_id = $inputs['language_id'] ?? 4;

            $association = Association::where('code',$code)->first();

            if(empty($association)){
                $buttonKey = "language_$language_id.not_found";
                return $this->sendFailedResponse(__("messages.$buttonKey",['name' => 'Association']), 422);

            }else{

                AssociationUsers::updateOrCreate([
                    'association_id' => $association->id,
                    'type' => AssociationUsers::SUPPORTER,
                    'user_id' => $user->id
                ]);

                $devices = UserDevices::where('user_id', $user->id)->pluck('device_token')->toArray();
                $language_id = $language_id;
                $key = "language_$language_id.".Notice::ASSOCIATION_ADDED;
                $title_msg = __("messages.$key", ['association_name' => $association->association_name]);
                $format = '';
                $notify_type = Notice::ASSOCIATION_ADDED;

                Notice::create([
                    'notify_type' => $notify_type,
                    'user_id' => $user->id,
                    'to_user_id' => $user->id,
                    'entity_type_id' => EntityTypes::ASSOCIATION_COMMUNITY,
                    'entity_id' => $association->id,
                    'title' => $title_msg,
                    'sub_title' => $association->association_name,
                ]);

                if (count($devices) > 0) {
                    $result = $this->sentPushNotification($devices,$title_msg, $format, [] ,$notify_type, $association->id);                        
                }

                $user = $this->getProfileFilterData($user);
                
            }


            return $this->sendSuccessResponse(Lang::get('messages.user.success'), 200, $user);

        } catch (\Exception $ex) {
            Log::info($ex);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }

    }

    public function leaveJoinAssociation(Request $request){
        $inputs = $request->all();
        $user = Auth::user();

        try{
            $validator = Validator::make($request->all(), [
                'association_id' => 'required',
            ]);
            if ($validator->fails()) {
                return $this->sendCustomErrorMessage($validator->errors()->toArray(), 422);
            }

            $association_id = $inputs['association_id'] ?? '';
            $association = Association::where('id',$association_id)->first();

            AssociationUsers::where('association_id',$association_id)->where('user_id',$user->id)->where('type',AssociationUsers::SUPPORTER)->delete();

            $devices = UserDevices::where('user_id', $user->id)->pluck('device_token')->toArray();
            $language_id = 4;
            $key = "language_$language_id.".Notice::ASSOCIATION_DISCONNECTED;
            $title_msg = __("messages.$key", ['association_name' => $association->association_name]);
            $format = '';
            $notify_type = Notice::ASSOCIATION_DISCONNECTED;

            Notice::create([
                'notify_type' => $notify_type,
                'user_id' => $user->id,
                'to_user_id' => $user->id,
                'entity_type_id' => EntityTypes::ASSOCIATION_COMMUNITY,
                'entity_id' => $association->id,
                'title' => $title_msg,
                'sub_title' => $association->association_name,
            ]);

            if (count($devices) > 0) {
                $result = $this->sentPushNotification($devices,$title_msg, $format, [] ,$notify_type, $association->id);                        
            }

            $user = $this->getProfileFilterData($user);
            return $this->sendSuccessResponse(Lang::get('messages.user.success'), 200, $user);
        } catch (\Exception $ex) {
            Log::info($ex);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }
}
