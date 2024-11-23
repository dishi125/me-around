<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class UserHidePopupImage extends Model
{
    protected $table = 'user_hide_popup_image';

    protected $fillable = [
        'user_id','banner_image_id','created_at','updated_at'
    ];

    protected $casts = [
        'user_id' => 'int',
        'banner_image_id' => 'int',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];
}
