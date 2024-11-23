<?php

namespace App\Console\Commands;

use App\Http\Controllers\Controller;
use App\Models\Notice;
use App\Models\User;
use App\Models\UserCardLog;
use App\Models\UserCards;
use App\Models\UserDevices;
use App\Models\UserMissedFeedCard;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;


class FeedMissed extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'feedmissed:cron';

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
        $users = User::join('user_cards', 'user_cards.user_id', 'users.id')
            ->select(
                'users.*',
                'user_cards.id as applied_card_id'
            )
            ->where('user_cards.is_applied', 1)
            ->whereIn('user_cards.status', [UserCards::SOLD_CARD_STATUS, UserCards::REQUESTED_STATUS, UserCards::ASSIGN_STATUS, UserCards::DEAD_CARD_STATUS])
            ->groupBy('users.id')
            ->orderBy('user_cards.user_id', 'ASC')
            ->get();

        if ($users) {
            foreach ($users as $user) {
                $cardFeedLog = UserCardLog::where('user_id', $user->id)
                    ->where('card_id', $user->applied_card_id)
                    ->where('card_log', UserCardLog::FEED)
                    ->whereDate('created_at', Carbon::now())
                    ->get();
                if (!count($cardFeedLog)) {
                    $user_last_feed = UserCardLog::where('card_id',$user->applied_card_id)->where('user_id',$user->id)->where('card_log',UserCardLog::FEED)->orderBy('created_at','DESC')->first();
                    if($user_last_feed) {
                        $missed_feed_count = UserMissedFeedCard::where('card_id',$user->applied_card_id)->where('user_id',$user->id)->whereDate('missed_date', '>', $user_last_feed->created_at)->count();
                    }else{
                        $missed_feed_count = UserMissedFeedCard::where('card_id',$user->applied_card_id)->where('user_id',$user->id)->count();
                    }
                     

                    if($missed_feed_count < UserCardLog::MISSED_FOR_DEAD_STATUS){

                        $missedEntry = UserMissedFeedCard::updateOrCreate([
                            'user_id' => $user->id,
                            'card_id' => $user->applied_card_id,
                            'missed_date' => Carbon::now()->format('Y-m-d')
                        ]);


                        if ($missedEntry->wasRecentlyCreated) {
                            /* $userDetail = DB::table('users_detail')->where('user_id', $user->id)->first();
                            $language_id = $userDetail->language_id ?? 4;

                            $noticeMissedFeedCount = $missed_feed_count + 1;

                            
                            $devices = UserDevices::where('user_id', $user->id)->pluck('device_token')->toArray();
                            $next_level_key = "language_$language_id.user_missed_card";
                            $next_level_msg = __("messages.$next_level_key", ['dayCount' => $noticeMissedFeedCount]);

                            Notice::create([
                                'notify_type' => Notice::USER_MISSED_CARD,
                                'user_id' => $user->id,
                                'to_user_id' => $user->id,
                                'entity_type_id' => 3,
                                'entity_id' => $user->applied_card_id,
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
                            if (count($devices) > 0) {
                                $controller = new Controller();
                                $controller->sentPushNotification($devices,$title_msg, $format, $notificationData ,Notice::USER_MISSED_CARD);
                            } */

                        }
                    }
                }

                /*$appliedCardHistory = UserCardAppliedHistory::where('user_id',$user->id)
                    ->where('applied_date',Carbon::now()->format('Y-m-d'))
                    ->where('new_card_id','!=',$user->applied_card_id)
                    ->get();

                if($appliedCardHistory && !empty($appliedCardHistory->toArray())){
                    foreach ($appliedCardHistory as $cardHistory) {
                        $checkLog = UserCardLog::where('user_id',$user->id)
                            ->where('card_id',$cardHistory->new_card_id)
                            ->where('card_log',UserCardLog::FEED)
                            ->whereDate('created_at',$cardHistory->applied_date)
                            ->get();

                        if(!count($checkLog)){
                            UserMissedFeedCard::updateOrCreate([
                                'user_id' => $user->id,
                                'card_id' => $cardHistory->new_card_id,
                                'missed_date' => Carbon::now()->format('Y-m-d')
                            ]);
                        }
                    }
                }*/

            }
        }
        $this->info('FeedMissed:Cron Command Run successfully!');
    }

}
