<!doctype html>
<html>
  <head>
    <meta name="viewport" content="width=device-width" />
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <title>{{ $user_detail['title'] }} : MeAround</title>    
  </head>
  <body class="">
    <table border="0" style="border:1px solid #000;" cellspacing="0" align="left" width="500">
      <tr>
          <th style="padding:20px 0px;background-color: #ccc;font-size: 30px;">{{ $user_detail['title'] }} </th>
      </tr>
      <tr>
          <td style="padding:15px;">
              <!-- <p>Hello {{ $user_detail['username'] }},</p> -->
              {!! $user_detail['email_body'] !!}
              <!-- <p>{!! $user_detail['email_body'] !!}</p> -->
              
              <!-- <p style="margin: 0;"><strong>Regards,</strong></p>
              <p style="margin: 0;"><strong>Me-Talk Team</strong></p> -->
          </td>
      </tr>
    </table>
  </body>
</html>
