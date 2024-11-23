<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BigCategory;
use App\Models\BigCategoryLanguage;
use App\Models\Category;
use App\Models\PostLanguage;
use App\Models\Status;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class BigCategoryController extends Controller
{
    public function index()
    {
        $title = "Big Category List";
        return view('admin.big_category.index', compact('title'));
    }

    public function tableData(Request $request)
    {
        try {
            Log::info('Start get big category list');
            $user = Auth::user();
            $columns = array(
                0 => 'big_categories.name',
                1 => 'big_category_languages.name',
                3 => 'big_categories.order',
                4 => 'big_categories.status_id',
            );

            $limit = $request->input('length');
            $start = $request->input('start');
            $search = $request->input('search.value');
            $draw = $request->input('draw');
            $order = $columns[$request->input('order.0.column')];
            $dir = $request->input('order.0.dir');

            $query = BigCategory::leftjoin('big_category_languages', function($query) {
                        $query->on('big_category_languages.big_category_id','=','big_categories.id')
                            ->whereRaw('big_category_languages.post_language_id = 1');
                    })
                    ->select('big_categories.*','big_category_languages.name as koreanname');
            $totalData = $query->count();
            $totalFiltered = $totalData;

            if (!empty($search)) {
                $query = $query->where('big_categories.name', 'LIKE', "%{$search}%");
                $totalFiltered = $query->count();
            }

            $categories = $query->offset($start)
                ->limit($limit)
                //->orderBy('order')
                ->orderBy($order, $dir)
                ->get();

            $categories = $categories->makeHidden('created_at', 'updated_at');

            $data = array();

            if (!empty($categories)) {
                foreach ($categories as $value) {
                    $categoryId = $value->id;
                    $nestedData['id'] = $categoryId;
                    $edit = route('admin.big-category.edit', $categoryId);
                    $nestedData['name'] = $value->name;
                    $nestedData['koreanname'] = $value->koreanname;
                    $nestedData['image'] = ($value->logo_url) ? "<img src='".$value->logo_url."' alt='".$value->name."' class='requested-client-images m-1' />" : '';
                    $nestedData['order'] = $value->order;
                    $nestedData['status'] = $value->status_id == Status::ACTIVE ? '<span class="badge badge-success">Active</span>' : '<span class="badge badge-danger">In Active</span>';

                    $editButton =  "<a role='button' href='" . $edit . "' title='' data-original-title='Edit' class='btn btn-primary' data-toggle='tooltip'><i class='fa fa-edit'></i></a>";
                    $deleteButton = "<a href='javascript:void(0)' role='button' onclick='deleteBigCategory(" . $categoryId . ")' class='btn btn-danger' data-toggle='tooltip' data-original-title=" . Lang::get('general.delete') . "><i class='fa fa-trash'></button>";
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
            Log::info('End get big category list');
            return response()->json($jsonData);
        } catch (\Exception $ex) {
            Log::info('Exception in the get big category list');
            Log::info($ex);
            return response()->json([]);
        }
    }

    public function create(){
        $title = "Add Big Category";
        $categoryLanguages = [];
        $postLanguages = PostLanguage::whereNotIn('id', [4])->get();
        $statusData = Status::where('id', Status::ACTIVE)->orWhere('id', Status::INACTIVE)->pluck('name', 'id')->all();
        return view('admin.big_category.form', compact('title', 'statusData','postLanguages','categoryLanguages'));
    }

    public function store(Request $request)
    {
        try {
            DB::beginTransaction();
            Log::info('Start code for the add big category');
            $inputs = $request->all();
            $validator = Validator::make($request->all(), [
                'name' => 'required',
            ], [], [
                'name' => 'Name',
            ]);
            if ($validator->fails()) {
                notify()->error("Validation Error", "Error", "topRight");
                return redirect()->back()->withErrors($validator)->withInput();
            }
            $data = [
                "name" => $inputs['name'],
                "order" => $inputs['order'],
                "status_id" => $inputs['status_id'],
            ];

            if ($request->hasFile('logo')) {
                $categoryFolder = config('constant.big_category');
                if (!Storage::exists($categoryFolder)) {
                    Storage::makeDirectory($categoryFolder);
                }
                $logo = Storage::disk('s3')->putFile($categoryFolder, $request->file('logo'),'public');
                $fileName = basename($logo);
                $data['logo'] = $categoryFolder . '/' . $fileName;
            }

            $categoryData = BigCategory::create($data);

            foreach($inputs['cname'] as $key => $value) {
                BigCategoryLanguage::updateOrCreate([
                    'big_category_id' => $categoryData->id,
                    'post_language_id' => $key,
                ],[
                    'name' => $value,
                ]);
            }

            DB::commit();
            Log::info('End the code for the add big category');
            notify()->success("Big Category ". trans("messages.insert-success"), "Success", "topRight");
            return redirect()->route('admin.big-category.index');
        } catch (\Exception $e) {
            Log::info('Exception in the add big category');
            Log::info($e);
            notify()->error("Category ". trans("messages.insert-error"), "Error", "topRight");
            return redirect()->route('admin.big-category.index');
        }
    }

    public function edit($id)
    {
        $title = "Edit Big Category";
        $category = BigCategory::where('id', $id)->first();

        $postLanguages = PostLanguage::whereNotIn('id', [4])->get();
        $categoryLanguages = BigCategoryLanguage::where('big_category_id',$id)->pluck('name','post_language_id')->toArray();
        $statusData = Status::where('id', Status::ACTIVE)->orWhere('id', Status::INACTIVE)->pluck('name', 'id')->all();
        return view('admin.big_category.form', compact('title', 'category','statusData','postLanguages','categoryLanguages'));
    }

    public function update(Request $request, $id)
    {
        try {
            Log::info('Start code for the update big category');
            DB::beginTransaction();
            $inputs = $request->all();
            $validator = Validator::make($request->all(), [
                'name' => 'required',
            ], [], [
                'name' => 'Name',
            ]);
            if ($validator->fails()) {
                notify()->error("Validation Error", "Error", "topRight");
                return redirect()->back()->withErrors($validator)->withInput();
            }
            $data = [
                "name" => $inputs['name'],
                "order" => $inputs['order'],
                "status_id" => $inputs['status_id'],
            ];

            if ($request->hasFile('logo')) {
                $categoryFolder = config('constant.big_category');
                if (!Storage::exists($categoryFolder)) {
                    Storage::makeDirectory($categoryFolder);
                }
                $deleteCategory = BigCategory::whereId($id)->first();
                Storage::disk('s3')->delete($deleteCategory->logo);
                $logo = Storage::disk('s3')->putFile($categoryFolder, $request->file('logo'),'public');
                $fileName = basename($logo);
                $data['logo'] = $categoryFolder . '/' . $fileName;
            }

            $categoryData = BigCategory::updateOrCreate(['id' => $id],$data);

            foreach($inputs['cname'] as $key => $value) {
                $isChange = BigCategoryLanguage::updateOrCreate([
                    'big_category_id'   => $id,
                    'post_language_id'   => $key,
                ],[
                    'name' => $value,
                ]);
            }

            Log::info('End the code for the update big category');
            DB::commit();

            notify()->success("Big Category ". trans("messages.update-success"), "Success", "topRight");
            return redirect()->route('admin.big-category.index');
        } catch (\Exception $e) {
            Log::info('Exception in the update big category.');
            Log::info($e);
            notify()->error("Big Category ". trans("messages.update-error"), "Error", "topRight");
            return redirect()->route('admin.big-category.index');
        }
    }

    public function delete($id)
    {
        return view('admin.big_category.delete', compact('id'));
    }

    public function destroy($id)
    {
        try {
            DB::beginTransaction();
            $deleteCategory = BigCategory::whereId($id)->first();
            if($deleteCategory->logo){
                Storage::disk('s3')->delete($deleteCategory->logo);
            }
            BigCategoryLanguage::where('big_category_id',$id)->delete();
            BigCategory::where('id',$id)->delete();
            DB::commit();

            notify()->success("Big Category deleted successfully", "Success", "topRight");
            return redirect()->route('admin.big-category.index');
        } catch (\Exception $ex) {
            DB::rollBack();
            notify()->error("Failed to delete big category", "Error", "topRight");
            return redirect()->route('admin.big-category.index');
        }
    }

}
