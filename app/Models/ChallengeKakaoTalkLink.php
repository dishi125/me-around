<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChallengeKakaoTalkLink extends Model
{
    protected $table = "challenge_kakao_talk_links";

    protected $fillable = [
        'link',
    ];
}
