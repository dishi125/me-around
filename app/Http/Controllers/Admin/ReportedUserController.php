<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GroupMessage;
use App\Models\ReportedUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReportedUserController extends Controller
{
    public function index(Request $request)
    {
        $title = 'Reported Users List';
        ReportedUser::where('is_admin_read', 1)->update(['is_admin_read' => 0]);

        return view('admin.reported-group-chat.index', compact('title'));
    }

    public function getJsonData(Request $request){
        $columns = array(
            0 => 'reporter_name',
            1 => 'reported_user',
            2 => 'reason',
            3 => 'created_at',
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
            $query = ReportedUser::with(['reporter_user_detail','reported_user_detail']);

            $totalData = count($query->get());
            $totalFiltered = $totalData;

            if (!empty($search)) {
                $query = $query->where(function($q) use ($search){
                    $q->where('reported_users.reason', 'LIKE', "%{$search}%")
                        ->orWhereHas('reporter_user_detail', function($query) use ($search) {
                            $query->where('name', 'LIKE', "%{$search}%");
                        })
                        ->orWhereHas('reported_user_detail', function($query) use ($search) {
                            $query->where('name', 'LIKE', "%{$search}%");
                        });
                });

                $totalFiltered = count($query->get());
            }

            $result = $query->offset($start)
                ->limit($limit)
                ->orderBy($order, $dir)
                ->get();

            $count = 0;
            foreach($result as $report){
                $reporter_name = ($report->reporter_user_detail!=null) ? $report->reporter_user_detail->name : "";
                $cnt_reporter_text = DB::table('group_messages')->where('from_user',$report->reporter_user_id)->where('type','text')->count();
                $list_btn_reporter = '<a role="button" href="javascript:void(0);" onclick="showMessageList('.$report->reporter_user_id.')" title="" data-original-title="View" class="btn btn-primary btn-sm ml-2" data-toggle="tooltip">'.$cnt_reporter_text.' <i class="fas fa-eye"></i></a>';

                $reported_name = ($report->reported_user_detail!=null) ? $report->reported_user_detail->name : "";
                $cnt_reported_text = DB::table('group_messages')->where('from_user',$report->reported_user_id)->where('type','text')->count();
                $list_btn_reported = '<a role="button" href="javascript:void(0);" onclick="showMessageList('.$report->reported_user_id.')" title="" data-original-title="View" class="btn btn-primary btn-sm ml-2" data-toggle="tooltip">'.$cnt_reported_text.' <i class="fas fa-eye"></i></a>';

                $data[$count]['reporter_name'] = $reporter_name.$list_btn_reporter;
                $data[$count]['reported_user'] = $reported_name.$list_btn_reported;
                $data[$count]['reason'] = $report->reason;
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

    public function showUserMessages($id){
        $adminTimezone = $this->getAdminUserTimezone();
        $messages = DB::table('group_messages')->where('from_user',$id)->where('type','text')->orderBy('created_at','DESC')->get(['id','message','created_at']);
//        dd($messages->toArray());
        return view('admin.reported-group-chat.show-messages-popup', compact('messages','adminTimezone'));
    }

    public function deleteMessage($id)
    {
        return view('admin.reported-group-chat.delete', compact('id'));
    }

    public function destroyMessage($id)
    {
        try {
            DB::beginTransaction();

            GroupMessage::where('id',$id)->delete();

            DB::commit();
            notify()->success("Message deleted successfully", "Success", "topRight");
            return redirect()->route('admin.reported-group-chat.index');
        } catch (\Exception $ex) {
            DB::rollBack();
            notify()->error("Failed to delete message", "Error", "topRight");
            return redirect()->route('admin.reported-group-chat.index');
        }
    }

}
