<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserCredit extends Model
{
    use SoftDeletes;
    protected $table = 'user_credits';

    protected $dates = ['deleted_at'];

    protected $fillable = [
        'user_id','credits','created_at','updated_at'
    ];

    protected $casts = [
        'user_id' => 'int',
        'credits' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];
}
