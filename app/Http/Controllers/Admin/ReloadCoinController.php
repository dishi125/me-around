<?php

namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use App\Models\Status;
use App\Models\User;
use App\Models\EntityTypes;
use App\Models\UserEntityRelation;
use App\Models\Hospital;
use App\Models\Shop;
use App\Models\Post;
use App\Models\UserCredit;
use App\Models\UserCreditHistory;
use App\Models\UserDetail;
use App\Models\Country;
use App\Models\Notice;
use App\Models\CreditPlans;
use App\Models\CategoryTypes;
use App\Models\ReloadCoinRequest;
use App\Models\UserDevices;
use App\Models\ManagerActivityLogs;
use Illuminate\Support\Facades\DB;
use Log;
use Carbon\Carbon;
use App\Util\Firebase;
use Illuminate\Support\Facades\Lang;
use App\Models\Manager;
use Illuminate\Support\Facades\Auth;

class ReloadCoinController extends Controller
{
    protected $firebase;

    public function __construct()
    {
        $this->firebase = new Firebase();
        $this->middleware('permission:reload-coin-list', ['only' => ['index']]);
    }

/* ================ Hospital Code Start ======================= */
    public function index(Request $request)
    {
        $title = 'Reload Coins';

        DB::table('reload_coins_request')
            ->where('is_admin_read',1)
            ->update(['is_admin_read' => 0]);

        $countryId = $request->has('countryId') ? $request->countryId : 0;
        $countries = Country::WhereNotNull('code')->where('is_show',1)->get();

        return view('admin.reload-coin.index', compact('title','countries','countryId'));
    }

    public function getJsonAllData(Request $request,$countryId)
    {
        $adminTimezone = $this->getAdminUserTimezone();
        try {
            Log::info('Start all reload coin list');
            $columns = array(
                0 => 'id',
                1 => 'activate_name',
                2 => 'category_name',
                3 => 'coin_amount',
                4 => 'total_amount',
                5 => 'order_number',
                6 => 'sender_name',
                7 => 'phone_number',
                8 => 'manager_name',
                9 => 'created_at',
                10 => 'action',
            );
            $limit = $request->input('length');
            $start = $request->input('start');
            $search = $request->input('search.value');
            $draw = $request->input('draw');
            $order = $columns[$request->input('order.0.column')];
            $dir = $request->input('order.0.dir');

            $query = ReloadCoinRequest::select('reload_coins_request.*');

            $totalData = $query->count();
            $totalFiltered = $totalData;

            if (!empty($search)) {
                $query = $query->where(function($q) use ($search){
                    $q->where('coin_amount', 'LIKE', "%{$search}%")
                    ->orWhere('total_amount', 'LIKE', "%{$search}%")
                    ->orWhere('order_number', 'LIKE', "%{$search}%")
                    ->orWhere('sender_name', 'LIKE', "%{$search}%");
                });

                $totalFiltered = $query->count();
            }

            $all_data = $query->offset($start)
                ->limit($limit)
                ->orderBy($order, $dir)
                ->get();

            $data = array();
            if (!empty($all_data)) {
                if($countryId){
                    foreach($all_data as $key => $value) {
                        if($countryId != $value['country_code']) {
                            unset($all_data[$key]);
                        }
                    }
                }
                foreach ($all_data as $value) {
                    $created_at = Carbon::parse($value['created_at'])->format("Y-m-d H:i:s");

                    $id = $value['id'];
                    $nestedData['checkbox'] = "<td><div class=\"custom-checkbox custom-control\">
                    <input type=\"checkbox\" data-checkboxes=\"mygroup\" class=\"custom-control-input checkbox_id check-all-hospital\" id=\"$id\" data-id=\"$id\" value=\"$id\" name=\"checkbox_id[]\"><label for=\"$id\" class=\"custom-control-label\">&nbsp;</label></div></td>";

                    $nestedData['activate_name'] = $value['activate_name'];
                    $nestedData['category_name'] = $value['category_name'];
                    $nestedData['coin_amount'] = number_format($value['coin_amount']);
                    $nestedData['total_amount'] = $value['total_amount']." ".$value['currency_name'];
                    $nestedData['order_number'] = $value['order_number'];
                    $nestedData['sender_name'] = $value['sender_name'];
                    $nestedData['phone_number'] = $value['phone_number'];
                    $nestedData['manager'] = $value['manager_name'];
                    //$nestedData['date'] = $value['created_at'];
                    $dateShow = Carbon::createFromFormat('Y-m-d H:i:s',$created_at, "UTC")->setTimezone($adminTimezone)->toDateTimeString();
                    $nestedData['date'] = Carbon::parse($dateShow)->format('d-m-Y H:i');

                    $status = '';
                    if ($value['status'] == ReloadCoinRequest::GIVE_COIN) {
                        $status = '<span class="badge badge-success">&nbsp;</span>';
                    } else if ($value['status'] == ReloadCoinRequest::REJECT_COIN) {
                        $status = '<span class="badge badge-danger">&nbsp;</span>';
                    }

                    $disabled = $value['status'] == ReloadCoinRequest::REQUEST_COIN ? '' : 'disabled';

                    $giveCoinButton = "<button onclick='giveCoin(" . $id . ")' class='btn btn-primary ".$disabled." btn-sm mr-2' ".$disabled.">Give Coin</button>";
                    $rejectCoinButton = "<button onclick='rejectCoin(" . $id . ")' class='btn btn-primary ".$disabled." btn-sm mr-2' ".$disabled.">Reject Coin</button>";
                    $nestedData['actions'] = "<div class='d-flex'>$giveCoinButton $rejectCoinButton $status</div>";
                    $data[] = $nestedData;
                }
            }
            $jsonData = array(
                "draw" => intval($draw),
                "recordsTotal" => intval($totalData),
                "recordsFiltered" => intval($totalFiltered),
                "data" => $data,
                "Timezone" => $adminTimezone,
            );
            Log::info('End all reload coin list');
            return response()->json($jsonData);
        } catch (\Exception $ex) {
            Log::info('Exception all reload coin list');
            Log::info($ex);
            return response()->json([]);
        }
    }
    public function getJsonAllShopData(Request $request,$countryId)
    {
        $adminTimezone = $this->getAdminUserTimezone();
        try {
            Log::info('Start all shop reload coin list');
            $columns = array(
                0 => 'id',
                1 => 'activate_name',
                2 => 'category_name',
                3 => 'coin_amount',
                4 => 'total_amount',
                5 => 'order_number',
                6 => 'sender_name',
                7 => 'phone_number',
                8 => 'manager_name',
                9 => 'created_at',
                10 => 'action',
            );
            $limit = $request->input('length');
            $start = $request->input('start');
            $search = $request->input('search.value');
            $draw = $request->input('draw');
            $order = $columns[$request->input('order.0.column')];
            $dir = $request->input('order.0.dir');
            $query = ReloadCoinRequest::join('users','reload_coins_request.user_id','users.id')
                ->join('shops', function($query) {
                    $query->on('users.id','=','shops.user_id')
                    ->whereRaw('shops.id IN (select MAX(a2.id) from shops as a2 group by a2.user_id)');
                })
                ->select('reload_coins_request.*');

            $totalData = $query->count();
            $totalFiltered = $totalData;

            if (!empty($search)) {
                $query = $query->where(function($q) use ($search){
                    $q->where('coin_amount', 'LIKE', "%{$search}%")
                    ->orWhere('total_amount', 'LIKE', "%{$search}%")
                    ->orWhere('order_number', 'LIKE', "%{$search}%")
                    ->orWhere('sender_name', 'LIKE', "%{$search}%");
                });

                $totalFiltered = $query->count();
            }

            $shop_data = $query->offset($start)
                ->limit($limit)
                ->orderBy($order, $dir)
                ->get();


            $data = array();
            if (!empty($shop_data)) {
                if($countryId){
                    foreach($shop_data as $key => $value) {
                        if($countryId != $value['country_code']) {
                            unset($shop_data[$key]);
                        }
                    }
                }
                foreach ($shop_data as $value) {
                    $created_at = Carbon::parse($value['created_at'])->format("Y-m-d H:i:s");
                    $id = $value['id'];
                    $nestedData['checkbox'] = "<td><div class=\"custom-checkbox custom-control\">
                    <input type=\"checkbox\" data-checkboxes=\"mygroup\" class=\"custom-control-input checkbox_id check-all-hospital\" id=\"$id\" data-id=\"$id\" value=\"$id\" name=\"checkbox_id[]\"><label for=\"$id\" class=\"custom-control-label\">&nbsp;</label></div></td>";

                    $nestedData['activate_name'] = $value['activate_name'];
                    $nestedData['category_name'] = $value['category_name'];
                    $nestedData['coin_amount'] = number_format($value['coin_amount']);
                    $nestedData['total_amount'] = $value['total_amount']." ".$value['currency_name'];
                    $nestedData['order_number'] = $value['order_number'];
                    $nestedData['sender_name'] = $value['sender_name'];
                    $nestedData['phone_number'] = $value['phone_number'];
                    $nestedData['manager'] = $value['manager_name'];
                    //$nestedData['date'] = $value['created_at'];
                    $dateShow = Carbon::createFromFormat('Y-m-d H:i:s',$created_at, "UTC")->setTimezone($adminTimezone)->toDateTimeString();
                    $nestedData['date'] = Carbon::parse($dateShow)->format('d-m-Y H:i');

                    $status = '';
                    if ($value['status'] == ReloadCoinRequest::GIVE_COIN) {
                        $status = '<span class="badge badge-success">&nbsp;</span>';
                    } else if ($value['status'] == ReloadCoinRequest::REJECT_COIN) {
                        $status = '<span class="badge badge-danger">&nbsp;</span>';
                    }

                    $disabled = $value['status'] == ReloadCoinRequest::REQUEST_COIN ? '' : 'disabled';

                    $giveCoinButton = "<button onclick='giveCoin(" . $id . ")' class='btn btn-primary ".$disabled." btn-sm mr-2' ".$disabled.">Give Coin</button>";
                    $rejectCoinButton = "<button onclick='rejectCoin(" . $id . ")' class='btn btn-primary ".$disabled." btn-sm mr-2' ".$disabled.">Reject Coin</button>";
                    $nestedData['actions'] = "<div class='d-flex'>$giveCoinButton $rejectCoinButton $status</div>";
                    $data[] = $nestedData;
                }
            }
            $jsonData = array(
                "draw" => intval($draw),
                "recordsTotal" => intval($totalData),
                "recordsFiltered" => intval($totalFiltered),
                "data" => $data
            );
            Log::info('End all shop reload coin list');
            return response()->json($jsonData);
        } catch (\Exception $ex) {
            Log::info('Exception all shop reload coin list');
            Log::info($ex);
            return response()->json([]);
        }
    }
    public function getJsonAllHospitalData(Request $request,$countryId)
    {
        $adminTimezone = $this->getAdminUserTimezone();
        try {
            Log::info('Start all hospital reload coin list');
            $columns = array(
                0 => 'id',
                1 => 'activate_name',
                2 => 'category_name',
                3 => 'coin_amount',
                4 => 'total_amount',
                5 => 'order_number',
                6 => 'sender_name',
                7 => 'phone_number',
                8 => 'manager_name',
                9 => 'created_at',
                10 => 'action',
            );
            $limit = $request->input('length');
            $start = $request->input('start');
            $search = $request->input('search.value');
            $draw = $request->input('draw');
            $order = $columns[$request->input('order.0.column')];
            $dir = $request->input('order.0.dir');
            $query = ReloadCoinRequest::join('users','reload_coins_request.user_id','users.id')
                ->join('user_entity_relation','user_entity_relation.user_id','users.id')
                ->join('hospitals','user_entity_relation.entity_id','hospitals.id')
                ->where('user_entity_relation.entity_type_id', EntityTypes::HOSPITAL)
                ->select('reload_coins_request.*');

            $totalData = $query->count();
            $totalFiltered = $totalData;

            if (!empty($search)) {
                $query = $query->where(function($q) use ($search){
                    $q->where('coin_amount', 'LIKE', "%{$search}%")
                    ->orWhere('total_amount', 'LIKE', "%{$search}%")
                    ->orWhere('order_number', 'LIKE', "%{$search}%")
                    ->orWhere('sender_name', 'LIKE', "%{$search}%");
                });

                $totalFiltered = $query->count();
            }

            $hospital_data = $query->offset($start)
                ->limit($limit)
                ->orderBy($order, $dir)
                ->get();

            $data = array();
            if (!empty($hospital_data)) {
                if($countryId){
                    foreach($hospital_data as $key => $value) {
                        if($countryId != $value['country_code']) {
                            unset($hospital_data[$key]);
                        }
                    }
                }
                foreach ($hospital_data as $value) {
                    $created_at = Carbon::parse($value['created_at'])->format("Y-m-d H:i:s");
                    $id = $value['id'];
                    $nestedData['checkbox'] = "<td><div class=\"custom-checkbox custom-control\">
                    <input type=\"checkbox\" data-checkboxes=\"mygroup\" class=\"custom-control-input checkbox_id check-all-hospital\" id=\"$id\" data-id=\"$id\" value=\"$id\" name=\"checkbox_id[]\"><label for=\"$id\" class=\"custom-control-label\">&nbsp;</label></div></td>";

                    $nestedData['activate_name'] = $value['activate_name'];
                    $nestedData['category_name'] = $value['category_name'];
                    $nestedData['coin_amount'] = number_format($value['coin_amount']);
                    $nestedData['total_amount'] = $value['total_amount']."".$value['currency_name'];
                    $nestedData['order_number'] = $value['order_number'];
                    $nestedData['sender_name'] = $value['sender_name'];
                    $nestedData['phone_number'] = $value['phone_number'];
                    $nestedData['manager'] = $value['manager_name'];
                    //$nestedData['date'] = $value['created_at'];
                    $dateShow = Carbon::createFromFormat('Y-m-d H:i:s',$created_at, "UTC")->setTimezone($adminTimezone)->toDateTimeString();
                    $nestedData['date'] = Carbon::parse($dateShow)->format('d-m-Y H:i');

                    $status = '';
                    if ($value['status'] == ReloadCoinRequest::GIVE_COIN) {
                        $status = '<span class="badge badge-success">&nbsp;</span>';
                    } else if ($value['status'] == ReloadCoinRequest::REJECT_COIN) {
                        $status = '<span class="badge badge-danger">&nbsp;</span>';
                    }

                    $disabled = $value['status'] == ReloadCoinRequest::REQUEST_COIN ? '' : 'disabled';

                    $giveCoinButton = "<button onclick='giveCoin(" . $id . ")' class='btn btn-primary ".$disabled." btn-sm mr-2' ".$disabled.">Give Coin</button>";
                    $rejectCoinButton = "<button onclick='rejectCoin(" . $id . ")' class='btn btn-primary ".$disabled." btn-sm mr-2' ".$disabled.">Reject Coin</button>";
                    $nestedData['actions'] = "<div class='d-flex'>$giveCoinButton $rejectCoinButton $status</div>";
                    $data[] = $nestedData;
                }
            }
            $jsonData = array(
                "draw" => intval($draw),
                "recordsTotal" => intval($totalData),
                "recordsFiltered" => intval($totalFiltered),
                "data" => $data
            );
            Log::info('End all hospital reload coin list');
            return response()->json($jsonData);
        } catch (\Exception $ex) {
            Log::info('Exception all hospital reload coin list');
            Log::info($ex);
            return response()->json([]);
        }
    }

    public function giveCoinPopup($id)
    {
        return view('admin.reload-coin.give-coins', compact('id'));
    }

    public function giveCoins($id)
    {
        try {
            Log::info('Give reload coin start.');
            DB::beginTransaction();
            $reloadCoin = ReloadCoinRequest::find($id);
            $userCredits = UserCredit::where('user_id',$reloadCoin->user_id)->first();
            $old_credit = $userCredits->credits;
            $newCredits = $old_credit + $reloadCoin->coin_amount;
            $userCredits = UserCredit::where('user_id',$reloadCoin->user_id)->update(['credits' => $newCredits]);
            UserCreditHistory::create([
                'user_id' => $reloadCoin->user_id,
                'amount' => $reloadCoin->coin_amount,
                'total_amount' => $newCredits,
                'transaction' => 'credit',
                'type' => UserCreditHistory::RELOAD
            ]);
            ReloadCoinRequest::where('id',$id)->update(['status' => ReloadCoinRequest::GIVE_COIN]);

            $notice = Notice::create([
                'notify_type' => Notice::RELOAD_COIN_REQUEST_ACCEPTED,
                'user_id' => $reloadCoin->user_id,
                'to_user_id' => $reloadCoin->user_id,
                'entity_id' => $reloadCoin->id,
                'title' => number_format($reloadCoin->coin_amount),
            ]);

            $user_detail = DB::table('users_detail')->where('user_id', $reloadCoin->user_id)->first();
            $user_entity_relation = UserEntityRelation::where('user_id',$reloadCoin->user_id)
                                                        ->whereIn('entity_type_id',[EntityTypes::SHOP,EntityTypes::HOSPITAL])
                                                        ->first();


            $dt = Carbon::now();
            if($user_entity_relation->entity_type_id == EntityTypes::HOSPITAL) {
                $hospital = Hospital::find($user_entity_relation->entity_id);
                $creditPlan = CreditPlans::where('entity_type_id', EntityTypes::HOSPITAL)->where('package_plan_id', $user_detail->package_plan_id)->first();
                $defaultCredit = $creditPlan ? $creditPlan->amount : 0;
                $minHospitalCredit = $defaultCredit; // Remove * 2

                $userCredits = UserCredit::where('user_id',$hospital->user_id)->first();

                //  Check expire date and have enough coin
                if($hospital && $hospital->deactivate_by_user == 0 && Carbon::now()->gte(Carbon::parse($user_detail->plan_expire_date)) && $userCredits->credits > $minHospitalCredit){

                    UserCreditHistory::create([
                        'user_id' => $reloadCoin->user_id,
                        'amount' => $minHospitalCredit,
                        'total_amount' => ($userCredits->credits - $minHospitalCredit),
                        'transaction' => 'debit',
                        'type' => UserCreditHistory::REGULAR
                    ]);

                    Notice::create([
                        'notify_type' => Notice::MONTHLY_COIN_DEDUCT,
                        'user_id' => $reloadCoin->user_id,
                        'to_user_id' => $reloadCoin->user_id,
                        'entity_type_id' => EntityTypes::HOSPITAL,
                        'title' => $creditPlan->package_plan_name,
                        'sub_title' =>  number_format((float)$minHospitalCredit)
                    ]);

                    Hospital::where('id',$hospital->id)->update(['credit_deduct_date' => $dt->toDateString(),'status_id' => Status::ACTIVE]);
                    UserDetail::where('id',$user_detail->id)->update(['plan_expire_date' => Carbon::now()->addDays(30)]);

                }

                $userCredits = UserCredit::where('user_id',$hospital->user_id)->first();
                if($userCredits->credits > $minHospitalCredit && $hospital->deactivate_by_user == 0){
                    Hospital::where('id',$hospital->id)->update(['status_id' => Status::ACTIVE]);
                    Post::where('hospital_id',$hospital->id)->where('status_id',Status::INACTIVE)->update(['status_id' => Status::ACTIVE]);
                }

            }else{
                $creditPlan = CreditPlans::where('entity_type_id', EntityTypes::SHOP)->where('package_plan_id', $user_detail->package_plan_id)->first();
                $total_user_shops = UserEntityRelation::where('entity_type_id', EntityTypes::SHOP)->where('user_id',$reloadCoin->user_id)->count();
                $minShopCredit = $creditPlan ? ($creditPlan->amount * $total_user_shops) : 0;  // Remove * 2
                $userCredits = UserCredit::where('user_id',$reloadCoin->user_id)->first();


                $active_shop_count = UserEntityRelation::join('shops','shops.id','user_entity_relation.entity_id')
                                            ->where('entity_type_id', EntityTypes::SHOP)
                                            ->where('user_entity_relation.user_id',$reloadCoin->user_id)
                                            ->whereIn('shops.status_id',[Status::ACTIVE,Status::PENDING,Status::INACTIVE])
                                            ->where('deactivate_by_user',0)
                                            ->count();
                $minShopCredit = $creditPlan ? ($creditPlan->amount * $active_shop_count ) : 0;
                if( Carbon::now()->gte(Carbon::parse($user_detail->plan_expire_date))  && $userCredits->credits > $minShopCredit) {

                    UserCreditHistory::create([
                        'user_id' => $reloadCoin->user_id,
                        'amount' => $minShopCredit,
                        'total_amount' => ($userCredits->credits - $minShopCredit),
                        'transaction' => 'debit',
                        'type' => UserCreditHistory::REGULAR
                    ]);

                    Notice::create([
                        'notify_type' => Notice::MONTHLY_COIN_DEDUCT,
                        'user_id' => $reloadCoin->user_id,
                        'to_user_id' => $reloadCoin->user_id,
                        'entity_type_id' => EntityTypes::SHOP,
                        'title' => $creditPlan->package_plan_name,
                        'sub_title' =>  number_format((float)$minShopCredit)
                    ]);

                    Shop::whereIn('status_id',[Status::ACTIVE,Status::PENDING,Status::INACTIVE])
                        ->where('user_id',$reloadCoin->user_id)
                        ->where('deactivate_by_user',0)
                        ->update(['credit_deduct_date' => $dt->toDateString(),'status_id' => Status::ACTIVE]);
                    UserDetail::where('id',$user_detail->id)->update(['plan_expire_date' => Carbon::now()->addDays(30)]);
                }

                $userCredits = UserCredit::where('user_id',$reloadCoin->user_id)->first();
                if($userCredits->credits > $minShopCredit){
                    Shop::whereIn('status_id',[Status::PENDING,Status::INACTIVE])
                        ->where('user_id',$reloadCoin->user_id)
                        ->where('deactivate_by_user',0)
                        ->update(['status_id' => Status::ACTIVE]);
                }
            }

            /* if($user_entity_relation->entity_type_id == EntityTypes::HOSPITAL) {
                $hospital = Hospital::find($user_entity_relation->entity_id);
                if($hospital && $hospital->status_id == Status::INACTIVE && $hospital->deactivate_by_user == 0) {
                    $creditPlan = CreditPlans::where('entity_type_id', EntityTypes::HOSPITAL)->where('package_plan_id', $user_detail->package_plan_id)->first();
                    $minHospitalCredit = $creditPlan ? ($creditPlan->amount * 2) : 0;
                    $userCredits = UserCredit::where('user_id',$hospital->user_id)->first();
                    if($userCredits->credits > $minHospitalCredit) {
                        Hospital::where('id',$hospital->id)->update(['credit_deduct_date' => $dt->toDateString()]);
                        UserDetail::where('id',$user_detail->id)->update(['plan_expire_date' => Carbon::now()->addDays(30)]);
                    }
                }
            }else {
                $shop = Shop::where('status_id',Status::INACTIVE)
                                ->where('user_id',$reloadCoin->user_id)
                                ->where('deactivate_by_user',0)->first();

                if($shop) {
                    $creditPlan = CreditPlans::where('entity_type_id', EntityTypes::SHOP)->where('package_plan_id', $user_detail->package_plan_id)->first();
                    $total_user_shops = UserEntityRelation::where('entity_type_id', EntityTypes::SHOP)->where('user_id',$shop->user_id)->count();
                    $minShopCredit = $creditPlan ? ($creditPlan->amount * $total_user_shops * 2) : 0;
                    $userCredits = UserCredit::where('user_id',$shop->user_id)->first();
                    if($userCredits->credits > $minShopCredit) {
                        Shop::where('status_id',Status::INACTIVE)
                            ->where('user_id',$reloadCoin->user_id)
                            ->where('deactivate_by_user',0)
                            ->update(['credit_deduct_date' => $dt->toDateString()]);
                        UserDetail::where('id',$user_detail->id)->update(['plan_expire_date' => Carbon::now()->addDays(30)]);
                    }
                }

            } */


            $language_id = $user_detail ? $user_detail->language_id : 4;
            $key = Notice::RELOAD_COIN_REQUEST_ACCEPTED.'_'.$language_id;
            $devices = UserDevices::whereIn('user_id', [$reloadCoin->user_id])->pluck('device_token')->toArray();
            $format = __("notice.$key");
            $title_msg = '';
            $notify_type = Notice::RELOAD_COIN_REQUEST_ACCEPTED;

            $notificationData = [];
            if (count($devices) > 0) {
                $result = $this->sentPushNotification($devices,$title_msg, $format, $notificationData ,$reloadCoin->user_id);
            }

            $logData = [
                'activity_type' => ManagerActivityLogs::RELOAD_COIN,
                'user_id' => auth()->user()->id,
                'value' => Lang::get('messages.manager_activity.reload_coin'),
                'entity_id' => $reloadCoin->user_id,
            ];
            $this->addManagerActivityLogs($logData);

            DB::commit();

            Log::info('Give reload coin end.');
            notify()->success("Reload coin request accepted", "Success", "topRight");
            return redirect()->route('admin.reload-coin.index');
        } catch (\Exception $ex) {
            Log::info('Give reload coin exception.');
            Log::info($ex);
            DB::rollBack();
            notify()->error("Failed to accept reload coin request", "Error", "topRight");
            return redirect()->route('admin.reload-coin.index');
        }
    }

    public function rejectCoinPopup($id)
    {
        return view('admin.reload-coin.reject-coins', compact('id'));
    }

    public function rejectCoins($id)
    {
        try {
            Log::info('Reject reload coin start.');
            DB::beginTransaction();
            $reloadCoin = ReloadCoinRequest::find($id);
            ReloadCoinRequest::where('id',$id)->update(['status' => ReloadCoinRequest::REJECT_COIN]);
            $notice = Notice::create([
                'notify_type' => Notice::RELOAD_COIN_REQUEST_REJECTED,
                'user_id' => $reloadCoin->user_id,
                'to_user_id' => $reloadCoin->user_id,
                'entity_id' => $reloadCoin->id,
                'title' => number_format($reloadCoin->coin_amount),
            ]);

            $user_detail = UserDetail::where('user_id', $reloadCoin->user_id)->first();
            $language_id = $user_detail ? $user_detail->language_id : 4;
            $key = Notice::RELOAD_COIN_REQUEST_REJECTED.'_'.$language_id;
            $devices = UserDevices::whereIn('user_id', [$reloadCoin->user_id])->pluck('device_token')->toArray();

            $format = __("notice.$key");
            $title_msg = '';
            $notify_type = Notice::RELOAD_COIN_REQUEST_REJECTED;

            $notificationData = [];
            if (count($devices) > 0) {
                $result = $this->sentPushNotification($devices,$title_msg, $format, $notificationData ,$reloadCoin->user_id);
            }
            $logData = [
                'activity_type' => ManagerActivityLogs::REJECT_COIN,
                'user_id' => auth()->user()->id,
                'value' => Lang::get('messages.manager_activity.reload_coin_reject'),
                'entity_id' => $reloadCoin->user_id,
            ];
            $this->addManagerActivityLogs($logData);

            DB::commit();
            Log::info('Reject reload coin end.');
            notify()->success("Reload coin request rejected", "Success", "topRight");
            return redirect()->route('admin.reload-coin.index');
        } catch (\Exception $ex) {
            Log::info('Reject reload coin exception.');
            Log::info($ex);
            DB::rollBack();
            notify()->error("Failed to reject reload coin request", "Error", "topRight");
            return redirect()->route('admin.reload-coin.index');
        }
    }


    public function showReloadCoinLogs(Request $request){
        $user = Auth::user();
        $manager = Manager::where('user_id',$user->id)->first();
        $manager_id = $manager ? $manager->id : 0;

        $title = 'Reload Coin Logs';

        $totalUsers = UserEntityRelation::distinct('user_id')->count('user_id');;
        $totalShopsQuery = UserEntityRelation::join('users_detail','users_detail.user_id','user_entity_relation.user_id');
        $totalHospitalsQuery = UserEntityRelation::join('users_detail','users_detail.user_id','user_entity_relation.user_id');
        $lastMonthIncomeQuery = ReloadCoinRequest::join('users_detail','users_detail.user_id','reload_coins_request.user_id');
        $totalIncomeQuery = ReloadCoinRequest::join('users_detail','users_detail.user_id','reload_coins_request.user_id');
        $currentMonthIncomeQuery = ReloadCoinRequest::join('users_detail','users_detail.user_id','reload_coins_request.user_id');

        if($manager_id && $manager_id != 0) {
            $totalShopsQuery = $totalShopsQuery->where('users_detail.manager_id',$manager_id);
            $totalHospitalsQuery = $totalHospitalsQuery->where('users_detail.manager_id',$manager_id);
            $lastMonthIncomeQuery = $lastMonthIncomeQuery->where('users_detail.manager_id',$manager_id);
            $totalIncomeQuery = $totalIncomeQuery->where('users_detail.manager_id',$manager_id);
            $currentMonthIncomeQuery = $currentMonthIncomeQuery->where('users_detail.manager_id',$manager_id);
        }

        $totalShops = $totalShopsQuery->where('entity_type_id',EntityTypes::SHOP)->distinct('user_entity_relation.user_id')->count('user_entity_relation.user_id');
        $totalHospitals = $totalHospitalsQuery->where('entity_type_id',EntityTypes::HOSPITAL)->distinct('user_entity_relation.user_id')->count('user_entity_relation.user_id');
        $totalClients = UserEntityRelation::whereIn('entity_type_id',[EntityTypes::HOSPITAL,EntityTypes::SHOP])->distinct('user_id')->count('user_id');
        $dateS = Carbon::now()->startOfMonth()->subMonth(1);
        $dateE = Carbon::now()->startOfMonth();
        $lastMonthIncome = $lastMonthIncomeQuery->where('status',ReloadCoinRequest::GIVE_COIN)->whereBetween('reload_coins_request.created_at',[$dateS,$dateE])
                                ->sum('reload_coins_request.coin_amount');
        $lastMonthIncome = number_format($lastMonthIncome,0);
        $totalIncome = $totalIncomeQuery->where('status',ReloadCoinRequest::GIVE_COIN)
                                ->sum('reload_coins_request.coin_amount');
        $totalIncome = number_format($totalIncome,0);
        $currentMonthIncome = $currentMonthIncomeQuery->where('status',ReloadCoinRequest::GIVE_COIN)->whereMonth('reload_coins_request.created_at',$dateE->month)
                                ->sum('reload_coins_request.coin_amount');
        $currentMonthIncome = number_format($currentMonthIncome,0);

        return view('admin.reload-coin.logs', compact('title','manager_id', 'totalIncome', 'totalUsers','totalShops','totalHospitals','totalClients','lastMonthIncome','currentMonthIncome'));
    }

    public function getReloadCoinJsonData(Request $request){
        $user = Auth::user();
        $manager = Manager::where('user_id',$user->id)->first();
        $manager_id = $manager ? $manager->id : 0;

        $columns = array(
            0 => 'users_detail.name',
            1 => 'shops.main_name',
            2 => 'addresses.address',
            3 => 'users_detail.mobile',
            4 => 'user_credits_history.amount',
            5 => 'user_credits.credits',
            6 => 'user_credits_history.created_at',
        );

        $limit = $request->input('length');
        $start = $request->input('start');
        $draw = $request->input('draw');
        $dir = $request->input('order.0.dir');

        $adminTimezone = $this->getAdminUserTimezone();
        try{
            $order = $columns[$request->input('order.0.column')];

            $query = UserCreditHistory::join('users','users.id','user_credits_history.user_id')
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
                ->where('managers.id',$manager_id)
                ->where('user_credits_history.type',UserCreditHistory::RELOAD)
                ->select(
                    'users.id as user_id',
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
                    'addresses.*',
                    'users_detail.mobile',
                    'user_credits.credits',
                    'user_credits_history.created_at as display_date'
                )
                ->groupBy('user_credits_history.id');

            $totalData = count($query->get());
            $totalFiltered = $totalData;

            $result = $query
                ->offset($start)
                    ->limit($limit)
                    ->orderBy($order, $dir)
                    ->get();

            $data = array();
            if (!empty($result)) {
                foreach ($result as $value) {
                    $id = $value['id'];
                    $entity_type_id_array = explode(",",$value['entity_type_id_array']);
                    $entity_type_id_array = collect($entity_type_id_array)->unique()->values()->toArray();


                    $nestedData['user_name'] = $value->user_name;

                    $nestedData['reload_amount'] = $value->user_name;
                    $nestedData['current_coin'] = $value->user_name;
                    $nestedData['date'] = $value->user_name;

                   if(in_array(EntityTypes::HOSPITAL,$entity_type_id_array)) {
                        $business_name = $value['main_name'];
                    }else {
                        $business_name = $value['main_name'] != "" ? $value['main_name']."/" : "";
                        $business_name .= $value['sub_name'];
                    }
                    $nestedData['activate_name'] = $business_name;


                    $address = $value['address'];
                    $address .= $value['address2'] ? ','.$value['address2'] : '';
                    $address .= $value['city_name'] ? ','.$value['city_name'] : '';
                    $address .= $value['state_name'] ? ','.$value['state_name'] : '';
                    $address .= $value['country_name'] ? ','.$value['country_name'] : '';

                    $nestedData['address'] = $address;
                    $nestedData['phone_number'] = $value['mobile'];

                    $nestedData['reload_amount'] = number_format($value->amount,0);
                    $nestedData['current_coin'] = number_format($value->credits,0);
                    $nestedData['date'] = $this->formatDateTimeCountryWise($value['display_date'],$adminTimezone, 'd-m-Y H:i:s');


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
            $jsonData = array(
                "draw" => intval($draw),
                "recordsTotal" => 0,
                "recordsFiltered" => 0,
                "data" => []
            );
            return response()->json($jsonData);
        }
    }
/* ================ Hospital Code End ======================= */

}
