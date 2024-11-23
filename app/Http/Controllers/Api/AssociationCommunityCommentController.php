<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use DB;
use Illuminate\Support\Facades\Lang;
use App\Validators\AssociationCommunityCommentValidator;
use Carbon\Carbon;
use App\Models\AssociationCommunityComment;
use App\Models\AssociationCommunity;
use App\Models\Notice;
use App\Models\EntityTypes;
use App\Models\UserDetail;
use App\Models\UserDevices;
use App\Util\Firebase;

class AssociationCommunityCommentController extends Controller
{
    private $associationCommunityCommentValidator;
    protected $firebase;

    function __construct()
    {
        $this->associationCommunityCommentValidator = new AssociationCommunityCommentValidator();
        $this->firebase = new Firebase();
    }  

    public function addComment(Request $request)
    {
        $user = Auth::user();
        $inputs = $request->all();
        try {
            DB::beginTransaction();

            $validation = $this->associationCommunityCommentValidator->validateCommunityComment($inputs);
            if ($validation->fails()) {
                return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
            }

            /* $users_detail = '';
            if(isset($inputs['parent_id']) && !empty($inputs['parent_id'])){
                $users_detail = DB::table('association_community_comments')->join('users_detail','users_detail.user_id','association_community_comments.user_id')
                    ->whereNull('association_community_comments.deleted_at')
                    ->where('association_community_comments.id',$inputs['parent_id'])
                    ->select('users_detail.*')
                    ->first();
            }else{
                $users_detail = DB::table('association_communities')->join('users_detail','users_detail.user_id','association_communities.user_id')
                    ->whereNull('association_communities.deleted_at')
                    ->where('association_communities.id',$inputs['community_id'])
                    ->select('users_detail.*')
                    ->first();
            }

            $is_unread = 0;
            if($users_detail && $users_detail->is_outside){
                $is_unread = 1;
            } */

            $requestData = [
                'community_id' => $inputs['community_id'],
                'user_id' => $user->id,
                'comment' => $inputs['comment'],
                'parent_id' => $inputs['parent_id'] ?? 0,     
                'is_edited' => 0,
                'is_admin_read' => 1
            ];                    

            $comment = AssociationCommunityComment::create($requestData);  
            $community = AssociationCommunity::find($inputs['community_id']);

            if($user->id != $community->user_id){
                $notice = Notice::create([
                    'notify_type' => Notice::ASSOCIATION_COMMUNITY_COMMENT,
                    'user_id' => $user->id,
                    'to_user_id' => $community->user_id,
                    'entity_type_id' => EntityTypes::ASSOCIATION_COMMUNITY,
                    'entity_id' => $community->id,
                    'title' => $community->title,
                    'sub_title' => $inputs['comment'],
                    'is_aninomity' => 0
                ]);

                $user_detail = UserDetail::where('user_id', $community->user_id)->first();
                $language_id = $user_detail->language_id;
                $key = Notice::ASSOCIATION_COMMUNITY_COMMENT.'_'.$language_id;
                $userIds = [$community->user_id];
                $userName = $user->name;

                $format = __("notice.$key", ['username' => $userName]);
                $devices = UserDevices::whereIn('user_id', $userIds)->pluck('device_token')->toArray();

                $title_msg = $inputs['comment'];
                $notify_type = 'notices';

                $notificationData = [
                    'id' => $community->id,
                    'user_id' => $community->user_id,
                    'title' => $community->title,
                ];
                if (count($devices) > 0) {
                    $result = $this->sentPushNotification($devices,$title_msg, $format, $notificationData ,$notify_type);                        
                }
            }

            if($request->has('parent_id') && $inputs['parent_id'] != 0){
                $parentCommentId = $inputs['parent_id'];
                $parentComment = AssociationCommunityComment::where(['id' => $parentCommentId])->first();

                if($user->id != $parentComment->user_id){

                    Notice::create([
                        'notify_type' => Notice::ASSOCIATION_COMMUNITY_COMMENT_REPLY,
                        'user_id' => $user->id,
                        'to_user_id' => $parentComment->user_id,
                        'entity_type_id' => EntityTypes::ASSOCIATION_COMMUNITY,
                        'entity_id' => $community->id,
                        'title' => $community->title,
                        'sub_title' => $inputs['comment'],
                        'is_aninomity' => 0
                    ]);

                    $user_detail = UserDetail::where('user_id', $parentComment->user_id)->first();
                    $language_id = $user_detail->language_id;
                    $key = Notice::ASSOCIATION_COMMUNITY_COMMENT_REPLY.'_'.$language_id;
                    $userIds = [$parentComment->user_id];
                    $userName = $user->name;

                    $format = __("notice.$key", ['username' => $userName]);
                    $devices = UserDevices::whereIn('user_id', $userIds)->pluck('device_token')->toArray();
                    $title_msg = $inputs['comment'];
                    $notify_type = 'notices';
                    $notificationData = [
                        'id' => $community->id,
                        'user_id' => $community->user_id,
                        'title' => $community->title,
                    ];

                    if (count($devices) > 0) {
                        $result = $this->sentPushNotification($devices,$title_msg, $format, $notificationData ,$notify_type);                        
                    }

                }

            }

            DB::commit();
            return $this->sendSuccessResponse(Lang::get('messages.community.comment-success'), 200, $comment);
        } catch (\Exception $e) {
            Log::info($e);
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }

    }

    public function updateCommunityComment(Request $request, $id)
    {
        try {
            $inputs = $request->all();
            $user = Auth::user();
            $reviewComment = AssociationCommunityComment::find($id);
            if($reviewComment){
                DB::beginTransaction();  
                $validation = $this->associationCommunityCommentValidator->validateCommunityCommentEdit($inputs);
                if ($validation->fails()) {
                    return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
                } 
                AssociationCommunityComment::where('id',$id)->update(['comment' => $inputs['comment'],'is_edited' => 1]);
                $reviewComment = AssociationCommunityComment::find($id);
                $reviewComment->comments_reply;
                DB::commit();
                
                return $this->sendSuccessResponse(Lang::get('messages.community.comment-edit-success'), 200,$reviewComment);
            }else{
                return $this->sendSuccessResponse(Lang::get('messages.community.comment-empty'), 402);
            }
        } catch (\Exception $e) {
            Log::info($e);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function deleteCommunityComment($id)
    {       
        try {
            $user = Auth::user();
            if($user){
                $reviewComment = AssociationCommunityComment::find($id);
                if($reviewComment){
                    DB::beginTransaction();  
                    $review = AssociationCommunityComment::where('id',$id)->delete();
                    DB::commit();
                    return $this->sendSuccessResponse(Lang::get('messages.community.comment-delete-success'), 200,[]);
                }else{
                    return $this->sendSuccessResponse(Lang::get('messages.community.comment-empty'), 402);
                }
            }else{
                return $this->sendSuccessResponse(Lang::get('messages.user.token_expired'), 401);
            }  
        } catch (\Exception $e) {
            Log::info($e);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }
}
