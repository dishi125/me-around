<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class WeddingMetaData extends Model
{
    protected $table = 'wedding_meta_data';

    protected $fillable = [
        'wedding_id',
        'meta_key',
        'meta_value',
        'created_at',
        'updated_at'
    ];

    public function getMetaValueAttribute()
    {
        $meta_key = $this->attributes['meta_key']  ?? '';
        $fields = Wedding::FIELD_LIST;
        $currentField = $fields[$meta_key] ?? '';
        $value = $this->attributes['meta_value'];
        if($currentField){
            if($currentField['type'] == 'repeater'){
                return $this->attributes['meta_value'] = unserialize($value);
            }elseif($currentField['type'] == 'file'){
                if($currentField['is_multiple']){
                    return $this->attributes['meta_value'] = collect(unserialize($value))->map(function ($image) {
                            return Storage::disk('s3')->url($image);
                        });
                }else{
                    return $this->attributes['meta_value'] = Storage::disk('s3')->url($value);
                }
            }
        }
        return $this->attributes['meta_value'] = $value;
    }
    
}
