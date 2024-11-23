<?php
    $user = \Auth::user();
?>

<aside id="sidebar-wrapper">
<!--    <div class="sidebar-brand">
        <a href="{!! env('app.url') !!}">
            @if(request()->routeIs('spa.*'))
            <img src="{!! asset('img/spa-logo.png') !!}" width="150" height="auto" alt="{!! config('app.name') !!}">
            @else
            <img src="{!! asset('img/logo-main.png') !!}" width="150" height="auto" alt="{!! config('app.name') !!}">
            @endif
        </a>
    </div>-->
    <div class="sidebar-brand sidebar-brand-sm">
        <a href="{!! env('app.url') !!}">
            @if(request()->routeIs('spa.*'))
            <img src="{!! asset('img/spa-logo.png') !!}" width="60" height="60" alt="{!! config('app.name') !!}">
            @else
            <img src="{!! asset('img/logo.png') !!}" width="60" height="60" alt="{!! config('app.name') !!}">
            @endif
        </a>
    </div>
    <ul class="sidebar-menu mb-3">
        <li class="menu-header">Dashboard</li>
        <li class="{{ Request::segment(2) == 'dashboard' ? 'active' : '' }}">
            <a href="{{ route('qr-code.dashboard.index') }}" class="nav-link"><i class="fas fa-home"></i>
                <span>Dashboard</span>
            </a>
        </li>
    </ul>
</aside>
