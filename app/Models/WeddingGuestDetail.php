<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WeddingGuestDetail extends Model
{
    protected $table = 'wedding_guest_details';

    protected $fillable = [
        'wedding_id',
        'name',
        'pass',
        'description',
        'created_at',
        'updated_at'
    ];
}
