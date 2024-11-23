<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use App\Models\ReloadCoinCurrency;

class CurrencyCoin extends Model
{
    protected $table = 'currency_coin';
     
    protected $fillable = [
        'currency_id','coins'
    ];

    protected $casts = [
        'id' => 'int',
        'currency_id' => 'int',        
        'coins' => 'int',        
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = ['currency_name','display_created_at'];

    public function getCurrencyNameAttribute()
    {
        $value = $this->attributes['currency_id'];

        $currency = ReloadCoinCurrency::find($value);

        return $this->attributes['currency_name'] = $currency->name;
        
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

    public function getDisplayCreatedAtAttribute(){
        $created_at = $this->attributes['created_at'];
        return $this->attributes['display_created_at'] = Carbon::parse($created_at)->format('Y-m-d H:i:s');
    }
}
