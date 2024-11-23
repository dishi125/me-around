<?php

namespace App\Traits;
// use App\Models\Activity;
use carbon\carbon;
use Cache;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Response;
use Spatie\Activitylog\Models\Activity;

trait ActivityTraits
{
   public function logCreatedActivity($activity,$changes,$logModel,$request)
   {
       $activity = activity($activity)
           ->causedBy(\Auth::user())
           ->performedOn($logModel)
           ->withProperties(['attributes'=>$request])
           ->log($changes);
       $lastActivity = Activity::all()->last();

       return true;
   }

   public function logUpdatedActivity($activity,$changes,$list,$before,$list_changes)
   {
       unset($list_changes['updated_at']);
       $old_keys = [];
       $old_value_array = [];
       if(empty($list_changes)){
           $changes = 'No attribute changed';

       }else{

           if(count($before)>0){

               foreach($before as $key=>$original){
                   if(array_key_exists($key,$list_changes)){

                       $old_keys[$key]=$original;
                   }
               }
           }
           $old_value_array = $old_keys;
       }

       $properties = [
           'attributes'=>$list_changes,
           'old' =>$old_value_array
       ];

       $activity = activity($activity)
           ->causedBy(\Auth::user())
           ->performedOn($list)
           ->withProperties($properties)
           ->log($changes);

       return true;
   }

   public function logDeletedActivity($activity,$changes,$list)
   {
       $attributes = $this->unsetAttributes($list);

       $properties = [
           'attributes' => $attributes->toArray()
       ];

       $activity = activity($activity)
           ->causedBy(\Auth::user())
           ->performedOn($list)
           ->withProperties($properties)
           ->log($changes);

       return true;
   }

   public function logLoginDetails($user)
   {
       $updated_at = Carbon::now()->format('d/m/Y H:i:s');
       $properties = [
           'attributes' =>['name'=>$user->username,'description'=>'Login into the system by '.$updated_at]
       ];

       $changes = 'User '.$user->username.' loged in into the system';

       $activity = activity()
           ->causedBy(\Auth::user())
           ->performedOn($user)
           ->withProperties($properties)
           ->log($changes);

       return true;
   }

   public function logUserLogoutDetails($user)
   {
       $updated_at = Carbon::now()->format('d/m/Y H:i:s');
       $properties = [
           'attributes' =>['name'=>$user->username,'description'=>'Logged out of the system by '.$updated_at]
       ];

       $changes = 'User logged out of the system';

       $activity = activity('Logout')
           ->causedBy(\Auth::user())
           ->performedOn($user)
           ->withProperties($properties)
           ->log($changes);

       return true;
   }

   public function unsetAttributes($model){
       unset($model->created_at);
       unset($model->updated_at);
       return $model;
   }

}
