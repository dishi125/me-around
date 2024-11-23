<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssociationUsers extends Model
{
    protected $table = 'association_users';

    protected $fillable = [
        'association_id','type','user_id'
    ];

    protected $casts = [
        'association_id' => 'int',
        'type' => 'string',
        'user_id' => 'int',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    const PRESIDENT= 'president';
    const MANAGER= 'manager';
    const MEMBER= 'member';
    const SUPPORTER = 'supporter';
    const KICKED_MEMBER= 'kicked_members';
    const MANAGERCOUNT= 10;

    protected $appends = ['user_info'];

    public function getUserInfoAttribute()
    {
        $user_id = $this->attributes['user_id'] ?? 0;
        if($user_id){
        $userInfo = UserDetail::where('user_id',$user_id)->select('id','name','mobile','avatar','language_id','is_character_as_profile')->first();
            return $this->attributes['user_info'] = !empty($userInfo) ? $userInfo : (object)[];
        }else{
            return $this->attributes['user_info'] = (object)[];
        }
    }
}
