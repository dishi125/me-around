<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RequestFormStatus extends Model
{
    protected $table = 'request_form_status';
        
    const CONFIRM = 1;
    const PENDING = 2;
    const REJECT = 3;

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
