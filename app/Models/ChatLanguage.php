<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatLanguage extends Model
{
    protected $table = "chat_languages";

    protected $fillable = [
        'user_id',
        'type',
        'language',
    ];

}
