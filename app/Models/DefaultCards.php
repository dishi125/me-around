<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DefaultCards extends Model
{
    protected $table = 'default_cards';
    protected $fillable = [
        'name','start','end','created_at','updated_at'
    ];

    const Level_400 = 400;
    const DEFAULT_CARD = 'default';

    public function defaultCardsRiv()
    {
        return $this->hasMany(DefaultCardsRives::class, 'default_card_id', 'id')->orderBy('order','asc');
    }
    

}
