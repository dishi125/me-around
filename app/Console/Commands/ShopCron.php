<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Status;
use App\Models\Shop;
use Carbon\Carbon;

class ShopCron extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'shop:cron';

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
        $shops = Shop::where('status_id',Status::INACTIVE)->get();
        foreach($shops as $shop){            
            if($shop && $shop->main_name && $shop->portfolio >= 3) {
                Shop::where('id', $shop->id)->update(['status_id' => Status::ACTIVE,'deactivate_by_user' => 0]) ;
            }
        }
       
        $this->info('Shop:Cron Cummand Run successfully!');
    }
}
