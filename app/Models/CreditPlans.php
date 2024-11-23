<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\PackagePlan;

class CreditPlans extends Model
{
    use SoftDeletes;
    protected $table = 'credit_plans';
    protected $dates = ['deleted_at'];

    protected $fillable = [
        'entity_type_id','package_plan_id','deduct_rate','amount','km','no_of_posts','created_at','updated_at'
    ];

    protected $appends = ['package_plan_name'];

    protected $casts = [
        'entity_type_id' => 'int',
        'package_plan_id' => 'int',
        'deduct_rate' => 'int',
        'amount' => 'decimal:0',
        'km' => 'string',
        'no_of_posts' => 'int',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function getPackagePlanNameAttribute()
    {
        $value = $this->attributes['package_plan_id'];
        $type = PackagePlan::find($value);
        return $this->attributes['package_plan_name'] = $type->name;
    }

    public function getKmAttribute()
    {
        $value = $this->attributes['km'];
        return number_format((float)$value, 0, '.', '');
    }
}
