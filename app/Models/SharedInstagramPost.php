<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class SharedInstagramPost extends Model
{
    protected $table = 'shared_instagram_posts';
        
    protected $fillable = [
        'shop_id','shop_image_id','shop_user_id'
    ];

    protected $casts = [
        'id' => 'int',
        'shop_id' => 'int',        
        'shop_image_id' => 'int',        
        'shop_user_id' => 'int',        
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function getCreatedAtAttribute($date)
    {
        $date = new Carbon($date);
        return $date->format('d-m-Y H:i');
    }

    public function getUpdatedAtAttribute($date)
    {
        $date = new Carbon($date);
        return $date->format('d-m-Y H:i');
    }
}
