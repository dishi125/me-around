<?php

namespace App\Http\Controllers\Admin;
use QrCode;
use Carbon\Carbon;
use App\Models\User;
use App\Models\PostClicks;
use App\Models\EntityTypes;
use Illuminate\Http\Request;
use App\Models\UserCreditHistory;
use App\Models\UserEntityRelation;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $title = "Overview";
        $current_date_encoded = base64_encode(Carbon::now()->toDateString());
        $type = 0;
        $entity_ids = $type == 0 ? [EntityTypes::SHOP,EntityTypes::HOSPITAL] : [(int)$type];
        $user_entity_data = UserEntityRelation::whereIn('entity_type_id',$entity_ids)->pluck('user_id')->toArray();

        $yearIncome = UserCreditHistory::whereIn('user_id',$user_entity_data)
                                                ->where('type', UserCreditHistory::RELOAD)
                                                ->whereYear('created_at',Carbon::now())
                                                ->sum('amount');

        $monthIncome = UserCreditHistory::whereIn('user_id',$user_entity_data)
                                                ->where('type', UserCreditHistory::RELOAD)
                                                ->whereMonth('created_at',Carbon::now())
                                                ->whereYear('created_at',Carbon::now())
                                                ->sum('amount');

        $dayIncome = UserCreditHistory::whereIn('user_id',$user_entity_data)
                                                ->where('type', UserCreditHistory::RELOAD)
                                                ->whereDate('created_at',Carbon::now())
                                                ->sum('amount');

        $selectedCurrentYear = $request->get('year');
        if(empty($selectedCurrentYear)){
            $selectedCurrentYear = Carbon::now()->format('Y');
        }
        $postClicks = $this->getPostClickData($selectedCurrentYear);
        $outside_data = $postClicks['outside_data'];
        $shop_data = $postClicks['shop_data'];
        $hospital_data = $postClicks['hospital_data'];
        $call_data = $postClicks['call_data'];
        $book_data = $postClicks['book_data'];
        $months = $postClicks['months'];

        return view('admin.dashboard.index', compact('title','yearIncome','monthIncome','dayIncome','type','current_date_encoded','outside_data','shop_data','hospital_data','months','selectedCurrentYear','call_data','book_data'));
    }

    public function indexHospital(Request $request)
    {
        $title = "Overview Hospital";
        $current_date_encoded = base64_encode(Carbon::now()->toDateString());
        $type = 2;
        $yearIncome = [
            'income' => '50,000,000',
            'percent' => '13.8%'
        ];
        $entity_ids = $type == 0 ? [EntityTypes::SHOP,EntityTypes::HOSPITAL] : [(int)$type];
        $user_entity_data = UserEntityRelation::whereIn('entity_type_id',$entity_ids)->pluck('user_id')->toArray();

        $yearIncome = UserCreditHistory::whereIn('user_id',$user_entity_data)
                                                ->where('type', UserCreditHistory::RELOAD)
                                                ->whereYear('created_at',Carbon::now())
                                                ->sum('amount');

        $monthIncome = UserCreditHistory::whereIn('user_id',$user_entity_data)
                                                ->where('type', UserCreditHistory::RELOAD)
                                                ->whereMonth('created_at',Carbon::now())
                                                ->whereYear('created_at',Carbon::now())
                                                ->sum('amount');

        $dayIncome = UserCreditHistory::whereIn('user_id',$user_entity_data)
                                                ->where('type', UserCreditHistory::RELOAD)
                                                ->whereDate('created_at',Carbon::now())
                                                ->sum('amount');

        $selectedCurrentYear = $request->get('year');
        if(empty($selectedCurrentYear)){
            $selectedCurrentYear = Carbon::now()->format('Y');
        }

        $currentYear = Carbon::now()->format('Y');
        $postClicks = $this->getPostClickData($currentYear);
        $outside_data = $postClicks['outside_data'];
        $shop_data = $postClicks['shop_data'];
        $hospital_data = $postClicks['hospital_data'];
        $months = $postClicks['months'];

        return view('admin.dashboard.index', compact('title','yearIncome','monthIncome','dayIncome','type','current_date_encoded','outside_data','shop_data','hospital_data','months','selectedCurrentYear'));
    }

    public function indexShop(Request $request)
    {
        $title = "Overview Shop";
        $type = 1;
        $current_date_encoded = base64_encode(Carbon::now()->toDateString());
        $entity_ids = $type == 0 ? [EntityTypes::SHOP,EntityTypes::HOSPITAL] : [(int)$type];
        $user_entity_data = UserEntityRelation::whereIn('entity_type_id',$entity_ids)->pluck('user_id')->toArray();

        $yearIncome = UserCreditHistory::whereIn('user_id',$user_entity_data)
                                                ->where('type', UserCreditHistory::RELOAD)
                                                ->whereYear('created_at',Carbon::now())
                                                ->sum('amount');

        $monthIncome = UserCreditHistory::whereIn('user_id',$user_entity_data)
                                                ->where('type', UserCreditHistory::RELOAD)
                                                ->whereMonth('created_at',Carbon::now())
                                                ->whereYear('created_at',Carbon::now())
                                                ->sum('amount');

        $dayIncome = UserCreditHistory::whereIn('user_id',$user_entity_data)
                                                ->where('type', UserCreditHistory::RELOAD)
                                                ->whereDate('created_at',Carbon::now())
                                                ->sum('amount');

        $selectedCurrentYear = $request->get('year');
        if(empty($selectedCurrentYear)){
            $selectedCurrentYear = Carbon::now()->format('Y');
        }
        $currentYear = Carbon::now()->format('Y');
        $postClicks = $this->getPostClickData($currentYear);
        $outside_data = $postClicks['outside_data'];
        $shop_data = $postClicks['shop_data'];
        $hospital_data = $postClicks['hospital_data'];
        $call_data = $postClicks['call_data'];
        $book_data = $postClicks['book_data'];
        $months = $postClicks['months'];

        return view('admin.dashboard.index', compact('title','yearIncome','monthIncome','dayIncome','type','current_date_encoded','outside_data','shop_data','hospital_data','months','selectedCurrentYear','call_data','book_data'));
    }

    public function getPostClickData($currentYear)
    {
        $months = ["Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec"];


        $outside_data = [];
        for($i = 1; $i <= 12; $i++)
        {
            $temp['month'] = $i;
            $temp['count'] = PostClicks::where('type',PostClicks::OUTSIDE)->whereMonth('created_at',$i)->whereYear('created_at',$currentYear)->count();
            $outside_data[$i] = $temp;
        }

        $shop_data = [];
        for($i = 1; $i <= 12; $i++)
        {
            $temp['month'] = $i;
            $temp['count'] = PostClicks::where('type',PostClicks::SHOP)->whereMonth('created_at',$i)->whereYear('created_at',$currentYear)->count();
            $shop_data[$i] = $temp;
        }

        $hospital_data = [];
        for($i = 1; $i <= 12; $i++)
        {
            $temp['month'] = $i;
            $temp['count'] = PostClicks::where('type',PostClicks::HOSPITAL)->whereMonth('created_at',$i)->whereYear('created_at',$currentYear)->count();
            $hospital_data[$i] = $temp;
        }

        $call_data = [];
        for($i = 1; $i <= 12; $i++)
        {
            $temp['month'] = $i;
            $temp['count'] = PostClicks::where('type',PostClicks::CALL)->whereMonth('created_at',$i)->whereYear('created_at',$currentYear)->count();
            $call_data[$i] = $temp;
        }

        $book_data = [];
        for($i = 1; $i <= 12; $i++)
        {
            $temp['month'] = $i;
            $temp['count'] = PostClicks::where('type',PostClicks::BOOK)->whereMonth('created_at',$i)->whereYear('created_at',$currentYear)->count();
            $book_data[$i] = $temp;
        }

        $data = [
            'outside_data' => $outside_data,
            'shop_data' => $shop_data,
            'hospital_data' => $hospital_data,
            'call_data' => $call_data,
            'book_data' => $book_data,
            'months' => $months,
        ];
        return $data;
    }

    public function getYearDetail($type)
    {
        $title = "Years Overview";
        $current_date_encoded = base64_encode(Carbon::now()->toDateString());
        $entity_ids = $type == 0 ? [EntityTypes::SHOP,EntityTypes::HOSPITAL] : [(int)$type];
        $user_entity_data = UserEntityRelation::whereIn('entity_type_id',$entity_ids)->pluck('user_id')->toArray();

        $years = range(Carbon::now()->year, Carbon::now()->subYears(1)->year);
        $yearsData = [];
        for($i = 0; $i < count($years); $i++) {
            $date = '01-01'.'-'.$years[$i];
            $month_start = Carbon::createFromFormat('d-m-Y',$date);
            $yearIncome = UserCreditHistory::whereIn('user_id',$user_entity_data)
                                                ->where('type', UserCreditHistory::RELOAD)
                                                ->whereYear('created_at',$month_start)
                                                ->sum('amount');
            $temp = [];
            $temp['year'] = $years[$i];
            $temp['date'] = base64_encode($month_start->toDateString());
            $temp['income'] =  number_format($yearIncome);
            $yearsData[$years[$i]]= $temp;
        }

        return view('admin.dashboard.year-detail', compact('title','yearsData','type','current_date_encoded'));
    }

    public function getMonthDetail($type,$date)
    {
        $title = "Month Overview";
        $current_date_encoded = base64_encode(Carbon::now()->toDateString());
        $entity_ids = $type == 0 ? [EntityTypes::SHOP,EntityTypes::HOSPITAL] : [(int)$type];
        $user_entity_data = UserEntityRelation::whereIn('entity_type_id',$entity_ids)->pluck('user_id')->toArray();
        $years = range(Carbon::now()->year, Carbon::now()->subYears(1)->year);

        $decoded_date = base64_decode($date);
        $current_date = Carbon::createFromFormat('Y-m-d',$decoded_date);
        $current_year = $current_date->year;

        $years_data = [];
        for($j = 0; $j < count($years); $j++) {
            for($i = 1; $i <= 12; $i++) {
                $date = '01-'.$i.'-'.$years[$j];
                $month_start = Carbon::createFromFormat('d-m-Y',$date);

                $monthIncome = UserCreditHistory::whereIn('user_id',$user_entity_data)
                                                ->where('type', UserCreditHistory::RELOAD)
                                                ->whereMonth('created_at',$month_start)
                                                ->whereYear('created_at',$month_start)
                                                ->sum('amount');

                $temp = [];
                $temp['month'] = $i;
                $temp['date'] = base64_encode($month_start->toDateString());
                $temp['income'] =  number_format($monthIncome);
                $years_data[$years[$j]][$i] = $temp;
            }
        }

        return view('admin.dashboard.month-detail', compact('title','years_data','type','years','current_year','current_date_encoded'));
    }

    public function getDayDetail($type,$date)
    {
        $title = "Month Overview";
        $current_date_encoded = base64_encode(Carbon::now()->toDateString());
        $entity_ids = $type == 0 ? [EntityTypes::SHOP,EntityTypes::HOSPITAL] : [(int)$type];
        $decoded_date = base64_decode($date);

        $current_date = Carbon::createFromFormat('Y-m-d',$decoded_date);
        $current_month = $current_date->month;
        $months_data = [];
        $month_start = $current_date->firstOfMonth()->subDay();
        for($j = 1; $j <= 12; $j++) {
            $date = '01-'.$j.'-'.$current_date->year;
            $month_start = Carbon::createFromFormat('d-m-Y',$date)->subDay();
            $number_of_days_in_month = cal_days_in_month(CAL_GREGORIAN,$j,$current_date->year);
            for($i = 1; $i <= $number_of_days_in_month; $i++) {
                $test = $month_start;
                $date = $test->addDay();
                $user_entity_data = UserEntityRelation::whereIn('entity_type_id',$entity_ids)->pluck('user_id')->toArray();
                $dayIncome = UserCreditHistory::whereIn('user_id',$user_entity_data)
                                                ->where('type', UserCreditHistory::RELOAD)
                                                ->whereDate('created_at',$date)
                                                ->sum('amount');

                $temp = [];
                $temp['day'] = $i;
                $temp['date'] = base64_encode($date->toDateString());
                $temp['income'] =  number_format($dayIncome);
                $months_data[$j][$i] = $temp;
            }
        }

        return view('admin.dashboard.day-detail', compact('title','months_data','type','current_month','current_date_encoded'));
    }

    public function getAllDayDetail($type,$date)
    {
        $title = "Day Overview";
        $current_date_encoded = base64_encode(Carbon::now()->toDateString());
        $decoded_date = base64_decode($date);
        $compare_date = Carbon::createFromFormat('Y-m-d',$decoded_date);
        $entity_ids = $type == 0 ? [EntityTypes::SHOP,EntityTypes::HOSPITAL] : [(int)$type];
        $dayIncome = UserCreditHistory::leftjoin('user_entity_relation','user_entity_relation.user_id','user_credits_history.user_id')
                                        ->join('users','user_entity_relation.user_id','users.id')
                                        ->join('users_detail','users_detail.user_id','users.id')
                                        ->leftjoin('managers','managers.id','users_detail.manager_id')
                                        ->leftjoin('shops', function($query) {
                                            $query->on('users.id','=','shops.user_id')
                                            ->whereRaw('shops.id IN (select MAX(a2.id) from shops as a2 group by a2.user_id)');
                                        })
                                        ->leftjoin('category as shop_category','shop_category.id','shops.category_id')
                                        ->leftjoin('hospitals','user_entity_relation.entity_id','hospitals.id')
                                        ->leftjoin('category as hospital_category','hospital_category.id','hospitals.category_id')
                                        ->whereIn('entity_type_id',$entity_ids)
                                        ->where('user_credits_history.type', UserCreditHistory::RELOAD)
                                        ->whereDate('user_credits_history.created_at',$compare_date)
                                        ->select(
                                            'user_credits_history.*',
                                            'user_entity_relation.entity_type_id',
                                            'users_detail.name as user_name',
                                            'users_detail.mobile',
                                            \DB::raw('(CASE
                                            WHEN user_entity_relation.entity_type_id = 1 THEN  shops.main_name
                                            WHEN user_entity_relation.entity_type_id = 2 THEN hospitals.main_name
                                            ELSE ""
                                            END) AS main_name'),
                                            \DB::raw('(CASE
                                            WHEN user_entity_relation.entity_type_id = 1 THEN  shop_category.name
                                            WHEN user_entity_relation.entity_type_id = 2 THEN hospital_category.name
                                            ELSE ""
                                            END) AS category_name'),
                                            'managers.name as manager_name'
                                        )
                                        ->groupBy('user_credits_history.id')
                                        ->get();
        foreach($dayIncome as $income) {
            $total_income = UserCreditHistory::where('user_id',$income->user_id) ->where('type', UserCreditHistory::RELOAD)->sum('amount');
            $income->user_total_amount = number_format($total_income);
            $date = Carbon::createFromFormat('Y/M/d A h:i',$income->created_at);
            $income->formatted_created_at = $date->format('d-m-Y H:i');
            $income->client_name = $income->entity_type_id == EntityTypes::SHOP ? $income->user_name." / ".$income->main_name : $income->main_name;
        }

        return view('admin.dashboard.all-day-detail', compact('title','type','dayIncome','current_date_encoded'));
    }

    public function getQr()
    {
        //https://bit.ly/3rbqGAE
        //Me-talk link - https://play.google.com/store/apps/details?id=com.cis.me_talk
        //return \QrCode::size(300)->generate("https://bit.ly/3rbqGAE");
        return \QrCode::size(300)->generate(route('deeplink'));
    }

    public function getWeddingQr($id){
        $qrcode = \QrCode::size(300)->generate(route('wedding.view', ['uuid' => $id]));
        return view("wedding-qr-code",compact('id','qrcode'));
    }
    public function downloadWeddingQr($id)
    {
        $headers    = array('Content-Type' => ['svg']);

        $image      = \QrCode::size(300)->generate(route('wedding.view', ['uuid' => $id]));

        $imageName = "wedding-qr-code-$id.svg";
        //if ($type == 'svg') {
            $svgTemplate = new \SimpleXMLElement($image);
            $svgTemplate->registerXPathNamespace('svg', 'http://www.w3.org/2000/svg');
            $svgTemplate->rect->addAttribute('fill-opacity', 0);
            $image = $svgTemplate->asXML();
        //}

		\Storage::disk('s3')->put("uploads/qr-code/".$imageName, $image, 'public');
		return \Storage::disk('s3')->download('uploads/qr-code/'.$imageName, $imageName, $headers);
    }

    public function getShopQr($type,$id)
    {
        $qrcode = \QrCode::size(300)->generate(route('shop-deeplink', ['type' => $type, 'id' => $id]));
        return view("qr-code",compact('id','type','qrcode'));
    }

    public function downloadShopQr($type,$id)
    {
        $headers    = array('Content-Type' => ['svg']);
        $image      = \QrCode::size(300)->generate(route('shop-deeplink', ['type' => $type, 'id' => $id]));

        $imageName = "shop-qr-code-$id.svg";
        if ($type == 'svg') {
            $svgTemplate = new \SimpleXMLElement($image);
            $svgTemplate->registerXPathNamespace('svg', 'http://www.w3.org/2000/svg');
            $svgTemplate->rect->addAttribute('fill-opacity', 0);
            $image = $svgTemplate->asXML();
        }

		\Storage::disk('s3')->put("uploads/qr-code/".$imageName, $image, 'public');
		return \Storage::disk('s3')->download('uploads/qr-code/'.$imageName, $imageName, $headers);
    }

    public function updateLanguage(Request $request,$id){
        $inputs = $request->all();
        $language_id = (!empty($inputs) && $inputs['status'] == 'true') ? 1 : 2;


        User::where('id',$id)->update(['lang_id' => $language_id]);

        $jsonData = [
            'message' => "Language successfully updated.",
            'response' => true
        ];
        return response()->json($jsonData);
    }

    public function getAllClickDetail(Request $request)
    {
        $inputs = $request->all();
        $year = $inputs['year'];
        $month = $inputs['month'];
        $type = $inputs['type'];

        $title = ucfirst($type)." Details";

        $adminTimezone = $this->getAdminUserTimezone();

        $query = PostClicks::select('post_clicks.*')
            ->where('post_clicks.type',$type)
            ->whereMonth('post_clicks.created_at',$month)
            ->whereYear('post_clicks.created_at',$year);

        if($type == 'hospitals'){
            $query = $query->leftJoin('hospitals','hospitals.id','post_clicks.entity_id')
                ->addSelect('hospitals.id as profile_id')
                ->addSelect('hospitals.email')
                ->addSelect('hospitals.main_name as name')
                ->addSelect(DB::raw('"" as user_name'));
        }else{
            $query = $query->leftJoin('shops','shops.id','post_clicks.entity_id')
                ->leftJoin('users','users.id','shops.user_id')
                ->leftJoin('users_detail','users_detail.user_id','shops.user_id')
                ->addSelect('users.email')
                ->addSelect('shops.id as profile_id')
                ->addSelect('shops.shop_name as name')
                ->addSelect('users_detail.name as user_name');
        }
        //echo $query->toSql(); exit;
        $detail = $query->get();
        return view('admin.dashboard.show-click-popup',compact('title','adminTimezone','detail','type'));
    }
}
