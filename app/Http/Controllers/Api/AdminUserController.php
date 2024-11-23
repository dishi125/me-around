<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EntityTypes;
use App\Models\LinkedSocialProfile;
use App\Models\ReportedUser;
use App\Models\ReportGroupMessage;
use App\Models\Status;
use App\Models\UserDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Log;

class AdminUserController extends Controller
{
    public function allUserData(Request $request){
        try {
            $timezone = $request->timezone;

            $userQuery = DB::table('users')->leftJoin('user_entity_relation', 'user_entity_relation.user_id', 'users.id')
                ->leftJoin('users_detail', 'users_detail.user_id', 'users.id')
                ->leftjoin('shops', function ($query) {
                    $query->on('users.id', '=', 'shops.user_id');
                })
                ->leftjoin('user_cards', function ($query) {
                    $query->on('users.id', '=', 'user_cards.user_id')->where('user_cards.is_applied', 1);
                })
                ->leftjoin('hospitals', 'user_entity_relation.entity_id', 'hospitals.id')
                ->whereIN('user_entity_relation.entity_type_id', [EntityTypes::NORMALUSER, EntityTypes::HOSPITAL, EntityTypes::SHOP])
                ->whereNotNull('users.email')
                ->whereNull('users.deleted_at')
                ->whereIn('users.status_id', [Status::ACTIVE, Status::INACTIVE])
                ->select(
                    'users.id',
                    'users_detail.name',
                    'users_detail.level',
                    'users_detail.mobile',
//                    'users_detail.recommended_by',
                    'users.inquiry_phone',
                    'users.connect_instagram',
                    'users.email',
                    'users.is_admin_access',
                    'users.is_support_user',
                    'users.created_at as signup_date',
                    'users.last_login as last_access',
                    DB::raw('IFNULL(user_cards.love_count, 0) as love_count'),
                    DB::raw('(SELECT group_concat(entity_type_id) from user_entity_relation WHERE user_id = users.id) as entity_types')
                )
                /*->selectSub(function ($q) {
                    $q->select(DB::raw('count(id) as count'))->from('linked_social_profiles')->where('social_type', LinkedSocialProfile::Instagram)->whereRaw("`user_id` = `users`.`id`");
                }, 'linked_account_count')
                ->selectSub(function ($q) {
                    $q->select('ref.name as referred_by_name')->from('users_detail as ref')->join('users as ru', 'ru.id', 'ref.user_id')->whereNull('ru.deleted_at')->whereIn('ru.status_id', [Status::ACTIVE, Status::INACTIVE])->whereRaw("`ref`.`user_id` = `users_detail`.`recommended_by`");
                }, 'referred_by_name')*/
                ->groupBy('users.id')
                ->orderBy('signup_date','DESC')
                ->paginate(config('constant.pagination_count'), "*", "all_users");

                $userQuery->map(function ($value) use($timezone){
                    $userTypes = explode(",", $value->entity_types);
                    $otherNumber = [];
                    if (in_array(EntityTypes::SHOP, $userTypes)){
                        $shopsData = DB::table('shops')->whereNull('deleted_at')->where('user_id', $value->id)->whereNotNull('another_mobile')->pluck('another_mobile');
                        $otherNumber = $shopsData->toArray();
                    }
                    $value->another_mobile = $otherNumber;

                    $value->signup_date = $this->formatDateTimeCountryWise($value->signup_date, $timezone);
                    $value->last_access = $this->formatDateTimeCountryWise($value->last_access, $timezone);

                    return $value;
                });
//                dd($userQuery->toArray());

            return $this->sendSuccessResponse(Lang::get('messages.user.user-list'), 200, $userQuery);
        } catch (\Exception $e) {
            Log::info($e);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function allUsers(Request $request){
        try {
            $users = UserDetail::get(['user_id','name']);
            $users->makeHidden(['language_name','level_name','user_points','user_applied_card']);

            return $this->sendSuccessResponse(Lang::get('messages.user.user-list'), 200, $users);
        } catch (\Exception $e) {
            Log::info($e);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function reportedUsers(Request $request){
        try {
            $timezone = $request->timezone;

            $reportUserData = ReportedUser::with(['reporter_user_detail','reported_user_detail'])
                ->orderBy('created_at')
                ->get();
            $reportUserData->map(function ($value) use($timezone){
                $value->reported_at = $this->formatDateTimeCountryWise($value->created_at, $timezone);
                $value->reporter_name = ($value->reporter_user_detail!=null) ? $value->reporter_user_detail->name : "";
                $value->reported_user_name = ($value->reported_user_detail!=null) ? $value->reported_user_detail->name : "";

                return $value;
            });
            $reportUserData->makeHidden(['reporter_user_id','reported_user_id','is_admin_read','created_at','updated_at','reporter_user_detail','reported_user_detail']);

            return $this->sendSuccessResponse(Lang::get('messages.report.reported-user-list'), 200, $reportUserData);
        } catch (\Exception $e) {
            Log::info($e);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function reportedMessages(Request $request){
        try {
            $timezone = $request->timezone;

            $reportedMessageData = ReportGroupMessage::leftjoin('users_detail', function ($join){
                $join->on('users_detail.user_id', '=', 'report_group_messages.reporter_user_id');
            })
                ->leftjoin('group_messages', function ($join){
                    $join->on('group_messages.id', '=', 'report_group_messages.message_id');
                })
                ->select(
                    'report_group_messages.*',
                    'users_detail.name as reporter_name',
                    'group_messages.type',
                    'group_messages.message',
                    'group_messages.from_user'
                )
                ->orderBy('report_group_messages.created_at')
                ->get();
            $reportedMessageData->map(function ($value) use($timezone){
                $value->reported_message = ($value->type == "file") ? url('chat-root/'.$value->message) : $value->message;
                $value->reported_at = $this->formatDateTimeCountryWise($value->created_at, $timezone);

                return $value;
            });
            $reportedMessageData->makeHidden(['reporter_user_id','message_id','is_admin_read','created_at','updated_at','message','from_user']);

            return $this->sendSuccessResponse(Lang::get('messages.report.reported-message-list'), 200, $reportedMessageData);
        } catch (\Exception $e) {
            Log::info($e);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }
}
