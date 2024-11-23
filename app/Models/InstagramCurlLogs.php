<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InstagramCurlLogs extends Model
{
    protected $table = 'instagram_curl_logs';
    protected $fillable = [
        'id',
        'shop_id',
        'social_id',
        'instagram_id',
        'post_data',
        'is_error',
        'created_at',
        'updated_at'
    ];

}
