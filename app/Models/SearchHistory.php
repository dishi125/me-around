<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class SearchHistory extends Model
{
    protected $table = 'search_history';

    protected $fillable = [
        'user_id',
        'entity_type_id',
        'category_id',
        'keyword',
        'created_at',
        'updated_at'
    ];


    protected $casts = [
        'user_id' => 'int',
        'entity_type_id' => 'int',
        'category_id' => 'int',
        'keyword' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function getCreatedAtAttribute($date)
    {
        $date = new Carbon($date);
        return $date->format('Y.m.d');
    }

    public function getUpdatedAtAttribute($date)
    {
        $date = new Carbon($date);
        return $date->format('Y.m.d');
    }
}
