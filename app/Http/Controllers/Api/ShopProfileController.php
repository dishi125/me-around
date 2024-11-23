<?php

namespace App\Http\Controllers\Api;

use App\Models\DeletedShopPost;
use App\Models\GifticonDetail;
use App\Models\InstaImportantSetting;
use App\Models\ShopDetail;
use App\Models\User;
use Validator;
use Carbon\Carbon;
use App\Models\Shop;
use App\Models\Banner;
use App\Models\Config;
use App\Models\Notice;
use App\Models\Status;
use App\Util\Firebase;
use App\Models\Address;
use App\Models\HashTag;
use App\Models\Category;
use App\Models\ShopPost;
use App\Models\CardLevel;
use App\Models\UserCards;
use App\Models\PostClicks;
use App\Models\PostImages;
use App\Models\ShopImages;
use App\Models\ShopPrices;
use App\Models\UserCredit;
use App\Models\UserDetail;
use App\Models\UserPoints;
use App\Models\CreditPlans;
use App\Models\EntityTypes;
use App\Models\RequestForm;
use App\Models\UserDevices;
use Illuminate\Support\Str;
use App\Models\PostLanguage;
use App\Models\UserReferral;
use Illuminate\Http\Request;
use App\Models\CategoryTypes;
use App\Models\MetalkOptions;
use App\Models\ShopFollowers;
use App\Models\ShopImagesTypes;
use App\Models\MultipleShopPost;
use App\Jobs\SendNotificationJob;
use App\Models\UserCreditHistory;
use App\Models\CustomerAttachment;
use App\Models\ShopDetailLanguage;
use App\Models\UserEntityRelation;
use App\Models\UserReferralDetail;
use Illuminate\Support\Facades\DB;
use App\Models\LinkedSocialProfile;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Models\UserInstagramHistory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Mail;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Storage;
use App\Validators\ShopProfileValidator;

class ShopProfileController extends Controller
{
    private $shopProfileValidator;
    protected $firebase;

    function __construct()
    {
        $this->shopProfileValidator = new ShopProfileValidator();
        $this->firebase = new Firebase();
    }

    public function getShopBusinessProfile(Request $request)
    {
        $user = Auth::user();
        try {
            Log::info('Start code for the get shop list');
            $inputs = $request->all();
            $validation = $this->shopProfileValidator->validateGetShop($inputs);

            if ($validation->fails()) {
                Log::info('End code for get all shops');
                return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
            }


            $main_country = getCountryFromLatLong($inputs['latitude'], $inputs['longitude']);
            $language_id = $inputs['language_id'] ?? 4;
            if ($user) {
                $shopCategoryIds = Shop::join('user_entity_relation', 'shops.id', '=', 'user_entity_relation.entity_id')
                    ->where('user_entity_relation.entity_type_id', EntityTypes::SHOP)
                    ->where('user_entity_relation.user_id', $user->id)
                    ->pluck('shops.category_id')->toArray();

                $customShops = Category::whereIn('id', $shopCategoryIds)->where('category_type_id', CategoryTypes::CUSTOM)->count();
                $query = Shop::join('user_entity_relation', 'shops.id', '=', 'user_entity_relation.entity_id')
                    ->where('user_entity_relation.entity_type_id', EntityTypes::SHOP)
                    ->where('user_entity_relation.user_id', $user->id);



                $shops = $query->select('shops.*')->get();
                $shops->makeHidden(['address', 'rating', 'work_complete', 'portfolio', 'thumbnail_image', 'reviews_list', 'main_profile_images', 'workplace_images', 'portfolio_images', 'best_portfolio', 'business_licence', 'identification_card']);
                $shopData = [];

                if (!empty($shops)) {
                    foreach ($shops as $shop) {
                        $temp = [];
                        $temp['shop_name'] = $shop->shop_name;
                        $temp['main_name'] = $shop->main_name;
                        $temp['category_id'] = $shop->category_id;
                        $temp['category_name'] = $shop->category_name;
                        $temp['category_icon'] = $shop->category_icon;
                        $temp['status_id'] = $shop->status_id;
                        $temp['status_name'] = $shop->status_name;
                        $temp['reviews'] = $shop->reviews;
                        $temp['followers'] = $shop->followers;
                        $temp['work_complete'] = $shop->work_complete;
                        $temp['portfolio'] = $shop->portfolio;
                        $temp['id'] = $shop->id;
                        array_push($shopData, $temp);
                    }
                }
                $config = Config::where('key', Config::SHOP_RECOMMEND_MONEY)->first();
                $shop_recommended_coins = $config ? (int) filter_var($config->value, FILTER_SANITIZE_NUMBER_INT) : 0;

                $config1 = Config::where('key', Config::SNS_REWARD)->first();
                $instagram_reward = $config1 ? (int) filter_var($config1->value, FILTER_SANITIZE_NUMBER_INT) : 0;

                $config2 = Config::where('key', Config::SHOP_PROFILE_ADD_PRICE)->first();
                $shop_profile_add_price = $config2 ? (int) filter_var($config2->value, FILTER_SANITIZE_NUMBER_INT) : 0;

                $configShowCoin = Config::where('key', Config::SHOW_COIN_INFO)->first();
                $show_coin_info = $configShowCoin ? (int) filter_var($configShowCoin->value, FILTER_SANITIZE_NUMBER_INT) : 0;

                $shopCategoryIds = '';
                $allShops = $shops->toArray();
                if (!empty($allShops)) {
                    $shopCategoryIds = $allShops[0]['category_id'];
                }

                //$shopCategoryIds = Shop::where('user_id',$user->id)->pluck('category_id');
                $bannerImages = Banner::join('banner_images', 'banners.id', '=', 'banner_images.banner_id')
                    ->where('banners.entity_type_id', EntityTypes::SHOP)
                    ->where('banners.section', 'profile')
                    ->whereNull('banners.deleted_at')
                    ->whereNull('banner_images.deleted_at')
                    ->where('banners.category_id', $shopCategoryIds)
                    ->where('banners.country_code', $main_country)
                    ->orderBy('banner_images.order', 'asc')->orderBy('banner_images.id', 'desc')
                    ->get('banner_images.*');

                $sliders = [];
                foreach ($bannerImages as $banner) {
                    $temp = [];
                    $temp['image'] = Storage::disk('s3')->url($banner->image);
                    $temp['link'] = $banner->link;
                    $temp['slide_duration'] = $banner->slide_duration;
                    $temp['order'] = $banner->order;
                    $sliders[] = $temp;
                }

                $data = [];
                Log::info('End code for the get shop list');
                if (!empty($shops)) {
                    $snsData = DB::table('user_intagram_history')->where('user_id', $user->id)->first();
                    $canRequestActive = true;
                    $buttonKey = "language_$language_id.request_reward";
                    $daysDiff = '';
                    if (!empty($snsData)) {
                        $requestDate = $snsData->requested_at;
                        $configData = Config::where('key', Config::SPONSOR_POST_LIMIT)->first();
                        $subDays = (!empty($configData) && !empty($configData->value)) ? $configData->value : 0;

                        $checkDate = Carbon::parse($requestDate)->addDays($subDays);
                        if (Carbon::now()->lt($checkDate)) {
                            $daysDiff = $checkDate->diffInDays() + 1;
                            if ($daysDiff == 1) {
                                $daysDiff = $checkDate->diffInHours();
                                $buttonKey = "language_$language_id.request_reward_disable_hours";
                            } else {
                                $buttonKey = "language_$language_id.request_reward_disable_days";
                            }
                            $canRequestActive = false;
                        }
                    }

                    $gifticon_details = GifticonDetail::with('attachments')->where('user_id', $user->id)->orderBy('created_at','DESC')->get()->toArray();
                    foreach ($gifticon_details as &$gifticon_detail){
                        $gifticon_detail['created_at'] = $gifticon_detail ? timeAgo($gifticon_detail['created_at'], $language_id)  : "null";
                    }

                    $requestButtonLabel = __("messages.$buttonKey", ['ntime' => $daysDiff]);
                    $data['shops'] = $shopData;
                    $data['recommended_code'] = $user->recommended_code;
                    $data['package_plan_id'] = $user->package_plan_id;
                    $data['package_plan_name'] = $user->package_plan_name;
                    $data['total_credits'] = number_format((float)$user->user_credits);
                    $data['recommended_coins'] = number_format((float)$shop_recommended_coins);
                    $data['instagram_reward_coins'] = number_format((float)$instagram_reward);
                    $data['shop_profile_add_price'] = number_format((float)$shop_profile_add_price);
                    $data['is_suggested_category'] = $customShops > 0 ? 1 : 0;
                    $data['sns_type'] = $user->sns_type;
                    $data['sns_link'] = $user->sns_link;
                    $data['can_request'] = $canRequestActive;
                    $data['request_button_label'] = $requestButtonLabel;
                    $data['show_coin_info'] = $show_coin_info;
                    $data['sliders'] = $sliders;
                    $data['coffee_access_data'] = UserReferral::where('referred_by', $user->id)->where('has_coffee_access', 0)->get();
                    $data['total_coffee_count'] = UserReferralDetail::where('user_id', $user->id)->where('is_sent', 0)->count();
                    $data['gifticon_details'] = $gifticon_details;

                    return $this->sendSuccessResponse(Lang::get('messages.shop.success'), 200, $data);
                } else {
                    return $this->sendSuccessResponse(Lang::get('messages.shop.empty'), 501);
                }
            } else {
                Log::info('End code for the get shop list');
                return $this->sendSuccessResponse(Lang::get('messages.user.token_expired'), 401);
            }
        } catch (\Exception $e) {
            Log::info('Exception in the get shop list');
            Log::info($e);
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function getShopDetail(Request $request)
    {
        $shopId = $request->shop_id;
        // get device using user agent
        if (preg_match('/(iphone|ipad|ipod)/i', $_SERVER['HTTP_USER_AGENT'])) {
            $browser = 'ios';
        } elseif (preg_match('/(android)/i', $_SERVER['HTTP_USER_AGENT'])) {
            $browser = 'android';
        } elseif (preg_match('/(bitlybot)/i', $_SERVER['HTTP_USER_AGENT'])) {
            $browser = 'curl';
        } else {
            $browser = 'other';
        }
        $data['browser'] = $browser;
        $data['dest'] = "shop";
        $data['dest_id'] = $shopId;
        return view('admin.deep-link', compact('data'));
    }

    public function editShopProfile(Request $request, $id)
    {
        $user = Auth::user();
        $inputs = $request->all();

        try {
            Log::info('Start code for the get shop list');
            if ($user) {
                $language_id = $inputs['language_id'] ?? PostLanguage::ENGLISH;

                $shopExists = Shop::where('id', $id)->first();
                if ($shopExists) {
                    $shops = Shop::where('id', $id)->first();
                    $shops->speciality_of_languages = $shops->shopLanguageDetails()->where('key', ShopDetailLanguage::SPECIALITY_OF)->where('entity_type_id', EntityTypes::SHOP)->get();

                    $options = MetalkOptions::leftjoin('metalk_option_languages', function ($join) use ($language_id) {
                        $join->on('metalk_options.id', '=', 'metalk_option_languages.metalk_options_id')
                            ->where('metalk_option_languages.language_id', $language_id);
                    })
                        ->where('metalk_options.options_type', MetalkOptions::EXPLANATION)
                        ->select(
                            'metalk_options.id',
                            'metalk_options.key',
                            'metalk_options.type',
                            DB::raw('IFNULL(metalk_option_languages.value, metalk_options.value) as value')
                        )
                        ->get();

                    $explanation_detail = [];
                    if ($options) {
                        $explanation_detail = collect($options)->mapWithKeys(function ($item) {
                            $fieldValue = ($item->type == MetalkOptions::FILE && !empty($item->value)) ? Storage::disk('s3')->url($item->value) : $item->value;
                            return [$item->key => $fieldValue];
                        })->toArray();
                    }
                    $data = [];

                    $shops->explanation_detail = $explanation_detail;

                    $userInstaProfile = LinkedSocialProfile::where('user_id',$user->id)->where('shop_id',$id)->where('social_type',LinkedSocialProfile::Instagram)->first();
                    $shops->is_instagram_connect = (!empty($userInstaProfile) && !empty($userInstaProfile->social_id));
                    $shops->insta_social_name = (!empty($userInstaProfile) && !empty($userInstaProfile->social_name)) ? $userInstaProfile->social_name : '';

                    Log::info('End code for the get shop list');
                    if (!empty($shops)) {
                        return $this->sendSuccessResponse(Lang::get('messages.shop.edit-success'), 200, $shops);
                    } else {
                        return $this->sendSuccessResponse(Lang::get('messages.shop.empty'), 501);
                    }
                } else {
                    return $this->sendSuccessResponse(Lang::get('messages.shop.empty'), 402);
                }
            } else {
                Log::info('End code for the get shop list');
                return $this->sendSuccessResponse(Lang::get('messages.user.token_expired'), 401);
            }
        } catch (\Exception $e) {
            Log::info('Exception in the get shop list');
            Log::info($e);
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }
    public function shopDetail(Request $request)
    {
       // dd(1);
        $user = Auth::user();
        try {
            Log::info('Start code for the get shop list');
            if ($user) {
                $inputs = $request->all();
                $id = $inputs['shop_id'];
                $shopExists = Shop::where('id', $id)->get()->first();
                if ($shopExists) {
                    $shops = Shop::where('id', $id)->first();


                    $data = [];
                    Log::info('End code for the get shop list');
                    if (!empty($shops)) {
                        return $this->sendSuccessResponse(Lang::get('messages.shop.edit-success'), 200, $shops);
                    } else {
                        return $this->sendSuccessResponse(Lang::get('messages.shop.empty'), 501);
                    }
                } else {
                    return $this->sendSuccessResponse(Lang::get('messages.shop.empty'), 402);
                }
            } else {
                Log::info('End code for the get shop list');
                return $this->sendSuccessResponse(Lang::get('messages.user.token_expired'), 401);
            }
        } catch (\Exception $e) {
            Log::info('Exception in the get shop list');
            Log::info($e);
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function updateOutsideShopBusinessProfile(Request $request, $id){
        $inputs = $request->all();
        try {

            $requestData = [
                'business_link' => isset($inputs['business_link']) && $inputs['business_link'] != "" ? $inputs['business_link'] : NULL,
                'another_mobile' => isset($inputs['another_mobile']) && $inputs['another_mobile'] != "" ? $inputs['another_mobile'] : NULL,
                'booking_link' => isset($inputs['booking_link']) && $inputs['booking_link'] != "" ? $inputs['booking_link'] : NULL,
                'show_price' => $inputs['show_price'] ?? 0,
                'show_address' => $inputs['show_address'] ?? 0
            ];

            if (isset($inputs['another_mobile']) && !empty($inputs['another_mobile'])) {
                $is_exist_another_mobile = Shop::where('id', '!=', $id)->where('another_mobile', $inputs['another_mobile'])->count();
                if ($is_exist_another_mobile > 0) {
                    return $this->sendFailedResponse(Lang::get('messages.shop.phone-number-exist'), 400);
                }
            }

            $updateShop = Shop::where('id', $id)->update($requestData);
            $returnShop = Shop::where('id', $id)->get();
            return $this->sendSuccessResponse(Lang::get('messages.shop.update-success'), 200, $returnShop);
        } catch (\Exception $e) {
            Log::info($e);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function updateShopBusinessProfile(Request $request, $id)
    {
        $user = Auth::user();
        $inputs = $request->all();
        try {
            Log::info('Start code for the update shop list');
            if ($user) {
                DB::beginTransaction();
                $shopExists = Shop::where('id', $id)->first();
                if ($shopExists) {
                    $validation = $this->shopProfileValidator->validateUpdate($inputs);
                    if ($validation->fails()) {
                        Log::info('End code for add form request');
                        return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
                    }
                    $requestData = [
                        'main_name' => $inputs['main_name'],
                        'shop_name' => $inputs['shop_name'],
                        'speciality_of' => $inputs['speciality_of'],
                        'is_discount' => $inputs['is_discount'],
                        'business_license_number' => $inputs['business_license_number'],
                        'mobile' => isset($inputs['mobile']) && $inputs['mobile'] != "" ? $inputs['mobile'] : NULL,
                    ];

                    if (!empty($inputs['country_id']) && !empty($inputs['state_id']) && !empty($inputs['city_id'])) {
                        $main_country = getCountryFromLatLong($inputs['latitude'], $inputs['longitude']);
                        $location = $this->addCurrentLocation($inputs['country_id'], $inputs['state_id'], $inputs['city_id']);
                        $country_code = getCountryFromLatLong($inputs['latitude'], $inputs['longitude']);
                        if ($location) {
                            $address = Address::updateOrCreate(['entity_type_id' => EntityTypes::SHOP, 'entity_id' => $id], [
                                'address' => $inputs['address'],
                                'address2' => $inputs['address_detail'],
                                'country_id' => $location['country']->id,
                                'city_id' => $location['city']->id,
                                'state_id' => $location['city']->state_id,
                                'latitude' => $inputs['latitude'],
                                'longitude' => $inputs['longitude'],
                                'entity_type_id' => EntityTypes::SHOP,
                                'main_country' => $country_code,
                                'entity_id' => $id,
                                'main_country' => $main_country,
                            ]);
                        }
                    }

                    $shopsFolder = config('constant.shops') . '/' . $id;


                    if (!Storage::exists($shopsFolder)) {
                        Storage::makeDirectory($shopsFolder);
                    }

                    if (!empty($inputs['thumbnail_image'])) {
                        if ($request->hasFile('thumbnail_image')) {
                            $thumb = DB::table('shop_images')->whereNull('deleted_at')->where('shop_image_type', ShopImagesTypes::THUMB)->where('shop_id', $id)->first();
                            if (!empty($thumb)) {
                                Storage::disk('s3')->delete($thumb->image);
                                ShopImages::where('id', $thumb->id)->delete();
                            }
                            $thumbnail_image = Storage::disk('s3')->putFile($shopsFolder, $request->file('thumbnail_image'), 'public');
                            $fileName = basename($thumbnail_image);
                            $finalImage = $shopsFolder . '/' . $fileName;
                            ShopImages::create(['shop_id' => $id, 'shop_image_type' => ShopImagesTypes::THUMB, 'image' => $finalImage]);

                            $newThumb = Image::make($request->file('thumbnail_image'))->resize(200, 200, function ($constraint) {
                                $constraint->aspectRatio();
                            })->encode(null,90);
                            Storage::disk('s3')->put($shopsFolder.'/thumb/'.$fileName,  $newThumb->stream(), 'public');
                        }
                    }
                    $shopMainProfileImages = [];
                    if (!empty($inputs['main_profile_image'])) {
                        foreach ($inputs['main_profile_image'] as $mainProfileImage) {
                            $mainProfile = Storage::disk('s3')->putFile($shopsFolder, $mainProfileImage, 'public');
                            $fileName = basename($mainProfile);
                            $image_url = $shopsFolder . '/' . $fileName;
                            $temp = [
                                'shop_id' => $id,
                                'shop_image_type' => ShopImagesTypes::MAINPROFILE,
                                'image' => $image_url
                            ];
                            array_push($shopMainProfileImages, $temp);

                            $newThumb = Image::make($mainProfileImage)->resize(200, 200, function ($constraint) {
                                $constraint->aspectRatio();
                            })->encode(null,90);
                            Storage::disk('s3')->put($shopsFolder.'/thumb/'.$fileName,  $newThumb->stream(), 'public');
                        }
                    }

                    $workplaceImages = [];
                    if (!empty($inputs['workplace_image'])) {
                        foreach ($inputs['workplace_image'] as $workpalce) {
                            $workplaceImage = Storage::disk('s3')->putFile($shopsFolder, $workpalce, 'public');
                            $fileName = basename($workplaceImage);
                            $image_url = $shopsFolder . '/' . $fileName;
                            $temp = [
                                'shop_id' => $id,
                                'shop_image_type' => ShopImagesTypes::WORKPLACE,
                                'image' => $image_url
                            ];
                            array_push($workplaceImages, $temp);

                            $newThumb = Image::make($workpalce)->resize(200, 200, function ($constraint) {
                                $constraint->aspectRatio();
                            })->encode(null,90);
                            Storage::disk('s3')->put($shopsFolder.'/thumb/'.$fileName,  $newThumb->stream(), 'public');
                        }
                    }

                    $updateShop = Shop::where('id', $id)->update($requestData);

                    if (!empty($inputs['deleted_image'])) {
                        foreach ($inputs['deleted_image'] as $deleteImage) {
                            $image = DB::table('shop_images')->whereId($deleteImage)->whereNull('deleted_at')->first();
                            if ($image) {
                                Storage::disk('s3')->delete($image->image);
                                ShopImages::where('id', $image->id)->delete();
                            }
                        }
                    }
                    if (count($shopMainProfileImages) > 0) {
                        // $deleteOld = ShopImages::where('shop_image_type',ShopImagesTypes::MAINPROFILE)->where('shop_id',$id)->get();
                        // foreach($deleteOld as $file){
                        //     $image_url = Storage::delete($file->image_url);
                        // }
                        // ShopImages::where('shop_image_type',ShopImagesTypes::MAINPROFILE)->where('shop_id',$id)->delete();
                        foreach ($shopMainProfileImages as $val) {
                            $addNew = ShopImages::create($val);
                        }
                    }

                    if (count($workplaceImages) > 0) {
                        //    $deleteOld = ShopImages::where('shop_image_type',ShopImagesTypes::WORKPLACE)->where('shop_id',$id)->get();
                        //     foreach($deleteOld as $file){
                        //         $image_url = Storage::delete($file->image_url);
                        //     }
                        // ShopImages::where('shop_image_type',ShopImagesTypes::WORKPLACE)->where('shop_id',$id)->delete();
                        foreach ($workplaceImages as $val) {
                            $addNew = ShopImages::create($val);
                        }
                    }
                    $currentShop = $this->checkShopStatus($id);
                    $returnShop = Shop::where('id', $id)->get();


                    DB::commit();
                    Log::info('End code for the get shop list');
                    return $this->sendSuccessResponse(Lang::get('messages.shop.update-success'), 200, $returnShop);
                } else {
                    Log::info('End code for the get shop list');
                    return $this->sendSuccessResponse(Lang::get('messages.shop.empty'), 402);
                }
            } else {
                Log::info('End code for update shop list');
                return $this->sendSuccessResponse(Lang::get('messages.user.token_expired'), 401);
            }
        } catch (\Exception $e) {
            Log::info('Exception in update shop list');
            Log::info($e);
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function deleteShopImage($id)
    {
        $user = Auth::user();
        try {
            Log::info('Start code for the delete shop image');
            if ($user) {
                $shopImage = DB::table('shop_images')->whereId($id)->whereNull('deleted_at')->first();
                $image_url = Storage::disk('s3')->delete($shopImage->image);
                ShopImages::where('id', $id)->delete();
                Log::info('End code for the delete shop image');
                return $this->sendSuccessResponse(Lang::get('messages.shop.delete-image-success'), 200, []);
            } else {
                Log::info('End code for delete shop image');
                return $this->sendSuccessResponse(Lang::get('messages.user.token_expired'), 401);
            }
        } catch (\Exception $e) {
            Log::info('Exception in update shop list');
            Log::info($e);
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function store(Request $request)
    {
        try {
            Log::info('Start code for add shop');
            $user = Auth::user();
            $configData = Config::where('key', Config::CREATE_SHOP_PROFILE_LIMIT)->first();
            $limitPost = !empty($configData) ? $configData->value : 5;
            if ($user) {
                DB::beginTransaction();
                $inputs = $request->all();
                $validation = $this->shopProfileValidator->validateStore($inputs);
                if ($validation->fails()) {
                    Log::info('End code for add form request');
                    return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
                }

                $totalUserShops = Shop::join('user_entity_relation', 'shops.id', '=', 'user_entity_relation.entity_id')
                    ->where('user_entity_relation.entity_type_id', EntityTypes::SHOP)
                    ->where('user_entity_relation.user_id', $user->id)->count();
                if ($totalUserShops >= $limitPost) {
                    return $this->sendFailedResponse(Lang::get('messages.shop.max-shops', ['count' => $limitPost]), 400);
                }
                $userCategoryShop = Shop::where('user_id', $user->id)->where('category_id', $inputs['category_id'])->count();
                if ($userCategoryShop > 0) {
                    return $this->sendFailedResponse(Lang::get('messages.shop.same-category-shop'), 400);
                }

                $user_detail = UserDetail::where('user_id', $user->id)->first();
                $creditPlan = CreditPlans::where('entity_type_id', EntityTypes::SHOP)->where('package_plan_id', $user_detail->package_plan_id)->first();
                $defaultCredit = $creditPlan ? $creditPlan->amount : 0;
                $minShopCredit = $defaultCredit * $totalUserShops;
                $userCredits = UserCredit::where('user_id', $user->id)->first();

                if ($userCredits->credits < $minShopCredit) {
                    return $this->sendFailedResponse(Lang::get('messages.shop.not-enough-coins'), 402);
                }



                $dt = Carbon::now();
                $userLangDetail = UserDetail::where('user_id',$user->id)->first();
                $shop = Shop::create([
                    'shop_name' => $inputs['shop_name'],
                    'category_id' => $inputs['category_id'],
                    'email' => $inputs['email'],
                    'status_id' => Status::PENDING,
                    'user_id' => $user->id,
                    'uuid' => (string) Str::uuid(),
                    'credit_deduct_date' => $dt->toDateString()
                ]);
                syncGlobalPriceSettings($shop->id,$userLangDetail->language_id ?? 4);
                UserEntityRelation::create([
                    'user_id' => $user->id,
                    'entity_type_id' => EntityTypes::SHOP,
                    'entity_id' => $shop->id
                ]);

                $config = Config::where('key', Config::SHOP_PROFILE_ADD_PRICE)->first();
                $defaultCredit = $config ? (int) filter_var($config->value, FILTER_SANITIZE_NUMBER_INT) : null;

                $old_credit = $userCredits->credits;
                $total_credit = $old_credit - $defaultCredit;
                if ($defaultCredit && $defaultCredit > 0) {
                    $userCredits = UserCredit::where('user_id', $user->id)->update(['credits' => $total_credit]);
                    UserCreditHistory::create([
                        'user_id' => $user->id,
                        'amount' => $defaultCredit,
                        'total_amount' => $total_credit,
                        'transaction' => 'debit',
                        'type' => UserCreditHistory::REGULAR
                    ]);

                    $devices = UserDevices::whereIn('user_id', [$user->id])->pluck('device_token')->toArray();
                    $user_detail = UserDetail::where('user_id', $user->id)->first();
                    $language_id = $user_detail->language_id;
                    $key = Notice::ADD_MULTI_PROFILE . '_' . $language_id;
                    $format = __("notice.$key", ['name' => $shop->category_name]);
                    $title_msg = '';
                    $notify_type = Notice::ADD_MULTI_PROFILE;

                    $notice = Notice::create([
                        'notify_type' => Notice::ADD_MULTI_PROFILE,
                        'user_id' => $user->id,
                        'to_user_id' => $user->id,
                        'entity_type_id' => EntityTypes::SHOP,
                        'entity_id' => $shop->id,
                        'title' => $shop->category_name,
                    ]);

                    $notificationData = [
                        'id' => $shop->id,
                        'main_name' => $shop->main_name,
                        'shop_name' => $shop->shop_name,
                        'category_id' => $shop->category_id,
                        'category_name' => $shop->category_name,
                        'category_icon' => $shop->category_icon,
                    ];

                    if (count($devices) > 0) {
                        $result = $this->sentPushNotification($devices, $title_msg, $format, $notificationData, $notify_type, $shop->id);
                    }
                }
                $this->updateUserChatStatus();
                DB::commit();
                Log::info('End code for add shop');
                if ($shop) {
                    return $this->sendSuccessResponse(Lang::get('messages.shop.add-success'), 200, $shop);
                } else {
                    return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
                }
            } else {
                Log::info('End code for add shop');
                return $this->sendSuccessResponse(Lang::get('messages.user.token_expired'), 401);
            }
        } catch (\Exception $ex) {
            Log::info('Exception in the add shop');
            Log::info($ex);
            DB::rollback();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function inActiveShop(Request $request, $id)
    {
        try {
            Log::info('Start code for get inactive shop');
            $user = Auth::user();
            if ($user) {
                DB::beginTransaction();
                $inputs = $request->all();
                $shop = Shop::where('id', $id)->first();
                if ($shop) {
                    $data = [];

                    $data['is_profile'] = $shop->main_name ? 1 : 0;
                    $data['is_portfolio'] = $shop->portfolio >= 3 ? 1 : 0;

                    return $this->sendSuccessResponse(Lang::get('messages.shop.inactive-success'), 200, $data);
                } else {
                    return $this->sendSuccessResponse(Lang::get('messages.shop.empty'), 402);
                }
            } else {
                Log::info('End code for add shop');
                return $this->sendSuccessResponse(Lang::get('messages.user.token_expired'), 401);
            }
        } catch (\Exception $ex) {
            Log::info('Exception in the add form request');
            Log::info($ex);
            DB::rollback();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function addShopPortfolio(Request $request)
    {
        try {
            Log::info('Start code for add shop portfolio');
            $user = Auth::user();
            if ($user) {
                DB::beginTransaction();
                $inputs = $request->all();
                $validation = $this->shopProfileValidator->validatePortfolio($inputs);
                if ($validation->fails()) {
                    Log::info('End code for add form request');
                    return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
                }
                $shop = Shop::where('id', $inputs['shop_id'])->first();
                if ($shop) {
                    $shopsFolder = config('constant.shops') . '/' . $shop->id;

                    if (!Storage::exists($shopsFolder)) {
                        Storage::makeDirectory($shopsFolder);
                    }

                    if (!empty($inputs['portfolio_images'])) {
                        foreach ($inputs['portfolio_images'] as $portfolio) {
                            $portfolioImage = Storage::disk('s3')->putFile($shopsFolder, $portfolio, 'public');
                            $fileName = basename($portfolioImage);
                            $image_url = $shopsFolder . '/' . $fileName;
                            $temp = [
                                'shop_id' => $shop->id,
                                'shop_image_type' => ShopImagesTypes::PORTFOLIO,
                                'image' => $image_url
                            ];
                            $addNew = ShopImages::create($temp);
                        }
                    }
                    DB::commit();
                    Log::info('End code for add shop portfolio');
                    return $this->sendSuccessResponse(Lang::get('messages.shop.portfolio-success'), 200, $shop);
                } else {
                    return $this->sendSuccessResponse(Lang::get('messages.shop.empty'), 402);
                }
            } else {
                Log::info('End code for add shop portfolio');
                return $this->sendSuccessResponse(Lang::get('messages.user.token_expired'), 401);
            }
        } catch (\Exception $ex) {
            Log::info('Exception in the add shop portfolio');
            Log::info($ex);
            DB::rollback();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function addShopPost(Request $request)
    {
        try {
            Log::info('Start code for add shop portfolio');
            $user = Auth::user();
            if ($user) {
                DB::beginTransaction();
                $inputs = $request->all();
                $validation = $this->shopProfileValidator->validatePost($inputs);
                if ($validation->fails()) {
                    Log::info('End code for add form request');
                    return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
                }

                $shop = Shop::where('id', $inputs['shop_id'])->first();
                if ($shop) {
                    $config = Config::where('key', CONFIG::TOTAL_SHOP_PORTFOLIO_POST)->first();
                    $total_allowed_post = $config ? (int) filter_var($config->value, FILTER_SANITIZE_NUMBER_INT) : 50;

                    $config1 = Config::where('key', Config::PORTFOLIO_LIMIT_PER_DAY)->first();
                    $limit_per_day = $config1 ? (int) filter_var($config1->value, FILTER_SANITIZE_NUMBER_INT) : 3;

                    $shop_total_post = ShopPost::where('shop_id', $shop->id)->count();

                    if ($shop_total_post >= $total_allowed_post) {
                        return $this->sendFailedResponse(Lang::get('messages.shop.portfolio-max-post', ['count' => $total_allowed_post]), 422);
                    }

                    $shop_limit_per_day_post = ShopPost::where('shop_id', $shop->id)->whereDate('created_at', '>=', Carbon::now())->count();

                    if ($shop_limit_per_day_post >= $limit_per_day) {
                        return $this->sendFailedResponse(Lang::get('messages.shop.portfolio-per-day-max-post', ['count' => $limit_per_day]), 422);
                    }

                    $shopsFolder = config('constant.shops') . "/posts/" . $shop->id;

                    if (!Storage::exists($shopsFolder)) {
                        Storage::makeDirectory($shopsFolder);
                    }
                    $data = [
                        'shop_id' => $shop->id,
                        'type' => $inputs['type'] == ShopPost::IMAGE ? 'image' : 'video',
                    ];
                    if (!empty($inputs['post_item'])) {
                        $postImage = Storage::disk('s3')->putFile($shopsFolder, $inputs['post_item'], 'public');
                        $fileName = basename($postImage);
                        $image_url = $shopsFolder . '/' . $fileName;
                        $data['post_item'] =  $image_url;
                    }
                    if (!empty($inputs['video_thumbnail'])) {
                        $postImage = Storage::disk('s3')->putFile($shopsFolder, $inputs['video_thumbnail'], 'public');
                        $fileName = basename($postImage);
                        $image_url = $shopsFolder . '/' . $fileName;
                        $data['video_thumbnail'] =  $image_url;
                    }
                    $data['post_order_date'] = Carbon::now();
                    $insta_type = User::join('shops', 'shops.user_id', 'users.id')
                        ->where('shops.id',$inputs['shop_id'])
                        ->pluck('users.insta_type')
                        ->first();
                    $remain_download_insta = null;
                    if ($insta_type=="pro"){
                        $remain_download_insta = null;
                    }
                    elseif ($insta_type=="free"){
                        $default_limit = InstaImportantSetting::where('field','Default download')->pluck('value')->first();
                        $remain_download_insta = ($default_limit) ? (int)$default_limit : 10;
                    }
                    $data['remain_download_insta'] = $remain_download_insta;
                    $addPost = ShopPost::create($data);
                    $currentShop = $this->checkShopStatus($shop->id);
                    $return = ShopPost::find($addPost->id);
                    DB::commit();
                    Log::info('End code for add shop portfolio');
                    return $this->sendSuccessResponse(Lang::get('messages.shop.portfolio-success'), 200, $return);
                } else {
                    return $this->sendSuccessResponse(Lang::get('messages.shop.empty'), 402);
                }
            } else {
                Log::info('End code for add shop portfolio');
                return $this->sendSuccessResponse(Lang::get('messages.user.token_expired'), 401);
            }
        } catch (\Exception $ex) {
            Log::info('Exception in the add shop portfolio');
            Log::info($ex);
            DB::rollback();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function shopPostDetail(Request $request, $id)
    {
        $user = Auth::user();
        try {
            Log::info('Start code for the get shop post detail');
            if ($user) {

                $shopPost = ShopPost::where('id', $id)->first();
                if ($shopPost) {
                    ShopPost::where('id', $id)->update(['views_count' => DB::raw('views_count + 1')]);
                    $shopPost = ShopPost::where('id', $id)->first();
                    Log::info('End code for the get shop post detail');
                    return $this->sendSuccessResponse(Lang::get('messages.shop.post-get-success'), 200, $shopPost);
                } else {
                    return $this->sendSuccessResponse(Lang::get('messages.shop.post-empty'), 402);
                }
            } else {
                Log::info('End code for the get shop post detail');
                return $this->sendSuccessResponse(Lang::get('messages.user.token_expired'), 401);
            }
        } catch (\Exception $e) {
            Log::info('Exception in the get shop post detail');
            Log::info($e);
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function shopPostDelete(Request $request, $id)
    {
        $user = Auth::user();
        try {
            Log::info('Start code for the delete shop post detail');
            if ($user) {
                $shopPost = ShopPost::where('id', $id)->first();
                if ($shopPost) {
                    $shop_id = $shopPost->shop_id;
                    if ($shopPost->post_item) {
                        $pos = strpos($shopPost->post_item, '/uploads');
                        $path = substr($shopPost->post_item, $pos);
                        Storage::delete($path);
                    }
                    $shopPost = ShopPost::where('id', $id)->delete();
                    $currentShop = $this->checkShopStatus($shop_id);
                    Log::info('End code for the delete shop post detail');
                    return $this->sendSuccessResponse(Lang::get('messages.shop.post-delete-success'), 200, []);
                } else {
                    return $this->sendSuccessResponse(Lang::get('messages.shop.post-empty'), 402);
                }
            } else {
                Log::info('End code for the delete shop post detail');
                return $this->sendSuccessResponse(Lang::get('messages.user.token_expired'), 401);
            }
        } catch (\Exception $e) {
            Log::info('Exception in the delete shop post detail');
            Log::info($e);
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function followShop(Request $request)
    {
        try {

            Log::info('Start code for follow / unfollow shop');
            $user = Auth::user();
            if ($user) {
                DB::beginTransaction();
                $inputs = $request->all();
                $validation = $this->shopProfileValidator->validateShopFollow($inputs);

                if ($validation->fails()) {
                    Log::info('End code for follow / unfollow shop');
                    return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
                }
                $shop = Shop::where('id', $inputs['shop_id'])->first();
                if ($shop) {

                    $data = [
                        'shop_id' => $shop->id,
                        'user_id' => $user->id,
                    ];

                    if ($inputs['follow'] == 1) {
                        $addFollow = ShopFollowers::firstOrCreate($data);
                        $user_detail = UserDetail::where('user_id', $shop->user_id)->first();
                        $language_id = $user_detail->language_id;


                        $notice = Notice::create([
                            'notify_type' => Notice::FOLLOW,
                            'user_id' => $user->id,
                            'to_user_id' => $shop->user_id,
                            'entity_type_id' => EntityTypes::SHOP,
                            'entity_id' => $shop->id,
                            'title' => $shop->shop_name . '(' . $shop->main_name . ')',
                        ]);


                        $can_follow = 'can_follow_' . $language_id;
                        $can_follow_format = __("notice.$can_follow");
                        Notice::create([
                            'notify_type' => Notice::FOLLOWED_BUSINESS,
                            'user_id' => $user->id,
                            'to_user_id' => $shop->user_id,
                            'entity_type_id' => EntityTypes::SHOP,
                            'entity_id' => $shop->id,
                            'title' => $can_follow_format,
                        ]);

                        $notificationData = [
                            'id' => $shop->id,
                            'main_name' => $shop->main_name,
                            'shop_name' => $shop->shop_name,
                            'category_id' => $shop->category_id,
                            'category_name' => $shop->category_name,
                            'category_icon' => $shop->category_icon,
                        ];

                        $notify_type = Notice::FOLLOW;
                        $key = Notice::FOLLOW . '_' . $language_id;
                        $format = __("notice.$key", ['name' => $user->name]);
                        $title_msg = '';

                        SendNotificationJob::dispatch($format, $title_msg, $notificationData, $shop->id, $notify_type, $user->id);


                        $businessKey = Notice::FOLLOWED_BUSINESS . '_' . $language_id;
                        $businessFormat = __("notice.$businessKey", ['name' => $user->name]);
                        $title_msg = '';

                        SendNotificationJob::dispatch($businessFormat, $title_msg, $notificationData, $shop->id, Notice::FOLLOWED_BUSINESS, $user->id);

                        DB::commit();
                        Log::info('End code for follow / unfollow shop');
                        return $this->sendSuccessResponse(Lang::get('messages.shop.follow-success'), 200, []);
                    } else {
                        $unFollow = ShopFollowers::where('shop_id', $shop->id)->where('user_id', $user->id)->forcedelete();
                        DB::commit();
                        Log::info('End code for follow / unfollow shop');
                        return $this->sendSuccessResponse(Lang::get('messages.shop.unfollow-success'), 200, []);
                    }

                    DB::commit();
                    Log::info('End code for follow / unfollow shop');
                    return $this->sendSuccessResponse(Lang::get('messages.shop.follow-success'), 200, []);
                } else {
                    return $this->sendSuccessResponse(Lang::get('messages.shop.empty'), 402);
                }
            } else {
                Log::info('End code for follow / unfollow shop');
                return $this->sendSuccessResponse(Lang::get('messages.user.token_expired'), 401);
            }
        } catch (\Exception $ex) {
            Log::info('Exception in the follow / unfollow shop');
            Log::info($ex);
            DB::rollback();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function checkShopStatus($id)
    {

        $currentShop = Shop::where('id', $id)->first();

        $shopPrices = 1;
        /* ShopPrices::join('shop_price_category', 'shop_price_category.id', 'shop_prices.shop_price_category_id')
            ->where('shop_price_category.shop_id', $id)->count(); */

        $shopPosts = ShopPost::where('shop_id', $id)->count();

        $isShopPost = $shopPosts >= 3  ? true : false;
        $isThumbnail = (!empty($currentShop->thumbnail_image) && !empty(collect($currentShop->thumbnail_image)->toArray())) ? true : false;
        $isWokplace = count($currentShop->workplace_images) > 0 ? true : false;
        $isMainProfile = count($currentShop->main_profile_images) > 0 ? true : false;
        $isAddress = $currentShop->address && isset($currentShop->address->address) && $currentShop->address->address != NULL ? true : false;
        $isShopPrices = $shopPrices > 0 ? true : false;
        $isMainName = $currentShop->main_name != NULL ? true : false;
        $isShopName = $currentShop->shop_name != NULL ? true : false;
        $isSpecialityOf = $currentShop->speciality_of != NULL ? true : false;

        if ($isShopPost && $isThumbnail && $isWokplace && $isMainProfile && $isAddress && $isMainName && $isShopName && $isSpecialityOf) {
            Shop::where('id', $id)->update(['status_id' => Status::ACTIVE, 'deactivate_by_user' => 0]);
        } else {
            Shop::where('id', $id)->update(['status_id' => Status::PENDING]);
        }
        return true;
    }

    public function statusDetail(Request $request)
    {
        try {

            Log::info('Start code for get shop status');
            $user = Auth::user();
            if ($user) {
                DB::beginTransaction();
                $inputs = $request->all();
                $validation = $this->shopProfileValidator->validateShopStatus($inputs);

                if ($validation->fails()) {
                    Log::info('End code for get shop status');
                    return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
                }

                $currentShop = Shop::where('id', $inputs['shop_id'])->first();
                if ($currentShop && $currentShop->last_activate_deactivate != NULL) {
                    $lastActiveDate = new Carbon($currentShop->last_activate_deactivate);
                    $lastActiveDate = $lastActiveDate->addDays(30);
                    $current_date = Carbon::now();
                    $can_activate_deactivate = $current_date->greaterThan($lastActiveDate) ? 1 : 0;
                } else {
                    $can_activate_deactivate = 1;
                }
                $total_user_shops = Shop::where('deactivate_by_user', 0)->where('user_id', $user->id)->count();

                $shopPrices = ShopPrices::join('shop_price_category', 'shop_price_category.id', 'shop_prices.shop_price_category_id')
                    ->where('shop_price_category.shop_id', $inputs['shop_id'])->count();

                $shopPosts = ShopPost::where('shop_id', $inputs['shop_id'])->count();

                $user_detail = UserDetail::where('user_id', $currentShop->user_id)->first();
                $creditPlan = CreditPlans::where('entity_type_id', EntityTypes::SHOP)->where('package_plan_id', $user_detail->package_plan_id)->first();
                $userCredits = UserCredit::where('user_id', $currentShop->user_id)->first();
                $defaultCredit = $creditPlan ? $creditPlan->amount : null;
                $pendingData['post_uploads'] = $shopPosts >= 3  ? true : false;
                $pendingData['thumbnail_image'] = !empty((array)$currentShop->thumbnail_image) ? true : false;
                $pendingData['workplace_interior_image'] = count($currentShop->workplace_images) > 0 ? true : false;
                $pendingData['main_profile_image'] = count($currentShop->main_profile_images) > 0 ? true : false;
                $pendingData['address_information'] = isset($currentShop->address->address) && $currentShop->address->address != NULL ? true : false;
                $pendingData['price_information'] = $shopPrices > 0 ? true : false;
                $pendingData['main_name'] = $currentShop->main_name != NULL ? true : false;
                $pendingData['shop_name'] = $currentShop->shop_name != NULL ? true : false;
                $pendingData['speciality_of'] = $currentShop->speciality_of != NULL ? true : false;
                $deactivateData['not_enough_coin'] = $userCredits->credits < ($defaultCredit * $total_user_shops) ? true : false;
                $deactivateData['deactivated_by_you'] = $currentShop->deactivate_by_user == 1 ? true : false;

                $data = [
                    'pending' => $pendingData,
                    'deactivate' => $deactivateData,
                    'can_activate_deactivate' => $can_activate_deactivate
                ];
                DB::commit();
                Log::info('End code for get shop status');
                return $this->sendSuccessResponse(Lang::get('messages.shop.status-success'), 200, $data);
            } else {
                Log::info('End code for get shop status');
                return $this->sendSuccessResponse(Lang::get('messages.user.token_expired'), 401);
            }
        } catch (\Exception $ex) {
            Log::info('Exception in the get shop status');
            Log::info($ex);
            DB::rollback();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function statusChange(Request $request)
    {
        try {

            Log::info('Start code for change shop status');
            $user = Auth::user();
            if ($user) {
                DB::beginTransaction();
                $inputs = $request->all();
                $validation = $this->shopProfileValidator->validateShopStatusChange($inputs);

                if ($validation->fails()) {
                    Log::info('End code for change shop status');
                    return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
                }

                $shop = Shop::find($inputs['shop_id']);
                $devices = UserDevices::whereIn('user_id', [$user->id])->pluck('device_token')->toArray();
                $user_detail = UserDetail::where('user_id', $user->id)->first();
                $language_id = $user_detail->language_id;
                $title_msg = '';

                $notificationData = [
                    'id' => $shop->id,
                    'main_name' => $shop->main_name,
                    'shop_name' => $shop->shop_name,
                    'category_id' => $shop->category_id,
                    'category_name' => $shop->category_name,
                    'category_icon' => $shop->category_icon,
                ];

                if ($inputs['status_id'] == Status::UNHIDE || $inputs['status_id'] == Status::ACTIVE) {
                    $type = $inputs['status_id'] == Status::UNHIDE ? Notice::PROFILE_UNHIDE  : Notice::PROFILE_ACTIVATE;
                    $return = $this->checkShopStatus($inputs['shop_id']);
                    $updateData = ['deactivate_by_user' => 0];
                    if ($inputs['status_id'] == Status::ACTIVE) {
                        $updateData['last_activate_deactivate'] = Carbon::now();
                    }
                    Shop::where('id', $inputs['shop_id'])->update($updateData);
                } else {
                    $data = ['status_id' => $inputs['status_id']];

                    $data['deactivate_by_user'] = $inputs['status_id'] == Status::INACTIVE ? 1 : 0;
                    if ($inputs['status_id'] == Status::INACTIVE) {
                        $type = Notice::PROFILE_DEACTIVATE;
                        $data['last_activate_deactivate'] = Carbon::now();
                    } elseif ($inputs['status_id'] == Status::PENDING) {
                        $type = Notice::PROFILE_PENDING;
                    } elseif ($inputs['status_id'] == Status::HIDDEN) {
                        $type = Notice::PROFILE_HIDE;
                    }
                    Shop::where('id', $inputs['shop_id'])->update($data);
                }

                $key = $type . '_' . $language_id;
                $format = __("notice.$key");
                $notify_type = $type;
                $notice = Notice::create([
                    'notify_type' => $type,
                    'user_id' => $user->id,
                    'to_user_id' => $user->id,
                    'entity_type_id' => EntityTypes::SHOP,
                    'entity_id' => $shop->id,
                    'sub_title' => $shop->shop_name . '(' . $shop->main_name . ')',
                ]);

                $this->updateUserChatStatus();
                if (count($devices) > 0) {
                    $result = $this->sentPushNotification($devices, $title_msg, $format, $notificationData, $notify_type, $shop->id);
                }

                DB::commit();
                Log::info('End code for change shop status');
                return $this->sendSuccessResponse(Lang::get('messages.shop.status-change-success'), 200);
            } else {
                Log::info('End code for change shop status');
                return $this->sendSuccessResponse(Lang::get('messages.user.token_expired'), 401);
            }
        } catch (\Exception $ex) {
            Log::info('Exception in the change shop status');
            Log::info($ex);
            DB::rollback();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function saveMultipleShopPost(Request $request){
        $inputs = $request->all();
        try {
            $validation = $this->shopProfileValidator->validateMultiPost($inputs);
            if ($validation->fails()) {
                return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
            }

            $attechmentIds = $inputs['ids'] ?? [];
            $is_multiple = $inputs['is_multiple'] ?? 0;
            $description = $inputs['description'] ?? '';

            $addPost = (object)[];
            if(!empty($attechmentIds)){
                foreach($attechmentIds as $attKey => $attID){
                    $attData = CustomerAttachment::find($attID);
                    $data = [];
                    $data = [
                        'type' => 'image',
                    ];

                    if($attData){
                        $data['post_item'] =  $attData->image;
                        if($attKey == 0){
                            $data['shop_id'] = $inputs['shop_id'];
                            $data['description'] = $description;
                            $data['is_multiple'] = $is_multiple;
                            $data['post_order_date'] = Carbon::now();
                            $insta_type = User::join('shops', 'shops.user_id', 'users.id')
                                ->where('shops.id',$inputs['shop_id'])
                                ->pluck('users.insta_type')
                                ->first();
                            $remain_download_insta = null;
                            if ($insta_type=="pro"){
                                $remain_download_insta = null;
                            }
                            elseif ($insta_type=="free"){
                                $default_limit = InstaImportantSetting::where('field','Default download')->pluck('value')->first();
                                $remain_download_insta = ($default_limit) ? (int)$default_limit : 10;
                            }
                            $data['remain_download_insta'] = $remain_download_insta;
                            $addPost = ShopPost::create($data);
                            saveHashTagDetails($description,$addPost->id,HashTag::SHOP_POST);
                        }else{
                            $data['shop_posts_id'] = $addPost->id;
                            MultipleShopPost::create($data);
                        }
                    }
                }
            }
            return $this->sendSuccessResponse(Lang::get('messages.shop.portfolio-success'), 200);
        } catch (\Exception $e) {
            Log::info($e);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function updateMultipleShopPost(Request $request){
        $inputs = $request->all();
        try {

            $validator = Validator::make($request->all(), [
                'shop_post_id' => 'required',
            ], [], [
                'shop_post_id' => 'Post ID',
            ]);

            if ($validator->fails()) {
                Log::info('End code for add form request');
                return $this->sendCustomErrorMessage($validator->errors()->toArray(), 422);
            }

            $shop_post_id = $inputs['shop_post_id'];
            $description = $inputs['description'] ?? '';

            $postData = DB::table('shop_posts')->whereId($shop_post_id)->first();
            $shopsFolder = config('constant.shops') . "/posts/" . $postData->shop_id;

            if(!empty($inputs['images'])){
                if($postData && $postData->post_item){
                    Storage::disk('s3')->delete($postData->post_item);
                }
                if($postData && $postData->type == 'video'){
                    Storage::disk('s3')->delete($postData->video_thumbnail);
                }

                $postMultiData = MultipleShopPost::where('shop_posts_id',$shop_post_id)->get();
                if($postMultiData){
                    foreach ($postMultiData as $key => $value) {
                        if($value && $value->post_item){
                            Storage::disk('s3')->delete($value->post_item);
                        }
                        if($value && $value->type == 'video'){
                            Storage::disk('s3')->delete($value->video_thumbnail);
                        }

                        $value->delete();
                    }
                }

                foreach($inputs['images'] as $fileKey => $fileData) {
                    $data = [];
                    $data = [
                        'type' => $fileData['type'] == ShopPost::IMAGE ? 'image' : 'video',
                    ];

                    if($fileKey == 0){
                        $data['description'] = $description;
                        $data['is_multiple'] = $inputs['is_multiple'] ?? 0;
                    }

                    if (is_file($fileData['file'])) {
                        $postImage = Storage::disk('s3')->putFile($shopsFolder, $fileData['file'], 'public');
                        $fileName = basename($postImage);
                        $image_url = $shopsFolder . '/' . $fileName;
                        $data['post_item'] =  $image_url;
                    }

                    if($fileData['type'] == ShopPost::IMAGE && !empty($image_url)){
                        if (!filter_var($image_url, FILTER_VALIDATE_URL)) {
                            $newurl = Storage::disk('s3')->url($image_url);
                        } else {
                            $newurl = $image_url;
                        }

                        $newThumb = Image::make($newurl)->resize(200, 200, function ($constraint) {
                            $constraint->aspectRatio();
                        })->encode(null,90);
                        Storage::disk('s3')->put($shopsFolder.'/thumb/'.$fileName,  $newThumb->stream(), 'public');
                    }

                    if ($fileData['type'] != ShopPost::IMAGE && !empty($fileData['video_thumbnail']) && is_file($fileData['video_thumbnail'])) {
                        $postThumbImage = Storage::disk('s3')->putFile($shopsFolder, $fileData['video_thumbnail'], 'public');
                        $fileThumbName = basename($postThumbImage);
                        $image_thumb_url = $shopsFolder . '/' . $fileThumbName;
                        $data['video_thumbnail'] =  $image_thumb_url;
                    }

                    if($fileKey == 0){
                        $addPost = ShopPost::where('id',$shop_post_id)->update($data);
                    }else{
                        $data['shop_posts_id'] = $shop_post_id;
                        MultipleShopPost::create($data);
                    }

                }
            }

            $data = [];
            $data['description'] = $description;
            ShopPost::where('id',$shop_post_id)->update($data);
            saveHashTagDetails($description,$shop_post_id,HashTag::SHOP_POST);
            $return = ShopPost::where('id',$shop_post_id)->first();

            return $this->sendSuccessResponse(Lang::get('messages.shop.update-success'), 200, $return);

        } catch (\Exception $e) {
            Log::info($e);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }
    public function addMultipleShopPost(Request $request)
    {
        try {

            $user =Auth::user();
            $inputs = $request->all();
            $validation = $this->shopProfileValidator->validateMultiPost($inputs);
            if ($validation->fails()) {
                Log::info('End code for add form request');
                return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
            }

            $description = $inputs['description'] ?? '';
            $shopsFolder = config('constant.shops') . "/posts/" . $inputs['shop_id'];

            if (!Storage::exists($shopsFolder)) {
                Storage::makeDirectory($shopsFolder);
            }


            $addedPosts = [];
            $addPost = (object)[];

            if(!empty($inputs['images'])){
                foreach($inputs['images'] as $fileKey => $fileData) {
                    $data = [];
                    $data = [
                        'type' => $fileData['type'] == ShopPost::IMAGE ? 'image' : 'video',
                    ];

                    if($fileKey == 0){
                        $data['shop_id'] = $inputs['shop_id'];
                        $data['description'] = $description;
                        $data['is_multiple'] = $inputs['is_multiple'] ?? 0;
                    }

                    if (is_file($fileData['file'])) {
                        $postImage = Storage::disk('s3')->putFile($shopsFolder, $fileData['file'], 'public');
                        $fileName = basename($postImage);
                        $image_url = $shopsFolder . '/' . $fileName;
                        $data['post_item'] =  $image_url;
                    }

                    if ($fileData['type'] != ShopPost::IMAGE && !empty($fileData['video_thumbnail']) && is_file($fileData['video_thumbnail'])) {
                        $postThumbImage = Storage::disk('s3')->putFile($shopsFolder, $fileData['video_thumbnail'], 'public');
                        $fileThumbName = basename($postThumbImage);
                        $image_thumb_url = $shopsFolder . '/' . $fileThumbName;
                        $data['video_thumbnail'] =  $image_thumb_url;
                    }

                    if($fileData['type'] == ShopPost::IMAGE && !empty($image_url)){
                        if (!filter_var($image_url, FILTER_VALIDATE_URL)) {
                            $newurl = Storage::disk('s3')->url($image_url);
                        } else {
                            $newurl = $image_url;
                        }

                        $newThumb = Image::make($newurl)->resize(200, 200, function ($constraint) {
                            $constraint->aspectRatio();
                        })->encode(null,90);
                        Storage::disk('s3')->put($shopsFolder.'/thumb/'.$fileName,  $newThumb->stream(), 'public');
                    }

                    if($fileKey == 0){
                        $data['post_order_date'] = Carbon::now();
                        $insta_type = User::join('shops', 'shops.user_id', 'users.id')
                            ->where('shops.id',$inputs['shop_id'])
                            ->pluck('users.insta_type')
                            ->first();
                        $remain_download_insta = null;
                        if ($insta_type=="pro"){
                            $remain_download_insta = null;
                        }
                        elseif ($insta_type=="free"){
                            $default_limit = InstaImportantSetting::where('field','Default download')->pluck('value')->first();
                            $remain_download_insta = ($default_limit) ? (int)$default_limit : 10;
                        }
                        $data['remain_download_insta'] = $remain_download_insta;
                        $addPost = ShopPost::create($data);
                        $addedPosts[] = $addPost->id;
                        saveHashTagDetails($description,$addPost->id,HashTag::SHOP_POST);

                        // points added onece per day
                        $isAvailable = UserPoints::where(['user_id' => $user->id,'entity_type' => UserPoints::UPLOAD_SHOP_POST,'entity_created_by_id' => $user->id])->whereDate('created_at',Carbon::now()->format('Y-m-d'))->first();

                        if(empty($isAvailable)){

                            UserPoints::create([
                                'user_id' => $user->id,
                                'entity_type' => UserPoints::UPLOAD_SHOP_POST,
                                'entity_id' => $addPost->id,
                                'entity_created_by_id' => $user->id,
                                'points' => UserPoints::UPLOAD_SHOP_POST_POINT]);

                            // Send Push notification start
                            $notice = Notice::create([
                                'notify_type' => Notice::UPLOAD_SHOP_POST,
                                'user_id' => $user->id,
                                'to_user_id' => $user->id,
                                'entity_type_id' => EntityTypes::SHOP_POST,
                                'entity_id' => $addPost->id,
                                'title' => '+'.UserPoints::UPLOAD_SHOP_POST_POINT.'exp',
                                'sub_title' => '',
                                'is_aninomity' => 0
                            ]);

                            $user_detail = UserDetail::where('user_id', $user->id)->first();
                            $language_id = $user_detail->language_id;
                            $key = Notice::UPLOAD_SHOP_POST.'_'.$language_id;
                            $userIds = [$user->id];

                            $format = '+'.UserPoints::UPLOAD_SHOP_POST_POINT.'exp';
                            $devices = UserDevices::whereIn('user_id', $userIds)->pluck('device_token')->toArray();

                            $title_msg = __("notice.$key");
                            $notify_type = Notice::UPLOAD_SHOP_POST;

                            $notificationData = [
                                'id' => $addPost->id,
                                'user_id' => $user->id,
                                'title' => $title_msg,
                            ];
                            if (count($devices) > 0) {
                                $result = $this->sentPushNotification($devices,$title_msg, $format, $notificationData ,$notify_type);
                            }
                            // Send Push notification end

                        }


                    }else{
                        $data['shop_posts_id'] = $addPost->id;
                        MultipleShopPost::create($data);
                    }

                }
            }

            $return = ShopPost::whereIn('id',$addedPosts)->get();
            return $this->sendSuccessResponse(Lang::get('messages.shop.portfolio-success'), 200, $return);
        } catch (\Exception $e) {

            Log::info($e);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function manageClick(Request $request)
    {

        $inputs = $request->all();
        try {

            $validator = Validator::make($request->all(), [
                'type' => 'required',
                'post_id' => 'required',
            ], [], [
                'type' => 'Type',
                'post_id' => 'Post ID',
            ]);

            if ($validator->fails()) {
                Log::info('End code for add form request');
                return $this->sendCustomErrorMessage($validator->errors()->toArray(), 422);
            }
            $user = Auth::user();
            $type = $inputs['type'];
            $post_id = $inputs['post_id'];
            $currentMonth = Carbon::now()->format('m');
            $currentYear = Carbon::now()->format('Y');

            $isAvailable = PostClicks::where(['user_id' => $user->id, 'type' => $type, 'entity_id' => $post_id])->whereMonth('created_at',$currentMonth)->whereYear('created_at',$currentYear)->first();

            if(empty($isAvailable)){
                $data = [
                    'user_id' => $user->id,
                    'type' => $type,
                    'entity_id' => $post_id
                ];

                PostClicks::create($data);
            }

            return $this->sendSuccessResponse(Lang::get('messages.general.click_count'), 200);

        } catch (\Exception $e) {
            Log::info($e);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }

    }

    public function checkAlready(Request $request)
    {
        $inputs = $request->all();
        $posts = [];
        $runType = $inputs['type'];

        if($runType == 'post'){
            if(isset($inputs['shop_id'])){
                $posts = DB::table('shop_posts')->where('type','image')->where('shop_id',$inputs['shop_id'])->whereNull('deleted_at')->get();
            }elseif(isset($inputs['limit']) && isset($inputs['offset'])){
                $posts = DB::table('shop_posts')->where('type','image')->whereNull('deleted_at')->offset($inputs['offset'])->take($inputs['limit'])->get();
            }
            $ids = [];
            if(!empty($posts) && count($posts)){
                foreach($posts as $post){
                    $ids[] = $post->id;

                    $shopID = $post->shop_id;
                    try {
                        $shopsFolder = config('constant.shops') . "/posts/" . $shopID;
                        if (!filter_var($post->post_item, FILTER_VALIDATE_URL)) {
                            $url = Storage::disk('s3')->url($post->post_item);
                        } else {
                            $url = $post->post_item;
                        }

                        $newfilepath = $shopsFolder.'/thumb/'.basename($url);
                        //if(!Storage::disk('s3')->exists($newfilepath)){
                            $newThumb = Image::make($url)->resize(200, 200, function ($constraint) {
                                $constraint->aspectRatio();
                            })->encode(null,90);
                            Storage::disk('s3')->put($newfilepath,  $newThumb->stream(), 'public');
                        //}
                    } catch (\Throwable $th) {
                        print_r($post->id);
                        print_r($th->getMessage());
                    }

                }
            }
        }
        elseif($runType == 'multi'){
            if(isset($inputs['limit']) && isset($inputs['offset'])){
                $posts = DB::table('multiple_shop_posts')->where('type','image')->whereNull('deleted_at')->offset($inputs['offset'])->take($inputs['limit'])->get();
            }
            $ids = [];
            if(!empty($posts) && count($posts)){
                foreach($posts as $post){
                    $ids[] = $post->id;
                    $shopPostDetail = DB::table('shop_posts')->whereId($post->shop_posts_id)->first();
                    if(!empty($shopPostDetail)){
                        $shopID = $shopPostDetail->shop_id;
                        try {
                            $shopsFolder = config('constant.shops') . "/posts/" . $shopID;
                            if (!filter_var($post->post_item, FILTER_VALIDATE_URL)) {
                                $url = Storage::disk('s3')->url($post->post_item);
                            } else {
                                $url = $post->post_item;
                            }

                            $newfilepath = $shopsFolder.'/thumb/'.basename($url);
                            //if(!Storage::disk('s3')->exists($newfilepath)){
                                $newThumb = Image::make($url)->resize(200, 200, function ($constraint) {
                                    $constraint->aspectRatio();
                                })->encode(null,90);
                                Storage::disk('s3')->put($newfilepath,  $newThumb->stream(), 'public');
                            //}
                        } catch (\Throwable $th) {
                            print_r($post->id);
                            print_r($th->getMessage());
                        }
                    }

                }
            }
        }
        elseif($runType == 'shop'){
            if(isset($inputs['limit']) && isset($inputs['offset'])){
                //->where('shop_image_type',PostImages::THUMBNAIL)
                $posts = DB::table('shop_images')->whereNull('deleted_at')->offset($inputs['offset'])->take($inputs['limit'])->get();
            }
            $ids = [];
            if(!empty($posts) && count($posts)){
                foreach($posts as $post){
                    $ids[] = $post->id;
                    $shopID = $post->shop_id;
                    try {
                        $shopsFolder = config('constant.shops') . '/' . $shopID;
                        if (!filter_var($post->image, FILTER_VALIDATE_URL)) {
                            $url = Storage::disk('s3')->url($post->image);
                        } else {
                            $url = $post->image;
                        }

                        $newfilepath = $shopsFolder.'/thumb/'.basename($url);
                        //if(!Storage::disk('s3')->exists($newfilepath)){
                            $newThumb = Image::make($url)->resize(200, 200, function ($constraint) {
                                $constraint->aspectRatio();
                            })->encode(null,90);
                            Storage::disk('s3')->put($newfilepath,  $newThumb->stream(), 'public');
                        //}
                    } catch (\Throwable $th) {
                        print_r($post->id);
                        print_r($th->getMessage());
                    }


                }
            }
        }
        elseif($runType == 'shop_price'){
            if(isset($inputs['limit']) && isset($inputs['offset'])){
                $shop_prices = DB::table('shop_price_images')->offset($inputs['offset'])->take($inputs['limit'])->get();
            }
            $ids = [];
            if(!empty($shop_prices) && count($shop_prices)){
                foreach($shop_prices as $shop_price){
                    try {
                        $shopsPriceFolder = config('constant.shops_price');
                        $image = ($shop_price->thumb_url!='') ? $shop_price->thumb_url : $shop_price->image;

                        if (!filter_var($image, FILTER_VALIDATE_URL)) {
                            $newurl = Storage::disk('s3')->url($image);
                        } else {
                            $newurl = $image;
                        }
                        $newThumb = Image::make($newurl)->resize(200, 200, function ($constraint) {
                            $constraint->aspectRatio();
                        })->encode(null,90);
                        Storage::disk('s3')->put($shopsPriceFolder.'/thumb/'.basename($newurl),  $newThumb->stream(), 'public');
                    } catch (\Throwable $th) {
                        print_r($shop_price->id);
                        print_r($th->getMessage());
                    }
                }
            }
        }
        return $this->sendSuccessResponse(Lang::get('messages.shop.portfolio-success'), 200, $ids);
    }

    public function updateBusinessDetail(Request $request)
    {
        $inputs = $request->all();
        try {
            $validator = Validator::make($request->all(), [
                'shop_id' => 'required|integer',
                'chat_option' => 'required|integer|in:0,1,2',
                'business_link' => 'required_if:chat_option,1',
            ]);

            if ($validator->fails()) {
                return $this->sendCustomErrorMessage($validator->errors()->toArray(), 422);
            }

            $shop_id = $inputs['shop_id'];
            $chat_option = $inputs['chat_option'];

            $updateData = [];
            $updateData['chat_option'] = $chat_option;

            if($chat_option == 1 && isset($inputs['business_link']) && !empty($inputs['business_link'])){
                $updateData['business_link'] = $inputs['business_link'];
            }else{
                $updateData['business_link'] = null;
            }

            //if(isset($inputs['booking_link']) && !empty($inputs['booking_link'])){
                $updateData['booking_link'] = $inputs['booking_link'] ?? null;
            //}

            //if(isset($inputs['another_mobile']) && !empty($inputs['another_mobile'])){
                $updateData['another_mobile'] = $inputs['another_mobile'] ?? null;
            //}

            $updateData['show_price'] = $inputs['show_price'] ?? 0;

            $updateData['show_address'] = $inputs['show_address'] ?? 0;

            if (isset($inputs['another_mobile']) && !empty($inputs['another_mobile'])) {
                $is_exist_another_mobile = Shop::where('id', '!=', $shop_id)->where('another_mobile', $inputs['another_mobile'])->count();
                if ($is_exist_another_mobile > 0) {
                    return $this->sendFailedResponse(Lang::get('messages.shop.phone-number-exist'), 400);
                }
            }

            Shop::where('id', $shop_id)->update($updateData);

            $returnShop = Shop::where('id', $shop_id)->get();
            return $this->sendSuccessResponse(Lang::get('messages.shop.update-success'), 200, $returnShop);
        } catch (\Throwable $th) {
            Log::info($th);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function sendBusinessNotification(Request $request)
    {
        $inputs = $request->all();
        try {
            $validator = Validator::make($request->all(), [
                'shop_id' => 'required',
                'button_type' => 'required|in:chat,book,call',
            ]);

            if ($validator->fails()) {
                return $this->sendCustomErrorMessage($validator->errors()->toArray(), 422);
            }

            $language_id = $inputs['language_id'] ?? 4;
            $shop_id = $inputs['shop_id'];
            $button_type = $inputs['button_type'];

            $shopDetail = DB::table('shops')->whereId($shop_id)->first();
            $button_type = $button_type."_button";
            $notice_key = "language_$language_id.$button_type";
            $notice_msg = __("messages.$notice_key");

            $devices = UserDevices::where('user_id', $shopDetail->user_id)->pluck('device_token')->toArray();

            $notice = Notice::create([
                'notify_type' => $button_type,
                'user_id' => $shopDetail->user_id,
                'to_user_id' => $shopDetail->user_id,
                'title' => $notice_msg,
                'entity_id' => $shop_id,
            ]);

            $notificationData = [
                'id' => $shopDetail->user_id,
                'user_id' => $shopDetail->user_id,
                'title' => $notice_msg,
            ];
            if (count($devices) > 0) {
                $result = $this->sentPushNotification($devices, $notice_msg, "", $notificationData, $button_type);
            }

            return $this->sendSuccessResponse(Lang::get('messages.shop.update-success'), 200);
        } catch (\Throwable $th) {
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function fixedHashTagIssue(Request $request)
    {
        $inputs = $request->all();
        $posts = [];
        $ids = [];

        if(isset($inputs['limit']) && isset($inputs['offset'])){
            $posts = DB::table('shop_posts')->leftjoin('hash_tag_mappings', function ($join) {
                $join->on('hash_tag_mappings.entity_id', '=', 'shop_posts.id')
                    ->where('hash_tag_mappings.entity_type_id', HashTag::SHOP_POST);
            })
            ->select('shop_posts.*')
            ->whereNull('hash_tag_mappings.id')
            ->where('shop_posts.is_multiple',1)
            ->where('shop_posts.type','image')
            ->whereNull('shop_posts.deleted_at')
            ->orderBy('shop_posts.created_at','DESC')
            ->offset($inputs['offset'])->take($inputs['limit'])->get();

            foreach ($posts as $key => $value) {
                $id = $value->id;
                $ids[] = $id;
                $description = $value->description;
                saveHashTagDetails($description,$id,HashTag::SHOP_POST);
            }
        }
        return $this->sendSuccessResponse(Lang::get('messages.shop.portfolio-success'), 200, $ids);
    }

    public function updateCardDetails(Request $request)
    {
        $inputs = $request->all();
        $ids = [];

        if(isset($inputs['is_api']) && $inputs['is_api'] == 'yes'){
            $cardRange = CardLevel::all();
            $userCards = UserCards::all();
            if($userCards){
                foreach ($userCards as $key => $card) {
                    $cardRangeCollection = $cardRange;

                    $checkCountLove = $card->love_count;
                    if($checkCountLove > CardLevel::LAST_LEVEL_COUNT){
                        $checkCountLove = CardLevel::LAST_LEVEL_COUNT;
                    }

                    $currentLevel = $cardRangeCollection->where('start','<=',$checkCountLove)->where('end','>=',$checkCountLove)->first();

                    if($currentLevel->id > $card->active_level){
                        $card->update(['active_level' => $currentLevel->id]);
                        $ids[] = $card->id;
                    }
                }
            }
        }
        return $this->sendSuccessResponse(Lang::get('messages.shop.portfolio-success'), 200, $ids);
    }

    public function priceDebug(Request $request)
    {
        $ids = syncGlobalPriceSettings(768,1);
        return $this->sendSuccessResponse(Lang::get('messages.shop.portfolio-success'), 200, $ids);
    }

    public function readGifticonDetail(Request $request)
    {
        $inputs = $request->all();
        try {
            $gifticon_id = $inputs['gifticon_id'] ?? '';
            if(!empty($gifticon_id)){
                GifticonDetail::whereId($gifticon_id)->update(['is_new' => 0]);
            }
            $gifticon_detail = GifticonDetail::whereId($gifticon_id)->first();
            return $this->sendSuccessResponse(Lang::get('messages.shop.portfolio-success'), 200, $gifticon_detail);
        } catch (\Throwable $th) {
            Log::info($th);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function adminShopPostDelete(Request $request, $id)
    {
        $user = Auth::user();

        try {
            DB::beginTransaction();

            if ($user) {
                $shopPost = DB::table('shop_posts')->where('id', $id)->first();
                if ($shopPost) {
                    if ($user->is_admin_access != 1){
                        return $this->sendSuccessResponse(Lang::get('messages.shop.not-admin-access'), 500);
                    }
                    DeletedShopPost::create([
                        'user_id' => $user->id,
                        'shop_post_id' => $id,
                    ]);
                    if(!empty($shopPost->post_item)) {
                        Storage::disk('s3')->delete($shopPost->post_item);
                    }
                    $shop_id = $shopPost->shop_id;
                    ShopPost::where('id', $id)->delete();
//                        $currentShop = $this->checkShopStatus($shop_id);
                    DB::commit();
                    return $this->sendSuccessResponse(Lang::get('messages.shop.post-delete-success'), 200, []);
                } else {
                    return $this->sendSuccessResponse(Lang::get('messages.shop.post-empty'), 402);
                }
            } else {
                return $this->sendSuccessResponse(Lang::get('messages.user.token_expired'), 401);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function shopProfileLink(Request $request){
        $inputs = $request->all();
        try {
            $shopExists = Shop::where('id', $inputs['shop_id'])->get(['id','category_id','status_id'])->first();
            if ($shopExists) {
                return $this->sendSuccessResponse(Lang::get('messages.shop.link-success'), 200, $shopExists->deeplink);
            } else {
                return $this->sendSuccessResponse(Lang::get('messages.shop.empty'), 402);
            }
        } catch (\Exception $e) {
            Log::info($e);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

}
