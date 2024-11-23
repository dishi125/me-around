<?php

namespace App\Http\Controllers\Api;

use App\Models\ChallengeConfig;
use App\Models\ChallengeMenu;
use App\Models\ChallengeNotice;
use App\Models\ChallengeUserFollowing;
use App\Models\ChallengeUserPoint;
use App\Models\InstagramLog;
use App\Models\InstaImportantSetting;
use App\Models\ShopPost;
use App\Models\TempUser;
use App\Models\UserFeedLog;
use DB;
use GuzzleHttp\Client;
use Log;
use Auth;
use Hash;
use Lang;
use JWTAuth;
use Validator;
use Carbon\Carbon;
use App\Models\Shop;
use App\Models\User;
use App\Models\Cards;
use App\Models\Config;
use App\Models\Notice;
use App\Models\Status;
use App\Util\Firebase;
use App\Models\Country;
use App\Models\Manager;
use App\Jobs\SignupMail;
use App\Models\Category;
use App\Models\Hospital;
use App\Mail\ForgotEmail;
use App\Models\UserCards;
use App\Models\UserCredit;
use App\Models\UserDetail;
use App\Models\UserPoints;
use App\Models\EntityTypes;
use App\Models\PackagePlan;
use App\Models\UserCardLog;
use App\Models\UserDevices;
use Illuminate\Support\Str;
use App\Mail\ForgotPassword;
use App\Models\DefaultCards;
use App\Models\UserReferral;
use Illuminate\Http\Request;
use App\Models\CategoryTypes;
use App\Models\UserCoinHistory;
use App\Models\CategorySettings;
use App\Jobs\RegisterSendMailJob;
use App\Mail\RegisterAccountMail;
use App\Models\DefaultCardsRives;
use App\Models\UserCreditHistory;
use App\Validators\UserValidator;
use App\Models\NonLoginUserDetail;
use App\Models\UserEntityRelation;
use App\Models\UserHiddenCategory;
use App\Models\UserReferralDetail;
use App\Validators\LoginValidator;
use App\Models\LinkedSocialProfile;
use App\Models\NonLoginLoveDetails;
use App\Http\Controllers\Controller;
use App\Models\LinkedProfileHistory;
use Illuminate\Support\Facades\Mail;
use Tymon\JWTAuth\Exceptions\JWTException;

class AuthenticateController extends Controller
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

    public function registerValidate(Request $request)
    {
        $inputs = $request->all();
        try {
            $language_id = $inputs['language_id'] ?? 4;
            $validation = $this->userValidator->validateRegister($inputs, $language_id);
            if ($validation->fails()) {
                return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
            }
            return $this->sendSuccessResponse(Lang::get('messages.authenticate.validate_email'), 200);
        } catch (\Exception $e) {
            Log::info($e);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    /**
     * Register User
     *
     * Register new user as App User
     *
     * @param Request $request
     * @return void
     */
    public function register(Request $request)
    {
//        try {
            $inputs = $request->all();
            $language_id = $request->has('language_id') ? $inputs['language_id'] : 4;
            $validation = $this->userValidator->validateStore($inputs, $language_id);
            if ($validation->fails()) {
                return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
            }
            DB::beginTransaction();
            Log::info('Start code for the user register');
            $app_type = $inputs['app_type'] ?? "mearound";
            if($app_type=="mearound" || $app_type=="tattoocity" || $app_type=="spa") {
                $is_exist_user = User::where('email', $inputs['email'])->whereIn('app_type',['mearound','tattoocity','spa'])->count();
                if ($is_exist_user > 0){
                    return $this->sendFailedResponse("User already exist!!", 401);
                }
            }
            elseif ($app_type=="challenge"){
                $is_exist_user = User::where('email', $inputs['email'])->where('app_type','challenge')->count();
                if ($is_exist_user > 0){
                    return $this->sendFailedResponse("User already exist!!", 401);
                }
            }
            $recommended_user = UserDetail::where('recommended_code', $inputs['recommended_code'])->first();
            $exp_amount = 0;
            $exp_config = Config::where('key', Config::GIVE_REFERRAL_EXP)->first();
            $exp_amount = $exp_config ? (int) filter_var($exp_config->value, FILTER_SANITIZE_NUMBER_INT) : 0;

            $giveLoveCount = UserCards::REGISTER_LOVE_COUNT;
            $registerCoin = UserCoinHistory::REGISTER_COIN;
            $referral_register = UserCoinHistory::REFERRAL_REGISTER_COIN;
            $referral_register_bonus = UserCoinHistory::REFERRAL_REGISTER_BONUS_COIN;

            $recommended_by = NULL;
            if ($recommended_user) {
                $user = User::find($recommended_user->user_id);
                if (isset($inputs['phone_code']) && isset($inputs['phone'])) {
                    $user_phone_count = UserDetail::where('recommended_by', $recommended_user->user_id)->where('mobile', $inputs['phone'])->where('phone_code', $inputs['phone_code'])->count();
                }
                if (isset($inputs['phone']) && isset($inputs['phone_code']) && $user_phone_count == 0) {
                    $giveLoveCount += UserCards::REFERRAL_LOVE_COUNT;

                    $recommended_by = $recommended_user->user_id;
                    $isShop = $user->entityType->contains('entity_type_id', EntityTypes::SHOP);
                    $isHospital = $user->entityType->contains('entity_type_id', EntityTypes::HOSPITAL);
                    $add_credit = 0;

                    // Give EXP

                    $userIds = [$recommended_user->user_id];
                    $devices = UserDevices::whereIn('user_id', $userIds)->pluck('device_token')->toArray();

                    $referralLove = UserCards::REFERRAL_LOVE_COUNT;
                    $appliedCard = DB::table('user_cards')->where('user_id', $recommended_user->user_id)->where('is_applied', 1)->first();
                    if ($appliedCard) {
                        UserCards::whereId($appliedCard->id)->update(['love_count' => DB::raw("love_count + $referralLove")]);

                        $notify_type = Notice::LOVE_REFERRAL;
                        $love_notice_key = "language_$language_id.give_referral_exp";
                        $love_notice_msg = __("messages.$love_notice_key",['username' => trim($inputs['name'])]);
                        //$love_notice_msg = __("messages.$love_notice_key", ['level' => $nextCardLevel]);

                        $referralCount = UserReferral::where('referred_by',$recommended_by)->where('has_coffee_access',0)->count();
                        $referralCount = $referralCount + 1;
                        $sub_title_display = "+10 Love, +1 Starbuck stamp($referralCount/3)";
                        $notice = Notice::create([
                            'notify_type' => $notify_type,
                            'user_id' => $recommended_user->user_id,
                            'to_user_id' => $recommended_user->user_id,
                            'title' => $love_notice_msg,
                            'sub_title' => trim($inputs['name']),
                            'entity_id' => $referralCount,
                        ]);

                        //$format = __("messages.language_$language_id.subscriber", ['name' => $inputs['name']]);
                        $format = $sub_title_display;
                        $notificationData = [
                            'id' => $recommended_user->user_id,
                            'user_id' => $recommended_user->user_id,
                            'title' => $love_notice_msg,
                        ];
                        if (count($devices) > 0) {
                            $result = $this->sentPushNotification($devices, $love_notice_msg, $format, $notificationData, $notify_type);
                        }

                        $checkLoveCount = $appliedCard->love_count + $referralLove;
                        $cardLevel = DB::table('card_levels')->whereRaw("(start <= " . $checkLoveCount . " AND end >= " . $checkLoveCount . ")")->first();

                        if ($cardLevel->id > $appliedCard->active_level) {
                            UserCards::whereId($appliedCard->id)->update(['active_level' => $cardLevel->id]);
                        }
                    }

                    //UserDetail::where('user_id',$recommended_user->user_id)->update(['points' => DB::raw("points + $exp_amount")]);
                    $userDetails = DB::table('users_detail')->where('user_id', $recommended_user->user_id)->first();

                    /* UserPoints::create([
                        'user_id' => $recommended_user->user_id,
                        'entity_type' => UserPoints::GIVE_REFERRAL_EXP,
                        'entity_id' => '',
                        'entity_created_by_id' => $recommended_user->user_id,
                        'points' => $exp_amount
                    ]); */
                    $getLevel = DB::table('levels')->select('id')->where('points', '<=', $userDetails->points)->orderBy('id', 'desc')->first();
                    $updateLevel = !empty($getLevel) ? $getLevel->id : 1;

                    if (($userDetails) && $updateLevel > $userDetails->level) {
                        $cards = Cards::select('card_number')->whereRaw("start <=" . $updateLevel . " OR (end <= " . $updateLevel . " )")->orderBy('id', 'desc')->limit(0, 1)->first()->toArray();
                        $cardNumber = !empty($cards) ? $cards['card_number'] : 1;

                        UserDetail::where('user_id', $recommended_user->user_id)->update(['level' => $updateLevel, 'card_number' => $cardNumber]);
                        $getUserOwnCardCount = UserCards::where(['user_id' => $recommended_user->user_id])->count();
                        if ($cardNumber > $getUserOwnCardCount) {
                            $getCardsByLevelQ = DefaultCardsRives::select('default_cards_rives.*')->leftjoin('default_cards as dc', 'dc.id', 'default_cards_rives.default_card_id');

                            $getCardsByLevelQ = $getCardsByLevelQ->whereRaw("(dc.start <= " . $updateLevel . " AND dc.end >= " . $updateLevel . " )");

                            $getCardsByLevelQ = $getCardsByLevelQ->whereNotIn('default_cards_rives.id', function ($q) use ($recommended_user) {
                                $q->select('default_cards_id')->from('user_cards')->where('user_id', $recommended_user->user_id);
                            });

                            $getCardsByLevelQ = $getCardsByLevelQ->inRandomOrder()->limit(1)->first();

                            if (!empty($getCardsByLevelQ)) {
                                $cardData = [
                                    'user_id' => $recommended_user->user_id,
                                    'default_cards_id' => $getCardsByLevelQ->default_card_id,
                                    'default_cards_riv_id' => $getCardsByLevelQ->id
                                ];
                                $userCard = UserCards::create($cardData);
                                createUserCardDetail($getCardsByLevelQ, $userCard);
                            }
                        }


                        /* $nextCardLevel = getUserNextAwailLevel($recommended_user->user_id, $updateLevel);
                        $key = Notice::LEVEL_UP . '_' . $language_id;

                        $title_msg = __("notice.$key");
                        $notify_type = Notice::LEVEL_UP;

                        $notice = Notice::create([
                            'notify_type' => Notice::LEVEL_UP,
                            'user_id' => $recommended_user->user_id,
                            'to_user_id' => $recommended_user->user_id,
                            'entity_type_id' => 3,
                            'entity_id' => $recommended_user->user_id,
                            'title' => 'LV ' . $updateLevel,
                            'sub_title' => $nextCardLevel,
                            'is_aninomity' => 0
                        ]);

                        $next_level_key = "language_$language_id.next_level_card";
                        $next_level_msg = __("messages.$next_level_key", ['level' => $nextCardLevel]);

                        $format = 'LV ' . $updateLevel . " \n" . $next_level_msg;
                        $notificationData = [
                            'id' => $recommended_user->user_id,
                            'user_id' => $recommended_user->user_id,
                            'title' => $title_msg,
                        ];
                        if (count($devices) > 0) {
                            $result = $this->sentPushNotification($devices, $title_msg, $format, $notificationData, $notify_type);
                        }
                         */
                    }

                    $devices = UserDevices::whereIn('user_id', [$recommended_user->user_id])->pluck('device_token')->toArray();

                    $language_id = $userDetails->language_id ?? 4;

                    /* $title_msg = __("messages.language_$language_id.give_referral_exp");
                    $notify_type = Notice::GIVE_REFERRAL_EXP;

                    $nextCardLevel = getUserNextAwailLevel($recommended_user->user_id,$updateLevel);
                    $next_level_key = "language_$language_id.next_level_card";
                    $next_level_msg = __("messages.$next_level_key", ['level' => $nextCardLevel]);

                    $subtitle = trim($inputs['name'])." +".$exp_amount."EXP ".$next_level_msg;
                    $format = trim($inputs['name'])." +".$exp_amount."EXP \n".$next_level_msg;

                    $notice = Notice::create([
                        'notify_type' => $notify_type,
                        'user_id' => $recommended_user->user_id,
                        'to_user_id' => $recommended_user->user_id,
                        'title' => trim($inputs['name'])." +".$exp_amount."EXP ",
                        'sub_title' => $nextCardLevel,
                    ]);

                    $notificationData = [
                        'id' => $recommended_user->user_id,
                        'credits' => number_format((float)$exp_amount),
                    ];

                    if (count($devices) > 0) {
                        $result = $this->sentPushNotification($devices,$title_msg, $format, $notificationData ,$notify_type, $recommended_user->user_id);
                    } */


                    // Reward Coin
                    if ($isShop) {
                        $config = Config::where('key', Config::SHOP_RECOMMEND_MONEY)->first();
                    }
                    if ($isHospital) {
                        $config = Config::where('key', Config::HOSPITAL_RECOMMEND_MONEY)->first();
                    }
                    $new_credit = !empty($config) ? (int) filter_var($config->value, FILTER_SANITIZE_NUMBER_INT) : 0;
                    $userCredits = UserCredit::where('user_id', $recommended_user->user_id)->first();
                    $old_credit = $userCredits->credits ?? 0;
                    $total_credit = $old_credit + $new_credit;

                    $credit = UserCredit::where('user_id', $recommended_user->user_id)->update(['credits' => $total_credit]);

                    $creditHistory = UserCreditHistory::create([
                        'user_id' => $recommended_user->user_id,
                        'amount' => $new_credit,
                        'total_amount' => $total_credit,
                        'transaction' => 'credit',
                        'type' => UserCreditHistory::RECOMMENDED
                    ]);
                    /*
                    $devices = UserDevices::whereIn('user_id', [$user->id])->pluck('device_token')->toArray();
                    $user_detail = UserDetail::where('user_id', $user->id)->first();
                    $language_id = $user_detail->language_id ?? 4;
                    $title_msg = '';

                    $key = Notice::REWARD_RECOMMENDED . '_' . $language_id;
                    $format = __("notice.$key");
                    $notify_type = Notice::REWARD_RECOMMENDED;
                    $notice = Notice::create([
                        'notify_type' => Notice::REWARD_RECOMMENDED,
                        'user_id' => $user->id,
                        'to_user_id' => $user->id,
                        'title' => $total_credit,
                        'sub_title' => number_format((float)$new_credit),
                    ]);

                    $notificationData = [
                        'id' => $user->id,
                        'credits' => number_format((float)$new_credit),
                    ];

                    if (count($devices) > 0) {
                        $result = $this->sentPushNotification($devices, $title_msg, $format, $notificationData, $notify_type, $user->id);
                    }
                    */
                } else {
                    $notice = Notice::create([
                        'notify_type' => Notice::REWARD_RECOMMENDED_ONCE,
                        'user_id' => $user->id,
                        'to_user_id' => $user->id,
                    ]);
                }
            }

            $non_login_user_id = $inputs['user_id'] ?? '';
            $recommended_code = $inputs['recommended_code'] ?? '';
            $manager = Manager::where('recommended_code', $recommended_code)->first();
            $managerID = !empty($manager) ? $manager->id : null;
            if (isset($inputs['phone_code'])) {
                $country = Country::where('phonecode', $inputs['phone_code'])->first();
            }

            $app_type = isset($inputs['app_type']) ? $inputs['app_type'] : "mearound";
            $random_password = Str::random(10);
            $user = User::create([
                "email" => $inputs['email'],
                'username' => $inputs['email'],
                "password" => Hash::make($inputs['password']),
                "org_password" => $inputs['password'],
                'status_id' => Status::ACTIVE,
                'app_type' => $app_type,
                'signup_type' => "email",
            ]);

            UserEntityRelation::create(['user_id' => $user->id, "entity_type_id" => EntityTypes::NORMALUSER, 'entity_id' => $user->id]);
            $random_code = mt_rand(1000000, 9999999);

            $userGetLevel = DB::table('levels')->select('id')->where('points', '<=', (UserDetail::POINTS_40))->orderBy('id', 'desc')->first();
            $userUpdateLevel = !empty($userGetLevel) ? $userGetLevel->id : 1;

            $member = UserDetail::create([
                'user_id' => $user->id,
                'country_id' => isset($country) ? $country->id : null,
                'name' => trim($inputs['name']),
                'email' => $inputs['email'],
                'phone_code' => $inputs['phone_code'] ?? null,
                'mobile' => $inputs['phone'] ?? null,
                'gender' => $inputs['gender'] ?? null,
                'device_type_id' => $inputs['device_type_id'],
                'device_id' => $inputs['device_id'],
                'device_token' => $inputs['device_token'],
                'recommended_code' => $random_code,
                'recommended_by' => $recommended_by,
                'points_updated_on' => Carbon::now(),
                'points' => (UserDetail::POINTS_40), //  + $exp_amount
                'level' => $userUpdateLevel,
                'manager_id' => $managerID,
                'mbti' => isset($inputs['mbti']) ? trim($inputs['mbti']) : null,
            ]);

            UserDevices::create(['user_id' => $user->id, 'device_token' => $inputs['device_token']]);

            // Referral Logs
            if($recommended_by){
                if ($app_type=="challenge") {
                    ChallengeUserPoint::create([
                        'user_id' => $recommended_by,
                        'bp' => 1000
                    ]);
                    ChallengeUserPoint::create([
                        'user_id' => $user->id,
                        'bp' => 1000
                    ]);
                    ChallengeUserFollowing::firstOrCreate([
                        'followed_by' => $user->id,
                        'follows_to' => $recommended_by,
                    ]);
                    ChallengeNotice::create([
                        'user_id' => $user->id,
                        'to_user_id' => $recommended_by,
                        'notify_type' => 'get_follower'
                    ]);
                }

                UserReferral::create([
                    'referred_by' => $recommended_by,
                    'referral_user' => $user->id
                ]);

                $referralCount = UserReferral::where('referred_by',$recommended_by)->where('has_coffee_access',0)->count();

                if(!empty($referralCount) && $referralCount >= 3){
                    UserReferralDetail::create([
                        'user_id' => $recommended_by
                    ]);
                    UserReferral::where('referred_by',$recommended_by)->where('has_coffee_access',0)->take(3)->update(['has_coffee_access' => 1]);
                }

                //send mail to admin
                if ($app_type=="challenge"){
                    $configSettings = ChallengeConfig::where('key', ChallengeConfig::SIGNUP_EMAIL)->first();
                }
                else {
                    $configSettings = Config::where('key', Config::SIGNUP_EMAIL)->first();
                }
                $mailData = (object)[
                    'email' => $inputs['email'],
                    'username' => $inputs['name'],
                    'gender' => $inputs['gender'] ?? null,
                    'phone' => $inputs['phone'] ?? null,
                    'subject' => "[MeAround] New referral sign up",
                    'to_email' => $configSettings->value
                ];
                SignupMail::dispatch($mailData);
            }
            // Referral Logs


            // Assign Default Card Start
            $getDefaultCard = DefaultCards::select('dcr.default_card_id', 'dcr.id as riv_id', 'dcr.background_rive', 'dcr.character_rive')->leftJoin('default_cards_rives as dcr', 'default_card_id', 'default_cards.id')->where('default_cards.name', DefaultCards::DEFAULT_CARD)->first();

            $default_cards_id = (!empty($getDefaultCard)) ? $getDefaultCard->default_card_id : 0;
            $default_cards_riv_id = (!empty($getDefaultCard)) ? $getDefaultCard->riv_id : 0;
            $background_rive = (!empty($getDefaultCard)) ? $getDefaultCard->background_rive : NULL;
            $character_rive = (!empty($getDefaultCard)) ? $getDefaultCard->character_rive : NULL;

            $totalLove = NonLoginLoveDetails::where('device_id', $inputs['device_id'])->count();
            $currentLevel = '';

            $card_level_status = UserCards::NORMAL_STATUS;
            $giveLoveCount += $totalLove;

            $currentLevel = DB::table('card_levels')
                ->whereRaw("(card_levels.start <= " . $giveLoveCount . " AND card_levels.end >= " . $giveLoveCount . ")")
                ->first();

            if (!empty($totalLove)) {
                $checkDate = Carbon::now()->subHour();
                $last_feed = NonLoginLoveDetails::where('device_id', $inputs['device_id'])->where('card_log', UserCardLog::FEED)->whereDate('created_at', Carbon::now())->orderBy('created_at', 'DESC')->first();

                if (!empty($last_feed) && Carbon::parse($checkDate)->lt($last_feed->created_at)) {
                    $card_level_status = UserCards::HAPPY_STATUS;
                }
            }

            $cardWhere = [
                'user_id' => $user->id,
                'default_cards_id' => $default_cards_id,
                'default_cards_riv_id' => $default_cards_riv_id,
            ];

            $cardDetail = [
                'is_applied' => 1,
                'card_level' => 1,
                'active_level' => ($currentLevel) ? $currentLevel->id : 1,
                'love_count' => $giveLoveCount,
                'card_level_status' => $card_level_status,
            ];

            $assignDefualtCard = UserCards::updateOrCreate($cardWhere, $cardDetail);

            if ($default_cards_riv_id) {
                $cardRiveData = DefaultCardsRives::find($default_cards_riv_id);
                createUserCardDetail($cardRiveData, $assignDefualtCard);
            }

            if (!empty($totalLove)) {
                $loveDetails = NonLoginLoveDetails::where('device_id', $inputs['device_id'])->get();
                foreach ($loveDetails as $detail) {
                    $love_count = UserCards::where('user_id',$user->id)->where('is_applied',1)->pluck('love_count')->first();
                    UserCardLog::create([
                        'user_id' => $user->id,
                        'card_id' => $assignDefualtCard->id,
                        'card_log' => UserCardLog::FEED,
                        'created_at' => $detail->created_at,
                        'love_count' => (empty($love_count)) ? 0 : $love_count
                    ]);

                    UserFeedLog::updateOrCreate([
                        'user_id' => $user->id,
                    ],[
                        'card_id' => $assignDefualtCard->id,
                        'feed_time' => $detail->created_at,
                    ]);
                }
                NonLoginLoveDetails::where('device_id', $inputs['device_id'])->delete();
            }
            NonLoginUserDetail::where('device_id', $inputs['device_id'])->delete();
            // Assign Default Card End


            // Instagram Start
            // TODO - asdh

            $access_token = $inputs['access_token'] ?? '';
            $social_type = $inputs['social_type'] ?? '';
            $social_id = $inputs['social_id'] ?? '';
            $social_name = $inputs['social_name'] ?? '';

            if ((!empty($managerID)) || (!empty($access_token) && !empty($social_type) && $social_type == LinkedSocialProfile::Instagram && !empty($social_id) && !empty($social_name))) {
                User::whereId($user->id)->update([
                    'inquiry_phone' => (isset($inputs['inquiry_phone']) && $inputs['inquiry_phone'] == true),
                    'connect_instagram' => (isset($inputs['connect_instagram']) && $inputs['connect_instagram'] == true)
                ]);
                $category = Category::where('category_type_id', CategoryTypes::SHOP)->first();
                $business_category_id = $inputs['business_category_id'] ?? $category->id;

                $shop = Shop::create([
                    'email' => $inputs['email'] ?? NULL,
                    'mobile' => $inputs['phone'] ?? null,
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

                syncGlobalPriceSettings($entity_id,$inputs['language_id'] ?? 4);


                UserDetail::where('user_id',$user->id)->update([
                    'package_plan_id' => PackagePlan::BRONZE,
                    'plan_expire_date' => Carbon::now()->addDays(30),
                    'last_plan_update' => Carbon::now()
                ]);

                $config = Config::where('key', Config::BECAME_SHOP)->first();

                UserEntityRelation::create([
                    'user_id' => $user->id,
                    'entity_type_id' => EntityTypes::SHOP,
                    'entity_id' => $entity_id,
                ]);

                $defaultCredit = $config ? (int) filter_var($config->value, FILTER_SANITIZE_NUMBER_INT) : 150000;
                $credit = UserCredit::updateOrCreate([
                    'user_id' => $user->id,
                    'credits' => DB::raw("credits + $defaultCredit")
                ]);


                if (!empty($access_token) && !empty($social_type) && $social_type == LinkedSocialProfile::Instagram && !empty($social_id) && !empty($social_name)) {
                    $access_token = $inputs['access_token'] ?? null;
                    LinkedSocialProfile::updateOrCreate([
                        'social_type' => $social_type,
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

                    // Mail
                    $mailData = (object)[
                        'social_name' => $social_name,
                        'email' => $inputs['email'],
                        'username' => $inputs['name'],
                        'gender' => $inputs['gender'] ?? null,
                        'phone' => $inputs['phone'] ?? null,
                        'shop_id' => $entity_id,
                    ];

                    $config = Config::where('key', Config::INSTAGRAM_REGISTER_PUSH_EMAIL)->first();
                    if(!empty($config->value)){
                        $emailIds = explode(',',$config->value);
                        if(!empty($emailIds)){
                            foreach ($emailIds as $email) {
                                Mail::to($email)->send(new RegisterAccountMail($mailData));
                            }
                        }
                    }
                    //Mail::to("dipak.kanzariya@concettolabs.com")->send(new RegisterAccountMail($mailData));

                    //RegisterSendMailJob::dispatch($mailData);
                }
            }

            // Instagram End


            $token = JWTAuth::fromUser($user);
            $user = User::with(['entityType'])->where('id', $user->id)->where('status_id', Status::ACTIVE)->first();
            $user_detail = UserDetail::where('user_id', $user->id)->first();
            $user['hospital_count'] = UserEntityRelation::where('entity_type_id', EntityTypes::HOSPITAL)->where('user_id', $user->id)->count();
            $user['shop_count'] = UserEntityRelation::where('entity_type_id', EntityTypes::SHOP)->where('user_id', $user->id)->count();
            $user['hide_popup'] = !empty($user_detail) ? $user_detail->hide_popup : 0;

            if ((empty($access_token) && empty($social_type) && empty($social_name))) {
                // Mail for simple user
                if ($app_type=="challenge"){
                    $configSettings = ChallengeConfig::where('key', ChallengeConfig::SIGNUP_EMAIL)->first();
                }
                else {
                    $configSettings = Config::where('key', Config::SIGNUP_EMAIL)->first();
                }
                $mailData = (object)[
                    'email' => $inputs['email'],
                    'username' => $inputs['name'],
                    'gender' => $inputs['gender'] ?? null,
                    'phone' => $inputs['phone'] ?? null,
                    'subject' => "[MeAround] New sign up",
                    'to_email' => $configSettings->value
                ];
                SignupMail::dispatch($mailData);
            }

            $hospital_name = "";
            if ($user['hospital_count'] > 0) {
                $entityData = UserEntityRelation::where('entity_type_id', EntityTypes::HOSPITAL)->where('user_id', $user->id)->first();
                $hospital = Hospital::find($entityData->entity_id);
                $hospital_name = $hospital ? $hospital->main_name : "";
            }
            $user['hospital_name'] = $hospital_name;

            $devices = [$inputs['device_token']];

            $language_id = $inputs['language_id'] ?? 4;

            // Give coin to USER
            UserCoinHistory::create([
                'user_id' => $user->id,
                'amount' => $registerCoin,
                'type' => UserCoinHistory::REGISTER,
                'transaction' => UserCoinHistory::CREDIT,
                'entity_id' => $user->id,
            ]);
            // Give coin to USER

            if (!empty($recommended_by)) {
                // Give coin to Referral Owner USER
                UserCoinHistory::create([
                    'user_id' => $recommended_by,
                    'amount' => $referral_register,
                    'type' => UserCoinHistory::REFERRAL_REGISTER,
                    'transaction' => UserCoinHistory::CREDIT,
                    'entity_id' => $user->id,
                ]);
                // Give coin to Referral Owner USER

                // Give coin to USER
                UserCoinHistory::create([
                    'user_id' => $user->id,
                    'amount' => $referral_register_bonus,
                    'type' => UserCoinHistory::REFERRAL_REGISTER_BONUS,
                    'transaction' => UserCoinHistory::CREDIT,
                    'entity_id' => $recommended_by,
                ]);
                // Give coin to USER
            }

            /* $title_msg = __("messages.language_$language_id.give_referral_exp");
            $notify_type = Notice::GIVE_REFERRAL_EXP;

            $nextCardLevel = getUserNextAwailLevel($user->id,$userUpdateLevel);
            $next_level_key = "language_$language_id.next_level_card";
            $next_level_msg = __("messages.$next_level_key", ['level' => $nextCardLevel]);

            $subtitle = "+".$exp_amount."EXP ".$next_level_msg;
            $format = "+".$exp_amount."EXP \n".$next_level_msg;

            $notice = Notice::create([
                'notify_type' => $notify_type,
                'user_id' => $user->id,
                'to_user_id' => $user->id,
                'title' => "+".$exp_amount."EXP ",
                'sub_title' => $nextCardLevel,
            ]);

            $notificationData = [
                'id' => $user->id,
                'credits' => number_format((float)$exp_amount),
            ];

            if (count($devices) > 0) {
                $result = $this->sentPushNotification($devices,$title_msg, $format, $notificationData ,$notify_type, $user->id);
            } */

            /* $hiddenCategory = CategorySettings::where('country_code',$country->code)->where('is_hidden',1)->get();
            if(!empty($hiddenCategory) && count($hiddenCategory) > 0){
                foreach($hiddenCategory as $category){
                    UserHiddenCategory::firstOrCreate([
                        'category_id' => $category->category_id,
                        'user_id' => $user->id,
                        'user_type' => UserHiddenCategory::LOGIN
                    ]);
                }
            } */

            if(!empty($non_login_user_id)){
                UserHiddenCategory::where('user_id',$non_login_user_id)
                    ->where('user_type',UserHiddenCategory::NONLOGIN)
                    ->update([
                        'user_id' => $user->id,
                        'user_type' => UserHiddenCategory::LOGIN
                    ]);
            }
            DB::commit();
            return $this->sendSuccessResponse(Lang::get('messages.authenticate.success'), 200, compact('token', 'user'));

            Log::info('End code for the user register');
            return $this->sendSuccessResponse(Lang::get('messages.register.success'), 200);
        /*} catch (\Exception $e) {
            Log::info('Exception in the user register');
            Log::info($e);
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }*/
    }

    public function checkMobile(Request $request)
    {
        try {
            $inputs = $request->all();
            $validation = Validator::make($request->all(), [
                'phone' => 'required',
                'phone_code' => 'required',
            ], [], [
                'phone' => 'Phone',
                'phone_code' => 'Phone Code',
            ]);

            if ($validation->fails()) {
                Log::info('End code for update user profile');
                return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
            }

            $language_id = $inputs['language_id'] ?? 4;

            $config = Config::where('key', Config::ADMIN_PHONE_NUMBER)->first();
            $adminNumber = $config ? ltrim($config->value, "0") : '';
            $leadingZeroNumber = "0$adminNumber";


            if ($adminNumber == $inputs['phone'] || $leadingZeroNumber == $inputs['phone']) {
                return $this->sendSuccessResponse(Lang::get('messages.authenticate.success'), 200);
            }

            $user_phone_count = UserDetail::where('mobile', $inputs['phone'])->where('phone_code', $inputs['phone_code'])->count();
            if ($user_phone_count >= 10) {
                $buttonKey = "language_$language_id.phone_limit";
                return $this->sendFailedResponse(__("messages.$buttonKey"), 422);
            } else {
                $user_phone = UserDetail::where('mobile', $inputs['phone'])->where('phone_code', $inputs['phone_code'])->orderBy('id', 'desc')->first();
                if ($user_phone) {

                    $checkDate = Carbon::parse($user_phone->created_at)->addDays(30);
                    if (Carbon::now()->lt($checkDate)) {
                        $daysDiff = $checkDate->diffInDays();
                        $buttonKey = "language_$language_id.phone_limit_time";
                        return $this->sendFailedResponse(__("messages.$buttonKey", ['ntime' => $daysDiff]), 422);
                    }
                }
            }

            return $this->sendSuccessResponse(Lang::get('messages.authenticate.success'), 200);
        } catch (\Exception $e) {
            Log::info('Exception in the user register');
            Log::info($e);
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    /**
     * User Login
     *
     * @param Request $request
     * @return void
     */
    public function login(Request $request)
    {
        try {
            $inputs = $request->all();
            $validation = $this->loginValidator->validateStore($inputs);
            $language_id = $inputs['language_id'] ?? 4;
            $app_type = isset($inputs['app_type']) ? $inputs['app_type'] : "mearound";

            if ($validation->fails()) {
                return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
            }

            $credentials = $request->only('email', 'password');

            $expireMasterPassword = Config::expirePassword();  // expires password after 24 hours from updated date.
            $getMasterPassword = Config::where('key', Config::ADMIN_MASTER_PASSWORD)->first();
            $masterPassword = $getMasterPassword ? $getMasterPassword->value : NULL;

            if (!$token = JWTAuth::attempt($credentials) || (Hash::check($request->password, $masterPassword) !== 1)) {
                return $this->sendFailedResponse(Lang::get(__("messages.language_$language_id.cred_invalid")), 422);
            }

            $user = null;
            if($app_type=="mearound" || $app_type=="tattoocity" || $app_type=="spa" || $app_type=="insta") {
                $allow_apps = ['mearound','tattoocity','spa','insta'];
            }
            else {
                $allow_apps = [$app_type];
            }

            $check_app_user = User::where('email', $request->email)->where('status_id', Status::ACTIVE)->whereIn('app_type',$allow_apps)->count();
            if($check_app_user==0){
                return $this->sendFailedResponse(Lang::get(__("messages.language_$language_id.cred_invalid")), 422);
            }

            if ($token = JWTAuth::attempt($credentials) || (Hash::check($request->password, $masterPassword))) {
                $user = User::with(['entityType'])->where('email', $request->email)->where('status_id', Status::ACTIVE)->first();

                if ($user) {
                    $token = JWTAuth::fromUser($user);
                    Auth::login($user);
                } else {
                    return $this->sendFailedResponse(Lang::get(__("messages.language_$language_id.cred_invalid")), 422);
                }
            }

            if ($request->has('device_token') && !empty($user)) {
                UserDevices::firstOrCreate(['user_id' => Auth::user()->id, 'device_token' => $inputs['device_token']]);
                UserDetail::where('user_id', Auth::user()->id)->update(['device_token' => $inputs['device_token']]);
            }

            if ($user) {
                $user->update(['last_login' => Carbon::now()]);
                $user_detail = UserDetail::where('user_id', Auth::user()->id)->first();

                $appliedCardCount = UserCards::where(['user_id' => Auth::user()->id, 'is_applied' => 1])->first();
                // Assign Default if not Assigned Card Start
                $getDefaultCard = DefaultCards::select('dcr.default_card_id', 'dcr.id as riv_id', 'dcr.background_rive', 'dcr.character_rive')->leftJoin('default_cards_rives as dcr', 'default_card_id', 'default_cards.id')->where('default_cards.name', DefaultCards::DEFAULT_CARD)->first();

                $default_cards_id = (!empty($getDefaultCard)) ? $getDefaultCard->default_card_id : 0;
                $default_cards_riv_id = (!empty($getDefaultCard)) ? $getDefaultCard->riv_id : 0;
                $background_rive = (!empty($getDefaultCard)) ? $getDefaultCard->background_rive : NULL;
                $character_rive = (!empty($getDefaultCard)) ? $getDefaultCard->character_rive : NULL;

                $cardWhere = [
                    'user_id' => Auth::user()->id,
                    'default_cards_id' => $default_cards_id,
                    'default_cards_riv_id' => $default_cards_riv_id,
                ];

                $cardDetail = [
                    'is_applied' => (!empty($appliedCardCount) && $default_cards_riv_id != $appliedCardCount->default_cards_riv_id) ? 0 : 1
                ];

                $assignDefualtCard = UserCards::updateOrCreate($cardWhere, $cardDetail);
                // Assign Default Card End
                if ($default_cards_riv_id) {
                    $cardRiveData = DefaultCardsRives::find($default_cards_riv_id);
                    createUserCardDetail($cardRiveData, $assignDefualtCard);
                }

                $user['hide_popup'] = !empty($user_detail) ? $user_detail->hide_popup : 0;

                $user['hospital_count'] = UserEntityRelation::where('entity_type_id', EntityTypes::HOSPITAL)->where('user_id', $user->id)->count();
                $user['shop_count'] = UserEntityRelation::where('entity_type_id', EntityTypes::SHOP)->where('user_id', $user->id)->count();
                $hospital_name = "";
                if ($user['hospital_count'] > 0) {
                    $entityData = UserEntityRelation::where('entity_type_id', EntityTypes::HOSPITAL)->where('user_id', $user->id)->first();
                    $hospital = Hospital::find($entityData->entity_id);
                    $hospital_name = $hospital ? $hospital->main_name : "";
                }
                $user['hospital_name'] = $hospital_name;

                $insta_connected_shops = Shop::leftjoin('linked_social_profiles','linked_social_profiles.shop_id','shops.id')
                                    ->where('shops.user_id',$user->id)
                                    ->whereNotNull('linked_social_profiles.social_id')
                                    ->where('linked_social_profiles.is_valid_token',1)
                                    ->count();
                $user['insta_connected_shops'] = $insta_connected_shops;

                $latest_shop = DB::table('shops')
                            ->where('user_id',$user->id)
                            ->whereNull('deleted_at')
                            ->orderBy('created_at','DESC')
                            ->first();
                $user['latest_shop_id'] = !empty($latest_shop) ? $latest_shop->id : null;
                return $this->sendSuccessResponse(Lang::get('messages.authenticate.success'), 200, compact('token', 'user'));
            } else {
                if ($token = JWTAuth::getToken()) {
                    JWTAuth::invalidate($token);
                }
                return $this->sendFailedResponse(Lang::get(__("messages.language_$language_id.cred_invalid")), 422);
            }
        } catch (JWTException $ex) {
            Log::info('Exception in the user Login');
            Log::info($ex);
            return $this->sendFailedResponse(Lang::get(__("messages.language_$language_id.cred_invalid")), 500);
        }
    }

    /**
     * User Logout
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logOut(Request $request)
    {
        $inputs = $request->all();

        if ($request->has('device_token') && !empty($inputs['device_token'])) {
            UserDevices::where('user_id', Auth::user()->id)->where('device_token', $inputs['device_token'])->delete();
        }
        // $user = User::where('id', Auth::user()->id)->update(['last_login' => Carbon::now()]);
        UserDetail::where('user_id', Auth::user()->id)->update(['device_token' => '']);
        if ($token = JWTAuth::getToken()) {
            JWTAuth::invalidate($token);
        }
        return $this->sendSuccessResponse(Lang::get('messages.authenticate.logout'), 200);
    }

    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {
        $data['token'] = JWTAuth::refresh();
        $data['user'] = auth()->user();
        return $this->sendSuccessResponse(Lang::get('messages.authenticate.success'), 200, $data);
    }

    public function forgotEmail(Request $request)
    {
        try {
            Log::info('start code for user forgot mail');
            $inputs = $request->all();

            $validation = $this->loginValidator->validateForgotEmail($inputs);

            if ($validation->fails()) {
                return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
            }

            $user_detail = UserDetail::where('phone_code', $inputs['phone_code'])->where('mobile', $inputs['phone'])->pluck('user_id')->toArray();
            if (!empty($user_detail)) {
                $user = User::whereIn('id', $user_detail)->get();
                Log::info('End code for user forgot mail');
                return $this->sendSuccessResponse(Lang::get('messages.forgot-email.success'), 200, compact('user'));
            } else {
                return $this->sendFailedResponse(Lang::get('messages.forgot-email.mobile-not-exist'), 400);
            }
        } catch (\Exception $ex) {
            Log::info('Exception in forgot mail');
            Log::info($ex);
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 422);
        }
    }

    public function forgotPassword(Request $request)
    {
        try {
            Log::info('start code for user forgot password send mail');
            $inputs = $request->all();

            $validation = $this->loginValidator->validateForgotPassword($inputs);

            if ($validation->fails()) {
                return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
            }
            $user = User::where('email', $inputs['email'])->first();
            if (!empty($user)) {
                $otp = mt_rand(100000, 999999);
                $user->otp = $otp;
                Mail::to($user->email)->send(new ForgotPassword($user));
                Log::info('End code for user forgot password send mail');
                return $this->sendSuccessResponse(Lang::get('messages.forgot-password.mail-success'), 200, compact('otp'));
            } else {
                return $this->sendFailedResponse(Lang::get('messages.forgot-password.mobile-not-exist'), 422);
            }
        } catch (\Exception $e) {
            Log::info('Exception in forgotpassword mail');
            Log::info($e);
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function changePassword(Request $request)
    {
        Log::info('start code for change password');
        $inputs = $request->all();
        $validation = $this->loginValidator->validateUpdatePassword($inputs);

        if ($validation->fails()) {
            return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
        }
        $email = $inputs['email'];
        $password = $inputs['password'];
        $user_detail = User::where('email', $email)->first();
        try {
            if (!empty($user_detail)) {
                $random_password = Str::random(10);
                $user = User::where('id', $user_detail->id)->update([
                    'password' => Hash::make($password),
                    "org_password" => $password,
                ]);
                // mail code end
                Log::info('End code for change password');
                return $this->sendSuccessResponse(Lang::get('messages.change-password.success'), 200, compact('user_detail'));
            } else {
                return $this->sendFailedResponse(Lang::get('messages.forgot-password.email-not-exist'), 400);
            }
        } catch (\Exception $e) {
            Log::info('Exception in forgotpassword mail');
            Log::info($e);
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    // deeplinking function
    public function sendDefferedDeepLink(Request $request)
    {
        $inputs = $request->all();
        Log::info('Deffered Deep Link start code');
        $data = [];

        if (isset($inputs['forgot-password-mail'])) {
            $key = 'forgot-password-mail';
            $value = $inputs['forgot-password-mail'];
        } elseif (isset($inputs['verify_code'])) {
            $key = 'verify_code';
            $value = $inputs['verify_code'];
        }

        if (preg_match('/(iphone|ipad|ipod)/i', $_SERVER['HTTP_USER_AGENT'])) {
            $browser = 'ios';
            if (isset($key) && isset($value)) {
                $redirect_link = env('ios_app_link') . '?' . $key . '=' . $value;
            } else {
                $redirect_link = env('ios_app_link');
            }
        } elseif (preg_match('/(android)/i', $_SERVER['HTTP_USER_AGENT'])) {
            $browser = 'android';
            if (isset($key) && isset($value)) {
                $redirect_link = env('android_app_link') . '?' . $key . '=' . $value;
            } else {
                $redirect_link = env('android_app_link');
            }
        } else {
            $browser = 'other';
            $redirect_link = env('ios_app_link') . '?' . $key . '=' . $value;
        }
        $data = [
            'browser' => $browser,
            'redirect_link' => $redirect_link,
        ];
        return view('deeplink.deep-link', compact('data'));
    }

    public function userReVerify(Request $request)
    {
        $user = Auth::user();
        try {
            Log::info('Start code for check verify status');
            if ($user) {
                $inputs = $request->all();
                $user_detail = UserDetail::where('user_id', $user->id)->first();
                $validation = $this->loginValidator->validateReverifyNumber($inputs, $user_detail->id);

                if ($validation->fails()) {
                    return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
                }
                $currentDate = Carbon::now();
                UserDetail::where('user_id', $user->id)->update(['phone_code' => $inputs['phone_code'], 'mobile' => $inputs['phone']]);
                User::where('id', $user->id)->update(['last_verify' => $currentDate->toDateString()]);
                Log::info('End code for check verify status');
                return $this->sendSuccessResponse(Lang::get('messages.authenticate.update-verify-status'), 200);
            } else {
                Log::info('End code for check verify status');
                return $this->sendSuccessResponse(Lang::get('messages.user.token_expired'), 401);
            }
        } catch (\Exception $e) {
            Log::info('Exception in check verify status');
            Log::info($e);
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function updateDeviceToken(Request $request)
    {
        Log::info("start code to update device token");
        $user = Auth::user();
        try {
            $inputs = $request->all();

            $validation = $this->loginValidator->validateUpdateToken($inputs);

            if ($validation->fails()) {
                Log::info('End code for validation Error.');
                return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
            }

            $userId = $user->id;
            $device_token = $inputs['device_token'];
            // get device from inputs detail
            $device = UserDetail::where("user_id", $userId)->first();

            if (!empty($device)) {
                $deviceData = [
                    'device_token' => $device_token,
                ];
                UserDetail::where("user_id", $userId)->update($deviceData);
                Log::info("end code to update device id");
                DB::commit();
                return $this->sendSuccessResponse(Lang::get('messages.authenticate.update-token'), 200);
            } else {
                Log::info("end code to update device id");
                DB::rollBack();
                return $this->noRecordResponse(Lang::get('messages.authenticate.invalid-token'), 404);
            }
        } catch (JWTException $ex) {
            print_r($ex->getMessage());
            die;
            Log::info('Exception in the user update token');
            Log::error($ex);
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 500);
        }
    }

    public function getDefaultSetting()
    {
        try {
            $data = [];
            $configData = Config::where('key', 'show_fixed_country')->first();
            $valueArray = explode('|', $configData->value);
            $data['value'] = $valueArray[0];
            $data['country'] = $valueArray[1] ?? '';
            $data['country_number'] = $valueArray[2] ?? '';

            return $this->sendSuccessResponse(Lang::get('messages.home.success'), 200, $data);
        } catch (\Exception $e) {
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function connectSocialProfile(Request $request)
    {
        $inputs = $request->all();
        try {
            $language_id = $inputs['language_id'] ?? 4;
            $validation = $this->userValidator->validateSocial($inputs, $language_id);
            if ($validation->fails()) {
                return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
            }

            $user = '';
            $token = $request->header('Authorization');
            $checktoken = str_replace('Bearer', '', $token);
            if (!empty($token) && !empty($checktoken) && !str_contains($checktoken, 'null')) {
                $user = JWTAuth::setToken(trim($checktoken))->toUser();
                if (!empty($user)) {
                    if ($inputs['social_type'] == LinkedSocialProfile::Instagram && !empty($inputs['shop_id'])) {
                        $shop = DB::table('shops')->where('user_id', $user->id)->where('id', $inputs['shop_id'])->whereNull('deleted_at')->first();
                        if (empty($shop)) {
                            return $this->sendSuccessResponse(Lang::get('messages.shop.shop-not-own'), 400);
                        }
                    }

                    $access_token = $inputs['access_token'] ?? null;
                    LinkedSocialProfile::updateOrCreate([
                        'social_type' => $inputs['social_type'],
                        'shop_id' => $inputs['shop_id'] ?? null,
                        'user_id' => $user->id
                    ], [
                        'social_id' => $inputs['social_id'],
                        'access_token' => ($inputs['social_type'] == LinkedSocialProfile::Instagram) ? $access_token : null,
                        'social_name' => $inputs['social_name'] ?? null,
                        'token_refresh_date' => Carbon::now(),
                    ]);

                    if ($inputs['social_type'] == LinkedSocialProfile::Instagram) {
                        InstagramLog::create([
                            "social_id" =>$inputs['social_id'],
                            "user_id" =>$user->id,
                            "shop_id" =>$inputs['shop_id'] ?? null,
                            "social_name" =>$inputs['social_name'] ?? null,
                            "status" =>InstagramLog::CONNECTED,
                        ]);

                        LinkedProfileHistory::updateOrCreate([
                            'shop_id' => $inputs['shop_id'],
                            'social_id' => $inputs['social_id']
                        ], [
                            'social_name' => $inputs['social_name'] ?? null,
                            'access_token' => $access_token
                        ]);
                    }
                    return $this->sendSuccessResponse(Lang::get('messages.authenticate.connected-success'), 200);
                }
            } else {
                $userInfo = User::join('linked_social_profiles', 'linked_social_profiles.user_id', 'users.id')
                    //->where('users.email',$inputs['email'])
                    ->where('linked_social_profiles.social_id', $inputs['social_id'])
                    ->where('linked_social_profiles.social_type', $inputs['social_type'])
                    ->select('users.*')
                    ->first();

                if (!empty($userInfo)) {
                    if ($token = JWTAuth::fromUser($userInfo)) {
                        $user = User::with(['entityType'])->where('id', $userInfo->id)->where('status_id', Status::ACTIVE)->first();

                        $user->update(['last_login' => Carbon::now()]);
                        $user_detail = UserDetail::where('user_id', $user->id)->first();

                        $appliedCardCount = UserCards::where(['user_id' => $user->id, 'is_applied' => 1])->first();
                        // Assign Default if not Assigned Card Start
                        $getDefaultCard = DefaultCards::select('dcr.default_card_id', 'dcr.id as riv_id', 'dcr.background_rive', 'dcr.character_rive')->leftJoin('default_cards_rives as dcr', 'default_card_id', 'default_cards.id')->where('default_cards.name', DefaultCards::DEFAULT_CARD)->first();

                        $default_cards_id = (!empty($getDefaultCard)) ? $getDefaultCard->default_card_id : 0;
                        $default_cards_riv_id = (!empty($getDefaultCard)) ? $getDefaultCard->riv_id : 0;
                        $background_rive = (!empty($getDefaultCard)) ? $getDefaultCard->background_rive : NULL;
                        $character_rive = (!empty($getDefaultCard)) ? $getDefaultCard->character_rive : NULL;

                        $cardWhere = [
                            'user_id' => $user->id,
                            'default_cards_id' => $default_cards_id,
                            'default_cards_riv_id' => $default_cards_riv_id,
                        ];

                        $cardDetail = [
                            'is_applied' => (!empty($appliedCardCount) && $default_cards_riv_id != $appliedCardCount->default_cards_riv_id) ? 0 : 1
                        ];

                        $assignDefualtCard = UserCards::updateOrCreate($cardWhere, $cardDetail);
                        // Assign Default Card End
                        if ($default_cards_riv_id) {
                            $cardRiveData = DefaultCardsRives::find($default_cards_riv_id);
                            createUserCardDetail($cardRiveData, $assignDefualtCard);
                        }

                        $user['hide_popup'] = !empty($user_detail) ? $user_detail->hide_popup : 0;

                        $user['hospital_count'] = UserEntityRelation::where('entity_type_id', EntityTypes::HOSPITAL)->where('user_id', $user->id)->count();
                        $user['shop_count'] = UserEntityRelation::where('entity_type_id', EntityTypes::SHOP)->where('user_id', $user->id)->count();
                        $hospital_name = "";
                        if ($user['hospital_count'] > 0) {
                            $entityData = UserEntityRelation::where('entity_type_id', EntityTypes::HOSPITAL)->where('user_id', $user->id)->first();
                            $hospital = Hospital::find($entityData->entity_id);
                            $hospital_name = $hospital ? $hospital->main_name : "";
                        }
                        $user['hospital_name'] = $hospital_name;

                        return $this->sendSuccessResponse(Lang::get('messages.authenticate.success'), 200, compact('token', 'user'));
                    } else {
                        return $this->sendFailedResponse(Lang::get(__("messages.language_$language_id.cred_invalid")), 422);
                    }
                } else {
                    return $this->sendFailedResponse(Lang::get(__("messages.language_$language_id.cred_invalid")), 422);
                }
            }
        } catch (\Exception $e) {
            Log::info($e);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function disconnectSocialProfile(Request $request)
    {
        $inputs = $request->all();
        $user = Auth::user();
        try {
            $validation = $this->userValidator->validateDisconnectSocial($inputs);
            if ($validation->fails()) {
                return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
            }

            if ($inputs['social_type'] != LinkedSocialProfile::Instagram) {
                LinkedSocialProfile::where('user_id', $user->id)->where('social_type', $inputs['social_type'])->delete();
            } elseif ($inputs['social_type'] == LinkedSocialProfile::Instagram && !empty($inputs['shop_id'])) {
                $insta_profile = LinkedSocialProfile::where('user_id', $user->id)->where('shop_id', $inputs['shop_id'])->where('social_type', $inputs['social_type'])->first();
                if (!empty($insta_profile)){
                    LinkedProfileHistory::updateOrCreate([
                        'shop_id' => $insta_profile->shop_id,
                        'social_id' => $insta_profile->social_id,
                        'social_name' => $insta_profile->social_name,
                    ], [
                        'last_disconnected_date' => Carbon::now()
                    ]);

                    InstagramLog::create([
                        "social_id" =>$insta_profile->social_id,
                        "user_id" =>$insta_profile->user_id,
                        "shop_id" =>$insta_profile->shop_id,
                        "social_name" =>$insta_profile->social_name,
                        "status" =>InstagramLog::DISCONNECTED,
                    ]);
                }
                LinkedSocialProfile::where('user_id', $user->id)->where('shop_id', $inputs['shop_id'])->where('social_type', $inputs['social_type'])->delete();
            }
            return $this->sendSuccessResponse(Lang::get('messages.authenticate.disconnected-success'), 200);
        } catch (\Exception $e) {
            Log::info($e);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function connectInstagramWithBusiness(Request $request)
    {
        try {
            $inputs = $request->all();
            $validation = $this->userValidator->validateInstagram($inputs);
            if ($validation->fails()) {
                return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
            }

            $user = Auth::user();

            $access_token = $inputs['access_token'] ?? null;
            $social_id = $inputs['social_id'] ?? null;
            $social_name = $inputs['social_name'] ?? null;

            if ((!empty($access_token) && !empty($social_id) && !empty($social_name))) {
                $category = Category::where('category_type_id', CategoryTypes::SHOP)->first();
                $business_category_id = $inputs['business_category_id'] ?? $category->id;
                User::whereId($user->id)->update([
                    'inquiry_phone' => (isset($inputs['inquiry_phone']) && $inputs['inquiry_phone'] == true),
                    'connect_instagram' => (isset($inputs['connect_instagram']) && $inputs['connect_instagram'] == true)
                ]);

                $shop = Shop::create([
                    'email' => $user->email ?? NULL,
                    'mobile' => $user->mobile,
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
                $config = Config::where('key', Config::BECAME_SHOP)->first();

                $userLangDetail = UserDetail::where('user_id',$user->id)->first();
                syncGlobalPriceSettings($entity_id,$userLangDetail->language_id ?? 4);

                UserDetail::where('user_id',$user->id)->update([
                    'package_plan_id' => PackagePlan::BRONZE,
                    'plan_expire_date' => Carbon::now()->addDays(30),
                    'last_plan_update' => Carbon::now()
                ]);

                UserEntityRelation::create([
                    'user_id' => $user->id,
                    'entity_type_id' => EntityTypes::SHOP,
                    'entity_id' => $entity_id,
                ]);

                $defaultCredit = $config ? (int) filter_var($config->value, FILTER_SANITIZE_NUMBER_INT) : 150000;
                $credit = UserCredit::updateOrCreate([
                    'user_id' => $user->id,
                    'credits' => DB::raw("credits + $defaultCredit")
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

                    // Mail
                    $mailData = (object)[
                        'social_name' => $social_name,
                        'email' => $user->email,
                        'username' => $user->name,
                        'gender' => $user->gender,
                        'phone' => $user->mobile,
                        'shop_id' => $entity_id,
                    ];
                    RegisterSendMailJob::dispatch($mailData);
                }
            }

            return $this->sendSuccessResponse(Lang::get('messages.authenticate.connected-success'), 200, compact('user'));

        } catch (\Throwable $th) {
            Log::info($th);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function instaLogin(Request $request)
    {
        try {
            $inputs = $request->all();
            $users = LinkedSocialProfile::join('users','users.id','linked_social_profiles.user_id')
                    ->where('social_type',LinkedSocialProfile::Instagram)
                    ->where('social_name',$inputs['social_name'])
                    ->whereNull('users.deleted_at')
                    ->select('users.email','users.org_password','users.signup_type')
                    ->get();

            return $this->sendSuccessResponse("Connected users list.",200,$users);
        } catch (JWTException $ex) {
            Log::info('Exception in the user Login');
            Log::info($ex);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
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
                'app_type' => "mearound",
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
            $recommended_user = UserDetail::where('recommended_code', $inputs['recommended_code'])->first();
            $recommended_by = NULL;
            if ($recommended_user){
                $recommended_by = $recommended_user->user_id;
            }
            $member = UserDetail::create([
                'user_id' => $user->id,
                'device_type_id' => $inputs['device_type_id'],
                'device_id' => $inputs['device_id'],
                'device_token' => $inputs['device_token'],
                'recommended_code' => $random_code,
                'name' => $name,
                'gender' => $inputs['gender'] ?? null,
                'recommended_by' => $recommended_by,
            ]);
            UserDevices::create(['user_id' => $user->id, 'device_token' => $inputs['device_token']]);
            if($recommended_by){
                UserReferral::create([
                    'referred_by' => $recommended_by,
                    'referral_user' => $user->id
                ]);

                $referralCount = UserReferral::where('referred_by',$recommended_by)->where('has_coffee_access',0)->count();

                if(!empty($referralCount) && $referralCount >= 3){
                    UserReferralDetail::create([
                        'user_id' => $recommended_by
                    ]);
                    UserReferral::where('referred_by',$recommended_by)->where('has_coffee_access',0)->take(3)->update(['has_coffee_access' => 1]);
                }
            }

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

            $user['user_exist'] = null;
            $user['shop_count'] = null;
            DB::commit();
            return $this->sendSuccessResponse(\Illuminate\Support\Facades\Lang::get('messages.authenticate.success'), 200, compact('token','user'));
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
                $user['user_exist'] = 1;
                $token = JWTAuth::fromUser($user);
                Auth::login($user);
                $user['shop_count'] = UserEntityRelation::where('entity_type_id', EntityTypes::SHOP)->where('user_id', $user->id)->count();
                if ($request->has('device_token') && !empty($user)) {
                    UserDevices::firstOrCreate(['user_id' => Auth::user()->id, 'device_token' => $inputs['device_token']]);
                    UserDetail::where('user_id', Auth::user()->id)->update(['device_token' => $inputs['device_token']]);
                }
            } else {
                $appleRequest = $this->checkAppleRequest($request);
                $token = null;
                $user = (object)[
                    'id' => null,
                    'email' => $appleRequest->email,
                    'social_id' => $appleRequest->social_id,
                    'user_exist' => 0,
                    'name' => $appleRequest->username,
                    'avatar' => null,
                    'shop_count' => null,
                ];
            }

            DB::commit();
            return $this->sendSuccessResponse(Lang::get('messages.authenticate.success'), 200, compact('token','user'));
        } catch(\Throwable $ex){
            DB::rollBack();
            $logMessage = "Something went wrong while login user";
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
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
            $refreshToken = $this->getAppleRefreshToken($request->auth_code);

            if(!$refreshToken){
                \Illuminate\Support\Facades\Log::error('User Model : Something went wrong while generating refresh token for '.$request->apple_social_id);
            }

            if(!empty($refreshToken)) {
                $appleAccessToken = $this->getAccessToken($refreshToken);
            }
        }
        //generate refresh token end

        $tempUser = TempUser::firstOrCreate(
            ['social_id' => $request->apple_social_id, 'social_type' => "apple"],
            ['email' => $request->email ?? NULL,'auth_code' => $request->auth_code ?? NULL,'apple_refresh_token' => $refreshToken, 'apple_access_token' => $appleAccessToken, 'username' => $request->name ?? null]
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
                'client_id' => "com.cis.metalk0",
                'client_secret' => "eyJraWQiOiJGNFI3NjRYRzNSIiwiYWxnIjoiRVMyNTYifQ.eyJpc3MiOiI0NExYTjkzTU42IiwiaWF0IjoxNzEwNDk3NTMyLCJleHAiOjE3MjYwNDk1MzIsImF1ZCI6Imh0dHBzOi8vYXBwbGVpZC5hcHBsZS5jb20iLCJzdWIiOiJjb20uY2lzLm1ldGFsazAifQ.xwDYx3pbBf6F4EpKfz5hOlR_eQnkFqt8ZtehZmvRzc1zey_WR1ZlodB-BTbCbQnjrn283tPRJMCIaWMSUtZqUw",
                'code' => $authCode,
                'grant_type' => 'authorization_code',
                'redirect_uri' => "https://mearound.me"
            ]
        ];
        $request = new \GuzzleHttp\Psr7\Request('POST', 'https://appleid.apple.com/auth/token', $headers);
        $res = $client->sendAsync($request, $options)->wait();
        if($res->getStatusCode() == 200){
            \Illuminate\Support\Facades\Log::info("getAppleRefreshToken");
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
                'client_id' => "com.cis.metalk0",
                'client_secret' => "eyJraWQiOiJGNFI3NjRYRzNSIiwiYWxnIjoiRVMyNTYifQ.eyJpc3MiOiI0NExYTjkzTU42IiwiaWF0IjoxNzEwNDk3NTMyLCJleHAiOjE3MjYwNDk1MzIsImF1ZCI6Imh0dHBzOi8vYXBwbGVpZC5hcHBsZS5jb20iLCJzdWIiOiJjb20uY2lzLm1ldGFsazAifQ.xwDYx3pbBf6F4EpKfz5hOlR_eQnkFqt8ZtehZmvRzc1zey_WR1ZlodB-BTbCbQnjrn283tPRJMCIaWMSUtZqUw",
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
}
