<?php

namespace App\Http\Controllers\Admin;

use App\Jobs\HiddenCategory;
use App\Models\UserHiddenCategory;
use Exception;
use Validator;
use App\Models\Status;
use App\Models\Country;
use App\Models\Category;
use App\Models\BigCategory;
use App\Models\EntityTypes;
use App\Models\MenuSetting;
use App\Models\PostLanguage;
use Illuminate\Http\Request;
use App\Models\CategoryTypes;
use App\Models\CategoryLanguage;
use App\Models\CategorySettings;
use Illuminate\Http\JsonResponse;
use App\Models\BigCategorySetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;

class CategorySettingsAdminController extends Controller
{
    public function index()
    {
        $title = 'Category Settings';
        $countries = Country::WhereNotNull('code')->where('is_show',1)->orderBy('priority')->get();
        $countries = collect($countries)->mapWithKeys(function ($value) {
            return [$value->code => $value->name];
        })->toArray();
        $big_categories = BigCategory::orderBy('order')->get();

        $menuItem = MenuSetting::select("*")->whereNull('country_code')->whereIn('menu_key',MenuSetting::MENU_CARD_LIST)->get();

        return view('admin.important-setting.category-setting.index-card', compact('title', 'countries', 'big_categories','menuItem'));
    }

    public function cardData(Request $request){
        $inputs = $request->all();
        try {
            $country = $inputs['country'] ?? '';
            if (!empty($country)) {
                $insertData = Category::where('status_id', Status::ACTIVE)
                    ->where('category_type_id', EntityTypes::SHOP)
                    ->where('parent_id', 0)
                    ->get();

                foreach ($insertData as $item) {
                    CategorySettings::firstOrCreate([
                        'category_id' => $item->id,
                        'country_code' => $country,
                    ], [
                        'is_show' => $item->is_show,
                        'is_hidden' => $item->is_hidden,
                        'order' => $item->order,
                    ]);
                }
            }

            $menuQuery = MenuSetting::select('*')->whereIn('menu_key',MenuSetting::MENU_CARD_LIST);

            if(!empty($country)){
                $menuQuery = $menuQuery->where('country_code',$country);
            }else{
                $menuQuery = $menuQuery->whereNull('country_code');
            }

            $menu_detail = $menuQuery->pluck('menu_name','menu_key');

            $query = Category::where('category.status_id', Status::ACTIVE)
                ->where('category.category_type_id', EntityTypes::SHOP)
                ->where('category.parent_id', 0);

            if (!empty($country)) {
                $query = $query->join('category_settings', 'category_settings.category_id', 'category.id')
                    ->where('category_settings.country_code', $country);

                $order = 'category_settings.order';

                $query = $query->select('category.id as category_id','category.logo', 'category.name','category_settings.id','category_settings.is_show','category_settings.is_hidden','category_settings.order','category_settings.menu_key');
            }else{
                $query = $query->select('category.*','category.id as category_id');

                $order = 'category.order';
            }

            $result = $query->orderBy($order, 'ASC')->get();

            $result = collect($result)->groupBy('menu_key');

            $responseData = [];
            if(!empty($result)){
                foreach ($result as $key => $catData) {
                    $html = '';
                    foreach ($catData as $value) {
                        $toggle = '';
                        $checked = $value->is_show ? 'checked' : '';
                        $toggle .= '<input type="checkbox" class="toggle-btn-display toggle-btn" ' . $checked . ' data-id="' . $value->id . '" data-toggle="toggle" data-height="20" data-size="sm" data-onstyle="success" data-offstyle="danger">';

                        $hiddenChecked = $value->is_hidden ? 'checked' : '';
                        $hidden = '<input type="checkbox" class="toggle-btn-display toggle-hidden-button" ' . $hiddenChecked . ' data-id="' . $value->id . '" data-toggle="toggle" data-height="20" data-size="sm" data-on="Hidden" data-off="Visible" data-onstyle="default" data-offstyle="success">';

                        $editLink = route('admin.important-setting.category-setting.edit',[$value->category_id, $country]);
                        $editButton =  "<a role='button' href='" . $editLink . "' title='' data-original-title='Edit' class='btn btn-primary p-0 mb-1' data-toggle='tooltip'><i class='fa fa-edit'></i></a>";

                        $html .= '<div class="list-group-item d-flex" data-cat-id="'.$value->id.'" draggable="true">';
                            $html .= '<div class="list-image"><img src="'.$value->logo.'" alt="'.$value->name.'" class="requested-client-images m-1" /></div>';
                                $html .= "<div class='name-group'>";
                                    $html .= "<div class='d-flex justify-content-between'>";
                                    $html .= '<div class="list-name">'.$value->name.'</div>';
                                    $html .= '<div class="list-edit">'.$editButton.'</div>';
                                $html .= '</div>';
                                $html .= "<div class='d-flex justify-content-between'>";
                                    $html .= '<div class="list-toggle">'.$toggle.'</div>';
                                    $html .= '<div class="list-hidden">'.$hidden.'</div>';
                                $html .= '</div>';
                            $html .= '</div>';
                        $html .= '</div>';
                    }
                    $responseData[$key] = $html;
                }
            }

            return response()->json(["title" => $menu_detail, "html" => $responseData]);
        } catch (\Throwable $th) {
            return response()->json(["title" => '', "html" => ""]);
        }
    }

    public function updateCardOrder(Request $request)
    {
        $inputs = $request->all();
//        dd($inputs);
        try {
            $country = $inputs['country'] ?? '';
            $from_card = $inputs['from_card'] ?? '';
            $to_card = $inputs['to_card'] ?? '';
            $item_id = $inputs['item_id'] ?? '';
            $new_index = $inputs['new_index'] ?? '';
            $old_index = $inputs['old_index'] ?? '';

            //code start - dishita
            if($from_card == $to_card){
                $from_order = $inputs['from_order'] ?? '';
                foreach($from_order as $value){
                    if(!empty($country)){
                        CategorySettings::where('id',$value['id'])->where('country_code',$country)->update(['order' => $value['position']]);
                    }else{
                        Category::whereId($value['id'])->update(['order' => $value['position']]);
                    }
                }

                return ['status' => 1];
            }
            //code end - dishita

            if(!empty($country)){
                $category = CategorySettings::where('country_code',$country)->where('menu_key' , $to_card)->orderBy('order','ASC')->get();
            }else{
                $category = Category::where('category.status_id', Status::ACTIVE)
                ->where('category.category_type_id', EntityTypes::SHOP)
                ->where('category.parent_id', 0)
                ->where('menu_key' , $to_card)
                ->orderBy('order','ASC')->get();
            }
            if(!empty($category)){
                $maxOrder = $updateOrder = $category->max('order');
                $totalCategory = count($category);
                $checkIndex = ($from_card != $to_card) ? $new_index : min($new_index,$old_index);
                foreach ($category as $key => $value) {
                    if($from_card != $to_card){
                        if($key >= $checkIndex){
                            $updateOrder++;
                            $value->update(['order' => $updateOrder]);
                        }
                    }
                    else{
                        if($new_index > $old_index){
                            if($key >= $old_index){
                                if($value->id == $item_id){
                                    $value->update(['order' => ($maxOrder + $new_index + 1)]);
                                }else{
                                    $updateOrder++;
                                    $value->update(['order' => $updateOrder]);
                                }
                                if($key == $new_index){
                                    $updateOrder = ($maxOrder + $new_index + 1);
                                    $updateOrder++;
                                }
                            }
                        }
                        else{
                            if($key >= $new_index){
                                $updateOrder++;
                                if($value->id == $item_id){
                                    $value->update(['order' => $maxOrder]);
                                }else{
                                    $value->update(['order' => $updateOrder]);
                                }
                            }
                        }
                    }
                }
            }
            if($from_card != $to_card){
                if(!empty($country)){
                    CategorySettings::whereId($item_id)->update(['menu_key' => $to_card,'order' => $maxOrder]);
                }else{
                    Category::whereId($item_id)->update(['menu_key' => $to_card,'order' => $maxOrder]);
                }
            }
            return ['status' => 1];

        //code start - dishita
            /*$from_order = $inputs['from_order'] ?? '';
            $to_order = $inputs['to_order'] ?? '';
            if(!empty($from_order)){
                foreach($from_order as $value){
                    if(!empty($country)){
                        CategorySettings::where('id',$value['id'])->where('country_code',$country)->update(['order' => $value['position']]);
                    }else{
                        Category::whereId($value['id'])->update(['order' => $value['position']]);
                    }
                }
            }

            if(!empty($to_order) && count($to_order)>0){
                if(!empty($country)){
                    $from_item_data = CategorySettings::where('category_id',$item_id)->where('country_code',$country)->first();
                }else{
                    $from_item_data = Category::whereId($item_id)->first();
                }

                foreach($to_order as $to_value){
                    if(!empty($country)){
                        $to_item_data = CategorySettings::where('category_id',$to_value['id'])->where('country_code',$country)->where('menu_key',$to_card)->first();
                        if ($to_item_data){
                            $to_item_data->order = $to_value['position'];
                            $to_item_data->save();
                        }
                        else {
                            $check_exist = CategorySettings::where('category_id',$from_item_data->category_id)->where('country_code',$from_item_data->country_code)->where('menu_key',$to_card)->first();
                            if (!$check_exist) {
                                $category_settings = CategorySettings::create([
                                    'category_id' => $from_item_data->category_id,
                                    'is_show' => $from_item_data->is_show,
                                    'order' => $to_value['position'],
                                    'country_code' => $from_item_data->country_code,
                                    'is_hidden' => $from_item_data->is_hidden,
                                    'status_id' => $from_item_data->status_id,
                                    'menu_key' => $to_card,
                                ]);
                            }
                            $from_item_data->delete();
                        }
                    }
                    else {
                        $to_item_data = Category::whereId($to_value['id'])->where('menu_key',$to_card)->first();
                        if ($to_item_data){
                            $to_item_data->order = $to_value['position'];
                            $to_item_data->save();
                        }
                        else {
                            $check_exist = Category::where('id',$item_id)->where('menu_key',$to_card)->first();
                            if (!$check_exist) {
                                $category = Category::Create([
                                    'category_type_id' => $from_item_data->category_type_id,
                                    'type' => $from_item_data->type,
                                    'parent_id' => $from_item_data->parent_id,
                                    'name' => $from_item_data->name,
                                    'logo' => $from_item_data->logo,
                                    'status_id' => $from_item_data->status_id,
                                    'order' => $to_value['position'],
                                    'is_show' => $from_item_data->is_show,
                                    'is_hidden' => $from_item_data->is_hidden,
                                    'menu_key' => $to_card,
                                ]);
                            }
                            $from_item_data->delete();
                        }
                    }
                }
            }*/
            //code end - dishita

        } catch (\Throwable $th) {

        }
    }

    public function tableData(Request $request)
    {
        try {
            $columns = array(
                0 => 'category.name',
                1 => 'is_show',
                3 => 'category.order',
            );

            $limit = $request->input('length');
            $filter = $request->input('filter');
            $start = $request->input('start');
            $search = $request->input('search.value');
            $draw = $request->input('draw');
            $order = $columns[$request->input('order.0.column')];
            $dir = $request->input('order.0.dir');

            if (!empty($filter)) {
                $insertData = Category::where('status_id', Status::ACTIVE)
                    ->where('category_type_id', EntityTypes::SHOP)
                    ->where('parent_id', 0)
                    ->get();

                foreach ($insertData as $item) {
                    CategorySettings::firstOrCreate([
                        'category_id' => $item->id,
                        'country_code' => $filter,
                    ], [
                        'is_show' => $item->is_show,
                        'is_hidden' => $item->is_hidden,
                        'order' => $item->order,
                    ]);
                }
            }

            $query = Category::where('category.status_id', Status::ACTIVE)
                ->where('category.category_type_id', EntityTypes::SHOP)
                ->where('category.parent_id', 0);

            if (!empty($filter)) {
                $query = $query->join('category_settings', 'category_settings.category_id', 'category.id')
                    ->where('category_settings.country_code', $filter);

                $order = 'category_settings.order';

                $query = $query->select('category.id as category_id','category.logo', 'category.name','category_settings.id','category_settings.is_show','category_settings.is_hidden','category_settings.order');
            }else{
                $query = $query->select('category.*','category.id as category_id');
            }

            $totalData = $query->count();
            $totalFiltered = $totalData;
            if (!empty($search)) {
                $query = $query->where(function ($q) use ($search) {
                    $q->where('category.name', 'LIKE', "%{$search}%");
                });
                $totalFiltered = $query->count();
            }

            $settings = $query->offset($start)
                ->limit($limit)
                ->orderBy($order, $dir)
                ->get();

            $data = array();
            if (!empty($settings)) {
                foreach ($settings as $key => $value) {

                    $data[$key]['id'] = $value->id;
                    $data[$key]['category_name'] = $value->name;
                    $data[$key]['menu_order'] = $value->order;
                    $data[$key]['image'] = "<img src='".$value->logo."' alt='".$value->name."' class='requested-client-images m-1' />";

                    $toggle = '';
                    $checked = $value->is_show ? 'checked' : '';
                    $toggle .= '<input type="checkbox" class="toggle-btn-display toggle-btn" ' . $checked . ' data-id="' . $value->id . '" data-toggle="toggle" data-height="20" data-size="sm" data-onstyle="success" data-offstyle="danger">';

                    $data[$key]['is_show'] = $toggle; //;

                    $hiddenChecked = $value->is_hidden ? 'checked' : '';
                    $hidden = '<input type="checkbox" class="toggle-btn-display toggle-hidden-button" ' . $hiddenChecked . ' data-id="' . $value->id . '" data-toggle="toggle" data-height="20" data-size="sm" data-on="Hidden" data-off="Visible" data-onstyle="default" data-offstyle="success">';
                    $data[$key]['is_hidden'] = $hidden; //;

                    $editLink = route('admin.important-setting.category-setting.edit',[$value->category_id, $filter]);
                    $editButton =  "<a role='button' href='" . $editLink . "' title='' data-original-title='Edit' class='btn btn-primary' data-toggle='tooltip'><i class='fa fa-edit'></i></a>";
                    $data[$key]['is_show'] = $toggle;
                    $data[$key]['actions'] = "$editButton";
                }
            }
            $jsonData = array(
                "draw" => intval($draw),
                "recordsTotal" => intval($totalData),
                "recordsFiltered" => intval($totalFiltered),
                "data" => $data
            );
            return response()->json($jsonData);
        } catch (Exception $ex) {
            Log::info($ex);
            $jsonData = array(
                "draw" => 0,
                "recordsTotal" => 0,
                "recordsFiltered" => 0,
                "data" => []
            );
            return response()->json($jsonData);
        }
    }

    public function updateOnOff(Request $request): JsonResponse
    {
        $inputs = $request->all();
        try {
            $country = $inputs['country'] ?? '';
            $data_id = $inputs['data_id'] ?? '';
            $checked = (string)$inputs['checked'];
            $isChecked = ($checked == 'true') ? 1 : 0;
            if (!empty($data_id)) {
                if (!empty($country)) {
                    CategorySettings::where('id',$data_id)->update(['is_show' => $isChecked]);
                } else {
                    Category::where('id', $data_id)->update(['is_show' => $isChecked]);
                }
            }
            return response()->json([
                'success' => true
            ]);
        } catch (Exception $ex) {
            Log::info($ex);
            return response()->json([
                'success' => false
            ]);
        }
    }

    public function updateOnOffHidden(Request $request): JsonResponse
    {
        $inputs = $request->all();
        try {
            $country = $inputs['country'] ?? '';
            $data_id = $inputs['data_id'] ?? '';
            $checked = (string)$inputs['checked'];
            $isChecked = ($checked == 'true') ? 1 : 0;
            if (!empty($data_id)) {
                if (!empty($country)) {
                    CategorySettings::where('id',$data_id)->update(['is_hidden' => $isChecked]);
                    $category_id = CategorySettings::where('id',$data_id)->pluck('category_id')->first();
                } else {
                    Category::where('id', $data_id)->update(['is_hidden' => $isChecked]);
                    $category_id = $data_id;
                }
            }

            return response()->json([
                'success' => true
            ]);
        } catch (Exception $ex) {
            Log::info($ex);
            return response()->json([
                'success' => false
            ]);
        }
    }

    public function updateOrder(Request $request)
    {
        $inputs = $request->all();
        try {
            $order = $inputs['order'] ?? '';
            $country = $inputs['country'] ?? '';
            if (!empty($order)) {
                if(!empty($country)){
                    foreach ($order as $value) {
                        CategorySettings::where('id', $value['id'])->update(['order' => $value['position']]);
                    }
                }else {
                    foreach ($order as $value) {
                        Category::where('id', $value['id'])->update(['order' => $value['position']]);
                    }
                }
            }
            return response()->json([
                'success' => true
            ]);
        } catch (Exception $ex) {
            Log::info($ex);
            return response()->json([
                'success' => false
            ]);
        }
    }

    public function updateBigcategoryOrder(Request $request){
        try {
            $inputs = $request->all();
            $myValue=  array();
            parse_str($inputs['orders'], $myValue);
//        print_r($myValue);
            $order_items = $myValue['item'];
            $country = $inputs['country'];
            foreach ($order_items as $key=>$order_item){
                if (!empty($country)) {
                    BigCategorySetting::where('big_category_id', $order_item)->where('country_code', $country)->update(['order' => $key]);
                }
                else {
                    BigCategory::where('id', $order_item)->update(['order' => $key]);
                }
            }

            return response()->json([
                'success' => true
            ]);
        } catch (Exception $ex) {
            return response()->json([
                'success' => false
            ]);
        }
    }

    public function createCategory()
    {
        $title = "Add Category";
        $categoryLanguages = [];
        $postLanguages = PostLanguage::whereNotIn('id', [4])->get();
        $statusData = Status::where('id', Status::ACTIVE)->orWhere('id', Status::INACTIVE)->pluck('name', 'id')->all();
        $action = "add";

        return view('admin.important-setting.category-setting.form', compact('title', 'statusData','postLanguages','categoryLanguages','action'));
    }

    public function storeCategroy(Request $request)
    {
        $inputs = $request->all();
//        dd($inputs);
        try {
            DB::beginTransaction();
            Log::info('Start code for the add category');
            $validator = Validator::make($request->all(), [
                'name' => 'required',
                'order' => 'required',
            ], [], [
                'name' => 'Name',
                'order' => 'Order',
            ]);
            if ($validator->fails()) {
                notify()->error("Validation Error", "Error", "topRight");
                return redirect()->back()->withErrors($validator)->withInput();
            }

            $data = [
                "name" => $inputs['name'],
                "category_type_id" => CategoryTypes::SHOP,
                "order" => $inputs['order'],
                "status_id" => $inputs['status_id'],
                "parent_id" => 0,
                "is_hidden" => $inputs['is_hidden'],
                "is_show" => $inputs['is_show'],
            ];

            if ($request->hasFile('logo')) {
                $categoryFolder = config('constant.category');
                if (!Storage::exists($categoryFolder)) {
                    Storage::makeDirectory($categoryFolder);
                }
                $logo = Storage::disk('s3')->putFile($categoryFolder, $request->file('logo'), 'public');
                $fileName = basename($logo);
                $data['logo'] = $categoryFolder . '/' . $fileName;
            }

            $categoryData = Category::create($data);

            foreach ($inputs['cname'] as $key => $value) {
                CategoryLanguage::updateOrCreate([
                    'category_id' => $categoryData->id,
                    'post_language_id' => $key,
                ], [
                    'name' => $value,
                ]);
            }
            $categorySettings = CategorySettings::groupBy('country_code')->pluck('country_code');

            if (!empty($categorySettings)) {
                foreach ($categorySettings as $country) {
                    CategorySettings::firstOrCreate([
                        'category_id' => $categoryData->id,
                        'country_code' => $country,
                    ], [
                        "is_hidden" => $inputs['is_hidden'],
                        "status_id" => $inputs['status_id'],
                        'is_show' => $inputs['is_show'],
                        'order' => $inputs['order'],
                    ]);
                }
            }

            //code start - dishita
            if ($inputs['is_hidden'] == 1){
                $data = [
                    'is_hidden' => $inputs['is_hidden'],
                    'category_id' => $categoryData->id,
                ];
                HiddenCategory::dispatch($data);
            }
            //code end - dishita

            DB::commit();
            notify()->success("Category " . trans("messages.insert-success"), "Success", "topRight");
            return redirect()->route('admin.important-setting.category-setting.index');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::info($e);
            notify()->error("Category ". trans("messages.insert-error"), "Error", "topRight");
            return redirect()->route('admin.important-setting.category-setting.index');
        }
    }

    public function editCategory($id, $country_code="")
    {
        $title = "Edit Category";
        $postLanguages = PostLanguage::whereNotIn('id', [4])->get();
        $statusData = Status::where('id', Status::ACTIVE)->orWhere('id', Status::INACTIVE)->pluck('name', 'id')->all();

        $categoryLanguages = CategoryLanguage::where('category_id',$id)->pluck('name','post_language_id')->toArray();
        $englishCat = Category::where('id', $id)->first();

        $category_settings = "";
        if (!empty($country_code) && $country_code!=""){
            $category_settings = CategorySettings::where('category_id',$id)->where('country_code',$country_code)->first();
        }

        $action = "edit";

        return view('admin.important-setting.category-setting.form', compact('title','statusData','postLanguages','categoryLanguages', 'englishCat', 'country_code', 'category_settings', 'action', 'id'));
    }

    public function updateCategory(Request $request, $id)
    {
        try {
            Log::info('Start code for the update category');
            DB::beginTransaction();
            $inputs = $request->all();
            $validator = Validator::make($request->all(), [
                'name' => 'required',
                'order' => 'required',
            ], [], [
                'name' => 'Name',
                'order' => 'Order',
            ]);
            if ($validator->fails()) {
                notify()->error("Validation Error", "Error", "topRight");
                return redirect()->back()->withErrors($validator)->withInput();
            }

            if (empty($inputs['country_code'])) {
                $data = [
                    "name" => $inputs['name'],
                    "category_type_id" => CategoryTypes::SHOP,
                    "order" => $inputs['order'],
                    "status_id" => $inputs['status_id'],
                    'is_show' => $inputs['is_show'],
                    "is_hidden" => $inputs['is_hidden'],
                    "parent_id" => 0,
                ];
            }
            else{
                $data = [
                    "name" => $inputs['name'],
                    "category_type_id" => CategoryTypes::SHOP,
                    "parent_id" => 0,
                ];
            }

            if ($request->hasFile('logo')) {
                $categoryFolder = config('constant.category');
                if (!Storage::exists($categoryFolder)) {
                    Storage::makeDirectory($categoryFolder);
                }
                $deleteCategory = DB::table('category')->whereId($id)->first();
                Storage::disk('s3')->delete($deleteCategory->logo);
                $logo = Storage::disk('s3')->putFile($categoryFolder, $request->file('logo'),'public');
                $fileName = basename($logo);
                $data['logo'] = $categoryFolder . '/' . $fileName;
            }
            $categoryData = Category::updateOrCreate(['id' => $id],$data);

            foreach($inputs['cname'] as $key => $value) {
                CategoryLanguage::updateOrCreate([
                    'category_id'   => $id,
                    'post_language_id'   => $key,
                ],[
                    'name' => $value,
                ]);
            }

            if (!empty($inputs['country_code'])){
                CategorySettings::where([
                    'category_id'   => $id,
                    'country_code' => $inputs['country_code'],
                ])->update([
                    'order' => $inputs['order'],
                    "status_id" => $inputs['status_id'],
                    "is_hidden" => $inputs['is_hidden'],
                    'is_show' => $inputs['is_show'],
                ]);
            }

            DB::commit();
            notify()->success("Category ". trans("messages.update-success"), "Success", "topRight");
            return redirect()->route('admin.important-setting.category-setting.index');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::info($e);
            notify()->error("Category ". trans("messages.update-error"), "Error", "topRight");
            return redirect()->route('admin.important-setting.category-setting.index');
        }
    }

    public function displayeBigcategory(Request $request){
        try {
            $filter = $request->input('filter');

            $query = BigCategory::where('big_categories.status_id', Status::ACTIVE);
            if (!empty($filter)) {
                $insertBigcatData = BigCategory::where('status_id', Status::ACTIVE)
                    ->get();
                foreach ($insertBigcatData as $item) {
                    BigCategorySetting::firstOrCreate([
                        'big_category_id' => $item->id,
                        'country_code' => $filter,
                    ], [
                        'order' => $item->order,
                    ]);
                }

                $query = $query->join('big_category_settings', 'big_category_settings.big_category_id', 'big_categories.id')
                    ->where('big_category_settings.country_code', $filter);
                $order = 'big_category_settings.order';
                $query = $query->select('big_categories.id as category_id', 'big_categories.name', 'big_category_settings.id', 'big_category_settings.is_show', 'big_category_settings.order');
            } else {
                $order = 'big_categories.order';
                $query = $query->select('big_categories.*', 'big_categories.id as category_id');
            }
            $data = $query->orderBy($order)
                ->get();

            $html = "";
            foreach ($data as $value) {
                $html .= '<div class="ui-state-default badge badge-light mt-1 ml-2" id="item-' . $value->category_id . '">' . $value->name . '</div>';
            }

            return response()->json(['success' => true, 'data' => $html]);
        } catch (Exception $ex) {
            return response()->json(['success' => false]);
        }
    }

    public function deleteCategory($id)
    {
        return view('admin.important-setting.category-setting.delete', compact('id'));
    }

    public function destroyCategory($id)
    {
        try {
            DB::beginTransaction();

            $categoryLanguages = CategoryLanguage::where('category_id',$id)->delete();
            $englishCat = Category::where('id', $id)->delete();
            $category_settings = CategorySettings::where('category_id',$id)->delete();

            DB::commit();
            notify()->success("Category deleted successfully", "Success", "topRight");
            return redirect()->route('admin.important-setting.category-setting.index');
        } catch (\Exception $ex) {
            DB::rollBack();
            notify()->error("Failed to delete category", "Error", "topRight");
            return redirect()->back();
        }
    }


}
