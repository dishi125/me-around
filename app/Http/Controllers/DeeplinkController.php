<?php

namespace App\Http\Controllers;

use App\Models\InstagramLog;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\LinkedSocialProfile;
use App\Models\ShopConnectLink;
use Illuminate\Support\Facades\Log;
use App\Models\LinkedProfileHistory;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Crypt;

class DeeplinkController extends Controller
{
    public function index(Request $request)
    {

        //$redirectLink = env('MEAROUND_LIVE_LINK');
        $redirectLink = env('MEAROUND_PLAY_STORE_LINK');
        if (preg_match('/(iphone|ipad|ipod)/i', $_SERVER['HTTP_USER_AGENT'])) {
            $redirectLink = env('MEAROUND_APP_STORE_LINK');
        } elseif (preg_match('/(android)/i', $_SERVER['HTTP_USER_AGENT'])) {
            $redirectLink = env('MEAROUND_PLAY_STORE_LINK');
        }

        $data = [
            'redirectLink' => $redirectLink,
        ];

        return view('deeplink', compact('data'));

        //return redirect()->away($redirectLink);
    }

    public function shopDeeplink(Request $request)
    {
        $inputs = $request->all();
        $product_id = $inputs['shop_id'] ?? 1;
        $data = [];
        $defferLink = null;

        $key = 'shop_detail';
        $param = "";
        if (preg_match('/(iphone|ipad|ipod)/i', $_SERVER['HTTP_USER_AGENT'])) {
            $browser = 'ios';
            if (isset($key)) {
                $appLink = env('IOS_APP_DEST_LINK') . '?data=' . $key . '&id=' . $product_id;
                $param = ''; //'?data='.$key.'&id='.$product_id;
            } else {
                $defferLink = env('MEAROUND_APP_STORE_LINK');
            }
        } elseif (preg_match('/(android)/i', $_SERVER['HTTP_USER_AGENT'])) {
            $browser = 'android';
            if (isset($key)) {
                $appLink = env('ANDROID_APP_DEST_LINK') . '&data=' . $key . '&id=' . $product_id;
                $param = '&data=' . $key . '&id=' . $product_id;
            } else {
                $defferLink = env('MEAROUND_PLAY_STORE_LINK');
            }
        } else {
            $browser = 'other';
            // $appLink = env('IOS_APP_DEST_LINK').'?data='.$key.'&id='.$product_id;
            $appLink = env('MEAROUND_PLAY_STORE_LINK');
            $defferLink = env('APP_URL');
        }

        $user_agent = getenv("HTTP_USER_AGENT");
        $os = "Linux";
        if (strpos($user_agent, "Win") !== FALSE) {
            $os = "Windows";
        } elseif (strpos($user_agent, "Mac") !== FALSE) {
            $os = "Mac";
        }

        $data = [
            'browser' => $browser,
            'appLink' => $appLink,
            'defferLink' => $defferLink,
            'param' => $param,
            'os' => $os
        ];

        return view('deeplink.shopdeeplink', compact('data', 'browser', 'appLink', 'defferLink'));
    }

    public function appleRedirect(Request $request)
    {
        try {
            $url = '';
            $url .= 'intent://callback?';
            $url .= http_build_query($request->all());
            $url .= '#Intent;package=com.cis.me_talk;scheme=signinwithapple;end';

            Log::info($url);
            Log::info($request->all());
            return redirect($url);
        } catch (\Exception $e) {
            Log::info($e);
            return response()->json(["success" => false, "message" => "Pages" . trans("messages.insert-error"), "redirect" => route('admin.important-setting.policy-pages.index')], 200);
        }
    }

    public function socialRedirect(Request $request)
    {
        try {
            $inputs = $request->all();
            $code = $inputs['code'] ?? '';
            $apiHostURL = config('app.INSTAGRAM_HOST_URL');
            $client_secret = config('app.client_secret');
            $message = '';
            if (!empty($code)) {
                $message = 'There is something wrong. please try again later.';
                $response = Http::asForm()->post('https://api.instagram.com/oauth/access_token', [
                    'client_id' => config('app.client_id'),
                    'client_secret' => config('app.client_secret'),
                    'grant_type' => config('app.grant_type'),
                    'redirect_uri' => route('social-redirect'),
                    'code' => $code,
                ]);

                // print_r($response);
                $jsonData = $response->json();

                if ($jsonData && isset($jsonData['user_id']) && !empty($jsonData['user_id'])) {
                    $insta_user_id = $jsonData['user_id'];

                    $access_token = $jsonData['access_token'];
                    $requestURL = "$apiHostURL/access_token?grant_type=ig_exchange_token&&client_secret=$client_secret&access_token=$access_token";

                    $tokenresponse = Http::get($requestURL);
                    $tokenjsonData = $tokenresponse->json();

                    if ($tokenjsonData && isset($tokenjsonData['access_token']) && !empty($tokenjsonData['access_token'])) {

                        $shopID = $_COOKIE['insta_shop_id'] ?? '';
                        $shopData = DB::table('shops')->whereId($shopID)->first();
                        $long_access_token = $tokenjsonData['access_token'];

                        if (!empty($shopID)) {
                            $nameRequestURL = "$apiHostURL/me?fields=id,username&access_token=$access_token";
                            $nameresponse = Http::get($nameRequestURL);
                            $namejsonData = $nameresponse->json();
                            $instaUsername = null;
                            if ($namejsonData && isset($namejsonData['username']) && !empty($namejsonData['username'])) {
                                $instaUsername = $namejsonData['username'];
                            }

                            LinkedSocialProfile::updateOrCreate([
                                'social_type' => LinkedSocialProfile::Instagram,
                                'shop_id' => $shopID,
                                'user_id' => $shopData->user_id
                            ], [
                                'social_id' => $insta_user_id,
                                'access_token' => $long_access_token,
                                'social_name' => $instaUsername,
                                'token_refresh_date' => Carbon::now(),
                            ]);

                            InstagramLog::create([
                                "social_id" =>$insta_user_id,
                                "user_id" =>$shopData->user_id,
                                "shop_id" =>$shopID,
                                "social_name" =>$instaUsername,
                                "status" =>InstagramLog::CONNECTED,
                            ]);

                            LinkedProfileHistory::updateOrCreate([
                                'shop_id' => $shopID,
                                'social_id' => $insta_user_id
                            ], [
                                'social_name' => $instaUsername,
                                'access_token' => $long_access_token
                            ]);

                            if (isset($_COOKIE['insta_shop_id'])) {
                                unset($_COOKIE['insta_shop_id']);
                                setcookie('insta_shop_id', '', time() - 3600, '/'); // empty value and old timestamp
                            }
                            if (isset($_COOKIE['insta_shop_link_id'])) {
                                ShopConnectLink::whereId($_COOKIE['insta_shop_link_id'])->update(['is_expired' => 1]);
                                $_SESSION['is_connected_instagram'] = 'yes';
                                setcookie('is_connected_instagram', 'yes', time() + (900 * 30), '/');

                                unset($_COOKIE['insta_shop_link_id']);
                                setcookie('insta_shop_link_id', '', time() - 3600, '/');
                            }
                            $message = "Instagram account connected successfully.";
                        }
                    }
                }
            }
            echo "<div class='insta_popup_close'> $message <span></span></div>";

            echo '<script>
                localStorage.setItem("is_insta_reload","true");
                var timeleft = 5;
                var downloadTimer = setInterval(function(){
                if(timeleft <= 0){
                    clearInterval(downloadTimer);
                    close();
                }
                document.querySelector(".insta_popup_close > span").innerHTML = "Popup autometicaly close after "+timeleft+" seconds";
                timeleft -= 1;
                }, 1000);
            </script>';
            //echo '<script> setTimeout(function(){ close(); }, 2000); </script>';
            return '';
            //  return $message;
        } catch (\Exception $e) {
            Log::info($e);
            return "Something went wrong. please try again.";
            //return response()->json(["success" => false, "message" => "Pages". trans("messages.insert-error"), "redirect" => route('admin.important-setting.policy-pages.index')], 200);
        }
    }

    public function connectLinkView(Request $request,$code = '')
    {
        if(empty($code)) return '';

        //setcookie('is_connected_instagram', 'yes', time() + (900 * 30), '/');
        // LinkID | Shop ID | User ID | Time
        $codeData = Crypt::decrypt($code);
        $codeArray = explode('|',$codeData);
        $linkid = $codeArray[0] ?? '';
        $id = $codeArray[1] ?? '';
        $userid = $codeArray[2] ?? '';
        $timestamp = $codeArray[3] ?? time();

        if(Carbon::createFromTimestamp($timestamp)->lt(Carbon::now()->subDays(2))){
            echo '<div style="margin-top: 40px; font-size: 30px; text-align: center;">This page is expired. Please contact administrator.</div>';
            exit;
        }

        if($linkid){
            $shopLinkData = ShopConnectLink::whereId($linkid)->first();
            if(isset($_COOKIE['is_connected_instagram']) && $_COOKIE['is_connected_instagram'] == 'yes'){
                unset($_COOKIE['is_connected_instagram']);
                setcookie('is_connected_instagram', '', time() - 3600, '/');
                echo '<div style="color:green; margin-top: 40px; font-size: 30px; text-align: center;">Your Instagram account connected successfully.</div>';
                exit;
            }elseif($shopLinkData && $shopLinkData->is_expired == true){
                echo '<div style="margin-top: 40px; font-size: 30px; text-align: center;">This page is expired. Please contact administrator.</div>';
                exit;
            }
        }

        return view('instagram-connect',compact('id','linkid'));
    }
}
