<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactManager extends Model
{
    protected $table = 'contact_managers';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'category',
        'comment',
        'created_at',
        'updated_at'
    ];



    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'category' => 'string',
        'comment' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];
}
