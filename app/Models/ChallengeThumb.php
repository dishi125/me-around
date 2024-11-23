<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ChallengeThumb extends Model
{
    protected $table = "challenge_thumbs";

    protected $fillable = [
        'image',
        'order',
        'challenge_type',
        'category_id',
    ];

    protected $appends = ['image_url'];

    public function getImageUrlAttribute()
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
