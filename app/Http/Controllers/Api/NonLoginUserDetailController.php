<?php

namespace App\Http\Controllers\Api;

use App\Models\CardLevel;
use App\Models\DefaultCardsRives;
use App\Models\GroupMessage;
use App\Models\UserCardLevel;
use App\Models\UserCards;
use App\Models\UserDetail;
use Illuminate\Support\Facades\Auth;
use Validator;
use Carbon\Carbon;
use App\Models\Notice;
use App\Models\Message;
use App\Models\Category;
use Illuminate\Http\Request;
use App\Models\CategoryTypes;
use App\Models\NonLoginNotice;
use App\Models\CategorySettings;
use App\Models\NonLoginUserDetail;
use App\Models\UserHiddenCategory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Storage;

class NonLoginUserDetailController extends Controller
{
    public function saveDetails(Request $request)
    {
        $inputs = $request->all();
        try {
            $validator = Validator::make($request->all(), [
                'device_id' => 'required',
               /*  'username' => 'required',
                'gender' => 'required', */
            ]);
            if ($validator->fails()) {
                return $this->sendCustomErrorMessage($validator->errors()->toArray(), 422);
            }

            $device_id = $inputs['device_id'];
            $country = $inputs['country'] ?? '';
            $updateData['last_access'] = Carbon::now();
            if(isset($inputs['username'])){
                $updateData['username'] = $inputs['username'];
            }
            if(isset($inputs['gender'])){
                $updateData['gender'] = $inputs['gender'];
            }
            if(isset($inputs['device_token'])){
                $updateData['device_token'] = $inputs['device_token'];
            }

            if ($request->hasFile('avatar')) {
                $profileFolder = config('constant.profile');
                if (!Storage::exists($profileFolder)) {
                    Storage::makeDirectory($profileFolder);
                }
                $avatar = Storage::disk('s3')->putFile($profileFolder, $request->file('avatar'),'public');
                $fileName = basename($avatar);
                $updateData['avatar'] = $profileFolder . '/' . $fileName;
            }

            $user = NonLoginUserDetail::updateOrCreate(
                ['device_id' => $device_id],
                $updateData
            );

            //if ($user->wasRecentlyCreated === true) {
            if (isset($inputs['is_new']) && $inputs['is_new'] == true) {
                UserHiddenCategory::where('user_id',$user->id)->where('user_type',UserHiddenCategory::NONLOGIN)->delete();
                if(!empty($country)){
                    $hiddenCategory = CategorySettings::where('country_code',$country)->where('is_hidden',1)->get();
                }else{
                    $hiddenCategory = Category::select('category.id as category_id')->where('category_type_id',CategoryTypes::SHOP)->where('is_hidden',1)->get();
                }
                if(!empty($hiddenCategory) && count($hiddenCategory) > 0){
                    foreach($hiddenCategory as $category){
                        UserHiddenCategory::firstOrCreate([
                            'category_id' => $category->category_id,
                            'user_id' => $user->id,
                            'user_type' => UserHiddenCategory::NONLOGIN
                        ]);
                    }
                }
            }
            return $this->sendSuccessResponse(Lang::get('messages.user-profile.update-success'), 200, $user);

        } catch (\Exception $e) {
            Log::info($e);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function getDetails(Request $request)
    {
        $inputs = $request->all();
        try{
            $validator = Validator::make($request->all(), [
                'device_id' => 'required',
            ]);
            if ($validator->fails()) {
                return $this->sendCustomErrorMessage($validator->errors()->toArray(), 422);
            }
            $user = NonLoginUserDetail::where('device_id',$inputs['device_id'])->first();
            return $this->sendSuccessResponse(Lang::get('messages.user-profile.success'), 200, $user);
        } catch (\Exception $e) {
            Log::info($e);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function getMessages(Request $request)
    {
        $inputs = $request->all();

        try{
            $validation = Validator::make($request->all(), [
                'user_id' => 'required',
            ]);

            if ($validation->fails()) {
                return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
            }

            $user_id = $inputs['user_id'];

           /*  $chat = Message::where(function($q) use ($user_id){
                    $q->where('from_user_id',$user_id)->orWhere('to_user_id',$user_id);
                })
                ->get(); */

      /*   $chatQuery = DB::select("select max(message_id) message_id from
         (select messages.message,messages.to_user_id user_id, messages.to_user_id, messages.from_user_id,max(messages.id) message_id
         from messages
         where messages.from_user_id=?
         group by messages.to_user_id
         union distinct
         (select messages.message,messages.from_user_id user_id, messages.to_user_id, messages.from_user_id,max(messages.id) message_id
         from messages  where messages.to_user_id = ?
         group by messages.from_user_id)) chatMsg
         group by chatMsg.user_id
         order by message_id desc", [$user_id,$user_id])
         ; */


        $chatQuery = DB::query()->select(DB::raw("max(message_id) message_id"))
            ->fromSub(function ($query) use ($user_id) {
                $query->select('messages.to_user_id as user_id',DB::raw('max(messages.id) message_id'))->from('messages')->where('from_user_id',$user_id)->orderBy('id','desc')->groupBy('to_user_id')
                ->union(
                    DB::table('messages')->select('messages.from_user_id as user_id',DB::raw('max(messages.id) message_id'))->where('to_user_id',$user_id)->orderBy('id','desc')->groupBy('from_user_id')
                );
            }, 'a')
            ->groupBy('user_id')
            ->pluck('message_id');

        $chatData = Message::whereIn('id',$chatQuery)->paginate(config('constant.pagination_count'), "*", "chat_list_page");

        return $this->sendSuccessResponse(Lang::get('messages.user-profile.success'), 200, $chatData);
        }catch (\Exception $e) {
            Log::info($e);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function getNotice(Request $request)
    {
        $inputs = $request->all();
        try{
            $validation = Validator::make($request->all(), [
                'user_id' => 'required',
            ]);

            if ($validation->fails()) {
                return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
            }

            $user_id = $inputs['user_id'];
            $language_id = $inputs['language_id'] ?? 4;
           // $timezone = $inputs['timezone'] ?? 'UTC';

            $notices = NonLoginNotice::where('user_id',$user_id)->whereNotIn('notify_type',[Notice::USER_MISSED_CARD])->orderBy('id', 'desc')->paginate(config('constant.pagination_count'), "*", "notices_page");
            foreach ($notices as $notice) {
                $notice->time_difference = $notice ? timeAgo($notice->created_at, $language_id)  : "null";
                if ($notice->notify_type == Notice::USER_MISSED_CARD) {

                    if ($notice->sub_title) {
                        $next_level_key = "language_$language_id.$notice->notify_type";
                        $notice->heading = __("messages.$next_level_key", ['dayCount' => $notice->sub_title]);
                    } else {
                        $notice->heading = $notice->title;
                    }
                    $notice->title = '';
                    $notice->description = '';
                    $notice->sub_title = '';
                }
            }
            return $this->sendSuccessResponse(Lang::get('messages.messages.delete-notice'), 200, compact('notices'));
        } catch (\Exception $e) {
            Log::info($e);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function getGroupChat(Request $request){
        $country = $request->country;
        $timezone = isset($request->timezone) ? $request->timezone : "Asia/Seoul";
        try {
            $messages = DB::table('group_messages')->where('country',$country)
                ->orderBy('id','DESC')
                ->select('group_messages.*')
                ->paginate(config('constant.chat_pagination_count'), "*", "group_chat_list_page");

            $messages->getCollection()->transform(function($item) {
                $item->from_user_id = $item->from_user;
                return $item;
            });

            foreach ($messages as $key => &$message){
                $utcDateTime = Carbon::createFromTimestampMs($message->created_at, 'UTC');
                $convertedDateTime = $utcDateTime->setTimezone($timezone);
                $convertedMilliseconds = $convertedDateTime->timestamp * 1000;
                $message->created_at = $convertedMilliseconds;
                $message->time = $convertedMilliseconds;

                $utcDateTime = Carbon::createFromTimestampMs($message->updated_at, 'UTC');
                $convertedDateTime = $utcDateTime->setTimezone($timezone);
                $convertedMilliseconds = $convertedDateTime->timestamp * 1000;
                $message->updated_at = $convertedMilliseconds;

                $message->message = ($message->type=="file") ? url('chat-root/'.$message->message) : $message->message;

                $user_data = UserDetail::where('user_id',$message->from_user_id)->select(['name','avatar','is_character_as_profile'])->first();
                $message->name = !empty($user_data) ? $user_data->name : "";
                $message->avatar = !empty($user_data) ? $user_data->avatar : "";
                $message->is_character_as_profile = !empty($user_data) ? $user_data->is_character_as_profile : "";

                if ($message->reply_of!=null) {
                    $parent_message_data = DB::table('group_messages')->where('country', $country)->where('id', $message->reply_of)->first();
                    if (!empty($parent_message_data)) {
                        $parent_user_data = UserDetail::where('user_id', $parent_message_data->from_user)->select(['name', 'avatar'])->first();

                        $message->parent_message = ($parent_message_data->type == "file") ? url('chat-root/' . $parent_message_data->message) : $parent_message_data->message;
                        $message->parent_message_user = !empty($parent_user_data) ? $parent_user_data->name : "";

                        $utcDateTime = Carbon::createFromTimestampMs($parent_message_data->created_at, 'UTC');
                        $convertedDateTime = $utcDateTime->setTimezone($timezone);
                        $convertedMilliseconds = $convertedDateTime->timestamp * 1000;
                        $message->parent_message_time = $convertedMilliseconds;
                    }
                }

                $appliedCard = DefaultCardsRives::join('user_cards', 'user_cards.default_cards_riv_id', 'default_cards_rives.id')
                    ->select('default_cards_rives.*','user_cards.id as user_card_id','user_cards.active_level','user_cards.card_level_status')
                    ->where(['user_cards.user_id' => $message->from_user,'user_cards.is_applied' => 1])
                    ->first();
                if (!$appliedCard){
                    $message->background_thumbnail_url = "";
                    $message->character_thumbnail_url = "";
                }
                else {
                    if ($appliedCard->active_level == CardLevel::DEFAULT_LEVEL) {
                        $message->background_thumbnail_url = $appliedCard->background_thumbnail_url ?? "";
                        if($appliedCard->card_level_status == UserCards::NORMAL_STATUS) {
                            $message->character_thumbnail_url = $appliedCard->character_thumbnail_url ?? "";
                        }else{
                            $defaultCardStatus = $appliedCard->cardLevelStatusThumb()->where('card_level_status',$appliedCard->card_level_status)->where('card_level_id',$appliedCard->active_level)->first();
                            $message->character_thumbnail_url = $defaultCardStatus->character_thumb_url ?? "";
                        }
                    } else {
                        $cardLevelData = UserCardLevel::where('user_card_id',$appliedCard->user_card_id)->where('card_level',$appliedCard->active_level)->first();
                        $levelCard = $appliedCard->cardLevels()->firstWhere('card_level', $appliedCard->active_level);
                        if ($levelCard) {
                            $card_level_status = $cardLevelData->card_level_status ?? UserCards::NORMAL_STATUS;
                            $message->background_thumbnail_url = $levelCard->background_thumbnail_url ?? '';
                            if($card_level_status == UserCards::NORMAL_STATUS) {
                                $message->character_thumbnail_url = $levelCard->character_thumbnail_url ?? '';
                            }else{
                                $defaultCardStatus = $appliedCard->cardLevelStatusThumb()->where('card_level_status',$card_level_status)->where('card_level_id',$appliedCard->active_level)->first();
                                $message->character_thumbnail_url = $defaultCardStatus->character_thumb_url ?? '';
                            }
                        }
                    }
                }

                $liked_by_names = DB::table('liked_group_messages')
                    ->join('users_detail','users_detail.user_id','liked_group_messages.user_id')
                    ->where('liked_group_messages.message_id', $message->id)
                    ->pluck('users_detail.name')
                    ->toArray();
                $message->liked_by = $liked_by_names;
            }

            $messages_data = $messages->toArray();
            $res_data['total_data'] = $messages_data['total'];
            $res_data['total_page'] = ceil($res_data['total_data'] / config('constant.chat_pagination_count'));
            $res_data['current_page'] = $messages_data['current_page'];
            $res_data['data_per_page'] = config('constant.chat_pagination_count');
            $res_data['data'] = $messages_data['data'];
            $res_data['cnt_reply'] = 0;

            return $this->sendGroupChatResponse($res_data);
        } catch (\Throwable $th) {
            Log::info($th);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }
}
