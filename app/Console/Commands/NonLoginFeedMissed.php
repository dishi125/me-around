<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use App\Models\Notice;
use App\Models\UserCardLog;
use App\Models\UserDevices;
use App\Models\NonLoginNotice;
use Illuminate\Console\Command;
use App\Models\NonLoginUserDetail;
use App\Models\UserMissedFeedCard;
use Illuminate\Support\Facades\DB;
use App\Models\NonLoginLoveDetails;
use App\Http\Controllers\Controller;
use App\Models\NonLoginMissedFeedCard;


class NonLoginFeedMissed extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'nonloginfeedmissed:cron';

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
        $users = NonLoginUserDetail::select('*')->get();

        if ($users) {
            foreach ($users as $user) {
                $cardFeedLog = NonLoginLoveDetails::where('device_id', $user->device_id)
                    ->where('card_log', UserCardLog::FEED)
                    ->whereDate('created_at', Carbon::now())
                    ->get();
                
                if (!count($cardFeedLog)) {
                    $user_last_feed = NonLoginLoveDetails::where('device_id', $user->device_id)->where('card_log',UserCardLog::FEED)->orderBy('created_at','DESC')->first();
                    if($user_last_feed) {
                        $missed_feed_count = NonLoginMissedFeedCard::where('user_id',$user->id)->whereDate('missed_date', '>', $user_last_feed->created_at)->count();
                    }else{
                        $missed_feed_count = NonLoginMissedFeedCard::where('user_id',$user->id)->count();
                    }
                     

                    if($missed_feed_count < UserCardLog::MISSED_FOR_DEAD_STATUS){

                        $missedEntry = NonLoginMissedFeedCard::updateOrCreate([
                            'user_id' => $user->id,
                            'missed_date' => Carbon::now()->format('Y-m-d')
                        ]);

                        if ($missedEntry->wasRecentlyCreated) {
                            $language_id = 4;
                            $noticeMissedFeedCount = $missed_feed_count + 1;

                            /* $next_level_key = "language_$language_id.user_missed_card";
                            $next_level_msg = __("messages.$next_level_key", ['dayCount' => $noticeMissedFeedCount]);

                            NonLoginNotice::create([
                                'notify_type' => Notice::USER_MISSED_CARD,
                                'user_id' => $user->id,
                                'entity_type_id' => 3,
                                'entity_id' => 1,
                                'title' => $next_level_msg,
                                'sub_title' => $noticeMissedFeedCount,
                                'is_aninomity' => 0
                            ]);

                            $title_msg = $next_level_msg;
                            $format = '';
                            $notificationData = [
                                'id' => $user->id,
                                'user_id' => $user->id,
                                'title' => $next_level_msg,
                            ];
                            if (!empty($user->device_token)) {
                                $controller = new Controller();
                                $controller->sentPushNotification([$user->device_token],$title_msg, $format, $notificationData ,Notice::USER_MISSED_CARD);
                            } */

                        }
                    }
                }

                

            }
        }
        $this->info('NonLoginFeedMissed:Cron Command Run successfully!');
    }

}
