<!doctype html>
<html>
  <head>
    <meta name="viewport" content="width=device-width" />
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <title>{!! $subjectTitle !!}</title>    
  </head>
  <body class="">
    <table border="0" style="border:1px solid #000;" cellspacing="0" align="left" >
      <tr>
          <th colspan="2" style="padding:20px 50px;background-color: #ccc;font-size: 30px;">{!! $subjectTitle !!}</th>
      </tr>
      <tr>
          <th style="padding:15px;">User Name</th>
          <td style="padding:15px;">{!! $mailContent->username !!}</td>
      </tr>
      <tr>
          <th style="padding:15px;">Product Name</th>
          <td style="padding:15px;">{!! $mailContent->productname !!}</td>
      </tr>
      <tr>
        <th style="padding:15px;">Price</th>
        <td style="padding:15px;">{!! $mailContent->price !!}</td>
      </tr>
      <tr>
        <th style="padding:15px;">Phone Number</th>
        <td style="padding:15px;">{!! $mailContent->phone !!}</td>
      </tr>
      <tr>
        <th style="padding:15px;">Date</th>
        <td style="padding:15px;">{!! $mailContent->date !!}</td>
      </tr>
    </table>
  </body>
</html>
