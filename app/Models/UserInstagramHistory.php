<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserInstagramHistory extends Model
{
    protected $table = 'user_intagram_history';
        
    const REQUEST_COIN = 0;
    const GIVE_COIN = 1;
    const REJECT_COIN = 2;
    const PENALTY_COIN = 3;

    protected $fillable = [
        'user_id','penalty_count','reward_count','reject_count', 'requested_at', 'status', 'request_count'
    ];

    protected $casts = [
        'id' => 'int',
        'user_id' => 'int',        
        'penalty_count' => 'int',        
        'reward_count' => 'int',        
        'reject_count' => 'int',        
        'request_count' => 'int',        
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = ['entity_type_id','entity_id','entity_name','phone','sns_link'];

    public function getEntityTypeIdAttribute()
    {
        $id = $this->attributes['user_id'];
        $user = User::find($id);
        $entity_type_id = $user->entityType->contains('entity_type_id', EntityTypes::SHOP) ? EntityTypes::SHOP : EntityTypes::HOSPITAL; 
                
        return $this->attributes['entity_type_id'] = $entity_type_id;
    }

    public function getEntityIdAttribute()
    {
        $id = $this->attributes['user_id'];
        $user = User::find($id);
        $entity_type_id = $user->entityType->contains('entity_type_id', EntityTypes::SHOP) ? EntityTypes::SHOP : EntityTypes::HOSPITAL; 
        $entity_relation = UserEntityRelation::where('entity_type_id', $entity_type_id)->where('user_id',$user->id)->first();     
        if($entity_type_id == EntityTypes::SHOP) {
            $shop = Shop::find($entity_relation->entity_id);
            $entity_id = $shop->id;
        }else{
            $hospital = Hospital::find($entity_relation->entity_id);
            $entity_id = $hospital->id;
        }
        return $this->attributes['entity_id'] = $entity_id;
    }
    public function getEntityNameAttribute()
    {
        $id = $this->attributes['user_id'];
        $user = User::find($id);
        $entity_type_id = $user->entityType->contains('entity_type_id', EntityTypes::SHOP) ? EntityTypes::SHOP : EntityTypes::HOSPITAL; 
        $entity_relation = UserEntityRelation::where('entity_type_id', $entity_type_id)->where('user_id',$user->id)->first();     
        if($entity_type_id == EntityTypes::SHOP) {
            $shop = Shop::find($entity_relation->entity_id);
            $name = $shop->shop_name;
        }else{
            $hospital = Hospital::find($entity_relation->entity_id);
            $name = $hospital->main_name;
        }
        return $this->attributes['entity_name'] = $name;
    }

    public function getPhoneAttribute()
    {
        $id = $this->attributes['user_id'];
        $user = UserDetail::where('user_id',$id)->first();
                
        return $this->attributes['phone'] = $user && $user->mobile;
    }
    public function getSnsLinkAttribute()
    {
        $id = $this->attributes['user_id'];
        $user = UserDetail::where('user_id',$id)->first();
                
        return $this->attributes['sns_link'] = $user && $user->sns_link;
    }
}
