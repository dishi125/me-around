<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InstagramSubscribedPlan extends Model
{
    protected $table = "instagram_subscribed_plans";

    protected $fillable = [
        'user_id',
        'instagram_category_id',
        'instagram_category_option_id',
    ];

}
