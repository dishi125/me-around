<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Currency extends Model
{
    protected $table = 'currency';
     
    protected $fillable = [
        'name','status_id'
    ];

    protected $casts = [
        'id' => 'int',
        'name' => 'string',        
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
}
