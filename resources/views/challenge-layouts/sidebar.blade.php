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
            @elseif(request()->routeIs('challenge.*'))
            @else
            <img src="{!! asset('img/logo.png') !!}" width="60" height="60" alt="{!! config('app.name') !!}">
            @endif
        </a>
    </div>
    <ul class="sidebar-menu mb-3">
        <li class="menu-header">Dashboard</li>
        <li class="{{ Request::segment(2) == 'dashboard' ? 'active' : '' }}">
            <a href="{{ route('challenge.dashboard.index') }}" class="nav-link"><i class="fas fa-home"></i>
                <span>{{ __('menu.dashboard') }}</span>
            </a>
        </li>

        <li class="{{ Request::segment(2) == 'users' ? 'active' : '' }}">
            <a href="{{ route('challenge.user.index') }}" class="nav-link"><i class="fas fa-users"></i>
                <span>{{ __('menu.user') }}</span></a>
        </li>

        <li class="{{ Request::segment(2) == 'calendar' ? 'active' : '' }}">
            <a href="{{ route('challenge.calendar.period-challenge.index') }}" class="nav-link"><i class="fas fa-calendar"></i>
                <span>{{ __('menu.calender') }}</span></a>
        </li>

        <li class="{{ Request::segment(2) == 'challenge-page' ? 'active' : '' }}">
            <a href="{{ route('challenge.challenge-page.index') }}" class="nav-link"><i class="fas fa-trophy"></i>
                <span>{{ __('menu.challenge_page') }}</span></a>
        </li>

        <li class="{{ Request::segment(2) == 'thumb-image' ? 'active' : '' }}">
            <a href="{{ route('challenge.thumb-image.index') }}" class="nav-link"><i class="fas fa-images"></i>
                <span>{{ __('menu.thumb_image') }}</span></a>
        </li>

        <li class="{{ Request::segment(2) == 'delete-account' ? 'active' : '' }}">
            <a href="{{ route('challenge.delete-account.index') }}" class="nav-link"><i class="fas fa-user-times"></i>
                <span>{{ __('menu.delete_account') }}</span></a>
        </li>

        <li class="{{ Request::segment(2) == 'verification' ? 'active' : '' }}">
            <a href="{{ route('challenge.verification.index') }}" class="nav-link"><i class="fas fa-check-double"></i>
                <span>{{ __('menu.verification') }} <span class="verification_unread_count unread_count">0</span></span></a>
        </li>

        <li class="{{ Request::segment(2) == 'order' ? 'active' : '' }}">
            <a href="{{ route('challenge.order.index') }}" class="nav-link"><i class="fas fa-list"></i>
                <span>{{ __('menu.order') }}</span></a>
        </li>

        <li class="{{ Request::segment(2) == 'category' ? 'active' : '' }}">
            <a href="{{ route('challenge.category.index') }}" class="nav-link"><i class="fas fa-list"></i>
                <span>{{ __('menu.challenge_category') }}</span></a>
        </li>

        <li class="{{ Request::segment(2) == 'admin-push' ? 'active' : '' }}">
            <a href="{{ route('challenge.admin-push.index') }}" class="nav-link"><i class="fas fa-list"></i>
                <span>{{ __('menu.admin_push_management') }}</span></a>
        </li>

        <li class="{{ Request::segment(2) == 'policy' ? 'active' : '' }}">
            <a href="{{ route('challenge.policy.index') }}" class="nav-link"><i class="fas fa-list"></i>
                <span>{{ __('menu.policy') }}</span></a>
        </li>

        <li class="{{ Request::segment(2) == 'link' ? 'active' : '' }}">
            <a href="{{ route('challenge.link.index') }}" class="nav-link"><i class="fas fa-link"></i>
                <span>{{ __('menu.link') }}</span></a>
        </li>

        <li class="{{ Request::segment(2) == 'naming-customizing' ? 'active' : '' }}">
            <a href="{{ route('challenge.naming-customizing.index') }}" class="nav-link"><i class="fas fa-list"></i>
                <span>{{ __('menu.naming_customizing') }}</span></a>
        </li>

        <li class="{{ Request::segment(2) == 'invitation-management' ? 'active' : '' }}">
            <a href="{{ route('challenge.invitation-management.index') }}" class="nav-link"><i class="fas fa-list"></i>
                <span>{{ __('menu.invitation_management') }} <span class="invitation_unread_count unread_count">0</span></span></a>
        </li>

        <li class="{{ Request::segment(2) == 'invite-text' ? 'active' : '' }}">
            <a href="{{ route('challenge.invite-text.index') }}" class="nav-link"><i class="fas fa-list"></i>
                <span>{{ __('menu.invite_text') }}</span></a>
        </li>

        <li class="{{ Request::segment(2) == 'notification-admin' ? 'active' : '' }}">
            <a href="{{ route('challenge.notification-admin.index') }}" class="nav-link"><i class="fas fa-list"></i>
                <span>{{ __('menu.notification_for_admin') }}</span></a>
        </li>

        <li class="{{ Request::segment(2) == 'admin-notice' ? 'active' : '' }}">
            <a href="{{ route('challenge.admin-notice.index') }}" class="nav-link"><i class="fas fa-list"></i>
                <span>{{ __('menu.admin_notice') }}</span></a>
        </li>

        <li class="{{ Request::segment(2) == 'kakao-talk-link' ? 'active' : '' }}">
            <a href="{{ route('challenge.kakao-talk-link.index') }}" class="nav-link"><i class="fas fa-list"></i>
                <span>{{ __('menu.open_kakao_talk_link') }}</span></a>
        </li>
    </ul>
</aside>
