<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class UserCreditHistory extends Model
{
    use SoftDeletes;
    protected $table = 'user_credits_history';

    const REGULAR = 'Regular';
    const DEFAULT = 'Default';
    const RELOAD = 'Reload';
    const CHATING = 'Chating';
    const PENALTY = 'Penalty';
    const REWARD = 'Reward';
    const RECOMMENDED = 'Recommended';

    protected $dates = ['deleted_at'];

    protected $fillable = [
       'booked_user_id','user_id','amount','total_amount','transaction','type','created_at','updated_at'
    ];

    protected $appends = ['classification','formatted_amount','display_created_at'];

    protected $casts = [
        'booked_user_id ' => 'int',
        'user_id' => 'int',
        'amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'transaction' => 'string',
        'type' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function getClassificationAttribute()
    {
        $type = $this->attributes['type'];
        $text = '';
        if($type == UserCreditHistory::REGULAR) {
            $text = 'Monthly deducts';
        } else if($type == UserCreditHistory::RELOAD) {
            $text = 'Coin reload';
        } else if($type == UserCreditHistory::CHATING) {
            $text = 'Inquiry deducts';
        } else if($type == UserCreditHistory::PENALTY) {
            $text = 'Penalty';
        } else if($type == UserCreditHistory::REWARD) {
            $text = 'SNS Rewards';
        } else if($type == UserCreditHistory::RECOMMENDED) {
            $text = 'Recommended Rewards';
        } else if($type == UserCreditHistory::DEFAULT) {
            $text = 'Business Profile Rewards';
        }         
       return $this->attributes['classification'] = $text;
    }
    
    public function getFormattedAmountAttribute()
    {
        $amount = $this->attributes['amount'];
        $transaction = $this->attributes['transaction'];
        $prefix = $transaction == 'debit' ? '-' : '+';
       return $this->attributes['formatted_amount'] = $prefix.number_format($amount,0).' Coin';
    }

    public function getTotalAmountAttribute($amount)
    {
       return number_format($amount,0).' Coin';
    }

    public function getCreatedAtAttribute($date)
    {
        $date = new Carbon($date);
        return $date->format('Y/M/d A h:i');
    }

    public function getDisplayCreatedAtAttribute(){
        $created_at = $this->attributes['created_at'];
        return $this->attributes['display_created_at'] = Carbon::parse($created_at)->format('Y-m-d H:i:s');
    }
}
