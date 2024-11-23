<?php

namespace App\Http\Controllers\Api;

use Validator;
use App\Models\ShopPost;
use Illuminate\Http\Request;
use App\Models\ShopBlockHistory;
use App\Models\ShopReportHistory;
use App\Models\EntityTypes;
use App\Models\ShopImagesTypes;
use App\Models\ShopDetailLanguage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Models\ShopReportAttachment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Storage;

class ShopBlockController extends Controller
{
    public function blockShop(Request $request)
    {
        $inputs = $request->all();
        try {
            $validation = Validator::make($inputs, [
                'shop_id' => 'required',
            ], [], [
                'shop_id' => 'Shop ID',
            ]);

            if ($validation->fails()) {
                return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
            }

            $user = Auth::user();
            DB::beginTransaction();
            $shop_id = $inputs['shop_id'];

            ShopBlockHistory::updateOrCreate([
                    'user_id' => $user->id,
                    'shop_id' => $shop_id
                ],[
                    'is_block' => 1
                ]
            );

            DB::commit();
            return $this->sendSuccessResponse(Lang::get('messages.user-profile.block-shop'), 200);
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::info($th);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function unblockShop(Request $request)
    {
        $inputs = $request->all();
        try {
            $validation = Validator::make($inputs, [
                'shop_id' => 'required',
            ], [], [
                'shop_id' => 'Shop ID',
            ]);

            if ($validation->fails()) {
                return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
            }

            $user = Auth::user();
            DB::beginTransaction();
            $shop_id = $inputs['shop_id'];
            ShopBlockHistory::where('user_id' , $user->id)->where( 'shop_id' , $shop_id)->update([ 'is_block' => 0 ] );
            DB::commit();
            return $this->sendSuccessResponse(Lang::get('messages.user-profile.un-block-shop'), 200);
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::info($th->getMessage());
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function blockShopList(Request $request)
    {
        $inputs = $request->all();
        try {
            $validation = Validator::make($inputs, [
                'latitude' => 'required',
                'longitude' => 'required',
            ], [], [
                'latitude' => 'Latitude',
                'longitude' => 'Longitude',
            ]);

            if ($validation->fails()) {
                return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
            }

            $user = Auth::user();
            $perPage = $inputs['per_page'] ?? 6;
            $latitude = $inputs['latitude'] ?? '';
            $longitude = $inputs['longitude'] ?? '';
            $language_id = $inputs['language_id'] ?? 4;
    
            $coordinate = $longitude . ',' . $latitude;

            $results = ShopBlockHistory::where('shop_block_histories.user_id',$user->id)
                ->where('shop_block_histories.is_block',1)
                ->join('shops','shops.id','shop_block_histories.shop_id')
                ->leftjoin('addresses', function ($join) {
                    $join->on('shops.id', '=', 'addresses.entity_id')
                        ->where('addresses.entity_type_id', EntityTypes::SHOP);
                })
                ->leftjoin('cities', function ($join) {
                    $join->on('addresses.city_id', '=', 'cities.id');
                })
                ->leftjoin('shop_detail_languages', function ($join) use ($language_id) {
                    $join->on('shops.id', '=', 'shop_detail_languages.shop_id')
                        ->where('shop_detail_languages.key', ShopDetailLanguage::SPECIALITY_OF)
                        ->where('shop_detail_languages.entity_type_id', EntityTypes::SHOP)
                        ->where('shop_detail_languages.language_id', $language_id);
                })
                ->leftjoin('shop_images', function ($join) {
                    $join->on('shops.id', '=', 'shop_images.shop_id')
                        ->where('shop_images.shop_image_type', ShopImagesTypes::THUMB)
                        ->whereNull('shop_images.deleted_at');
                })
                ->leftjoin('users_detail', function ($join) {
                    $join->on('shops.user_id', '=', 'users_detail.user_id');
                })
                ->select(
                    'shops.id',
                    'shops.main_name',
                    'shops.shop_name',
                    //'shops.speciality_of',
                    'shops.is_discount',
                    'cities.name as city_name',
                    'shops.user_id as user_id',
                    'shop_images.image as thumbnail_image',
                    'shop_images.id as thumbnail_image_id',
                    DB::raw('IFNULL(shop_detail_languages.value, shops.speciality_of) as speciality_of'),
                    DB::raw("IFNULL(CAST(ROUND(st_distance_sphere(POINT(addresses.longitude,addresses.latitude), POINT( $coordinate)) * .001,2) AS DECIMAL(21,2)) ,'') as shop_distance"),
                    'users_detail.name'
                )
                ->paginate($perPage,"*","all_shop_page");

            $results->getCollection()->transform(function($item, $key) {
                $item->thumbnail_image = Storage::disk('s3')->url($item->thumbnail_image);
                return $item;
            });
            return $this->sendSuccessResponse(Lang::get('messages.user-profile.block-shop-list'), 200,$results);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function reportShop(Request $request)
    {
        $inputs = $request->all();
        try {
            $validation = Validator::make($inputs, [
                'shop_id' => 'required',
                'description' => 'required',
                'images' => 'required|array|min:1',
                'images.*.type' => 'required',
                'images.*.file' => 'required',
                'images.*.video_thumbnail' => 'required_if:images.*.type,==,2',
            ], [], [
                'shop_id' => 'Shop ID',
                'images.*.type' => 'Type',
                'images.*.file' => 'Attachment',
                'images.*.video_thumbnail' => 'Video Thumbnail',
            ]);

            if ($validation->fails()) {
                return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
            }

            $user = Auth::user();
            DB::beginTransaction();
            $shop_id = $inputs['shop_id'];
            $description = $inputs['description'] ?? null;
            $images = $inputs['images'] ?? [];


            $report = ShopReportHistory::create([
                'user_id' => $user->id,
                'shop_id' => $shop_id,
                'description' => $description
            ]);

            if(!empty($images)){
                $shopsFolder = config('constant.shops') . '/' . $shop_id . "/report/" . $report->id;

                if (!Storage::exists($shopsFolder)) {
                    Storage::makeDirectory($shopsFolder);
                }
                foreach($images as $imagesData){
                    $insertData = [];
                    $insertData['shop_report_id'] = $report->id;
                    $insertData['type'] = $imagesData['type'] == ShopPost::IMAGE ? 'image' : 'video';

                    if (is_file($imagesData['file'])) {
                        $postImage = Storage::disk('s3')->putFile($shopsFolder, $imagesData['file'], 'public');
                        $fileName = basename($postImage);
                        $image_url = $shopsFolder . '/' . $fileName;
                        $insertData['attachment_item'] =  $image_url;
                    }

                    if ($imagesData['type'] != ShopPost::IMAGE && !empty($imagesData['video_thumbnail']) && is_file($imagesData['video_thumbnail'])) {
                        $postThumbImage = Storage::disk('s3')->putFile($shopsFolder, $imagesData['video_thumbnail'], 'public');
                        $fileThumbName = basename($postThumbImage);
                        $image_thumb_url = $shopsFolder . '/' . $fileThumbName;
                        $insertData['video_thumbnail'] =  $image_thumb_url;
                    }

                    ShopReportAttachment::create($insertData);
                }
            }

            DB::commit();
            return $this->sendSuccessResponse(Lang::get('messages.user-profile.report-shop'), 200);
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::info($th->getMessage());
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }
}
