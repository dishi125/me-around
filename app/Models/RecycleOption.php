<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RecycleOption extends Model
{
    protected $table = 'recycle_options';

    protected $fillable = [
        'value','created_at','updated_at'
    ];
}
