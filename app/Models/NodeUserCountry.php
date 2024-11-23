<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NodeUserCountry extends Model
{
    protected $table = "node_user_countries";

    protected $fillable = [
        'from_user_id',
        'country',
    ];

}
