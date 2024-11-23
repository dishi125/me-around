<?php

namespace App\Http\Controllers;

use App\Jobs\DeleteAccountReasonMail;
use App\Models\DeleteAccountReason;
use App\Models\Status;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function getDelete(){
        return view('request-delete');
    }

    public function submitDelete(Request $request){
        try{
            DB::beginTransaction();
            $inputs = $request->all();
            $validator = Validator::make($inputs, [
                'email' => 'required|string|max:255',
                'password' => 'required|string',
                'reason' => 'required|string',
//                'acknowledge' => 'required',
            ], [
                'email.required' => 'The e-mail is required.',
                'password.required' => 'The password is required.',
//                'acknowledge.required' => 'Please acknowledge for remove account.',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors(), 'success' => false]);
            }

            $user = User::where('email',$inputs['email'])->where('status_id', Status::ACTIVE)->where('app_type','mearound')->first();
            if(empty($user)) {
                return response()->json(array('success' => false,'message' => 'Invalid credentials!!'));
            }

            if (Hash::check($inputs['password'], $user->password)) {
                if(strlen(trim($inputs['reason'])) < 10){
                    return response()->json(array('success' => false,'message' => 'Please enter atleast 10 words in reason.'));
                }
                $toDate = Carbon::parse(date('Y-m-d H:i:s', strtotime($user->created_at)));
                $fromDate = Carbon::parse(Carbon::now()->format('Y-m-d H:i:s'));
                $months = $toDate->diffInMonths($fromDate);
                if ($months < 1){
                    $toDate = Carbon::parse(date('Y-m-d H:i:s', strtotime('+1 month', strtotime($user->created_at))));
                    $diff_in_days = $toDate->diffInDays($fromDate);
                    $diff_in_hours = $toDate->diffInHours($fromDate);
                    $days = "days";
                    $day = "day";
                    $hours = "hours";
                    $hour = "hour";
                    if ($diff_in_hours >= 24){
                        $remain_cnt = ($diff_in_days>1)?($diff_in_days." $days"):($diff_in_days." $day");
                    }
                    else {
                        $remain_cnt = ($diff_in_hours>1)?($diff_in_hours." $hours"):($diff_in_hours." $hour");
                    }
                    $remain_delete = "Account deletion is possible $remain_cnt after account creation.";
                    return response()->json(array('success' => false,'message' => $remain_delete));
                }
                DeleteAccountReason::create([
                    'user_id' => $user->id,
                    'reason' => $inputs['reason'],
                ]);
                //send mail to admin
                $mailData = (object)[
                    'username' => $user->name,
                    'phone' => $user->mobile,
                    'reason' => $inputs['reason'],
                    'signup_date' => $user->created_at,
                ];
                DeleteAccountReasonMail::dispatch($mailData);

                DB::commit();
                return response()->json(array('success' => true, 'message' => 'Your account deletion request has been successfully submitted.'));
            } else {
                return response()->json(array('success' => false,'message' => 'Invalid credentials!!'));
            }
        }catch(\Exception $e){
            Log::info("$e");
            DB::rollBack();
            return response()->json(array('success' => false));
        }
    }

}
