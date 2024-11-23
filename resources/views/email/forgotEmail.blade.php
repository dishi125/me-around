<!doctype html>
<html>

<head>
    <meta name="viewport" content="width=device-width" />
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <title>Forgot ID: {!! env('APP_NAME') !!}</title>
</head>

<body class="" style="text-align: center;">
    <table border="0" style="border:1px solid #000;" cellspacing="0" align="left" width="500">
        <tr>
            <th style="padding:20px 0px;text-align: center;background-color: #ccc;font-size: 30px;">Me Talk</th>
        </tr>
        <tr>
            <td style="padding:15px;">
                <p>Hello {{ $user_detail->name }},</p>
                <p>Your ID = {{ $user_detail->email }}</p>
                <p>Thank you.</p>
                <p style="margin: 0;"><strong>Regrads,</strong></p>
                <p style="margin: 0;"><strong>{!! env('APP_NAME') !!} Team</strong></p>
            </td>
        </tr>
    </table>
</body>

</html>