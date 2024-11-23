<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\CategoryTypes;
use App\Models\Config;
use App\Models\EntityTypes;
use App\Models\InstagramLog;
use App\Models\InstaImportantSetting;
use App\Models\LinkedProfileHistory;
use App\Models\LinkedSocialProfile;
use App\Models\PackagePlan;
use App\Models\Shop;
use App\Models\ShopPost;
use App\Models\Status;
use App\Models\TempUser;
use App\Models\User;
use App\Models\UserCredit;
use App\Models\UserDetail;
use App\Models\UserDevices;
use App\Models\UserEntityRelation;
use App\Util\Firebase;
use App\Validators\LoginValidator;
use App\Validators\UserValidator;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Http\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use JWTAuth;

class InstaController extends Controller
{
    private $userValidator;
    private $loginValidator;
    protected $firebase;
    public function __construct()
    {
        $this->userValidator = new UserValidator();
        $this->loginValidator = new LoginValidator();
        $this->firebase = new Firebase();
    }

    public function googleSignup(Request $request) {
        DB::beginTransaction();
        $inputs = $request->all();
        try {
            //validate request start
            $validation = $this->userValidator->validateGoogleSignup($inputs);
            if ($validation->fails()) {
                return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
            }
            //validate request end

            $check_user_exist = User::where('email',$inputs['email'])->where('app_type','!=','challenge')->select('signup_type')->get();
            if (count($check_user_exist) > 0){
                if ($check_user_exist[0]['signup_type']=="google"){
                    $err_text = "The email is signed up already (Google)";
                }
                elseif ($check_user_exist[0]['signup_type']=="apple"){
                    $err_text = "The email is signed up already (Apple)";
                }
                else {
                    $err_text = "The email is signed up already";
                }
                return $this->sendFailedResponse($err_text, 403);
            }

            $random_password = Str::random(10);
            $user = User::create([
                "email" => $inputs['email'],
                'username' => $inputs['email'],
                "password" => Hash::make($random_password),
                'status_id' => Status::ACTIVE,
                'app_type' => "insta",
                "signup_type" => "google",
                "org_password" => $random_password,
                "insta_type" => $inputs['user_type']
            ]);

            UserEntityRelation::create(['user_id' => $user->id, "entity_type_id" => EntityTypes::NORMALUSER, 'entity_id' => $user->id]);

            if (isset($request->avatar) && $request->avatar!="" && $request->avatar!=null) {
                $file_url = $request->avatar;
                $fileContents = file_get_contents($file_url);
                $tempFilePath = storage_path('app/public/temp.jpg');
                file_put_contents($tempFilePath, $fileContents);
                $file = new File($tempFilePath);

                $profileFolder = config('constant.profile');
                if (!Storage::exists($profileFolder)) {
                    Storage::makeDirectory($profileFolder);
                }
                $avatar = Storage::disk('s3')->putFile($profileFolder, $file,'public');
                $fileName = basename($avatar);
                $profile_pic = $profileFolder . '/' . $fileName;
                // Delete the temporary file
                unlink($tempFilePath);
            }

            $random_code = mt_rand(1000000, 9999999);
            $member = UserDetail::create([
                'user_id' => $user->id,
//                'phone_code' => $inputs['phone_code'],
//                'mobile' => $inputs['phone'],
                'device_type_id' => $inputs['device_type_id'],
                'device_id' => $inputs['device_id'],
                'device_token' => $inputs['device_token'],
                'recommended_code' => $random_code,
                'name' => $inputs['name'],
                "avatar" => isset($profile_pic) ? $profile_pic : null,
            ]);
            UserDevices::create(['user_id' => $user->id, 'device_token' => $inputs['device_token']]);

            // Instagram Start
            $access_token = $inputs['access_token'] ?? '';
            $social_id = $inputs['social_id'] ?? '';
            $social_name = $inputs['social_name'] ?? '';
            if (!empty($access_token) && !empty($social_id) && !empty($social_name)) {
                User::whereId($user->id)->update([
                    'connect_instagram' => true
                ]);
                $category = Category::where('category_type_id', CategoryTypes::SHOP)->first();
                $business_category_id = $inputs['business_category_id'] ?? $category->id;
                $shop = Shop::create([
                    'email' => $inputs['email'] ?? NULL,
//                    'mobile' => $inputs['phone'],
                    'shop_name' => NULL,
                    'best_portfolio' => NULL,
                    'business_licence' => NULL,
                    'identification_card' => NULL,
                    'business_license_number' => '',
                    'status_id' => Status::PENDING,
                    'category_id' => $business_category_id,
                    'user_id' => $user->id,
                    'manager_id' => '',
                    'uuid' => (string) Str::uuid(),
                    'credit_deduct_date' => Carbon::now()->toDateString()
                ]);
                $entity_id = $shop->id;
                UserEntityRelation::create([
                    'user_id' => $user->id,
                    'entity_type_id' => EntityTypes::SHOP,
                    'entity_id' => $entity_id,
                ]);

                if (!empty($access_token) && !empty($social_id) && !empty($social_name)) {
                    $access_token = $inputs['access_token'] ?? null;
                    LinkedSocialProfile::updateOrCreate([
                        'social_type' => LinkedSocialProfile::Instagram,
                        'shop_id' => $entity_id,
                        'user_id' => $user->id
                    ], [
                        'social_id' => $social_id,
                        'access_token' => $access_token,
                        'social_name' => $social_name,
                        'token_refresh_date' => Carbon::now(),
                    ]);

                    InstagramLog::create([
                        "social_id" =>$social_id,
                        "user_id" =>$user->id,
                        "shop_id" =>$entity_id,
                        "social_name" =>$social_name,
                        "status" =>InstagramLog::CONNECTED,
                    ]);

                    LinkedProfileHistory::updateOrCreate([
                        'shop_id' => $entity_id,
                        'social_id' => $social_id
                    ], [
                        'social_name' => $social_name,
                        'access_token' => $access_token
                    ]);
                }
            }
            // Instagram End

            $token = JWTAuth::fromUser($user);
            $data['id'] = $user->id;
            $data['email'] = $user->email;
            $data['social_id'] = null;
            $data['user_exist'] = null;
            $data['token'] = $token;
            $data['name'] = $inputs['name'];
            $data['avatar'] = $user->avatar;
            $data['user_type'] = $user->insta_type;
            DB::commit();
            return $this->sendSuccessResponse(Lang::get('messages.authenticate.success'), 200, $data);
        } catch (\Throwable $ex) {
            DB::rollback();
            $logMessage = "Something went wrong while signup user";
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function googleLogin(Request $request) {
        DB::beginTransaction();
        $inputs = $request->all();
        try{
            //validate request start
            $validation = $this->userValidator->validateGoogleLogin($inputs);
            if ($validation->fails()) {
                return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
            }
            //validate request end

            $is_email_exist = User::where('email',$inputs['email'])->where('signup_type','google')->first();
            if (empty($is_email_exist)){
                $data['id'] = null;
                $data['email'] = $inputs['email'];
                $data['social_id'] = null;
                $data['user_exist'] = 0;
                $data['token'] = null;
                $data['name'] = $inputs['name'];
                $data['avatar'] = $inputs['avatar'];
                $data['user_type'] = $inputs['user_type'];
                return $this->sendFailedResponse("User not found", 200,$data);
            }

            //check credential as per its data
            $credentials = $this->credentials($inputs['email'], $is_email_exist->org_password);
            if(!$token = JWTAuth::attempt($credentials)){
                return $this->sendFailedResponse("Invalid credentials", 422);
            }
            $user = null;
            if ($token = JWTAuth::attempt($credentials)) {
                $user = User::with(['entityType'])->where('email',$inputs['email'])->where('status_id', Status::ACTIVE)->first();

                if ($user) {
                    $token = JWTAuth::fromUser($user);
                    Auth::login($user);
                    if($inputs['user_type']=="pro" && $user->insta_type!="pro") {
                        $user->insta_type = $inputs['user_type'];
                        $user->save();
                        ShopPost::join('shops', 'shops.id', 'shop_posts.shop_id')
                            ->where('shops.user_id',$user->id)
                            ->update([
                                'remain_download_insta' => null
                            ]);
                    }
                    elseif($inputs['user_type']=="free" && $user->insta_type!="pro" && $user->insta_type!="free") {
                        $user->insta_type = $inputs['user_type'];
                        $user->save();
                        $default_limit = InstaImportantSetting::where('field','Default download')->pluck('value')->first();
                        ShopPost::join('shops', 'shops.id', 'shop_posts.shop_id')
                            ->where('shops.user_id',$user->id)
                            ->update([
                                'remain_download_insta' => ($default_limit) ? (int)$default_limit : 10
                            ]);
                    }
                } else {
                    return $this->sendFailedResponse("Invalid credentials", 422);
                }
            }
            if ($request->has('device_token') && !empty($user)) {
                UserDevices::firstOrCreate(['user_id' => Auth::user()->id, 'device_token' => $inputs['device_token']]);
                UserDetail::where('user_id', Auth::user()->id)->update(['device_token' => $inputs['device_token']]);
            }

            if ($user) {
                $user->update(['last_login' => Carbon::now()]);
                $data['id'] = $user->id;
                $data['email'] = $user->email;
                $data['social_id'] = null;
                $data['user_exist'] = 1;
                $data['token'] = $token;
                $data['name'] = $user->name;
                $data['avatar'] = $user->avatar;
                $data['user_type'] = $inputs['user_type'];
                DB::commit();
                return $this->sendSuccessResponse(Lang::get('messages.authenticate.success'), 200, $data);
            } else {
                if ($token = JWTAuth::getToken()) {
                    JWTAuth::invalidate($token);
                }
                return $this->sendFailedResponse("Invalid credentials", 422);
            }
        } catch(\Throwable $ex){
            DB::rollBack();
            $logMessage = "Something went wrong while login user";
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    protected function credentials($username = "", $password = "")
    {
        return ['email' => $username, 'password' => $password];
    }

    public function appleSignup(Request $request) {
        DB::beginTransaction();
        $inputs = $request->all();

        try {
            //validate request start
            $validation = $this->userValidator->validateAppleSignup($inputs);
            if ($validation->fails()) {
                return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
            }
            //validate request end

            //find user from temp table start
            if(!empty($request->apple_id)) {
                $tempUserInfo = TempUser::where(['social_id' => $request->apple_id, 'social_type' => 'apple'])->first();
            }
            //find user from temp table end
            $email = (isset($inputs['email']) && $inputs['email']!="") ? $inputs['email'] : $tempUserInfo->email;
            $name = (isset($inputs['name']) && $inputs['name']!="") ? $inputs['name'] : $tempUserInfo->username;
            $check_user_exist = User::where('email',$email)->where('app_type','!=','challenge')->select('id','signup_type','created_at')->get();
            if (count($check_user_exist) > 0){
                if ($check_user_exist[0]['signup_type']=="google"){
                    $err_text = "The email is signed up already (Google)";
                }
                elseif ($check_user_exist[0]['signup_type']=="apple"){
                    $err_text = "The email is signed up already (Apple)";
                }
                else {
                    $err_text = "The email is signed up already";
                }
                return $this->sendFailedResponse($err_text, 403);
            }

            $refreshToken = $tempUserInfo->apple_refresh_token ?? NULL;
            $accessToken = $tempUserInfo->apple_access_token ?? NULL;
            $random_password = Str::random(10);
            $user = User::create([
                "email" => $email,
                'username' => $email,
                "password" => Hash::make($random_password),
                'status_id' => Status::ACTIVE,
                'app_type' => "insta",
                "signup_type" => "apple",
                "org_password" => $random_password,
                "social_id" => isset($request->apple_social_id) ? $request->apple_social_id : null,
                "apple_refresh_token" => $refreshToken,
                "apple_access_token" => $accessToken,
                "insta_type" => $inputs['user_type'],
            ]);

            //remove user from temp table start
            if(!empty($request->apple_id)) {
                $tempUser = TempUser::where(['social_id' => $request->apple_id, 'social_type' => 'apple'])->delete();
            }
            //remove user from temp table end

            UserEntityRelation::create(['user_id' => $user->id, "entity_type_id" => EntityTypes::NORMALUSER, 'entity_id' => $user->id]);
            $random_code = mt_rand(1000000, 9999999);
            $member = UserDetail::create([
                'user_id' => $user->id,
//                'phone_code' => $inputs['phone_code'],
//                'mobile' => $inputs['phone'],
                'device_type_id' => $inputs['device_type_id'],
                'device_id' => $inputs['device_id'],
                'device_token' => $inputs['device_token'],
                'recommended_code' => $random_code,
                'name' => $name
            ]);
            UserDevices::create(['user_id' => $user->id, 'device_token' => $inputs['device_token']]);

            // Instagram Start
            $access_token = $inputs['access_token'] ?? '';
            $social_id = $inputs['social_id'] ?? '';
            $social_name = $inputs['social_name'] ?? '';
            if (!empty($access_token) && !empty($social_id) && !empty($social_name)) {
                User::whereId($user->id)->update([
                    'connect_instagram' => true
                ]);
                $category = Category::where('category_type_id', CategoryTypes::SHOP)->first();
                $business_category_id = $inputs['business_category_id'] ?? $category->id;
                $shop = Shop::create([
                    'email' => $email,
//                    'mobile' => $inputs['phone'],
                    'shop_name' => NULL,
                    'best_portfolio' => NULL,
                    'business_licence' => NULL,
                    'identification_card' => NULL,
                    'business_license_number' => '',
                    'status_id' => Status::PENDING,
                    'category_id' => $business_category_id,
                    'user_id' => $user->id,
                    'manager_id' => '',
                    'uuid' => (string) Str::uuid(),
                    'credit_deduct_date' => Carbon::now()->toDateString()
                ]);
                $entity_id = $shop->id;
                UserEntityRelation::create([
                    'user_id' => $user->id,
                    'entity_type_id' => EntityTypes::SHOP,
                    'entity_id' => $entity_id,
                ]);

                if (!empty($access_token) && !empty($social_id) && !empty($social_name)) {
                    $access_token = $inputs['access_token'] ?? null;
                    LinkedSocialProfile::updateOrCreate([
                        'social_type' => LinkedSocialProfile::Instagram,
                        'shop_id' => $entity_id,
                        'user_id' => $user->id
                    ], [
                        'social_id' => $social_id,
                        'access_token' => $access_token,
                        'social_name' => $social_name,
                        'token_refresh_date' => Carbon::now(),
                    ]);

                    InstagramLog::create([
                        "social_id" =>$social_id,
                        "user_id" =>$user->id,
                        "shop_id" =>$entity_id,
                        "social_name" =>$social_name,
                        "status" =>InstagramLog::CONNECTED,
                    ]);

                    LinkedProfileHistory::updateOrCreate([
                        'shop_id' => $entity_id,
                        'social_id' => $social_id
                    ], [
                        'social_name' => $social_name,
                        'access_token' => $access_token
                    ]);
                }
            }
            // Instagram End

            $token = JWTAuth::fromUser($user);
            $data['id'] = $user->id;
            $data['email'] = $user->email;
            $data['social_id'] = $user->social_id;
            $data['user_exist'] = null;
            $data['token'] = $token;
            $data['name'] = $name;
            $data['avatar'] = $user->avatar;
            $data['user_type'] = $user->insta_type;
            DB::commit();
            return $this->sendSuccessResponse(Lang::get('messages.authenticate.success'), 200, $data);
        } catch (\Throwable $ex) {
            DB::rollback();
            $logMessage = "Something went wrong while signup user";
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function appleLogin(Request $request) {
        DB::beginTransaction();
        $inputs = $request->all();
        try{
            //validate request start
            $validation = $this->userValidator->validateAppleLogin($inputs);
            if ($validation->fails()) {
                return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
            }
            //validate request end

            $user = new User();
            $user = $user->getExistUser($request);
            if ($user) {
                $user->update(['last_login' => Carbon::now()]);
                if($inputs['user_type']=="pro" && $user->insta_type!="pro") {
                    $user->insta_type = $inputs['user_type'];
                    $user->save();
                    ShopPost::join('shops', 'shops.id', 'shop_posts.shop_id')
                        ->where('shops.user_id',$user->id)
                        ->update([
                            'remain_download_insta' => null
                        ]);
                }
                elseif($inputs['user_type']=="free" && $user->insta_type!="pro" && $user->insta_type!="free") {
                    $user->insta_type = $inputs['user_type'];
                    $user->save();
                    $default_limit = InstaImportantSetting::where('field','Default download')->pluck('value')->first();
                    ShopPost::join('shops', 'shops.id', 'shop_posts.shop_id')
                        ->where('shops.user_id',$user->id)
                        ->update([
                            'remain_download_insta' => ($default_limit) ? (int)$default_limit : 10
                        ]);
                }
                $data['id'] = $user->id;
                $data['email'] = $user->email;
                $data['social_id'] = $user->social_id;
                $data['user_exist'] = 1;
                $token = JWTAuth::fromUser($user);
                Auth::login($user);
                $data['token'] = $token;
                $data['name'] = $user->name;
                $data['avatar'] = $user->avatar;
                $data['user_type'] = $inputs['user_type'];
                if ($request->has('device_token') && !empty($user)) {
                    UserDevices::firstOrCreate(['user_id' => Auth::user()->id, 'device_token' => $inputs['device_token']]);
                    UserDetail::where('user_id', Auth::user()->id)->update(['device_token' => $inputs['device_token']]);
                }
            } else {
                $appleRequest = User::checkAppleRequest($request);
                $data['id'] = null;
                $data['email'] = $appleRequest->email;
                $data['social_id'] = $appleRequest->social_id;
                $data['user_exist'] = 0;
                $data['token'] = null;
                $data['name'] = $appleRequest->username;
                $data['avatar'] = null;
                $data['user_type'] = $inputs['user_type'];
            }

            DB::commit();
            return $this->sendSuccessResponse(Lang::get('messages.authenticate.success'), 200, $data);
        } catch(\Throwable $ex){
            DB::rollBack();
            $logMessage = "Something went wrong while login user";
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public static function getAppleRefreshToken($authCode) {
        $client = new Client();
        $headers = [
            'Content-Type' => 'application/x-www-form-urlencoded'
        ];
        $options = [
            'form_params' => [
                'client_id' => "com.insta.apps",
                'client_secret' => "eyJraWQiOiI4NFMyQjRBOFUzIiwiYWxnIjoiRVMyNTYifQ.eyJpc3MiOiI0NExYTjkzTU42IiwiaWF0IjoxNzA5MDk4MjY5LCJleHAiOjE3MjQ2NTAyNjksImF1ZCI6Imh0dHBzOi8vYXBwbGVpZC5hcHBsZS5jb20iLCJzdWIiOiJjb20uaW5zdGEuYXBwcyJ9.sI5Egw_nqfRBYLlqc2s7Nf-0EK-hDyywIXCJaWgR6TPr-yImSEd1SJjsV4JXmKipGa5zaMSl8v8DluMxYPsizA",
                'code' => $authCode,
                'grant_type' => 'authorization_code',
                'redirect_uri' => "https://mearound.me"
            ]
        ];
        $request = new \GuzzleHttp\Psr7\Request('POST', 'https://appleid.apple.com/auth/token', $headers);
        $res = $client->sendAsync($request, $options)->wait();
        if($res->getStatusCode() == 200){
            Log::info("getAppleRefreshToken");
            Log::info($res->getBody());
            $json = json_decode($res->getBody());
            return $json->refresh_token;
        }
        return false;
    }

    public static function getAccessToken($refreshToken)
    {
        $client = new Client();
        $headers = [
            'Content-Type' => 'application/x-www-form-urlencoded'
        ];
        $options = [
            'form_params' => [
                'client_id' => "com.insta.apps",
                'client_secret' => "eyJraWQiOiI4NFMyQjRBOFUzIiwiYWxnIjoiRVMyNTYifQ.eyJpc3MiOiI0NExYTjkzTU42IiwiaWF0IjoxNzA5MDk4MjY5LCJleHAiOjE3MjQ2NTAyNjksImF1ZCI6Imh0dHBzOi8vYXBwbGVpZC5hcHBsZS5jb20iLCJzdWIiOiJjb20uaW5zdGEuYXBwcyJ9.sI5Egw_nqfRBYLlqc2s7Nf-0EK-hDyywIXCJaWgR6TPr-yImSEd1SJjsV4JXmKipGa5zaMSl8v8DluMxYPsizA",
                'grant_type' => 'refresh_token',
                'refresh_token' => $refreshToken
            ]
        ];
        $request = new \GuzzleHttp\Psr7\Request('POST', 'https://appleid.apple.com/auth/token', $headers);
        $res = $client->sendAsync($request, $options)->wait();

        if ($res->getStatusCode() == 200) {
            $json = json_decode($res->getBody());
            return $json->access_token;
        }
        return false;
    }

    public function postList(Request $request) {
        DB::beginTransaction();
        $inputs = $request->all();
        $user = Auth::user();
        try{
            \Illuminate\Support\Facades\Config::set('shop_detail_per_page',21);
            $insta_connected_shop = Shop::leftjoin('linked_social_profiles','linked_social_profiles.shop_id','shops.id')
                ->leftjoin('linked_profile_histories', function ($join) {
                    $join->on('shops.id', '=', 'linked_profile_histories.shop_id');
                })
                ->where('shops.user_id',$user->id)
                ->where(function ($q){
                    $q->whereNotNull('linked_social_profiles.id')
                        ->orWhereNotNull('linked_profile_histories.id');
                })
                ->select(
                    'shops.*',
                    DB::raw('IFNULL(linked_profile_histories.social_name, linked_social_profiles.social_name) as social_name'),
                    'linked_social_profiles.is_valid_token as is_valid_token',
                    DB::raw('IFNULL(linked_social_profiles.social_id, "") as is_connect')
                )
                ->orderBy('shops.created_at','DESC')
                ->first();
            if($insta_connected_shop && !empty($insta_connected_shop->is_connect)){
                if ($insta_connected_shop->is_valid_token==0){
                    $status = "yellow";
                }
                else {
                    $status = "green";
                }
            }
            else {
                $status = "red";
            }

            $data['social_name'] = ($insta_connected_shop) ? $insta_connected_shop->social_name : null;
            $data['shop_id'] = ($insta_connected_shop) ? $insta_connected_shop->id : null;
            $data['portfolio_images'] = ($insta_connected_shop) ? $insta_connected_shop->portfolio_images : null;
            $data['status'] = $status;
            DB::commit();
            return $this->sendSuccessResponse("Posts list.", 200,$data);
        } catch(\Throwable $ex){
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function promotInstaAround(Request $request) {
        DB::beginTransaction();
        $inputs = $request->all();
        $user = Auth::user();
        try{
            UserDetail::where('user_id',$user->id)->update([
                'promot_insta_around' => $inputs['promot_insta_around']
            ]);

            DB::commit();
            return $this->sendSuccessResponse("Agree/disagree done.", 200);
        } catch(\Throwable $ex){
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function downloadPost(Request $request) {
        DB::beginTransaction();
        $inputs = $request->all();
        $user = Auth::user();
        try{
            $shopPost = ShopPost::where('id',$inputs['post_id'])->first();
            if ($shopPost && ($shopPost->remain_download_insta > 0)){
                $update_limit = $shopPost->remain_download_insta - 1;
            }
            else {
                $update_limit = $shopPost->remain_download_insta;
            }

            ShopPost::where('id',$inputs['post_id'])->update([
                'remain_download_insta' => $update_limit
            ]);

            DB::commit();
            return $this->sendSuccessResponse("Download completed.", 200);
        } catch(\Throwable $ex){
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function myProfile(Request $request) {
        DB::beginTransaction();
        $inputs = $request->all();
        $user = Auth::user();
        try{
            $insta_accounts = DB::table('shops')
                ->leftjoin('linked_social_profiles','linked_social_profiles.shop_id','shops.id')
                ->leftjoin('linked_profile_histories', function ($join) {
                    $join->on('shops.id', '=', 'linked_profile_histories.shop_id');
                })
                ->where('shops.user_id',$user->id)
                ->whereNull('shops.deleted_at')
                ->where(function ($q){
                    $q->whereNotNull('linked_social_profiles.id')
                        ->orWhereNotNull('linked_profile_histories.id');
                })
                ->select(
                    'shops.id',
                    DB::raw('IFNULL(linked_profile_histories.social_name, linked_social_profiles.social_name) as social_name'),
                    'linked_social_profiles.is_valid_token as is_valid_token',
                    DB::raw('IFNULL(linked_social_profiles.social_id, "") as is_connect')
                )
                ->orderBy('shops.created_at','DESC')
                ->get();
            $insta_accounts->map(function ($item){
                if($item && !empty($item->is_connect)){
                    if ($item->is_valid_token==0){
                        $status = "yellow";
                    }
                    else {
                        $status = "green";
                    }
                }
                else {
                    $status = "red";
                }
                $item->status = $status;

                return $item;
            });

            $data['user_name'] = $user->name;
            $data['avatar'] = $user->avatar;
            $data['email'] = $user->email;
            $data['business_profiles'] = $insta_accounts;
            DB::commit();
            return $this->sendSuccessResponse("Connected instagram accounts list.", 200,$data);
        } catch(\Throwable $ex){
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function shopData(Request $request) {
        $user = Auth::user();
        $inputs = $request->all();
        try {
            if ($user) {
                $latitude = $inputs['latitude'] ?? '';
                $longitude = $inputs['longitude'] ?? '';
                $per_page = $inputs['per_page'] ?? 9;
                $coordinate = $longitude . ',' . $latitude;

                \Illuminate\Support\Facades\Config::set('shop_detail_per_page',$per_page);

                $shopQuery = Shop::leftjoin('addresses', function ($join) {
                    $join->on('shops.id', '=', 'addresses.entity_id')
                        ->where('addresses.entity_type_id', EntityTypes::SHOP);
                })
                    ->leftjoin('linked_social_profiles','linked_social_profiles.shop_id','shops.id')
                    ->select(
                        'shops.*',
                        'linked_social_profiles.is_valid_token as is_valid_token',
                        DB::raw('IFNULL(linked_social_profiles.social_id, "") as is_connect')
                    );

                if (isset($inputs['latitude']) && isset($inputs['longitude'])) {
                    $shopQuery = $shopQuery->addSelect(DB::raw("IFNULL(CAST(ROUND(st_distance_sphere(POINT(addresses.longitude,addresses.latitude), POINT( $coordinate)) * .001,2) AS DECIMAL(21,2)) , '') as shop_distance"));
                }

                $shopExists = $shopQuery->where('shops.id',$inputs['shop_id'])->first();

                if ($shopExists) {
                    $shopExists->user_details = ['id' => $user->id, 'status_id' => $user->status_id, 'chat_status' => $user->chat_status, 'is_admin_access' => ($user->is_admin_access==1)?true:false, 'name' => $user->name];

                    $userInstaProfile = LinkedSocialProfile::where('user_id', $user->id)->where('shop_id',$shopExists->id)->where('social_type', LinkedSocialProfile::Instagram)->first();
                    $shopExists->insta_social_name = (!empty($userInstaProfile) && !empty($userInstaProfile->social_name)) ? $userInstaProfile->social_name : '';

                    $postCollection = collect($shopExists->portfolio_images->getCollection())->map(function ($value) use ($shopExists,$user) {
                        $value->shop_distance = $shopExists->shop_distance;
                        $value->is_admin_access = ($user->is_admin_access==1)?true:false;
                        return $value;
                    });

                    $shopExists = $shopExists->toArray();
                    $shopExists['portfolio_images']['data'] = $postCollection;
                    return $this->sendSuccessResponse(Lang::get('messages.shop.edit-success'), 200, $shopExists);
                } else {
                    return $this->sendSuccessResponse(Lang::get('messages.shop.empty'), 402);
                }
            } else {
                return $this->sendSuccessResponse(Lang::get('messages.user.token_expired'), 401);
            }
        } catch (\Exception $e) {
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

}
