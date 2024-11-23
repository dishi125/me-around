<?php

namespace App\Http\Controllers\Api;

use App\Models\Brands;
use Illuminate\Http\Request;
use App\Models\BrandCategory;
use App\Models\BrandProducts;
use App\Models\ProductOrders;
use Log, Validator, DB, Auth;
use App\Models\UserCoinHistory;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Support\Facades\Lang;
use App\Jobs\OrderSendMail;
use Tymon\JWTAuth\Facades\JWTAuth;

class BrandApiController extends Controller
{
    public function index(Request $request)
    {
        $inputs = $request->all();
        try{
            $country = $inputs['country'] ?? 'KR';
            $language_id = $inputs['language_id'] ?? 4;

            $data = BrandCategory::leftjoin('brand_category_languages', function ($join) use ($language_id) {
                $join->on('brand_categories.id', '=', 'brand_category_languages.brand_category_id')
                    ->where('brand_category_languages.post_language_id', $language_id);
                })
                ->where('country_code',$country)
                ->select(
                    'brand_categories.*',
                    DB::raw('IFNULL(brand_category_languages.name, brand_categories.name) as display_name')
                )
                ->with('brands')->get();
            return $this->sendSuccessResponse(Lang::get('messages.general.success'), 200, $data);
        } catch (\Exception $e) {
            Log::info($e);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function brandProducts(Request $request)
    {
        $inputs = $request->all();
        try{
            $validator = Validator::make($request->all(), [
                'brand_id' => 'required'
            ]);
            if ($validator->fails()) {
                return $this->sendCustomErrorMessage($validator->errors()->toArray(), 422);
            }
            $brand_id = $inputs['brand_id'] ?? '';
            $userHaveCoin = 0;
            $user = '';
            $token = $request->header('Authorization');
            if(!empty($token)){
                $checktoken = str_replace('Bearer','',$token);
                if(!empty($checktoken) && !str_contains($checktoken, 'null')){
                    $user = JWTAuth::setToken(trim($checktoken))->toUser();
                    if(!empty($user)){
                        $userHaveCoin = getUserTotalCoin($user->id);
                    }
                }
            }
            $brandData = Brands::find($brand_id);
            $data = Brands::select(
                            "*",
                            DB::raw("(
                                CASE
                                    WHEN id = $brand_id THEN 1
                                    ELSE 0
                                END
                            ) AS is_active")
                        )
                        ->where('category_id',$brandData->category_id)
                        ->orderBy('sort_order')
                    ->get();

            $productList = BrandProducts::join('brands','brands.id','brand_products.brand_id')
                ->where('brand_products.brand_id',$brand_id)
                ->select(
                    'brand_products.*',
                    'brands.name as brand_name'
                )
                ->orderBy('brand_products.sort_order')
                ->paginate(config('constant.portfolio_pagination_count_shop'),"*","products_page");

            $productList->getCollection()->transform(function($item, $key) use ($user,$userHaveCoin){
                $item->can_purchase = (!empty($user) && $userHaveCoin > $item->coin_amount) ? true : false;
                $item->coin_amount = number_format($item->coin_amount,0);
                return $item;
            });
            return $this->sendSuccessResponse(Lang::get('messages.general.success'), 200, ["brands" => $data, "products" => $productList]);
        } catch (\Exception $e) {
            Log::info($e);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function purchaseProduct(Request $request)
    {
        $inputs = $request->all();
        $user = Auth::user();
        try{
            $validator = Validator::make($request->all(), [
                'product_id' => 'required'
            ]);
            if ($validator->fails()) {
                return $this->sendCustomErrorMessage($validator->errors()->toArray(), 422);
            }
            DB::beginTransaction();
            $product_id = $inputs['product_id'];

            $brandProduct = BrandProducts::find($product_id);
            $brandCoin = $brandProduct->coin_amount ?? '';

            $userCoin = getUserTotalCoin($user->id);

            if($brandCoin > $userCoin){
                return $this->sendSuccessResponse(Lang::get('messages.cards.purchase-denied'), 400);
            }

            UserCoinHistory::create([
                'user_id' => $user->id,
                'amount' => $brandCoin,
                'type' => UserCoinHistory::PURCHASE_PRODUCT,
                'transaction' => UserCoinHistory::DEBIT,
                'entity_id' => $product_id,
            ]);

            $date = Carbon::now()->format('Y-m-d H:i:s');
            ProductOrders::create([
                'user_id' => $user->id,
                'coin_amount' => $brandCoin,
                'product_id' => $product_id,
                'created_at' => $date
            ]);
            
            

            $productData = BrandProducts::select('name')
                ->where('id',$product_id)
                ->first();
            $mailData = (object)[
                'username' => $user->name,
                'phone' => $user->mobile,
                'price' => $brandCoin,
                'date' => $date,
                'productname' => $productData->name
            ];
            
            OrderSendMail::dispatch($mailData);
            
            DB::commit();
            return $this->sendSuccessResponse(Lang::get('messages.cards.order-placed'), 200, []);
        } catch (\Exception $e) {
            Log::info($e);
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }
}
