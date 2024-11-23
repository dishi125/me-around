<?php

namespace App\Models;

use App\Models\RequestBookingStatus;
use App\Models\UserDetail;
use App\Models\UserEntityRelation;
use App\Models\Hospital;
use App\Models\Shop;
use App\Models\ReloadCoinCurrency;
use App\Models\Manager;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class ReloadCoinRequest extends Model
{
    protected $table = 'reload_coins_request';    
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */

     const REQUEST_COIN = 0;
     const GIVE_COIN = 1;
     const REJECT_COIN = 2;
     
    protected $fillable = [
        'user_id','currency_id','sender_name', 'order_number','coin_amount','supply_price','vat_amount','total_amount','status','created_at', 'is_admin_read'
    ];

    protected $casts = [
        'user_id' => 'int',
        'currency_id' => 'int',
        'sender_name' => 'string',
        'order_number' => 'string',
        'coin_amount' => 'decimal:0',
        'supply_price' => 'decimal:0',
        'vat_amount' => 'decimal:0',
        'total_amount' => 'decimal:0',
        'status' => 'int',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    protected $appends = ['country_code','activate_name','category_name','phone_number','manager_name','currency_name','bank_name','bank_account_number'];

    public function getCountryCodeAttribute()
    {
        $user_id = !empty($this->attributes['user_id']) ? $this->attributes['user_id'] : 0;
        $hospital_count = UserEntityRelation::where('entity_type_id', EntityTypes::HOSPITAL)->where('user_id',$user_id)->count();
        $shop_count = UserEntityRelation::where('entity_type_id', EntityTypes::SHOP)->where('user_id',$user_id)->count();
        $name = '';
        if($hospital_count > 0) {
            $user_data = UserEntityRelation::where('entity_type_id', EntityTypes::HOSPITAL)->where('user_id',$user_id)->first();
            $hospital = Hospital::find($user_data->entity_id);
            $country_code = $hospital && $hospital->address && isset($hospital->address->main_country) ? $hospital->address->main_country : '';
        }else if($shop_count > 0) {
            $user_data = UserEntityRelation::where('entity_type_id', EntityTypes::SHOP)->where('user_id',$user_id)->first();
            $shop = Shop::find($user_data->entity_id);
            $country_code = $shop && $shop->address && isset($shop->address->main_country) ? $shop->address->main_country : '';
        }
        
        return $this->attributes['country_code'] = $country_code;
    }
    public function getActivateNameAttribute()
    {
        $user_id = !empty($this->attributes['user_id']) ? $this->attributes['user_id'] : 0;
        $hospital_count = UserEntityRelation::where('entity_type_id', EntityTypes::HOSPITAL)->where('user_id',$user_id)->count();
        $shop_count = UserEntityRelation::where('entity_type_id', EntityTypes::SHOP)->where('user_id',$user_id)->count();
        $name = '';
        if($hospital_count > 0) {
            $user_data = UserEntityRelation::where('entity_type_id', EntityTypes::HOSPITAL)->where('user_id',$user_id)->first();
            $hospital = Hospital::find($user_data->entity_id);
            $name = $hospital ? $hospital->main_name : "";
        }else if($shop_count > 0) {
            $user_data = UserEntityRelation::where('entity_type_id', EntityTypes::SHOP)->where('user_id',$user_id)->first();
            $shop = Shop::find($user_data->entity_id);
            $name = $shop ? $shop->main_name : "";
        }
        
        return $this->attributes['activate_name'] = $name;
    }

    public function getCategoryNameAttribute()
    {
        $user_id = !empty($this->attributes['user_id']) ? $this->attributes['user_id'] : 0;
        $hospital_count = UserEntityRelation::where('entity_type_id', EntityTypes::HOSPITAL)->where('user_id',$user_id)->count();
        $shop_count = UserEntityRelation::where('entity_type_id', EntityTypes::SHOP)->where('user_id',$user_id)->count();
        $name = '';
        if($hospital_count > 0) {
            $user_data = UserEntityRelation::where('entity_type_id', EntityTypes::HOSPITAL)->where('user_id',$user_id)->first();
            $hospital = Hospital::find($user_data->entity_id);
            $name = $hospital ? $hospital->category_name : "";
        }else if($shop_count > 0) {
            $user_data = UserEntityRelation::where('entity_type_id', EntityTypes::SHOP)->where('user_id',$user_id)->first();
            $shop = Shop::find($user_data->entity_id);
            $name = $shop ? $shop->category_name : "";
        }
        
        return $this->attributes['category_name'] = $name;
    }

    public function getPhoneNumberAttribute()
    {
        $user_id = !empty($this->attributes['user_id']) ? $this->attributes['user_id'] : 0;
        $user_data = UserDetail::where('user_id',$user_id)->first();
        $mobile = $user_data ? $user_data->mobile : '';
        
        
        return $this->attributes['phone_number'] = $mobile;
    }

    public function getManagerNameAttribute()
    {
        $user_id = !empty($this->attributes['user_id']) ? $this->attributes['user_id'] : 0;
        $user_data = UserDetail::where('user_id',$user_id)->first();
        $manager_id = $user_data ? $user_data->manager_id : 0;
        $manager = Manager::find($manager_id);
        $name = $manager ? $manager->name : '';
        
        
        return $this->attributes['manager_name'] = $name;
    }

    public function getCurrencyNameAttribute()
    {
        $currency_id = !empty($this->attributes['currency_id']) ? $this->attributes['currency_id'] : 0;
        $currency = ReloadCoinCurrency::find($currency_id);
        $name = $currency ? $currency->name : '';

        return $this->attributes['currency_name'] = $name;
    }

    public function getBankNameAttribute()
    {
        $currency_id = !empty($this->attributes['currency_id']) ? $this->attributes['currency_id'] : 0;
        $currency = ReloadCoinCurrency::find($currency_id);
        $name = $currency ? $currency->bank_name : '';

        return $this->attributes['bank_name'] = $name;
    }

    public function getBankAccountNumberAttribute()
    {
        $currency_id = !empty($this->attributes['currency_id']) ? $this->attributes['currency_id'] : 0;
        $currency = ReloadCoinCurrency::find($currency_id);
        $name = $currency ? $currency->bank_account_number : '';

        return $this->attributes['bank_account_number'] = $name;
    }

    public function getCreatedAtAttribute($date)
    {
        $date = new Carbon($date);
        return $date->format('d-m-Y H:i');
    }

    public function getUpdatedAtAttribute($date)
    {
        $date = new Carbon($date);
        return $date->format('d-m-Y H:i');
    }

    // public function getCoinAmountAttribute($value)
    // {
    //     return number_format($value);
    // }
    public function getSupplyPriceAttribute($value)
    {
        return number_format($value);
    }
    public function getVatAmountAttribute($value)
    {
        return number_format($value);
    }
    public function getTotalAmountAttribute($value)
    {
        return number_format($value);
    }
}
