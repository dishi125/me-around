<?php

namespace App\Http\Controllers\Api;

use App\Jobs\DeleteAccountReasonMail;
use App\Models\CardLevel;
use App\Models\DeleteAccountReason;
use App\Models\PostLanguage;
use App\Models\RecycleOption;
use App\Models\ReportedUser;
use App\Models\ReportedUserAttachment;
use App\Models\ReportGroupMessage;
use App\Models\ShopDetail;
use App\Models\UserHiddenCategory;
use App\Models\UserLocationHistory;
use Illuminate\Support\Str;
use Validator;
use Carbon\Carbon;
use App\Models\Post;
use App\Models\Shop;
use App\Models\User;
use App\Models\Cards;
use App\Models\Banner;
use App\Models\Config;
use App\Models\Notice;
use App\Models\Status;
use App\Util\Firebase;
use App\Models\Address;
use App\Models\Message;
use App\Models\Reviews;
use App\Mail\CommonMail;
use App\Models\Category;
use App\Models\Hospital;
use App\Models\ShopPost;
use App\Models\Community;
use App\Models\UserCards;
use App\Models\UserCredit;
use App\Models\UserDetail;
use App\Models\UserPoints;
use App\Models\ActivityLog;
use App\Models\CreditPlans;
use App\Models\EntityTypes;
use App\Models\PackagePlan;
use App\Models\RequestForm;
use App\Models\ReviewLikes;
use App\Models\UserDevices;
use App\Models\BannerImages;
use App\Models\ReportClient;
use Illuminate\Http\Request;
use App\Models\SearchHistory;
use App\Models\ShopFollowers;
use App\Models\CommunityLikes;
use App\Models\ReviewComments;
use App\Models\UserBlockHistory;
use App\Models\UserSavedHistory;
use App\Models\CommunityComments;
use App\Models\CompletedCustomer;
use App\Models\DefaultCardsRives;
use App\Models\ReloadCoinRequest;
use App\Models\RequestedCustomer;
use App\Models\SavedHistoryTypes;
use App\Models\UserCreditHistory;
use App\Models\ReviewCommentLikes;
use App\Models\ReviewCommentReply;
use App\Models\UserEntityRelation;
use App\Models\UserHidePopupImage;
use Illuminate\Support\Facades\DB;
use App\Models\LinkedSocialProfile;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Models\AssociationCommunity;
use App\Models\RequestBookingStatus;
use App\Models\UserInstagramHistory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Mail;
use App\Models\CommunityCommentLikes;
use App\Models\CommunityCommentReply;
use App\Models\CompleteCustomerDetails;
use App\Models\ReviewCommentReplyLikes;
use Illuminate\Support\Facades\Storage;
use App\Validators\ShopProfileValidator;
use App\Validators\UserProfileValidator;
use App\Models\MessageNotificationStatus;
use App\Models\CommunityCommentReplyLikes;


class UserProfileController extends Controller
{
    private $userProfileValidator;
    private $shopProfileValidator;
    protected $firebase;

    function __construct()
    {
        $this->userProfileValidator = new UserProfileValidator();
        $this->shopProfileValidator = new ShopProfileValidator();
        $this->firebase = new Firebase();
    }


    public function getUserProfile(Request $request)
    {
        $user = Auth::user();
        try {
            Log::info('Start code for the get user profile');
            $inputs = $request->all();
            $validation = $this->shopProfileValidator->validateGetShop($inputs);

            if ($validation->fails()) {
                Log::info('End code for get all shops');
                return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
            }

            if(isset($inputs['country'])){
                $main_country = $inputs['country'];
            }else if(!empty($inputs['latitude']) && !empty($inputs['longitude'])){
                $main_country = getCountryFromLatLong($inputs['latitude'],$inputs['longitude']);
            }else{
                $main_country = "KR";
            }

            if($user) {
                $userSocialProfile = LinkedSocialProfile::where('user_id',$user->id)->where('social_type',LinkedSocialProfile::Facebook)->first();
                $userAppleProfile = LinkedSocialProfile::where('user_id',$user->id)->where('social_type',LinkedSocialProfile::Apple)->first();
                $configInstaOption = Config::where('key', Config::INSTAGRAM_EXTRA_SERVICE)->first();
                $Insta_info = $configInstaOption ? (int) filter_var($configInstaOption->value, FILTER_SANITIZE_NUMBER_INT) : 0;

                $data = [
                    'id' => $user->id,
                    'name' => $user->name,
                    'phone_code' => $user->phone_code,
                    'mobile' => $user->mobile,
                    'avatar' => $user->avatar,
                    'gender' => $user->gender,
                    'is_character_as_profile' => $user->is_character_as_profile,
                    'user_applied_card' => getUserAppliedCard($user->id),
                    'is_facebook_connect' => (!empty($userSocialProfile) && !empty($userSocialProfile->social_id)),
                    'is_apple_connect' => (!empty($userAppleProfile) && !empty($userAppleProfile->social_id)),
                    'mbti' => $user->mbti,
                    'is_admin_access' => $user->is_admin_access,
                    'is_support_user' => $user->is_support_user,
                    'insta_extra_service' => $Insta_info,
                ];

                $bannerImages = Banner::join('banner_images', 'banners.id', '=', 'banner_images.banner_id')
                            ->where('banners.entity_type_id',EntityTypes::NORMALUSER)
                            ->where('banners.section','profile')
                            ->whereNull('banners.deleted_at')
                            ->whereNull('banner_images.deleted_at')
                            ->where('banners.country_code',$main_country)
                            ->orderBy('banner_images.order','asc')->orderBy('banner_images.id','desc')
                            ->get('banner_images.*');

                $sliders = [];
                foreach($bannerImages as $banner){
                    $temp = [];
                    $temp['image'] = Storage::disk('s3')->url($banner->image);
                    $temp['link'] = $banner->link;
                    $temp['slide_duration'] = $banner->slide_duration;
                    $temp['order'] = $banner->order;
                    $sliders[] = $temp;
                }
                $data['banner_images'] = $sliders;

                $shops = Shop::where('user_id',$user->id)
                    ->select('id','main_name','shop_name','category_id','status_id')
                    ->get();
                $shops->makeHidden(['category_id','category_name','category_icon','status_id','status_name','reviews','followers','is_follow','is_block','instagram_status','address', 'rating', 'work_complete', 'portfolio', 'reviews_list', 'main_profile_images', 'workplace_images', 'portfolio_images', 'best_portfolio', 'business_licence', 'identification_card']);
                $data['shops'] = $shops;

                Log::info('End code for the get user profile');
                return $this->sendSuccessResponse(Lang::get('messages.user-profile.success'), 200, $data);
            }
            else{
                Log::info('End code for the get user profile');
                return $this->sendSuccessResponse(Lang::get('messages.user.token_expired'), 401);
            }
        } catch (\Exception $e) {
            Log::info('Exception in the get user profile');
            Log::info($e);
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function updateAvatarImage(Request $request)
    {
        $user = Auth::user();
        $inputs = $request->all();
        try {
            Log::info('Start code for the update user avatar image');
            if($user){
                DB::beginTransaction();
                $validation = $this->userProfileValidator->validateImage($inputs);
                if ($validation->fails()) {
                    Log::info('End code for update user avatar image');
                    return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
                }

                $is_character_as_profile = $inputs['is_character_as_profile'] ?? 1;
                $userDetail = DB::table('users_detail')->where('user_id', $user->id)->first();
                $data = [];
                if ($request->hasFile('avatar')) {
                    $profileFolder = config('constant.profile');
                    if (!Storage::exists($profileFolder)) {
                        Storage::makeDirectory($profileFolder);
                    }
                    Storage::disk('s3')->delete($userDetail->avatar);
                    $avatar = Storage::disk('s3')->putFile($profileFolder, $request->file('avatar'),'public');
                    $fileName = basename($avatar);
                    $data['avatar'] = $profileFolder . '/' . $fileName;
                }
                $data['is_character_as_profile'] = $is_character_as_profile;

                UserDetail::where('user_id', $user->id)->update($data);
                $user = Auth::user();

                DB::commit();
                Log::info('End code for the get user profile');
                return $this->sendSuccessResponse(Lang::get('messages.user-profile.update-success'), 200, $user);

            }else{
                Log::info('End code for update user avatar image');
                return $this->sendSuccessResponse(Lang::get('messages.user.token_expired'), 401);
            }
        } catch (\Exception $e) {
            Log::info('Exception in update user avatar image');
            Log::info($e);
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }
    public function updateUserProfile(Request $request)
    {
        $user = Auth::user();
        $inputs = $request->all();
        try {
            Log::info('Start code for the update user profile');
            if($user){
                DB::beginTransaction();
                $language_id = $request->has('language_id') ? $inputs['language_id'] : 4;

                $validation = $this->userProfileValidator->validateUpdate($inputs,$language_id);
                if ($validation->fails()) {
                    Log::info('End code for update user profile');
                    return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
                }
                if($user->phone_code != $inputs['phone_code'] || $user->mobile != $inputs['phone']){
                    $user_phone_count = UserDetail::where('mobile',$inputs['phone'])->where('phone_code',$inputs['phone_code'])->count();
                    if($user_phone_count >= 10) {
                        return $this->sendFailedResponse(Lang::get('validation.user.phone_max'), 422);
                    }else {
                        $user_phone = UserDetail::where('mobile',$inputs['phone'])->where('phone_code',$inputs['phone_code'])->orderBy('id','desc')->first();
                        if($user_phone) {
                            $user_date = new Carbon($user_phone->created_at);
                            $current_date = Carbon::now();
                            $user_date = $user_date->addDays(30);
                            $current_date = $current_date;
                            if($current_date->lessThan($user_date)) {
                                return $this->sendFailedResponse(Lang::get('validation.user.phone_max_month'), 422);
                            }
                        }
                    }
                }

                $data = [
                    'name' => trim($inputs['name']),
                    'gender' => $inputs['gender'],
                    'mobile' => $inputs['phone'],
                    'phone_code' => $inputs['phone_code'],
                    'mbti' => isset($inputs['mbti']) ? trim($inputs['mbti']) : null,
                ];

                UserDetail::where('user_id', $user->id)->update($data);
//                User::where('id', $user->id)->update(['is_show_shops' => $request->is_show_shops]);
                $user = Auth::user();
                // $returnData = [
                //     'id' => $user->id,
                //     'name' => $user->name,
                //     'mobile' => $user->mobile,
                //     'avatar' => $user->avatar,
                //     'gender' => $user->gender,
                // ];

                Log::info('End code for the get user profile');
                DB::commit();
                return $this->sendSuccessResponse(Lang::get('messages.user-profile.update-success'), 200, compact('user'));

            }else{
                Log::info('End code for update user profile');
                return $this->sendSuccessResponse(Lang::get('messages.user.token_expired'), 401);
            }
        } catch (\Exception $e) {
            Log::info('Exception in update user profile');
            Log::info($e);
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }
    public function changePassword(Request $request)
    {
        $user = Auth::user();
        $inputs = $request->all();
        try {
            Log::info('Start code for the change password');
            if($user){
                DB::beginTransaction();
                $validation = $this->userProfileValidator->validateChangePassword($inputs);
                if ($validation->fails()) {
                    Log::info('End code for change password');
                    return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
                }
                $old_password = $user->password;
                if (Hash::check($request->old_password, $old_password)) {
                    $user = User::find(Auth::User()->id);
                    $user->password = Hash::make($request->password);
                    $user->save();
                    DB::commit();
                    Log::info('End Change Password');
                    return $this->sendSuccessResponse(Lang::get('messages.user-profile.update-password-success'), 200);
                } else {
                    return $this->sendSuccessResponse(Lang::get('messages.user-profile.old-password-error'), 422);
                }
            }else{
                Log::info('End code for change password');
                return $this->sendSuccessResponse(Lang::get('messages.user.token_expired'), 401);
            }
        } catch (\Exception $e) {
            Log::info('Exception in change password');
            Log::info($e);
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function addHistory(Request $request)
    {
        $user = Auth::user();
        $inputs = $request->all();
        try {
            Log::info('Start code for the save portfolio to user history');
            if($user){
                DB::beginTransaction();
                $validation = $this->userProfileValidator->validateAddHistory($inputs);
                if ($validation->fails()) {
                    Log::info('End code for save portfolio to user history');
                    return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
                }

                $data = [
                    'entity_id' => $inputs['entity_id'],
                    'saved_history_type_id' => $inputs['type'],
                    'user_id' => $user->id
                ];

                $data = UserSavedHistory::updateOrcreate($data,['is_like' => 1]);

                if($inputs['type'] == SavedHistoryTypes::SHOP) {

                    $shopPost = ShopPost::find($inputs['entity_id']);
                    $shop = Shop::find($shopPost->shop_id);

                    $isAvailable = UserPoints::where(['user_id' => $user->id,'entity_type' => UserPoints::LIKE_SHOP_POST])->whereDate('created_at',Carbon::now()->format('Y-m-d'))->first();

                    if(empty($isAvailable)){

                        UserPoints::create([
                            'user_id' => $user->id,
                            'entity_type' => UserPoints::LIKE_SHOP_POST,
                            'entity_id' => $inputs['entity_id'],
                            'entity_created_by_id' => $shop->user_id,
                            'points' => UserPoints::LIKE_SHOP_POST_POINT]);

                        // Send Push notification start
                        /* $notice = Notice::create([
                            'notify_type' => Notice::LIKE_SHOP_POST,
                            'user_id' => $user->id,
                            'to_user_id' => $user->id,
                            'entity_type_id' => EntityTypes::SHOP_POST,
                            'entity_id' => $shopPost->id,
                            'title' => '+'.UserPoints::LIKE_SHOP_POST_POINT.'exp',
                            'sub_title' => '',
                            'is_aninomity' => 0
                        ]);

                        $user_detail = UserDetail::where('user_id', $user->id)->first();
                        $language_id = $user_detail->language_id;
                        $key = Notice::LIKE_SHOP_POST.'_'.$language_id;
                        $userIds = [$user->id];

                        $format = '+'.UserPoints::LIKE_SHOP_POST_POINT.'exp';
                        $devices = UserDevices::whereIn('user_id', $userIds)->pluck('device_token')->toArray();

                        $title_msg = __("notice.$key");
                        $notify_type = Notice::LIKE_SHOP_POST;

                        $notificationData = [
                            'id' => $shopPost->id,
                            'user_id' => $user->id,
                            'title' => $title_msg,
                        ];
                        if (count($devices) > 0) {
                            $result = $this->sentPushNotification($devices,$title_msg, $format, $notificationData ,$notify_type);
                        } */
                            // Send Push notification end

                    }

                    $userIds = [$shop->user_id];
                    $devices = UserDevices::whereIn('user_id', [$userIds])->pluck('device_token')->toArray();
                    $user_detail = UserDetail::where('user_id', $shop->user_id)->first();
                    $language_id = $user_detail->language_id;
                    $key = Notice::POST_LIKE.'_'.$language_id;
                    $format = __("notice.$key");
                    $title_msg = '';
                    $notify_type = Notice::POST_LIKE;

                    $notificationData = [
                        'id' => $shop->id,
                        'main_name' => $shop->main_name,
                        'shop_name' => $shop->shop_name,
                        'category_id' => $shop->category_id,
                        'category_name' => $shop->category_name,
                        'category_icon' => $shop->category_icon,
                    ];

                    $notice = Notice::create([
                        'notify_type' => Notice::POST_LIKE,
                        'user_id' => $user->id,
                        'to_user_id' => $shop->user_id,
                        'entity_type_id' => EntityTypes::SHOP,
                        'entity_id' => $inputs['entity_id'],
                        'title' => $shop->shop_name.'('.$shop->main_name.')',
                    ]);
                    if (count($devices) > 0) {
                        // $result = $this->sentPushNotification($devices,$title_msg, $format, $notificationData ,$notify_type, $shop->id);
                    }
                }

                if($inputs['type'] == SavedHistoryTypes::HOSPITAL) {
                    $post = Post::find($inputs['entity_id']);
                    $hospital = Hospital::find($post->hospital_id);
                    $userIds = [$hospital->user_id];
                    $devices = UserDevices::whereIn('user_id', [$userIds])->pluck('device_token')->toArray();
                    $user_detail = UserDetail::where('user_id', $hospital->user_id)->first();
                    $language_id = $user_detail->language_id;
                    $key = Notice::POST_LIKE.'_'.$language_id;
                    $format = __("notice.$key");
                    $title_msg = '';
                    $notify_type = Notice::POST_LIKE;

                    $notificationData = [
                        'id' => $hospital->id,
                        'main_name' => $hospital->main_name,
                        'category_id' => $hospital->category_id,
                        'category_name' => $hospital->category_name,
                        'category_icon' => $hospital->category_icon,
                    ];

                    $notice = Notice::create([
                        'notify_type' => Notice::POST_LIKE,
                        'user_id' => $user->id,
                        'to_user_id' => $hospital->user_id,
                        'entity_type_id' => EntityTypes::HOSPITAL,
                        'entity_id' => $inputs['entity_id'],
                        'title' => $hospital->main_name,
                    ]);
                    if (count($devices) > 0) {
                        // $result = $this->sentPushNotification($devices,$title_msg, $format, $notificationData ,$notify_type, $hospital->id);
                    }
                }

                Log::info('End code for the save portfolio to user history');
                DB::commit();
                return $this->sendSuccessResponse(Lang::get('messages.user-profile.add-history-success'), 200);

            }else{
                Log::info('End code for save portfolio to user history');
                return $this->sendSuccessResponse(Lang::get('messages.user.token_expired'), 401);
            }
        } catch (\Exception $e) {
            print_r($e->getMessage());die;
            Log::info('Exception in save portfolio to user history');
            Log::info($e);
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function removeFromHistory(Request $request)
    {
        $user = Auth::user();
        $inputs = $request->all();
        try {
            Log::info('Start code for the remove portfolio to user history');
            if($user){
                DB::beginTransaction();
                $validation = $this->userProfileValidator->validateAddHistory($inputs);
                if ($validation->fails()) {
                    Log::info('End code for remove portfolio to user history');
                    return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
                }

                $data = [
                    'entity_id' => $inputs['entity_id'],
                    'saved_history_type_id' => $inputs['type'],
                    'user_id' => $user->id,
                ];

                $data = UserSavedHistory::updateOrcreate($data,['is_like' => 0]);
                Log::info('End code for the remove portfolio to user history');
                DB::commit();
                return $this->sendSuccessResponse(Lang::get('messages.user-profile.remove-history-success'), 200);

            }else{
                Log::info('End code for remove portfolio to user history');
                return $this->sendSuccessResponse(Lang::get('messages.user.token_expired'), 401);
            }
        } catch (\Exception $e) {
            Log::info('Exception in remove portfolio to user history');
            Log::info($e);
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function getCustomers(Request $request)
    {
        $user = Auth::user();
        try {
            Log::info('Start code for the get user customers');
            if($user) {
                $inputs = $request->all();
                $validation = Validator::make($request->all(), [
                    'latitude' => 'required',
                    'longitude' => 'required',
                ], [], [
                    'latitude' => 'Latitude',
                    'longitude' => 'Longitude',
                ]);

                if ($validation->fails()) {
                    Log::info('End code for update user profile');
                    return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
                }

                $main_country = getCountryFromLatLong($inputs['latitude'],$inputs['longitude']);
                $timezone = get_nearest_timezone($inputs['latitude'],$inputs['longitude'],$main_country);

                $returnData = [];
                $isShop = $user->entityType->contains('entity_type_id', EntityTypes::SHOP);
                $isHospital = $user->entityType->contains('entity_type_id', EntityTypes::HOSPITAL);

                $concatQuery = 'CONCAT(requested_customer.user_id, "_", requested_customer.entity_id)';
                $outsideConcatQuery = 'CONCAT(complete_customer_details.customer_id, "_", complete_customer_details.entity_id)';

                if($isShop){
                    $usersShop = [];
                    foreach($user->entityType as $userEntity){
                        if($userEntity->entity_type_id == EntityTypes::SHOP){
                            $usersShop[] = $userEntity->entity_id;
                        }
                    }
                    $bookingUser = $outsideBookingUser = $bookingUserVisited = $outSidebookingUserVisited = [];

                    // Outside Customers
                    $outsideSelectVariables = [
                        'complete_customer_details.id',
                        'complete_customer_details.date as booking_date',
                        'complete_customer_details.entity_type_id',
                        'complete_customer_details.entity_id',
                        'complete_customer_details.customer_id as user_id',
                        'complete_customer_details.customer_id as customer_id',
                        'complete_customer_details.status_id as request_booking_status_id',
                        'shops.shop_name as title',
                        'shops.category_id',
                        'complete_customer_details.status_id',
                        'complete_customer_details.status_id as chat_status',
                        'customer_lists.customer_name as display_user_name',
                        'customer_lists.customer_phone as user_phone',
                        'complete_customer_details.updated_at',
                        'complete_customer_details.created_at'
                    ];


                    $outsideRecentComingOrder = CompleteCustomerDetails::join('shops','shops.id','=','complete_customer_details.entity_id')
                        ->join('customer_lists','customer_lists.id','complete_customer_details.customer_id')
                        ->where('complete_customer_details.user_id',$user->id)
                        ->where('complete_customer_details.entity_type_id',EntityTypes::SHOP)
                        ->where('complete_customer_details.status_id', RequestBookingStatus::BOOK)
                        ->groupBy('complete_customer_details.customer_id','complete_customer_details.entity_id')
                        ->select(
                            $outsideSelectVariables
                        )
                        ->selectRaw("{$outsideConcatQuery} AS uniqe_records")
                        ->addSelect(DB::raw("'static' as customer_type"));
                    // Outside Customers

                    $insideSelectVariable = [
                        'requested_customer.id',
                        'requested_customer.booking_date',
                        'requested_customer.entity_type_id',
                        'requested_customer.entity_id',
                        'requested_customer.user_id',
                        'requested_customer.user_id as customer_id',
                        'requested_customer.request_booking_status_id',
                        'shops.shop_name as title',
                        'shops.category_id',
                        'users.status_id',
                        'users.chat_status',
                        'users_detail.name as display_user_name',
                        'users_detail.mobile as user_phone',
                        'requested_customer.updated_at',
                        'requested_customer.created_at'
                    ];
                    $recentComingOrder = RequestedCustomer::join('shops','shops.id','=','requested_customer.entity_id')
                                        ->join('category','category.id','=','shops.category_id')
                                        ->join('users','users.id','=','requested_customer.user_id')
                                        ->leftjoin('users_detail','users.id','=','users_detail.user_id')
                                        ->whereNotNull('users.email')
                                        ->where('requested_customer.entity_type_id',EntityTypes::SHOP)
                                        // ->where('shops.status_id', Status::ACTIVE)
                                        ->whereIn('shops.id', $usersShop)
                                        ->where('requested_customer.request_booking_status_id', RequestBookingStatus::BOOK)

                                        ->groupBy('requested_customer.user_id','requested_customer.entity_id')
                                        ->select(
                                            $insideSelectVariable
                                        )
                                        ->selectRaw("{$concatQuery} AS uniqe_records")
                                        ->addSelect(DB::raw("'booked' as customer_type"))
                                        ->union($outsideRecentComingOrder)
                                        ->orderBy('booking_date','ASC')
                                        ->paginate(config('constant.pagination_count'),"*","coming_order_page")->toArray();
                    $filteredData = [];

                    //collect($recentComingOrder->items())->sortByDesc('booking_date');
                    foreach($recentComingOrder['data'] as $d){

                        if($d["customer_type"] == "static"){
                            $outsideBookingUser[] = $d['user_id'].'_'.$d['entity_id'];
                        }else{
                            $bookingUser[] = $d['user_id'].'_'.$d['entity_id'];
                        }

                        $category = Category::find($d['category_id']);
                        $d['category_name'] = $category->name ?? '';
                        $d['category_logo'] = $category->logo ?? '';
                        $test = Carbon::createFromFormat('Y-m-d H:i:s',$d['booking_date'], "UTC")->setTimezone($timezone);
                        $d['booking_date'] = $test->toDateTimeString();

                        if($d['display_user_name'] && $d["customer_type"] == "static"){
                            $d['user_name'] = $d['display_user_name'];
                            $d['user_image'] = '';
                        }
                        // $d['booking_date'] = Carbon::createFromFormat('Y-m-d H:i:s',$d['booking_date'], "UTC")->setTimezone($timezone);
                        $filteredData[] = $d;

                    }

                    $recentComingOrder['data'] = array_values($filteredData);
                    $recentOrder = RequestedCustomer::join('shops','shops.id','=','requested_customer.entity_id')
                                        ->join('category','category.id','=','shops.category_id')
                                        ->join('users','users.id','=','requested_customer.user_id')
                                        ->leftjoin('users_detail','users.id','=','users_detail.user_id')
                                        ->whereNotNull('users.email')
                                        ->where('requested_customer.entity_type_id',EntityTypes::SHOP)
                                        // ->where('shops.status_id', Status::ACTIVE)
                                        ->whereIn('shops.id', $usersShop)
                                        ->where('requested_customer.request_booking_status_id', RequestBookingStatus::BOOK)
                                        ->orderBy('requested_customer.created_at')
                                        ->groupBy('requested_customer.user_id','requested_customer.entity_id')
                                        ->select(
                                            $insideSelectVariable
                                        )
                                        ->selectRaw("{$concatQuery} AS uniqe_records")
                                        ->addSelect(DB::raw("'booked' as customer_type"))
                                        ->union($outsideRecentComingOrder)
                                        ->paginate(config('constant.pagination_count'),"*","recent_order_page")->toArray();
                    $filteredData = [];
                    foreach($recentOrder['data'] as $d){
                        $category = Category::find($d['category_id']);
                        $d['category_name'] = $category->name ?? '';
                        $d['category_logo'] = $category->logo ?? '';
                        $test = Carbon::createFromFormat('Y-m-d H:i:s',$d['booking_date'], "UTC")->setTimezone($timezone);
                        $d['booking_date'] = $test->toDateTimeString();

                        if($d['display_user_name'] && $d["customer_type"] == "static"){
                            $d['user_name'] = $d['display_user_name'];
                            $d['user_image'] = '';
                        }

                        $filteredData[] = $d;

                    }

                    $recentOrder['data'] = array_values($filteredData);


                    //$notInUser = (!empty($bookingUser)) ? "'".implode("','",$bookingUser)."'" : '';
                    $notInUser = collect($bookingUser)->implode("','");
                    $outsideNotInUser = collect($outsideBookingUser)->implode("','");

                    $outsideVisitedCustomer = CompleteCustomerDetails::join('shops','shops.id','=','complete_customer_details.entity_id')
                        ->join('customer_lists','customer_lists.id','complete_customer_details.customer_id')
                        ->where('complete_customer_details.user_id',$user->id)
                        ->where('complete_customer_details.entity_type_id',EntityTypes::SHOP)
                        ->where('complete_customer_details.status_id', RequestBookingStatus::VISIT)
                        ->groupBy('complete_customer_details.customer_id','complete_customer_details.entity_id')
                        ->select(
                            $outsideSelectVariables
                        )
                        ->selectRaw("{$outsideConcatQuery} AS uniqe_records")
                        ->whereRaw("{$outsideConcatQuery} NOT IN ('{$outsideNotInUser}')")
                        ->addSelect(DB::raw("'static' as customer_type"));

                    $visitedCustomer = RequestedCustomer::join('shops','shops.id','=','requested_customer.entity_id')
                                        ->join('category','category.id','=','shops.category_id')
                                        ->join('users','users.id','=','requested_customer.user_id')
                                        ->leftjoin('users_detail','users.id','=','users_detail.user_id')
                                        ->whereNotNull('users.email')
                                        ->where('requested_customer.entity_type_id',EntityTypes::SHOP)
                                        // ->where('shops.status_id', Status::ACTIVE)
                                        ->whereIn('shops.id', $usersShop)
                                        ->groupBy('requested_customer.user_id','requested_customer.entity_id')
                                        ->where('requested_customer.request_booking_status_id', RequestBookingStatus::VISIT)
                                        //->select('requested_customer.*','shops.shop_name as title','shops.category_id','users.status_id', 'users.chat_status')
                                        ->select(
                                            $insideSelectVariable
                                        )
                                        ->selectRaw("{$concatQuery} AS uniqe_records")
                                        ->addSelect(DB::raw("'booked' as customer_type"))
                                        ->whereRaw("{$concatQuery} NOT IN ('{$notInUser}')")
                                        ->union($outsideVisitedCustomer)
                                        ->orderBy('booking_date','desc')
                                        ->paginate(config('constant.pagination_count'),"*","visited_customer_page")->toArray();
                    $filteredData = [];
                    foreach($visitedCustomer['data'] as $d){

                        if($d["customer_type"] == "static"){
                            $outSidebookingUserVisited[] = $d['user_id'].'_'.$d['entity_id'];
                        }else{
                            $bookingUserVisited[] = $d['user_id'].'_'.$d['entity_id'];
                        }

                        if($d['display_user_name'] && $d["customer_type"] == "static"){
                            $d['user_name'] = $d['display_user_name'];
                            $d['user_image'] = '';
                        }

                        $category = Category::find($d['category_id']);
                        $d['category_name'] = $category->name ?? '';
                        $d['category_logo'] = $category->logo ?? '';
                        $test = Carbon::createFromFormat('Y-m-d H:i:s',$d['booking_date'], "UTC")->setTimezone($timezone);
                        $d['booking_date'] = $test->toDateTimeString();
                        $filteredData[] = $d;
                    }

                    $visitedCustomer['data'] = array_values($filteredData);

                    $bookingUserMerge = array_merge($bookingUser,$bookingUserVisited);
                    $notInCompleteUser = collect($bookingUserMerge)->implode("','");

                    $outsidebookingUserMerge = array_merge($outsideBookingUser,$outSidebookingUserVisited);
                    $outsidenotInCompleteUser = collect($outsidebookingUserMerge)->implode("','");


                    $outsideCompletedCustomer = CompleteCustomerDetails::join('shops','shops.id','=','complete_customer_details.entity_id')
                        ->join('customer_lists','customer_lists.id','complete_customer_details.customer_id')
                        ->where('complete_customer_details.user_id',$user->id)
                        ->where('complete_customer_details.entity_type_id',EntityTypes::SHOP)
                        ->where('complete_customer_details.status_id', RequestBookingStatus::COMPLETE)
                        ->groupBy('complete_customer_details.customer_id','complete_customer_details.entity_id')
                        ->select(
                            $outsideSelectVariables
                        )
                        ->selectRaw("{$outsideConcatQuery} AS uniqe_records")
                        ->whereRaw("{$outsideConcatQuery} NOT IN ('{$outsidenotInCompleteUser}')")
                        ->addSelect(DB::raw("'static' as customer_type"));

                    $completeOrder = RequestedCustomer::join('shops','shops.id','=','requested_customer.entity_id')
                                        ->join('category','category.id','=','shops.category_id')
                                        ->join('users','users.id','=','requested_customer.user_id')
                                        ->leftjoin('users_detail','users.id','=','users_detail.user_id')
                                        ->whereNotNull('users.email')
                                        ->where('requested_customer.entity_type_id',EntityTypes::SHOP)
                                        // ->where('shops.status_id', Status::ACTIVE)
                                        ->whereIn('shops.id', $usersShop)
                                        ->where('requested_customer.request_booking_status_id', RequestBookingStatus::COMPLETE)
                                        ->groupBy('requested_customer.user_id','requested_customer.entity_id')
                                        //->select('requested_customer.*','shops.shop_name as title','shops.category_id','users.status_id', 'users.chat_status')
                                        ->select(
                                            $insideSelectVariable
                                        )
                                        ->selectRaw("{$concatQuery} AS uniqe_records")
                                        ->addSelect(DB::raw("'booked' as customer_type"))
                                        ->whereRaw("{$concatQuery} NOT IN ('{$notInCompleteUser}')")
                                        ->whereIn('requested_customer.id', function($q){
                                            $q->select(DB::raw('max(id)'))->from('requested_customer')->where('requested_customer.request_booking_status_id', RequestBookingStatus::COMPLETE)->groupBy('requested_customer.user_id','requested_customer.entity_id');
                                        })
                                        ->union($outsideCompletedCustomer)
                                        ->orderBy('booking_date','desc')
                                        ->paginate(config('constant.pagination_count'),"*","completed_customer_page")->toArray();
                    $filteredData = [];
                    foreach($completeOrder['data'] as $d){
                        $category = Category::find($d['category_id']);
                        $d['category_name'] = $category->name ?? '';
                        $d['category_logo'] = $category->logo ?? '';
                        $test = Carbon::createFromFormat('Y-m-d H:i:s',$d['booking_date'], "UTC")->setTimezone($timezone);
                        $d['booking_date'] = $test->toDateTimeString();

                        $updated = Carbon::createFromFormat('Y-m-d H:i:s',$d['updated_at'], "UTC")->setTimezone($timezone);
                        $d['updated_at'] = $updated->toDateTimeString();
                        $d['user_name'] = $d['display_user_name'];

                        if($d['display_user_name'] && $d["customer_type"] == "static"){
                            $d['user_name'] = $d['display_user_name'];
                            $d['user_image'] = '';
                        }


                        $filteredData[] = $d;

                    }

                    $completeOrder['data'] = array_values($filteredData);
                    $returnData['booking_customer'] = [
                        'coming_order' => $recentComingOrder,
                        'recent_order' => $recentOrder,
                    ];

                    $returnData['visited_customer'] = $visitedCustomer;
                    $returnData['completed_customer'] = $completeOrder;
                }
                if($isHospital){
                    $usersHospitals = [];
                    foreach($user->entityType as $userEntity){
                        if($userEntity->entity_type_id == EntityTypes::HOSPITAL){
                            $usersHospitals[] = $userEntity->entity_id;
                        }
                    }

                    $bookingUserHospital = $bookingUserVisitedHospital = $outsideBookingUserHospital = $outsideBookingUserVisitedHospital = [];

                    $insideHospitalSelectVariable = [
                        'requested_customer.id',
                        'requested_customer.booking_date',
                        'requested_customer.entity_type_id',
                        'requested_customer.entity_id',
                        'requested_customer.user_id',
                        'requested_customer.user_id as customer_id',
                        'requested_customer.request_booking_status_id',
                        'posts.title as title',
                        'posts.category_id',
                        'users.status_id',
                        'users.chat_status',
                        'users_detail.name as display_user_name',
                        'users_detail.mobile as user_phone',
                        'requested_customer.updated_at',
                        'requested_customer.created_at',
                        \DB::raw('(CASE
                            WHEN category.logo != NULL THEN  category.logo
                            ELSE ""
                            END) AS category_logo')
                    ];

                    $outsideHospitalSelectVariables = [
                        'complete_customer_details.id',
                        'complete_customer_details.date as booking_date',
                        'complete_customer_details.entity_type_id',
                        'complete_customer_details.entity_id',
                        'complete_customer_details.customer_id as user_id',
                        'complete_customer_details.customer_id as customer_id',
                        'complete_customer_details.status_id as request_booking_status_id',
                        'posts.title as title',
                        'posts.category_id',
                        'complete_customer_details.status_id',
                        'complete_customer_details.status_id as chat_status',
                        'customer_lists.customer_name as display_user_name',
                        'customer_lists.customer_phone as user_phone',
                        'complete_customer_details.updated_at',
                        'complete_customer_details.created_at',
                        \DB::raw('(CASE
                            WHEN category.logo != NULL THEN  category.logo
                            ELSE ""
                            END) AS category_logo')
                    ];

                    $outsideHospitalRecentComingOrder = CompleteCustomerDetails::join('posts','posts.id','=','complete_customer_details.entity_id')
                        ->join('category','category.id','=','posts.category_id')
                        ->join('customer_lists','customer_lists.id','complete_customer_details.customer_id')
                        ->where('complete_customer_details.user_id',$user->id)
                        ->where('complete_customer_details.entity_type_id',EntityTypes::HOSPITAL)
                        ->where('complete_customer_details.status_id', RequestBookingStatus::BOOK)
                        ->groupBy('complete_customer_details.customer_id','complete_customer_details.entity_id')
                        ->select(
                            $outsideHospitalSelectVariables
                        )
                        ->selectRaw("{$outsideConcatQuery} AS uniqe_records")
                        ->addSelect(DB::raw("'static' as customer_type"));

                    $recentComingOrder = RequestedCustomer::join('posts','posts.id','=','requested_customer.entity_id')
                                                            ->join('category','category.id','=','posts.category_id')
                                                            ->join('users','users.id','=','requested_customer.user_id')
                                                            ->leftjoin('users_detail','users.id','=','users_detail.user_id')
                                                            ->whereNotNull('users.email')
                                                            ->where('requested_customer.entity_type_id',EntityTypes::HOSPITAL)
                                                            // ->where('posts.status_id', Status::ACTIVE)
                                                            ->whereIn('posts.hospital_id', $usersHospitals)
                                                            ->where('requested_customer.request_booking_status_id', RequestBookingStatus::BOOK)
                                                            ->groupBy('requested_customer.user_id','requested_customer.entity_id')
                                                            ->select(
                                                                $insideHospitalSelectVariable
                                                            )
                                                            ->selectRaw("{$concatQuery} AS uniqe_records")
                                                            ->addSelect(DB::raw("'booked' as customer_type"))
                                                            ->union($outsideHospitalRecentComingOrder)
                                                            ->orderBy('booking_date')
                                                            ->paginate(config('constant.pagination_count'),"*","coming_order_page")->toArray();

                    $filteredData = [];
                    foreach($recentComingOrder['data'] as $d){
                        if($d["customer_type"] == "static"){
                            $outsideBookingUserHospital[] = $d['user_id'].'_'.$d['entity_id'];
                        }else{
                            $bookingUserHospital[] = $d['user_id'].'_'.$d['entity_id'];
                        }

                        $test = Carbon::createFromFormat('Y-m-d H:i:s',$d['booking_date'], "UTC")->setTimezone($timezone);
                        $d['booking_date'] = $test->toDateTimeString();

                        if($d['display_user_name'] && $d["customer_type"] == "static"){
                            $d['user_name'] = $d['display_user_name'];
                            $d['user_image'] = '';
                        }

                        $filteredData[] = $d;
                    }
                    $recentComingOrder['data'] = array_values($filteredData);

                    $recentOrder = RequestedCustomer::join('posts','posts.id','=','requested_customer.entity_id')
                                                        ->join('category','category.id','=','posts.category_id')
                                                        ->join('users','users.id','=','requested_customer.user_id')
                                                        ->leftjoin('users_detail','users.id','=','users_detail.user_id')
                                                        ->whereNotNull('users.email')
                                                        ->where('requested_customer.entity_type_id',EntityTypes::HOSPITAL)
                                                        // ->where('posts.status_id', Status::ACTIVE)
                                                        ->whereIn('posts.hospital_id', $usersHospitals)
                                                        ->where('requested_customer.request_booking_status_id', RequestBookingStatus::BOOK)
                                                        ->groupBy('requested_customer.user_id','requested_customer.entity_id')
                                                        ->select(
                                                            $insideHospitalSelectVariable
                                                        )
                                                        ->selectRaw("{$concatQuery} AS uniqe_records")
                                                        ->addSelect(DB::raw("'booked' as customer_type"))
                                                        ->union($outsideHospitalRecentComingOrder)
                                                        ->orderBy('created_at','desc')
                                                        ->paginate(config('constant.pagination_count'),"*","recent_order_page")->toArray();

                    $filteredData = [];
                    foreach($recentOrder['data'] as $d){
                        $test = Carbon::createFromFormat('Y-m-d H:i:s',$d['booking_date'], "UTC")->setTimezone($timezone);
                        $d['booking_date'] = $test->toDateTimeString();

                        if($d['display_user_name'] && $d["customer_type"] == "static"){
                            $d['user_name'] = $d['display_user_name'];
                            $d['user_image'] = '';
                        }

                        $filteredData[] = $d;
                    }
                    $recentOrder['data'] = array_values($filteredData);

                    $notInUserHospital = collect($bookingUserHospital)->implode("','");
                    $outsidenotInUserHospital = collect($outsideBookingUserHospital)->implode("','");


                    $outsideHospitalVisited = CompleteCustomerDetails::join('posts','posts.id','=','complete_customer_details.entity_id')
                        ->join('category','category.id','=','posts.category_id')
                        ->join('customer_lists','customer_lists.id','complete_customer_details.customer_id')
                        ->where('complete_customer_details.user_id',$user->id)
                        ->where('complete_customer_details.entity_type_id',EntityTypes::HOSPITAL)
                        ->where('complete_customer_details.status_id', RequestBookingStatus::VISIT)
                        ->groupBy('complete_customer_details.customer_id','complete_customer_details.entity_id')
                        ->select(
                            $outsideHospitalSelectVariables
                        )
                        ->selectRaw("{$outsideConcatQuery} AS uniqe_records")
                        ->whereRaw("{$outsideConcatQuery} NOT IN ('{$outsidenotInUserHospital}')")
                        ->addSelect(DB::raw("'static' as customer_type"));


                    $visitedCustomer = RequestedCustomer::
                                        join('posts','posts.id','=','requested_customer.entity_id')
                                        ->join('category','category.id','=','posts.category_id')
                                        ->join('users','users.id','=','requested_customer.user_id')
                                        ->leftjoin('users_detail','users.id','=','users_detail.user_id')
                                        ->whereNotNull('users.email')
                                        ->where('requested_customer.entity_type_id',EntityTypes::HOSPITAL)
                                        // ->where('posts.status_id', Status::ACTIVE)
                                        ->whereIn('posts.hospital_id', $usersHospitals)
                                        ->where('requested_customer.request_booking_status_id', RequestBookingStatus::VISIT)
                                        ->groupBy('requested_customer.user_id','requested_customer.entity_id')
                                        ->select(
                                            $insideHospitalSelectVariable
                                        )
                                        ->selectRaw("{$concatQuery} AS uniqe_records")
                                        ->whereRaw("{$concatQuery} NOT IN ('{$notInUserHospital}')")
                                        ->addSelect(DB::raw("'booked' as customer_type"))
                                        ->union($outsideHospitalVisited)
                                        ->orderBy('booking_date','desc')
                                        ->paginate(config('constant.pagination_count'),"*","visited_customer_page")->toArray();
                    $filteredData = [];
                    foreach($visitedCustomer['data'] as $d){
                        if($d["customer_type"] == "static"){
                            $outsideBookingUserVisitedHospital[] = $d['user_id'].'_'.$d['entity_id'];
                        }else{
                            $bookingUserVisitedHospital[] = $d['user_id'].'_'.$d['entity_id'];
                        }

                        $test = Carbon::createFromFormat('Y-m-d H:i:s',$d['booking_date'], "UTC")->setTimezone($timezone);
                        $d['booking_date'] = $test->toDateTimeString();

                        if($d['display_user_name'] && $d["customer_type"] == "static"){
                            $d['user_name'] = $d['display_user_name'];
                            $d['user_image'] = '';
                        }

                        $filteredData[] = $d;
                    }
                    $visitedCustomer['data'] = array_values($filteredData);

                    $bookingUserMergeHospital = array_merge($bookingUserHospital,$bookingUserVisitedHospital);
                    $notInCompleteUserHospital = collect($bookingUserMergeHospital)->implode("','");

                    $outsideBookingUserMergeHospital = array_merge($outsideBookingUserHospital,$outsideBookingUserVisitedHospital);
                    $outsidenotInCompleteUserHospital = collect($outsideBookingUserMergeHospital)->implode("','");


                    $outsideHospitalCompleted = CompleteCustomerDetails::join('posts','posts.id','=','complete_customer_details.entity_id')
                        ->join('category','category.id','=','posts.category_id')
                        ->join('customer_lists','customer_lists.id','complete_customer_details.customer_id')
                        ->where('complete_customer_details.user_id',$user->id)
                        ->where('complete_customer_details.entity_type_id',EntityTypes::HOSPITAL)
                        ->where('complete_customer_details.status_id', RequestBookingStatus::COMPLETE)
                        ->groupBy('complete_customer_details.customer_id','complete_customer_details.entity_id')
                        ->select(
                            $outsideHospitalSelectVariables
                        )
                        ->selectRaw("{$outsideConcatQuery} AS uniqe_records")
                        ->whereRaw("{$outsideConcatQuery} NOT IN ('{$outsidenotInCompleteUserHospital}')")
                        ->addSelect(DB::raw("'static' as customer_type"));

                    $completeOrder = RequestedCustomer::join('posts','posts.id','=','requested_customer.entity_id')
                    ->join('category','category.id','=','posts.category_id')
                    ->join('users','users.id','=','requested_customer.user_id')
                    ->leftjoin('users_detail','users.id','=','users_detail.user_id')
                    ->whereNotNull('users.email')
                    ->where('requested_customer.entity_type_id',EntityTypes::HOSPITAL)
                    // ->where('posts.status_id', Status::ACTIVE)
                    ->whereIn('posts.hospital_id', $usersHospitals)
                    ->where('requested_customer.request_booking_status_id', RequestBookingStatus::COMPLETE)
                    ->groupBy('requested_customer.user_id','requested_customer.entity_id')
                    ->select(
                        $insideHospitalSelectVariable
                    )
                    ->selectRaw("{$concatQuery} AS uniqe_records")
                    ->whereRaw("{$concatQuery} NOT IN ('{$notInCompleteUserHospital}')")
                    ->addSelect(DB::raw("'booked' as customer_type"))
                    ->whereIn('requested_customer.id', function($q){
                        $q->select(DB::raw('max(id)'))->from('requested_customer')->where('requested_customer.request_booking_status_id', RequestBookingStatus::COMPLETE)->groupBy('requested_customer.user_id','requested_customer.entity_id');
                    })
                    ->union($outsideHospitalCompleted)
                    ->orderBy('booking_date','desc')
                    ->paginate(config('constant.pagination_count'),"*","completed_customer_page")->toArray();

                    $filteredData = [];
                    foreach($completeOrder['data'] as $d){
                        $test = Carbon::createFromFormat('Y-m-d H:i:s',$d['booking_date'], "UTC")->setTimezone($timezone);
                        $d['booking_date'] = $test->toDateTimeString();

                        if($d['display_user_name'] && $d["customer_type"] == "static"){
                            $d['user_name'] = $d['display_user_name'];
                            $d['user_image'] = '';
                        }

                        $filteredData[] = $d;
                    }
                    $completeOrder['data'] = array_values($filteredData);

                    $returnData['booking_customer'] = [
                        'coming_order' => $recentComingOrder,
                        'recent_order' => $recentOrder,
                    ];

                    $returnData['visited_customer'] = $visitedCustomer;
                    $returnData['completed_customer'] = $completeOrder;
                }
                $returnData['user_details'] = Auth::user();
                Log::info('End code for the get user customers');
                return $this->sendSuccessResponse(Lang::get('messages.user-profile.success'), 200, $returnData);
            }
            else{
                Log::info('End code for the get user customers');
                return $this->sendSuccessResponse(Lang::get('messages.user.token_expired'), 401);
            }
        } catch (\Exception $e) {
            Log::info('Exception in the get user customers');
            Log::info($e);
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }
    public function getSchedule(Request $request)
    {
        $user = Auth::user();
        $inputs = $request->all();
        try {
            Log::info('Start code for the get user schedules');
            if($user) {
                $validation = Validator::make($request->all(), [
                    'latitude' => 'required',
                    'longitude' => 'required',
                ], [], [
                    'latitude' => 'Latitude',
                    'longitude' => 'Longitude',
                ]);

                if ($validation->fails()) {
                    Log::info('End code for the get user schedules');
                    return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
                }

                $main_country = getCountryFromLatLong($inputs['latitude'],$inputs['longitude']);
                $timezone = get_nearest_timezone($inputs['latitude'],$inputs['longitude'],$main_country);

                $data = [];
                $isShop = $user->entityType->contains('entity_type_id', EntityTypes::SHOP);
                $isHospital = $user->entityType->contains('entity_type_id', EntityTypes::HOSPITAL);

                $validation = $this->userProfileValidator->validateSchedule($inputs);
                if ($validation->fails()) {
                    Log::info('End code for the get user schedules');
                    return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
                }
                if($isShop){
                    $usersShop = [];
                    foreach($user->entityType as $userEntity){
                        if($userEntity->entity_type_id == EntityTypes::SHOP){
                            $usersShop[] = $userEntity->entity_id;
                        }
                    }
                    $schedules = RequestedCustomer::join('shops','shops.id','=','requested_customer.entity_id')
                                        ->join('category','category.id','=','shops.category_id')
                                        ->where('requested_customer.entity_type_id',EntityTypes::SHOP)
                                        // ->where('shops.status_id', Status::ACTIVE)
                                        //->where('requested_customer.booking_date','like', "{$inputs['date']}%")
                                        ->whereIn('shops.id', $usersShop)
                                        ->whereIn('requested_customer.request_booking_status_id', [RequestBookingStatus::BOOK,RequestBookingStatus::VISIT,RequestBookingStatus::COMPLETE])
                                        ->orderBy('requested_customer.booking_date')
                                        ->get(['requested_customer.*','shops.category_id','shops.main_name as title']);
                    foreach($schedules as $d){
                        $category = Category::find($d->category_id);
                        $d['category_name'] = $category ? $category->name : '';
                        $d['category_logo'] = $category ? $category->logo : '';
                        $test = Carbon::createFromFormat('Y-m-d H:i:s',$d['booking_date'], "UTC")->setTimezone($timezone);
                        $d['booking_date'] = $test->toDateTimeString();
                        $d['customer_type'] = 'booked';
                    }

                    $schedules = collect($schedules)->filter(function ($value, $key) use ($inputs) {
                        return Carbon::parse($value->booking_date)->format("Y-m-d") ==  $inputs['date'];
                    })->values();

                    // OutSideBooking

                    $outsideBooking = CompleteCustomerDetails::join('customer_lists','customer_lists.id','complete_customer_details.customer_id')
                        ->join('shops','shops.id','=','complete_customer_details.entity_id')
                        ->join('category','category.id','=','shops.category_id')
                        ->where('complete_customer_details.user_id',$user->id)
                        //->whereDate('date',$inputs['date'])
                        ->where('complete_customer_details.entity_type_id',EntityTypes::SHOP)
                        ->select('complete_customer_details.*','shops.shop_name', 'shops.main_name', 'shops.category_id', 'customer_lists.customer_name','customer_lists.customer_phone as user_phone')
                        ->get();

                    foreach($outsideBooking as $book){
                        $updateDate = Carbon::createFromFormat('Y-m-d H:i:s',$book['date'], "UTC")->setTimezone($timezone);
                        $book['booking_date'] = $updateDate->toDateTimeString();

                        $category = Category::find($book->category_id);
                        $book['category_name'] = $category->name ?? '';
                        $book['category_logo'] = $category->logo ?? '';
                        $book['customer_type'] = 'static';

                        $book['user_name'] = $book['customer_name'] ?? ''; //$book->customer_name;
                        $book['user_image'] = '';
                        $book['title'] = $book['main_name'] ?? '';
                        $book['requested_item_name'] = $book['shop_name'] ?? '';
                        $book['request_booking_status_id'] = $book['status_id'] ?? 1;
                    }
                    $outsideBookingSchedules = collect($outsideBooking)->filter(function ($value, $key) use ($inputs) {
                        return Carbon::parse($value->booking_date)->format("Y-m-d") ==  $inputs['date'];
                    })->values();

                    // OutSideBooking

                    $data['schedules'] = collect($schedules)->merge($outsideBookingSchedules)->sortBy('booking_date')->values();
                }

                if($isHospital){
                    $usersHospitals = [];
                    foreach($user->entityType as $userEntity){
                        if($userEntity->entity_type_id == EntityTypes::HOSPITAL){
                            $usersHospitals[] = $userEntity->entity_id;
                        }
                    }

                    $schedules = RequestedCustomer::join('posts','posts.id','=','requested_customer.entity_id')
                                                            ->join('category','category.id','=','posts.category_id')
                                                            ->where('requested_customer.entity_type_id',EntityTypes::HOSPITAL)
                                                            // ->where('posts.status_id', Status::ACTIVE)
                                                            //->where('requested_customer.booking_date','like', "{$inputs['date']}%")
                                                            ->whereIn('posts.hospital_id', $usersHospitals)
                                                            ->whereIn('requested_customer.request_booking_status_id', [RequestBookingStatus::BOOK,RequestBookingStatus::VISIT,RequestBookingStatus::COMPLETE])
                                                            ->orderBy('requested_customer.booking_date')
                                                            ->get(['requested_customer.*','posts.category_id','category.name as category_name','posts.sub_title as title']);

                    foreach($schedules as $d){
                        $post = Post::find($d->entity_id);
                        $d['category_logo'] = $post && !empty($post->thumbnail_url) && !empty($post->thumbnail_url->image) ? $post->thumbnail_url->image : '';
                        $test = Carbon::createFromFormat('Y-m-d H:i:s',$d['booking_date'], "UTC")->setTimezone($timezone);
                        $d['booking_date'] = $test->toDateTimeString();
                        $d['customer_type'] = 'booked';
                    }

                    $schedules = collect($schedules)->filter(function ($value, $key) use ($inputs) {
                        return Carbon::parse($value->booking_date)->format("Y-m-d") ==  $inputs['date'];
                    })->values();

                    // OutSideBooking

                    $outsideBooking = CompleteCustomerDetails::join('customer_lists','customer_lists.id','complete_customer_details.customer_id')
                        ->join('posts','posts.id','=','complete_customer_details.entity_id')
                        ->join('category','category.id','=','posts.category_id')
                        ->where('complete_customer_details.user_id',$user->id)
                        ->where('complete_customer_details.user_id',$user->id)
                        //->whereDate('date',$inputs['date'])
                        ->where('complete_customer_details.entity_type_id',EntityTypes::HOSPITAL)
                        ->select('complete_customer_details.*','posts.title', 'posts.sub_title', 'customer_lists.customer_name','customer_lists.customer_phone as user_phone')
                        ->get();

                    foreach($outsideBooking as $book){
                        $updateDate = Carbon::createFromFormat('Y-m-d H:i:s',$book['date'], "UTC")->setTimezone($timezone);
                        $book['booking_date'] = $updateDate->toDateTimeString();

                        $post = Post::find($book->entity_id);
                        $book['category_logo'] = $post && !empty($post->thumbnail_url) && !empty($post->thumbnail_url->image) ? $post->thumbnail_url->image : '';

                        $book['customer_type'] = 'static';
                        $book['user_name'] = $book['customer_name'] ?? '';
                        $book['user_image'] = '';
                        $book['requested_item_name'] = $book['title'] ?? '';
                        $book['title'] = $book['sub_title'] ?? '';
                        $book['request_booking_status_id'] = $book['status_id'] ?? 1;
                    }
                    $outsideBookingSchedules = collect($outsideBooking)->filter(function ($value, $key) use ($inputs) {
                        return Carbon::parse($value->booking_date)->format("Y-m-d") ==  $inputs['date'];
                    })->values();

                    // OutSideBooking
                    $data['schedules'] = collect($schedules)->merge($outsideBookingSchedules)->sortBy('booking_date')->values();
                }



                Log::info('End code for the get user schedules');
                return $this->sendSuccessResponse(Lang::get('messages.user-profile.success'), 200, $data);
            }
            else{
                Log::info('End code for the get user schedules');
                return $this->sendSuccessResponse(Lang::get('messages.user.token_expired'), 401);
            }
        } catch (\Exception $e) {
            Log::info('Exception in the get user schedules');
            Log::info($e);
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function addSchedule(Request $request)
    {
        try {
            Log::info('Start code for add request service');
            $inputs = $request->all();
            $user = Auth::user();
            if($user){
                DB::beginTransaction();
                $validation = $this->userProfileValidator->validateAddSchedule($inputs);
                if ($validation->fails()) {
                    Log::info('End code for add request service');
                    return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
                }
                $main_country = getCountryFromLatLong($inputs['latitude'],$inputs['longitude']);
                $timezone = get_nearest_timezone($inputs['latitude'],$inputs['longitude'],$main_country);

                $bookingDate = Carbon::createFromFormat('Y-m-d H:i:s', $inputs['booking_date'], $timezone)->setTimezone('UTC');

                if($inputs['entity_type_id'] == EntityTypes::SHOP){
                    $service = Shop::find($inputs['entity_id']);
                }else {
                    $service = Post::find($inputs['entity_id']);
                }
                if($service){
                    $user = User::create(['username' => $inputs['user_name'], 'status_id' => Status::ACTIVE]);

                    UserEntityRelation::create(['user_id' => $user->id,"entity_type_id" => EntityTypes::NORMALUSER,'entity_id' => $user->id]);
                    $random_code = mt_rand(1000000, 9999999);
                    $member = UserDetail::create([
                        'user_id' => $user->id,
                        'name' => $inputs['user_name'],
                        'recommended_code' => $random_code,
                    ]);
                    $data = [
                        'entity_type_id' => $inputs['entity_type_id'],
                        'entity_id' => $inputs['entity_id'],
                        'user_id' => $user->id,
                        'request_booking_status_id' => RequestBookingStatus::BOOK,
                        'booking_date' => $bookingDate
                    ];

                    $requestCustomerData = RequestedCustomer::where('entity_type_id',$inputs['entity_type_id'])
                                                        ->where('entity_id',$inputs['entity_id'])
                                                        ->where('user_id',$user->id)
                                                        ->where('request_booking_status_id',RequestBookingStatus::TALK)->orderBy('id','desc')->first();
                    if($requestCustomerData) {
                        $requestCustomerUpdate = RequestedCustomer::where('id',$requestCustomerData->id)->update($data);
                    }  else {
                        $requestCustomerCreate = RequestedCustomer::create($data);
                    }

                    DB::commit();
                   Log::info('End code for the add request service');
                   return $this->sendSuccessResponse(Lang::get('messages.request-service.add-success'), 200);
                }else{
                    Log::info('End code for the add request service');
                    $message = $inputs['entity_type_id'] == EntityTypes::SHOP ? Lang::get('messages.shop.empty') : Lang::get('messages.post.empty');
                    return $this->sendSuccessResponse($message, 402);
                }
            }else{
                Log::info('End code for add request service');
                return $this->sendSuccessResponse(Lang::get('messages.user.token_expired'), 401);
            }
        } catch (\Exception $e) {
            Log::info('Exception in add request service');
            Log::info($e);
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function deleteSchedule($id)
    {
        try {
            Log::info('Start code for delete request service');
            $user = Auth::user();
            if($user){
                $requestCustomerData = RequestedCustomer::where('id',$id)->delete();
                $notify_type_array = [
                    Notice::BOOKING, Notice::BOOKING_CANCEL, Notice::HOUR_1_BEFORE_VISIT, Notice::HOUR_2_BEFORE_VISIT,Notice::VISIT
                ];
                $delete = Notice::whereIn('notify_type',$notify_type_array)->where('entity_id',$id)->delete();
                Log::info('End code for the delete request service');
                return $this->sendSuccessResponse(Lang::get('messages.request-service.delete-success'), 200);
            }else{
                Log::info('End code for delete request service');
                return $this->sendSuccessResponse(Lang::get('messages.user.token_expired'), 401);
            }
        } catch (\Exception $e) {
            Log::info('Exception in delete request service');
            Log::info($e);
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function checkCoinUsage(Request $request)
    {
        try {
            Log::info('Start code for check coin usage');
            $user = Auth::user();
            $inputs = $request->all();
            $validation = Validator::make($request->all(), [
                'latitude' => 'required',
                'longitude' => 'required',
            ], [], [
                'latitude' => 'Latitude',
                'longitude' => 'Longitude',
            ]);

            if ($validation->fails()) {
                Log::info('End code for update user profile');
                return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
            }

            $main_country = getCountryFromLatLong($inputs['latitude'],$inputs['longitude']);
            $timezone = get_nearest_timezone($inputs['latitude'],$inputs['longitude'],$main_country);

            if($user){
                $coin_usage = UserCreditHistory::where('user_id',$user->id)->orderBy('id','desc')->paginate(config('constant.pagination_count'),"*","coin_usage_page");

                foreach($coin_usage as $coins) {
                    $test = Carbon::createFromFormat('Y/M/d A h:i',$coins->created_at, "UTC")->setTimezone($timezone);
                    $coins->created_at = $test->toDateTimeString();
                }
                Log::info('End code for the check coin usage');
                return $this->sendSuccessResponse(Lang::get('messages.user-profile.coin-usage-success'), 200,compact('coin_usage'));
            }else{
                Log::info('End code for check coin usage');
                return $this->sendSuccessResponse(Lang::get('messages.user.token_expired'), 401);
            }
        } catch (\Exception $e) {
            Log::info('Exception in check coin usage');
            Log::info($e);
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function getCreditPlans(Request $request)
    {
        $user = Auth::user();;
        $inputs = $request->all();
        try {
            Log::info('Start code for the get credit plans');
            if($user) {
                $validation = $this->userProfileValidator->validatePlan($inputs);
                if ($validation->fails()) {
                    Log::info('End code for the get credit plans');
                    return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
                }
                $hospital_count = UserEntityRelation::where('entity_type_id', EntityTypes::HOSPITAL)->where('user_id',$user->id)->count();
                $shop_count = UserEntityRelation::where('entity_type_id', EntityTypes::SHOP)->where('user_id',$user->id)->count();
                $deactivated_by_you = $not_enough_coin = false;
                $is_plan_update = 1;
                if($shop_count) {
                    $active_shop_count = UserEntityRelation::join('shops','shops.id','user_entity_relation.entity_id')
                                                        ->where('entity_type_id', EntityTypes::SHOP)
                                                        ->where('user_entity_relation.user_id',$user->id)
                                                        ->whereIn('shops.status_id',[Status::ACTIVE,Status::PENDING])
                                                        ->count();
                    $total_user_shops = Shop::where('deactivate_by_user',0)->where('user_id', $user->id)->count();
                    // dd($total_user_shops);
                    $user_detail = UserDetail::where('user_id', $user->id)->first();
                    $creditPlan = CreditPlans::where('entity_type_id', EntityTypes::SHOP)->where('package_plan_id', $user_detail->package_plan_id)->first();
                    $userCredits = UserCredit::where('user_id',$user->id)->first();
                    $defaultCredit = $creditPlan ? $creditPlan->amount : 0;
                    $minShopCredit = $defaultCredit * $total_user_shops;
                    $userShop = UserEntityRelation::where('user_id',$user->id)->where('entity_type_id',EntityTypes::SHOP)->pluck('entity_id');
                    $currentShop = Shop::whereIn('status_id',[Status::ACTIVE,Status::PENDING])->whereIn('id',$userShop)->count();
                    $deactivated_by_you = $currentShop == 0 ? true : false;
                    $not_enough_coin = $userCredits->credits >= $minShopCredit ? false : true;
                    $plan_expire_date_next_amount = "-".number_format($active_shop_count * $defaultCredit,0);

                }
                if($hospital_count) {
                    $active_hospital_count = UserEntityRelation::join('hospitals','hospitals.id','user_entity_relation.entity_id')
                                                        ->where('entity_type_id', EntityTypes::HOSPITAL)
                                                        ->where('user_entity_relation.user_id',$user->id)
                                                        ->whereIn('hospitals.status_id',[Status::ACTIVE,Status::PENDING])
                                                        ->count();
                    $userHospital = UserEntityRelation::where('entity_type_id', EntityTypes::HOSPITAL)->where('user_id',$user->id)->first();
                    $currentHospital = Hospital::with(['address' => function($query) {
                        $query->where('entity_type_id', EntityTypes::HOSPITAL);
                    }])->where('id',$userHospital->entity_id)->first();
                    $user_detail = UserDetail::where('user_id', $currentHospital->user_id)->first();
                    $creditPlan = CreditPlans::where('entity_type_id', EntityTypes::HOSPITAL)->where('package_plan_id', $user_detail->package_plan_id)->first();
                    $minHospitalCredit = $creditPlan ? $creditPlan->amount : 0;
                    $userCredits = UserCredit::where('user_id',$currentHospital->user_id)->first();

                    $not_enough_coin= $userCredits->credits <= $minHospitalCredit ? true : false;
                    $deactivated_by_you= $currentHospital->deactivate_by_user == 1 ? true : false;
                    $plan_expire_date_next_amount = "-".number_format($active_hospital_count * $minHospitalCredit,0);
                }
                if($user_detail && $user_detail->last_plan_update != NULL) {
                    $lastPlanDate = new Carbon($user_detail->last_plan_update);
                    $lastPlanDate = $lastPlanDate->addDays(30);
                    $current_date = Carbon::now();
                    $is_plan_update = $current_date->greaterThan($lastPlanDate) ? 1 : 0;
                }

                $plans = CreditPlans::where('entity_type_id',$inputs['entity_type_id'])->get();
                $user_detail = UserDetail::where('user_id',$user->id)->first();

                $user_detail_new = DB::table('users_detail')->where('user_id', $user->id)->first();
                if($not_enough_coin){
                    $plan_expire_date_next = 'Expired';
                }else {
                    $plan_expire_date_next = $user_detail->plan_expire_date;
                }
                // elseif($deactivated_by_you){
                //     $plan_expire_date_next = 'Deactivate';
                // }

                $plan_expire_date_next1 = new Carbon($user_detail->plan_expire_date);
                $plan_expire_date_next1_amount = new Carbon($user_detail->plan_expire_date);

                $checkCurrentDate = $plan_expire_date_next1_amount->subDays(30);
                $checkStartDate = Carbon::parse($checkCurrentDate)->subDay();
                $checkEndDate = Carbon::parse($checkCurrentDate)->addDay();

                $plan_expire_date_amount = UserCreditHistory::where('user_id',$user->id)->where('transaction','debit')->where('type',UserCreditHistory::REGULAR)->whereBetween('created_at',[$checkStartDate,$checkEndDate])->sum('amount');
                $plan_expire_date = $plan_expire_date_next1->subDays(30)->format('M d');


                $plan_expire_date_amount = "-".number_format($plan_expire_date_amount,0);
                $returnData = [
                    'plans' => $plans,
                    'plan_expire_date' => $plan_expire_date,
                    'plan_expire_date_amount' => $plan_expire_date_amount,
                    'plan_expire_date_next' => $plan_expire_date_next,
                    'plan_expire_date_next_amount' => $plan_expire_date_next_amount,
                    'is_plan_update' => $is_plan_update,
                    'checkStartDate' => $checkStartDate,
                    'checkEndDate' => $checkEndDate,
                ];

                Log::info('End code for the get credit plans');
                return $this->sendSuccessResponse(Lang::get('messages.user-profile.plan-success'), 200,$returnData);
            }
            else{
                Log::info('End code for the get credit plans');
                return $this->sendSuccessResponse(Lang::get('messages.user.token_expired'), 401);
            }
        } catch (\Exception $e) {
            Log::info('Exception in the get credit plans');
            Log::info($e);
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }
    public function updateCreditPlans(Request $request)
    {
        $user = Auth::user();
        $inputs = $request->all();
        try {
            Log::info('Start code for the update user profile');
            if($user) {
                $validation = $this->userProfileValidator->validateUpdatePlan($inputs);
                if ($validation->fails()) {
                    Log::info('End code for the update user profile');
                    return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
                }
                UserDetail::where('user_id',$user->id)->update(['package_plan_id' => $inputs['package_plan_id'],'last_plan_update' => Carbon::now()]);
                Log::info('End code for the update user profile');
                return $this->sendSuccessResponse(Lang::get('messages.user-profile.plan-update-success'), 200,[]);
            }
            else{
                Log::info('End code for the update user profile');
                return $this->sendSuccessResponse(Lang::get('messages.user.token_expired'), 401);
            }
        } catch (\Exception $e) {
            Log::info('Exception in the update user profile');
            Log::info($e);
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function deactivateProfile(Request $request)
    {
        $user = Auth::user();
        $inputs = $request->all();
        try {
            Log::info('Start code for the deactivate user profile');
            DB::beginTransaction();
            if($user) {
                $isShop = $user->entityType->contains('entity_type_id', EntityTypes::SHOP);
                $isHospital = $user->entityType->contains('entity_type_id', EntityTypes::HOSPITAL);
                if($isHospital) {
                    $hospitals = UserEntityRelation::where('entity_type_id', EntityTypes::HOSPITAL)->where('user_id', $user->id)->pluck('entity_id');
                    Hospital::whereIn('id',$hospitals)->update(['status_id' => Status::INACTIVE,'deactivate_by_user' => 1]);
                }

                if($isShop) {
                    $shops = UserEntityRelation::where('entity_type_id', EntityTypes::SHOP)->where('user_id', $user->id)->pluck('entity_id');
                    Shop::whereIn('id',$shops)->update(['status_id' => Status::INACTIVE,'deactivate_by_user' => 1]);
                }

                UserDetail::where('user_id',$user->id)->update(['package_plan_id' => PackagePlan::BRONZE]);
                DB::commit();
                Log::info('End code for the deactivate user profile');
                return $this->sendSuccessResponse(Lang::get('messages.user-profile.profile-deactivate-success'), 200,[]);
            }
            else{
                Log::info('End code for the deactivate user profile');
                return $this->sendSuccessResponse(Lang::get('messages.user.token_expired'), 401);
            }
        } catch (\Exception $e) {
            Log::info('Exception in the deactivate user profile');
            Log::info($e);
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }
    public function activateProfile(Request $request)
    {
        $user = Auth::user();
        $inputs = $request->all();
        try {
            Log::info('Start code for the activate user profile');
            DB::beginTransaction();
            $dt = Carbon::now();
            if($user) {
                UserDetail::where('user_id',$user->id)->update(['package_plan_id' => PackagePlan::BRONZE]);
                $isShop = $user->entityType->contains('entity_type_id', EntityTypes::SHOP);
                $isHospital = $user->entityType->contains('entity_type_id', EntityTypes::HOSPITAL);
                if($isHospital) {
                    $hospitals = UserEntityRelation::where('entity_type_id', EntityTypes::HOSPITAL)->where('user_id', $user->id)->get();
                    foreach($hospitals as $hospital){
                        $user_detail = UserDetail::where('user_id', $hospital->user_id)->first();
                        $creditPlan = CreditPlans::where('entity_type_id', EntityTypes::HOSPITAL)->where('package_plan_id', $user_detail->package_plan_id)->first();

                        $defaultCredit = $creditPlan ? $creditPlan->amount : null;
                        if($defaultCredit) {
                            $userCredits = UserCredit::where('user_id',$user_detail->user_id)->first();
                            $old_credit = $userCredits->credits;
                            $total_credit = $old_credit - $defaultCredit;

                            $userCredits = UserCredit::where('user_id',$user_detail->user_id)->update(['credits' => $total_credit]);
                            UserCreditHistory::create([
                                'user_id' => $user_detail->user_id,
                                'amount' => $defaultCredit,
                                'total_amount' => $total_credit,
                                'transaction' => 'debit',
                                'type' => UserCreditHistory::REGULAR
                            ]);
                            Hospital::where('id',$hospital->entity_id)->update(['status_id' => Status::ACTIVE,'credit_deduct_date' => $dt->toDateString()]);
                        }
                    }
                }

                if($isShop) {
                    $shops = UserEntityRelation::where('entity_type_id', EntityTypes::SHOP)->where('user_id', $user->id)->get();
                    foreach($shops as $shop){
                        $user_detail = UserDetail::where('user_id', $shop->user_id)->first();
                        $creditPlan = CreditPlans::where('entity_type_id', EntityTypes::SHOP)->where('package_plan_id', $user_detail->package_plan_id)->first();

                        $defaultCredit = $creditPlan ? $creditPlan->amount : null;
                        if($defaultCredit) {
                            $userCredits = UserCredit::where('user_id',$shop->user_id)->first();
                            $old_credit = $userCredits->credits;
                            $total_credit = $old_credit - $defaultCredit;

                            $userCredits = UserCredit::where('user_id',$shop->user_id)->update(['credits' => $total_credit]);
                            UserCreditHistory::create([
                                'user_id' => $shop->user_id,
                                'amount' => $defaultCredit,
                                'total_amount' => $total_credit,
                                'transaction' => 'debit',
                                'type' => UserCreditHistory::REGULAR
                            ]);
                        }

                        Shop::where('id',$shop->entity_id)->update(['credit_deduct_date' => $dt->toDateString()]);
                    }
                }


                DB::commit();
                Log::info('End code for the activate user profile');
                return $this->sendSuccessResponse(Lang::get('messages.user-profile.profile-activate-success'), 200,[]);
            }
            else{
                Log::info('End code for the activate user profile');
                return $this->sendSuccessResponse(Lang::get('messages.user.token_expired'), 401);
            }
        } catch (\Exception $e) {
            Log::info('Exception in the activate user profile');
            Log::info($e);
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }
    public function activateInactivateEntity(Request $request)
    {
        $user = Auth::user();
        $inputs = $request->all();
        try {
            Log::info('Start code for the deactivate user profile');
            DB::beginTransaction();
            if($user) {
                $validation = $this->userProfileValidator->validateStatusChange($inputs);
                if ($validation->fails()) {
                    Log::info('End code for the deactivate user profile');
                    return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
                }
                if($inputs['entity_type_id'] == EntityTypes::HOSPITAL) {
                    $entity = "Hospital ";
                    Hospital::where('id',$inputs['entity_id'])->update(['status_id' => $inputs['status_id']]);
                }

                if($inputs['entity_type_id'] == EntityTypes::SHOP) {
                    $entity = "Shop ";
                    Shop::where('id',$inputs['entity_id'])->update(['status_id' => $inputs['status_id'],'deactivate_by_user' => 0]);
                }

                $status = $inputs['status_id'] == Status::ACTIVE ? 'activated ' : 'deactivated ';
                $message = $entity.$status."successfully";
                DB::commit();
                Log::info('End code for the deactivate user profile');
                return $this->sendSuccessResponse($message, 200,[]);
            }
            else{
                Log::info('End code for the deactivate user profile');
                return $this->sendSuccessResponse(Lang::get('messages.user.token_expired'), 401);
            }
        } catch (\Exception $e) {
            Log::info('Exception in the deactivate user profile');
            Log::info($e);
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function getSearchHistory(Request $request)
    {
        $user = Auth::user();
        $inputs = $request->all();
        try {
            Log::info('Start code for the get search history');
            if($user) {
                $validation = $this->userProfileValidator->validateSearchHistory($inputs);
                if ($validation->fails()) {
                    Log::info('End code for the get search history');
                    return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
                }
                $category_id = isset($inputs['category_id']) ? $inputs['category_id'] : 0;

                $search_history_query = SearchHistory::where('entity_type_id',$inputs['entity_type_id'])->where('user_id',$user->id);

                if($category_id != 0) {
                    $search_history_query = $search_history_query->where('category_id',$category_id);
                }

                $search_history = $search_history_query->orderBy('updated_at','desc')->limit(10)->get();
                Log::info('End code for the get search history');
                return $this->sendSuccessResponse(Lang::get('messages.user-profile.search-history-success'), 200, compact('search_history'));
            }
            else{
                Log::info('End code for the get search history');
                return $this->sendSuccessResponse(Lang::get('messages.user.token_expired'), 401);
            }
        } catch (\Exception $e) {
            Log::info('Exception in the get search history');
            Log::info($e);
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function deleteSearchHistory($id)
    {
        try {
            Log::info('Start code for delete search history');
            $user = Auth::user();
            if($user){
                $requestCustomerData = SearchHistory::where('id',$id)->delete();
                Log::info('End code for the delete search history');
                return $this->sendSuccessResponse(Lang::get('messages.user-profile.delete-search-success'), 200);
            }else{
                Log::info('End code for delete search history');
                return $this->sendSuccessResponse(Lang::get('messages.user.token_expired'), 401);
            }
        } catch (\Exception $e) {
            Log::info('Exception in delete search history');
            Log::info($e);
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function getSaveHistory(Request $request)
    {
        $user = Auth::user();
        $inputs = $request->all();
        try {
            Log::info('Start code for the get user history');
            $validation = Validator::make($request->all(), [
                'latitude' => 'required',
                'longitude' => 'required',
            ], [], [
                'latitude' => 'Latitude',
                'longitude' => 'Longitude',
            ]);

            if ($validation->fails()) {
                Log::info('End code for get user history');
                return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
            }


            $language_id = isset($inputs['language_id']) ? $inputs['language_id'] : 4;
            if(isset($inputs['latitude']) && isset($inputs['longitude'])) {
                $main_country = getCountryFromLatLong($inputs['latitude'],$inputs['longitude']);
                $timezone = get_nearest_timezone($inputs['latitude'],$inputs['longitude'],$main_country);
            }else {
                $timezone = '';
            }
            if($user) {
                $shopPost = ShopPost::join('user_saved_history', function ($join) use ($user) {
                                    $join->on('shop_posts.id', '=', 'user_saved_history.entity_id')
                                        ->where('user_saved_history.saved_history_type_id', SavedHistoryTypes::SHOP);
                                })
                                ->join('shops', 'shop_posts.shop_id', 'shops.id')
                                ->join('category', function ($join) {
                                    $join->on('shops.category_id', '=', 'category.id')
                                        ->whereNull('category.deleted_at');
                                })
                                ->where(function($query) use ($user){
                                    if ($user) {
                                        $query->whereRaw("shops.id NOT IN (select shop_id from shop_block_histories where user_id = {$user->id} AND is_block = 1)");
                                    }
                                })
                                ->where('user_saved_history.user_id',$user->id)
                                ->where('user_saved_history.is_like',1)
                                ->whereNull('user_saved_history.deleted_at')
                                ->orderBy('user_saved_history.created_at','desc')
                                ->groupBy('shop_posts.id')
                                ->select('shop_posts.*')
                                ->paginate(config('constant.pagination_count'),"*","shop_page");

                $hospitalPost = Post::join('user_saved_history', function ($join) use ($user) {
                                    $join->on('posts.id', '=', 'user_saved_history.entity_id')
                                        ->where('user_saved_history.saved_history_type_id', SavedHistoryTypes::HOSPITAL);
                                })
                                ->where('user_saved_history.user_id',$user->id)
                                ->where('user_saved_history.is_like',1)
                                ->whereNull('user_saved_history.deleted_at')
                                ->orderBy('user_saved_history.created_at','desc')
                                ->groupBy('posts.id')
                                ->select('posts.*')
                                ->paginate(config('constant.pagination_count'),"*","hospital_page");

                $community = Community::join('user_saved_history', function ($join) use ($user) {
                                    $join->on('community.id', '=', 'user_saved_history.entity_id')
                                    ->where('user_saved_history.saved_history_type_id', SavedHistoryTypes::COMMUNITY);
                                })
                                ->where('user_saved_history.user_id',$user->id)
                                ->where('user_saved_history.is_like',1)
                                ->whereNull('user_saved_history.deleted_at')
                                ->orderBy('user_saved_history.created_at','desc')
                                ->groupBy('community.id')
                                ->select('community.*')
                                ->paginate(config('constant.pagination_count'),"*","community_page");


                $reviews = Reviews::join('user_saved_history', function ($join) use ($user) {
                                    $join->on('reviews.id', '=', 'user_saved_history.entity_id')
                                        ->where('user_saved_history.saved_history_type_id', SavedHistoryTypes::REVIEWS);
                                })
                                ->where('user_saved_history.user_id',$user->id)
                                ->where('user_saved_history.is_like',1)
                                ->whereNull('user_saved_history.deleted_at')
                                ->orderBy('user_saved_history.created_at','desc')
                                ->groupBy('reviews.id')
                                ->select('reviews.*')
                                ->paginate(config('constant.pagination_count'),"*","reviews_page")->toArray();

                $associationCommunity = AssociationCommunity::join('user_saved_history', function ($join) use ($user) {
                                    $join->on('association_communities.id', '=', 'user_saved_history.entity_id')
                                    ->where('user_saved_history.saved_history_type_id', SavedHistoryTypes::ASSOCIATION_COMMUNITY);
                                })
                                ->where('user_saved_history.user_id',$user->id)
                                ->where('user_saved_history.is_like',1)
                                ->whereNull('user_saved_history.deleted_at')
                                ->orderBy('user_saved_history.created_at','desc')
                                ->groupBy('association_communities.id')
                                ->select('association_communities.*')
                                ->with('comments')
                                ->paginate(config('constant.pagination_count'),"*","association_community_page");

                foreach($reviews['data'] as $key => $value) {
                    if($value['entity_type_id'] == EntityTypes::SHOP){
                        $shop = Shop::find($value['entity_id']);
                        if($shop) {
                            $temp = ['category' => $shop->category_name, 'title' => $shop->main_name];
                            $reviews['data'][$key]['post_detail'] = $temp;
                        }else {
                            unset($reviews['data'][$key]);
                        }
                    }else{
                        $post = Post::find($value['entity_id']);
                        if($post) {
                            $temp = ['category' => $post->category_name, 'title' => $post->title];
                            $reviews['data'][$key]['post_detail'] = $temp;
                        }else {
                            unset($reviews['data'][$key]);
                        }
                    }
                }
                $reviews['data'] = array_values($reviews['data']);

                $data = [
                    'shop' => $shopPost,
                    'hospital' => $hospitalPost,
                    'community' => $this->timeLanguageFilter($community->toArray(),$language_id,$timezone),
                    'reviews' => $this->timeLanguageFilter($reviews,$language_id,$timezone),
                    'association_community' => $this->timeLanguageFilter($associationCommunity->toArray(),$language_id,$timezone),
                ];
                Log::info('End code for the get user history');
                return $this->sendSuccessResponse(Lang::get('messages.user-profile.save-history-success'), 200, $data);
            }
            else{
                Log::info('End code for the get user history');
                return $this->sendSuccessResponse(Lang::get('messages.user.token_expired'), 401);
            }
        } catch (\Exception $e) {
            Log::info($e);
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function getYourPost(Request $request)
    {
        $user = Auth::user();
        $inputs = $request->all();
        try {
            Log::info('Start code for the get your post');
            $validation = Validator::make($request->all(), [
                'latitude' => 'required',
                'longitude' => 'required',
            ], [], [
                'latitude' => 'Latitude',
                'longitude' => 'Longitude',
            ]);

            if ($validation->fails()) {
                Log::info('End code for get user history');
                return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
            }


            $language_id = isset($inputs['language_id']) ? $inputs['language_id'] : 4;
            if(isset($inputs['latitude']) && isset($inputs['longitude'])) {
                $main_country = getCountryFromLatLong($inputs['latitude'],$inputs['longitude']);
                $timezone = get_nearest_timezone($inputs['latitude'],$inputs['longitude'],$main_country);
            }else {
                $timezone = '';
            }

            if($user) {
                $community = Community::where('user_id',$user->id)
                                ->orderBy('created_at','desc')
                                ->select('community.*')
                                ->paginate(config('constant.pagination_count'),"*","community_page");

                $reviews = Reviews::where('reviews.user_id',$user->id)
                                ->orderBy('created_at','desc')
                                ->select('reviews.*')
                                ->paginate(config('constant.pagination_count'),"*","reviews_page")->toArray();

                foreach($reviews['data'] as $key => $value) {
                    if($value['entity_type_id'] == EntityTypes::SHOP){
                        $shop = Shop::find($value['entity_id']);
                        if($shop) {
                            $temp = ['category' => $shop->category_name, 'title' => $shop->main_name];
                            $reviews['data'][$key]['post_detail'] = $temp;
                        }else {
                            unset($reviews['data'][$key]);
                        }
                    }else{
                        $post = Post::find($value['entity_id']);
                        if($post) {
                            $temp = ['category' => $post->category_name, 'title' => $post->title];
                            $reviews['data'][$key]['post_detail'] = $temp;
                        }else {
                            unset($reviews['data'][$key]);
                        }
                    }
                }
                $reviews['data'] = array_values($reviews['data']);

                $data = [
                    'community' => $this->timeLanguageFilter($community->toArray(),$language_id,$timezone),
                    'reviews' => $this->timeLanguageFilter($reviews,$language_id,$timezone),
                ];
                Log::info('End code for the get your post');
                return $this->sendSuccessResponse(Lang::get('messages.user-profile.your-post-success'), 200, $data);
            }
            else{
                Log::info('End code for the get your post');
                return $this->sendSuccessResponse(Lang::get('messages.user.token_expired'), 401);
            }
        } catch (\Exception $e) {
            Log::info('Exception in the get your post');
            Log::info($e);
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }
    public function changeUserLanguage(Request $request)
    {
        $user = Auth::user();
        $inputs = $request->all();
        try {
            Log::info('Start code for change language');
            if($user) {
                $validation = $this->userProfileValidator->validateChangeLanguage($inputs);
                if ($validation->fails()) {
                    Log::info('End code for change language');
                    return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
                }

                $updateDate = ['language_id' => $inputs['language_id']];

                $getUserDetail = UserDetail::where('user_id',$user->id)->first();
                $previousPoint = !empty($getUserDetail->points) ? $getUserDetail->points : UserDetail::POINTS_40;
                $points_updated_on = Carbon::parse($getUserDetail->points_updated_on)->format('Y-m-d');
                $now = date('Y-m-d');
                $days = !empty($getUserDetail->count_days) ? $getUserDetail->count_days : 1;
                $diff_in_days = Carbon::parse($points_updated_on)->diffInDays($now);
                $points = $previousPoint;
                $previousLevel = !empty($getUserDetail->level) ? $getUserDetail->level : 1;
                $cardNumber = !empty($getUserDetail->card_number) ? $getUserDetail->card_number : 1;
                $language_id = $getUserDetail->language_id;

                if($points_updated_on  != Carbon::now()->toDateString()){
                    $days = $days + 1;
                    $points = $previousPoint + UserDetail::POINTS_40;
                    $updateDate['points_updated_on'] = Carbon::now();

                    // Send Push notification start
                    /*
                    $key = Notice::CONNECTING_FIRST_TIME_IN_DAY.'_'.$language_id;
                    $userIds = [$user->id];

                    $devices = UserDevices::whereIn('user_id', $userIds)->pluck('device_token')->toArray();

                    $title_msg = __("notice.$key");
                    $notify_type = Notice::CONNECTING_FIRST_TIME_IN_DAY;

                    $nextLevel = getUserNextAwailLevel($user->id,$getUserDetail->level);
                    $next_level_key = "language_$language_id.next_level_card";
                    $next_level_msg = __("messages.$next_level_key", ['level' => $nextLevel]);

                    $notice = Notice::create([
                        'notify_type' => Notice::CONNECTING_FIRST_TIME_IN_DAY,
                        'user_id' => $user->id,
                        'to_user_id' => $user->id,
                        'entity_type_id' => $user->entity_type_id,
                        'entity_id' => $user->id,
                        'title' => '+'.UserDetail::POINTS_40.'exp',
                        'sub_title' => $nextLevel,
                        'is_aninomity' => 0
                    ]);

                    $format = '+'.UserDetail::POINTS_40.'exp'." ".$next_level_msg;
                    $notificationData = [
                        'id' => $user->id,
                        'user_id' => $user->id,
                        'title' => $title_msg,
                    ];
                    if (count($devices) > 0) {
                        $result = $this->sentPushNotification($devices,$title_msg, $format, $notificationData ,$notify_type);
                    } */
                    // Send Push notification end
                }

                if(!empty($points)){
                    $getLevel = DB::table('levels')->select('id')->where('points','<=',$points)->orderBy('id','desc')->limit(1)->first();
                    $level = !empty($getLevel) ? $getLevel->id : 1;

                    if(($previousLevel) && $level > $previousLevel){

                        $cards = Cards::select('card_number')->whereRaw("start <=".$level." OR (end <= ".$level ." )")->orderBy('id','desc')->limit(0,1)->first()->toArray();

                        $cardNumber = !empty($cards) ? $cards['card_number'] :1;

                        // user automatically owned random cards
                        $getUserOwnCardCount = UserCards::where(['user_id'=>$user->id])->count();
                        if($cardNumber > $getUserOwnCardCount){

                            // To get random card according to user's level
                            $getCardsByLevelQ = DefaultCardsRives::select('default_cards_rives.*')->leftjoin('default_cards as dc','dc.id','default_cards_rives.default_card_id');

                            //$getCardsByLevelQ = $getCardsByLevelQ->whereRaw("(dc.start <= ".$level." OR (dc.end <= ".$level." ))");
                            $getCardsByLevelQ = $getCardsByLevelQ->whereRaw("(dc.start <= ".$level." AND dc.end >= ".$level." )");

                            $getCardsByLevelQ = $getCardsByLevelQ->whereNotIn('default_cards_rives.id',function($q) use($user){
                                $q->select('default_cards_id')->from('user_cards')->where('user_id',$user->id);
                            });

                            $getCardsByLevelQ = $getCardsByLevelQ->inRandomOrder()->limit(1)->first();

                            if(!empty($getCardsByLevelQ)){
                                $cardData = [
                                    'user_id' => $user->id,
                                    'default_cards_id' => $getCardsByLevelQ->default_card_id,
                                    'default_cards_riv_id' => $getCardsByLevelQ->id
                                ];
                                $userCard = UserCards::create($cardData);
                                createUserCardDetail($getCardsByLevelQ,$userCard);

                                 // Send Push notification when new card acquired start
                                $key = Notice::NEW_CARD_ACQUIRED.'_'.$language_id;
                                $userIds = [$user->id];
                                $devices = UserDevices::whereIn('user_id', $userIds)->pluck('device_token')->toArray();

                                $notify_type = Notice::NEW_CARD_ACQUIRED;

                                $notice = Notice::create([
                                    'notify_type' => Notice::NEW_CARD_ACQUIRED,
                                    'user_id' => $user->id,
                                    'to_user_id' => $user->id,
                                    'entity_type_id' => $user->entity_type_id,
                                    'entity_id' => $user->id,
                                    'title' => '',
                                    'sub_title' => '',
                                    'is_aninomity' => 0
                                ]);

                                $title_msg = '';
                                $format = __("notice.$key");
                                $notificationData = [
                                    'id' => $user->id,
                                    'user_id' => $user->id,
                                    'title' => $title_msg,
                                ];
                                if (count($devices) > 0) {
                                    $result = $this->sentPushNotification($devices,$title_msg, $format, $notificationData ,$notify_type);
                                }
                                // Send Push notification when new card acquired end
                            }
                        }

                        // Send push when level up to the next
                        // Send Push notification start
                       /*  $nextCardLevel = getUserNextAwailLevel($user->id,$level);
                        $key = Notice::LEVEL_UP.'_'.$language_id;
                        $userIds = [$user->id];
                        $devices = UserDevices::whereIn('user_id', $userIds)->pluck('device_token')->toArray();

                        $title_msg = __("notice.$key");
                        $notify_type = Notice::LEVEL_UP;

                        $notice = Notice::create([
                            'notify_type' => Notice::LEVEL_UP,
                            'user_id' => $user->id,
                            'to_user_id' => $user->id,
                            'entity_type_id' => $user->entity_type_id,
                            'entity_id' => $user->id,
                            'title' => 'LV '.$level,
                            'sub_title' => $nextCardLevel,
                            'is_aninomity' => 0
                        ]);

                        $next_level_key = "language_$language_id.next_level_card";
                        $next_level_msg = __("messages.$next_level_key", ['level' => $nextCardLevel]);

                        $format ='LV '.$level." ".$next_level_msg;
                        $notificationData = [
                            'id' => $user->id,
                            'user_id' => $user->id,
                            'title' => $title_msg,
                        ];
                        if (count($devices) > 0) {
                            $result = $this->sentPushNotification($devices,$title_msg, $format, $notificationData ,$notify_type);
                        } */
                    // Send Push notification end
                    }
                }

                $updateDate['count_days'] = $days;
                $updateDate['points'] = $points;
                $updateDate['level'] = $level;
                $updateDate['card_number'] = $cardNumber;

                $user_detail = UserDetail::where('user_id',$user->id)->update($updateDate);

                Log::info('End code for change language');
                return $this->sendSuccessResponse(Lang::get('messages.user-profile.language-success'), 200);
            }
            else{
                Log::info('End code for change language');
                return $this->sendSuccessResponse(Lang::get('messages.user.token_expired'), 401);
            }
        } catch (\Exception $e) {
            print_r($e->getMessage());die;
            Log::info('Exception in change language');
            Log::info($e);
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function changeUserLocation(Request $request)
    {
        $user = Auth::user();
        $inputs = $request->all();
        try {
            Log::info('Start code for change location');
            if($user) {
                $validation = $this->userProfileValidator->validateChangeLocation($inputs);
                if ($validation->fails()) {
                    Log::info('End code for change location');
                    return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
                }
                $address = $inputs['address'].", ";
                $address .= $request->has('address_detail') && $inputs['address_detail'] != NULL ? $inputs['address_detail'].", " : "";
                $address .= $inputs['city_id'].", ".$inputs['state_id'].", ".$inputs['country_id'];

                $devices = UserDevices::whereIn('user_id', [$user->id])->pluck('device_token')->toArray();
                $user_detail = UserDetail::where('user_id', $user->id)->first();
                $language_id = $user_detail->language_id;
                $key = Notice::AREA_CHANGE.'_'.$language_id;
                $format = __("notice.$key");
                $notify_type = Notice::AREA_CHANGE;
                $title_msg = '';
                $notice = Notice::create([
                    'notify_type' => Notice::AREA_CHANGE,
                    'user_id' => $user->id,
                    'to_user_id' => $user->id,
                    'title' => $address,
                ]);

                $notificationData = [
                    'id' => $user->id,
                    'name' => $user->name,
                    'address' => $address,
                ];

                if (count($devices) > 0) {
                    $result = $this->sentPushNotification($devices,$title_msg, $format, $notificationData ,$notify_type, $user->id);
                }

                Log::info('End code for change location');
                return $this->sendSuccessResponse(Lang::get('messages.user-profile.location-success'), 200);
            }
            else{
                Log::info('End code for change location');
                return $this->sendSuccessResponse(Lang::get('messages.user.token_expired'), 401);
            }
        } catch (\Exception $e) {
            Log::info('Exception in change location');
            Log::info($e);
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }
    public function getUserPopup(Request $request)
    {
        $user = Auth::user();
        $inputs = $request->all();
        try {
            Log::info('Start code for get popup');
            if($user) {
                $validation = $this->userProfileValidator->validatePopup($inputs);
                if ($validation->fails()) {
                    Log::info('End code for get popup');
                    return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
                }
                $main_country = getCountryFromLatLong($inputs['latitude'],$inputs['longitude']);
                $date = Carbon::now();
                $exclude = UserHidePopupImage::where('user_id',$user->id)->pluck('banner_image_id')->toArray();

                $bannerImages = Banner::join('banner_images', 'banners.id', '=', 'banner_images.banner_id')
                                    ->where('banners.entity_type_id',NULL)
                                    ->where('banners.section','popup')
                                    ->whereNull('banners.deleted_at')
                                    ->whereNull('banner_images.deleted_at')
                                    ->whereNotIn('banner_images.id', $exclude)
                                    ->whereRaw('? between banner_images.from_date and banner_images.to_date', [$date->toDateString()])
                                    // ->whereDate('banner_images.from_date', '>=', Carbon::now())
                                    // ->whereDate('banner_images.to_date', '<=', Carbon::now())
                                    ->where('banners.country_code',$main_country)
                                    ->orderBy('banner_images.order','desc')
                                    ->orderBy('banner_images.id','desc')
                                    ->get('banner_images.*');
                                    // ->toSql();
                                    // dd($bannerImages);
                $banners = [];
                foreach($bannerImages as $banner){
                    $temp = [];
                    $temp['image'] = Storage::disk('s3')->url($banner->image);
                    $temp['id'] = $banner->id;
                    $temp['link'] = $banner->link;
                    $temp['from_date'] = $banner->from_date;
                    $temp['to_date'] = $banner->to_date;
                    $banners[] = $temp;
                }
                $user_detail = UserDetail::where('user_id', $user->id)->first();
                $hide_popup = !empty($user_detail) ? $user_detail->hide_popup : 0;

                Log::info('End code for get popup');
                return $this->sendSuccessResponse(Lang::get('messages.user-profile.popup-success'), 200,compact('banners','hide_popup'));
            }
            else{
                Log::info('End code for get popup');
                return $this->sendSuccessResponse(Lang::get('messages.user.token_expired'), 401);
            }
        } catch (\Exception $e) {
            Log::info('Exception in for get popup');
            Log::info($e);
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }
    public function popupHide(Request $request)
    {
        $user = Auth::user();
        $inputs = $request->all();
        try {
            Log::info('Start code for hide popup');
            if($user) {
                $validation = $this->userProfileValidator->validatePopupHide($inputs);
                if ($validation->fails()) {
                    Log::info('End code for get popup');
                    return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
                }

                UserHidePopupImage::firstOrCreate(['user_id' => $user->id, 'banner_image_id' => $inputs['banner_image_id']]);

                // UserDetail::where('user_id',$user->id)->update(['hide_popup' => 1]);
                Log::info('End code for hide popup');
                return $this->sendSuccessResponse(Lang::get('messages.user-profile.popup-hide-success'), 200);
            }
            else{
                Log::info('End code for hide popup');
                return $this->sendSuccessResponse(Lang::get('messages.user.token_expired'), 401);
            }
        } catch (\Exception $e) {
            Log::info('Exception in hide popup');
            Log::info($e);
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function getUserEntityList(Request $request)
    {
        $user = Auth::user();
        $inputs = $request->all();
        try {
            Log::info('Start code for the get your post');
            if($user) {
                if(isset($inputs['display']) && !empty($inputs['display']) && $inputs['display'] == 'all'){
                    $statusToShow = [Status::ACTIVE,Status::INACTIVE,Status::PENDING];
                }else{
                    $statusToShow = [Status::ACTIVE];
                }
                $hospital_count = UserEntityRelation::where('entity_type_id', EntityTypes::HOSPITAL)->where('user_id',$user->id)->count();
                $shop_count = UserEntityRelation::where('entity_type_id', EntityTypes::SHOP)->where('user_id',$user->id)->count();
                if($shop_count > 0) {
                    $posts = Shop::where('user_id',$user->id)->whereIn('status_id',$statusToShow)->get();
                }elseif($hospital_count > 0) {
                    $user_detail = UserEntityRelation::where('entity_type_id', EntityTypes::HOSPITAL)->where('user_id',$user->id)->first();
                    $posts = Post::where('hospital_id',$user_detail->entity_id)->whereIn('status_id',$statusToShow)->get();
                }else {
                    $posts = [];
                }


                $data = [
                    'posts' => $posts,
                ];
                Log::info('End code for the get your post');
                return $this->sendSuccessResponse(Lang::get('messages.user-profile.your-post-success'), 200, $data);
            }
            else{
                Log::info('End code for the get your post');
                return $this->sendSuccessResponse(Lang::get('messages.user.token_expired'), 401);
            }
        } catch (\Exception $e) {
            Log::info('Exception in the get your post');
            Log::info($e);
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function updateEntityAddress(Request $request)
    {
        $user = Auth::user();
        $inputs = $request->all();
        try {
            Log::info('Start code for the update shop / hospital address');
            if($user) {
                $validation = $this->userProfileValidator->validateChangeAddress($inputs);
                if ($validation->fails()) {
                    Log::info('End code for the update shop / hospital address');
                    return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
                }
                if(!empty($inputs['country_id']) && !empty($inputs['state_id']) && !empty($inputs['city_id'])){
                    $location = $this->addCurrentLocation($inputs['country_id'], $inputs['state_id'], $inputs['city_id']);

                    $isExist = Address::where(['entity_type_id' => $inputs['entity_type_id'], 'entity_id'=> $inputs['entity_id']])->first();

                    $defaultCountryCode = (!empty($isExist)) ? $isExist->main_country : 'KR';
                    $country_code =  $inputs['country_code'] ?? $defaultCountryCode; //getCountryFromLatLong($inputs['latitude'],$inputs['longitude']);
                    if ($location) {
                        $address = Address::updateOrCreate(['entity_type_id' => $inputs['entity_type_id'], 'entity_id'=> $inputs['entity_id']],[
                                    'address' => $inputs['address'],
                                    'address2' => isset($inputs['address_detail']) ? $inputs['address_detail'] : '',
                                    'country_id' => $location['country']->id,
                                    'city_id' => $location['city']->id,
                                    'state_id' => $location['city']->state_id,
                                    'latitude' => $inputs['latitude'],
                                    'longitude' => $inputs['longitude'],
                                    'main_country' => $country_code,
                                    'entity_type_id' => $inputs['entity_type_id'],
                                    'entity_id'=> $inputs['entity_id']
                                ]);

                        if($inputs['entity_type_id'] == EntityTypes::SHOP){
                            $profileController = new \App\Http\Controllers\Api\ShopProfileController;
                            $profileController->checkShopStatus($inputs['entity_id']);
                        }
                    }
                }
                Log::info('End code for the update shop / hospital address');
                return $this->sendSuccessResponse(Lang::get('messages.user-profile.location-success'), 200);
            }
            else{
                Log::info('End code for the update shop / hospital address');
                return $this->sendSuccessResponse(Lang::get('messages.user.token_expired'), 401);
            }
        } catch (\Exception $e) {
            Log::info('Exception in the update shop / hospital address');
            Log::info($e);
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function userInconvinenceMail(Request $request)
    {
        $user = Auth::user();
        $inputs = $request->all();
        try {
            Log::info('Start code for the send user profile inconvinence mail');
            if($user) {
                $validation = Validator::make($request->all(), [
                    'comment' => 'required',
                ], [], [
                    'comment' => 'Comment',
                ]);

                if ($validation->fails()) {
                    Log::info('End code for the send user profile inconvinence mail');
                    return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
                }

                $config = Config::where('key',Config::USER_INCONVINIENCE_EMAIL)->first();
                if($config) {
                    $userData = [];
                    $userData['email_body'] = "<p><b>User Name: </b>".$user['name']."</p>";
                    $userData['email_body'] .= "<p><b>User Email: </b>".$user['email']."</p>";
                    $userData['email_body'] .= "<p><b>User Phone Number: </b>".$user['mobile']."</p>";
                    $userData['email_body'] .= "<p><b>User Complain: </b>".$inputs['comment']."</p>";
                    $userData['title'] = 'User Inconvinence';
                    $userData['subject'] = 'User Inconvinence';
                    $userData['username'] = 'Admin';
                    if($config->value) {
                        Mail::to($config->value)->send(new CommonMail($userData));
                    }
                }

                Log::info('End code for the send user profile inconvinence mail');
                return $this->sendSuccessResponse(Lang::get('messages.user-profile.mail-success'), 200, []);
            }
            else{
                Log::info('End code for the send user profile inconvinence mail');
                return $this->sendSuccessResponse(Lang::get('messages.user.token_expired'), 401);
            }
        } catch (\Exception $e) {
            Log::info('Exception in the send user profile inconvinence mail');
            Log::info($e);
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function BussinessInconvinenceMail(Request $request)
    {
        $user = Auth::user();
        $inputs = $request->all();
        try {
            Log::info('Start code for the send business profile inconvinence mail');
            if($user) {
                $validation = Validator::make($request->all(), [
                    'category' => 'required',
                    'comment' => 'required',
                ], [], [
                    'category' => 'Category',
                    'comment' => 'Comment',
                ]);

                if ($validation->fails()) {
                    Log::info('End code for the send business profile inconvinence mail');
                    return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
                }

                $config = Config::where('key',Config::BUSINESS_ASKING_EMAIL)->first();
                $hospital_data = UserEntityRelation::where('entity_type_id', EntityTypes::HOSPITAL)->where('user_id',$user->id)->first();
                $shop_data = UserEntityRelation::where('entity_type_id', EntityTypes::SHOP)->where('user_id',$user->id)->first();

                if($config) {
                    if($hospital_data) {
                        $hospital = Hospital::find($hospital_data->entity_id);
                        $name = $hospital->main_name;
                        $category = $hospital->category_name;
                    }else if($shop_data) {
                        $shop = Shop::find($shop_data->entity_id);
                        $name = $shop->main_name ."/". $shop->shop_name;
                        $category = $shop->category_name;
                    }else {
                        $name = "Not business user";
                        $category = "";
                    }
                    $userData = [];
                    $userData['email_body'] = "<p><b>Inquire Category: </b>".$inputs['category']."</p>";
                    $userData['email_body'] .= "<p><b>User Name: </b>".$user['name']."</p>";
                    $userData['email_body'] .= "<p><b>Business Name: </b>".$name."</p>";
                    $userData['email_body'] .= "<p><b>Business Category: </b>".$category."</p>";
                    $userData['email_body'] .= "<p><b>Phone Number: </b>".$user['mobile']."</p>";
                    $userData['email_body'] .= "<p><b>Text Field: </b>".$inputs['comment']."</p>";
                    $userData['title'] = 'Business Inquiry - '.$inputs['category'];
                    $userData['subject'] = 'Business Inquiry - '.$inputs['category'];
                    $userData['username'] = 'Admin';
                    if($config->value) {
                        Mail::to($config->value)->send(new CommonMail($userData));
                    }
                }

                Log::info('End code for the send business profile inconvinence mail');
                return $this->sendSuccessResponse(Lang::get('messages.user-profile.mail-success'), 200, []);
            }
            else{
                Log::info('End code for the send business profile inconvinence mail');
                return $this->sendSuccessResponse(Lang::get('messages.user.token_expired'), 401);
            }
        } catch (\Exception $e) {
            Log::info('Exception in the send business profile inconvinence mail');
            Log::info($e);
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function getBlockedUser(Request $request)
    {
        $user = Auth::user();
        $inputs = $request->all();
        try {

            if($user) {

                $validation = Validator::make($inputs, [
                    'type' => 'required',
                ], [], [
                    'type' => 'Type',
                ]);

                if ($validation->fails()) {
                    return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
                }
                $type = !empty($inputs['type']) ? $inputs['type'] : UserBlockHistory::VIDEO_CALL;
                $user_id = $user->id;

                $query = User::select('users.id','users.created_at','user_block_history.block_for as type','user_block_history.id as block_id','user_block_history.user_id','user_block_history.block_user_id');
                $query = $query->leftJoin('user_block_history','user_block_history.block_user_id','users.id');
                $query = $query->where(['user_block_history.user_id' => $user_id,'block_for' => $type, 'is_block' => 1]);
                $blockUser = $query->get();
                $blockUser = $blockUser->makeHidden(['mobile','gender','phone_code','mobile','recommended_code','user_credits','package_plan_id','package_plan_name','verify_status','sns_type','sns_link']);

                return $this->sendSuccessResponse(Lang::get('messages.user-profile.list-success'), 200,compact('blockUser'));
            }
            else{

                return $this->sendSuccessResponse(Lang::get('messages.user.token_expired'), 401);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function blockUser(Request $request)
    {
        $user = Auth::user();
        $inputs = $request->all();
        try {
            Log::info('Start code to make user block for video call');
            if($user) {

                $validation = Validator::make($inputs, [
                    'user_id' => 'required',
                    'type' => 'required',
                ], [], [
                    'user_id' => 'User ID',
                    'type' => 'Type',
                ]);

                if ($validation->fails()) {
                    Log::info('End code to make user block for video call');
                    return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
                }
                $blockUserId = $inputs['user_id'];
                $type = !empty($inputs['type']) ? $inputs['type'] : UserBlockHistory::VIDEO_CALL;
                $user_id = $user->id;

                $is_available = UserBlockHistory::where(['user_id' => $user_id, 'block_user_id' => $blockUserId,'block_for' => $type])->first();

                if(!empty($is_available)){
                    UserBlockHistory::where(['user_id' => $user_id, 'block_user_id' => $blockUserId,'block_for' => $type])->update(['is_block' => 1]);

                }else{
                    UserBlockHistory::create(['user_id' => $user_id, 'block_user_id' => $blockUserId, 'is_block' => 1,'block_for' => $type]);
                }

                Log::info('End code to make user block for video call');
                return $this->sendSuccessResponse(Lang::get('messages.user-profile.block-user'), 200);
            }
            else{
                Log::info('End code to make user block for video call');
                return $this->sendSuccessResponse(Lang::get('messages.user.token_expired'), 401);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function unBlockUser(Request $request)
    {
        $user = Auth::user();
        $inputs = $request->all();
        try {
            Log::info('Start code to make user unblock for video call');
            if($user) {

                $validation = Validator::make($inputs, [
                    'user_id' => 'required',
                    'type' => 'required',
                ], [], [
                    'user_id' => 'User ID',
                    'type' => 'Type',
                ]);

                if ($validation->fails()) {
                    Log::info('End code to make user unblock for video call');
                    return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
                }
                $blockUserId = $inputs['user_id'];
                $user_id = $user->id;
                $type = !empty($inputs['type']) ? $inputs['type'] : UserBlockHistory::VIDEO_CALL;

                $is_available = UserBlockHistory::where(['user_id' => $user_id, 'block_user_id' => $blockUserId,'block_for' => $type])->first();

                if(!empty($is_available)){
                    UserBlockHistory::where(['user_id' => $user_id, 'block_user_id' => $blockUserId,'block_for' => $type])->update(['is_block' => 0]);
                }else{
                    UserBlockHistory::create(['user_id' => $user_id, 'block_user_id' => $blockUserId,'block_for' => $type, 'is_block' => 0]);
                }

                $query = User::select('users.id','users.created_at','user_block_history.id as block_id','user_block_history.user_id','user_block_history.block_user_id');
                $query = $query->leftJoin('user_block_history','user_block_history.block_user_id','users.id');
                $query = $query->where(['user_block_history.user_id' => $user_id,'block_for' => $type, 'is_block' => 1]);
                $blockUser = $query->get();
                $blockUser = $blockUser->makeHidden(['mobile','gender','phone_code','mobile','recommended_code','user_credits','package_plan_id','package_plan_name','verify_status','sns_type','sns_link']);

                Log::info('End code to make user unblock for video call');
                return $this->sendSuccessResponse(Lang::get('messages.user-profile.un-block-user'), 200,compact('blockUser'));
            }
            else{
                Log::info('End code to make user unblock for video call');
                return $this->sendSuccessResponse(Lang::get('messages.user.token_expired'), 401);
            }
        } catch (\Exception $e) {
            Log::info('Exception in make user unblock for video call');
            Log::info($e);
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function connectSNStoProfile(Request $request)
    {
        $user = Auth::user();
        try {
            Log::info('Start code for connect SNS to profile');
            if($user){
                $inputs = $request->all();
                $validation = $this->userProfileValidator->validateConnectSNSStatus($inputs);

                if ($validation->fails()) {
                    Log::info('End code for connect SNS to profile');
                    return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
                }

                UserDetail::where('user_id', $user->id)->update([
                    'sns_type' => $inputs['sns_type'],
                    'sns_link' => $inputs['sns_link']
                ]);
                $user = Auth::user();

                Log::info('End code for the get shop list');
                return $this->sendSuccessResponse(Lang::get('messages.user-profile.update-success'), 200, $user);

            }else{
                Log::info('End code for update user avatar image');
                return $this->sendSuccessResponse(Lang::get('messages.user.token_expired'), 401);
            }

        } catch (\Exception $e) {
            Log::info('Exception in connect SNS to shops');
            Log::info($e);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function rewardSNSRequest(Request $request)
    {
        $user = Auth::user();
        $current_date = Carbon::now();
        try {
            Log::info('Start request reward SNS ');
            $inputs = $request->all();
            $snsData = UserInstagramHistory::where('user_id',$user->id)->first();

            if(!empty($snsData)){
                UserInstagramHistory::where('user_id',$user->id)->update(['requested_at' => $current_date, 'status' => 0, 'request_count' => DB::raw('request_count + 1')]);
            }else{
                $snsData = UserInstagramHistory::create([
                    'user_id' => $user->id,
                    'penalty_count' => 0,
                    'reward_count' => 0,
                    'reject_count' => 0,
                    'requested_at' => $current_date,
                    'status' => 0,
                ]);

                $config = Config::where('key',Config::REQUEST_CLIENT_REPORT_SNS_REWARD_EMAIL)->first();
                if($config) {
                    $userData = [];
                    $userData['email_body'] = "<p><b>Shop Name: </b>".$snsData->entity_name."</p>";
                    $userData['email_body'] .= "<p><b>SNS Link: </b>".$snsData->sns_link."</p>";
                    $userData['email_body'] .= "<p><b>Penalty Times: </b>".$snsData->penalty_count."</p>";
                    $userData['email_body'] .= "<p><b>Reject Times: </b>".$snsData->reject_count."</p>";
                    $userData['email_body'] .= "<p><b>Reward Times: </b>".$snsData->reward_count."</p>";
                    $userData['email_body'] .= "<p><b>Phone Number: </b>".$snsData->phone."</p>";
                    $userData['title'] = 'SNS Reward';
                    $userData['subject'] = 'SNS Reward';
                    $userData['username'] = 'Admin';
                    if($config->value) {
                        Mail::to($config->value)->send(new CommonMail($userData));
                    }
                }
            }
            Log::info('End request reward SNS ' );
            return $this->sendSuccessResponse(Lang::get('messages.user-profile.sns-request-success'), 200, $snsData);
        } catch (\Exception $e) {
            Log::info('Exception in request SNS');
            Log::info($e);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    // Check recommonded code exist for shop / hospital
    public function checkRecommondedCode(Request $request){
        $inputs = $request->all();
        try {
                $validation = Validator::make($inputs, [
                    'code' => 'required',
                    'language_id' => 'required',
                ], [], [
                    'code' => 'Code',
                    'language_id' => 'Language ID',
                ]);

                if ($validation->fails()) {
                    return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
                }
                $code = $inputs['code'];
                $language_id = $inputs['language_id'];

                $isExist = UserDetail::where('recommended_code',$code)->first();

                if(empty($isExist)){
                    // not exist
                    $key = "language_$language_id";
                    $message = __("messages.$key.recommended_code_not_exist");
                    return $this->sendSuccessResponse($message, 422);
                }else{
                    // exist

                    $key = "language_$language_id";
                    $message = __("messages.$key.recommended_code_exist");
                    return $this->sendSuccessResponse($message, 200);
                }



        } catch (\Exception $e) {
            Log::info($e);
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function checkUserBusiness()
    {
        $user = Auth::user();
        $data = [];
        try{
            $data['user_hospital_count'] = UserEntityRelation::where('entity_type_id', EntityTypes::HOSPITAL)->where('user_id', $user->id)->count();
            $data['user_shop_count'] = UserEntityRelation::where('entity_type_id', EntityTypes::SHOP)->where('user_id', $user->id)->count();

            return $this->sendSuccessResponse(Lang::get('messages.home.success'), 200, $data);
        }catch (\Exception $e) {
            Log::info($e);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function check_signup_time(Request $request){
        try{
            DB::beginTransaction();
            $user = Auth::user();
            $respData = [];

            $toDate = Carbon::parse(date('Y-m-d H:i:s', strtotime($user->created_at)));
            $fromDate = Carbon::parse(Carbon::now()->format('Y-m-d H:i:s'));
            $months = $toDate->diffInMonths($fromDate);
            if ($months < 1){
                $respData = false;

                return $this->sendSuccessResponse("Success", 200,$respData);
            }

            $respData = true;
            DB::commit();
            return $this->sendSuccessResponse("Success", 200,$respData);
        }catch(\Throwable $e){
            DB::rollBack();
            Log::info($e);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function deleteMySelf(Request $request)
    {
        $inputs = $request->all();
//        dd(Auth::user()->toArray());
        try{
            DB::beginTransaction();
            $user = Auth::user();
            $userID = $user->id;

            if(!empty($userID)){
                $password = $inputs['password'] ?? '';
                if(!Hash::check($password,Auth::user()->password)){
                    if($inputs['language_id']==PostLanguage::KOREAN){
                        $wrong_pass = "비밀번호가 잘못되었습니다.";
                    }
                    else if($inputs['language_id']==PostLanguage::JAPANESE){
                        $wrong_pass = "パスワードが間違っています。";
                    }
                    else {
                        $wrong_pass = "Password is wrong.";
                    }
                    return $this->sendFailedResponse($wrong_pass, 400);
                }

                if(!isset($inputs['reason'])){
                    if($inputs['language_id']==PostLanguage::KOREAN){
                        $empty_reason = "계정 삭제 사유를 입력하세요.";
                    }
                    else if($inputs['language_id']==PostLanguage::JAPANESE){
                        $empty_reason = "アカウントの削除理由を入力してください。";
                    }
                    else {
                        $empty_reason = "Please enter reason for remove account.";
                    }
                    return $this->sendFailedResponse($empty_reason, 400);
                }

                if(strlen(trim($inputs['reason'])) < 10){
                    if($inputs['language_id']==PostLanguage::KOREAN){
                        $invalid_reason = "이유작성은 10글자 이상 입력해주세요.";
                    }
                    else if($inputs['language_id']==PostLanguage::JAPANESE){
                        $invalid_reason = "理由作成は10文字以上入力してください。";
                    }
                    else {
                        $invalid_reason = "Please enter atleast 10 words in reason.";
                    }
                    return $this->sendFailedResponse($invalid_reason, 400);
                }

                $toDate = Carbon::parse(date('Y-m-d H:i:s', strtotime($user->created_at)));
                $fromDate = Carbon::parse(Carbon::now()->format('Y-m-d H:i:s'));
                $months = $toDate->diffInMonths($fromDate);
                if ($months < 1){
                    $respData = [];
                    $toDate = Carbon::parse(date('Y-m-d H:i:s', strtotime('+1 month', strtotime($user->created_at))));
                    $diff_in_days = $toDate->diffInDays($fromDate);
                    $diff_in_hours = $toDate->diffInHours($fromDate);
                    if($inputs['language_id']==PostLanguage::KOREAN){
                        $days = "일이";
                        $day = "일";
                        $hours = "시간";
                        $hour = "시간";
                    }
                    else if($inputs['language_id']==PostLanguage::JAPANESE){
                        $days = "日";
                        $day = "日";
                        $hours = "時間";
                        $hour = "時間";
                    }
                    else {
                        $days = "days";
                        $day = "day";
                        $hours = "hours";
                        $hour = "hour";
                    }

                    if ($diff_in_hours >= 24){
                        $remain_cnt = ($diff_in_days>1)?($diff_in_days." $days"):($diff_in_days." $day");
                        $respData['count'] = $diff_in_days;
                        $respData['type'] = 'day';
                    }
                    else {
                        $remain_cnt = ($diff_in_hours>1)?($diff_in_hours." $hours"):($diff_in_hours." $hour");
                        $respData['count'] = $diff_in_hours;
                        $respData['type'] = 'hour';
                    }

                    if($inputs['language_id']==PostLanguage::KOREAN){
                        $remain_delete = "계정 삭제는 계정 생성 후 $remain_cnt 지나면 가능합니다.";
                    }
                    else if($inputs['language_id']==PostLanguage::JAPANESE){
                        $remain_delete = "アカウントの削除は、アカウント作成後".$remain_cnt."後に可能です。";
                    }
                    else {
                        $remain_delete = "Account deletion is possible $remain_cnt after account creation.";
                    }
                    return $this->sendFailedResponse($remain_delete, 500,$respData);
                }

                DeleteAccountReason::create([
                    'user_id' => $userID,
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

                /*ActivityLog::where('user_id',$userID)->delete();
                CommunityComments::where('user_id',$userID)->delete();
                CommunityCommentLikes::where('user_id',$userID)->delete();
                CommunityCommentReply::where('user_id',$userID)->delete();
                CommunityCommentReplyLikes::where('user_id',$userID)->delete();
                CommunityLikes::where('user_id',$userID)->delete();
                Community::where('user_id',$userID)->delete();

                CompletedCustomer::where('user_id',$userID)->delete();
                Message::where('from_user_id',$userID)->delete();
                Message::where('to_user_id',$userID)->delete();
                MessageNotificationStatus::where('user_id',$userID)->delete();
                Notice::where('user_id',$userID)->delete();
                Notice::where('to_user_id',$userID)->delete();
                ReloadCoinRequest::where('user_id',$userID)->delete();
                ReportClient::where('reported_user_id',$userID)->delete();
                ReportClient::where('user_id',$userID)->delete();
                RequestedCustomer::where('user_id',$userID)->delete();
                RequestForm::where('user_id',$userID)->delete();

                ReviewCommentReplyLikes::where('user_id',$userID)->delete();
                ReviewCommentReply::where('user_id',$userID)->delete();
                ReviewCommentLikes::where('user_id',$userID)->delete();
                ReviewComments::where('user_id',$userID)->delete();
                ReviewLikes::where('user_id',$userID)->delete();
                Reviews::where('user_id',$userID)->delete();

                SearchHistory::where('user_id',$userID)->delete();
                ShopFollowers::where('user_id',$userID)->delete();
                UserBlockHistory::where('user_id',$userID)->orWhere('block_user_id',$userID)->delete();
                DB::table('user_calls')->where('from_user_id',$userID)->orWhere('to_user_id',$userID)->delete();
                UserCredit::where('user_id',$userID)->delete();
                UserCreditHistory::where('user_id',$userID)->orWhere('booked_user_id',$userID)->delete();
                UserDevices::where('user_id',$userID)->delete();
                UserHidePopupImage::where('user_id',$userID)->delete();
                UserInstagramHistory::where('user_id',$userID)->delete();

                $businessProfiles = UserEntityRelation::where('user_id',$userID)->get();

                foreach($businessProfiles as $profile){
                    if($profile->entity_type_id == EntityTypes::SHOP){
                        Shop::where('id',$profile->entity_id)->delete();
                    }
                    if($profile->entity_type_id == EntityTypes::HOSPITAL){
                        Hospital::where('id',$profile->entity_id)->delete();
                        Post::where('hospital_id',$profile->entity_id)->delete();
                    }
                }

                UserEntityRelation::where('user_id',$userID)->delete();
                UserDetail::where('user_id',$userID)->delete();
                User::where('id',$userID)->delete();*/
            }

            if($inputs['language_id']==PostLanguage::KOREAN){
                $reason_success = "계정 삭제 요청이 성공적으로 완료되었습니다.";
            }
            else if($inputs['language_id']==PostLanguage::JAPANESE){
                $reason_success = "アカウント削除リクエストが正常に完了しました。";
            }
            else {
                $reason_success = "Your account deletion request has been successfully completed.";
            }
            DB::commit();
            return $this->sendSuccessResponse($reason_success, 200, []);
        }catch(\Throwable $e){
            DB::rollBack();
            Log::info($e);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function referralBusinessUserList(Request $request){
        $inputs = $request->all();
        try{
            DB::beginTransaction();

            $users = UserDetail::select(
                'users_detail.user_id',
                'users.status_id',
                'users_detail.name',
                'users_detail.mobile',
                'shops.main_name',
                'shops.shop_name',
                'users.is_support_user',
                'users_detail.supporter_type'
                )
                ->selectSub(function($q) {
                    $q->select(DB::raw('count(user_referrals.referral_user) as count'))->from('user_referrals')->whereRaw("`user_referrals`.`referred_by` = `users_detail`.`user_id`");
                }, 'referral_count')
                ->join('users', function($join){
                    $join->on('users.id','=','users_detail.user_id')
                        ->whereNull('users.deleted_at');
                })
                ->leftjoin('shops', function ($join) {
                    $join->on('users.id', '=', 'shops.user_id')
                        ->whereNull('shops.deleted_at')
                        ->where('shops.created_at','=',DB::raw("(select max(`created_at`) from shops where shops.user_id = users.id and shops.deleted_at IS NULL)"))
                        ->where('users.status_id',Status::ACTIVE);
                })
                ->groupBy('users_detail.user_id')
                ->where('users_detail.recommended_by',$inputs['user_id'])
                ->where(function($q) use ($inputs){
                    $q->where('users.is_support_user',1)
                        ->orWhereNotNull('shops.id');
                });
            if (isset($inputs['search'])){
                $users = $users->where(function($q) use ($inputs){
                    $q->where('users_detail.name', 'LIKE', "%{$inputs['search']}%")
                        ->orWhere('shops.main_name', 'LIKE', "%{$inputs['search']}%")
                        ->orWhere('shops.shop_name', 'LIKE', "%{$inputs['search']}%");
                });
            }
            $users = $users->orderBy('users.is_support_user', 'DESC')
                    ->orderBy('referral_count', 'DESC')
                    ->paginate(config('constant.pagination_count'), "*", "referral_list_page");
            $users->makeHidden(['language_name','level_name','user_points','user_applied_card']);
            $data['referral_list'] = $users;

            $count_supporter_referral = UserDetail::join('users', function($join){
                    $join->on('users.id','=','users_detail.user_id')
                        ->whereNull('users.deleted_at');
                })
                ->where('users_detail.recommended_by',$inputs['user_id'])
                ->where('users.is_support_user',1)
                ->get();
            $data['count_supporter_referral'] = count($count_supporter_referral);

            $count_business_referral = UserDetail::join('users', function($join){
                $join->on('users.id','=','users_detail.user_id')
                    ->whereNull('users.deleted_at');
                })
                ->join('shops', function ($join) {
                    $join->on('users_detail.user_id', '=', 'shops.user_id')
                        ->whereNull('shops.deleted_at')
                        ->where('shops.created_at','=',DB::raw("(select max(`created_at`) from shops where shops.user_id = users_detail.user_id and shops.deleted_at IS NULL)"));
                })
                ->groupBy('users_detail.user_id')
                ->where('users_detail.recommended_by',$inputs['user_id'])
                ->where('users.status_id',Status::ACTIVE)
                ->get();
            $data['count_business_referral'] = count($count_business_referral);

            $count_normal_referral = UserDetail::join('users', function($join){
                $join->on('users.id','=','users_detail.user_id')
                    ->whereNull('users.deleted_at');
                })
                ->leftjoin('shops', function ($join) {
                    $join->on('users_detail.user_id', '=', 'shops.user_id')
                        ->whereNull('shops.deleted_at')
                        ->where('shops.created_at','=',DB::raw("(select max(`created_at`) from shops where shops.user_id = users_detail.user_id and shops.deleted_at IS NULL)"));
                })
                ->whereNull('shops.id')
                ->groupBy('users_detail.user_id')
                ->where('users_detail.recommended_by',$inputs['user_id'])
                ->whereIn('users.status_id',[Status::PENDING,Status::INACTIVE])
                ->get();
            $data['count_normal_referral'] = count($count_normal_referral);

            DB::commit();
            return $this->sendSuccessResponse("Referral users get successfully.", 200, $data);
        } catch(\Throwable $e){
            DB::rollBack();
            Log::info($e);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function referralNormalUserList(Request $request){
        $inputs = $request->all();
        try{
            DB::beginTransaction();

            $users = UserDetail::select(
                'users_detail.user_id',
                'users.status_id',
                'users_detail.name',
                'users_detail.mobile',
                'shops.main_name',
                'shops.shop_name'
                )
                ->selectSub(function($q) {
                    $q->select(DB::raw('count(user_referrals.referral_user) as count'))->from('user_referrals')->whereRaw("`user_referrals`.`referred_by` = `users_detail`.`user_id`");
                }, 'referral_count')
                ->join('users', function($join){
                    $join->on('users.id','=','users_detail.user_id')
                        ->whereNull('users.deleted_at');
                })
                ->leftjoin('shops', function ($join) {
                    $join->on('users_detail.user_id', '=', 'shops.user_id')
                        ->whereNull('shops.deleted_at')
                        ->where('shops.created_at','=',DB::raw("(select max(`created_at`) from shops where shops.user_id = users_detail.user_id and shops.deleted_at IS NULL)"));
                })
                ->whereNull('shops.id')
                ->groupBy('users_detail.user_id')
                ->where('users_detail.recommended_by',$inputs['user_id'])
                ->whereIn('users.status_id',[Status::PENDING,Status::INACTIVE]);
            if (isset($inputs['search'])){
                $users = $users->where(function($q) use ($inputs){
                    $q->where('users_detail.name', 'LIKE', "%{$inputs['search']}%")
                        ->orWhere('shops.main_name', 'LIKE', "%{$inputs['search']}%")
                        ->orWhere('shops.shop_name', 'LIKE', "%{$inputs['search']}%");
                });
            }
            $users = $users->orderBy('referral_count', 'DESC')
                    ->paginate(config('constant.pagination_count'), "*", "referral_list_page");
            $users->makeHidden(['language_name','level_name','user_points','user_applied_card']);
            $data['referral_list'] = $users;

            $count_supporter_referral = UserDetail::join('users', function($join){
                $join->on('users.id','=','users_detail.user_id')
                    ->whereNull('users.deleted_at');
            })
                ->where('users_detail.recommended_by',$inputs['user_id'])
                ->where('users.is_support_user',1)
                ->get();
            $data['count_supporter_referral'] = count($count_supporter_referral);

            $count_business_referral = UserDetail::join('users', function($join){
                $join->on('users.id','=','users_detail.user_id')
                    ->whereNull('users.deleted_at');
                })
                ->join('shops', function ($join) {
                    $join->on('users_detail.user_id', '=', 'shops.user_id')
                        ->whereNull('shops.deleted_at')
                        ->where('shops.created_at','=',DB::raw("(select max(`created_at`) from shops where shops.user_id = users_detail.user_id and shops.deleted_at IS NULL)"));
                })
                ->groupBy('users_detail.user_id')
                ->where('users_detail.recommended_by',$inputs['user_id'])
                ->where('users.status_id',Status::ACTIVE)
                ->get();
            $data['count_business_referral'] = count($count_business_referral);

            $count_normal_referral = UserDetail::join('users', function($join){
                $join->on('users.id','=','users_detail.user_id')
                    ->whereNull('users.deleted_at');
                })
                ->leftjoin('shops', function ($join) {
                    $join->on('users_detail.user_id', '=', 'shops.user_id')
                        ->whereNull('shops.deleted_at')
                        ->where('shops.created_at','=',DB::raw("(select max(`created_at`) from shops where shops.user_id = users_detail.user_id and shops.deleted_at IS NULL)"));
                })
                ->whereNull('shops.id')
                ->groupBy('users_detail.user_id')
                ->where('users_detail.recommended_by',$inputs['user_id'])
                ->whereIn('users.status_id',[Status::PENDING,Status::INACTIVE])
                ->get();
            $data['count_normal_referral'] = count($count_normal_referral);

            DB::commit();
            return $this->sendSuccessResponse("Referral users get successfully.", 200, $data);
        } catch(\Throwable $e){
            DB::rollBack();
            Log::info($e);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function getUserInfo(Request $request){
        $user_id = $request->user_id ?? "";
        try {
            DB::beginTransaction();

            $shop_profiles = Shop::where('user_id',$user_id)->get(['id','category_id','status_id']);
            $shop_profiles = $shop_profiles->makeHidden(['is_block','is_follow','rating','address','thumbnail_image','reviews_list','main_profile_images','portfolio_images']);

            $user_info = DB::table('users')
                ->leftJoin('users_detail', 'users_detail.user_id', 'users.id')
                ->whereNull('users.deleted_at')
                ->where('users.id', $user_id)
                ->select(
                    'users.id',
                    'users_detail.name',
                    'users_detail.mobile as phone_number',
                    'users.email',
                    'users.created_at as signup_date',
                    'users.last_login as last_access'
                )
                ->first();
            if (!empty($user_info)) {
                $user_info->signup_date = Carbon::parse($user_info->signup_date)->format('Y/m/d');
                $user_info->last_access = Carbon::parse($user_info->last_access)->format('Y/m/d');
            }

            $data['shop_profiles'] = $shop_profiles;
            $data['user_info'] = $user_info;

            return $this->sendSuccessResponse(Lang::get('messages.user.success'), 200,$data);

        } catch (\Exception $e) {
            Log::info($e);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function UserDetail(Request $request){
        $user_id = $request->user_id ?? "";
        try {
            DB::beginTransaction();

            $user_info = UserDetail::Join('users', 'users.id', 'users_detail.user_id')
                ->whereNull('users.deleted_at')
                ->where('users.id', $user_id)
                ->select(
                    'users.id',
//                    'users.is_show_shops',
                    'users.is_show_gender',
                    'users.is_show_mbti',
                    'users_detail.name',
                    'users_detail.gender',
                    'users_detail.mbti',
                    'users_detail.avatar',
                    'users_detail.is_character_as_profile'
                )
                ->first();
            $user_info_data = [];
            if (!empty($user_info)) {
                $user_info = $user_info->makeHidden(['level_name', 'language_name', 'user_points', 'user_applied_card']);
                $user_info_data['id'] = $user_info->id;
//                $user_info_data['is_show_shops'] = $user_info->is_show_shops;
                $user_info_data['is_show_gender'] = $user_info->is_show_gender;
                $user_info_data['is_show_mbti'] = $user_info->is_show_mbti;
                $user_info_data['name'] = $user_info->name;
                $user_info_data['gender'] = $user_info->gender;
                $user_info_data['mbti'] = $user_info->mbti;
                $user_info_data['avatar'] = $user_info->avatar;
                $user_info_data['is_character_as_profile'] = $user_info->is_character_as_profile;
                $user_info_data['user_applied_card'] = getUserAppliedCard($user_id);
                $user_info_data['thumb_user_applied_card'] = getThumbnailUserAppliedCard($user_id);

                $userPoints = UserDetail::select('id', 'user_id')->where('user_id', $user_id)->first();
                $activeLevelId = $userPoints->user_applied_card ? $userPoints->user_applied_card->active_level : 1;
                $cardLevelDetail = CardLevel::where('id', $activeLevelId)->first();
                $love_count = $userPoints->user_applied_card ? $userPoints->user_applied_card->love_count : 0;
                $per = ($cardLevelDetail->end - $cardLevelDetail->start);
                $percentage = ((($love_count - $cardLevelDetail->start) / $per) * 100);
                $user_info_data['love_details'] = [
                    'start' => $cardLevelDetail->start,
                    'end' => $cardLevelDetail->end,
                    'percentage' => ($percentage > 100) ? 100 : round($percentage, 2),
                    'love_count' => $love_count
                ];
            }
            $data['user_info'] = $user_info_data;

//            if ($user_info->is_show_shops == 1) {
                $shop_profiles = Shop::where('user_id', $user_id)->get(['id', 'main_name', 'shop_name','is_show']);
                if (count($shop_profiles) > 0) {
                    $shop_profiles = $shop_profiles->makeHidden(['is_block', 'is_follow', 'rating', 'address', 'reviews_list', 'main_profile_images', 'portfolio_images', 'category_name', 'category_icon', 'status_name', 'work_complete', 'portfolio', 'reviews', 'followers', 'deeplink']);
                }

                $data['shop_profiles'] = $shop_profiles;
//            }

            return $this->sendSuccessResponse(Lang::get('messages.user.success'), 200,$data);

        } catch (\Exception $e) {
            Log::info($e);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function updateShowhide(Request $request){
        $user = Auth::user();
        $inputs = $request->all();
//        dd(json_decode($request->shop_profiles,true));
        try {
            if($user){
                DB::beginTransaction();

                $data = [
                    'is_show_gender' => $inputs['is_show_gender'],
                    'is_show_mbti' => $inputs['is_show_mbti'],
                ];
                User::where('id', $user->id)->update($data);

                $hideshow_shop_profiles = json_decode($inputs['shop_profiles'],true);
                foreach ($hideshow_shop_profiles as $shop_hideshow){
                    Shop::where('id', $shop_hideshow['shop_id'])->update(['is_show'=>$shop_hideshow['is_show']]);
                }

                $data = [
                    'gender' => $inputs['gender'],
                    'mbti' => isset($inputs['mbti']) ? trim($inputs['mbti']) : null,
                    'name' => isset($inputs['username']) ? $inputs['username'] : $user->name
                ];
                UserDetail::where('user_id', $user->id)->update($data);

                DB::commit();
                return $this->sendSuccessResponse(Lang::get('messages.user-profile.update-success'), 200);

            }else{
                return $this->sendSuccessResponse(Lang::get('messages.user.token_expired'), 401);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function reportUser(Request $request){
        $user = Auth::user();
        $inputs = $request->all();
        try {
            if($user){
                DB::beginTransaction();

                $validation = Validator::make($request->all(), [
                    'user_id' => 'required',
                    'reason' => 'required',
                    'images' => 'required|array|min:1',
                    'images.*.type' => 'required',
                    'images.*.file' => 'required',
                    'images.*.video_thumbnail' => 'required_if:images.*.type,==,2',
                ], [], [
                    'user_id' => 'User ID',
                    'reason' => 'Reason',
                    'images.*.type' => 'Type',
                    'images.*.file' => 'Attachment',
                    'images.*.video_thumbnail' => 'Video Thumbnail',
                ]);

                if ($validation->fails()) {
                    return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
                }

                $images = $inputs['images'] ?? [];

                $data = [
                    'reporter_user_id' => $user->id,
                    'reported_user_id' => $inputs['user_id'],
                    'reason' => $inputs['reason'],
                ];
                $report = ReportedUser::create($data);

                if(!empty($images)){
                    $profileFolder = config('constant.profile') . '/' . $inputs['user_id'] . "/report/" . $report->id;

                    if (!Storage::exists($profileFolder)) {
                        Storage::makeDirectory($profileFolder);
                    }
                    foreach($images as $imagesData){
                        $insertData = [];
                        $insertData['user_report_id'] = $report->id;
                        $insertData['type'] = $imagesData['type'] == ShopPost::IMAGE ? 'image' : 'video';

                        if (is_file($imagesData['file'])) {
                            $postImage = Storage::disk('s3')->putFile($profileFolder, $imagesData['file'], 'public');
                            $fileName = basename($postImage);
                            $image_url = $profileFolder . '/' . $fileName;
                            $insertData['attachment_item'] =  $image_url;
                        }

                        if ($imagesData['type'] != ShopPost::IMAGE && !empty($imagesData['video_thumbnail']) && is_file($imagesData['video_thumbnail'])) {
                            $postThumbImage = Storage::disk('s3')->putFile($profileFolder, $imagesData['video_thumbnail'], 'public');
                            $fileThumbName = basename($postThumbImage);
                            $image_thumb_url = $profileFolder . '/' . $fileThumbName;
                            $insertData['video_thumbnail'] =  $image_thumb_url;
                        }

                        ReportedUserAttachment::create($insertData);
                    }
                }

                DB::commit();
                return $this->sendSuccessResponse(Lang::get('messages.user-profile.report-user'), 200);
            }else{
                return $this->sendSuccessResponse(Lang::get('messages.user.token_expired'), 401);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function reportMessage(Request $request){
        $user = Auth::user();
        $inputs = $request->all();
        try {
            if($user){
                DB::beginTransaction();

                $data = [
                    'reporter_user_id' => $user->id,
                    'message_id' => $inputs['message_id'],
                ];
                ReportGroupMessage::create($data);

                DB::commit();
                return $this->sendSuccessResponse(Lang::get('messages.user-profile.report-message'), 200);

            }else{
                return $this->sendSuccessResponse(Lang::get('messages.user.token_expired'), 401);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function saveLocation(Request $request){
        $inputs = $request->all();
        try {
                DB::beginTransaction();

                $validation = Validator::make($inputs, [
                    'latitude' => 'required',
                    'longitude' => 'required',
                    'city' => 'required',
                    'country_code' => 'required',
                ]);

                if ($validation->fails()) {
                    return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
                }

                $user_type = ($inputs['is_login']=="true") ? UserHiddenCategory::LOGIN : UserHiddenCategory::NONLOGIN;

                $data = [
                    'user_id' => $inputs['user_id'],
                    'latitude' => $inputs['latitude'],
                    'longitude' => $inputs['longitude'],
                    'city' => $inputs['city'],
                    'country_code' => $inputs['country_code'],
                    'user_type' => $user_type,
                ];
                UserLocationHistory::create($data);

                DB::commit();
                return $this->sendSuccessResponse("Location added successfully.", 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function lastLocation(Request $request){
        $inputs = $request->all();
        try {
            DB::beginTransaction();

            $user_type = ($inputs['is_login']=="true") ? UserHiddenCategory::LOGIN : UserHiddenCategory::NONLOGIN;
            $last_loc = UserLocationHistory::where('user_id',$inputs['user_id'])->where('user_type',$user_type)->orderBy('created_at','DESC')->first();

            DB::commit();
            return $this->sendSuccessResponse("Location get successfully.", 200, $last_loc);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

}
