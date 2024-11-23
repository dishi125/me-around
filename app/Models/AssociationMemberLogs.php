<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssociationMemberLogs extends Model
{
    protected $table = 'association_member_logs';

    protected $fillable = [
        'associations_id',
        'user_id',
        'removed_by',
        'removed_type',
        'created_at',
        'updated_at'
    ];
}
