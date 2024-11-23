<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;


class MetalkOptions extends Model
{
    protected $table = 'metalk_options';

    const FILE = 'file';
    const TEXT = 'text';
    const DROPDOWN = 'dropdown';

    const THEME_OPTIONS = 1;
    const EXPLANATION = 2;
    
    protected $fillable = [
        'key','label','value','type','created_at','updated_at', 'options_type', 'is_different_lang'
    ];

    protected $appends = ['file_url'];

    public function getFileUrlAttribute()
    {
        $value = $this->attributes['value'];
        $type = $this->attributes['type'];
        if (empty($value)) {
            return $this->attributes['file_url'] = '';
        } 
           
        if ($type == MetalkOptions::FILE && !filter_var($value, FILTER_VALIDATE_URL)) {
            return $this->attributes['file_url'] = Storage::disk('s3')->url($value);
        }
    }

    public function optionsData(){
        return $this->hasMany(MetalkDropdown::class,'metalk_options_id', 'id');            
    }

    public function languageData(){
        return $this->hasMany(MetalkOptionLanguage::class,'metalk_options_id', 'id');            
    }
    
    public function dropdown() 
    {
       // return $this->belongsToMany(MetalkDropdown::class,'metalk_dropdowns','metalk_options_id', 'id');            
        return $this->belongsToMany(MetalkDropdown::class,'metalk_dropdowns','metalk_options_id','id')->withTimestamps();
    }

}
