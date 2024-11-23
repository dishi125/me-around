<!doctype html>
<html>

<head>
    <meta name="viewport" content="width=device-width" />
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <title>{!! $subjectTitle !!}</title>
</head>

<body class="">
    <table border="1" style="border:1px solid #000;" cellspacing="0" align="left"  cellpadding="5">
        <thead>
        <tr>
            <th>Product</th>
            <th>Amount</th>
            <th>Instagram Name</th>
            <th>Payer Name</th>
            <th>Payer phone</th>
            <th>Payer E-mail</th>
            <th>Starting Date</th>
            <th>Card No.</th>
            <th>Card Name</th>
            <th>Recent Payment</th>
        </tr>
        </thead>
        <tbody>
        @foreach($repayments_data as $data)
        <tr>
            <td>{!! $data->pay_goods !!}</td>
            <td>{!! $data->pay_total !!}</td>
            <td>{!! $data->instagram_account !!}</td>
            <td>{!! $data->payer_name !!}</td>
            <td>{!! $data->payer_phone !!}</td>
            <td>{!! $data->payer_email !!}</td>
            <td>{!! $data->start_date !!}</td>
            <td>{!! $data->card_number !!}</td>
            <td>{!! $data->card_name !!}</td>
            <?php
            $controllerInstance = new \App\Http\Controllers\Controller();

            $last_payment = \App\Models\PaypalRepaymentUser::where('paypal_payment_id',$data->id)->orderBy('created_at','DESC')->first();
            $last_payment_date = $formattedDate =  "";
            if(isset($last_payment)){
                $last_payment_date = $controllerInstance->formatDateTimeCountryWise($last_payment->created_at,'Asia/Seoul','Y-m-d');
            }
            ?>
            <td>{!! $last_payment_date !!}</td>
        </tr>
        @endforeach
        </tbody>
    </table>
</body>

</html>
