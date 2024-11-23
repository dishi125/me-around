<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Storage;
use Auth;

class DefaultCardsRives extends Model
{
    protected $table = 'default_cards_rives';
    protected $fillable = [
        'default_card_id','card_name','background_rive','character_rive','created_at','updated_at', 'usd_price', 'japanese_yen_price', 'chinese_yuan_price', 'korean_won_price','order', 'download_file', 'required_love_in_days', 'feeding_rive', 'background_thumbnail', 'character_thumbnail'
    ];

    protected $appends = ['is_owned','tab_name','background_rive_url','character_rive_url','background_rive_animation','character_rive_animation','download_file_url','feeding_rive_url','background_thumbnail_url','character_thumbnail_url'];

    const USD_SYMBOL = "$";
    const JAPANESE_SYMBOL = "¥";
    const CHINESE_SYMBOL = "元";
    const KOREAN_SYMBOL = "₩";

    const CARD_PRICES = [
        'usd_price' => self::USD_SYMBOL,
        'japanese_yen_price' => self::JAPANESE_SYMBOL,
        'chinese_yuan_price' => self::CHINESE_SYMBOL,
        'korean_won_price' => self::KOREAN_SYMBOL
    ];

    public function cardLevelStatusRive() {
        return $this->hasMany(CardStatusRives::class, 'card_id', 'id');
    }

    public function cardLevelStatusThumb() {
        return $this->hasMany(CardStatusThumbnails::class, 'card_id', 'id');
    }

    public function cardLevels() {
        return $this->hasMany(CardLevelDetail::class, 'main_card_id', 'id');
    }

    public function getIsOwnedAttribute()
    {
        $value = $this->attributes['default_cards_riv_id'] ?? $this->attributes['id'];
        $user = Auth::user();
        $is_owned = 0;
        if($user) {
            $is_owned = UserCards::where('default_cards_riv_id',$value)->where('user_id',$user->id)->count();
        }

        return $this->attributes['is_owned'] = $is_owned > 0;

    }

    public function getTabNameAttribute()
    {
        $value = $this->attributes['default_card_id'] ?? '';
        $tabName = '';
        if (!empty($value)) {
            $getName = DefaultCards::find($value);
            $tabName = !empty($getName) ? $getName->name : '';
        }
        return $this->attributes['tab_name'] = $tabName;
    }

    public function getFeedingRiveUrlAttribute()
    {
        $value = $this->attributes['feeding_rive'] ?? '';
        $file_url = '';
        if (!empty($value) && !filter_var($value, FILTER_VALIDATE_URL)) {
            $file_url = Storage::disk('s3')->url($value);
        }
        return $this->attributes['feeding_rive_url'] = $file_url;
    }

    public function getCharacterThumbnailUrlAttribute()
    {
        $value = $this->attributes['character_thumbnail'] ?? '';
        $file_url = '';
        if (!empty($value) && !filter_var($value, FILTER_VALIDATE_URL)) {
            $file_url = Storage::disk('s3')->url($value);
        }
        return $this->attributes['character_thumbnail_url'] = $file_url;
    }
    public function getBackgroundThumbnailUrlAttribute()
    {
        $value = $this->attributes['background_thumbnail'] ?? '';
        $file_url = '';
        if (!empty($value) && !filter_var($value, FILTER_VALIDATE_URL)) {
            $file_url = Storage::disk('s3')->url($value);
        }
        return $this->attributes['background_thumbnail_url'] = $file_url;
    }

    public function getBackgroundRiveUrlAttribute()
    {
        $value = $this->attributes['background_rive'] ?? '';
        $file_url = '';
        if (!empty($value) && !filter_var($value, FILTER_VALIDATE_URL)) {
            $file_url = Storage::disk('s3')->url($value);
        }
        return $this->attributes['background_rive_url'] = $file_url;
    }

    public function getCharacterRiveUrlAttribute()
    {
        $value = $this->attributes['character_rive'] ?? '';
        $file_url = '';
        if (!empty($value) && !filter_var($value, FILTER_VALIDATE_URL)) {
            $file_url = Storage::disk('s3')->url($value);
        }
        return $this->attributes['character_rive_url'] = $file_url;
    }

    public function getBackgroundRiveAnimationAttribute()
    {
        $value = $this->attributes['background_rive'] ?? '';
        $animation = '';
        if (!empty($value)) {
            $animation = pathinfo($value, PATHINFO_FILENAME);
        }
        return $this->attributes['background_rive_animation'] = $animation;
    }

    public function getCharacterRiveAnimationAttribute()
    {
        $value = $this->attributes['character_rive'] ?? '';
        $animation = '';
        if (!empty($value)) {
            $animation = pathinfo($value, PATHINFO_FILENAME);
        }
        return $this->attributes['character_rive_animation'] = $animation;
    }

    public function getDownloadFileUrlAttribute()
    {
        $value = $this->attributes['download_file'] ?? '';
        $file_url = '';
        if (!empty($value) && !filter_var($value, FILTER_VALIDATE_URL)) {
            $file_url = Storage::disk('s3')->url($value);
        }
        return $this->attributes['download_file_url'] = $file_url;
    }
}
