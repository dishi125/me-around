<?php

namespace App\Jobs;

use App\Models\InstagramLog;
use App\Models\PostLanguage;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use App\Models\InstagramCurlLogs;
use App\Models\LinkedSocialProfile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class SyncInstagramPostSameTime implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    private $requestURL;
    private $insta;
    private $checkIDs;
    public $tries = 5;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($requestURL,$insta,$checkIDs)
    {
        $this->requestURL = $requestURL;
        $this->insta = $insta;
        $this->checkIDs = $checkIDs;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Log::info("come inside Insta job");
        $this->recursivePostsPageData($this->requestURL,$this->insta,$this->checkIDs);
    }

    public function recursivePostsPageData($requestURL,$insta,$checkIDs)
    {
        $isNextPage = false;
        $shop_id = $insta->shop_id;

        $response = Http::get($requestURL);
        $jsonData = $response->json();

        if(!empty($jsonData) && !empty($jsonData['data'])){
            if(!empty($insta) && !empty($insta->id) && $insta->is_valid_token == 0){
                LinkedSocialProfile::whereId($insta->id)->update(['is_valid_token' => 1, 'invalid_token_date' => null]);
                $insta_profile = LinkedSocialProfile::whereId($insta->id)->first();
                InstagramLog::create([
                    "social_id" =>$insta_profile->social_id,
                    "user_id" =>$insta_profile->user_id,
                    "shop_id" =>$insta_profile->shop_id,
                    "social_name" =>$insta_profile->social_name,
                    "status" =>InstagramLog::CONNECTED,
                ]);
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
                LinkedSocialProfile::whereId($insta->id)->update(['is_valid_token' => 0, 'invalid_token_date' => Carbon::now()]);
                $insta_profile = LinkedSocialProfile::whereId($insta->id)->first();

                $user = DB::table('users')->leftjoin('users_detail', function ($join) {
                        $join->on('users.id', '=', 'users_detail.user_id')
                            ->whereNull('users_detail.deleted_at');
                    })
                    ->whereNull('users.deleted_at')
                    ->where('users.id',$insta_profile->user_id)
                    ->select(
                        'users_detail.language_id',
                        'users.email'
                    )
                    ->first();
                $img_url = "";
                $subject = "";
                if ($user->language_id==PostLanguage::ENGLISH){
                    $img_url = asset('img/eng_insta_disconnect.png');
                    $subject = "[MeAround] Instagram sync is broken. please reconnect";
                }
                else if ($user->language_id==PostLanguage::KOREAN){
                    $img_url = asset('img/Kor_insta_disconnect.png');
                    $subject = "[MeAround] 인스타그램 동기화가 풀렸습니다. 다시 연결해 주세요";
                }
                else if ($user->language_id==PostLanguage::JAPANESE){
                    $img_url = asset('img/jap_insta_disconnect.png');
                    $subject = "[MeAround]インスタグラムの同期が解除されました。 再接続してください";
                }
                $mailData = (object)[
                    'email' => $user->email,
                    'social_name' => $insta_profile->social_name,
                    'img_url' => $img_url,
                    'deeplink' => "http://app.mearoundapp.com/me-talk/deeplink",
                    'subject' => $subject
                ];
                InstaStatusMail::dispatch($mailData);
                InstagramLog::create([
                    "social_id" =>$insta_profile->social_id,
                    "user_id" =>$insta_profile->user_id,
                    "shop_id" =>$insta_profile->shop_id,
                    "social_name" =>$insta_profile->social_name,
                    "status" =>InstagramLog::SOMETHINGDISCONNECTED,
                    "mail_count" => 1
                ]);
            }
        }
    }
}
