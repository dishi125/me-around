<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\UserDevices;
use App\Models\Notice;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;

class SendNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $notificationData;
    protected $title_msg;
    protected $position;
    protected $format;
    protected $notify_type;
    protected $user_id;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($format, $title_msg, $notificationData,$position,$notify_type, $user_id)
    {
        $this->format = $format;
        $this->title_msg = $title_msg;
        $this->notificationData = $notificationData;
        $this->position = $position;
        $this->notify_type = $notify_type;
        $this->user_id = $user_id;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        
        try { 


            $devices = UserDevices::whereIn('user_id', [$this->user_id])->pluck('device_token')->toArray();

            if (count($devices) > 0) {
                (new Controller)->sentPushNotification($devices,$this->title_msg, $this->format, $this->notificationData ,$this->notify_type, $this->position);                        
                Log::info('Send Notification');
            }
        } catch (\Exception $ex) {
            Log::info('Exception in Job');
            Log::info($ex);
        }
    }
}
