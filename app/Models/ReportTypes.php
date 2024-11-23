<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReportTypes extends Model
{
    protected $table = 'report_types';

    const SHOP = 1;
    const HOSPITAL = 2;
    const SHOP_USER = 3;
    const SHOP_PORTFOLIO = 4;
    const REVIEWS = 5;
    const REVIEWS_COMMENT = 6;
    const REVIEWS_COMMENT_REPLY = 7;
    const COMMUNITY = 8;
    const COMMUNITY_COMMENT = 9;
    const COMMUNITY_COMMENT_REPLY = 10;
    const SHOP_PLACE = 11;
    const HOSPITAL_PLACE = 12;
    const ASSOCIATION_COMMUNITY = 13;
    const ASSOCIATION_COMMUNITY_COMMENT = 14;
        
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
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
