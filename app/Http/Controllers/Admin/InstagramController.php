<?php

namespace App\Http\Controllers\Admin;

use App\Jobs\InstaStatusMail;
use App\Jobs\LikeOrderMail;
use App\Jobs\SendMailAllInactiveInsta;
use App\Models\Config;
use App\Models\InstagramCurlLogs;
use App\Models\InstaImportantSetting;
use App\Models\PostLanguage;
use App\Models\ShopPostLikes;
use App\Models\User;
use Carbon\Carbon;
use App\Models\Shop;
use App\Models\Status;
use App\Models\HashTag;
use App\Models\ShopPost;
use App\Jobs\SyncInstagram;
use Illuminate\Http\Request;
use App\Models\MultipleShopPost;
use Illuminate\Support\Facades\DB;
use App\Models\LinkedSocialProfile;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Storage;

class InstagramController extends Controller
{
    public function getInstaPosts()
    {
        echo "Nothing!!!";
    }

    public function syncInstaPosts()
    {
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
                    $shop_id = $insta->shop_id;
                    Log::info("Instagram Account Cron: ".$insta->social_name);
                    $insertedMax = DB::table('shop_posts')->select(DB::raw('MAX(post_order_date) as max_date'))->whereNull('deleted_at')->whereNotNull('instagram_post_id')->where('shop_id',$shop_id)->groupBy('shop_id')->first();

                    //"$apiHostURL/me/media",['access_token' =>  $access_token, 'fields' => $fields ,'limit' => $limit ]
                    $checkIds = []; //DB::table('shop_posts')->whereNull('deleted_at')->whereNotNull('instagram_post_id')->where('shop_id',$shop_id)->pluck('instagram_post_id')->toArray();
                    $requestURL = "$apiHostURL/me/media?access_token=$access_token&fields=$fields&limit=$limit";

                    if($insta->social_name == 'deonaun_official'){
                        SyncInstagram::dispatch($insta);
                    }
                  //  $this->recursivePostsData($requestURL,$insta,$checkIds,$insertedMax);
                }
            }
        }catch (\Exception $e) {
            //Log::info($e);
        }
    }

    public function recursivePostsData($requestURL,$insta,$checkIds,$insertedMax)
    {
        $shop_id = $insta->shop_id;

        $response = Http::get($requestURL);
        $jsonData = $response->json();

        if(!empty($jsonData) && !empty($jsonData['data'])){
            $data = $jsonData['data'];
            $paging = $jsonData['paging'];
            //Log::info(count($jsonData['data']));
            $continueNextPage = true;

            foreach($data as $key => $instaMedia){
                $instaID = $instaMedia['id'];
                $addPost = (object)[];
                $insertData = $updateData = [];

                if(!empty($insertedMax) && !empty($insertedMax->max_date)){
                    $maxDate = $insertedMax->max_date;
                    $instaDate = $instaMedia['timestamp'];

                    if(Carbon::parse($maxDate)->gt(Carbon::parse($instaDate))){
                        $continueNextPage = false;
                        break;
                    }
                }

                //if(!in_array($instaID,$checkIds)){

                    //Image & Video
                    if($instaMedia['media_type'] == 'IMAGE' || $instaMedia['media_type'] == 'VIDEO'){

                        $updateData['shop_id'] = $shop_id;
                        $updateData['instagram_post_id'] = $instaID;

                        $insertData['description'] = $instaMedia['caption'] ?? '';
                        $insertData['is_multiple'] = 0;

                        $this->insertPostsData($updateData, $insertData, $shop_id,$instaMedia,$addPost,0);

                    }elseif($instaMedia['media_type'] == 'CAROUSEL_ALBUM'){
                        $apiHostURL = config('app.INSTAGRAM_HOST_URL');
                        $fields = config('app.INSTAGRAM_CHILD_FIELDS');
                        $limit = config('app.INSTAGRAM_POST_LIMIT');

                        $multiResponse = Http::get("$apiHostURL/$instaID/children",['access_token' =>  $insta->access_token, 'fields' => $fields ,'limit' => $limit ]);
                        $multiJsonData = $multiResponse->json();

                        if(!empty($multiJsonData) && !empty($multiJsonData['data'])){
                            $multiData = $multiJsonData['data'];
                            foreach($multiData as $multiKey => $multiInstaMedia){

                                if($multiKey == 0){
                                    $updateData['shop_id'] = $shop_id;

                                    $insertData['description'] = $instaMedia['caption'] ?? '';
                                    $insertData['is_multiple'] = 1;
                                }else{
                                    unset($updateData['shop_id']);
                                }
                                $updateData['instagram_post_id'] = $instaID;

                                $addPost = $this->insertPostsData($updateData,$insertData, $shop_id,$multiInstaMedia,$addPost,$multiKey);
                            }
                        }

                    }


                //}
            }

            if(!empty($paging) && isset($paging['next']) && !empty($paging['next']) && $continueNextPage == true){
                //Log::info("call next page");
                $this->recursivePostsData($paging['next'],$insta,$checkIds,$insertedMax);
            }
        }
    }

    public function insertPostsData($updateData,$insertData,$shop_id,$instaMedia,$addPost,$fieldKey = 0)
    {
        $isImage = '';
        $shopsFolder = config('constant.shops') . "/posts/$shop_id/";
        if (!Storage::exists($shopsFolder)) {
            Storage::makeDirectory($shopsFolder);
        }

        $insertData['post_order_date'] = Carbon::parse($instaMedia['timestamp']);
        if($instaMedia['media_type'] == 'IMAGE'){
            $insertData['type'] = 'image';
        }else{
            $insertData['type'] = 'video';
        }

        $url = $instaMedia['media_url'];
        $filename = basename(parse_url($url, PHP_URL_PATH));
        $contents = file_get_contents($url);

        if (!empty($contents)) {
            $isImage = $image_url = $shopsFolder . $filename;
            Storage::disk('s3')->put($image_url, $contents,'public');
            $insertData['post_item'] =  $image_url;
        }

        if($instaMedia['media_type'] == 'VIDEO' && isset($instaMedia['thumbnail_url']) && !empty($instaMedia['thumbnail_url'])){
            $thumbContents = file_get_contents($instaMedia['thumbnail_url']);
            if(!empty($thumbContents)){
                $fileThumbName = basename(parse_url($instaMedia['thumbnail_url'], PHP_URL_PATH));
                $image_thumb_url = $shopsFolder . $fileThumbName;

                $postThumbImage = Storage::disk('s3')->put($image_thumb_url, $thumbContents, 'public');
                $fileThumbName = basename($postThumbImage);
                $insertData['video_thumbnail'] =  $image_thumb_url;
            }
        }

        if($instaMedia['media_type'] == 'IMAGE'){
            $newThumb = Image::make($instaMedia['media_url'])->resize(200, 200, function ($constraint) {
                $constraint->aspectRatio();
            })->encode(null,90);
            Storage::disk('s3')->put($shopsFolder.'thumb/'.$filename,  $newThumb->stream(), 'public');
        }
        if(!empty($isImage)){
            if($fieldKey == 0){
                $insta_type = User::join('shops', 'shops.user_id', 'users.id')
                    ->where('shops.id',$shop_id)
                    ->pluck('users.insta_type')
                    ->first();
                $remain_download_insta = null;
                if ($insta_type=="pro"){
                    $remain_download_insta = null;
                }
                elseif ($insta_type=="free"){
                    $exist_shopPost = ShopPost::where('shop_id',$updateData['shop_id'])->where('instagram_post_id',$updateData['instagram_post_id'])->pluck('remain_download_insta')->first();
                    $default_limit = InstaImportantSetting::where('field','Default download')->pluck('value')->first();
                    $remain_download_insta = ($exist_shopPost) ? $exist_shopPost : $default_limit;
                }
                $insertData['remain_download_insta'] = $remain_download_insta;
                $addPost = ShopPost::firstOrCreate($updateData,$insertData);
                // dishita code - start
                if($addPost->wasRecentlyCreated == true){
                    InstagramCurlLogs::where('is_error',0)
                        ->where('shop_id',$updateData['shop_id'])
                        ->where('instagram_id',$updateData['instagram_post_id'])
                        ->delete();
                }
                // dishita code - end

                //send mail to multiple
                if($addPost->wasRecentlyCreated){
                    $shopData = DB::table('shops')->where('id',$addPost->shop_id)->whereNull('deleted_at')->first();
                    if(!empty($shopData) && $shopData->count_days > 0){
                        $user = DB::table('users')->where('id', $shopData->user_id)->whereNull('deleted_at')->first();

                        LikeOrderMail::dispatch($addPost, $shopData, $user);
                    }
                }

                if(isset($instaMedia['caption']) && !empty($instaMedia['caption']) && $addPost->wasRecentlyCreated == true){
                    saveHashTagDetails($instaMedia['caption'],$addPost->id,HashTag::SHOP_POST);
                }
                /* Log::info($addPost->id);
                if(!$addPost->wasRecentlyCreated && $addPost->wasChanged()){
                    Log::info($addPost->id.' updateOrCreate performed an update');
                } */
            }else{
                $updateData['shop_posts_id'] = $addPost->id;
               // Log::info($updateData);
                MultipleShopPost::firstOrCreate($updateData,$insertData);
            }
        }

        return $addPost;
    }

    public function indexList()
    {
        $title = __('menu.instagram_account');
        return view('admin.instagram-account.index',compact('title'));
    }

    public function getJsonAllData(Request $request)
    {
        $adminTimezone = $this->getAdminUserTimezone();

        try {
            $columns = array(
                0 => 'shops.main_name',
                1 => 'shops.shop_name',
                2 => 'shops.shop_name',
                3 => 'social_name',
                4 => 'linked_social_profiles.created_at',
                5 => 'users.email',
                6 => 'last_access',
            );

            $filter = $request->input('filter');
            $limit = $request->input('length');
            $start = $request->input('start');
            $search = $request->input('search.value');
            $draw = $request->input('draw');
            $order = $columns[$request->input('order.0.column')];
            $dir = $request->input('order.0.dir');

            $query = Shop::select(
                        'shops.*',
                        'linked_social_profiles.id as insta_id',
                        'linked_social_profiles.created_at as signup_date',
                        'linked_social_profiles.is_valid_token as is_valid_token',
                        'linked_social_profiles.invalid_token_date',
                        DB::raw('IFNULL(linked_profile_histories.social_name, linked_social_profiles.social_name) as social_name'),
                        DB::raw('IFNULL(linked_social_profiles.social_id, "") as is_connect'),
                        'linked_profile_histories.last_disconnected_date',
                        'users.email as user_email',
                        'linked_social_profiles.mail_count',
                        'linked_social_profiles.last_send_mail_at'
                    )
                    ->leftjoin('linked_social_profiles','linked_social_profiles.shop_id','shops.id')
                    ->leftjoin('linked_profile_histories', function ($join) {
                        $join->on('shops.id', '=', 'linked_profile_histories.shop_id');
                    })
                    ->leftjoin('users', function ($join) {
                        $join->on('linked_social_profiles.user_id', '=', 'users.id')
                            ->whereNull('users.deleted_at');
                    })
                    ->where(function ($q){
                        $q->whereNotNull('linked_social_profiles.id')
                        ->orWhereNotNull('linked_profile_histories.id');
                    })
                    /* ->selectSub(function($q) {
                        $q->select( DB::raw('count(non_login_love_details.id) as count'))->from('non_login_love_details')->whereRaw("`non_login_love_details`.`device_id` = `non_login_user_details`.`device_id`");
                    }, 'love_count') */
                    //->havingRaw('love_count > 0')
                    //->whereNotNull('username')
                    ;

            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('shops.main_name', 'LIKE', "%{$search}%")
                    ->orWhere('shops.shop_name', 'LIKE', "%{$search}%");
                });
            }

            if(!empty($filter) && $filter != 'all'){
                if($filter == 'active'){
                    $query->whereNotNull('linked_social_profiles.social_id')->where('linked_social_profiles.is_valid_token',1);
                }elseif($filter == 'inactive'){
                    $query->whereNotNull('linked_social_profiles.social_id')->where('linked_social_profiles.is_valid_token',0);
                }elseif($filter == 'disconnect'){
                    $query->whereNull('linked_social_profiles.social_id');
                }
            }

            $totalData = count($query->get());
            $totalFiltered = $totalData;

            $users = $query->offset($start)
                ->limit($limit)
                ->orderBy($order, $dir)
                ->get();

//            dd($users->toArray());
            $data = array();
            if (!empty($users)) {
                foreach ($users as $value) {
                    $id = $value['id'];
                    $insta_id = $value['insta_id'];
                    $send_mail_btn = $last_send_mail_at = "";

                    $nestedData['active_name'] = $value['main_name'];
                    $nestedData['shop_name'] = $value['shop_name'];
                    $nestedData['instagram'] = $value['social_name'];
                    $nestedData['signup_date'] = $this->formatDateTimeCountryWise($value['signup_date'],$adminTimezone);

                    $viewLink = route('admin.business-client.shop.show', $id);
                    $nestedData['view_shop'] = "<a role='button' href='$viewLink' title='' class='btn btn-primary btn-sm mr-3'>See</a>";

                    if(!empty($value['is_connect'])){
                        if($value['is_valid_token'] == 0){
                            $nestedData['status'] = '<span class="badge" style="background-color: #fff700;">&nbsp;</span><span class="ml-1">'.$value['invalid_token_date'].'</span>';
                            $send_mail_btn = '<a role="button" href="javascript:void(0)" title="" class="mx-1 btn btn-primary btn-sm sendmail" data-toggle="tooltip" data-id="'.$value['insta_id'].'">Send Mail ('.$value['mail_count'].')</a>';
                            $last_send_mail_at = ($value['last_send_mail_at']!=null) ? '<p>Last sent at: '.$this->formatDateTimeCountryWise($value['last_send_mail_at'],$adminTimezone).'</p>' : "";
                        }else{
                            $nestedData['status'] = '<span class="badge badge-success">&nbsp;</span>';
                        }
//                        $connectButton = view('admin.business-client.disconnect-instagram', compact('id','insta_id'))->render();
                        $connectButton = '<button class="btn btn-primary btn-sm connect ml-3 mr-1 p-1 pl-2 pr-2 rounded" onclick="disconnectInsta('.$id.','.$insta_id.')">Disconnect Instagram</button>';
                    }else{
                        $nestedData['status'] = '<span class="badge badge-secondary">&nbsp;</span><span class="ml-1">'.$value['last_disconnected_date'].'</span>';
                        $connectButton = "<a class='btn btn-primary btn-sm connect ml-3 mr-1 p-1 pl-2 pr-2 rounded' href='javascript:void(0);' onclick='connectInstagram(`https://www.instagram.com/oauth/authorize?client_id=".config('app.client_id')."&redirect_uri=".route('social-redirect')."&scope=user_profile,user_media&response_type=code`,$id);'>Connect Instagram</a>";
                    }

                    $nestedData['email'] = $value['user_email'].$send_mail_btn.$last_send_mail_at;

                    $isConnect = (!empty($value['is_connect'])) ? "Connected" : "Disconnected";

                 //   $connectButton = "<a class='btn btn-primary btn-sm connect ml-3 mr-1 p-1 pl-2 pr-2 rounded' href='javascript:void(0);' onclick='connectInstagram(`https://www.instagram.com/oauth/authorize?client_id=".config('app.client_id')."&redirect_uri=".route('social-redirect')."&scope=user_profile,user_media&response_type=code`,$id);'>Connect Instagram</a>";
                  //  $connect = '<a class="btn btn-primary btn-sm connect ml-3 mr-1 p-1 pl-2 pr-2 rounded" href="javascript:void(0);" onclick="connectInstagram('.'"https://www.instagram.com/oauth/authorize?client_id={{config('app.client_id')}}&redirect_uri={{route('social-redirect')}}&scope=user_profile,user_media&response_type=code"'.',$shop->id');">

                    $nestedData['actions'] = "<div class='d-flex'> $connectButton </div>";

                    $data[] = $nestedData;
                }
            }

            $jsonData = array(
                "draw" => intval($draw),
                "recordsTotal" => intval($totalData),
                "recordsFiltered" => intval($totalFiltered),
                "data" => $data,
                "query" => $query->toSql()
            );
            return response()->json($jsonData);
        } catch (\Exception $ex) {
            //Log::info($ex);
            return response()->json(array(
                "draw" => 0,
                "recordsTotal" => 0,
                "recordsFiltered" => 0,
                "data" => []
            ));
        }
    }

    public function statusSendMail($insta_id){
        $query = LinkedSocialProfile::select(
            'linked_social_profiles.*',
            'users_detail.language_id',
            'users.email'
            )
            ->leftjoin('users_detail', function ($join) {
                $join->on('linked_social_profiles.user_id', '=', 'users_detail.user_id')
                    ->whereNull('users_detail.deleted_at');
            })
            ->leftjoin('users', function ($join) {
                $join->on('linked_social_profiles.user_id', '=', 'users.id')
                    ->whereNull('users.deleted_at');
            })
            ->where('linked_social_profiles.id',$insta_id)
            ->first();

        $img_url = "";
        $subject = "";
        $adminTimezone = $this->getAdminUserTimezone();
        if ($query['language_id']==PostLanguage::ENGLISH){
            $img_url = asset('img/eng_insta_disconnect.png');
            $subject = "[MeAround] Instagram sync is broken. please reconnect";
        }
        else if ($query['language_id']==PostLanguage::KOREAN){
            $img_url = asset('img/Kor_insta_disconnect.png');
            $subject = "[MeAround] 인스타그램 동기화가 풀렸습니다. 다시 연결해 주세요";
        }
        else if ($query['language_id']==PostLanguage::JAPANESE){
            $img_url = asset('img/jap_insta_disconnect.png');
            $subject = "[MeAround]インスタグラムの同期が解除されました。 再接続してください";
        }

        $mailData = (object)[
            'email' => $query['email'],
            'social_name' => $query['social_name'],
            'img_url' => $img_url,
            'deeplink' => "http://app.mearoundapp.com/me-talk/deeplink",
            'subject' => $subject
        ];
        InstaStatusMail::dispatch($mailData);
        $query->mail_count = $query->mail_count + 1;
        $query->last_send_mail_at = Carbon::now();
        $query->save();

        $last_send_mail_at = $this->formatDateTimeCountryWise($query->last_send_mail_at,$adminTimezone);
        return ['success' => true , 'mail_count' => $query->mail_count, 'last_send_mail_at' => $last_send_mail_at];
    }

    public function statusSendMailAll(){
        $yellow_status_data = DB::table('shops')->select(
            'linked_social_profiles.social_name',
            'linked_social_profiles.id',
            'users_detail.language_id',
            'users.email'
            )
            ->leftjoin('linked_social_profiles','linked_social_profiles.shop_id','shops.id')
            ->leftjoin('linked_profile_histories', function ($join) {
                $join->on('shops.id', '=', 'linked_profile_histories.shop_id');
            })
            ->leftjoin('users', function ($join) {
                $join->on('linked_social_profiles.user_id', '=', 'users.id')
                    ->whereNull('users.deleted_at');
            })
            ->leftjoin('users_detail', function ($join) {
                $join->on('linked_social_profiles.user_id', '=', 'users_detail.user_id')
                    ->whereNull('users_detail.deleted_at');
            })
            ->where(function ($q){
                $q->whereNotNull('linked_social_profiles.id')
                    ->orWhereNotNull('linked_profile_histories.id');
            })
            ->whereNull('shops.deleted_at')
            ->whereNotNull('linked_social_profiles.social_id')->where('linked_social_profiles.is_valid_token',0)
            ->get();

        SendMailAllInactiveInsta::dispatch($yellow_status_data);
        /*$query->mail_count = $query->mail_count + 1;
        $query->last_send_mail_at = Carbon::now();
        $query->save();*/

        return ['success' => true];
    }

    public function duplicateIssue()
    {
        Log::info("Start Duplicate");
        try{
            $updateData = array(
                'shop_id' => 9,
                'instagram_post_id' => 1212121,
                'type' => 'image',
                'post_item' => 'uploads/shops/posts/189/301248935_186601070406383_5757667060828649227_n.webp',
            );
           /*  $insertData = array(
                'type' => 'image',
                'post_item' => 'uploads/shops/posts/189/301248935_186601070406383_5757667060828649227_n.webp',
            );
            $data = ShopPost::firstOrCreate($updateData,$insertData); */
            $data = ShopPost::create($updateData);

            Log::info($data);
        }catch(\Throwable $e){
            Log::info($e->getMessage());
        }
        Log::info("End Duplicate");
    }
}

