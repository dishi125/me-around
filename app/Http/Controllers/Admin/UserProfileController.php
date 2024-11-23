<?php

namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;

use App\Models\User;
use App\Models\RoleUser;
use App\Models\Role;
use App\Models\Manager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\VerifyEmail;
use Hash;

class UserProfileController extends Controller
{
    public function editProfile(User $user)
    {
        $title = "Edit Profile";
        if ($user->email == env('ADMIN')) {
            $is_admin = 1;
            $id = $user->id;
        } else {
            $id = Auth::user()->id;
            $manager = Manager::where('user_id', $id)->first();
            $is_admin = 0;
            $user['name'] = $manager->name;
            $user['mobile'] = $manager->mobile;
        }
        $user['is_admin'] = $is_admin;
        return view('admin.user-profile.form', compact('title', 'user'));
    }

    public function updateProfile(Request $request, $id)
    {
        try {
            DB::beginTransaction();
            $inputs = $request->all();
            Log::info('Update profile code start.');
            $updateData = array();
            if (isset($inputs['name'])) {
                $updateData['name'] = $inputs['name'];
            }
            if (isset($inputs['mobile'])) {
                $updateData['mobile'] = $inputs['mobile'];
            }
            if (!empty($updateData)) {
                $updateUser = Manager::where('user_id', $id)->update($updateData);
            }

            DB::commit();
            Log::info('Update profile add code end.');
            return redirect()->route('admin.user.profile', $id)->with(['toastr' => ['success' => 'Profile Updated Successfully.']]);
        } catch (\Exception $ex) {
            DB::rollBack();
            Log::info('Update profile update code exception.');
            Log::info($ex);
            return redirect()->route('admin.user.profile', $id)->with(['toastr' => ['error' => 'Failed to update profile!']]);
        }
    }

    public function editPassword($id)
    {
        return view('admin.user-profile.change-password-popup', compact('id'));
    }

    public function updatePassword(Request $request)
    {
        try {
            DB::beginTransaction();
            $inputs = $request->all();
            Log::info('Update password code start.');

            $updateUser = User::where('id', $inputs['user_id'])->update([
                "password" => Hash::make($inputs['password']),
            ]);
            DB::commit();
            Log::info('Update password add code end.');
            return $this->sendSuccessResponse('Update password successfully.', 200);
        } catch (\Exception $ex) {
            DB::rollBack();
            Log::info('Update password update code exception.');
            Log::info($ex);
            return $this->sendFailedResponse('Password not updated.', 400);
        }
    }

    public function updateEmailMail(Request $request)
    {
        try {
            DB::beginTransaction();
            $inputs = $request->all();
            Log::info('Change email mail code start.');

            $role = RoleUser::where('user_id', $inputs['user_id'])->first();
            if ($role->role_id == Role::ADMIN) {
                $user = User::where('id', $inputs['user_id'])->first();
                $user['name'] = 'Admin';
            } else if ($role->role_id == Role::MANAGER) {
                $user = User::join('managers as M', 'M.user_id', '=', 'users.id')
                    ->where('users.id', $inputs['user_id'])
                    ->where('M.user_id', $inputs['user_id'])
                    ->first();
            }

            //$base_url = URL::to('/');
            $encrypt_email = base64_encode($user->email);
            $user['verify_link'] = route('user.profile.verify.email', $encrypt_email);
            $user['subject'] = 'Change Email';
            // mail code start
            Mail::to($user->email)->send(new VerifyEmail($user));
            DB::commit();
            Log::info('Change email add code end.');
            return $this->sendSuccessResponse('Change mail link sent to your mail id', 200);
        } catch (\Exception $ex) {
            DB::rollBack();
            Log::info('Change email update code exception.');
            Log::info($ex);
            return $this->sendFailedResponse('Failed to update email.', 400);
        }
    }

    public function verifyUserEmail($email)
    {
        Auth::logout();
        $title = "Edit Email";
        $userEmail = base64_decode($email);
        $user = User::where('email', $userEmail)->first();
        return view('admin.user-profile.change-email', compact('title', 'user'));
    }

    public function updateUserEmail(Request $request)
    {
        $inputs = $request->all();

        Log::info('Update password code start.');
        $request->validate([
            'email' => 'required|string|max:255|unique:users,email,NULL,id,deleted_at,NULL',
        ]);
        try {
            DB::beginTransaction();

            $updateUser = User::where('id', $inputs['user_id'])->update(["email" => $inputs['email']]);
            DB::commit();
            Log::info('Update email code end.');
            return redirect()->route('login')->with('info', 'Email updated successfully!');
        } catch (\Exception $ex) {
            DB::rollBack();
            Log::info('Update email code exception.');
            Log::info($ex);
            return redirect()->route('login')->with('error', 'Failed to update email!');
        }
    }

    //check email is already exist or not
    public function checkEmailExist(Request $request)
    {
        Log::info("start code check email of user");
        $email = $request->email;
        $emailCnt = User::where('email', $email)->count();
        if ($emailCnt > 0) {
            echo 'false';
        } else {
            echo 'true';
        }
        Log::info("end code check email of user");
    }
    public function logout()
    {
        $user = Auth::user();
        $this->logUserLogoutDetails($user);
        Auth::logout();

        return redirect(route('login'))->withInfo('You have successfully logged out!');
    }
}
