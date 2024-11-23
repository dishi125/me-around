<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\InstagramCategory;
use App\Models\InstagramCategoryLanguage;
use App\Models\InstagramCategoryOption;
use App\Models\InstagramSubscribedPlan;
use App\Models\PostLanguage;
use App\Validators\InstaCategoryValidator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Exception;

class InstaCategoryApiController extends Controller
{
    private $instaCategoryValidator;

    function __construct()
    {
        $this->instaCategoryValidator = new InstaCategoryValidator();
    }

    public function listCategory(Request $request)
    {
        $inputs = $request->all();
        try {
            $validation = $this->instaCategoryValidator->validateList($inputs);
            if ($validation->fails()) {
                return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
            }

            $language_id = $inputs['language_id'] ?? PostLanguage::ENGLISH;
            if ($language_id == PostLanguage::ENGLISH){
                $categoryData = InstagramCategory::orderBy('order', 'ASC')->select('id','title as name')->get()->toArray();
            }
            else {
                $categoryData = InstagramCategoryLanguage::join('instagram_categories', function($query) use($language_id){
                                    $query->on('instagram_categories.id','=','instagram_category_languages.entity_id');
                                })
                                ->where('instagram_category_languages.entity_type', InstagramCategoryLanguage::CATEGORY)
                                ->where('instagram_category_languages.language_id', $language_id)
                                ->whereNotNull('instagram_category_languages.value')
                                ->select('instagram_categories.id','instagram_category_languages.value as name')
                                ->orderBy('instagram_categories.order', 'ASC')
                                ->get()
                                ->toArray();
            }

            if (!empty($categoryData)) {
                return $this->sendSuccessResponse(Lang::get('messages.insta-category.success'), 200, $categoryData);
            } else {
                return $this->sendSuccessResponse(Lang::get('messages.insta-category.empty'), 501);
            }
        } catch (Exception $e) {
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function listOption(Request $request){
        $inputs = $request->all();
        try {
            $validation = $this->instaCategoryValidator->validateOptionList($inputs);
            if ($validation->fails()) {
                return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
            }

            $language_id = $inputs['language_id'] ?? PostLanguage::ENGLISH;
            $category_id = $inputs['category_id'] ?? '';

            $data = array();
            if ($language_id == PostLanguage::ENGLISH){
                $sub_title = InstagramCategory::where('id',$category_id)->pluck('sub_title')->first();
                $optionData = InstagramCategoryOption::where('instagram_category_id', $category_id)->orderBy('order', 'ASC')->select('instagram_category_id','id as option_id','title as option','price','link')->get();
            }
            else {
                $sub_title = InstagramCategoryLanguage::where('entity_id',$category_id)
                    ->where('entity_type',InstagramCategoryLanguage::SUB_TITLE)
                    ->where('language_id',$language_id)
                    ->whereNotNull('value')
                    ->pluck('value')
                    ->first();

                $optionData = InstagramCategoryOption::join('instagram_category_option_languages', function($query) {
                        $query->on('instagram_category_option_languages.entity_id','=','instagram_category_options.id');
                    })
                    ->where('instagram_category_options.instagram_category_id', $category_id)
                    ->where('instagram_category_option_languages.language_id', $language_id)
                    ->whereNotNull('instagram_category_option_languages.title')
//                    ->whereNotNull('instagram_category_option_languages.price')
//                    ->whereNotNull('instagram_category_option_languages.link')
                    ->orderBy('instagram_category_options.order', 'ASC')
                    ->select('instagram_category_options.instagram_category_id','instagram_category_options.id as option_id','instagram_category_option_languages.title as option','instagram_category_option_languages.price','instagram_category_option_languages.link')
                    ->get();
            }

            if (!empty($sub_title)){
                $data['sub_title'] = $sub_title;
            }
            if (!empty($optionData) && count($optionData) > 0){
                $data['options'] = $optionData;
            }

            foreach ($optionData as &$option){
                $is_subscribed = InstagramSubscribedPlan::where('user_id',Auth::user()->id)->where('instagram_category_id', $option->instagram_category_id)->where('instagram_category_option_id', $option->option_id)->count();
                $option['applied'] = ($is_subscribed>0) ? true : false;
            }

            if (!empty($data)) {
                return $this->sendSuccessResponse(Lang::get('messages.insta-category-option.success'), 200, $data);
            } else {
                return $this->sendSuccessResponse(Lang::get('messages.insta-category-option.empty'), 501);
            }
        } catch (Exception $e) {
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function subscribePlan(Request $request){
        $inputs = $request->all();
        try {
            $validation = $this->instaCategoryValidator->validateSubscribe($inputs);
            if ($validation->fails()) {
                return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
            }

            $instagram_category_id = $inputs['instagram_category_id'];
            $option_id = $inputs['option_id'];
            $user_id = Auth::user()->id;

            $is_exist_option = InstagramCategoryOption::where('id',$option_id)->where('instagram_category_id',$instagram_category_id)->first();
            if (empty($is_exist_option)){
                return $this->sendSuccessResponse(Lang::get('messages.subscribe-plan.not-found'), 501);
            }

            $delete_old_plan = InstagramSubscribedPlan::where('user_id', $user_id)->where('instagram_category_id', $instagram_category_id)->delete();
            InstagramSubscribedPlan::create([
                'user_id' => $user_id,
                'instagram_category_id' => $instagram_category_id,
                'instagram_category_option_id' => $option_id,
            ]);

            return $this->sendSuccessResponse(Lang::get('messages.subscribe-plan.success'), 200);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

}
