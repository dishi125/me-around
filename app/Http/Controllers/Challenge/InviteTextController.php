<?php

namespace App\Http\Controllers\Challenge;

use App\Http\Controllers\Controller;
use App\Models\Challenge;
use App\Models\ChallengeInviteText;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class InviteTextController extends Controller
{
    public function index(Request $request)
    {
        $title = 'Invite text';
        $inviteText = ChallengeInviteText::select('text')->first();
        return view('challenge.invite-text.index', compact('title','inviteText'));
    }

    public function saveInviteText(Request $request){
        try{
            DB::beginTransaction();
            $inputs = $request->all();
            $validator = Validator::make($inputs, [
                'invite_text' => 'required',
            ], [
                'invite_text.required' => 'The text is required.',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors(), 'success' => false]);
            }

            $data = ChallengeInviteText::updateOrCreate([
               'id' => 1,
            ], [
                'text' => $inputs['invite_text'],
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
