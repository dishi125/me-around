<?php

namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\RequestFormStatus;
use App\Models\RequestForm;
use App\Models\EntityTypes;
use App\Models\Hospital;
use App\Models\Shop;
use App\Models\ShopPrices;
use App\Models\ShopPost;
use App\Models\User;
use App\Models\Status;
use App\Models\City;
use App\Models\Address;
use App\Models\UserEntityRelation;
use App\Models\UserCredit;
use App\Models\UserCreditHistory;
use App\Models\BasicMentions;
use App\Models\UserDetail;
use App\Models\Manager;
use App\Models\PackagePlan;
use App\Models\Notice;
use App\Models\Category;
use App\Models\CategoryTypes;
use App\Models\Config;
use App\Models\ManagerActivityLogs;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\Lang;
use Illuminate\Http\Request;
use Log;
use Illuminate\Support\Str;

class RequestedClientController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:requested-client-list', ['only' => ['index','indexShop','indexSuggest']]);
    }
/* ================ Hospital Code Start ======================= */
    public function index()
    {
        $title = 'Requested Client Hospital';   
        $rejectMentionText = BasicMentions::where('name','requested_client_reject')->pluck('value')->first();     
        return view('admin.requested-client.index-hospital', compact('title','rejectMentionText'));
    }

    public function getJsonAllHospitalData(Request $request)
    {
        try {   
            Log::info('Start requested hospital list');
            $columns = array(
                0 => 'id',
                1 => 'name',
                2 => 'category_name',
                3 => 'address',
                4 => 'city_name',
                5 => 'mobile',
                6 => 'email',
                7 => 'business_license_number',
                8 => 'photo',
                // 8 => 'status',
                9 => 'request_forms.created_at',
                // 9 => 'action'
            );
            $limit = $request->input('length');
            $start = $request->input('start');
            $search = $request->input('search.value');
            $draw = $request->input('draw');
            $order = $columns[$request->input('order.0.column')];
            $dir = $request->input('order.0.dir');
            $adminTimezone = $this->getAdminUserTimezone();

            $query = RequestForm::leftjoin('category','category.id','request_forms.category_id')
                ->leftjoin('cities','cities.id','request_forms.city_id')
                ->where('entity_type_id', EntityTypes::HOSPITAL)->where('request_status_id', RequestFormStatus::PENDING)
                ->select('request_forms.*','category.name as category_name','cities.name as city_name');
            $totalData = $query->count();
            $totalFiltered = $totalData;

            if (!empty($search)) {
                $query = $query->where(function($q) use ($search){
                    $q->where('request_forms.name', 'LIKE', "%{$search}%")
                    ->orWhere('category.name', 'LIKE', "%{$search}%")
                    ->orWhere('request_forms.email', 'LIKE', "%{$search}%")
                    ->orWhere('request_forms.address', 'LIKE', "%{$search}%")
                    ->orWhere('cities.name', 'LIKE', "%{$search}%");
                });
                
                $totalFiltered = $query->count();
            }

            $requestedHospitals = $query->offset($start)
                ->limit($limit)
                ->orderBy($order, $dir)
                ->get();

            $data = array();
            if (!empty($requestedHospitals)) {
                foreach ($requestedHospitals as $value) {
                    $id = $value['id'];
                    $nestedData['checkbox'] = "<td><div class=\"custom-checkbox custom-control\">
                    <input type=\"checkbox\" data-checkboxes=\"mygroup\" class=\"custom-control-input checkbox_id check-hospital\" id=\"$id\" data-id=\"$id\" value=\"$id\" name=\"checkbox_id[]\"><label for=\"$id\" class=\"custom-control-label\">&nbsp;</label></div></td>";
                   
                    $nestedData['business_name'] = $value['name'];
                    $nestedData['type_of_business'] = $value['category_name'];
                    $nestedData['address'] = $value['address'];
                    $nestedData['city'] = $value['city_name'];
                    $nestedData['phone_number'] = $value['mobile'];
                    $nestedData['email'] = $value['email']; 
                    $nestedData['business_license_number'] = $value['business_license_number']; 
                    $requestImages = '<div class="d-flex">';
                    if($value['business_licence_url']){
                        $requestImages .= '<img src="'.$value['business_licence_url'].'" alt="'.$value['name'].'" class="requested-client-images m-1" />';                     
                    }
                    if($value['interior_photo_url']){
                        $requestImages .= '<img src="'.$value['interior_photo_url'].'" alt="'.$value['name'].'" class="requested-client-images m-1" />';                     
                    }
                    
                    $requestImages .= "</div>";
                    $nestedData['photos'] = $requestImages; 
                    $nestedData['date'] = $this->formatDateTimeCountryWise($value['display_created_at'],$adminTimezone, 'd-m-Y H:i');
                    $data[] = $nestedData;
                }
            }
            $jsonData = array(
                "draw" => intval($draw),
                "recordsTotal" => intval($totalData),
                "recordsFiltered" => intval($totalFiltered),
                "data" => $data
            );
            Log::info('End requested hospital list');
            return response()->json($jsonData);
        } catch (\Exception $ex) {
            Log::info('Exception requested hospital list');
            Log::info($ex);
            return response()->json([]);
        }
    }    

/* ================ Hospital Code End ======================= */

    public function getAll()
    {
        $title = 'Requested Client';   
        $requestedClientCount = DB::table('request_forms')->whereNull('deleted_at')
                    ->where('request_status_id', RequestFormStatus::PENDING)
                    ->where('is_admin_read',1)
                    ->update(['is_admin_read' => 0]);

        $rejectMentionText = BasicMentions::where('name','requested_client_reject')->pluck('value')->first();     
        return view('admin.requested-client.index', compact('title','rejectMentionText'));
    }

    public function getJsonAllData(Request $request)
    {
        try {   
            Log::info('Start requested hospital list');
            $columns = array(
                0 => 'id',
                1 => 'name',
                2 => 'category_name',
                3 => 'address',
                4 => 'city_name',
                5 => 'request_forms.mobile',
                6 => 'email',
                7 => 'business_license_number',
                8 => 'photo',
                // 8 => 'status',
                9 => 'request_forms.created_at',
                // 9 => 'action'
            );
            $limit = $request->input('length');
            $start = $request->input('start');
            $search = $request->input('search.value');
            $draw = $request->input('draw');
            $order = $columns[$request->input('order.0.column')];
            $dir = $request->input('order.0.dir');
            $adminTimezone = $this->getAdminUserTimezone();

            $query = RequestForm::leftjoin('category','category.id','request_forms.category_id')
                ->leftjoin('cities','cities.id','request_forms.city_id')
                ->whereIn('entity_type_id', [EntityTypes::HOSPITAL,EntityTypes::SHOP])
                ->where('request_status_id', RequestFormStatus::PENDING)
                ->select('request_forms.*','category.name as category_name','cities.name as city_name');
            $totalData = $query->count();
            $totalFiltered = $totalData;

            if (!empty($search)) {
                $query = $query->where(function($q) use ($search){
                    $q->where('request_forms.name', 'LIKE', "%{$search}%")
                    ->orWhere('category.name', 'LIKE', "%{$search}%")
                    ->orWhere('request_forms.email', 'LIKE', "%{$search}%")
                    ->orWhere('request_forms.address', 'LIKE', "%{$search}%")
                    ->orWhere('cities.name', 'LIKE', "%{$search}%");
                });
                
                $totalFiltered = $query->count();
            }

            $requestedHospitals = $query->offset($start)
                ->limit($limit)
                ->orderBy($order, $dir)
                ->get();

            $data = array();
            if (!empty($requestedHospitals)) {
                foreach ($requestedHospitals as $value) {
                    $id = $value['id'];
                    $nestedData['checkbox'] = "<td><div class=\"custom-checkbox custom-control\">
                    <input type=\"checkbox\" data-checkboxes=\"mygroup\" class=\"custom-control-input checkbox_id check-hospital\" id=\"$id\" data-id=\"$id\" value=\"$id\" name=\"checkbox_id[]\"><label for=\"$id\" class=\"custom-control-label\">&nbsp;</label></div></td>";
                   
                    $nestedData['business_name'] = $value['name'];
                    $nestedData['type_of_business'] = $value['category_name'];
                    $nestedData['address'] = $value['address'];
                    $nestedData['city'] = $value['city_name'];
                    $nestedData['phone_number'] = $value['mobile'];
                    $nestedData['email'] = $value['email']; 
                    $nestedData['business_license_number'] = $value['business_license_number']; 
                    $requestImages = '<div class="d-flex">';
                    if($value['business_licence_url']){
                        $requestImages .= '<img src="'.$value['business_licence_url'].'" alt="'.$value['name'].'" class="requested-client-images m-1" />';                     
                    }
                    if($value['interior_photo_url']){
                        $requestImages .= '<img src="'.$value['interior_photo_url'].'" alt="'.$value['name'].'" class="requested-client-images m-1" />';                     
                    }
                    
                    $requestImages .= "</div>";
                    $nestedData['photos'] = $requestImages; 
                    $nestedData['date'] = $this->formatDateTimeCountryWise($value['display_created_at'],$adminTimezone, 'd-m-Y H:i');

                    $confirmButton = "<a role='button' href='javascript:void(0)' onclick='confirmRequest(" . $id . ")' title='' class='mx-1 btn btn-primary btn-sm' data-toggle='tooltip'>Confirm</a>";
                    $nestedData['actions'] = "<div class='d-flex'>$confirmButton</div>";
                    $data[] = $nestedData;
                }
            }
            $jsonData = array(
                "draw" => intval($draw),
                "recordsTotal" => intval($totalData),
                "recordsFiltered" => intval($totalFiltered),
                "data" => $data
            );
            Log::info('End requested hospital list');
            return response()->json($jsonData);
        } catch (\Exception $ex) {
            Log::info('Exception requested hospital list');
            Log::info($ex);
            return response()->json([]);
        }
    }   
/* ================ Shop Code Start ======================= */
    public function indexShop()
    {
        $title = "Requested Client Shop";
        $rejectMentionText = BasicMentions::where('name','requested_client_reject')->pluck('value')->first(); 
        return view('admin.requested-client.index-shop', compact('title','rejectMentionText'));
    }

    public function getJsonAllShopData(Request $request)
    {
        try {   
            Log::info('Start requested shop list');
            $columns = array(
                0 => 'id',
                1 => 'name',
                2 => 'category_name',
                3 => 'address',
                4 => 'city_name',
                5 => 'mobile',
                6 => 'email',
                7 => 'business_license_number',
                8 => 'photo',
                // 8 => 'status',
                9 => 'request_forms.created_at',
                // 9 => 'action'
            );
            $limit = $request->input('length');
            $start = $request->input('start');
            $search = $request->input('search.value');
            $draw = $request->input('draw');
            $order = $columns[$request->input('order.0.column')];
            $dir = $request->input('order.0.dir');
            $adminTimezone = $this->getAdminUserTimezone();

            $query = RequestForm::leftjoin('category','category.id','request_forms.category_id')
                ->leftjoin('cities','cities.id','request_forms.city_id')
                ->where('entity_type_id', EntityTypes::SHOP)
                ->where('request_status_id', RequestFormStatus::PENDING)
                ->where('category.category_type_id', CategoryTypes::SHOP)
                ->select('request_forms.*','category.name as category_name','cities.name as city_name');
            $totalData = $query->count();
            $totalFiltered = $totalData;

            if (!empty($search)) {
                $query = $query->where(function($q) use ($search){
                    $q->where('request_forms.name', 'LIKE', "%{$search}%")
                    ->orWhere('category.name', 'LIKE', "%{$search}%")
                    ->orWhere('request_forms.email', 'LIKE', "%{$search}%")
                    ->orWhere('request_forms.address', 'LIKE', "%{$search}%")
                    ->orWhere('cities.name', 'LIKE', "%{$search}%");
                });
                
                $totalFiltered = $query->count();
            }

            $requestedShops = $query->offset($start)
                ->limit($limit)
                ->orderBy($order, $dir)
                ->get();

            $data = array();
            if (!empty($requestedShops)) {
                foreach ($requestedShops as $value) {
                    $id = $value['id'];
                    $nestedData['checkbox'] = "<td><div class=\"custom-checkbox custom-control\">
                    <input type=\"checkbox\" data-checkboxes=\"mygroup\" class=\"custom-control-input checkbox_id check-shop\" id=\"$id\" data-id=\"$id\" value=\"$id\" name=\"checkbox_id[]\"><label for=\"$id\" class=\"custom-control-label\">&nbsp;</label></div></td>";
                   
                    $nestedData['business_name'] = $value['name'];
                    $nestedData['type_of_business'] = $value['category_name'];
                    $nestedData['address'] = $value['address'];
                    $nestedData['city'] = $value['city_name'];
                    $nestedData['phone_number'] = $value['mobile'];
                    $nestedData['email'] = $value['email']; 
                    $nestedData['business_license_number'] = $value['business_license_number']; 
                    $requestImages = '<div class="d-flex">';
                    if($value['business_licence_url']){
                        $requestImages .= '<img src="'.$value['business_licence_url'].'" alt="'.$value['name'].'" class="requested-client-images m-1" />';                     
                    }
                    if($value['identification_card_url']){
                        $requestImages .= '<img src="'.$value['identification_card_url'].'" alt="'.$value['name'].'" class="requested-client-images m-1" />';                     
                    }
                    if($value['best_portfolio_url']){
                        $requestImages .= '<img src="'.$value['best_portfolio_url'].'" alt="'.$value['name'].'" class="requested-client-images m-1" />';                     
                    }
                    
                    $requestImages .= "</div>";
                    $nestedData['photos'] = $requestImages; 
                    $nestedData['date'] = $this->formatDateTimeCountryWise($value['display_created_at'],$adminTimezone,'d-m-Y H:i');
                    $data[] = $nestedData;
                }
            }
            $jsonData = array(
                "draw" => intval($draw),
                "recordsTotal" => intval($totalData),
                "recordsFiltered" => intval($totalFiltered),
                "data" => $data
            );
            Log::info('End requested shop list');
            return response()->json($jsonData);
        } catch (\Exception $ex) {
            Log::info('Exception requested shop list');
            Log::info($ex);
            return response()->json([]);
        }
    }   
    
    /* ================ Shop Code End ======================= */ 

    /* ================ Suggest Code Start ======================= */
    public function indexSuggest()
    {
        $title = "Requested Client Suggest";
        $rejectMentionText = BasicMentions::where('name','requested_client_reject')->pluck('value')->first(); 
        return view('admin.requested-client.index-suggest', compact('title','rejectMentionText'));
    }

    public function getJsonAllSuggestData(Request $request)
    {
        try {   
            Log::info('Start requested suggest list');
            $columns = array(
                0 => 'id',
                1 => 'name',
                2 => 'category_name',
                3 => 'address',
                4 => 'city_name',
                5 => 'mobile',
                6 => 'email',
                7 => 'photo',
                // 8 => 'status',
                8 => 'request_forms.created_at',
                // 9 => 'action'
            );
            $limit = $request->input('length');
            $start = $request->input('start');
            $search = $request->input('search.value');
            $draw = $request->input('draw');
            $order = $columns[$request->input('order.0.column')];
            $dir = $request->input('order.0.dir');
            $adminTimezone = $this->getAdminUserTimezone();

            $query = RequestForm::leftjoin('category','category.id','request_forms.category_id')
                ->leftjoin('cities','cities.id','request_forms.city_id')
                ->where('entity_type_id', EntityTypes::SHOP)
                ->where('request_status_id', RequestFormStatus::PENDING)
                ->where('category.category_type_id', CategoryTypes::CUSTOM)
                ->select('request_forms.*','category.name as category_name','cities.name as city_name');
            $totalData = $query->count();
            $totalFiltered = $totalData;

            if (!empty($search)) {
                $query = $query->where(function($q) use ($search){
                    $q->where('request_forms.name', 'LIKE', "%{$search}%")
                    ->orWhere('category.name', 'LIKE', "%{$search}%")
                    ->orWhere('request_forms.email', 'LIKE', "%{$search}%")
                    ->orWhere('request_forms.address', 'LIKE', "%{$search}%")
                    ->orWhere('cities.name', 'LIKE', "%{$search}%");
                });
                
                $totalFiltered = $query->count();
            }

            $requestedShops = $query->offset($start)
                ->limit($limit)
                ->orderBy($order, $dir)
                ->get();

            $data = array();
            if (!empty($requestedShops)) {
                foreach ($requestedShops as $value) {
                    $id = $value['id'];
                    $nestedData['checkbox'] = "<td><div class=\"custom-checkbox custom-control\">
                    <input type=\"checkbox\" data-checkboxes=\"mygroup\" class=\"custom-control-input checkbox_id check-suggest\" id=\"$id\" data-id=\"$id\" value=\"$id\" name=\"checkbox_id[]\"><label for=\"$id\" class=\"custom-control-label\">&nbsp;</label></div></td>";
                   
                    $nestedData['business_name'] = $value['name'];
                    $nestedData['type_of_business'] = $value['category_name'];
                    $nestedData['address'] = $value['address'];
                    $nestedData['city'] = $value['city_name'];
                    $nestedData['phone_number'] = $value['mobile'];
                    $nestedData['email'] = $value['email']; 
                    $requestImages = '<div class="d-flex">';
                    if($value['business_licence_url']){
                        $requestImages .= '<img src="'.$value['business_licence_url'].'" alt="'.$value['name'].'" class="requested-client-images m-1" />';                     
                    }
                    if($value['identification_card_url']){
                        $requestImages .= '<img src="'.$value['identification_card_url'].'" alt="'.$value['name'].'" class="requested-client-images m-1" />';                     
                    }
                    if($value['best_portfolio_url']){
                        $requestImages .= '<img src="'.$value['best_portfolio_url'].'" alt="'.$value['name'].'" class="requested-client-images m-1" />';                     
                    }
                    
                    $requestImages .= "</div>";
                    $nestedData['photos'] = $requestImages; 
                    $nestedData['date'] = $this->formatDateTimeCountryWise($value['display_created_at'],$adminTimezone, 'd-m-Y H:i');
                    $data[] = $nestedData;
                }
            }
            $jsonData = array(
                "draw" => intval($draw),
                "recordsTotal" => intval($totalData),
                "recordsFiltered" => intval($totalFiltered),
                "data" => $data
            );
            Log::info('End requested suggest list');
            return response()->json($jsonData);
        } catch (\Exception $ex) {
            Log::info('Exception requested suggest list');
            Log::info($ex);
            return response()->json([]);
        }
    }   
    
    /* ================ Suggest Code End ======================= */ 

    public function approveMultiple(Request $request)
    {
        try {
            $loggedInUser = Auth::user();
            $manager = Manager::where('user_id',$loggedInUser->id)->first();
            $inputs = $request->all();
            $ids = explode(',',$inputs['ids']);


            DB::beginTransaction();
            foreach ($ids as $id)
            {
                $requestForm = RequestForm::find($id);
                $entityType = $requestForm->entity_type_name;
                $entityTypeID = $requestForm->entity_type_id;
                $request_id = $requestForm->id;
                $city_id = $requestForm->city_id;

                Log::info('Start Create ' . $entityType . ' :');

                // Condition By EntityType
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
                $this->addManagerActivityLogs($logData);
                Log::info('End Create ' . $entityType);
            }
                DB::commit();
                return $this->sendSuccessResponse('Request Confirm  successfully.', 200);
        } catch (\Exception $ex) {
            Log::info('Exception in Create ' . $entityType . ' :');
            Log::info($ex);
            DB::rollBack();
            return $this->sendFailedResponse('Unable to approve.', 400);
        }
    }

    public function rejectMultiple(Request $request)
    {
        try {
            $inputs = $request->all();
            $ids = explode(',',$inputs['ids']);
            $reject_comment = $inputs['reject_comment'];
            foreach ($ids as $id)
            {
                $requestForm = RequestForm::find($id);
                $entityType = $requestForm->entity_type_name;
                $request_id = $requestForm->id;

                Log::info('Start Create ' . $entityType . ' :');

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
            }
            DB::commit();
            Log::info('End Create ' . $entityType);
            return $this->sendSuccessResponse('Request Rejected  successfully.', 200);
        } catch (\Exception $ex) {
            Log::info('Exception in Create ' . $entityType . ' :');
            Log::info($ex);
            return $this->sendFailedResponse('Unable to reject.', 400);            
        }
    }

    public function confirmRejectMention(Request $request)
    {
        try {
            Log::info('Start Basic Mention ');
            $inputs = $request->all();
            $content = $inputs['content'];
            $type = $inputs['type'];
            $name = $type == 'confirm' ? 'request_form_confirm' : 'requested_client_reject';
            $rejectMentionText = BasicMentions::where('name',$name)->update(['value' => $content]); 
            Log::info('End Basic Mention ' );
            return $this->sendSuccessResponse('Basic mention set successfully.', 200);
        } catch (\Exception $ex) {
            Log::info('Exception in Basic Mention ');
            Log::info($ex);
            return $this->sendFailedResponse('Unable to set basic mention.', 400);
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

    public function getAllConfirmed(Request $request,$type = 'all')
    {
        $title = ucfirst($type).' Confirmed';         
        return view('admin.requested-client.index-confirmed', compact('title','type'));
    }

    public function getJsonAllConfirmedData(Request $request){

        $columns = array(
            0 => 'users_detail.name',
            1 => 'users.email',
            3 => 'users.created_at',
            5 => 'users.last_login',
            11 => 'action',
        );

        $filter = $request->input('filter');
        $limit = $request->input('length');
        $start = $request->input('start');
        $search = $request->input('search.value');
        $draw = $request->input('draw');
        $order = $columns[$request->input('order.0.column')];
        $dir = $request->input('order.0.dir');
        
        $adminTimezone = $this->getAdminUserTimezone();
        $viewButton = '';
        $toBeShopButton = '';

        try {
            $data = [];
            $userQuery = DB::table('users')->leftJoin('user_entity_relation','user_entity_relation.user_id','users.id')
                ->leftJoin('users_detail','users_detail.user_id','users.id')
                //->whereIN('user_entity_relation.entity_type_id', [EntityTypes::HOSPITAL, EntityTypes::SHOP])
                ->whereNotNull('users.email')
                ->whereNull('users.deleted_at')
                ->whereIn('users.status_id', [Status::ACTIVE, Status::INACTIVE])
                ->select(
                    'users.id',
                    'users_detail.name',
                    'users_detail.mobile',
                    'users.email',
                    'users.created_at as date',
                    'users.last_login as last_access',
                    DB::raw('(SELECT group_concat(entity_type_id) from user_entity_relation WHERE user_id = users.id) as entity_types')
                )
                ->groupBy('users.id');
            
            if (!empty($search)) {
                $userQuery = $userQuery->where(function($q) use ($search){
                    $q->where('users_detail.name', 'LIKE', "%{$search}%")
                    ->orWhere('users.email', 'LIKE', "%{$search}%")
                    ->orWhere('users.created_at', 'LIKE', "%{$search}%")
                    ->orWhere('users_detail.mobile', 'LIKE', "%{$search}%");
                });
                
            }

            if($filter != 'all'){
                if($filter == 'shop'){
                    $userQuery->join('shops','shops.user_id','users.id')
                        ->leftjoin('category','category.id','shops.category_id')
                        ->where('category.category_type_id', CategoryTypes::SHOP);
                    $filterWhere = [EntityTypes::SHOP];
                }else if($filter == 'suggest'){
                    
                    $userQuery->join('shops','shops.user_id','users.id')
                        ->leftjoin('category','category.id','shops.category_id')
                        ->where('category.category_type_id', CategoryTypes::CUSTOM);
                    $filterWhere = [EntityTypes::SHOP];
                }else{
                    $filterWhere = [EntityTypes::HOSPITAL]; 
                }
            }else{                
                $filterWhere = [EntityTypes::HOSPITAL, EntityTypes::SHOP];
            }

            $userQuery = $userQuery->whereIn('user_entity_relation.entity_type_id', $filterWhere);

            $totalData = count($userQuery->get());
            $totalFiltered = $totalData;
            
            $userData = $userQuery->offset($start)
                        ->limit($limit)
                        ->orderBy($order, $dir)  
                        ->get();

            
            $count = 0;
            foreach($userData as $user){

                $statusHtml = '<div style="display:flex;" class="align-items-center">';
                $userTypes = explode(",",$user->entity_types);
                $outsideBusinessButton = '';

                if(in_array(EntityTypes::HOSPITAL,$userTypes)){
                    $userType = "Hospital";
                    $hospitalData = DB::table('hospitals')->join('user_entity_relation','user_entity_relation.entity_id','hospitals.id')
                    ->where('user_entity_relation.user_id',$user->id)
                    ->where('user_entity_relation.entity_type_id',EntityTypes::HOSPITAL)
                    ->select('hospitals.*')
                    ->first();
                    if($hospitalData->status_id == Status::ACTIVE){
                        $statusHtml .= '<span class="badge badge-success">&nbsp;</span>';
                    }elseif($hospitalData->status_id == Status::PENDING){
                        $statusHtml .= '<span class="badge" style="background-color: #fff700;">&nbsp;</span>';
                    }else{
                        $statusHtml .= '<span class="badge badge-secondary">&nbsp;</span>';
                    }
                    $linkButton = route('admin.business-client.hospital.show', $hospitalData->id);

                    $viewButton = "<a role='button' href='$linkButton'  data-original-title='View' class='btn btn-primary btn-sm ' data-toggle='tooltip'><i class='fas fa-eye mt-1'></i></a>";
                    $toBeShopButton = '';

                    $loginUser = Auth::user();
                   // $editCoinButton =  $loginUser->hasRole('Admin') ? "<a href='javascript:void(0)' role='button' onclick='editCredits(" . $user->id . ")' class='btn btn-primary btn-sm mx-1' data-toggle='tooltip' data-original-title='Edit'><i class='fa fa-edit'></a>" : "";

                   $editCoinButton = '';
                   if($hospitalData->business_link != ''){
                        $statusHtml .= '<i class="fas fa-star" style="font-size: 25px; color: #fff700; -webkit-text-stroke-width: 1px; -webkit-text-stroke-color: #43425d;"></i>';
                    }
                }elseif(in_array(EntityTypes::SHOP,$userTypes)){
                    $userType = "Shop";
                    $viewButton = "<a role='button' href='javascript:void(0)' onclick='viewShopProfile(" . $user->id . ")'  title='' data-original-title='View' class='btn btn-primary btn-sm ' data-toggle='tooltip'><i class='fas fa-eye mt-1'></i></a>";
                    $toBeShopButton = '';
                    
                    $shopsData = DB::table('shops')->whereNull('deleted_at')->where('user_id',$user->id)->get();
                    
                    $isOutsideBusiness = false;
                    if($shopsData){
                        foreach ($shopsData as $key => $value) {
                            if($value->status_id == Status::ACTIVE){
                                $statusHtml .= '<span class="badge badge-success">&nbsp;</span>';
                            }elseif($value->status_id == Status::PENDING){
                                $statusHtml .= '<span class="badge" style="background-color: #fff700;">&nbsp;</span>';
                            }else{
                                $statusHtml .= '<span class="badge badge-secondary">&nbsp;</span>';
                            }
                            if($value->business_link != ''){
                                $isOutsideBusiness = true;
                            }
                        }
                    }
                    if($isOutsideBusiness == true){
                        $statusHtml .= '<i class="fas fa-star" style="font-size: 25px; color: #fff700; -webkit-text-stroke-width: 1px; -webkit-text-stroke-color: #43425d;"></i>';
                    }

                    $loginUser = Auth::user();
                    $editCoinButton =  $loginUser->hasRole('Admin') ? "<a href='javascript:void(0)' role='button' onclick='editCredits(" . $user->id . ")' class='btn btn-primary btn-sm mx-1' data-toggle='tooltip' data-original-title='Edit'><i class='fa fa-edit' style='font-size: 15px;margin: 4px -3px 0 0;'></a>" : "";
                }else{
                    $userType = "User";
                    $viewButton = "";

                    $isRequested = RequestForm::leftjoin('category','category.id','request_forms.category_id')
                    ->leftjoin('cities','cities.id','request_forms.city_id')
                    ->whereIn('entity_type_id', [EntityTypes::HOSPITAL,EntityTypes::SHOP])
                    ->where('request_status_id', RequestFormStatus::PENDING)
                    ->where('user_id',$user->id)
                    ->select('request_forms.*')->count();

                    if($isRequested > 0){
                        $toBeShopButton = "<a role='button' href='javascript:void(0)' title='' data-original-title='Requested' class='btn btn-danger btn-sm ' data-toggle='tooltip' style='pointer-events: none; cursor: default;'>Requested </a>";
                    }else{
                        $toBeShopButton = "<a role='button' href='javascript:void(0)' onclick='viewUserToShop(" . $user->id . ")'  title='' data-original-title='User to be shop' class='btn btn-primary btn-sm ' data-toggle='tooltip'>User to be shop</a>";

                    }
                    
                    $editCoinButton = '';
                }

                $statusHtml .= '</div>';
                $deleteButton = "<a role='button' href='javascript:void(0)' onclick='deleteUser(" . $user->id . ")' title='' data-original-title='Delete Account' class='mx-1 btn btn-primary btn-sm' data-toggle='tooltip'>Delete</a>";
                $editButton = "<a role='button' href='javascript:void(0)' onclick='editPassword(" . $user->id . ")' title='' data-original-title='Edit Account' class='mx-1 btn btn-primary btn-sm' data-toggle='tooltip'>Edit Password</a>";

                $data[$count]['name'] = $user->name;
                $data[$count]['email'] = $user->email;
                $data[$count]['phone'] = $user->mobile;
                $data[$count]['signup'] = $this->formatDateTimeCountryWise($user->date,$adminTimezone);
                $data[$count]['business_type'] = $userType;
                $data[$count]['status'] = "$statusHtml $outsideBusinessButton";
                $data[$count]['last_access'] = $this->formatDateTimeCountryWise($user->last_access,$adminTimezone);
                $data[$count]['actions'] = "<div class='d-flex'>$viewButton $deleteButton $editCoinButton</div>";
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
   
}
