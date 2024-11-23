<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Models\ShopReportHistory;
use App\Http\Controllers\Controller;
use Log;

class ShopBlockAdminController extends Controller
{
    public function index(Request $request)
    {
        $title = 'Reported Shop List';

        return view('admin.report-shop.index', compact('title'));
    }

    public function getJsonData(Request $request){

        $columns = array(
            0 => 'shop_report_histories.created_at',
            1 => 'email',
            3 => 'update_date',
            4 => 'users.last_login',
            5 => 'post_title',
        );

        $filter = $request->input('filter');
        $limit = $request->input('length');
        $start = $request->input('start');
        $search = $request->input('search.value');
        $draw = $request->input('draw');
        $order = $columns[$request->input('order.0.column')];
        $dir = $request->input('order.0.dir');
       // $adminTimezone = $this->getAdminUserTimezone();

        try {
            $data = [];
            
            $query = ShopReportHistory::leftjoin('shops','shops.id','shop_report_histories.shop_id')
                ->join('users_detail','users_detail.user_id','shop_report_histories.user_id')
                ->whereRaw('shop_report_histories.id in (select max(id) from shop_report_histories group by user_id,shop_id)')
                ->select(
                    'shops.shop_name as shopname',
                    'shops.main_name as active_name',
                    'users_detail.name as user_name',
                    'shop_report_histories.*'
                );

            $totalData = count($query->get());
            $totalFiltered = $totalData;

            if (!empty($search)) {
                $query = $query->where(function($q) use ($search){
                    $q->where('shops.shop_name', 'LIKE', "%{$search}%")
                    ->orWhere('shops.main_name', 'LIKE', "%{$search}%")
                    ->orWhere('users_detail.name', 'LIKE', "%{$search}%")
                    ->orWhere('shop_report_histories.description', 'LIKE', "%{$search}%");
                });
                
                $totalFiltered = $query->count();
            }
            
            $result = $query->offset($start)
                ->limit($limit)
                ->orderBy($order, $dir)
                ->get();

            /* $totalData = count($result);
            $totalFiltered = $totalData; */

            $count = 0;
            foreach($result as $report){

                $show = route('admin.reported-shop.view', [$report->id]);
                $viewButton = "<a role='button' href='".$show."' title='' data-original-title='View' class='mx-1 btn btn-primary btn-sm' data-toggle='tooltip'>View Report</a>";

                $data[$count]['shopname'] = $report->shopname;
                $data[$count]['active_name'] = $report->active_name;
                $data[$count]['user_name'] = $report->user_name;
                $data[$count]['description'] = $report->description;
                $data[$count]['actions'] = "<div class='d-flex'>$viewButton</div>";
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

    public function viewDetail($id)
    {
        $title = 'View Reported Shop';
        $reportData = ShopReportHistory::with('attachments')
            ->join('shops','shops.id','shop_report_histories.shop_id')
            ->join('users_detail','users_detail.user_id','shop_report_histories.user_id')
            ->select(
                'shops.shop_name as shopname',
                'shops.main_name as active_name',
                'users_detail.name as user_name',
                'shop_report_histories.*'
            )
            ->where('shop_report_histories.id',$id)
            ->first();
        return view('admin.report-shop.show', compact('title','reportData'));
    }
}
