<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChallengeVerify extends Model
{
    protected $table = "challenge_verify";

    protected $fillable = [
        'challenge_id',
        'user_id',
        'date',
        'is_verified',
        'is_rejected',
        'is_admin_read',
    ];

    public function verifiedimages()
    {
        return $this->hasMany(ChallengeVerifyImage::class, 'challenge_verify_id', 'id')->orderBy('created_at','DESC');
    }
}
