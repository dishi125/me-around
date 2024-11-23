<?php

namespace App\Http\Controllers\Api;

use App\Models\EntityTypes;
use App\Models\ShopPost;
use App\Models\Status;
use App\Models\Shop;
use App\Models\UserEntityRelation;
use App\Models\ShopPriceCategory;
use App\Models\ShopPrices;
use App\Models\DiscountCondition;
use App\Models\ShopDiscountCondition;
use App\Models\ShopPriceImages;
use App\Validators\ShopPriceValidator;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;


class ShopPriceController extends Controller
{
    private $shopPriceValidator;

    function __construct()
    {
        $this->shopPriceValidator = new ShopPriceValidator();
    }
    /*================ Shop Prices Category Code Start ==================*/

    public function indexPriceCategory(Request $request, $id)
    {
        $inputs = $request->all();
        try {
            Log::info('Start code for the get shop items category list');
            $shop = Shop::find($id);
            if($shop){
               $shopItems = ShopPriceCategory::where('shop_id',$id)->get();
                Log::info('End code for the get shop items category list');
                return $this->sendSuccessResponse(Lang::get('messages.shop-price-category.list-success'), 200, $shopItems);
            }else {
                Log::info('End code for the get shop items category list');
                return $this->sendSuccessResponse(Lang::get('messages.shop.empty'), 402);
            }

        } catch (\Exception $e) {
            Log::info('Exception in the get shop items category list');
            Log::info($e);
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function storePriceCategory(Request $request)
    {
        $inputs = $request->all();
        try {
            Log::info('Start code for the add shop items category');
            DB::beginTransaction();
            $validation = $this->shopPriceValidator->validateItemCategoryStore($inputs);
            if ($validation->fails()) {
                Log::info('End code for the add shop items category');
                return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
            }
            $shop = Shop::find($inputs['shop_id']);
            if($shop){
                $data = [
                    'shop_id' => $inputs['shop_id'],
                    'name' => $inputs['name'],
                ];
                $shopItem = ShopPriceCategory::create($data);
                DB::commit();
                Log::info('End code for the add shop items category');
                return $this->sendSuccessResponse(Lang::get('messages.shop-price-category.add-success'), 200, $shopItem);
            }else {
                Log::info('End code for the add shop items category');
                return $this->sendSuccessResponse(Lang::get('messages.shop.empty'), 402);
            }

        } catch (\Exception $e) {
            Log::info('Exception in the get add items');
            Log::info($e);
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function editPriceCategory(Request $request, $id)
    {
        $inputs = $request->all();
        try {
            Log::info('Start code for the edit shop items category ');
            $shopItemCategory = ShopPriceCategory::find($id);
            if($shopItemCategory){
                Log::info('End code for the edit shop items category');
                return $this->sendSuccessResponse(Lang::get('messages.shop-price-category.edit-success'), 200, $shopItemCategory);
            }else {
                Log::info('End code for the edit shop items category');
                return $this->sendSuccessResponse(Lang::get('messages.shop-price-category.empty'), 402);
            }

        } catch (\Exception $e) {
            Log::info('Exception in the edit shop items category');
            Log::info($e);
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }
    public function updatePriceCategory(Request $request, $id)
    {
        $inputs = $request->all();
        try {
            Log::info('Start code for the update shop items category ');
            DB::beginTransaction();
            $shopItemCategory = ShopPriceCategory::find($id);
            if($shopItemCategory){
                $validation = $this->shopPriceValidator->validateItemsCategoryUpdate($inputs);
                if ($validation->fails()) {
                    Log::info('End code for the update shop items category');
                    return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
                }
                $data = [
                    'name' => $inputs['name'],
                ];
                $updateShopItem = ShopPriceCategory::where('id',$id)->update($data);
                $return = ShopPriceCategory::find($id);
                DB::commit();
                Log::info('End code for the update shop items category');
                return $this->sendSuccessResponse(Lang::get('messages.shop-price-category.update-success'), 200, $return);
            }else {
                Log::info('End code for the update shop items category');
                return $this->sendSuccessResponse(Lang::get('messages.shop-price-category.empty'), 402);
            }

        } catch (\Exception $e) {
            Log::info('Exception in the update shop items category');
            Log::info($e);
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }
    public function deletePriceCategory(Request $request, $id)
    {
        $inputs = $request->all();
        try {
            Log::info('Start code for the delete shop items category ');
            DB::beginTransaction();
            $shopItemCategory = ShopPriceCategory::find($id);
            if($shopItemCategory){
                $shopItems = ShopPrices::where('shop_price_category_id',$id)->delete();
               $shopItemDelete = ShopPriceCategory::where('id',$id)->delete();
               DB::commit();
                Log::info('End code for the delete shop items category');
                return $this->sendSuccessResponse(Lang::get('messages.shop-price-category.delete-success'), 200);
            }else {
                Log::info('End code for the delete shop items category');
                return $this->sendSuccessResponse(Lang::get('messages.shop-price-category.empty'), 402);
            }

        } catch (\Exception $e) {
            Log::info('Exception in the delete shop items category');
            Log::info($e);
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    /*================ Shop Prices Category Code End ==================*/

    /*================ Shop Prices Code Start ==================*/

    public function storePrice(Request $request)
    {
        $inputs = $request->all();
        try {
            Log::info('Start code for the add shop items');
            DB::beginTransaction();
            $validation = $this->shopPriceValidator->validateStore($inputs);
            if ($validation->fails()) {
                Log::info('End code for the add shop items');
                return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
            }
            $shopItemCatogory = ShopPriceCategory::find($inputs['item_category_id']);
            if($shopItemCatogory){
                $data = [
                    'shop_price_category_id' => $inputs['item_category_id'],
                    'name' => $inputs['name'],
                    'price' => $inputs['price'],
                    'discount' => isset($inputs['discounted_price']) ? $inputs['discounted_price'] : 0,
                ];

                if(isset($inputs['id']) && !empty($inputs['id'])){
                    ShopPrices::where('id',$inputs['id'])->update($data);
                    $shopItem = ShopPrices::find($inputs['id']);
                    $shopItemID = $inputs['id'];
                }else{
                    $shopItem = ShopPrices::create($data);
                    $shopItemID = $shopItem->id;
                    $profileController = new \App\Http\Controllers\Api\ShopProfileController;
                    $profileController->checkShopStatus($shopItemCatogory->shop_id);
                }

                $shopsPriceFolder = config('constant.shops_price');

                if (!Storage::disk('s3')->exists($shopsPriceFolder)) {
                    Storage::disk('s3')->makeDirectory($shopsPriceFolder);
                }

                if(isset($inputs['images']) && !empty($inputs['images'])){
                    foreach($inputs['images'] as $imageItem) {
                        $image = $imageItem['image'];
                        $thumb_image = $imageItem['thumb_image'] ?? '';

                        $imageUpdateID = $imageItem['id'] ?? 0;

                        if($image){
                            $mainImage = Storage::disk('s3')->putFile($shopsPriceFolder, $image,'public');
                            $fileName = basename($mainImage);
                            $image_url = $shopsPriceFolder . '/' . $fileName;
                            $thumbImageUrl = '';

                            if(!empty($thumb_image)){
                                $thumbImageMainImage = Storage::disk('s3')->putFile($shopsPriceFolder, $thumb_image,'public');
                                $thumbFileName = basename($thumbImageMainImage);
                                $thumbImageUrl = $shopsPriceFolder . '/' . $thumbFileName;

                                if (!filter_var($thumbImageUrl, FILTER_VALIDATE_URL)) {
                                    $newurl = Storage::disk('s3')->url($thumbImageUrl);
                                } else {
                                    $newurl = $thumbImageUrl;
                                }
                                $newThumb = Image::make($newurl)->resize(200, 200, function ($constraint) {
                                    $constraint->aspectRatio();
                                })->encode(null,90);
                                Storage::disk('s3')->put($shopsPriceFolder.'/thumb/'.$thumbFileName,  $newThumb->stream(), 'public');
                            }

                            if(empty($thumb_image) && !empty($image_url)){
                                if (!filter_var($image_url, FILTER_VALIDATE_URL)) {
                                    $newurl = Storage::disk('s3')->url($image_url);
                                } else {
                                    $newurl = $image_url;
                                }
                                $newThumb = Image::make($newurl)->resize(200, 200, function ($constraint) {
                                    $constraint->aspectRatio();
                                })->encode(null,90);
                                Storage::disk('s3')->put($shopsPriceFolder.'/thumb/'.$fileName,  $newThumb->stream(), 'public');
                            }

                            if(empty($imageUpdateID)){
                                ShopPriceImages::create([
                                    'shop_price_id' => $shopItemID,
                                    'image' => $image_url,
                                    'thumb_url' => $thumbImageUrl,
                                    'order' => $imageItem['order'] ?? 0,
                                ]);
                            }else{
                                $imageData = ShopPriceImages::whereId($imageUpdateID)->first();
                                if(!empty($imageData)){
                                    Storage::disk('s3')->delete($imageData->image);
                                    if(!empty($imageData->thumb_url)){
                                        Storage::disk('s3')->delete($imageData->thumb_url);
                                    }
                                    $imageData->update([
                                        'image' => $image_url,
                                        'thumb_url' => $thumbImageUrl
                                    ]);
                                }

                            }
                        }
                    }
                }


                if(isset($inputs['deleted_images']) && !empty($inputs['deleted_images'])){
                    $imagesDelete = ShopPriceImages::whereIn('id',$inputs['deleted_images'])->where('shop_price_id',$shopItemID)->get();
                    foreach($imagesDelete as $delete){
                        Storage::disk('s3')->delete($delete->image);
                        $delete->delete();
                    }
                }

                DB::commit();
                Log::info('End code for the add shop items');
                if(isset($inputs['id']) && !empty($inputs['id'])){
                    return $this->sendSuccessResponse(Lang::get('messages.shop-price.update-success'), 200, $shopItem);
                }else{
                    return $this->sendSuccessResponse(Lang::get('messages.shop-price.add-success'), 200, $shopItem);
                }
            }else {
                Log::info('End code for the add shop items');
                return $this->sendSuccessResponse(Lang::get('messages.shop-price-category.empty'), 402);
            }

        } catch (\Exception $e) {
            Log::info('Exception in the get add items');
            Log::info($e);
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function editPrice(Request $request, $id)
    {
        $inputs = $request->all();
        try {
            Log::info('Start code for the edit shop items ');
            $shopItem = ShopPrices::find($id);
            if($shopItem){
                Log::info('End code for the edit shop items');
                return $this->sendSuccessResponse(Lang::get('messages.shop-price.edit-success'), 200, $shopItem);
            }else {
                Log::info('End code for the edit shop items');
                return $this->sendSuccessResponse(Lang::get('messages.shop-price.empty'), 402);
            }

        } catch (\Exception $e) {
            Log::info('Exception in the edit shop items');
            Log::info($e);
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }
    public function updatePrice(Request $request, $id)
    {
        $inputs = $request->all();
        try {
            Log::info('Start code for the update shop items ');
            DB::beginTransaction();
            $shopItem = ShopPrices::find($id);
            if($shopItem){
                $validation = $this->shopPriceValidator->validateUpdate($inputs);
                if ($validation->fails()) {
                    Log::info('End code for the update shop items');
                    return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
                }
                $data = [
                    'name' => $inputs['name'],
                    'price' => $inputs['price'],
                    'discount' => isset($inputs['discounted_price']) ? $inputs['discounted_price'] : 0,
                ];
                $updateShopItem = ShopPrices::where('id',$id)->update($data);
                $updateShopItem = ShopPrices::find($id);
                DB::commit();
                Log::info('End code for the update shop items');
                return $this->sendSuccessResponse(Lang::get('messages.shop-price.update-success'), 200, $updateShopItem);
            }else {
                Log::info('End code for the update shop items');
                return $this->sendSuccessResponse(Lang::get('messages.shop-price.empty'), 402);
            }

        } catch (\Exception $e) {
            Log::info('Exception in the update shop items');
            Log::info($e);
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }
    public function deletePrice(Request $request, $id)
    {
        $inputs = $request->all();
        try {
            Log::info('Start code for the delete shop items ');
            DB::beginTransaction();
            $shopItem = ShopPrices::find($id);
            if($shopItem){
               $shopItemDelete = ShopPrices::where('id',$id)->delete();
               DB::commit();
                Log::info('End code for the delete shop items');
                return $this->sendSuccessResponse(Lang::get('messages.shop-price.delete-success'), 200, []);
            }else {
                Log::info('End code for the delete shop items');
                return $this->sendSuccessResponse(Lang::get('messages.shop-price.empty'), 402);
            }

        } catch (\Exception $e) {
            Log::info('Exception in the delete shop items');
            Log::info($e);
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    /*================ Shop Prices Code End ==================*/

    public function getDiscountCondition(Request $request)
    {
        $inputs = $request->all();
        try {
            Log::info('Start code for the get discount conditions');
            DB::beginTransaction();
            $validation = $this->shopPriceValidator->validateGetDiscountCondition($inputs);
            if ($validation->fails()) {
                Log::info('End code for the get discount conditions');
                return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
            }
            $discount_conditions = DiscountCondition::get(['id','title']);
            $language_id = $request->has('language_id') ? $inputs['language_id'] : 4;
            $selectedConditions = ShopDiscountCondition::where('shop_id' ,$inputs['shop_id'])->pluck('discount_condition_id')->toArray();
            foreach($discount_conditions as $condition) {
                $key = $condition->title.'_'.$language_id;
                $condition->condition = __("notice.$key");
                $condition->is_selected = in_array($condition->id, $selectedConditions) ? 1 : 0;
            }

            DB::commit();
            Log::info('End code for the get discount conditions');
            return $this->sendSuccessResponse(Lang::get('messages.shop-price.get-discount-success'), 200, compact('discount_conditions'));

        } catch (\Exception $e) {
            Log::info('Exception in the get discount conditions');
            Log::info($e);
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function selectDiscountCondition(Request $request)
    {
        $inputs = $request->all();
        try {
            Log::info('Start code for the get discount conditions');
            DB::beginTransaction();
            $validation = $this->shopPriceValidator->validateSelectDiscountCondition($inputs);
            if ($validation->fails()) {
                Log::info('End code for the get discount conditions');
                return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
            }

            ShopDiscountCondition::where('shop_id' ,$inputs['shop_id'])->delete();
            foreach($inputs['discount_condition_id'] as $condition) {
                $discount_conditions = ShopDiscountCondition::create(['discount_condition_id' => $condition,'shop_id' => $inputs['shop_id']]);
            }

            DB::commit();
            Log::info('End code for the get discount conditions');
            return $this->sendSuccessResponse(Lang::get('messages.shop-price.select-discount-success'), 200);

        } catch (\Exception $e) {
            Log::info('Exception in the get discount conditions');
            Log::info($e);
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }
}
