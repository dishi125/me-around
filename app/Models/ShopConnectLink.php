<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShopConnectLink extends Model
{
    protected $table = 'shop_connect_links';

    protected $fillable = [
        'shop_id',
        'is_expired',
        'created_at',
        'updated_at'
    ];
}
