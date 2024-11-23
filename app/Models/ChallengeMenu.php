<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChallengeMenu extends Model
{
    protected $table = "challenge_menus";

    protected $fillable = [
        'eng_menu',
        'kr_menu',
    ];
}
