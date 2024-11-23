<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\ChallengeVerificationMail;
use App\Models\Challenge;
use App\Models\ChallengeAdmin;
use App\Models\ChallengeAdminNotice;
use App\Models\ChallengeAppInvited;
use App\Models\ChallengeCategory;
use App\Models\ChallengeConfig;
use App\Models\ChallengeDay;
use App\Models\ChallengeImages;
use App\Models\ChallengeInvitedUser;
use App\Models\ChallengeInviteText;
use App\Models\ChallengeKakaoTalkLink;
use App\Models\ChallengeMenu;
use App\Models\ChallengeNotice;
use App\Models\ChallengeParticipatedUser;
use App\Models\ChallengeThumb;
use App\Models\ChallengeUserFollowing;
use App\Models\ChallengeUserPoint;
use App\Models\ChallengeVerify;
use App\Models\ChallengeVerifyImage;
use App\Models\LinkChallengeSetting;
use App\Models\User;
use App\Models\UserDetail;
use App\Models\UserDevices;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class ChallengeController extends Controller
{
    public function listParticipatedChallenge(Request $request)
    {
        $user = Auth::user();
        try {
            $inputs = $request->all();
            $day = "";
            if ($inputs['day']=="Mon"){
                $day = "mo";
            }
            elseif ($inputs['day']=="Tue"){
                $day = "tu";
            }
            elseif ($inputs['day']=="Wed"){
                $day = "we";
            }
            elseif ($inputs['day']=="Thu"){
                $day = "th";
            }
            elseif ($inputs['day']=="Fri"){
                $day = "fr";
            }
            elseif ($inputs['day']=="Sat"){
                $day = "sa";
            }
            elseif ($inputs['day']=="Sun"){
                $day = "su";
            }

            if($user) {
                $challenges = ChallengeParticipatedUser::join('challenges', 'challenges.id', 'challenge_participated_users.challenge_id')
                                ->where('challenge_participated_users.user_id',$user->id)
                                ->select(
                                    'challenges.id',
                                    'challenges.title',
                                    'challenges.challenge_thumb_id',
                                    'challenges.description'
                                )
                                ->orderBy('challenges.verify_time')
                                ->get();

                if (isset($inputs['date']) && isset($inputs['day'])){
                    $inputDate = $inputs['date'];
                    $period_challenges = ChallengeParticipatedUser::join('challenges', 'challenges.id', 'challenge_participated_users.challenge_id')
                            ->leftjoin('challenge_days', function ($join) {
                                $join->on('challenges.id', '=', 'challenge_days.challenge_id');
                            })
                            ->where('challenge_participated_users.user_id',$user->id)
                            ->whereDate('challenges.start_date', '<=', $inputDate)
                            ->whereDate('challenges.end_date', '>=', $inputDate)
                            ->where('challenge_days.day',$day)
                            ->where('challenges.is_period_challenge',1)
                            ->groupby('challenges.id')
                            ->select(
                                'challenges.id',
                                'challenges.title',
                                'challenges.challenge_thumb_id',
                                'challenges.verify_time',
                                'challenges.description'
                            )
                            ->get();
                    $normal_challenges = ChallengeParticipatedUser::join('challenges', 'challenges.id', 'challenge_participated_users.challenge_id')
                        ->where('challenge_participated_users.user_id',$user->id)
                        ->where('challenges.is_period_challenge',0)
                        ->where('challenges.date',$inputs['date'])
                        ->select(
                            'challenges.id',
                            'challenges.title',
                            'challenges.challenge_thumb_id',
                            'challenges.verify_time',
                            'challenges.description'
                        )
                        ->get();

                    $challenges = $period_challenges->merge($normal_challenges);
                    $challenges = $challenges->sortByDesc('verify_time')->values();
                }

                $challenges->map(function($item) use ($inputs){
                    $thumb = "";
                    if (!empty($item->challenge_thumb_id)){
                        $thumb = ChallengeThumb::where('id',$item->challenge_thumb_id)->pluck('image')->first();
                        $thumb = !empty($thumb) ? Storage::disk('s3')->url($thumb) : "";
                    }
                    $item->thumb_image = $thumb;

                    $timeArr = explode(":",$item->verify_time);
                    $time = isset($timeArr[2]) ? $timeArr[0].":".$timeArr[1] : $item->verify_time; //remove seconds
                    $dbTime = \Carbon\Carbon::createFromFormat('H:i', $time, "UTC");
                    $userTime = $dbTime->setTimezone($inputs['timezone']);
                    $item->verify_time = $userTime->format('H:i');

                    return $item;
                });

                return $this->sendSuccessResponse("Challenge list.", 200, $challenges);
            }
            else{
                return $this->sendSuccessResponse(Lang::get('messages.user.token_expired'), 401);
            }
        } catch (\Exception $e) {
            Log::info('Exception in the list challenge');
            Log::info($e);
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function viewChallenge(Request $request)
    {
        $user = Auth::user();
        try {
            $inputs = $request->all();

            if($user) {
                $challenge = Challenge::with(['challengeimages'])->where('id',$inputs['challenge_id'])->first();
                $participants = ChallengeParticipatedUser::join('users_detail', 'users_detail.user_id', 'challenge_participated_users.user_id')
                    ->where('challenge_participated_users.challenge_id',$inputs['challenge_id'])
                    ->select('users_detail.name','users_detail.user_id')
                    ->get();

                $timeArr = explode(":",$challenge->verify_time);
                $time = isset($timeArr[2]) ? $timeArr[0].":".$timeArr[1] : $challenge->verify_time; //remove seconds
                $dbTime = \Carbon\Carbon::createFromFormat('H:i', $time, "UTC");
                $userTime = $dbTime->setTimezone($inputs['timezone']);
                $challenge->verify_time = $userTime->format('H:i');

                $challenge_days = ChallengeDay::where('challenge_id',$challenge->id)->orderBy('created_at','ASC')->pluck('day')->toArray();
                foreach ($challenge_days as &$challenge_day){
                    if($challenge_day=="mo"){
                        $day = "Mon";
                    }
                    elseif($challenge_day=="tu"){
                        $day = "Tue";
                    }
                    elseif($challenge_day=="we"){
                        $day = "Wed";
                    }
                    elseif($challenge_day=="th"){
                        $day = "Thu";
                    }
                    elseif($challenge_day=="fr"){
                        $day = "Fri";
                    }
                    elseif($challenge_day=="sa"){
                        $day = "Sat";
                    }
                    elseif($challenge_day=="su"){
                        $day = "Sun";
                    }

                    $challenge_day = $day;
                }
                $challenge->challenge_days = $challenge_days;

                $participants->map(function ($val) {
                    $image = UserDetail::where('user_id',$val->user_id)->pluck('avatar')->first();
                    $val->avatar = $image;

                    return $val;
                });

                $data['info'] = $challenge;
                $data['participants'] = $participants;

                $is_partcipate = ChallengeParticipatedUser::where('challenge_id',$inputs['challenge_id'])->where('user_id',$user->id)->first();
                $data['is_participated'] = !empty($is_partcipate) ? 1 : 0;

                return $this->sendSuccessResponse("Challenge room.", 200, $data);
            }
            else{
                return $this->sendSuccessResponse(Lang::get('messages.user.token_expired'), 401);
            }
        } catch (\Exception $e) {
            Log::info('Exception in the challenge room');
            Log::info($e);
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function createPeriodChallenge(Request $request)
    {
        $user = Auth::user();
        try {
            DB::beginTransaction();
            $inputs = $request->all();

            if($user) {
                $inputTime = Carbon::createFromFormat('H:i', $inputs['verify_time'], $inputs['timezone']);
                $utcTime = $inputTime->setTimezone('UTC');
                $formattedUtcTime = $utcTime->format('H:i');

                $PeriodChallenge = Challenge::create([
                    'title' => $inputs['title'],
                    'verify_time' => $formattedUtcTime,
                    'deal_amount' => $inputs['deal_amount'] ?? null,
                    'description' => $inputs['description'],
                    'start_date' => $inputs['start_date'],
                    'end_date' => $inputs['end_date'],
                    'is_period_challenge' => 1,
                    'category_id' => $inputs['category_id'],
                    'challenge_thumb_id' => $inputs['thumb_id'] ?? null,
                    'depositor_name' => $inputs['depositor_name'] ?? null,
                    'user_id' => $user->id
                ]);
                foreach ($inputs['day'] as $day){
                    if ($day=="Mon"){
                        $day = "mo";
                    }
                    elseif ($day=="Tue"){
                        $day = "tu";
                    }
                    elseif ($day=="Wed"){
                        $day = "we";
                    }
                    elseif ($day=="Thu"){
                        $day = "th";
                    }
                    elseif ($day=="Fri"){
                        $day = "fr";
                    }
                    elseif ($day=="Sat"){
                        $day = "sa";
                    }
                    elseif ($day=="Sun"){
                        $day = "su";
                    }

                    ChallengeDay::create([
                        'challenge_id' => $PeriodChallenge->id,
                        'day' => $day,
                    ]);
                }
                if (!empty($inputs['images'])) {
                    $ChallengeFolder = config('constant.challenge');
                    if (!Storage::exists($ChallengeFolder)) {
                        Storage::makeDirectory($ChallengeFolder);
                    }
                    foreach ($inputs['images'] as $image) {
                        if (is_file($image)) {
                            $fileType = $image->getMimeType();
                            $mainImage = Storage::disk('s3')->putFile($ChallengeFolder, $image, 'public');
                            $fileName = basename($mainImage);
                            $image_url = $ChallengeFolder . '/' . $fileName;

                            ChallengeImages::create([
                                'challenge_id' => $PeriodChallenge->id,
                                'image' => $image_url,
                            ]);
                        }
                    }
                }

                DB::commit();
                return $this->sendSuccessResponse("Challenge created.", 200);
            }
            else{
                return $this->sendSuccessResponse(Lang::get('messages.user.token_expired'), 401);
            }
        } catch (\Exception $e) {
            Log::info('Exception in the period challenge create');
            Log::info($e);
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function createChallenge(Request $request)
    {
        $user = Auth::user();
        try {
            DB::beginTransaction();
            $inputs = $request->all();

            if($user) {
                $inputTime = Carbon::createFromFormat('H:i', $inputs['verify_time'], $inputs['timezone']);
                $utcTime = $inputTime->setTimezone('UTC');
                $formattedUtcTime = $utcTime->format('H:i');

                $Challenge = Challenge::create([
                    'title' => $inputs['title'],
                    'verify_time' => $formattedUtcTime,
                    'deal_amount' => $inputs['deal_amount'] ?? null,
                    'description' => $inputs['description'],
                    'date' => $inputs['date'],
                    'is_period_challenge' => 0,
                    'category_id' => $inputs['category_id'],
                    'challenge_thumb_id' => $inputs['thumb_id'] ?? null,
                    'depositor_name' => $inputs['depositor_name'] ?? null,
                    'user_id' => $user->id
                ]);

                if (!empty($inputs['images'])) {
                    $ChallengeFolder = config('constant.challenge');
                    if (!Storage::exists($ChallengeFolder)) {
                        Storage::makeDirectory($ChallengeFolder);
                    }
                    foreach ($inputs['images'] as $image) {
                        if (is_file($image)) {
                            $fileType = $image->getMimeType();
                            $mainImage = Storage::disk('s3')->putFile($ChallengeFolder, $image, 'public');
                            $fileName = basename($mainImage);
                            $image_url = $ChallengeFolder . '/' . $fileName;

                            ChallengeImages::create([
                                'challenge_id' => $Challenge->id,
                                'image' => $image_url,
                            ]);
                        }
                    }
                }

                DB::commit();
                return $this->sendSuccessResponse("Challenge created.", 200);
            }
            else{
                return $this->sendSuccessResponse(Lang::get('messages.user.token_expired'), 401);
            }
        } catch (\Exception $e) {
            Log::info('Exception in the challenge create');
            Log::info($e);
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function listChallenge(Request $request)
    {
        try {
            $inputs = $request->all();

            $period_challenge_cats = ChallengeCategory::where('challenge_type',ChallengeCategory::PERIODCHALLENGE)->where('is_hidden',0)->orderBy('order','ASC')->get(['id','name']);
            $data['period_challenge_categories'] = $period_challenge_cats;
            $data['period_challenges'] = Challenge::where('is_period_challenge',1)->get(['id','challenge_thumb_id','title','description']);

/*            $best_users = DB::table('users')
                ->join('users_detail', 'users_detail.user_id', 'users.id')
                ->whereNull('users.deleted_at')
                ->whereNull('users_detail.deleted_at')
                ->where('users.app_type','challenge')
                ->select('users_detail.user_id','users_detail.name','users_detail.avatar')
                ->get()
                ->map(function($item){
                    if (empty($item->avatar)) {
                        $item->avatar = asset('img/avatar/avatar-1.png');
                    } else {
                        if (!filter_var($item->avatar, FILTER_VALIDATE_URL)) {
                            $item->avatar = Storage::disk('s3')->url($item->avatar);
                        }
                    }

                    $count = ChallengeParticipatedUser::where('user_id',$item->user_id)->count();
                    $participated_count = $count;

                    $challenges = ChallengeParticipatedUser::join('challenges', 'challenges.id', 'challenge_participated_users.challenge_id')
                        ->where('challenge_participated_users.user_id',$item->user_id)
                        ->where('challenges.is_period_challenge',0)
                        ->count();
                    $period_challenges = ChallengeParticipatedUser::join('challenges', 'challenges.id', 'challenge_participated_users.challenge_id')
                        ->where('challenge_participated_users.user_id',$item->user_id)
                        ->whereNotNull('challenges.start_date')
                        ->whereNotNull('challenges.end_date')
                        ->get(['challenges.id','challenges.start_date','challenges.end_date'])
                        ->map(function ($item){
                            $challenge_days = ChallengeDay::where('challenge_id',$item['id'])->pluck('day')->toArray();

                            $startDate = Carbon::createFromFormat('Y-m-d',$item['start_date']);
                            $endDate = Carbon::createFromFormat('Y-m-d', $item['end_date']);
                            $dates = collect(Carbon::parse($startDate)->range($endDate));
                            $cnt = 0;
                            $dayNames = $dates->map(function ($date) use ($challenge_days,&$cnt){
                                $dayName = $date->formatLocalized('%a');
                                $shortenedDayName = strtolower(substr($dayName, 0, 2));
                                if (in_array($shortenedDayName,$challenge_days)){
                                    $cnt++;
                                }
                                return $shortenedDayName;
                            });

                            $item['count'] = $cnt;
                            return $item;
                        })
                        ->sum('count');
                    $total_schedule = $challenges + $period_challenges;
                    $verified = ChallengeVerify::where('user_id',$item->user_id)->where('is_verified',1)->count();
                    if ($total_schedule!=0) {
                        $rate = ((int)$verified / (int)$total_schedule) * 100;
                        $rate = max(0, min(100, $rate));
                    }
                    $achievement = isset($rate) ? round($rate,2) : 0;

                    if ($achievement!=0) {
                        $best_user_rate = (int)$participated_count / (int)$achievement;
                        $best_user_rate = max(0, min(100, $best_user_rate));
                    }
                    $best_user_rate = isset($best_user_rate) ? round($best_user_rate,2) : 0;
                    $item->best_user_rate = $best_user_rate;

                    return $item;
                })
                ->sortByDesc('best_user_rate')
                ->values();
            $data['best_users'] = $best_users;*/

            $best_challenges = Challenge::select('id','title','challenge_thumb_id')
                ->selectSub(function ($q) {
                    $q->select(DB::raw('count(id) as count'))->from('challenge_participated_users')->whereRaw("`challenges`.`id` = `challenge_participated_users`.`challenge_id`");
                }, 'participated_count')
                ->orderBy('participated_count','DESC')
                ->get();
            $data['best_challenges'] = $best_challenges;

            $recent_users = User::select('id','created_at')->orderBy('created_at','DESC')->get();
            $data['recent_users'] = $recent_users;
            return $this->sendSuccessResponse("Challenge list page.", 200, $data);
        } catch (\Exception $e) {
            Log::info($e);
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function listChallengeCategory(Request $request)
    {
        try {
            $inputs = $request->all();

            $data = Challenge::where('is_period_challenge',1)
                ->where('category_id',$inputs['category_id'])
                ->get(['id','challenge_thumb_id','title','description']);

            return $this->sendSuccessResponse("Challenge list page.", 200, $data);
        } catch (\Exception $e) {
            Log::info($e);
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function listCategory(Request $request)
    {
        try {
            $inputs = $request->all();

            $categories = ChallengeCategory::where('challenge_type',$inputs['challenge_type'])->orderBy('order','ASC')->get(['id','name']);

            return $this->sendSuccessResponse("Category page.", 200, $categories);
        } catch (\Exception $e) {
            Log::info($e);
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function listThumb(Request $request)
    {
        try {
            $inputs = $request->all();

            $thumbs = ChallengeThumb::where('category_id',$inputs['category_id'])->orderBy('order','ASC')->get(['id','image']);

            return $this->sendSuccessResponse("Thumb list page.", 200, $thumbs);
        } catch (\Exception $e) {
            Log::info($e);
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function participate(Request $request)
    {
        $user = Auth::user();
        try {
            $inputs = $request->all();

            $is_exist = ChallengeParticipatedUser::where('challenge_id',$inputs['challenge_id'])->where('user_id',$user->id)->first();
            if (!empty($is_exist)){
                return $this->sendFailedResponse("You are already participated.", 401);
            }
            ChallengeParticipatedUser::firstOrCreate([
                'challenge_id' => $inputs['challenge_id'],
                'user_id' => $user->id,
            ]);

            return $this->sendSuccessResponse("Participated in challenge.", 200);
        } catch (\Exception $e) {
            Log::info($e);
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function uploadImages(Request $request)
    {
        $user = Auth::user();
        DB::beginTransaction();
        try {
            $inputs = $request->all();

            if (!empty($inputs['images'])) {
                $ChallengeFolder = config('constant.challenge_verify');
                if (!Storage::exists($ChallengeFolder)) {
                    Storage::makeDirectory($ChallengeFolder);
                }
                foreach ($inputs['images'] as $image) {
                    if (is_file($image)) {
                        $mainImage = Storage::disk('s3')->putFile($ChallengeFolder, $image, 'public');
                        $fileName = basename($mainImage);
                        $image_url = $ChallengeFolder . '/' . $fileName;

                        $ChallengeVerify = ChallengeVerify::where('challenge_id',$inputs['challenge_id'])->where('user_id',$user->id)->where('date',$inputs['date'])->first();
                        if (empty($ChallengeVerify)) {
                            $ChallengeVerify =  ChallengeVerify::firstOrCreate([
                                'challenge_id' => $inputs['challenge_id'],
                                'user_id' => $user->id,
                                'date' => $inputs['date'],
                            ]);
                        }
                        $challenge_verify_id = $ChallengeVerify->id;
                        ChallengeVerifyImage::create([
                            'challenge_verify_id' => $challenge_verify_id,
                            'image' => $image_url,
                        ]);
                    }
                }

                $ChallengeVerify = ChallengeVerify::with('verifiedimages')
                    ->leftjoin('users_detail', function ($join) {
                        $join->on('challenge_verify.user_id', '=', 'users_detail.user_id')
                            ->whereNull('users_detail.deleted_at');
                    })
                    ->leftjoin('challenges', function ($join) {
                        $join->on('challenge_verify.challenge_id', '=', 'challenges.id');
                    })
                    ->where('challenge_verify.id',$challenge_verify_id)
                    ->select(
                        'users_detail.name',
                        'challenges.title',
                        'challenge_verify.*'
                    )
                    ->first()->toArray();
                $MailData = [
                    'subject' => "새 인증 사진 ".$ChallengeVerify['name']." ".$ChallengeVerify['title'],
                    'images' => $ChallengeVerify['verifiedimages'],
                ];
                $configSettings = ChallengeConfig::where('key', ChallengeConfig::VERIFICATION_EMAIL)->first();
                Mail::to($configSettings->value)->send(new ChallengeVerificationMail($MailData));
            }

            DB::commit();
            return $this->sendSuccessResponse("Images uploaded.", 200);
        } catch (\Exception $e) {
            Log::info($e);
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function verifiedImages(Request $request){
        $user = Auth::user();
        try {
            $inputs = $request->all();

            $verified = ChallengeVerify::join('users_detail','users_detail.user_id','challenge_verify.user_id')
                        ->where('challenge_verify.challenge_id',$inputs['challenge_id'])
                        ->where('challenge_verify.date',$inputs['date'])
                        ->select('challenge_verify.id','users_detail.name','challenge_verify.user_id')
                        ->with('verifiedimages')
                        ->get();

            $timezone = isset($inputs['timezone']) ? $inputs['timezone'] : "Asia/Seoul";
            $verified->map(function($item) use ($timezone){
                $date = ChallengeVerifyImage::where('challenge_verify_id',$item->id)->orderBy('created_at','DESC')->pluck('created_at')->first();
                $item->date = $this->formatDateTimeCountryWise($date,$timezone,'Y m d H:i');
                $item->avatar = UserDetail::where('user_id',$item->user_id)->pluck('avatar')->first();
                return $item;
            });
            $data['verify_data'] = $verified;

            $check_rejected = ChallengeVerify::where('challenge_id',$inputs['challenge_id'])->where('user_id',$user->id)->where('date',$inputs['date'])->pluck('is_rejected')->first();
            $check_verified = ChallengeVerify::where('challenge_id',$inputs['challenge_id'])->where('user_id',$user->id)->where('date',$inputs['date'])->pluck('is_verified')->first();
            if ($check_rejected==1){
                $data['button'] = "rejected";
            }
            elseif ($check_verified==1){
                $data['button'] = "verified";
            }
            else {
                $check_participated = ChallengeParticipatedUser::where('challenge_id', $inputs['challenge_id'])->where('user_id', $user->id)->first();
                $data['button'] = !empty($check_participated) ? "upload" : "";
            }

            return $this->sendSuccessResponse("Verified data.", 200, $data);
        } catch (\Exception $e) {
            Log::info($e);
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function viewUserProfile(Request $request)
    {
        $user = Auth::user();
        try {
            $inputs = $request->all();

            $userDetail = UserDetail::where('user_id',$inputs['user_id'])->select('name','avatar')->first();

            $verifiedData = ChallengeVerify::where('user_id',$inputs['user_id'])
                            ->select('id')
                            ->get();
            $verifiedData->map(function($item){
                $latestPost = ChallengeVerifyImage::where('challenge_verify_id',$item->id)->orderBy('created_at','DESC')->first();
                $item->post = $latestPost->image_url;
                $item->uploaded_at = $latestPost->created_at;
                return $item;
            });
            $verifiedData = $verifiedData->sortByDesc('uploaded_at')->values();

            $followerCount = ChallengeUserFollowing::where('follows_to',$inputs['user_id'])->count();
            $followingCount = ChallengeUserFollowing::where('followed_by',$inputs['user_id'])->count();
            $followData = ChallengeUserFollowing::where('followed_by',$user->id)->where('follows_to',$inputs['user_id'])->first();

            $data['user_name'] = $userDetail->name;
            $data['avatar'] = $userDetail->avatar;
            $data['update'] = count($verifiedData);
            $data['follower'] = $followerCount;
            $data['following'] = $followingCount;
            $data['verified_data'] = $verifiedData;
            $data['is_follow'] = !empty($followData) ? 1 : 0;
            return $this->sendSuccessResponse("User profile page.", 200, $data);
        } catch (\Exception $e) {
            Log::info($e);
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function followUnfollow(Request $request)
    {
        $user = Auth::user();
        try {
            $inputs = $request->all();

            $followData = ChallengeUserFollowing::where('followed_by',$user->id)->where('follows_to',$inputs['user_id'])->first();
            if (empty($followData)){
                ChallengeUserFollowing::firstOrCreate([
                    'followed_by' => $user->id,
                    'follows_to' => $inputs['user_id'],
                ]);
                ChallengeNotice::create([
                    'user_id' => $user->id,
                    'to_user_id' => $inputs['user_id'],
                    'notify_type' => 'get_follower'
                ]);
            }
            else {
                ChallengeUserFollowing::where('followed_by',$user->id)->where('follows_to',$inputs['user_id'])->delete();
            }

            return $this->sendSuccessResponse("Follow/unfollow updated.", 200);
        } catch (\Exception $e) {
            Log::info($e);
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function followerList(Request $request)
    {
        $user = Auth::user();
        try {
            $inputs = $request->all();

            $followerData = ChallengeUserFollowing::where('follows_to',$inputs['user_id'])->select('followed_by')->get();
            $followerData->map(function($item) use ($user){
                $userDetail = UserDetail::where('user_id',$item->followed_by)->select('name','avatar')->first();
                $item->avatar = $userDetail->avatar;
                $item->user_name = $userDetail->name;

                $is_follow = ChallengeUserFollowing::where('followed_by',$user->id)->where('follows_to',$item->followed_by)->first();
                $item->is_follow = !empty($is_follow) ? 1 : 0;

                return $item;
            });

            return $this->sendSuccessResponse("Follower list.", 200, $followerData);
        } catch (\Exception $e) {
            Log::info($e);
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function followingList(Request $request)
    {
        $user = Auth::user();
        try {
            $inputs = $request->all();

            $followingData = ChallengeUserFollowing::where('followed_by',$inputs['user_id'])->select('follows_to')->get();
            $followingData->map(function($item) use ($user){
                $userDetail = UserDetail::where('user_id',$item->follows_to)->select('name','avatar')->first();
                $item->avatar = $userDetail->avatar;
                $item->user_name = $userDetail->name;

                $is_follow = ChallengeUserFollowing::where('followed_by',$user->id)->where('follows_to',$item->follows_to)->first();
                $item->is_follow = !empty($is_follow) ? 1 : 0;

                return $item;
            });

            return $this->sendSuccessResponse("Following list.", 200, $followingData);
        } catch (\Exception $e) {
            Log::info($e);
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function logoutPage(Request $request)
    {
        $user = Auth::user();
        try {
            $inputs = $request->all();
            $points = ChallengeUserPoint::where('user_id',$user->id)->sum('bp');
            $links = LinkChallengeSetting::get(['title','link']);

            $data['avatar'] = $user->avatar;
            $data['user_name'] = $user->name;
            $data['points'] = $points;
            $data['links'] = $links;

            $kakaoTalk = ChallengeKakaoTalkLink::where('id',1)->first();
            $data['kakao_talk'] = $kakaoTalk->link;
            return $this->sendSuccessResponse("User data.", 200, $data);
        } catch (\Exception $e) {
            Log::info($e);
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function postDetail(Request $request)
    {
        $user = Auth::user();
        try {
            $inputs = $request->all();

            $userDetail = UserDetail::where('user_id',$inputs['user_id'])->select('name','avatar')->first();
            $challenge = ChallengeVerify::join('challenges', function ($join) {
                        $join->on('challenge_verify.challenge_id', '=', 'challenges.id');
                    })
                    ->where('challenge_verify.id',$inputs['post_id'])
                    ->select('challenges.title')
                    ->first();

            $verifiedData = ChallengeVerifyImage::where('challenge_verify_id',$inputs['post_id'])->orderBy('created_at','DESC')->get()->toArray();

            $data['user_name'] = $userDetail->name;
            $data['avatar'] = $userDetail->avatar;
            $data['time'] = (count($verifiedData) > 0) ? timeAgo($verifiedData[0]['created_at'],4,$inputs['timezone']) : "";
            $data['posts'] = $verifiedData;
            $data['challenge'] = $challenge->title;
            return $this->sendSuccessResponse("User profile page.", 200, $data);
        } catch (\Exception $e) {
            Log::info($e);
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function removeParticipate(Request $request)
    {
        $user = Auth::user();
        try {
            $inputs = $request->all();
            ChallengeParticipatedUser::where('challenge_id',$inputs['challenge_id'])
                ->where('user_id',$user->id)
                ->delete();

            return $this->sendSuccessResponse("Challenge removed.", 200);
        } catch (\Exception $e) {
            Log::info($e);
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function allParticipated(Request $request)
    {
        $user = Auth::user();
        try {
            $inputs = $request->all();

            if($user) {
                $challenges = ChallengeParticipatedUser::join('challenges', 'challenges.id', 'challenge_participated_users.challenge_id')
                    ->where('challenge_participated_users.user_id',$user->id)
                    ->select(
                        'challenges.id',
                        'challenges.title',
                        'challenges.challenge_thumb_id',
                        'challenges.verify_time',
                        'challenges.end_date',
                        'challenges.date',
                        'challenges.description'
                    )
                    ->get();

                $challenges->map(function($item) use ($inputs){
                    $thumb = "";
                    if (!empty($item->challenge_thumb_id)){
                        $thumb = ChallengeThumb::where('id',$item->challenge_thumb_id)->pluck('image')->first();
                        $thumb = !empty($thumb) ? Storage::disk('s3')->url($thumb) : "";
                    }
                    $item->thumb_image = $thumb;

                    $timeArr = explode(":",$item->verify_time);
                    $time = isset($timeArr[2]) ? $timeArr[0].":".$timeArr[1] : $item->verify_time; //remove seconds
                    $dbTime = \Carbon\Carbon::createFromFormat('H:i', $time, "UTC");
                    $userTime = $dbTime->setTimezone($inputs['timezone']);
                    $item->verify_time = $userTime->format('g:ia');

                    if ($item->end_date!=null) {
                        $item->end_date = Carbon::parse($item->end_date)->format('Y/m/d');
                    }
                    elseif ($item->date!=null) {
                        $item->end_date = Carbon::parse($item->date)->format('Y/m/d');
                    }

                    $challenge_days = ChallengeDay::where('challenge_id',$item->id)->orderBy('created_at','ASC')->pluck('day')->toArray();
                    foreach ($challenge_days as &$challenge_day){
                        if($challenge_day=="mo"){
                            $day = "Mon";
                        }
                        elseif($challenge_day=="tu"){
                            $day = "Tue";
                        }
                        elseif($challenge_day=="we"){
                            $day = "Wed";
                        }
                        elseif($challenge_day=="th"){
                            $day = "Thu";
                        }
                        elseif($challenge_day=="fr"){
                            $day = "Fri";
                        }
                        elseif($challenge_day=="sa"){
                            $day = "Sat";
                        }
                        elseif($challenge_day=="su"){
                            $day = "Sun";
                        }

                        $challenge_day = $day;
                    }
                    $item->challenge_days = $challenge_days;

                    return $item;
                });

                $data['user_name'] = $user->name;
                $data['avatar'] = $user->avatar;
                $data['challenges'] = $challenges;
                return $this->sendSuccessResponse("Challenge list.", 200, $data);
            }
            else{
                return $this->sendSuccessResponse(Lang::get('messages.user.token_expired'), 401);
            }
        } catch (\Exception $e) {
            Log::info('Exception in the list challenge');
            Log::info($e);
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function allExpired(Request $request)
    {
        $user = Auth::user();
        try {
            $inputs = $request->all();

            if($user) {
                $challenges = ChallengeParticipatedUser::join('challenges', 'challenges.id', 'challenge_participated_users.challenge_id')
                    ->where('challenge_participated_users.user_id',$user->id)
                    ->select(
                        'challenges.id',
                        'challenges.title',
                        'challenges.challenge_thumb_id',
                        'challenges.verify_time',
                        'challenges.end_date',
                        'challenges.date',
                        'challenges.description'
                    )
                    ->get();

                $challenges->map(function($item) use ($inputs){
                    $thumb = "";
                    if (!empty($item->challenge_thumb_id)){
                        $thumb = ChallengeThumb::where('id',$item->challenge_thumb_id)->pluck('image')->first();
                        $thumb = !empty($thumb) ? Storage::disk('s3')->url($thumb) : "";
                    }
                    $item->thumb_image = $thumb;

                    $timeArr = explode(":",$item->verify_time);
                    $time = isset($timeArr[2]) ? $timeArr[0].":".$timeArr[1] : $item->verify_time; //remove seconds
                    $dbTime = \Carbon\Carbon::createFromFormat('H:i', $time, "UTC");
                    $userTime = $dbTime->setTimezone($inputs['timezone']);
                    $item->verify_time = $userTime->format('g:ia');

                    if ($item->end_date!=null) {
                        $item->expired_date = $item->end_date;
                        $item->end_date = Carbon::parse($item->end_date)->format('Y/m/d');
                    }
                    elseif ($item->date!=null) {
                        $item->expired_date = $item->date;
                        $item->end_date = Carbon::parse($item->date)->format('Y/m/d');
                    }

                    $challenge_days = ChallengeDay::where('challenge_id',$item->id)->orderBy('created_at','ASC')->pluck('day')->toArray();
                    foreach ($challenge_days as &$challenge_day){
                        if($challenge_day=="mo"){
                            $day = "Mon";
                        }
                        elseif($challenge_day=="tu"){
                            $day = "Tue";
                        }
                        elseif($challenge_day=="we"){
                            $day = "Wed";
                        }
                        elseif($challenge_day=="th"){
                            $day = "Thu";
                        }
                        elseif($challenge_day=="fr"){
                            $day = "Fri";
                        }
                        elseif($challenge_day=="sa"){
                            $day = "Sat";
                        }
                        elseif($challenge_day=="su"){
                            $day = "Sun";
                        }

                        $challenge_day = $day;
                    }
                    $item->challenge_days = $challenge_days;

                    return $item;
                });

                $challenges = $challenges->where('expired_date','<',Carbon::today())->values();

                $data['user_name'] = $user->name;
                $data['avatar'] = $user->avatar;
                $data['challenges'] = $challenges;
                return $this->sendSuccessResponse("Challenge list.", 200, $data);
            }
            else{
                return $this->sendSuccessResponse(Lang::get('messages.user.token_expired'), 401);
            }
        } catch (\Exception $e) {
            Log::info('Exception in the list challenge');
            Log::info($e);
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function achievementPage(Request $request)
    {
        $user = Auth::user();
        try {
            $inputs = $request->all();

            $challenges = ChallengeParticipatedUser::join('challenges', 'challenges.id', 'challenge_participated_users.challenge_id')
                ->where('challenge_participated_users.user_id',$user->id)
                ->where('challenges.is_period_challenge',0)
                ->count();

            $period_challenges = ChallengeParticipatedUser::join('challenges', 'challenges.id', 'challenge_participated_users.challenge_id')
                ->where('challenge_participated_users.user_id',$user->id)
                ->whereNotNull('challenges.start_date')
                ->whereNotNull('challenges.end_date')
                ->get(['challenges.id','challenges.start_date','challenges.end_date'])
                ->map(function ($item){
                    $challenge_days = ChallengeDay::where('challenge_id',$item['id'])->pluck('day')->toArray();

                    $startDate = Carbon::createFromFormat('Y-m-d',$item['start_date']);
                    $endDate = Carbon::createFromFormat('Y-m-d', $item['end_date']);
                    $dates = collect(Carbon::parse($startDate)->range($endDate));
                    $cnt = 0;
                    $dayNames = $dates->map(function ($date) use ($challenge_days,&$cnt){
                        $dayName = $date->formatLocalized('%a');
                        $shortenedDayName = strtolower(substr($dayName, 0, 2));
                        if (in_array($shortenedDayName,$challenge_days)){
                            $cnt++;
                        }
                        return $shortenedDayName;
                    });

                    $item['count'] = $cnt;
                    return $item;
                })
                ->sum('count');
            $total_schedule = $challenges + $period_challenges;

            $verified = ChallengeVerify::where('user_id',$user->id)->where('is_verified',1)->count();

            if ($total_schedule!=0) {
                $rate = ((int)$verified / (int)$total_schedule) * 100;
                $rate = max(0, min(100, $rate));
            }
            $data['achievement'] = isset($rate) ? round($rate,2)."%" : "0%";
            return $this->sendSuccessResponse("Achievement page.", 200, $data);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function inviteFollowerList(Request $request)
    {
        $user = Auth::user();
        try {
            $inputs = $request->all();

            $followerData = ChallengeUserFollowing::where('follows_to',$user->id)->select('followed_by')->get();
            $followerData->map(function($item) use ($user,$inputs){
                $userDetail = UserDetail::where('user_id',$item->followed_by)->select('name','avatar')->first();
                $item->avatar = $userDetail->avatar;
                $item->user_name = $userDetail->name;

                $is_invited = ChallengeInvitedUser::where('challenge_id',$inputs['challenge_id'])->where('user_id',$item->followed_by)->where('invite_by',$user->id)->first();
                $item->is_invited = !empty($is_invited) ? 1 : 0;
                return $item;
            });

            return $this->sendSuccessResponse("Follower list.", 200, $followerData);
        } catch (\Exception $e) {
            Log::info($e);
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function inviteUser(Request $request)
    {
        $user = Auth::user();
        try {
            $inputs = $request->all();

            $challenge = Challenge::where('id',$inputs['challenge_id'])->select('id','title')->first();
            if (!empty($challenge)) {
                ChallengeInvitedUser::firstOrCreate([
                    'challenge_id' => $inputs['challenge_id'],
                    'user_id' => $inputs['user_id'],
                    'invite_by' => $user->id,
                ]);
                $devices = UserDevices::where('user_id', $inputs['user_id'])->pluck('device_token')->toArray();
                if (count($devices) > 0) {
                    $notification_msg = $user->name . " 이 새로운 도전에 초대했습니다. (" . $challenge->title . ")";
                    $result = $this->sentPushNotification($devices, "Invited for challenge", $notification_msg, [], "invited_challenge");
                }

                ChallengeNotice::create([
                    'user_id' => $user->id,
                    'to_user_id' => $inputs['user_id'],
                    'challenge_id' => $inputs['challenge_id'],
                    'notify_type' => 'get_invite'
                ]);
            }

            return $this->sendSuccessResponse("Invited user successfully.", 200);
        } catch (\Exception $e) {
            Log::info($e);
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function invitedList(Request $request)
    {
        $user = Auth::user();
        try {
            $inputs = $request->all();

            $challenges = ChallengeInvitedUser::join('challenges', 'challenges.id', 'challenge_invited_users.challenge_id')
                ->where('challenge_invited_users.user_id',$user->id)
                ->select(
                    'challenges.id',
                    'challenges.title',
                    'challenges.challenge_thumb_id',
                    'challenges.verify_time',
                    'challenges.end_date',
                    'challenges.date'
                )
                ->get();

            $challenges->map(function($item) use ($inputs){
                $thumb = "";
                if (!empty($item->challenge_thumb_id)){
                    $thumb = ChallengeThumb::where('id',$item->challenge_thumb_id)->pluck('image')->first();
                    $thumb = !empty($thumb) ? Storage::disk('s3')->url($thumb) : "";
                }
                $item->thumb_image = $thumb;

                $timeArr = explode(":",$item->verify_time);
                $time = isset($timeArr[2]) ? $timeArr[0].":".$timeArr[1] : $item->verify_time; //remove seconds
                $dbTime = \Carbon\Carbon::createFromFormat('H:i', $time, "UTC");
                $userTime = $dbTime->setTimezone($inputs['timezone']);
                $item->verify_time = $userTime->format('g:ia');

                if ($item->end_date!=null) {
                    $item->end_date = Carbon::parse($item->end_date)->format('M d');
                }
                elseif ($item->date!=null) {
                    $item->end_date = Carbon::parse($item->date)->format('M d');
                }
                return $item;
            });

            return $this->sendSuccessResponse("Invited list.", 200, $challenges);
        } catch (\Exception $e) {
            Log::info($e);
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function menuList(Request $request)
    {
        $user = Auth::user();
        try {
            $inputs = $request->all();

            $menus = ChallengeMenu::get(['id','eng_menu','kr_menu']);

            return $this->sendSuccessResponse("Menu list.", 200,$menus);
        } catch (\Exception $e) {
            Log::info($e);
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function noticeList(Request $request)
    {
        $user = Auth::user();
        try {
            $inputs = $request->all();

            $notices = ChallengeNotice::where('to_user_id',288)->get(['user_id','to_user_id','challenge_id','notify_type','created_at']);
            $language = "";
            foreach ($notices as &$notice){
                $userDetail = UserDetail::where('user_id',$notice->user_id)->select('id','name')->first();
                if ($notice->notify_type=="get_follower") {
                    if ($inputs['lang'] == "kr") {
                        $language = 1;
                        $notice->message = $userDetail->name.' 이 당신을 팔로잉 합니다';
                    } else {
                        $language = 4;
                        $notice->message = $userDetail->name.' is following you';
                    }
                }

                if ($notice->notify_type=="get_invite") {
                    $challenge = Challenge::where('id',$notice->challenge_id)->pluck('title')->first();
                    if ($inputs['lang'] == "kr") {
                        $language = 1;
                        $notice->message = $userDetail->name.' 님이 챌린지에 초대했습니다. '.$challenge;
                    } else {
                        $language = 4;
                        $notice->message = $userDetail->name.' invited you to the challenge. '.$challenge;
                    }
                }

                $notice->time_ago = timeAgo($notice->created_at,$language,$inputs['timezone']);
            }

            return $this->sendSuccessResponse("Notice list.", 200,$notices);
        } catch (\Exception $e) {
            Log::info($e);
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function homePage(Request $request)
    {
        $user = Auth::user();
        try {
            $inputs = $request->all();

            $data['user_name'] = $user->name;
            $data['avatar'] = $user->avatar;
            $data['participated_count'] = ChallengeParticipatedUser::where('user_id',$user->id)->count();

            return $this->sendSuccessResponse("Home page.",200,$data);
        } catch (\Exception $e) {
            Log::info($e);
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function adminNoticeList(Request $request)
    {
        $user = Auth::user();
        try {
            $inputs = $request->all();

            $adminNotices = ChallengeAdminNotice::select(['id','title','notice','created_at'])
                ->paginate(config('constant.pagination_count'),"*","admin_notice_page");
            $adminNotices->getCollection()->transform(function($item) use ($inputs){
                $date = $this->formatDateTimeCountryWise($item->created_at,$inputs['timezone']);
                $item->display_created_at = $date;

                return $item;
            });

            $admin = ChallengeAdmin::where('id',1)->first();
            $date['image'] = $admin->image;
            $date['bio'] = $admin->bio;
            $date['notices'] = $adminNotices;

            return $this->sendSuccessResponse("Admin notice page.",200,$date);
        } catch (\Exception $e) {
            Log::info($e);
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function sendInvite(Request $request)
    {
        $user = Auth::user();
        try {
            $inputs = $request->all();

            $data['invite_text'] = ChallengeInviteText::where('id',1)->pluck('text')->first();
            $data['referral_code'] = $user->recommended_code;

            ChallengeAppInvited::firstOrCreate([
                'invite_by' => $user->id,
            ]);

            return $this->sendSuccessResponse("Invite data.", 200, $data);
        } catch (\Exception $e) {
            Log::info($e);
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

}
