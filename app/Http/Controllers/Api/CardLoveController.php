<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CardLevel;
use App\Models\UserCardLevel;
use App\Models\UserCardLog;
use App\Models\UserCards;
use App\Models\NonLoginLoveDetails;
use App\Models\UserFeedLog;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class CardLoveController extends Controller
{
    public function updateLove(Request $request)
    {
        $inputs = $request->all();
        $user = Auth::user();
        try {
            $validator = Validator::make($request->all(), [
                'type' => 'required|in:feed,open_app'
            ]);
            if ($validator->fails()) {
                return $this->sendCustomErrorMessage($validator->errors()->toArray(), 422);
            }
            DB::beginTransaction();
            $type = $inputs['type'];

            $appliedCard = DB::table('user_cards')->where('user_id',$user->id)->where('is_applied',1)->first();
            if($type == UserCardLog::FEED){
                $feedCount = UserCardLog::where('user_id',$user->id)->where('card_log',UserCardLog::FEED)->whereDate('created_at',Carbon::now())->count();
                if($feedCount < UserCardLog::ALLOW_FEED) {
                    UserCards::whereId($appliedCard->id)->update(['love_count' => DB::raw('love_count + 1')]);
                    if($appliedCard->active_level == CardLevel::DEFAULT_LEVEL) {
                        UserCards::whereId($appliedCard->id)->update(['card_level_status' => UserCards::HAPPY_STATUS]);
                    }else{
                        UserCardLevel::where('user_card_id',$appliedCard->id)->update(['card_level_status' => UserCards::HAPPY_STATUS]);
                    }

                    $love_count = UserCards::where('user_id',$user->id)->where('is_applied',1)->pluck('love_count')->first();
                    UserCardLog::create([
                        'user_id' => $user->id,
                        'card_id' => $appliedCard->id,
                        'card_log' => UserCardLog::FEED,
                        'created_at' => Carbon::now(),
                        'love_count' => (empty($love_count)) ? 0 : $love_count
                    ]);

                    UserFeedLog::updateOrCreate([
                        'user_id' => $user->id,
                    ],[
                        'card_id' => $appliedCard->id,
                        'feed_time' => Carbon::now()
                    ]);
                }
            }
            elseif($type == UserCardLog::OPEN_APP){
                $feedCount = UserCardLog::where('user_id',$user->id)->where('card_id',$appliedCard->id)->where('card_log',UserCardLog::OPEN_APP)->whereDate('created_at',Carbon::now())->count();
                if($feedCount < 1) {
                    UserCards::whereId($appliedCard->id)->update(['love_count' => DB::raw('love_count + 1')]);
                    UserCardLog::create([
                        'user_id' => $user->id,
                        'card_id' => $appliedCard->id,
                        'card_log' => UserCardLog::OPEN_APP,
                        'created_at' => Carbon::now()
                    ]);
                }
            }

            $appliedCard = DB::table('user_cards')->where('user_id',$user->id)->where('is_applied',1)->first();
            $cardLevel = DB::table('card_levels')->whereRaw("(start <= ".$appliedCard->love_count." AND end >= ".$appliedCard->love_count.")")->first();

            if($cardLevel->id > $appliedCard->active_level){
                UserCards::whereId($appliedCard->id)->update(['active_level' => $cardLevel->id]);
            }

            DB::commit();
            return $this->sendSuccessResponse(Lang::get('messages.general.success'), 200, $appliedCard);
        }catch (\Exception $e){
            Log::info($e);
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function giveLoveNonLogin(Request $request)
    {
        $inputs = $request->all();
        try {
            $validator = Validator::make($request->all(), [
                'type' => 'required|in:feed,open_app',
                'device_id' => 'required'
            ]);
            if ($validator->fails()) {
                return $this->sendCustomErrorMessage($validator->errors()->toArray(), 422);
            }

            DB::beginTransaction();
            $type = $inputs['type'];
            $device_id = $inputs['device_id'];

            if($type == UserCardLog::FEED){
                $feedCount = NonLoginLoveDetails::where('device_id',$device_id)->where('card_log',UserCardLog::FEED)->whereDate('created_at',Carbon::now())->count();
                if($feedCount < UserCardLog::ALLOW_FEED) {
                    NonLoginLoveDetails::create([
                        'device_id' => $device_id,
                        'card_log' => UserCardLog::FEED,
                        'created_at' => Carbon::now()
                    ]);
                }
            }elseif($type == UserCardLog::OPEN_APP){
                $feedCount = NonLoginLoveDetails::where('device_id',$device_id)->where('card_log',UserCardLog::OPEN_APP)->whereDate('created_at',Carbon::now())->count();
                if($feedCount < 1) {
                    NonLoginLoveDetails::create([
                        'device_id' => $device_id,
                        'card_log' => UserCardLog::OPEN_APP,
                        'created_at' => Carbon::now()
                    ]);
                }
            }
            DB::commit();
            return $this->sendSuccessResponse(Lang::get('messages.general.success'), 200, []);
        } catch (\Exception $e){
            Log::info($e);
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }
}
