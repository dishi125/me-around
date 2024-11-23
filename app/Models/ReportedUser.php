<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReportedUser extends Model
{
    protected $table = "reported_users";

    protected $fillable = [
        'reporter_user_id',
        'reported_user_id',
        'reason',
        'is_admin_read',
    ];

    public function reporter_user_detail() {
        return $this->hasOne(UserDetail::class, 'user_id', 'reporter_user_id');
    }

    public function reported_user_detail() {
        return $this->hasOne(UserDetail::class, 'user_id', 'reported_user_id');
    }

    public function attachments() {
        return $this->hasMany(ReportedUserAttachment::class, 'user_report_id', 'id');
    }
}
