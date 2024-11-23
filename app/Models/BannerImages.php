<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class BannerImages extends Model
{
    use SoftDeletes;
    protected $table = 'banner_images';

    protected $dates = ['deleted_at'];

    protected $fillable = [
        'image','banner_id','link','slide_duration','order','from_date','to_date','created_at','updated_at'
    ];

    protected $casts = [
        'image' => 'string',
        'banner_id' => 'int',
        'slide_duration' => 'int',
        'order' => 'int',
        'link' => 'string',
        'from_date' => 'date',
        'to_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    protected $appends = ['image_url'];

    public function getImageUrlAttribute()
    {
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

    public function getFromDateAttribute($date)
    {
        if($date){
            $date = new Carbon($date);
            return $date->format('Y-m-d');
        }
        return $date;
    }

    public function getToDateAttribute($date)
    {
        if($date){
            $date = new Carbon($date);
            return $date->format('Y-m-d');
        }
        return $date;
    }
}
