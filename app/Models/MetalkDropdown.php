<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MetalkDropdown extends Model
{
    protected $table = 'metalk_dropdowns';
    
    protected $fillable = [
        'key','label', 'option_id', 'created_at','updated_at'
    ];
}
