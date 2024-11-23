<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\CategoryTypes;
use App\Models\Status;
use Illuminate\Support\Facades\Storage;

class Category extends Model
{
    use SoftDeletes;
    protected $table = 'category';
    
    protected $fillable = [
        'type','name','logo','category_type_id','parent_id','status_id','order','created_at','updated_at','is_show','is_hidden','menu_key'
    ];

    protected $casts = [
        'type' => 'string',
        'name' => 'string',
        'logo' => 'string',
        'category_type_id' => 'int',
        'status_id' => 'int',
        'order' => 'int',
        'parent_id' => 'int',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    protected $appends = ['category_type_name','parent_name','status_name','sub_categories'];

    protected $dates = ['deleted_at'];

    public function parentCategories()
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function getLogoAttribute()
    {
        $value = $this->attributes['logo'];
        if (empty($value)) {
            return $this->attributes['logo'] = '';
        } else {
            if (!filter_var($value, FILTER_VALIDATE_URL)) {
                return $this->attributes['logo'] = Storage::disk('s3')->url($value);
            } else {
                return $this->attributes['logo'] = $value;
            }
        }
    }

    public function getCategoryTypeNameAttribute()
    {
        $value = isset($this->attributes['category_type_id']) ? $this->attributes['category_type_id'] : NULL;
        if($value) {
            $type = CategoryTypes::find($value);
            return $this->attributes['category_type_name'] = !empty($type) ? $type->name : '';
        }
        return $this->attributes['category_type_name'] = '';
        
    }

    public function getParentNameAttribute()
    {
        $value = isset($this->attributes['parent_id']) ? $this->attributes['parent_id'] : NULL;
        if($value) {
            $type = Category::find($value);
            return $this->attributes['parent_name'] = !empty($type) ? $type->name : '';
        }   
        
        return $this->attributes['parent_name'] = '';
    }

    public function getStatusNameAttribute()
    {
        $value = isset($this->attributes['status_id']) ? $this->attributes['status_id'] : NULL;
        if($value) {
            $status = Status::find($value);
            return $this->attributes['status_name'] = !empty($status) ? $status->name : '';
        } 
        return $this->attributes['status_name'] = '';
    }

    public function getSubCategoriesAttribute()
    {
        $value = isset($this->attributes['parent_id']) ? $this->attributes['parent_id'] : NULL;
        $id = isset($this->attributes['id']) ? $this->attributes['id'] : NULL;
        if($value == 0) {
            $categories = Category::where('parent_id',$id)->get();
            return $this->attributes['sub_categories'] = !empty($categories) ? $categories : [];
        }   
        
        return $this->attributes['sub_categories'] = [];
    }

    public function categoryType()
    {
        return $this->hasOne(CategoryTypes::class, 'id', 'category_type_id');
    }
}
