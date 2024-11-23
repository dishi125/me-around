<?php

namespace App\Http\Controllers\Insta;

use App\Http\Controllers\Controller;
use App\Models\InstaImportantSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ImportantSettingController extends Controller
{
    public function index()
    {
        $title = 'Important Settings';
        return view('insta.important-setting.index', compact('title'));
    }

    public function getJsonData(Request $request)
    {
        try {
            Log::info('Start important settings');
            $user = Auth::user();
            $columns = array(
                0 => 'field',
                1 => 'value',
            );
            $limit = $request->input('length');
            $start = $request->input('start');
            $search = $request->input('search.value');
            $draw = $request->input('draw');
            $order = $columns[$request->input('order.0.column')];
            $dir = $request->input('order.0.dir');

            $query = InstaImportantSetting::query();

            $totalData = $query->count();
            $totalFiltered = $totalData;

            if (!empty($search)) {
                $query = $query->where(function($q) use ($search){
                    $q->where('field', 'LIKE', "%{$search}%")
                        ->orWhere('value', 'LIKE', "%{$search}%");
                });

                $totalFiltered = $query->count();
            }

            $settings = $query->offset($start)
                ->limit($limit)
                ->orderBy($order, $dir)
                ->get();

            $data = array();
            if (!empty($settings)) {
                foreach ($settings as $value) {
                    $nestedData = [];
                    $nestedData['field'] = $value->field;
                    $nestedData['value'] = $value->value;

                    $edit = route('insta.important-setting.edit',$value->id);
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
            Log::info('End important setting shop');
            return response()->json($jsonData);
        } catch (\Exception $ex) {
            Log::info('Exception important setting shop');
            Log::info($ex);
            return response()->json([]);
        }
    }

    public function editData($id)
    {
        $title = "Edit Important Settings";
        $settings = InstaImportantSetting::find($id);

        return view('insta.important-setting.edit', compact('title', 'settings'));
    }

    public function updateData(Request $request, $id)
    {
        try {
            Log::info('Start code for the update');
            DB::beginTransaction();
            $inputs = $request->all();

            $validator = Validator::make($request->all(), [
                'field' => 'required',
                'value' => 'required',
            ]);
            if ($validator->fails()) {
                return redirect()->back()->withErrors($validator)->withInput();
            }

            $update = InstaImportantSetting::find($id);
            $update->value = $inputs['value'];
            $update->save();

            DB::commit();
            notify()->success("Important settings updated successfully", "Success", "topRight");
            return redirect()->route('insta.important-setting.index');
        } catch (\Exception $e) {
            Log::info('Exception in the update.');
            Log::info($e);
            notify()->error("Failed to update", "Error", "topRight");
            return redirect()->route('insta.important-setting.index');
        }
    }

}
