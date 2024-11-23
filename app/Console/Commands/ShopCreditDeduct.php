<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Status;
use App\Models\Shop;
use App\Models\UserDetail;
use App\Models\CreditPlans;
use App\Models\EntityTypes;
use App\Models\UserCredit;
use App\Models\UserCreditHistory;
use App\Models\UserEntityRelation;
use App\Models\Hospital;
use App\Models\Notice;
use App\Models\Post;
use App\Models\UserDevices;
use App\Models\User;
use Carbon\Carbon;
use App\Util\Firebase;
use Illuminate\Support\Facades\Log;

class ShopCreditDeduct extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'shop-credit-deduct:cron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Monthly deduct credits of shop';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->firebase = new Firebase();
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $dt = Carbon::now();  
        $shops = Shop::whereIn('status_id',[Status::ACTIVE,Status::PENDING,Status::INACTIVE])->whereDate('credit_deduct_date', '=', Carbon::now()->subDays(30))->groupBy('user_id')->get();
        foreach($shops as $shop){            
            $user_detail = UserDetail::where('user_id', $shop->user_id)->first();
            $creditPlan = CreditPlans::where('entity_type_id', EntityTypes::SHOP)->where('package_plan_id', $user_detail->package_plan_id)->first();
            $total_user_shops = UserEntityRelation::where('entity_type_id', EntityTypes::SHOP)->where('user_id',$shop->user_id)->count();
            $defaultCredit = $creditPlan ? ($creditPlan->amount * $total_user_shops) : 0;
            $minimumCreditRequired = $defaultCredit; // Remove  * 2
            $userCredits = UserCredit::where('user_id',$shop->user_id)->first(); 
            if($userCredits->credits >= $minimumCreditRequired) {
                $old_credit = $userCredits->credits;
                $total_credit = $old_credit - $defaultCredit;
    
                $userCredits = UserCredit::where('user_id',$shop->user_id)->update(['credits' => $total_credit]); 
                UserCreditHistory::create([
                    'user_id' => $shop->user_id,
                    'amount' => $defaultCredit,
                    'total_amount' => $total_credit,
                    'transaction' => 'debit',
                    'type' => UserCreditHistory::REGULAR
                ]);

                $notificationData = [
                    'id' => $shop->id,
                    'main_name' => $shop->main_name,
                    'shop_name' => $shop->shop_name,
                    'category_id' => $shop->category_id,
                    'category_name' => $shop->category_name,
                    'category_icon' => $shop->category_icon,
                ];

                $devices = UserDevices::whereIn('user_id', [$shop->user_id])->pluck('device_token')->toArray();
                $user_detail = UserDetail::where('user_id', $shop->user_id)->first();
                $language_id = $user_detail->language_id;
                $title_msg = '';
                $temp[] = $shop->user_id;    
                $key = Notice::MONTHLY_COIN_DEDUCT.'_'.$language_id;
                $format = __("notice.$key");
                $notify_type = Notice::MONTHLY_COIN_DEDUCT;
                $notice = Notice::create([
                    'notify_type' => Notice::MONTHLY_COIN_DEDUCT,
                    'user_id' => $shop->user_id,
                    'to_user_id' => $shop->user_id,
                    'entity_type_id' => EntityTypes::SHOP,
                    'title' => $creditPlan->package_plan_name,
                    'sub_title' =>  number_format((float)$defaultCredit)
                ]);

                if (count($devices) > 0) {
                    $result = $this->sentPushNotification($devices,$title_msg, $format, $notificationData ,$notify_type, $shop->id);                        
                }
                Shop::where('user_id',$shop->user_id)->update(['credit_deduct_date' => $dt->toDateString()]);
                UserDetail::where('id',$user_detail->id)->update(['plan_expire_date' => Carbon::now()->addDays(30)]);
            }else {
                User::where('id',$shop->user_id)->update(['chat_status' => 0]); 
                Shop::where('user_id',$shop->user_id)->update(['status_id' => Status::INACTIVE]);
            }

        }

        $hospitals = Hospital::whereIn('status_id',[Status::ACTIVE,Status::PENDING,Status::INACTIVE])->whereDate('credit_deduct_date', '=', Carbon::now()->subDays(30))->get();
        foreach($hospitals as $hospital){   
            $userRelation = UserEntityRelation::where('entity_type_id',EntityTypes::HOSPITAL)->where('entity_id',$hospital->id)->first();         
            $user_detail = UserDetail::where('user_id', $userRelation->user_id)->first();
            $creditPlan = CreditPlans::where('entity_type_id', EntityTypes::HOSPITAL)->where('package_plan_id', $user_detail->package_plan_id)->first();

            $defaultCredit = $creditPlan && $hospital->status_id != Status::INACTIVE ? $creditPlan->amount : 0;
            $minimumCreditRequired = $defaultCredit; // Remove  * 2
            $userCredits = UserCredit::where('user_id',$userRelation->user_id)->first();   
            if($userCredits >= $minimumCreditRequired) {
                $old_credit = $userCredits->credits;
                $total_credit = $old_credit - $defaultCredit;
    
                $userCredits = UserCredit::where('user_id',$userRelation->user_id)->update(['credits' => $total_credit]); 
                UserCreditHistory::create([
                    'user_id' => $userRelation->user_id,
                    'amount' => $defaultCredit,
                    'total_amount' => $total_credit,
                    'transaction' => 'debit',
                    'type' => UserCreditHistory::REGULAR
                ]);
                Hospital::where('id',$hospital->id)->update(['credit_deduct_date' => $dt->toDateString()]);
                UserDetail::where('id',$user_detail->id)->update(['plan_expire_date' => Carbon::now()->addDays(30)]);

                $notificationData = [
                    'id' => $hospital->id,
                    'main_name' => $hospital->main_name,
                    'category_id' => $hospital->category_id,
                    'category_name' => $hospital->category_name,
                    'category_icon' => $hospital->category_icon,
                ];

                $devices = UserDevices::whereIn('user_id', [$userRelation->user_id])->pluck('device_token')->toArray();
                $user_detail = UserDetail::where('user_id', $userRelation->user_id)->first();
                $language_id = $user_detail->language_id;
                $title_msg = '';
                $temp[] = $userRelation->user_id;    
                $key = Notice::MONTHLY_COIN_DEDUCT.'_'.$language_id;
                $format = __("notice.$key");
                $notify_type = Notice::MONTHLY_COIN_DEDUCT;
                $notice = Notice::create([
                    'notify_type' => Notice::MONTHLY_COIN_DEDUCT,
                    'user_id' => $userRelation->user_id,
                    'to_user_id' => $userRelation->user_id,
                    'entity_type_id' => EntityTypes::HOSPITAL,
                    'title' => $creditPlan->package_plan_name,
                    'sub_title' => number_format((float)$defaultCredit)
                ]);

                if (count($devices) > 0) {
                    $result = $this->sentPushNotification($devices,$title_msg, $format, $notificationData ,$notify_type, $hospital->id);                        
                }

            }else {
                Hospital::where('id',$hospital->id)->update(['status_id' => Status::INACTIVE]);
                Post::where('hospital_id',$hospital->id)->update(['status_id' => Status::INACTIVE]);
                User::where('id',$userRelation->user_id)->update(['chat_status' => 0]);
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
