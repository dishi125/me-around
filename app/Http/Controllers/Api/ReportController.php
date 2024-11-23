<?php

namespace App\Http\Controllers\Api;

use App\Models\Status;
use App\Models\Config;
use App\Models\EntityTypes;
use App\Models\CategoryTypes;
use App\Models\Category;
use App\Models\ReportClient;
use App\Models\ReportTypes;
use App\Models\UserEntityRelation;
use App\Models\ShopImages;
use App\Models\UserDetail;
use App\Models\Community;
use App\Models\Notice;
use App\Models\Reviews;
use App\Models\ReviewComments;
use App\Models\ReviewCommentReply;
use App\Models\CommunityComments;
use App\Models\CommunityCommentReply;
use App\Models\CategoryLanguage;
use App\Models\UserDevices;
use App\Models\RequestedCustomer;
use App\Models\AssociationCommunity;
use App\Models\AssociationCommunityComment;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use App\Validators\ReportValidator;
use App\Validators\CategoryValidator;
use Validator;
use Carbon\Carbon;
use App\Util\Firebase;
use App\Mail\CommonMail;


class ReportController extends Controller
{
    private $reportValidator;
    private $categoryValidator;
    protected $firebase;

    function __construct()
    {
        $this->reportValidator = new ReportValidator();
        $this->categoryValidator = new CategoryValidator();
        $this->firebase = new Firebase();
    }   

    public function getReportCategory(Request $request)
    {       
        try {
            Log::info('Start code get report category');  
            $inputs = $request->all();
            $validation = $this->categoryValidator->validateList($inputs);
            if ($validation->fails()) {
                Log::info('End code for the get category');
                return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
            }
            $category_id = isset($inputs['category_id']) ? $inputs['category_id'] : 0;
            $returnData = [];
            $reportQuery = Category::where('status_id',Status::ACTIVE)->where('category_type_id',CategoryTypes::REPORT);
            if($category_id != 0){
                $reportQuery = $reportQuery->where('parent_id',$category_id);
            }else {
                $reportQuery = $reportQuery->where('parent_id',0);
            }

            $report_category = $reportQuery->get();
            $report_category = $report_category->makeHidden(['type', 'parent_id','sub_categories']);
            $report_category->map(function ($item) use($inputs) {
                    $category_language = CategoryLanguage::where('category_id',$item->id)->where('post_language_id',$inputs['language_id'])->first();
                    $item['category_language_name'] = $category_language && $category_language->name != NULL ? $category_language->name : $item->name;
                    $items = Category::where('status_id',Status::ACTIVE)->where('parent_id', $item->id)->orderBy('order')->get();
                    $items = $items->makeHidden(['type', 'parent_id']);
                    foreach($items as $i) {
                        $category_language = CategoryLanguage::where('category_id',$i->id)->where('post_language_id',$inputs['language_id'])->first();
                        $i['category_language_name'] = $category_language && $category_language->name != NULL ? $category_language->name : $i->name;
                    }
                    $item['children'] = $items;
                
                return $item;
            });



                // foreach($report_category as $cat) {
                //     $category_language = CategoryLanguage::where('category_id',$cat->id)->where('post_language_id',$inputs['language_id'])->first();
                //     $cat['category_language_name'] = $category_language && $category_language->name != NULL ? $category_language->name : $cat->name;

                //     $cat->sub_categories->map(function ($item) use($inputs) {
                //         $category_language = CategoryLanguage::where('category_id',$item->id)->where('post_language_id',$inputs['language_id'])->first();
                //         $item['category_language_name'] = $category_language && $category_language->name != NULL ? $category_language->name : $item->name;
                //        dd($item);
                //         return $item;
                //     });
                    
                //     // dd($cat);
                // }
           
            Log::info('End code get report category');
            return $this->sendSuccessResponse(Lang::get('messages.report.category-success'), 200, compact('report_category'));
        } catch (\Exception $e) {
            Log::info('Exception in get report category');
            Log::info($e);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }
    
    public function addReportClient(Request $request)
    {       
        try {
            Log::info('Start code add report');  
            $authUser = Auth::user();
            $inputs = $request->all();
            if($authUser){
                DB::beginTransaction();
                $validation = $this->reportValidator->validateStore($inputs);
                if ($validation->fails()) {
                    Log::info('End code for add doctor');
                    return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
                }

                if($inputs['report_type_id'] == ReportTypes::SHOP) {
                    $user = UserEntityRelation::where('entity_type_id',EntityTypes::SHOP)
                                                ->where('entity_id',$inputs['entity_id'])->first();
                    $reported_user_id = !empty($user) ? $user->user_id : NULL;
                }else if($inputs['report_type_id'] == ReportTypes::HOSPITAL){
                    $user = UserEntityRelation::where('entity_type_id',EntityTypes::HOSPITAL)
                                                ->where('entity_id',$inputs['entity_id'])->first();
                    $reported_user_id = !empty($user) ? $user->user_id : NULL;
                }else if($inputs['report_type_id'] == ReportTypes::SHOP_PORTFOLIO){
                    $shopImage = ShopImages::find($inputs['entity_id']); 
                    $user = UserEntityRelation::where('entity_type_id',EntityTypes::SHOP)
                                                ->where('entity_id',$shopImage->shop_id)->first();
                    $reported_user_id = !empty($user) ? $user->user_id : NULL;
                }else if($inputs['report_type_id'] == ReportTypes::SHOP_USER){
                    $data = UserDetail::where('user_id',$inputs['entity_id'])->first(); 
                    $reported_user_id = !empty($data) ? $data->user_id : NULL;
                }else if($inputs['report_type_id'] == ReportTypes::COMMUNITY){
                    $data = Community::find($inputs['entity_id']); 
                    $reported_user_id = !empty($data) ? $data->user_id : NULL;
                }else if($inputs['report_type_id'] == ReportTypes::REVIEWS){
                    $data = Reviews::find($inputs['entity_id']); 
                    $reported_user_id = !empty($data) ? $data->user_id : NULL;
                }else if($inputs['report_type_id'] == ReportTypes::REVIEWS_COMMENT){
                    $data = ReviewComments::find($inputs['entity_id']); 
                    $reported_user_id = !empty($data) ? $data->user_id : NULL;
                }else if($inputs['report_type_id'] == ReportTypes::REVIEWS_COMMENT_REPLY){
                    $data = ReviewCommentReply::find($inputs['entity_id']); 
                    $reported_user_id = !empty($data) ? $data->user_id : NULL;
                }else if($inputs['report_type_id'] == ReportTypes::COMMUNITY_COMMENT){
                    $data = CommunityComments::find($inputs['entity_id']); 
                    $reported_user_id = !empty($data) ? $data->user_id : NULL;
                }else if($inputs['report_type_id'] == ReportTypes::COMMUNITY_COMMENT_REPLY){
                    $data = CommunityCommentReply::find($inputs['entity_id']); 
                    $reported_user_id = !empty($data) ? $data->user_id : NULL;
                }else if($inputs['report_type_id'] == ReportTypes::SHOP_PLACE){
                    $reportCustomer = RequestedCustomer::find($inputs['entity_id']); 
                    $userData = UserEntityRelation::join('shops','shops.user_id','user_entity_relation.user_id')
                        ->where('user_entity_relation.entity_type_id',$reportCustomer->entity_type_id)
                        ->where('shops.id',$reportCustomer->entity_id)->first();
                    $reported_user_id = !empty($userData) ? $userData->user_id : NULL;
                    $inputs['report_category_id'] = isset($inputs['report_category_id']) ? $inputs['report_category_id'] : 0;
                }else if($inputs['report_type_id'] == ReportTypes::HOSPITAL_PLACE){
                    $reportCustomer = RequestedCustomer::find($inputs['entity_id']); 
                    $userData = UserEntityRelation::join('posts','posts.hospital_id','user_entity_relation.entity_id')
                        ->where('user_entity_relation.entity_type_id',$reportCustomer->entity_type_id)
                        ->where('posts.id',$reportCustomer->entity_id)
                        ->first();
                    $reported_user_id = !empty($userData) ? $userData->user_id : NULL;
                    $inputs['report_category_id'] = isset($inputs['report_category_id']) ? $inputs['report_category_id'] : 0;
                }elseif($inputs['report_type_id'] == ReportTypes::ASSOCIATION_COMMUNITY){
                    $data = AssociationCommunity::find($inputs['entity_id']); 
                    $reported_user_id = !empty($data) ? $data->user_id : NULL;
                }elseif($inputs['report_type_id'] == ReportTypes::ASSOCIATION_COMMUNITY_COMMENT){
                    $data = AssociationCommunityComment::find($inputs['entity_id']); 
                    $reported_user_id = !empty($data) ? $data->user_id : NULL;
                }else{
                    $reported_user_id = NULL;
                }
                

                $data = [
                    'report_type_id' => $inputs['report_type_id'],
                    'category_id' => $inputs['report_category_id'],
                    'entity_id' => $inputs['entity_id'],
                    'user_id' => $authUser->id,
                    'reported_user_id' => $reported_user_id,
                ];                
                $reportData = ReportClient::create($data);
                $notificationData = $reportData->toArray();
                if($inputs['report_type_id'] == ReportTypes::SHOP|| $inputs['report_type_id'] == ReportTypes::HOSPITAL || $inputs['report_type_id'] == ReportTypes::SHOP_PORTFOLIO || $inputs['report_type_id'] == ReportTypes::SHOP_USER || $inputs['report_type_id'] == ReportTypes::COMMUNITY || $inputs['report_type_id'] == ReportTypes::SHOP_PLACE || $inputs['report_type_id'] == ReportTypes::HOSPITAL_PLACE) {
                    $userIds = [$reported_user_id];
    
                    foreach($userIds as $uId){
                        $devices = UserDevices::whereIn('user_id', [$uId])->pluck('device_token')->toArray();
                        $user_detail = UserDetail::where('user_id', $uId)->first();
                        $language_id = $user_detail->language_id;
                        $key = Notice::REPORT.'_'.$language_id;
                        $format = __("notice.$key");
                        $title_msg = '';
                        $notify_type = 'type_report';
    
                        $notice = Notice::create([
                            'notify_type' => Notice::REPORT,
                            'user_id' => $authUser->id,
                            'to_user_id' => $uId,
                            'title' => $reportData->category_name,
                        ]);
                        if (count($devices) > 0) {
                            $result = $this->sentPushNotification($devices,$title_msg, $format, $notificationData ,$notify_type, $reportData->id);                        
                        }
                    }   
                }

                $config = Config::where('key',Config::REQUEST_CLIENT_REPORT_SNS_REWARD_EMAIL)->first();
                $reportData->reported_count = DB::table('report_clients')->where('entity_id',$reportData->entity_id)->where('report_type_id',$reportData->report_type_id)->whereNull('deleted_at')->sum('status_count');
                if($config) {
                    $userData = [];
                    $userData['email_body'] = "<p><b>Business Name: </b>".$reportData->report_item_name."</p>";
                    $userData['email_body'] .= "<p><b>Type of Business: </b>".$reportData->report_item_category."</p>";
                    $userData['email_body'] .= "<p><b>Reason: </b>".$reportData->category_name."</p>";
                    $userData['email_body'] .= "<p><b>Status: </b>".$reportData->reported_count."</p>";
                    $userData['title'] = 'Reported Client';
                    $userData['subject'] = 'Reported Client';
                    $userData['username'] = 'Admin';
                    if($config->value) {
                        Mail::to($config->value)->send(new CommonMail($userData));
                    }
                }

                DB::commit();
                Log::info('End code for the add report');
                return $this->sendSuccessResponse(Lang::get('messages.report.add-success'), 200, $reportData);                
               
            }else{
                Log::info('End code for add report');
                return $this->sendSuccessResponse(Lang::get('messages.user.token_expired'), 401);
            }                 
            
            
        } catch (\Exception $e) {
            Log::info('Exception in add report');
            Log::info($e);
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }
}
