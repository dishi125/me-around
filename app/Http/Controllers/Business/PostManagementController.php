<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Hospital;
use App\Models\Post;
use App\Models\UserEntityRelation;
use Illuminate\Support\Facades\Auth;
use App\Models\EntityTypes;
use App\Models\PostLanguage;
use App\Models\Currency;
use App\Models\Status;
use App\Models\Category;
use App\Models\PostImages;
use App\Models\CategoryTypes;
use App\Models\Notice;
use Log;
use Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class PostManagementController extends Controller
{
    public function index()
    {
        $title = 'Posts';
        return view('business.posts.index', compact('title'));
    }

    public function create(Request $request){
        $title = 'Create Post';
        $page = $request->get('page') ?? '';

        $postLanguages = PostLanguage::all();
        $postLanguages= collect($postLanguages)->mapWithKeys(function ($value) {
            return [$value->id => $value->name];
        })->toArray();

        $allCurrency = Currency::all();
        $allCurrency= collect($allCurrency)->mapWithKeys(function ($value) {
            return [$value->id => $value->name];
        })->toArray();

        $categorySelect = [];

        $category = Category::where('status_id',Status::ACTIVE)->where('category_type_id', CategoryTypes::HOSPITAL)->where('parent_id', 0)->orderBy('order')->get();
        $categorySelect = $category->mapWithKeys(function ($item) {
            $childCategory = Category::select('id','name')->where('status_id',Status::ACTIVE)->where('parent_id', $item->id)->orderBy('order')->get();
            $childCategory= collect($childCategory)->mapWithKeys(function ($value) {
                return [$value->id => $value->name];
            })->toArray();

            return [$item->name => $childCategory];
        });

        $selectedLanguage = [];
        return view('business.posts.edit', compact('title','postLanguages', 'allCurrency', 'categorySelect','selectedLanguage', 'page'));
    }


    public function store(Request $request){
        $inputs = $request->all();
        $minLength = isset($inputs['post_languages']) ? count($inputs['post_languages']) : 0;
        $validator = Validator::make($request->all(), [
            'period' => 'required',
            'before_price' => 'required',
            'final_price' => 'required',
            'currency_id' => 'required',
            'category_id' => 'required',
            'title' => 'required',
            'sub_title' => 'required',
            'post_languages' => 'required',
            'thumbnail_image' => 'required',
            "main_language_image"    => "required|array|min:$minLength",
        ],[
            'main_language_image.min' => 'The main language image field is required.'
        ]);
        
        
        if ($validator->fails()) {
            return response()->json(["errors" => $validator->messages()->toArray()], 422);
        }
        $user = Auth::user();
        $hospitals = UserEntityRelation::where('user_id',$user->id)->where('entity_type_id',EntityTypes::HOSPITAL)->first();
        
        $page = $inputs['page'] ?? ''; 
       
        if($page == 'dashboard'){
            $redirectURL = route('business.dashboard.index');
        }else{
            $redirectURL = route('business.posts.index');
        }

        try {
            if(!empty($user) && !empty($hospitals)){
                $period = $inputs['period']; 
                $dateRange = array_map('trim', explode('to',$period));
                $fromDate = new Carbon($dateRange[0]);
                $dateDiff = $fromDate->diffInDays(Carbon::today());
                $status = $dateDiff == 0 || $fromDate->isPast() ? Status::ACTIVE : Status::FUTURE;
                $requestData = [
                    'from_date' => $dateRange[0] ?? null,
                    'to_date' => $dateRange[1] ?? null,
                    'before_price' => $inputs['before_price'],
                    'final_price' => $inputs['final_price'],
                    'currency_id' => $inputs['currency_id'],
                    'discount_percentage' => $inputs['discount_percentage'],
                    'category_id' => $inputs['category_id'],
                    'title' => $inputs['title'],
                    'sub_title' => $inputs['sub_title'],
                    'is_discount' => $inputs['is_discount'],
                    'hospital_id' => $hospitals->entity_id,
                    'status_id' => $status
                ]; 

                $post = Post::create($requestData);   

                $hospitalPostFolder = config('constant.hospital-posts');                     
            
                if (!Storage::disk('s3')->exists($hospitalPostFolder)) {
                    Storage::disk('s3')->makeDirectory($hospitalPostFolder);
                }  
                if(!empty($inputs['thumbnail_image'])){
                        $mainProfile = Storage::disk('s3')->putFile($hospitalPostFolder, $inputs['thumbnail_image'],'public');
                        $fileName = basename($mainProfile);
                        $image_url = $hospitalPostFolder . '/' . $fileName;
                        PostImages::create([
                            'post_id' => $post->id,
                            'type' => PostImages::THUMBNAIL,
                            'image' => $image_url
                        ]);
                }  

                if(isset($inputs['main_language_image']) && !empty($inputs['main_language_image'])){
                    foreach($inputs['main_language_image'] as $languageID => $imageDetails) {
                        if(!empty($imageDetails)){
                            foreach($imageDetails as $imageData) {
                                $mainImage = Storage::disk('s3')->putFile($hospitalPostFolder, $imageData,'public');
                                $fileName = basename($mainImage);
                                $image_url = $hospitalPostFolder . '/' . $fileName;
                                $temp = [
                                    'post_id' => $post->id,
                                    'type' => PostImages::MAINPHOTO,
                                    'image' => $image_url,
                                    'post_language_id' => $languageID
                                ];
                                $addNew = PostImages::create($temp);
                            }
                        }
                    }
                }
            }

            return response()->json(["success" => true, "message" => "Post". trans("messages.insert-success"), "redirect" => $redirectURL], 200);
        } catch (\Exception $e) {
            Log::info($e);
            DB::rollBack();
            return response()->json(["success" => false, "message" => "Post". trans("messages.insert-error"), "redirect" => $redirectURL], 200);
        }

    }

    public function edit(Post $post,Request $request){
        $title = 'Edit Post';
        $postLanguages = PostLanguage::all();
        $postLanguages= collect($postLanguages)->mapWithKeys(function ($value) {
            return [$value->id => $value->name];
        })->toArray();

        $page = $request->get('page') ?? ''; 
        
        $allCurrency = Currency::all();
        $allCurrency= collect($allCurrency)->mapWithKeys(function ($value) {
            return [$value->id => $value->name];
        })->toArray();

        $categorySelect = [];

        $category = Category::where('status_id',Status::ACTIVE)->where('category_type_id', CategoryTypes::HOSPITAL)->where('parent_id', 0)->orderBy('order')->get();
        $categorySelect = $category->mapWithKeys(function ($item) {
            $childCategory = Category::select('id','name')->where('status_id',Status::ACTIVE)->where('parent_id', $item->id)->orderBy('order')->get();
            $childCategory= collect($childCategory)->mapWithKeys(function ($value) {
                return [$value->id => $value->name];
            })->toArray();

            return [$item->name => $childCategory];
        });

        $selectedLanguage = $imagesArray = [];
        if(!empty($post->main_images)){
            foreach($post->main_images as $images){
                $selectedLanguage[] = $images['language_id'];
                foreach($images['photos'] as $photo){
                    $imagesArray[$images['language_id']][] = json_encode(["image" => $photo->image, "uploaded" => true, 'id'=>$photo->id]);
                }                
            }
        }
        
        
        return view('business.posts.edit', compact('title','post', 'imagesArray', 'postLanguages', 'allCurrency', 'categorySelect','selectedLanguage', 'page'));
    }

    public function update(Request $request,$id)
    {
        $inputs = $request->all();
        $minLength = isset($inputs['post_languages']) ? count($inputs['post_languages']) : 0;
        $validator = Validator::make($request->all(), [
            'period' => 'required',
            'before_price' => 'required',
            'final_price' => 'required',
            'currency_id' => 'required',
            'category_id' => 'required',
            'title' => 'required',
            'sub_title' => 'required',
            'post_languages' => 'required',
            'thumbnail_image' => 'required_without:has_thumb',
            "main_language_image"    => "required|array|min:$minLength",
        ],[
            'thumbnail_image.required_without' => 'The thumbnail image field is required',
            'main_language_image.min' => 'The main language image field is required.'
        ]);
        
        if ($validator->fails()) {
            return response()->json(["errors" => $validator->messages()->toArray()], 422);
        }
        
        $user = Auth::user();
        $inputs = $request->all();
        $page = $inputs['page'] ?? ''; 

        if($page == 'dashboard'){
            $redirectURL = route('business.dashboard.index');
        }else{
            $redirectURL = route('business.posts.index');
        }
        
        try {
            Log::info('Start code for update hospital post');   
            if($user){
                DB::beginTransaction();
                $post = Post::find($id);
                if($post){
                    $period = $inputs['period']; 
                    $dateRange = array_map('trim', explode('to',$period));
                    $fromDate = new Carbon($dateRange[0]);
                    $dateDiff = $fromDate->diffInDays(Carbon::today());
                    $status = $dateDiff == 0 || $fromDate->isPast() ? Status::ACTIVE : Status::FUTURE;
                    $requestData = [
                        'from_date' => $fromDate,
                        'to_date' => $dateRange[1],
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
                    if (!Storage::disk('s3')->exists($hospitalPostFolder)) {
                        Storage::disk('s3')->makeDirectory($hospitalPostFolder);
                    }  
                    if(!empty($inputs['thumbnail_image'])){
                        if(!empty($post->thumbnail_url) && !empty($post->thumbnail_url->image_path)){
                            Storage::disk('s3')->delete($post->thumbnail_url->image_path);
                            PostImages::where('id',$post->thumbnail_url->id)->delete();
                        }
                        
                            $mainProfile = Storage::disk('s3')->putFile($hospitalPostFolder, $inputs['thumbnail_image'],'public');
                            $fileName = basename($mainProfile);
                            $image_url = $hospitalPostFolder . '/' . $fileName;
                            PostImages::create([
                                'post_id' => $post->id,
                                'type' => PostImages::THUMBNAIL,
                                'image' => $image_url,
                            ]);
                    }     
                    
                    if(isset($inputs['main_language_image']) && !empty($inputs['main_language_image'])){
                        foreach($inputs['main_language_image'] as $languageID => $imageDetails) {
                            if(!empty($imageDetails)){
                                foreach($imageDetails as $imageData) {
                                    $fileUpload = json_decode($imageData);
                                    if(is_file($imageData)){
                                        $mainImage = Storage::disk('s3')->putFile($hospitalPostFolder, $imageData,'public');
                                        $fileName = basename($mainImage);
                                        $image_url = $hospitalPostFolder . '/' . $fileName;
                                        $temp = [
                                            'post_id' => $post->id,
                                            'type' => PostImages::MAINPHOTO,
                                            'image' => $image_url,
                                            'post_language_id' => $languageID
                                        ];
                                        $addNew = PostImages::create($temp);
                                    }
                                }
                                
                            }
                        }
                    }   

                    
                    /* if(!empty($inputs['deleted_image'])){
                        foreach($inputs['deleted_image'] as $deleteImage) {
                           $image = DB::table('post_images')->whereId($deleteImage)->whereNull()->first('deleted_at');
                           if($image) {
                               Storage::disk('s3')->delete($image->image);
                               PostImages::where('id',$image->id)->delete();
                           }
                        }
                    } */
                   
                   DB::commit();

                   return response()->json(["success" => true, "message" => "Post". trans("messages.update-success"), "redirect" => $redirectURL], 200);

                }else{                    
                    return response()->json(["success" => false, "message" => "Post". trans("messages.update-error"), "redirect" => $redirectURL], 200);
                }
            }else{
                return response()->json(["success" => false, "message" => "Post". trans("messages.update-error"), "redirect" => $redirectURL], 200);
            }   
        } catch (\Exception $e) {
            Log::info('Exception in add hospital post');
            Log::info($e);
            DB::rollBack();
            return response()->json(["success" => false, "message" => "Post". trans("messages.update-error"), "redirect" => $redirectURL], 200);
        }
    }

    public function getJsonAllData(Request $request){
        $user = Auth::user();

        $hospitals = UserEntityRelation::where('user_id',$user->id)->where('entity_type_id',EntityTypes::HOSPITAL)->first();

        $columns = array(
            0 => 'title',
            1 => 'sub_title',
            2 => 'from_date',
            3 => 'views_count',
            4 => 'action',
        );

        $filter = $request->input('filter');
        $limit = $request->input('length');
        $start = $request->input('start');
        $search = $request->input('search.value');
        $draw = $request->input('draw');
        $order = $columns[$request->input('order.0.column')];
        $dir = $request->input('order.0.dir');
        
        $adminTimezone = $this->getAdminUserTimezone();
        try {
            $data = [];

            if(!empty($hospitals)){
                //->where('status_id',Status::ACTIVE)
                $postQuery = Post::where('hospital_id',$hospitals->entity_id);

                if (!empty($search)) {
                    $postQuery = $postQuery->where(function($q) use ($search){
                        $q->where('title', 'LIKE', "%{$search}%")
                        ->orWhere('sub_title', 'LIKE', "%{$search}%");
                    });                    
                }

                if($filter != 'all'){
                    if($filter == 'active'){
                        $filterWhere = [Status::ACTIVE];
                    }elseif($filter == 'future'){
                        $filterWhere = [Status::FUTURE];
                    }elseif($filter == 'inactive'){
                        $filterWhere = [Status::PENDING, Status::INACTIVE, Status::EXPIRE];
                    }
                    if($filterWhere){
                        $postQuery = $postQuery->whereIn('status_id', $filterWhere);
                    }
                }

                $totalData = count($postQuery->get());
                $totalFiltered = $totalData;
            
                $activePosts = $postQuery->offset($start)
                        ->limit($limit)
                        ->orderBy($order, $dir)  
                        ->get();

                $count = 0;
                foreach($activePosts as $post){
    
                    $edit = route('business.posts.edit', [$post->id]);
                    $deleteURL = route('business.get.post.delete',['id'=> $post->id]);
                    $deleteButton = "<a role='button' href='javascript:void(0)' onclick='deletePost(`" . $deleteURL . "`)' title='' data-original-title='Delete Account' class='mx-1 btn btn-primary btn-sm' data-toggle='tooltip'>Delete Post</a>";
                    $editButton = "<a role='button' href='$edit'  title='' data-original-title='Delete Account' class='mx-1 btn btn-primary btn-sm' data-toggle='tooltip'>Edit Post</a>";

                    $data[$count]['title'] = $post->title;
                    $data[$count]['sub_title'] = $post->sub_title;
                    $data[$count]['post_date'] = $post->from_date.' - '.$post->to_date;
                    $data[$count]['views_count'] = $post->views_count;
                    $data[$count]['actions'] = "<div class='d-flex'>$deleteButton $editButton</div>";
                    $count++;
                }
    
                $jsonData = array(
                    "draw" => intval($draw),
                    "recordsTotal" => intval($totalData),
                    "recordsFiltered" => intval($totalFiltered),
                    "data" => $data
                );
                
            }else{
                
                $jsonData = array(
                    "draw" => intval($draw),
                    "recordsTotal" => 0,
                    "recordsFiltered" => 0,
                    "data" => []
                );
            }
            
            return response()->json($jsonData);
        } catch (\Exception $ex) {
            Log::info('Exception all hospital list');
            Log::info($ex);
            $jsonData = array(
                "draw" => intval($draw),
                "recordsTotal" => 0,
                "recordsFiltered" => 0,
                "data" => []
            );
            return response()->json($jsonData);
        }
    }

    public function getNewImageHtml(Request $request){
        $inputs = $request->all();

        $id = $inputs['id'];
        $postLanguages = PostLanguage::find($id);
        $text = $postLanguages->name;
        return view('business.posts.language-image', compact('id','text'));
    }

    public function removePostImage(Request $request){
        $inputs = $request->all();

        $postid = $inputs['postid'] ?? '';
        $imageid = $inputs['imageid'] ?? '';

        if(!empty($imageid) && !empty($postid)){
            $image = PostImages::whereId($imageid)->first();
            if($image){
                Storage::disk('s3')->delete($image->image_path);
                PostImages::where('id',$image->id)->delete();
            }
        }
    }

    public function getDeletePost($id)
    {
        return view('business.posts.delete-post', compact('id'));
    }

    public function DeletePost(Request $request){
        $inputs = $request->all();
        $postid = $inputs['postid'];

        $hospitalPost = Post::where('id',$postid)->first();
        try {
            if($hospitalPost){  
                DB::beginTransaction();
                $postImages = PostImages::where('post_id',$postid)->whereNull('deleted_at')->get();
                foreach($postImages as $pi) {
                    if($pi->image_path){                           
                        Storage::disk('s3')->delete($pi->image_path);
                    }  
                }
                $postImages = PostImages::where('post_id',$postid)->delete(); 
                Notice::where('entity_id',$postid)->where('entity_type_id',EntityTypes::HOSPITAL)->delete();
                Post::where('id',$postid)->delete();     
                DB::commit();    

                return $this->sendSuccessResponse('Post deleted successfully.', 200);
            }else{
                return $this->sendSuccessResponse('Failed to delete post.', 201);
            }
        } catch (\Exception $ex) {
            DB::rollBack();
            Log::info($ex);
            return $this->sendFailedResponse('Failed to delete post.', 201);
        }
    }

    public function editHospital(Request $request)
    {
        $title = 'Hospital Detail';
        $user = Auth::user();
        $hospitals = UserEntityRelation::where('user_id',$user->id)->where('entity_type_id',EntityTypes::HOSPITAL)->first();
        $hospital = Hospital::findOrFail($hospitals->entity_id ?? '');
        
        return view('business.posts.show', compact('title','hospital'));
    }
}
