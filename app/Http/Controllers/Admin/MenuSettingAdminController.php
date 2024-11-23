<?php

namespace App\Http\Controllers\Admin;

use Log;
use App\Models\Country;
use App\Models\MenuSetting;
use App\Models\PostLanguage;
use Illuminate\Http\Request;
use App\Models\MenuSettingLanguage;
use App\Http\Controllers\Controller;

class MenuSettingAdminController extends Controller
{
    public function index()
    {
        $title = 'Menu Settings';
        $countries = Country::WhereNotNull('code')->where('is_show',1)->orderBy('priority')->get();
        $countries= collect($countries)->mapWithKeys(function ($value) {
            return [$value->code => $value->name];
        })->toArray();

        return view('admin.important-setting.menu-setting.index', compact('title','countries'));
    }

    public function updateOrder(Request $request){
        $inputs = $request->all();
        try{
            $order = $inputs['order'] ?? '';
            if(!empty($order)){
                foreach($order as $value){
                    $isUpdate = MenuSetting::where('id',$value['id'])->update(['menu_order' => $value['position']]);
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

    public function updateOnOff(Request $request){
        $inputs = $request->all();
        try{
            $data_id = $inputs['data_id'] ?? '';
            $checked = (string)$inputs['checked'];
            $isChecked = ($checked == 'true') ? 1 : 0;
            if(!empty($data_id)){
                MenuSetting::where('id',$data_id)->update(['is_show' => $isChecked]);
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

    public function updateCategoryOnOff(Request $request){
        $inputs = $request->all();
        try{
            $data_id = $inputs['data_id'] ?? '';
            $checked = (string)$inputs['checked'];
            $isChecked = ($checked == 'true') ? 1 : 0;
            if(!empty($data_id)){
                MenuSetting::where('id',$data_id)->update(['category_option' => $isChecked]);
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

    public function tableData(Request $request)
    {
        try {
            $columns = array(
                0 => 'menu_name',
                1 => 'is_show',
                2 => 'menu_order',
            );

            $filter = $request->input('filter');
            $limit = $request->input('length');
            $start = $request->input('start');
            $search = $request->input('search.value');
            $draw = $request->input('draw');
            $order = $columns[$request->input('order.0.column')];
            $dir = $request->input('order.0.dir');

            if(!empty($filter)){
                $menuData = MenuSetting::MENU_LIST;
                foreach ($menuData as $item) {
                    MenuSetting::firstOrCreate([
                        'menu_key' => $item['menu_key'],
                        'country_code' => $filter,
                    ],[
                        'menu_name' => $item['menu_name'],
                        'is_show' => $item['is_show'],
                        'menu_order' => $item['menu_order']
                    ]);
                }
            }

            $query = MenuSetting::select('*');

            if(!empty($filter)){
                $query = $query->where('country_code',$filter);
            }else{
                $query = $query->whereNull('country_code');
            }

            $totalData = $query->count();
            $totalFiltered = $totalData;
            if (!empty($search)) {
                $query = $query->where(function($q) use ($search){
                    $q->where('menu_name', 'LIKE', "%{$search}%");
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
                    $data[$key]['menu_name'] = $value->menu_name;
                    $data[$key]['menu_order'] = $value->menu_order;
                    $toggle = '';
                    $checked = $value->is_show ? 'checked' : '';
                    $toggle .= '<input type="checkbox" class="toggle-btn show-toggle-btn" '.$checked.' data-id="'.$value->id.'" data-toggle="toggle" data-height="20" data-size="sm" data-onstyle="success" data-offstyle="danger">';
                    $searchkey = array_search($value->menu_key, array_column(MenuSetting::MENU_LIST, 'menu_key'));
                    $is_category_toggle = MenuSetting::MENU_LIST[$searchkey]['is_category_toggle'];
                    if ($is_category_toggle == 1){
                        $cat_checked = $value->category_option ? 'checked' : '';
                        $toggle .= '<span class="ml-2 "> <input type="checkbox" data-on="Big Category" data-off="Small Category" class="toggle-btn category-toggle-btn" '.$cat_checked.' data-id="'.$value->id.'" data-toggle="toggle" data-height="20" data-size="sm" data-onstyle="primary" data-offstyle="primary"></span>';
                    }


                    $editButton = "<a href='javascript:void(0);' onclick='EditMenuSetting(".$value->id.")' role='button' class='btn btn-primary btn-sm mx-1' data-toggle='tooltip' data-original-title='Edit'><i class='fa fa-edit' style='font-size: 15px;margin: 4px -3px 4px 0px;'></i></a>";
                   /*  $toggle .= '<label class="toggleSwitch large" onclick="">';
                        $toggle .= "<input type='checkbox' $checked />";
                        $toggle .= '<span>';
                            $toggle .= '<span>OFF</span>';
                            $toggle .= '<span>ON</span>';
                        $toggle .= '</span>';
                        $toggle .= '<a></a>';
                    $toggle .= '</label>'; */
                    $data[$key]['is_show'] = $toggle; //;
                    $data[$key]['action'] = $editButton; //;

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

    public function editMenuSetting(Request $request, $id)
    {
        $menu = MenuSetting::whereId($id)->first();
        $postLanguages = PostLanguage::whereNotIn('id', [PostLanguage::ENGLISH])->where('is_support',1)->get();
        $menuLanguage = MenuSettingLanguage::where('menu_id',$id)->get();
        return view('admin.important-setting.menu-setting.form',compact('menu','postLanguages','menuLanguage'));
    }

    public function saveMenuSetting(Request $request,$id)
    {
        $inputs = $request->all();
        try {
            $menu_name = $inputs['menu_name'] ?? '';
            $menu_language_name = $inputs['menu_language_name'] ?? [];

            MenuSetting::whereId($id)->update(['menu_name' => $menu_name]);

            foreach ($menu_language_name as $key => $value) {
                MenuSettingLanguage::updateOrCreate([
                    'menu_id' => $id,
                    'language_id' => $key,
                ],[
                    'menu_name' => $value
                ]);
            }
            return response()->json(array(
                'success' => true,
                'message' => "Menu name successfully updated."
            ), 200);
        } catch (\Throwable $th) {
            Log::info($th);
            return response()->json(array(
                'success' => false,
                'message' => "Unable to update Menu name"
            ), 400);
        }
    }
}
