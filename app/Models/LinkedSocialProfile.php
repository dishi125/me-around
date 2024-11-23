<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LinkedSocialProfile extends Model
{
    protected $table = 'linked_social_profiles';
    protected $fillable = [
        'social_id',
        'social_type',
        'user_id',
        'shop_id',
        'access_token',
        'token_refresh_date',
        'social_name',
        'is_valid_token',
        'created_at',
        'updated_at',
        'invalid_token_date',
        'mail_count'
    ];

    const Facebook = 'facebook';
    const Instagram = 'instagram';
    const Apple = 'apple';

    const CRON_TIME = [
        'everyTwoHours' => "12 time per a day",
        'hourly' => "24 time per a day",
        'everyThirtyMinutes' => "2 time per a hr",
        'everyTenMinutes' => "6 time per a hr",
        'everyFiveMinutes' => "12 time per a hr",
        'everyMinute' => "60 time per a hr (once per 1 min)",
    ];
}
