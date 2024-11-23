<?php

namespace App\Http\Controllers\Api;

use App\Models\RequestForm;
use App\Models\EntityTypes;
use App\Models\Category;
use App\Models\CategoryTypes;
use App\Models\Status;
use App\Models\RequestFormStatus;
use App\Models\PackagePlan;
use App\Models\CreditPlans;
use App\Models\ShopPost;
use App\Models\Shop;
use App\Models\CategoryLanguage;
use App\Models\Config;
use App\Validators\RequestFormValidator;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use App\Validators\ShopProfileValidator;
use App\Validators\CategoryValidator;


class SuggestCustomController extends Controller
{
    private $requestFormValidator;
    private $shopProfileValidator;
    private $categoryValidator;

    function __construct()
    {
        $this->requestFormValidator = new RequestFormValidator();
        $this->shopProfileValidator = new ShopProfileValidator();
        $this->categoryValidator = new CategoryValidator();
    }


    public function getCustomCategory(Request $request)
    {
        try {
            $inputs = $request->all();
            Log::info('Start code get report category');
            $validation = $this->categoryValidator->validateList($inputs);
            if ($validation->fails()) {
                Log::info('End code for the get category');
                return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
            }
            $returnData = [];
            $getConfig = Config::where(['key' => Config::SUGGESTED_CATEGORY])->first();

            if($getConfig && $getConfig->value == 1){

                $returnData = Category::where('status_id',Status::ACTIVE)->where('category_type_id',CategoryTypes::CUSTOM)->get();
                $returnData->map(function ($item) use($inputs) {
                    $category_language = CategoryLanguage::where('category_id',$item->id)->where('post_language_id',$inputs['language_id'])->first();
                    $item['category_language_name'] = $category_language && $category_language->name != NULL ? $category_language->name : $item->name;

                    return $item;
                });

            }
            $data = [
                'category' => $returnData
            ];
            Log::info('End code get report category');
            return $this->sendSuccessResponse(Lang::get('messages.report.category-success'), 200, $data);
        } catch (\Exception $e) {
            Log::info('Exception in get report category');
            Log::info($e);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function store(Request $request)
    {
        try {
            Log::info('Start code for add custom form request');
            DB::beginTransaction();
            $inputs = $request->all();

            $user = Auth::user();
            $inputs['user_id'] = $user->id;

            $validation = $this->requestFormValidator->validateCustomStore($inputs);

            if ($validation->fails()) {
                Log::info('End code for add custom form request');
                return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
            }

            $location = $this->addCurrentLocation($inputs['country_id'], $inputs['state_id'], $inputs['city_id']);

            $requestData = [
                'user_id' => $user->id,
                'entity_type_id' => $inputs['entity_type_id'],
                'category_id' => $inputs['category_id'],
                'name' => $inputs['name'],
                'address' => $inputs['address'],
                'country_id' => $location['country']->id,
                'city_id' => $location['city']->id,
                'latitude' => $inputs['latitude'],
                'longitude' => $inputs['longitude'],
                'request_status_id' => RequestFormStatus::PENDING,
                'email' => $inputs['email'],
                'request_count' => DB::raw('request_count + 1'),
                'manager_id' => 0,
                'recommend_code' => $request->has('recommend_code') ? $inputs['recommend_code'] : null
            ];

            $path = config('constant.requested-client');

            if (!Storage::exists($path)) {
                Storage::makeDirectory($path);
            }
            if ($request->hasFile('business_licence')) {
                $business_licence = Storage::disk('s3')->putFile($path, $request->file('business_licence'),'public');
                $fileName = basename($business_licence);
                $requestData['business_licence'] = $path . '/' . $fileName;
            }

            if ($inputs['entity_type_id'] == EntityTypes::SHOP) {
                if ($request->hasFile('identification_card')) {
                    $identification_card = Storage::disk('s3')->putFile($path, $request->file('identification_card'),'public');
                    $fileName = basename($identification_card);
                    $requestData['identification_card'] = $path . '/' . $fileName;
                }

                if ($request->hasFile('best_portfolio')) {
                    $best_portfolio = Storage::disk('s3')->putFile($path, $request->file('best_portfolio'),'public');
                    $fileName = basename($best_portfolio);
                    $requestData['best_portfolio'] = $path . '/' . $fileName;
                }
            }
            $request_form = RequestForm::updateOrCreate(['user_id' => $user->id,
            'entity_type_id' => $inputs['entity_type_id']],$requestData);
            DB::commit();
            Log::info('End code for add custom form request');
            return $this->sendSuccessResponse(Lang::get('messages.form-request.success'), 200, compact('request_form'));
        } catch (\Exception $ex) {
            DB::rollBack();
            Log::info('Exception in the add custom form request');
            Log::info($ex);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
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

    public function getAllCustomShops(Request $request)
    {
        try {
            Log::info('Start code for get all shops');
            $inputs = $request->all();
            $category_id = isset($inputs['category_id']) ? $inputs['category_id'] : 0;
            $validation = $this->shopProfileValidator->validateGetShop($inputs);

            if ($validation->fails()) {
                Log::info('End code for get all shops');
                return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
            }

            $main_country = getCountryFromLatLong($inputs['latitude'],$inputs['longitude']);

            $returnData = [];
            $creditPlans = CreditPlans::where('entity_type_id', EntityTypes::SHOP)->get();
            $bronzePlanKm = $silverPlanKm = $goldPlanKm = $platiniumPlanKm = 0;
            foreach($creditPlans as $plan) {
                if($plan->package_plan_id == PackagePlan::BRONZE) {
                    $bronzePlanKm = $plan->km;
                }else if($plan->package_plan_id == PackagePlan::SILVER) {
                    $silverPlanKm = $plan->km;
                }else if($plan->package_plan_id == PackagePlan::GOLD) {
                    $goldPlanKm = $plan->km;
                }else if($plan->package_plan_id == PackagePlan::PLATINIUM) {
                    $platiniumPlanKm = $plan->km;
                }
            }
            $shopsQuery = Shop::leftjoin('addresses', function ($join) {
                                    $join->on('shops.id', '=', 'addresses.entity_id')
                                         ->where('addresses.entity_type_id', EntityTypes::SHOP);
                                })
                                ->leftjoin('users_detail', function ($join) {
                                    $join->on('shops.user_id', '=', 'users_detail.user_id');
                                })
                                ->join('category','category.id','shops.category_id')
                                ->where('category.category_type_id', CategoryTypes::CUSTOM)
                                ->where('addresses.main_country',$main_country)
                                ->where('shops.status_id',Status::ACTIVE);

            $recentPortfolioQuery = ShopPost::join('shops','shop_posts.shop_id','shops.id')
                                    ->leftjoin('addresses', function ($join) {
                                        $join->on('shops.id', '=', 'addresses.entity_id')
                                             ->where('addresses.entity_type_id', EntityTypes::SHOP);
                                    })
                                    ->leftjoin('users_detail', function ($join) {
                                        $join->on('shops.user_id', '=', 'users_detail.user_id');
                                    })
                                    ->join('category','category.id','shops.category_id')
                                    ->where('category.category_type_id', CategoryTypes::CUSTOM)
                                    ->where('addresses.main_country',$main_country)
                                    ->where('shops.status_id',Status::ACTIVE);



            if($category_id != 0){
                $shopsQuery = $shopsQuery->where('shops.category_id',$category_id);
                $recentPortfolioQuery = $recentPortfolioQuery->where('shops.category_id',$category_id);
            }


            $distance = "(6371 * acos(cos(radians(".$inputs['latitude']."))
                     * cos(radians(addresses.latitude))
                     * cos(radians(addresses.longitude)
                     - radians(".$inputs['longitude']."))
                     + sin(radians(".$inputs['latitude']."))
                     * sin(radians(addresses.latitude))))";

            $sqlPriority = DB::raw('case when `shops`.`expose_distance` IS NOT NULL then `shops`.`expose_distance`
             when `users_detail`.package_plan_id = '. PackagePlan::BRONZE .' and '. $distance .'  < '. $bronzePlanKm .' then 4
             when `users_detail`.package_plan_id = '. PackagePlan::SILVER .' and '. $distance .'  < '. $silverPlanKm .' then 3
             when `users_detail`.package_plan_id = '. PackagePlan::GOLD .' and '. $distance .' < '. $goldPlanKm .' then 2
             when `users_detail`.package_plan_id = '. PackagePlan::PLATINIUM .' and '. $distance .' < '. $platiniumPlanKm .' then 1
             else 5 end ');

            $shops = $shopsQuery->orderby('priority')
                    ->orderby('distance')
                    ->orderby('shopreviews_count','desc')
                    ->orderby('completed_customer_count','desc')
                    ->select('shops.*')
                    ->selectRaw("{$distance} AS distance")
                    ->selectRaw("{$sqlPriority} AS priority")
                    ->withCount([
                        'shopreviews' => function($query) {
                            $query->where('entity_type_id', EntityTypes::SHOP);
                        }
                    ])
                    ->withCount([
                        'completedCustomer' => function($query) {
                            $query->where('entity_type_id', EntityTypes::SHOP);
                        }
                    ])
                    ->get();

            $recentPortfolio = $recentPortfolioQuery->orderBy('shop_posts.created_at','desc')
                                ->orderby('priority')
                                ->orderby('distance')
                                ->select('shop_posts.*')
                                ->selectRaw("{$distance} AS distance")
                                ->selectRaw("{$sqlPriority} AS priority")
                                ->get();

            foreach($shops as $shop) {
                $shop->distance = number_format((float)$shop->distance, 1, '.', '');
            }

            foreach($recentPortfolio as $shop) {
                $shop->distance = number_format((float)$shop->distance, 1, '.', '');
            }

            $returnData['all_shops'] = $shops;
            $returnData['recent_portfolio'] = $recentPortfolio;
            Log::info('End code get all shops');
            return $this->sendSuccessResponse(Lang::get('messages.shop.success'), 200, $returnData);
        } catch (\Exception $e) {
            Log::info('Exception in get all shops');
            Log::info($e);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function getDeeplink(Request $request)
    {
        $dest_id = $request->category_id;
        // get device using user agent
        if (preg_match('/(iphone|ipad|ipod)/i', $_SERVER['HTTP_USER_AGENT'])) {
            $browser = 'ios';
        } elseif (preg_match('/(android)/i', $_SERVER['HTTP_USER_AGENT'])) {
            $browser = 'android';
        } elseif (preg_match('/(bitlybot)/i', $_SERVER['HTTP_USER_AGENT'])) {
            $browser = 'curl';
        } else {
            $browser = 'other';
        }
        $data['browser'] = $browser;
        $data['dest'] = 'suggest_custom';
        $data['dest_id'] = $dest_id;
        return view('admin.deep-link',compact('data'));
    }
}
