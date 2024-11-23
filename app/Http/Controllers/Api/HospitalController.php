<?php

namespace App\Http\Controllers\Api;

use App\Models\Hospital;
use App\Models\Status;
use App\Models\EntityTypes;
use App\Models\RequestBookingStatus;
use App\Models\Banner;
use App\Models\Post;
use App\Models\Category;
use App\Models\CreditPlans;
use App\Models\PackagePlan;
use App\Models\UserDetail;
use App\Models\Address;
use App\Models\PostImages;
use App\Models\PostLanguage;
use App\Models\Reviews;
use App\Models\Currency;
use App\Models\SavedHistoryTypes;
use App\Models\UserCredit;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use App\Validators\HospitalProfileValidator;
use Validator;
use Carbon\Carbon;


class HospitalController extends Controller
{
    private $hospitalProfileValidator;

    function __construct()
    {
        $this->hospitalProfileValidator = new HospitalProfileValidator();
    }
    public function getAllHospitals(Request $request)
    {
        try {
            Log::info('Start code for get all hospital');
            $inputs = $request->all();
            $user = Auth::user();

            $category_id = isset($inputs['category_id']) ? $inputs['category_id'] : 0;



            $validation = $this->hospitalProfileValidator->validateGetPost($inputs);

            if ($validation->fails()) {
                Log::info('End code for get all hospital');
                return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
            }
            $main_country = getCountryFromLatLong($inputs['latitude'],$inputs['longitude']);
            $returnData = [];
            $creditPlans = CreditPlans::where('entity_type_id', EntityTypes::HOSPITAL)->get();
            $bronzePlanKm = $silverPlanKm = $goldPlanKm = $platiniumPlanKm = 0;
            foreach($creditPlans as $plan) {
                if($plan->package_plan_id == PackagePlan::BRONZE) {
                    $bronzePlanKm = $plan->km;
                }else if($plan->package_plan_id == PackagePlan::SILVER) {
                    $silverPlanKm = $plan->km;
                }else if($plan->package_plan_id == PackagePlan::GOLD) {
                    $goldPlanKm = $plan->km;
                }else if($plan->package_plan_id == PackagePlan::PLATINIUM) {
                    $platiniumPlanKm = $plan->km;
                }
            }


            // Restricted All Posts code
            $allHospitalWithPlan = DB::table('hospitals')
            ->join('user_entity_relation','user_entity_relation.entity_id','hospitals.id')
            ->join('users_detail','users_detail.user_id','user_entity_relation.user_id')
            ->join('credit_plans','credit_plans.package_plan_id','users_detail.package_plan_id')
            ->where('user_entity_relation.entity_type_id', EntityTypes::HOSPITAL)
            ->select('users_detail.user_id', 'credit_plans.package_plan_id','credit_plans.no_of_posts','credit_plans.no_of_posts', 'user_entity_relation.entity_id as hospital_id')
            ->groupBy('hospitals.id')
            ->get();
            $showPostIds = [];
            foreach($allHospitalWithPlan as $hospitalDetail){
                $postIds = DB::table('posts')->where('hospital_id',$hospitalDetail->hospital_id)->where('status_id',Status::ACTIVE)->limit($hospitalDetail->no_of_posts)->pluck('id')->toArray();
                $showPostIds = array_merge($showPostIds, $postIds);
            }
            // Restricted All Posts code end

            $distance = "(6371 * acos(cos(radians(".$inputs['latitude']."))
                     * cos(radians(addresses.latitude))
                     * cos(radians(addresses.longitude)
            - radians(".$inputs['longitude']."))
            + sin(radians(".$inputs['latitude']."))
                     * sin(radians(addresses.latitude))))";

            $limitByPackage = DB::raw('case when `shops`.`expose_distance` IS NOT NULL then `shops`.`expose_distance`
                when `users_detail`.package_plan_id = '. PackagePlan::BRONZE .' then '.$bronzePlanKm.'
                when `users_detail`.package_plan_id = '. PackagePlan::SILVER .' then '.$silverPlanKm.'
                when `users_detail`.package_plan_id = '. PackagePlan::GOLD .' then '.$goldPlanKm.'
                when `users_detail`.package_plan_id = '. PackagePlan::PLATINIUM .' then '.$platiniumPlanKm.'
                else 40 end ');

             // All Hospitals
            $hospitalsQuery = Post::join('hospitals', function ($join) {
                $join->on('posts.hospital_id', '=', 'hospitals.id');
            })
            ->leftjoin('addresses', function ($join) {
                $join->on('hospitals.id', '=', 'addresses.entity_id')
                ->where('addresses.entity_type_id', EntityTypes::HOSPITAL);
            })
            ->leftjoin('user_entity_relation', function ($join) {
                $join->on('hospitals.id', '=', 'user_entity_relation.entity_id')
                ->where('user_entity_relation.entity_type_id', EntityTypes::HOSPITAL);
            })
            ->leftjoin('users_detail', function ($join) {
                $join->on('user_entity_relation.user_id', '=', 'users_detail.user_id');
            })
            ->where(function($query) use ($showPostIds){
                if(count($showPostIds)){
                  $query->whereIn('posts.id',$showPostIds);
              }
          })
            ->where('addresses.main_country',$main_country)
            ->where(function($q) {
                $q->where('posts.status_id',Status::ACTIVE);
                                        // ->orWhere('status_id',Status::PENDING);
            });

            $recentCompletedDistanceQuery = Post::join('requested_customer','requested_customer.entity_id','posts.id')
            ->join('hospitals', function ($join) {
                $join->on('posts.hospital_id', '=', 'hospitals.id');
            })
            ->leftjoin('addresses', function ($join) {
                $join->on('hospitals.id', '=', 'addresses.entity_id')
                ->where('addresses.entity_type_id', EntityTypes::HOSPITAL);
            })
            ->leftjoin('user_entity_relation', function ($join) {
                $join->on('hospitals.id', '=', 'user_entity_relation.entity_id')
                ->where('user_entity_relation.entity_type_id', EntityTypes::HOSPITAL);
            })
            ->leftjoin('users_detail', function ($join) {
                $join->on('user_entity_relation.user_id', '=', 'users_detail.user_id');
            })
            ->where(function($query) use ($showPostIds){
                if(count($showPostIds)){
                  $query->whereIn('posts.id',$showPostIds);
              }
          })
            ->where('addresses.main_country',$main_country)
            ->where(function($q) {
                $q->where('posts.status_id',Status::ACTIVE);
                                        // ->orWhere('status_id',Status::PENDING);
            })
            ->where('requested_customer.entity_type_id',EntityTypes::HOSPITAL)
            ->where('requested_customer.request_booking_status_id',RequestBookingStatus::COMPLETE);

            // Recent Completed All Area
            $recentCompletedAllAreaQuery = Post::join('requested_customer','requested_customer.entity_id','posts.id')
            ->join('hospitals', function ($join) {
                $join->on('posts.hospital_id', '=', 'hospitals.id');
            })
            ->leftjoin('addresses', function ($join) {
                $join->on('hospitals.id', '=', 'addresses.entity_id')
                ->where('addresses.entity_type_id', EntityTypes::HOSPITAL);
            })
            ->leftjoin('user_entity_relation', function ($join) {
                $join->on('hospitals.id', '=', 'user_entity_relation.entity_id')
                ->where('user_entity_relation.entity_type_id', EntityTypes::HOSPITAL);
            })
            ->leftjoin('users_detail', function ($join) {
                $join->on('user_entity_relation.user_id', '=', 'users_detail.user_id');
            })
            ->where(function($query) use ($showPostIds){
                if(count($showPostIds)){
                  $query->whereIn('posts.id',$showPostIds);
              }
          })
            ->where('addresses.main_country',$main_country)
            ->where(function($q) {
                $q->where('posts.status_id',Status::ACTIVE);
                                                // ->orWhere('status_id',Status::PENDING);
            })
            ->where('requested_customer.entity_type_id',EntityTypes::HOSPITAL)
            ->where('requested_customer.request_booking_status_id',RequestBookingStatus::COMPLETE);

           // Around You Random
            $aroundYouRandomQuery = Post::leftjoin('reviews', function ($join) {
                $join->on('posts.id', '=', 'reviews.entity_id')
                ->where('reviews.entity_type_id', EntityTypes::HOSPITAL);
            })
            ->join('hospitals', function ($join) {
                $join->on('posts.hospital_id', '=', 'hospitals.id');
            })
            ->leftjoin('addresses', function ($join) {
                $join->on('hospitals.id', '=', 'addresses.entity_id')
                ->where('addresses.entity_type_id', EntityTypes::HOSPITAL);
            })
            ->leftjoin('user_entity_relation', function ($join) {
                $join->on('hospitals.id', '=', 'user_entity_relation.entity_id')
                ->where('user_entity_relation.entity_type_id', EntityTypes::HOSPITAL);
            })
            ->leftjoin('users_detail', function ($join) {
                $join->on('user_entity_relation.user_id', '=', 'users_detail.user_id');
            })
            ->where(function($query) use ($showPostIds){
                if(count($showPostIds)){
                  $query->whereIn('posts.id',$showPostIds);
              }
          })
            ->where('addresses.main_country',$main_country)
            ->where('posts.status_id',Status::ACTIVE);

        // Around You Best
            $aroundYouBestQuery = Post::join('hospitals','posts.hospital_id', 'hospitals.id')
            ->leftjoin('addresses', function ($join) {
                $join->on('hospitals.id', '=', 'addresses.entity_id')
                ->where('addresses.entity_type_id', EntityTypes::HOSPITAL);
            })
            ->leftjoin('reviews', function ($join) {
                $join->on('posts.id', '=', 'reviews.entity_id')
                ->where('reviews.entity_type_id', EntityTypes::HOSPITAL);
            })
            ->leftjoin('user_entity_relation', function ($join) {
                $join->on('hospitals.id', '=', 'user_entity_relation.entity_id')
                ->where('user_entity_relation.entity_type_id', EntityTypes::HOSPITAL);
            })
            ->leftjoin('users_detail', function ($join) {
                $join->on('user_entity_relation.user_id', '=', 'users_detail.user_id');
            })
            ->where(function($query) use ($showPostIds){
                if(count($showPostIds)){
                  $query->whereIn('posts.id',$showPostIds);
              }
          })
            ->where('addresses.main_country',$main_country)
            ->where('posts.status_id',Status::ACTIVE);

            if($category_id != 0){
                $hospitalsQuery = $hospitalsQuery->where('posts.category_id',$category_id);
                $recentCompletedDistanceQuery = $recentCompletedDistanceQuery->where('posts.category_id',$category_id);
                $recentCompletedAllAreaQuery = $recentCompletedAllAreaQuery->where('posts.category_id',$category_id);
                $aroundYouRandomQuery = $aroundYouRandomQuery->where('posts.category_id',$category_id);
                $aroundYouBestQuery = $aroundYouBestQuery->where('posts.category_id',$category_id);
            }

            $hospitalsQuery = $hospitalsQuery->orderby('distance')
            ->select('posts.*')
            ->selectRaw("{$distance} AS distance")
            ->selectRaw("{$limitByPackage} AS limitByPackage")
            ->whereRaw("{$distance} <= {$limitByPackage}");

            // // Recent Completed Distance
            // $recentCompletedDistanceQuery = $recentCompletedDistanceQuery->join('requested_customer','requested_customer.entity_id','posts.id')
            //                                 ->where('requested_customer.entity_type_id',EntityTypes::HOSPITAL)
            //                                 ->where('requested_customer.request_booking_status_id',RequestBookingStatus::COMPLETE);

            $recentCompletedAllAreaQuery = $recentCompletedAllAreaQuery->select('posts.*')
                                                // ->limit(10)
            ->selectRaw("{$distance} AS distance")
            ->selectRaw("{$limitByPackage} AS limitByPackage")
            ->whereRaw("{$distance} <= {$limitByPackage}")
            ->groupBy('posts.id');

            $recentCompletedDistanceQuery = $recentCompletedDistanceQuery->orderby('distance')
                                                // ->limit(10)
            ->select('posts.*')
            ->selectRaw("{$distance} AS distance")
            ->selectRaw("{$limitByPackage} AS limitByPackage")
            ->whereRaw("{$distance} <= {$limitByPackage}")
            ->groupBy('posts.id');

            // Around You Random
            $aroundYouRandomQuery = $aroundYouRandomQuery->orderby('rating','desc')
            ->groupBy('posts.id')
            ->select('posts.*')
            ->selectRaw("{$distance} AS distance")
            ->selectRaw("{$limitByPackage} AS limitByPackage")
            ->whereRaw("{$distance} <= {$limitByPackage}")
            ->groupBy('posts.id');

            // Around You Best
            $aroundYouBestQuery = $aroundYouBestQuery->orderby('distance')
            ->orderby('rating')
                                // ->limit(10)
            ->select('posts.*')
            ->selectRaw("{$distance} AS distance")
            ->selectRaw("{$limitByPackage} AS limitByPackage")
            ->whereRaw("{$distance} <= {$limitByPackage}")
            ->groupBy('posts.id');

            $recentCompletedDistanceHospitals = $recentCompletedDistanceQuery->paginate(config('constant.post_pagination_count'),"*","recent_completed_hospitals_distance_page");
            $recentCompletedAllAreaHospitals = $recentCompletedAllAreaQuery->paginate(config('constant.post_pagination_count'),"*","recent_completed_hospitals_all_area_page");
            $hospitals = $hospitalsQuery->paginate(config('constant.post_pagination_count'),"*","all_hospital_post_page");
            $aroundYouRandom = $aroundYouRandomQuery->paginate(config('constant.post_pagination_count'),"*","around_you_random_page");
            $aroundYouBest = $aroundYouBestQuery->paginate(config('constant.post_pagination_count'),"*","around_you_best_page");

            foreach($recentCompletedDistanceHospitals as $h) {
                $h->distance = number_format((float)$h->distance, 1, '.', '');
            }
            foreach($recentCompletedAllAreaHospitals as $h) {
                $h->distance = number_format((float)$h->distance, 1, '.', '');
            }
            foreach($hospitals as $h) {
                $h->distance = number_format((float)$h->distance, 1, '.', '');
            }
            foreach($aroundYouRandom as $h) {
                $h->distance = number_format((float)$h->distance, 1, '.', '');
            }
            foreach($aroundYouBest as $h) {
                $h->distance = number_format((float)$h->distance, 1, '.', '');
            }

            $returnData['recent_completed_hospitals_distance'] = $recentCompletedDistanceHospitals; //$this->hospitalDistanceFilter($recentCompletedDistanceHospitals);
            $returnData['all_hospital_post'] = $hospitals; //$this->hospitalDistanceFilter($hospitals);
            $returnData['recent_completed_hospitals_all_area'] = $recentCompletedAllAreaHospitals;
            $returnData['around_you_random'] = $aroundYouRandom;
            $returnData['around_you_best'] = $aroundYouBest;
            Log::info('End code get all hospital');
            return $this->sendSuccessResponse(Lang::get('messages.post.success'), 200, $returnData);
        } catch (\Exception $e) {
            Log::info('Exception in get all hospital');
            Log::info($e);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function hospitalDistanceFilter($posts) {
        $filteredShop = [];
        $paginateData = $posts->toArray();
        foreach($paginateData['data'] as $key => $post) {
            $hospitalData = Hospital::find($post['hospital_id']);
            $user_id = $hospitalData['user_id'];
            $userdetail = UserDetail::where('user_id',$user_id)->first();
            $planDetail = CreditPlans::where('entity_type_id',EntityTypes::HOSPITAL)->where('package_plan_id',$userdetail->package_plan_id)->first();
            if($planDetail->km >= $post['distance']) {
                $post['distance'] = number_format((float)$post['distance'], 1, '.', '');
                $filteredShop[] = $post;
            } else {
                unset($paginateData['data'][$key]);
            }
        }
        $paginateData['data'] = array_values($filteredShop);
        return $paginateData;
    }

    public function getBestHospitals(Request $request)
    {
        try {
            Log::info('Start code for get all hospital');
            $inputs = $request->all();
            $category_id = isset($inputs['category_id']) ? $inputs['category_id'] : 0;

            $validation = $this->hospitalProfileValidator->validateGetPost($inputs);

            if ($validation->fails()) {
                Log::info('End code for get all hospital');
                return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
            }
            $main_country = getCountryFromLatLong($inputs['latitude'],$inputs['longitude']);

            $allCurrency = Currency::all();
            // Recent Completed All Area
            $recentCompletedAllAreaQuery = DB::table('posts')->join('requested_customer','requested_customer.entity_id','posts.id')
            ->join('hospitals', function ($join) {
                $join->on('posts.hospital_id', '=', 'hospitals.id')->whereNull('hospitals.deleted_at');
            })
            ->leftjoin('addresses', function ($join) {
                $join->on('hospitals.id', '=', 'addresses.entity_id')
                ->where('addresses.entity_type_id', EntityTypes::HOSPITAL);
            })
            ->leftjoin('cities', function ($join) {
                $join->on('addresses.city_id', '=', 'cities.id');
            })
            ->leftjoin('post_images', function ($join) {
                $join->on('posts.id', '=', 'post_images.post_id')
                    ->where('post_images.type',PostImages::THUMBNAIL)
                    ->whereNull('post_images.deleted_at');
            })
            ->select(
                'posts.id',
                'cities.name as city_name',
                'hospitals.main_name as hospital_name',
                'posts.title',
                'posts.sub_title',
                'posts.discount_percentage',
                'posts.final_price',
                'posts.before_price',
                'posts.currency_id',
                'post_images.id as thumbnail_image_id',
                'post_images.image as thumbnail_image'
            )
            ->where('addresses.main_country',$main_country)
            ->where('requested_customer.entity_type_id',EntityTypes::HOSPITAL)
            ->where('requested_customer.request_booking_status_id',RequestBookingStatus::COMPLETE)
            ->where(function($q) {
                $q->where('posts.status_id',Status::ACTIVE);
            })
            ->whereNull('posts.deleted_at');

            // Recent Post
            $recentPostQuery = DB::table('posts')->join('requested_customer','requested_customer.entity_id','posts.id')
            ->join('hospitals', function ($join) {
                $join->on('posts.hospital_id', '=', 'hospitals.id')->whereNull('hospitals.deleted_at');
            })
            ->leftjoin('addresses', function ($join) {
                $join->on('hospitals.id', '=', 'addresses.entity_id')
                ->where('addresses.entity_type_id', EntityTypes::HOSPITAL);
            })
            ->leftjoin('cities', function ($join) {
                $join->on('addresses.city_id', '=', 'cities.id');
            })
            ->leftjoin('post_images', function ($join) {
                $join->on('posts.id', '=', 'post_images.post_id')
                    ->where('post_images.type',PostImages::THUMBNAIL)
                    ->whereNull('post_images.deleted_at');
            })
            ->leftjoin('user_entity_relation', function ($join) {
                $join->on('hospitals.id', '=', 'user_entity_relation.entity_id')
                ->where('user_entity_relation.entity_type_id', EntityTypes::HOSPITAL);
            })
            ->leftjoin('users_detail', function ($join) {
                $join->on('user_entity_relation.user_id', '=', 'users_detail.user_id');
            })
            ->select(
                'posts.id',
                'users_detail.user_id',
                'cities.name as city_name',
                'hospitals.main_name as hospital_name',
                'posts.title',
                'posts.sub_title',
                'posts.discount_percentage',
                'posts.final_price',
                'posts.before_price',
                'posts.currency_id',
                'post_images.id as thumbnail_image_id',
                'post_images.image as thumbnail_image'
            )
            ->selectSub(function($q) {
                $q->select( DB::raw('avg(reviews.rating) as rating'))->from('reviews')->whereNull('reviews.deleted_at')->where('entity_type_id',EntityTypes::HOSPITAL)->whereRaw("`reviews`.`entity_id` = `posts`.`id`");
            }, 'rating')
            ->where('addresses.main_country',$main_country)
            ->where(function($q) {
                $q->where('posts.status_id',Status::ACTIVE);
            })
            ->whereNull('posts.deleted_at');

            //Recent Most Popular
            $recentMostPopularPostQuery = DB::table('posts')->join('requested_customer','requested_customer.entity_id','posts.id')
            ->join('hospitals', function ($join) {
                $join->on('posts.hospital_id', '=', 'hospitals.id')->whereNull('hospitals.deleted_at');
            })
            ->leftjoin('addresses', function ($join) {
                $join->on('hospitals.id', '=', 'addresses.entity_id')
                ->where('addresses.entity_type_id', EntityTypes::HOSPITAL);
            })
            ->leftjoin('cities', function ($join) {
                $join->on('addresses.city_id', '=', 'cities.id');
            })
            ->leftjoin('post_images', function ($join) {
                $join->on('posts.id', '=', 'post_images.post_id')
                    ->where('post_images.type',PostImages::THUMBNAIL)
                    ->whereNull('post_images.deleted_at');
            })
            ->leftjoin('user_entity_relation', function ($join) {
                $join->on('hospitals.id', '=', 'user_entity_relation.entity_id')
                ->where('user_entity_relation.entity_type_id', EntityTypes::HOSPITAL);
            })
            ->leftjoin('users_detail', function ($join) {
                $join->on('user_entity_relation.user_id', '=', 'users_detail.user_id');
            })
            ->select(
                'posts.id',
                'users_detail.user_id',
                'cities.name as city_name',
                'hospitals.main_name as hospital_name',
                'posts.title',
                'posts.sub_title',
                'posts.discount_percentage',
                'posts.final_price',
                'posts.before_price',
                'posts.currency_id',
                'post_images.id as thumbnail_image_id',
                'post_images.image as thumbnail_image'
            )
            ->selectSub(function($q) {
                $q->select( DB::raw('avg(reviews.rating) as rating'))->from('reviews')->whereNull('reviews.deleted_at')->where('entity_type_id',EntityTypes::HOSPITAL)->whereRaw("`reviews`.`entity_id` = `posts`.`id`");
            }, 'rating')
            ->where('addresses.main_country',$main_country)
            ->where('requested_customer.entity_type_id',EntityTypes::HOSPITAL)
            ->where('requested_customer.request_booking_status_id',RequestBookingStatus::COMPLETE)
            ->whereDate('posts.updated_at','>=', Carbon::now()->subDays(14))
            ->where(function($q) {
                $q->where('posts.status_id',Status::ACTIVE);
                                        // ->orWhere('status_id',Status::PENDING);
            })
            ->whereNull('posts.deleted_at');

            //Most Popular
            $mostPopularPostQuery = DB::table('posts')->join('requested_customer','requested_customer.entity_id','posts.id')
            ->join('hospitals', function ($join) {
                $join->on('posts.hospital_id', '=', 'hospitals.id')->whereNull('hospitals.deleted_at');
            })
            ->leftjoin('addresses', function ($join) {
                $join->on('hospitals.id', '=', 'addresses.entity_id')
                ->where('addresses.entity_type_id', EntityTypes::HOSPITAL);
            })
            ->leftjoin('cities', function ($join) {
                $join->on('addresses.city_id', '=', 'cities.id');
            })
            ->leftjoin('post_images', function ($join) {
                $join->on('posts.id', '=', 'post_images.post_id')
                    ->where('post_images.type',PostImages::THUMBNAIL)
                    ->whereNull('post_images.deleted_at');
            })
            ->leftjoin('user_entity_relation', function ($join) {
                $join->on('hospitals.id', '=', 'user_entity_relation.entity_id')
                ->where('user_entity_relation.entity_type_id', EntityTypes::HOSPITAL);
            })
            ->leftjoin('users_detail', function ($join) {
                $join->on('user_entity_relation.user_id', '=', 'users_detail.user_id');
            })
            ->select(
                'posts.id',
                'users_detail.user_id',
                'cities.name as city_name',
                'hospitals.main_name as hospital_name',
                'posts.title',
                'posts.sub_title',
                'posts.discount_percentage',
                'posts.final_price',
                'posts.before_price',
                'posts.currency_id',
                'post_images.id as thumbnail_image_id',
                'post_images.image as thumbnail_image'
            )
            ->selectSub(function($q) {
                $q->select( DB::raw('avg(reviews.rating) as rating'))->from('reviews')->whereNull('reviews.deleted_at')->where('entity_type_id',EntityTypes::HOSPITAL)->whereRaw("`reviews`.`entity_id` = `posts`.`id`");
            }, 'rating')
            ->where('addresses.main_country',$main_country)
            ->where('requested_customer.entity_type_id',EntityTypes::HOSPITAL)
            ->where('requested_customer.request_booking_status_id',RequestBookingStatus::COMPLETE)
            ->whereDate('posts.updated_at', '>=', Carbon::now()->subDays(60))
            ->where(function($q) {
                $q->where('posts.status_id',Status::ACTIVE);
                                        // ->orWhere('status_id',Status::PENDING);
            })
            ->whereNull('posts.deleted_at');

            $maxReviewsPostQuery = DB::table('posts')->join('requested_customer','requested_customer.entity_id','posts.id')
            ->join('hospitals', function ($join) {
                $join->on('posts.hospital_id', '=', 'hospitals.id')->whereNull('hospitals.deleted_at');
            })
            ->leftjoin('addresses', function ($join) {
                $join->on('hospitals.id', '=', 'addresses.entity_id')
                ->where('addresses.entity_type_id', EntityTypes::HOSPITAL);
            })
            ->leftjoin('cities', function ($join) {
                $join->on('addresses.city_id', '=', 'cities.id');
            })
            ->leftjoin('post_images', function ($join) {
                $join->on('posts.id', '=', 'post_images.post_id')
                    ->where('post_images.type',PostImages::THUMBNAIL)
                    ->whereNull('post_images.deleted_at');
            })
            ->leftjoin('user_entity_relation', function ($join) {
                $join->on('hospitals.id', '=', 'user_entity_relation.entity_id')
                ->where('user_entity_relation.entity_type_id', EntityTypes::HOSPITAL);
            })
            ->leftjoin('users_detail', function ($join) {
                $join->on('user_entity_relation.user_id', '=', 'users_detail.user_id');
            })
            ->select(
                'posts.id',
                'users_detail.user_id',
                'cities.name as city_name',
                'hospitals.main_name as hospital_name',
                'posts.title',
                'posts.sub_title',
                'posts.discount_percentage',
                'posts.final_price',
                'posts.before_price',
                'posts.currency_id',
                'post_images.id as thumbnail_image_id',
                'post_images.image as thumbnail_image'
            )
            ->selectSub(function($q) {
                $q->select( DB::raw('avg(reviews.rating) as rating'))->from('reviews')->whereNull('reviews.deleted_at')->where('entity_type_id',EntityTypes::HOSPITAL)->whereRaw("`reviews`.`entity_id` = `posts`.`id`");
            }, 'rating')
            ->selectSub(function($q) {
                $q->select( DB::raw('count(reviews.id) as count'))->from('reviews')->whereNull('reviews.deleted_at')->where('reviews.entity_type_id',EntityTypes::HOSPITAL)->whereRaw("`reviews`.`entity_id` = `posts`.`id`");
            }, 'reviews_count')
            ->where('addresses.main_country',$main_country)
            ->where(function($q) {
                $q->where('posts.status_id',Status::ACTIVE);
            })
            ->whereNull('posts.deleted_at');

            $recommendedPostQuery = DB::table('posts')->join('requested_customer','requested_customer.entity_id','posts.id')
            ->join('hospitals', function ($join) {
                $join->on('posts.hospital_id', '=', 'hospitals.id')->whereNull('hospitals.deleted_at');
            })
            ->join('reviews', function ($join) {
                $join->on('posts.id', '=', 'reviews.entity_id')
                ->where('reviews.entity_type_id', EntityTypes::HOSPITAL);
            })
            ->leftjoin('addresses', function ($join) {
                $join->on('hospitals.id', '=', 'addresses.entity_id')
                ->where('addresses.entity_type_id', EntityTypes::HOSPITAL);
            })
            ->leftjoin('cities', function ($join) {
                $join->on('addresses.city_id', '=', 'cities.id');
            })
            ->leftjoin('post_images', function ($join) {
                $join->on('posts.id', '=', 'post_images.post_id')
                    ->where('post_images.type',PostImages::THUMBNAIL)
                    ->whereNull('post_images.deleted_at');
            })
            ->leftjoin('user_entity_relation', function ($join) {
                $join->on('hospitals.id', '=', 'user_entity_relation.entity_id')
                ->where('user_entity_relation.entity_type_id', EntityTypes::HOSPITAL);
            })
            ->leftjoin('users_detail', function ($join) {
                $join->on('user_entity_relation.user_id', '=', 'users_detail.user_id');
            })
            ->select(
                'posts.id',
                'users_detail.user_id',
                'cities.name as city_name',
                'hospitals.main_name as hospital_name',
                'posts.title',
                'posts.sub_title',
                'posts.discount_percentage',
                'posts.final_price',
                'posts.before_price',
                'posts.currency_id',
                'post_images.id as thumbnail_image_id',
                'post_images.image as thumbnail_image'
            )
            ->selectSub(function($q) {
                $q->select( DB::raw('avg(reviews.rating) as rating'))->from('reviews')->whereNull('reviews.deleted_at')->where('entity_type_id',EntityTypes::HOSPITAL)->whereRaw("`reviews`.`entity_id` = `posts`.`id`");
            }, 'rating')
            ->where('addresses.main_country',$main_country)
            ->where('reviews.rating',5)
            ->where(function($q) {
                $q->where('posts.status_id',Status::ACTIVE);
            })
            ->whereNull('posts.deleted_at');

            if($category_id != 0){
                $recentCompletedAllAreaQuery = $recentCompletedAllAreaQuery->where('posts.category_id',$category_id);
                $recentPostQuery = $recentPostQuery->where('posts.category_id',$category_id);
                $recentMostPopularPostQuery = $recentMostPopularPostQuery->where('posts.category_id',$category_id);
                $mostPopularPostQuery = $mostPopularPostQuery->where('posts.category_id',$category_id);
                $maxReviewsPostQuery = $maxReviewsPostQuery->where('posts.category_id',$category_id);
                $recommendedPostQuery = $recommendedPostQuery->where('posts.category_id',$category_id);
            }

            $recentCompletedAllAreaHospitals = $recentCompletedAllAreaQuery->orderBy('requested_customer.id','desc')
            ->groupBy('posts.id')
            ->paginate(config('constant.post_pagination_count'),"*","recent_completed_all_area_page");

            $recentMostPopularPost = $recentMostPopularPostQuery->orderBy('requested_customer.id','desc')
            ->groupBy('posts.id')
            ->paginate(config('constant.post_pagination_count'),"*","nowadays_popular_page");
            $mostPopularPost = $mostPopularPostQuery->orderBy('requested_customer.id','desc')
            ->groupBy('posts.id')
            ->paginate(config('constant.post_pagination_count'),"*","most_popular_page");

            $recentPost = $recentPostQuery->orderBy('posts.id','desc')
            ->groupBy('posts.id')
            ->paginate(config('constant.post_pagination_count'),"*","recent_updated_page");

            $maxReviewsPost = $maxReviewsPostQuery->orderBy('reviews_count','desc')
            ->orderBy('posts.id','desc')
            ->groupBy('posts.id')
            ->paginate(config('constant.post_pagination_count'),"*","max_reviews_page");
            $recommendedPost = $recommendedPostQuery->orderBy('posts.id','desc')
            ->groupBy('posts.id')
            ->paginate(config('constant.post_pagination_count'),"*","recommended_page");

            $returnData = [
                'recommended' => $this->hospitalFilterNew($recommendedPost, $allCurrency),
                'recent_completed_all_area' => $this->hospitalFilterNew($recentCompletedAllAreaHospitals, $allCurrency),
                'nowadays_popular' => $this->hospitalFilterNew($recentMostPopularPost, $allCurrency),
                'max_reviews' => $this->hospitalFilterNew($maxReviewsPost, $allCurrency),
                'most_popular' => $this->hospitalFilterNew($mostPopularPost, $allCurrency),
                'recent_updated' => $this->hospitalFilterNew($recentPost,$allCurrency),
            ];
            Log::info('End code get all hospital');
            return $this->sendSuccessResponse(Lang::get('messages.post.success'), 200, $returnData);
        } catch (\Exception $e) {
            print_r($e->getMessage());die;
            Log::info('Exception in get all hospital');
            Log::info($e);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function getPostDetail($id)
    {
        try {
            $user = Auth::user();
            Log::info('Start code get post detail');
            Post::where('id',$id)->update(['views_count' => DB::raw('views_count + 1')]);
            $postDetail = Post::where('id',$id)->first();
            Log::info('End code for the get post detail');
            if($postDetail){
                if($user){
                    $postDetail->user_details = ['id' => $user->id, 'status_id' => $user->status_id, 'chat_status' => $user->chat_status];
                }
                return $this->sendSuccessResponse(Lang::get('messages.post.success'), 200, $postDetail);
            }else{
                return $this->sendSuccessResponse(Lang::get('messages.post.empty'), 402);
            }

        } catch (\Exception $e) {
            Log::info('Exception in get post detail');
            Log::info($e);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function getHospitalPosts(Request $request){
        try{
            $inputs = $request->all();
            $user = Auth::user();
            $search = $inputs['search'] ?? '';
            $viewOrder = (isset($inputs['order']) && !empty($inputs['order']) && $inputs['order'] == 'distance') ? true : false;

            $validation = $this->hospitalProfileValidator->validateGetPost($inputs);

            if ($validation->fails()) {
                Log::info('End code for get all hospital');
                return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
            }


            $hospitalData = $this->getHospitalFuncation($inputs,$viewOrder);
            return $this->sendSuccessResponse(Lang::get('messages.post.success'), 200, $hospitalData);
        }catch (\Exception $e) {
            Log::info($e);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function getHospitalFuncation($inputs,$viewOrder)
    {
        $allCurrency = Currency::all();
            $creditPlans = CreditPlans::where('entity_type_id', EntityTypes::HOSPITAL)->get();
            $bronzePlanKm = $silverPlanKm = $goldPlanKm = $platiniumPlanKm = 0;
            foreach($creditPlans as $plan) {
                if($plan->package_plan_id == PackagePlan::BRONZE) {
                    $bronzePlanKm = $plan->km;
                }else if($plan->package_plan_id == PackagePlan::SILVER) {
                    $silverPlanKm = $plan->km;
                }else if($plan->package_plan_id == PackagePlan::GOLD) {
                    $goldPlanKm = $plan->km;
                }else if($plan->package_plan_id == PackagePlan::PLATINIUM) {
                    $platiniumPlanKm = $plan->km;
                }
            }

            $limitByPackage = DB::raw('case when `shops`.`expose_distance` IS NOT NULL then `shops`.`expose_distance`
                when `users_detail`.package_plan_id = '. PackagePlan::BRONZE .' then '.$bronzePlanKm.'
                when `users_detail`.package_plan_id = '. PackagePlan::SILVER .' then '.$silverPlanKm.'
                when `users_detail`.package_plan_id = '. PackagePlan::GOLD .' then '.$goldPlanKm.'
                when `users_detail`.package_plan_id = '. PackagePlan::PLATINIUM .' then '.$platiniumPlanKm.'
                else 40 end ');

        $allHospitalWithPlan = DB::table('hospitals')
                ->join('user_entity_relation','user_entity_relation.entity_id','hospitals.id')
                ->join('users_detail','users_detail.user_id','user_entity_relation.user_id')
                ->join('credit_plans','credit_plans.package_plan_id','users_detail.package_plan_id')
                ->where('user_entity_relation.entity_type_id', EntityTypes::HOSPITAL)
                ->whereNull('hospitals.deleted_at')
                ->select('users_detail.user_id', 'credit_plans.package_plan_id','credit_plans.no_of_posts','credit_plans.no_of_posts', 'user_entity_relation.entity_id as hospital_id')
                ->groupBy('hospitals.id')
                ->get();
            $showPostIds = [];
            foreach($allHospitalWithPlan as $hospitalDetail){
                $postIds = DB::table('posts')->where('hospital_id',$hospitalDetail->hospital_id)->whereNull('posts.deleted_at')->where('status_id',Status::ACTIVE)->limit($hospitalDetail->no_of_posts)->pluck('id')->toArray();
                $showPostIds = array_merge($showPostIds, $postIds);
            }

            $main_country = getCountryFromLatLong($inputs['latitude'],$inputs['longitude']);
            $distance = "(6371 * acos(cos(radians(".$inputs['latitude']."))
                    * cos(radians(addresses.latitude))
                    * cos(radians(addresses.longitude)
                    - radians(".$inputs['longitude']."))
                    + sin(radians(".$inputs['latitude']."))
                    * sin(radians(addresses.latitude))))";

            $hospitalsQuery = DB::table('posts')->join('hospitals', function ($join) {
                $join->on('posts.hospital_id', '=', 'hospitals.id');
            })
            ->leftjoin('addresses', function ($join) {
                $join->on('hospitals.id', '=', 'addresses.entity_id')
                ->where('addresses.entity_type_id', EntityTypes::HOSPITAL);
            })
            ->leftjoin('user_entity_relation', function ($join) {
                $join->on('hospitals.id', '=', 'user_entity_relation.entity_id')
                ->where('user_entity_relation.entity_type_id', EntityTypes::HOSPITAL);
            })
            ->leftjoin('users_detail', function ($join) {
                $join->on('user_entity_relation.user_id', '=', 'users_detail.user_id');
            })
            ->leftjoin('post_images', function ($join) {
                $join->on('posts.id', '=', 'post_images.post_id')
                    ->where('post_images.type',PostImages::THUMBNAIL)
                    ->whereNull('post_images.deleted_at');
            })
            ->where(function($query) use ($showPostIds){
                if(count($showPostIds)){
                  $query->whereIn('posts.id',$showPostIds);
              }
            })
            ->where('addresses.main_country',$main_country)
            ->where(function($q) {
                $q->where('posts.status_id',Status::ACTIVE);
                                        // ->orWhere('status_id',Status::PENDING);
            })->whereNull('posts.deleted_at');

            if (!empty($search)) {
                $hospitalsQuery = $hospitalsQuery->where(function($q) use ($search){
                    $q->where('posts.title', 'LIKE', "%{$search}%");
                });
            }

            if($viewOrder == true){
                $hospitalsQuery = $hospitalsQuery->orderby('distance');
            }else{
                $hospitalsQuery = $hospitalsQuery->orderBy('posts.created_at','DESC');
            }

            $hospitalsQuery = $hospitalsQuery
            ->select('posts.*', 'post_images.id as thumbnail_image_id', 'post_images.image as thumbnail_image','hospitals.main_name as hospital_name')
            ->selectRaw("{$distance} AS distance")
            ->selectRaw("{$limitByPackage} AS limitByPackage")
            ->whereRaw("{$distance} <= {$limitByPackage}");

            $hospitals = $hospitalsQuery->paginate(config('constant.post_pagination_count'),"*","all_hospital_post_page");
            return $hospitalData = $this->hospitalFilterNew($hospitals,$allCurrency);
    }

    public function getAllHospital(Request $request)
    {
        try {
            Log::info('Start code for get all hospital');
            $inputs = $request->all();
            $user = Auth::user();
            $category_id = isset($inputs['category_id']) ? $inputs['category_id'] : 0;
            $validation = $this->hospitalProfileValidator->validateGetPost($inputs);

            if ($validation->fails()) {
                Log::info('End code for get all hospital');
                return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
            }
            $main_country = getCountryFromLatLong($inputs['latitude'],$inputs['longitude']);
            $returnData = [];

            $creditPlans = CreditPlans::where('entity_type_id', EntityTypes::HOSPITAL)->get();
            $bronzePlanKm = $silverPlanKm = $goldPlanKm = $platiniumPlanKm = 0;
            foreach($creditPlans as $plan) {
                if($plan->package_plan_id == PackagePlan::BRONZE) {
                    $bronzePlanKm = $plan->km;
                }else if($plan->package_plan_id == PackagePlan::SILVER) {
                    $silverPlanKm = $plan->km;
                }else if($plan->package_plan_id == PackagePlan::GOLD) {
                    $goldPlanKm = $plan->km;
                }else if($plan->package_plan_id == PackagePlan::PLATINIUM) {
                    $platiniumPlanKm = $plan->km;
                }
            }

            $limitByPackage = DB::raw('case when `shops`.`expose_distance` IS NOT NULL then `shops`.`expose_distance`
                when `users_detail`.package_plan_id = '. PackagePlan::BRONZE .' then '.$bronzePlanKm.'
                when `users_detail`.package_plan_id = '. PackagePlan::SILVER .' then '.$silverPlanKm.'
                when `users_detail`.package_plan_id = '. PackagePlan::GOLD .' then '.$goldPlanKm.'
                when `users_detail`.package_plan_id = '. PackagePlan::PLATINIUM .' then '.$platiniumPlanKm.'
                else 40 end ');

            // Restricted All Posts code
            $allHospitalWithPlan = DB::table('hospitals')
            ->join('user_entity_relation','user_entity_relation.entity_id','hospitals.id')
            ->join('users_detail','users_detail.user_id','user_entity_relation.user_id')
            ->join('credit_plans','credit_plans.package_plan_id','users_detail.package_plan_id')
            ->where('user_entity_relation.entity_type_id', EntityTypes::HOSPITAL)
            ->whereNull('hospitals.deleted_at')
            ->select('users_detail.user_id', 'credit_plans.package_plan_id','credit_plans.no_of_posts','credit_plans.no_of_posts', 'user_entity_relation.entity_id as hospital_id')
            ->groupBy('hospitals.id')
            ->get();
            $showPostIds = [];
            foreach($allHospitalWithPlan as $hospitalDetail){
                $postIds = DB::table('posts')->where('hospital_id',$hospitalDetail->hospital_id)->whereNull('posts.deleted_at')->where('status_id',Status::ACTIVE)->limit($hospitalDetail->no_of_posts)->pluck('id')->toArray();
                $showPostIds = array_merge($showPostIds, $postIds);
            }
            // Restricted All Posts code end

            $allCurrency = Currency::all();
            $distance = "(6371 * acos(cos(radians(".$inputs['latitude']."))
                    * cos(radians(addresses.latitude))
                    * cos(radians(addresses.longitude)
                    - radians(".$inputs['longitude']."))
                    + sin(radians(".$inputs['latitude']."))
                    * sin(radians(addresses.latitude))))";

             // All Hospitals
            /* $hospitalsQuery = DB::table('posts')->join('hospitals', function ($join) {
                $join->on('posts.hospital_id', '=', 'hospitals.id');
            })
            ->leftjoin('addresses', function ($join) {
                $join->on('hospitals.id', '=', 'addresses.entity_id')
                ->where('addresses.entity_type_id', EntityTypes::HOSPITAL);
            })
            ->leftjoin('user_entity_relation', function ($join) {
                $join->on('hospitals.id', '=', 'user_entity_relation.entity_id')
                ->where('user_entity_relation.entity_type_id', EntityTypes::HOSPITAL);
            })
            ->leftjoin('users_detail', function ($join) {
                $join->on('user_entity_relation.user_id', '=', 'users_detail.user_id');
            })
            ->where(function($query) use ($showPostIds){
                if(count($showPostIds)){
                  $query->whereIn('posts.id',$showPostIds);
              }
          })
            ->where('addresses.main_country',$main_country)
            ->where(function($q) {
                $q->where('posts.status_id',Status::ACTIVE);
                                        // ->orWhere('status_id',Status::PENDING);
            })->whereNull('posts.deleted_at'); */

            /* $recentCompletedDistanceQuery = DB::table('posts')->join('requested_customer','requested_customer.entity_id','posts.id')
            ->join('hospitals', function ($join) {
                $join->on('posts.hospital_id', '=', 'hospitals.id');
            })
            ->leftjoin('addresses', function ($join) {
                $join->on('hospitals.id', '=', 'addresses.entity_id')
                ->where('addresses.entity_type_id', EntityTypes::HOSPITAL);
            })
            ->leftjoin('user_entity_relation', function ($join) {
                $join->on('hospitals.id', '=', 'user_entity_relation.entity_id')
                ->where('user_entity_relation.entity_type_id', EntityTypes::HOSPITAL);
            })
            ->leftjoin('users_detail', function ($join) {
                $join->on('user_entity_relation.user_id', '=', 'users_detail.user_id');
            })
            ->where(function($query) use ($showPostIds){
                if(count($showPostIds)){
                  $query->whereIn('posts.id',$showPostIds);
              }
          })
            ->where('addresses.main_country',$main_country)
            ->where(function($q) {
                $q->where('posts.status_id',Status::ACTIVE);
                                        // ->orWhere('status_id',Status::PENDING);
            })
            ->where('requested_customer.entity_type_id',EntityTypes::HOSPITAL)
            ->where('requested_customer.request_booking_status_id',RequestBookingStatus::COMPLETE)->whereNull('posts.deleted_at'); */

            // Recent Completed All Area
            $recentCompletedAllAreaQuery = DB::table('posts')->join('requested_customer','requested_customer.entity_id','posts.id')
                ->join('hospitals', function ($join) {
                    $join->on('posts.hospital_id', '=', 'hospitals.id')->whereNull('hospitals.deleted_at');
                })
                ->leftjoin('addresses', function ($join) {
                    $join->on('hospitals.id', '=', 'addresses.entity_id')
                    ->where('addresses.entity_type_id', EntityTypes::HOSPITAL);
                })
                ->leftjoin('cities', function ($join) {
                    $join->on('addresses.city_id', '=', 'cities.id');
                })
                ->leftjoin('user_entity_relation', function ($join) {
                    $join->on('hospitals.id', '=', 'user_entity_relation.entity_id')
                    ->where('user_entity_relation.entity_type_id', EntityTypes::HOSPITAL);
                })
                ->leftjoin('users_detail', function ($join) {
                    $join->on('user_entity_relation.user_id', '=', 'users_detail.user_id');
                })
                ->leftjoin('post_images', function ($join) {
                    $join->on('posts.id', '=', 'post_images.post_id')
                        ->where('post_images.type',PostImages::THUMBNAIL)
                        ->whereNull('post_images.deleted_at');
                })
                ->select(
                    'posts.id',
                    'users_detail.user_id as user_id',
                    'cities.name as city_name',
                    'hospitals.main_name as hospital_name',
                    'posts.title',
                    'posts.sub_title',
                    'posts.discount_percentage',
                    'posts.final_price',
                    'posts.before_price',
                    'posts.currency_id',
                    'post_images.id as thumbnail_image_id',
                    'post_images.image as thumbnail_image'
                )
                ->selectSub(function($q) {
                    $q->select( DB::raw('avg(reviews.rating) as rating'))->from('reviews')->whereNull('reviews.deleted_at')->where('entity_type_id',EntityTypes::HOSPITAL)->whereRaw("`reviews`.`entity_id` = `posts`.`id`");
                }, 'rating')
                ->where(function($query) use ($showPostIds){
                    if(count($showPostIds)){
                        $query->whereIn('posts.id',$showPostIds);
                    }
                })
                ->where('addresses.main_country',$main_country)
                ->where(function($q) {
                    $q->where('posts.status_id',Status::ACTIVE);
                })
                ->where('requested_customer.entity_type_id',EntityTypes::HOSPITAL)
                ->where('requested_customer.request_booking_status_id',RequestBookingStatus::COMPLETE)
                ->whereNull('posts.deleted_at');

           // Around You Random
            $aroundYouRandomQuery = DB::table('posts')->leftjoin('reviews', function ($join) {
                $join->on('posts.id', '=', 'reviews.entity_id')
                ->where('reviews.entity_type_id', EntityTypes::HOSPITAL);
            })
            ->join('hospitals', function ($join) {
                $join->on('posts.hospital_id', '=', 'hospitals.id')->whereNull('hospitals.deleted_at');
            })
            ->leftjoin('addresses', function ($join) {
                $join->on('hospitals.id', '=', 'addresses.entity_id')
                ->where('addresses.entity_type_id', EntityTypes::HOSPITAL);
            })
            ->leftjoin('cities', function ($join) {
                $join->on('addresses.city_id', '=', 'cities.id');
            })
            ->leftjoin('user_entity_relation', function ($join) {
                $join->on('hospitals.id', '=', 'user_entity_relation.entity_id')
                ->where('user_entity_relation.entity_type_id', EntityTypes::HOSPITAL);
            })
            ->leftjoin('users_detail', function ($join) {
                $join->on('user_entity_relation.user_id', '=', 'users_detail.user_id');
            })
            ->leftjoin('post_images', function ($join) {
                $join->on('posts.id', '=', 'post_images.post_id')
                    ->where('post_images.type',PostImages::THUMBNAIL)
                    ->whereNull('post_images.deleted_at');
            })
            ->select(
                'posts.id',
                'users_detail.user_id as user_id',
                'cities.name as city_name',
                'hospitals.main_name as hospital_name',
                'posts.title',
                'posts.sub_title',
                'posts.discount_percentage',
                'posts.final_price',
                'posts.before_price',
                'posts.currency_id',
                'post_images.id as thumbnail_image_id',
                'post_images.image as thumbnail_image'
            )
            ->selectSub(function($q) {
                $q->select( DB::raw('avg(reviews.rating) as rating'))->from('reviews')->whereNull('reviews.deleted_at')->where('entity_type_id',EntityTypes::HOSPITAL)->whereRaw("`reviews`.`entity_id` = `posts`.`id`");
            }, 'rating')
            ->where(function($query) use ($showPostIds){
                if(count($showPostIds)){
                  $query->whereIn('posts.id',$showPostIds);
              }
          })
            ->where('addresses.main_country',$main_country)
            ->where('posts.status_id',Status::ACTIVE)
            ->whereNull('posts.deleted_at');

        // Around You Best
            $aroundYouBestQuery = DB::table('posts')->join('hospitals','posts.hospital_id', 'hospitals.id')
            ->leftjoin('addresses', function ($join) {
                $join->on('hospitals.id', '=', 'addresses.entity_id')
                ->where('addresses.entity_type_id', EntityTypes::HOSPITAL);
            })
            ->leftjoin('cities', function ($join) {
                $join->on('addresses.city_id', '=', 'cities.id');
            })
            ->leftjoin('reviews', function ($join) {
                $join->on('posts.id', '=', 'reviews.entity_id')
                ->where('reviews.entity_type_id', EntityTypes::HOSPITAL);
            })
            ->leftjoin('user_entity_relation', function ($join) {
                $join->on('hospitals.id', '=', 'user_entity_relation.entity_id')
                ->where('user_entity_relation.entity_type_id', EntityTypes::HOSPITAL);
            })
            ->leftjoin('users_detail', function ($join) {
                $join->on('user_entity_relation.user_id', '=', 'users_detail.user_id');
            })
            ->leftjoin('post_images', function ($join) {
                $join->on('posts.id', '=', 'post_images.post_id')
                    ->where('post_images.type',PostImages::THUMBNAIL)
                    ->whereNull('post_images.deleted_at');
            })
            ->select(
                'posts.id',
                'users_detail.user_id as user_id',
                'cities.name as city_name',
                'hospitals.main_name as hospital_name',
                'posts.title',
                'posts.sub_title',
                'posts.discount_percentage',
                'posts.final_price',
                'posts.before_price',
                'posts.currency_id',
                'post_images.id as thumbnail_image_id',
                'post_images.image as thumbnail_image'
            )
            ->selectSub(function($q) {
                $q->select( DB::raw('avg(reviews.rating) as rating'))->from('reviews')->whereNull('reviews.deleted_at')->where('entity_type_id',EntityTypes::HOSPITAL)->whereRaw("`reviews`.`entity_id` = `posts`.`id`");
            }, 'rating')
            ->where(function($query) use ($showPostIds){
                if(count($showPostIds)){
                  $query->whereIn('posts.id',$showPostIds);
                }
            })
            ->where('addresses.main_country',$main_country)
            ->whereNull('posts.deleted_at')
            ->where('posts.status_id',Status::ACTIVE);

            if($category_id != 0){
                //$hospitalsQuery = $hospitalsQuery->where('posts.category_id',$category_id);
                //$recentCompletedDistanceQuery = $recentCompletedDistanceQuery->where('posts.category_id',$category_id);
                $recentCompletedAllAreaQuery = $recentCompletedAllAreaQuery->where('posts.category_id',$category_id);
                $aroundYouRandomQuery = $aroundYouRandomQuery->where('posts.category_id',$category_id);
                $aroundYouBestQuery = $aroundYouBestQuery->where('posts.category_id',$category_id);
            }

            /* $hospitalsQuery = $hospitalsQuery->orderby('distance')
            ->select('posts.*')
            ->selectRaw("{$distance} AS distance")
            ->selectRaw("{$limitByPackage} AS limitByPackage")
            ->whereRaw("{$distance} <= {$limitByPackage}"); */

            $recentCompletedAllAreaQuery = $recentCompletedAllAreaQuery
                ->selectRaw("{$distance} AS distance")
                ->selectRaw("{$limitByPackage} AS limitByPackage")
                ->whereRaw("{$distance} <= {$limitByPackage}")
                ->groupBy('posts.id');

            /* $recentCompletedDistanceQuery = $recentCompletedDistanceQuery->orderby('distance')
                                                // ->limit(10)
            ->select('posts.*')
            ->selectRaw("{$distance} AS distance")
            ->selectRaw("{$limitByPackage} AS limitByPackage")
            ->whereRaw("{$distance} <= {$limitByPackage}")
            ->groupBy('posts.id'); */

            // Around You Random
            $aroundYouRandomQuery = $aroundYouRandomQuery->orderby('rating','desc')
            ->selectRaw("{$distance} AS distance")
            ->selectRaw("{$limitByPackage} AS limitByPackage")
            ->whereRaw("{$distance} <= {$limitByPackage}")
            ->groupBy('posts.id');

            // Around You Best
            $aroundYouBestQuery = $aroundYouBestQuery->orderby('distance')
            ->orderby('rating')
            ->selectRaw("{$distance} AS distance")
            ->selectRaw("{$limitByPackage} AS limitByPackage")
            ->whereRaw("{$distance} <= {$limitByPackage}")
            ->groupBy('posts.id');

            //$recentCompletedDistanceHospitals = $recentCompletedDistanceQuery->paginate(config('constant.post_pagination_count'),"*","recent_completed_hospitals_distance_page");
            //$hospitals = $hospitalsQuery->paginate(config('constant.post_pagination_count'),"*","all_hospital_post_page");
            $recentCompletedAllAreaHospitals = $recentCompletedAllAreaQuery->paginate(config('constant.post_pagination_count'),"*","recent_completed_hospitals_all_area_page");
            $aroundYouRandom = $aroundYouRandomQuery->paginate(config('constant.post_pagination_count'),"*","around_you_random_page");
            $aroundYouBest = $aroundYouBestQuery->paginate(config('constant.post_pagination_count'),"*","around_you_best_page");

            /* foreach($recentCompletedDistanceHospitals as $h) {
                $h->distance = number_format((float)$h->distance, 1, '.', '');
            }

            foreach($hospitals as $h) {
                $h->distance = number_format((float)$h->distance, 1, '.', '');
            }
            */


            $blankObjectArray = [
                "current_page" => 0,
                "data" => [],
                "total" => 0
            ];

            $returnData['recent_completed_hospitals_distance'] = $blankObjectArray; //$this->hospitalFilter($recentCompletedDistanceHospitals);
            $returnData['all_hospital_post'] = $blankObjectArray; //$this->hospitalFilter($hospitals);
            $returnData['recent_completed_hospitals_all_area'] = $this->hospitalFilterNew($recentCompletedAllAreaHospitals, $allCurrency);
            $returnData['around_you_random'] = $this->hospitalFilterNew($aroundYouRandom, $allCurrency);
            $returnData['around_you_best'] = $this->hospitalFilterNew($aroundYouBest,$allCurrency);
            Log::info('End code get all hospital');
            return $this->sendSuccessResponse(Lang::get('messages.post.success'), 200, $returnData);
        } catch (\Exception $e) {
            //print_r($e->getMessage());die;
            Log::info('Exception in get all hospital');
            Log::info($e);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function hospitalFilterNew($posts, $allCurrency) {
        $postsData = $posts->toArray();

        foreach($postsData['data'] as $key => $post) {
            $post->location = (property_exists($post,'city_name')) ? ["city_name" => $post->city_name] : '' ;
            $post->location_data = (property_exists($post,'hospital_id')) ? Address::where('entity_type_id', EntityTypes::HOSPITAL)->where('entity_id',$post->hospital_id)->first() : null;
            $currency = $allCurrency->where('id',$post->currency_id)->first();
            $post->currency_name = ($currency) ? $currency->name : '';

            $post->final_price = number_format($post->final_price,0);
            $post->before_price = number_format($post->before_price,0);

            if(property_exists($post,'distance')){
                $post->distance = number_format((float)$post->distance, 1, '.', '');
            }
            $post->rating = property_exists($post,'rating') ? number_format($post->rating,1) : "0";

            if(property_exists($post,'thumbnail_image')){
                $post->thumbnail_url = ["image" => Storage::disk('s3')->url($post->thumbnail_image), "id" => $post->thumbnail_image_id];
            }else{
                $post->thumbnail_url = (object)[];
            }

            $mainImages = PostImages::where('post_id', $post->id)->where('type',PostImages::MAINPHOTO)->groupBy('post_language_id')->get();
            $images = [];
            foreach($mainImages as $image){
                $temp['language_id'] = $image->post_language_id;
                $language = PostLanguage::find($image->post_language_id);
                $temp['language_name'] = $language ? $language->name : '';
                $temp['language_icon'] = $language ? $language->icon : '';
                $images[] = $temp;
            }

            $post->main_images = $images;

        }

        return $postsData;

    }
}
