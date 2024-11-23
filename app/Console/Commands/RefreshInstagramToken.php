<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use App\Models\LinkedSocialProfile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\Admin\InstagramController;

class RefreshInstagramToken extends Command
{
    protected $firebase;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'refresh_instagram_token:cron';

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
        $apiHostURL = config('app.INSTAGRAM_HOST_URL');

        //$access_token = "IGQVJWQThVc05ycks4WWMxWDBIVzl4ajk2ZAkJiR3kwdnlETHZACcERVM1NKSEk5YzFhZAk82amxVUDd1U3puXzc4RzgxTURiRlF4LWtuWks1WjBYX0t1eFRwdjhXVHRDZATZAGZATNoeUR3";
        

        $instaToken = LinkedSocialProfile::where('social_type',LinkedSocialProfile::Instagram)
                ->whereNotNull('shop_id')
                ->whereNotNull('user_id')
                ->whereNotNull('access_token')
                ->whereDate('token_refresh_date','<',Carbon::now()->subDays(58))
                ->get();

        if(!empty($instaToken->toArray())){
            foreach($instaToken as $insta){
                $access_token = $insta->access_token;

                $requestURL = "$apiHostURL/refresh_access_token?grant_type=ig_refresh_token&access_token=$access_token";

                $response = Http::get($requestURL);
                $jsonData = $response->json();

                if(!empty($jsonData) && !empty($jsonData['access_token'])){
                    LinkedSocialProfile::whereId($insta->id)->update([
                        'access_token' => $jsonData['access_token'],
                        'token_refresh_date' => Carbon::now()
                    ]);

                    Log::info("Token refreshed..");
                }
            }
        }
                
        Log::info("inside instagram Refresh Token");
        $this->info('Refresh Token Instagram:Cron Cummand Run successfully!');
    }

}
