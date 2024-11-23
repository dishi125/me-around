<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\CategoryTypes;
use App\Models\HashTag;
use App\Models\SavedHistoryTypes;
use App\Models\Status;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class HashtagController extends Controller
{
    public function index(Request $request)
    {
        $title = 'Hashtag List';
        $categories = Category::where('type', 'default')
                        ->where('category_type_id', CategoryTypes::SHOP)
                        ->select('*')
                        ->selectSub(function($q) {
                            $q->select(DB::raw('count(*) as total'))->from('shops')->whereNotNull('shops.deleted_at')->whereRaw("`shops`.`category_id` = `category`.`id`")->where('shops.status_id', Status::ACTIVE);
                        }, 'shops_count')
                        ->orderBy('shops_count','DESC')
                        ->get();
        $first_cat = 0;
        if(count($categories) > 0){
            $first_cat = $categories[0]['id'];
        }

        return view('admin.hashtags.index', compact('title','categories','first_cat'));
    }

    public function getJsonAllData(Request $request){
        $columns = array(
            0 => 'hash_tags.tags',
        );

        $limit = $request->input('length');
        $start = $request->input('start');
        $search = $request->input('search.value');
        $draw = $request->input('draw');
        $order = $columns[$request->input('order.0.column')];
        $dir = $request->input('order.0.dir');
        $adminTimezone = $this->getAdminUserTimezone();
        $category_filter = $request->input('categoryFilter');

        try {
            $data = [];
            $query = HashTag::join('hash_tag_mappings', function ($join) {
                $join->on('hash_tag_mappings.hash_tag_id', '=', 'hash_tags.id')
                    ->where('hash_tag_mappings.entity_type_id', HashTag::SHOP_POST);
                })
                ->join('shop_posts', function ($join) {
                    $join->on('shop_posts.id', '=', 'hash_tag_mappings.entity_id');
                })
                ->join('shops', 'shop_posts.shop_id', 'shops.id')
                ->join('category', function ($join) {
                    $join->on('shops.category_id', '=', 'category.id')
                        ->whereNull('category.deleted_at');
                })
                ->whereNull('shops.deleted_at')
                ->where('shops.status_id', Status::ACTIVE)
                ->select(
                    'hash_tags.*',
                    DB::raw('COUNT(hash_tag_mappings.id) as total_posts'),
                    'shop_posts.id as post_id',
                    DB::raw('group_concat(shop_posts.id) as shop_posts'),
                    'category.name as category_name'
                )
                ->groupBy('hash_tags.id');

            if ($category_filter!="all"){
                $query = $query->where('shops.category_id',$category_filter);
            }

            if (!empty($search)) {
                $query =  $query->where(function ($q) use ($search) {
                    $q->where('hash_tags.tags', 'LIKE', "%{$search}%");
                        /*->orWhere('payer_phone', 'LIKE', "%{$search}%")
                        ->orWhere('payer_email', 'LIKE', "%{$search}%")
                        ->orWhere('card_number', 'LIKE', "%{$search}%")
                        ->orWhere('card_name', 'LIKE', "%{$search}%")
                        ->orWhere('pay_goods', 'LIKE', "%{$search}%")
                        ->orWhere('pay_total', 'LIKE', "%{$search}%")
                        ->orWhere('instagram_account', 'LIKE', "%{$search}%");*/
                });
            }

            $totalData = count($query->get());
            $totalFiltered = $totalData;

            $tagsData = $query->offset($start)
                ->limit($limit)
//                ->orderBy($order, $dir)
                ->orderBy('total_posts','DESC')
                ->get();

            $count = 0;
            foreach($tagsData as $tag){
                $data[$count]['rank'] = $count + 1;
                $data[$count]['hashtag_name'] = $tag->tags;
                $data[$count]['posts'] = $tag->total_posts;
                $data[$count]['category'] = $tag->category_name;

                $postIds = explode(',',$tag->shop_posts);
                $shopPost = DB::table('shop_posts')->leftjoin('user_saved_history', function ($join) {
                    $join->on('user_saved_history.entity_id', '=', 'shop_posts.id')
                        ->where('user_saved_history.saved_history_type_id',SavedHistoryTypes::SHOP)->where('user_saved_history.is_like',1);
                    })
                    ->join('shops', 'shop_posts.shop_id', 'shops.id')
                    ->join('category', function ($join) {
                        $join->on('shops.category_id', '=', 'category.id')
                            ->whereNull('category.deleted_at');
                    })
                    ->where('shops.status_id', Status::ACTIVE)
                    ->whereIn('shop_posts.id',$postIds)
                    ->select(
                        'shop_posts.*',
                        DB::raw('COUNT(user_saved_history.id) as total_saved')
                    )
                    ->orderBy('total_saved','DESC')
                    ->orderBy('shop_posts.created_at','DESC')
                    ->groupBy('shop_posts.id')
                    ->first();

                $recent_post_image = '';
                if($shopPost) {
                    if ($shopPost->type == 'video') {
                        $recent_post_image = filterDataUrl($shopPost->video_thumbnail);
                        $recent_post_image = "<img src='$recent_post_image' alt='Recent Post' width='50' height='50'>";
                    } else {
                        $recent_post_image = filterDataUrl($shopPost->post_item);
                        $recent_post_image = "<img src='$recent_post_image' alt='Recent Post' width='50' height='50'>";
//                        $recent_post_image = filterDataThumbnailUrl($shopPost->post_item);
                    }
                }
                $data[$count]['recent_post_image'] = $recent_post_image;

                $postsLink = route('admin.business-client.get.shop.post',['hashtag_id' => $tag->id]);
                $seePost = '<a role="button" href="'.$postsLink.'" title="" data-original-title="" class="btn btn-primary btn-sm" data-toggle="tooltip" target="_blank">See Post</a>';
                $data[$count]['action'] = $seePost;

                $checked = $tag->is_show ? 'checked' : '';
                $toggle = '<input type="checkbox" class="toggle-btn hide-show-toggle-btn" '.$checked.' data-id="'.$tag->id.'" data-toggle="toggle" data-height="20" data-size="sm" data-onstyle="success" data-offstyle="danger" data-on="Show" data-off="Hide">';
                $data[$count]['hide_show'] = $toggle;

                $count++;
            }

            $jsonData = array(
                "draw" => intval($draw),
                "recordsTotal" => intval($totalData),
                "recordsFiltered" => intval($totalFiltered),
                "data" => $data,
            );
            return response()->json($jsonData);
        } catch (\Exception $ex) {
            Log::info($ex);
            $jsonData = array(
                "draw" => intval($draw),
                "recordsTotal" => 0,
                "recordsFiltered" => 0,
                "data" => []
            );
            return response()->json($jsonData);
        }
    }

    public function updateOnOff(Request $request){
        $inputs = $request->all();
        try{
            $data_id = $inputs['data_id'] ?? '';
            $checked = (string)$inputs['checked'];
            $isChecked = ($checked == 'true') ? 1 : 0;
            if(!empty($data_id)){
                HashTag::where('id',$data_id)->update(['is_show' => $isChecked]);
            }
            return response()->json([
                'success' => true
            ]);
        }catch (\Exception $ex) {
            Log::info($ex);
            return response()->json([
                'success' => false
            ]);
        }
    }

}
