<?php

namespace App\Http\Controllers\Admin;
use Storage;

use Validator;
use App\Models\Status;
use App\Models\Category;
use App\Models\Currency;
use App\Models\PostLanguage;
use Illuminate\Http\Request;
use App\Models\CategoryTypes;
use App\Models\CategoryLanguage;
use App\Models\CategorySettings;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use App\Models\ManagerActivityLogs;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Lang;
use Spatie\Permission\Models\Permission;

class CategoryController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:category-list', ['only' => ['index','indexShop','indexCommunity','indexSuggest','indexReport','indexCurrency']]);
    }

    /* ================= Hospital code start ======================== */
    public function index()
    {
        $title = "Hospital Category List";
        return view('admin.category.index', compact('title'));
    }

    public function getHospitalJsonData(Request $request)
    {
        try {
            Log::info('Start get hospital category list');
            $user = Auth::user();
            $columns = array(
                0 => 'category.name',
                1 => 'category_languages.name',
                2 => 'category.parent_id',
                3 => 'category.category_type_name',
                4 => 'category.order',
                5 => 'category.status_id',
            );

            $limit = $request->input('length');
            $start = $request->input('start');
            $search = $request->input('search.value');
            $draw = $request->input('draw');
            $order = $columns[$request->input('order.0.column')];
            $dir = $request->input('order.0.dir');

            $query = Category::where('type', 'default')
                ->leftjoin('category_languages', function($query) {
                    $query->on('category_languages.category_id','=','category.id')
                    ->whereRaw('category_languages.post_language_id = 1');
                })
                ->where('category_type_id', CategoryTypes::HOSPITAL)
                ->whereNotIn('parent_id', [0])
                ->select('category.*','category_languages.name as koreanname');
            $totalData = $query->count();
            $totalFiltered = $totalData;

            if (!empty($search)) {
                $query = $query->where('category.name', 'LIKE', "%{$search}%");
                $totalFiltered = $query->count();
            }

            $categories = $query->offset($start)
                ->limit($limit)
                ->orderBy('parent_id')
                ->orderBy('order')
                ->orderBy($order, $dir)
                ->get();

            $categories = $categories->makeHidden('type', 'category_type_id', 'created_at', 'updated_at');

            $data = array();

            if (!empty($categories)) {
                foreach ($categories as $value) {
                    $categoryId = $value->id;
                    $nestedData['id'] = $categoryId;
                    $edit = route('admin.category.edit', $categoryId);
                    $nestedData['name'] = $value->name;
                    $nestedData['koreanname'] = $value->koreanname;
                    $nestedData['parent'] = $value->parent_name;
                    $nestedData['order'] = $value->order;
                    $nestedData['status'] = $value->status_id == Status::ACTIVE ? '<span class="badge badge-success">Active</span>' : '<span class="badge badge-danger">In Active</span>';
                    $nestedData['category_type'] = $value->category_type_name;
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
            Log::info('End get hospital category list');
            return response()->json($jsonData);
        } catch (\Exception $ex) {
            Log::info('Exception in the get hospital category list');
            Log::info($ex);
            return response()->json([]);
        }
    }

    /* ================= Hospital code end ======================== */

    /* ================= Shop code start ======================== */

    public function indexShop($custom)
    {
        $title = "Shop Category List";
        return view('admin.category.index-shop', compact('title','custom'));
    }

    public function getShopJsonData(Request $request,$custom)
    {
        try {
            Log::info('Start get shop category list');
            $user = Auth::user();
            $columns = array(
                0 => 'category.name',
                1 => 'category_languages.name',
                2 => 'category.parent_id',
                3 => 'category.category_type_name',
                4 => 'category.order',
                5 => 'category.status_id',
            );

            $limit = $request->input('length');
            $start = $request->input('start');
            $search = $request->input('search.value');
            $draw = $request->input('draw');
            $order = $columns[$request->input('order.0.column')];
            $dir = $request->input('order.0.dir');
            // dd($search);

            $query = Category::where('type', 'default')
                        ->leftjoin('category_languages', function($query) {
                            $query->on('category_languages.category_id','=','category.id')
                            ->whereRaw('category_languages.post_language_id = 1');
                        })
                        ->where('category_type_id', $custom)
                        ->select('category.*','category_languages.name as koreanname');
            $totalData = $query->count();
            $totalFiltered = $totalData;

            if (!empty($search)) {
                $query = $query->where('category.name', 'LIKE', "%{$search}%");
                $totalFiltered = $query->count();
            }

            $categories = $query->offset($start)
                ->limit($limit)
                ->orderBy('parent_id')
                ->orderBy('order')
                ->orderBy($order, $dir)
                ->get();

            $categories = $categories->makeHidden('type', 'category_type_id', 'created_at', 'updated_at');

            $data = array();

            if (!empty($categories)) {
                foreach ($categories as $value) {
                    $categoryId = $value->id;
                    $nestedData['id'] = $categoryId;
                    $edit = route('admin.category.edit', $categoryId);
                    $nestedData['name'] = $value->name;
                    $nestedData['koreanname'] = $value->koreanname;
                    $nestedData['order'] = $value->order;
                    $nestedData['status'] = $value->status_id == Status::ACTIVE ? '<span class="badge badge-success">Active</span>' : '<span class="badge badge-danger">In Active</span>';
                    $nestedData['image'] = "<img src='".$value->logo."' alt='".$value->name."' class='requested-client-images m-1' />";
                    $nestedData['category_type'] = $value->category_type_name;
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

            Log::info('End get shop category list');
            return response()->json($jsonData);
        } catch (\Exception $ex) {
            Log::info('Exception in the get shop category list');
            Log::info($ex);
            return response()->json([]);
        }
    }

    /* ================= Shop code start ======================== */

    /* ================= Community code start ======================== */

    public function indexCommunity()
    {
        $title = "Community Category List";
        return view('admin.category.index-community', compact('title'));

    }

    public function getCommunityJsonData(Request $request)
    {
        try {
            Log::info('Start get community category list');
            $user = Auth::user();
            $columns = array(
                0 => 'category.name',
                1 => 'category_languages.name',
                2 => 'category.parent_id',
                3 => 'category.category_type_name',
                4 => 'category.order',
                5 => 'category.status_id',
            );

            $limit = $request->input('length');
            $start = $request->input('start');
            $search = $request->input('search.value');
            $draw = $request->input('draw');
            $order = $columns[$request->input('order.0.column')];
            $dir = $request->input('order.0.dir');

            $query = Category::where('type', 'default')
                        ->leftjoin('category_languages', function($query) {
                            $query->on('category_languages.category_id','=','category.id')
                            ->whereRaw('category_languages.post_language_id = 1');
                        })
                        ->where('category_type_id', CategoryTypes::COMMUNITY)
                        ->whereNotIn('parent_id', [0])
                        ->select('category.*','category_languages.name as koreanname');
            $totalData = $query->count();
            $totalFiltered = $totalData;

            if (!empty($search)) {
                $query = $query->where('category.name', 'LIKE', "%{$search}%");
                $totalFiltered = $query->count();
            }

            $categories = $query->offset($start)
                ->limit($limit)
                ->orderBy('parent_id')
                ->orderBy('order')
                ->orderBy($order, $dir)
                ->get();

            $categories = $categories->makeHidden('type', 'category_type_id', 'created_at', 'updated_at');

            $data = array();

            if (!empty($categories)) {
                foreach ($categories as $value) {
                    $categoryId = $value->id;
                    $nestedData['id'] = $categoryId;
                    $edit = route('admin.category.edit', $categoryId);
                    $nestedData['name'] = $value->name;
                    $nestedData['koreanname'] = $value->koreanname;
                    $nestedData['order'] = $value->order;
                    $nestedData['parent'] = $value->parent_name;
                    $nestedData['status'] = $value->status_id == Status::ACTIVE ? '<span class="badge badge-success">Active</span>' : '<span class="badge badge-danger">In Active</span>';
                    $nestedData['category_type'] = $value->category_type_name;
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
            Log::info('End get hospital category list');
            return response()->json($jsonData);
        } catch (\Exception $ex) {
            Log::info('Exception in the get hospital category list');
            Log::info($ex);
            return response()->json([]);
        }
    }

    /* ================= Community code start ======================== */

      /* ================= Suggest code start ======================== */
    public function indexSuggest($custom)
    {
        $title = "Suggest Category List";
        return view('admin.category.index-suggest', compact('title','custom'));
    }

    public function getSuggestJsonData(Request $request,$custom)
    {
        try {
            Log::info('Start get suggest category list');
            $user = Auth::user();
            $columns = array(
                0 => 'category.name',
                1 => 'category_languages.name',
                2 => 'category.parent_id',
                3 => 'category.category_type_name',
                4 => 'category.order',
                5 => 'category.status_id',
            );

            $limit = $request->input('length');
            $start = $request->input('start');
            $search = $request->input('search.value');
            $draw = $request->input('draw');
            $order = $columns[$request->input('order.0.column')];
            $dir = $request->input('order.0.dir');

            $query = Category::where('category_type_id', $custom)
                        ->leftjoin('category_languages', function($query) {
                            $query->on('category_languages.category_id','=','category.id')
                            ->whereRaw('category_languages.post_language_id = 1');
                        })
                        ->select('category.*','category_languages.name as koreanname');
            $totalData = $query->count();
            $totalFiltered = $totalData;
            if (!empty($search)) {
                $query = $query->where('category.name', 'LIKE', "%{$search}%");
                $totalFiltered = $query->count();
            }

            $categories = $query->offset($start)
            ->limit($limit)
            ->orderBy('parent_id')
            ->orderBy('order')
            ->orderBy($order, $dir)
            ->get();


            $categories = $categories->makeHidden('type', 'category_type_id', 'created_at', 'updated_at');

            $data = array();

            if (!empty($categories)) {
                foreach ($categories as $value) {
                    $categoryId = $value->id;
                    $nestedData['id'] = $categoryId;
                    $edit = route('admin.category.edit', $categoryId);
                    $bitlyUrl = url('public/api/get/all/custom/shops/deeplink?category_id=' . $categoryId);
                    $nestedData['name'] = $value->name;
                    $nestedData['koreanname'] = $value->koreanname;
                    $nestedData['order'] = $value->order;
                    $nestedData['category_type'] = $value->category_type_name;
                    $nestedData['image'] = "<img src='".$value->logo."' alt='".$value->name."' class='requested-client-images m-1' />";
                    $nestedData['status'] = $value->status_id == Status::ACTIVE ? '<span class="badge badge-success">Active</span>' : '<span class="badge badge-danger">In Active</span>';
                    $editButton =  "<a role='button' href='" . $edit . "' title='' data-original-title='Edit' class='btn btn-primary' data-toggle='tooltip'><i class='fa fa-edit'></i></a>";
                    $deleteButton = "<a href='javascript:void(0)' role='button' onclick='deleteCategory(" . $categoryId . ")' class='btn btn-danger' data-toggle='tooltip' data-original-title=" . Lang::get('general.delete') . "><i class='fa fa-trash'></i></a>";
                    $copyButton = "<button role='button' onclick='copyCategoryLink(" . $categoryId . ")' class='btn btn-outline-primary' data-toggle='tooltip' data-original-title='Copy Link'><i class='fa fa-clone'></i></button>";
                    $inputId = 'category_url_'.$categoryId;
                    $inputHidden = "<input type='text' style='display:none' value='".$bitlyUrl."' id='".$inputId."'>";
                    $nestedData['actions'] = "$copyButton $editButton $deleteButton $inputHidden";
                    $data[] = $nestedData;
                }
            }

            $jsonData = array(
                "draw" => intval($draw),
                "recordsTotal" => intval($totalData),
                "recordsFiltered" => intval($totalFiltered),
                "data" => $data,
            );
            Log::info('End get suggest category list');
            return response()->json($jsonData);
        } catch (\Exception $ex) {
            Log::info('Exception in the get suggest category list');
            Log::info($ex);
            return response()->json([]);
        }
    }

    /* ================= Suggest code end ======================== */

    /* ================= Report code start ======================== */

    public function indexReport()
    {
        $title = "Report Category List";
        return view('admin.category.index-report', compact('title'));

    }

    public function getReportJsonData(Request $request)
    {
        try {
            Log::info('Start get report category list');
            $user = Auth::user();
            $columns = array(
                0 => 'category.name',
                1 => 'category_languages.name',
                2 => 'category.parent_id',
                3 => 'category.category_type_name',
                4 => 'category.order',
                5 => 'category.status_id',
            );

            $limit = $request->input('length');
            $start = $request->input('start');
            $search = $request->input('search.value');
            $draw = $request->input('draw');
            $order = $columns[$request->input('order.0.column')];
            $dir = $request->input('order.0.dir');

            $query = Category::where('type', 'default')
                        ->leftjoin('category_languages', function($query) {
                            $query->on('category_languages.category_id','=','category.id')
                            ->whereRaw('category_languages.post_language_id = 1');
                        })
                        ->where('category_type_id', CategoryTypes::REPORT)
                        ->whereNotIn('parent_id', [0])
                        ->select('category.*','category_languages.name as koreanname');

            $totalData = $query->count();
            $totalFiltered = $totalData;

            if (!empty($search)) {
                $query = $query->where('category.name', 'LIKE', "%{$search}%");
                $totalFiltered = $query->count();
            }

            $categories = $query->offset($start)
                ->limit($limit)
                ->orderBy('parent_id')
                ->orderBy('order')
                ->orderBy($order, $dir)
                ->get();

            $categories = $categories->makeHidden('type', 'category_type_id', 'created_at', 'updated_at');

            $data = array();

            if (!empty($categories)) {
                foreach ($categories as $value) {
                    $categoryId = $value->id;
                    $nestedData['id'] = $categoryId;
                    $edit = route('admin.category.edit', $categoryId);
                    $nestedData['name'] = $value->name;
                    $nestedData['koreanname'] = $value->koreanname;
                    $nestedData['order'] = $value->order;
                    $nestedData['parent'] = $value->parent_name;
                    $nestedData['category_type'] = $value->category_type_name;
                    $nestedData['status'] = $value->status_id == Status::ACTIVE ? '<span class="badge badge-success">Active</span>' : '<span class="badge badge-danger">In Active</span>';
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
            Log::info('End get report category list');
            return response()->json($jsonData);
        } catch (\Exception $ex) {
            Log::info('Exception in the get report category list');
            Log::info($ex);
            return response()->json([]);
        }
    }

    /* ================= Report code end ======================== */

    /* ================= Currency code start ======================== */

    public function indexCurrency()
    {
        $title = "Currency List";
        return view('admin.category.index-currency', compact('title'));

    }

    public function getCurrencyJsonData(Request $request)
    {
        try {
            Log::info('Start get currency list');
            $user = Auth::user();
            $columns = array(
                0 => 'name',
                1 => 'created_at',
            );

            $limit = $request->input('length');
            $start = $request->input('start');
            $search = $request->input('search.value');
            $draw = $request->input('draw');
            $order = $columns[$request->input('order.0.column')];
            $dir = $request->input('order.0.dir');

            $query = Currency::where('status_id',Status::ACTIVE);
            $totalData = $query->count();
            $totalFiltered = $totalData;

            if (!empty($search)) {
                $query = $query->where('name', 'LIKE', "%{$search}%");
                $totalFiltered = $query->count();
            }

            $currencies = $query->offset($start)
                ->limit($limit)
                ->orderBy($order, $dir)
                ->get();

            $data = array();

            if (!empty($currencies)) {
                foreach ($currencies as $value) {
                    $currency_id = $value->id;
                    $nestedData['id'] = $currency_id;
                    $edit = route('admin.currency.edit', $currency_id);
                    $nestedData['name'] = $value->name;
                    $nestedData['created_at'] = $value->created_at;
                    $editButton = "<a role='button' href='" . $edit . "' title='' data-original-title='Edit' class='btn btn-primary' data-toggle='tooltip'><i class='fa fa-edit'></i></a>";
                    $deleteButton = "<a href='javascript:void(0)' role='button' onclick='deleteCategory(" . $currency_id . ")' class='btn btn-danger' data-toggle='tooltip' data-original-title=" . Lang::get('general.delete') . "><i class='fa fa-trash'></button>";
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
            Log::info('End get currency list');
            return response()->json($jsonData);
        } catch (\Exception $ex) {
            Log::info('Exception in the get currency list');
            Log::info($ex);
            return response()->json([]);
        }
    }

    /* ================= currency code end ======================== */

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $title = "Add Category";
        $categoryLanguages = [];
        $categories = Category::where('type', 'default')->where('parent_id', 0)->get();
        $categoryType = CategoryTypes::pluck('name', 'id')->all();
        $postLanguages = PostLanguage::whereNotIn('id', [4])->get();
        $parentCat = Category::where('parent_id', 0)->pluck('name', 'id')->all();
        $statusData = Status::where('id', Status::ACTIVE)->orWhere('id', Status::INACTIVE)->pluck('name', 'id')->all();
        return view('admin.category.form', compact('title', 'categoryType', 'categories','parentCat','statusData','postLanguages','categoryLanguages'));
    }

    public function store(Request $request)
    {
        try {
            DB::beginTransaction();
            Log::info('Start code for the add category');
            $inputs = $request->all();
            $validator = Validator::make($request->all(), [
                'name' => 'required',
                'order' => 'required',
                'category_type_id' => 'required',
            ], [], [
                'name' => 'Name',
                'order' => 'Order',
                'category_type_id' => 'Category',
            ]);
            if ($validator->fails()) {
                notify()->error("Validation Error", "Error", "topRight");
                return redirect()->back()->withErrors($validator)->withInput();
            }
            $data = [
                "name" => $inputs['name'],
                "category_type_id" => $inputs['category_type_id'],
                "order" => $inputs['order'],
                "status_id" => $inputs['status_id'],
                "is_hidden" => $inputs['is_hidden'],
                "is_show" => $inputs['is_show'] ?? 0,
                "parent_id" => isset($inputs['parent_id']) && $inputs['parent_id'] != NULL ? $inputs['parent_id'] : 0,
            ];

            if ($request->hasFile('logo')) {
                $categoryFolder = config('constant.category');
                if (!Storage::exists($categoryFolder)) {
                    Storage::makeDirectory($categoryFolder);
                }
                $logo = Storage::disk('s3')->putFile($categoryFolder, $request->file('logo'),'public');
                $fileName = basename($logo);
                $data['logo'] = $categoryFolder . '/' . $fileName;
            }
            $categoryData = Category::create($data);

            foreach($inputs['cname'] as $key => $value) {
                CategoryLanguage::updateOrCreate([
                    'category_id'   => $categoryData->id,
                    'post_language_id'   => $key,
                ],[
                    'name' => $value,
                ]);
            }
            $route = config('constant.category_url_'.$inputs['category_type_id']);

            if($inputs['category_type_id'] == CategoryTypes::SHOP){
                $categorySettings = CategorySettings::groupBy('country_code')->pluck('country_code');

                if(!empty($categorySettings)){
                    foreach ($categorySettings as $country) {
                        CategorySettings::firstOrCreate([
                            'category_id' => $categoryData->id,
                            'country_code' => $country,
                        ], [
                            'is_show' => 1,
                            'order' => $inputs['order'],
                        ]);
                    }
                }
            }

            DB::commit();
            $routeParams = [];
            if(in_array($inputs['category_type_id'],[CategoryTypes::CUSTOM,CategoryTypes::CUSTOM2, CategoryTypes::SHOP2])){
                $routeParams = ['custom'=>$inputs['category_type_id']];
            }
            Log::info('End the code for the add category');
            notify()->success("Category ". trans("messages.insert-success"), "Success", "topRight");
            return redirect()->route($route,$routeParams);
        } catch (\Exception $e) {
            //dd($inputs);
            Log::info('Exception in the add category');
            Log::info($e);
            notify()->error("Category ". trans("messages.insert-error"), "Error", "topRight");
            return redirect()->route('admin.category.index');
        }
    }

    public function edit(Category $category)
    {
        $title = "Edit Category";
        $categoryType = CategoryTypes::pluck('name', 'id')->all();
        $postLanguages = PostLanguage::whereNotIn('id', [4])->get();
        $categoryLanguages = CategoryLanguage::where('category_id',$category->id)->pluck('name','post_language_id')->toArray();
        $parentCat = Category::where('category_type_id', $category->category_type_id)->where('parent_id', 0)->pluck('name', 'id')->all();
        $statusData = Status::where('id', Status::ACTIVE)->orWhere('id', Status::INACTIVE)->pluck('name', 'id')->all();
        return view('admin.category.form', compact('title', 'category','categoryType','parentCat','statusData','postLanguages','categoryLanguages'));
    }

    public function update(Request $request, Category $category)
    {
        try {
            Log::info('Start code for the update category');
            DB::beginTransaction();
            $inputs = $request->all();
            $validator = Validator::make($request->all(), [
                'name' => 'required',
                'order' => 'required',
                'category_type_id' => 'required',
            ], [], [
                'name' => 'Name',
                'order' => 'Order',
                'category_type_id' => 'Category',
            ]);
            if ($validator->fails()) {
                notify()->error("Validation Error", "Error", "topRight");
                return redirect()->back()->withErrors($validator)->withInput();
            }
            $data = [
                "name" => $inputs['name'],
                "category_type_id" => $inputs['category_type_id'],
                "order" => $inputs['order'],
                "status_id" => $inputs['status_id'],
                "is_hidden" => $inputs['is_hidden'],
                "is_show" => $inputs['is_show'] ?? 0,
                "parent_id" => isset($inputs['parent_id']) && $inputs['parent_id'] != NULL ? $inputs['parent_id'] : 0,
            ];

            if ($request->hasFile('logo')) {
                $categoryFolder = config('constant.category');
                if (!Storage::exists($categoryFolder)) {
                    Storage::makeDirectory($categoryFolder);
                }
                $deleteCategory = DB::table('category')->whereId($category->id)->first();
                Storage::disk('s3')->delete($deleteCategory->logo);
                $logo = Storage::disk('s3')->putFile($categoryFolder, $request->file('logo'),'public');
                $fileName = basename($logo);
                $data['logo'] = $categoryFolder . '/' . $fileName;
            }
            $categoryData = Category::updateOrCreate(['id' => $category->id],$data);
            if ($categoryData->wasChanged()) {
                $changes = $categoryData->getChanges();
                if(!empty($changes)){
                    foreach($changes as $changeKey => $changeValue){
                        if(!in_array($changeKey,['created_at','updated_at'])){
                            $logData = [
                                'activity_type' => ManagerActivityLogs::EDIT_CATEGORY,
                                'user_id' => auth()->user()->id,
                                'value' => "$changeValue|$changeKey",
                                'entity_id' => $category->id,
                            ];
                            $this->addManagerActivityLogs($logData);
                        }
                    }
                }
            }
            foreach($inputs['cname'] as $key => $value) {
                $isChange = CategoryLanguage::updateOrCreate([
                    'category_id'   => $category->id,
                    'post_language_id'   => $key,
                ],[
                    'name' => $value,
                ]);

                if (($isChange->wasChanged() || $isChange->wasRecentlyCreated) && !empty($value)) {
                    $logData = [
                        'activity_type' => ManagerActivityLogs::EDIT_LANGUAGE_CATEGORY,
                        'user_id' => auth()->user()->id,
                        'value' => $value,
                        'entity_id' => $isChange->id,
                    ];
                    $this->addManagerActivityLogs($logData);
                }
            }
            $route = config('constant.category_url_'.$inputs['category_type_id']);

            Log::info('End the code for the update category');
            DB::commit();
            $routeParams = [];
            if(in_array($inputs['category_type_id'],[CategoryTypes::CUSTOM,CategoryTypes::CUSTOM2, CategoryTypes::SHOP2])){
                $routeParams = ['custom'=>$inputs['category_type_id']];
            }

            notify()->success("Category ". trans("messages.update-success"), "Success", "topRight");
            return redirect()->route($route,$routeParams);
        } catch (\Exception $e) {
            Log::info('Exception in the update category.');
            Log::info($e);
            notify()->error("Category ". trans("messages.update-error"), "Error", "topRight");
            return redirect()->route('admin.category.index');
        }
    }

    public function delete($id)
    {
        return view('admin.category.delete', compact('id'));
    }

    public function destroy(Category $category)
    {
        try {
            Log::info('Category delete code start.');
            DB::beginTransaction();
            $deleteCategory = DB::table('category')->whereId($category->id)->first();
            if($deleteCategory->logo){
                Storage::disk('s3')->delete($deleteCategory->logo);
            }
            $route = config('constant.category_url_'.$category->category_type_id);
            CategoryLanguage::where('category_id',$category->id)->delete();
            Category::where('id',$category->id)->delete();
            DB::commit();

            $routeParams = [];
            if(in_array($category->category_type_id,[CategoryTypes::CUSTOM,CategoryTypes::CUSTOM2,CategoryTypes::SHOP2])){
                $routeParams = ['custom'=>$category->category_type_id];
            }

            Log::info('Category delete code end.');
            notify()->success("Category deleted successfully", "Success", "topRight");
            return redirect()->route($route,$routeParams);
        } catch (\Exception $ex) {
            DB::rollBack();
            Log::info('Category delete exception.');
            Log::info($ex);
            notify()->error("Failed to deleted category", "Error", "topRight");
            return redirect()->route('admin.category.index');
        }
    }

    public function parent($id)
    {
        $category = Category::where('category_type_id',$id)->where('parent_id', 0)->get();
        $category_data = [];

        foreach ($category as $value) {
            $category_data[$value->id] = $value->name;
        }
        return response()->json(compact('category_data'));
    }

}
