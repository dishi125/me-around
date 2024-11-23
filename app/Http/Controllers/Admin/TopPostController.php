<?php

namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Log;
use Storage;
use Validator;
use App\Models\Country;
use App\Models\Category;
use App\Models\CategoryTypes;
use App\Models\Status;
use App\Models\Banner;
use App\Models\BannerImages;
use App\Models\EntityTypes;
use App\Models\Post;
use App\Models\Hospital;
use App\Models\SliderPosts;
use Carbon\Carbon;

class TopPostController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:top-post-list', ['only' => ['index','popupPost','hospitalPost']]);
    }
    /* ================= Top Post code start ======================== */

    public function index(){

        $title = "Top Post";
        $is_popup = 0;
        $countries = Country::WhereNotNull('code')->where('is_show',1)->orderBy('priority')->get();

        return view('admin.top-post-new.index', compact('title','is_popup','countries'));
    }

    public function filterCountryData(Request $request){
        $inputs = $request->all();
        $title = "Top Post";

        $country_id = isset($inputs['country_id']) ? $inputs['country_id'] : '';
        $countryData = Country::whereId($country_id)->first();

        if($country_id != 'Select Country'){
            $beautyCategory = Category::where('status_id', Status::ACTIVE)
                        ->where('type', 'default')
                        ->where('category_type_id', CategoryTypes::SHOP)
                        ->pluck('name','id');

            $communityCategory = Category::where('status_id', Status::ACTIVE)
                            ->where('parent_id', 0)
                            ->where('type', 'default')
                            ->where('category_type_id', CategoryTypes::COMMUNITY)
                            ->pluck('name','id');

            $homeSlides = Banner::where('section','home')->where('country_code',$countryData->code)->where('entity_type_id',NULL)->where('category_id',NULL)->get();
            $categorySlides = Banner::where('section','category')->where('country_code',$countryData->code)->where('entity_type_id',NULL)->where('category_id',NULL)->get();
            $shopHomeSlides = Banner::where('section','home')->where('country_code',$countryData->code)->where('entity_type_id',EntityTypes::SHOP)->where('category_id',NULL)->get();
            $shopSlides = Banner::where('section','home')->where('country_code',$countryData->code)->where('entity_type_id',EntityTypes::SHOP)->get();

            $communitySlides = Banner::where('section','home')->where('country_code',$countryData->code)->where('entity_type_id',EntityTypes::COMMUNITY)->get();
            $normalUserSlides = Banner::where('section','profile')->where('country_code',$countryData->code)->where('entity_type_id',EntityTypes::NORMALUSER)->get();
            $hospitalUserSlides = Banner::where('section','profile')->where('country_code',$countryData->code)->where('entity_type_id',EntityTypes::HOSPITAL)->get();
            $shopProfileSlides = Banner::where('section','profile')->where('country_code',$countryData->code)->where('entity_type_id',EntityTypes::SHOP)->get();



            return view('admin.top-post-new.country', compact('country_id', 'countryData', 'beautyCategory','communityCategory','homeSlides','categorySlides','shopHomeSlides','shopSlides','communitySlides','normalUserSlides','hospitalUserSlides','shopProfileSlides'));
        }else{
            return '';
        }

    }

    public function oldindex()
    {
        $title = "Top Post";
        $is_popup = 0;

        $beautyCategory = Category::where('status_id', Status::ACTIVE)
                        ->where('type', 'default')
                        ->where('category_type_id', CategoryTypes::SHOP)
                        ->pluck('name','id');

        $communityCategory = Category::where('status_id', Status::ACTIVE)
                            ->where('parent_id', 0)
                            ->where('type', 'default')
                            ->where('category_type_id', CategoryTypes::COMMUNITY)
                            ->pluck('name','id');

        /*  $hospitalCategory = Category::where('status_id', Status::ACTIVE)
                            ->where('parent_id','!=',0)
                            ->where('type', 'default')
                            ->where('category_type_id', CategoryTypes::HOSPITAL)
                            ->get();

       $hospitalParentCategory = Category::where('status_id', Status::ACTIVE)
                            ->where('parent_id','=',0)
                            ->where('type', 'default')
                            ->where('category_type_id', CategoryTypes::HOSPITAL)
                            ->get(); */

        $posts = Post::all();
        $countries = Country::WhereNotNull('code')->where('is_show',1)->orderBy('priority')->get();
        $hospitals = Hospital::where('status_id',Status::ACTIVE)->get();

        $popupSlides = Banner::where('section','popup')->where('entity_type_id',NULL)->where('category_id',NULL)->get();

        $homeSlides = Banner::where('section','home')->where('entity_type_id',NULL)->where('category_id',NULL)->get();
        $shopHomeSlides = Banner::where('section','home')->where('entity_type_id',EntityTypes::SHOP)->where('category_id',NULL)->get();
        $shopSlides = Banner::where('section','home')->where('entity_type_id',EntityTypes::SHOP)->get();
        $communitySlides = Banner::where('section','home')->where('entity_type_id',EntityTypes::COMMUNITY)->get();
        $normalUserSlides = Banner::where('section','profile')->where('entity_type_id',EntityTypes::NORMALUSER)->get();
        $hospitalUserSlides = Banner::where('section','profile')->where('entity_type_id',EntityTypes::HOSPITAL)->get();
        $shopProfileSlides = Banner::where('section','profile')->where('entity_type_id',EntityTypes::SHOP)->get();
        $categorySlides = Banner::where('section','category')->where('entity_type_id',NULL)->where('category_id',NULL)->get();
        return view('admin.top-post.index', compact('title','is_popup','countries','beautyCategory','communityCategory','homeSlides','shopHomeSlides','shopSlides','communitySlides','normalUserSlides','hospitalUserSlides','shopProfileSlides','categorySlides'));
    }

    public function popupPost()
    {
        $title = "Top Post";
        $is_popup = 1;
        $countries = Country::WhereNotNull('code')->where('is_show',1)->orderBy('priority')->get();

        $popupSlides = Banner::where('section','popup')->where('entity_type_id',NULL)->where('category_id',NULL)->get();

        return view('admin.top-post.popup-index', compact('title','countries','popupSlides','is_popup'));
    }
    public function hospitalPost()
    {
        $title = "Top Post";

        $hospitalCategory = Category::where('status_id', Status::ACTIVE)
                            ->where('parent_id','!=',0)
                            ->where('type', 'default')
                            ->where('category_type_id', CategoryTypes::HOSPITAL)
                            ->get();

        $hospitalParentCategory = Category::where('status_id', Status::ACTIVE)
                            ->where('parent_id','=',0)
                            ->where('type', 'default')
                            ->where('category_type_id', CategoryTypes::HOSPITAL)
                            ->get();
        $allHomeHospitalPost = Post::join('slider_posts','slider_posts.post_id','posts.id')
                            ->where('slider_posts.section',SliderPosts::HOME)
                            ->where('slider_posts.entity_type_id',EntityTypes::HOSPITAL)
                            ->where('slider_posts.deleted_at',NULL)
                            ->where('slider_posts.category_id',NULL)->get(['posts.*']);

        $allTopHospitalPost = Post::join('slider_posts','slider_posts.post_id','posts.id')
                                ->where('slider_posts.section',SliderPosts::TOP)
                                ->where('slider_posts.entity_type_id',EntityTypes::HOSPITAL)
                                ->where('slider_posts.deleted_at',NULL)
                                ->where('slider_posts.category_id',NULL)->get(['posts.*']);

        $categoryHospitalPost = Post::join('slider_posts','slider_posts.post_id','posts.id')
                                ->where('slider_posts.entity_type_id',EntityTypes::HOSPITAL)
                                ->where('slider_posts.deleted_at',NULL)
                                ->where('slider_posts.category_id','!=',NULL)->get(['posts.*','slider_posts.category_id as sliderCategoryId','slider_posts.section as sliderSection']);

        $posts = Post::all();
        $hospitals = Hospital::where('status_id',Status::ACTIVE)->get();
        return view('admin.top-post.index-hospital', compact('title','hospitalParentCategory','hospitalCategory','posts','hospitals','allHomeHospitalPost','allTopHospitalPost','categoryHospitalPost'));
    }

    public function store(Request $request)
    {
        try {
            DB::beginTransaction();
            Log::info('Start code for the add announcement');

            DB::commit();
            Log::info('End the code for the add announcement');
            notify()->success("Announcement send successfully", "Success", "topRight");
            return redirect()->route('admin.announcement.index');
        } catch (\Exception $e) {
            Log::info('Exception in the add announcement');
            Log::info($e);
            notify()->error("Failed to send announcement", "Error", "topRight");
            return redirect()->route('admin.announcement.index');
        }
    }

    public function addPost(Request $request) {
        // $userCredits = UserCredit::where('user_id',$id)->first();
        $inputs = $request->all();

        if($inputs['cat-id'] && $inputs['cat-id'] != 0) {
            $bannerData['category_id'] = $inputs['cat-id'];
        }else {
            $bannerData['category_id'] = NULL;
        }

        if($inputs['entity-id'] && $inputs['entity-id'] != 0) {
            $bannerData['entity_type_id'] = $inputs['entity-id'];
        }
        else {
            $bannerData['entity_type_id'] = NULL;
        }

        if($inputs['section']) {
            $bannerData['section'] = $inputs['section'];
        }
        if($inputs['country-id']) {
            $country = Country::find($inputs['country-id']);
            $bannerData['country_code'] = $country->code;
        }

        $is_popup = $inputs['section'] && $inputs['section'] == 'popup' ? 1 :0;
        $date = Carbon::now();
        $today = $date->toDateString();
        $banner = Banner::firstOrCreate($bannerData);
        return view('admin.top-post.add-post',compact('banner','is_popup','today'));
    }

    public function storePost(Request $request) {
        try {
            DB::beginTransaction();
            Log::info('Top Post add code start.');
            $inputs = $request->all();

            $bannerData = [];
            $data = [
                'banner_id' => $inputs['banner_id'],
                'link' => $inputs['link'],
                // 'slide_duration' => $inputs['slide_duration'],
                // 'order' => $inputs['order'],
            ];
            if ($request->has('slide_duration')) {
                $data['slide_duration'] = $inputs['slide_duration'];
            }
            if ($request->has('order')) {
                $data['order'] = $inputs['order'];
            }
            if ($request->has('from_date')) {
                $data['from_date'] = $inputs['from_date'];
            }
            if ($request->has('to_date')) {
                $data['to_date'] = $inputs['to_date'];
            }
            if ($request->hasFile('file')) {
                $topPostFolder = config('constant.top-post');
                if (!Storage::exists($topPostFolder)) {
                    Storage::makeDirectory($topPostFolder);
                }
                $file = Storage::disk('s3')->putFile($topPostFolder, $request->file('file'),'public');
                $fileName = basename($file);
                $data['image'] = $topPostFolder . '/' . $fileName;
            }

            BannerImages::create($data);

            DB::commit();
            Log::info('Top Post add code end.');
            return $this->sendSuccessResponse('Top Post add successfully.', 200);
        } catch (\Exception $ex) {
            DB::rollBack();
            Log::info('Top Post add code exception.');
            Log::info($ex);
            return $this->sendFailedResponse('Failed to add top post.', 400);
        }
    }

    public function editPost($id) {
        $bannerImage = BannerImages::find($id);
        $banner = Banner::find($bannerImage->banner_id);
        $is_popup = $banner && $banner->section == 'popup' ? 1 :0;
        $date = Carbon::now();
        $today = $date->toDateString();
        return view('admin.top-post.edit-post',compact('bannerImage','is_popup','today'));
    }

    public function updatePost(Request $request) {
        try {
            DB::beginTransaction();
            Log::info('Top Post add code start.');
            $inputs = $request->all();
            $bannerData = [];
            $data = [
                'link' => $inputs['link'],
            ];
            $banner = BannerImages::find($inputs['banner_image_id']);

            if ($request->hasFile('file')) {
                $topPostFolder = config('constant.top-post');
                if (!Storage::exists($topPostFolder)) {
                    Storage::makeDirectory($topPostFolder);
                }
                Storage::disk('s3')->delete($banner->image);
                $file = Storage::disk('s3')->putFile($topPostFolder, $request->file('file'),'public');
                $fileName = basename($file);
                $data['image'] = $topPostFolder . '/' . $fileName;
            }

            if ($request->has('slide_duration')) {
                $data['slide_duration'] = $inputs['slide_duration'];
            }
            if ($request->has('order')) {
                $data['order'] = $inputs['order'];
            }

            if ($request->has('from_date')) {
                $data['from_date'] = $inputs['from_date'];
            }
            if ($request->has('to_date')) {
                $data['to_date'] = $inputs['to_date'];
            }

            BannerImages::where('id',$inputs['banner_image_id'])->update($data);

            DB::commit();
            Log::info('Top Post add code end.');
            return $this->sendSuccessResponse('Top Post add successfully.', 200);
        } catch (\Exception $ex) {
            DB::rollBack();
            Log::info('Top Post add code exception.');
            Log::info($ex);
            return $this->sendFailedResponse('Failed to add top post.', 400);
        }
    }

    public function updateCheckbox(Request $request) {
        try {
            DB::beginTransaction();
            Log::info('Top Post checkbox update code start.');
            $inputs = $request->all();
            $data = [];
            $where = [];
            $category_id = $inputs['cat_id'] && $inputs['cat_id'] != 0 ? $inputs['cat_id'] : NULL;
            $section = $inputs['section']  ? $inputs['section'] : NULL;
            $country_code = $inputs['country_code']  ? $inputs['country_code'] : NULL;
            $entity_type_id = $inputs['entity_id'] && $inputs['entity_id'] != 0 ? $inputs['entity_id'] : NULL;
            $is_random = $inputs['is_random'] == "true" ? 1 : 0;
            $data = $where = [
                'category_id' => $category_id,
                'section' => $section,
                'entity_type_id' => $entity_type_id,
                'country_code' => $country_code,
            ];

            $data['is_random'] = $is_random;

            $banner = Banner::updateOrCreate(
                $where,
                $data
            );

            DB::commit();

            Log::info('Top Post checkbox update  code end.');
            return $this->sendSuccessResponse('Top Post checkbox update successfully.', 200);
        } catch (\Exception $ex) {
            DB::rollBack();
            Log::info('Top Post checkbox update code exception.');
            Log::info($ex);
            return $this->sendFailedResponse('Failed to checkbox update top post.', 400);
        }
    }

    public function delete($id)
    {
        return view('admin.top-post.delete', compact('id'));
    }

    public function destroy($id)
    {
        try {
            Log::info('Banner image delete code start.');
            DB::beginTransaction();
            $banner = BannerImages::find($id);
            $bannerData = Banner::find($banner->banner_id);
            $country = Country::where('code',$bannerData->country_code)->first();
            if($banner->image){
                Storage::disk('s3')->delete($banner->image);
            }
            BannerImages::where('id',$id)->delete();
            DB::commit();
            Log::info('Banner image delete code end.');
            notify()->success("Post image deleted successfully", "Success", "topRight");
            $append = "countryId=".$country->id;
            return redirect()->route('admin.top-post.index',[$append]);
        } catch (\Exception $ex) {
            DB::rollBack();
            Log::info('Banner image delete exception.');
            Log::info($ex);
            notify()->error("Failed to deleted post image", "Error", "topRight");
            return redirect()->route('admin.top-post.index');
        }
    }

    public function updateHospitalPost(Request $request) {
        try {
            DB::beginTransaction();
            Log::info('Top Post for hospital add code start.');
            $inputs = $request->all();
            $posts = $inputs['postArray'] ? explode(',',$inputs['postArray']) : [];
            $category_id = $inputs['category'] != 0 ? $inputs['category'] : NULL;
            $oldPosts =SliderPosts::where('section',$inputs['section'])
                          ->where('entity_type_id',EntityTypes::HOSPITAL)
                          ->where('category_id',$category_id)->forcedelete();
            foreach($posts as $post){
                $postData = [
                    'section' => $inputs['section'],
                    'entity_type_id' => EntityTypes::HOSPITAL,
                    'category_id' => $category_id,
                    'post_id' => $post
                ];
                SliderPosts::updateOrCreate($postData,$postData);
            }

            DB::commit();
            Log::info('Top Post for hospital add code end.');
            return $this->sendSuccessResponse('Top Post add successfully.', 200);
        } catch (\Exception $ex) {
            DB::rollBack();
            Log::info('Top Post for hospital add code exception.');
            Log::info($ex);
            return $this->sendFailedResponse('Failed to add top post for hospital.', 400);
        }
    }

    function getHospitalEvents(Request $request)
    {
        if($request->ajax())
        {
            $output = '';
            $search = $request->get('search');
            $hospital_id = $request->get('hospital_id');
            if($search == '' && $hospital_id == 0) {
                $data = Post::all();
            }else{
                $postQuery = Post::orderBy('id');
                if($search != '')      {
                    $postQuery = $postQuery->where('title', 'like', '%'.$search.'%');
                }
                if($hospital_id != 0) {
                    $postQuery = $postQuery->where('hospital_id', $hospital_id);
                }
                $data = $postQuery->get();
            }

            $total_row = $data->count();
            if($total_row > 0)
            {
            foreach($data as $post)
            {
                $output .= '
                    <li class="treatment-list ui-draggable ui-draggable-handle" id="'.$post->id.'">
                        <div class="treatment-img">
                        <img src="'.$post->thumbnail_url.'" class="img-fluid" alt="treatment-img">
                        </div>
                        <div class="treatment-text">
                        <p>'.$post->location->city_name.'.'. $post->hospital_name.'</p>
                        <h4>'.$post->title.'</h4>
                        <p>'.$post->sub_title.'</p>
                        <p class="star-rating"><i class="fa fa-star"></i><i class="fa fa-star"></i><i class="fa fa-star"></i><i class="fa fa-star"></i><i class="fa fa-star-half-alt"></i></p>
                        <h4>
                            <span class="percentage">'.$post->discount_percentage.'%</span>
                            <span class="price">'.$post->before_price.'USD</span>
                            <span class="original-price">'.$post->final_price.'USD</span>
                        </h4>
                        </div>
                    </li>
                ';
            }
        }
        else
        {
        $output = '
        <div class="treatment-list">No Events Found</div>';
        }
        $data = array(
        'events_data'  => $output,
        );

        echo json_encode($data);
        }
    }

    /* ================= Top post code end ======================== */

}
