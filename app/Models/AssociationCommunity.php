<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use DB;

class AssociationCommunity extends Model
{
    use SoftDeletes;

    protected $table = 'association_communities';
    protected $dates = ['deleted_at'];

    protected $fillable = [
        'user_id','associations_id','category_id','title','description','views_count','country_code', 'created_at','updated_at', 'is_pin'
    ];

    protected $casts = [
        'user_id' => 'int',
        'category_id' => 'int',
        'title'=> 'string',
        'description'=> 'string',
        'views_count'=> 'int',
        'is_pin'=> 'boolean',
        'country_code'=> 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    protected $appends = ['category_name','user_name','time_difference','comments_count','is_saved_in_history','save_type_id','report_type_id','is_reported','block_type'];

    public function comments() {
        return $this->hasMany(AssociationCommunityComment::class, 'community_id', 'id')->where('parent_id',0);
    }

    public function images() {
        return $this->hasMany(AssociationCommunityImage::class, 'community_id', 'id');
    }

    public function association() {
        return $this->belongsTo(Association::class, 'associations_id');
    }

    public function user_detail() {
        return $this->hasOne(UserDetail::class, 'user_id', 'user_id');
        //return $this->belongsTo(UserDetail::class, 'user_id');
    }

    public function category() {
        return $this->hasOne(AssociationCategory::class, 'id', 'category_id');
    }

    public function associationLike() {
        return $this->hasMany(AssociationLikes::class, 'entity_id', 'id');
    }

    public function community_history() {
        return $this->hasMany(UserSavedHistory::class, 'entity_id', 'id')->where('saved_history_type_id',UserSavedHistory::ASSOCIATION_COMMUNITY);
    }

    public function getCommentsCountAttribute()
    {
        $value = $this->attributes['id'];
        $communityComments = DB::table('association_community_comments')->where('community_id',$value)->count();
        return $this->attributes['comments_count'] = $communityComments;

    }

    public function getIsSavedInHistoryAttribute(){
        $user = auth()->user();
        return $this->attributes['is_saved_in_history'] = ($user && $this->community_history()->where('user_id',$user->id)->count() > 0) ? true : false ;
    }

    public function getReportTypeIdAttribute(){
        return $this->attributes['report_type_id'] = ReportTypes::ASSOCIATION_COMMUNITY;
    }

    public function getSaveTypeIdAttribute(){
        return $this->attributes['save_type_id'] = SavedHistoryTypes::ASSOCIATION_COMMUNITY;
    }

    public function getIsReportedAttribute()
    {
        $user = auth()->user();
        if($user){
            $reported = DB::table('report_clients')->where('report_type_id',ReportTypes::ASSOCIATION_COMMUNITY)
                ->where('entity_id',$this->attributes['id'])
                ->where('user_id',$user->id)->count();
        }else{
            $reported = 0;
        }
        return $this->attributes['is_reported'] = $reported > 0 ? true : false;
    }

    public function getBlockTypeAttribute(){
        return $this->attributes['block_type'] = UserBlockHistory::ASSOCIATION_COMMUNITY_POST;
    }

    public function getCategoryNameAttribute()
    {
        $value = isset($this->attributes['category_id']) ? $this->attributes['category_id'] : NULL;
        if($value) {
            $category = AssociationCategory::find($value);
            return $this->attributes['category_name'] = !empty($category) ? $category->name : NULL;
        }

        return $this->attributes['category_name'] = '';

    }

    public function getUserNameAttribute()
    {
        $value = isset($this->attributes['user_id']) ? $this->attributes['user_id'] : NULL;
        if($value) {
            $user = UserDetail::where('user_id',$value)->first();
            return $this->attributes['user_name'] = !empty($user) ? $user->name : '';
        }

        return $this->attributes['user_name'] = '';
    }

    public function getTimeDifferenceAttribute()
    {
        $value = $this->attributes['created_at'];
        return $this->attributes['time_difference'] = timeAgo($value);

    }
}
