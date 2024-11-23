<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Community;
use App\Models\CommunityCommentLikes;
use App\Models\CommunityCommentReply;
use App\Models\CommunityCommentReplyLikes;
use App\Models\CommunityComments;
use App\Models\CommunityLikes;
use App\Models\CompletedCustomer;
use App\Models\DeleteAccountReason;
use App\Models\EntityTypes;
use App\Models\Hospital;
use App\Models\Message;
use App\Models\MessageNotificationStatus;
use App\Models\Notice;
use App\Models\Post;
use App\Models\ReloadCoinRequest;
use App\Models\ReportClient;
use App\Models\RequestedCustomer;
use App\Models\RequestForm;
use App\Models\ReviewCommentLikes;
use App\Models\ReviewCommentReply;
use App\Models\ReviewCommentReplyLikes;
use App\Models\ReviewComments;
use App\Models\ReviewLikes;
use App\Models\Reviews;
use App\Models\SearchHistory;
use App\Models\Shop;
use App\Models\ShopFollowers;
use App\Models\User;
use App\Models\UserBlockHistory;
use App\Models\UserCredit;
use App\Models\UserCreditHistory;
use App\Models\UserDetail;
use App\Models\UserDevices;
use App\Models\UserEntityRelation;
use App\Models\UserHidePopupImage;
use App\Models\UserInstagramHistory;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DeleteAccountReasonController extends Controller
{
    public function index(Request $request)
    {
        $title = 'Reasons List';
        DeleteAccountReason::where('is_admin_read', 1)->update(['is_admin_read' => 0]);

        return view('admin.reasons-delete-account.index', compact('title'));
    }

    public function getJsonData(Request $request){
        $columns = array(
            0 => 'users_detail.name',
            1 => 'users_detail.mobile',
            2 => 'delete_account_reasons.reason',
            3 => 'delete_account_reasons.created_at',
            4 => 'users.created_at',
        );

        $limit = $request->input('length');
        $start = $request->input('start');
        $search = $request->input('search.value');
        $draw = $request->input('draw');
        $order = $columns[$request->input('order.0.column')];
        $dir = $request->input('order.0.dir');

        try {
            $data = [];
            $query = DeleteAccountReason::leftjoin('users', 'users.id', 'delete_account_reasons.user_id')
                ->leftjoin('users_detail','users_detail.user_id','delete_account_reasons.user_id')
                ->select(
                    'delete_account_reasons.*',
                    'users_detail.name as user_name',
                    'users_detail.mobile as mobile_number',
                    'users.created_at as signup_date'
                );

            $totalData = count($query->get());
            $totalFiltered = $totalData;

            if (!empty($search)) {
                $query = $query->where(function($q) use ($search){
                    $q->where('users_detail.name', 'LIKE', "%{$search}%")
                        ->orWhere('delete_account_reasons.reason', 'LIKE', "%{$search}%");
                });

                $totalFiltered = $query->count();
            }

            $result = $query->offset($start)
                ->limit($limit)
                ->orderBy($order, $dir)
                ->get();

            $count = 0;
            foreach($result as $res){
                $status = "";
                if ($res->is_deleted_user==1){
                    $status .= "Deleted";
                }
                else {
                    $status .= '<a href="javascript:void(0)" role="button" onclick="removeUser('.$res->id.')" class="btn btn-danger" data-toggle="tooltip" data-original-title="Delete"><i class="fa fa-trash"></i></a>';
                }

                $data[$count]['username'] = $res->user_name;
                $data[$count]['phone'] = $res->mobile_number;
                $data[$count]['reason'] = $res->reason;
                $data[$count]['request_date'] = Carbon::parse($res->created_at)->format('Y-m-d H:i:s');
                $data[$count]['signup_date'] = Carbon::parse($res->signup_date)->format('Y-m-d H:i:s');
                $data[$count]['status'] = $status;

                $count++;
            }

            $jsonData = array(
                "draw" => intval($draw),
                "recordsTotal" => intval($totalData),
                "recordsFiltered" => intval($totalFiltered),
                "data" => $data,
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

    public function delete($id)
    {
        return view('admin.reasons-delete-account.delete', compact('id'));
    }

    public function destroy($id)
    {
        try {
            DB::beginTransaction();

            $delete_account_reason = DeleteAccountReason::where('id', $id)->first();
            $userID = $delete_account_reason->user_id;
            /*$delete_account_reason->is_deleted_user = 1;
            $delete_account_reason->save();*/
            DeleteAccountReason::where('user_id', $userID)->update(['is_deleted_user' => 1]);

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

            DB::commit();
            notify()->success("User deleted successfully", "Success", "topRight");
            return redirect()->route('admin.reasons-delete-account.index');
        } catch (\Exception $ex) {
            DB::rollBack();
            notify()->error("Failed to delete user", "Error", "topRight");
            return redirect()->route('admin.reasons-delete-account.index');
        }
    }

}
