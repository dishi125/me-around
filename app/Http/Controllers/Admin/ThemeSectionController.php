<?php

namespace App\Http\Controllers\Admin;

use App\Models\ChallengeAppInvited;
use App\Models\ChallengeInvitedUser;
use App\Models\ChallengeVerify;
use App\Models\DeleteAccountReason;
use App\Models\GroupMessage;
use App\Models\InstagramLog;
use App\Models\ReportedUser;
use App\Models\ReportGroupMessage;
use App\Models\UserCardLog;
use App\Models\UserFeedLog;
use Carbon\Carbon;
use App\Models\ShopPost;
use App\Models\UserCards;
use App\Models\EntityTypes;
use App\Models\PostLanguage;
use Illuminate\Http\Request;
use App\Models\MetalkOptions;
use App\Models\GeneralSettings;
use App\Models\CommunityComments;
use App\Models\RequestFormStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Models\MetalkOptionLanguage;
use App\Models\RequestBookingStatus;
use App\Models\CommunityCommentReply;
use Illuminate\Support\Facades\Storage;
use App\Models\AssociationCommunityComment;

class ThemeSectionController extends Controller
{
    public function index()
    {
        $title = "Theme Section";
        $options = MetalkOptions::where('options_type',MetalkOptions::THEME_OPTIONS)->get();
        return view('admin.theme.index', compact('title', 'options'));
    }

    public function saveOptions(Request $request)
    {
        $options = MetalkOptions::where('options_type',MetalkOptions::THEME_OPTIONS)->get();
        $inputs = $request->all();

        try {
            $updateOptionsKey = collect($options)->pluck('key')->toArray();
            foreach ($inputs as $optionKey => $optionData) {
                if (in_array($optionKey, $updateOptionsKey)) {
                    $currentOption = collect($options)->where('key', $optionKey)->first();
                    if ($currentOption->type == MetalkOptions::FILE) {
                        $optionFolder = config('constant.options');
                        if (is_file($optionData)) {
                            $originalName = $optionData->getClientOriginalName();

                            if(!empty($currentOption->value) && !empty($currentOption->file_url)){
                                Storage::disk('s3')->delete($currentOption->value);
                            }

                            if (!Storage::disk('s3')->exists($originalName)) {
                                Storage::disk('s3')->makeDirectory($originalName);
                            }
                            $mainFile = Storage::disk('s3')->putFileAs($optionFolder, $optionData, $originalName, 'public');
                            $fileName = basename($mainFile);
                            $file_url = $optionFolder . '/' . $fileName;
                            MetalkOptions::where('key', $optionKey)->update(['value' => $file_url]);
                        }
                    }elseif ($currentOption->type == MetalkOptions::TEXT) {
                        MetalkOptions::where('key', $optionKey)->update(['value' => $optionData]);
                    }elseif ($currentOption->type == MetalkOptions::DROPDOWN) {
                        MetalkOptions::where('key', $optionKey)->update(['value' => $optionData]);
                    }
                }
            }
            notify()->success("Theme Options " . trans("messages.update-success"), "Success", "topRight");
            return redirect()->route('admin.theme-section.index');
        } catch (\Exception $e) {
            Log::info($e);
            DB::rollBack();
            notify()->error("Theme Options " . trans("messages.update-error"), "Error", "topRight");
            return redirect()->route('admin.theme-section.index');
        }
    }

    public function showExplanation(Request $request)
    {
        $title = "Explanation";
        return view('admin.theme.explanation', compact('title'));
    }

    public function getJsonAllData(Request $request){

        $columns = array(
            0 => 'label',
            11 => 'action',
        );

        $limit = $request->input('length');
        $start = $request->input('start');
        $search = $request->input('search.value');
        $draw = $request->input('draw');
        $order = $columns[$request->input('order.0.column')];
        $dir = $request->input('order.0.dir');

        $adminTimezone = $this->getAdminUserTimezone();

        try {
            $data = [];
            $optionsQuery = MetalkOptions::where('options_type',MetalkOptions::EXPLANATION);

            if (!empty($search)) {
                $optionsQuery = $optionsQuery->where(function($q) use ($search){
                    $q->where('key', 'LIKE', "%{$search}%")
                    ->orWhere('label', 'LIKE', "%{$search}%");
                });
            }

            $totalData = count($optionsQuery->get());
            $totalFiltered = $totalData;

            $options = $optionsQuery->offset($start)
                        ->limit($limit)
                        ->orderBy($order, $dir)
                        ->get();

            $count = 0;
            foreach($options as $value){

                $edit = route('admin.explanation.edit', $value->id);
                $editButton = "<a href='".$edit."' role='button' class='btn btn-primary btn-sm mx-1' data-toggle='tooltip' data-original-title='Edit'><i class='fa fa-edit'></a>";

                $images = $value->languageData;

                if($value->type == MetalkOptions::FILE){
                    $images[] = $value;
                    $valueHtml = collect($images)->map(function ($image) use ($value) {
                        if(empty($image->value)) return;

                        $displayImage = Storage::disk('s3')->url($image->value);
                        return '<img onclick="showImage(`'. $displayImage .'`)" src="'.$displayImage.'" alt="'.$value->id.'" class="reported-client-images pointer m-1" width="50" height="50" />';
                    });
                    $valueHtml = collect($valueHtml)->implode('');
                }else{
                    $valueHtml = $value->value;
                }

                $data[$count]['title'] = $value->label;
                $data[$count]['value'] =  $valueHtml;
                $data[$count]['actions'] = "<div class='d-flex'>$editButton</div>";
                $count++;
            }

            $jsonData = array(
                "draw" => intval($draw),
                "recordsTotal" => intval($totalData),
                "recordsFiltered" => intval($totalFiltered),
                "data" => $data
            );
            return response()->json($jsonData);
        } catch (\Exception $ex) {
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

    public function editExplanation($id)
    {
        $optionData = MetalkOptions::whereId($id)->where('options_type',MetalkOptions::EXPLANATION)->first();
        $title = "Edit ".$optionData->label;

        $options = [];

        $languages = PostLanguage::where('is_support',1)->orderBy('id','DESC')->get();

        foreach($languages as $key => $lang){

            $languageData = MetalkOptionLanguage::where('metalk_options_id',$optionData->id)->where('language_id',$lang->id)->first();

            $tempData['id'] = ($languageData) ? $languageData->id : $optionData->id;
            $tempData['key'] =  $optionData->key;
            $tempData['label'] = $optionData->label;
            $tempData['type'] = $optionData->type;
            $tempData['value'] = ($languageData) ? $languageData->value : $optionData->value;
            $tempData['file_url'] = ($languageData) ? Storage::disk('s3')->url($languageData->value) : $optionData->file_url;

            $tempData['language'] = $lang->id;
            $tempData['language_name'] = $lang->name;

            $options[] = (object)$tempData;
        }

        return view('admin.theme.explanation-edit', compact('id', 'title','options'));
    }

    public function updateExplanation(Request $request, $id)
    {
        $inputs = $request->all();
        $option = MetalkOptions::find($id);

        $fieldKey = $option->key;
        $fieldType = $option->type;

        try {

            $optionsValue = $inputs[$fieldKey] ?? [];

            foreach($optionsValue as $languageID => $optionData){
                if ($fieldType == MetalkOptions::FILE ) {
                    $optionFolder = config('constant.options');
                    if (is_file($optionData)) {
                        $originalName = $optionData->getClientOriginalName();

                        if($languageID == PostLanguage::ENGLISH){
                            if(!empty($option->value) && !empty($option->file_url)){
                                Storage::disk('s3')->delete($option->value);
                            }
                        }else{
                            $languageData = MetalkOptionLanguage::where('metalk_options_id',$option->id)->where('language_id',$languageID)->first();
                            if(!empty($languageData) && !empty($languageData->value)){
                                Storage::disk('s3')->delete($option->value);
                            }
                        }

                        if (!Storage::disk('s3')->exists($originalName)) {
                            Storage::disk('s3')->makeDirectory($originalName);
                        }
                        $mainFile = Storage::disk('s3')->putFileAs($optionFolder, $optionData, $originalName, 'public');
                        $fileName = basename($mainFile);
                        $file_url = $optionFolder . '/' . $fileName;

                        //$file_url = "/uploads/options/download.png";
                        if($languageID == PostLanguage::ENGLISH){
                            MetalkOptions::where('key', $fieldKey)->update(['value' => $file_url]);
                        }else{
                            MetalkOptionLanguage::updateOrCreate([
                                'metalk_options_id'   => $id,
                                'language_id'   => $languageID,
                            ],[
                                'value' => $file_url,
                            ]);
                        }
                    }
                }else{
                    if($languageID == PostLanguage::ENGLISH){
                        MetalkOptions::where('key', $fieldKey)->update(['value' => $optionData]);
                    }else{
                        MetalkOptionLanguage::updateOrCreate([
                            'metalk_options_id'   => $id,
                            'language_id'   => $languageID,
                        ],[
                            'value' => $optionData,
                        ]);
                    }
                }
            }

            notify()->success("Explanation ". trans("messages.update-success"), "Success", "topRight");
            return redirect()->route('admin.explanation.index');

        } catch (\Exception $e) {
            Log::info($e);
            notify()->error("Explanation ". trans("messages.update-error"), "Error", "topRight");
            return redirect()->route('admin.explanation.index');
        }
    }

    public function checkUserUnreadComments(Request $request)
    {
        $isUnread = false;

        try{
            // User
            /* $associationCommentsCount = AssociationCommunityComment::where('is_admin_read',1)->whereNull('deleted_at')->count();
            $commentsCount = CommunityComments::where('is_admin_read',1)->whereNull('deleted_at')->count();
            $commentsReplyCount = CommunityCommentReply::where('is_admin_read',1)->whereNull('deleted_at')->count(); */

            $userCount = DB::table('users')->whereNull('deleted_at')->where('is_admin_read',1)->get('id');
            // Outside User
            $outsideCommentCount = DB::table('community')
                ->join('community_comments', function ($query) {
                    $query->on('community.id', '=', 'community_comments.community_id')
                        ->whereNull('community_comments.deleted_at');
                })
                ->join('users_detail','users_detail.user_id','community.user_id')
                ->where('users_detail.is_outside',1)
                ->where('community_comments.is_admin_read',1)
                ->groupBy('community_comments.id')
                ->get('community_comments.id');

            $outsideCommentReplyCount = DB::table('community_comments')
                ->join('community_comment_reply', function ($query) {
                    $query->on('community_comments.id', '=', 'community_comment_reply.community_comment_id')
                        ->whereNull('community_comment_reply.deleted_at');
                })
                ->where('community_comment_reply.is_admin_read',1)
                ->join('users_detail','users_detail.user_id','community_comments.user_id')
                ->where('users_detail.is_outside',1)
                ->groupBy('community_comment_reply.id')
                ->get('community_comment_reply.id');

            $outsideAssociationComment = DB::table('association_communities')
                ->join('association_community_comments', function ($query) {
                    $query->on('association_communities.id', '=', 'association_community_comments.community_id')
                        ->whereNull('association_community_comments.deleted_at');
                })
                ->join('users_detail','users_detail.user_id','association_communities.user_id')
                ->where('users_detail.is_outside',1)
                ->where('association_community_comments.is_admin_read',1)
                ->where('association_community_comments.parent_id',0)
                ->groupBy('association_community_comments.id')
                ->get('association_community_comments.id');

            $outsideAssociationReplyComment = DB::table('association_community_comments')
                ->join('association_community_comments as child', function ($query) {
                    $query->on('association_community_comments.id', '=', 'child.parent_id')
                        ->whereNull('child.deleted_at');
                })
                ->join('users_detail','users_detail.user_id','association_community_comments.user_id')
                ->where('users_detail.is_outside',1)
                ->where('child.is_admin_read',1)
                ->where('child.parent_id','!=', 0)
                ->groupBy('child.id')
                ->get('child.id');

            $requestedClientCount = DB::table('request_forms')->whereNull('deleted_at')
                    ->where('request_status_id', RequestFormStatus::PENDING)
                    ->where('is_admin_read',1)
                    ->get('id');

            $reportedClientCount = DB::table('report_clients')->whereNull('deleted_at')
                    ->where('is_admin_read',1)
                    ->get('id');


            // Check Bad Completed
            $badCompletedCount = $this->getBadCompletedDetail();

            // Shop Post count
            $shopPostCount = DB::table('shop_posts')->join('shops','shops.id','shop_posts.shop_id')
                ->select('shop_posts.id')
                ->whereNull('shop_posts.deleted_at')
                ->where('shop_posts.is_admin_read',1)
                ->get();

            $adminTimezone = $this->getAdminUserTimezone();
            // Like order count
            $startDate = Carbon::now()->timezone($adminTimezone)->subDay()->format('Y-m-d');
            $endDate = Carbon::now()->timezone($adminTimezone)->addDay()->format('Y-m-d');
            $likeOrderCount = DB::table('shop_posts')->join('shops as s','s.id','shop_posts.shop_id')
                ->whereNull('s.deleted_at')
                ->whereNull('shop_posts.deleted_at')
                ->whereNotNull('shop_posts.insta_link')
                ->where('shop_posts.is_like_order_admin_read',1)
                ->select('shop_posts.id')
                ->groupBy('shop_posts.id')
                ->whereBetween('post_order_date',[$startDate,$endDate])
                ->get();

            $likeOrderRealCount = DB::table('shop_posts')->join('shops as s','s.id','shop_posts.shop_id')
                ->whereNull('s.deleted_at')
                ->whereNull('shop_posts.deleted_at')
                ->whereNotNull('shop_posts.insta_link')
                ->where('shop_posts.is_like_order_admin_read',1)
                ->where('s.count_days','>',0)
                ->select('shop_posts.id')
                ->groupBy('shop_posts.id')
                ->whereBetween('post_order_date',[$startDate,$endDate])
                ->get();

            // Review Post
            $reviewsCount = DB::table('reviews')
                ->whereNull('deleted_at')
                ->where('is_admin_read',1)
                ->get();

            // Reload Coin request
            $reloadCoinRequestCount = DB::table('reload_coins_request')
                ->where('is_admin_read',1)
                ->get();

            $requestedCardCount = DB::table('user_cards')
                ->whereIn('status',[UserCards::SOLD_CARD_STATUS, UserCards::REQUESTED_STATUS,UserCards::REQUEST_ACCEPT_STATUS])
                ->where('is_admin_read',1)
                ->get();

            $productOrderCount = DB::table('product_orders')
                ->where('is_admin_read',1)
                ->get();

            $adminChatCount = DB::table('admin_messages')->where('is_read', 0)
                ->where('to_user', 0)
                ->where('from_user','!=',0)
                ->get();

            $reportShopCount = 0; //DB::table('shop_report_histories')->where('is_admin_read',1)->get();

            $lastView = GeneralSettings::where('key',GeneralSettings::LAST_DELETED_VIEW)->first();
            $lastViewDate = $lastView ? $lastView->value : '2000-01-01 01:00:00';
            $deletedUsersCount = DB::table('users')->join('users_detail','users_detail.user_id','users.id')->whereNotNull('users.deleted_at')->where('users.deleted_at','>=',Carbon::parse($lastViewDate))->get();
            $deleteReasonCount = DeleteAccountReason::where('is_admin_read',1)->get();
            $reportedUserCount = ReportedUser::where('is_admin_read',1)->get();
            $reportedMessageCount = ReportGroupMessage::where('is_admin_read',1)->get();
            $GroupMessageCount = GroupMessage::where('is_admin_read',1)->get();
            $InstaLogCount = InstagramLog::where('is_admin_read',1)->get();
            $FeedLogCount = UserFeedLog::where('is_admin_read',1)->get();

            $ChallengeVerificationCount = ChallengeVerify::where('is_admin_read',0)->get();

            $followerInvitationCount = ChallengeInvitedUser::where('is_admin_read',0)->count();
            $appInvitationCount = ChallengeAppInvited::where('is_admin_read',0)->count();
            $invitationCount = $followerInvitationCount + $appInvitationCount;

            $jsonData = array(
                'success' => true,
                //"user_unread_count" => ($associationCommentsCount + $commentsCount + $commentsReplyCount),
                "user_unread_count" => count($userCount),
                "outside_unread_count" => (count($outsideCommentCount) + count($outsideCommentReplyCount) + count($outsideAssociationComment) + count($outsideAssociationReplyComment)),
                "requested_unread_count" => count($requestedClientCount),
                "reported_unread_count" => count($reportedClientCount),
                "bad_complete_unread_count" => $badCompletedCount,
                "shop_post_unread_count" => count($shopPostCount),
                "like_order_unread_count" => count($likeOrderCount),
                "like_order_real_unread_count" => count($likeOrderRealCount),
                "review_unread_count" => count($reviewsCount),
                "reload_coin_unread_count" => count($reloadCoinRequestCount),
                "requested_card_unread_count" => count($requestedCardCount),
                "product_order_unread_count" => count($productOrderCount),
                "report_shop_unread_count" => 0,
                "deleted_user_unread_count" => count($deletedUsersCount),
                "reported_user_unread_count" => count($reportedUserCount),
                "reported_message_unread_count" => count($reportedMessageCount),
                "reasons_delete_account_unread_count" => count($deleteReasonCount),
                "message_unread_count" => count($GroupMessageCount),
                "instagram_connect_log_unread_count" => count($InstaLogCount),
                "feed_log_unread_count" => count($FeedLogCount),
                "admin_chat_unread_count" => count($adminChatCount),
                "verification_unread_count" => count($ChallengeVerificationCount),
                "invitation_unread_count" => $invitationCount,
            );
            return response()->json($jsonData);
        } catch (\Exception $ex) {
            $jsonData = array(
                'success' => false,
                'user_unread_count' => 0,
                'outside_unread_count' => 0,
                'requested_unread_count' => 0,
                'reported_unread_count' => 0,
                'bad_complete_unread_count' => 0,
                'shop_post_unread_count' => 0,
                'like_order_unread_count' => 0,
                'like_order_real_unread_count' => 0,
                'review_unread_count' => 0,
                'reload_coin_unread_count' => 0,
                'requested_card_unread_count' => 0,
                'product_order_unread_count' => 0,
                "report_shop_unread_count" => 0,
                "deleted_user_unread_count" => 0,
                "reported_user_unread_count" => 0,
                "reported_message_unread_count" => 0,
                "reasons_delete_account_unread_count" => 0,
                "message_unread_count" => 0,
                "instagram_connect_log_unread_count" => 0,
                "feed_log_unread_count" => 0,
                "admin_chat_unread_count" => 0,
                "verification_unread_count" => 0,
                "invitation_unread_count" => 0,
            );
            return response()->json($jsonData);
        }
    }

    public function getBadCompletedDetail(){
        $completedQuery = DB::table('requested_customer')
            ->leftjoin('users_detail','users_detail.user_id', 'requested_customer.user_id')
            ->select(
                'users_detail.user_id as user_id',
                'requested_customer.entity_type_id',
                'requested_customer.entity_id',
                DB::raw("DATE_FORMAT(requested_customer.booking_date, '%Y-%m-%d') as date"),
                'requested_customer.id'
            )
            ->whereNull('requested_customer.deleted_at')
            ->where('requested_customer.is_admin_read',1)
            ->where('requested_customer.request_booking_status_id',RequestBookingStatus::COMPLETE)
            ->orderBy('requested_customer.booking_date','ASC')
            ->get();

        $completedResult = collect($completedQuery)->groupBy(['user_id','entity_type_id', 'entity_id']);
        $displayResult = $userDisplayData = [];
        foreach($completedResult as $userKey => $userData){
            foreach($userData as $typeKey => $entityType){
                foreach($entityType as $entityKey => $entityData){
                    if(count($entityData) > 1){
                        foreach($entityData as $bookingKey => $bookingData){
                            if(isset($entityData[$bookingKey+1]) ){

                                $currentDate = $bookingData->date;
                                $nextBookingDate = $entityData[$bookingKey+1]->date;
                                $nextWeekBookDate = Carbon::parse($currentDate)->addDays(14)->format('Y-m-d');

                                if(Carbon::parse($nextBookingDate)->between($currentDate,$nextWeekBookDate)){
                                    $displayResult[$bookingData->user_id][$entityKey]['ids'][] = $bookingData->id;
                                    $displayResult[$bookingData->user_id][$entityKey]['ids'][] = $entityData[$bookingKey+1]->id;
                                }
                            }
                        }
                    }
                }
            }
        }
        $innerCount = 0;
        foreach($displayResult as $userDetail){
            foreach($userDetail as $entityDetail){
                foreach($entityDetail as $bookingDetail){
                    $userDisplayData[$innerCount] = $bookingDetail;
                    $innerCount++;
                }
            }
        }

        return count($userDisplayData);
    }
}
