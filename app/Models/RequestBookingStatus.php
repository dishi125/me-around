<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RequestBookingStatus extends Model
{
    protected $table = 'request_booking_status';
        
    const TALK = 1;
    const BOOK = 2;
    const VISIT = 3;
    const COMPLETE = 4;
    const NOSHOW = 5;
    const CANCEL = 6;

    protected $fillable = [
        'name'
    ];

    protected $casts = [
        'id' => 'int',
        'name' => 'string',        
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
