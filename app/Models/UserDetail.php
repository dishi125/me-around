<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use App\Models\PostLanguage;
use App\Models\UserCards;
use DB;

class UserDetail extends Model
{
    use SoftDeletes;
    protected $table = 'users_detail';

    protected $dates = ['deleted_at'];

    const INSTAGRAM = 'instagram';
    const FACEBOOK = 'facebook';
    const WEIBO = 'weibo';

    const POINTS_40 = 40;

    protected $fillable = [
       'phone_code','hide_popup', 'package_plan_id','last_plan_update','language_id','plan_expire_date','recommended_code','recommended_by','country_id','manager_id','business_approved_by','name','mobile','gender','avatar','user_id','device_type_id','device_id','device_token', 'sns_type', 'sns_link', 'created_at','updated_at','points_updated_on','points','level','count_days','card_number', 'is_outside','is_character_as_profile','is_referral_read','mbti','supporter_type','promot_insta_around'
    ];


    protected $casts = [
        'name' => 'string',
        'recommended_code' => 'string',
        'recommended_by' => 'int',
        'mobile' => 'string',
        'gender' => 'string',
        'avatar' => 'string',
        'phone_code' => 'string',
        'user_id' => 'int',
        'package_plan_id' => 'int',
        'last_plan_update' => 'date',
        'hide_popup' => 'int',
        'language_id' => 'int',
        'plan_expire_date' => 'date',
        'country_id' => 'int',
        'manager_id' => 'int',
        'business_approved_by' => 'int',
        'device_type_id' => 'int',
        'device_id' => 'string',
        'device_token' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    protected $appends = ['language_name','level_name','user_points','user_applied_card'];

    /*public function getUserOwnCardAttribute(){

        $user_own_card = [];
        $value = $this->attributes['user_id'] ?? 0;
        if(!empty($value)){
            $user_own_card = DefaultCardsRives::join('user_cards', 'user_cards.default_cards_riv_id', 'default_cards_rives.id')
                ->select('user_cards.*','default_cards_rives.id as default_cards_rives_id','default_cards_rives.background_rive','default_cards_rives.character_rive','default_cards_rives.download_file')
                ->where('user_cards.user_id',$value)->whereIn('user_cards.status',[UserCards::SOLD_CARD_STATUS, UserCards::REQUESTED_STATUS,UserCards::ASSIGN_STATUS,UserCards::DEAD_CARD_STATUS])->orderBy('user_cards.is_applied','DESC')->orderBy('user_cards.created_at','DESC')->get();
        }

        return $this->attributes['user_own_card'] = $user_own_card;
    }*/

    public function parentFollowersDetail(){
        return $this->belongsTo(UserDetail::class,'recommended_by','user_id')->with('parentFollowersDetail');
    }

    public function followersDetail(){
        return $this->hasMany(UserDetail::class,'recommended_by','user_id')->with('followersDetail');
    }

    public function getUserAppliedCardAttribute()
    {
        $id = $this->attributes['user_id'] ?? 0;
        $card = [];
        if(!empty($id)){
            $card = DefaultCardsRives::join('user_cards', 'user_cards.default_cards_riv_id', 'default_cards_rives.id')
                ->leftjoin('card_level_details', function ($join) {
                    $join->on('card_level_details.main_card_id', '=', 'default_cards_rives.id')
                        ->whereRaw('card_level_details.card_level = user_cards.active_level');
                })
                ->select('user_cards.*',
                    'default_cards_rives.id as default_cards_rives_id',
                    'default_cards_rives.default_card_id',
                    'default_cards_rives.background_rive',
                    'default_cards_rives.character_rive',
                    'user_cards.active_level',
                    'user_cards.love_count',
                    'default_cards_rives.download_file',
                    'default_cards_rives.feeding_rive',
                    'default_cards_rives.background_thumbnail',
                    'default_cards_rives.character_thumbnail',
                    DB::raw('(CASE
                        WHEN user_cards.active_level != 1 THEN card_level_details.usd_price
                        ELSE default_cards_rives.usd_price
                    END) AS usd_price'),
                    DB::raw('(CASE
                        WHEN user_cards.active_level != 1 THEN card_level_details.japanese_yen_price
                        ELSE default_cards_rives.japanese_yen_price
                    END) AS japanese_yen_price'),
                    DB::raw('(CASE
                        WHEN user_cards.active_level != 1 THEN card_level_details.chinese_yuan_price
                        ELSE default_cards_rives.chinese_yuan_price
                    END) AS chinese_yuan_price'),
                    DB::raw('(CASE
                        WHEN user_cards.active_level != 1 THEN card_level_details.korean_won_price
                        ELSE default_cards_rives.korean_won_price
                    END) AS korean_won_price')
                )
                ->where(['user_cards.user_id' => $id,'user_cards.is_applied' => 1])
                ->first();

            if($card){
                $music_files = CardMusic::where('card_id',$card->default_cards_riv_id)->get();
                $card->music_files = $music_files->pluck('music_file_url');
            }
        }
        return $this->attributes['user_applied_card'] = $card;
    }

    public function getUserPointsAttribute(){

        $user_points = NULL;
        if(!empty($this->attributes['points'])){
            $points = $this->attributes['points'];
            $startPoints = DB::table('levels')->where('points','<',$points)->orderBy('id','desc')->limit(1)->first('points');
            $endPoints = DB::table('levels')->where('points','>',$points)->orderBy('id','asc')->limit(1)->first('points');

            $start = !empty($startPoints) ? (int)$startPoints->points : 0;
            $end = !empty($endPoints) ? (int)$endPoints->points : 0;
            $per = ($end - $start);
            $percentage = ((($points - $start)/$per) * 100);

            $user_points = [
                'start' => $start,
                'end' => $end,
                'percentage' => $percentage
            ];
        }

        return $this->attributes['user_points'] = $user_points;
    }

    public function getLevelNameAttribute()
    {
        $value = $this->attributes['level'] ?? '';
        $levelName = NULL;
        if (!empty($value)) {
            $getLevel = DB::table('levels')->select('name')->where('id',$value)->first();
            $levelName = !empty($getLevel) ? $getLevel->name : NULL;
        }
        return $this->attributes['level_name'] = $levelName;
    }


    public function getAvatarAttribute()
    {
        $value = $this->attributes['avatar'];
        if (empty($value)) {
            return $this->attributes['avatar'] = asset('img/avatar/avatar-1.png');
        } else {
            if (!filter_var($value, FILTER_VALIDATE_URL)) {
                return $this->attributes['avatar'] = Storage::disk('s3')->url($value);
            } else {
                return $this->attributes['avatar'] = $value;
            }
        }
    }

    public function getPlanExpireDateAttribute($date)
    {
        $date = new Carbon($date);
        return $date->format('M d');
    }

    public function getLanguageNameAttribute()
    {
        if(isset($this->attributes['language_id'])) {
            $id = $this->attributes['language_id'];
            $langauge = PostLanguage::find($id);
        }

        return $this->attributes['language_name'] = isset($langauge) ? $langauge->name : '';
    }

}
