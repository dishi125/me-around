<?php

namespace App\Http\Controllers\Admin;

use DB, Lang;
use Validator;
use App\Models\Country;
use App\Models\PostLanguage;
use Illuminate\Http\Request;
use App\Models\BrandCategory;
use App\Http\Controllers\Controller;
use App\Models\BrandCategoryLanguage;

class BrandCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $title = "Brand Category";

        $countries = Country::WhereNotNull('code')->where('is_show',1)->orderBy('priority')->get();
        $countries= collect($countries)->mapWithKeys(function ($value) {
            return [$value->code => $value->name];
        })->toArray();
        
        return view('admin.brand-category.index', compact('title','countries'));
    }

    public function getJsonData(Request $request){

        try {
            $columns = array(
                0 => 'brand_categories.name',
                1 => 'brand_category_languages.name',
                2 => 'brand_categories.sort_order',
            );

            $filter = $request->input('filter');
            $limit = $request->input('length');
            $start = $request->input('start');
            $search = $request->input('search.value');
            $draw = $request->input('draw');
            $order = $columns[$request->input('order.0.column')];
            $dir = $request->input('order.0.dir');

            $query = BrandCategory::leftjoin('brand_category_languages', function($query) {
                    $query->on('brand_category_languages.brand_category_id','=','brand_categories.id')
                    ->whereRaw('brand_category_languages.post_language_id = 1');
                })
                ->select('brand_categories.*','brand_category_languages.name as koreanname');
            if(!empty($filter)){
                $query = $query->where('brand_categories.country_code',$filter);
            }else{
                $query = $query->where('brand_categories.country_code','KR');
            }
            $totalData = $query->count();
            $totalFiltered = $totalData;

            if (!empty($search)) {
                $query = $query->where('brand_categories.name', 'LIKE', "%{$search}%");
                $totalFiltered = $query->count();
            }

            $categories = $query->offset($start)
                ->limit($limit)
                ->orderBy($order, $dir)
                ->get();

                $data = array();

                
            if (!empty($categories)) {
                foreach ($categories as $value) {
                    $categoryId = $value->id;
                    $nestedData['id'] = $categoryId;
                    $edit = route('admin.brand-category.edit', $categoryId);
                    $nestedData['name'] = $value->name;
                    $nestedData['koreanname'] = $value->koreanname;
                    $nestedData['order'] = $value->sort_order;
                    
                    $editButton =  "<a role='button' href='" . $edit . "' title='' data-original-title='Edit' class='btn btn-primary' data-toggle='tooltip'><i class='fa fa-edit'></i></a>";
                    $deleteButton = "<a href='javascript:void(0)' role='button' onclick='deleteCategory(" . $categoryId . ")' class='btn btn-danger' data-toggle='tooltip' data-original-title=" . Lang::get('general.delete') . "><i class='fa fa-trash'></button>";
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
            $jsonData = array(
                "draw" => intval(0),
                "recordsTotal" => intval(0),
                "recordsFiltered" => intval(0),
                "data" => [],
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
        $title = "Add Category";  
        $categoryLanguages = [];
        $postLanguages = PostLanguage::where('is_support',1)->whereNotIn('id', [4])->get();

        $countries = Country::WhereNotNull('code')->where('is_show',1)->orderBy('priority')->get();
        $countries= collect($countries)->mapWithKeys(function ($value) {
            return [$value->code => $value->name];
        })->toArray();

        return view('admin.brand-category.form', compact('title', 'postLanguages','categoryLanguages','countries'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
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
            $categoryData = BrandCategory::create([
                'name' => $inputs['name'],
                'country_code' => !empty($inputs['country_code']) ? $inputs['country_code'] : 'KR'
            ]);

            foreach($inputs['cname'] as $key => $value) {
                if(!empty($value)){
                    BrandCategoryLanguage::updateOrCreate([
                        'brand_category_id'   => $categoryData->id,
                        'post_language_id'   => $key,
                    ],[
                        'name' => $value,
                    ]);
                }
            }

            DB::commit();
            notify()->success("Category ". trans("messages.insert-success"), "Success", "topRight");
            return redirect()->route('admin.brand-category.index');

        } catch (\Exception $e) {
            DB::rollBack();
            notify()->error("Category ". trans("messages.insert-error"), "Error", "topRight");
            return redirect()->route('admin.brand-category.index');
        }
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
    public function edit(BrandCategory $brandCategory)
    {
        $title = "Edit Category";
        $postLanguages = PostLanguage::where('is_support',1)->whereNotIn('id', [4])->get();
        $categoryLanguages = BrandCategoryLanguage::where('brand_category_id',$brandCategory->id)->pluck('name','post_language_id')->toArray();
        
        $countries = Country::WhereNotNull('code')->where('is_show',1)->orderBy('priority')->get();
        $countries= collect($countries)->mapWithKeys(function ($value) {
            return [$value->code => $value->name];
        })->toArray();

        return view('admin.brand-category.form', compact('title', 'brandCategory','postLanguages','categoryLanguages','countries'));
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

            BrandCategory::where('id',$id)->update([
                'name' => $inputs['name'],
                'country_code' => !empty($inputs['country_code']) ? $inputs['country_code'] : 'KR'
            ]);

            foreach($inputs['cname'] as $key => $value) {
                if(!empty($value)){
                    BrandCategoryLanguage::updateOrCreate([
                        'brand_category_id'   => $id,
                        'post_language_id'   => $key,
                    ],[
                        'name' => $value,
                    ]);
                }
            }

            DB::commit();
            notify()->success("Category ". trans("messages.update-success"), "Success", "topRight");
            return redirect()->route('admin.brand-category.index');

        } catch (\Exception $e) {
            notify()->error("Category ". trans("messages.update-error"), "Error", "topRight");
            return redirect()->route('admin.brand-category.index');
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */

    public function delete($id)
    {   
        return view('admin.brand-category.delete', compact('id'));
    }

    public function destroy(BrandCategory $brandCategory)
    {
        try {
            DB::beginTransaction();
            BrandCategory::where('id',$brandCategory->id)->delete();
            DB::commit();

            notify()->success("Category deleted successfully", "Success", "topRight");
            return redirect()->route('admin.brand-category.index');
        } catch (\Exception $ex) {
            DB::rollBack();
            notify()->error("Failed to deleted category", "Error", "topRight");
            return redirect()->route('admin.brand-category.index');        
        }
    }

    public function updateOrder(Request $request)
    {
        $inputs = $request->all();
        try {
            $order = $inputs['order'] ?? '';
            $country = $inputs['country'] ?? '';
            if (!empty($order)) {
                foreach ($order as $value) {
                    BrandCategory::where('id', $value['id'])
                    ->when(!empty($country), function ($query) use ($country) {
                        $query->where('country_code', $country);
                    })
                    ->update(['sort_order' => $value['position']]);
                }
            }
            return response()->json([
                'success' => true
            ]);
        } catch (\Exception $ex) {
            return response()->json([
                'success' => false
            ]);
        }
    }
}
