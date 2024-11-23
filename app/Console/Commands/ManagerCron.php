<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Status;
use App\Models\Manager;
use Carbon\Carbon;
use Illuminate\Support\Str;

class ManagerCron extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'manager:cron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate New Recommended code for managers daily';

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
        $managers = Manager::all();
        foreach($managers as $manager){                        
            Manager::where('id', $manager->id)->update(['recommended_code' => Str::upper(Str::random(7))]);
        }
       
        $this->info('Manager:Cron Cummand Run successfully!');
    }
}
