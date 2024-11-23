<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class CompletedCustomer extends Model
{
    use SoftDeletes;
    protected $table = 'completed_customer';

    protected $dates = ['deleted_at'];

    protected $hidden = ['deleted_at'];

    protected $fillable = [
        'user_id',
        'requested_customer_id',
        'revenue',
        'customer_memo',
        'date',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'user_id' => 'int',
        'requested_customer_id' => 'int',
        'revenue' => 'string',
        'customer_memo' => 'string',
        'date' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    protected $appends = ['display_booking_date'];

    public function getDateAttribute($date)
    {
        $date = new Carbon($date);
        return $date->format('Y.m.d');
    }

    public function getDisplayBookingDateAttribute()
    {
        if (isset($this->attributes['date'])) {
            $date = new Carbon($this->attributes['date']);
            return $this->attributes['display_booking_date'] = $date->format('Y/m/d H:i A');
        }
        return $this->attributes['display_booking_date'] = '';
    }

    public function getCustomerMemoAttribute()
    {
        $value = !empty($this->attributes['customer_memo']) ? $this->attributes['customer_memo'] : "";

        return $this->attributes['customer_memo'] = $value ? $value : '';
    }

    public function images()
    {
        return $this->hasMany(CustomerAttachment::class, 'entity_id', 'id')->where('type', CustomerAttachment::INSIDE);
    }
}
