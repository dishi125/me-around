<?php

use Carbon\Carbon;
use App\Models\Cards;
use App\Models\Banner;
use App\Models\Status;
use App\Models\HashTag;
use App\Models\Category;
use App\Models\CardLevel;
use App\Models\UserCards;
use App\Models\ShopPrices;
use App\Models\UserDetail;
use App\Models\Association;
use App\Models\CreditPlans;
use App\Models\EntityTypes;
use App\Models\PackagePlan;
use App\Models\DefaultCards;
use App\Models\PostLanguage;
use App\Models\CategoryTypes;
use App\Models\UserCardLevel;
use App\Models\HashTagMapping;
use App\Models\UserCoinHistory;
use App\Models\WeddingSettings;
use App\Models\AssociationUsers;
use App\Models\CardSoldFollowers;
use App\Models\DefaultCardsRives;
use App\Models\ShopPriceCategory;
use Illuminate\Support\Facades\DB;
use App\Models\ShopGlobalPriceCategory;
use Illuminate\Support\Facades\Storage;
use GuzzleHttp\Client;

function timeAgo($time_ago, $language = 4,$timezone = '')
{
//     dd($time_ago);
    if($timezone != '') {
        $date = new Carbon($time_ago);
        $test = $date->format('d-m-Y H:i');
        $currTime = Carbon::now()->format('Y-m-d H:i:s');
        $startTime = Carbon::createFromFormat('d-m-Y H:i',$test, "UTC")->setTimezone($timezone);
        $finishTime = Carbon::createFromFormat('Y-m-d H:i:s', $currTime, "UTC")->setTimezone($timezone);
        $time_elapsed = $finishTime->diffInSeconds($startTime);
    }else {
        $time_ago = strtotime($time_ago);
        $cur_time = time();
        $time_elapsed = $cur_time - $time_ago;
    }

    $seconds = $time_elapsed;
    $minutes = round($time_elapsed / 60);
    $hours = round($time_elapsed / 3600);
    $days = round($time_elapsed / 86400);
    $weeks = round($time_elapsed / 604800);
    $months = round($time_elapsed / 2600640);
    $years = round($time_elapsed / 31207680);

    // Seconds
    if ($seconds <= 60) {
        // return "just now";
        return __("general.just_now_$language");
    } //Minutes
    else if ($minutes <= 60) {
        if ($minutes == 1) {
            return "1". __("general.min_ago_$language");
        } else {
            return $minutes. __("general.min_ago_$language");
        }
    } //Hours
    else if ($hours <= 24) {
        if ($hours == 1) {
            return "1".__("general.hr_ago_$language");
        } else {
            return $hours.__("general.hr_ago_$language");
        }
    } //Days
    else if ($days <= 7) {
        if ($days == 1) {
            return __("general.yesterday_$language");
        } else {
            return $days.__("general.d_ago_$language");
        }
    } //Weeks
    else if ($weeks <= 4.3) {
        if ($weeks == 1) {
            return "1".__("general.w_ago_$language");
        } else {
            return $weeks.__("general.w_ago_$language");
        }
    } //Months
    else if ($months <= 12) {
        if ($months == 1) {
            return "4".__("general.w_ago_$language");
        } else {
            return ($months * 4).__("general.w_ago_$language");
        }
    } //Years
    else {
        if ($years == 1) {
            return "1".__("general.y_ago_$language");
        } else {
            return $years.__("general.y_ago_$language");
        }
    }
}

function getCountryFromLatLong($lat, $long)
{
    $geolocation = $lat . ',' . $long;
    $GOOGLE_API_KEY_HERE = env('GOOGLE_MAP_KEY', 'AIzaSyAzi0podPWmE2dzLWwHGwCAiVHR9Ur4DeY');
    $map = 'https://maps.google.com/maps/api/geocode/json?key=' . $GOOGLE_API_KEY_HERE . '&latlng=' . $geolocation . '&sensor=false';
    $file_contents = file_get_contents($map);
    $json_decode = json_decode($file_contents);
    $country = '';
    if (isset($json_decode->results[0])) {
        $response = array();

        foreach ($json_decode->results[0]->address_components as $addressComponet) {
            if (in_array('political', $addressComponet->types)) {
                $response[] = $addressComponet->short_name;
            }
        }

        $country = end($response);
    }else {
        $country = "KR";
    }
    return $country;
}

function get_nearest_timezone($cur_lat, $cur_long, $country_code = '') {
    $timezone_ids = ($country_code) ? DateTimeZone::listIdentifiers(DateTimeZone::PER_COUNTRY, $country_code)
                                    : DateTimeZone::listIdentifiers();

    if($timezone_ids && is_array($timezone_ids) && isset($timezone_ids[0])) {

        $time_zone = '';
        $tz_distance = 0;

        //only one identifier?
        if (count($timezone_ids) == 1) {
            $time_zone = $timezone_ids[0];
        } else {

            foreach($timezone_ids as $timezone_id) {
                $timezone = new DateTimeZone($timezone_id);
                $location = $timezone->getLocation();
                $tz_lat   = $location['latitude'];
                $tz_long  = $location['longitude'];

                $theta    = $cur_long - $tz_long;
                $distance = (sin(deg2rad($cur_lat)) * sin(deg2rad($tz_lat)))
                + (cos(deg2rad($cur_lat)) * cos(deg2rad($tz_lat)) * cos(deg2rad($theta)));
                $distance = acos($distance);
                $distance = abs(rad2deg($distance));
                // echo '<br />'.$timezone_id.' '.$distance;

                if (!$time_zone || $tz_distance > $distance) {
                    $time_zone   = $timezone_id;
                    $tz_distance = $distance;
                }

            }
        }
        return  $time_zone;
    }
    return 'unknown';
}

function getDeepLink($type,$detailid, $parent = ''){
    if(!empty($parent)){
        $parent = $parent."/";
    }
    $webLink = env('MEAROUND_LIVE_LINK') ? env('MEAROUND_LIVE_LINK') : 'http://www.mearound.kr/';
    return $webLink.$parent.$type."/".$detailid;
    //return route('shop-deeplink', ['type' => $type, 'id' => $detailid]);
    //return url('/deeplink');
}


function saveHashTagDetails($descriptions, $entityID, $type)
{
  // preg_match_all("/#(\\w+)/u", $descriptions, $matches);
    preg_match_all("/#([^\s#]+)/u", $descriptions, $matches);
    $hash = $matches[1] ?? [];

    HashTagMapping::where('entity_id',$entityID)->where('entity_type_id',$type)->delete();

    $tagIDs = [];
    if(!empty($hash)){
        foreach ($hash as $key => $tag) {
            $insert = HashTag::updateOrCreate(['tags' => $tag]);
            HashTagMapping::create([
                'hash_tag_id' => $insert->id,
                'entity_id' => $entityID,
                'entity_type_id' => $type,
            ]);
            $tagIDs[] = $insert->id;
        }
    }
}

function filterDataThumbnailUrl($url){
    if(empty($url)) return '';
    $fileName = basename($url);
    $newValue = str_replace($fileName,"thumb/$fileName",$url);
    if (!filter_var($newValue, FILTER_VALIDATE_URL)) {
        return Storage::disk('s3')->url($newValue);
    } else {
        return $newValue;
    }
}

function filterDataUrl($url)
{
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        return Storage::disk('s3')->url($url);
    } else {
        return $url;
    }
}

function filterPorfolioDate($portfolioData,$timezone,$format = "Y-m-d H:i:s"){
    foreach ($portfolioData as $key => $portfolio) {
        $portfolio = (object)$portfolio;
        $dateShow = Carbon::createFromFormat('Y-m-d H:i:s',$portfolio->created_at, "UTC")->setTimezone($timezone)->toDateTimeString();
        $portfolio->created_at = Carbon::parse($dateShow)->format($format);
        echo $portfolio->created_at; exit;
    }
    return $portfolioData;
}

function getDistanceQuery($latitude,$longitude): string
{
    return "(6371 * acos(cos(radians(" . $latitude . "))
                * cos(radians(addresses.latitude))
            * cos(radians(addresses.longitude)
        - radians(" . $longitude . "))
        + sin(radians(" . $latitude . "))
            * sin(radians(addresses.latitude))))";
}

function loadBannerImages($country): array
{
    $sliders = [];
    $images = Banner::join('banner_images', 'banners.id', '=', 'banner_images.banner_id')
        ->where('banners.entity_type_id', NULL)
        ->where('banners.section', 'home')
        ->where('banners.category_id', null)
        ->whereNull('banners.deleted_at')
        ->whereNull('banner_images.deleted_at')
        ->where('banners.country_code', $country)
        ->orderBy('banner_images.order')->orderBy('banner_images.id', 'desc')
        ->get('banner_images.*');

    foreach ($images as $banner) {
        $temp = [];
        $temp['image'] = Storage::disk('s3')->url($banner->image);
        $temp['link'] = $banner->link;
        $temp['slide_duration'] = $banner->slide_duration;
        $temp['order'] = $banner->order;
        $sliders[] = $temp;
    }
    return $sliders;
}


function getLimitPackageByQuery($typeID): string
{
    $creditPlans = CreditPlans::where('entity_type_id', $typeID)->get();
    $bronzePlanKm = $silverPlanKm = $goldPlanKm = $platinumPlanKm = 0;
    foreach($creditPlans as $plan) {
        if($plan->package_plan_id == PackagePlan::BRONZE) {
            $bronzePlanKm = $plan->km;
        }else if($plan->package_plan_id == PackagePlan::SILVER) {
            $silverPlanKm = $plan->km;
        }else if($plan->package_plan_id == PackagePlan::GOLD) {
            $goldPlanKm = $plan->km;
        }else if($plan->package_plan_id == PackagePlan::PLATINIUM) {
            $platinumPlanKm = $plan->km;
        }
    }

    return DB::raw('case when `shops`.`expose_distance` IS NOT NULL then `shops`.`expose_distance`
            when `users_detail`.package_plan_id = '. PackagePlan::BRONZE .' then '.$bronzePlanKm.'
            when `users_detail`.package_plan_id = '. PackagePlan::SILVER .' then '.$silverPlanKm.'
            when `users_detail`.package_plan_id = '. PackagePlan::GOLD .' then '.$goldPlanKm.'
            when `users_detail`.package_plan_id = '. PackagePlan::PLATINIUM .' then '.$platinumPlanKm.'
            else 40 end ');
}

function getCommunityTabs($id)
{
    $associationsTabs = [];
    $communityCategory = Category::where('category_type_id', CategoryTypes::COMMUNITY)
        ->where('parent_id', 0)
        ->where('status_id', Status::ACTIVE)
        ->get();

    $communityCategory = collect($communityCategory)->map(function ($value) {
        return ['id' => $value->id, 'name' => $value->name, 'is_access' => false, 'type' => 'category', 'category_type' => strtolower($value->name)];
    })->toArray();

    $associations = AssociationUsers::join('associations', 'associations.id', 'association_users.association_id')
        ->where('association_users.user_id', $id)
        ->whereIn('association_users.type', [AssociationUsers::PRESIDENT, AssociationUsers::MANAGER, AssociationUsers::MEMBER])
        ->whereNull('associations.deleted_at')
        ->select('association_users.type', 'associations.association_name', 'associations.id')
        ->groupBy('associations.id')
        ->get();

    $associationsTabs = collect($associations)->map(function ($value) use ($id) {
        $is_access = (in_array($value->type, [AssociationUsers::PRESIDENT, AssociationUsers::MANAGER]));
        return ['id' => $value->id, 'name' => $value->association_name, 'is_access' => $is_access, 'type' => 'associations'];
    })->toArray();

    return array_merge($associationsTabs, $communityCategory);
}


function loadSubCategoryHtml($type,$categoryID,$selectedCategory): string
{
    $returnHTML = '';
    if ($type == 'category') {
        $subCategory = Category::where('category_type_id', CategoryTypes::COMMUNITY)
            ->where('parent_id', $categoryID)
            ->where('status_id', Status::ACTIVE)
            ->get(['id', 'name', 'category_type_id', 'parent_id', 'status_id', 'order']);

        $sub_category = collect($subCategory)->map(function ($value) {
            return ['id' => $value->id, 'name' => $value->name, 'order' => $value->order, 'is_disabled' => false];
        })->toArray();

    } elseif ($type == 'associations') {
        $association = Association::find($categoryID);
        $category = $association->associationCategory;

        $sub_category = collect($category)->map(function ($value) use ($association) {
            $is_disabled = ($association->user_type == AssociationUsers::MEMBER && $value->can_post == 1) ? true : false;
            return ['id' => $value->id, 'name' => $value->name, 'is_disabled' => $is_disabled, 'order' => $value->order];
        })->toArray();
    }

    if ($sub_category) {
        foreach ($sub_category as $cat) {
            $selected = ($cat['id'] == $selectedCategory) ? "selected" : "";
            $returnHTML .= "<option $selected value='" . $cat['id'] . "'>" . $cat['name'] . " </option>";
        }
    }
    return $returnHTML;
}

function getMetaData($id, $metaKey, $single = true)
{
    if(!$id) return '';
    $metaValue = DB::table('wedding_meta_data')->where('wedding_id',$id)->where('meta_key',$metaKey)->first();
    if($single) return $metaValue ? $metaValue->meta_value : '';
    return $metaValue;
}

function getDefaultCard(){
    return DefaultCardsRives::where('card_name',DefaultCards::DEFAULT_CARD)->first();
}

function getUserNextAwailLevel($user_id,$level){
    $currentRange = DB::table('default_cards')
        ->whereRaw("(default_cards.start <= ".$level." AND default_cards.end >= ".$level.")")
        ->first();

    return ($currentRange) ? $currentRange->end + 1: 0;
}

function createUserCardDetail($levelCards,$userCard){
    if($levelCards->cardLevels()->count()){
        foreach ($levelCards->cardLevels as $card){
            UserCardLevel::updateOrCreate([
                'user_card_id' => $userCard->id,
                'card_level' => $card->card_level,
            ]);
        }
    }else{
        $other_level = CardLevel::where('id','!=',CardLevel::DEFAULT_LEVEL)->get();
        foreach ($other_level as $level){
            UserCardLevel::updateOrCreate([
                'user_card_id' => $userCard->id,
                'card_level' => $level->id,
            ]);
        }
    }
}

function getUserCardDetailLevelWise($cards){

    if($cards->active_level == CardLevel::DEFAULT_LEVEL) return $cards;

    $defaultCard = DefaultCardsRives::where('id',$cards->default_cards_riv_id)->first();
    if(!$defaultCard->cardLevels()->count()) return $cards;

    $cardsLevelData = (object)[];

    $levelCard = $defaultCard->cardLevels()->firstWhere('card_level',$cards->active_level);
    $cardsLevelData->background_rive_url = $levelCard->background_rive_url ?? '';
    $cardsLevelData->character_rive_url = $levelCard->character_rive_url ?? '';
    return $cardsLevelData;
}

function getUserAppliedCard($user_id): ?object
{
    $appliedCardObject = (object)[];
    $appliedCard = DefaultCardsRives::join('user_cards', 'user_cards.default_cards_riv_id', 'default_cards_rives.id')
                ->select('default_cards_rives.*','user_cards.id as user_card_id','user_cards.active_level','user_cards.card_level_status')
                ->where(['user_cards.user_id' => $user_id,'user_cards.is_applied' => 1])
                ->first();

    if(!$appliedCard) return null;
    $appliedCardObject->card_id = $appliedCard->id;
    if($appliedCard->active_level == CardLevel::DEFAULT_LEVEL){
        $appliedCardObject->display_background_rive_url = $appliedCard->background_rive_url ?? '';
        $appliedCardObject->background_rive_url = $appliedCard->background_rive_url ?? '';
        $appliedCardObject->card_level_status = $appliedCard->card_level_status;
        if($appliedCard->card_level_status == UserCards::NORMAL_STATUS) {
            $appliedCardObject->display_character_rive_url = $appliedCard->character_rive_url ?? '';
            $appliedCardObject->character_rive_url = $appliedCard->character_rive_url ?? '';
        }else{
            $defaultCardStatus = $appliedCard->cardLevelStatusRive()->where('card_level_status',$appliedCard->card_level_status)->where('card_level_id',$appliedCard->active_level)->first();
            $appliedCardObject->display_character_rive_url = $defaultCardStatus->character_riv_url ?? '';
            $appliedCardObject->character_rive_url = $defaultCardStatus->character_riv_url ?? '';
        }
    }else {

        $cardLevelData = UserCardLevel::where('user_card_id',$appliedCard->user_card_id)->where('card_level',$appliedCard->active_level)->first();
        $levelCard = $appliedCard->cardLevels()->firstWhere('card_level', $appliedCard->active_level);
        if ($levelCard) {
            $card_level_status = $cardLevelData->card_level_status ?? UserCards::NORMAL_STATUS;
            $appliedCardObject->card_level_status = $card_level_status;
            $appliedCardObject->display_background_rive_url = $levelCard->background_rive_url ?? '';
            $appliedCardObject->background_rive_url = $levelCard->background_rive_url ?? '';
            if($card_level_status == UserCards::NORMAL_STATUS) {
                $appliedCardObject->display_character_rive_url = $levelCard->character_rive_url ?? '';
                $appliedCardObject->character_rive_url = $levelCard->character_rive_url ?? '';
            }else{
                $defaultCardStatus = $appliedCard->cardLevelStatusRive()->where('card_level_status',$card_level_status)->where('card_level_id',$appliedCard->active_level)->first();
                $appliedCardObject->display_character_rive_url = $defaultCardStatus->character_riv_url ?? '';
                $appliedCardObject->character_rive_url = $defaultCardStatus->character_riv_url ?? '';
            }
        }
    }
    return property_exists($appliedCardObject,'display_character_rive_url') ? $appliedCardObject : null;
    //return UserCards::select('id','character_riv')->where(['user_id' => $user_id,'is_applied' => 1])->first();
}

function getThumbnailUserAppliedCard($user_id): ?object
{
    $appliedCardObject = (object)[];
    $appliedCard = DefaultCardsRives::join('user_cards', 'user_cards.default_cards_riv_id', 'default_cards_rives.id')
        ->select('default_cards_rives.*','user_cards.id as user_card_id','user_cards.active_level','user_cards.card_level_status')
        ->where(['user_cards.user_id' => $user_id,'user_cards.is_applied' => 1])
        ->first();

    if(!$appliedCard) return null;
    $appliedCardObject->card_id = $appliedCard->id;
    if($appliedCard->active_level == CardLevel::DEFAULT_LEVEL){
        $appliedCardObject->display_background_thumbnail_url = $appliedCard->background_thumbnail_url ?? '';
        $appliedCardObject->background_thumbnail_url = $appliedCard->background_thumbnail_url ?? '';
        $appliedCardObject->card_level_status = $appliedCard->card_level_status;
        if($appliedCard->card_level_status == UserCards::NORMAL_STATUS) {
            $appliedCardObject->display_character_thumbnail_url = $appliedCard->character_thumbnail_url ?? '';
            $appliedCardObject->character_thumbnail_url = $appliedCard->character_thumbnail_url ?? '';
        }else{
            $defaultCardStatus = $appliedCard->cardLevelStatusThumb()->where('card_level_status',$appliedCard->card_level_status)->where('card_level_id',$appliedCard->active_level)->first();
            $appliedCardObject->display_character_thumbnail_url = $defaultCardStatus->character_thumb_url ?? '';
            $appliedCardObject->character_thumbnail_url = $defaultCardStatus->character_thumb_url ?? '';
        }
    }else {
        $cardLevelData = UserCardLevel::where('user_card_id',$appliedCard->user_card_id)->where('card_level',$appliedCard->active_level)->first();
        $levelCard = $appliedCard->cardLevels()->firstWhere('card_level', $appliedCard->active_level);
        if ($levelCard) {
            $card_level_status = $cardLevelData->card_level_status ?? UserCards::NORMAL_STATUS;
            $appliedCardObject->card_level_status = $card_level_status;
            $appliedCardObject->display_background_thumbnail_url = $levelCard->background_thumbnail_url ?? '';
            $appliedCardObject->background_thumbnail_url = $levelCard->background_thumbnail_url ?? '';
            if($card_level_status == UserCards::NORMAL_STATUS) {
                $appliedCardObject->display_character_thumbnail_url = $levelCard->character_thumbnail_url ?? '';
                $appliedCardObject->character_thumbnail_url = $levelCard->character_thumbnail_url ?? '';
            }else{
                $defaultCardStatus = $appliedCard->cardLevelStatusThumb()->where('card_level_status',$card_level_status)->where('card_level_id',$appliedCard->active_level)->first();
                $appliedCardObject->display_character_thumbnail_url = $defaultCardStatus->character_thumb_url ?? '';
                $appliedCardObject->character_thumbnail_url = $defaultCardStatus->character_thumb_url ?? '';
            }
        }
    }
    return property_exists($appliedCardObject,'display_character_thumbnail_url') ? $appliedCardObject : null;
}

function getLevelNameByID($level_id){
    $cardLevel = CardLevel::whereId($level_id)->first();
    if(!$cardLevel) return;
    return $cardLevel->level_name ?? '';
}


function getCardRangePrice($cardID): string
{
    $cardRiveData = DefaultCardsRives::where('id',$cardID)->first();
    $cardStartPrice = $cardEndPrice = $cardRiveData->usd_price;
    if($cardRiveData->cardLevels()->count()){
        $cardLastLevel = $cardRiveData->cardLevels()->firstWhere('card_level',CardLevel::LAST_LEVEL);
        $cardEndPrice = $cardLastLevel->usd_price;
    }
    if($cardStartPrice > 0 || $cardEndPrice > 0) {
        $cardRangePrice = "$cardStartPrice ~ $cardEndPrice";
    }else{
        $cardRangePrice = $cardStartPrice;
    }
    return $cardRangePrice;
}

function filterPrice($price,$countryPrice): string
{
    $priceSymbol = DefaultCardsRives::CARD_PRICES[$countryPrice];
    return $priceSymbol.$price;
}

function getWeddingSettingOptions($key){

    if($key == 'design'){
        return ['design_1' => "Design 1", 'design_2' => "Design 2", 'design_3' => "Design 3"];
    }else{
        $settings = WeddingSettings::where('key' , $key)->get();
        $data = $settings->mapWithKeys(function ($item, $key) {
            return [$item['id'] => basename($item['value'])];
        });
        return $data;
    }
    return [];
}

function getSettingURL($fileID){
    $setting = WeddingSettings::whereId($fileID)->first();
    return $setting ? $setting->filter_value : '';
}

function getFollowerUsers($user_id)
{
    static $followers = [];
    $userDetail = UserDetail::where('user_id',$user_id)->with('followersDetail')->first();
    if(!empty($userDetail)){
        if(!empty($userDetail->followersDetail->count())){
            $userDetail->followersDetail->map(function ($item) use(&$followers){
                $followers[] = $item->user_id;
                $item->followersDetail->map(function ($item) use(&$followers){
                    $followers[] = $item->user_id;
                    $item->followersDetail->map(function ($item) use(&$followers){
                        $followers[] = $item->user_id;
                    });
                });
            });

            /* $followers = $userDetail->followersDetail->map(function ($item){
                $grandFollowers = $item->followersDetail->map(function ($item){
                    $greatGrandFollowers = $item->followersDetail->map(function ($item){
                        return $item->user_id;
                    });
                    return [$item->user_id,...$greatGrandFollowers];
                });
                return [$item->user_id,...$grandFollowers];
            }); */
        }
    }

    return $followers;
}
function getUserSoldCardPrice($user_id)
{
    $details = CardSoldFollowers::where('user_id',$user_id)
        ->where('status','0')
        ->select(
            DB::raw("SUM(
                CASE
                    WHEN follower_level = ".CardSoldFollowers::FOLLOWERS." THEN ".CardSoldFollowers::FOLLOWERS_COIN."
                    WHEN follower_level = ".CardSoldFollowers::GRAND_FOLLOWERS." THEN ".CardSoldFollowers::GRAND_FOLLOWERS_COIN."
                    WHEN follower_level = ".CardSoldFollowers::GREAT_GRAND_FOLLOWERS." THEN ".CardSoldFollowers::GREAT_GRAND_FOLLOWERS_COIN."
                    ELSE '0'
                END
            ) AS amount")
        )
        ->first();

    return $details->amount ?? 0;
}

function getUserTotalCoin($user_id){
    $totalCreditCardCoin = UserCoinHistory::where('user_id',$user_id)->where('transaction','credit')->sum('amount');
    $totalDebitCardCoin = UserCoinHistory::where('user_id',$user_id)->where('transaction','debit')->sum('amount');

    return ($totalCreditCardCoin - $totalDebitCardCoin) ?? 0;
}

function syncGlobalPriceSettings($shop_id,$language_id = PostLanguage::ENGLISH)
{
    $globalPrice = ShopGlobalPriceCategory::with(['category_languages','prices.price_languages',])->get();

    if($globalPrice){
        foreach ($globalPrice as $settings) {
            $settings_category_name = $settings->name;
            if($language_id != PostLanguage::ENGLISH){
                $catLang = $settings->category_languages()->where('language_id',$language_id)->first();
                $settings_category_name = $catLang->name ?? $settings_category_name;
            }

            $data = [
                'shop_id' => $shop_id,
                'name' => $settings_category_name,
            ];
            $shopItem = ShopPriceCategory::firstOrCreate($data);

            $prices = $settings->prices;

            if($prices){
                foreach ($prices as $priceData) {
                    $price_name = $priceData->name;
                    $price = $priceData->price;
                    $price_discount = $priceData->discount;
                    if($language_id != PostLanguage::ENGLISH){
                        $priceLang = $priceData->price_languages()->where('language_id',$language_id)->first();
                        $price_name = $priceLang->name ?? $price_name;
                    }

                    $priceInsertData = [
                        'shop_price_category_id' => $shopItem->id,
                        'name' => $price_name,
                        'price' => $price,
                        'discount' => $price_discount,
                    ];
                    ShopPrices::firstOrCreate($priceInsertData);

                }
            }

        }
    }
    return $globalPrice;
}

function uploadImage($accessToken, $imagePath, $caption = null)
{
    $apiBaseUrl = config('app.INSTAGRAM_HOST_URL');
    $url = $apiBaseUrl.'/me/media';
    /*$httpClient = new Client();
    $response = $httpClient->post($url, [
        'query' => [
            'access_token' => $accessToken,
            'image_url' => $imagePath,
            'caption' => $caption,
        ],
    ]);*/
    $response = \Illuminate\Support\Facades\Http::post(
        "https://graph.instagram.com/5313327515429211/media",
        [
            'image_url' => 'https://picsum.photos/200/300',
            'access_token' => $accessToken,
        ]
    );

    if (file_exists($imagePath)) {
        unlink($imagePath);
    }

    dd($response->json());
//    return $response->getBody()->getContents();
}
