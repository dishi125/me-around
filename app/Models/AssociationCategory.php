<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AssociationCategory extends Model
{
    use SoftDeletes;

    protected $table = 'association_categories';
    protected $dates = ['deleted_at'];  

    protected $fillable = [
        'associations_id','is_hide', 'name', 'can_post','order', 'created_at', 'updated_at'
    ];

    public function associationCommunity() {
        return $this->hasMany(AssociationCommunity::class, 'category_id', 'id');
    }
}
