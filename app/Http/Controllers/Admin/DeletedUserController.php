<?php

namespace App\Http\Controllers\Admin;

use App\Models\Shop;
use App\Models\User;
use App\Models\EntityTypes;
use Illuminate\Http\Request;
use App\Models\CategoryTypes;
use App\Models\GeneralSettings;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;

class DeletedUserController extends Controller
{
    public function index(Request $request)
    {
        $title = 'Deleted Users List';
        GeneralSettings::where('key',GeneralSettings::LAST_DELETED_VIEW)->update(['value' => date('Y-m-d H:i:s')]);

        return view('admin.deleted-users.index', compact('title'));
    }

    public function getJsonData(Request $request){

        $columns = array(
            0 => 'username',
            1 => 'gender',
            2 => 'email',
            3 => 'phone_number',
            4 => 'deleted_at',
            5 => 'created_at',
        );

        $limit = $request->input('length');
        $start = $request->input('start');
        $search = $request->input('search.value');
        $draw = $request->input('draw');
        $order = $columns[$request->input('order.0.column')];
        $dir = $request->input('order.0.dir');
        $adminTimezone = $this->getAdminUserTimezone();

        try {
            $data = [];
            $query = User::join('users_detail','users_detail.user_id','users.id')
                ->select(
                    'users.*',
                    'users_detail.name as user_name',
                    'users_detail.gender as display_gender',
                    'users_detail.mobile as mobile_number',
                    DB::raw('(SELECT group_concat(entity_type_id) from user_entity_relation WHERE user_id = users.id) as entity_types')
                )
                ->selectSub(function($q) {
                    $q->select(DB::raw('count(*) as total'))->from('shops')->whereNotNull('shops.deleted_at')->whereRaw("`shops`.`user_id` = `users`.`id`");
                }, 'shops_count')
                ->onlyTrashed();

            $totalData = count($query->get());
            $totalFiltered = $totalData;

            if (!empty($search)) {
                $query = $query->where(function($q) use ($search){
                    $q->where('users_detail.name', 'LIKE', "%{$search}%")
                        ->orWhere('users_detail.gender', 'LIKE', "%{$search}%")
                        ->orWhere('users.email', 'LIKE', "%{$search}%")
                        ->orWhere('users_detail.mobile', 'LIKE', "%{$search}%");
                });

                $totalFiltered = $query->count();
            }

            $result = $query->offset($start)
                ->limit($limit)
                ->orderBy($order, $dir)
                ->get();

            /* $totalData = count($result);
            $totalFiltered = $totalData; */
//            dd($result->toArray());

            $count = 0;
            foreach($result as $report){
                $actions = "";
                if ($report->shops_count > 0){
                    $actions .= "<a role='button' href='javascript:void(0)' onclick='viewShopProfile(" . $report->id . ")'  title='' data-original-title='View' class='btn btn-primary btn-sm ' data-toggle='tooltip'><i class='fas fa-eye mt-1'></i></a>";
                }

                if ($actions == ""){
                    $actions = "-";
                }

                $data[$count]['username'] = $report->user_name;
                $data[$count]['gender'] = $report->display_gender;
                $data[$count]['email'] = $report->email;
                $data[$count]['phone_number'] = $report->mobile_number;
                $data[$count]['deleted_at'] = $this->formatDateTimeCountryWise($report->deleted_at,$adminTimezone);
                $data[$count]['signup_date'] = $this->formatDateTimeCountryWise($report->display_created_at,$adminTimezone);
                $data[$count]['actions'] = "<div class='d-flex align-items-center'>$actions</div>";

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

    public function viewShopProfile($id) {
        $shops = Shop::leftjoin('category','category.id','shops.category_id')
            ->leftjoin('reviews', function ($join) {
                $join->on('shops.id', '=', 'reviews.entity_id')
                    ->where('reviews.entity_type_id', EntityTypes::SHOP);
            })
            ->whereIn('category.category_type_id', [CategoryTypes::SHOP,CategoryTypes::CUSTOM])
            ->where('shops.user_id',$id)
            ->groupby('shops.id')
            ->select('shops.*','category.name as category', 'category.category_type_id',DB::raw('round(AVG(reviews.rating),1) as avg_rating'))
            ->onlyTrashed()
            ->get();

        return view('admin.deleted-users.check-shop-profile',compact('shops'));
    }

}
