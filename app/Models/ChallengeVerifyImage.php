<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ChallengeVerifyImage extends Model
{
    protected $table = "challenge_verify_images";

    protected $fillable = [
        'challenge_verify_id',
        'image',
    ];

    protected $appends = ['image_url'];

    public function getImageUrlAttribute(){
        $value = $this->attributes['image'];
        if (empty($value)) {
            return $this->attributes['image_url'] = '';
        } else {
            if (!filter_var($value, FILTER_VALIDATE_URL)) {
                return $this->attributes['image_url'] = Storage::disk('s3')->url($value);
            } else {
                return $this->attributes['image_url'] = $value;
            }
        }
    }
}
