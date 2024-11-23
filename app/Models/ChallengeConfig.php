<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ChallengeConfig extends Model
{
    protected $table = "challenge_configs";

    protected $fillable = [
        'key',
        'value',
    ];

    const SIGNUP_EMAIL = "signup_email";
    const VERIFICATION_EMAIL = "new_verification_post";

    public function getKeyAttribute()
    {
        $value = $this->attributes['key'];
        return $this->attributes['key'] = Str::ucfirst(str_replace('_', ' ', $value));
    }
}
