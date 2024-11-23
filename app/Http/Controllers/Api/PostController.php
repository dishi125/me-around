<?php

namespace App\Http\Controllers\Api;

use Validator;
use Carbon\Carbon;
use App\Models\Post;
use App\Models\Notice;
use App\Models\Status;
use App\Models\Hospital;
use App\Models\PostImages;
use App\Models\EntityTypes;
use App\Models\PostLanguage;
use Illuminate\Http\Request;
use App\Validators\PostValidator;
use App\Models\ShopDetailLanguage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;


class PostController extends Controller
{
    private $postValidator;

    function __construct()
    {
        $this->postValidator = new PostValidator();
    }  
    
    public function postLanguages()
    {
        try {
            Log::info('Start code for get post languages');   
            $post_languages = PostLanguage::all();
            Log::info('End code for the get post languages');
            return $this->sendSuccessResponse(Lang::get('messages.hospital.get-language-success'), 200, compact('post_languages'));        
        } catch (\Exception $e) {
            Log::info('Exception in get post languages');
            Log::info($e);
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }
   
    public function addHospitalPost(Request $request)
    {
        $user = Auth::user();
        $inputs = $request->all();
        try {
            Log::info('Start code for add hospital post');   
            if($user){
                DB::beginTransaction();
                $hospitalExists = Hospital::find($inputs['hospital_id']);
                if($hospitalExists){
                    $validation = $this->postValidator->validateStore($inputs);
                    if ($validation->fails()) {
                        Log::info('End code for add hospital post');
                        return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
                    }
                    $fromDate = new Carbon($inputs['from_date']);
                    $dateDiff = $fromDate->diffInDays(Carbon::today());
                    $status = $dateDiff == 0 ? Status::ACTIVE : Status::FUTURE;
                    $requestData = [
                        'from_date' => $inputs['from_date'],
                        'to_date' => $inputs['to_date'],
                        'before_price' => $inputs['before_price'],
                        'final_price' => $inputs['final_price'],
                        'currency_id' => $inputs['currency_id'],
                        'discount_percentage' => $inputs['discount_percentage'],
                        'category_id' => $inputs['category_id'],
                        'title' => $inputs['title'],
                        'sub_title' => $inputs['sub_title'],
                        'is_discount' => $inputs['is_discount'],
                        'hospital_id' => $inputs['hospital_id'],
                        'status_id' => $status
                    ];                    
        
                    $post = Post::create($requestData);   

                    $hospitalPostFolder = config('constant.hospital-posts');                     
                
                    if (!Storage::exists($hospitalPostFolder)) {
                        Storage::makeDirectory($hospitalPostFolder);
                    }  
                    if(!empty($inputs['thumbnail'])){
                            $mainProfile = Storage::disk('s3')->putFile($hospitalPostFolder, $inputs['thumbnail'],'public');
                            $fileName = basename($mainProfile);
                            $image_url = $hospitalPostFolder . '/' . $fileName;
                            PostImages::create([
                                'post_id' => $post->id,
                                'type' => PostImages::THUMBNAIL,
                                'image' => $image_url
                            ]);
                    }     
                    
                    if(!empty($inputs['main_photos'])){
                        foreach($inputs['main_photos'] as $imageData) {
                            $mainImage = Storage::disk('s3')->putFile($hospitalPostFolder, $imageData['image'],'public');
                            $fileName = basename($mainImage);
                            $image_url = $hospitalPostFolder . '/' . $fileName;
                            $temp = [
                                'post_id' => $post->id,
                                'type' => PostImages::MAINPHOTO,
                                'image' => $image_url,
                                'post_language_id' => $imageData['language_id']
                            ];
                            $addNew = PostImages::create($temp);
                        }
                    }        
                   
                    if(isset($inputs['title_languages']) && !empty($inputs['title_languages'])){
                        foreach($inputs['title_languages'] as $data){
                            ShopDetailLanguage::updateOrCreate([
                                'shop_id' => $post->id,
                                'key' => ShopDetailLanguage::TITLE,
                                'entity_type_id' => EntityTypes::HOSPITAL,
                                'language_id' => $data['language_id'],
                            ],[
                                'value' => $data['value']
                            ]);
                        }
                    }

                    if(isset($inputs['subtitle_languages']) && !empty($inputs['subtitle_languages'])){
                        foreach($inputs['subtitle_languages'] as $data){
                            ShopDetailLanguage::updateOrCreate([
                                'shop_id' => $post->id,
                                'key' => ShopDetailLanguage::SUBTITLE,
                                'entity_type_id' => EntityTypes::HOSPITAL,
                                'language_id' => $data['language_id'],
                            ],[
                                'value' => $data['value']
                            ]);
                        }
                    }

                   DB::commit();
                   Log::info('End code for the add hospital post');
                   return $this->sendSuccessResponse(Lang::get('messages.hospital.post-add-success'), 200, $post);
                }else{
                    Log::info('End code for the add hospital post');
                    return $this->sendSuccessResponse(Lang::get('messages.hospital.empty'), 402);
                }
            }else{
                Log::info('End code for add hospital post');
                return $this->sendSuccessResponse(Lang::get('messages.user.token_expired'), 401);
            }   
        } catch (\Exception $e) {
            Log::info('Exception in add hospital post');
            Log::info($e);
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function hospitalPostDelete(Request $request,$id)
    {
        $user = Auth::user();
        try {
            Log::info('Start code for the delete hospital post detail');     
            if($user) {
                $hospitalPost = Post::where('id',$id)->first();
                if($hospitalPost){  
                    DB::beginTransaction();
                    $postImages = DB::table('post_images')->where('post_id',$id)->whereNull('deleted_at')->get();
                    foreach($postImages as $pi) {
                        if($pi->image){                           
                            Storage::disk('s3')->delete($pi->image);
                        }  
                    }
                    $postImages = PostImages::where('post_id',$id)->delete();
                    Post::where('id',$id)->delete();      
                    Notice::where('entity_id',$id)->where('entity_type_id',EntityTypes::HOSPITAL)->delete();
                    DB::commit();       
                    Log::info('End code for the delete hospital post detail');
                    return $this->sendSuccessResponse(Lang::get('messages.hospital.post-delete-success'), 200, []);
                }else{
                    return $this->sendSuccessResponse(Lang::get('messages.shop.post-empty'), 402);
                }
            }  else{
                Log::info('End code for the delete hospital post detail');
                return $this->sendSuccessResponse(Lang::get('messages.user.token_expired'), 401);
            }    

        } catch (\Exception $e) {
            Log::info('Exception in the delete hospital post detail');
            Log::info($e);
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function editPost($id)
    {       
        try {
            Log::info('Start code edit post detail');  
            $user = Auth::user();
            if($user) {   
                $postDetail = Post::where('id',$id)->first();
                Log::info('End code for the edit post detail');

                $postDetail->title_languages = $postDetail->shopLanguageDetails()->where('key',ShopDetailLanguage::TITLE)
                    ->where('entity_type_id',EntityTypes::HOSPITAL)->get();

                $postDetail->subtitle_languages = $postDetail->shopLanguageDetails()->where('key',ShopDetailLanguage::SUBTITLE)
                    ->where('entity_type_id',EntityTypes::HOSPITAL)->get();

                if($postDetail){ 
                    return $this->sendSuccessResponse(Lang::get('messages.post.success'), 200, $postDetail);                
                }else{
                    return $this->sendSuccessResponse(Lang::get('messages.post.empty'), 402);
                }
            }
            else{
                Log::info('End code for the get shop list');
                return $this->sendSuccessResponse(Lang::get('messages.user.token_expired'), 401);
            }
            
        } catch (\Exception $e) {
            Log::info('Exception in edit post detail');
            Log::info($e);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function updatePost(Request $request,$id)
    {
        $user = Auth::user();
        $inputs = $request->all();
        try {
            Log::info('Start code for update hospital post');   
            if($user){
                DB::beginTransaction();
                $post = Post::find($id);
                if($post){
                    $validation = $this->postValidator->validateUpdate($inputs);
                    if ($validation->fails()) {
                        Log::info('End code for update hospital post');
                        return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
                    }
                    $fromDate = new Carbon($inputs['from_date']);
                    $dateDiff = $fromDate->diffInDays(Carbon::today());
                    $status = $dateDiff == 0 || $fromDate->isPast() ? Status::ACTIVE : Status::FUTURE;
                    $requestData = [
                        'from_date' => $inputs['from_date'],
                        'to_date' => $inputs['to_date'],
                        'before_price' => $inputs['before_price'],
                        'final_price' => $inputs['final_price'],
                        'currency_id' => $inputs['currency_id'],
                        'discount_percentage' => $inputs['discount_percentage'],
                        'category_id' => $inputs['category_id'],
                        'title' => $inputs['title'],
                        'sub_title' => $inputs['sub_title'],
                        'is_discount' => $inputs['is_discount'],
                        'status_id' => $status
                    ];                    
        
                    $updatePost = Post::where('id',$id)->update($requestData);   

                    $hospitalPostFolder = config('constant.hospital-posts');  
                    if (!Storage::exists($hospitalPostFolder)) {
                        Storage::makeDirectory($hospitalPostFolder);
                    }  
                    if(!empty($inputs['thumbnail'])){
                            $mainProfile = Storage::disk('s3')->putFile($hospitalPostFolder, $inputs['thumbnail'],'public');
                            $fileName = basename($mainProfile);
                            $image_url = $hospitalPostFolder . '/' . $fileName;
                            PostImages::create([
                                'post_id' => $post->id,
                                'type' => PostImages::THUMBNAIL,
                                'image' => $image_url,
                            ]);
                    }     
                    
                    if(!empty($inputs['main_photos'])){
                        foreach($inputs['main_photos'] as $imageData) {
                            $mainImage = Storage::disk('s3')->putFile($hospitalPostFolder, $imageData['image'],'public');
                            $fileName = basename($mainImage);
                            $image_url = $hospitalPostFolder . '/' . $fileName;
                            $temp = [
                                'post_id' => $post->id,
                                'type' => PostImages::MAINPHOTO,
                                'image' => $image_url,
                                'post_language_id' => $imageData['language_id']
                            ];
                            $addNew = PostImages::create($temp);
                        }
                    }   
                    
                    if(!empty($inputs['deleted_image'])){
                        foreach($inputs['deleted_image'] as $deleteImage) {
                           $image = DB::table('post_images')->whereId($deleteImage)->whereNull()->first('deleted_at');
                           if($image) {
                               Storage::disk('s3')->delete($image->image);
                               PostImages::where('id',$image->id)->delete();
                           }
                        }
                    }

                    if(isset($inputs['title_languages']) && !empty($inputs['title_languages'])){
                        foreach($inputs['title_languages'] as $data){
                            ShopDetailLanguage::updateOrCreate([
                                'shop_id' => $id,
                                'key' => ShopDetailLanguage::TITLE,
                                'entity_type_id' => EntityTypes::HOSPITAL,
                                'language_id' => $data['language_id'],
                            ],[
                                'value' => $data['value']
                            ]);
                        }
                    }

                    if(isset($inputs['subtitle_languages']) && !empty($inputs['subtitle_languages'])){
                        foreach($inputs['subtitle_languages'] as $data){
                            ShopDetailLanguage::updateOrCreate([
                                'shop_id' => $id,
                                'key' => ShopDetailLanguage::SUBTITLE,
                                'entity_type_id' => EntityTypes::HOSPITAL,
                                'language_id' => $data['language_id'],
                            ],[
                                'value' => $data['value']
                            ]);
                        }
                    }
                   
                   DB::commit();
                   Log::info('End code for the update hospital post');
                   return $this->sendSuccessResponse(Lang::get('messages.hospital.post-add-success'), 200, $post);
                }else{
                    Log::info('End code for the update hospital post');
                    return $this->sendSuccessResponse(Lang::get('messages.post.empty'), 402);
                }
            }else{
                Log::info('End code for add hospital post');
                return $this->sendSuccessResponse(Lang::get('messages.user.token_expired'), 401);
            }   
        } catch (\Exception $e) {
            Log::info('Exception in add hospital post');
            Log::info($e);
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }
}
