<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Hospital;
use App\Models\EntityTypes;
use App\Models\Post;
use App\Models\Status;
use App\Models\Config;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $title = "Overview";
        $coin = $user->user_credits;
        $hospital = $recommended_coins = '';
        $activePosts = $readyPosts = $pendingPosts = [];
        if(!empty($user->all_entity_type_id) && in_array(EntityTypes::HOSPITAL, $user->all_entity_type_id)){
            $hospital = Hospital::join('user_entity_relation', 'hospitals.id', '=', 'user_entity_relation.entity_id')
                    ->where('user_entity_relation.entity_type_id',EntityTypes::HOSPITAL)
                    ->where('user_entity_relation.user_id',$user->id)
                    ->select('hospitals.*')
                    ->first();

            $config = Config::where('key',Config::HOSPITAL_RECOMMEND_MONEY)->first();
            $recommended_coins = $config ? (int) filter_var($config->value, FILTER_SANITIZE_NUMBER_INT) : 0;

            $activePosts = Post::where('hospital_id',$hospital->id)->where('status_id',Status::ACTIVE)->orderBy('created_at','asc')->get();
            $readyPosts = Post::where('hospital_id',$hospital->id)->where('status_id',Status::FUTURE)->get();
            $pendingPosts = Post::where('hospital_id',$hospital->id)->whereIn('status_id',[Status::PENDING, Status::INACTIVE, Status::EXPIRE])->get();
            
        }elseif(!empty($user->all_entity_type_id) && in_array(EntityTypes::SHOP, $user->all_entity_type_id)){

            $config = Config::where('key',Config::SHOP_RECOMMEND_MONEY)->first();
            $recommended_coins = $config ? (int) filter_var($config->value, FILTER_SANITIZE_NUMBER_INT) : 0;
        }

        return view('business.dashboard.index', compact('title','user', 'coin','hospital', 'recommended_coins','activePosts','readyPosts','pendingPosts'));
    }
}
