<?php

namespace App\Console\Commands;

use App\Models\Shop;
use App\Models\ShopPost;
use Illuminate\Console\Command;
use App\Models\InstagramCurlLogs;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\Admin\InstagramController;

class SyncInstagramPosts extends Command
{
    protected $firebase;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync_instagram_posts:cron';

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
        ini_set('memory_limit', '2048M');
        Log::info("inside instagram cron start");
        $conObj = new InstagramController();

        $outerQuery = InstagramCurlLogs::leftjoin('linked_social_profiles', function($query) {
            $query->on('linked_social_profiles.social_id','=','instagram_curl_logs.social_id')
            ->on('linked_social_profiles.shop_id','=','instagram_curl_logs.shop_id');
        })
        ->join('shops', function($query) {
            $query->on('shops.id','=','instagram_curl_logs.shop_id')->whereNull('shops.deleted_at');
        })
        ->where('instagram_curl_logs.is_error',0)
        ->select(DB::raw('COUNT(instagram_curl_logs.shop_id) as available_post'),'instagram_curl_logs.shop_id')
        ->groupBy('instagram_curl_logs.shop_id')
        ->orderByRaw('available_post','ASC')
        ->orderBy('instagram_curl_logs.id','ASC')
        ->get();


        if($outerQuery){
            $defaultCount = 50;
            $diffCount = 0;
            if($defaultCount < count($outerQuery)){
                $defaultCount = count($outerQuery);
            }

            foreach ($outerQuery as $key => $outerValue) {
                $defaultCount += $diffCount;
                $limitCount = (int)($defaultCount/(count($outerQuery) - $key));
                $queryLimitCount = ($outerValue->available_post && $outerValue->available_post < $limitCount ) ? $outerValue->available_post : $limitCount;

                $diffCount = $limitCount - $queryLimitCount;

                $added_post_insta_ids = ShopPost::where('shop_id',$outerValue->shop_id)->whereNotNull('instagram_post_id')->pluck('instagram_post_id')->toArray();
                $insertedPosts = InstagramCurlLogs::leftjoin('linked_social_profiles', function($query) {
                        $query->on('linked_social_profiles.social_id','=','instagram_curl_logs.social_id')
                        ->on('linked_social_profiles.shop_id','=','instagram_curl_logs.shop_id');
                    })
                    ->join('shops', function($query) {
                        $query->on('shops.id','=','instagram_curl_logs.shop_id')->whereNull('shops.deleted_at');
                    })
                    ->where('instagram_curl_logs.is_error',0)
                    ->where('instagram_curl_logs.shop_id',$outerValue->shop_id)
                    ->whereNotIn('instagram_id',$added_post_insta_ids) //By dishita
                    ->select('linked_social_profiles.access_token','instagram_curl_logs.*')->orderBy('id','ASC')
                    ->limit(5); //By dishita
//                    ->limit($queryLimitCount);

            // Log::info($insertedPosts->toSql());
                $insertedPosts = $insertedPosts->get();

                //Log::info($insertedPosts);
                if (!empty($insertedPosts)) {
                    foreach ($insertedPosts as $post) {
                        try {
                            DB::beginTransaction();
                            $shop_id = $post->shop_id;
                            $instaID = $post->instagram_id;
                            $instaMedia = unserialize($post->post_data);
                            $addPost = (object)[];
                            $insertData = [];

                            if ($instaMedia['media_type'] == 'IMAGE' || $instaMedia['media_type'] == 'VIDEO') {
                                $updateData['shop_id'] = $shop_id;
                                $updateData['instagram_post_id'] = $instaID;

                                $insertData['description'] = $instaMedia['caption'] ?? '';
                                $insertData['insta_link'] = $instaMedia['permalink'] ?? '';
                                $insertData['is_multiple'] = 0;

                                // dishita code - start
                                if (!file_exists($instaMedia['media_url'])){
                                    $apiHostURL = config('app.INSTAGRAM_HOST_URL');
                                    $fields = config('app.INSTAGRAM_FIELDS');

                                    $response = Http::get("$apiHostURL/$instaID",['fields' => $fields,'access_token' => $post->access_token]);
                                    $jsonData = $response->json();
                                    if (!empty($jsonData)){
                                        $instaMedia['media_url'] = $jsonData['media_url'];
                                        if($instaMedia['media_type'] == 'VIDEO' && isset($instaMedia['thumbnail_url']) && !empty($instaMedia['thumbnail_url'])) {
                                            $instaMedia['thumbnail_url'] = $jsonData['thumbnail_url'];
                                        }
                                    }
                                }
                                // dishita code - end

                                $insertPost = $conObj->insertPostsData($updateData, $insertData, $shop_id, $instaMedia, $addPost, 0);

                                if($insertPost->wasRecentlyCreated == true){
                                    $post->delete();
                                }
                            }elseif($instaMedia['media_type'] == 'CAROUSEL_ALBUM'){
                                $apiHostURL = config('app.INSTAGRAM_HOST_URL');
                                $fields = config('app.INSTAGRAM_CHILD_FIELDS');
                                $limit = config('app.INSTAGRAM_POST_LIMIT');

                                $multiResponse = Http::get("$apiHostURL/$instaID/children",['access_token' =>  $post->access_token, 'fields' => $fields ,'limit' => $limit ]);
                                $multiJsonData = $multiResponse->json();

                                if(!empty($multiJsonData) && !empty($multiJsonData['data'])){
                                    $multiData = $multiJsonData['data'];
                                    /* Log::info("***************");
                                    Log::info($instaID);
                                    Log::info(count($multiData)); */
                                    foreach($multiData as $multiKey => $multiInstaMedia){
                                        $multiInstaMedia['caption'] = $instaMedia['caption'];
                                        if($multiKey == 0){
                                            $updateData['shop_id'] = $shop_id;

                                            $insertData['description'] = $instaMedia['caption'] ?? '';
                                            $insertData['is_multiple'] = 1;
                                            $updateData['instagram_post_id'] = $instaID;
                                            $insertData['insta_link'] = $instaMedia['permalink'] ?? '';
                                        }else{
                                            unset($updateData['shop_id']);
                                            $updateData['instagram_post_id'] = $multiInstaMedia['id'];
                                        }

                                        //Log::info("childID -".$multiInstaMedia['id']);
                                        $addPost = $conObj->insertPostsData($updateData,$insertData, $shop_id,$multiInstaMedia,$addPost,$multiKey);
                                    }
                                // Log::info("***************");
                                    if($addPost->wasRecentlyCreated == true){
                                        $post->delete();
                                    }
                                }
                            }

                            DB::commit();
                        } catch (\Throwable $e) {
                            DB::rollBack();
                            InstagramCurlLogs::where('id',$post->id)->update(['is_error' => 1]);
                            Log::info("***** Got error while added posts in DB-start *****");
                            Log::info($post->id);
                            Log::info($e->getMessage());
                            Log::info("***** Got error while added posts in DB-end *****");
                        }
                    }
                }
            }
        }

        Log::info("inside instagram cron End");
        $this->info('Sync Instagram:Cron Cummand Run successfully!');
    }
}
