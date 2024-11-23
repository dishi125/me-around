<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\InstagramCurlLogs;
use Illuminate\Support\Facades\DB;
use App\Models\LinkedSocialProfile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Jobs\SyncInstagramPostSameTime;
use App\Http\Controllers\Admin\InstagramController;

class SyncInstagramPostPages extends Command
{
    protected $firebase;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync_instagram_pages:cron';

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
        Log::info("inside instagram pages start");
       
        try{
            $apiHostURL = config('app.INSTAGRAM_HOST_URL');
            $fields = config('app.INSTAGRAM_FIELDS');
            $limit = config('app.INSTAGRAM_POST_LIMIT');
            //$limit = 1;

            $instaToken = LinkedSocialProfile::where('social_type',LinkedSocialProfile::Instagram)
                ->whereNotNull('shop_id')
                ->whereNotNull('user_id')
                ->whereNotNull('access_token')
                ->get();
                
            if(!empty($instaToken->toArray())){
                foreach($instaToken as $insta){
                    $access_token = $insta->access_token;
                    
                    $requestURL = "$apiHostURL/me/media?access_token=$access_token&fields=$fields&limit=$limit";
                   // if($insta->social_name == 'dev.concetto' || $insta->social_name == 'deonaun_official'){
                        //Log::info("Instagram Account Cron Page: ".$insta->social_name);

                        $checkIDs = DB::table('shop_posts')->whereNull('deleted_at')->whereNotNull('instagram_post_id')->where('shop_id',$insta->shop_id)->pluck('instagram_post_id')->toArray();
                       
                        SyncInstagramPostSameTime::dispatch($requestURL,$insta,$checkIDs);
                       //For sync instant
                       // $this->recursivePostsPageData($requestURL,$insta,$checkIDs);
                        
                   // }

                }
            }           
        }catch (\Exception $e) {
            Log::info($e);
        }
        
        Log::info("inside instagram pages End");
        $this->info('Sync Instagram:Cron Cummand Run successfully!');
    }

    /* public function recursivePostsPageData($requestURL,$insta,$checkIDs)
    {
        $isNextPage = false;
        $shop_id = $insta->shop_id;
        
        $response = Http::get($requestURL);
        $jsonData = $response->json();

        if(!empty($jsonData) && !empty($jsonData['data'])){
            if(!empty($insta) && !empty($insta->id) && $insta->is_valid_token == 0){
                LinkedSocialProfile::whereId($insta->id)->update(['is_valid_token' => 1]);
            }

            $data = $jsonData['data'];
            $paging = $jsonData['paging'];

            foreach($data as $key => $instaMedia){
                $instaID = $instaMedia['id'];

                if(!in_array($instaID,$checkIDs)){
                    $logPost = InstagramCurlLogs::firstOrCreate([
                        'shop_id' => $shop_id,
                        'social_id' => $insta->social_id,
                        'instagram_id' => $instaID
                    ],[
                        'post_data' => serialize($instaMedia),
                    ]);

                    if($logPost->wasRecentlyCreated == true){
                        $isNextPage = true;
                    }
                }
            }
          
            if(!empty($paging) && isset($paging['next']) && !empty($paging['next']) && $isNextPage == true){
                Log::info("Next");
                $this->recursivePostsPageData($paging['next'],$insta,$checkIDs);
            }
        }elseif(!empty($jsonData) && !empty($jsonData['error'])){
            if(!empty($insta) && !empty($insta->id) && $insta->is_valid_token == 1){
                LinkedSocialProfile::whereId($insta->id)->update(['is_valid_token' => 0]);
            }
        }
    } */

}
