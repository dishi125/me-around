<?php

namespace App\Http\Controllers;

use Cookie;
use Carbon\Carbon;
use App\Models\City;
use App\Models\Post;
use App\Models\Shop;
use App\Models\User;
use App\Models\State;
use App\Models\Notice;
use App\Models\Status;
use App\Util\Firebase;
use App\Models\Country;
use App\Models\Message;
use App\Models\Reviews;
use App\Models\Hospital;
use App\Models\Community;
use App\Models\UserCredit;
use App\Models\UserDetail;
use App\Models\ActivityLog;
use App\Models\EntityTypes;
use App\Models\RequestForm;
use App\Models\ReviewLikes;
use App\Models\UserDevices;
use App\Models\PostLanguage;
use App\Models\ReportClient;
use Illuminate\Http\Request;
use App\Models\SearchHistory;
use App\Models\ShopFollowers;
use App\Traits\ResponseTrait;
use App\Models\CommunityLikes;
use App\Models\ReviewComments;
use App\Traits\ActivityTraits;
use App\Models\UserBlockHistory;
use App\Models\CommunityComments;
use App\Models\CompletedCustomer;
use App\Models\ReloadCoinRequest;
use App\Models\RequestedCustomer;
use App\Models\UserCreditHistory;
use App\Models\ReviewCommentLikes;
use App\Models\ReviewCommentReply;
use App\Models\UserEntityRelation;
use App\Models\UserHidePopupImage;
use Illuminate\Support\Facades\DB;
use App\Models\ManagerActivityLogs;
use Illuminate\Support\Facades\Log;
use App\Models\UserInstagramHistory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Lang;
use App\Models\CommunityCommentLikes;
use App\Models\CommunityCommentReply;
use App\Models\ReviewCommentReplyLikes;
use Spatie\Activitylog\Models\Activity;
use App\Models\MessageNotificationStatus;
use App\Models\CommunityCommentReplyLikes;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests, ResponseTrait, ActivityTraits;

    protected $firebase;
    public function __construct()
    {
        $this->firebase = new Firebase();
    }
    public function addCurrentLocation($country, $state, $city)
    {

        $city_list = NULL;
        $country_list = Country::where('name', $country)->first();
        $state_list = State::where('name', $state)->first();

        if(!empty($city)){
            $city_list = City::where('name', $city)->first();
        }

        if (empty($country_list)) {
            $country_list = Country::create([
                'name' => $country
            ]);
        }

        if (empty($state_list)) {
            $state_list = State::create([
                'name' => $state,
                'country_id' => $country_list->id
            ]);
        }

        if (empty($city_list) && !empty($city)) {
            $city_list = City::create([
                'name' => $city,
                'state_id' => $state_list->id
            ]);
        }

        $data =  [];
        $data['country'] = $country_list;
        $data['state'] = $state_list;
        $data['city'] = $city_list;
        return $data;
    }

    public function sentPushNotification($registration_ids,$title_msg, $format, $notificationData =[], $notify_type = null, $event_id = null, $action = null, $broadcaster = null, $position = null)
    {
        try {
            Log::info('Start code for push notification');
            $msg = array(
                'body' => $format,
                'title' => $title_msg,
                'notification_data' => $notificationData,
                'priority'=> 'high',
                'sound' => 'notifytune.wav',
            );
            $data = array(
                'notification_data' => $notificationData,
                'type' => $notify_type,
                'msgcnt' => 1,
                'action' => $action,
                "click_action" => "FLUTTER_NOTIFICATION_CLICK",
                "event_id" => $event_id,
                "broadcaster_name" => $broadcaster,
                "position" => $position
            );

            $response = $this->firebase->sendMultiple($registration_ids, $data, $msg);
            Log::info('End code for push notification');
            return $response;
        } catch (\Exception $ex) {
            Log::info("Exception SentPushnotification:");
            Log::error($ex);
            return;
        }
    }

    public function timeLanguageFilterNew($paginateData,$language_id = 4,$timezone = "") {
        $filteredData = [];
        foreach($paginateData['data'] as $key => $value) {
            $value = is_array($value) ? (object)$value : $value;
            $value->time_difference = timeAgo($value->created_at, $language_id,$timezone);
            $filteredData[] = $value;
        }

        $paginateData['data'] = array_values($filteredData);
        return $paginateData;
    }

    public function timeLanguageFilter($paginateData,$language_id = 4,$timezone = "") {
        $filteredData = [];
        // $paginateData = $data->toArray();
        //print_r($paginateData['data']);die;
        foreach($paginateData['data'] as $key => $value) {
            $value['time_difference'] = timeAgo($value['created_at'], $language_id,$timezone);

            $value['comments'] = $this->commentTimeFilter($value['comments'],$language_id,$timezone);

            $filteredData[] = $value;
        }

        $paginateData['data'] = array_values($filteredData);
        return $paginateData;
    }

    public function commentTimeFilter($data,$language_id = 4,$timezone = "") {
        $filteredData = [];
        $paginateData = $data;

        if(!empty($paginateData['data'])){
            foreach($paginateData['data'] as $key => $value) {
                $value['comment_time'] = timeAgo($value['created_at'], $language_id,$timezone);

                foreach($value['comments_reply'] as $key2 => $value2) {
                    $value['comments_reply'][$key2]['comment_time'] = timeAgo($value2['created_at'], $language_id,$timezone);
                }

                $filteredData[] = $value;
            }
        }

        $paginateData['data'] = array_values($filteredData);
        return $paginateData;
    }

    public function updateUserChatStatus(){
        $user = Auth::user();
        $isShop = DB::table('user_entity_relation')->where('user_id',$user->id)->where('entity_type_id',EntityTypes::SHOP)->count();
        if($isShop > 0){
            $shop_count = DB::table('user_entity_relation')
                            ->join('shops','shops.id','user_entity_relation.entity_id')
                            ->where('entity_type_id', EntityTypes::SHOP)
                            ->where('user_entity_relation.user_id',$user->id)
                            ->whereIn('shops.status_id',[Status::ACTIVE,Status::PENDING])
                            ->count();
            $updateStatus =  ($shop_count > 0) ? 1 : 0;
            User::where('id',$user->id)->update(['chat_status' => $updateStatus]);
        }else{
            $isHospital = DB::table('user_entity_relation')->where('user_id',$user->id)->where('entity_type_id',EntityTypes::HOSPITAL)->count();
            if($isHospital > 0){
                $hospital_count = DB::table('user_entity_relation')
                                    ->join('hospitals','hospitals.id','user_entity_relation.entity_id')
                                    ->where('entity_type_id', EntityTypes::HOSPITAL)
                                    ->where('user_entity_relation.user_id',$user->id)
                                    ->whereIn('hospitals.status_id',[Status::ACTIVE,Status::PENDING])
                                    ->count();
                $updateStatus =  ($hospital_count > 0) ? 1 : 0;
                User::where('id',$user->id)->update(['chat_status' => $updateStatus]);
            }
        }

    }

    public function deleteUserDetails(Request $request){
        $inputs = $request->all();
        $userID = $inputs['userId'];
        try {
            DB::beginTransaction();

            if(!empty($userID)){

                ActivityLog::where('user_id',$userID)->delete();
                CommunityComments::where('user_id',$userID)->delete();
                CommunityCommentLikes::where('user_id',$userID)->delete();
                CommunityCommentReply::where('user_id',$userID)->delete();
                CommunityCommentReplyLikes::where('user_id',$userID)->delete();
                CommunityLikes::where('user_id',$userID)->delete();
                Community::where('user_id',$userID)->delete();

                CompletedCustomer::where('user_id',$userID)->delete();
                Message::where('from_user_id',$userID)->delete();
                Message::where('to_user_id',$userID)->delete();
                MessageNotificationStatus::where('user_id',$userID)->delete();
                Notice::where('user_id',$userID)->delete();
                Notice::where('to_user_id',$userID)->delete();
                ReloadCoinRequest::where('user_id',$userID)->delete();
                ReportClient::where('reported_user_id',$userID)->delete();
                ReportClient::where('user_id',$userID)->delete();
                RequestedCustomer::where('user_id',$userID)->delete();
                RequestForm::where('user_id',$userID)->delete();

                ReviewCommentReplyLikes::where('user_id',$userID)->delete();
                ReviewCommentReply::where('user_id',$userID)->delete();
                ReviewCommentLikes::where('user_id',$userID)->delete();
                ReviewComments::where('user_id',$userID)->delete();
                ReviewLikes::where('user_id',$userID)->delete();
                Reviews::where('user_id',$userID)->delete();

                SearchHistory::where('user_id',$userID)->delete();
                ShopFollowers::where('user_id',$userID)->delete();
                UserBlockHistory::where('user_id',$userID)->orWhere('block_user_id',$userID)->delete();
                DB::table('user_calls')->where('from_user_id',$userID)->orWhere('to_user_id',$userID)->delete();
                UserCredit::where('user_id',$userID)->delete();
                UserCreditHistory::where('user_id',$userID)->orWhere('booked_user_id',$userID)->delete();
                UserDevices::where('user_id',$userID)->delete();
                UserHidePopupImage::where('user_id',$userID)->delete();
                UserInstagramHistory::where('user_id',$userID)->delete();

                $businessProfiles = UserEntityRelation::where('user_id',$userID)->get();

                foreach($businessProfiles as $profile){
                    if($profile->entity_type_id == EntityTypes::SHOP){
                        Shop::where('id',$profile->entity_id)->delete();
                    }
                    if($profile->entity_type_id == EntityTypes::HOSPITAL){
                        Hospital::where('id',$profile->entity_id)->delete();
                        Post::where('hospital_id',$profile->entity_id)->delete();
                    }
                }

                UserEntityRelation::where('user_id',$userID)->delete();
                UserDetail::where('user_id',$userID)->delete();
                User::where('id',$userID)->delete();

                $logData = [
                    'activity_type' => ManagerActivityLogs::DELETE_ACCOUNT,
                    'user_id' => auth()->user()->id,
                    'value' => Lang::get('messages.manager_activity.delete_account'),
                    'entity_id' => $userID,
                ];
                $this->addManagerActivityLogs($logData);

                DB::commit();
                Log::info('Delete user code end.');
                return $this->sendSuccessResponse('User deleted successfully.', 200);

            }else{
                return $this->sendSuccessResponse('Failed to delete user.', 201);
            }

        } catch (\Exception $ex) {
            DB::rollBack();
            Log::info('Delete user code exception.');
            Log::info($ex);
            return $this->sendFailedResponse('Failed to delete user.', 201);
        }
    }

    public function getAdminUserTimezone(){

        $timezone = isset($_COOKIE['admin_timezone_new']) ? $_COOKIE['admin_timezone_new'] : '';

        if(empty($timezone)){
            Log::info('Admin Timezone Function.');
            $timezone = '';
            $ip     = $this->getIPAddress();
            //$ip     = '182.70.126.26';
            $json   = file_get_contents( 'http://ip-api.com/json/' . $ip);
            $ipData = json_decode( $json, true);
            Log::info(Carbon::now()->format('Y-m-d H:i:s'));
            Log::info($ip);
            Log::info($json);
            if (!empty($ipData) && !empty($ipData['timezone'])) {
                $timezone = $ipData['timezone'];
                $countryCode = $ipData['countryCode'];
            } else {
                $timezone = 'UTC';
                $countryCode = 'KR';
            }
            setcookie('admin_timezone_new',$timezone,time()+60*60*24*365, '/');
            setcookie('admin_country_code',$countryCode,time()+60*60*24*365, '/');
            Log::info($timezone);
            return $timezone;
        }else{
            return $timezone;
        }
    }

    public function getAdminCountryCode(){
        return isset($_COOKIE['admin_country_code']) ? $_COOKIE['admin_country_code'] : 'KR';
    }

    public function getIPAddress() {
        if(!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        }elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        }else{
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return $ip;
    }

    /* Format DateTime Country wise */
    public static function formatDateTimeCountryWise($date,$adminTimezone,$format='Y-m-d H:i:s'){
        if(empty($date)) return;
        $dateShow = Carbon::createFromFormat('Y-m-d H:i:s',Carbon::parse($date), "UTC")->setTimezone($adminTimezone)->toDateTimeString();
        return Carbon::parse($dateShow)->format($format);
    }

    /* Insert manager activity Log details */
    public function addManagerActivityLogs($data){
        ManagerActivityLogs::create($data);
    }

    public static function get_image_mime_type($image_path)
    {
        $fileExt = pathinfo($image_path, PATHINFO_EXTENSION);

        $extCheck = [
            'mp4',
            'mov',
            'wmv',
            'avi',
            'flv',
            'f4v',
            'swf',
            'mkv',
            'webm',
        ];

        if (in_array($fileExt,$extCheck)){
            return true;
        }else{
            return false;
        }
    }
}
