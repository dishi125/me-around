<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReportGroupMessage extends Model
{
    protected $table = "report_group_messages";

    protected $fillable = [
        'reporter_user_id',
        'message_id',
        'is_admin_read',
    ];

}
