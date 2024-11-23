<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssociationLikes extends Model
{
   protected $table = 'association_likes';

   protected $fillable = [
        'entity_id',
        'type',
        'user_id',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'entity_id' => 'int',
        'type' => 'string',
        'user_id' => 'int',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    const TYPE_ASSOCIATION = 'association';
    const TYPE_ASSOCIATION_COMMUNITY = 'community';
    const TYPE_COMMUNITY_COMMENT = 'comment';
}
