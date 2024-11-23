<?php

namespace App\Http\Controllers\Api;

use Validator, Auth;
use App\Models\CardLevel;
use App\Models\UserCards;
use App\Models\UserDetail;
use Illuminate\Http\Request;
use App\Models\UserCoinHistory;
use App\Models\CardSoldFollowers;
use Illuminate\Support\Facades\DB;
use App\Models\UserCardSoldDetails;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Lang;

class SoldCardController extends Controller
{
    public function soldRequestCard(Request $request)
    {
        $inputs = $request->all();
        try {
            $validation = Validator::make($request->all(), [
                'card_id' => 'required',
            ], [], [
                'card_id' => 'Card Id',
            ]);

            if ($validation->fails()) {
                return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
            }

            DB::beginTransaction();
            $user = Auth::user();

            $userCards = UserCards::where('id', $inputs['card_id'])->where('user_id',$user->id)->first();

            if(empty($userCards)){
                return $this->sendSuccessResponse(Lang::get('messages.cards.card-not-own'), 400);
            }

            if($userCards->active_level != CardLevel::LAST_LEVEL){
                return $this->sendFailedResponse(Lang::get('messages.cards.card-sold-denied'), 400);
            }

            if($userCards->status == UserCards::SOLD_CARD_STATUS){
                return $this->sendFailedResponse(Lang::get('messages.cards.already-sold'), 400);
            }

            $soldCard = UserCardSoldDetails::create(['card_id' => $inputs['card_id'], 'card_level' => $userCards->active_level]);
//,'active_level' => CardLevel::DEFAULT_LEVEL
            UserCards::where('id', $inputs['card_id'])->update(['status' => UserCards::SOLD_CARD_STATUS]);

            $userOwnCards = UserCards::where('id', $inputs['card_id'])->get();

            $userDetail = DB::table('users_detail')->where('user_id',$user->id)->first();
            if(!empty($userDetail->recommended_by)){
                CardSoldFollowers::create([
                    'sold_id' => $soldCard->id,
                    'user_id' => $userDetail->recommended_by,
                    'follower_level' => CardSoldFollowers::FOLLOWERS,
                ]);

                $grandUserDetail = DB::table('users_detail')->where('user_id',$userDetail->recommended_by)->first();
                if(!empty($grandUserDetail->recommended_by)){
                    CardSoldFollowers::create([
                        'sold_id' => $soldCard->id,
                        'user_id' => $grandUserDetail->recommended_by,
                        'follower_level' => CardSoldFollowers::GRAND_FOLLOWERS,
                    ]);

                    $greatGrandUserDetail = DB::table('users_detail')->where('user_id',$grandUserDetail->recommended_by)->first();
                    if(!empty($greatGrandUserDetail->recommended_by)){
                        CardSoldFollowers::create([
                            'sold_id' => $soldCard->id,
                            'user_id' => $greatGrandUserDetail->recommended_by,
                            'follower_level' => CardSoldFollowers::GREAT_GRAND_FOLLOWERS,
                        ]);
                    }
                }
            }

            $followersCoin = getUserSoldCardPrice($user->id);

            UserCoinHistory::create([
                'user_id' => $user->id,
                'amount' => CardSoldFollowers::SOLD_CARD_COIN + $followersCoin,
                'type' => UserCoinHistory::SOLD_CARD,
                'entity_id' => $inputs['card_id'],
            ]);

            CardSoldFollowers::where('user_id',$user->id)
                ->where('status','0')
                ->update(['status' => 1]);

            DB::commit();
            return $this->sendSuccessResponse(Lang::get('messages.general.success'), 200, []);
        } catch (\Exception $e) {
            Log::info($e);
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }
}
