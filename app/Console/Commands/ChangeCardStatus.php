<?php

namespace App\Console\Commands;

use App\Models\CardLevel;
use App\Models\UserCardLevel;
use App\Models\UserCardLog;
use App\Models\UserCards;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ChangeCardStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'changecardstatus:cron';

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
        $cards = UserCardLog::select("*")
            ->orderBy('created_at', 'desc')
            ->where('created_at', '<=', Carbon::now()->format("Y-m-d H:i:s"))
            ->where('created_at', '>=', Carbon::now()->subDay()->format("Y-m-d H:i:s"))
            ->where('card_log',UserCardLog::FEED)
            ->get()
            ->unique('card_id')
            ->values();

        if ($cards) {
            foreach ($cards as $cardData) {
                if ((Carbon::parse($cardData->created_at))->lt(Carbon::now()->subHour())) {
                    $userCard = UserCards::where('id', $cardData->card_id)->first();
                    if ($userCard->active_level == CardLevel::DEFAULT_LEVEL) {
                        if($userCard->card_level_status == UserCards::HAPPY_STATUS){
                            UserCards::whereId($cardData->card_id)->update(['card_level_status' => UserCards::NORMAL_STATUS]);
                        }
                    }else{
                        $userCardLevel = $userCard->cardLevels()->firstWhere('card_level',$userCard->active_level);
                        if($userCardLevel && $userCardLevel->card_level_status == UserCards::HAPPY_STATUS){
                            UserCardLevel::where('id',$userCardLevel->id)->update(['card_level_status' => UserCards::NORMAL_STATUS]);
                        }
                    }
                }
            }
        }


        $this->info('ChangeCardStatus:Cron Command Run successfully!');
    }

}
