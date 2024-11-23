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

    <title>@if (@$title) {{ @$title }} - @endif {{ config('app.name', 'Laravel') }}</title>

    <!-- General CSS Files -->
    <link rel="stylesheet" href="{!! asset('plugins/bootstrap/css/bootstrap.min.css') !!}">
    <link rel="stylesheet" href="{!! asset('plugins/fontawesome/css/all.min.css') !!}">

    <!-- CSS Libraries -->
    <link rel="stylesheet" href="{{ asset('plugins/izitoast/css/iziToast.css') }}">
    @yield('styles')

    <!-- Template CSS -->
    <link rel="stylesheet" href="{!! asset('css/style.css') !!}">
    <link rel="stylesheet" href="{!! asset('css/components.css') !!}">
    <link rel="stylesheet" href="{!! asset('plugins/select2/dist/css/select2.min.css') !!}">
</head>

<body>
    <?php
        $user = \Auth::user();
    ?>
    <div id="app">
        <div class="main-wrapper">
            <div class="navbar-bg"></div>
            <nav class="navbar navbar-expand-lg main-navbar">
                <div class="form-inline mr-auto">
                    <ul class="navbar-nav mr-3">
                        <li><a href="#" data-toggle="sidebar" class="nav-link nav-link-lg">
                                <i class="fas fa-bars"></i></a>
                        </li>
                    </ul>
                </div>
                <div class="pt-1 text-white">
                @if(in_array(\App\Models\EntityTypes::HOSPITAL, $user->all_entity_type_id) || in_array(\App\Models\EntityTypes::SHOP, $user->all_entity_type_id))
                        Your Recommended Code : <b style="text-decoration: underline;">{{Auth::user()->recommended_code}}</b>
                    @endif
                </div>
                <ul class="navbar-nav navbar-right">

                    <li class="dropdown"><a href="#" data-toggle="dropdown"
                            class="nav-link dropdown-toggle nav-link-lg nav-link-user">
                            <?php
                                $avatar = Auth::user()->avatar;
                            ?>
                            <img alt="image" src="@if (@$avatar) {!! asset($avatar) !!} @else {!! asset('img/avatar/avatar-1.png') !!} @endif" class="rounded-circle mr-1">

                            <div class="d-sm-none d-lg-inline-block">Hi {{ Auth::user()->name }}</div>
                        </a>
                        <div class="dropdown-menu dropdown-menu-right">
                            <div class="dropdown-title">Welcome, {{ Auth::user()->name }}</div>
                            <div class="dropdown-divider"></div>
                                <a href="{!! route('user.profile.show') !!}" class=" dropdown-item has-icon">
                                    <i class="far fa-user"></i> Profile
                                </a>
                            <a class="dropdown-item has-icon text-danger" href="{{ route('logout') }}"
                                onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </a>

                            <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                                <input type="hidden" name="redirectTo" id="redirect-to" value="{{\Route::currentRouteName()}}" />
                                @csrf
                            </form>
                        </div>
                    </li>
                </ul>
            </nav>
            <div class="main-sidebar">
                @include('user-layouts.sidebar')
            </div>

            <!-- Main Content -->
            <div class="main-content">
                <section class="section">
                    <div class="section-header">
                        @yield('header-content')
                    </div>

                    <div class="section-body">
                        @yield('content')
                    </div>
                </section>
            </div>
            <footer class="main-footer">
                <div class="footer-left">
                    Copyright &copy; {{date('Y')}}
                    <div class="bullet"></div> {{ config('app.name') }}
                </div>
                <div class="footer-right">
                    1.5.0
                </div>
            </footer>
        </div>
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
    @yield('scripts')

    <!-- Page Specific JS File -->
    <script src="{{ asset('js/page/bootstrap-modal.js')}}"></script>

    <!-- Template JS File -->
    <script src="{!! asset('js/scripts.js') !!}"></script>
    <script src="{!! asset('js/custom.js') !!}"></script>

    <!-- Custom scripts for all pages -->
    <script>
        var baseUrl = "{{ url('/') }}";
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
        $('.dropdown-toggle').dropdown();
    </script>

    @yield('page-script')

    @include('vendor.lara-izitoast.toast')
</body>

</html>
