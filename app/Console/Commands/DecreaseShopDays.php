<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DecreaseShopDays extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'decreaseshopday:cron';

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
        $shops = DB::table('shops')->where('count_days','>',0)->get();        
        
        if(!empty($shops)){
            foreach ($shops as $value) {
                if($value->count_days == 1 && $value->is_regular_service == 1){
                    $nextMonth = Carbon::now()->addMonth();
                    $diffDay = Carbon::now()->diffInDays($nextMonth);
                    DB::table('shops')->whereId($value->id)->update(['count_days' => $diffDay,'last_count_updated_at' => Carbon::now()]);
                }else{
                    DB::table('shops')->whereId($value->id)->update(['count_days' => DB::raw('count_days - 1'),'last_count_updated_at' => Carbon::now()]);
                }
            }
        }
    }
}
