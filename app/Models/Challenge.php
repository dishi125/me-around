<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Challenge extends Model
{
    protected $table = "challenges";

    protected $fillable = [
        'title',
        'verify_time',
        'deal_amount',
        'description',
        'date',
        'start_date',
        'end_date',
        'is_period_challenge',
        'category_id',
        'challenge_thumb_id',
        'depositor_name',
        'user_id',
    ];

    protected $appends = ['challenge_thumb_url'];

    public function challengeimages() {
        return $this->hasMany(ChallengeImages::class, 'challenge_id', 'id')->orderBy('created_at','ASC');
    }

    public function challengedays() {
        return $this->hasMany(ChallengeDay::class, 'challenge_id', 'id')->orderBy('created_at','ASC');
    }

    public function getChallengeThumbUrlAttribute()
    {
        $value = $this->attributes['challenge_thumb_id'];
        if (empty($value)) {
            return $this->attributes['challenge_thumb_url'] = '';
        } else {
            $thumb = ChallengeThumb::where('id',$value)->pluck('image')->first();
            if (!filter_var($thumb, FILTER_VALIDATE_URL)) {
                return $this->attributes['challenge_thumb_url'] = Storage::disk('s3')->url($thumb);
            } else {
                return $this->attributes['challenge_thumb_url'] = $thumb;
            }
        }
    }

}
