<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ChallengeAdmin extends Model
{
    protected $table = "challenge_admin";

    protected $fillable = [
        'image',
        'bio',
    ];

    public function getImageAttribute()
    {
        $value = $this->attributes['image'];
        if (empty($value)) {
            return $this->attributes['image'] = '';
        } else {
            if (!filter_var($value, FILTER_VALIDATE_URL)) {
                return $this->attributes['image'] = Storage::disk('s3')->url($value);
            } else {
                return $this->attributes['image'] = $value;
            }
        }
    }
}
