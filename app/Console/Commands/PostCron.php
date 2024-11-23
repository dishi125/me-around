<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Status;
use App\Models\Post;
use App\Models\EntityTypes;
use App\Models\UserDetail;
use App\Models\Notice;
use App\Models\RequestedCustomer;
use App\Models\UserDevices;
use Carbon\Carbon;
use App\Util\Firebase;

class PostCron extends Command
{
    protected $firebase;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'post:cron';

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
        $posts = Post::whereIn('status_id',[Status::ACTIVE, Status::FUTURE])->get();
        foreach($posts as $post){
            $fromDate = new Carbon($post->from_date);
            $toDate = new Carbon($post->to_date);
            $date1 = Carbon::now();
            $currentDate = $date1->format('d-m-Y');
            $fromDate1 = $fromDate->format('d-m-Y');
            $toDate1 = $toDate->format('d-m-Y');
            
            $check = Carbon::now()->between($fromDate,$toDate);
            
            if($check || $currentDate == $fromDate1 || $currentDate == $toDate1) {
                Post::where('id', $post->id)->update(['status_id' => Status::ACTIVE]) ;
            }else if($fromDate1 > $currentDate && $toDate1 > $currentDate) {
                Post::where('id', $post->id)->update(['status_id' => Status::FUTURE]) ;
            }else if ($currentDate > $fromDate1 && $currentDate > $toDate1 ) {
                Post::where('id', $post->id)->update(['status_id' => Status::EXPIRE]) ;
                $devices = UserDevices::whereIn('user_id', [$post->user_id])->pluck('device_token')->toArray();
                $user_detail = UserDetail::where('user_id', $post->user_id)->first();
                $language_id = $user_detail->language_id;
                $title_msg = '';
                $key = Notice::POST_EXPIRE.'_'.$language_id;
                $format = __("notice.$key");
                $notify_type = Notice::POST_EXPIRE;
                $notice = Notice::create([
                    'notify_type' => Notice::POST_EXPIRE,
                    'user_id' => $post->user_id,
                    'to_user_id' => $post->user_id,
                    'entity_type_id' => EntityTypes::HOSPITAL,
                    'entity_id' => $post->id,
                    'title' => $post->title,
                    'sub_title' => $post->to_date,
                ]);  

                $notificationData = [
                    'id' => $post->id,
                    'title' => $post->title,
                    'sub_title' => $post->sub_title,
                    'hospital_id' => $post->hospital_id,
                ];

                if (count($devices) > 0) {
                    $result = $this->sentPushNotification($devices,$title_msg, $format, $notificationData ,$notify_type, $post->id);                        
                }
            } 
        }
       
        $this->info('Post:Cron Cummand Run successfully!');
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
