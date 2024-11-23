<?php

namespace App\Console\Commands;

use App\Models\Challenge;
use App\Models\ChallengeParticipatedUser;
use App\Models\UserDevices;
use App\Util\Firebase;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class VerifyChallenge extends Command
{
    protected $firebase;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'verifychallenge:cron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send notifications to users before 24 hours from challenge';

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
        Log::info("================Start verifychallenge:cron====================");
        $tomorrow_date = Carbon::tomorrow()->format('Y-m-d');
        $dayName = Carbon::tomorrow()->format('D');
        $dayName = strtolower($dayName);
        $tomorrow_day = substr($dayName, 0, 2);

        $period_challenges = Challenge::leftjoin('challenge_days', function ($join) {
                $join->on('challenges.id', '=', 'challenge_days.challenge_id');
            })
            ->whereDate('challenges.start_date', '<=', $tomorrow_date)
            ->whereDate('challenges.end_date', '>=', $tomorrow_date)
            ->where('challenge_days.day',$tomorrow_day)
            ->where('challenges.is_period_challenge',1)
            ->pluck('challenges.id')
            ->toArray();

        $challenges = Challenge::whereDate('date',$tomorrow_date)
            ->where('is_period_challenge',0)
            ->pluck('challenges.id')
            ->toArray();

        $challengeIds = array_merge($period_challenges,$challenges);
        $participated_users = ChallengeParticipatedUser::whereIn('challenge_id',$challengeIds)
            ->select('challenge_id','user_id')
            ->get()
            ->groupBy('challenge_id')
            ->toArray();
        foreach ($participated_users as $challengeId=>$users){
            $userIds = array_column($users,'user_id');
//        dd($challengeId,$userIds);
            $devices = UserDevices::whereIn('user_id',$userIds)->pluck('device_token')->toArray();
            if (count($devices) > 0) {
                $challenge = Challenge::where('id',$challengeId)->select('title','verify_time')->first();

                $timeArr = explode(":",$challenge->verify_time);
                $time = isset($timeArr[2]) ? $timeArr[0].":".$timeArr[1] : $challenge->verify_time; //remove seconds
                $dbTime = \Carbon\Carbon::createFromFormat('H:i', $time, "UTC");
                $userTime = $dbTime->setTimezone("Asia/Seoul");
                $verify_time = $userTime->format('H:i');

                $message = "You need to verify '$challenge->title' at $verify_time";
                $result = $this->sentPushNotification($devices,$message,$message,[],"verify_challenge");
            }
        }

        Log::info("================End verifychallenge:cron====================");
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
