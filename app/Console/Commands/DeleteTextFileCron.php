<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class DeleteTextFileCron extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'deletetextfile:cron';

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
        Log::info("inside delete text file cron");

        $files = File::files(public_path('text_files'));
        foreach ($files as $file) {
            $file_path = public_path('text_files/'.$file->getRelativePathname());
            if (file_exists($file_path)){
                unlink($file_path);
            }
        }

        $this->info('Delete text file:Cron Command Run successfully!');
    }

}
