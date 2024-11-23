<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Log;
use App\Models\EntityTypes;
use App\Models\Status;
use App\Models\Community;
use App\Models\Shop;
use App\Models\Hospital;
use App\Models\UserDetail;
use App\Models\ReportClient;
use App\Models\Notice;
use App\Models\UserEntityRelation;
use App\Models\User;
use App\Models\CommunityComments;
use App\Models\CommunityCommentReply;
use App\Models\ReviewComments;
use App\Models\ReviewCommentReply;

class CommentController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:comment-list', ['only' => ['index']]);
    }

    public function index(Request $request)
    {
        $title = 'Comment List';

        return view('admin.comment.index', compact('title'));
    }

    public function getJsonData(Request $request){

        $columns = array(
            0 => 'name',
            1 => 'email',
            3 => 'update_date',
            4 => 'users.last_login',
            5 => 'post_title',
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
            $commentsQuery = DB::table('community_comments')
                ->leftJoin('community','community.id','community_comments.community_id')
                ->leftJoin('users','users.id','community_comments.user_id')
                ->leftJoin('users_detail','users_detail.user_id','users.id')
                ->whereNull('community_comments.deleted_at')
                ->where(function($q) use ($search){
                    if (!empty($search)) {
                        $q->where('users_detail.name', 'LIKE', "%{$search}%")
                        ->orWhere('users.email', 'LIKE', "%{$search}%")
                        ->orWhere('community.title', 'LIKE', "%{$search}%")
                        ->orWhere('community_comments.comment', 'LIKE', "%{$search}%");
                    }
                })
                ->select(
                    'community_comments.id',
                    'community_comments.user_id',
                    'users_detail.name',
                    'users_detail.mobile',
                    'users.email',
                    'community_comments.updated_at as update_date',
                    'community.title as post_title'
                )
                ->groupBy('community_comments.id')
                ->get();

            $commentsQuery->map(function($item){
                $item->post_type = "Community";
                $item->post_table = "community_comments";
                return $item;
            });

            $commentsReplyQuery = DB::table('community_comment_reply')
                ->leftJoin('community_comments','community_comments.id','community_comment_reply.community_comment_id')
                ->leftJoin('community','community.id','community_comments.community_id')
                ->leftJoin('users','users.id','community_comments.user_id')
                ->leftJoin('users_detail','users_detail.user_id','users.id')
                ->whereNull('community_comment_reply.deleted_at')
                ->where(function($q) use ($search){
                    if (!empty($search)) {
                        $q->where('users_detail.name', 'LIKE', "%{$search}%")
                        ->orWhere('users.email', 'LIKE', "%{$search}%")
                        ->orWhere('community.title', 'LIKE', "%{$search}%")
                        ->orWhere('users_detail.mobile', 'LIKE', "%{$search}%")
                        ->orWhere('community_comment_reply.comment', 'LIKE', "%{$search}%");
                    }
                })
                ->select(
                    'community_comment_reply.id',
                    'community_comment_reply.user_id',
                    'users_detail.name',
                    'users_detail.mobile',
                    'users.email',
                    'community_comment_reply.updated_at as update_date',
                    'community.title as post_title'
                )
                ->groupBy('community_comment_reply.id')
                ->get();
            $commentsReplyQuery->map(function($item){
                $item->post_type = "Community";
                $item->post_table = "community_comment_reply";
                return $item;
            });

            $AllCommentData = $commentsQuery->merge($commentsReplyQuery);

            // Review Comments
            $reviewCommentsQuery = DB::table('review_comments')
                ->leftJoin('reviews','reviews.id','review_comments.review_id')
                ->leftjoin('shops', function ($join) {
                    $join->on('shops.id', '=', 'reviews.entity_id')
                        ->where('reviews.entity_type_id', EntityTypes::SHOP);
                })
                ->leftjoin('posts', function ($join) {
                    $join->on('posts.id', '=', 'reviews.entity_id')
                        ->where('reviews.entity_type_id', EntityTypes::HOSPITAL);
                })
                ->leftJoin('users','users.id','review_comments.user_id')
                ->leftJoin('users_detail','users_detail.user_id','users.id')
                ->whereNull('review_comments.deleted_at')
                ->where(function($q) use ($search){
                    if (!empty($search)) {
                        $q->where('users_detail.name', 'LIKE', "%{$search}%")
                        ->orWhere('users.email', 'LIKE', "%{$search}%")
                        ->orWhere('posts.title', 'LIKE', "%{$search}%")
                        ->orWhere('shops.shop_name', 'LIKE', "%{$search}%")
                        ->orWhere('review_comments.comment', 'LIKE', "%{$search}%");
                    }
                })
                ->select(
                    'review_comments.id',
                    'review_comments.user_id',
                    'users_detail.name',
                    'users_detail.mobile',
                    'users.email',
                    'review_comments.updated_at as update_date',
                    \DB::raw('(CASE 
                            WHEN reviews.entity_type_id = 1 THEN  shops.shop_name
                            WHEN reviews.entity_type_id = 2 THEN posts.title
                            ELSE "" 
                                END) AS post_title') 
                )
                ->groupBy('review_comments.id')
                ->get();

            $reviewCommentsQuery->map(function($item){
                $item->post_type = "Review";
                $item->post_table = "review_comments";
                return $item;
            });

            $reviewCommentsReplyQuery = DB::table('review_comment_reply')
                ->leftJoin('review_comments','review_comments.id','review_comment_reply.review_comment_id')
                ->leftJoin('reviews','reviews.id','review_comments.review_id')
                ->leftjoin('shops', function ($join) {
                    $join->on('shops.id', '=', 'reviews.entity_id')
                        ->where('reviews.entity_type_id', EntityTypes::SHOP);
                })
                ->leftjoin('posts', function ($join) {
                    $join->on('posts.id', '=', 'reviews.entity_id')
                        ->where('reviews.entity_type_id', EntityTypes::HOSPITAL);
                })
                ->leftJoin('users','users.id','review_comments.user_id')
                ->leftJoin('users_detail','users_detail.user_id','users.id')
                ->whereNull('review_comment_reply.deleted_at')
                ->where(function($q) use ($search){
                    if (!empty($search)) {
                        $q->where('users_detail.name', 'LIKE', "%{$search}%")
                        ->orWhere('users.email', 'LIKE', "%{$search}%")
                        ->orWhere('posts.title', 'LIKE', "%{$search}%")
                        ->orWhere('shops.shop_name', 'LIKE', "%{$search}%")
                        ->orWhere('review_comment_reply.comment', 'LIKE', "%{$search}%");
                    }
                })
                ->select(
                    'review_comment_reply.id',
                    'review_comment_reply.user_id',
                    'users_detail.name',
                    'users_detail.mobile',
                    'users.email',
                    'review_comment_reply.updated_at as update_date',
                    \DB::raw('(CASE 
                            WHEN reviews.entity_type_id = 1 THEN  shops.shop_name
                            WHEN reviews.entity_type_id = 2 THEN posts.title
                            ELSE "" 
                                END) AS post_title') 
                )
                ->groupBy('review_comment_reply.id')
                ->get();

            $reviewCommentsReplyQuery->map(function($item){
                $item->post_type = "Review";
                $item->post_table = "review_comment_reply";
                return $item;
            });
            
            
            $AllReviewCommentData = $reviewCommentsQuery->merge($reviewCommentsReplyQuery);
            // Review Comments

            $result = $AllCommentData->merge($AllReviewCommentData);
            $result = $result->all();

            $totalData = count($result);
            $totalFiltered = $totalData;
            

            if($dir == 'asc'){
                $result = collect($result)->sortBy($order);
            }else{
                $result = collect($result)->sortByDesc($order);
            }
            $result = collect($result)->slice($start, $limit);
            $count = 0;
            foreach($result as $comment){

                $show = route('admin.comment.show', [$comment->post_table, $comment->id]);
                
                $params = ",`{$comment->post_table}`";
                $deleteFun = "deleteComment($comment->id $params)";

                $deleteComment = "<a role='button' href='javascript:void(0)' onclick='$deleteFun' title='' data-original-title='Delete Account' class='mx-1 btn btn-primary btn-sm' data-toggle='tooltip'>Delete Comment</a>";
                $deleteButton = "<a role='button' href='javascript:void(0)' onclick='deleteUser(" . $comment->user_id . ")' title='' data-original-title='Delete Account' class='mx-1 btn btn-primary btn-sm' data-toggle='tooltip'>Delete Account</a>";
                $viewButton = "<a role='button' href='".$show."' title='' data-original-title='View' class='mx-1 btn btn-primary btn-sm' data-toggle='tooltip'>View Comment</a>";

                $data[$count]['name'] = $comment->name;
                $data[$count]['email'] = $comment->email;
                $data[$count]['mobile'] = $comment->mobile;
                $data[$count]['update_date'] = $this->formatDateTimeCountryWise($comment->update_date,$adminTimezone);
                $data[$count]['post_type'] = $comment->post_type;
                $data[$count]['post_title'] = $comment->post_title;
                $data[$count]['actions'] = "<div class='d-flex'>$deleteComment $deleteButton $viewButton</div>";
                $count++;
            }

            $jsonData = array(
                "draw" => intval($draw),
                "recordsTotal" => intval($totalData),
                "recordsFiltered" => intval($totalFiltered),
                "data" => $data,
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


    public function deleteAccount(Request $request,$id)
    { 
        try {
            Log::info('Start delete user ');
            DB::beginTransaction();
            $userId = $id;
            
            $userRelation = UserEntityRelation::where('user_id',$userId)->get();
            foreach($userRelation as $ur){
                if($ur->entity_type_id == EntityTypes::SHOP){                    
                    Shop::where('id',$ur->entity_id)->delete();
                }else if($ur->entity_type_id == EntityTypes::HOSPITAL){
                    Hospital::where('id',$ur->entity_id)->delete();
                }
            }
            Community::where('user_id',$userId)->delete();
            UserDetail::where('user_id',$userId)->delete();
            Notice::where('user_id',$userId)->delete();
            CommunityComments::where('user_id',$userId)->delete();
            CommunityCommentReply::where('user_id',$userId)->delete();
            ReviewComments::where('user_id',$userId)->delete();
            ReviewCommentReply::where('user_id',$userId)->delete();
            $userRelation = UserEntityRelation::where('user_id',$userId)->delete();
            $deleteReport =  ReportClient::where('reported_user_id',$userId)->delete();
            User::where('id',$userId)->delete();

            DB::commit();
            Log::info('End delete user.' );
            notify()->success("User Account deleted successfully.", "Success", "topRight");
            return redirect()->route('admin.comment.index');
        } catch (\Exception $ex) {
            DB::rollBack();
            Log::info('Exception in delete user');
            Log::info($ex);
            notify()->error("Unable to delete user", "Error", "topRight");
            return redirect()->route('admin.comment.index');    
        }
    }

    public function getAccount($id)
    {
        return view('admin.comment.delete-account', compact('id'));
    }

    public function getCommentModel($id, $table)
    {
        return view('admin.comment.delete-comment', compact('id','table'));
    }

    public function deleteCommentDetails(Request $request, $id, $table)
    {
        try {
            if(!empty($id) && !empty($table)){
                DB::table($table)->where('id',$id)->delete();
            }
            notify()->success("Comment deleted successfully.", "Success", "topRight");
            return redirect()->route('admin.comment.index');
            
        } catch (\Exception $ex) {
            DB::rollBack();
            Log::info('Exception in delete user');
            Log::info($ex);
            notify()->error("Unable to delete user", "Error", "topRight");
            return redirect()->route('admin.comment.index');    
        }
    }

    public function show($table, $id)
    {       
        $title = 'Comment Detail';
        $comment = DB::table($table)->leftJoin('users_detail','users_detail.user_id',"$table.user_id")
            ->select(
                "$table.*",
                'users_detail.name as username'
            )
            ->where("$table.id",$id)->first();  
         
        return view('admin.comment.show', compact('title','comment'));
    }

}
