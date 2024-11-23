<?php

namespace App\Http\Controllers\Challenge;

use App\Http\Controllers\Controller;
use App\Models\Challenge;
use App\Models\ChallengeCategory;
use App\Models\ChallengeDay;
use App\Models\ChallengeImages;
use App\Models\ChallengeParticipatedUser;
use App\Models\ChallengeThumb;
use App\Models\ChallengeVerify;
use App\Models\EntityTypes;
use App\Models\LinkedSocialProfile;
use App\Models\PeriodChallenge;
use App\Models\PeriodChallengeImages;
use App\Models\ShopPriceImages;
use App\Models\Status;
use Carbon\Carbon;
use FFMpeg\FFMpeg;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ChallengeController extends Controller
{
    public function index()
    {
        $title = "Challenge";
        $challenge_cats = ChallengeCategory::where('challenge_type',ChallengeCategory::CHALLENGE)->orderBy('order','ASC')->get();
        $period_challenge_cats = ChallengeCategory::where('challenge_type',ChallengeCategory::PERIODCHALLENGE)->orderBy('order','ASC')->get();

        $today_date = Carbon::now()->format('Y-m-d');
        $dayName = Carbon::now()->format('D');
        $dayName = strtolower($dayName);
        $today_day = substr($dayName, 0, 2);

        $count_today_period_challenges = Challenge::leftjoin('challenge_days', function ($join) {
            $join->on('challenges.id', '=', 'challenge_days.challenge_id');
        })
            ->whereDate('challenges.start_date', '<=', $today_date)
            ->whereDate('challenges.end_date', '>=', $today_date)
            ->where('challenge_days.day',$today_day)
            ->where('challenges.is_period_challenge',1)
            ->count();

        $count_today_challenges = Challenge::whereDate('date',$today_date)
            ->where('is_period_challenge',0)
            ->count();

        return view('challenge.challenge-page.index', compact('title','challenge_cats','period_challenge_cats','count_today_period_challenges','count_today_challenges'));
    }

    public function savePeriodChallenge(Request $request){
        $inputs = $request->all();
        try{
            DB::beginTransaction();
            $validator = Validator::make($inputs, [
                'title' => 'required',
                'day' => 'required|array',
                'time' => 'required',
                'deal_amount' => 'required',
                'description' => 'required',
                'start_date' => 'required',
                'end_date' => 'required',
                'category_id' => 'required',
            ],[
                'title.required' => 'The title is required.',
                'day.required' => 'Please select day.',
                'time.required' => 'Verify time is required.',
                'deal_amount.required' => 'The deal amount is required.',
                'description.required' => 'The description is required.',
                'start_date.required' => 'The starting date is required.',
                'end_date.required' => 'The end date is required.',
                'category_id.required' => 'Please select category.',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors(), 'success' => false]);
            }

            $adminTimezone = $this->getAdminUserTimezone();
            $inputTime = Carbon::createFromFormat('H:i', $inputs['time'], "Asia/Seoul");
            $utcTime = $inputTime->setTimezone('UTC');
            $formattedUtcTime = $utcTime->format('H:i');

            $PeriodChallenge = Challenge::create([
                'title' => $inputs['title'],
                'verify_time' => $formattedUtcTime,
                'deal_amount' => $inputs['deal_amount'],
                'description' => $inputs['description'],
                'start_date' => $inputs['start_date'],
                'end_date' => $inputs['end_date'],
                'is_period_challenge' => 1,
                'category_id' => $inputs['category_id'],
                'challenge_thumb_id' => $inputs['thumb_image'] ?? null,
            ]);

            if(isset($inputs['day'])){
                foreach ($inputs['day'] as $day){
                    ChallengeDay::create([
                        'challenge_id' => $PeriodChallenge->id,
                        'day' => $day,
                    ]);
                }
            }
            if (!empty($inputs['main_images'])) {
                $ChallengeFolder = config('constant.challenge');
                if (!Storage::exists($ChallengeFolder)) {
                    Storage::makeDirectory($ChallengeFolder);
                }
                foreach ($inputs['main_images'] as $image) {
                    if (is_file($image)) {
                        $fileType = $image->getMimeType();
                        $mainImage = Storage::disk('s3')->putFile($ChallengeFolder, $image, 'public');
                        $fileName = basename($mainImage);
                        $image_url = $ChallengeFolder . '/' . $fileName;

                        ChallengeImages::create([
                            'challenge_id' => $PeriodChallenge->id,
                            'image' => $image_url,
                        ]);
                    }
                }
            }

            DB::commit();
            return response()->json(array('success' => true));
        }catch(\Exception $e){
            Log::info("$e");
            DB::rollBack();
            return response()->json(array('success' => false));
        }
    }

    public function saveChallenge(Request $request){
        $inputs = $request->all();
        try{
            DB::beginTransaction();
            $validator = Validator::make($inputs, [
                'challenge_title' => 'required',
                'challenge_time' => 'required',
                'challenge_deal_amount' => 'required',
                'desc' => 'required',
                'date' => 'required',
                'category_id' => 'required',
            ],[
                'category_id.required' => 'Please select category.',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors(), 'success' => false]);
            }

            $adminTimezone = $this->getAdminUserTimezone();
            $inputTime = Carbon::createFromFormat('H:i', $inputs['challenge_time'], "Asia/Seoul");
            $utcTime = $inputTime->setTimezone('UTC');
            $formattedUtcTime = $utcTime->format('H:i');

            $Challenge = Challenge::create([
                'title' => $inputs['challenge_title'],
                'verify_time' => $formattedUtcTime,
                'deal_amount' => $inputs['challenge_deal_amount'],
                'description' => $inputs['desc'],
                'date' => $inputs['date'],
                'is_period_challenge' => 0,
                'category_id' => $inputs['category_id'],
                'challenge_thumb_id' => $inputs['thumb_image'] ?? null,
            ]);
            if (!empty($inputs['main_images'])) {
                $ChallengeFolder = config('constant.challenge');
                if (!Storage::exists($ChallengeFolder)) {
                    Storage::makeDirectory($ChallengeFolder);
                }
                foreach ($inputs['main_images'] as $image) {
                    if (is_file($image)) {
                        $fileType = $image->getMimeType();
                        $mainImage = Storage::disk('s3')->putFile($ChallengeFolder, $image, 'public');
                        $fileName = basename($mainImage);
                        $image_url = $ChallengeFolder . '/' . $fileName;

                        ChallengeImages::create([
                            'challenge_id' => $Challenge->id,
                            'image' => $image_url,
                        ]);
                    }
                }
            }

            DB::commit();
            return response()->json(array('success' => true));
        }catch(\Exception $e){
            Log::info("$e");
            DB::rollBack();
            return response()->json(array('success' => false));
        }
    }

    public function getJsonAllData(Request $request)
    {
        $columns = array(
            0 => 'challenge_thumb_id',
            1 => 'title',
            2 => 'is_period_challenge',
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

            $Query = Challenge::select('*')
                ->selectSub(function($q) {
                    $q->select(DB::raw('count(*) as total'))->from('challenge_participated_users')->whereRaw("`challenge_participated_users`.`challenge_id` = `challenges`.`id`");
                }, 'participant_count');

            if (!empty($search)) {
                $Query = $Query->where(function ($q) use ($search) {
                    $q->where('title', 'LIKE', "%{$search}%");
                });
            }

            $totalData = count($Query->get());
            $totalFiltered = $totalData;

            $challengeData = $Query->offset($start)
                ->limit($limit)
                ->orderBy($order, $dir)
                ->get();

            $count = 0;
            foreach ($challengeData as $challenge) {
                $data[$count]['thumb'] = ($challenge->challenge_thumb_url!="") ? '<img src="'.$challenge->challenge_thumb_url.'" alt="Thumb Image" width="50" height="50">' : "";
                $data[$count]['title'] = $challenge->title;
                $data[$count]['mark'] = ($challenge->is_period_challenge==1) ? __('general.period_challenge') : __('general.challenge');
                $data[$count]['participants'] = $challenge->participant_count;

                $editBtn = '<a href="javascript:void(0)" role="button" onclick="editChallenge('.$challenge->id.')" class="btn btn-primary btn-sm mx-1" data-toggle="tooltip" data-original-title="Edit"><i class="fa fa-edit" style="font-size: 15px;"></i></a>';
                $seeBtn = '<a href="javascript:void(0)" role="button" onclick="seeChallenge('.$challenge->id.')" class="btn btn-primary btn-sm mx-1" data-toggle="tooltip" data-original-title=""><i class="fa fa-eye" style="font-size: 15px;"></i></a>';
                $addUserBtn = '<a href="javascript:void(0)" role="button" onclick="showUserList('.$challenge->id.')" class="btn btn-primary btn-sm mx-1" data-toggle="tooltip" data-original-title="">'.__('general.add_participants').'</a>';
                $data[$count]['action'] = "$editBtn $seeBtn $addUserBtn";

                $count++;
            }

            $jsonData = array(
                "draw" => intval($draw),
                "recordsTotal" => intval($totalData),
                "recordsFiltered" => intval($totalFiltered),
                "data" => $data
            );
            return response()->json($jsonData);
        } catch (Exception $ex) {
            Log::info('Exception all challenge list');
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

    public function editData($id){
        $challenge = Challenge::with(['challengeimages','challengedays'])->where('id',$id)->first();
        $challenge_cats = ChallengeCategory::where('challenge_type',ChallengeCategory::CHALLENGE)->orderBy('order','ASC')->get();
        $period_challenge_cats = ChallengeCategory::where('challenge_type',ChallengeCategory::PERIODCHALLENGE)->orderBy('order','ASC')->get();
        $thumbs = ChallengeThumb::where('category_id',$challenge->category_id)->orderBy('order','ASC')->get(['id','image']);

        $timeArr = explode(":",$challenge->verify_time);
        $time = isset($timeArr[2]) ? $timeArr[0].":".$timeArr[1] : $challenge->verify_time; //remove seconds
        $dbTime = \Carbon\Carbon::createFromFormat('H:i', $time, "UTC");
        $adminTime = $dbTime->setTimezone("Asia/Seoul");
        $adminTime = $adminTime->format('H:i');

        return view('challenge.challenge-page.edit-popup',compact('challenge','challenge_cats','period_challenge_cats','thumbs','adminTime'));
    }

    public function updateData(Request $request){
        $inputs = $request->all();
        try{
            DB::beginTransaction();
            $challenge = Challenge::where('id',$inputs['challenge_id'])->first();
            if($challenge->is_period_challenge==1) {
                $validator = Validator::make($inputs, [
                    'title' => 'required',
                    'day' => 'required|array',
                    'time' => 'required',
                    'deal_amount' => 'required',
                    'description' => 'required',
                    'start_date' => 'required',
                    'end_date' => 'required',
                    'category_id' => 'required',
                ], [
                    'title.required' => 'The title is required.',
                    'day.required' => 'Please select day.',
                    'time.required' => 'Verify time is required.',
                    'deal_amount.required' => 'The deal amount is required.',
                    'description.required' => 'The description is required.',
                    'start_date.required' => 'The starting date is required.',
                    'end_date.required' => 'The end date is required.',
                    'category_id.required' => 'Please select category.',
                ]);
            }
            else {
                $validator = Validator::make($inputs, [
                    'title' => 'required',
                    'time' => 'required',
                    'deal_amount' => 'required',
                    'description' => 'required',
                    'date' => 'required',
                    'category_id' => 'required',
                ],[
                    'category_id.required' => 'Please select category.',
                ]);
            }

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors(), 'success' => false]);
            }

            $adminTimezone = $this->getAdminUserTimezone();
            $timeArr = explode(":",$inputs['time']);
            $time = isset($timeArr[2]) ? $timeArr[0].":".$timeArr[1] : $inputs['time']; //remove seconds
            $inputTime = Carbon::createFromFormat('H:i', $time, "Asia/Seoul");
            $utcTime = $inputTime->setTimezone('UTC');
            $formattedUtcTime = $utcTime->format('H:i');

            Challenge::updateOrCreate([
                'id' => $challenge->id
            ],[
                'title' => $inputs['title'],
                'verify_time' => $formattedUtcTime,
                'deal_amount' => $inputs['deal_amount'],
                'description' => $inputs['description'],
                'start_date' => $inputs['start_date'] ?? null,
                'end_date' => $inputs['end_date'] ?? null,
                'date' => $inputs['date'] ?? null,
                'category_id' => $inputs['category_id'],
                'challenge_thumb_id' => $inputs['thumb_image'] ?? null,
            ]);
            if(isset($inputs['day'])){
                foreach ($inputs['day'] as $day){
                    ChallengeDay::firstOrCreate([
                        'challenge_id' => $challenge->id,
                        'day' => $day,
                    ]);
                }
                ChallengeDay::where('challenge_id',$challenge->id)->whereNotIn('day',$inputs['day'])->delete();
            }
            if (!empty($inputs['main_images'])) {
                $ChallengeFolder = config('constant.challenge');
                if (!Storage::exists($ChallengeFolder)) {
                    Storage::makeDirectory($ChallengeFolder);
                }
                foreach ($inputs['main_images'] as $image) {
                    if (is_file($image)) {
                        $fileType = $image->getMimeType();
                        $mainImage = Storage::disk('s3')->putFile($ChallengeFolder, $image, 'public');
                        $fileName = basename($mainImage);
                        $image_url = $ChallengeFolder . '/' . $fileName;

                        ChallengeImages::create([
                            'challenge_id' => $challenge->id,
                            'image' => $image_url,
                        ]);
                    }
                }
            }

            if (isset($inputs['remove_images'])){
                foreach ($inputs['remove_images'] as $image_id){
                    $image = ChallengeImages::whereId($image_id)->first();
                    if ($image) {
                        Storage::disk('s3')->delete($image->image);
                        ChallengeImages::where('id', $image_id)->delete();
                    }
                }
            }

            DB::commit();
            return response()->json(array('success' => true));
        }catch(\Exception $e){
            Log::info("$e");
            DB::rollBack();
            return response()->json(array('success' => false, 'messsage' => 'Something went wrong!!'));
        }
    }

    public function userList($id){
        $adminTimezone = $this->getAdminUserTimezone();
        $users = DB::table('users')->leftJoin('user_entity_relation', 'user_entity_relation.user_id', 'users.id')
            ->leftJoin('users_detail', 'users_detail.user_id', 'users.id')
            ->whereIN('user_entity_relation.entity_type_id', [EntityTypes::NORMALUSER, EntityTypes::HOSPITAL, EntityTypes::SHOP])
            ->whereNotNull('users.email')
            ->whereNull('users.deleted_at')
            ->whereIn('users.status_id', [Status::ACTIVE, Status::INACTIVE])
            ->where('users.app_type','challenge')
            ->select(
                'users.id',
                'users_detail.name',
                'users_detail.mobile',
                'users.email',
                'users.created_at as signup_date',
                'users.last_login as last_access'
            )
            ->selectSub(function($q) use($id){
                $q->select(DB::raw('count(*) as total'))->from('challenge_participated_users')->where('challenge_id',$id)->whereRaw("`challenge_participated_users`.`user_id` = `users`.`id`");
            }, 'selected_count')
            ->get();

        return view('challenge.challenge-page.show-users-popup', compact('users','adminTimezone','id'));
    }

    public function selectUsers(Request $request){
        $inputs = $request->all();
        try{
            DB::beginTransaction();

            if (isset($inputs['user_ids'])) {
                ChallengeParticipatedUser::where('challenge_id', $inputs['challenge_id'])
                    ->whereNotIn('user_id', $inputs['user_ids'])
                    ->delete();

                foreach ($inputs['user_ids'] as $user_id) {
                    ChallengeParticipatedUser::firstOrCreate([
                        'challenge_id' => $inputs['challenge_id'],
                        'user_id' => $user_id,
                    ]);
                }
            }
            else {
                ChallengeParticipatedUser::where('challenge_id', $inputs['challenge_id'])->delete();
            }

            DB::commit();
            return response()->json(array('success' => true));
        }catch(\Exception $e){
            Log::info("$e");
            DB::rollBack();
            return response()->json(array('success' => false));
        }
    }

    public function viewChallenge($id){
        $adminTimezone = $this->getAdminUserTimezone();
        $challenge = Challenge::with(['challengeimages','challengedays'])->where('id',$id)->first();
        $participants = ChallengeParticipatedUser::join('users_detail', 'users_detail.user_id', 'challenge_participated_users.user_id')
                        ->where('challenge_participated_users.challenge_id',$id)
                        ->select('users_detail.name')
                        ->get();
        $notVerifiedData = ChallengeVerify::with('verifiedimages')
            ->leftjoin('users_detail', function ($join) {
                $join->on('challenge_verify.user_id', '=', 'users_detail.user_id')
                    ->whereNull('users_detail.deleted_at');
            })
            ->leftjoin('challenges', function ($join) {
                $join->on('challenge_verify.challenge_id', '=', 'challenges.id');
            })
            ->where('challenge_verify.challenge_id',$id)
            ->where('challenge_verify.is_verified',0)
            ->select(
                'users_detail.name',
                'challenges.title',
                'challenge_verify.*'
            )->get();
        $VerifiedData = ChallengeVerify::with('verifiedimages')
            ->leftjoin('users_detail', function ($join) {
                $join->on('challenge_verify.user_id', '=', 'users_detail.user_id')
                    ->whereNull('users_detail.deleted_at');
            })
            ->leftjoin('challenges', function ($join) {
                $join->on('challenge_verify.challenge_id', '=', 'challenges.id');
            })
            ->where('challenge_verify.challenge_id',$id)
            ->where('challenge_verify.is_verified',1)
            ->select(
                'users_detail.name',
                'challenges.title',
                'challenge_verify.*'
            )->get();
        $allData = ChallengeVerify::with('verifiedimages')
            ->leftjoin('users_detail', function ($join) {
                $join->on('challenge_verify.user_id', '=', 'users_detail.user_id')
                    ->whereNull('users_detail.deleted_at');
            })
            ->leftjoin('challenges', function ($join) {
                $join->on('challenge_verify.challenge_id', '=', 'challenges.id');
            })
            ->where('challenge_verify.challenge_id',$id)
            ->select(
                'users_detail.name',
                'challenges.title',
                'challenge_verify.*'
            )->get();

        return view('challenge.challenge-page.view-challenge-popup', compact('challenge','adminTimezone','participants','notVerifiedData','VerifiedData','allData'));
    }

    public function getThumb(Request $request){
        try{
            DB::beginTransaction();
            $inputs = $request->all();

            $html = "";
            $html .= '<div class="d-flex align-items-center">
                    <div class="d-flex flex-wrap">';
            $thumbs = ChallengeThumb::where('category_id',$inputs['category_id'])->orderBy('order','ASC')->get(['id','image']);
            foreach ($thumbs as $thumb){
                $displayImage = Storage::disk('s3')->url($thumb->image);
                $noImage = asset('img/noImage.png');
                $html .= '<div class="removeImage">
                            <div style="background-image: url('.$displayImage.');" class="bgcoverimage image-item">
                                <img src="'.$noImage.'">
                            </div>
                            <input type="radio" name="thumb_image" value="'.$thumb->id.'" style="margin-right: 5px" />
                        </div>';
            }
            $html .= '</div>
                       </div>';

            DB::commit();
            return response()->json(array('success' => true, 'html' => $html));
        }catch(\Exception $e){
            Log::info("$e");
            DB::rollBack();
            return response()->json(array('success' => false));
        }
    }

}
