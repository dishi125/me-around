<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;
use App\Models\Category;
use App\Models\ReportTypes;
use App\Models\Shop;
use App\Models\Hospital;
use App\Models\ShopImages;
use App\Models\UserDetail;
use App\Models\Community;
use App\Models\UserEntityRelation;
use App\Models\EntityTypes;
use App\Models\ReviewCommentReply;
use App\Models\ReviewComments;
use App\Models\Reviews;
use App\Models\CommunityComments;
use App\Models\CommunityCommentReply;

class ReportClient extends Model
{
    use SoftDeletes;
    protected $table = 'report_clients';

    protected $dates = ['deleted_at'];

    protected $fillable = [
        'report_type_id',
        'user_id',
        'reported_user_id',
        'entity_id',
        'category_id', 
        'status_count', 
        'created_at',
        'updated_at',
        'is_admin_read'
    ];


    protected $casts = [
        'report_type_id' => 'int',
        'user_id' => 'int',
        'reported_user_id' => 'int',
        'entity_id' => 'int',
        'category_id' => 'int',
        'status_count' => 'int',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    protected $appends = ['display_created_at', 'report_item_name','report_item_category','category_name','report_count'];

    public function getCategoryNameAttribute()
    {
        $value = isset($this->attributes['category_id']) ? $this->attributes['category_id'] : null;
        if($value) {
            $category = Category::find($value);    
            return $this->attributes['category_name'] =  !empty($category) ? $category->name : '';
        }
        return $this->attributes['category_name'] = '';        
    }

    public function getReportItemNameAttribute()
    {
        $report_type_id = isset($this->attributes['report_type_id']) ? $this->attributes['report_type_id'] : null;
        $entity_id = isset($this->attributes['entity_id']) ? $this->attributes['entity_id'] : null;

        if($report_type_id && $entity_id) {
            if($report_type_id == ReportTypes::SHOP) {
                $data = Shop::find($entity_id);   
                return $this->attributes['report_item_name'] =  !empty($data) ? $data->shop_name : ''; 
            }else if($report_type_id == ReportTypes::HOSPITAL){
                $data = Hospital::find($entity_id); 
                return $this->attributes['report_item_name'] =  !empty($data) ? $data->main_name : '';
            }else if($report_type_id == ReportTypes::SHOP_PORTFOLIO){
                $shopImage = ShopImages::find($entity_id); 
                $data = $shopImage ? Shop::find($shopImage->shop_id) : '';
                return $this->attributes['report_item_name'] =  !empty($data) ? $data->shop_name : '';
            }else if($report_type_id == ReportTypes::SHOP_USER){
                $data = UserDetail::where('user_id',$entity_id)->first(); 
                return $this->attributes['report_item_name'] =  !empty($data) ? $data->name : '';
            }else if($report_type_id == ReportTypes::COMMUNITY){
                $data = Community::find($entity_id); 
                return $this->attributes['report_item_name'] =  !empty($data) ? $data->title : '';
            } else if($report_type_id == ReportTypes::COMMUNITY_COMMENT){
                $data = CommunityComments::find($entity_id); 
                $userId = !empty($data) ? $data->user_id : 0;
            }else if($report_type_id == ReportTypes::COMMUNITY_COMMENT_REPLY){
                $data = CommunityCommentReply::find($entity_id); 
                $userId = !empty($data) ? $data->user_id : 0;
                $user = User::find($userId);
                return $this->attributes['report_item_name'] =  !empty($user) ? $user->name : '';
            }else if($report_type_id == ReportTypes::REVIEWS){
                $data = Reviews::find($entity_id); 
                $userId = !empty($data) ? $data->user_id : 0;
                $user = User::find($userId);
                return $this->attributes['report_item_name'] =  !empty($data) ? $data->review_comment : '';
            }else if($report_type_id == ReportTypes::REVIEWS_COMMENT){
                $data = ReviewComments::find($entity_id); 
                $userId = !empty($data) ? $data->user_id : 0;
                $user = User::find($userId);
                return $this->attributes['report_item_name'] =  !empty($user) ? $user->name : '';
            }else if($report_type_id == ReportTypes::REVIEWS_COMMENT_REPLY){
                $data = ReviewCommentReply::find($entity_id); 
                $userId = !empty($data) ? $data->user_id : 0;
                $user = User::find($userId);
                return $this->attributes['report_item_name'] =  !empty($user) ? $user->name : '';
            }else if($report_type_id == ReportTypes::SHOP_PLACE){
                $data = RequestedCustomer::find($entity_id); 
                return $this->attributes['report_item_name'] =  !empty($data) ? $data->comment : '';
            }else if($report_type_id == ReportTypes::HOSPITAL_PLACE){
                $data = RequestedCustomer::find($entity_id); 
                return $this->attributes['report_item_name'] =  !empty($data) ? $data->comment : '';
            }elseif($report_type_id == ReportTypes::ASSOCIATION_COMMUNITY){
                $data = AssociationCommunity::find($entity_id); 
                return $this->attributes['report_item_name'] = !empty($data) ? $data->title : '';
            }elseif($report_type_id == ReportTypes::ASSOCIATION_COMMUNITY_COMMENT){
                $data = AssociationCommunityComment::find($entity_id); 
                return $this->attributes['report_item_name'] = !empty($data) ? $data->comment : '';
            }else{
                return $this->attributes['report_item_name'] =  '';
            }
        }
        return $this->attributes['report_item_name'] = '';        
    }
    public function getReportItemCategoryAttribute()
    {
        $report_type_id = isset($this->attributes['report_type_id']) ? $this->attributes['report_type_id'] : null;
        $entity_id = isset($this->attributes['entity_id']) ? $this->attributes['entity_id'] : null;

        if($report_type_id && $entity_id) {
            if($report_type_id == ReportTypes::SHOP) {
                $data = Shop::find($entity_id); 
                $category = Category::find($data->category_id);  
                return $this->attributes['report_item_category'] =  !empty($category) ? $category->name : ''; 
            }else if($report_type_id == ReportTypes::HOSPITAL){
                $data = Hospital::find($entity_id); 
                $category = Category::find($data->category_id);  
                return $this->attributes['report_item_category'] =  !empty($category) ? $category->name : '';
            }else if($report_type_id == ReportTypes::SHOP_PORTFOLIO){
                $shopImage = ShopImages::find($entity_id); 
                $data = $shopImage ? Shop::find($shopImage->shop_id) : '';
                $category = $data ? Category::find($data->category_id) : '';  
                return $this->attributes['report_item_category'] =  !empty($category) ? $category->name : '';
            }else if($report_type_id == ReportTypes::COMMUNITY){
                $data = Community::find($entity_id); 
                $category = Category::find($data->category_id);  
                return $this->attributes['report_item_category'] =  !empty($category) ? $category->name : '';
            }else if($report_type_id == ReportTypes::SHOP_PLACE){
                $customer = RequestedCustomer::find($entity_id); 
                $data = Shop::find($customer->entity_id); 
                $category = Category::find($data->category_id);  
                return $this->attributes['report_item_category'] =  !empty($category) ? $category->name : ''; 
            }else if($report_type_id == ReportTypes::HOSPITAL_PLACE){
                $customer = RequestedCustomer::find($entity_id); 
                $data = Hospital::join('posts','posts.hospital_id','hospitals.id')->where('posts.id',$customer->entity_id)->first();
                $category = Category::find($data->category_id);  
                return $this->attributes['report_item_category'] =  !empty($category) ? $category->name : '';
            }else{
                return $this->attributes['report_item_category'] =  '';
            }
        }
        return $this->attributes['report_item_category'] = '';        
    }

    public function getReportCountAttribute()
    {
        $report_type_id = isset($this->attributes['report_type_id']) ? $this->attributes['report_type_id'] : null;
        $entity_id = isset($this->attributes['entity_id']) ? $this->attributes['entity_id'] : null;

        if($report_type_id && $entity_id) {
            if($report_type_id == ReportTypes::SHOP) {
                $user = UserEntityRelation::where('entity_type_id',EntityTypes::SHOP)
                                            ->where('entity_id',$entity_id)->first();
                $userId = !empty($user) ? $user->user_id : 0;
            }else if($report_type_id == ReportTypes::HOSPITAL){
                $user = UserEntityRelation::where('entity_type_id',EntityTypes::HOSPITAL)
                                            ->where('entity_id',$entity_id)->first();
                $userId = !empty($user) ? $user->user_id : 0;
            }else if($report_type_id == ReportTypes::SHOP_PORTFOLIO){
                $shopImage = ShopImages::find($entity_id); 
                if($shopImage){
                    $user = UserEntityRelation::where('entity_type_id',EntityTypes::SHOP)
                                            ->where('entity_id',$shopImage->shop_id)->first();
                }else{
                    $user = '';
                }
                $userId = !empty($user) ? $user->user_id : 0;
            }else if($report_type_id == ReportTypes::SHOP_USER){
                $data = UserDetail::where('user_id',$entity_id)->first(); 
                $userId = !empty($data) ? $data->user_id : 0;
            }else if($report_type_id == ReportTypes::COMMUNITY){
                $data = Community::find($entity_id); 
                $userId = !empty($data) ? $data->user_id : 0;
            }else {
                $userId = 0;
            }

            if($userId != 0){
                $userData = UserDetail::where('user_id',$userId)->first(); 
                return $this->attributes['report_count'] =  $userData->report_count;
            }else {
                return $this->attributes['report_count'] =  0;
            }
        }
        
        return $this->attributes['report_count'] = 0;        
    }

    public function getCreatedAtAttribute($date)
    {
        $date = new Carbon($date);
        return $date->format('d-m-Y H:i');
    }

    public function getUpdatedAtAttribute($date)
    {
        $date = new Carbon($date);
        return $date->format('d-m-Y H:i');
    }

    public function getDisplayCreatedAtAttribute(){
        $created_at = $this->attributes['created_at'];
        return $this->attributes['display_created_at'] = Carbon::parse($created_at)->format('Y-m-d H:i:s');
    }
}
