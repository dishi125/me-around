<?php

namespace App\Http\Controllers\Challenge;

use App\Http\Controllers\Controller;
use App\Models\ChallengeConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class NotificationAdminController extends Controller
{
    public function index(Request $request)
    {
        $title = 'Notification for admin';
        return view('challenge.notification-admin.index', compact('title'));
    }

    public function getJsonData(Request $request)
    {
        try {
            $columns = array(
                0 => 'key',
                1 => 'value',
            );
            $limit = $request->input('length');
            $start = $request->input('start');
            $search = $request->input('search.value');
            $draw = $request->input('draw');
            $order = $columns[$request->input('order.0.column')];
            $dir = $request->input('order.0.dir');

            $query = ChallengeConfig::select('*');

            $totalData = $query->count();
            $totalFiltered = $totalData;

            if (!empty($search)) {
                $query = $query->where(function($q) use ($search){
                    $q->where('key', 'LIKE', "%{$search}%")
                        ->orWhere('value', 'LIKE', "%{$search}%");
                });

                $totalFiltered = $query->count();
            }

            $allData = $query->offset($start)
                ->limit($limit)
                ->orderBy($order, $dir)
                ->get();

            $data = array();
            if (!empty($allData)) {
                foreach ($allData as $value) {
                    $nestedData = [];
                    $id = $value['id'];

                    if ($value['key']=="New verification post"){
                        $key = __('datatable.notification_for_admin.new_verification_post');
                    }
                    else {
                        $key = $value['key'];
                    }
                    $nestedData['field'] = $key;
                    $nestedData['figure'] = $value['value'];

                    $edit = route('challenge.notification-admin.edit', $id);
                    $editButton = "<a href='".$edit."' role='button' class='btn btn-primary btn-sm mx-1' data-toggle='tooltip' data-original-title='Edit'><i class='fa fa-edit'></a>";
                    $nestedData['actions'] = $editButton;

                    $data[] = $nestedData;
                }
            }
            $jsonData = array(
                "draw" => intval($draw),
                "recordsTotal" => intval($totalData),
                "recordsFiltered" => intval($totalFiltered),
                "data" => $data
            );
            return response()->json($jsonData);
        } catch (\Exception $ex) {
            return response()->json([]);
        }
    }

    public function editData($id)
    {
        $title = "Edit";
        $settings = ChallengeConfig::find($id);
        return view('challenge.notification-admin.edit', compact('title', 'settings'));
    }

    public function updateData(Request $request, $id)
    {
        try {
            DB::beginTransaction();
            $inputs = $request->all();

            $validator = Validator::make($request->all(), [
                'key' => 'required',
                'value' => 'required',
            ]);
            if ($validator->fails()) {
                return redirect()->back()->withErrors($validator)->withInput();
            }

            $value = $inputs["value"];
            $key = strtolower(str_replace(' ', '_', $inputs["key"]));

            $updatePlan = ChallengeConfig::find($id);
            $updatePlan->value = $value ?? '';
            $updatePlan->save();

            DB::commit();
            notify()->success("Notification settings updated successfully", "Success", "topRight");
            return redirect()->route("challenge.notification-admin.index");
        } catch (\Exception $e) {
            notify()->error("Failed to update notification settings", "Error", "topRight");
            return redirect()->route('challenge.notification-admin.index');
        }
    }

}
