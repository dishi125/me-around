<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\PaypalCardPaymentMail as registerMail;
use App\Models\PaypalBillPaymentUser;
use App\Models\PaypalRepaymentUser;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class RegularPaymentController extends Controller
{
    public function index(Request $request)
    {
        $title = 'Regular payments';
        $today_payments = PaypalBillPaymentUser::where('payment_method','01')
                            ->where('status','success')
                            ->where('next_payment_date',Carbon::today())
                            ->where('is_hide_next_payment_date',0)
                            ->count();
        $missed_payments = PaypalBillPaymentUser::where('payment_method','01')
                            ->where('status','success')
                            ->where('next_payment_date', '<', Carbon::today())
                            ->where('is_hide_next_payment_date',0)
                            ->count();

        return view('admin.regular-payment.index', compact('title','today_payments','missed_payments'));
    }

    public function regularPaymentgetJsonData(Request $request){
        $columns = array(
            0 => 'id',
            1 => 'pay_goods',
            2 => 'pay_total',
            3 => 'instagram_account',
            4 => 'payer_name',
            5 => 'payer_phone',
            6 => 'payer_email',
            7 => 'start_date',
            8 => 'card_number',
            9 => 'card_name',
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
            $query = PaypalBillPaymentUser::where('payment_method','01')
                    ->where('status','success');

            if (!empty($search)) {
                $query =  $query->where(function ($q) use ($search) {
                    $q->where('payer_name', 'LIKE', "%{$search}%")
                        ->orWhere('payer_phone', 'LIKE', "%{$search}%")
                        ->orWhere('payer_email', 'LIKE', "%{$search}%")
                        ->orWhere('card_number', 'LIKE', "%{$search}%")
                        ->orWhere('card_name', 'LIKE', "%{$search}%")
                        ->orWhere('pay_goods', 'LIKE', "%{$search}%")
                        ->orWhere('pay_total', 'LIKE', "%{$search}%")
                        ->orWhere('instagram_account', 'LIKE', "%{$search}%");
                });
            }

            $totalData = count($query->get());
            $totalFiltered = $totalData;

            $paymentData = $query->offset($start)
                ->limit($limit)
                ->orderBy($order, $dir)
                ->get();

            $count = 0;
            foreach($paymentData as $payment){
                $editLink = url('admin/regular-payment/get/edit/'.$payment->id);
                $edit = '<a role="button" href="javascript:void(0)" onclick="editData(`'.$editLink.'`)" title="" data-original-title="Edit Amount" class="mx-1 btn btn-primary btn-sm mb-1" data-toggle="tooltip">Edit</a>';
                $payment_log = '<a role="button" href="javascript:void(0)" onclick="paymentLog('.$payment->id.')" title="" data-original-title="" class="mx-1 btn btn-primary btn-sm mb-1" data-toggle="tooltip">Payment log</a>';
                $data[$count]['payment_log'] = $edit.$payment_log;

                $data[$count]['pay_goods'] = '<span onclick="copyTextLink(`'.$payment->pay_goods.'`,`Product name`)" style="cursor:pointer;">'.$payment->pay_goods.'</span>';
                $data[$count]['pay_total'] = $payment->pay_total;
                $data[$count]['instagram_account'] = '<span onclick="copyTextLink(`'.$payment->instagram_account.'`,`Instagram account name`)" style="cursor:pointer;">'.$payment->instagram_account.'</span>';
                $data[$count]['payer_name'] = '<span onclick="copyTextLink(`'.$payment->payer_name.'`,`Payer name`)" style="cursor:pointer;">'.$payment->payer_name.'</span>';
                $data[$count]['payer_phone'] = '<span onclick="copyTextLink(`'.$payment->payer_phone.'`,`Payer phone number`)" style="cursor:pointer;">'.$payment->payer_phone.'</span>';
                $data[$count]['payer_email'] = '<span onclick="copyTextLink(`'.$payment->payer_email.'`,`Payer e-mail`)" style="cursor:pointer;">'.$payment->payer_email.'</span>';
                $data[$count]['start_date'] = $payment->start_date;
                $data[$count]['card_number'] = $payment->card_number;
                $data[$count]['card_name'] = $payment->card_name;

                $rePayment = '<a role="button" href="javascript:void(0)" onclick="rePaymentRegular('.$payment->id.')" title="" data-original-title="" class="btn btn-primary btn-sm" data-toggle="tooltip">Repayment</a>';
                $data[$count]['action'] = $rePayment;

                $today_payment = PaypalRepaymentUser::where('paypal_payment_id',$payment->id)->where('status','success')->whereDate('created_at', Carbon::now())->count();
                $data[$count]['mark_today'] = ($today_payment > 0) ? '<span class="badge badge-success">&nbsp;</span>' : '';

                $recent_payment = "";
                $last_payment = PaypalRepaymentUser::where('paypal_payment_id',$payment->id)->orderBy('created_at','DESC')->first();
                if(isset($last_payment)){
                    $last_payment_date = $this->formatDateTimeCountryWise($last_payment->created_at,$adminTimezone,'Y-m-d');
                    $recent_payment .= "<p>$last_payment_date</p>";
                }
                if (isset($payment->next_payment_date)) {
                    $editNextPayLink = url('admin/regular-payment/get/edit-next-payment/' . $payment->id);
                    if($payment->is_hide_next_payment_date==0) {
                        $recent_payment .= "<div class='d-flex'>";
                        $recent_payment .= "<a onclick='editNextPayment(`" . $editNextPayLink . "`)' style='cursor: pointer'>$payment->next_payment_date</a>";
                        $recent_payment .= "<a role='button' class='mx-1 btn btn-primary btn-sm' data-toggle='tooltip' onclick='updateNextPayDateVisibility($payment->id,1)'>Hide</a>";
                        $recent_payment .= "</div>";
                    }
                    if($payment->is_hide_next_payment_date==1) {
                        $recent_payment .= "<a role='button' class='btn btn-primary btn-sm' data-toggle='tooltip' onclick='updateNextPayDateVisibility($payment->id,0)'>Show</a>";
                    }
                }
                $data[$count]['recent_payment'] = $recent_payment;

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

    public function rePayment(Request $request){
        try {
            $auth_data = array (
                "cst_id" => env('cst_id'),
                "custKey" => env('custKey'),
                "PCD_PAY_TYPE" => "card",
                "PCD_SIMPLE_FLAG" => "Y"
            );
            $CURLOPT_HTTPHEADER = array(
                "cache-control: no-cache",
                "content-type: application/json; charset=UTF-8",
                "referer: http://".$_SERVER['HTTP_HOST']
            );
            $ch = curl_init(env('authUrl'));
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($auth_data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, $CURLOPT_HTTPHEADER);
            ob_start();
            $authRes = curl_exec($ch);
            $authBuffer = ob_get_contents();
            ob_end_clean();
            $authResult = json_decode($authBuffer);
            if (!isset($authResult->result)) throw new Exception("인증요청 실패");
            if ($authResult->result != 'success') throw new Exception($authResult->result_msg);
            $cst_id = $authResult->cst_id;                  // 파트너사 ID
            $custKey = $authResult->custKey;                // 파트너사 키
            $authKey = $authResult->AuthKey;                // 인증 키
            $payReqURL = $authResult->return_url;

            $payment_data = PaypalBillPaymentUser::where('id',$request->payment_id)->first();
            $pay_data = array (
                "PCD_CST_ID" => $cst_id,
                "PCD_CUST_KEY" => $custKey,
                "PCD_AUTH_KEY" => $authKey,
                "PCD_PAY_TYPE" => "card",
                "PCD_PAYER_ID" => $payment_data->payer_id,
                "PCD_PAY_GOODS" => $payment_data->pay_goods,
                "PCD_SIMPLE_FLAG" => "Y",
                "PCD_PAY_TOTAL" => $payment_data->pay_total,
                "PCD_PAY_OID" => "",
                "PCD_PAYER_NO" => $payment_data->payer_no, //payer_no
                "PCD_PAYER_NAME" => $payment_data->payer_name,
                "PCD_PAYER_HP" => $payment_data->payer_phone,
                "PCD_PAYER_EMAIL" => $payment_data->payer_email,
                "PCD_PAY_ISTAX" => "Y",
                "PCD_PAY_TAXTOTAL" => ""
            );
            $ch = curl_init($payReqURL);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($pay_data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, $CURLOPT_HTTPHEADER);
            ob_start();
            $payRes = curl_exec($ch);
            $payBuffer = ob_get_contents();
            ob_end_clean();
            $payResult = json_decode($payBuffer);

            if($payResult->PCD_PAY_RST=="success"){
                $originalDate = Carbon::now();
                $newDate = $originalDate->addDays(30);
                $formattedDate = $newDate->toDateString();
                $payment_data->next_payment_date = $formattedDate;
                $payment_data->save();
            }
            PaypalRepaymentUser::create([
                'paypal_payment_id' => $payment_data->id,
                'status' => $payResult->PCD_PAY_RST,
                'message' => $payResult->PCD_PAY_MSG,
                'oid' => $payResult->PCD_PAY_OID,
                'product_name' => $payment_data->pay_goods,
                'amount' => $payment_data->pay_total,
            ]);

            if ($payResult->PCD_PAY_RST=="success") {
                if($payment_data->instagram_account!=null){
                    $subject = "[$payment_data->instagram_account] [$payment_data->payer_name] Regular payment is occurred in MeAround";
                }
                else {
                    $subject = "[$payment_data->payer_name] Regular payment is occurred in MeAround";
                }
                $mailData = (object)[
                    'product' => $payment_data->pay_goods,
                    'amount' => $payment_data->pay_total,
                    'payer_name' => $payment_data->payer_name,
                    'payer_phone' => $payment_data->payer_phone,
                    'payer_email' => $payment_data->payer_email,
                    'start_date' => $payment_data->start_date,
                    'card_no' => $payment_data->card_number,
                    'card_name' => $payment_data->card_name,
                    'instagram_account' => $payment_data->instagram_account,
                    'payment_time' => Carbon::now()->format('Y-m-d H:i:s'),
                    'subject' => $subject
                ];
//                PaypalCardPaymentMail::dispatch($mailData);
//                Mail::to("dishu1205099@gmail.com")->send(new registerMail($mailData));
                Mail::to("gwb9160@nate.com")->send(new registerMail($mailData));
            }

            return response()->json(['success' => true, 'message' => $payResult->PCD_PAY_MSG]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Something went wrong !!']);
        }
    }

    public function getEdit($id)
    {
        $data = PaypalBillPaymentUser::whereId($id)->first();
        return view('admin.regular-payment.edit-popup', compact('id', 'data'));
    }

    public function updateData(Request $request, $id)
    {
        $inputs = $request->all();
        try {
            PaypalBillPaymentUser::where('id', $id)->update([
                'pay_goods' => $inputs['pay_goods'],
                'pay_total' => $inputs['amount'],
                'start_date' => $inputs['start_date'],
                'instagram_account' => $inputs['instagram_account'],
            ]);

            return response()->json(array(
                'success' => true,
                'message' => "Data successfully updated."
            ), 200);
        } catch (Exception $ex) {
            Log::info($ex);
            return response()->json(array(
                'success' => false,
                'message' => "Unable to update"
            ), 400);
        }
    }

    public function calendarIndex(Request $request)
    {
        $title = 'Calendar';
        $count_next_pays = PaypalBillPaymentUser::where('payment_method','01')
                    ->where('status','success')
                    ->where('is_hide_next_payment_date',0)
                    ->distinct()
                    ->get(['next_payment_date'])
                    ->map(function ($item) {
                        $item['count'] = PaypalBillPaymentUser::where('payment_method','01')
                            ->where('status','success')
                            ->where('is_hide_next_payment_date',0)
                            ->where('next_payment_date',$item['next_payment_date'])
                            ->count();
                        return $item;
                    });
        $next_pays = PaypalBillPaymentUser::where('payment_method','01')
                    ->where('status','success')
                    ->where('is_hide_next_payment_date',0)
                    ->get(['payer_name','next_payment_date','instagram_account']);

        $currentMonth = Carbon::now()->format('Y-m');
        $this_month_pays = PaypalBillPaymentUser::where('payment_method','01')
                    ->where('status','success')
                    ->where('is_hide_next_payment_date',0)
                    ->whereRaw("DATE_FORMAT(next_payment_date, '%Y-%m') = ?", [$currentMonth])
                    ->count();
        $today = Carbon::now()->format('Y-m-d');
        $today_pays = PaypalBillPaymentUser::where('payment_method','01')
            ->where('status','success')
            ->where('is_hide_next_payment_date',0)
            ->whereDate('next_payment_date',$today)
            ->count();

        return view('admin.regular-payment.calendar-index', compact('title','next_pays','count_next_pays','this_month_pays','today_pays'));
    }

    public function getEditNextPay($id)
    {
        $data = PaypalBillPaymentUser::whereId($id)->first();
        return view('admin.regular-payment.edit-next-pay-popup', compact('id', 'data'));
    }

    public function editNextPayment(Request $request, $id)
    {
        $inputs = $request->all();
        try {
            PaypalBillPaymentUser::where('id', $id)->update([
                'next_payment_date' => $inputs['next_payment_date'],
            ]);

            return response()->json(array(
                'success' => true,
                'message' => "Next payment date successfully updated."
            ), 200);
        } catch (Exception $ex) {
            Log::info($ex);
            return response()->json(array(
                'success' => false,
                'message' => "Unable to update"
            ), 400);
        }
    }

    public function editNextPaymentVisibility(Request $request)
    {
        $inputs = $request->all();
        try {
            PaypalBillPaymentUser::where('id', $inputs['payment_id'])->update([
                'is_hide_next_payment_date' => $inputs['is_hide_next_payment_date'],
            ]);

            return response()->json(array(
                'success' => true,
                'message' => "Next payment date visibility successfully updated."
            ), 200);
        } catch (Exception $ex) {
            Log::info($ex);
            return response()->json(array(
                'success' => false,
                'message' => "Unable to update"
            ), 400);
        }
    }

    public function paymentLog($id){
        $adminTimezone = $this->getAdminUserTimezone();
        $payment_logs = PaypalRepaymentUser::with('billpayment')
                        ->where('paypal_payment_id',$id)
                        ->where('status','success')
                        ->get();

        return view('admin.regular-payment.show-paymentlogs-popup', compact('payment_logs','adminTimezone'));
    }

    public function showAllPayment(){
        $adminTimezone = $this->getAdminUserTimezone();
        $payment_logs = PaypalRepaymentUser::with('billpayment')
                        ->where('status','success')
                        ->get();

        return view('admin.regular-payment.show-allpayment-popup', compact('payment_logs','adminTimezone'));
    }

    public function nextPayIndex($next_payment)
    {
        $title = 'Regular payments';

        return view('admin.regular-payment.next-payment-index', compact('title','next_payment'));
    }

    public function nextPaymentJsonData(Request $request){
        $columns = array(
            0 => 'id',
            1 => 'pay_goods',
            2 => 'pay_total',
            3 => 'instagram_account',
            4 => 'payer_name',
            5 => 'payer_phone',
            6 => 'payer_email',
            7 => 'start_date',
            8 => 'card_number',
            9 => 'card_name',
        );

        $limit = $request->input('length');
        $start = $request->input('start');
        $search = $request->input('search.value');
        $draw = $request->input('draw');
        $order = $columns[$request->input('order.0.column')];
        $dir = $request->input('order.0.dir');
        $next_payment_date = $request->input('next_payment_date');
        $adminTimezone = $this->getAdminUserTimezone();

        try {
            $data = [];
            $query = PaypalBillPaymentUser::where('payment_method','01')
                ->where('status','success')
                ->where('next_payment_date',$next_payment_date);

            if (!empty($search)) {
                $query =  $query->where(function ($q) use ($search) {
                    $q->where('payer_name', 'LIKE', "%{$search}%")
                        ->orWhere('payer_phone', 'LIKE', "%{$search}%")
                        ->orWhere('payer_email', 'LIKE', "%{$search}%")
                        ->orWhere('card_number', 'LIKE', "%{$search}%")
                        ->orWhere('card_name', 'LIKE', "%{$search}%")
                        ->orWhere('pay_goods', 'LIKE', "%{$search}%")
                        ->orWhere('pay_total', 'LIKE', "%{$search}%")
                        ->orWhere('instagram_account', 'LIKE', "%{$search}%");
                });
            }

            $totalData = count($query->get());
            $totalFiltered = $totalData;

            $paymentData = $query->offset($start)
                ->limit($limit)
                ->orderBy($order, $dir)
                ->get();

            $count = 0;
            foreach($paymentData as $payment){
                $editLink = url('admin/regular-payment/get/edit/'.$payment->id);
                $edit = '<a role="button" href="javascript:void(0)" onclick="editData(`'.$editLink.'`)" title="" data-original-title="Edit Amount" class="mx-1 btn btn-primary btn-sm mb-1" data-toggle="tooltip">Edit</a>';
                $payment_log = '<a role="button" href="javascript:void(0)" onclick="paymentLog('.$payment->id.')" title="" data-original-title="" class="mx-1 btn btn-primary btn-sm mb-1" data-toggle="tooltip">Payment log</a>';
                $data[$count]['payment_log'] = $edit.$payment_log;

                $data[$count]['pay_goods'] = '<span onclick="copyTextLink(`'.$payment->pay_goods.'`,`Product name`)" style="cursor:pointer;">'.$payment->pay_goods.'</span>';
                $data[$count]['pay_total'] = $payment->pay_total;
                $data[$count]['instagram_account'] = '<span onclick="copyTextLink(`'.$payment->instagram_account.'`,`Instagram account name`)" style="cursor:pointer;">'.$payment->instagram_account.'</span>';
                $data[$count]['payer_name'] = '<span onclick="copyTextLink(`'.$payment->payer_name.'`,`Payer name`)" style="cursor:pointer;">'.$payment->payer_name.'</span>';
                $data[$count]['payer_phone'] = '<span onclick="copyTextLink(`'.$payment->payer_phone.'`,`Payer phone number`)" style="cursor:pointer;">'.$payment->payer_phone.'</span>';
                $data[$count]['payer_email'] = '<span onclick="copyTextLink(`'.$payment->payer_email.'`,`Payer e-mail`)" style="cursor:pointer;">'.$payment->payer_email.'</span>';
                $data[$count]['start_date'] = $payment->start_date;
                $data[$count]['card_number'] = $payment->card_number;
                $data[$count]['card_name'] = $payment->card_name;

                $rePayment = '<a role="button" href="javascript:void(0)" onclick="rePaymentRegular('.$payment->id.')" title="" data-original-title="" class="btn btn-primary btn-sm" data-toggle="tooltip">Repayment</a>';
                $data[$count]['action'] = $rePayment;

                $today_payment = PaypalRepaymentUser::where('paypal_payment_id',$payment->id)->where('status','success')->whereDate('created_at', Carbon::now())->count();
                $data[$count]['mark_today'] = ($today_payment > 0) ? '<span class="badge badge-success">&nbsp;</span>' : '';

                $last_payment = PaypalRepaymentUser::where('paypal_payment_id',$payment->id)->orderBy('created_at','DESC')->first();
                $last_payment_date = $formattedDate =  "";
                if(isset($last_payment)){
                    $last_payment_date = $this->formatDateTimeCountryWise($last_payment->created_at,$adminTimezone,'Y-m-d');
                }
                $editNextPayLink = url('admin/regular-payment/get/edit-next-payment/'.$payment->id);
                $data[$count]['recent_payment'] = "<p>$last_payment_date</p><a onclick='editNextPayment(`".$editNextPayLink."`)' style='cursor: pointer'>$payment->next_payment_date</a>";

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

    public function removePayment(Request $request)
    {
        $inputs = $request->all();
        try {
            PaypalRepaymentUser::where('paypal_payment_id',$inputs['payment_id'])->delete();
            PaypalBillPaymentUser::where('id', $inputs['payment_id'])->delete();

            return response()->json(array(
                'success' => true,
                'message' => "Payment successfully removed."
            ), 200);
        } catch (Exception $ex) {
            Log::info($ex);
            return response()->json(array(
                'success' => false,
                'message' => "Unable to remove"
            ), 400);
        }
    }

    public function monthPayments(Request $request)
    {
        $inputs = $request->all();
        try {
            $currentMonth = $inputs['year']."-".$inputs['month'];
            $count_month_pays = PaypalBillPaymentUser::where('payment_method','01')
                ->where('status','success')
                ->where('is_hide_next_payment_date',0)
                ->whereRaw("DATE_FORMAT(next_payment_date, '%Y-%m') = ?", [$currentMonth])
                ->count();

            return response()->json(array(
                'success' => true,
                'count_month_pays' => $count_month_pays
            ), 200);
        } catch (Exception $ex) {
            Log::info($ex);
            return response()->json(array(
                'success' => false,
                'message' => "Unable to update"
            ), 400);
        }
    }

}
