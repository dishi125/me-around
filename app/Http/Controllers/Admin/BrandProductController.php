<?php

namespace App\Http\Controllers\Admin;

use Validator;
use App\Models\Brands;
use Illuminate\Http\Request;
use App\Models\BrandProducts;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Storage;

class BrandProductController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $title = "Brand Products";

        $brands = Brands::orderBy('sort_order')->get();

        $brands = collect($brands)->mapWithKeys(function ($value) {
            return [$value->id => $value->name];
        })->toArray();
        
        
        return view('admin.brands-product.index', compact('title','brands'));
    }

    public function getJsonData(Request $request){

        try {
            $columns = array(
                0 => 'brand_products.name',
                1 => 'brands.name',
                2 => 'brand_products.coin_amount',
                3 => 'brand_products.sort_order',
            );

            $filter = $request->input('filter');
            $limit = $request->input('length');
            $start = $request->input('start');
            $search = $request->input('search.value');
            $draw = $request->input('draw');
            $order = $columns[$request->input('order.0.column')];
            $dir = $request->input('order.0.dir');

            $query = BrandProducts::join('brands','brands.id','brand_products.brand_id')                
                ->select('brand_products.*','brands.name as brandname');

            if(!empty($filter)){
                $query = $query->where('brand_products.brand_id',$filter);
            }

            $totalData = $query->count();
            $totalFiltered = $totalData;

            if (!empty($search)) {
                $query = $query->where('brand_products.name', 'LIKE', "%{$search}%");
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
                    $edit = route('admin.brand-products.edit', $brandId);
                    $nestedData['name'] = $value->name;
                    $nestedData['brandname'] = $value->brandname;
                    $nestedData['coin_amount'] = $value->coin_amount;
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
        $title = "Add Brand Product";

        $brands = Brands::orderBy('sort_order')->get();

        $brands = collect($brands)->mapWithKeys(function ($value) {
            return [$value->id => $value->name];
        })->toArray();

        return view('admin.brands-product.form', compact('title','brands'));
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
                'coin_amount' => 'required',
                'brand_id' => 'required',
            ], [], [
                'name' => 'Name',
                'coin_amount' => 'Coin Amount',
                'brand_id' => 'Brand',
            ]);
            if ($validator->fails()) {
                notify()->error("Validation Error", "Error", "topRight");
                return redirect()->back()->withErrors($validator)->withInput();
            }
            DB::beginTransaction();

            $brandData = [
                'name' => $inputs['name'],
                'coin_amount' => $inputs['coin_amount'],
                'brand_id' => $inputs['brand_id']
            ];

            if ($request->hasFile('product_image')) {
                $brandsFolder = config('constant.brand-products');                
                if (!Storage::exists($brandsFolder)) {
                    Storage::makeDirectory($brandsFolder);
                }
                $product_image = Storage::disk('s3')->putFile($brandsFolder, $request->file('product_image'),'public');
                $fileName = basename($product_image);
                $brandData['product_image'] = $brandsFolder . '/' . $fileName;
            }   

            BrandProducts::create($brandData);

            DB::commit();
            notify()->success("Category ". trans("messages.insert-success"), "Success", "topRight");
            return redirect()->route('admin.brand-products.index');
        } catch (\Exception $e) {
            DB::rollBack();
            notify()->error("Category ". trans("messages.insert-error"), "Error", "topRight");
            return redirect()->route('admin.brand-products.index');
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
    public function edit(BrandProducts $brand_product)
    {
        $title = "Edit Brand Product";

        $brands = Brands::orderBy('sort_order')->get();

        $brands = collect($brands)->mapWithKeys(function ($value) {
            return [$value->id => $value->name];
        })->toArray();

        return view('admin.brands-product.form', compact('title','brands', 'brand_product'));
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
                'coin_amount' => 'required',
                'brand_id' => 'required',
            ], [], [
                'name' => 'Name',
                'coin_amount' => 'Coin Amount',
                'brand_id' => 'Brand',
            ]);
            if ($validator->fails()) {
                notify()->error("Validation Error", "Error", "topRight");
                return redirect()->back()->withErrors($validator)->withInput();
            }
            DB::beginTransaction();

            $brandData = [
                'name' => $inputs['name'],
                'coin_amount' => $inputs['coin_amount'],
                'brand_id' => $inputs['brand_id']
            ];

            if(isset($inputs['is_image_remove']) && !empty($inputs['is_image_remove'])){
                $brandData['product_image'] = null;
            }
            if ($request->hasFile('product_image')) {
                $brandsFolder = config('constant.brand-products');                
                if (!Storage::exists($brandsFolder)) {
                    Storage::makeDirectory($brandsFolder);
                }
                $product_image = Storage::disk('s3')->putFile($brandsFolder, $request->file('product_image'),'public');
                $fileName = basename($product_image);
                $brandData['product_image'] = $brandsFolder . '/' . $fileName;
            }

            BrandProducts::where('id',$id)->update($brandData);

            DB::commit();
            notify()->success("Product ". trans("messages.update-success"), "Success", "topRight");
            return redirect()->route('admin.brand-products.index');

        } catch (\Exception $e) {
            DB::rollBack();
            notify()->error("Product ". trans("messages.insert-error"), "Error", "topRight");
            return redirect()->route('admin.brand-products.index');
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
        return view('admin.brands-product.delete', compact('id'));
    }

    public function destroy($id)
    {
        try {
            DB::beginTransaction();
            BrandProducts::where('id',$id)->delete();
            DB::commit();

            notify()->success("Brand Product deleted successfully", "Success", "topRight");
            return redirect()->route('admin.brand-products.index');
        } catch (\Exception $ex) {
            DB::rollBack();
            notify()->error("Failed to deleted brand product", "Error", "topRight");
            return redirect()->route('admin.brand-products.index');        
        }
    }

    public function updateOrder(Request $request)
    {
        $inputs = $request->all();
        try {
            $order = $inputs['order'] ?? '';
            if (!empty($order)) {
                foreach ($order as $value) {
                    BrandProducts::where('id', $value['id'])
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
