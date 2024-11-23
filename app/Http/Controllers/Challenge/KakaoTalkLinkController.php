<?php

namespace App\Http\Controllers\Challenge;

use App\Http\Controllers\Controller;
use App\Models\ChallengeKakaoTalkLink;
use App\Models\ChallengeMenu;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class KakaoTalkLinkController extends Controller
{
    public function index(Request $request)
    {
        $title = 'Open kakao talk link';
        $link = ChallengeKakaoTalkLink::where('id',1)->first();

        return view('challenge.kakao-talk-link.index', compact('title','link'));
    }

    public function editData()
    {
        $link = ChallengeKakaoTalkLink::where('id',1)->first();
        return view('challenge.kakao-talk-link.edit-popup',compact('link'));
    }

    public function updateData(Request $request){
        try{
            DB::beginTransaction();
            $inputs = $request->all();
            $validator = Validator::make($inputs, [
                'link' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors(), 'success' => false]);
            }

            ChallengeKakaoTalkLink::updateOrCreate([
                'id' => 1
            ],[
                'link' => $inputs['link']
            ]);

            DB::commit();
            return response()->json(array('success' => true));
        }catch(\Exception $e){
            Log::info("$e");
            DB::rollBack();
            return response()->json(array('success' => false));
        }
    }

}
