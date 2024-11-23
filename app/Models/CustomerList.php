<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerList extends Model
{
    protected $table = 'customer_lists';
    
    protected $fillable = [
        'user_id','customer_name', 'customer_phone', 'created_at','updated_at', 'is_deleted'
    ];

    protected $appends = ['is_completed_before'];

    public function bookingDetails() {
        return $this->hasMany(CompleteCustomerDetails::class, 'customer_id', 'id');
    }

    public function getIsCompletedBeforeAttribute(){
        $id = $this->attributes['id'];
        $customerBooking = CompleteCustomerDetails::where('customer_id',$id)->where('status_id',RequestBookingStatus::COMPLETE)->count();
        return $this->attributes['is_completed_before'] = ($customerBooking > 0) ? true : false;
    }
}
