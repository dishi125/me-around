<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Manager extends Model
{
    use SoftDeletes;
    protected $table = 'managers';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name','mobile','recommended_code','avatar','user_id','created_at','updated_at'
    ];



    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'name' => 'string',
        'mobile' => 'string',
        'recommended_code' => 'string',
        'avatar' => 'string',
        'user_id' => 'int',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];
}
