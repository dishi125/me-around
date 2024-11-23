<?php

namespace App\Console\Commands;

use Log;
use Carbon\Carbon;
use Illuminate\Console\Command;
use App\Models\Level;
use App\Models\Cards;
use App\Models\DefaultCards;
use Illuminate\Support\Facades\DB;

class LevelUpdate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'levelupdate:cron';

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
        $cardLevelIncrease = 20;
        $defaultCardsLevelIncrease = 80;
        $pointIncrease = 400;

        $maxLevelUser = DB::table('users_detail')->whereNull('deleted_at')->max('level');

        $allLevels = DB::table('levels')->get();
        $maxLevelPoints = collect($allLevels)->max('points');
        $maxLevel = DB::table('levels')->where('points',$maxLevelPoints)->first();
        $maxLevelNumber = (int) filter_var($maxLevel->name, FILTER_SANITIZE_NUMBER_INT);

        $maxStartLevel = DB::table('cards')->max('start');
        $maxEndLevel = DB::table('cards')->max('end');
        $maxCardNumber = DB::table('cards')->max('card_number');

        $maxStartDefault = DB::table('default_cards')->max('start');
        $maxEndDefault = DB::table('default_cards')->max('end');

        if($maxLevelUser > $maxStartLevel){
            $updateMaxStartLevel = $maxStartLevel + $cardLevelIncrease;
            $updateMaxEndLevel = $maxEndLevel + $cardLevelIncrease;
            Cards::firstOrCreate([
                'start' => $updateMaxStartLevel,
                'end' => $updateMaxEndLevel,
                'card_number' => $maxCardNumber+1,
            ]);

            if($updateMaxStartLevel == $maxLevelNumber+1){
                for($lv = $updateMaxStartLevel; $lv <= $updateMaxEndLevel; $lv++){
                    $name = "Lv$lv";
                    $maxLevelPoints += $pointIncrease;
                    Level::firstOrCreate([
                        'name' => $name,
                        'points' => $maxLevelPoints
                    ]);
                }
            }
        }
        if($maxLevelUser > $maxStartDefault){    
            $updateMaxStartDefault = $maxStartDefault + $defaultCardsLevelIncrease;
            $updateMaxEndDefault = $maxEndDefault + $defaultCardsLevelIncrease;
            DefaultCards::firstOrCreate([
                'name' => "Lv. ".$updateMaxStartDefault,
                'start' => $updateMaxStartDefault,
                'end' => $updateMaxEndDefault,
            ]);
        }
       $this->info('LevelUpdate:Cron Cummand Run successfully!');
    }

}
