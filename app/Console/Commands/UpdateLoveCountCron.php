<?php

namespace App\Console\Commands;

use App\Models\CardLevel;
use App\Models\UserCardLevel;
use App\Models\UserCardLog;
use App\Models\UserCards;
use App\Models\UserDetail;
use App\Models\UserFeedLog;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class UpdateLoveCountCron extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'updatelovecount:cron';

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
        $all_users = DB::table('users_detail')
            ->whereNull('deleted_at')
            ->where('is_increase_love_count_daily',1)
            ->whereNotNull('increase_love_count')
            ->get(['user_id','increase_love_count']);
        foreach ($all_users as $user){
            $user_applied_card = UserCards::where('user_id',$user->user_id)->where('is_applied',1)->first();
            $love_count = $user_applied_card->love_count + $user->increase_love_count;
            $user_applied_card->love_count = $love_count;
            $user_applied_card->save();

            if($user_applied_card->active_level == CardLevel::DEFAULT_LEVEL) {
                UserCards::whereId($user_applied_card->id)->update(['card_level_status' => UserCards::HAPPY_STATUS]);
            }else{
                UserCardLevel::where('user_card_id',$user_applied_card->id)->update(['card_level_status' => UserCards::HAPPY_STATUS]);
            }

            UserCardLog::create([
                'user_id' => $user->user_id,
                'card_id' => $user_applied_card->id,
                'card_log' => UserCardLog::FEED,
                'created_at' => Carbon::now(),
                'love_count' => (empty($love_count)) ? 0 : $love_count
            ]);
            UserFeedLog::updateOrCreate([
                'user_id' => $user->user_id,
            ],[
                'card_id' => $user_applied_card->id,
                'feed_time' => Carbon::now()
            ]);
        }

        $this->info('updatelovecount:cron Command Run successfully!');
    }

}
