<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PostLanguage;
use App\Models\ShopGlobalPrice;
use App\Models\ShopGlobalPriceCategory;
use App\Models\ShopGlobalPriceLanguage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class GlobalPriceController extends Controller
{
    public function index()
    {
        $title = 'Global Price Settings';

        return view('admin.important-setting.global-price-setting.index', compact('title'));
    }

    public function tableData(Request $request){
        $columns = array(
            1 => 'name',
            2 => 'korean_name',
        );

        $limit = $request->input('length');
        $start = $request->input('start');
        $search = $request->input('search.value');
        $draw = $request->input('draw');
        $order = $columns[$request->input('order.0.column')];
        $dir = $request->input('order.0.dir');

        try {
            $data = [];
            $query = ShopGlobalPriceCategory::with('globalprice');

            $totalData = count($query->get());
            $totalFiltered = $totalData;

            if (!empty($search)) {
                $query = $query->where(function($q) use ($search){
                    $q->where('name', 'LIKE', "%{$search}%");
                });

                $totalFiltered = $query->count();
            }

            $global_categories = $query->offset($start)
                ->limit($limit)
                ->orderBy($order, $dir)
                ->get();

            $count = 0;
            foreach($global_categories as $global_category){
                $table = '<table class="subclass text-center" cellpadding="0" cellspacing="0" border="0" style="padding-left:50px; width: 100%">';
                $table .= '<tr>
                                <th>Name</th>
                                <th>Korean Name</th>
                                <th>Price</th>
                                <th>Action</th>
                           </tr>';
                if (count($global_category->globalprice) > 0) {
                    foreach ($global_category->globalprice as $item) {
                        $edit = route('admin.important-setting.global-price-setting.price.edit',[$item->id]);
                        $type = "price";
                        $item_actions = "<a role='button' href='$edit' data-original-title='Edit Price' class='mx-1 btn btn-primary btn-sm' data-toggle='tooltip'>Edit</a>";
                        $item_actions .= "<a role='button' onclick=deleteGlobalPrice($item->id,'price') data-original-title='Delete Price' class='mx-1 btn btn-primary btn-sm' data-toggle='tooltip'>Delete</a>";

                        $table .= '<tr>
                                    <td>' . $item->name . '</td>
                                    <td>' . $item->korean_name . '</td>
                                    <td>' . $item->price . '</td>
                                    <td><div class="d-flex align-items-center">'.$item_actions.'</div></td>
                               </tr>';
                    }
                }
                else{
                    $table .= '<tr><td colspan="4" class="text-center">There is no records.</td></tr>';
                }
                $table .= '</table>';

                $actions = "";
                $addItemLink = route('admin.important-setting.global-price-setting.price.add',[$global_category->id]);
                $editLink = route('admin.important-setting.global-price-setting.price-category.edit',[$global_category->id]);
                $type = "category";

                $actions .= "<a role='button' href='$addItemLink' data-original-title='Add Item' class='mx-1 btn btn-primary btn-sm' data-toggle='tooltip'>Add Item</a>";
                $actions .= "<a role='button' href='$editLink' data-original-title='Edit Price Category' class='mx-1 btn btn-primary btn-sm' data-toggle='tooltip'>Edit</a>";
                $actions .= "<a role='button' onclick=deleteGlobalPrice($global_category->id,'category') data-original-title='Delete Price Category' class='mx-1 btn btn-primary btn-sm' data-toggle='tooltip'>Delete</a>";

                $data[$count]['name'] = $global_category->name;
                $data[$count]['korean_name'] = $global_category->korean_name;
                $data[$count]['actions'] = "<div class='d-flex align-items-center'>$actions</div>";
                $data[$count]['table1'] = $table;

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

    public function createPriceCategory()
    {
        $title = "Add Price Category";
        $postLanguages = PostLanguage::where('is_support', 1)->whereNotIn('id', [4])->get();
        $categoryLanguages = [];

        return view('admin.important-setting.global-price-setting.form', compact('title','postLanguages', 'categoryLanguages'));
    }

    public function storePriceCategory(Request $request)
    {
        try {
            DB::beginTransaction();
            Log::info('Start code for the add category');
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
            ];

            $categoryData = ShopGlobalPriceCategory::create($data);

            foreach($inputs['cname'] as $key => $value) {
                ShopGlobalPriceLanguage::updateOrCreate([
                    'entity_id' => $categoryData->id,
                    'entity_type' => 'category',
                    'language_id' => $key,
                ], [
                    'name' => $value,
                ]);
            }

            DB::commit();
            notify()->success("Global Price Category ". trans("messages.insert-success"), "Success", "topRight");
            return redirect()->route('admin.important-setting.global-price-setting.index');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::info($e);
            notify()->error("Global Price Category ". trans("messages.insert-error"), "Error", "topRight");
            return redirect()->route('admin.important-setting.global-price-setting.index');
        }
    }

    public function editPriceCategory($id)
    {
        $title = "Edit Price Category";
        $postLanguages = PostLanguage::where('is_support', 1)->whereNotIn('id', [4])->get();

        $categoryLanguages = ShopGlobalPriceLanguage::where('entity_id',$id)->where('entity_type','category')->pluck('name','language_id')->toArray();
        $englishCat = ShopGlobalPriceCategory::where('id', $id)->first();

        return view('admin.important-setting.global-price-setting.form', compact('title','postLanguages','categoryLanguages', 'englishCat'));
    }

    public function updatePriceCategory(Request $request, $id)
    {
        try {
            Log::info('Start code for the update category');
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
            ];

            $categoryData = ShopGlobalPriceCategory::updateOrCreate(['id' => $id],$data);

            foreach($inputs['cname'] as $key => $value) {
                ShopGlobalPriceLanguage::updateOrCreate([
                    'entity_id'   => $id,
                    'entity_type' => 'category',
                    'language_id'   => $key,
                ],[
                    'name' => $value,
                ]);
            }
            DB::commit();
            notify()->success("Global Price Category ". trans("messages.update-success"), "Success", "topRight");
            return redirect()->route('admin.important-setting.global-price-setting.index');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::info($e);
            notify()->error("Global Price Category ". trans("messages.update-error"), "Error", "topRight");
            return redirect()->route('admin.important-setting.global-price-setting.index');
        }
    }

    public function createPrice($id)
    {
        $title = "Add Price";
        $postLanguages = PostLanguage::where('is_support', 1)->whereNotIn('id', [4])->get();
        $price_category_id = $id;
        $priceLanguages = [];

        return view('admin.important-setting.global-price-setting.item_form', compact('title','postLanguages', 'price_category_id', 'priceLanguages'));
    }

    public function storePrice(Request $request)
    {
        try {
            DB::beginTransaction();
            Log::info('Start code for the add price');
            $inputs = $request->all();
            $validator = Validator::make($request->all(), [
                'name' => 'required',
                'price' => 'required',
            ], [], [
                'name' => 'Name',
                'price' => 'Price',
            ]);
            if ($validator->fails()) {
                notify()->error("Validation Error", "Error", "topRight");
                return redirect()->back()->withErrors($validator)->withInput();
            }

            $data = [
                "shop_global_price_category_id" => $inputs['price_category_id'],
                "name" => $inputs['name'],
                "price" => $inputs['price'],
                "discount" => isset($inputs['discount_price']) ? $inputs['discount_price'] : 0,
            ];

            $priceData = ShopGlobalPrice::create($data);

            foreach($inputs['cname'] as $key => $value) {
                ShopGlobalPriceLanguage::updateOrCreate([
                    'entity_id' => $priceData->id,
                    'entity_type' => 'price',
                    'language_id' => $key,
                ], [
                    'name' => $value,
                ]);
            }

            DB::commit();
            notify()->success("Global Price ". trans("messages.insert-success"), "Success", "topRight");
            return redirect()->route('admin.important-setting.global-price-setting.index');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::info($e);
            notify()->error("Global Price ". trans("messages.insert-error"), "Error", "topRight");
            return redirect()->route('admin.important-setting.global-price-setting.index');
        }
    }

    public function editPrice($id)
    {
        $title = "Edit Price";
        $postLanguages = PostLanguage::where('is_support', 1)->whereNotIn('id', [4])->get();

        $priceLanguages = ShopGlobalPriceLanguage::where('entity_id',$id)->where('entity_type','price')->pluck('name','language_id')->toArray();
        $englishPrice = ShopGlobalPrice::where('id', $id)->first();

        return view('admin.important-setting.global-price-setting.item_form', compact('title','postLanguages', 'priceLanguages', 'englishPrice'));
    }

    public function updatePrice(Request $request, $id)
    {
        try {
            Log::info('Start code for the update price');
            DB::beginTransaction();
            $inputs = $request->all();
            $validator = Validator::make($request->all(), [
                'name' => 'required',
                'price' => 'required',
            ], [], [
                'name' => 'Name',
                'price' => 'Price',
            ]);

            if ($validator->fails()) {
                notify()->error("Validation Error", "Error", "topRight");
                return redirect()->back()->withErrors($validator)->withInput();
            }

            $data = [
                "name" => $inputs['name'],
                "price" => $inputs['price'],
                "discount" => isset($inputs['discount_price']) ? $inputs['discount_price'] : 0,
            ];

            $priceData = ShopGlobalPrice::updateOrCreate(['id' => $id],$data);

            foreach($inputs['cname'] as $key => $value) {
                ShopGlobalPriceLanguage::updateOrCreate([
                    'entity_id' => $priceData->id,
                    'entity_type' => 'price',
                    'language_id' => $key,
                ], [
                    'name' => $value,
                ]);
            }
            DB::commit();
            notify()->success("Global Price ". trans("messages.update-success"), "Success", "topRight");
            return redirect()->route('admin.important-setting.global-price-setting.index');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::info($e);
            notify()->error("Global Price ". trans("messages.update-error"), "Error", "topRight");
            return redirect()->route('admin.important-setting.global-price-setting.index');
        }
    }

    public function delete($id,$type)
    {
        $title = 'Global Price';
        if($type == "category"){
            $title = 'Global Price Category';
        }
        return view('admin.important-setting.global-price-setting.delete', compact('id','type','title'));
    }

    public function destroy($id,$type){
        try {
            if(!empty($id) && !empty($type)){
                if($type == 'price'){
                    ShopGlobalPrice::where('id',$id)->delete();
                    $GlobalPriceLanguages = ShopGlobalPriceLanguage::where('entity_type','price')->where('entity_id',$id)->delete();

                    return response()->json(array(
                        'response' => true,
                        'message' => "Global price deleted successfully."
                    ));
                }

                if($type == 'category'){
                    $Category = ShopGlobalPriceCategory::find($id);
                    if($Category){
                        $GlobalPriceLanguages = ShopGlobalPriceLanguage::where('entity_type','category')->where('entity_id',$id)->delete();
                        $Items = ShopGlobalPrice::where('shop_global_price_category_id',$id)->get();
                        foreach ($Items as $item){
                            ShopGlobalPriceLanguage::where('entity_type', 'price')->where('entity_id',$item->id)->delete();
                            $item->delete();
                        }
                        $catDelete = ShopGlobalPriceCategory::where('id',$id)->delete();
                    }
                    return response()->json(array(
                        'response' => true,
                        'message' => "Global price category deleted successfully."
                    ));
                }
            } else{
                return response()->json(array(
                    'response' => false,
                    'message' => "All parameters are required."
                ));
            }
        } catch (\Exception $e) {
            return response()->json(array(
                'response' => false,
                'message' => trans("messages.save-error")
            ), 400);
        }
    }

}
