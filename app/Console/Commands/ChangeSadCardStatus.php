<?php

namespace App\Console\Commands;

use App\Http\Controllers\Controller;
use App\Models\CardLevel;
use App\Models\Notice;
use App\Models\PostLanguage;
use App\Models\UserCardLevel;
use App\Models\UserCardLog;
use App\Models\UserCards;
use App\Models\UserDevices;
use App\Models\UserMissedFeedCard;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ChangeSadCardStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'changesadcardstatus:cron';

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
        $missedCards = UserMissedFeedCard::groupBy('card_id')->pluck('card_id');

        $defaultCard = getDefaultCard();
        foreach ($missedCards as $cardID) {
            $user_last_feed = UserCardLog::where('card_id',$cardID)->where('card_log',UserCardLog::FEED)->orderBy('created_at','DESC')->first();
            if($user_last_feed) {
                $missed_feed_count = UserMissedFeedCard::where('card_id',$cardID)->whereDate('missed_date', '>', $user_last_feed->created_at)->count();
            }else{
                $missed_feed_count = UserMissedFeedCard::where('card_id',$cardID)->count();
            }

            if($missed_feed_count >= UserCardLog::MISSED_FOR_DEAD_STATUS){
                $userCard = UserCards::where('id', $cardID)->first();
                if($userCard->default_cards_riv_id != $defaultCard->id) {
                    UserCards::where('id', $cardID)->update(['status' => UserCards::DEAD_CARD_STATUS]);
                }



                if ($userCard->active_level == CardLevel::DEFAULT_LEVEL) {
                    if($userCard->card_level_status != UserCards::DEAD_STATUS) {
                        $this->SendDeadNotice($userCard);
                    }
                    UserCards::whereId($cardID)->update(['card_level_status' => UserCards::DEAD_STATUS]);
                }else{
                    $userCardLevel = $userCard->cardLevels()->firstWhere('card_level',$userCard->active_level);
                    if($userCardLevel){
                        if($userCardLevel->card_level_status != UserCards::DEAD_STATUS) {
                            $this->SendDeadNotice($userCard);
                        }
                        UserCardLevel::where('id',$userCardLevel->id)->update(['card_level_status' => UserCards::DEAD_STATUS]);
                    }
                }
            }else if($missed_feed_count >= UserCardLog::MISSED_FOR_SAD_STATUS) {
                $userCard = UserCards::where('id', $cardID)->first();
                if ($userCard->active_level == CardLevel::DEFAULT_LEVEL) {
                    UserCards::whereId($cardID)->update(['card_level_status' => UserCards::SAD_STATUS]);
                }else{
                    $userCardLevel = $userCard->cardLevels()->firstWhere('card_level',$userCard->active_level);
                    if($userCardLevel){
                        if($userCardLevel->card_level_status != UserCards::DEAD_STATUS) {
                           // $this->SendDeadNotice($userCard);
                        }
                        UserCardLevel::where('id',$userCardLevel->id)->update(['card_level_status' => UserCards::SAD_STATUS]);
                    }
                }
            }
        }


        $this->info('ChangeSadCardStatus:Cron Command Run successfully!');
    }

    public function SendDeadNotice($userCard)
    {

        $userDetail = DB::table('users_detail')->where('user_id',$userCard->user_id)->first();
        $devices = UserDevices::where('user_id', $userCard->user_id)->pluck('device_token')->toArray();

        $language_id = $userDetail->language_id ?? PostLanguage::ENGLISH;
        $dead_card_key = "language_$language_id.dead_card";
        $dead_card_msg = __("messages.$dead_card_key");


        Notice::create([
            'notify_type' => Notice::DEAD_CARD,
            'user_id' => $userCard->user_id,
            'to_user_id' => $userCard->user_id,
            'entity_type_id' => 3,
            'entity_id' => $userCard->id,
            'title' => $dead_card_msg,
            'sub_title' => 0,
            'is_aninomity' => 0
        ]);


        $title_msg = $dead_card_msg;
        $format = '';
        $notificationData = [
            'id' => $userCard->user_id,
            'user_id' => $userCard->user_id,
            'title' => $dead_card_msg,
        ];
        if (count($devices) > 0) {
            $controller = new Controller();
            $controller->sentPushNotification($devices,$title_msg, $format, $notificationData ,Notice::USER_MISSED_CARD);
        }
    }
}
