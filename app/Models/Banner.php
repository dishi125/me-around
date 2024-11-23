<?php

namespace App\Models;

use App\Models\BannerImages;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Banner extends Model
{
    use SoftDeletes;
    protected $table = 'banners';

    protected $dates = ['deleted_at'];
    protected $fillable = [
        'section','country_code','entity_type_id','category_id','is_random','created_at','updated_at'
    ];

    protected $appends = ['banner_images'];

    protected $casts = [
        'section' => 'string',
        'country_code' => 'string',
        'entity_type_id' => 'int',
        'is_random' => 'boolean',
        'category_id' => 'int',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function getBannerImagesAttribute()
    {
        $value = $this->attributes['id'];
        $images = [];
        $bannerImages = BannerImages::where('banner_id',$value)->orderBy('order')->get();

        if (empty($bannerImages)) {
            return $this->attributes['banner_images'] = $images;
        } else {            
            foreach($bannerImages as $val){
                if (!filter_var($value, FILTER_VALIDATE_URL)) {
                    $images[$val->id] = Storage::disk('s3')->url($val->image);
                } else {
                    $images[$val->id] = $val->image;
                    // array_push($images,$val->image); 
                }
            }
        }
        return $this->attributes['banner_images'] = $images;        
    }

    public function bannerDetail()
    {
        return $this->hasMany(BannerImages::class, 'banner_id', 'id')->orderBy('order')->orderBy('id','desc');
    }
}
