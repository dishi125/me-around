<?php

namespace App\Models;
use App\Models\RequestFormStatus;
use App\Models\UserDetail;
use App\Models\Category;
use App\Models\EntityTypes;
use App\Models\Country;
use App\Models\City;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Model;

class RequestForm extends Model
{
    use SoftDeletes;
    protected $table = 'request_forms';
    protected $dates = ['deleted_at'];

    protected $fillable = [
        'entity_type_id',
        'user_id',
        'category_id',
        'name',
        'address',
        'city_id',
        'state_id',
        'country_id',
        'main_country',
        'latitude',
        'longitude',
        'email',
        'recommended_code',
        'business_licence',
        'best_portfolio',
        'identification_card',
        'interior_photo',
        'business_license_number',
        'manager_id',
        'request_status_id',
        'request_count',
        'created_at',
        'updated_at',
        'is_admin_read'
    ];

    protected $casts = [
        'entity_type_id' => 'int',
        'user_id' => 'int',
        'category_id' => 'int',
        'name' => 'string',
        'address' => 'string',
        'city_id' => 'int',
        'state_id' => 'int',
        'country_id' => 'int',
        // 'request_count' => 'int',
        'main_country' => 'string',
        'latitude' => 'string',
        'longitude' => 'string',
        'email' => 'string',
        'recommended_code' => 'string',
        'business_licence' => 'string',
        'best_portfolio' => 'string',
        'identification_card' => 'string',
        'interior_photo' => 'string',
        'business_license_number' => 'string',
        'manager_id' => 'int',
        'request_status_id' => 'int',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    protected $appends = [
        'display_created_at', 'request_status_name', 'category_name', 'entity_type_name', 'mobile', 'country_name', 'city_name', 'user_name',
        'best_portfolio_url', 'identification_card_url', 'business_licence_url', 'interior_photo_url'
    ];

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

    public function getRequestStatusNameAttribute()
    {
        $value = $this->attributes['request_status_id'];
        $status = RequestFormStatus::find($value);
        return $this->attributes['request_status_name'] = $status->name;
    }

    public function getMobileAttribute()
    {
        $value = $this->attributes['user_id'];
        $user = UserDetail::where('user_id',$value)->first();
        return $this->attributes['mobile'] = $user->mobile ?? '';
    }

    public function getCategoryNameAttribute()
    {
        $value = $this->attributes['category_id'];
        $category = Category::find($value);
        return $this->attributes['category_name'] = $category->name ?? '';
    }

    public function getEntityTypeNameAttribute()
    {
        $value = $this->attributes['entity_type_id'];
        $entityType = EntityTypes::find($value);
        return $this->attributes['entity_type_name'] = $entityType->name;
    }

    public function getCountryNameAttribute()
    {
        $value = $this->attributes['country_id'];
        $country = Country::find($value);
        return $this->attributes['country_name'] = $country->name ?? '';
    }

    public function getCityNameAttribute()
    {
        $value = $this->attributes['city_id'];
        $city = City::find($value);
        return $this->attributes['city_name'] = $city->name ?? '';
    }

    public function getUserNameAttribute()
    {
        $value = $this->attributes['user_id'];
        $user = UserDetail::where('user_id',$value)->first();
        return $this->attributes['user_name'] = $user->name ?? '';
    }

    public function getBestPortfolioUrlAttribute()
    {
        $value = isset($this->attributes['best_portfolio']) ? $this->attributes['best_portfolio'] : null;
        if (empty($value)) {
            return $this->attributes['best_portfolio_url'] = '';
        } else {
            if (!filter_var($value, FILTER_VALIDATE_URL)) {
                return $this->attributes['best_portfolio_url'] = Storage::disk('s3')->url($value);
            } else {
                return $this->attributes['best_portfolio_url'] = $value;
            }
        }
    }

    public function getIdentificationCardUrlAttribute()
    {
        $value = isset($this->attributes['identification_card']) ? $this->attributes['identification_card'] : null;
        if (empty($value)) {
            return $this->attributes['identification_card_url'] = '';
        } else {
            if (!filter_var($value, FILTER_VALIDATE_URL)) {
                return $this->attributes['identification_card_url'] = Storage::disk('s3')->url($value);
            } else {
                return $this->attributes['identification_card_url'] = $value;
            }
        }
    }

    public function getBusinessLicenceUrlAttribute()
    {
        $value = isset($this->attributes['business_licence']) ? $this->attributes['business_licence'] : null;
        if (empty($value)) {
            return $this->attributes['business_licence_url'] = '';
        } else {
            if (!filter_var($value, FILTER_VALIDATE_URL)) {
                return $this->attributes['business_licence_url'] = Storage::disk('s3')->url($value);
            } else {
                return $this->attributes['business_licence_url'] = $value;
            }
        }
    }

    public function getInteriorPhotoUrlAttribute()
    {
        $value = isset($this->attributes['interior_photo']) ? $this->attributes['interior_photo'] : null;
        if (empty($value)) {
            return $this->attributes['interior_photo_url'] = '';
        } else {
            if (!filter_var($value, FILTER_VALIDATE_URL)) {
                return $this->attributes['interior_photo_url'] = Storage::disk('s3')->url($value);
            } else {
                return $this->attributes['interior_photo_url'] = $value;
            }
        }
    }

    // public function getIconAttribute()
    // {
    //     $value = $this->attributes['category_name'];
    //     $category = Category::where('name',$value)->pluck('logo')->first();
    //     if (empty($category)) {
    //         return $this->attributes['icon'] = null;
    //     } else {
    //         if (!filter_var($value, FILTER_VALIDATE_URL)) {
    //             return $this->attributes['icon'] = Storage::disk('s3')->url($category);
    //         } else {
    //             return $this->attributes['icon'] = $category;
    //         }
    //     }        
    // }

    public function user()
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }

    public function entityType()
    {
        return $this->hasOne(EntityTypes::class, 'id', 'entity_type_id');
    }

    public function category()
    {
        return $this->hasOne(Category::class, 'id', 'category_id');
    }

    public function status()
    {
        return $this->hasOne(RequestFormStatus::class, 'id', 'request_status_id');
    }

    public function country()
    {
        return $this->hasOne(Country::class, 'id', 'country_id');
    }

    public function city()
    {
        return $this->hasOne(City::class, 'id', 'country_id');
    }

    public function getDisplayCreatedAtAttribute(){
        $created_at = $this->attributes['created_at'];
        return $this->attributes['display_created_at'] = Carbon::parse($created_at)->format('Y-m-d H:i:s');
    }
}
