<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Shop;
use App\Models\UserEntityRelation;
use App\Models\EntityTypes;
use App\Models\Status;
use Illuminate\Support\Facades\Auth;
use Log;
use Validator;
use Illuminate\Support\Facades\DB;

class ShopManagementController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $title = 'Shop Management';
        return view('business.shop.index', compact('title'));
    }

    public function getJsonAllData(Request $request){
        $user = Auth::user();

        $isShop = UserEntityRelation::where('user_id',$user->id)->where('entity_type_id',EntityTypes::SHOP)->first();

        $columns = array(
            0 => 'main_name',
            1 => 'shop_name',
            2 => 'followers',
            3 => 'work_complete',
            4 => 'portfolio',
            5 => 'reviews',
            6 => 'created_at'
        );

        $filter = $request->input('filter');
        $limit = $request->input('length');
        $start = $request->input('start');
        $search = $request->input('search.value');
        $draw = $request->input('draw');
        $order = $columns[$request->input('order.0.column')];
        $dir = $request->input('order.0.dir');
        
        $adminTimezone = $this->getAdminUserTimezone();
        try {
            $data = [];

            if(!empty($isShop)){
                $shopQuery = Shop::join('user_entity_relation', 'shops.id', '=', 'user_entity_relation.entity_id')
                    ->where('user_entity_relation.entity_type_id',EntityTypes::SHOP)
                    ->where('user_entity_relation.user_id',$user->id);

                if($filter != 'all'){
                    if($filter == 'active'){
                        $filterWhere = [Status::ACTIVE];
                    }elseif($filter == 'inactive'){
                        $filterWhere = [Status::PENDING, Status::INACTIVE, Status::EXPIRE];
                    }
                    if($filterWhere){
                        $shopQuery = $shopQuery->whereIn('status_id', $filterWhere);
                    }
                }
                $activePosts = $shopQuery->get();

                if(!empty($search)){
                    $activePosts = collect($activePosts)->filter(function ($item) use ($search) {
                        return false !== stripos($item->main_name, $search) ||
                                false !== stripos($item->shop_name, $search);
                    })->values();
                }

                if($dir == 'asc'){
                    $activePosts = collect($activePosts)->sortBy($order);
                }else{
                    $activePosts = collect($activePosts)->sortByDesc($order);
                }

                $totalData = count($activePosts);
                $totalFiltered = $totalData;
            
                

                $count = 0;
                foreach($activePosts as $post){
    
                    $edit = route('business.shop.edit', [$post->id]);
                    $editButton = "<a role='button' href='$edit'  title='' data-original-title='Delete Account' class='mx-1 btn btn-primary btn-sm' data-toggle='tooltip'>Edit Post</a>";

                    $data[$count]['main_name'] = $post->main_name;
                    $data[$count]['shop_name'] = $post->shop_name;
                    $data[$count]['followers'] = $post->followers;
                    $data[$count]['work_complete'] = $post->work_complete;
                    $data[$count]['portfolio'] = $post->portfolio;
                    $data[$count]['reviews'] = $post->reviews;
                    $data[$count]['date'] = $post->created_at;
                    $data[$count]['actions'] = "<div class='d-flex'> $editButton</div>";
                    $count++;
                }
    
                $jsonData = array(
                    "draw" => intval($draw),
                    "recordsTotal" => intval($totalData),
                    "recordsFiltered" => intval($totalFiltered),
                    "data" => $data
                );
                
            }else{
                
                $jsonData = array(
                    "draw" => intval($draw),
                    "recordsTotal" => 0,
                    "recordsFiltered" => 0,
                    "data" => []
                );
            }
            
            return response()->json($jsonData);
        } catch (\Exception $ex) {
            Log::info('Exception all hospital list');
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

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
