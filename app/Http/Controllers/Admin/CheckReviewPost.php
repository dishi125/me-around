<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Log;
use Illuminate\Support\Facades\DB;
use App\Models\EntityTypes;
use App\Models\ReviewImages;
use App\Models\Reviews;
use App\Models\ReviewLikes;
use App\Models\ReviewComments;
use App\Models\ReviewCommentLikes;
use App\Models\ReviewCommentReply;
use App\Models\ReviewCommentReplyLikes;

class CheckReviewPost extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:review-list', ['only' => ['index']]);
    }

    public function index()
    {
        $title = 'Check Review Post';
        DB::table('reviews')->where('is_admin_read',1)->update(['is_admin_read' => 0]);
        return view('admin.review-post.index', compact('title'));
    }

    public function getJsonData(Request $request){

        $columns = array(
            0 => 'users_detail.name',
            2 => 'main_name',
            4 => 'reviews.updated_at'
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
            $reviewQuery = DB::table('reviews')
                                ->join('users_detail','users_detail.user_id','reviews.user_id')
                                ->leftjoin('shops', function ($join) {
                                    $join->on('shops.id', '=', 'reviews.entity_id')
                                        ->where('reviews.entity_type_id', EntityTypes::SHOP);
                                })
                                ->leftjoin('posts', function ($join) {
                                    $join->on('posts.id', '=', 'reviews.entity_id')
                                        ->where('reviews.entity_type_id', EntityTypes::HOSPITAL);
                                })
                                ->leftjoin('hospitals', function ($join) {
                                    $join->on('hospitals.id', '=', 'posts.hospital_id');
                                })
                                ->leftjoin('user_entity_relation', function ($join) {
                                    $join->on('user_entity_relation.entity_id', '=', 'hospitals.id')
                                        ->where('user_entity_relation.entity_type_id', EntityTypes::HOSPITAL);
                                })
                                ->select(
                                    'reviews.*',
                                    'users_detail.user_id as user_id',
                                    'users_detail.name as username',
                                    'users_detail.mobile as user_mobile',
                                    \DB::raw('(CASE 
                                    WHEN reviews.entity_type_id = 1 THEN  shops.main_name
                                    WHEN reviews.entity_type_id = 2 THEN hospitals.main_name 
                                    ELSE "" 
                                    END) AS main_name'),
                                    \DB::raw('(CASE 
                                    WHEN reviews.entity_type_id = 1 THEN  shops.user_id
                                    WHEN reviews.entity_type_id = 2 THEN user_entity_relation.user_id 
                                    ELSE "" 
                                    END) AS business_user_id')
                                )
                                ->whereNull('reviews.deleted_at');

                if($filter != 'all'){
                    if($filter == 'shop'){
                        $reviewQuery = $reviewQuery->where('reviews.entity_type_id',EntityTypes::SHOP);
                    }else{
                        $reviewQuery = $reviewQuery->where('reviews.entity_type_id',EntityTypes::HOSPITAL);
                    }                    
                }
                if (!empty($search)) {
                    $reviewQuery = $reviewQuery->where(function($q) use ($search){
                        $q->where('users_detail.name', 'LIKE', "%{$search}%")
                        ->orWhere('shops.main_name', 'LIKE', "%{$search}%")
                        ->orWhere('hospitals.main_name', 'LIKE', "%{$search}%")
                        ->orWhere('users_detail.mobile', 'LIKE', "%{$search}%");
                    });
                    
                }
            $totalData = count($reviewQuery->get());
            $totalFiltered = $totalData;
            $reviewData = $reviewQuery->offset($start)
                ->limit($limit)
                ->orderBy($order, $dir)  
                ->get();

            $count = 0;
            foreach($reviewData as $review){
                $businessUser = DB::table('users_detail')->where('id',$review->business_user_id)->first();
                $images = ReviewImages::where('review_id',$review->id)->orderBy('type','ASC')->pluck('image');
                

                $imageHtml = collect($images)->map(function ($image) use ($review) {
                    return '<img onclick="showImage(`'. $image .'`)" src="'.$image.'" alt="'.$review->id.'" class="reported-client-images pointer m-1" width="50" height="50" />';
                });

                
                $show = route('admin.review-post.show', [$review->id]);
                $deleteURL = route('admin.get.review-post.delete', [$review->id]);
                $viewPost = "<a role='button' href='".$show."' title='' data-original-title='View' class='mx-1 btn btn-primary btn-sm' data-toggle='tooltip'>View Post</a>";
                $deletePost = "<a role='button' href='javascript:void(0)' onclick='deletePost(`" . $deleteURL . "`)' title='' data-original-title='Delete Post' class='mx-1 btn btn-primary btn-sm' data-toggle='tooltip'>Delete Post</a>";
                
                $data[$count]['user_name'] = $review->username;
                $data[$count]['phone'] = $review->user_mobile;
                $data[$count]['business_name'] = $review->main_name;
                $data[$count]['business_phone'] = (!empty($businessUser)) ? $businessUser->mobile : '';
                $data[$count]['updated_at'] = $this->formatDateTimeCountryWise($review->updated_at,$adminTimezone);
                $data[$count]['images'] = $imageHtml;
                $data[$count]['actions'] = "<div class='d-flex'>$viewPost $deletePost</div>";
                $count++;
            }

            $jsonData = array(
                "draw" => intval($draw),
                "recordsTotal" => intval($totalData),
                "recordsFiltered" => intval($totalFiltered),
                "data" => $data,
                "reveiwdata" => $reviewData,
            );
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

    public function getDeletePost($id)
    {
        return view('admin.review-post.delete-post', compact('id'));
    }

    public function DeleteReviewPost(Request $request){
        $inputs = $request->all();
        $reviewID = $inputs['reviewId'];
        try {
            if(!empty($reviewID)){
                DB::beginTransaction();  
                ReviewImages::where('review_id',$reviewID)->delete();
                $comments = ReviewComments::where('review_id',$reviewID)->get();
                foreach($comments as $comment){
                    ReviewCommentLikes::where('review_comment_id',$comment->id)->delete();
                    $replyComment = ReviewCommentReply::where('review_comment_id',$comment->id)->get();
                    foreach($replyComment as $commentData){
                        ReviewCommentReplyLikes::where('id',$commentData->id)->delete();
                        $commentData->delete();
                    }
                    $comment->delete();
                }                
                Reviews::where('id',$reviewID)->delete();
                DB::commit();

                return $this->sendSuccessResponse('Review post deleted successfully.', 200);

            }else{
                return $this->sendSuccessResponse('Failed to delete review post.', 201);
            }
            

        } catch (\Exception $ex) {
            DB::rollBack();
            Log::info('Delete user code exception.');
            Log::info($ex);
            return $this->sendFailedResponse('Failed to delete review post.', 201);
        }
    }

    public function show($id)
    {       
        $title = 'Review Post Detail';
        $reviews = Reviews::find($id);  
         
        return view('admin.review-post.show', compact('title','reviews'));
    }
}
