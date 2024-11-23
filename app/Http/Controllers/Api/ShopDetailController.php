<?php

namespace App\Http\Controllers\Api;

use Validator;
use Carbon\Carbon;
use App\Models\Shop;
use App\Models\ShopDetail;
use App\Models\EntityTypes;
use App\Models\PostLanguage;
use Illuminate\Http\Request;
use App\Models\RecycleOption;
use App\Models\ShopDetailLanguage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Mail;
use App\Validators\ShopDetailValidator;
use Illuminate\Support\Facades\Storage;


class ShopDetailController extends Controller
{
    private $shopDetailValidator;
    function __construct()
    {
        $this->shopDetailValidator = new ShopDetailValidator();
    }

    public function getRecycleOptions(Request $request){
        try {
            DB::beginTransaction();  
            $getRecycleOption = RecycleOption::get();

            $inputs = $request->all();
            $language_id = $inputs['language_id'] ?? 4;

            $recycleOption = collect($getRecycleOption)->map(function ($value) use($language_id){

                if(!empty($value->type) && $value->type == 'time') {
                    $type = __('messages.language_'.$language_id.'.time');
                    $value_name = $value->value." ".__('messages.language_'.$language_id.'.time');
                } else if(!empty($value->type) && $value->type == 'times'){
                    $type = __('messages.language_'.$language_id.'.times');   
                    $value_name = $value->value." ".__('messages.language_'.$language_id.'.times');
                } else {
                    $type = __('messages.language_'.$language_id.'.permanent');  
                    $value_name = __('messages.language_'.$language_id.'.permanent');
                }

                return ['id'=>$value->id,'value'=>$value_name , 'type' => $type];
            })->toArray();

            return $this->sendSuccessResponse(Lang::get('messages.shop.recycle_option'), 200,$recycleOption);

        } catch (\Exception $e) {
            Log::info($e);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }

    }

    public function saveShopDetail(Request $request){
        try {
            DB::beginTransaction();  
            $inputs = $request->all();

            $validation = $this->shopDetailValidator->validateSaveShopDetail($inputs);
            if ($validation->fails()) {
                return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
            }

            $shop_id =  $inputs['shop_id'];
            //$type =  $inputs['type']; // certificate / mention
            $mention = $inputs['mention'] ?? NULL;
            $id = $inputs['id'] ?? NULL;

            if(!empty($mention)){
                ShopDetail::updateOrCreate(['shop_id'=> $shop_id,'type' => ShopDetail::TYPE_MENTION],['description' => $mention]);            
            }

            $certificateFolder = config('constant.shops_certificate').'/'.$shop_id;
            if(isset($inputs['images']) && !empty($inputs['images'])){
                foreach($inputs['images'] as $imageItem) {
                    $image = $imageItem['image'];

                    $imageUpdateID = $imageItem['id'] ?? 0;

                    if($image){
                        $mainImage = Storage::disk('s3')->putFile($certificateFolder, $image,'public');
                        $fileName = basename($mainImage);
                        $image_url = $certificateFolder . '/' . $fileName;
                        
                        if(empty($imageUpdateID)){
                            ShopDetail::create([
                                'shop_id'=> $shop_id,
                                'type' => ShopDetail::TYPE_CERTIFICATE,
                                'attachment' => $image_url
                            ]);
                        }else{
                            $imageData = ShopDetail::whereId($imageUpdateID)->first();
                            if(!empty($imageData && $imageData->attachment)){
                                Storage::disk('s3')->delete($imageData->attachment);
                            }
                            $imageData->update([
                                'attachment' => $image_url,
                            ]);                            
                        }
                    }
                }
            }
            
            if(isset($inputs['deleted_image']) && !empty($inputs['deleted_image'])){
                foreach($inputs['deleted_image'] as $deleteImage) {
                    $image = DB::table('shop_details')->whereId($deleteImage)->first();
                    if($image) {
                        Storage::disk('s3')->delete($image->attachment);
                        ShopDetail::where('id',$image->id)->delete();
                    }
                }
            }
            DB::commit();
            $shopDetail = $this->getDetail($shop_id);
            return $this->sendSuccessResponse(Lang::get('messages.shop.save-detail'), 200,$shopDetail);

        } catch (\Exception $e) {
            Log::info($e);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function saveUsageInformation(Request $request){
        try {
            $inputs = $request->all();

            $validation = $this->shopDetailValidator->validateShopUsageDetail($inputs);
            if ($validation->fails()) {
                return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
            } 

            $data = [
                'shop_id' => $inputs['shop_id'],
                'description' => $inputs['title'],
                'type' => ShopDetail::TYPE_TOOLS_MATERIAL_INFO,
                'recycle_type' => $inputs['recycle_type'],
                'recycle_option_id' => $inputs['recycle_option'] ?? null,
            ];

            //$image_url = null;
            if(isset($inputs['id']) && !empty($inputs['id']) && isset($inputs['deleted_image']) && $inputs['deleted_image'] == true ){
                $shopOld = ShopDetail::whereId($inputs['id'])->first();
                if(!empty($shopOld->attachment)){
                    Storage::disk('s3')->delete($shopOld->attachment);
                }
                $shopOld->update(['attachment' => '']);
            }

            if($request->hasFile('image')){                

                $certificateFolder = config('constant.shops_certificate').'/'.$inputs['shop_id'];         
                if (!Storage::disk('s3')->exists($certificateFolder)) {
                    Storage::disk('s3')->makeDirectory($certificateFolder);
                } 
                $mainImage = Storage::disk('s3')->putFile($certificateFolder, $inputs['image'],'public');
                $fileName = basename($mainImage);
                $image_url = $certificateFolder . '/' . $fileName;
                $data['attachment'] = $image_url;
            }

            if(isset($inputs['id']) && !empty($inputs['id']) ){
                ShopDetail::whereId($inputs['id'])->update($data);
            }else{
                ShopDetail::create($data);
            }

            $shopDetail = $this->getDetail($inputs['shop_id']);
            return $this->sendSuccessResponse(Lang::get('messages.shop.save-detail'), 200,$shopDetail);

        } catch (\Exception $e) {
            Log::info($e);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function getShopDetail(Request $request,$shop_id){
        try {
            DB::beginTransaction();  
            
            $inputs = $request->all();
            $shopDetail = $this->getDetail($shop_id);
            return $this->sendSuccessResponse(Lang::get('messages.shop.save-detail'), 200,$shopDetail);

        } catch (\Exception $e) {
            print_r($e->getMessage());die;
            Log::info($e);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function deleteShopDetail($shop_info_id){
        try {
            DB::beginTransaction();  
 
            $getData = DB::table('shop_details')->whereId($shop_info_id)->first();
            $shop_id = $getData->shop_id;
            
            if($getData) {
                Storage::disk('s3')->delete($getData->attachment);
                ShopDetail::where('id',$getData->id)->delete();
            }
            DB::commit();

            $shopDetail = $this->getDetail($shop_id);  
            return $this->sendSuccessResponse(Lang::get('messages.shop.delete-tools-meterial-detail'), 200,$shopDetail);

        } catch (\Exception $e) {
            Log::info($e);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function getDetail($shop_id){
        $data = ShopDetail::where('shop_id',$shop_id)->get();
        $shopDetail = [];
        $mention = $certificate = $tools_material_info = NULL;

        if($data){
            foreach ($data as $key => $value) {

                $recycle_option_name = "0";
                $recycle_option_type = NULL;
                $getName = RecycleOption::where('id',$value->recycle_option_id)->first();
                if($getName){
                    $recycle_option_name = !empty($getName) ? (string)$getName->value : "0";
                    $recycle_option_type = !empty($getName) ? (string)$getName->type : NULL;
                }
                $value->recycle_option_name = $recycle_option_name;
                $value->recycle_option_type = $recycle_option_type;


                if(!empty($value) && $value->type == ShopDetail::TYPE_MENTION){
                    $mention = $value;
                }else if(!empty($value) && $value->type == ShopDetail::TYPE_CERTIFICATE){
                    $certificate[] = $value;
                }else{
                    $tools_material_info[] = $value;
                }  
            }
        }

        $shopInfo = Shop::whereId($shop_id)->select('id','main_name','shop_name','category_id','status_id')->first();
        $shopInfo = $shopInfo->makeHidden(['address','thumbnail_image','reviews_list','main_profile_images','portfolio_images']);

        $shopDetail = [
            'certificate' => $certificate,
            'mention' => $mention,
            'tools_material_info' => $tools_material_info,
            'shop_info' => $shopInfo
        ];
        
        return $shopDetail;
    }

    public function saveShopLanguageDetail(Request $request){
        $inputs = $request->all();

        try{

            $validation = $this->shopDetailValidator->validateShopLanguageDetail($inputs);
            if ($validation->fails()) {
                return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
            }

            $shop_id = $inputs['shop_id'];
            $details = $inputs['details'];

            foreach($details as $data){
                ShopDetailLanguage::updateOrCreate([
                    'shop_id' => $shop_id,
                    'key' => ShopDetailLanguage::SPECIALITY_OF,
                    'entity_type_id' => EntityTypes::SHOP,
                    'language_id' => $data['language_id'],
                ],[
                    'value' => $data['value']
                ]);
            }
            return $this->sendSuccessResponse(Lang::get('messages.shop.update-success'), 200);
        } catch (\Exception $e) {
            Log::info($e);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }
}
