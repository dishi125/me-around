<?php 
    $user = \Auth::user();
    $association = \App\Models\Association::join('association_users','association_users.association_id','associations.id')
        ->where('association_users.user_id',$user->id)
        ->whereIn('association_users.type',[\App\Models\AssociationUsers::PRESIDENT,\App\Models\AssociationUsers::MANAGER])
        ->groupBy('associations.id')
        ->count();
?>

<aside id="sidebar-wrapper">
    <div class="sidebar-brand">
        <a href="{!! env('app.url') !!}">
            <img src="{!! asset('img/logo-main.png') !!}" width="150" height="auto" alt="{!! config('app.name') !!}">
        </a>
    </div>
    <div class="sidebar-brand sidebar-brand-sm">
        <a href="{!! env('app.url') !!}">
            <img src="{!! asset('img/logo.png') !!}" width="60" height="60" alt="{!! config('app.name') !!}">
        </a>
    </div>
    <ul class="sidebar-menu mb-3">
        <li class="menu-header">Dashboard</li>
        <li class="{{ Request::segment(2) == 'dashboard' ? 'active' : '' }}">
            <a href="{{ route('user.dashboard.index') }}" class="nav-link"><i class="fas fa-home"></i>
                <span>Dashboard</span>
            </a>
        </li>

        @if($association > 0)
            <li class="{{ Request::segment(2) == 'association' ? 'active' : '' }}">
                <a href="{{ route('user.association.index') }}" class="nav-link"><i class="fas fa-list-alt"></i>
                    <span>Association</span>
                </a>
            </li>
        @endif

    </ul>
</aside>