<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShopDetailLanguage extends Model
{
    protected $table = 'shop_detail_languages';

    const SPECIALITY_OF = 'speciality_of';
    const TITLE = 'title';
    const SUBTITLE = 'subtitle';
    
    protected $fillable = [
        'shop_id', 'key','value','language_id','created_at','updated_at', 'entity_type_id'
    ];
}
