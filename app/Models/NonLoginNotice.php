<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NonLoginNotice extends Model
{
    protected $table = 'non_login_notices';
    protected $fillable = [
        'notify_type',  'title', 'sub_title', 'entity_type_id', 'entity_id', 'user_id', 'is_read', 'created_at','updated_at'
    ];
}
