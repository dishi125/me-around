<?php

namespace App\Http\Controllers\Challenge;

use App\Http\Controllers\Controller;
use App\Models\ChallengeVerify;
use App\Models\ChallengeVerifyImage;
use App\Models\UserDevices;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;

class VerificationController extends Controller
{
    public function index()
    {
        $title = "Verification";
        ChallengeVerify::where('is_admin_read', 0)->update(['is_admin_read' => 1]);

        return view('challenge.verification.index', compact('title'));
    }

    public function getJsonAllData(Request $request)
    {
        $columns = array(
            0 => 'users_detail.name',
            1 => 'challenges.title',
            2 => 'challenge_verify.date',
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

            $Query = ChallengeVerify::with('verifiedimages')
                    ->leftjoin('users_detail', function ($join) {
                        $join->on('challenge_verify.user_id', '=', 'users_detail.user_id')
                            ->whereNull('users_detail.deleted_at');
                    })
                    ->leftjoin('challenges', function ($join) {
                        $join->on('challenge_verify.challenge_id', '=', 'challenges.id');
                    })
                    ->select(
                        'users_detail.name',
                        'challenges.title',
                        'challenge_verify.*'
                    );

            if (!empty($search)) {
                $Query = $Query->where(function ($q) use ($search) {
                    $q->where('users_detail.name', 'LIKE', "%{$search}%")
                        ->orWhere('challenges.title', 'LIKE', "%{$search}%");
                });
            }

            $totalData = count($Query->get());
            $totalFiltered = $totalData;

            $verifiedData = $Query->offset($start)
                ->limit($limit)
                ->orderBy($order, $dir)
                ->get();
//            dd($verifiedData->toArray());

            $count = 0;
            foreach ($verifiedData as $verified) {
                $data[$count]['user_name'] = $verified->name;
                $data[$count]['challenge_name'] = '<p onclick="seeChallenge('.$verified->challenge_id.')" style="cursor: pointer;">'.$verified->title.'</p>';
                $data[$count]['date'] = $verified->date;

                $images = '';
                foreach ($verified->verifiedimages as $key=>$img) {
                    if ($key==0){
                        $time = $this->formatDateTimeCountryWise($img->created_at,$adminTimezone);
                    }
                    $images .= ($img) ? '<img src="' . $img->image_url . '" class="reported-client-images pointer m-1" width="50" height="50" onclick="showImage(`'.$img->image_url.'`,'.$verified->id.')"/>' : '';
                }
                $data[$count]['images'] = $images;
                $data[$count]['time'] = isset($time) ? $time : "";

                $checked_reject = "";
                if ($verified->is_rejected==1){
                    $checked_reject = "checked";
                }
                $checkboxReject = '<div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" id="reject_checkbox" value="1" name="reject" verify-id="'.$verified->id.'"'.$checked_reject.'></div>';
                $data[$count]['reject'] = "$checkboxReject";

                $checked = $mark_html = "";
                if ($verified->is_verified==1){
                    $checked = "checked";
                    $mark_html = '<label class="form-check-label" for="verified_checkbox">'.__('datatable.verification.verified').'</label>';
                }
                $checkbox = '<div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" id="verified_checkbox" value="1" name="verified" '.$checked.' verify-id="'.$verified->id.'">'.$mark_html.'</div>';
                $editBtn = '<a href="javascript:void(0)" role="button" onclick="" class="btn btn-primary btn-sm mx-1" data-toggle="tooltip" data-original-title="Edit"><i class="fa fa-edit"></i></a>';
                $deleteBtn = '<a href="javascript:void(0)" role="button" onclick="" class="btn btn-primary btn-sm mx-1" data-toggle="tooltip" data-original-title="Delete"><i class="fa fa-trash"></i></a>';
                $data[$count]['action'] = "$checkbox $editBtn $deleteBtn";

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
            Log::info('Exception all thumb list');
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

    public function updateVerify(Request $request){
        $inputs = $request->all();
        try{
            DB::beginTransaction();

            $verify_id = $inputs['verify_id'] ?? '';
            $isChecked = ($inputs['checked'] == 1) ? 1 : 0;
            if(!empty($verify_id)){
                ChallengeVerify::where('id',$verify_id)->update(['is_verified' => $isChecked]);
            }

            if ($isChecked==1) {
                $challenge = ChallengeVerify::join('challenges', 'challenges.id', 'challenge_verify.challenge_id')
                    ->where('challenge_verify.id', $verify_id)
                    ->select('challenge_verify.user_id', 'challenges.title')
                    ->first();
                $devices = UserDevices::where('user_id', $challenge->user_id)->pluck('device_token')->toArray();
                if (count($devices) > 0) {
                    $result = $this->sentPushNotification($devices, "Challenge verified", "'$challenge->title' 가 인증됨", [], "challenge_verified");
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

    public function updateReject(Request $request){
        $inputs = $request->all();
        try{
            DB::beginTransaction();

            $verify_id = $inputs['verify_id'] ?? '';
            $isChecked = ($inputs['checked'] == 1) ? 1 : 0;
            if(!empty($verify_id)){
                ChallengeVerify::where('id',$verify_id)->update(['is_rejected' => $isChecked]);
            }

            DB::commit();
            return response()->json(array('success' => true));
        }catch(\Exception $e){
            Log::info("$e");
            DB::rollBack();
            return response()->json(array('success' => false));
        }
    }

    public function viewImages(Request $request){
        $inputs = $request->all();
        try{
            DB::beginTransaction();

            $images = ChallengeVerifyImage::where('challenge_verify_id',$inputs['verify_id'])->get();
            $active_image = $inputs['activeImage'];
            $challenge_verify = ChallengeVerify::where('id',$inputs['verify_id'])->first();
            $html = View::make('challenge.verification.view-image-popup',compact('images','active_image','challenge_verify'))->render();

            DB::commit();
            return response()->json(array('success' => true,'html' => $html));
        }catch(\Exception $e){
            Log::info("$e");
            DB::rollBack();
            return response()->json(array('success' => false));
        }
    }

}
