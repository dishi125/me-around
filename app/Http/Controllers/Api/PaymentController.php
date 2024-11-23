<?php

namespace App\Http\Controllers\Api;

use App\Models\EntityTypes;
use App\Models\Status;
use App\Models\User;
use App\Models\UserEntityRelation;
use App\Models\UserDetail;
use App\Models\UserSavedHistory;
use App\Validators\PaymentValidator;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
use Validator;
use Carbon\Carbon;
use App\Util\Firebase;
use Braintree_Transaction;


class PaymentController extends Controller
{
    private $paymentValidator;
    protected $firebase;

    function __construct()
    {
        $this->paymentValidator = new PaymentValidator();
        $this->firebase = new Firebase();
    }

    public function payWithPaypal(Request $request)
    {
        Log::info('start code for paypal payment.');
        try {
            $user = Auth::user();
            if($user){
                DB::beginTransaction();
                $inputs = $request->all();
                $validation = $this->paymentValidator->validatePaypal($inputs);
                if ($validation->fails()) {
                    return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
                }
                $amount = $inputs['amount'];

                /* Payment with paypal using payment method nonce */
                $braintree = config('braintree');

                $transactionResult = $braintree->transaction()->sale([
                    'paymentMethodNonce' => $inputs['payment_method_nonce'],
                    'amount' => $amount,
                    'customer' => [
                        'firstName' => $user->name,
                        'email' => $user->email
                    ],
                    'options' => [
                        'submitForSettlement' => true
                    ]
                ]);
                //dd($transactionResult);        
                Log::info($transactionResult);
                Log::info($transactionResult->success);

                dd($transactionResult);
                if ($transactionResult->success == true || $transactionResult->success == 1) {
                    /* SAVE TRANSACTION START CODE */
                    $transactionData = [
                        'user_id' => $user->id,
                        'paypal_transaction_id' => $transactionResult->transaction->id,
                        'paypal_transaction_status' => $transactionResult->transaction->status,
                        'amount' => $amount,
                        'currency' => env('CURRENCY', 'USD')
                    ];

                    Transaction::create($transactionData);
                    /* SAVE TRANSACTION END CODE */

                    DB::commit();
                    Log::info('end code for paypal payment.');
                    return $this->sendSuccessResponse(Lang::get('messages.payment.success'), 200, []);
                }else {
                    Log::info('end code for paypal payment.');
                    return $this->sendFailedResponse(Lang::get('messages.payment.failed'), 400, []);
                }
            }else{
                Log::info('End code for paypal payment');
                return $this->sendSuccessResponse(Lang::get('messages.user.token_expired'), 401);
            } 
        } catch (\Exception $exception) {
            DB::rollback();
            Log::info("exception code for paypal payment." . $exception);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }
}
