<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShopReportHistory extends Model
{
    protected $table = 'shop_report_histories';

    protected $fillable = [
        'user_id',
        'shop_id',
        'description',
        'created_at',
        'updated_at'
    ];

    public function attachments() {
        return $this->hasMany(ShopReportAttachment::class, 'shop_report_id', 'id');
    }
}
