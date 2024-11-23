<?php

namespace App\Http\Controllers\Challenge;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Challenge;
use App\Models\ChallengeDay;
use App\Models\ChallengeParticipatedUser;
use App\Models\ChallengeVerify;
use App\Models\EntityTypes;
use App\Models\LinkedSocialProfile;
use App\Models\Status;
use App\Models\User;
use App\Models\UserDetail;
use App\Models\UserEntityRelation;
use App\Util\Firebase;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    protected $firebase;

    public function __construct()
    {
        $this->firebase = new Firebase();
//        $this->middleware('permission:user-list', ['only' => ['index']]);
    }

    public function index(Request $request)
    {
        $title = 'All User';

        $totalUsers = UserEntityRelation::join('users', 'users.id', 'user_entity_relation.user_id')->whereIN('user_entity_relation.entity_type_id', [EntityTypes::NORMALUSER, EntityTypes::HOSPITAL, EntityTypes::SHOP])->whereIn('users.status_id', [Status::ACTIVE, Status::INACTIVE])->whereNotNull('users.email')->distinct('user_id')->count('user_id');
        $totalShops = UserEntityRelation::where('entity_type_id', EntityTypes::SHOP)->distinct('user_entity_relation.user_id')->count('user_entity_relation.user_id');
        $totalHospitals = UserEntityRelation::where('entity_type_id', EntityTypes::HOSPITAL)->distinct('user_entity_relation.user_id')->count('user_entity_relation.user_id');

        $totalNormalUser = $totalUsers - ($totalShops + $totalHospitals);

        $category = Category::where('category.status_id', Status::ACTIVE)
            ->where('category.category_type_id', EntityTypes::SHOP)
            ->where('category.parent_id', 0)
            ->get();

        $unreadReferralCount = DB::table('users_detail')->join('users', 'users.id', 'users_detail.user_id')->whereNull('users.deleted_at')->whereNotNull('users_detail.recommended_by')->where('users_detail.is_referral_read', 1)->count();
        return view('challenge.users.index', compact('title', 'category', 'totalUsers', 'totalShops', 'totalHospitals', 'totalNormalUser', 'unreadReferralCount'));
    }

    public function getJsonAllData(Request $request)
    {
        $columns = array(
            0 => 'users_detail.name',
            1 => 'users.email',
            2 => 'users_detail.mobile',
            3 => 'users.created_at',
            4 => 'users.last_login',
        );

        $filter = $request->input('filter');
        $categoryFilterID = $request->input('category');
        $limit = $request->input('length');
        $start = $request->input('start');
        $search = $request->input('search.value');
        $draw = $request->input('draw');
        $order = $columns[$request->input('order.0.column')];
        $dir = $request->input('order.0.dir');

        $adminTimezone = $this->getAdminUserTimezone();
        $viewButton = '';
        $toBeShopButton = '';
        $loginUser = Auth::user();
        try {
            $data = [];

            $userQuery = DB::table('users')->leftJoin('user_entity_relation', 'user_entity_relation.user_id', 'users.id')
                ->leftJoin('users_detail', 'users_detail.user_id', 'users.id')
                ->leftjoin('shops', function ($query) use ($filter) {
                    $query->on('users.id', '=', 'shops.user_id');
                })
                ->leftjoin('user_cards', function ($query) use ($filter) {
                    $query->on('users.id', '=', 'user_cards.user_id')->where('user_cards.is_applied', 1);
                })
                ->leftjoin('hospitals', 'user_entity_relation.entity_id', 'hospitals.id')
                ->whereIN('user_entity_relation.entity_type_id', [EntityTypes::NORMALUSER, EntityTypes::HOSPITAL, EntityTypes::SHOP])
                ->whereNotNull('users.email')
                ->whereNull('users.deleted_at')
                ->whereIn('users.status_id', [Status::ACTIVE, Status::INACTIVE])
                ->where('users.app_type','challenge')
                ->select(
                    'users.id',
                    'users_detail.name',
                    'users_detail.level',
                    'users_detail.mobile',
                    'users_detail.recommended_by',
                    'users_detail.recommended_code',
                    'users.inquiry_phone',
                    'users.connect_instagram',
                    'users.email',
                    'users.is_admin_access',
                    'users.is_support_user',
                    'users.created_at as date',
                    'users.last_login as last_access',
                    'users.app_type',
                    DB::raw('IFNULL(user_cards.love_count, 0) as love_count'),
                    DB::raw('(SELECT group_concat(entity_type_id) from user_entity_relation WHERE user_id = users.id) as entity_types')
                )
                ->selectSub(function ($q) {
                    $q->select(DB::raw('count(id) as count'))->from('linked_social_profiles')->where('social_type', LinkedSocialProfile::Instagram)->whereRaw("`user_id` = `users`.`id`");
                }, 'linked_account_count')
                ->selectSub(function ($q) {
                    $q->select('ref.name as referred_by_name')->from('users_detail as ref')->join('users as ru', 'ru.id', 'ref.user_id')->whereNull('ru.deleted_at')->whereIn('ru.status_id', [Status::ACTIVE, Status::INACTIVE])->whereRaw("`ref`.`user_id` = `users_detail`.`recommended_by`");
                }, 'referred_by_name')
                ->groupBy('users.id');

            if (!empty($search)) {
                $userQuery = $userQuery->where(function ($q) use ($search) {
                    $q->where('users_detail.name', 'LIKE', "%{$search}%")
                        ->orWhere('users.email', 'LIKE', "%{$search}%")
                        ->orWhere('users.created_at', 'LIKE', "%{$search}%")
                        ->orWhere('shops.shop_name', 'LIKE', "%{$search}%")
                        ->orWhere('shops.main_name', 'LIKE', "%{$search}%")
                        ->orWhere('hospitals.main_name', 'LIKE', "%{$search}%")
                        ->orWhere('users_detail.mobile', 'LIKE', "%{$search}%")
                        ->orWhere('shops.another_mobile', 'LIKE', "%{$search}%");
                });
            }

            $userQuery = $userQuery->selectSub(function ($q) {
                $q->select(DB::raw('count(users_detail.id) as count'))->from('users_detail')->whereRaw("`users_detail`.`recommended_by` = `users`.`id`");
            }, 'referral_count');

            // Count Number
            $userQuery = $userQuery->selectSub(function ($q) {
                $q->select(DB::raw('count(users_detail.id) as count'))->from('users_detail')->where('users_detail.is_referral_read', 1)->whereRaw("`users_detail`.`recommended_by` = `users`.`id`");
            }, 'new_referral_count');

            $totalData = count($userQuery->get());
            $totalFiltered = $totalData;

            $userData = $userQuery->offset($start)
                ->limit($limit)
                ->orderBy($order, $dir)
                ->get();

            $count = 0;
            foreach ($userData as $user) {
                $style = ($user->is_admin_access == 1) ? "color:deeppink" : '';
                $data[$count]['name'] = "<div class='d-flex align-items-center'>
<p style='$style;margin: 0'>$user->name</p>
</div>";

                $data[$count]['email'] = $user->email;

                if (Auth::user()->hasRole('Sub Admin')) {
                    $data[$count]['phone'] = "";
                }
                else {
                    $data[$count]['phone'] = '<span class="copy_clipboard">' . $user->mobile . '</span>';
                }

                $data[$count]['signup'] = $this->formatDateTimeCountryWise($user->date, $adminTimezone);
                $data[$count]['last_access'] = $this->formatDateTimeCountryWise($user->last_access, $adminTimezone);

                $cntChallenges = ChallengeParticipatedUser::join('challenges', 'challenges.id', 'challenge_participated_users.challenge_id')
                            ->where('challenge_participated_users.user_id',$user->id)
                            ->count();
                $calendarBtn = '<a href="'.route('challenge.users.calender.index',['id' => $user->id]).'" role="button" class="btn btn-primary btn-sm mx-1" data-toggle="tooltip" data-original-title="">'.__('general.see_calendar').' ('.$cntChallenges.')</a>';
                $challengeBtn = '<a href="javascript:void(0)" role="button" onclick="showChallengeList('.$user->id.')" class="btn btn-primary btn-sm mx-1" data-toggle="tooltip" data-original-title="">'.__('general.challenge_list').'</a>';
                $editBtn = '<a href="javascript:void(0)" role="button" onclick="editUser('.$user->id.')" class="btn btn-primary btn-sm mx-1" data-toggle="tooltip" data-original-title="Edit">'.__('datatable.edit').'</a>';
                $seeUserBtn = '<a href="javascript:void(0)" role="button" onclick="" class="btn btn-primary btn-sm mx-1" data-toggle="tooltip" data-original-title="">'.__('datatable.see_user').'</a>';
                $data[$count]['action'] = $calendarBtn.$challengeBtn.$editBtn.$seeUserBtn;

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
                $data[$count]['achievement'] = isset($rate) ? round($rate,2)."%" : "0%";
                $count++;
            }

            $jsonData = array(
                "draw" => intval($draw),
                "recordsTotal" => intval($totalData),
                "recordsFiltered" => intval($totalFiltered),
                "data" => $data
            );
            return response()->json($jsonData);
        } catch (Exception $ex) {
            Log::info('Exception all user list');
            Log::info($ex);
            $jsonData = array(
                "draw" => intval($draw),
                "recordsTotal" => 0,
                "recordsFiltered" => 0,
                "data" => []
            );
            return response()->json($jsonData);
        }
    }

    public function saveUser(Request $request){
        try{
            DB::beginTransaction();
            $inputs = $request->all();
            $validator = Validator::make($inputs, [
                'user_name' => 'required',
                'email' => 'required|string|max:255',
                'phone' => 'required|numeric',
                'password' => 'required|string|min:6|confirmed',
                'password_confirmation' => 'required|string|min:6'
            ], [
                'user_name.required' => 'The User Name is required.',
                'email.required' => 'The Email is required.',
                'email.unique' => 'This Email is already been taken.',
                'phone.required' => 'The Phone Number is required.',
                'password.required' => 'The password is required.',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors(), 'success' => false]);
            }

            $is_exist_user = User::where('email', $inputs['email'])->where('app_type','challenge')->count();
            if ($is_exist_user > 0){
                return response()->json(array('success' => false,'message' => 'User already exist!!'));
            }

            $user = User::create([
                "email" => $inputs['email'],
                'username' => $inputs['user_name'],
                "password" => Hash::make($inputs['password']),
                'status_id' => Status::ACTIVE,
                'app_type' => "challenge",
            ]);

            UserEntityRelation::create(['user_id' => $user->id, "entity_type_id" => EntityTypes::NORMALUSER, 'entity_id' => $user->id]);
            $random_code = mt_rand(1000000, 9999999);
            $recommended_user = UserDetail::where('recommended_code', $inputs['referral_code'])->first();
            if(isset($inputs['referral_code']) && empty($recommended_user)) {
                return response()->json(array('success' => false,'message' => 'Invalid referral code!!'));
            }

            $member = UserDetail::create([
                'user_id' => $user->id,
                'country_id' => NULL,
                'name' => trim($inputs['user_name']),
                'email' => $inputs['email'],
                'mobile' => $inputs['phone'],
                'gender' => $inputs['gender'] ?? NULL,
                'recommended_code' => $random_code,
                'recommended_by' => isset($inputs['referral_code']) ? $recommended_user->user_id : null,
                'points_updated_on' => Carbon::now(),
                'points' => UserDetail::POINTS_40,
            ]);

            DB::commit();
            return response()->json(array('success' => true));
        }catch(\Exception $e){
            Log::info("$e");
            DB::rollBack();
            return response()->json(array('success' => false));
        }
    }

    public function challengeList($id){
        $adminTimezone = $this->getAdminUserTimezone();
        $challenges = ChallengeParticipatedUser::join('challenges', 'challenges.id', 'challenge_participated_users.challenge_id')
                ->where('challenge_participated_users.user_id',$id)
                ->select(
                    'challenges.id',
                    'challenges.title'
                )
                ->get();

        return view('challenge.users.show-challenges-popup', compact('challenges'));
    }

    public function calenderIndex($id)
    {
        $title = "Calender";
        $adminTimezone = $this->getAdminUserTimezone();

        $period_challenges = ChallengeParticipatedUser::join('challenges', 'challenges.id', 'challenge_participated_users.challenge_id')
            ->where('challenge_participated_users.user_id',$id)
            ->whereNotNull('challenges.start_date')
            ->whereNotNull('challenges.end_date')
            ->get(['challenges.id','challenges.title','challenges.start_date','challenges.end_date','challenges.created_at'])
            ->map(function ($item) use($adminTimezone){
                $item['days'] = ChallengeDay::where('challenge_id',$item['id'])->pluck('day')->toArray();
                return $item;
            });

        $challenges = ChallengeParticipatedUser::join('challenges', 'challenges.id', 'challenge_participated_users.challenge_id')
            ->where('challenge_participated_users.user_id',$id)
            ->where('challenges.is_period_challenge',0)
            ->get(['challenges.title','challenges.date','challenges.created_at']);

        $participated = ChallengeParticipatedUser::join('challenges', 'challenges.id', 'challenge_participated_users.challenge_id')
            ->where('challenge_participated_users.user_id',$id)
            ->get(['challenge_participated_users.created_at'])
            ->map(function ($item) use($adminTimezone){
                $participated_at = $this->formatDateTimeCountryWise($item['created_at'],$adminTimezone,'Y-m-d');
                $item['participated_at'] = $participated_at;
                return $item;
            })
            ->unique('participated_at');

        return view('challenge.users.calender-index', compact('title','period_challenges','challenges','participated'));
    }

    public function editData($id){
        $user = User::leftjoin('users_detail','users_detail.user_id','users.id')
                ->where('users.id',$id)
                ->select([
                    'users.id',
                    'users.email',
                    'users_detail.name',
                    'users_detail.mobile',
                    'users_detail.gender'
                ])
                ->first();

        return view('challenge.users.edit-popup',compact('user'));
    }

    public function updateUser(Request $request){
        try{
            DB::beginTransaction();
            $inputs = $request->all();
            $validator = Validator::make($inputs, [
                'edit_user_name' => 'required',
                'edit_email' => 'required|string|max:255',
                'edit_phone' => 'required|numeric',
            ], [
                'edit_user_name.required' => 'The User Name is required.',
                'edit_email.required' => 'The Email is required.',
                'edit_phone.required' => 'The Phone Number is required.',
                'edit_phone.numeric' => 'The Phone Number should be numeric.',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors(), 'success' => false]);
            }

            $is_exist_user = User::where('id','!=',$inputs['user_id'])->where('email', $inputs['edit_email'])->where('app_type','challenge')->count();
            if ($is_exist_user > 0){
                return response()->json(array('success' => false,'message' => 'User already exist!!'));
            }

            $user = User::where('id',$inputs['user_id'])->update([
                "email" => $inputs['edit_email'],
                'username' => $inputs['edit_user_name'],
            ]);

            $member = UserDetail::where('user_id',$inputs['user_id'])->update([
                'name' => trim($inputs['edit_user_name']),
                'mobile' => $inputs['edit_phone'],
                'gender' => $inputs['gender'] ?? NULL,
            ]);

            DB::commit();
            return response()->json(array('success' => true));
        }catch(\Exception $e){
            Log::info("$e");
            DB::rollBack();
            return response()->json(array('success' => false));
        }
    }

}
