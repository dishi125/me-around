<?php

namespace App\Console\Commands;

use Log;
use Carbon\Carbon;
use App\Models\Post;
use App\Models\Shop;
use App\Models\User;
use App\Models\Notice;
use App\Models\Status;
use App\Models\Hospital;
use App\Models\UserCredit;
use App\Models\UserDetail;
use App\Models\CreditPlans;
use App\Models\EntityTypes;
use App\Models\UserDevices;
use Illuminate\Console\Command;
use App\Models\UserCreditHistory;
use App\Models\UserEntityRelation;
use Illuminate\Support\Facades\DB;

class UserExpiry extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'userexpiry:cron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $expiredUser = User::join('users_detail','users_detail.user_id','users.id')
                ->join('user_credits','user_credits.user_id','users.id')
                ->select(
                    'users.id',
                    'user_credits.credits',
                    'users_detail.package_plan_id',
                    'users_detail.plan_expire_date',
                    'users.created_at'
                )
                ->where('users_detail.plan_expire_date','<=',Carbon::now())
                ->groupBy('users.id')
                ->get();
                   
        

        $dt = Carbon::now();  

        Log::channel('coincron')->info("");
        Log::channel('coincron')->info("-------------------------------------");
        Log::channel('coincron')->info("");
        Log::channel('coincron')->info("Cron Start at $dt");
        if(!empty($expiredUser)){
            foreach($expiredUser as $user){
                Log::channel('coincron')->info("Cron UserId - ".$user->id);
                $hospital_count = UserEntityRelation::where('entity_type_id', EntityTypes::HOSPITAL)->where('user_id',$user->id)->count();

                if($hospital_count){    
                    Log::channel('coincron')->info("Cron User Type Hospital");

                    $user_entity_relation = UserEntityRelation::where('user_id',$user->id)
                                                ->where('entity_type_id',EntityTypes::HOSPITAL)
                                                ->first();

                    $hospital = Hospital::find($user_entity_relation->entity_id);
                    $creditPlan = CreditPlans::where('entity_type_id', EntityTypes::HOSPITAL)->where('package_plan_id', $user->package_plan_id)->first();
                    $defaultCredit = $creditPlan ? $creditPlan->amount : 0;

                    if($user->credits > $defaultCredit && $hospital && $hospital->deactivate_by_user == 0) {
                        Log::channel('coincron')->info("Cron User deduct amount $defaultCredit");
                        // Deduct amount Start
                        $old_credit = $user->credits;
                        $total_credit = $old_credit - $defaultCredit;
                        UserCredit::where('user_id',$user->id)->update(['credits' => $total_credit]); 
                        $historyData = UserCreditHistory::create([
                            'user_id' => $user->id,
                            'amount' => $defaultCredit,
                            'total_amount' => $total_credit,
                            'transaction' => 'debit',
                            'type' => UserCreditHistory::REGULAR,
                            'created_at' => Carbon::now()
                        ]);

                        Log::channel('coincron')->info("Cron User History Date ".$historyData->created_at);

                        if($total_credit > $defaultCredit){
                            User::where('id',$user->id)->update(['chat_status' => 1]);
                            Hospital::where('id',$hospital->id)->update(['status_id' => Status::ACTIVE]);
                            Post::where('hospital_id',$hospital->id)->where('status_id',Status::INACTIVE)->update(['status_id' => Status::ACTIVE]);
                        }
                        Hospital::where('id',$hospital->id)->update(['credit_deduct_date' => $dt->toDateString()]);
                        UserDetail::where('user_id',$user->id)->update(['plan_expire_date' => Carbon::now()->addDays(30)]);
                        
                        Log::channel('coincron')->info("Cron User New Expiry Date ".Carbon::now()->addDays(30));

                        $notificationData = [
                            'id' => $hospital->id,
                            'main_name' => $hospital->main_name,
                            'category_id' => $hospital->category_id,
                            'category_name' => $hospital->category_name,
                            'category_icon' => $hospital->category_icon,
                        ];

                        $devices = UserDevices::whereIn('user_id', [$user->id])->pluck('device_token')->toArray();
                        $user_detail = UserDetail::where('user_id', $user->id)->first();
                        $language_id = $user_detail->language_id ?? 4;
                        $title_msg = '';
                        $temp[] = $user->id;    
                        $key = Notice::MONTHLY_COIN_DEDUCT.'_'.$language_id;
                        $format = __("notice.$key");
                        $notify_type = Notice::MONTHLY_COIN_DEDUCT;
                        Notice::create([
                            'notify_type' => Notice::MONTHLY_COIN_DEDUCT,
                            'user_id' => $user->id,
                            'to_user_id' => $user->id,
                            'entity_type_id' => EntityTypes::HOSPITAL,
                            'title' => $creditPlan->package_plan_name,
                            'sub_title' => number_format((float)$defaultCredit)
                        ]);

                        if (count($devices) > 0) {
                            $result = $this->sentPushNotification($devices,$title_msg, $format, $notificationData ,$notify_type, $hospital->id);                        
                        }
                        // Deduct amount End

                        
                        
                    }else{
                        User::where('id',$user->id)->update(['chat_status' => 0]);
                        Hospital::where('id',$hospital->id)->update(['status_id' => Status::INACTIVE]);
                        Post::where('hospital_id',$hospital->id)->where('status_id',Status::ACTIVE)->update(['status_id' => Status::INACTIVE]);
                        Log::channel('coincron')->info("Cron User Not enough coin");
                    }
                }else{
                    Log::channel('coincron')->info("Cron User Type Shop");

                    $shop_count = UserEntityRelation::where('entity_type_id', EntityTypes::SHOP)->where('user_id',$user->id)->count();
                    
                    if($shop_count){
                        $shop = DB::table('shops')->whereIn('status_id',[Status::ACTIVE,Status::PENDING,Status::INACTIVE])
                                ->where('user_id',$user->id)
                                ->where('deactivate_by_user',0)->count();

                        if($shop){
                            $creditPlan = CreditPlans::where('entity_type_id', EntityTypes::SHOP)->where('package_plan_id', $user->package_plan_id)->first();
                            $minShopCredit = $creditPlan ? ($creditPlan->amount * $shop ) : 0;
                            
                            if($user->credits > $minShopCredit){
                                Log::channel('coincron')->info("Cron User deduct amount $minShopCredit");
                                // Deduct amount Start
                                $old_credit = $user->credits;
                                $total_credit = $old_credit - $minShopCredit;

                                UserCredit::where('user_id',$user->id)->update(['credits' => $total_credit]); 
                                $historyData = UserCreditHistory::create([
                                    'user_id' => $user->id,
                                    'amount' => $minShopCredit,
                                    'total_amount' => $total_credit,
                                    'transaction' => 'debit',
                                    'type' => UserCreditHistory::REGULAR,
                                    'created_at' => Carbon::now()
                                ]);

                                Log::channel('coincron')->info("Cron User History Date ".$historyData->created_at);

                                if($total_credit > $minShopCredit){
                                    User::where('id',$user->id)->update(['chat_status' => 1]);
                                    Shop::whereIn('status_id',[Status::ACTIVE,Status::PENDING,Status::INACTIVE])
                                        ->where('user_id',$user->id)
                                        ->where('deactivate_by_user',0)
                                        ->update(['status_id' => Status::INACTIVE]);
                                }
                                
                                Shop::whereIn('status_id',[Status::ACTIVE,Status::PENDING,Status::INACTIVE])
                                    ->where('user_id',$user->id)
                                    ->where('deactivate_by_user',0)
                                    ->update(['credit_deduct_date' => $dt->toDateString()]);
                                UserDetail::where('user_id',$user->id)->update(['plan_expire_date' => Carbon::now()->addDays(30)]);
                               
                                Log::channel('coincron')->info("Cron User New Expiry Date ".Carbon::now()->addDays(30));

                                $shopData = Shop::whereIn('status_id',[Status::ACTIVE,Status::PENDING,Status::INACTIVE])
                                    ->where('user_id',$user->id)
                                    ->where('deactivate_by_user',0)->first();
                                    
                                $notificationData = [
                                    'id' => $shopData->id,
                                    'main_name' => $shopData->main_name,
                                    'shop_name' => $shopData->shop_name,
                                    'category_id' => $shopData->category_id,
                                    'category_name' => $shopData->category_name,
                                    'category_icon' => $shopData->category_icon,
                                ];

                                $devices = UserDevices::whereIn('user_id', [$shopData->user_id])->pluck('device_token')->toArray();
                                $user_detail = UserDetail::where('user_id', $shopData->user_id)->first();
                                $language_id = $user_detail->language_id ?? 4;
                                $title_msg = '';
                                $key = Notice::MONTHLY_COIN_DEDUCT.'_'.$language_id;
                                $format = __("notice.$key");
                                $notify_type = Notice::MONTHLY_COIN_DEDUCT;
                                Notice::create([
                                    'notify_type' => Notice::MONTHLY_COIN_DEDUCT,
                                    'user_id' => $shopData->user_id,
                                    'to_user_id' => $shopData->user_id,
                                    'entity_type_id' => EntityTypes::SHOP,
                                    'title' => $creditPlan->package_plan_name,
                                    'sub_title' =>  number_format((float)$minShopCredit)
                                ]);

                                if (count($devices) > 0) {
                                    $result = $this->sentPushNotification($devices,$title_msg, $format, $notificationData ,$notify_type, $shopData->id);                        
                                }
                                // Deduct amount End

                               
                            }else{
                                User::where('id',$user->id)->update(['chat_status' => 0]);
                                Shop::where('user_id',$user->id)->update(['status_id' => Status::INACTIVE]);
                                Log::channel('coincron')->info("Cron User Not enough coin");
                            }
                        }
                    }
                }
                
            }
        }

       $this->info('Shop:Cron Cummand Run successfully!');
    }

    public function sentPushNotification($registration_ids,$title_msg, $format, $notificationData =[], $notify_type = null, $event_id = null, $action = null, $broadcaster = null, $position = null)
    {
        try {
            $msg = array(
                'body' => $format,
                'title' => $title_msg,
                'notification_data' => $notificationData,
                'priority'=> 'high', 
                'sound' => 'notifytune.wav',
            );
            $data = array(
                'notification_data' => $notificationData,
                'type' => $notify_type,
                'msgcnt' => 1,
                'action' => $action,
                "click_action" => "FLUTTER_NOTIFICATION_CLICK",
                "event_id" => $event_id,
                "broadcaster_name" => $broadcaster,
                "position" => $position
            );
            $response = $this->firebase->sendMultiple($registration_ids, $data, $msg);
            return $response;
        } catch (\Exception $ex) {
            return;
        }
    }
}
