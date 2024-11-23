<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class UserBlockHistory extends Model
{
    protected $table = 'user_block_history';

    protected $fillable = [
        'user_id',
        'block_user_id',
        'is_block',
        'block_for',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'user_id' => 'int',
        'block_user_id' => 'int',
        'is_block' => 'boolean',
        'block_for' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    const CHAT = 'chat';
    const VIDEO_CALL = 'video_call';
    const COMMUNITY_POST = 'community_post';
    const ASSOCIATION_COMMUNITY_POST = 'association_community_post';
    const AUDIO_CALL = 'audio_call';

    


}
