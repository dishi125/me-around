<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\CategoryTypes;
use App\Models\Config;
use App\Models\EntityTypes;
use App\Models\InstagramLog;
use App\Models\LinkedProfileHistory;
use App\Models\LinkedSocialProfile;
use App\Models\PackagePlan;
use App\Models\QrCode;
use App\Models\Shop;
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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use JWTAuth;

class QrcodeController extends Controller
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
//        try {
        //validate request start
        $validation = $this->userValidator->validateGoogleSignup($inputs);
        if ($validation->fails()) {
            return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
        }
        //validate request end

        $check_user_exist = User::where('email',$inputs['email'])->where('app_type','!=','challenge')->pluck('signup_type')->first();
        if (!empty($check_user_exist)){
            if ($check_user_exist=="google"){
                $err_text = "The email is signed up already (Google)";
            }
            elseif ($check_user_exist=="apple"){
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
            'app_type' => "qr code",
            "signup_type" => "google",
            "org_password" => $random_password
        ]);

        UserEntityRelation::create(['user_id' => $user->id, "entity_type_id" => EntityTypes::NORMALUSER, 'entity_id' => $user->id]);

        $random_code = mt_rand(1000000, 9999999);
        $member = UserDetail::create([
            'user_id' => $user->id,
            'phone_code' => $inputs['phone_code'],
            'mobile' => $inputs['phone'],
            'device_type_id' => $inputs['device_type_id'],
            'device_id' => $inputs['device_id'],
            'device_token' => $inputs['device_token'],
            'recommended_code' => $random_code,
        ]);
        UserDevices::create(['user_id' => $user->id, 'device_token' => $inputs['device_token']]);

        $token = JWTAuth::fromUser($user);
        DB::commit();
        return $this->sendSuccessResponse(Lang::get('messages.authenticate.success'), 200, compact('token', 'user'));
        /*} catch (\Throwable $ex) {
            DB::rollback();
            $logMessage = "Something went wrong while signup user";
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }*/
    }

    public function googleLogin(Request $request) {
        DB::beginTransaction();
        $inputs = $request->all();
//        try{
        //validate request start
        $validation = $this->userValidator->validateGoogleLogin($inputs);
        if ($validation->fails()) {
            return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
        }
        //validate request end

        $is_email_exist = User::where('email',$inputs['email'])->where('signup_type','google')->first();
        if (empty($is_email_exist)){
            return $this->sendFailedResponse("User not found", 402);
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
            DB::commit();
            return $this->sendSuccessResponse(Lang::get('messages.authenticate.success'), 200, compact('token', 'user'));
        } else {
            if ($token = JWTAuth::getToken()) {
                JWTAuth::invalidate($token);
            }
            return $this->sendFailedResponse("Invalid credentials", 422);
        }
        /*} catch(\Throwable $ex){
            DB::rollBack();
            $logMessage = "Something went wrong while login user";
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }*/
    }

    protected function credentials($username = "", $password = "")
    {
        return ['email' => $username, 'password' => $password];
    }

    public function appleSignup(Request $request) {
        DB::beginTransaction();
        $inputs = $request->all();

//        try {
        //validate request start
        $validation = $this->userValidator->validateAppleSignup($inputs);
        if ($validation->fails()) {
            return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
        }
        //validate request end

        $check_user_exist = User::where('email',$inputs['email'])->where('app_type','!=','challenge')->pluck('signup_type')->first();
        if (!empty($check_user_exist)){
            if ($check_user_exist=="google"){
                $err_text = "The email is signed up already (Google)";
            }
            elseif ($check_user_exist=="apple"){
                $err_text = "The email is signed up already (Apple)";
            }
            else {
                $err_text = "The email is signed up already";
            }
            return $this->sendFailedResponse($err_text, 403);
        }

        //find user from temp table start
        if(!empty($request->apple_id)) {
            $tempUserInfo = TempUser::where(['social_id' => $request->apple_id, 'social_type' => 'apple'])->first();
        }
        //find user from temp table end
        $refreshToken = $tempUserInfo->apple_refresh_token ?? NULL;
        $accessToken = $tempUserInfo->apple_access_token ?? NULL;
        $random_password = Str::random(10);
        $user = User::create([
            "email" => $inputs['email'],
            'username' => $inputs['email'],
            "password" => Hash::make($random_password),
            'status_id' => Status::ACTIVE,
            'app_type' => "insta",
            "signup_type" => "apple",
            "org_password" => $random_password,
            "social_id" => isset($request->apple_social_id) ? $request->apple_social_id : null,
            "apple_refresh_token" => $refreshToken,
            "apple_access_token" => $accessToken,
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
            'phone_code' => $inputs['phone_code'],
            'mobile' => $inputs['phone'],
            'device_type_id' => $inputs['device_type_id'],
            'device_id' => $inputs['device_id'],
            'device_token' => $inputs['device_token'],
            'recommended_code' => $random_code,
        ]);
        UserDevices::create(['user_id' => $user->id, 'device_token' => $inputs['device_token']]);

        $token = JWTAuth::fromUser($user);
        DB::commit();
        return $this->sendSuccessResponse(Lang::get('messages.authenticate.success'), 200, compact('token', 'user'));
        /*} catch (\Throwable $ex) {
            DB::rollback();
            $logMessage = "Something went wrong while signup user";
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }*/
    }

    public function appleLogin(Request $request) {
        DB::beginTransaction();
        $inputs = $request->all();
//        try{
        //validate request start
        $validation = $this->userValidator->validateAppleLogin($inputs);
        if ($validation->fails()) {
            return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
        }
        //validate request end
        $userInfo = (object) [];

        $user = new User();
        $user = $user->getExistUser($request);
        if ($user) {
            $userInfo = $user;
            $userInfo->user_exist = 1;
            $token = JWTAuth::fromUser($user);
            Auth::login($user);
            $userInfo->token = $token;
            if ($request->has('device_token') && !empty($user)) {
                UserDevices::firstOrCreate(['user_id' => Auth::user()->id, 'device_token' => $inputs['device_token']]);
                UserDetail::where('user_id', Auth::user()->id)->update(['device_token' => $inputs['device_token']]);
            }
            $user->update(['last_login' => Carbon::now()]);
        } else {
            $appleRequest = $this->checkAppleRequest($request);
            $userInfo->temp_user_detail = $appleRequest;
            $userInfo->user_exist = 0;
        }

        DB::commit();
        return $this->sendSuccessResponse(Lang::get('messages.authenticate.success'), 200, compact( 'userInfo'));
        /*} catch(\Throwable $ex){
            DB::rollBack();
            $logMessage = "Something went wrong while login user";
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }*/
    }

    public function checkAppleRequest($request){
        $tempUser = null;
        //check into temp user if exist start
        $tempUser = TempUser::where(['social_id' => $request->apple_social_id, 'social_type' => "apple"])->first();
        if(!empty($tempUser)) {
            return $tempUser;
        }
        //check into temp user if exist end
        //generate refresh token start
        $refreshToken = null;
        $appleAccessToken = null;
        if(!empty($request->auth_code)) {
            // echo $request->auth_code;exit;
            $refreshToken = $this->getAppleRefreshToken($request->auth_code);

            if(!$refreshToken){
                Log::error('User Model : Something went wrong while generating refresh token for '.$request->apple_social_id);
            }

            if(!empty($refreshToken)) {
                $appleAccessToken = $this->getAccessToken($refreshToken);
            }
        }
        //generate refresh token end

        $tempUser = TempUser::firstOrCreate(
            ['social_id' => $request->apple_social_id, 'social_type' => "apple"],
            ['email' => $request->email ?? NULL,'auth_code' => $request->auth_code ?? NULL,'apple_refresh_token' => $refreshToken, 'apple_access_token' => $appleAccessToken]
        );
        return $tempUser;
    }

    public function getAppleRefreshToken($authCode) {
        $client = new Client();
        $headers = [
            'Content-Type' => 'application/x-www-form-urlencoded'
        ];
        $options = [
            'form_params' => [
                'client_id' => config('constant.apple_client_id'),
                'client_secret' => config('constant.apple_client_secret'),
                'code' => $authCode,
                'grant_type' => 'authorization_code',
                'redirect_uri' => env('APPLE_REDIRECT_URI')
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

    public function getAccessToken($refreshToken)
    {
        $client = new Client();
        $headers = [
            'Content-Type' => 'application/x-www-form-urlencoded'
        ];
        $options = [
            'form_params' => [
                'client_id' => config('apple_client_id'),
                'client_secret' => config('apple_client_secret'),
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

    public function createQr(Request $request)
    {
        try {
            $inputs = $request->all();

            $qrCode = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')->size(200)
                ->backgroundColor(255, 255, 255) // Set white background
                ->color(0, 0, 0) // Set black color for the QR code
                ->margin(1) // Set a 1-pixel border
                ->generate($inputs['link']);
            $imageName = rand().'_'.time().'.svg';
            $qrCodeFolder = config('constant.qr_code');
            if (!Storage::disk('s3')->exists($qrCodeFolder)) {
                Storage::disk('s3')->makeDirectory($qrCodeFolder);
            }
            $mainFile = Storage::disk('s3')->put($qrCodeFolder.'/'.$imageName, $qrCode, 'public');
            $image_url = $qrCodeFolder . '/' . $imageName;

            $qr_code = QrCode::create([
                'title' => $inputs['title'],
                'link' => $inputs['link'],
                'image' => $image_url,
            ]);

            return $this->sendSuccessResponse("QR code created.", 200, $qr_code);
        } catch (\Exception $e) {
            Log::info($e);
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function listQr(Request $request)
    {
        try {
            $inputs = $request->all();

            $qrCodes = QrCode::get(['id','title','image']);

            return $this->sendSuccessResponse("QR code list.", 200, $qrCodes);
        } catch (\Exception $e) {
            Log::info($e);
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

}
