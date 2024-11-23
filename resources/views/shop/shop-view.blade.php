<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="apple-touch-icon" sizes="96x96" href="{!! asset('favicon/favicon-96x96.png') !!}">
    <link rel="icon" type="image/png" sizes="96x96" href="{!! asset('favicon/favicon-96x96.png') !!}">
    <link rel="shortcut icon" href="{!! asset('favicon/favicon-32x32.png') !!}">
    <link rel="manifest" href="{!! asset('favicon/manifest.json') !!}">
    <meta name="msapplication-TileColor" content="#43425D">
    <meta name="msapplication-TileImage" content="{!! asset('favicon/ms-icon-144x144.png') !!}">
    <meta name="theme-color" content="#43425D">

    <meta property="og:title" content="{{ $shopData->main_name }} {{ $shopData->shop_name }}" />
    <meta property="og:site_name" content="{{ $shopData->main_name }} {{ $shopData->shop_name }}" />
    <meta property="og:description" content="{{ $shopData->main_name }} {{ $shopData->shop_name }}" />
    @if (isset($shopData->main_profile_images) && !empty($shopData->main_profile_images))
        <meta property="og:image" content="{{ $shopData->main_profile_images[0]['image'] ?? '' }}" />
    @endif
    <meta property="og:type" content="website" />
    <title>{{ $shopData->main_name }} {{ $shopData->shop_name }}</title>

    <!-- General CSS Files -->

    <link rel="stylesheet" href="{!! asset('plugins/fontawesome/css/all.min.css') !!}">
    <link rel="stylesheet" href="{!! asset('plugins/bootstrap/css/bootstrap.min.css') !!}">
    <link rel="stylesheet" href="{!! asset('plugins/slick/slick.css') !!}">
    <link rel="stylesheet" href="{!! asset('css/shop.css') !!}">
    @yield('styles')

</head>

<body>
    <div id="app" class="">
        @if ($shopData)
            <div class="shop-view theme ">
                @if (isset($shopData->main_profile_images))
                    <div class="shop-main-image image-custom-slider pt-5">
                        @foreach ($shopData->main_profile_images as $item)
                            @if ($item->image)
                                <div style="height: 300px">
                                    <img src="{{ $item->image }}" />
                                </div>
                            @endif
                        @endforeach
                    </div>
                @endif

                <div class="shop-heading">
                    <h1>{{ $shopData->main_name }}</h1>
                    <h4>{{ $shopData->shop_name }}</h4>
                </div>


                <div class="shop-content">
                    <ul class="nav nav-tabs pt-4 justify-content-center" id="shopTab" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" id="info-tab" data-toggle="tab" href="#info" role="tab"
                                aria-controls="Info" aria-selected="true">Info</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="review-tab" data-toggle="tab" href="#review" role="tab"
                                aria-controls="Review" aria-selected="false">Review</a>
                        </li>
                    </ul>
                    <div class="tab-content" id="shopTabContent">
                        <div class="tab-pane fade show active" id="info" role="tabpanel" aria-labelledby="info-tab">
                            <div class="shop-count-info mt-4">
                                <div>
                                    <span>125</span>
                                    <span>Followers</span>
                                </div>
                                <div>
                                    <span>150</span>
                                    <span>Completed Work</span>
                                </div>
                                <div>
                                    <span>211</span>
                                    <span>Portfolio</span>
                                </div>
                                <div>
                                    <span>41</span>
                                    <span>Review</span>
                                </div>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="review" role="tabpanel" aria-labelledby="review-tab">2</div>
                    </div>
                </div>
            </div>
        @endif
        <!-- Page Specific JS File -->

        <script src="{!! asset('plugins/jquery.min.js') !!}"></script>
        <script src="{!! asset('plugins/bootstrap/js/bootstrap.min.js') !!}"></script>
        <script src="{!! asset('plugins/slick/slick.js') !!}"></script>

        <script type="text/javascript">
            $('.image-custom-slider').slick({
                slidesToShow: 1,
                slidesToScroll: 1,
                infinite: true,
                prevArrow: false,
                nextArrow: false,
                dots: true,
            });
        </script>

</body>

</html>
