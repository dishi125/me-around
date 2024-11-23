<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class ReloadCoinCurrency extends Model
{
    protected $table = 'reload_coin_currency';
     
    protected $fillable = [
        'name','priority','status_id','bank_name','bank_account_number', 'country_id'
    ];

    protected $appends = ['display_created_at'];

    protected $casts = [
        'id' => 'int',
        'name' => 'string', 
        'priority' => 'int',       
        'bank_name' => 'string',        
        'bank_account_number' => 'string',        
        'status_id' => 'int',        
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

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
