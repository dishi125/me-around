<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Hospital;
use App\Models\EntityTypes;
use App\Models\Config;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $title = "Overview";
        $coin = $user->user_credits;
        $hospital = $recommended_coins = '';
        
        if(!empty($user->all_entity_type_id) && in_array(EntityTypes::HOSPITAL, $user->all_entity_type_id)){
            $hospital = Hospital::join('user_entity_relation', 'hospitals.id', '=', 'user_entity_relation.entity_id')
                    ->where('user_entity_relation.entity_type_id',EntityTypes::HOSPITAL)
                    ->where('user_entity_relation.user_id',$user->id)
                    ->select('hospitals.*')
                    ->first();

            $config = Config::where('key',Config::HOSPITAL_RECOMMEND_MONEY)->first();
            $recommended_coins = $config ? (int) filter_var($config->value, FILTER_SANITIZE_NUMBER_INT) : 0;
        }elseif(!empty($user->all_entity_type_id) && in_array(EntityTypes::SHOP, $user->all_entity_type_id)){

            $config = Config::where('key',Config::SHOP_RECOMMEND_MONEY)->first();
            $recommended_coins = $config ? (int) filter_var($config->value, FILTER_SANITIZE_NUMBER_INT) : 0;
        }

        return view('user.dashboard.index', compact('title','user', 'coin','hospital', 'recommended_coins'));
    }
}
