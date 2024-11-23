<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductOrders extends Model
{
    protected $table = 'product_orders';
    protected $fillable = [
        'user_id', 'product_id', 'coin_amount', 'is_admin_read', 'created_at', 'updated_at'
    ];
}
