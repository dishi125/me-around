<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class ReviewImages extends Model
{
    use SoftDeletes;
    const BEFORE = 'before';
    const AFTER = 'after';
    protected $table = 'review_images';

    protected $dates = ['deleted_at'];

    protected $fillable = [
        'review_id',
        'type',
        'image',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'review_id' => 'int',
        'type' => 'string',
        'image' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
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
