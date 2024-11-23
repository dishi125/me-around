<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Storage;
use Illuminate\Support\Facades\DB;

class UserCards extends Model
{
    protected $table = 'user_cards';
    protected $fillable = [
        'user_id','default_cards_id','default_cards_riv_id','created_at','updated_at', 'status', 'bank_id','is_applied', 'is_admin_read', 'card_level', 'active_level', 'love_count','card_level_status'
    ];

    const ASSIGN_STATUS = 0;
    const REQUESTED_STATUS = 1;
    const REQUEST_ACCEPT_STATUS = 2;
    const DEAD_CARD_STATUS = 3;
    const HIDE_DEAD_CARD_STATUS = 4;
    const SOLD_CARD_STATUS = 5;

    const NORMAL_STATUS = 'Normal';
    const HAPPY_STATUS = 'Happy';
    const SAD_STATUS = 'Sad';
    const DEAD_STATUS = 'Dead';

    const REGISTER_LOVE_COUNT = 12;
    const REFERRAL_LOVE_COUNT = 10;

    protected $appends = ['default_riv_detail', 'is_default_card', 'active_level_name'];

    public function cardLevels(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(UserCardLevel::class, 'user_card_id', 'id');
    }

    public function getIsDefaultCardAttribute(){
        $value = $this->attributes['default_cards_riv_id'] ?? '';
        $cardData = DB::table('default_cards_rives')->join('default_cards','default_cards.id','default_cards_rives.default_card_id')->where('default_cards_rives.id',$value)->select('default_cards.*')->first();
        return $this->attributes['is_default_card'] = ($cardData && ($cardData->name == "Default" || $cardData->name == DefaultCards::DEFAULT_CARD)) ? true : false;
    }

    public function getActiveLevelNameAttribute(){
        $value = $this->attributes['active_level'] ?? 1;
        $cardLvl = DB::table('card_levels')->whereId($value)->first();
        return $this->attributes['active_level_name'] = $cardLvl->level_name ?? '' ;
    }
    public function getDefaultRivDetailAttribute()
    {
        $value = $this->attributes['default_cards_riv_id'] ?? '';
        $default_riv_detail = NULL;
        if (!empty($value)) {
            $default_riv_detail = DefaultCardsRives::find($value);

        }
        return $this->attributes['default_riv_detail'] = $default_riv_detail;
    }
}
