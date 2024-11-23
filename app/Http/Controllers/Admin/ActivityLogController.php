<?php

namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use App\Models\Status;
use App\Models\User;
use App\Models\EntityTypes;
use App\Models\Category;
use App\Models\Hospital;
use App\Models\Shop;
use App\Models\UserCredit;
use App\Models\UserCreditHistory;
use App\Models\UserDetail;
use App\Models\CategoryTypes;
use App\Models\Reviews;
use App\Models\ActivityLog;
use App\Models\RequestBookingStatus;
use App\Models\Country;
use Illuminate\Support\Facades\DB;
use Log;
use Carbon\Carbon;

class ActivityLogController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:activity-log-list', ['only' => ['index','indexHospital','indexShop','indexCustom']]);
    }

    public function index(Request $request)
    {
        $title = 'All Logs';
        $dateFilter = $request->has('date') ? (int)$request->date : null;
        $countryId = $request->has('countryId') ? $request->countryId : null;
        $shopCategories = Category::where('type', 'default')->where('category_type_id', CategoryTypes::SHOP)->get();
        $hospitalCategories = Category::where('type', 'default')->where('category_type_id',CategoryTypes::HOSPITAL)->whereNotIn('parent_id', [0])->get();
        $customCategories = Category::where('category_type_id',CategoryTypes::CUSTOM)->get();
        $countries = Country::WhereNotNull('code')->where('is_show',1)->get();

        $categories = $shopCategories->concat($hospitalCategories)->concat($customCategories);
        $pageType = 'all';

        return view('admin.activity-log.index-logs', compact('title','categories','countries','pageType'));
    }

    public function indexHospital(Request $request)
    {
        $title = "Hospital Logs";

        $dateFilter = $request->has('date') ? (int)$request->date : null;
        $countryId = $request->has('countryId') ? $request->countryId : null;
        $categories = Category::where('type', 'default')->where('category_type_id',CategoryTypes::HOSPITAL)->whereNotIn('parent_id', [0])->get();
        $countries = Country::WhereNotNull('code')->where('is_show',1)->get();

        //$allLogsQuery = ActivityLog::where('entity_type_id',EntityTypes::HOSPITAL);
        $pageType = EntityTypes::HOSPITAL;

        return view('admin.activity-log.index-logs', compact('title','categories','countries','pageType'));
    }
    public function indexShop(Request $request)
    {
        $title = "Shop Logs";

        $dateFilter = $request->has('date') ? (int)$request->date : null;
        $countryId = $request->has('countryId') ? $request->countryId : null;
        $categories = Category::where('type', 'default')->where('category_type_id', CategoryTypes::SHOP)->get();
        $countries = Country::WhereNotNull('code')->where('is_show',1)->get();

        $pageType = EntityTypes::SHOP;
        return view('admin.activity-log.index-logs', compact('title','categories','countries', 'pageType'));
    }

    public function getJsonAllData(Request $request)
    {
        try {
            $columns = array(
                0 => 'request_booking_status_name',
                1 => 'user_name',
                2 => 'business_user_name',
                3 => 'business_name',
                4 => 'business_address',
                5 => 'created_at'
            );

            $statusFilers = array(
                'all' => 'all',
                'inquire' => RequestBookingStatus::TALK,
                'book' => RequestBookingStatus::BOOK,
                'visited' => RequestBookingStatus::VISIT,
                'noshow' => RequestBookingStatus::NOSHOW,
                'cancelBusiness' => RequestBookingStatus::CANCEL,
                'cancelUser' => RequestBookingStatus::CANCEL,
                'complete' => RequestBookingStatus::COMPLETE,
                'reviews' => null,
            );

            $pageType = $request->input('pageType');
            $categoryFilter = $request->input('categoryFilter');
            $countryFilter = $request->input('countryFilter');
            $dateFilter = $request->input('dateFilter');
            $status = $request->input('status');
            $filterStatus = $statusFilers[$status];

            $limit = $request->input('length');
            $start = $request->input('start');
            $search = $request->input('search.value');
            $draw = $request->input('draw');
            $order = $columns[$request->input('order.0.column')];
            $dir = $request->input('order.0.dir');
            $adminTimezone = $this->getAdminUserTimezone();

            if($pageType != 'all'){
                if($pageType == 'custom'){
                    $allLogsQuery = ActivityLog::join('shops','shops.id','activity_log.entity_id')
                                    ->join('category','category.id','shops.category_id')
                                    ->where('category.category_type_id', CategoryTypes::CUSTOM)
                                    ->where('entity_type_id',EntityTypes::SHOP)->select('activity_log.*')
                                    ->select('*');
                }else{
                    $allLogsQuery = ActivityLog::where('entity_type_id',$pageType);
                }
            }else{
                $allLogsQuery = ActivityLog::select('*');
            }


            if($countryFilter != 'all'){
                $allLogsQuery = $allLogsQuery->where('country',$countryFilter);
            }
            if($dateFilter != 'all'){
                $allLogsQuery->whereDate('created_at','>=',Carbon::now()->subMonths((int)$dateFilter));
            }

            $allLogsCount = $allLogsQuery->get();
            if($categoryFilter != 'all'){
                $allLogsCount = collect($allLogsCount)->where('category_id',$categoryFilter);
            }
            if(!empty($search)){
                $allLogsCount = collect($allLogsCount)->filter(function ($item) use ($search) {
                    return false !== stripos($item->user_name, $search) ||
                            false !== stripos($item->business_user_name, $search) ||
                            false !== stripos($item->business_name, $search);
                })->values();
            }

            $statusCount = array(
                'all' => '', //number_format($allLogsCount->count(), 0, '.', ','),
                'inquire' => number_format($allLogsCount->where('request_booking_status_id',$statusFilers['inquire'])->count(), 0, '.', ','),
                'book' => number_format($allLogsCount->where('request_booking_status_id',$statusFilers['book'])->count(), 0, '.', ','),
                'visited' => number_format($allLogsCount->where('request_booking_status_id',$statusFilers['visited'])->count(), 0, '.', ','),
                'noshow' => number_format($allLogsCount->where('request_booking_status_id',$statusFilers['noshow'])->count(), 0, '.', ','),
                'cancelBusiness' => number_format($allLogsCount->where('request_booking_status_id',$statusFilers['cancelBusiness'])->where('is_cancelled_by_shop',1)->count(), 0, '.', ','),
                'cancelUser' => number_format($allLogsCount->where('request_booking_status_id',$statusFilers['cancelUser'])->where('is_cancelled_by_shop',0)->count(), 0, '.', ','),
                'complete' => number_format($allLogsCount->where('request_booking_status_id',$statusFilers['complete'])->count(), 0, '.', ','),
                'reviews' => number_format($allLogsCount->where('request_booking_status_id',$statusFilers['reviews'])->count(), 0, '.', ','),
            );

            if($filterStatus != 'all'){
                $allLogsQuery->where('request_booking_status_id',$filterStatus);
                if($status == 'cancelBusiness'){
                    $allLogsQuery->where('is_cancelled_by_shop',1);
                }else if($status == 'cancelUser'){
                    $allLogsQuery->where('is_cancelled_by_shop',0);
                }
            }

            $allLogs = $allLogsQuery
                    //->offset($start)
                    //->limit($limit)
                    ->get();

            if($dir == 'asc'){
                $allLogs = collect($allLogs)->sortBy($order);
            }else{
                $allLogs = collect($allLogs)->sortByDesc($order);
            }

            if($categoryFilter != 'all'){
                $allLogs = collect($allLogs)->where('category_id',$categoryFilter);
            }

            if(!empty($search)){
                $allLogs = collect($allLogs)->filter(function ($item) use ($search) {
                    return false !== stripos($item->user_name, $search) ||
                            false !== stripos($item->business_user_name, $search) ||
                            false !== stripos($item->business_name, $search);
                })->values();
            }

            $totalData = count($allLogs);
            $totalFiltered = $totalData;

            $allLogs = collect($allLogs)->slice($start, $limit);

            $data = array();
            foreach($allLogs as $logs){
               $nestedData['request_booking_status_name'] = $logs->request_booking_status_name;
               $nestedData['user_name'] = $logs->user_name;
               $nestedData['business_user_name'] = $logs->business_user_name;
               $nestedData['business_name'] = $logs->business_name;
               $nestedData['business_address'] = $logs->business_address;
               $nestedData['created_at'] = $this->formatDateTimeCountryWise($logs->created_at,$adminTimezone);

               $data[] = $nestedData;
            }

            $jsonData = array(
                "draw" => intval($draw),
                "recordsTotal" => intval($totalData),
                "recordsFiltered" => intval($totalFiltered),
                "data" => $data,
                "statusCount" => $statusCount,
            );
            Log::info('End all hospital list');
            return response()->json($jsonData);
        } catch (\Exception $ex) {
            Log::info('Exception all hospital list');
            Log::info($ex);
            $jsonData = array(
                "draw" => intval(0),
                "recordsTotal" => intval(0),
                "recordsFiltered" => intval(0),
                "data" => []
            );

            return response()->json($jsonData);
        }
    }

    public function indexCustom(Request $request)
    {
        $title = "Suggest Business Logs";

        $dateFilter = $request->has('date') ? (int)$request->date : null;
        $countryId = $request->has('countryId') ? $request->countryId : null;
        $categories = Category::where('category_type_id',CategoryTypes::CUSTOM)->get();
        $countries = Country::WhereNotNull('code')->where('is_show',1)->get();

        $pageType = 'custom';
        return view('admin.activity-log.index-logs', compact('title','categories','countries', 'pageType'));

        /* $allLogsQuery = ActivityLog::join('shops','shops.id','activity_log.entity_id')
                                ->join('category','category.id','shops.category_id')
                                ->where('category.category_type_id', CategoryTypes::CUSTOM)
                                ->where('entity_type_id',EntityTypes::SHOP)->select('activity_log.*')
                                ->select('*');
        $inquireLogsQuery = ActivityLog::join('shops','shops.id','activity_log.entity_id')
                                    ->join('category','category.id','shops.category_id')
                                    ->where('category.category_type_id', CategoryTypes::CUSTOM)
                                    ->where('entity_type_id',EntityTypes::SHOP)
                                    ->where('request_booking_status_id',RequestBookingStatus::TALK)->select('activity_log.*');

        $bookLogsQuery = ActivityLog::join('shops','shops.id','activity_log.entity_id')
                                ->join('category','category.id','shops.category_id')
                                ->where('category.category_type_id', CategoryTypes::CUSTOM)
                                ->where('entity_type_id',EntityTypes::SHOP)
                                ->where('request_booking_status_id',RequestBookingStatus::BOOK)->select('activity_log.*');

        $visitedLogsQuery = ActivityLog::join('shops','shops.id','activity_log.entity_id')
                                    ->join('category','category.id','shops.category_id')
                                    ->where('category.category_type_id', CategoryTypes::CUSTOM)
                                    ->where('entity_type_id',EntityTypes::SHOP)
                                    ->where('request_booking_status_id',RequestBookingStatus::VISIT)->select('activity_log.*');

        $noshowLogsQuery = ActivityLog::join('shops','shops.id','activity_log.entity_id')
                                    ->join('category','category.id','shops.category_id')
                                    ->where('category.category_type_id', CategoryTypes::CUSTOM)
                                    ->where('entity_type_id',EntityTypes::SHOP)
                                    ->where('request_booking_status_id',RequestBookingStatus::NOSHOW)->select('activity_log.*');

        $cancelBusinessLogsQuery = ActivityLog::join('shops','shops.id','activity_log.entity_id')
                                    ->join('category','category.id','shops.category_id')
                                    ->where('category.category_type_id', CategoryTypes::CUSTOM)
                                    ->where('entity_type_id',EntityTypes::SHOP)
                                    ->where('request_booking_status_id',RequestBookingStatus::CANCEL)
                                    ->where('is_cancelled_by_shop',1)->select('activity_log.*');

        $cancelUserLogsQuery = ActivityLog::join('shops','shops.id','activity_log.entity_id')
                                    ->join('category','category.id','shops.category_id')
                                    ->where('category.category_type_id', CategoryTypes::CUSTOM)
                                    ->where('entity_type_id',EntityTypes::SHOP)
                                    ->where('request_booking_status_id',RequestBookingStatus::CANCEL)
                                    ->where('is_cancelled_by_shop',0)->select('activity_log.*');

        $completeLogsQuery = ActivityLog::join('shops','shops.id','activity_log.entity_id')
                                    ->join('category','category.id','shops.category_id')
                                    ->where('category.category_type_id', CategoryTypes::CUSTOM)
                                    ->where('entity_type_id',EntityTypes::SHOP)
                                    ->where('request_booking_status_id',RequestBookingStatus::COMPLETE);

        $reviewLogsQuery = ActivityLog::join('shops','shops.id','activity_log.entity_id')
                                    ->join('category','category.id','shops.category_id')
                                    ->where('category.category_type_id', CategoryTypes::CUSTOM)
                                    ->where('entity_type_id',EntityTypes::SHOP)
                                    ->where('request_booking_status_id',NULL)->select('activity_log.*');

        if($countryId) {
            $allLogsQuery = $allLogsQuery->where('country',$countryId);
            $inquireLogsQuery = $inquireLogsQuery->where('country',$countryId);
            $bookLogsQuery = $bookLogsQuery->where('country',$countryId);
            $visitedLogsQuery = $visitedLogsQuery->where('country',$countryId);
            $noshowLogsQuery = $noshowLogsQuery->where('country',$countryId);
            $cancelBusinessLogsQuery = $cancelBusinessLogsQuery->where('country',$countryId);
            $cancelUserLogsQuery = $cancelUserLogsQuery->where('country',$countryId);
            $completeLogsQuery = $completeLogsQuery->where('country',$countryId);
            $reviewLogsQuery = $reviewLogsQuery->where('country',$countryId);
        }

        if($dateFilter) {
            $allLogsQuery = $allLogsQuery->whereDate('activity_log.created_at',Carbon::now()->subMonths($dateFilter));
            $inquireLogsQuery = $inquireLogsQuery->whereDate('activity_log.created_at',Carbon::now()->subMonths($dateFilter));
            $bookLogsQuery = $bookLogsQuery->whereDate('activity_log.created_at',Carbon::now()->subMonths($dateFilter));
            $visitedLogsQuery = $visitedLogsQuery->whereDate('activity_log.created_at',Carbon::now()->subMonths($dateFilter));
            $noshowLogsQuery = $noshowLogsQuery->whereDate('activity_log.created_at',Carbon::now()->subMonths($dateFilter));
            $cancelBusinessLogsQuery = $cancelBusinessLogsQuery->whereDate('activity_log.created_at',Carbon::now()->subMonths($dateFilter));
            $cancelUserLogsQuery = $cancelUserLogsQuery->whereDate('activity_log.created_at',Carbon::now()->subMonths($dateFilter));
            $completeLogsQuery = $completeLogsQuery->whereDate('activity_log.created_at',Carbon::now()->subMonths($dateFilter));
            $reviewLogsQuery = $reviewLogsQuery->whereDate('activity_log.created_at',Carbon::now()->subMonths($dateFilter));
        }

        $allLogs = $allLogsQuery->get();
        $inquireLogs = $inquireLogsQuery->get();
        $bookLogs = $bookLogsQuery->get();
        $visitedLogs = $visitedLogsQuery->get();
        $noshowLogs = $noshowLogsQuery->get();
        $cancelBusinessLogs = $cancelBusinessLogsQuery->get();
        $cancelUserLogs = $cancelUserLogsQuery->get();
        $completeLogs = $completeLogsQuery->get();
        $reviewLogs = $reviewLogsQuery->get();

        return view('admin.activity-log.index-category', compact('title','categories','countries','allLogs','inquireLogs','bookLogs','visitedLogs','noshowLogs','cancelBusinessLogs','cancelUserLogs','completeLogs','reviewLogs')); */
    }

}
