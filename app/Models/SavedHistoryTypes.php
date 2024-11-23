<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SavedHistoryTypes extends Model
{
    protected $table = 'saved_history_types';
        
    const SHOP = 1;
    const HOSPITAL = 2;
    const COMMUNITY = 3;
    const REVIEWS = 4;
    const ASSOCIATION_COMMUNITY = 5;

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
