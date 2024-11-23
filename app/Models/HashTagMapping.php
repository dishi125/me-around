<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HashTagMapping extends Model
{
    protected $table = 'hash_tag_mappings';

    protected $fillable = [
        'hash_tag_id',
        'entity_id',
        'entity_type_id',
        'created_at',
        'updated_at'
    ];
}
