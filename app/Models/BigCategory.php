<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class BigCategory extends Model
{
    use SoftDeletes;
    protected $table = 'big_categories';

    protected $fillable = [
        'name','status_id','created_at','updated_at','logo', 'order'
    ];

    protected $casts = [
        'name' => 'string',
        'status_id' => 'int',
        'logo' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    protected $dates = ['deleted_at'];

    protected $appends = ['logo_url'];

    public function getLogoUrlAttribute()
    {
        $value = $this->attributes['logo'];
        if (empty($value)) {
            return '';
        } else {
            if (!filter_var($value, FILTER_VALIDATE_URL)) {
                return Storage::disk('s3')->url($value);
            } else {
                return $value;
            }
        }
    }

}
