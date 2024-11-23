<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\UserCards;

class CompleteCustomerDetails extends Model
{
    use SoftDeletes;
    protected $table = 'complete_customer_details';

    protected $dates = ['deleted_at'];

    protected $hidden = ['deleted_at'];

    protected $fillable = [
        'user_id',
        'customer_id',
        'revenue',
        'comment',
        'date',
        'entity_type_id',
        'entity_id',
        'status_id',
        'memo_completed',
        'created_at',
        'updated_at'
    ];

    protected $appends = ['entity_user_id','is_character_as_profile','user_applied_card'];

   /*  public function getDisplayBookingDateAttribute()
    {
        //$date = new Carbon($this->attributes['date']);
        return $this->attributes['display_booking_date'] = Carbon::parse($this->attributes['date'])->format('Y/m/d H:i A');
    } */

    public function getEntityUserIdAttribute()
    {
        $entityTypeId = $this->attributes['entity_type_id'];
        $user_id = NULL;
        if($entityTypeId == EntityTypes::HOSPITAL){
            $post = Post::find($this->attributes['entity_id']);
            if($post) {
                $user_id = !empty($post) ? $post->user_id : '';
            }else {
                $user_id = null;
            }
        }else {
            $shop = DB::table('shops')->whereId($this->attributes['entity_id'])->first();
            $user_id = !empty($shop) ? $shop->user_id : '';
        }

        return $this->attributes['entity_user_id'] = $user_id;

    }

    public function getUserAppliedCardAttribute()
    {
        $id = $this->attributes['user_id'] ?? 0;
        $card = [];
        if(!empty($id)){

            $card = getUserAppliedCard($id);
        }
        return $this->attributes['user_applied_card'] = $card;
    }

    public function getIsCharacterAsProfileAttribute()
    {
        $id = $this->attributes['user_id'] ?? 0;
        $is_character_as_profile = 1;
        if(!empty($id)){
            $userDetail = DB::table('users_detail')->where('user_id',$id)->first('is_character_as_profile');
            $is_character_as_profile = $userDetail ? $userDetail->is_character_as_profile : 1;
        }
        return $this->attributes['is_character_as_profile'] = $is_character_as_profile;
    }

    public function images(){
        return $this->hasMany(CustomerAttachment::class, 'entity_id', 'id')->where('type', CustomerAttachment::OUTSIDE);
    }
}
