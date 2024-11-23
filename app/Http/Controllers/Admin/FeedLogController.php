<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CardLevel;
use App\Models\DefaultCardsRives;
use App\Models\EntityTypes;
use App\Models\UserCardLevel;
use App\Models\UserCardLog;
use App\Models\UserCards;
use App\Models\UserDetail;
use App\Models\UserFeedLog;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FeedLogController extends Controller
{
    public function index(Request $request)
    {
        $title = 'Feed log List';
        UserFeedLog::where('is_admin_read', 1)->update(['is_admin_read' => 0]);

        return view('admin.feed-log.index', compact('title'));
    }

    public function getJsonData(Request $request){
        $columns = array(
            0 => 'users_detail.name',
            4 => 'shops.main_name',
            5 => 'shops.shop_name',
            6 => 'user_cards.love_count',
            7 => 'user_feed_logs.feed_time',
        );

        $limit = $request->input('length');
        $start = $request->input('start');
        $search = $request->input('search.value');
        $draw = $request->input('draw');
        $order = $columns[$request->input('order.0.column')];
        $dir = $request->input('order.0.dir');
        $adminTimezone = $this->getAdminUserTimezone();

        $loginUser = Auth::user();
        try {
            $data = [];
            $query = UserFeedLog::select(
                'user_feed_logs.*',
                'users_detail.name',
                'users_detail.mobile',
                'users_detail.is_increase_love_count_daily',
                'users.email',
                'user_cards.love_count',
                'shops.main_name',
                'shops.shop_name',
                'shops.id as shop_id'
                )
                ->join('users_detail', function ($join) {
                    $join->on('user_feed_logs.user_id', '=', 'users_detail.user_id')
                        ->whereNull('users_detail.deleted_at');
                })
                ->join('users', function ($join) {
                    $join->on('user_feed_logs.user_id', '=', 'users.id')
                        ->whereNull('users.deleted_at');
                })
                ->leftjoin('user_cards', function ($join) {
                    $join->on('user_feed_logs.user_id', '=', 'user_cards.user_id')
                        ->where('is_applied',1);
                })
                ->leftjoin('shops', function ($join) {
                    $join->on('user_feed_logs.user_id', '=', 'shops.user_id')
                        ->whereNull('shops.deleted_at')
                        ->where('shops.created_at','=',DB::raw("(select max(`created_at`) from shops where shops.user_id = user_feed_logs.user_id and shops.deleted_at IS NULL)"));
                })
                ->groupBy('users_detail.user_id');

            if (!empty($search)) {
                $query =  $query->where(function ($q) use ($search) {
                    $q->where('users_detail.name', 'LIKE', "%{$search}%")
                        ->orWhere('users.email', 'LIKE', "%{$search}%")
                        ->orWhere('users_detail.mobile', 'LIKE', "%{$search}%")
                        ->orWhere('user_cards.love_count', 'LIKE', "%{$search}%");
                });
            }

            $totalData = count($query->get());
            $totalFiltered = $totalData;

            $feed_logs = $query->offset($start)
                ->limit($limit)
                ->orderBy($order, $dir)
                ->get();
//            dd($feed_logs->toArray());

            $count = 0;
            foreach($feed_logs as $feed_log){
                $appliedCard = DefaultCardsRives::join('user_cards', 'user_cards.default_cards_riv_id', 'default_cards_rives.id')
                    ->select('default_cards_rives.*','user_cards.id as user_card_id','user_cards.active_level','user_cards.card_level_status')
                    ->where(['user_cards.user_id' => $feed_log->user_id,'user_cards.is_applied' => 1])
                    ->first();
                $character_thumbnail_url = "";
                if ($appliedCard->active_level == CardLevel::DEFAULT_LEVEL) {
                    if($appliedCard->card_level_status == UserCards::NORMAL_STATUS) {
                        $character_thumbnail_url = $appliedCard->character_thumbnail_url ?? "";
                    }else{
                        $defaultCardStatus = $appliedCard->cardLevelStatusThumb()->where('card_level_status',$appliedCard->card_level_status)->where('card_level_id',$appliedCard->active_level)->first();
                        $character_thumbnail_url = $defaultCardStatus->character_thumb_url ?? "";
                    }
                } else {
                    $cardLevelData = UserCardLevel::where('user_card_id',$appliedCard->user_card_id)->where('card_level',$appliedCard->active_level)->first();
                    $levelCard = $appliedCard->cardLevels()->firstWhere('card_level', $appliedCard->active_level);
                    if ($levelCard) {
                        $card_level_status = $cardLevelData->card_level_status ?? UserCards::NORMAL_STATUS;
                        if($card_level_status == UserCards::NORMAL_STATUS) {
                            $character_thumbnail_url = $levelCard->character_thumbnail_url ?? '';
                        }else{
                            $defaultCardStatus = $appliedCard->cardLevelStatusThumb()->where('card_level_status',$card_level_status)->where('card_level_id',$appliedCard->active_level)->first();
                            $character_thumbnail_url = $defaultCardStatus->character_thumb_url ?? '';
                        }
                    }
                }

                $note = '<div class="d-flex align-items-center">
                        <textarea name="note" rows="2">'.$feed_log->note.'</textarea>
                        <a role="button" href="javascript:void(0)" data-id="'.$feed_log->id.'" title="" data-original-title="Edit Note" class="mx-1 btn btn-primary btn-sm editnote" data-toggle="tooltip">Edit</a>
                        </div>';

                $green_status = "";
                if ($feed_log->is_increase_love_count_daily == 1){
                    $green_status = '<span class="badge badge-success ml-2">&nbsp;</span>';
                }

                $data[$count]['username'] = $feed_log->name;
                $data[$count]['phone'] = $feed_log->mobile;
                $data[$count]['email'] = $feed_log->email;
                $data[$count]['see_profile'] = "";
                if (isset($feed_log->shop_id) && $feed_log->shop_id!=null) {
                    $profileLink = route('admin.business-client.shop.show', [$feed_log->shop_id]);
                    $data[$count]['see_profile'] = "<a role='button' href='$profileLink' title='' data-original-title='View' class='mr-2 btn btn-primary btn-sm ' data-toggle='tooltip'><i class='fas fa-eye mt-1'></i></a>";
                }
                $data[$count]['total_love'] = $feed_log->love_count.$green_status;
                $data[$count]['feed_time'] = $this->formatDateTimeCountryWise($feed_log->feed_time,$adminTimezone);
                if ($character_thumbnail_url!="") {
                    $data[$count]['char_image'] = "<img src='$character_thumbnail_url' width='100' height='100' alt='Character image'>";
                }
                else {
                    $data[$count]['char_image'] = "";
                }
                $data[$count]['note'] = $note;
                $data[$count]['history'] = '<a role="button" href="javascript:void(0)" user-id="'.$feed_log->user_id.'" title="" data-original-title="" class="mx-1 btn btn-primary btn-sm btnhistory" data-toggle="tooltip">History</a>';

                $editBtn = $loginUser->hasRole('Admin') ? '<a href="javascript:void(0)" role="button" onclick="editCredits('.$feed_log->user_id.')" class="btn btn-primary btn-sm mx-1" data-toggle="tooltip" data-original-title="Edit"><i class="fa fa-edit" style="font-size: 15px;margin: 4px -5px 4px 0;"></i></a>' : "";

                $data[$count]['action'] = $editBtn;
                $data[$count]['main_name'] = $feed_log->main_name;
                $data[$count]['shop_name'] = $feed_log->shop_name;

                $addmoreBtn = '<a role="button" href="javascript:void(0)" user-id="'.$feed_log->user_id.'" title="" data-original-title="" class="mx-1 btn btn-primary btn-sm btn_add_more" data-toggle="tooltip">Add More</a>';
                $data[$count]['add_more'] = $addmoreBtn;
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

    public function editNote(Request $request){
        try {
            DB::beginTransaction();

            UserFeedLog::where('id',$request->id)->update([
                'note' => $request->note
            ]);

            DB::commit();
            return response()->json(['status' => 1, 'message' => 'Note updated successfully.']);
        } catch (\Exception $ex) {
            DB::rollBack();
            return response()->json(['status' => 0, 'message' => 'Failed to edit note!!']);
        }
    }

    public function showUserFeedlogs($id){
        $adminTimezone = $this->getAdminUserTimezone();
        $feed_logs = UserCardLog::select(
            'user_card_logs.*'
            )
            ->where('user_card_logs.user_id',$id)
            ->where('user_card_logs.card_log',UserCardLog::FEED)
            ->orderBy('user_card_logs.created_at','DESC')
            ->get();
        return view('admin.feed-log.show-feedlogs-popup', compact('feed_logs','adminTimezone'));
    }

    public function showAutoloveUsers(){
        $auto_love_users = UserDetail::where('is_increase_love_count_daily',1)->get(['name','increase_love_count']);

        return view('admin.feed-log.show-autoloveuser-popup', compact('auto_love_users'));
    }

    public function addMoreLove(Request $request){
        $inputs = $request->all();

        try {
            DB::beginTransaction();

            $user_applied_card = UserCards::where('user_id',$inputs['user_id'])->where('is_applied',1)->first();
            $love_count = $user_applied_card->love_count + $inputs['love_amount'];
            $user_applied_card->love_count = $love_count;
            $user_applied_card->save();

            if($user_applied_card->active_level == CardLevel::DEFAULT_LEVEL) {
                UserCards::whereId($user_applied_card->id)->update(['card_level_status' => UserCards::HAPPY_STATUS]);
            }else{
                UserCardLevel::where('user_card_id',$user_applied_card->id)->update(['card_level_status' => UserCards::HAPPY_STATUS]);
            }

            UserCardLog::create([
                'user_id' => $inputs['user_id'],
                'card_id' => $user_applied_card->id,
                'card_log' => UserCardLog::FEED,
                'created_at' => Carbon::now(),
                'love_count' => (empty($love_count)) ? 0 : $love_count
            ]);
            UserFeedLog::updateOrCreate([
                'user_id' => $inputs['user_id'],
            ],[
                'card_id' => $user_applied_card->id,
                'feed_time' => Carbon::now()
            ]);

            DB::commit();
            return response()->json(['status' => 1, 'message' => 'Love amount added successfully.']);
        } catch (\Exception $ex) {
            DB::rollBack();
            return response()->json(['status' => 0, 'message' => 'Failed to add love amount!!']);
        }
    }

}
