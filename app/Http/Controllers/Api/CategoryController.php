<?php

namespace App\Http\Controllers\Api;

use Auth;
use JWTAuth;
use Exception;
use App\Models\Banner;
use App\Models\Status;
use App\Models\Category;
use App\Models\Currency;
use App\Models\CreditPlans;
use App\Models\EntityTypes;
use App\Models\PackagePlan;
use App\Models\BannerImages;
use App\Models\PostLanguage;
use Illuminate\Http\Request;
use App\Models\CategoryTypes;
use Illuminate\Http\Response;
use App\Models\CategoryLanguage;
use App\Models\UserHiddenCategory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Lang;
use App\Validators\CategoryValidator;
use Illuminate\Support\Facades\Storage;

class CategoryController extends Controller
{
    private $categoryValidator;

    function __construct()
    {
        $this->categoryValidator = new CategoryValidator();
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index(Request $request)
    {
        $inputs = $request->all();
        try {
            Log::info('Start code for the get category');
            $validation = $this->categoryValidator->validateList($inputs);
            if ($validation->fails()) {
                Log::info('End code for the get category');
                return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
            }
            if ($request->has('category_type_id')) {
                $category = Category::where('status_id', Status::ACTIVE)->where('category_type_id', $inputs['category_type_id'])->where('parent_id', 0)->orderBy('order')->get();

                $category = $category->map(function ($item) use ($inputs) {
                    $items = Category::where('status_id', Status::ACTIVE)->where('parent_id', $item->id)->orderBy('order')->get();
                    $items = $items->makeHidden(['type', 'parent_id', 'sub_categories']);
                    foreach ($items as $i) {
                        $category_language = CategoryLanguage::where('category_id', $i->id)->where('post_language_id', $inputs['language_id'])->first();
                        $i['category_language_name'] = $category_language && $category_language->name != NULL ? $category_language->name : $i->name;
                    }
                    $item['children'] = $items;
                    return $item;
                });
            } else {
                $category = Category::all();
            }

            foreach ($category as $cat) {
                $category_language = CategoryLanguage::where('category_id', $cat->id)->where('post_language_id', $inputs['language_id'])->first();
                $cat['category_language_name'] = $category_language && $category_language->name != NULL ? $category_language->name : $cat->name;
            }
            $category = $category->makeHidden(['type', 'parent_id', 'sub_categories']);

            $sliders = [];
            if (isset($inputs['latitude']) && !empty($inputs['latitude']) && isset($inputs['longitude']) && !empty($inputs['longitude'])) {
                $main_country = getCountryFromLatLong($inputs['latitude'], $inputs['longitude']);
                $bannerImages = Banner::join('banner_images', 'banners.id', '=', 'banner_images.banner_id')
                    ->where('banners.entity_type_id', NULL)
                    ->where('banners.section', 'category')
                    ->where('banners.category_id', null)
                    ->whereNull('banners.deleted_at')
                    ->whereNull('banner_images.deleted_at')
                    ->where('banners.country_code', $main_country)
                    ->orderBy('banner_images.order')->orderBy('banner_images.id', 'desc')
                    ->get('banner_images.*');


                foreach ($bannerImages as $banner) {
                    $temp = [];
                    $temp['image'] = Storage::disk('s3')->url($banner->image);
                    $temp['link'] = $banner->link;
                    $temp['slide_duration'] = $banner->slide_duration;
                    $temp['order'] = $banner->order;
                    $sliders[] = $temp;
                }
            }

            Log::info('End code for the get category');
            if (!empty($category)) {
                return $this->sendSuccessResponse(Lang::get('messages.category.success'), 200, compact('category', 'sliders'));
            } else {
                return $this->sendSuccessResponse(Lang::get('messages.category.empty'), 501);
            }
        } catch (Exception $e) {
            Log::info('Exception in the  get category');
            Log::info($e);
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function indexCurrency(Request $request)
    {
        $inputs = $request->all();
        try {
            Log::info('Start code for the get currency');
            $currency = Currency::where('status_id', Status::ACTIVE)->get();
            Log::info('End code for the get currency');
            return $this->sendSuccessResponse(Lang::get('messages.category.currency-success'), 200, compact('currency'));

        } catch (Exception $e) {
            Log::info('Exception in the get currency');
            Log::info($e);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function getMearoundCategory(Request $request)
    {
        $inputs = $request->all();
        try {
            $language_id = $inputs['language_id'] ?? PostLanguage::ENGLISH;

            // Shop Category Start
            $shopCategory = Category::leftjoin('category_languages', function ($join) use ($language_id) {
                $join->on('category.id', '=', 'category_languages.category_id')
                    ->where('category_languages.post_language_id', $language_id);
            })
                ->where('category.status_id', Status::ACTIVE)
                ->where('category.category_type_id', CategoryTypes::SHOP)
                ->select(
                    'category.*',
                    DB::raw('IFNULL(category_languages.name, category.name) as category_language_name')
                )->get();

            // Shop Category END

            // Custom category Start
            $customCategory = Category::leftjoin('category_languages', function ($join) use ($language_id) {
                $join->on('category.id', '=', 'category_languages.category_id')
                    ->where('category_languages.post_language_id', $language_id);
            })
                ->where('category.status_id', Status::ACTIVE)
                ->where('category.category_type_id', CategoryTypes::CUSTOM)
                ->select(
                    'category.*',
                    DB::raw('IFNULL(category_languages.name, category.name) as category_language_name')
                )->get();

            // Custom category End

            $sliders = [];
            if (isset($inputs['latitude']) && !empty($inputs['latitude']) && isset($inputs['longitude']) && !empty($inputs['longitude'])) {
                $main_country = getCountryFromLatLong($inputs['latitude'], $inputs['longitude']);

                $sliders = BannerImages::join('banners', 'banners.id', '=', 'banner_images.banner_id')
                    ->where('banners.entity_type_id', NULL)
                    ->where('banners.section', 'category')
                    ->where('banners.category_id', null)
                    ->whereNull('banners.deleted_at')
                    ->whereNull('banner_images.deleted_at')
                    ->where('banners.country_code', $main_country)
                    ->orderBy('banner_images.order')
                    ->orderBy('banner_images.id', 'desc')
                    ->get('banner_images.*');
            }

            $response = [
                'main_category' => $shopCategory,
                'custom_category' => $customCategory,
                'sliders' => $sliders
            ];
            return $this->sendSuccessResponse(Lang::get('messages.report.category-success'), 200, $response);

        } catch (Exception $e) {
            Log::info($e);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function getCountryWiseCategory(Request $request)
    {
        $inputs = $request->all();
        try {
            $country = $inputs['country'] ?? '';
            $menu_key = $inputs['menu_key'] ?? 'all';
            $language_id = $inputs['language_id'] ?? PostLanguage::ENGLISH;

            $user = '';
            $token = $request->header('Authorization');
            $checktoken = str_replace('Bearer','',$token);
            if(!empty($token) && !empty($checktoken) && !str_contains($checktoken, 'null')){
                $user = JWTAuth::setToken(trim($checktoken))->toUser();
            }

            if(!empty($user)){
                $user_id = $user->id;
                $user_type = UserHiddenCategory::LOGIN;
            }else{
                $user_id = $inputs['user_id'] ?? null;
                $user_type = UserHiddenCategory::NONLOGIN;
            }
            $hiddenCategory = [];
            if(!empty($user_id)){
                $hiddenCategory = UserHiddenCategory::where('user_id',$user_id)->where('user_type',$user_type)->pluck('category_id')->toArray();
            }

            $query = Category::where('category.status_id', Status::ACTIVE)
                ->where('category.category_type_id', EntityTypes::SHOP)
                ->where('category.parent_id', 0)
                ->where(function ($query) use ($hiddenCategory) {
                    if (!empty($hiddenCategory)) {
                        $query->whereNotIn("category.id",$hiddenCategory);
                    }
                });
            if (!empty($country)) {
                $query = $query->join('category_settings', 'category_settings.category_id', 'category.id')
                    ->where('category_settings.country_code', $country)
                    ->where('category_settings.is_show',1)
                    ->orderBy('category_settings.order', 'ASC');

                /* if($menu_key != 'all'){
                    $query = $query->where('category_settings.menu_key',$menu_key);
                } */

                $query = $query->select('category.name', 'category.logo', 'category.id', 'category_settings.is_show', 'category_settings.order', 'category_settings.menu_key', 'category_settings.is_hidden');
            } else {
                $query = $query->select('category.name', 'category.logo', 'category.id', 'category.is_show', 'category.order', 'category.menu_key', 'category.is_hidden')
                        ->where('category.is_show',1)
                        ->orderBy('category.order', 'ASC');

                /* if($menu_key != 'all'){
                    $query = $query->where('category.menu_key',$menu_key);
                } */
            }

            $filterCategory = $query->get();
            if (!count($filterCategory)) {
                $filterCategory = Category::where('status_id', Status::ACTIVE)
                    ->where('category_type_id', EntityTypes::SHOP)
                    ->where('parent_id', 0)
                    ->where('category.is_show',1)
                    ->where(function ($query) use ($hiddenCategory) {
                        if (!empty($hiddenCategory)) {
                            $query->whereNotIn("category.id",$hiddenCategory);
                        }
                    })
                    ->select('name', 'logo', 'id', 'is_show', 'order','menu_key', 'is_hidden')
                    ->orderBy('order', 'ASC');
                /* if($menu_key != 'all'){
                    $filterCategory = $filterCategory->where('category.menu_key',$menu_key);
                } */
                $filterCategory = $filterCategory->get();
            }

            $filterCategory = $filterCategory->makeHidden(['sub_categories', 'parent_name', 'status_name', 'category_type_name']);

            $filterCategory = collect($filterCategory)->map(function ($item) use ($language_id) {
                $category_language = CategoryLanguage::where('category_id', $item->id)->where('post_language_id', $language_id)->first();
                $item->category_language_name = $category_language && $category_language->name != NULL ? $category_language->name : $item->name;
                return $item;
            });

            if($menu_key != 'all'){
                $filterCategory = $filterCategory->where('menu_key',$menu_key);
            }
//            $filterCategory = $filterCategory->where('is_hidden',0); //by dishita
            $category = [];
            // Filter by shop

            //$user = Auth::user();


            $latitude = $inputs['latitude'] ?? '';
            $longitude = $inputs['longitude'] ?? '';

            $creditPlans = CreditPlans::where('entity_type_id', EntityTypes::SHOP)->get();
            $bronzePlanKm = $silverPlanKm = $goldPlanKm = $platiniumPlanKm = 0;
            foreach ($creditPlans as $plan) {
                if ($plan->package_plan_id == PackagePlan::BRONZE) {
                    $bronzePlanKm = $plan->km;
                } else if ($plan->package_plan_id == PackagePlan::SILVER) {
                    $silverPlanKm = $plan->km;
                } else if ($plan->package_plan_id == PackagePlan::GOLD) {
                    $goldPlanKm = $plan->km;
                } else if ($plan->package_plan_id == PackagePlan::PLATINIUM) {
                    $platiniumPlanKm = $plan->km;
                }
            }

            $distance = "(6371 * acos(cos(radians(" . $latitude . "))
                        * cos(radians(addresses.latitude))
                        * cos(radians(addresses.longitude)
                        - radians(" . $longitude . "))
                        + sin(radians(" . $latitude . "))
                        * sin(radians(addresses.latitude))))";

            $limitByPackage = DB::raw('case when `shops`.`expose_distance` IS NOT NULL then `shops`.`expose_distance`
                when `users_detail`.package_plan_id = ' . PackagePlan::BRONZE . ' then ' . $bronzePlanKm . '
                when `users_detail`.package_plan_id = ' . PackagePlan::SILVER . ' then ' . $silverPlanKm . '
                when `users_detail`.package_plan_id = ' . PackagePlan::GOLD . ' then ' . $goldPlanKm . '
                when `users_detail`.package_plan_id = ' . PackagePlan::PLATINIUM . ' then ' . $platiniumPlanKm . '
                else 40 end ');

            foreach($filterCategory as $fcat){
                $catID = $fcat->id;
                if(!empty($latitude) && !empty($longitude)){
                    $shopsQuery = DB::table('shops')->leftjoin('addresses', function ($join) {
                        $join->on('shops.id', '=', 'addresses.entity_id')
                            ->where('addresses.entity_type_id', EntityTypes::SHOP);
                        })
                        ->leftjoin('users_detail', function ($join) {
                            $join->on('shops.user_id', '=', 'users_detail.user_id');
                        })
                        ->join('category', 'category.id', 'shops.category_id')
                        ->select(
                            'shops.id',
                            'shops.main_name',
                            'shops.shop_name',
                            'shops.is_discount'
                        )
                        ->where(function($query) use ($user){
                            if ($user) {
                                $query->whereRaw("shops.id NOT IN (select shop_id from shop_block_histories where user_id = {$user->id} AND is_block = 1)");
                            }
                        })
                        ->where('addresses.main_country', $country)
                        ->where('shops.status_id', Status::ACTIVE)
                        ->where('category_id', $catID)
                        ->whereNull('shops.deleted_at')
                        ->groupBy('shops.id')
                        ->selectRaw("{$distance} AS distance")
                        ->selectRaw("{$limitByPackage} AS priority")
                        ->whereRaw("{$distance} <= {$limitByPackage}")
                        ->limit(1)
                        ->count();

                    $fcat['count_posts'] = $shopsQuery;
                    if($shopsQuery > 0){
                        $category[] = $fcat;
                    }
                }else{
                    $category[] = $fcat;
                }
            }

            return $this->sendSuccessResponse(Lang::get('messages.category.success'), 200, compact('category','hiddenCategory'));
        } catch (Exception $e) {
            Log::info($e);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }
}
