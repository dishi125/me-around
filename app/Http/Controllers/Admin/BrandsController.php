<?php

namespace App\Http\Controllers\Admin;

use DB, Validator, Lang;
use App\Models\Brands;
use App\Models\Country;
use Illuminate\Http\Request;
use App\Models\BrandCategory;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;

class BrandsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $title = "Brands";

        $category = BrandCategory::leftjoin('countries','countries.code','brand_categories.country_code')
            ->select(
                'brand_categories.id',
                'brand_categories.name',
                'countries.name as country_name'
            )
            ->orderBy('countries.priority')
            ->get();

        $category = collect($category)->groupBy('country_name');

        $category= collect($category)->map(function ($item){
            return collect($item)->mapWithKeys(function ($value) {
                return [$value->id => $value->name];
            });
        })->toArray();
        
        return view('admin.brands.index', compact('title','category'));
    }

    public function getJsonData(Request $request){

        try {
            $columns = array(
                0 => 'brands.name',
                1 => 'brand_categories.name',
                2 => 'brands.sort_order',
            );

            $filter = $request->input('filter');
            $limit = $request->input('length');
            $start = $request->input('start');
            $search = $request->input('search.value');
            $draw = $request->input('draw');
            $order = $columns[$request->input('order.0.column')];
            $dir = $request->input('order.0.dir');

            $query = Brands::join('brand_categories','brand_categories.id','brands.category_id')                
                ->select('brands.*','brand_categories.name as categoryname');

            if(!empty($filter)){
                $query = $query->where('brands.category_id',$filter);
            }

            $totalData = $query->count();
            $totalFiltered = $totalData;

            if (!empty($search)) {
                $query = $query->where('brands.name', 'LIKE', "%{$search}%");
                $totalFiltered = $query->count();
            }

            $brands = $query->offset($start)
                ->limit($limit)
                ->orderBy($order, $dir)
                ->get();

                $data = array();

            if (!empty($brands)) {
                foreach ($brands as $value) {
                    $brandId = $value->id;
                    $nestedData['id'] = $brandId;
                    $edit = route('admin.brands.edit', $brandId);
                    $nestedData['name'] = $value->name;
                    $nestedData['categoryname'] = $value->categoryname;
                    $nestedData['order'] = $value->sort_order;
                    
                    $editButton =  "<a role='button' href='" . $edit . "' title='' data-original-title='Edit' class='btn btn-primary' data-toggle='tooltip'><i class='fa fa-edit'></i></a>";
                    $deleteButton = "<a href='javascript:void(0)' role='button' onclick='deleteCategory(" . $brandId . ")' class='btn btn-danger' data-toggle='tooltip' data-original-title=" . Lang::get('general.delete') . "><i class='fa fa-trash'></button>";
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
        $title = "Add Brand";

        $category = BrandCategory::leftjoin('countries','countries.code','brand_categories.country_code')
            ->select(
                'brand_categories.id',
                'brand_categories.name',
                'countries.name as country_name'
            )
            ->orderBy('countries.priority')
            ->get();

        $category = collect($category)->groupBy('country_name');

        $category= collect($category)->map(function ($item){
            return collect($item)->mapWithKeys(function ($value) {
                return [$value->id => $value->name];
            });
        })->toArray();

        return view('admin.brands.form', compact('title','category'));
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
        try{
            $validator = Validator::make($request->all(), [
                'name' => 'required',
                'category_id' => 'required',
            ], [], [
                'name' => 'Name',
                'category_id' => 'Category',
            ]);
            if ($validator->fails()) {
                notify()->error("Validation Error", "Error", "topRight");
                return redirect()->back()->withErrors($validator)->withInput();
            }
            DB::beginTransaction();

            $brandData = [
                'name' => $inputs['name'],
                'category_id' => $inputs['category_id']
            ];

            if ($request->hasFile('brand_logo')) {
                $brandsFolder = config('constant.brands');                
                if (!Storage::exists($brandsFolder)) {
                    Storage::makeDirectory($brandsFolder);
                }
                $brand_logo = Storage::disk('s3')->putFile($brandsFolder, $request->file('brand_logo'),'public');
                $fileName = basename($brand_logo);
                $brandData['brand_logo'] = $brandsFolder . '/' . $fileName;
            }   

            Brands::create($brandData);

            DB::commit();
            notify()->success("Category ". trans("messages.insert-success"), "Success", "topRight");
            return redirect()->route('admin.brands.index');
        } catch (\Exception $e) {
            DB::rollBack();
            notify()->error("Category ". trans("messages.insert-error"), "Error", "topRight");
            return redirect()->route('admin.brands.index');
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
    public function edit(Brands $brand)
    {
        $title = "Edit Brand";

        $category = BrandCategory::leftjoin('countries','countries.code','brand_categories.country_code')
            ->select(
                'brand_categories.id',
                'brand_categories.name',
                'countries.name as country_name'
            )
            ->orderBy('countries.priority')
            ->get();

        $category = collect($category)->groupBy('country_name');

        $category= collect($category)->map(function ($item){
            return collect($item)->mapWithKeys(function ($value) {
                return [$value->id => $value->name];
            });
        })->toArray();

        return view('admin.brands.form', compact('title','category', 'brand'));
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

        try{
            $validator = Validator::make($request->all(), [
                'name' => 'required',
                'category_id' => 'required',
            ], [], [
                'name' => 'Name',
                'category_id' => 'Category',
            ]);
            if ($validator->fails()) {
                notify()->error("Validation Error", "Error", "topRight");
                return redirect()->back()->withErrors($validator)->withInput();
            }
            DB::beginTransaction();

            $brandData = [
                'name' => $inputs['name'],
                'category_id' => $inputs['category_id']
            ];
            if(isset($inputs['is_image_remove']) && !empty($inputs['is_image_remove'])){
                $brandData['brand_logo'] = null;
            }
            if ($request->hasFile('brand_logo')) {
                $brandsFolder = config('constant.brands');                
                if (!Storage::exists($brandsFolder)) {
                    Storage::makeDirectory($brandsFolder);
                }
                $brand_logo = Storage::disk('s3')->putFile($brandsFolder, $request->file('brand_logo'),'public');
                $fileName = basename($brand_logo);
                $brandData['brand_logo'] = $brandsFolder . '/' . $fileName;
            }   

            Brands::where('id',$id)->update($brandData);

            DB::commit();
            notify()->success("Category ". trans("messages.update-success"), "Success", "topRight");
            return redirect()->route('admin.brands.index');

        } catch (\Exception $e) {
            DB::rollBack();
            notify()->error("Category ". trans("messages.insert-error"), "Error", "topRight");
            return redirect()->route('admin.brands.index');
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
        return view('admin.brands.delete', compact('id'));
    }

    public function destroy(Brands $brand)
    {
        try {
            DB::beginTransaction();
            Brands::where('id',$brand->id)->delete();
            DB::commit();

            notify()->success("Brand deleted successfully", "Success", "topRight");
            return redirect()->route('admin.brands.index');
        } catch (\Exception $ex) {
            DB::rollBack();
            notify()->error("Failed to deleted brand", "Error", "topRight");
            return redirect()->route('admin.brands.index');        
        }
    }

    public function updateOrder(Request $request)
    {
        $inputs = $request->all();
        try {
            $order = $inputs['order'] ?? '';
            if (!empty($order)) {
                foreach ($order as $value) {
                    Brands::where('id', $value['id'])
                    /* ->when(!empty($country), function ($query) use ($country) {
                        $query->where('country_code', $country);
                    }) */
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
