<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminMessage extends Model
{
    protected $table = 'admin_messages';

    protected $fillable = [
        'from_user ',
        'to_user',
        'send_by',
        'type',
        'message',
        'created_at',
        'updated_at',
        'is_read'
    ];

    //protected $appends = ['image'];

   /*  public function getImageAttribute()
    {
        return $this->attributes['image'] = asset('img/avatar/avatar-1.png');
    } */
}
