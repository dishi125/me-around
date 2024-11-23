<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Association;
use App\Models\AssociationCommunity;
use App\Models\AssociationCommunityComment;
use App\Models\AssociationImage;
use App\Models\AssociationLikes;
use App\Models\Banner;
use App\Models\Category;
use App\Models\CategoryTypes;
use App\Models\Community;
use App\Models\CommunityCommentLikes;
use App\Models\CommunityCommentReply;
use App\Models\CommunityCommentReplyLikes;
use App\Models\CommunityComments;
use App\Models\CommunityLikes;
use App\Models\Country;
use App\Models\EntityTypes;
use App\Models\Notice;
use App\Models\Status;
use App\Models\UserDetail;
use App\Models\UserDevices;
use App\Models\UserPoints;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ViewCommunityController extends Controller
{
    public function viewCommunity(Request $request, $id)
    {
        $title = "View Community";
        $community_tabs = getCommunityTabs($id);

        $countries = Country::WhereNotNull('code')->where('is_show',1)->orderBy('priority')->get();
        $countries = collect($countries)->mapWithKeys(function ($value) {
            return [$value->code => $value->name];
        })->toArray();

        return view('admin.users.community.community-view', compact('title', 'id', 'community_tabs', 'countries'));
    }

    public function loadCommunityDetails(Request $request, $id)
    {
        $inputs = $request->all();
        $categoryHtml = '<option value="">Select Category</option>';
        try {
            $tabId = $inputs['active_tab_id'];
            $type = $inputs['active_tab_type'] ?? '';
            $category = $inputs['category'] ?? '';
            $ordervalue = $inputs['ordervalue'] ?? 'popular';
            $country = $inputs['country'] ?? 'KR';

            if ($type == 'associations') {
                $association = Association::find($tabId);
                $bannerImages = $association->associationImage()->where('type', AssociationImage::BANNER_IMAGE)->get();
                /*$bannerImages = collect($bannerImage)->map(function($value){
                    return ['image' => $value->image, 'link' => '', 'slide_duration' => 5 , 'order' => 0];
                });*/


                $hideCategories = $association->associationCategory()->where('is_hide', 1)->pluck('id');

                $communityData = $association->associationCommunity()
                    ->withCount('comments')
                    ->with('user_detail:id,user_id,name,language_id,gender')
                    ->with('category')
                    ->whereNotIn('category_id', $hideCategories)
                    //->where('user_id', $id)
                    ->where('country_code', $country)
                    ->where(function ($q) use ($category) {
                        if (!empty($category)) {
                            $q->where('category_id', $category);
                        }
                    })
                    ->orderBy('is_pin', 'DESC');
                if ($ordervalue == 'popular') {
                    $communityData = $communityData->orderBy('comments_count', 'DESC')->get();
                } else {
                    $communityData = $communityData->orderBy('created_at', 'DESC')->get();
                }

            } else {
                $subCategory = Category::where('category_type_id', CategoryTypes::COMMUNITY)
                    ->where('parent_id', $tabId)
                    ->where('status_id', Status::ACTIVE)->pluck('id');

                $communityQueryPopular = DB::table('community')
                    ->leftJoin('users_detail', 'users_detail.user_id', 'community.user_id')
                    ->leftJoin('category', 'category.id', 'community.category_id')
                    ->select(
                        'community.id',
                        'community.title',
                        'community.views_count',
                        'community.created_at',
                        'users_detail.name as user_name',
                        'users_detail.gender as user_gender',
                        'community.category_id',
                        'category.name as category_name'
                    )
                    ->selectSub(function ($q) {
                        $q->select(DB::raw('count(distinct(id)) as total'))->from('community_comments')->whereNull('community_comments.deleted_at')->whereRaw("`community_comments`.`community_id` = `community`.`id`");
                    }, 'comments_count')
                    //->where('community.user_id', $id)
                    ->whereIn('community.category_id', $subCategory)
                    ->where('community.country_code', $country)
                    ->whereNull('community.deleted_at');

                if (!empty($category)) {
                    $communityQueryPopular = $communityQueryPopular->where('category_id', $category);
                }

                if ($ordervalue == 'popular') {
                    $communityData = $communityQueryPopular->orderBy('comments_count', 'desc')->get();
                } else {
                    $communityData = $communityQueryPopular->orderBy('id', 'desc')->get();
                }

                $communityData->map(function ($item, $key) {
                    $item->time_difference = timeAgo($item->created_at);
                    return $item;
                });

                $bannerImages = Banner::join('banner_images', 'banners.id', '=', 'banner_images.banner_id')
                    ->where('banners.entity_type_id', EntityTypes::COMMUNITY)
                    ->where('banners.section', 'home')
                    ->whereNull('banners.deleted_at')
                    ->whereNull('banner_images.deleted_at')
                    ->where('banners.country_code', $country)
                    ->where('banners.category_id', $tabId)
                    /*->where(function($query) use ($inputs){
                        if (isset($inputs['category']) && !empty($inputs['category'])) {
                            $query
                        }
                    })*/
                    ->orderBy('banner_images.order', 'desc')->orderBy('banner_images.id', 'desc')
                    ->get('banner_images.*');
            }


            $categoryHtml .= loadSubCategoryHtml($type, $tabId, $category);
            $html = view('admin.users.community.community-content', ['id' => $id, 'communityData' => $communityData, "bannerImages" => $bannerImages])->render();
            $jsonData = array(
                'categoryHtml' => $categoryHtml,
                'success' => true,
                'html' => $html,
            );
            return response()->json($jsonData);
        } catch (Exception $e) {
            Log::info($e);
            $jsonData = array(
                'categoryHtml' => $categoryHtml,
                'success' => false,
                'html' => '',
            );
            return response()->json($jsonData);
        }
    }

    public function communityDetails(Request $request, $id, $community_id)
    {
        $inputs = $request->all();
        $user = Auth::user();
        try {

            $tabId = $inputs['active_tab_id'];
            $type = $inputs['active_tab_type'] ?? '';
            $category = $inputs['category'] ?? '';
            $country = $inputs['country'] ?? 'KR';

            if ($type == 'category') {
                $community = Community::find($community_id);
            } else {
                $community = AssociationCommunity::find($community_id);
                if (!empty($community->user_detail)) {
                    $community->user_name = $community->user_detail->name;
                    $community->user_avatar = $community->user_detail->avatar;
                    $community->user_gender = $community->user_detail->gender;
                } else {
                    $community->user_name = '';
                    $community->user_avatar =
                    $community->user_gender = '';
                }
                $community->comments = $community->comments()->with('comments_reply')->withCount('likes')->get();
                $community->likes_count = $community->associationLike()->where(['type' => AssociationLikes::TYPE_ASSOCIATION_COMMUNITY])->count();
                $community->is_liked = ($community->associationLike()->where(['user_id' => $user->id, 'type' => AssociationLikes::TYPE_ASSOCIATION_COMMUNITY])->count() > 0) ? true : false;

            }

            $html = view('admin.users.community.community-detail', ['id' => $id, 'community' => $community])->render();
            $jsonData = array(
                'success' => true,
                'html' => $html,
            );
            return response()->json($jsonData);

        } catch (Exception $e) {
            Log::info($e);
            $jsonData = array(
                'success' => false,
                'html' => '',
            );
            return response()->json($jsonData);
        }
    }

    public function likeCommunity(Request $request, $community_id)
    {

        $inputs = $request->all();
        $user = Auth::user();
        $message = '';
        try {
            $active_tab_type = $inputs['active_tab_type'];
            $type = $inputs['type'];
            $entity_id = $inputs['entity_id'];
            $id = $inputs['id'] ?? 0;
            $user_id = $inputs['user_id'] ?? 0;
            $is_reply = $inputs['is_reply'] ?? 'false';

            if ($active_tab_type == 'associations') {
                $data = [
                    'type' => $type,
                    'entity_id' => $entity_id,
                    'user_id' => $user->id
                ];

                if (!empty($id)) {
                    AssociationLikes::where($data)->delete();
                    $message = 'Dislike successfully.';
                } else {
                    AssociationLikes::create($data);
                    $message = 'Like successfully.';
                }
            } elseif ($active_tab_type == 'category') {

                if ($type == 'community') {

                    if (!empty($id)) {
                        CommunityLikes::where('user_id', $user->id)->where('community_id', $community_id)->forcedelete();
                        UserPoints::where([
                            'user_id' => $user->id,
                            'entity_type' => UserPoints::LIKE_COMMUNITY_POST,
                            'entity_id' => $community_id,
                            'entity_created_by_id' => $user_id,
                        ])->delete();

                    } else {
                        $data = [
                            'user_id' => $user->id,
                            'community_id' => $community_id,
                        ];
                        CommunityLikes::create($data);
                        $community = Community::find($community_id);
                        $notice = Notice::create([
                            'notify_type' => Notice::COMMUNITY_POST_LIKE,
                            'user_id' => $user->id,
                            'to_user_id' => $community->user_id,
                            'entity_type_id' => EntityTypes::COMMUNITY,
                            'entity_id' => $community->id,
                            'title' => $community->title
                        ]);

                        UserPoints::updateOrCreate([
                            'user_id' => $user->id,
                            'entity_type' => UserPoints::LIKE_COMMUNITY_POST,
                            'entity_id' => $community_id,
                            'entity_created_by_id' => $community->user_id,
                        ], ['points' => UserPoints::LIKE_COMMUNITY_POST_POINT]);

                        $user_detail = UserDetail::where('user_id', $community->user_id)->first();
                        $language_id = $user_detail->language_id;
                        $key = Notice::COMMUNITY_POST_LIKE . '_' . $language_id;

                        $userIds = [$community->user_id];

                        $format = __("notice.$key", ['username' => $user->name]);
                        $devices = UserDevices::whereIn('user_id', $userIds)->pluck('device_token')->toArray();
                        $title_msg = '';
                        $notify_type = Notice::COMMUNITY_POST_LIKE;

                        $notificationData = [
                            'id' => $community->id,
                            'user_id' => $community->user_id,
                            'category_id' => $community->category_id,
                            'category_name' => $community->category_name,
                            'title' => $community->title,
                            'description' => $community->description,
                        ];
                        if (count($devices) > 0) {
                            $result = $this->sentPushNotification($devices, $title_msg, $format, $notificationData, $notify_type, $community->id);
                        }
                    }
                } elseif ($type == 'comment') {
                    if($is_reply == 'true'){
                        $data = [
                            'user_id' => $user->id,
                            'community_comment_reply_id' => $entity_id,
                        ];
                        CommunityCommentReplyLikes::create($data);

                    }else {
                        $data = [
                            'user_id' => $user->id,
                            'community_comment_id' => $entity_id,
                        ];
                        CommunityCommentLikes::create($data);
                    }
                }
            }

            $jsonData = array(
                'success' => true,
                'message' => $message,
                'refresh_url' => route('admin.user.community.detail', ['id' => $user_id, 'community_id' => $community_id])
            );
            return response()->json($jsonData);
        } catch (Exception $e) {
            Log::info($e);
            $jsonData = array(
                'success' => false,
                'message' => 'Something went wrong.',
            );
            return response()->json($jsonData);
        }
    }

    public function postComments(Request $request,$community_id): \Illuminate\Http\JsonResponse
    {
        $inputs = $request->all();
        $user = Auth::user();

        try{
            $active_tab_id = $inputs['active_tab_id'];
            $active_tab_type = $inputs['active_tab_type'];
            $user_id = $inputs['user_id'];
            $type = $inputs['type'];
            $entity_id = $inputs['entity_id'];
            $parent_id = $inputs['parent_id'] ?? 0;
            $comment = $inputs['comment'] ?? '';

            if(!empty($comment)) {
                if ($active_tab_type == 'associations') {
                    $users_detail = '';
                    /* if(!empty($parent_id)){
                        $users_detail = DB::table('association_community_comments')->join('users_detail','users_detail.user_id','association_community_comments.user_id')
                            ->whereNull('association_community_comments.deleted_at')
                            ->where('association_community_comments.id',$parent_id)
                            ->select('users_detail.*')
                            ->first();
                    }else{
                        $users_detail = DB::table('association_communities')->join('users_detail','users_detail.user_id','association_communities.user_id')
                            ->whereNull('association_communities.deleted_at')
                            ->where('association_communities.id',$community_id)
                            ->select('users_detail.*')
                            ->first();
                    }

                    $is_unread = 0;
                    if($users_detail && $users_detail->is_outside){
                        $is_unread = 1;
                    } */

                    $requestData = [
                        'community_id' => $community_id,
                        'user_id' => $user->id,
                        'comment' => $comment,
                        'parent_id' => $parent_id,
                        'is_edited' => 0,
                        'is_admin_read' => 1
                    ];
                    AssociationCommunityComment::create($requestData);
                }elseif($active_tab_type == 'category'){
                    if(!empty($parent_id)) {
                        $community_comment_id = (isset($inputs['is_reply_id']) && !empty($inputs['is_reply_id']) && $inputs['is_reply_id'] != 'no') ? $inputs['is_reply_id'] : $parent_id;

                        /* $users_detail = DB::table('community_comments')->join('users_detail','users_detail.user_id','community_comments.user_id')
                                ->whereNull('community_comments.deleted_at')
                                ->where('community_comments.id',$community_comment_id)
                                ->select('users_detail.*')
                                ->first();

                        $is_reply_unread = 0;
                        if($users_detail && $users_detail->is_outside){
                            $is_reply_unread = 1;
                        } */

                        $data = [
                            'user_id' => $user->id,
                            'community_comment_id' => $community_comment_id,
                            'comment' => $comment,
                            'reply_parent_id' => (isset($inputs['is_reply_id']) && !empty($inputs['is_reply_id']) && $inputs['is_reply_id'] != 'no') ? $parent_id : null,
                            'is_admin_read' => 1
                        ];
                        CommunityCommentReply::create($data);

                        $community = CommunityComments::find($community_comment_id);

                        // Send Notification and notice to parent
                        if(isset($inputs['is_reply_id']) && !empty($inputs['is_reply_id']) && $inputs['is_reply_id'] != 'no'){
                            $parentCommentId = $inputs['parent_id'];

                            $parentComment = CommunityCommentReply::where(['id' => $parentCommentId])->first();

                            if($user->id != $parentComment->user_id){

                                Notice::create([
                                    'notify_type' => Notice::COMMUNITY_REPLY_COMMENT,
                                    'user_id' => $user->id,
                                    'to_user_id' => $parentComment->user_id,
                                    'entity_type_id' => EntityTypes::COMMUNITY,
                                    'entity_id' => $community_comment_id,
                                    'sub_title' => $comment,
                                    'is_aninomity' => 0
                                ]);

                                $user_detail = UserDetail::where('user_id', $parentComment->user_id)->first();

                                $language_id = $user_detail->language_id;
                                $key = Notice::COMMUNITY_REPLY_COMMENT.'_'.$language_id;

                                $userIds = [$parentComment->user_id];
                                $userName = $user->name;

                                $format = __("notice.$key", ['username' => $userName]);
                                $devices = UserDevices::whereIn('user_id', $userIds)->pluck('device_token')->toArray();
                                $title_msg = '';
                                $notify_type = 'notices';

                                $notificationData = $community->toArray();
                                if (count($devices) > 0) {
                                    $result = $this->sentPushNotification($devices,$title_msg, $format, [] ,$notify_type);
                                }

                            }

                        }

                        // Send Notification and notice to Main parent Comment
                        if(isset($inputs['is_reply_id']) && $inputs['is_reply_id'] == 'no'){
                            if($user->id != $community->user_id){
                                Notice::create([
                                    'notify_type' => Notice::COMMUNITY_REPLY_COMMENT,
                                    'user_id' => $user->id,
                                    'to_user_id' => $community->user_id,
                                    'entity_type_id' => EntityTypes::COMMUNITY,
                                    'entity_id' => $community->community_id,
                                    'sub_title' => $comment,
                                    'is_aninomity' => 0
                                ]);

                                $user_detail = UserDetail::where('user_id', $community->user_id)->first();
                                $language_id = $user_detail->language_id;
                                $key = Notice::COMMUNITY_REPLY_COMMENT.'_'.$language_id;

                                $userIds = [$community->user_id];

                                $userName = $user->name;

                                $format = __("notice.$key", ['username' => $userName]);

                                // $format = __("notice.$key", ['username' => $user->name]);
                                $devices = UserDevices::whereIn('user_id', $userIds)->pluck('device_token')->toArray();
                                $title_msg = '';
                                $notify_type = 'notices';

                                $notificationData = $community->toArray();
                                if (count($devices) > 0) {
                                    $result = $this->sentPushNotification($devices,$title_msg, $format, [] ,$notify_type);
                                }
                            }
                        }
                    }else{
                        /* $users_detail = DB::table('community')->join('users_detail','users_detail.user_id','community.user_id')
                                ->whereNull('community.deleted_at')
                                ->where('community.id',$community_id)
                                ->select('users_detail.*')
                                ->first();

                        $is_unread = 0;
                        if($users_detail && $users_detail->is_outside){
                            $is_unread = 1;
                        } */

                        CommunityComments::create([
                            'user_id' => $user->id,
                            'community_id' => $community_id,
                            'comment' => $comment,
                            'is_admin_read' => 1
                        ]);
                        $community = DB::table('community')->whereId($community_id)->first();

                        if($user->id != $community->user_id){

                            $notice = Notice::create([
                                'notify_type' => Notice::COMMUNITY_POST_COMMENT,
                                'user_id' => $user->id,
                                'to_user_id' => $community->user_id,
                                'entity_type_id' => EntityTypes::COMMUNITY,
                                'entity_id' => $community->id,
                                'title' => $community->title,
                                'sub_title' => $inputs['comment'],
                                'is_aninomity' => 0
                            ]);

                            $user_detail = UserDetail::where('user_id', $community->user_id)->first();
                            $language_id = $user_detail->language_id;
                            $key = Notice::COMMUNITY_POST_COMMENT.'_'.$language_id;

                            $userIds = [$community->user_id];

                            $userName = $user->name;

                            $format = __("notice.$key", ['username' => $userName]);
                            $devices = UserDevices::whereIn('user_id', $userIds)->pluck('device_token')->toArray();
                            $title_msg = '';
                            $notify_type = 'notices';

                            $notificationData = collect($community)->toArray();
                            if (count($devices) > 0) {
                                $result = $this->sentPushNotification($devices,$title_msg, $format, [] ,$notify_type);
                            }
                        }
                    }
                }
            }

            $jsonData = array(
                'success' => true,
                'message' => "",
                'refresh_url' => route('admin.user.community.detail', ['id' => $user_id, 'community_id' => $community_id])
            );
            return response()->json($jsonData);
        }catch (\Exception $e){
            Log::info($e);
            $jsonData = array(
                'success' => false,
                'message' => 'Something went wrong.',
            );
            return response()->json($jsonData);
        }
    }
}
