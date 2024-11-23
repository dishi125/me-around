<?php

namespace App\Console\Commands;

use Log;
use Carbon\Carbon;
use App\Models\Post;
use App\Models\Notice;
use App\Util\Firebase;
use App\Models\Address;
use App\Models\Hospital;
use App\Models\UserDetail;
use App\Models\EntityTypes;
use App\Models\UserDevices;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\RequestBookingStatus;
use App\Models\CompleteCustomerDetails;

class OutsideBooking extends Command
{
    protected $firebase;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'outsidebooking:cron';

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
        
        $customers = CompleteCustomerDetails::where('status_id',RequestBookingStatus::BOOK)->get();

        foreach($customers as $customer){
            $date1 = Carbon::now();

            if(!empty($customer->date) && Carbon::now()->gte(Carbon::parse($customer->date))) {

                CompleteCustomerDetails::where('id',$customer->id)->update(['status_id' => RequestBookingStatus::VISIT]);
                
                $devices = UserDevices::whereIn('user_id', [$customer->entity_user_id])->pluck('device_token')->toArray();
                $user_detail = UserDetail::where('user_id', $customer->entity_user_id)->first();
                $language_id = ($user_detail) ? $user_detail->language_id : 4;
                $key = Notice::VISIT.'_'.$language_id;
                $format = __("notice.$key", ['name' => $customer->user_name]);
                $title_msg = '';
                $notify_type = Notice::VISIT;

                $mainName = '';
                if($customer->entity_type_id == EntityTypes::HOSPITAL){
                    $post = Post::find($customer->entity_id);
                    if($post) {
                        $hospital = Hospital::find($post->hospital_id);
                        $mainName = !empty($hospital) ? $hospital->main_name : '';
                    }else {
                        $mainName = '';
                    }
                }else {
                    $shop = DB::table('shops')->whereId($customer->entity_id)->first();
                    $mainName = !empty($shop) ? $shop->main_name : '';
                }    

                $insert = Notice::create([
                    'notify_type' => Notice::OUTSIDE_VISIT,
                    'user_id' => $customer->entity_user_id,
                    'to_user_id' => $customer->entity_user_id,
                    'entity_type_id' => $customer->entity_type_id,
                    'entity_id' => $customer->id,
                    'title' => $mainName,
                    'sub_title' => $customer->date,
                    'is_aninomity' => 0,
                ]);

                Log::info("In notice cron for visit");
                Log::info($insert);
                
                $country = '';
                if($customer->entity_type_id == EntityTypes::SHOP) {
                    $address = Address::where('entity_type_id',$customer->entity_type_id)
                                        ->where('entity_id',$customer->entity_id)->first();
                    $country = $address ? $address->main_country : '';
                    $notificationData = CompleteCustomerDetails::join('shops','shops.id','=','complete_customer_details.entity_id')
                                                        ->join('category','category.id','=','shops.category_id')
                                                        ->where('complete_customer_details.entity_type_id',EntityTypes::SHOP)
                                                        ->where('complete_customer_details.id',$customer->id)
                                                        ->select(['complete_customer_details.*','shops.main_name','shops.category_id'])->first()->toArray();

                }else if ($customer->entity_type_id == EntityTypes::HOSPITAL) {
                    $post = Post::find($customer->entity_id);
                    $hospital_id = $post ? $post->hospital_id : null;
                    $address = Address::where('entity_type_id',$customer->entity_type_id)
                                        ->where('entity_id',$hospital_id)->first();
                    $country = $address ? $address->main_country : '';

                    $notificationData = CompleteCustomerDetails::join('posts','posts.id','=','complete_customer_details.entity_id')
                                    ->join('hospitals','hospitals.id','=','posts.hospital_id')
                                    ->join('category','category.id','=','posts.category_id')
                                    ->where('complete_customer_details.entity_type_id',EntityTypes::HOSPITAL)
                                    ->where('complete_customer_details.id',$customer->id)
                                    ->select(['complete_customer_details.*','hospitals.main_name','posts.id as post_id','posts.category_id','category.name as category_name'])->first()->toArray();
                }

                if (count($devices) > 0) {
                    $this->sentPushNotification($devices,$title_msg, $format, $notificationData ,$notify_type, $customer->id);                        
                }
            }
        }
        

        $date = Carbon::now()->addHours(1);
        $userBookings = CompleteCustomerDetails::where('status_id',RequestBookingStatus::BOOK)->where(DB::raw("(DATE_FORMAT(date,'%Y-%m-%d %H:%i'))"),$date->format('Y-m-d H:i'))->get();
        Log::info(Carbon::now()->format("Y-m-d H:i:s"));
        Log::info("All notice cron customer");
        Log::info($userBookings);
        foreach($userBookings as $booking) {
            
            if($booking->entity_user_id && Carbon::now()->subMinutes(5)->gt(Carbon::parse($booking->updated_at))) {
                CompleteCustomerDetails::where('id',$booking->id)->update(['updated_at' => Carbon::now()]);
                $devices = UserDevices::whereIn('user_id', [$booking->entity_user_id])->pluck('device_token')->toArray();
                $user_detail = UserDetail::where('user_id', $booking->entity_user_id)->first();
                $language_id = $user_detail->language_id;
                $key = Notice::HOUR_1_BEFORE_VISIT.'_'.$language_id;
                $format = __("notice.$key", ['name' => $booking->user_name]);
                $title_msg = '';
                $notify_type = Notice::OUTSIDE_HOUR_1_BEFORE_VISIT;
    
                $mainName = '';
                if($customer->entity_type_id == EntityTypes::HOSPITAL){
                    $post = Post::find($customer->entity_id);
                    if($post) {
                        $hospital = Hospital::find($post->hospital_id);
                        $mainName = !empty($hospital) ? $hospital->main_name : '';
                    }else {
                        $mainName = '';
                    }
                }else {
                    $shop = DB::table('shops')->whereId($customer->entity_id)->first();
                    $mainName = !empty($shop) ? $shop->main_name : '';
                }

                $insert = Notice::create([
                    'notify_type' => Notice::OUTSIDE_HOUR_1_BEFORE_VISIT,
                    'user_id' => $booking->entity_user_id,
                    'to_user_id' => $booking->entity_user_id,
                    'entity_type_id' => $booking->entity_type_id,
                    'entity_id' => $booking->id,
                    'title' => $mainName,
                    'sub_title' => $booking->date,
                    'is_aninomity' => 0,
                ]);
                Log::info("In notice cron for before 1 visit");
                Log::info($insert);

                if($booking && $booking->entity_type_id == EntityTypes::SHOP) {
                    $notificationData = CompleteCustomerDetails::join('shops','shops.id','=','complete_customer_details.entity_id')
                    ->join('category','category.id','=','shops.category_id')
                    ->where('complete_customer_details.entity_type_id',EntityTypes::SHOP)
                    ->where('complete_customer_details.id',$booking->id)
                    ->select(['complete_customer_details.*','shops.main_name','shops.category_id'])->first()->toArray();
                }else {
                    $notificationData = CompleteCustomerDetails::join('posts','posts.id','=','complete_customer_details.entity_id')
                                    ->join('hospitals','hospitals.id','=','posts.hospital_id')
                                    ->join('category','category.id','=','posts.category_id')
                                    ->where('complete_customer_details.entity_type_id',EntityTypes::HOSPITAL)
                                    ->where('complete_customer_details.id',$booking->id)
                                    ->select(['complete_customer_details.*','hospitals.main_name','posts.id as post_id','posts.category_id','category.name as category_name'])->first()->toArray();
                }   
                if (count($devices) > 0) {
                    $result = $this->sentPushNotification($devices,$title_msg, $format, $notificationData ,$notify_type, $booking->id);                        
                }
            }
        }

        $this->info('OutsideBooking:Cron Cummand Run successfully!');
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
