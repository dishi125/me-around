<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserHiddenCategory extends Model
{
    use SoftDeletes;
    protected $table = 'user_hidden_categories';

    const LOGIN = 'login';
    const NONLOGIN = 'nonlogin';

    protected $fillable = [
        'category_id','user_id','user_type','created_at','updated_at','deleted_at','hidden_by'
    ];
}
