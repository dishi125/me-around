<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Status;
use App\Models\Shop;
use App\Models\UserDetail;
use App\Models\CreditPlans;
use App\Models\EntityTypes;
use App\Models\UserCredit;
use App\Models\ShopPrices;
use App\Models\ShopPost;
use App\Models\Hospital;
use App\Models\HospitalDoctor;
use App\Models\Post;
use App\Models\Notice;
use App\Models\UserDevices;
use Carbon\Carbon;
use App\Util\Firebase;

class CreditCheckCron extends Command
{
    protected $firebase;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'credit-check:cron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->firebase = new Firebase();
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $shops = Shop::whereIn('status_id',[Status::ACTIVE,Status::INACTIVE,Status::PENDING])->where('deactivate_by_user',0)->get();
        $temp = [];
        foreach($shops as $shop){            
            $devices = [];
            $user_detail = UserDetail::where('user_id', $shop->user_id)->first();
            if($user_detail){
            $creditPlan = CreditPlans::where('entity_type_id', EntityTypes::SHOP)->where('package_plan_id', $user_detail->package_plan_id)->first();
            $total_user_shops = Shop::where('deactivate_by_user',0)->where('user_id', $shop->user_id)->count();
            $defaultCredit = $creditPlan ? $creditPlan->amount : 0;
            $minShopCredit = $defaultCredit * $total_user_shops;
            if($defaultCredit) {             

                $notificationData = [
                    'id' => $shop->id,
                    'main_name' => $shop->main_name,
                    'shop_name' => $shop->shop_name,
                    'category_id' => $shop->category_id,
                    'category_name' => $shop->category_name,
                    'category_icon' => $shop->category_icon,
                ];
                
                $userCredits = UserCredit::where('user_id',$shop->user_id)->first(); 
                if($userCredits->credits < $minShopCredit) {
                    Shop::where('id',$shop->id)->update(['status_id' => Status::INACTIVE]);
                    if(!in_array($shop->user_id, $temp)) {
                        
                        // $devices = UserDetail::whereIn('user_id', [$shop->user_id])->pluck('device_token')->toArray();
                        // $user_detail = UserDetail::where('user_id', $shop->user_id)->first();
                        // $language_id = $user_detail->language_id;
                        // $title_msg = '';
                        // $temp[] = $shop->user_id;    
                        // $key = Notice::OUT_OF_COINS.'_'.$language_id;
                        // $format = __("notice.$key");
                        // $notify_type = Notice::OUT_OF_COINS;
                        // $notice = Notice::create([
                        //     'notify_type' => Notice::OUT_OF_COINS,
                        //     'user_id' => $shop->user_id,
                        //     'to_user_id' => $shop->user_id,
                        //     'entity_type_id' => EntityTypes::SHOP,
                        // ]);
                    }    

                }else {
                    $currentShop = Shop::where('id',$shop->id)->first();
            
                    $shopPrices = ShopPrices::join('shop_price_category','shop_price_category.id','shop_prices.shop_price_category_id')
                            ->where('shop_price_category.shop_id',$shop->id)->count();
            
                    $shopPosts = ShopPost::where('shop_id',$shop->id)->count();
            
                    $isShopPost = $shopPosts >= 3  ? true : false;
                    $isThumbnail = !empty($currentShop->thumbnail_image) > 0 ? true : false;
                    $isWokplace = count($currentShop->workplace_images) > 0 ? true : false;
                    $isMainProfile = count($currentShop->main_profile_images) > 0 ? true : false;
                    $isAddress = $currentShop && $currentShop->address && isset($currentShop->address->address) && $currentShop->address->address != NULL ? true : false;
                    $isShopPrices = $shopPrices > 0 ? true : false;
                    $isMainName = $currentShop->main_name != NULL ? true : false;
                    $isShopName = $currentShop->shop_name != NULL ? true : false;
                    $isSpecialityOf = $currentShop->speciality_of != NULL ? true : false;
            
                    if($isShopPost && $isThumbnail && $isWokplace && $isMainProfile && $isAddress && $isMainName && $isShopName && $isSpecialityOf){
                            Shop::where('id',$shop->id)->update(['status_id' => Status::ACTIVE,'deactivate_by_user' => 0]);
                            if($shop->status_id != Status::ACTIVE){
                                $userCredits = UserCredit::where('user_id',$shop->user_id)->first(); 
                                $devices = UserDevices::whereIn('user_id', [$shop->user_id])->pluck('device_token')->toArray();
                                $user_detail = UserDetail::where('user_id', $shop->user_id)->first();
                                $language_id = $user_detail->language_id;
                                $title_msg = '';
                                $key = Notice::PROFILE_ACTIVATE.'_'.$language_id;
                                $format = __("notice.$key");
                                $notify_type = Notice::PROFILE_ACTIVATE;
                                $notice = Notice::create([
                                    'notify_type' => Notice::PROFILE_ACTIVATE,
                                    'user_id' => $shop->user_id,
                                    'to_user_id' => $shop->user_id,
                                    'entity_type_id' => EntityTypes::SHOP,
                                    'entity_id' => $shop->id,
                                    'sub_title' => $shop->shop_name.'('.$shop->main_name.')',
                                ]);                   
                            }
                    }else {
                            Shop::where('id',$shop->id)->update(['status_id' => Status::PENDING]);
                            if($shop->status_id != Status::PENDING){
                                $userCredits = UserCredit::where('user_id',$shop->user_id)->first(); 
                                $devices = UserDevices::whereIn('user_id', [$shop->user_id])->pluck('device_token')->toArray();
                                $user_detail = UserDetail::where('user_id', $shop->user_id)->first();
                                $language_id = $user_detail->language_id;
                                $title_msg = '';
                                $key = Notice::PROFILE_PENDING.'_'.$language_id;
                                $format = __("notice.$key");
                                $notify_type = Notice::PROFILE_PENDING;
                                $notice = Notice::create([
                                    'notify_type' => Notice::PROFILE_PENDING,
                                    'user_id' => $shop->user_id,
                                    'to_user_id' => $shop->user_id,
                                    'entity_type_id' => EntityTypes::SHOP,
                                    'entity_id' => $shop->id,
                                    'sub_title' => $shop->shop_name.'('.$shop->main_name.')',
                                ]);                   
                            }
                    }
                }                
                if (count($devices) > 0) {
                    $result = $this->sentPushNotification($devices,$title_msg, $format, $notificationData ,$notify_type, $shop->id);                        
                }
            }
            }
        }

        $hospitals = Hospital::whereIn('status_id',[Status::ACTIVE,Status::INACTIVE,Status::PENDING])->where('deactivate_by_user',0)->get();
        $temp1 = [];
        foreach($hospitals as $hospital){  
            $devices = [];          
            $user_detail = UserDetail::where('user_id', $hospital->user_id)->first();
            if($user_detail){
                $creditPlan = CreditPlans::where('entity_type_id', EntityTypes::HOSPITAL)->where('package_plan_id', $user_detail->package_plan_id)->first();
                $minHospitalCredit = $creditPlan ? $creditPlan->amount : null;
                if($minHospitalCredit) {
                    $notificationData = [
                        'id' => $hospital->id,
                        'main_name' => $hospital->main_name,
                        'category_id' => $hospital->category_id,
                        'category_name' => $hospital->category_name,
                        'category_icon' => $hospital->category_icon,
                    ];

                    $userCredits = UserCredit::where('user_id',$hospital->user_id)->first(); 
                    if($userCredits->credits < $minHospitalCredit) {
                        Hospital::where('id',$hospital->id)->update(['status_id' => Status::INACTIVE]);
                        Post::where('hospital_id',$hospital->id)->update(['status_id' => Status::INACTIVE]);
                        if(!in_array($hospital->user_id, $temp1)) {
                            $temp1[] = $hospital->user_id;    
                            $userCredits = UserCredit::where('user_id',$hospital->user_id)->first(); 
                            // $devices = UserDetail::whereIn('user_id', [$hospital->user_id])->pluck('device_token')->toArray();
                            // $user_detail = UserDetail::where('user_id', $hospital->user_id)->first();
                            // $language_id = $user_detail->language_id;
                            // $title_msg = '';
                            // $key = Notice::OUT_OF_COINS.'_'.$language_id;
                            // $format = __("notice.$key");
                            // $notify_type = Notice::OUT_OF_COINS;
                            // $notice = Notice::create([
                            //     'notify_type' => Notice::OUT_OF_COINS,
                            //     'user_id' => $hospital->user_id,
                            //     'to_user_id' => $hospital->user_id,
                            //     'entity_type_id' => EntityTypes::HOSPITAL,
                            //     'sub_title' => $hospital->main_name
                            // ]);
                        }  
                    }else {
                        $currentHospital = Hospital::with(['address' => function($query) {
                            $query->where('entity_type_id', EntityTypes::HOSPITAL);
                        }])->where('id',$hospital->id)->first();
                
                        $hospitalDoctors = HospitalDoctor::where('hospital_id',$hospital->id)->count();
                
                        $isHospitalImages = count($currentHospital->images) > 0 ? true : false;
                        $isDescription = $currentHospital->description != NULL ? true : false;
                        $isAddress = $currentHospital && $currentHospital->address && $currentHospital->address->address != NULL ? true : false;
                        $isDoctors = $hospitalDoctors > 0 ? true : false;
                
                        if($isHospitalImages && $isDescription && $isDoctors && $isAddress){
                            Hospital::where('id',$hospital->id)->update(['status_id' => Status::ACTIVE,'deactivate_by_user' => 0]);
                            if($hospital->status_id != Status::ACTIVE){
                                $userCredits = UserCredit::where('user_id',$hospital->user_id)->first(); 
                                $devices = UserDevices::whereIn('user_id', [$hospital->user_id])->pluck('device_token')->toArray();
                                $user_detail = UserDetail::where('user_id', $hospital->user_id)->first();
                                $language_id = $user_detail->language_id;
                                $title_msg = '';
                                $key = Notice::PROFILE_ACTIVATE.'_'.$language_id;
                                $format = __("notice.$key");
                                $notify_type = Notice::PROFILE_ACTIVATE;
                                $notice = Notice::create([
                                    'notify_type' => Notice::PROFILE_ACTIVATE,
                                    'user_id' => $hospital->user_id,
                                    'to_user_id' => $hospital->user_id,
                                    'entity_type_id' => EntityTypes::HOSPITAL,
                                    'entity_id' => $hospital->id,
                                    'sub_title' => $hospital->main_name,
                                ]);                   
                            }
                            $posts = Post::where('hospital_id',$hospital->id)->get();
                            foreach($posts as $post){
                                $fromDate = new Carbon($post->from_date);
                                $toDate = new Carbon($post->to_date);
                                $date1 = Carbon::now();
                                $currentDate = $date1->format('d-m-Y');
                                $fromDate1 = $fromDate->format('d-m-Y');
                                $toDate1 = $toDate->format('d-m-Y');
                                
                                $check = Carbon::now()->between($fromDate,$toDate);
                                
                                if($check || $currentDate == $fromDate1 || $currentDate == $toDate1) {
                                    Post::where('id', $post->id)->update(['status_id' => Status::ACTIVE]) ;
                                }else if(!$fromDate->isPast() &&  !$toDate->isPast()) {
                                    Post::where('id', $post->id)->update(['status_id' => Status::FUTURE]) ;
                                }else if ($fromDate->isPast() &&  $toDate->isPast()) {
                                    Post::where('id', $post->id)->update(['status_id' => Status::EXPIRE]) ;
                                } 
                            }
                            // Post::where('hospital_id',$hospital->id)->update(['status_id' => Status::ACTIVE]);
                        }else {
                            Hospital::where('id',$hospital->id)->update(['status_id' => Status::PENDING]);
                            Post::where('hospital_id',$hospital->id)->update(['status_id' => Status::PENDING]);
                            if($hospital->status_id != Status::PENDING){
                                $userCredits = UserCredit::where('user_id',$hospital->user_id)->first(); 
                                $devices = UserDevices::whereIn('user_id', [$hospital->user_id])->pluck('device_token')->toArray();
                                $user_detail = UserDetail::where('user_id', $hospital->user_id)->first();
                                $language_id = $user_detail->language_id;
                                $title_msg = '';
                                $key = Notice::PROFILE_PENDING.'_'.$language_id;
                                $format = __("notice.$key");
                                $notify_type = Notice::PROFILE_PENDING;
                                $notice = Notice::create([
                                    'notify_type' => Notice::PROFILE_PENDING,
                                    'user_id' => $hospital->user_id,
                                    'to_user_id' => $hospital->user_id,
                                    'entity_type_id' => EntityTypes::HOSPITAL,
                                    'entity_id' => $hospital->id,
                                    'sub_title' => $hospital->main_name,
                                ]);                   
                            }
                        }
                    }

                    if (count($devices) > 0) {
                        $result = $this->sentPushNotification($devices,$title_msg, $format, $notificationData ,$notify_type, $hospital->id);                        
                    }
                }
            }
        }
       
        $this->info('Credit Check:Cron Cummand Run successfully!');
    }

    public function sentPushNotification($registration_ids,$title_msg, $format, $notificationData =[], $notify_type = null, $event_id = null, $action = null, $broadcaster = null, $position = null)
    {
        try {
            $msg = array(
                'body' => $format,
                'title' => $title_msg,
                'notification_data' => $notificationData,
                'priority'=> 'high', 
                'sound' => 'notifytune.wav',
            );
            $data = array(
                'notification_data' => $notificationData,
                'type' => $notify_type,
                'msgcnt' => 1,
                'action' => $action,
                "click_action" => "FLUTTER_NOTIFICATION_CLICK",
                "event_id" => $event_id,
                "broadcaster_name" => $broadcaster,
                "position" => $position
            );
            $response = $this->firebase->sendMultiple($registration_ids, $data, $msg);
            return $response;
        } catch (\Exception $ex) {
            return;
        }
    }
}
