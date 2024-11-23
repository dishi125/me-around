<?php

namespace App\Http\Controllers\Challenge;

use App\Http\Controllers\Controller;
use App\Models\Challenge;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    public function index()
    {
        $title = "Order";

        return view('challenge.order.index', compact('title'));
    }

    public function getJsonAllData(Request $request)
    {
        $columns = array(
            0 => 'challenges.depositor_name',
            1 => 'users_detail.name',
            2 => 'challenges.title',
            4 => 'challenges.created_at',
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

            $Query = Challenge::leftjoin('users_detail', function ($join) {
                        $join->on('challenges.user_id', '=', 'users_detail.user_id')
                            ->whereNull('users_detail.deleted_at');
                    })
                    ->select(
                        'challenges.depositor_name',
                        'users_detail.name',
                        'challenges.title',
                        'challenges.created_at',
                        'challenges.user_id'
                    );

            if (!empty($search)) {
                $Query = $Query->where(function ($q) use ($search) {
                    $q->where('challenges.depositor_name', 'LIKE', "%{$search}%")
                        ->orWhere('challenges.title', 'LIKE', "%{$search}%");
                });
            }

            $totalData = count($Query->get());
            $totalFiltered = $totalData;

            $orderData = $Query->offset($start)
                ->limit($limit)
                ->orderBy($order, $dir)
                ->get();

            $count = 0;
            foreach ($orderData as $order) {
                $data[$count]['depositor_name'] = $order->depositor_name;

                $userName = ($order->user_id==null) ? "Admin" : $order->name;
                $data[$count]['user_name'] = $userName;
                $data[$count]['challenge_name'] = $order->title;
                $data[$count]['amount'] = 5000;
                $data[$count]['created_at'] = $this->formatDateTimeCountryWise($order->created_at,$adminTimezone);

                $giveDepositBtn = '<a role="button" class="btn btn-primary btn-sm mx-1" data-toggle="tooltip" data-original-title="">'.__('datatable.give_deposit_point').'</a>';
                $data[$count]['action'] = "$giveDepositBtn";

                $count++;
            }

            $jsonData = array(
                "draw" => intval($draw),
                "recordsTotal" => intval($totalData),
                "recordsFiltered" => intval($totalFiltered),
                "data" => $data
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

}
