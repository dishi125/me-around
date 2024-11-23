<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PostClicks extends Model
{
    protected $table = 'post_clicks';

    protected $fillable = [
        'user_id',
        'type',
        'entity_id',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'user_id' => 'int',
        'type' => 'string',
        'entity_id' => 'int',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    const OUTSIDE = 'outside';
    const SHOP = 'shop';
    const HOSPITAL = 'hospitals';
    const CALL = 'call';
    const BOOK = 'book';
}
