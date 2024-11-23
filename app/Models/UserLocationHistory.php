<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserLocationHistory extends Model
{
    protected $table = "user_location_histories";

    protected $fillable = [
        'user_id',
        'latitude',
        'longitude',
        'city',
        'country_code',
        'user_type',
    ];

}
