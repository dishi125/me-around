<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserFeeds extends Model
{
   use SoftDeletes;
   protected $table = 'user_feeds';


   protected $fillable = [
    'user_id','description','created_at','updated_at'
];

protected $casts = [
    'user_id' => 'int',
    'description' => 'string',
    'created_at' => 'datetime',
    'updated_at' => 'datetime'
];
}
