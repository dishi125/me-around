<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="apple-touch-icon" sizes="96x96" href="{!! asset('favicon/favicon-96x96.png') !!}">
    <?php /*<link rel="apple-touch-icon" sizes="60x60" href="{!! asset('favicon/apple-icon-60x60.png') !!}">
    <link rel="apple-touch-icon" sizes="72x72" href="{!! asset('favicon/apple-icon-72x72.png') !!}">
    <link rel="apple-touch-icon" sizes="76x76" href="{!! asset('favicon/apple-icon-76x76.png') !!}">
    <link rel="apple-touch-icon" sizes="114x114" href="{!! asset('favicon/apple-icon-114x114.png') !!}">
    <link rel="apple-touch-icon" sizes="120x120" href="{!! asset('favicon/apple-icon-120x120.png') !!}">
    <link rel="apple-touch-icon" sizes="144x144" href="{!! asset('favicon/apple-icon-144x144.png') !!}">
    <link rel="apple-touch-icon" sizes="152x152" href="{!! asset('favicon/apple-icon-152x152.png') !!}">
    <link rel="apple-touch-icon" sizes="180x180" href="{!! asset('favicon/apple-icon-180x180.png') !!}">
    <link rel="icon" type="image/png" sizes="192x192" href="{!! asset('favicon/android-icon-192x192.png') !!}">
    <link rel="icon" type="image/png" sizes="32x32" href="{!! asset('favicon/favicon-32x32.png') !!}">
    <link rel="icon" type="image/png" sizes="16x16" href="{!! asset('favicon/favicon-16x16.png') !!}"> */ ?>
    <link rel="icon" type="image/png" sizes="96x96" href="{!! asset('favicon/favicon-96x96.png') !!}">
    <link rel="shortcut icon" href="{!! asset('favicon/favicon-32x32.png') !!}">
    <link rel="manifest" href="{!! asset('favicon/manifest.json') !!}">
    <meta name="msapplication-TileColor" content="#43425D">
    <meta name="msapplication-TileImage" content="{!! asset('favicon/ms-icon-144x144.png') !!}">
    <meta name="theme-color" content="#43425D">

    <title>@if (@$title) {{ @$title }} - @endif {{ config('app.name', 'Laravel') }}</title>

    <link rel="stylesheet" href="{!! asset('plugins/bootstrap/css/bootstrap.min.css') !!}">
    <link rel="stylesheet" href="{!! asset('plugins/fontawesome/css/all.min.css') !!}">
    <link rel="stylesheet" href="{{ asset('plugins/izitoast/css/iziToast.css') }}">
    <link rel="stylesheet" href="{!! asset('css/style.css') !!}">
    <link rel="stylesheet" href="{!! asset('css/responsive.css') !!}">
    <link rel="stylesheet" href="{!! asset('css/components.css') !!}">
    <style type="text/css">
        .copy_clipboard,.copy_code {
            cursor: pointer;
        }
    </style>
</head>

<body>
<div class="container">
    <div class="align-items-xl-center">
{{--        <h4 class="mb-5 text-center">Delete Account</h4>--}}
        <div class="login-brand">
        <span class="text-center"><img src="{!! asset('img/logo1.png') !!}"
                                       alt="{{ config('app.name') }}" class="img-rounded" width="140px"
                                       height="140px" /></span>
        </div>
        <form method="post" id="deleteForm">
        {{ csrf_field() }}
        <div class="row mb-2">
            <div class="col-md-2 col-sm-12 col-xs-12">
                <label>E-mail</label>
            </div>
            <div class="col-md-8 col-sm-12 col-xs-12">
                <input type="text" name="email" id="email" class="form-control">
            </div>
        </div>
        <div class="row mb-2">
            <div class="col-md-2 col-sm-12 col-xs-12">
                <label>Password</label>
            </div>
            <div class="col-md-8 col-sm-12 col-xs-12">
                <input type="password" name="password" id="password" class="form-control">
            </div>
        </div>
        <div class="row mb-2">
            <div class="col-md-2 col-sm-12 col-xs-12">
                <label>Reason</label>
            </div>
            <div class="col-md-8 col-sm-12 col-xs-12">
                <input type="text" name="reason" id="reason" class="form-control">
            </div>
        </div>
<!--        <div class="form-check">
            <input type="checkbox" class="form-check-input" id="acknowledge" name="acknowledge">
            <label class="form-check-label" for="acknowledge">Check me out</label>
        </div>-->
        <button type="submit" class="btn btn-primary mt-3">Delete account</button>
        </form>
    </div>

    <div class="cover-spin"></div>
</div>
<!-- General JS Scripts -->
<script src="{!! asset('plugins/jquery.min.js') !!}"></script>
<script src="{!! asset('plugins/popper.js') !!}"></script>
<script src="{!! asset('plugins/tooltip.js') !!}"></script>
<script src="{!! asset('plugins/bootstrap/js/bootstrap.min.js') !!}"></script>
<script src="{!! asset('plugins/nicescroll/jquery.nicescroll.min.js') !!}"></script>
<script src="{!! asset('plugins/moment.min.js') !!}"></script>
<script src="{!! asset('plugins/jquery-validation/jquery.validate.js') !!}"></script>
<script src="{!! asset('plugins/jquery-validation/additional-methods.js') !!}"></script>
<script src="{!! asset('js/stisla.js') !!}"></script>
<script src="{!! asset('plugins/select2/dist/js/select2.full.min.js') !!}"></script>
<!-- JS Libraies -->
<script src="{{ asset('plugins/izitoast/js/iziToast.js') }}"></script>
@include('vendor.lara-izitoast.toast')

<script>
$(document).on('submit', '#deleteForm', function (e){
    e.preventDefault();
    var formData = $(this).serialize();
    $(".text-danger").remove();

    $.ajax({
        url: "{{ url('user-account/submit-request-delete') }}",
        // processData: false,
        // contentType: false,
        type: 'POST',
        data: formData,
        success:function(response){
            $(".cover-spin").hide();
            if(response.success == true){
                showToastMessage(response.message,true);
                location.reload();
            }
            else {
                if(response.errors) {
                    var errors = response.errors;
                    $.each(errors, function (key, value) {
                        $('#' + key).next('.text-danger').remove();
                        $('#' + key).after('<div class="text-danger">' + value[0] + '</div>');
                    });
                }
                else {
                    showToastMessage(response.message,false);
                }
            }
        },
        beforeSend: function (){
            $(".cover-spin").show();
        },
        error: function (xhr) {
            $(".cover-spin").hide();
            showToastMessage('Something went wrong!!',false);
        },
    });
})

function showToastMessage(message,isSuccess){
    if(iziToast){
        if(isSuccess == true){
            iziToast.success({
                title: '',
                message: message,
                position: 'topRight',
                progressBar: false,
                timeout: 1000,
            });
        }else {
            iziToast.error({
                title: '',
                message: message,
                position: 'topRight',
                progressBar: false,
                timeout: 1500,
            });
        }
    }
}
</script>
</body>
</html>
