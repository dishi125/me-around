<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>MeAround</title>
        <link rel="stylesheet" href="{!! asset('css/style.css') !!}">
        <link rel="stylesheet" href="{!! asset('plugins/bootstrap/css/bootstrap.min.css') !!}">
        <style>
            .text-center{
                text-align: center;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="mt-1 text-center">
                {!! $qrcode !!}
                <div class="mt-3">
                    <a href="{{route('download.wedding.qr.code',['id' => $id])}}" class="btn btn-primary">Download</a>
                </div>
            </div>
        </div>
    </body>
</html>