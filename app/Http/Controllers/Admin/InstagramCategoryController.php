<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InstagramCategory;
use App\Models\InstagramCategoryLanguage;
use App\Models\InstagramCategoryOption;
use App\Models\InstagramCategoryOptionLanguage;
use App\Models\PostLanguage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Validator;

class InstagramCategoryController extends Controller
{
    public function index()
    {
        $title = "Instagram Category List";
        return view('admin.insta-category.index', compact('title'));
    }

    public function tableData(Request $request)
    {
        try {
            $columns = array(
                0 => 'instagram_categories.title',
                1 => 'instagram_categories.sub_title',
                2 => 'instagram_categories.order',
            );

            $limit = $request->input('length');
            $start = $request->input('start');
            $search = $request->input('search.value');
            $draw = $request->input('draw');
            $order = $columns[$request->input('order.0.column')];
            $dir = $request->input('order.0.dir');

            $query = InstagramCategory::Query();
            $totalData = $query->count();
            $totalFiltered = $totalData;

            if (!empty($search)) {
                $query = $query->where('instagram_categories.title', 'LIKE', "%{$search}%")
                    ->orWhere('instagram_categories.sub_title', 'LIKE', "%{$search}%");
                $totalFiltered = $query->count();
            }

            $categories = $query->offset($start)
                ->limit($limit)
                ->orderBy($order, $dir)
                ->get();

            $categories = $categories->makeHidden('created_at', 'updated_at');

            $data = array();

            if (!empty($categories)) {
                foreach ($categories as $value) {
                    $nestedData['id'] = $value->id;
                    $categoryId = $value->id;
                    $edit = route('admin.insta-category.edit', $categoryId);
                    $nestedData['title'] = $value->title;
                    $nestedData['sub_title'] = $value->sub_title;
                    $nestedData['order'] = $value->order;

                    $editButton =  "<a role='button' href='" . $edit . "' title='' data-original-title='Edit' class='btn btn-primary' data-toggle='tooltip'><i class='fa fa-edit'></i></a>";
                    $deleteButton = "<a href='javascript:void(0)' role='button' onclick='deleteInstaCategory(" . $categoryId . ")' class='btn btn-danger' data-toggle='tooltip' data-original-title=" . Lang::get('general.delete') . "><i class='fa fa-trash'></button>";
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
            return response()->json([]);
        }
    }

    public function create(){
        $title = "Add Instagram Category";
//        $countries = Country::whereIn('code', ['US','KR','JP'])->orderBy('priority')->get();
        $postLanguages = PostLanguage::where('is_support', 1)->whereNotIn('id', [4])->get();
        $postLanguagesOption = PostLanguage::where('is_support', 1)->whereNotIn('id', [4,2])->get();
        $categoryLanguages = [];
        $subTitleLanguages = [];

        return view('admin.insta-category.form', compact('title', 'postLanguages', 'categoryLanguages', 'subTitleLanguages', 'postLanguagesOption'));
    }

    public function store(Request $request){
//        dd($request->all());
        try {
            DB::beginTransaction();
            $inputs = $request->all();
            $validator = Validator::make($request->all(), [
                'title' => 'required',
                'sub_title' => 'required',
            ], [], [
                'title' => 'Title',
                'sub_title' => 'Sub Title',
            ]);

            $data = [
                "title" => $inputs['title'],
                "sub_title" => $inputs['sub_title'],
            ];
            $instaCategoryData = InstagramCategory::create($data);

            foreach($inputs['cname'] as $key => $value) {
                InstagramCategoryLanguage::updateOrCreate([
                    'entity_id' => $instaCategoryData->id,
                    'entity_type' => InstagramCategoryLanguage::CATEGORY,
                    'language_id' => $key,
                ], [
                    'value' => $value,
                ]);
            }

            foreach($inputs['sname'] as $key => $value) {
                InstagramCategoryLanguage::updateOrCreate([
                    'entity_id' => $instaCategoryData->id,
                    'entity_type' => InstagramCategoryLanguage::SUB_TITLE,
                    'language_id' => $key,
                ], [
                    'value' => $value,
                ]);
            }

            for($i=1; $i<=$inputs['total_options']; $i++) {
                $optionData = InstagramCategoryOption::create([
                    'instagram_category_id' => $instaCategoryData->id,
                    'title' => $inputs['option_title_4_'.$i],
                    'price' => $inputs['option_price_4_'.$i],
                    'link' => $inputs['option_link_4_'.$i],
                    'order' => $inputs['option_order_'.$i],
                ]);

                $postLanguagesOption = PostLanguage::where('is_support', 1)->whereNotIn('id', [4,2])->get();
                foreach ($postLanguagesOption as $postLanguage){
                    InstagramCategoryOptionLanguage::create([
                        'entity_id' => $optionData->id,
                        'language_id' => $postLanguage->id,
                        'title' => $inputs['option_title_'.$postLanguage->id.'_'.$i],
                        'price' => $inputs['option_price_'.$postLanguage->id.'_'.$i],
                        'link' => $inputs['option_link_'.$postLanguage->id.'_'.$i],
                    ]);
                }
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => "Instagram Category ".trans("messages.insert-success")]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => "Instagram Category ".trans("messages.insert-error")]);
        }
    }

    public function edit($id)
    {
        $title = "Edit Instagram Category";
        $InstagramCategory = InstagramCategory::with('categoryoption')->where('id', $id)->first();

//        $countries = Country::whereIn('code', ['US','KR','JP'])->orderBy('priority')->get();
        $postLanguages = PostLanguage::where('is_support', 1)->whereNotIn('id', [4])->get();
        $postLanguagesOption = PostLanguage::where('is_support', 1)->whereNotIn('id', [4,2])->get();

        $categoryLanguages = InstagramCategoryLanguage::where('entity_id',$id)->where('entity_type', InstagramCategoryLanguage::CATEGORY)->pluck('value','language_id')->toArray();
        $subTitleLanguages = InstagramCategoryLanguage::where('entity_id',$id)->where('entity_type', InstagramCategoryLanguage::SUB_TITLE)->pluck('value','language_id')->toArray();
        return view('admin.insta-category.form', compact('title', 'InstagramCategory', 'categoryLanguages', 'subTitleLanguages', 'postLanguages', 'postLanguagesOption'));
    }

    public function update(Request $request, $id)
    {
        try {
            DB::beginTransaction();
            $inputs = $request->all();
            $validator = Validator::make($request->all(), [
                'title' => 'required',
                'sub_title' => 'required',
            ], [], [
                'title' => 'Title',
                'sub_title' => 'Sub Title',
            ]);

            $data = [
                "title" => $inputs['title'],
                "sub_title" => $inputs['sub_title'],
            ];
            $instaCategoryData = InstagramCategory::updateOrCreate(['id' => $id],$data);

            foreach($inputs['cname'] as $key => $value) {
                InstagramCategoryLanguage::updateOrCreate([
                    'entity_id' => $id,
                    'entity_type' => InstagramCategoryLanguage::CATEGORY,
                    'language_id' => $key,
                ], [
                    'value' => $value,
                ]);
            }

            foreach($inputs['sname'] as $key => $value) {
                InstagramCategoryLanguage::updateOrCreate([
                    'entity_id' => $id,
                    'entity_type' => InstagramCategoryLanguage::SUB_TITLE,
                    'language_id' => $key,
                ], [
                    'value' => $value,
                ]);
            }

            InstagramCategoryOption::where('instagram_category_id', $id)->delete();
            for($i=1; $i<=$inputs['total_options']; $i++) {
                $optionData = InstagramCategoryOption::create([
                    'instagram_category_id' => $id,
                    'title' => $inputs['option_title_4_'.$i],
                    'price' => $inputs['option_price_4_'.$i],
                    'link' => $inputs['option_link_4_'.$i],
                    'order' => $inputs['option_order_'.$i],
                ]);

                $postLanguagesOption = PostLanguage::where('is_support', 1)->whereNotIn('id', [4,2])->get();
                foreach ($postLanguagesOption as $postLanguage){
                    InstagramCategoryOptionLanguage::create([
                        'entity_id' => $optionData->id,
                        'language_id' => $postLanguage->id,
                        'title' => $inputs['option_title_'.$postLanguage->id.'_'.$i],
                        'price' => $inputs['option_price_'.$postLanguage->id.'_'.$i],
                        'link' => $inputs['option_link_'.$postLanguage->id.'_'.$i],
                    ]);
                }
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => "Instagram Category ".trans("messages.update-success")]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => "Instagram Category ".trans("messages.update-error")]);
        }
    }

    public function delete($id)
    {
        return view('admin.insta-category.delete', compact('id'));
    }

    public function destroy($id)
    {
        try {
            DB::beginTransaction();

            InstagramCategoryOption::where('instagram_category_id',$id)->delete();
            InstagramCategoryLanguage::where('entity_id',$id)->whereIn('entity_type',[InstagramCategoryLanguage::CATEGORY, InstagramCategoryLanguage::SUB_TITLE])->delete();
            InstagramCategory::where('id',$id)->delete();
            DB::commit();

            notify()->success("Instagram Category deleted successfully", "Success", "topRight");
            return redirect()->route('admin.insta-category.index');
        } catch (\Exception $ex) {
            DB::rollBack();
            notify()->error("Failed to delete instagram category", "Error", "topRight");
            return redirect()->route('admin.insta-category.index');
        }
    }

    public function updateOrder(Request $request){
        $inputs = $request->all();
        try{
            $order = $inputs['order'] ?? '';
            if(!empty($order)){
                foreach($order as $value){
                    $isUpdate = InstagramCategory::where('id',$value['id'])->update(['order' => $value['position']]);
                }
            }
            return response()->json([
                'success' => true
            ]);
        }catch (\Exception $ex) {
            return response()->json([
                'success' => false
            ]);
        }
    }

}
