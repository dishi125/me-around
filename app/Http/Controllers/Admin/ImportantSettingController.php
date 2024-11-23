<?php

namespace App\Http\Controllers\Admin;
use Log;

use Hash;
use App\Models\User;
use ReflectionClass;
use App\Models\Config;
use App\Models\Notice;
use App\Util\Firebase;
use App\Models\Country;
use App\Models\UserDetail;
use App\Models\CreditPlans;
use App\Models\EntityTypes;
use App\Models\PackagePlan;
use App\Models\UserDevices;
use Illuminate\Support\Str;
use App\Models\PostLanguage;
use Illuminate\Http\Request;
use App\Models\ConfigLanguages;
use App\Models\GeneralSettings;
use App\Models\UserEntityRelation;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use App\Models\ConfigCountryDetail;
use App\Models\LinkedSocialProfile;
use App\Models\ManagerActivityLogs;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Validator;

class ImportantSettingController extends Controller
{
    protected $firebase;

    public function __construct()
    {
        $this->firebase = new Firebase();
        $this->middleware('permission:important-custom-list', ['only' => ['index','indexLimitCustom']]);
    }

/* ================ Credit Rating Code Start ======================= */
    public function index()
    {
        $title = 'Credit deducting custom & Distance system custom (Hospital)';
        return view('admin.important-setting.index', compact('title'));
    }

    public function getJsonAllHospitalData(Request $request)
    {
        try {
            Log::info('Start important setting hospital');
            $user = Auth::user();
            $columns = array(
                // 0 => 'id',
                0 => 'credit_plans.package_plan_id',
                1 => 'credit_plans.entity_type_id',
                2 => 'credit_plans.deduct_rate',
                3 => 'credit_plans.amount',
                4 => 'credit_plans.no_of_posts',
                5 => 'credit_plans.km',
            );
            $limit = $request->input('length');
            $start = $request->input('start');
            $search = $request->input('search.value');
            $draw = $request->input('draw');
            $order = $columns[$request->input('order.0.column')];
            $dir = $request->input('order.0.dir');
            $query = CreditPlans::join('package_plans','package_plans.id','credit_plans.package_plan_id')->whereIn('entity_type_id', [EntityTypes::HOSPITAL, EntityTypes::SHOP])
                                ->select('credit_plans.*','package_plans.name')
                                ->orderBy('entity_type_id','DESC');
            $totalData = $query->count();
            $totalFiltered = $totalData;
            if (!empty($search)) {
                $query = $query->where(function($q) use ($search){
                    $q->where('credit_plans.deduct_rate', 'LIKE', "%{$search}%")
                    ->orWhere('package_plans.name', 'LIKE', "%{$search}%")
                    ->orWhere('credit_plans.amount', 'LIKE', "%{$search}%")
                    ->orWhere('credit_plans.km', 'LIKE', "%{$search}%")
                    ->orWhere('credit_plans.no_of_posts', 'LIKE', "%{$search}%");
                });

                $totalFiltered = $query->count();
            }
            $hospitals = $query->offset($start)
                ->limit($limit)
                ->orderBy($order, $dir)
                ->get();
            $data = array();
            if (!empty($hospitals)) {
                foreach ($hospitals as $value) {
                    $planType = '';
                    $id = $value['id'];

                    if($value['entity_type_id'] == EntityTypes::HOSPITAL){
                        $planType = "Hospital";
                    }elseif($value['entity_type_id'] == EntityTypes::SHOP){
                        $planType = "Shop";
                    }

                    $nestedData['package'] = $value['package_plan_name'];
                    $nestedData['type'] = $planType;
                    $nestedData['deducting_rate'] = $value['deduct_rate'];
                    $nestedData['regular_payment'] = number_format($value['amount']);
                    $nestedData['post'] = $value['no_of_posts'];
                    $nestedData['km'] = $value['km'];
                    $edit = route('admin.important-setting.hospital.edit', $id);
                    $editPostButton = "<a href='".$edit."' role='button' class='btn btn-primary btn-sm mx-1' data-toggle='tooltip' data-original-title='Edit'><i class='fa fa-edit'></a>";
                    $nestedData['actions'] = $editPostButton;

                    $data[] = $nestedData;
                }
            }
            $jsonData = array(
                "draw" => intval($draw),
                "recordsTotal" => intval($totalData),
                "recordsFiltered" => intval($totalFiltered),
                "data" => $data
            );
            Log::info('End important setting hospital');
            return response()->json($jsonData);
        } catch (\Exception $ex) {
            Log::info('Exception important setting hospital');
            Log::info($ex);
            return response()->json([]);
        }
    }

    public function getJsonAllShopData(Request $request)
    {
        try {
            Log::info('Start important setting shop');
            $user = Auth::user();
            $columns = array(
                0 => 'credit_plans.package_plan_id',
                1 => 'credit_plans.deduct_rate',
                2 => 'credit_plans.amount',
                3 => 'credit_plans.km',
            );
            $limit = $request->input('length');
            $start = $request->input('start');
            $search = $request->input('search.value');
            $draw = $request->input('draw');
            $order = $columns[$request->input('order.0.column')];
            $dir = $request->input('order.0.dir');
            $query = CreditPlans::join('package_plans','package_plans.id','credit_plans.package_plan_id')->where('entity_type_id', EntityTypes::SHOP)
                                ->select('credit_plans.*','package_plans.name');
            $totalData = $query->count();
            $totalFiltered = $totalData;
            if (!empty($search)) {
                $query = $query->where(function($q) use ($search){
                    $q->where('credit_plans.deduct_rate', 'LIKE', "%{$search}%")
                    ->orWhere('package_plans.name', 'LIKE', "%{$search}%")
                    ->orWhere('credit_plans.amount', 'LIKE', "%{$search}%")
                    ->orWhere('credit_plans.km', 'LIKE', "%{$search}%");
                });

                $totalFiltered = $query->count();
            }
            $shops = $query->offset($start)
                ->limit($limit)
                ->orderBy($order, $dir)
                ->get();

            $data = array();
            if (!empty($shops)) {
                foreach ($shops as $value) {
                    $id = $value['id'];

                    $nestedData['package'] = $value['package_plan_name'];
                    $nestedData['deducting_rate'] = $value['deduct_rate'];
                    $nestedData['regular_payment'] = number_format($value['amount']);
                    $nestedData['km'] = $value['km'];
                    $edit = route('admin.important-setting.shop.edit', $id);
                    $editPostButton = "<a href='".$edit."' role='button' class='btn btn-primary btn-sm mx-1' data-toggle='tooltip' data-original-title='Edit'><i class='fa fa-edit'></a>";
                    $nestedData['actions'] = $editPostButton;

                    $data[] = $nestedData;
                }
            }
            $jsonData = array(
                "draw" => intval($draw),
                "recordsTotal" => intval($totalData),
                "recordsFiltered" => intval($totalFiltered),
                "data" => $data
            );
            Log::info('End important setting shop');
            return response()->json($jsonData);
        } catch (\Exception $ex) {
            Log::info('Exception important setting shop');
            Log::info($ex);
            return response()->json([]);
        }
    }
    public function editHospital($id)
    {
        $title = "Edit Hospital Important Settings";
        $settings = CreditPlans::find($id);

        return view('admin.important-setting.edit-hospital-setting', compact('title', 'settings'));
    }
    public function editShop($id)
    {
        $title = "Edit Shop Important Settings";
        $settings = CreditPlans::find($id);

        return view('admin.important-setting.edit-shop-setting', compact('title', 'settings'));
    }

    public function updateHospital(Request $request,$id)
    {
        try {
            Log::info('Start code for the update hospital setting');
            DB::beginTransaction();
            $inputs = $request->all();
            $validator = Validator::make($request->all(), [
                'package' => 'required',
                'deduct_rate' => 'required|numeric',
                'no_of_posts' => 'required|numeric',
                'amount' => 'required|numeric',
                'km' => 'required|regex:/^\d*(\.\d{1})?$/',
            ]);
            if ($validator->fails()) {
                return redirect()->back()->withErrors($validator)->withInput();
            }

            $data = [
                "deduct_rate" => $inputs["deduct_rate"],
                "no_of_posts" => $inputs["no_of_posts"],
                "amount" => $inputs["amount"],
                "km" => $inputs["km"],
            ];
            $updatePlan = CreditPlans::updateOrCreate(['id' => $id],$data);

            if ($updatePlan->wasChanged()) {
                $changes = $updatePlan->getChanges();
                if(!empty($changes)){
                    foreach($changes as $changeKey => $changeValue){
                        if(!in_array($changeKey,['created_at','updated_at'])){
                            $logData = [
                                'activity_type' => ManagerActivityLogs::EDIT_CREDIT_SETTINGS,
                                'user_id' => auth()->user()->id,
                                'value' => "$changeValue|$changeKey",
                                'entity_id' => $id,
                            ];
                            $this->addManagerActivityLogs($logData);
                        }
                    }
                }
            }

            Log::info('End the code for the update hospital setting');
            DB::commit();
            notify()->success("hospital setting updated successfully", "Success", "topRight");
            return redirect()->route('admin.important-setting.index');
        } catch (\Exception $e) {
            Log::info('Exception in the update hospital setting.');
            Log::info($e);
            notify()->error("Failed to update hospital setting", "Error", "topRight");
            return redirect()->route('admin.important-setting.index');
        }
    }
    public function updateShop(Request $request, $id)
    {
        try {
            Log::info('Start code for the update shop setting');
            DB::beginTransaction();
            $inputs = $request->all();
            $validator = Validator::make($request->all(), [
                'package' => 'required',
                'deduct_rate' => 'required|numeric',
                'amount' => 'required|numeric',
                'km' => 'required|regex:/^\d*(\.\d{1})?$/',
            ]);
            if ($validator->fails()) {
                return redirect()->back()->withErrors($validator)->withInput();
            }

            $data = [
                "deduct_rate" => $inputs["deduct_rate"],
                "amount" => $inputs["amount"],
                "km" => $inputs["km"],
            ];
            $updatePlan = CreditPlans::where('id',$id)->update($data);

            Log::info('End the code for the update shop setting');
            DB::commit();
            notify()->success("shop setting updated successfully", "Success", "topRight");
            return redirect()->route('admin.important-setting.index');
        } catch (\Exception $e) {
            Log::info('Exception in the update shop setting.');
            Log::info($e);
            notify()->error("Failed to update shop setting", "Error", "topRight");
            return redirect()->route('admin.important-setting.index');
        }
    }

/* ================ Credit Rating Code End ======================= */

/* ================ Limit Custom Code Start ======================= */
    public function indexLimitCustom()
    {
        $title = 'Limit Custom';
        $expireMasterPassword = Config::expirePassword();
        return view('admin.important-setting.index-limit-custom', compact('title'));
    }

    public function getJsonLimitCustomData(Request $request)
    {
        try {
            Log::info('Start important setting shop');
            $user = Auth::user();
            $columns = array(
                0 => 'key',
                1 => 'value',
            );
            $limit = $request->input('length');
            $start = $request->input('start');
            $search = $request->input('search.value');
            $draw = $request->input('draw');
            $order = $columns[$request->input('order.0.column')];
            $dir = $request->input('order.0.dir');
            $query = Config::where('key','!=',Config::INSTA_CRON_TIME)->whereNotIn('key',Config::PAYPLE_FIELDS)->where('is_link',0)->where('is_show_hide',0)->select('*');
            $totalData = $query->count();
            $totalFiltered = $totalData;
            if (!empty($search)) {
                $query = $query->where(function($q) use ($search){
                    $q->where('key', 'LIKE', "%{$search}%")
                    ->orWhere('value', 'LIKE', "%{$search}%");
                });

                $totalFiltered = $query->count();
            }
            $shops = $query->offset($start)
                ->limit($limit)
                ->orderBy($order, $dir)
                ->get();

            $data = array();
            if (!empty($shops)) {
                foreach ($shops as $value) {
                    $id = $value['id'];
                    $formatted_value = $value['formatted_value'];
                    $nestedData = [];
                    $nestedData['field'] = $value['key'];
                    $nestedData['figure'] = $formatted_value;
                    $edit = route('admin.important-setting.limit-custom.edit', $id);
                    $editPostButton = "<a href='".$edit."' role='button' class='btn btn-primary btn-sm mx-1' data-toggle='tooltip' data-original-title='Edit'><i class='fa fa-edit'></a>";
                    $nestedData['actions'] = $editPostButton;

                    $data[] = $nestedData;
                }
            }
            $jsonData = array(
                "draw" => intval($draw),
                "recordsTotal" => intval($totalData),
                "recordsFiltered" => intval($totalFiltered),
                "data" => $data
            );
            Log::info('End important setting shop');
            return response()->json($jsonData);
        } catch (\Exception $ex) {
            Log::info('Exception important setting shop');
            Log::info($ex);
            return response()->json([]);
        }
    }

    public function indexLimitLinkCustom()
    {
        $title = 'Limit Custom';
        return view('admin.important-setting.index-limit-link-custom', compact('title'));
    }

    public function getJsonLimitLinkCustomData(Request $request)
    {
        try {
            Log::info('Start important setting links');
            $user = Auth::user();
            $columns = array(
                0 => 'key',
                1 => 'value',
            );
            $limit = $request->input('length');
            $start = $request->input('start');
            $search = $request->input('search.value');
            $draw = $request->input('draw');
            $order = $columns[$request->input('order.0.column')];
            $dir = $request->input('order.0.dir');

            $query = Config::where('is_link',1)->select('*');

            $totalData = $query->count();
            $totalFiltered = $totalData;

            if (!empty($search)) {
                $query = $query->where(function($q) use ($search){
                    $q->where('key', 'LIKE', "%{$search}%")
                    ->orWhere('value', 'LIKE', "%{$search}%");
                });

                $totalFiltered = $query->count();
            }

            if($order == 'key' && $dir == 'asc'){
                $order = 'sort_order';
            }
            $shops = $query->offset($start)
                ->limit($limit)
                ->orderBy($order, $dir)
                ->get();

            $data = array();
            if (!empty($shops)) {
                foreach ($shops as $value) {
                    $nestedData = [];
                    $key = $value['key'] == 'Request client report sns reward email' ? 'Request Client/Report/SNS Reward Email' : $value['key'];
                    $id = $value['id'];
                    $nestedData['field'] = $key;
                    $nestedData['figure'] = $value['value'];

                    if($value['is_different_lang'] == true){
                        $edit = route('admin.important-setting.limit-custom.edit.language', $id);
                    }else{
                        $edit = route('admin.important-setting.limit-custom.edit', $id);
                    }
                    $editPostButton = "<a href='".$edit."' role='button' class='btn btn-primary btn-sm mx-1' data-toggle='tooltip' data-original-title='Edit'><i class='fa fa-edit'></a>";
                    $nestedData['actions'] = $editPostButton;

                    $data[] = $nestedData;
                }
            }
            $jsonData = array(
                "draw" => intval($draw),
                "recordsTotal" => intval($totalData),
                "recordsFiltered" => intval($totalFiltered),
                "data" => $data
            );
            Log::info('End important setting shop');
            return response()->json($jsonData);
        } catch (\Exception $ex) {
            Log::info('Exception important setting shop');
            Log::info($ex);
            return response()->json([]);
        }
    }

    public function editLimitCustom($id)
    {
        $title = "Edit Limit Custom Settings";
        $settings = Config::find($id);
        $newsettings = DB::table('config')->whereId($id)->first();
        $expireMasterPassword = Config::expirePassword();
        return view('admin.important-setting.edit-limit-custom', compact('title', 'settings','newsettings'));
    }

    public function updateLimitCustom(Request $request, $id)
    {
        try {
            Log::info('Start code for the update limit custom setting');
            DB::beginTransaction();
            $inputs = $request->all();

            $validator = Validator::make($request->all(), [
                'key' => 'required',
                //'value' => 'required',
            ]);
            if ($validator->fails()) {
                return redirect()->back()->withErrors($validator)->withInput();
            }

            $value = $inputs["value"];
            $key = strtolower(str_replace(' ', '_', $inputs["key"]));
            if($key == Config::ADMIN_MASTER_PASSWORD){
                $value = Hash::make($inputs['value']);
            }

            $updatePlan = Config::find($id);
            $updatePlan->value = $value ?? '';
            $updatePlan->save();


            if ($updatePlan->wasChanged()) {
                $logData = [
                    'activity_type' => ManagerActivityLogs::EDIT_SETTINGS,
                    'user_id' => auth()->user()->id,
                    'value' => $inputs["value"],
                    'entity_id' => $id,
                ];
                $this->addManagerActivityLogs($logData);
            }

            Log::info('End the code for the update limit custom setting');
            DB::commit();
            notify()->success("limit custom setting updated successfully", "Success", "topRight");

            if(in_array($key,Config::PAYPLE_FIELDS)){
                $redirect = 'admin.important-setting.payple-setting.index';
            }else{
                $redirect = 'admin.important-setting.limit-custom.index';
            }

            return redirect()->route($redirect);
        } catch (\Exception $e) {
            Log::info('Exception in the update limit custom setting.');
            Log::info($e);
            notify()->error("Failed to update limit custom setting", "Error", "topRight");
            return redirect()->route('admin.important-setting.limit-custom.index');
        }
    }

    public function updateLimitCustomProduct(Request $request, $id)
    {
        try {
            DB::beginTransaction();
            $inputs = $request->all();

            $validator = Validator::make($request->all(), [
                'key' => 'required',
                //'value' => 'required',
            ]);
            if ($validator->fails()) {
                return redirect()->back()->withErrors($validator)->withInput();
            }

            $payment_type = $inputs['payment_type'] ?? '01';
            $payment_method = $inputs['payment_method'] ?? 'PAY';


            $updatePlan = Config::find($id);

            $prodDetail = ($payment_type == '01') ? "|$payment_method" : '';
            $updatePlan->value = $payment_type.$prodDetail;
            $updatePlan->save();

            DB::commit();
            notify()->success("limit custom setting updated successfully", "Success", "topRight");
            return redirect()->route('admin.important-setting.payple-setting.index');
        } catch (\Exception $e) {
            Log::info('Exception in the update limit custom setting.');
            Log::info($e);
            notify()->error("Failed to update limit custom setting", "Error", "topRight");
            return redirect()->route('admin.important-setting.payple-setting.index');
        }
    }

    public function sendNotification(Request $request)
    {
        try {
            $inputs = $request->all();
            Log::info('Start custom setting change send notification');
            $type = $inputs['type'];
            $userIds = [];
                if ($type == 'hospital'){
                    $entityType = EntityTypes::HOSPITAL;
                    $userIds = UserEntityRelation::where('entity_type_id',EntityTypes::HOSPITAL)->pluck('user_id');
                } else {
                    $entityType = EntityTypes::SHOP;
                    $userIds = UserEntityRelation::where('entity_type_id',EntityTypes::SHOP)->pluck('user_id');
                }

                foreach($userIds as $uId) {
                    $user_detail = UserDetail::where('user_id', $uId)->first();
                    $language_id = $user_detail->language_id;
                    $key = Notice::ADMIN_SETTING_CHANGE_NOTIFICATION.'_'.$language_id;

                    $notice = Notice::create([
                        'notify_type' => Notice::ADMIN_SETTING_CHANGE_NOTIFICATION,
                        'user_id' => $uId,
                        'to_user_id' => $uId,
                        'entity_type_id' => $entityType,
                    ]);

                    $devices = UserDevices::whereIn('user_id', [$uId])->pluck('device_token')->toArray();
                    $format = __("notice.$key");
                    $title_msg = '';
                    $notify_type = Notice::ADMIN_SETTING_CHANGE_NOTIFICATION;

                    $notificationData = [];
                    if (count($devices) > 0) {
                        $result = $this->sentPushNotification($devices,$title_msg, $format, $notificationData ,$uId);
                    }
                }
            Log::info('End custom setting change send notification');
            return $this->sendSuccessResponse('Notication send successfully.', 200);
        } catch (\Exception $ex) {
            Log::info('Exception in custom setting change send notification ' . $entityType . ' :');
            Log::info($ex);
            return $this->sendFailedResponse('Failed to send notification. Please try again.', 400);
        }
    }

    public function editLimitCustomLanguage($id)
    {
        $title = "Edit Limit Custom Settings";
        $settings = Config::find($id);
        $postLanguages = PostLanguage::whereNotIn('id', [4])->get();
        $configLanguages = ConfigLanguages::where('config_id',$id)->pluck('value','language_id')->toArray();

        return view('admin.important-setting.config-language', compact('title', 'id', 'postLanguages', 'configLanguages', 'settings'));
    }

    public function updateLimitCustomLanguage(Request $request, $id){
        try {
            DB::beginTransaction();
            $inputs = $request->all();

            $configValue = $inputs['value'];
            $configLanguageValue = $inputs['config_value'];

            $updatePlan = Config::find($id);
            $updatePlan->value = $configValue;
            $updatePlan->save();

            if ($updatePlan->wasChanged()) {
                $logData = [
                    'activity_type' => ManagerActivityLogs::EDIT_SETTINGS,
                    'user_id' => auth()->user()->id,
                    'value' => $configValue,
                    'entity_id' => $id,
                ];
                $this->addManagerActivityLogs($logData);
            }


            if(!empty($configLanguageValue)){
                foreach($configLanguageValue as $key => $value) {
                    if(!empty($value)){
                        $config = ConfigLanguages::updateOrCreate([
                            'config_id'   => $id,
                            'language_id'   => $key,
                        ],[
                            'value' => $value,
                        ]);

                        if ($config->wasChanged() || $config->wasRecentlyCreated) {
                            $logData = [
                                'activity_type' => ManagerActivityLogs::EDIT_LANGUAGE_SETTINGS,
                                'user_id' => auth()->user()->id,
                                'value' => $value,
                                'entity_id' => $config->id,
                            ];
                            $this->addManagerActivityLogs($logData);
                        }
                    }
                }
            }

            DB::commit();
            notify()->success("limit custom setting updated successfully", "Success", "topRight");
            return redirect()->route('admin.important-setting.limit-custom.index');
        }catch (\Exception $e) {
            notify()->error("Category ". trans("messages.update-error"), "Error", "topRight");
            return redirect()->route('admin.important-setting.limit-custom.index');
        }
    }

    /* ================ Limit Custom Code End ======================= */

    /* ================ Show - hide  Start ======================= */
    public function indexShowHide()
    {
        $title = 'Show & Hide settings';
        return view('admin.important-setting.index-show-hide', compact('title'));
    }

    public function getJsonShowHideData(Request $request)
    {
        try {
            Log::info('Start important setting links');
            $user = Auth::user();
            $columns = array(
                0 => 'key',
                1 => 'value',
            );
            $limit = $request->input('length');
            $start = $request->input('start');
            $search = $request->input('search.value');
            $draw = $request->input('draw');
            $order = $columns[$request->input('order.0.column')];
            $dir = $request->input('order.0.dir');
            $query = Config::where('is_show_hide',1)->select('*');
            $totalData = $query->count();
            $totalFiltered = $totalData;
            if (!empty($search)) {
                $query = $query->where(function($q) use ($search){
                    $q->where('key', 'LIKE', "%{$search}%")
                    ->orWhere('value', 'LIKE', "%{$search}%");
                });

                $totalFiltered = $query->count();
            }

            if($order == 'key' && $dir == 'asc'){
                $order = 'sort_order';
            }
            $shops = $query->offset($start)
            ->limit($limit)
            ->orderBy($order, $dir)
            ->get();

            $data = array();
            $categoryOption = DB::table('config')->where('key',Config::ONLY_SHOP_MODE)->first();
            if (!empty($shops)) {
                foreach ($shops as $value) {
                    $nestedData = [];
                    $key = $value['key'];
                    $id = $value['id'];
                    $nestedData['field'] = $key;
                    $nestedData['figure'] = !empty($value['value']) ? $value['value'] : '0';

                    if($categoryOption->id == $value['id']){
                        $edit = route('admin.important-setting.show-hide.edit', ['id' => $id,'type' => 'category']);
                    }else {
                        $edit = route('admin.important-setting.show-hide.edit', $id);
                    }
                    $editPostButton = "<a href='".$edit."' role='button' class='btn btn-primary btn-sm mx-1' data-toggle='tooltip' data-original-title='Edit'><i class='fa fa-edit'></a>";
                    $nestedData['actions'] = $editPostButton;

                    $data[] = $nestedData;
                }
            }
            $jsonData = array(
                "draw" => intval($draw),
                "recordsTotal" => intval($totalData),
                "recordsFiltered" => intval($totalFiltered),
                "data" => $data
            );
            Log::info('End important setting shop');
            return response()->json($jsonData);
        } catch (\Exception $ex) {
            Log::info('Exception important setting shop');
            Log::info($ex);
            return response()->json([]);
        }
    }

    public function editShowHide($id,$type='',$country = '')
    {
        $title = "Edit Show hide Settings";
        $settings = DB::table('config')->whereId($id)->first();
        $countries = Country::leftjoin('config_country_details', function ($join) use($id) {
                            $join->on('config_country_details.country_code', '=', 'countries.code')
                                ->where('config_country_details.config_id',$id);
                        })
                        ->WhereNotNull('countries.code')
                        ->orderBy('priority')
                        ->where('countries.is_show',1)
                        ->select(
                            'countries.*',
                            DB::raw('(CASE
                                WHEN config_country_details.id IS NOT NULL THEN 1
                                ELSE 0
                            END) AS is_saved')
                        )
                        ->get();

        if($type == 'category'){
            if($country) {
                $countryData = ConfigCountryDetail::where('config_id', $id)->where('country_code', $country)->first();
                $settings->value = $countryData ? $countryData->value : $settings->value;
            }
            return view('admin.important-setting.edit-show-hide-setting-category', compact('title','settings','countries','country'));
        }else{
            return view('admin.important-setting.edit-show-hide-setting', compact('title','settings','countries'));
        }
    }

    public function getCategoryCountry(Request $request)
    {
        $inputs = $request->all();
        $id = $inputs['id'] ?? '';
        $country_code = $inputs['country_code'] ?? '';

        if(!empty($country_code)) {
            $data = ConfigCountryDetail::where('config_id', $id)->where('country_code', $country_code)->first();
        }else{
            $data = Config::whereId($id)->first();
        }

        $jsonData = array(
            'success' => true,
            'value' => $data ? (int)$data->value : 0
        );
        return response()->json($jsonData);

    }

    public function saveCategoryCountry(Request $request)
    {
        $inputs = $request->all();
        $id = $inputs['id'] ?? '';
        $country_code = $inputs['country_code'] ?? '';
        try{
            $value = $inputs['value'] ?? 0;

            if(!empty($id)){
                if(!empty($country_code)){
                    ConfigCountryDetail::updateOrCreate([
                        'config_id' => $id,
                        'country_code' => $country_code
                    ],[
                        'value' => $value
                    ]);
                }else{
                    $updatePlan = Config::find($id);
                    $updatePlan->value = $value ?? '';
                    $updatePlan->save();
                }
            }
            notify()->success("Show Hide setting updated successfully", "Success", "topRight");
            return redirect()->route('admin.important-setting.show-hide.edit', ['id' => $id,'type' => 'category','country' => $country_code]);
        } catch (\Exception $e){
            notify()->error("Category ". trans("messages.update-error"), "Error", "topRight");
            return redirect()->route('admin.important-setting.show-hide.edit', ['id' => $id,'type' => 'category','country' => $country_code]);
        }
    }

    public function updateShowHide(Request $request, $id){
        try {
            DB::beginTransaction();
            $inputs = $request->all();

            $value = $inputs['value'];
            $updatePlan = Config::find($id);
            $updatePlan->value = $value ?? '';
            $updatePlan->save();


            if ($updatePlan->wasChanged()) {
                $logData = [
                    'activity_type' => ManagerActivityLogs::EDIT_SETTINGS,
                    'user_id' => auth()->user()->id,
                    'value' => $inputs["value"],
                    'entity_id' => $id,
                ];
                $this->addManagerActivityLogs($logData);
            }

            DB::commit();
            notify()->success("Show Hide setting updated successfully", "Success", "topRight");
            return redirect()->route('admin.important-setting.show-hide.index');
        }catch (\Exception $e) {
            notify()->error("Category ". trans("messages.update-error"), "Error", "topRight");
            return redirect()->route('admin.important-setting.show-hide.index');
        }
    }

    public function updateCountryDetail(Request $request, $id){
        try {
            DB::beginTransaction();
            $inputs = $request->all();

            $value = $inputs['value'];
            $country_id = $inputs['country_id'];
            $country_number = $inputs['country_number'];
            $updatePlan = Config::find($id);

            $countryDetail = ($value == 'Fixed') ? "|$country_id|$country_number" : '';
            $updatePlan->value = $value.$countryDetail ?? '';
            $updatePlan->save();


            if ($updatePlan->wasChanged()) {
                $logData = [
                    'activity_type' => ManagerActivityLogs::EDIT_SETTINGS,
                    'user_id' => auth()->user()->id,
                    'value' => $inputs["value"],
                    'entity_id' => $id,
                ];
                $this->addManagerActivityLogs($logData);
            }

            DB::commit();
            notify()->success("Show Hide setting updated successfully", "Success", "topRight");
            return redirect()->route('admin.important-setting.show-hide.index');
        }catch (\Exception $e) {
            notify()->error("Category ". trans("messages.update-error"), "Error", "topRight");
            return redirect()->route('admin.important-setting.show-hide.index');
        }
    }

    public function viewAppVersion()
    {
        $title = "App Versions";
        //$settings = GeneralSettings::whereIn('key',[GeneralSettings::IOS_APP_VERSION,GeneralSettings::ANDROID_APP_VERSION])->get();
        $settings = GeneralSettings::whereIn('key',[GeneralSettings::IOS_APP_VERSION,GeneralSettings::DISPLAY_APP_VERSION])->get();
        return view('admin.important-setting.app-version-view', compact('title','settings'));
    }

    public function updateSettings(Request $request){
        try {
            DB::beginTransaction();
            $inputs = $request->all();

            $excludeKey = ['_token'];
            foreach ($inputs as $key => $value){
                if(!in_array($key,$excludeKey)){
                    GeneralSettings::where('key',$key)->update(['value' => $value]);
                }
            }
            DB::commit();
            notify()->success("Setting updated successfully", "Success", "topRight");
            return redirect()->route('admin.important-setting.app-version.index');
        }catch (\Exception $e) {
            notify()->error("Setting ". trans("messages.update-error"), "Error", "topRight");
            return redirect()->route('admin.important-setting.app-version.index');
        }
    }

    public function indexInstagram()
    {
        $title = 'Instagram settings';
        $selected = Config::where('key',Config::INSTA_CRON_TIME)->first();
        $selectedVal = $selected->value ?? 'hourly';
        $times = LinkedSocialProfile::CRON_TIME;
        return view('admin.important-setting.index-instagram', compact('title','times','selectedVal'));
    }

    public function saveInstagram(Request $request)
    {
        $inputs = $request->all();

        try {
            $value = $inputs['instagram_sync_time'] ?? 'hourly';
            Config::where('key',Config::INSTA_CRON_TIME)->update(['value' => $value]);
            notify()->success("Instagram Setting " . trans("messages.update-success"), "Success", "topRight");
            return redirect()->route('admin.important-setting.instagram-settings');

        } catch (\Exception $e) {
            Log::info($e);
            DB::rollBack();
            notify()->error("Instagram Setting " . trans("messages.update-error"), "Error", "topRight");
            return redirect()->route('admin.important-setting.instagram-settings');
        }
    }

    public function indexPaypleSetting(Request $request)
    {
        $title = 'Payple Setting';
        return view('admin.important-setting.index-payple-settings', compact('title'));
    }

    public function getJsonPaypleData(Request $request)
    {
        try {
            $columns = array(
                0 => 'key',
                1 => 'value',
            );
            $limit = $request->input('length');
            $start = $request->input('start');
            $search = $request->input('search.value');
            $draw = $request->input('draw');
            $order = $columns[$request->input('order.0.column')];
            $dir = $request->input('order.0.dir');
            $query = Config::whereIn('key',Config::PAYPLE_FIELDS)->select('*');
            $totalData = $query->count();
            $totalFiltered = $totalData;
            if (!empty($search)) {
                $query = $query->where(function($q) use ($search){
                    $q->where('key', 'LIKE', "%{$search}%")
                    ->orWhere('value', 'LIKE', "%{$search}%");
                });

                $totalFiltered = $query->count();
            }
            $shops = $query->offset($start)
                ->limit($limit)
                ->orderBy($order, $dir)
                ->get();

            $data = array();
            if (!empty($shops)) {
                foreach ($shops as $value) {
                    $id = $value['id'];
                    $formatted_value = $value['formatted_value'];
                    $nestedData = [];
                    $nestedData['field'] = $value['key'];
                    $nestedData['figure'] = $formatted_value;
                    $edit = route('admin.important-setting.limit-custom.edit', $id);
                    $editPostButton = "<a href='".$edit."' role='button' class='btn btn-primary btn-sm mx-1' data-toggle='tooltip' data-original-title='Edit'><i class='fa fa-edit'></a>";
                    $nestedData['actions'] = $editPostButton;

                    $data[] = $nestedData;
                }
            }
            $jsonData = array(
                "draw" => intval($draw),
                "recordsTotal" => intval($totalData),
                "recordsFiltered" => intval($totalFiltered),
                "data" => $data
            );
            return response()->json($jsonData);
        } catch (\Exception $ex) {
            Log::info($ex);
            return response()->json([]);
        }
    }
    /* ===============================================================*/

}
