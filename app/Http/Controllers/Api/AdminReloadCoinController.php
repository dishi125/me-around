<?php

namespace App\Http\Controllers\Api;

use Log;
use Auth;
use Lang;
use Validator;
use Carbon\Carbon;
use App\Models\Shop;
use App\Models\Notice;
use App\Models\Status;
use App\Models\Hospital;
use App\Models\UserCredit;
use App\Models\UserDetail;
use App\Models\CreditPlans;
use App\Models\EntityTypes;
use App\Models\UserDevices;
use Illuminate\Http\Request;
use App\Models\ReloadCoinRequest;
use App\Models\UserCreditHistory;
use App\Models\UserEntityRelation;
use Illuminate\Support\Facades\DB;
use App\Models\ManagerActivityLogs;
use App\Http\Controllers\Controller;

class AdminReloadCoinController extends Controller
{
    public function getReloadCoinRequest(Request $request)
    {
        $inputs = $request->all();
        $user = Auth::user();

        $search = $inputs['search'] ?? '';

        try {
            $query = ReloadCoinRequest::where('reload_coins_request.status', ReloadCoinRequest::REQUEST_COIN);

            if (!empty($search)) {
                $query = $query->where(function ($q) use ($search) {
                    $q->where('reload_coins_request.coin_amount', 'LIKE', "%{$search}%")
                        ->orWhere('reload_coins_request.total_amount', 'LIKE', "%{$search}%")
                        ->orWhere('reload_coins_request.order_number', 'LIKE', "%{$search}%")
                        ->orWhere('reload_coins_request.sender_name', 'LIKE', "%{$search}%");
                });
            }

            if ($user->hasRole('Admin') == false) {
                $query = $query->join('users_detail', 'users_detail.user_id', 'reload_coins_request.user_id')
                    //->leftjoin('managers','managers.user_id','users_detail.manager_id')
                    ->where('users_detail.manager_id', $user->id);
            }

            /* $query = $query->select(
                'reload_coins_request.id',
                'reload_coins_request.user_id',
                'reload_coins_request.currency_id',
                'reload_coins_request.sender_name',
                'reload_coins_request.order_number',
                'reload_coins_request.coin_amount',
                'reload_coins_request.total_amount',
                'reload_coins_request.created_at',
                'reload_coins_request.manager_name'
            ); */
            $result = $query->paginate(config('constant.review_pagination_count'), "*", "reload_coin_page");

            return $this->sendSuccessResponse(Lang::get('messages.user.success'), 200, $result);
        } catch (\Exception $ex) {
            Log::info($ex);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function reloadCoinRequestActions(Request $request)
    {
        $inputs = $request->all();

        try {
            $validator = Validator::make($request->all(), [
                'type' => 'required|in:approve,reject',
                'id' => 'required',
            ]);
            if ($validator->fails()) {
                return $this->sendCustomErrorMessage($validator->errors()->toArray(), 422);
            }

            DB::beginTransaction();
            $type = $inputs['type'] ?? '';
            $id = $inputs['id'] ?? '';
            
            if ($type == 'approve') {
                $reloadCoin = ReloadCoinRequest::find($id);
                $userCredits = UserCredit::where('user_id', $reloadCoin->user_id)->first();
                $old_credit = $userCredits->credits;
                $newCredits = $old_credit + $reloadCoin->coin_amount;
                $userCredits = UserCredit::where('user_id', $reloadCoin->user_id)->update(['credits' => $newCredits]);
                UserCreditHistory::create([
                    'user_id' => $reloadCoin->user_id,
                    'amount' => $reloadCoin->coin_amount,
                    'total_amount' => $newCredits,
                    'transaction' => 'credit',
                    'type' => UserCreditHistory::RELOAD
                ]);
                ReloadCoinRequest::where('id', $id)->update(['status' => ReloadCoinRequest::GIVE_COIN]);

                $notice = Notice::create([
                    'notify_type' => Notice::RELOAD_COIN_REQUEST_ACCEPTED,
                    'user_id' => $reloadCoin->user_id,
                    'to_user_id' => $reloadCoin->user_id,
                    'entity_id' => $reloadCoin->id,
                    'title' => number_format($reloadCoin->coin_amount),
                ]);

                $user_detail = DB::table('users_detail')->where('user_id', $reloadCoin->user_id)->first();
                $user_entity_relation = UserEntityRelation::where('user_id', $reloadCoin->user_id)
                    ->whereIn('entity_type_id', [EntityTypes::SHOP, EntityTypes::HOSPITAL])
                    ->first();


                $dt = Carbon::now();
                if ($user_entity_relation->entity_type_id == EntityTypes::HOSPITAL) {
                    $hospital = Hospital::find($user_entity_relation->entity_id);
                    $creditPlan = CreditPlans::where('entity_type_id', EntityTypes::HOSPITAL)->where('package_plan_id', $user_detail->package_plan_id)->first();
                    $defaultCredit = $creditPlan ? $creditPlan->amount : 0;
                    $minHospitalCredit = $defaultCredit; // Remove * 2

                    $userCredits = UserCredit::where('user_id', $hospital->user_id)->first();

                    //  Check expire date and have enough coin
                    if ($hospital && $hospital->deactivate_by_user == 0 && Carbon::now()->gte(Carbon::parse($user_detail->plan_expire_date)) && $userCredits->credits > $minHospitalCredit) {

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

                        Hospital::where('id', $hospital->id)->update(['credit_deduct_date' => $dt->toDateString()]);
                        UserDetail::where('id', $user_detail->id)->update(['plan_expire_date' => Carbon::now()->addDays(30)]);
                    }
                } else {
                    $creditPlan = CreditPlans::where('entity_type_id', EntityTypes::SHOP)->where('package_plan_id', $user_detail->package_plan_id)->first();
                    $total_user_shops = UserEntityRelation::where('entity_type_id', EntityTypes::SHOP)->where('user_id', $reloadCoin->user_id)->count();
                    $minShopCredit = $creditPlan ? ($creditPlan->amount * $total_user_shops) : 0;  // Remove * 2
                    $userCredits = UserCredit::where('user_id', $reloadCoin->user_id)->first();


                    $active_shop_count = UserEntityRelation::join('shops', 'shops.id', 'user_entity_relation.entity_id')
                        ->where('entity_type_id', EntityTypes::SHOP)
                        ->where('user_entity_relation.user_id', $reloadCoin->user_id)
                        ->whereIn('shops.status_id', [Status::ACTIVE, Status::PENDING, Status::INACTIVE])
                        ->where('deactivate_by_user', 0)
                        ->count();
                    $minShopCredit = $creditPlan ? ($creditPlan->amount * $active_shop_count) : 0;
                    if (Carbon::now()->gte(Carbon::parse($user_detail->plan_expire_date))  && $userCredits->credits > $minShopCredit) {

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

                        Shop::whereIn('status_id', [Status::ACTIVE, Status::PENDING, Status::INACTIVE])
                            ->where('user_id', $reloadCoin->user_id)
                            ->where('deactivate_by_user', 0)
                            ->update(['credit_deduct_date' => $dt->toDateString()]);
                        UserDetail::where('id', $user_detail->id)->update(['plan_expire_date' => Carbon::now()->addDays(30)]);
                    }
                }

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
            }elseif($type == 'reject'){
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
            }

            DB::commit();
            return $this->sendSuccessResponse("Reload coin Request ". trans("messages.update-success"), 200);
        } catch (\Exception $ex) {
            DB::rollBack();
            Log::info($ex);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }
}
