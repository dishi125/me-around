<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="profile" href="http://gmpg.org/xfn/11">
    <!-- This site is optimized with the Yoast SEO Premium plugin v7.8.1 - https://yoast.com/wordpress/plugins/seo/ -->
    <meta name="description" content="Belletalk"/>
    <meta property="og:locale" content="en_US" />
    <meta property="og:type" content="website" />
    <meta property="og:title" content="Belletalk" />
    <meta property="og:description" content="Belletalk" />
    <meta property="og:url" content="http://belletalk.concetto-project-progress.com/" />
    <meta property="og:site_name" content="Belletalk" />
    <!-- <meta property="og:image" content="{{asset('/backend/images/Belletalk_icon.png')}}" /> -->
    <meta property="og:image:alt" content="belletalk" />
    <title>Me-talk</title>
    <link href="https://fonts.googleapis.com/css?family=Poppins:400,500,600,700" rel="stylesheet">
    <link href="{{ asset('backend/plugins/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('backend/plugins/bootstrap/css/bootstrap-theme.min.css') }}" rel="stylesheet">
    <link href="{{ asset('backend/css/template.css') }}" rel="stylesheet" type="text/css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
    <script src="{{ asset('backend/plugins/bootstrap/js/bootstrap.min.js') }}"></script>
    <script>
        function checkAppStatus() {
            <?php
            if ($data['browser'] == 'ios') {
                $appLink = env('ios_app_dest_link') . $data['dest'] . '&dest_id=' . $data['dest_id'];
            } elseif ($data['browser'] == 'android') {
                $appLink = env('android_app_dest_link') . $data['dest'] . '&dest_id=' . $data['dest_id'];
            } else {
                $appLink = '';
                $defferLink = '';
            }
            if (!empty($appLink) && !empty($defferLink)) {
                ?>
                window.location = " <?php echo $appLink; ?> ";
                setTimeout(function () {
                    window.location = "<?php echo $defferLink; ?>";
                }, 1000);
                <?php
            }
            ?>
        }
        $(document).ready(function () {
            checkAppStatus();
        });
    </script>
</head>
<body>
</body>
</html>
