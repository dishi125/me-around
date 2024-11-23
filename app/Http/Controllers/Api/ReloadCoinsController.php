<?php

namespace App\Http\Controllers\Api;

use App\Models\Config;
use App\Models\Status;
use App\Models\CurrencyCoin;
use App\Models\Currency;
use App\Models\ReloadCoinCurrency;
use App\Models\ReloadCoinRequest;
use App\Models\Notice;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use App\Validators\ReloadCoinValidator;
use App\Mail\CommonMail;
use Illuminate\Support\Facades\Mail;


class ReloadCoinsController extends Controller
{

    private $reloadCoinValidator;

    function __construct()
    {
        $this->reloadCoinValidator = new ReloadCoinValidator();
    }    
    
   
    public function getReloadCoinCurrencyList()
    {
        $user = Auth::user();
        try {
            Log::info('Start code for get reload coins currency');   
            if($user){
                $currency_list = ReloadCoinCurrency::where('status_id', Status::ACTIVE)->orderBy('priority')->get();
                Log::info('End code for the get reload coins currency');
                return $this->sendSuccessResponse(Lang::get('messages.reload-coin.get-success'), 200, compact('currency_list'));
            }else{
                Log::info('End code for get reload coins currency');
                return $this->sendSuccessResponse(Lang::get('messages.user.token_expired'), 401);
            }   
        } catch (\Exception $e) {
            Log::info('Exception in get reload coins currency');
            Log::info($e);
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function getReloadCoinData(Request $request)
    {
        $user = Auth::user();
        $inputs = $request->all();
        try {
            Log::info('Start code for get reload coins data');   
            if($user){
                $validation = $this->reloadCoinValidator->validateGetData($inputs);
                if ($validation->fails()) {
                    Log::info('End code for get reload coins data');
                    return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
                }  
                $config = Config::where('key',Config::VAT_RATE)->first();
                $vat_percentage = $config ?  (int) filter_var($config->value, FILTER_SANITIZE_NUMBER_INT) : 0;
                $currency_data = [];

                $currency = CurrencyCoin::where('currency_id', $inputs['currency_id'])->first();
                if($currency) {
                    $currency_data['currency_id'] = $currency->currency_id;
                    $currency_data['currency_name'] = $currency->currency_name;
                    $currency_data['coins'] = $currency->coins;
                } 
                              
                Log::info('End code for the get reload coins data');
                return $this->sendSuccessResponse(Lang::get('messages.reload-coin.get-success'), 200, compact('vat_percentage','currency_data'));
            }else{
                Log::info('End code for get reload coins data');
                return $this->sendSuccessResponse(Lang::get('messages.user.token_expired'), 401);
            }   
        } catch (\Exception $e) {
            Log::info('Exception in get reload coins data');
            Log::info($e);
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }
    
    public function reloadCoin(Request $request)
    {
        $user = Auth::user();
        $inputs = $request->all();
        try {
            Log::info('Start code for add reload coins request');   
            if($user){
                $validation = $this->reloadCoinValidator->validateStore($inputs);
                if ($validation->fails()) {
                    Log::info('End code for add reload coins request');
                    return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
                }  
                $order_number = mt_rand(1000000, 9999999);

                if(isset($inputs['latitude']) && isset($inputs['longitude'])) {
                    $main_country = getCountryFromLatLong($inputs['latitude'],$inputs['longitude']);
                    $timezone = get_nearest_timezone($inputs['latitude'],$inputs['longitude'],$main_country);  
                }else {
                    $timezone = '';
                }

                $currTime = Carbon::now()->format('Y-m-d H:i:s');
                //$curFormatTime = empty($timezone) ? $currTime : Carbon::createFromFormat('Y-m-d H:i:s', $currTime, $timezone)->setTimezone('UTC');

                $data = [
                    'currency_id' => $inputs['currency_id'],
                    'sender_name' => $inputs['sender_name'],
                    'coin_amount' => $inputs['coin_amount'],
                    'vat_amount' => $inputs['vat_amount'],
                    'supply_price' => $inputs['supply_price'],
                    'user_id' => $user->id,
                    'order_number' => $order_number,
                    'total_amount' => $inputs['total_amount'],
                    'status' => ReloadCoinRequest::REQUEST_COIN,
                    'created_at' => $currTime
                ];

                $reload_coin = ReloadCoinRequest::create($data);
                $currency_name = $reload_coin ? $reload_coin->currency_name : "";
                $config = Config::where('key',Config::RELOAD_COIN_ORDER_EMAIL)->first();
                $userData = [];
                $userData['email_body'] = "<p><b>Sender Name: </b>".$inputs['sender_name']."</p>";
                $userData['email_body'] .= "<p><b>Order Coin Amount: </b>".number_format($inputs['coin_amount'])."</p>";
                $userData['email_body'] .= "<p><b>Order Number: </b>".$order_number."</p>";
                $userData['email_body'] .= "<p><b>Supply Price: </b>".number_format($inputs['supply_price']).$currency_name."</p>";
                $userData['email_body'] .= "<p><b>VAT: </b>".number_format($inputs['vat_amount']).$currency_name."</p>";
                $userData['email_body'] .= "<p><b>Total Amount: </b>".number_format($inputs['total_amount']).$currency_name."</p>";
                $userData['email_body'] .= "<p><b>Payment Bank Account Name: </b>".$reload_coin->bank_name."</p>";
                $userData['email_body'] .= "<p><b>Payment Bank Account Number: </b>".$reload_coin->bank_account_number."</p>";
                $userData['title'] = 'MeAround - Reload Coin Request';
                $userData['subject'] = 'MeAround - Reload Coin Request';
                $userData['username'] = 'Admin';
                if($config->value) {
                    Mail::to($config->value)->send(new CommonMail($userData));
                }

                $notice = Notice::create([
                    'notify_type' => Notice::RELOAD_COIN_REQUEST,
                    'user_id' => $user->id,
                    'to_user_id' => $user->id,
                    'entity_id' => $reload_coin->id,
                    'title' => $reload_coin->order_number,
                    'sub_title' => number_format($reload_coin->coin_amount),
                ]);
                              
                Log::info('End code for the add reload coins request');
                return $this->sendSuccessResponse(Lang::get('messages.reload-coin.get-success'), 200, compact('reload_coin'));
            }else{
                Log::info('End code for add reload coins request');
                return $this->sendSuccessResponse(Lang::get('messages.user.token_expired'), 401);
            }   
        } catch (\Exception $e) {
            Log::info('Exception in add reload coins request');
            Log::info($e);
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }
}