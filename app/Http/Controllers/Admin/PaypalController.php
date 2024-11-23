<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\RegisterPaypalCardMail;
use App\Mail\RegisterPaypalCardMail as registerMail;
use App\Models\PaypalBill;
use App\Models\PaypalBillPaymentUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class PaypalController extends Controller
{
    public function index(Request $request)
    {
        $title = 'Paypal';

        return view('admin.paypal.index', compact('title'));
    }

    public function saveBill(Request $request){
        try{
            $inputs = $request->all();
            PaypalBill::create([
                'card_ver' => $inputs['card_ver'],
                'pay_work' => "AUTH",
                'pay_goods' => $inputs['pay_goods'],
                'pay_total' => $inputs['pay_total'],
                'start_date' => $inputs['start_date'],
            ]);

            return response()->json(array('success' => true));
        }catch(\Exception $e){
            Log::info("$e");
            return response()->json(array('success' => false));
        }
    }

    public function billinggetJsonData(Request $request){
        $columns = array(
            0 => 'pay_goods',
            1 => 'pay_total',
            2 => 'card_ver',
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
            $query = PaypalBill::query();

            if (!empty($search)) {
                $query =  $query->where(function ($q) use ($search) {
                    $q->where('pay_goods', 'LIKE', "%{$search}%")
                        ->orWhere('pay_total', 'LIKE', "%{$search}%");
                });
            }

            $totalData = count($query->get());
            $totalFiltered = $totalData;

            $billingData = $query->offset($start)
                ->limit($limit)
                ->orderBy($order, $dir)
                ->get();

            $count = 0;
            foreach($billingData as $billing){
                $data[$count]['product_name'] = $billing->pay_goods;
                $data[$count]['amount'] = $billing->pay_total;
                $data[$count]['payment_method'] = ($billing->card_ver=="01") ? "Regular payment" : "App card payment";

                $paymentLink = url('paypal/'.$billing->id.'/payment/');
                $data[$count]['link'] = '<a href="javascript:void(0);" onClick="copyTextLink(`' . $paymentLink . '`)" class="btn btn-primary"><i class="fas fa-copy"></i></a>';

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

    public function getPayment($id){
        $bill = PaypalBill::where('id',$id)->first();
        return view('admin.paypal.payment-form',compact('bill'));
    }

    public function paymentResult(Request $request,$id){
        $response = $request->all();
        $bill = PaypalBill::where('id',$id)->first();
        PaypalBillPaymentUser::create([
            'paypal_bill_id' => $id,
            'payment_method' => $bill->card_ver,
            'status' => $response['PCD_PAY_RST'],
            'payer_name' => $response['PCD_PAYER_NAME'],
            'payer_phone' => $response['PCD_PAYER_HP'],
            'payer_email' => $response['PCD_PAYER_EMAIL'],
            'card_number' => $response['PCD_PAY_CARDNUM'],
//            'oid' => $response['PCD_PAY_OID'],
            'card_name' => $response['PCD_PAY_CARDNAME'],
            'payer_id' => $response['PCD_PAYER_ID'],
            'pay_goods' => $bill->pay_goods,
            'pay_total' => $bill->pay_total,
//            'simple_flag' => $response['PCD_SIMPLE_FLAG'],
//            'pay_istax' => $response['PCD_PAY_ISTAX'],
//            'pay_taxtotal' => $response['PCD_PAY_TAXTOTAL'],
            'payer_no' => $response['PCD_PAYER_NO'],
            'start_date' => $bill->start_date,
        ]);

        $mailData = (object)[
            'product' => $bill->pay_goods,
            'amount' => $bill->pay_total,
            'payer_name' => $response['PCD_PAYER_NAME'],
            'payer_phone' => $response['PCD_PAYER_HP'],
            'payer_email' => $response['PCD_PAYER_EMAIL'],
            'start_date' => $bill->start_date,
            'card_no' => $response['PCD_PAY_CARDNUM'],
            'card_name' => $response['PCD_PAY_CARDNAME'],
            'subject' => "[".$response['PCD_PAYER_NAME']."] Card is Registered in MeAround"
        ];
//        RegisterPaypalCardMail::dispatch($mailData);
//        Mail::to("dishu1205099@gmail.com")->send(new registerMail($mailData));
        Mail::to("gwb9160@nate.com")->send(new registerMail($mailData));

        return view("admin.paypal.payment-result",compact('response','bill'));
    }

    public function authenticate(Request $request){
        try {
            $CURLOPT_HTTPHEADER = array(
                "referer: http://" . $_SERVER['HTTP_HOST']
            );

            $post_data = array(
                "cst_id" => env('cst_id'),
                "custKey" => env('custKey')
            );

            $ch = curl_init(env('authUrl'));
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $CURLOPT_HTTPHEADER);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post_data));

            ob_start();
            $authRes = curl_exec($ch);
            $authBuffer = ob_get_contents();
            ob_end_clean();

            $authResult = json_decode($authBuffer);

            if (!isset($authResult->result)) throw new \Exception("파트너 인증요청 실패");

            if ($authResult->result != 'success') throw new \Exception($authResult->result_msg);

            $cst_id = $authResult->cst_id;
            $custKey = $authResult->custKey;
            $AuthKey = $authResult->AuthKey;
            $return_url = $authResult->return_url;

            return response()->json(['success' => true,'cst_id' => $cst_id, 'custKey' => $custKey, 'AuthKey' => $AuthKey, 'return_url' => $return_url]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Something went wrong !!']);
        }
    }

}
