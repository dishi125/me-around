<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\EntityTypes;
use App\Models\ActivityLog;
use App\Models\RequestBookingStatus;
use App\Models\RequestedCustomer;
use App\Models\Address;
use App\Models\UserDetail;
use App\Models\Post;
use App\Models\Notice;
use App\Models\UserDevices;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Util\Firebase;
use Illuminate\Support\Facades\Log;

class BookingCron extends Command
{
    protected $firebase;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'booking:cron';

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
        $customers = RequestedCustomer::where('request_booking_status_id',RequestBookingStatus::BOOK)->get();
        foreach($customers as $customer){
            $date1 = Carbon::now();
            $currentDate = $date1->format('d-m-Y H:i');
            $date2 = new Carbon($customer->booking_date);
            $bookingDate = $date2->format('d-m-Y H:i');
            if(!empty($customer->booking_date) && Carbon::now()->gte(Carbon::parse($customer->booking_date))) {
                RequestedCustomer::where('id',$customer->id)->update(['request_booking_status_id' => RequestBookingStatus::VISIT]);
                
                $devices = UserDevices::whereIn('user_id', [$customer->entity_user_id])->pluck('device_token')->toArray();
                $user_detail = UserDetail::where('user_id', $customer->entity_user_id)->first();
                $language_id = ($user_detail) ? $user_detail->language_id : 4;
                $key = Notice::VISIT.'_'.$language_id;
                $format = __("notice.$key", ['name' => $customer->user_name]);
                $title_msg = '';
                $notify_type = Notice::VISIT;

                $notice = Notice::create([
                    'notify_type' => Notice::VISIT,
                    'user_id' => $customer->entity_user_id,
                    'to_user_id' => $customer->entity_user_id,
                    'entity_type_id' => $customer->entity_type_id,
                    'entity_id' => $customer->id,
                    'title' => $customer->main_name,
                    'sub_title' => $customer->booking_date,
                ]);
                
                $country = '';
                if($customer->entity_type_id == EntityTypes::SHOP) {
                    $address = Address::where('entity_type_id',$customer->entity_type_id)
                                        ->where('entity_id',$customer->entity_id)->first();
                    $country = $address ? $address->main_country : '';
                    $notificationData = RequestedCustomer::join('shops','shops.id','=','requested_customer.entity_id')
                                                        ->join('category','category.id','=','shops.category_id')
                                                        ->where('requested_customer.entity_type_id',EntityTypes::SHOP)
                                                        ->where('requested_customer.id',$customer->id)
                                                        ->select(['requested_customer.*','shops.main_name','shops.category_id'])->first()->toArray();

                }else if ($customer->entity_type_id == EntityTypes::HOSPITAL) {
                    $post = Post::find($customer->entity_id);
                    $hospital_id = $post ? $post->hospital_id : null;
                    $address = Address::where('entity_type_id',$customer->entity_type_id)
                                        ->where('entity_id',$hospital_id)->first();
                    $country = $address ? $address->main_country : '';

                    $notificationData = RequestedCustomer::join('posts','posts.id','=','requested_customer.entity_id')
                                    ->join('hospitals','hospitals.id','=','posts.hospital_id')
                                    ->join('category','category.id','=','posts.category_id')
                                    ->where('requested_customer.entity_type_id',EntityTypes::HOSPITAL)
                                    ->where('requested_customer.id',$customer->id)
                                    ->select(['requested_customer.*','hospitals.main_name','posts.id as post_id','posts.category_id','category.name as category_name'])->first()->toArray();
                }

                if (count($devices) > 0) {
                    $result = $this->sentPushNotification($devices,$title_msg, $format, $notificationData ,$notify_type, $customer->id);                        
                }

                ActivityLog::create([
                    'entity_type_id' => $customer->entity_type_id,
                    'entity_id' => $customer->entity_id,
                    'user_id' => $customer->user_id,
                    'country' => $country,
                    'request_booking_status_id' => RequestBookingStatus::VISIT,
                ]);
            }
        }
       
        //Send Notification to user before 2 hours
        $date = Carbon::now()->addHours(2);
        $bookings = RequestedCustomer::where('request_booking_status_id',RequestBookingStatus::BOOK)->where(DB::raw("(DATE_FORMAT(booking_date,'%Y-%m-%d %H:%i'))"),$date->format('Y-m-d H:i'))->get();
        foreach($bookings as $booking) {
            $devices = UserDevices::whereIn('user_id', [$booking->user_id])->pluck('device_token')->toArray();
            $user_detail = UserDetail::where('user_id', $booking->user_id)->first();
            $language_id = $user_detail->language_id;
            $key = Notice::HOUR_2_BEFORE_VISIT.'_'.$language_id;
            $format = __("notice.$key", ['name' => $booking->main_name]);
            $title_msg = '';
            $notify_type = Notice::HOUR_2_BEFORE_VISIT;
            $notice = Notice::create([
                'notify_type' => Notice::HOUR_2_BEFORE_VISIT,
                'user_id' => $booking->user_id,
                'to_user_id' => $booking->user_id,
                'entity_type_id' => $booking->entity_type_id,
                'entity_id' => $booking->id,
                'title' => $booking->main_name,
                'sub_title' => $booking->booking_date,
            ]);

            if($booking && $booking->entity_type_id == EntityTypes::SHOP) {
                $notificationData = RequestedCustomer::join('shops','shops.id','=','requested_customer.entity_id')
                ->join('category','category.id','=','shops.category_id')
                ->where('requested_customer.entity_type_id',EntityTypes::SHOP)
                ->where('requested_customer.id',$booking->id)
                ->select(['requested_customer.*','shops.main_name','shops.category_id'])->first()->toArray();
            }else {
                $notificationData = RequestedCustomer::join('posts','posts.id','=','requested_customer.entity_id')
                                ->join('hospitals','hospitals.id','=','posts.hospital_id')
                                ->join('category','category.id','=','posts.category_id')
                                ->where('requested_customer.entity_type_id',EntityTypes::HOSPITAL)
                                ->where('requested_customer.id',$booking->id)
                                ->select(['requested_customer.*','hospitals.main_name','posts.id as post_id','posts.category_id','category.name as category_name'])->first()->toArray();
            } 
            if (count($devices) > 0) {
                $result = $this->sentPushNotification($devices,$title_msg, $format, $notificationData ,$notify_type, $booking->id);                        
            }
        }

        //Send Notification to shop/hospital before 1 hours
        $date = Carbon::now()->addHours(1);
        $userBookings = RequestedCustomer::where('request_booking_status_id',RequestBookingStatus::BOOK)->where(DB::raw("(DATE_FORMAT(booking_date,'%Y-%m-%d %H:%i'))"),$date->format('Y-m-d H:i'))->get();
        foreach($userBookings as $booking) {
            if($booking->entity_user_id) {
                $devices = UserDevices::whereIn('user_id', [$booking->entity_user_id])->pluck('device_token')->toArray();
                $user_detail = UserDetail::where('user_id', $booking->entity_user_id)->first();
                $language_id = $user_detail->language_id;
                $key = Notice::HOUR_1_BEFORE_VISIT.'_'.$language_id;
                $format = __("notice.$key", ['name' => $booking->user_name]);
                $title_msg = '';
                $notify_type = Notice::HOUR_1_BEFORE_VISIT;
    
                $notice = Notice::create([
                    'notify_type' => Notice::HOUR_1_BEFORE_VISIT,
                    'user_id' => $booking->entity_user_id,
                    'to_user_id' => $booking->entity_user_id,
                    'entity_type_id' => $booking->entity_type_id,
                    'entity_id' => $booking->id,
                    'title' => $booking->main_name,
                    'sub_title' => $booking->booking_date,
                ]);

                if($booking && $booking->entity_type_id == EntityTypes::SHOP) {
                    $notificationData = RequestedCustomer::join('shops','shops.id','=','requested_customer.entity_id')
                    ->join('category','category.id','=','shops.category_id')
                    ->where('requested_customer.entity_type_id',EntityTypes::SHOP)
                    ->where('requested_customer.id',$booking->id)
                    ->select(['requested_customer.*','shops.main_name','shops.category_id'])->first()->toArray();
                }else {
                    $notificationData = RequestedCustomer::join('posts','posts.id','=','requested_customer.entity_id')
                                    ->join('hospitals','hospitals.id','=','posts.hospital_id')
                                    ->join('category','category.id','=','posts.category_id')
                                    ->where('requested_customer.entity_type_id',EntityTypes::HOSPITAL)
                                    ->where('requested_customer.id',$booking->id)
                                    ->select(['requested_customer.*','hospitals.main_name','posts.id as post_id','posts.category_id','category.name as category_name'])->first()->toArray();
                }   
                if (count($devices) > 0) {
                    $result = $this->sentPushNotification($devices,$title_msg, $format, $notificationData ,$notify_type, $booking->id);                        
                }
            }
        }

        $this->info('Booking:Cron Cummand Run successfully!');
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
