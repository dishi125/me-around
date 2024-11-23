<!doctype html>
<html>

<head>
    <meta name="viewport" content="width=device-width" />
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <title>{!! $subjectTitle !!}</title>
</head>

<body class="">
<h1>{{ $mailContent->social_name }} :</h1>
<a href="{{ $mailContent->deeplink }}">
    <img src="{{ $mailContent->img_url }}" alt="Instagram Disconnected">
</a>
</body>

</html>
