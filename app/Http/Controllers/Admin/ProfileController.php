<?php

namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Manager;
use App\Models\UserDetail;
use Illuminate\Support\Facades\Hash;
use Auth;
use Log, Storage, Crypt;
use Validator;
use Illuminate\Validation\ValidationException;

class ProfileController extends Controller
{
    public function edit($header)
    {
        try {
            Log::info("Start Get Profile Data:");
            $title = "Profile";
            $userDetail = Auth::user();
            Log::info("End Get Profile Data.");
            return view('admin.profile', compact('title', 'userDetail','header'));
        } catch (\Exception $ex) {
            Log::info("Exception on Get Profile Data:");
            Log::info($ex);
            return redirect()->back();
        }
    }

    public function update(Request $request,$id)
    {
        try {
            Log::info("Update Profile Data");
            $inputs = $request->all();
            $validator = Validator::make($request->all(), [
                'name' => 'required',
                'avatar' => 'mimes:jpeg,jpg,bmp,png',
            ]);
            if ($validator->fails()) {
                notify()->error("Validation Error", "Error", "topRight");
                return redirect()->back()->withErrors($validator)->withInput();
            }
            $data = [
                "name" => $inputs['name'],
                "mobile" => $inputs['mobile'],
            ];

            $manager = Manager::where('user_id',$id)->first();
            
            $userData = UserDetail::where('user_id',$id)->first();
            
            if ($request->hasFile('avatar') && !empty($manager)) {
                $profileFolder = config('constant.profile');                
                if (!Storage::exists($profileFolder)) {
                    Storage::makeDirectory($profileFolder);
                }
                Storage::disk('s3')->delete($manager->avatar);
                $avatar = Storage::disk('s3')->putFile($profileFolder, $request->file('avatar'),'public');
                $fileName = basename($avatar);
                $data['avatar'] = $profileFolder . '/' . $fileName;
            }       
            
            if ($request->hasFile('avatar') && !empty($userData)) {
                $profileFolder = config('constant.profile');                
                if (!Storage::exists($profileFolder)) {
                    Storage::makeDirectory($profileFolder);
                }
                Storage::disk('s3')->delete($userData->avatar);
                $avatar = Storage::disk('s3')->putFile($profileFolder, $request->file('avatar'),'public');
                $fileName = basename($avatar);
                $data['avatar'] = $profileFolder . '/' . $fileName;
            } 

            $categoryData = Manager::where('user_id',$id)->update($data);
          

            if(!empty($userData)){
                $userData->update($data);
            }

            Log::info("End Update Profile Data");
            notify()->success("Profile Updated Successfully", "Success", "topRight");
            return redirect()->back();
        } catch (\Exception $ex) {
            Log::info("Exception on Update Profile Data:");
            Log::info($ex);
            notify()->error("Unable to Update Profile", "Error", "topRight");
            return redirect()->back();
        }
    }

    public function changePassword(Request $request,$id)
    { 
        try {
            Log::info('Change Password');

            $validator = Validator::make($request->all(), [
                'old_password' => 'required|min:6',
                'password' => 'required|min:6',
                'password_confirm' => 'required|min:6|same:password'
            ], [], [
                'old_password' => 'Old Password',
                'password' => 'New Password',
                'password_confirm' => 'Confirm Password'
            ]);

            if ($validator->fails()) {
                notify()->error("Validation Error", "Error", "topRight");
                return redirect()->back()->withErrors($validator);
            }

            $old_password = Auth::User()->password;
            if (Hash::check($request->old_password, $old_password)) {
                $user = User::find(Auth::User()->id);
                $user->password = Hash::make($request->password);
                $user->save();
                Log::info('End Change Password');
                notify()->success("Password Changed Successfully & Please Logout", "Success", "topRight");
                return redirect()->back();
            } else {
                notify()->error("Please Enter Correct Old Password", "Error", "topRight");
                return redirect()->back()->withErrors($validator);
            }
        } catch (\Exception $ex) {
            Log::info("Exception in Change Password:");
            Log::info($ex);
            notify()->error("Unable to Change Password", "Error", "topRight");
            return redirect()->back();
        }
    }
}
