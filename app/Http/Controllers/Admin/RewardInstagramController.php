<?php

namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\SharedInstagramPost;
use App\Models\UserInstagramHistory;
use App\Models\UserCredit;
use App\Models\Config;
use App\Models\Shop;
use App\Models\ShopImages;
use App\Models\UserCreditHistory;
use App\Models\BasicMentions;
use App\Models\Notice;
use App\Models\EntityTypes;
use App\Models\UserDetail;
use App\Models\UserDevices;
use App\Models\ManagerActivityLogs;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Log;
use App\Util\Firebase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Lang;

class RewardInstagramController extends Controller
{

    public function __construct()
    {
        $this->firebase = new Firebase();
        $this->middleware('permission:reward-instagram-list', ['only' => ['index']]);
    }
/* ================ Reward Instagram Code Start ======================= */
    public function index()
    {
        $title = 'SNS Reward';   
        $rejectMentionText = BasicMentions::where('name','reward_instagram_reject')->pluck('value')->first();      
        $penaltyMentionText = BasicMentions::where('name','reward_instagram_penalty')->pluck('value')->first();      
        return view('admin.reward-instagram.index', compact('title','rejectMentionText','penaltyMentionText'));
    }

    public function getJsonAllData(Request $request)
    {
        try {   
            Log::info('Start reward instagram');
            $configData = Config::where('key',Config::SNS_REWARD_VISIBLE_HOURS)->first();
            $subHours = (!empty($configData) && !empty($configData->value)) ? $configData->value : 0;
            $columns = array(
                0 => 'id',
                1 => 'shops.shop_name',
                2 => 'shops.id',
                3 => 'shared_instagram_posts.shop_image_id',
                4 => 'user_intagram_history.penalty_count',
                5 => 'user_intagram_history.reject_count',
                6 => 'user_intagram_history.reward_count',
                7 => 'user_intagram_history.request_count',
                8 => 'phone',
                9 => 'user_intagram_history.requested_at',
                // 8 => 'status',
                10 => 'action',
                // 9 => 'action'
            );
            $limit = $request->input('length');
            $start = $request->input('start');
            $draw = $request->input('draw');
            $order = $columns[$request->input('order.0.column')];
            $dir = $request->input('order.0.dir');
            $adminTimezone = $this->getAdminUserTimezone();

            $query = UserInstagramHistory::leftJoin('shops','shops.user_id','user_intagram_history.user_id')
                ->join('users_detail','users_detail.user_id','user_intagram_history.user_id')              
                ->join('user_entity_relation','user_entity_relation.user_id','user_intagram_history.user_id')              
                ->select('user_intagram_history.*','shops.shop_name', 'shops.id as shop_id', 'users_detail.sns_link', 'users_detail.sns_link as social_link', 'users_detail.mobile')
                //->where('user_intagram_history.created_at', '<', Carbon::now()->subHours($configData->value))
                ->whereRaw("user_intagram_history.created_at < DATE_ADD(NOW(), INTERVAL -{$subHours} HOUR)")
                ->where('user_entity_relation.entity_type_id',EntityTypes::SHOP)
                ->groupBy('user_intagram_history.user_id');
            $totalData = count($query->get());
            $totalFiltered = $totalData;
            //DB::raw('CONCAT(users_detail.phone_code," ",users_detail.mobile) AS phone')
            $instagramData = $query->offset($start)
                ->limit($limit)
                ->orderBy($order, $dir)
                ->get();

            $data = array();
            if (!empty($instagramData)) {
                foreach ($instagramData as $value) { 
                    $id = $value['id'];
                    $disabled = $value['status'] == UserInstagramHistory::REQUEST_COIN ? '' : 'onclick="return false;"';
                    $disabledClass = $value['status'] == UserInstagramHistory::REQUEST_COIN ? '' : 'disabled';
                   
                    if(Carbon::parse($value['requested_at'])->gt(Carbon::now()->subHours($subHours))){
                        $disabled = 'onclick="return false;"';
                        $disabledClass = 'disabled';
                    }

                    $nestedData['checkbox'] = "<td><div class=\"custom-checkbox custom-control\">
                    <input $disabled $disabledClass type=\"checkbox\" data-checkboxes=\"mygroup\" class=\"custom-control-input checkbox_id check-reward-instagram\" id=\"$id\" data-id=\"$id\" value=\"$id\" name=\"checkbox_id[]\"><label for=\"$id\" class=\"custom-control-label\">&nbsp;</label></div></td>";
                   
                    $nestedData['shop_name'] = $value['shop_name'];
                    $shopProfile = route('admin.business-client.shop.show', $value['shop_id']);
                    $nestedData['shop_profile'] = "<a role='button' href='".$shopProfile."' title='' data-original-title='View Shop Profile' class='btn btn-primary btn-sm mr-3' data-toggle='tooltip'>See</a>";
                    $nestedData['instagram'] = "<a role='button' href='{$value['social_link']}' target='_blank' title='' data-original-title='Instagram' class='btn btn-primary btn-sm mr-3' data-toggle='tooltip'>See</a>";
                    $nestedData['penalty'] = $value['penalty_count'];
                    $nestedData['reject'] = $value['reject_count'];
                    $nestedData['reward'] = $value['reward_count'];
                    $nestedData['request_count'] = $value['request_count'];
                    $nestedData['phone'] = $value['mobile'];
                    $nestedData['date'] = $this->formatDateTimeCountryWise($value['requested_at'],$adminTimezone,'d-m-Y H:i'); 
                    $penaltyRoute = route('admin.reward-instagram.penalty', $id);
                    $rejectRoute = route('admin.reward-instagram.reject', $id);
                    $rewardRoute = route('admin.reward-instagram.reward', $id);

                    

                    $givePenalty = "<a $disabled role='button' href='".$penaltyRoute."' title='' data-original-title='Give Penalty' class='btn btn-primary btn-sm mx-1 $disabledClass' data-toggle='tooltip'>Give Penalty</a>";
                    $reject = "<a $disabled role='button' href='".$rejectRoute."' title='' data-original-title='Reject' class='btn btn-primary btn-sm mx-1 $disabledClass' data-toggle='tooltip'>Reject</a>";
                    $giveReward = "<a $disabled role='button' href='".$rewardRoute."' title='' data-original-title='Give Reward' class='btn btn-primary btn-sm mx-1 $disabledClass' data-toggle='tooltip'>Give Reward</a>";
                    $nestedData['actions'] = "<div class='d-flex'>$givePenalty $reject $giveReward</div>";       
                    $data[] = $nestedData;
                }
            }
            $jsonData = array(
                "draw" => intval($draw),
                "recordsTotal" => intval($totalData),
                "recordsFiltered" => intval($totalFiltered),
                "data" => $data,
                "instagramData" => $instagramData,
            );
            Log::info('End reward instagram');
            return response()->json($jsonData);
        } catch (\Exception $ex) {
            Log::info('Exception reward instagram');
            Log::info($ex);
            return response()->json([]);
        }
    }    

    public function givePenalty($id){
        try {
            DB::beginTransaction();
            Log::info('Give Penalty code start.');
            $configData = Config::where('key',Config::SNS_PENALTY)->first();
            $postDetail = UserInstagramHistory::find($id);
            $userCredits = UserCredit::where('user_id',$postDetail['user_id'])->first();              
            $old_credit = $userCredits->credits;
            $penaltyCredit = (int) filter_var($configData->value, FILTER_SANITIZE_NUMBER_INT);
            $total_credit = $old_credit - $penaltyCredit;
            if($penaltyCredit && $penaltyCredit > 0) {
                $userCredits = UserCredit::where('user_id',$postDetail['user_id'])->update(['credits' => $total_credit]); 
                UserCreditHistory::create([
                    'user_id' => $postDetail['user_id'],
                    'amount' => $penaltyCredit,
                    'total_amount' => $total_credit,
                    'transaction' => 'debit',
                    'type' => UserCreditHistory::PENALTY
                ]);

                $notice = Notice::create([
                    'notify_type' => Notice::SNS_PENALTY,
                    'user_id' => $postDetail->user_id,
                    'to_user_id' => $postDetail->user_id,
                    'entity_id' => $postDetail->id,
                ]);

                $user_detail = UserDetail::where('user_id', $postDetail->user_id)->first();
                $language_id = $user_detail ? $user_detail->language_id : 4;
                $key = Notice::SNS_PENALTY.'_'.$language_id;
                $devices = UserDevices::whereIn('user_id', [$postDetail->user_id])->pluck('device_token')->toArray();
                $format = __("notice.$key");
                $title_msg = '';
                $notify_type = Notice::SNS_PENALTY;
                
                $notificationData = [];
                if (count($devices) > 0) {
                    $result = $this->sentPushNotification($devices,$title_msg, $format, $notificationData ,$postDetail->user_id);                        
                }
            }
            
            UserInstagramHistory::where('id',$id)->update(['status' => UserInstagramHistory::PENALTY_COIN, 'penalty_count' => DB::raw('penalty_count + 1')]);

            $logData = [
                'activity_type' => ManagerActivityLogs::SNS_PENALTY,
                'user_id' => auth()->user()->id,
                'value' => Lang::get('messages.manager_activity.sns_penalty'),
                'entity_id' => $postDetail['user_id'],
            ];
            $this->addManagerActivityLogs($logData);

            DB::commit();
            Log::info('Give Penalty code end.');
            notify()->success("Successfully given penalty to user", "Success", "topRight");
            return redirect()->route('admin.reward-instagram.index');
        } catch (\Exception $ex) {
            DB::rollBack();
            Log::info('Give Penalty code exception.');
            Log::info($ex);
            notify()->error("Failed to give penlaty to user", "Error", "topRight");
            return redirect()->route('admin.reward-instagram.index');
        }
    }
    public function giveReject($id){
        try {
            DB::beginTransaction();
            Log::info('Give Reject code start.');
            $postDetail = UserInstagramHistory::find($id);
            $notice = Notice::create([
                'notify_type' => Notice::SNS_REJECT,
                'user_id' => $postDetail->user_id,
                'to_user_id' => $postDetail->user_id,
                'entity_id' => $postDetail->id,
            ]);

            $user_detail = UserDetail::where('user_id', $postDetail->user_id)->first();
            $language_id = $user_detail ? $user_detail->language_id : 4;
            $key = Notice::SNS_REJECT.'_'.$language_id;
            $devices = UserDevices::whereIn('user_id', [$postDetail->user_id])->pluck('device_token')->toArray();
            $format = __("notice.$key");
            $title_msg = '';
            $notify_type = Notice::SNS_REJECT;
            
            $notificationData = [];
            if (count($devices) > 0) {
                $result = $this->sentPushNotification($devices,$title_msg, $format, $notificationData ,$postDetail->user_id);                        
            }

            UserInstagramHistory::where('id',$id)->update(['status' => UserInstagramHistory::REJECT_COIN, 'reject_count' => DB::raw('reject_count + 1')]);

            DB::commit();
            Log::info('Give Reject code end.');
            notify()->success("Rejected successfully", "Success", "topRight");
            return redirect()->route('admin.reward-instagram.index');
        } catch (\Exception $ex) {
            DB::rollBack();
            Log::info('Give Reject code exception.');
            Log::info($ex);
            notify()->error("Failed to reject", "Error", "topRight");
            return redirect()->route('admin.reward-instagram.index');
        }
    }
    public function giveReward($id){
        try {
            $loggedInUser = Auth::user();
            DB::beginTransaction();
            $configData = Config::where('key',Config::SNS_REWARD)->first();
            $postDetail = UserInstagramHistory::find($id);
            $userCredits = UserCredit::where('user_id',$postDetail['user_id'])->first();              
            $old_credit = $userCredits->credits;
            $rewardCredit = (int) filter_var($configData->value, FILTER_SANITIZE_NUMBER_INT);
            $total_credit = $old_credit + $rewardCredit;
            $userCredits = UserCredit::where('user_id',$postDetail['user_id'])->update(['credits' => $total_credit]); 
            UserCreditHistory::create([
                'user_id' => $postDetail['user_id'],
                'amount' => $rewardCredit,
                'total_amount' => $total_credit,
                'transaction' => 'credit',
                'type' => UserCreditHistory::REWARD
            ]);

            $notice = Notice::create([
                'notify_type' => Notice::SNS_REWARD,
                'user_id' => $postDetail->user_id,
                'to_user_id' => $postDetail->user_id,
                'entity_id' => $postDetail->id,
                'title' => number_format($rewardCredit),
                'sub_title' => number_format($total_credit)
            ]);

            $user_detail = UserDetail::where('user_id', $postDetail->user_id)->first();
            $language_id = $user_detail ? $user_detail->language_id : 4;
            $key = Notice::SNS_REWARD.'_'.$language_id;
            $devices = UserDevices::whereIn('user_id', [$postDetail->user_id])->pluck('device_token')->toArray();
            $format = __("notice.$key");
            $title_msg = '';
            $notify_type = Notice::SNS_REWARD;
            
            $notificationData = [];
            if (count($devices) > 0) {
                $result = $this->sentPushNotification($devices,$title_msg, $format, $notificationData ,$postDetail->user_id);                        
            }

            UserInstagramHistory::where('id',$id)->update(['status' => UserInstagramHistory::GIVE_COIN, 'reward_count' => DB::raw('reward_count + 1')]);          

            $logData = [
                'activity_type' => ManagerActivityLogs::SNS_REWARD,
                'user_id' => $loggedInUser->id,
                'value' => Lang::get('messages.manager_activity.sns_reward'),
                'entity_id' => $postDetail['user_id'],
            ];
            $this->addManagerActivityLogs($logData);

            DB::commit();
           
            notify()->success("Successfully given reward to user", "Success", "topRight");
            return redirect()->route('admin.reward-instagram.index');
        } catch (\Exception $ex) {
            DB::rollBack();
            Log::info($ex);
            notify()->error("Failed to give reward to user", "Error", "topRight");
            return redirect()->route('admin.reward-instagram.index');
        }
    }

    public function viewShopImage($id){
        $postDetail = SharedInstagramPost::find($id);
        $shopImage = ShopImages::find($postDetail->shop_image_id);
        $shop = Shop::find($postDetail->shop_id);
        return view('admin.reward-instagram.check-shop-image',compact('shopImage','shop'));
    }

    public function rewardMultiple(Request $request)
    {
        try {            
                $inputs = $request->all();
                $ids = explode(',',$inputs['ids']);
                $configData = Config::where('key',Config::SNS_REWARD)->first();
                Log::info('Reward Multiple code start');
                DB::beginTransaction();
                foreach ($ids as $id)
                {     
                    $postDetail = UserInstagramHistory::find($id);
                    $userCredits = UserCredit::where('user_id',$postDetail['user_id'])->first();              
                    $old_credit = $userCredits->credits;
                    $rewardCredit = (int) filter_var($configData->value, FILTER_SANITIZE_NUMBER_INT);
                    $total_credit = $old_credit + $rewardCredit;
                    $userCredits = UserCredit::where('user_id',$postDetail['user_id'])->update(['credits' => $total_credit]); 
                    UserCreditHistory::create([
                        'user_id' => $postDetail['user_id'],
                        'amount' => $rewardCredit,
                        'total_amount' => $total_credit,
                        'transaction' => 'credit',
                        'type' => UserCreditHistory::REWARD
                    ]);

                    UserInstagramHistory::where('id',$id)->update(['status' => UserInstagramHistory::GIVE_COIN, 'reward_count' => DB::raw('reward_count + 1')]);                
                }
                DB::commit();
                Log::info('Reward Multiple code');
                return $this->sendSuccessResponse('Successfully given reward to users.', 200);
        } catch (\Exception $ex) {
            Log::info('Exception Reward Multiple code');
            Log::info($ex);
            DB::rollBack();
            return $this->sendFailedResponse('Failed to give reward to users.', 400);
        }
    }

    public function PenaltyRejectMention(Request $request)
    {
        try {
            Log::info('Start Basic Mention ');
            $inputs = $request->all();
            $content = $inputs['content'];
            $type = $inputs['type'];
            $name = $type == 'reject' ? 'reward_instagram_reject' : 'reward_instagram_penalty';
            $rejectMentionText = BasicMentions::where('name',$name)->update(['value' => $content]); 
            Log::info('End Basic Mention ' );
            return $this->sendSuccessResponse('Basic mention set successfully.', 200);
        } catch (\Exception $ex) {
            Log::info('Exception in Basic Mention ');
            Log::info($ex);
            return $this->sendFailedResponse('Unable to set basic mention.', 400);
        }
    }

/* ================ Reward Instagram Code End ======================= */
   
}
