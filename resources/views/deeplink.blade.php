<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="profile" href="http://gmpg.org/xfn/11">
    <meta property="og:locale" content="en_IN" />
    <meta property="og:type" content="website" />
    {{--  <meta property="og:title" content="앱 다운로드 링크 - Apps On Google play & App store" />  --}}
    <meta property="og:title" content="MeAround - Google play & App store" />
    <meta property="og:description" content="Welcome to Me Around" />
    <meta property="og:url" content="" />
    <meta property="og:site_name" content="Me Around" />
    <meta property="og:image" content="https://app.mearoundapp.com/me-talk/public/img/deeplink_image.png" />
    <meta property="og:image:alt" content="Me Around" />
    <title>{{ config('app.name', 'Laravel') }}</title>
    <link href="https://fonts.googleapis.com/css?family=Poppins:400,500,600,700" rel="stylesheet">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
    <script>
        function deeplinkRedirect() {
            @php
                $redirectLink = $data['redirectLink'];
            @endphp

            @if (!empty($redirectLink))
                window.location.href = "<?php echo $redirectLink; ?>";
            @endif
        }
        $(document).ready(function() {
            deeplinkRedirect();
        });
    </script>
</head>

<body>
</body>

</html>
