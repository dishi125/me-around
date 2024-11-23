<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LinkChallengeSetting extends Model
{
    protected $table = "link_challenge_settings";

    protected $fillable = [
        'title',
        'link',
    ];
}
