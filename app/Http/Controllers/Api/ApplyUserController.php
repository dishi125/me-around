<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppliedUsersChat;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ApplyUserController extends Controller
{
    public function searchUser(Request $request)
    {
        $user = Auth::user();
        $inputs = $request->all();
        try {
            DB::beginTransaction();

            if ($user) {
                $validation = Validator::make($request->all(), [
                    'country' => 'required',
                ]);

                if ($validation->fails()) {
                    return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
                }

                if (isset($inputs['search']) && $inputs['search']!="")
                {
                    $res_data = DB::table('users')->join('users_detail', 'users_detail.user_id', 'users.id')
                        ->join('node_user_countries', 'node_user_countries.from_user_id', 'users.id')
                        ->whereNull('users.deleted_at')
                        ->where('node_user_countries.country', $inputs['country'])
                        ->where(function ($q) use ($inputs) {
                            $q->where('users_detail.name', 'LIKE', "%{$inputs['search']}%")
                                ->orWhere('users.email', 'LIKE', "%{$inputs['search']}%");
                        })
                        ->select(['users.id', 'users_detail.name', 'users.email'])
                        ->get();
                }
                else {
                    $res_data = AppliedUsersChat::join('users','users.id','applied_users_chat.applied_user_id')
                        ->join('users_detail','users_detail.user_id','applied_users_chat.applied_user_id')
                        ->where('applied_users_chat.admin_user_id',$user->id)
                        ->where('applied_users_chat.country',$inputs['country'])
                        ->distinct('applied_users_chat.applied_user_id')
                        ->orderBy('applied_users_chat.id','DESC')
                        ->limit(12)
                        ->select(['users.id', 'users_detail.name', 'users.email'])
                        ->get();
                }
                $data['my_account'] = [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ];
                $data['recent_selected'] = $res_data;

                DB::commit();
                return $this->sendSuccessResponse("search data get successfully.", 200, $data);
            } else {
                return $this->sendSuccessResponse(Lang::get('messages.user.token_expired'), 401);
            }
        } catch (\Exception $e) {
            Log::info($e);
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function applyUser(Request $request)
    {
        $user = Auth::user();
        $inputs = $request->all();
        try {
            DB::beginTransaction();

            if ($user) {
                $validation = Validator::make($request->all(), [
                    'user_id' => 'required',
                    'country' => 'required',
                ]);

                if ($validation->fails()) {
                    return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
                }

                if ($inputs['user_id'] != $user->id){
                    AppliedUsersChat::create([
                        'admin_user_id' => $user->id,
                        'applied_user_id' => $inputs['user_id'],
                        'country' => $inputs['country'],
                    ]);
                }

                DB::commit();
                return $this->sendSuccessResponse("User applied successfully.", 200);
            } else {
                return $this->sendSuccessResponse(Lang::get('messages.user.token_expired'), 401);
            }
        } catch (\Exception $e) {
            Log::info($e);
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function recentAppliedUser(Request $request)
    {
        $user = Auth::user();
        $inputs = $request->all();
        try {
            DB::beginTransaction();

            if ($user) {
                $validation = Validator::make($request->all(), [
                    'country' => 'required',
                ]);

                if ($validation->fails()) {
                    return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
                }

                $applied_data = AppliedUsersChat::join('users','users.id','applied_users_chat.applied_user_id')
                            ->join('users_detail','users_detail.user_id','applied_users_chat.applied_user_id')
                            ->where('applied_users_chat.admin_user_id',$user->id)
                            ->where('applied_users_chat.country',$inputs['country'])
                            ->distinct('applied_users_chat.applied_user_id')
                            ->orderBy('applied_users_chat.id','DESC')
                            ->limit(12)
                            ->select(['users.id','users.email','users_detail.name'])
                            ->get();
//                dd($res_data->toArray());

                $data['my_account'] = [
                    'id' => $user->id,
                    'email' => $user->email,
                    'name' => $user->name,
                ];
                $data['recent_selected'] = $applied_data;
                DB::commit();
                return $this->sendSuccessResponse("Users get successfully.", 200, $data);
            } else {
                return $this->sendSuccessResponse(Lang::get('messages.user.token_expired'), 401);
            }
        } catch (\Exception $e) {
            Log::info($e);
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

}
