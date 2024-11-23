<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GeneralSettings;
use Illuminate\Http\Request;
use App\Models\MenuSetting;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Lang;

class MenuSettingApiController extends Controller
{
    public function index(Request $request)
    {
        $inputs = $request->all();
        try {
            $country = $inputs['country'] ?? '';
            $homeQuery = MenuSetting::where('menu_key', MenuSetting::DEFAULT_TAB);
            $query = MenuSetting::where('is_show', 1)->orderBy('menu_order', 'asc');
            if(!empty($country)){
                $query = $query->where('country_code',$country);
                $homeQuery = $homeQuery->where('country_code',$country);
            }else {
                $query = $query->whereNull('country_code');
                $homeQuery = $homeQuery->whereNull('country_code');
            }
            $menuItem = $query->get();
            $homeItem = $homeQuery->first();

            if(!count($menuItem)){
                $menuItem = MenuSetting::where('is_show', 1)->orderBy('menu_order', 'asc')->whereNull('country_code')->get();
            }
            if($menuItem->isEmpty()){
                $menuItem = MenuSetting::where('menu_key', MenuSetting::DEFAULT_TAB)->orderBy('menu_order', 'asc')->whereNull('country_code')->get();
            }

            if(empty($homeItem)){
                $homeItem = MenuSetting::where('menu_key', MenuSetting::DEFAULT_TAB)->whereNull('country_code')->first();
            }

            if($homeItem){
                $menuItem = collect($menuItem)->map(function ($item) use ($homeItem) {
                    if($homeItem->category_option == 1){
                        $visibleOption = (in_array($item->menu_key,MenuSetting::MENU_CARD_LIST)) ? 1 : 0;
                        $item->is_visible_menu = $visibleOption;
                        $item->display_menu_key = $item->menu_key;
                    }else{
                        $visibleOption = ($item->menu_key != MenuSetting::DEFAULT_TAB && in_array($item->menu_key,MenuSetting::MENU_CARD_LIST)) ? 0 : $item->is_show;
                        $item->is_visible_menu = $visibleOption;
                        $item->display_menu_key = ($item->menu_key ==  MenuSetting::DEFAULT_TAB) ? 'all' : $item->menu_key;
                    }
                    return $item;
                });
            }
            return $this->sendSuccessResponse(Lang::get('messages.general.success'), 200, $menuItem);
        } catch (\Exception $e) {
            Log::info($e);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function appVersions(): \Illuminate\Http\JsonResponse
    {
        try {
            $settings = GeneralSettings::whereIn('key',[GeneralSettings::IOS_APP_VERSION,GeneralSettings::ANDROID_APP_VERSION,GeneralSettings::DISPLAY_APP_VERSION])->pluck('value','key');
            return $this->sendSuccessResponse(Lang::get('messages.general.success'), 200, $settings);
        } catch (\Exception $e) {
            Log::info($e);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }
}
