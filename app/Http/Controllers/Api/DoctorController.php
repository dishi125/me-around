<?php

namespace App\Http\Controllers\Api;

use App\Models\Doctor;
use App\Models\HospitalDoctor;
use App\Models\Status;
use App\Models\Hospital;
use App\Validators\DoctorValidator;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Validator;


class DoctorController extends Controller
{
    private $doctorValidator;

    function __construct()
    {
        $this->doctorValidator = new DoctorValidator();
    }   
   
    public function addDoctor(Request $request)
    {
        $user = Auth::user();
        $inputs = $request->all();
        try {
            Log::info('Start code for add doctor');   
            if($user){
                DB::beginTransaction();
                $hospitalExists = Hospital::find($inputs['hospital_id']);
                if($hospitalExists){
                    $validation = $this->doctorValidator->validateStore($inputs);
                    if ($validation->fails()) {
                        Log::info('End code for add doctor');
                        return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
                    }
                    $requestData = [
                        'name' => $inputs['name'],
                        'gender' => $inputs['gender'],
                        'specialty' => $inputs['specialty'],
                    ];                    
        
                                 
                    if(!empty($inputs['avatar'])){
                        $doctorsFolder = config('constant.doctors');                     
                    
                        if (!Storage::exists($doctorsFolder)) {
                            Storage::makeDirectory($doctorsFolder);
                        }  
                            $mainProfile = Storage::disk('s3')->putFile($doctorsFolder, $inputs['avatar'],'public');
                            $fileName = basename($mainProfile);
                            $image_url = $doctorsFolder . '/' . $fileName;
                            $requestData['avatar'] = $image_url;
                    }                        
        
                   $doctor = Doctor::create($requestData);
                   $hospitalDoctor = HospitalDoctor::create(['hospital_id' => $inputs['hospital_id'], 'doctor_id' => $doctor->id]);
                   DB::commit();
                   Log::info('End code for the add doctor');
                   return $this->sendSuccessResponse(Lang::get('messages.doctor.add-success'), 200, $doctor);
                }else{
                    Log::info('End code for the add doctor');
                    return $this->sendSuccessResponse(Lang::get('messages.hospital.empty'), 402);
                }
            }else{
                Log::info('End code for add doctor');
                return $this->sendSuccessResponse(Lang::get('messages.user.token_expired'), 401);
            }   
        } catch (\Exception $e) {
            Log::info('Exception in add doctor');
            Log::info($e);
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function editDoctor($id)
    {
        $user = Auth::user();
        try {
            Log::info('Start code for edit doctor');   
            if($user){
                $doctor = Doctor::find($id);
                if($doctor){                   
                   Log::info('End code for the edit doctor');
                   return $this->sendSuccessResponse(Lang::get('messages.doctor.edit-success'), 200, $doctor);
                }else{
                    Log::info('End code for the edit doctor');
                    return $this->sendSuccessResponse(Lang::get('messages.doctor.empty'), 402);
                }
            }else{
                Log::info('End code for add doctor');
                return $this->sendSuccessResponse(Lang::get('messages.user.token_expired'), 401);
            }   
        } catch (\Exception $e) {
            Log::info('Exception in add doctor');
            Log::info($e);
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function updateDoctor(Request $request,$id)
    {
        $user = Auth::user();
        $inputs = $request->all();
        try {
            Log::info('Start code for update doctor');   
            if($user){
                DB::beginTransaction();
                $doctorExists = Doctor::find($id);
                if($doctorExists){
                    $validation = $this->doctorValidator->validateUpdate($inputs);
                    if ($validation->fails()) {
                        Log::info('End code for update doctor');
                        return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
                    }
                    $requestData = [
                        'name' => $inputs['name'],
                        'gender' => $inputs['gender'],
                        'specialty' => $inputs['specialty'],
                    ];                    
        
                                 
                    if(!empty($inputs['avatar'])){
                        $doctorsFolder = config('constant.doctors');                     
                    
                        if (!Storage::exists($doctorsFolder)) {
                            Storage::makeDirectory($doctorsFolder);
                        }  
                        Storage::disk('s3')->delete($doctorExists->avatar);
                        $mainProfile = Storage::disk('s3')->putFile($doctorsFolder, $inputs['avatar'],'public');
                        $fileName = basename($mainProfile);
                        $image_url = $doctorsFolder . '/' . $fileName;
                        $requestData['avatar'] = $image_url;
                    }                        
        
                    Doctor::where('id', $id)->update($requestData);
                   $doctor = Doctor::find($id);
                   DB::commit();
                   Log::info('End code for the update doctor');
                   return $this->sendSuccessResponse(Lang::get('messages.doctor.update-success'), 200, $doctor);
                }else{
                    Log::info('End code for the update doctor');
                    return $this->sendSuccessResponse(Lang::get('messages.doctor.empty'), 402);
                }
            }else{
                Log::info('End code for update doctor');
                return $this->sendSuccessResponse(Lang::get('messages.user.token_expired'), 401);
            }   
        } catch (\Exception $e) {
            Log::info('Exception in update doctor');
            Log::info($e);
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function deleteDoctor($id)
    {
        $user = Auth::user();
        try {
            Log::info('Start code for delete doctor');   
            if($user){
                DB::beginTransaction();
                $doctor = Doctor::find($id);               
                Storage::disk('s3')->delete($doctor->avatar);   
                HospitalDoctor::where('doctor_id',$id)->delete();                 
                Doctor::where('id', $id)->delete();
                DB::commit();
                Log::info('End code for the delete doctor');
                return $this->sendSuccessResponse(Lang::get('messages.doctor.delete-success'), 200, []);
               
            }else{
                Log::info('End code for delete doctor');
                return $this->sendSuccessResponse(Lang::get('messages.user.token_expired'), 401);
            }   
        } catch (\Exception $e) {
            Log::info('Exception in delete doctor');
            Log::info($e);
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function listDoctor(Request $request)
    {
        $user = Auth::user();
        $inputs = $request->all();
        try {
            Log::info('Start code for list doctor');   
            if($user){
                DB::beginTransaction();
                $validation = $this->doctorValidator->validateList($inputs);
                    if ($validation->fails()) {
                        Log::info('End code for add doctor');
                        return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
                    }              
                                          
        
                   $doctors = Doctor::join('hospital_doctors','hospital_doctors.doctor_id','doctors.id')
                                    ->where('hospital_doctors.hospital_id',$inputs['hospital_id'])
                                    ->get(['doctors.*']);
                   DB::commit();
                   Log::info('End code for the list doctor');
                   return $this->sendSuccessResponse(Lang::get('messages.doctor.list-success'), 200, compact('doctors'));
                
            }else{
                Log::info('End code for list doctor');
                return $this->sendSuccessResponse(Lang::get('messages.user.token_expired'), 401);
            }   
        } catch (\Exception $e) {
            Log::info('Exception in list doctor');
            Log::info($e);
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }
}
