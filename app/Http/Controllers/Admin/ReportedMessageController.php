<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ReportGroupMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Pawlox\VideoThumbnail\VideoThumbnail;
use Lakshmaji\Thumbnail\Thumbnail;

class ReportedMessageController extends Controller
{
    public function index(Request $request)
    {
        $title = 'Reported Messages List';
        ReportGroupMessage::where('is_admin_read', 1)->update(['is_admin_read' => 0]);

        return view('admin.reported-message.index', compact('title'));
    }

    public function getJsonData(Request $request){
        $columns = array(
            0 => 'users_detail.name',
            1 => 'group_messages.message',
            2 => 'report_group_messages.created_at',
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
            $query = ReportGroupMessage::leftjoin('users_detail', function ($join){
                        $join->on('users_detail.user_id', '=', 'report_group_messages.reporter_user_id');
                    })
                    ->leftjoin('group_messages', function ($join){
                        $join->on('group_messages.id', '=', 'report_group_messages.message_id');
                    })
                    ->select(
                        'report_group_messages.*',
                        'users_detail.name as user_name',
                        'group_messages.type',
                        'group_messages.message',
                        'group_messages.from_user'
                    );

            $totalData = count($query->get());
            $totalFiltered = $totalData;

            if (!empty($search)) {
                $query = $query->where(function($q) use ($search){
                    $q->where('users_detail.name', 'LIKE', "%{$search}%")
                        ->orWhere('group_messages.message', 'LIKE', "%{$search}%")
                        ->orWhere('report_group_messages.created_at', 'LIKE', "%{$search}%");
                });

                $totalFiltered = $query->count();
            }

            $result = $query->offset($start)
                ->limit($limit)
                ->orderBy($order, $dir)
                ->get();

            $count = 0;
            foreach($result as $report) {
                $message = "";
                if ($report->type == "text") {
                    $message = $report->message;
                }
                else if ($report->type == "file"){
                    $message = '<img onclick="showImage(`' . $report->message . '`)" src="' . $report->message . '" alt="file" class="reported-client-images pointer m-1" width="50" height="50" />';
                }
                else if ($report->type == "shop"){
                    $shop_data = json_decode($report->message,true);
                    $main_name = isset($shop_data['main_name']) ? $shop_data['main_name'] : "";
                    $shop_name = isset($shop_data['shop_name']) ? $shop_data['shop_name'] : "";
                    $message = '<img onclick="showImage(`' . $shop_data['thumbnail_image']['image'] . '`)" src="' . $shop_data['thumbnail_image']['thumb'] . '" alt="file" class="reported-client-images pointer m-1" width="50" height="50" />';
                    if ($main_name!=""){
                        $message .= "<div>$main_name</div>";
                    }
                    if ($shop_name!=""){
                        $message .= "<div>$shop_name</div>";
                    }
                }
                $data[$count]['reporter_name'] = $report->user_name;
                $data[$count]['reported_message'] = $message;
                $data[$count]['reported_at'] = $this->formatDateTimeCountryWise($report->created_at,$adminTimezone);

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
}
