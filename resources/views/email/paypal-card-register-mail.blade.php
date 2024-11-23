<!doctype html>
<html>

<head>
    <meta name="viewport" content="width=device-width" />
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <title>{!! $subjectTitle !!}</title>
</head>

<body class="">
    <table border="0" style="border:1px solid #000;" cellspacing="0" align="left">
<!--        <tr>
            <th colspan="2" style="padding:15px 50px;background-color: #ccc;font-size: 22px;">{!! $subjectTitle !!}
            </th>
        </tr>-->
        <tr>
            <th style="padding:15px;">Product</th>
            <td style="padding:15px;">{!! $mailContent->product !!}</td>
        </tr>
        <tr>
            <th style="padding:15px;">Amount</th>
            <td style="padding:15px;">{!! $mailContent->amount !!}</td>
        </tr>
        <tr>
            <th style="padding:15px;">Payer Name</th>
            <td style="padding:15px;">{!! $mailContent->payer_name !!}</td>
        </tr>
        <tr>
            <th style="padding:15px;">Payer Phone Number</th>
            <td style="padding:15px;">{!! $mailContent->payer_phone !!}</td>
        </tr>
        <tr>
            <th style="padding:15px;">Payer E-mail</th>
            <td style="padding:15px;">{!! $mailContent->payer_email !!}</td>
        </tr>
        <tr>
            <th style="padding:15px;">Starting Date</th>
            <td style="padding:15px;">{!! $mailContent->start_date !!}</td>
        </tr>
        <tr>
            <th style="padding:15px;">Card Number</th>
            <td style="padding:15px;">{!! $mailContent->card_no !!}</td>
        </tr>
        <tr>
            <th style="padding:15px;">Card Name</th>
            <td style="padding:15px;">{!! $mailContent->card_name !!}</td>
        </tr>

    </table>
</body>

</html>
