<?php

namespace App\Http\Controllers\Challenge;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\ChallengeCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class CategoryController extends Controller
{
    public function index()
    {
        $title = "Category";

        return view('challenge.category.index', compact('title'));
    }

    public function saveCategory(Request $request){
        try{
            DB::beginTransaction();
            $inputs = $request->all();
            $validator = Validator::make($inputs, [
                'title' => 'required',
                'order' => 'required|numeric',
                'challenge_type' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors(), 'success' => false]);
            }

            ChallengeCategory::create([
                'name' => $inputs['title'],
                'order' => $inputs['order'],
                'challenge_type' => $inputs['challenge_type'],
            ]);

            DB::commit();
            return response()->json(array('success' => true));
        }catch(\Exception $e){
            Log::info("$e");
            DB::rollBack();
            return response()->json(array('success' => false));
        }
    }

    public function getJsonData(Request $request){
        $columns = array(
            0 => 'name',
            1 => 'challenge_type',
            2 => 'order',
        );

        $limit = $request->input('length');
        $start = $request->input('start');
        $search = $request->input('search.value');
        $draw = $request->input('draw');
        $order = $columns[$request->input('order.0.column')];
        $dir = $request->input('order.0.dir');
        $filter = $request->input('filter');

        try {
            $data = [];
            $query = ChallengeCategory::query();

            $totalData = count($query->get());
            $totalFiltered = $totalData;

            if ($filter=="period_challenge"){
                $query = $query->where('challenge_type',ChallengeCategory::PERIODCHALLENGE);
            }
            elseif ($filter=="challenge"){
                $query = $query->where('challenge_type',ChallengeCategory::CHALLENGE);
            }

            if (!empty($search)) {
                $query = $query->where(function($q) use ($search){
                    $q->where('name', 'LIKE', "%{$search}%");
                });

                $totalFiltered = $query->count();
            }

            $result = $query->offset($start)
                ->limit($limit)
                ->orderBy($order, $dir)
                ->get();

            $count = 0;
            foreach($result as $res){
//                $editButton = "<a href='".route('admin.category.edit', $res->id)."' role='button' class='btn btn-primary btn-sm mx-1' data-toggle='tooltip' data-original-title='Edit'><i class='fa fa-edit'></a>";

                $data[$count]['name'] = $res->name;
                $data[$count]['type'] = ($res->challenge_type==1) ? __('general.challenge') : __('general.period_challenge');
                $data[$count]['order'] = $res->order;

                $checked = ($res->is_hidden==0) ? 'checked' : '';
                $data[$count]['status'] = '<input type="checkbox" class="toggle-btn showhide-toggle-btn" '.$checked.' data-id="'.$res->id.'" data-toggle="toggle" data-height="20" data-size="sm" data-onstyle="success" data-offstyle="danger" data-on="Show" data-off="Hide">';
//                $data[$count]['action'] = $editButton;
                $data[$count]['id'] = $res->id;

                $editBtn = '<a href="javascript:void(0)" role="button" onclick="editCategory('.$res->id.')" class="btn btn-primary btn-sm mx-1" data-toggle="tooltip" data-original-title="Edit"><i class="fa fa-edit"></i></a>';
                $data[$count]['action'] = "$editBtn";

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

    public function updateShowHide(Request $request){
        $inputs = $request->all();
        try{
            $data_id = $inputs['data_id'] ?? '';
            $checked = (string)$inputs['checked'];
            $isChecked = ($checked == 'true') ? 0 : 1;

            if(!empty($data_id)){
                ChallengeCategory::where('id',$data_id)->update(['is_hidden' => $isChecked]);
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

    public function updateOrder(Request $request){
        $inputs = $request->all();
        try{
            $order = $inputs['order'] ?? '';
            if(!empty($order)){
                foreach($order as $value){
                    $isUpdate = ChallengeCategory::where('id',$value['id'])->update(['order' => $value['position']]);
                }
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

    public function editData($id){
        $category = ChallengeCategory::where('id',$id)->first();

        return view('challenge.category.edit-popup',compact('category'));
    }

    public function updateCategory(Request $request){
        try{
            DB::beginTransaction();
            $inputs = $request->all();
            $validator = Validator::make($inputs, [
                'name' => 'required',
                'challenge_order' => 'required|numeric',
                'challenge_type' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors(), 'success' => false]);
            }

            ChallengeCategory::where('id',$inputs['category_id'])->update([
                'name' => $inputs['name'],
                'order' => $inputs['challenge_order'],
                'challenge_type' => $inputs['challenge_type'],
            ]);

            DB::commit();
            return response()->json(array('success' => true));
        }catch(\Exception $e){
            Log::info("$e");
            DB::rollBack();
            return response()->json(array('success' => false));
        }
    }

}
