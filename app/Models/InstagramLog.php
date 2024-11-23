<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InstagramLog extends Model
{
    protected $table = "instagram_logs";

    const CONNECTED = 1;
    const DISCONNECTED = 2;
    const SOMETHINGDISCONNECTED = 3;

    protected $fillable = [
        'social_id',
        'instagram_id',
        'user_id',
        'shop_id',
        'social_name',
        'status',
        'is_admin_read',
        'mail_count',
    ];

//    protected $appends = ['status_name'];

/*    public function getStatusNameAttribute()
    {
        $value = $this->attributes['status'];
        $sname = "";
        if ($value == 1){
            $sname = "Connected";
        }
        else if ($value == 2){
            $sname = "Disconnected";
        }
        else if ($value == 3){
            $sname = "Something is disconnected";
        }
        return $sname;
    }*/

}
