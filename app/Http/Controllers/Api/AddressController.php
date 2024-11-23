<?php

namespace App\Http\Controllers\Api;

use App\Models\City;
use App\Models\Country;
use App\Models\State;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Log;

class AddressController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function getCountry(Request $request)
    {
        $inputs = $request->all();
        $name = $inputs['name'];
        try {
            Log::info('Start code for the get country');
            $country = Country::where('name', $name)->first(['id', 'name']);
            Log::info('End code for the get country');
            if ($country) {
                return $this->sendSuccessResponse($country ? Lang::get('messages.country.success') : Lang::get('messages.country.empty'), 200, compact('country'));
            }
        } catch (\Exception $e) {
            Log::info('Exception in the  get country');
            Log::info($e);
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function getCurrentCity(Request $request)
    {
        $inputs = $request->all();
        try {
            Log::info('Start code for the get current city');
            $lat = $inputs['latitude'];
            $long = $inputs['longitude'];
            $google_api_path = 'https://maps.googleapis.com/maps/api/geocode/json?latlng=' . trim($lat) . ',' . trim($long) . '&key=' . env('GOOGLE_API');
            // $path = 'https://maps.googleapis.com/maps/api/place/nearbysearch/json?location=-33.8670522,151.1957362&radius=1500&type=restaurant&keyword=cruise&key=YOUR_API_KEY';
            $geocodeFromLatLong = file_get_contents($google_api_path);
            $cities = json_decode($geocodeFromLatLong);
            $add_array  = $cities->results;
            $add_array = $add_array[0];
            $add_array = $add_array->address_components;
            $country = "Not found";
            $state = "Not found";
            $city = "Not found";
            foreach ($add_array as $key) {
                if ($key->types[0] == 'administrative_area_level_2') {
                    $city = $key->long_name;
                }
                if ($key->types[0] == 'administrative_area_level_1') {
                    $state = $key->long_name;
                }
                if ($key->types[0] == 'country') {
                    $country = $key->long_name;
                }
            }
            $data = $this->addCurrentLocation($country, $state, $city);
            Log::info('End code for the get country');
            return $this->sendSuccessResponse(Lang::get('messages.city.success'), 200, $data);
        } catch (\Exception $e) {
            Log::info('Exception in the  get country');
            Log::info($e);
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function getAllCity(Request $request)
    {
        $inputs = $request->all();
        try {
            Log::info('Start code for the all city');
            $city_list = City::where('state_id', $inputs['state_id'])->get();
            Log::info('End code for the get country');
            return $this->sendSuccessResponse(Lang::get('messages.city.success'), 200, $city_list);
        } catch (\Exception $e) {
            Log::info('Exception in the  get country');
            Log::info($e);
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }
}
