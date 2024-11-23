<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\InstaStatusMail;
use App\Models\InstagramLog;
use App\Models\PostLanguage;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InstagramConnectLogController extends Controller
{
    public function index(Request $request)
    {
        $title = 'Instagram connect log List';
        InstagramLog::where('is_admin_read', 1)->update(['is_admin_read' => 0]);

        return view('admin.instagram-connect-log.index', compact('title'));
    }

    public function getJsonData(Request $request){
        $columns = array(
            1 => 'shops.main_name',
            2 => 'shops.shop_name',
            3 => 'instagram_logs.social_name',
            4 => 'users_detail.mobile',
            5 => 'users.email',
            6 => 'instagram_logs.status',
            7 => 'instagram_logs.created_at',
        );

        $limit = $request->input('length');
        $start = $request->input('start');
        $search = $request->input('search.value');
        $draw = $request->input('draw');
        $order = $columns[$request->input('order.0.column')];
        $dir = $request->input('order.0.dir');
        $adminTimezone = $this->getAdminUserTimezone();

        try {
            $data = [];
            $query = InstagramLog::select(
                    'instagram_logs.*',
                    'shops.main_name',
                    'shops.shop_name',
                    'users_detail.mobile',
                    'users.email'
                )
                ->leftjoin('shops', function ($join) {
                    $join->on('instagram_logs.shop_id', '=', 'shops.id')
                        ->whereNull('shops.deleted_at');
                })
                ->leftjoin('users_detail', function ($join) {
                    $join->on('instagram_logs.user_id', '=', 'users_detail.user_id')
                        ->whereNull('users_detail.deleted_at');
                })
                ->leftjoin('users', function ($join) {
                    $join->on('instagram_logs.user_id', '=', 'users.id')
                        ->whereNull('users.deleted_at');
                });

            if (!empty($search)) {
                $query =  $query->where(function ($q) use ($search) {
                    $q->where('shops.main_name', 'LIKE', "%{$search}%")
                        ->orWhere('shops.shop_name', 'LIKE', "%{$search}%")
                        ->orWhere('instagram_logs.social_name', 'LIKE', "%{$search}%")
                        ->orWhere('users_detail.mobile', 'LIKE', "%{$search}%")
                        ->orWhere('users.email', 'LIKE', "%{$search}%");
                });
            }

            $totalData = count($query->get());
            $totalFiltered = $totalData;

            $insta_logs = $query->offset($start)
                ->limit($limit)
                ->orderBy($order, $dir)
                ->get();

            $count = 0;
            foreach($insta_logs as $insta_log){
                $viewLink = route('admin.business-client.shop.show', $insta_log->shop_id);
                $status = "";
                $send_mail_btn = "";
                if ($insta_log->status == InstagramLog::CONNECTED){
                    $status = '<span class="badge badge-success">&nbsp;</span>';
                }
                else if ($insta_log->status == InstagramLog::DISCONNECTED){
                    $status = '<span class="badge badge-secondary">&nbsp;</span>';
                }
                else if ($insta_log->status == InstagramLog::SOMETHINGDISCONNECTED){
                    $status = '<span class="badge" style="background-color: #fff700;">&nbsp;</span>';
                    $send_mail_btn = '<a role="button" href="javascript:void(0)" title="" class="mx-1 btn btn-primary btn-sm sendmail" data-toggle="tooltip" data-id="'.$insta_log->id.'">Send Mail ('.$insta_log->mail_count.')</a>';
                }

                $data[$count]['see_profile'] = '<div class="d-flex align-items-center"><a role="button" href="'.$viewLink.'" title="" data-original-title="View" class="btn btn-primary btn-sm " data-toggle="tooltip"><i class="fas fa-eye mt-1"></i></a></div>';
                $data[$count]['main_name'] = $insta_log->main_name;
                $data[$count]['shop_name'] = $insta_log->shop_name;
                $data[$count]['social_name'] = $insta_log->social_name;
                $data[$count]['phone'] = $insta_log->mobile;
                $data[$count]['email'] = $insta_log->email.$send_mail_btn;
                $data[$count]['status'] = $status;
                $data[$count]['time'] = $this->formatDateTimeCountryWise($insta_log->created_at,$adminTimezone);

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

    public function statusSendMail($insta_log_id){
        $query = InstagramLog::select(
            'instagram_logs.*',
            'users_detail.language_id',
            'users.email'
            )
            ->leftjoin('users_detail', function ($join) {
                $join->on('instagram_logs.user_id', '=', 'users_detail.user_id')
                    ->whereNull('users_detail.deleted_at');
            })
            ->leftjoin('users', function ($join) {
                $join->on('instagram_logs.user_id', '=', 'users.id')
                    ->whereNull('users.deleted_at');
            })
            ->where('instagram_logs.id',$insta_log_id)
            ->first();

        $img_url = "";
        $subject = "";
        if ($query['language_id']==PostLanguage::ENGLISH){
            $img_url = asset('img/eng_insta_disconnect.png');
            $subject = "[MeAround] Instagram sync is broken. please reconnect";
        }
        else if ($query['language_id']==PostLanguage::KOREAN){
            $img_url = asset('img/Kor_insta_disconnect.png');
            $subject = "[MeAround] 인스타그램 동기화가 풀렸습니다. 다시 연결해 주세요";
        }
        else if ($query['language_id']==PostLanguage::JAPANESE){
            $img_url = asset('img/jap_insta_disconnect.png');
            $subject = "[MeAround]インスタグラムの同期が解除されました。 再接続してください";
        }

        $mailData = (object)[
            'email' => $query['email'],
            'social_name' => $query['social_name'],
            'img_url' => $img_url,
            'deeplink' => "http://app.mearoundapp.com/me-talk/deeplink",
            'subject' => $subject
        ];
        InstaStatusMail::dispatch($mailData);
        $query->mail_count = $query->mail_count + 1;
        $query->save();

        return ['success' => true , 'mail_count' => $query->mail_count];
    }

}
