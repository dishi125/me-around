<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class QrCode extends Model
{
    protected $table = "qr_codes";

    protected $fillable = [
        'title',
        'link',
        'image',
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

    public function getCreatedAtAttribute()
    {
        $value = $this->attributes['created_at'];
        if (empty($value)) {
            return $this->attributes['created_at'] = '';
        } else {
            return Carbon::parse($value)->format("Y/m/d");
        }
    }

}
