<?php

namespace App\Http\Controllers\Api;

use App\Models\Status;
use App\Models\Category;
use App\Models\EntityTypes;
use Illuminate\Http\Request;
use App\Models\CategoryLanguage;
use App\Models\UserHiddenCategory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Validator;

class ManageUserCategoryController extends Controller
{
    public function index(Request $request)
    {
        $inputs = $request->all();
        try {
            $validator = Validator::make($request->all(), [
                'country' => 'required',
                'language_id' => 'required',
                'user_id' => 'required',
                'user_type' => 'required|in:login,nonlogin',
            ]);
            if ($validator->fails()) {
                return $this->sendCustomErrorMessage($validator->errors()->toArray(), 422);
            }

            $country = $inputs['country'];
            $language_id = $inputs['language_id'];
            $user_id = $inputs['user_id'];
            $user_type = $inputs['user_type'];
            $menu_key = $inputs['menu_key'] ?? 'all';

            $query = Category::join('category_settings', 'category_settings.category_id', 'category.id')
                ->leftjoin('user_hidden_categories', function ($join) use ($user_id,$user_type) {
                    $join->on('user_hidden_categories.category_id', '=', 'category.id')
                        ->whereNull('user_hidden_categories.deleted_at')
                        ->where('user_hidden_categories.user_id',$user_id)
                        ->where('user_hidden_categories.user_type',$user_type);
                })
                ->where('category_settings.country_code', $country)
                ->where('category_settings.is_show',1)
                ->where('category.status_id', Status::ACTIVE)
                ->where('category.category_type_id', EntityTypes::SHOP)
                ->where('category.parent_id', 0)
                ->orderBy('category_settings.order', 'ASC')
                ->select(
                    'category.name',
                    'category.logo',
                    'category.id',
                    'category_settings.is_show',
                    'category_settings.order',
                    'category_settings.menu_key',
                    DB::raw('cast((CASE
                        WHEN user_hidden_categories.id is not null THEN "1"
                        ELSE "0"
                        END) as unsigned) AS is_hidden')
                    );
            /* if($menu_key != 'all'){
                $query = $query->where('category_settings.menu_key',$menu_key);
            } */

            $category = $query->get();
            if (!count($category)) {
                $category = Category::leftjoin('user_hidden_categories', function ($join) use ($user_id,$user_type) {
                        $join->on('user_hidden_categories.category_id', '=', 'category.id')
                            ->whereNull('user_hidden_categories.deleted_at')
                            ->where('user_hidden_categories.user_id',$user_id)
                            ->where('user_hidden_categories.user_type',$user_type);
                    })
                    ->where('category.status_id', Status::ACTIVE)
                    ->where('category.category_type_id', EntityTypes::SHOP)
                    ->where('category.parent_id', 0)
                    ->where('category.is_show',1)
                    ->select('category.name', 'category.logo', 'category.id', 'category.is_show', 'category.order', 'category.menu_key',
                        DB::raw('cast((CASE
                        WHEN user_hidden_categories.id is not null THEN "1"
                        ELSE "0"
                        END) as unsigned) AS is_hidden')
                    )
                    ->orderBy('category.order', 'ASC');
                /* if($menu_key != 'all'){
                    $category = $category->where('category.menu_key',$menu_key);
                } */
                $category = $category->get();
            }
            $category = $category->makeHidden(['sub_categories', 'parent_name', 'status_name', 'category_type_name']);

            $category = collect($category)->map(function ($item) use ($language_id) {
                $category_language = CategoryLanguage::where('category_id', $item->id)->where('post_language_id', $language_id)->first();
                $item->category_language_name = $category_language && $category_language->name != NULL ? $category_language->name : $item->name;
                return $item;
            });

            if($menu_key != 'all'){
                $category = $category->where('menu_key',$menu_key);
            }

            $category = $category->values();

            return $this->sendSuccessResponse(Lang::get('messages.category.success'), 200, compact('category'));

        }catch (\Exception $e){
            Log::info($e);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function editUserCategory(Request $request)
    {
        $inputs = $request->all();
        try {
            $validator = Validator::make($request->all(), [
                //'hidden_category_ids' => 'required|array|min:1',
               // 'visibility' => 'required|in:visible,hidden',
                'user_id' => 'required',
                'user_type' => 'required|in:login,nonlogin',
            ]);
            if ($validator->fails()) {
                return $this->sendCustomErrorMessage($validator->errors()->toArray(), 422);
            }

            $hidden_category_ids = $inputs['hidden_category_ids'] ?? [];
            $visible_category_ids = $inputs['visible_category_ids'] ?? [];

            //$visibility = $inputs['visibility'];
            $user_id = $inputs['user_id'];
            $user_type = $inputs['user_type'];

            if(!empty($hidden_category_ids)){
                foreach ($hidden_category_ids as $key => $catID) {
                    UserHiddenCategory::firstOrCreate([
                        'category_id' => $catID,
                        'user_id' => $user_id,
                        'user_type' => $user_type
                    ]);
                }

            }

            if(!empty($visible_category_ids)){
                UserHiddenCategory::whereIn('category_id',$visible_category_ids)->where('user_id',$user_id)->where('user_type',$user_type)->forceDelete();
            }

            return $this->sendSuccessResponse('Category'.Lang::get('messages.update-success'), 200);

        }catch (\Exception $e){
            Log::info($e);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }
}
