<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminMessage;
use App\Models\ShopImages;
use App\Models\ShopImagesTypes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AdminChatController extends Controller
{
    public function index(Request $request)
    {
        $title = 'Admin Chat List';

        return view('admin.admin-chat.index', compact('title'));
    }

    public function getJsonData(Request $request){
        $columns = array(
            0 => 'user_name',
            1 => 'admin_messages.message',
        );

        $limit = $request->input('length');
        $start = $request->input('start');
        $search = $request->input('search.value');
        $draw = $request->input('draw');
        $order = $columns[$request->input('order.0.column')];
        $dir = $request->input('order.0.dir');
        $adminTimezone = $this->getAdminUserTimezone();
        $language_id = 4;

        try {
            $data = [];

            $concatQuery = 'CASE
                                WHEN admin_messages.from_user = 0 THEN CONCAT(admin_messages.from_user, "_", admin_messages.to_user)
                                ELSE CONCAT(admin_messages.to_user, "_", admin_messages.from_user)
                            END';
            $user_id = 0;
            $chatQuery = AdminMessage::whereRaw("admin_messages.type='text' and (admin_messages.from_user = " . $user_id . " OR admin_messages.to_user = " . $user_id . ")")
                ->select(DB::raw('max(admin_messages.id) as message_id'))
                ->selectRaw("{$concatQuery} AS uniqe_records")
                ->groupBy('uniqe_records')
                ->pluck('message_id');
            $query = AdminMessage::whereIn('admin_messages.id',$chatQuery)
                ->orderBy('admin_messages.updated_at','DESC')
                ->select('admin_messages.*');

            if (!empty($search)) {
                $query =  $query->where(function ($q) use ($search) {
                    $q->where('admin_messages.message', 'LIKE', "%{$search}%");
                });
            }

            $totalData = count($query->get());
            $totalFiltered = $totalData;

            $message_data = $query->offset($start)
                ->limit($limit)
                ->get();

            $count = 0;
            foreach($message_data as $message){
                if(!empty($message->to_user)){
                    $userID = $message->to_user;
                }else{
                    $userID = $message->from_user;
                }
                $main_name = "";
                $shopData = DB::table('shops')->where('user_id',$userID)->whereNull('deleted_at')->first();
                $userData = DB::table('users_detail')->where('user_id',$userID)->whereNull('deleted_at')->first();
                if($shopData){
                    $displayName = [];
                    if(!empty($userData) && !empty($userData->name)){
                        $displayName[] = $userData->name;
                    }
                    if(!empty($shopData) && !empty($shopData->main_name)){
                        $displayName[] = $shopData->main_name;
                    }
                    if(!empty($shopData) && !empty($shopData->shop_name)){
                        $displayName[] = $shopData->shop_name;
                    }

                    $main_name = implode(' / ',$displayName);
                }else{
                    $main_name = (!empty($userData) && !empty($userData->name)) ? $userData->name : "";
                }

                $unread_count = DB::table('admin_messages')->where('is_read', 0)
                    ->where('to_user', 0)
                    ->where(function ($q) use ($message) {
                        $q->where('from_user', $message->from_user)
                            ->orWhere('from_user', $message->to_user);
                    })
                    ->count();
                if ($unread_count==0){
                    $unread_count = "";
                }

                $data[$count]['user_name'] = $main_name;
                $data[$count]['last_message'] = $message->message;
                $data[$count]['time'] = timeAgo($message->created_at, $language_id, $adminTimezone);
                $data[$count]['see_more'] = '<a role="button" href="javascript:void(0)" onclick="viewEntireChat('.$userID.')" title="" data-original-title="View More" class="mx-1 btn btn-primary btn-sm" data-toggle="tooltip">See More</a><span>'.$unread_count.'</span>';

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

    public function showUserMessages($id){
        $adminTimezone = $this->getAdminUserTimezone();
        $from_user = 0;
        $to_user = $id;
        $messages = AdminMessage::whereRaw("(admin_messages.from_user = " . $from_user . " OR admin_messages.from_user = " . $to_user . ") and (admin_messages.to_user = " . $from_user . " OR admin_messages.to_user = " . $to_user . ")")
            ->orderBy('id','DESC')
            ->get();
//        dd($messages->toArray());
        return view('admin.admin-chat.show-messages-popup', compact('messages','adminTimezone'));
    }

}
