<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use DB;

class Association extends Model
{
    use SoftDeletes;
    protected $table = 'associations';
    protected $dates = ['deleted_at'];  

    const SELF= 'self';
    const KICK= 'kick';
    const REMOVE= 'remove';
    const TYPE_PUBLIC = 'public';
    const TYPE_PRIVATE = 'private';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'association_name', 'country_id','code','type', 'description'
    ];

    protected $casts = [
        'association_name' => 'string',
        'country_id' => 'int',
        'code' => 'int',
        'type' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    protected $appends = ['user_type','is_joined'];

    public function associationImage() {
        return $this->hasMany(AssociationImage::class, 'associations_id', 'id')->orderBy('created_at','ASC');
    }

    public function associationUsers() {
        return $this->hasMany(AssociationUsers::class, 'association_id', 'id');
    }

    public function associationCommunity() {
        return $this->hasMany(AssociationCommunity::class, 'associations_id', 'id');
    }

    public function associationCategory() {
        return $this->hasMany(AssociationCategory::class, 'associations_id', 'id')->orderBy('order','ASC');
    }

    public function getUserTypeAttribute(){
        $user = auth()->user();
        $data = ($user) ? $this->associationUsers()->where('user_id',$user->id)->first() : NULL;
        return $this->attributes['user_type'] = !empty($data) ? $data->type : AssociationUsers::MEMBER; 
    }

    public function getIsJoinedAttribute(){
        $user = auth()->user();
        return $this->attributes['is_joined'] = ($user && $this->associationUsers()->where('user_id',$user->id)->first()) ? true : false;
    }
}
