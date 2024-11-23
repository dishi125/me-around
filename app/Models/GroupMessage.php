<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class GroupMessage extends Model
{
    protected $table = "group_messages";

    protected $fillable = [
        'from_user',
        'type',
        'message',
        'country',
        'created_at',
        'updated_at',
        'is_admin_read',
    ];

    public function getMessageAttribute(){
        $type = $this->attributes['type'];
        return ($type=="file") ? url('chat-root/'.$this->attributes['message']) : $this->attributes['message'];
    }

}
