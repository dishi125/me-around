<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserSavedHistory extends Model
{
    protected $table = 'user_saved_history';

    protected $dates = ['deleted_at'];

    protected $fillable = [
        'saved_history_type_id',
        'user_id',
        'entity_id',
        'is_like',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'saved_history_type_id' => 'int',
        'user_id' => 'int',
        'entity_id' => 'int',
        'is_like' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    const SHOP = 1;
    const HOSPITAL = 2;
    const COMMUNITY = 3;
    const REVIEWS = 4;
    const ASSOCIATION_COMMUNITY = 5;
}
