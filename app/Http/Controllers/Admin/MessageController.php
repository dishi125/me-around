<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AutoChatMessage;
use App\Models\CardLevel;
use App\Models\Country;
use App\Models\DefaultCardsRives;
use App\Models\GroupMessage;
use App\Models\NodeUserCountry;
use App\Models\UserDetail;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MessageController extends Controller
{
    public function index(Request $request)
    {
        $title = 'Message List';
        GroupMessage::where('is_admin_read', 1)->update(['is_admin_read' => 0]);
        $countries = Country::WhereNotNull('code')->where('is_show',1)->orderBy('priority')->get();
        $countries= collect($countries)->mapWithKeys(function ($value) {
            return [$value->code => $value->name];
        })->toArray();
        $users = UserDetail::orderBy('created_at','DESC')->get(['user_id','name']);
        $users= collect($users)->mapWithKeys(function ($value) {
            return [$value->user_id => $value->name];
        })->toArray();

        return view('admin.message.index', compact('title','countries','users'));
    }

    public function chatBotIndex($country){
        $title = 'List';
        $users = DB::table('users')->join('users_detail', 'users_detail.user_id', 'users.id')
            ->join('node_user_countries', 'node_user_countries.from_user_id', 'users.id')
            ->whereNull('users.deleted_at')
            ->where('node_user_countries.country', $country)
            ->pluck('users_detail.name','users.id')
            ->toArray();
        $week_days = array('Monday'=>'Monday','Tuesday'=>'Tuesday','Wednesday'=>'Wednesday','Thursday'=>'Thursday','Friday'=>'Friday','Saturday'=>'Saturday','Sunday'=>'Sunday');

        $autochat_messages = AutoChatMessage::where('country_code',$country)->get();
        return view('admin.chat-bot.index', compact('title','users','week_days','country','autochat_messages'));
    }

    public function storeData(Request $request){
        try{
            DB::beginTransaction();

            NodeUserCountry::updateOrCreate([
                'from_user_id' => $request->from_user_id
            ], [
                'country' => $request->country
            ]);

            $message_id = DB::table('group_messages')->insertGetId([
                "from_user" => $request->from_user_id,
                "type" => "text",
                "message" => $request->message,
                "country" => $request->country,
                "created_at" => strtotime("now"),
            ]);

            $message_data = DB::table('group_messages')->where('id', $message_id)->first();
            $message_data->from_user_id = (int)$request->from_user_id;

            $appliedCard = DefaultCardsRives::join('user_cards', 'user_cards.default_cards_riv_id', 'default_cards_rives.id')
                ->select('default_cards_rives.*','user_cards.id as user_card_id','user_cards.active_level','user_cards.card_level_status')
                ->where(['user_cards.user_id' => $request->from_user_id,'user_cards.is_applied' => 1])
                ->first();
            if (!$appliedCard){
                $message_data->background_thumbnail_url = "";
                $message_data->character_thumbnail_url = "";
            }
            else {
                if ($appliedCard->active_level == CardLevel::DEFAULT_LEVEL) {
                    $message_data->background_thumbnail_url = $appliedCard->background_thumbnail_url ?? "";
                    $message_data->character_thumbnail_url = $appliedCard->character_thumbnail_url ?? "";
                } else {
                    $levelCard = $appliedCard->cardLevels()->firstWhere('card_level', $appliedCard->active_level);
                    if ($levelCard) {
                        $message_data->background_thumbnail_url = $levelCard->background_thumbnail_url ?? "";
                        $message_data->character_thumbnail_url = $levelCard->character_thumbnail_url ?? "";
                    }
                }
            }

            $user_data = UserDetail::where('user_id',$request->from_user_id)->select(['name','avatar','is_character_as_profile'])->first();
            $message_data->name = !empty($user_data) ? $user_data->name : "";
            $message_data->avatar = !empty($user_data) ? $user_data->avatar : "";
            $message_data->is_character_as_profile = !empty($user_data) ? $user_data->is_character_as_profile : "";

            $receiver_user_ids = NodeUserCountry::where('country',$request->country)->where('from_user_id', '!=' , $request->from_user_id)->pluck('from_user_id');

            DB::commit();
            $jsonData = array("success" => true, "receiver_user_ids" => $receiver_user_ids, "message_data" => $message_data);
            return response()->json($jsonData);
        } catch (\Exception $ex) {
            DB::rollBack();
            $jsonData = array("success" => false);
            return response()->json($jsonData);
        }
    }

    public function getJsonData(Request $request){
        $columns = array(
            1 => 'group_messages.message',
            2 => 'users_detail.name',
            3 => 'group_messages.id',
        );

        $limit = $request->input('length');
        $start = $request->input('start');
        $search = $request->input('search.value');
        $draw = $request->input('draw');
        $order = "group_messages.created_at";
        $dir = $request->input('order.0.dir');
        $adminTimezone = $this->getAdminUserTimezone();

//        try {
            $data = [];
            $filter = $request->input('filter');

            $query = DB::table('group_messages')
                    ->leftjoin('users_detail', function ($join){
                        $join->on('users_detail.user_id', '=', 'group_messages.from_user');
                    })
                    ->select('group_messages.*', 'users_detail.name as user_name');

            $totalData = count($query->get());
            $totalFiltered = $totalData;

            if (!empty($filter)) {
                $query = $query->where('group_messages.country',$filter);

                $totalFiltered = $query->count();
            }

            if (!empty($search)) {
                $query = $query->where(function($q) use ($search){
                    $q->where('users_detail.name', 'LIKE', "%{$search}%")
                        ->orWhere('group_messages.message', 'LIKE', "%{$search}%");
                });

                $totalFiltered = $query->count();
            }

            $result = $query->offset($start)
                ->limit($limit)
                ->orderBy('group_messages.id','DESC')
                ->orderBy($order, $dir)
                ->get();

            $count = 0;
            foreach($result as $res) {
                $message = "";
                if ($res->type == "text") {
                    $message = $res->message;
                }
                else if ($res->type == "file"){
                    $message = url('chat-root/'.$res->message);
                    $message = '<img onclick="showImage(`' . $message . '`)" src="' . $message . '" alt="file" class="reported-client-images pointer m-1" width="50" height="50" />';
                }
                else if ($res->type == "shop"){
                    $shop_data = json_decode($res->message,true);
                    $main_name = isset($shop_data['main_name']) ? $shop_data['main_name'] : "";
                    $shop_name = isset($shop_data['shop_name']) ? $shop_data['shop_name'] : "";
                    $message = '<img onclick="showImage(`' . $shop_data['thumbnail_image']['image'] . '`)" src="' . $shop_data['thumbnail_image']['thumb'] . '" alt="file" class="reported-client-images pointer m-1" width="50" height="50" />';
                    if ($main_name!=""){
                        $message .= "<div>$main_name</div>";
                    }
                    if ($shop_name!=""){
                        $message .= "<div>$shop_name</div>";
                    }
                }
                else if ($res->type == "shop_post"){
                    $post_data = json_decode($res->message,true);
                    if ($post_data['type'] == "image"){
                        $message = '<img onclick="showImage(`' . $post_data['post_item'] . '`)" src="' . $post_data['post_item'] . '" alt="file" class="reported-client-images pointer m-1" width="50" height="50" />';
                    }
                    else if ($post_data['type'] == "video"){
                        $message = '<img onclick="showImage(`' . $post_data['video_thumbnail'] . '`)" src="' . $post_data['video_thumbnail'] . '" alt="file" class="reported-client-images pointer m-1" width="50" height="50" />';
                    }
                    $main_name = isset($post_data['shop_data']['main_name']) ? $post_data['shop_data']['main_name'] : "";
                    $shop_name = isset($post_data['shop_data']['shop_name']) ? $post_data['shop_data']['shop_name'] : "";
                    if ($main_name!=""){
                        $message .= "<div>$main_name</div>";
                    }
                    if ($shop_name!=""){
                        $message .= "<div>$shop_name</div>";
                    }
                }

                $deleteBtn = '<a href="javascript:void(0)" role="button" onclick="removeMessage('.$res->id.')" class="btn btn-danger" data-toggle="tooltip" data-original-title="Delete"><i class="fa fa-trash"></i></a>';

                $data[$count]['checkbox_delete'] = "<div class=\"custom-checkbox custom-control\">
                    <input type=\"checkbox\" data-checkboxes=\"mygroup\" class=\"custom-control-input checkbox_id check-all-hospital\" id=\"delete_$res->id\" data-id=\"$res->id\" value=\"$res->id\" name=\"delete_message_id[]\"><label for=\"delete_$res->id\" class=\"custom-control-label\">&nbsp;</label></div>";
                $data[$count]['message'] = $message;
                $data[$count]['user_name'] = $res->user_name;

                $seconds = $res->created_at / 1000;
                $created_date = date("Y-m-d H:i:s", $seconds);
                $dateShow = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s',\Carbon\Carbon::parse($created_date), "UTC")->setTimezone($adminTimezone)->toDateTimeString();
                $created_at = \Carbon\Carbon::parse($dateShow)->format('Y-m-d H:i:s');
                $data[$count]['time'] = $created_at;
                $data[$count]['action'] = $deleteBtn;

                $count++;
            }

            $jsonData = array(
                "draw" => intval($draw),
                "recordsTotal" => intval($totalData),
                "recordsFiltered" => intval($totalFiltered),
                "data" => $data,
            );
            return response()->json($jsonData);
       /* } catch (\Exception $ex) {
            Log::info($ex);
            $jsonData = array(
                "draw" => intval($draw),
                "recordsTotal" => 0,
                "recordsFiltered" => 0,
                "data" => []
            );
            return response()->json($jsonData);
        }*/
    }

    public function removeMultipleMessage(Request $request)
    {
        $inputs = $request->all();

        $deleteIds = $inputs['ids'];
        if (!empty($deleteIds)) {
            GroupMessage::whereIn('id',$deleteIds)->delete();
        }

        $jsonData = array(
            'success' => true,
            'message' => 'Messages deleted successfully',
            'redirect' => route('admin.message.index')
        );
        return response()->json($jsonData);
    }

    public function deleteMessage($id)
    {
        return view('admin.message.delete', compact('id'));
    }

    public function destroyMessage($id)
    {
        try {
            DB::beginTransaction();

            GroupMessage::where('id',$id)->delete();

            DB::commit();
            notify()->success("Message deleted successfully", "Success", "topRight");
            return redirect()->route('admin.message.index');
        } catch (\Exception $ex) {
            DB::rollBack();
            notify()->error("Failed to delete message", "Error", "topRight");
            return redirect()->route('admin.message.index');
        }
    }

    public function saveMessage(Request $request){
        try{
            $inputs = $request->all();
            AutoChatMessage::create([
                'user_id' => $inputs['user'],
                'message' => $inputs['message'],
                'time' => $inputs['time'],
                'week_day' => $inputs['weekday'],
                'country_code' => $inputs['country'],
            ]);
            return response()->json(array('success' => true));
        }catch(\Exception $e){
            Log::info("$e");
            return response()->json(array('success' => false));
        }
    }

    public function chatBotgetJsonData(Request $request){
        $columns = array(
            0 => 'users_detail.name',
            1 => 'auto_chat_messages.message',
            2 => 'auto_chat_messages.time',
            3 => 'auto_chat_messages.week_day',
            4 => 'auto_chat_messages.created_at',
        );

        $limit = $request->input('length');
        $start = $request->input('start');
        $search = $request->input('search.value');
        $draw = $request->input('draw');
        $order = $columns[$request->input('order.0.column')];
        $dir = $request->input('order.0.dir');
        $adminTimezone = $this->getAdminUserTimezone();
        $country = $request->input('country');

        try {
            $data = [];
            $query = AutoChatMessage::select(
                'auto_chat_messages.*',
                'users_detail.name'
                )
                ->leftjoin('users_detail', function ($join) {
                    $join->on('auto_chat_messages.user_id', '=', 'users_detail.user_id')
                        ->whereNull('users_detail.deleted_at');
                })
                ->where('auto_chat_messages.country_code',$country);

            if (!empty($search)) {
                $query =  $query->where(function ($q) use ($search) {
                    $q->where('users_detail.name', 'LIKE', "%{$search}%")
                        ->orWhere('auto_chat_messages.message', 'LIKE', "%{$search}%")
                        ->orWhere('auto_chat_messages.week_day', 'LIKE', "%{$search}%");
                });
            }

            $totalData = count($query->get());
            $totalFiltered = $totalData;

            $auto_chat_messages = $query->offset($start)
                ->limit($limit)
                ->orderBy($order, $dir)
//                ->orderBy("auto_chat_messages.created_at", "DESC")
                ->get();

            $count = 0;
            foreach($auto_chat_messages as $auto_chat_message){
                $data[$count]['user_name'] = $auto_chat_message->name;
                $data[$count]['message'] = $auto_chat_message->message;
                $data[$count]['time'] = $this->formatDateTimeCountryWise($auto_chat_message->time,$adminTimezone,'h:i a');
                $data[$count]['week_day'] = $auto_chat_message->week_day;

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

}
