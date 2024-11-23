<?php

namespace App\Http\Controllers\Api;

use App\Models\GifticonDetail;
use App\Models\UserReferral;
use App\Models\UserReferralDetail;
use DB;
use Auth;
use Lang;
use Storage;
use Exception;
use Validator;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Config;
use App\Models\CardLevel;
use App\Models\CardMusic;
use App\Models\UserCards;
use App\Models\UserDetail;
use App\Models\UserPoints;
use App\Models\UserCardLog;
use App\Models\DefaultCards;
use Illuminate\Http\Request;
use App\Models\UserCardLevel;
use App\Models\UserBankDetail;
use App\Models\UserCoinHistory;
use App\Models\CardSoldFollowers;
use App\Models\DefaultCardsRives;
use Illuminate\Http\JsonResponse;
use App\Models\UserMissedFeedCard;
use App\Models\NonLoginLoveDetails;
use App\Models\UserCardSellRequest;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Models\UserCardResetHistory;
use App\Models\UserCardAppliedHistory;

class CardsController extends Controller
{
    public function getNonLoginCards(Request $request)
    {
        $inputs = $request->all();
        try {
            $level = 1;

            $selectable = DB::raw("(CASE WHEN (`start` <=" . $level . " OR end <= " . $level . ") THEN 1 ELSE 0 END) as is_selectable");

            $defaultCards = DefaultCards::select('default_cards.*', $selectable)->with('defaultCardsRiv')->get();
            return $this->sendSuccessResponse(Lang::get('messages.general.success'), 200, $defaultCards);

        } catch (Exception $e) {
            Log::info($e);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function getCards(Request $request)
    {
        $inputs = $request->all();
        $user = Auth::user();
        try {
            $getUserDetail = UserDetail::where('user_id', $user->id)->first();
            $level = $getUserDetail->level;

            $selectable = DB::raw("(CASE WHEN (`start` <=" . $level . " OR (end <= " . $level . ")) THEN 1 ELSE 0 END) as is_selectable");

            //$defaultCards = DefaultCards::select('default_cards.*', $selectable)->with('defaultCardsRiv')->get();
            $defaultCards = DefaultCards::select('default_cards.*', $selectable)->with([
                'defaultCardsRiv' => function ($q) {
                        $q->leftjoin('card_level_details', function ($join) {
                            $join->on('card_level_details.main_card_id', '=', 'default_cards_rives.id')
                                ->where('card_level_details.card_level', CardLevel::MIDDLE_LEVEL);
                        })
                        ->select(
                            'default_cards_rives.*',
                            'card_level_details.background_rive',
                            'card_level_details.character_rive',
                            'card_level_details.background_thumbnail',
                            'card_level_details.character_thumbnail'
                        )
                        ->orderBy('default_cards_rives.order', 'asc');
                }
            ])->get();
            return $this->sendSuccessResponse(Lang::get('messages.general.success'), 200, $defaultCards);

        } catch (Exception $e) {
            Log::info($e);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function selectCards(Request $request)
    {
        $inputs = $request->all();
        $user = Auth::user();
        try {
            DB::beginTransaction();
            $getUserDetail = UserDetail::where('user_id', $user->id)->first();
            $card_limit = $getUserDetail->card_number;

            $validation = Validator::make($request->all(), [
                'riv_id' => 'required',
                'default_card_id' => 'required',
            ], [], [
                'riv_id' => 'Rive Id',
                'default_card_id' => 'Default Card Id',
            ]);

            if ($validation->fails()) {
                return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
            }

            $user_id = $user->id;
            $default_cards_id = $inputs['default_card_id'];
            $default_cards_riv_id = $inputs['riv_id'];

            $getRiv = DefaultCardsRives::where('id', $default_cards_riv_id)->first();
            $cardsAvailable = UserCards::where(['user_id' => $user_id])->count();

            if ($card_limit == $cardsAvailable) {
                return $this->sendSuccessResponse(Lang::get('messages.cards.card-limit'), 400);
            } else {
                $updateCard = UserCards::updateOrCreate([
                    'user_id' => $user_id,
                    'default_cards_id' => $getRiv->default_card_id,
                    'default_cards_riv_id' => $default_cards_riv_id,
                ]);

                if ($getRiv->cardLevels()->count()) {
                    foreach ($getRiv->cardLevels as $card) {
                        UserCardLevel::updateOrCreate([
                            'user_card_id' => $updateCard->id,
                            'card_level' => $card->card_level,
                        ]);
                    }
                } else {
                    $other_level = CardLevel::where('id', '!=', CardLevel::DEFAULT_LEVEL)->get();
                    foreach ($other_level as $level) {
                        UserCardLevel::updateOrCreate([
                            'user_card_id' => $updateCard->id,
                            'card_level' => $level->id,
                        ]);
                    }
                }

            }
            DB::commit();
            return $this->sendSuccessResponse(Lang::get('messages.general.success'), 200);
        } catch (Exception $e) {
            Log::info($e);
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function changeCardRiv(Request $request)
    {
        $inputs = $request->all();
        $user = Auth::user();
        try {
            $getUserDetail = UserDetail::where('user_id', $user->id)->first();
            $card_limit = $getUserDetail->card_number;

            $validation = Validator::make($request->all(), [
                'id' => 'required',
                'default_card_id' => 'required',
            ], [], [
                'id' => 'Card Id',
                'default_card_id' => 'Default Card Id',
            ]);

            if ($validation->fails()) {
                return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
            }

            $user_id = $user->id;
            $default_cards_id = $inputs['default_card_id'];
            $default_cards_riv_id = $inputs['id'];

            $is_your_own_card = UserCards::where(['user_id' => $user_id, 'default_cards_id' => $default_cards_id, 'default_cards_riv_id' => $default_cards_riv_id])->first();

            if ($is_your_own_card) {

                if ($request->hasFile('background_rive')) {
                    $backgroundFolder = config('constant.background_rive');
                    if (!Storage::exists($backgroundFolder)) {
                        Storage::makeDirectory($backgroundFolder);
                    }

                    $originalName = $request->file('background_rive')->getClientOriginalName();
                    $backgroundRiv = Storage::disk('s3')->putFileAs($backgroundFolder, $request->file('background_rive'), $originalName, 'public');
                    $fileName = basename($backgroundRiv);
                    $data['background_riv'] = $backgroundFolder . '/' . $fileName;
                }

                if ($request->hasFile('character_rive')) {
                    $characterFolder = config('constant.character_rive');
                    if (!Storage::exists($characterFolder)) {
                        Storage::makeDirectory($characterFolder);
                    }
                    $originalName = $request->file('character_rive')->getClientOriginalName();
                    $characterRiv = Storage::disk('s3')->putFileAs($characterFolder, $request->file('character_rive'), $originalName, 'public');
                    $fileName = basename($characterRiv);
                    $data['character_riv'] = $characterFolder . '/' . $fileName;
                }

                UserCards::where([
                    'user_id' => $user_id,
                    'default_cards_id' => $default_cards_id,
                    'default_cards_riv_id' => $default_cards_riv_id,
                ])->update($data);

                $userOwnCards = UserCards::where('user_id', $user_id)->get();

                return $this->sendSuccessResponse(Lang::get('messages.general.success'), 200, $userOwnCards);
            } else {
                return $this->sendSuccessResponse(Lang::get('messages.cards.card-not-own'), 400);
            }

        } catch (Exception $e) {
            Log::info($e);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function getWithoutLoginUserPoints(Request $request)
    {
        $inputs = $request->all();
        try {
            $validator = Validator::make($request->all(), [
                'device_id' => 'required'
            ]);
            if ($validator->fails()) {
                return $this->sendCustomErrorMessage($validator->errors()->toArray(), 422);
            }

            $device_id = $inputs['device_id'];
            $responseData = [];
            $level = 1;
            $selectable = DB::raw("(CASE WHEN (`start` <=" . $level . " OR (end <= " . $level . " )) THEN 1 ELSE 0 END) as is_selectable");
            $getCards = DefaultCardsRives::select(
                'default_cards_rives.*',
                'dc.start',
                'dc.end',
                'card_level_details.background_rive',
                'card_level_details.character_rive',
                'card_level_details.background_thumbnail',
                'card_level_details.character_thumbnail',
                $selectable
            )
                ->leftjoin('card_level_details', function ($join) {
                    $join->on('card_level_details.main_card_id', '=', 'default_cards_rives.id')
                        ->where('card_level_details.card_level', CardLevel::MIDDLE_LEVEL);
                })
                ->leftJoin('default_cards as dc', 'dc.id', 'default_cards_rives.default_card_id')
                ->orderBy('default_cards_rives.id', 'asc')
                ->limit(10)
                ->get();
            $last_feed = NonLoginLoveDetails::where('device_id', $device_id)->where('card_log', UserCardLog::FEED)->whereDate('created_at', Carbon::now())->orderBy('created_at', 'DESC')->first();
            $totalLove = NonLoginLoveDetails::where('device_id', $device_id)->count();

            $checkCountLove = $totalLove;
            if($checkCountLove > CardLevel::LAST_LEVEL_COUNT){
                $checkCountLove = CardLevel::LAST_LEVEL_COUNT;
            }
            $feedCount = NonLoginLoveDetails::where('device_id', $device_id)->where('card_log', UserCardLog::FEED)->whereDate('created_at', Carbon::now())->count();
            $responseData['feed_count'] = $feedCount;
            $responseData['remaining_feed_count'] = UserCardLog::ALLOW_FEED - $feedCount;

            $checkDate = Carbon::now()->subHour();
            $currentLevel = DB::table('card_levels')
                ->whereRaw("(card_levels.start <= ".$checkCountLove." AND card_levels.end >= ".$checkCountLove.")")
                ->first();

            $per = ($currentLevel->end - $currentLevel->start);
            $percentage = ((($checkCountLove - $currentLevel->start) / $per) * 100);

            $responseData['active_level'] = $currentLevel->id;

            $responseData['total_sold_card_coin'] = number_format(CardSoldFollowers::SOLD_CARD_COIN,0);
            $responseData['total_card_coin'] = number_format(0,0);

            $responseData['user_points'] = [
                "start" => 0,
                "end" => 0,
                "percentage" => 0,
            ];
            $responseData['love_details'] = [
                "start" => $currentLevel->start,
                "end" => $currentLevel->end,
                "percentage" => ($percentage > 100) ? 100 : round($percentage, 2),
                "love_count" => $totalLove
            ];
            $defaultCard = getDefaultCard();

            $card_level_status = UserCards::NORMAL_STATUS;

            if(!empty($last_feed) && Carbon::parse($checkDate)->lt($last_feed->created_at)){
                $card_level_status = UserCards::HAPPY_STATUS;
            }

            if ($currentLevel->id == CardLevel::DEFAULT_LEVEL) {
                $responseData['display_feeding_rive_url'] = $defaultCard->feeding_rive_url ?? '';
                $responseData['display_background_rive_url'] = $defaultCard->background_rive_url ?? '';
                $responseData['display_background_thumbnail_url'] = $defaultCard->background_thumbnail_url ?? '';
                $responseData['display_character_thumbnail_url'] = $defaultCard->character_thumbnail_url ?? '';
                if ($card_level_status == UserCards::NORMAL_STATUS) {
                    $responseData['display_character_rive_url'] = $defaultCard->character_rive_url ?? '';
                } else {
                    $defaultCardStatus = $defaultCard->cardLevelStatusRive()->where('card_level_status', $card_level_status)->where('card_level_id', $currentLevel->id)->first();
                    $responseData['display_character_rive_url'] = $defaultCardStatus->character_riv_url ?? '';
                }
            } else {

                $levelCard = $defaultCard->cardLevels()->firstWhere('card_level', $currentLevel->id);
                if ($levelCard) {
                    $responseData['display_feeding_rive_url'] = $levelCard->feeding_rive_url ?? '';
                    $responseData['display_background_rive_url'] = $levelCard->background_rive_url ?? '';
                    $responseData['display_background_thumbnail_url'] = $levelCard->background_thumbnail_url ?? '';
                    $responseData['display_character_thumbnail_url'] = $levelCard->character_thumbnail_url ?? '';
                    if ($card_level_status == UserCards::NORMAL_STATUS) {
                        $responseData['display_character_rive_url'] = $levelCard->character_rive_url ?? '';
                    } else {
                        $defaultCardStatus = $defaultCard->cardLevelStatusRive()->where('card_level_status', $card_level_status)->where('card_level_id', $currentLevel->id)->first();
                        $responseData['display_character_rive_url'] = $defaultCardStatus->character_riv_url ?? '';
                    }
                }
            }

            $responseData['level_name'] = "LV0";
            $responseData['last_feed'] = $last_feed;
            $responseData['points'] = 0;
            $responseData['today_comment_exp'] = 0;
            $responseData['count_days'] = 0;
            $responseData['level'] = $level;
            $responseData['all_cards'] = $getCards;

            $defaultCard = getDefaultCard();
            $music_files = CardMusic::where('card_id',$defaultCard->id)->get();
            $responseData['music_files'] = $music_files->pluck('music_file_url');

            return $this->sendSuccessResponse(Lang::get('messages.user-profile.update-success'), 200, $responseData);
        } catch (Exception $e) {
            Log::info($e);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function getUserPoints(Request $request, $user_id = '')
    {
        if ($user_id) {
            $user = User::find($user_id);
        } else {
            $user = Auth::user();
        }
        try {
            if ($user) {
                $userPoints = UserDetail::select('id', 'user_id', 'name', 'language_id', 'points_updated_on', 'points', 'level', 'count_days', 'card_number', 'is_character_as_profile')->where('user_id', $user->id)->first();

                //$userPoints->user_applied_card = $user->user_applied_card;
                $today_comment_exp = UserPoints::where('user_id', $user->id)->whereIn('entity_type', [UserPoints::COMMENT_ON_COMMUNITY_POST, UserPoints::COMMENT_ON_REVIEW_POST])->whereDate('created_at', Carbon::now()->format('Y-m-d'))->sum('points');
                $userPoints->avatar = $user->avatar;
                $userPoints->today_comment_exp = (int)$today_comment_exp;
                $userPoints->total_followers = UserDetail::where('recommended_by', $user->id)->count();

                $cardPrice = getUserSoldCardPrice($user->id) + CardSoldFollowers::SOLD_CARD_COIN;
                $userPoints->total_sold_card_coin = number_format($cardPrice,0);

                $userPoints->total_card_coin = number_format(getUserTotalCoin($user->id),0);

                $level = $userPoints->level;
                $selectable = DB::raw("(CASE WHEN (`start` <=" . $level . " OR (end <= " . $level . " )) THEN 1 ELSE 0 END) as is_selectable");

                $getCards = DefaultCardsRives::select(
                    'default_cards_rives.*',
                    'dc.start',
                    'dc.end',
                    'card_level_details.background_rive',
                    'card_level_details.character_rive',
                    'card_level_details.background_thumbnail',
                    'card_level_details.character_thumbnail',
                    $selectable
                )
                    ->leftjoin('card_level_details', function ($join) {
                        $join->on('card_level_details.main_card_id', '=', 'default_cards_rives.id')
                            ->where('card_level_details.card_level', CardLevel::MIDDLE_LEVEL);
                    })
                    ->leftJoin('default_cards as dc', 'dc.id', 'default_cards_rives.default_card_id')
                    ->orderBy('dc.id', 'asc')
                    ->orderBy('order', 'asc')
                    ->get();

                $userPoints->all_cards = $getCards;

                $userPoints->recommended_code = $user->recommended_code;

                //$config = Config::where('key', Config::GIVE_REFERRAL_EXP)->first();
                $userPoints->give_referral_exp = 0; // $config ? (int)filter_var($config->value, FILTER_SANITIZE_NUMBER_INT) : 0;


                $activeCardId = $userPoints->user_applied_card ? $userPoints->user_applied_card->id : '';
                $card_level_status = $userPoints->user_applied_card ? $userPoints->user_applied_card->card_level_status : UserCards::HAPPY_STATUS;
                $default_cards_riv_id = $userPoints->user_applied_card ? $userPoints->user_applied_card->default_cards_riv_id : '';
                $activeLevelId = $userPoints->user_applied_card ? $userPoints->user_applied_card->active_level : 1;
                $love_count = $userPoints->user_applied_card ? $userPoints->user_applied_card->love_count : 0;
                $cardLevelDetail = CardLevel::where('id', $activeLevelId)->first();
                $userCardsDetail = UserCards::whereId($activeCardId)->first();


                $defaultCard = $defaultCardData = getDefaultCard();
                // Change Rive based on Level
                if ($default_cards_riv_id) {
                    $defaultCard = DefaultCardsRives::where('id', $default_cards_riv_id)->first();
                }

                if ($activeLevelId == CardLevel::DEFAULT_LEVEL) {
                    $userPoints->display_feeding_rive_url = $defaultCard->feeding_rive_url ?? '';
                    $userPoints->display_background_rive_url = $defaultCard->background_rive_url ?? '';
                    $userPoints->display_background_thumbnail_url = $defaultCard->background_thumbnail_url ?? '';
                    $userPoints->display_character_thumbnail_url = $defaultCard->character_thumbnail_url ?? '';
                    if ($card_level_status == UserCards::NORMAL_STATUS) {
                        $userPoints->display_character_rive_url = $defaultCard->character_rive_url ?? '';
                    } else {
                        $defaultCardStatus = $defaultCard->cardLevelStatusRive()->where('card_level_status', $card_level_status)->where('card_level_id', $activeLevelId)->first();
                        $userPoints->display_character_rive_url = $defaultCardStatus->character_riv_url ?? '';
                    }
                    $userPoints->display_character_rive_status = $card_level_status;
                } else {

                    $levelCard = $defaultCard->cardLevels()->firstWhere('card_level', $activeLevelId);
                    $card_level_status = $userCardsDetail->cardLevels()->firstWhere('card_level', $activeLevelId);
                    $card_level_status = $card_level_status->card_level_status ?? UserCards::NORMAL_STATUS;
                    if ($levelCard) {
                        $userPoints->display_feeding_rive_url = $levelCard->feeding_rive_url ?? '';
                        $userPoints->display_background_rive_url = $levelCard->background_rive_url ?? '';
                        $userPoints->display_background_thumbnail_url = $levelCard->background_thumbnail_url ?? '';
                        $userPoints->display_character_thumbnail_url = $levelCard->character_thumbnail_url ?? '';
                        if ($card_level_status == UserCards::NORMAL_STATUS) {
                            $userPoints->display_character_rive_url = $levelCard->character_rive_url ?? '';
                        } else {
                            $defaultCardStatus = $defaultCard->cardLevelStatusRive()->where('card_level_status', $card_level_status)->where('card_level_id', $activeLevelId)->first();
                            $userPoints->display_character_rive_url = $defaultCardStatus->character_riv_url ?? '';
                        }
                        $userPoints->display_character_rive_status = $card_level_status;
                    }
                }
                // Change Rive based on Level

                // Is Feed
                $feedCount = UserCardLog::where('user_id', $user->id)->where('card_log', UserCardLog::FEED)->whereDate('created_at', Carbon::now())->count();
                $userPoints->feed_count = $feedCount;
                $userPoints->remaining_feed_count = UserCardLog::ALLOW_FEED - $feedCount;

                $userPoints->last_feed = UserCardLog::where('card_id', $activeCardId)->where('user_id', $user->id)->where('card_log', UserCardLog::FEED)->whereDate('created_at', Carbon::now())->orderBy('created_at', 'DESC')->first();

                $per = ($cardLevelDetail->end - $cardLevelDetail->start);
                $percentage = ((($love_count - $cardLevelDetail->start) / $per) * 100);

                $userPoints->love_details = (object)[
                    'start' => $cardLevelDetail->start,
                    'end' => $cardLevelDetail->end,
                    'percentage' => ($percentage > 100) ? 100 : round($percentage, 2),
                    'love_count' => $love_count
                ];

                // Missed Feed Days
                $user_last_feed = UserCardLog::where('card_id', $activeCardId)->where('user_id', $user->id)->where('card_log', UserCardLog::FEED)->orderBy('created_at', 'DESC')->first();
                if ($user_last_feed) {
                    $userPoints->missed_feed_count = UserMissedFeedCard::where('card_id', $activeCardId)->where('user_id', $user->id)->whereDate('missed_date', '>', $user_last_feed->created_at)->count();
                } else {
                    $userPoints->missed_feed_count = UserMissedFeedCard::where('card_id', $activeCardId)->where('user_id', $user->id)->count();
                }


                $user_own_card = DefaultCardsRives::join('user_cards', 'user_cards.default_cards_riv_id', 'default_cards_rives.id')
                    ->select('user_cards.*', 'default_cards_rives.id as default_cards_rives_id', 'default_cards_rives.background_rive', 'default_cards_rives.background_thumbnail', 'default_cards_rives.character_rive', 'default_cards_rives.download_file')
                    ->where('user_cards.user_id', $user->id)
                    ->whereIn('user_cards.status', [UserCards::SOLD_CARD_STATUS, UserCards::REQUESTED_STATUS, UserCards::ASSIGN_STATUS, UserCards::DEAD_CARD_STATUS])
                    ->orderBy('user_cards.is_applied', 'DESC')
                    ->orderBy('user_cards.created_at', 'DESC')
                    ->get();

                $user_own_card = $user_own_card->map(function ($item) use ($defaultCardData) {
                    if ($defaultCardData->id == $item->default_cards_riv_id) {
                        $isAlreadyRequested = UserCardSellRequest::where('card_id', $item->id)->where('status', 0)->count();
                        $item->is_able_to_sell = ($isAlreadyRequested == 0);
                    } else {
                        $item->is_able_to_sell = true;
                    }
                    $defaultCard = DefaultCardsRives::where('id', $item->default_cards_riv_id)->first();

                    if ($item->active_level == CardLevel::DEFAULT_LEVEL) {
                        $item->display_background_rive_url = $defaultCard->background_rive_url ?? '';
                        $item->display_background_thumbnail_url = $defaultCard->background_thumbnail_url ?? '';
                        $item->display_character_thumbnail_url = $defaultCard->character_thumbnail_url ?? '';
                        if ($item->card_level_status == UserCards::NORMAL_STATUS) {
                            $item->display_character_rive_url = $defaultCard->character_rive_url ?? '';
                        } else {
                            $defaultCardStatus = $defaultCard->cardLevelStatusRive()->where('card_level_status', $item->card_level_status)->where('card_level_id', $item->active_level)->first();
                            $item->display_character_rive_url = $defaultCardStatus->character_riv_url ?? '';
                        }
                    } else {
                        $cardLevelData = UserCardLevel::where('user_card_id', $item->id)->where('card_level', $item->active_level)->first();
                        $levelCard = $defaultCard->cardLevels()->firstWhere('card_level', $item->active_level);
                        // $item->display_character_rive_url = $defaultCard->cardLevelStatusRive;
                        $card_level_status = $cardLevelData->card_level_status ?? UserCards::NORMAL_STATUS;
                        if ($levelCard) {
                            $item->display_background_rive_url = $levelCard->background_rive_url ?? '';
                            $item->display_background_thumbnail_url = $levelCard->background_thumbnail_url ?? '';
                            $item->display_character_thumbnail_url = $levelCard->character_thumbnail_url ?? '';
                            if ($card_level_status == UserCards::NORMAL_STATUS) {
                                $item->display_character_rive_url = $levelCard->character_rive_url ?? '';
                            } else {
                                $defaultCardStatus = $defaultCard->cardLevelStatusRive()->where('card_level_status', $card_level_status)->where('card_level_id', $item->active_level)->first();
                                $item->display_character_rive_url = $defaultCardStatus->character_riv_url ?? '';
                            }
                        }
                    }

                    return $item;
                });

                $userPoints->user_own_card = $user_own_card;

                $total_deduct = UserCoinHistory::where('type',UserCoinHistory::PURCHASE_PRODUCT)
                    ->where('transaction',UserCoinHistory::DEBIT)
                    ->where('user_id',$user->id)
                    ->sum('amount');
                $userPoints->total_deduct =  ($total_deduct > 0 ) ? "-".number_format($total_deduct,0) : number_format($total_deduct,0);

                $has_coffee_access_data = UserReferral::where('referred_by', $user->id)->where('has_coffee_access', 0)->get();
//                $cnt_referral_detail = UserReferralDetail::where('user_id', $user->id)->where('is_sent', 0)->count();
                $total_coffee_count = UserReferralDetail::where('user_id', $user->id)->where('is_sent', 0)->count();

                $userPoints->coffee_access_data = $has_coffee_access_data;
//                $userPoints->is_sent_count = $cnt_referral_detail;
                $userPoints->total_coffee_count = $total_coffee_count;

                $gifticon_details = GifticonDetail::with('attachments')->where('user_id', $user->id)->orderBy('created_at','DESC')->get()->toArray();
                foreach ($gifticon_details as &$gifticon_detail){
                    $gifticon_detail['created_at'] = $gifticon_detail ? timeAgo($gifticon_detail['created_at'], $userPoints['language_id'])  : "null";
                }
                $userPoints->gifticon_details = $gifticon_details;

                return $this->sendSuccessResponse(Lang::get('messages.user-profile.update-success'), 200, $userPoints);

            } else {
                return $this->sendSuccessResponse(Lang::get('messages.user.token_expired'), 401);
            }

        } catch (Exception $e) {
            Log::info($e);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function getReferralUsers(Request $request, $user_id = '')
    {
        $inputs = $request->all();
        try{
            $language_id = $inputs['language_id'] ?? 4;
            if (empty($user_id)) {
                $user_id = Auth::user()->id;
            }

            $excludeField = [
                'language_name',
                'level_name',
                'user_points',
                'is_character_as_profile',
                'is_outside',
                'card_number',
                'count_days',
                'level',
                'points',
                'points_updated_on',
                'country_id',
                'manager_id',
                'device_token',
                'device_id',
                'device_type_id',
                'report_count',
                'recommended_by',
                'gender',
                'mobile',
                'phone_code',
                'plan_expire_date',
                'hide_popup',
                'language_id',
                'last_plan_update',
                'package_plan_id',
                'sns_link',
                'sns_type',
            ];

            $maxLevel = CardLevel::whereId(CardLevel::LAST_LEVEL)->first();
            $users = UserDetail::where('recommended_by', $user_id)
                ->select('*')
                ->selectSub(function($q) {
                    $q->select( DB::raw('count(detail.id) as count'))->from('users_detail as detail')->whereNull('detail.deleted_at')->whereRaw("`detail`.`recommended_by` = `users_detail`.`user_id`");
                }, 'followers_count')
                ->selectSub(function($q) {
                    $q->select( DB::raw('SUM(user_cards.love_count)'))->from('user_cards')->whereRaw("`user_cards`.`user_id` = `users_detail`.`user_id`");
                }, 'love_count')
                ->selectSub(function($q) {
                    $q->select( DB::raw('user_card_logs.created_at'))->from('user_card_logs')->where('user_card_logs.card_log',UserCardLog::FEED)->whereRaw("`user_card_logs`.`user_id` = `users_detail`.`user_id`")->orderBy('user_card_logs.created_at','DESC')->limit(1);
                }, 'last_feed_date')
                ->orderby('love_count','DESC')
                //->get();
                ->paginate(config('constant.post_pagination_count'), "*", "followers_page");

            $users->getCollection()->transform(function($item, $key) use ($maxLevel, $language_id){
                $item->love_count = (int)$item->love_count;
                $item->total_love_count = $maxLevel->start;
                $item->last_feed_date_display = !empty($item->last_feed_date) ? timeAgo($item->last_feed_date,$language_id) : null;

                $usersFollowers = DB::table('users_detail')->whereNull('users_detail.deleted_at')->where("recommended_by",$item->user_id)->take(3)->pluck('avatar');
                $item->followers_image = collect($usersFollowers)->map(function ($val) {
                            if (empty($val)) {
                                return asset('img/avatar/avatar-1.png');
                            } else {
                                return Storage::disk('s3')->url($val);
                            }
                        });
                return $item;
            });

            $users->makeHidden($excludeField);

            return $this->sendSuccessResponse(Lang::get('messages.general.success'), 200, $users);

        } catch (Exception $e) {
            Log::info($e);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }

    }

    public function sellRequestCard(Request $request)
    {
        $inputs = $request->all();
        try {
            $validation = Validator::make($request->all(), [
                'card_id' => 'required',
                'recipient_name' => 'required',
                'bank_name' => 'required',
                'bank_account_number' => 'required',
            ], [], [
                'card_id' => 'Card Id',
                'recipient_name' => 'Recipient Name',
                'bank_name' => 'Bank Name',
                'bank_account_number' => 'Bank Account Number',
            ]);

            if ($validation->fails()) {
                return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
            }
            $user = Auth::user();

            $bankDetails = [
                "user_id" => $user->id,
                "recipient_name" => $inputs['recipient_name'],
                "bank_name" => $inputs['bank_name'],
                "bank_account_number" => $inputs['bank_account_number'],
            ];

            $userCards = UserCards::where('id', $inputs['card_id'])->first();
            $bankData = UserBankDetail::updateOrCreate($bankDetails);

            UserCardSellRequest::create(['card_id' => $inputs['card_id'], 'status' => 0, 'card_level' => $userCards->active_level]);
            $updateCard = UserCards::where('id', $inputs['card_id'])->update(['status' => UserCards::REQUESTED_STATUS, 'bank_id' => $bankData->id]);

            $userOwnCards = UserCards::where('id', $inputs['card_id'])->get();

            return $this->sendSuccessResponse(Lang::get('messages.general.success'), 200, $userOwnCards);
        } catch (Exception $e) {
            Log::info($e);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function getCardDetail(Request $request)
    {
        $inputs = $request->all();
        $user = Auth::user();
        try {
            $validation = Validator::make($request->all(), [
                'card_id' => 'required',
                'detail_type' => 'in:own,all'
            ], [], [
                'card_id' => 'Card Id',
            ]);

            if ($validation->fails()) {
                return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
            }
            $cardID = $inputs['card_id'];
            $detailType = $inputs['detail_type'] ?? 'own';
            $cardDetail = '';
            if($detailType == 'own' && $user) {
                $isOwner = UserCards::whereId($cardID)->where('user_id', $user->id)->count();
                if (empty($isOwner)) {
                    return $this->sendSuccessResponse(Lang::get('messages.cards.card-not-own'), 400);
                }


                UserCards::whereId($cardID)->update(['is_new' => 0]);

                $cardsQuery = DefaultCardsRives::join('user_cards', 'user_cards.default_cards_riv_id', 'default_cards_rives.id')
                    ->leftjoin('card_level_details', function ($join) {
                        $join->on('card_level_details.main_card_id', '=', 'default_cards_rives.id')
                            ->whereRaw('card_level_details.card_level = user_cards.active_level');
                    })
                    ->where('user_cards.id', $cardID)
                    ->select(
                        'user_cards.*',
                        'default_cards_rives.card_name',
                        /* 'default_cards_rives.japanese_yen_price',
                        'default_cards_rives.chinese_yuan_price',
                        'default_cards_rives.korean_won_price', */
                        /* 'default_cards_rives.download_file',
                        'default_cards_rives.character_rive',
                        'default_cards_rives.background_rive', */
                        DB::raw('(CASE
                            WHEN user_cards.active_level != 1 THEN card_level_details.download_file
                            ELSE default_cards_rives.download_file
                        END) AS download_file'),
                        DB::raw('(CASE
                            WHEN user_cards.active_level != 1 THEN card_level_details.character_rive
                            ELSE default_cards_rives.character_rive
                        END) AS character_rive'),
                        DB::raw('(CASE
                            WHEN user_cards.active_level != 1 THEN card_level_details.background_rive
                            ELSE default_cards_rives.background_rive
                        END) AS background_rive'),
                        DB::raw('(CASE
                            WHEN user_cards.active_level != 1 THEN card_level_details.background_thumbnail
                            ELSE default_cards_rives.background_thumbnail
                        END) AS background_thumbnail'),
                        DB::raw('(CASE
                            WHEN user_cards.active_level != 1 THEN card_level_details.character_thumbnail
                            ELSE default_cards_rives.character_thumbnail
                        END) AS character_thumbnail'),
                        DB::raw('(CASE
                            WHEN user_cards.active_level != 1 THEN card_level_details.usd_price
                            ELSE default_cards_rives.usd_price
                        END) AS usd_price'),
                        DB::raw('(CASE
                            WHEN user_cards.active_level != 1 THEN card_level_details.japanese_yen_price
                            ELSE default_cards_rives.japanese_yen_price
                        END) AS japanese_yen_price'),
                        DB::raw('(CASE
                            WHEN user_cards.active_level != 1 THEN card_level_details.chinese_yuan_price
                            ELSE default_cards_rives.chinese_yuan_price
                        END) AS chinese_yuan_price'),
                        DB::raw('(CASE
                            WHEN user_cards.active_level != 1 THEN card_level_details.korean_won_price
                            ELSE default_cards_rives.korean_won_price
                        END) AS korean_won_price')
                    );

                $cardDetail = $cardsQuery->first();

                //$cardDetail = $cardDetail->makeHidden('is_owned');

                $cardRangePrice = getCardRangePrice($cardDetail->default_cards_riv_id);
                $bankDetail = UserBankDetail::where('user_id', $user->id)->orderby('created_at', 'DESC')->first();
            }else{
                $cardsQuery = DefaultCardsRives::leftjoin('card_level_details', function ($join) {
                        $join->on('card_level_details.main_card_id', '=', 'default_cards_rives.id')
                            ->where('card_level_details.card_level',CardLevel::MIDDLE_LEVEL);
                    })
                    ->where('default_cards_rives.id', $cardID)
                    ->select(
                        'default_cards_rives.*',
                        'card_level_details.background_rive',
                        'card_level_details.character_rive',
                        'card_level_details.background_thumbnail',
                        'card_level_details.character_thumbnail'
                    );

                $cardDetail = $cardsQuery->first();

                $cardRangePrice = getCardRangePrice($cardID);
            }
            if ($cardDetail) {
                $cardDetail->bank_details = $bankDetail ?? (object)[];
                $cardDetail->download_file_url = ($cardDetail->download_file) ? Storage::disk('s3')->url($cardDetail->download_file) : '';
                $cardDetail->card_range_price = $cardRangePrice;
            }
            return $this->sendSuccessResponse(Lang::get('messages.general.success'), 200, $cardDetail);
        } catch (Exception $e) {
            Log::info($e);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function ApplyCard(Request $request)
    {
        $inputs = $request->all();
        $user = Auth::user();
        try {

            $validation = Validator::make($request->all(), [
                'user_card_id' => 'required',
            ], [], [
                'user_card_id' => "user's card ID",
            ]);

            if ($validation->fails()) {
                return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
            }

            $user_card_id = $inputs['user_card_id'];
            $is_card_owned = UserCards::where(['id' => $user_card_id, 'user_id' => $user->id])->count();

            if ($is_card_owned == 0) {
                return $this->sendSuccessResponse(Lang::get('messages.cards.card-not-own'), 400);
            } else {
                $prevApplied = UserCards::where(['user_id' => $user->id, 'is_applied' => 1])->first();

                UserCards::where(['user_id' => $user->id, 'is_applied' => 1])->update(['is_applied' => 0]);
                UserCards::where(['id' => $user_card_id, 'user_id' => $user->id])->update(['is_applied' => 1]);

                if ($prevApplied && $prevApplied->id != $user_card_id) {
                    UserCardAppliedHistory::updateOrCreate([
                        'user_id' => $user->id,
                        'old_card_id' => $prevApplied->id,
                        'new_card_id' => $user_card_id,
                        'applied_date' => Carbon::now()->format('Y-m-d')
                    ]);
                }

                return $this->sendSuccessResponse(Lang::get('messages.cards.card-apply'), 200);
            }
        } catch (Exception $e) {
            Log::info($e);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function getCardLevelDetail(Request $request): JsonResponse
    {
        $inputs = $request->all();
        $user = Auth::user();
        try {
            $validation = Validator::make($inputs, [
                'card_id' => 'required',
                'detail_type' => 'in:own,all'
            ], [], [
                'card_id' => 'Card Id',
            ]);

            if ($validation->fails()) {
                return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
            }

            $card_id = $inputs['card_id'] ?? '';
            $detailType = $inputs['detail_type'] ?? 'own';
            $language_id = $inputs['language_id'] ?? 4;

            $responseData = [];
            $count = 0;
            $default_level = CardLevel::find(CardLevel::DEFAULT_LEVEL);
            $other_level = CardLevel::where('id', '!=', CardLevel::DEFAULT_LEVEL)->get();
            if($detailType == 'own' && $user) {
                $cardData = UserCards::find($card_id);

                $defaultCardData = getDefaultCard();
                if ($defaultCardData->id == $cardData->default_cards_riv_id) {
                    $isAlreadyRequested = UserCardSellRequest::where('card_id', $card_id)->where('status', 0)->count();
                   // $is_able_to_sell = ($cardData->active_level == CardLevel::LAST_LEVEL && $isAlreadyRequested == 0);
                    $is_able_to_sell = ($isAlreadyRequested == 0);
                } else {
                    $is_able_to_sell = true;
                }

                if ($cardData) {
                    $defaultCardData = DefaultCardsRives::whereId($cardData->default_cards_riv_id)->first();
                    $responseData = $this->getFilteredValue($responseData, $count, $cardData->id, $default_level, $defaultCardData, $cardData->active_level,$language_id);

                    if ($cardData->cardLevels) {
                        foreach ($cardData->cardLevels as $userCard) {
                            $count++;
                            $levelDetail = $other_level->firstWhere('id', $userCard->card_level);
                            $defaultCardDetail = $defaultCardData->cardLevels()->firstWhere('card_level', $userCard->card_level);
                            $responseData = $this->getFilteredValue($responseData, $count, $userCard->id, $levelDetail, $defaultCardDetail, $cardData->active_level,$language_id);
                        }
                    }
                }
                $bankDetail = UserBankDetail::where('user_id', $cardData->user_id)->orderby('created_at', 'DESC')->first();
            }else{
                $defaultCardData = DefaultCardsRives::whereId($card_id)->first();
                $responseData = $this->getFilteredValue($responseData, $count, $card_id, $default_level, $defaultCardData, CardLevel::DEFAULT_LEVEL,$language_id);

                if ($defaultCardData->cardLevels) {
                    foreach ($defaultCardData->cardLevels as $defaultCardDetail) {
                        $count++;
                        $levelDetail = $other_level->firstWhere('id', $defaultCardDetail->card_level);
                        $responseData = $this->getFilteredValue($responseData, $count, $defaultCardDetail->id, $levelDetail, $defaultCardDetail, CardLevel::DEFAULT_LEVEL,$language_id);
                    }
                }
            }
            $data['level_detail'] = $responseData;

            $data['bank_details'] = $bankDetail ?? (object)[];
            $data['is_able_to_sell'] = $is_able_to_sell ?? false;

            return $this->sendSuccessResponse(Lang::get('messages.general.success'), 200, $data);
        } catch (Exception $e) {
            Log::info($e);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function getFilteredValue(&$responseData, $count, $cardData_id, $default_level, $defaultCardData, $active_level = 0,$language_id = 4)
    {
        $level_name = $default_level->level_name ?? '';
        $level_lang_name = __("messages.language_$language_id.$level_name");
        $responseData[$count]['id'] = $cardData_id;
        $responseData[$count]['level_name'] = $level_lang_name ?? $level_name;
        $responseData[$count]['level_id'] = $default_level->id ?? '';
        $responseData[$count]['range'] = $default_level->range ?? '';
        $responseData[$count]['is_active_level'] = ($default_level->id == $active_level);
        $responseData[$count]['background_riv'] = $defaultCardData->background_rive ?? '';
        $responseData[$count]['background_rive_url'] = $defaultCardData->background_rive_url ?? '';
        $responseData[$count]['background_thumbnail_url'] = $defaultCardData->background_thumbnail_url ?? '';
        $responseData[$count]['character_thumbnail_url'] = $defaultCardData->character_thumbnail_url ?? '';
        $responseData[$count]['background_rive_animation'] = $defaultCardData->background_rive_animation ?? '';
        $responseData[$count]['character_riv'] = $defaultCardData->character_rive ?? '';
        $responseData[$count]['character_rive_url'] = $defaultCardData->character_rive_url ?? '';
        $responseData[$count]['character_rive_animation'] = $defaultCardData->character_rive_animation ?? '';
        $responseData[$count]['usd_price'] = $defaultCardData->usd_price ?? '';
        $responseData[$count]['japanese_yen_price'] = $defaultCardData->japanese_yen_price ?? '';
        $responseData[$count]['chinese_yuan_price'] = $defaultCardData->chinese_yuan_price ?? '';
        $responseData[$count]['korean_won_price'] = $defaultCardData->korean_won_price ?? '';
        $responseData[$count]['card_name'] = $defaultCardData->card_name ?? '';
        return $responseData;
    }

    public function removeDeadCard(Request $request): JsonResponse
    {
        $inputs = $request->all();
        try {
            $validation = Validator::make($inputs, [
                'card_id' => 'required',
            ], [], [
                'card_id' => 'Card Id',
            ]);

            if ($validation->fails()) {
                return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
            }

            DB::beginTransaction();
            $card_id = $inputs['card_id'] ?? '';

            $isDeadCard = UserCards::where('id', $card_id)->where('status', UserCards::DEAD_CARD_STATUS)->first();
            if ($isDeadCard) {
                UserCards::where('id', $card_id)->where('status', UserCards::DEAD_CARD_STATUS)->update(['status' => UserCards::HIDE_DEAD_CARD_STATUS, 'is_applied' => 0]);
                $defaultCard = getDefaultCard();
                if ($defaultCard) {
                    UserCards::where('default_cards_riv_id', $defaultCard->id)->update(['is_applied' => 1]);
                }
            }

            DB::commit();
            return $this->sendSuccessResponse(Lang::get('messages.general.success'), 200);
        } catch (Exception $e) {
            Log::info($e);
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function restartDefaultCard(Request $request)
    {
        $inputs = $request->all();
        $user = Auth::user();
        try {
            DB::beginTransaction();
            $defaultCard = getDefaultCard();
            $applied = UserCards::where(['user_id' => $user->id, 'is_applied' => 1])->first();
            if ($applied && $applied->default_cards_riv_id == $defaultCard->id) {
                if ($applied->active_level == CardLevel::DEFAULT_LEVEL) {
                    $card_level_status = $applied->card_level_status;
                } else {
                    $cardLevelData = UserCardLevel::where('user_card_id', $applied->id)->where('card_level', $applied->active_level)->first();
                    $card_level_status = $cardLevelData->card_level_status;
                }
                $history = UserCardResetHistory::create([
                    'user_id' => $applied->user_id,
                    'sell_card_id' => null,
                    'card_level' => $applied->active_level,
                    'love_count' => $applied->love_count,
                    'card_level_status' => $card_level_status
                ]);
                UserCards::whereId($applied->id)->update([
                    'status' => UserCards::ASSIGN_STATUS,
                    'love_count' => 0,
                    'active_level' => CardLevel::DEFAULT_LEVEL,
                    'card_level_status' => UserCards::NORMAL_STATUS
                ]);
                UserMissedFeedCard::where('user_id', $user->id)->where('card_id', $applied->id)->delete();
            }
            DB::commit();
            return $this->sendSuccessResponse("Card" . Lang::get('messages.update-success'), 200);
        } catch (Exception $e) {
            Log::info($e);
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function getFollowersCoinDetail(Request $request)
    {
        $user = Auth::user();
        try{
            $totalPrice = getUserSoldCardPrice($user->id) + CardSoldFollowers::SOLD_CARD_COIN;
            $details = CardSoldFollowers::join('user_card_sold_details','user_card_sold_details.id','card_sold_followers.sold_id')
                ->join('user_cards','user_cards.id','user_card_sold_details.card_id')
                ->where('card_sold_followers.user_id',$user->id)
                ->where('card_sold_followers.status','0')
                ->select(
                    'user_cards.user_id',
                    'card_sold_followers.follower_level',
                    'user_card_sold_details.created_at',
                    DB::raw("(
                        CASE
                            WHEN card_sold_followers.follower_level = ".CardSoldFollowers::FOLLOWERS." THEN ".CardSoldFollowers::FOLLOWERS_COIN."
                            WHEN card_sold_followers.follower_level = ".CardSoldFollowers::GRAND_FOLLOWERS." THEN ".CardSoldFollowers::GRAND_FOLLOWERS_COIN."
                            WHEN card_sold_followers.follower_level = ".CardSoldFollowers::GREAT_GRAND_FOLLOWERS." THEN ".CardSoldFollowers::GREAT_GRAND_FOLLOWERS_COIN."
                            ELSE '0'
                        END
                    ) AS coin_amount")
                )
                ->orderBy('user_card_sold_details.created_at','DESC')
                ->get();



            if(!empty($details)){
                foreach($details as $key => $data){
                    // Price

                    if($key == 0){
                        $data->total_coin_amount = number_format($totalPrice,0);
                    }else{
                        $totalPrice = ($totalPrice - (int)$details[$key-1]->coin_amount);
                        $data->total_coin_amount = number_format($totalPrice,0);
                    }
                    $data->display_coin_amount = number_format($data->coin_amount,0);
                    // Followers
                    $followers = [];
                    $userDetail = UserDetail::where('user_id',$data->user_id)->with('parentFollowersDetail')->first();
                    $followers[] = $userDetail->name;
                    $firstParent = $userDetail->parentFollowersDetail;
                    if(!empty($firstParent) && $data->follower_level > 1){
                        $followers[] = $firstParent->name;

                        $secondParent = $firstParent->parentFollowersDetail;

                        if(!empty($secondParent)  && $data->follower_level > 2){
                            $followers[] = $secondParent->name;

                            $thirdParent = $secondParent->parentFollowersDetail;

                            if(!empty($thirdParent)  && $data->follower_level > 3){
                                $followers[] = $thirdParent->name;
                            }
                        }

                    }

                    $data->followers_name = $followers;
                }
            }
            return $this->sendSuccessResponse(Lang::get('messages.general.success'), 200, $details);
        } catch (\Exception $e) {
            Log::info($e);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function getDeductCoinDetail(Request $request)
    {
        $user = Auth::user();
        try{
            $total_deduct = UserCoinHistory::where('type',UserCoinHistory::PURCHASE_PRODUCT)
            ->where('transaction',UserCoinHistory::DEBIT)
            ->where('user_id',$user->id)
            ->sum('amount');
            $total_deduct = ($total_deduct > 0 ) ? "-".number_format($total_deduct,0) : number_format($total_deduct,0);
            $deduct_data = UserCoinHistory::join('brand_products','brand_products.id','user_coin_histories.entity_id')
                ->join('brands','brands.id','brand_products.brand_id')
                ->where('user_coin_histories.type',UserCoinHistory::PURCHASE_PRODUCT)
                ->where('user_coin_histories.transaction',UserCoinHistory::DEBIT)
                ->where('user_coin_histories.user_id',$user->id)
                ->select(
                    'brand_products.name as product_name',
                    'brand_products.product_image as product_image',
                    'brands.name as brand_name',
                    'user_coin_histories.amount',
                    'user_coin_histories.created_at'
                )
                ->get();

            $deduct_data->map(function($item, $key){
                $item->product_image_url =  !empty($item->product_image) ? Storage::disk('s3')->url($item->product_image) : '';
                $item->amount = ($item->amount > 0 )? "-".number_format($item->amount,0) : number_format($item->amount,0);
                return $item;
            });
            return $this->sendSuccessResponse(Lang::get('messages.general.success'), 200, compact('total_deduct','deduct_data'));
        } catch (\Exception $e) {
            Log::info($e);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }
}
