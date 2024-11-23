<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BasicMentions extends Model
{
    protected $table = 'basic_mentions';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'value',
        'created_at',
        'updated_at'
    ];



    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'name' => 'string',
        'value' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];
}
