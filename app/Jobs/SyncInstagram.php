<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Http\Controllers\Admin\InstagramController;

class SyncInstagram implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public $tries = 1;
    private $insta;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($insta)
    {
        $this->insta = $insta;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $apiHostURL = config('app.INSTAGRAM_HOST_URL');
        $fields = config('app.INSTAGRAM_FIELDS');
        $limit = config('app.INSTAGRAM_POST_LIMIT');
        $insta = $this->insta;
        try { 
            //Log::info("Insta Job Start");
            DB::beginTransaction();
            $access_token = $insta->access_token;
            $shop_id = $insta->shop_id;
            Log::info("Instagram Account : ".$insta->social_name);
            $insertedMax = DB::table('shop_posts')->select(DB::raw('MAX(post_order_date) as max_date'))->whereNull('deleted_at')->whereNotNull('instagram_post_id')->where('shop_id',$shop_id)->groupBy('shop_id')->first();
            
            $checkIds = []; //DB::table('shop_posts')->whereNull('deleted_at')->whereNotNull('instagram_post_id')->where('shop_id',$shop_id)->pluck('instagram_post_id')->toArray();
            $requestURL = "$apiHostURL/me/media?access_token=$access_token&fields=$fields&limit=$limit";

            $conObj = new InstagramController();
          //  $conObj->aaa();
            $conObj->recursivePostsData($requestURL,$insta,$checkIds,$insertedMax);
            //Log::info("End Insta Job");
            DB::commit();
        } catch (\Throwable $ex) {
            Log::info('Exception in Insta1');
            DB::rollBack();
            //Log::info($ex);
        }
    }
}
