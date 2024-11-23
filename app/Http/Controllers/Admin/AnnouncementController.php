<?php

namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Log;
use Storage;
use Validator;
use App\Models\Category;
use App\Models\CategoryTypes;
use App\Models\Status;
use App\Models\EntityTypes;
use App\Models\UserEntityRelation;
use App\Models\UserDetail;
use App\Models\Notice;
use App\Models\PostLanguage;
use App\Models\UserDevices;
use App\Util\Firebase;

class AnnouncementController extends Controller
{
    protected $firebase;

    function __construct()
    {
        $this->firebase = new Firebase();
        $this->middleware('permission:announcement-list', ['only' => ['index']]);
    }  
    /* ================= Announcement code start ======================== */
    public function index()
    {
        $title = "Announcement";
        $beautyCategory = Category::where('status_id', Status::ACTIVE)
                        ->where('type', 'default')
                        ->where('category_type_id', CategoryTypes::SHOP)
                        ->pluck('name','id');       
        $supportLanguage = PostLanguage::where('is_support',1)->get();
        $annoncementList = ''; 
        return view('admin.announcement.index', compact('title','annoncementList','beautyCategory','supportLanguage'));
    }

    public function store(Request $request)
    {        
        try {
            $inputs = $request->all();
            DB::beginTransaction();
            Log::info('Start code for the add announcement');
            
            $language_id = $inputs['language_id'] ?? 4; // Default 4 for English 
            $categoryIDs = [];
            foreach($inputs['announcement_checkbox'] as $value) {
                $userIds = [];
                if($value == 'normal-user'){
                    $userIds = UserEntityRelation::join('users_detail','users_detail.user_id','user_entity_relation.user_id')
                                ->where('entity_type_id',EntityTypes::NORMALUSER)
                                ->where(function($query) use ($language_id){
                                    if($language_id != 'all'){
                                        $query->where('users_detail.language_id',$language_id);
                                    }
                                })
                                ->groupBy('user_entity_relation.user_id')
                                ->pluck('user_entity_relation.user_id');
                } else if ($value == 'hospital'){
                    $userIds = UserEntityRelation::join('users_detail','users_detail.user_id','user_entity_relation.user_id')
                                ->where('entity_type_id',EntityTypes::HOSPITAL)
                                ->where(function($query) use ($language_id){
                                    if($language_id != 'all'){
                                        $query->where('users_detail.language_id',$language_id);
                                    }
                                })
                                ->groupBy('user_entity_relation.user_id')
                                ->pluck('user_entity_relation.user_id');
                } else {
                    $categoryIDs[] = $value;
                    
                }
                
                $this->sendAnnouncementDetails($userIds,$inputs);

            }

            if(!empty($categoryIDs)){
                $catUserIds = UserEntityRelation::join('users_detail','users_detail.user_id','user_entity_relation.user_id')
                            ->join('shops','shops.user_id','user_entity_relation.user_id')
                            ->whereIn('shops.category_id',$categoryIDs)
                            ->where('user_entity_relation.entity_type_id',EntityTypes::SHOP)
                            ->where(function($query) use ($language_id){
                                if($language_id != 'all'){
                                    $query->where('users_detail.language_id',$language_id);
                                }
                            })
                            ->groupBy('user_entity_relation.user_id')
                            ->pluck('user_entity_relation.user_id');
                $this->sendAnnouncementDetails($catUserIds,$inputs);
            }
            DB::commit();
            Log::info('End the code for the add announcement');
            notify()->success("Announcement send successfully", "Success", "topRight");
            return redirect()->route('admin.announcement.index');
        } catch (\Exception $e) {
            Log::info('Exception in the add announcement');
            Log::info($e);
            notify()->error("Failed to send announcement", "Error", "topRight");
            return redirect()->route('admin.announcement.index');
        }
    }

    public function sendAnnouncementDetails($userIds,$inputs){
        foreach($userIds as $uId) {
            $user_detail = UserDetail::where('user_id', $uId)->first();
            $language_id = $user_detail->language_id;
            $key = Notice::ADMIN_NOTICE.'_'.$language_id;

            $notice = Notice::create([
                'notify_type' => Notice::ADMIN_NOTICE,
                'user_id' => $uId,
                'to_user_id' => $uId,
                'title' => $inputs['text'],
                'sub_title' => $inputs['link'],
            ]);

            $devices = UserDevices::whereIn('user_id', [$uId])->pluck('device_token')->toArray();
            $format = __("notice.$key");
            $title_msg = '';
            $notify_type = 'announcement';
            
            $notificationData = [
                'link' => $inputs['link'],
                'text' => $inputs['text'],
            ];
            if (count($devices) > 0) {
                $result = $this->sentPushNotification($devices,$title_msg, $format, $notificationData ,$notify_type);                        
            }
        }
    }
    /* ================= Announcement code end ======================== */
    
}
