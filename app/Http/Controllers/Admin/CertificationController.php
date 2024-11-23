<?php

namespace App\Http\Controllers\Admin;

use Validator;
use App\Models\ExamTests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Lang;

class CertificationController extends Controller
{
    public function index()
    {
        $title = "Certification Exam Management";
        return view('admin.certification-exam.index', compact('title'));
    }

    public function getJsonData(Request $request)
    {
        try {
            $columns = array(
                0 => 'name'
            );

            $limit = $request->input('length');
            $start = $request->input('start');
            $search = $request->input('search.value');
            $draw = $request->input('draw');
            $order = $columns[$request->input('order.0.column')];
            $dir = $request->input('order.0.dir');

            $query = ExamTests::select('*');

            $totalData = $query->count();
            $totalFiltered = $totalData;

            if (!empty($search)) {
                $query = $query->where('name', 'LIKE', "%{$search}%");
                $totalFiltered = $query->count();
            }

            $tests = $query->offset($start)
                ->limit($limit)
                ->orderBy($order, $dir)
                ->get();

            $data = array();

            if (!empty($tests)) {
                foreach ($tests as $value) {
                    $edit = route('admin.tests.edit', $value->id);

                    $nestedData['id'] = $value->id;
                    $nestedData['name'] = $value->name;

                    $editButton =  "<a role='button' href='" . $edit . "' title='' data-original-title='Edit' class='btn btn-primary' data-toggle='tooltip'><i class='fa fa-edit'></i></a>";
                    $deleteButton = "<a href='javascript:void(0)' role='button' onclick='deleteTests(" . $value->id . ")' class='btn btn-danger' data-toggle='tooltip' data-original-title=" . Lang::get('general.delete') . "><i class='fa fa-trash'></button>";
                    $nestedData['actions'] = "$editButton $deleteButton";
                    $data[] = $nestedData;
                }
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
                "draw" => intval(0),
                "recordsTotal" => intval(0),
                "recordsFiltered" => intval(0),
                "data" => [],
            );
            return response()->json($jsonData);
        }
    }

    public function createTests()
    {
        $title = 'Add Tests';
        return view('admin.certification-exam.form', compact('title'));
    }

    public function storeTests(Request $request)
    {
        $inputs = $request->all();
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required',
            ], [], [
                'name' => 'Name',
            ]);
            if ($validator->fails()) {
                notify()->error("Validation Error", "Error", "topRight");
                return redirect()->back()->withErrors($validator)->withInput();
            }
            DB::beginTransaction();
            ExamTests::create([
                'name' => $inputs['name']
            ]);

            DB::commit();
            notify()->success("Tests " . trans("messages.insert-success"), "Success", "topRight");
            return redirect()->route('admin.certification-exam.index');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::info($e);
            notify()->error("Tests " . trans("messages.insert-error"), "Error", "topRight");
            return redirect()->route('admin.certification-exam.index');
        }
    }

    public function editTests($id)
    {
        $title = 'Edit Tests';
        $tests = ExamTests::findOrFail($id);
        return view('admin.certification-exam.form', compact('title', 'tests'));
    }

    public function updateTests(Request $request, $id)
    {
        $inputs = $request->all();
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required',
            ], [], [
                'name' => 'Name',
            ]);
            if ($validator->fails()) {
                notify()->error("Validation Error", "Error", "topRight");
                return redirect()->back()->withErrors($validator)->withInput();
            }
            DB::beginTransaction();
            ExamTests::whereId($id)->update([
                'name' => $inputs['name']
            ]);

            DB::commit();
            notify()->success("Tests " . trans("messages.update-success"), "Success", "topRight");
            return redirect()->route('admin.certification-exam.index');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::info($e);
            notify()->error("Tests " . trans("messages.update-error"), "Error", "topRight");
            return redirect()->route('admin.certification-exam.index');
        }
    }

    public function delete($id)
    {   
        return view('admin.certification-exam.delete',compact('id'));
    }

    public function destroy($id)
    {
        try {
            DB::beginTransaction();
            ExamTests::where('id',$id)->delete();
            DB::commit();

            notify()->success("Tests deleted successfully", "Success", "topRight");
            return redirect()->route('admin.certification-exam.index');
        } catch (\Exception $ex) {
            DB::rollBack();
            notify()->error("Failed to deleted Tests", "Error", "topRight");
            return redirect()->route('admin.certification-exam.index');        
        }
    }
}
