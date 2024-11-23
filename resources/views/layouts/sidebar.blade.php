<?php
// $currentRole = \App\Models\Role::getRoleName();
 //$permissionList = \App\Models\PermissionUser::getPermission();

    $user = \Auth::user();
    $fontColor = "style=color:#ea4c89";
    //dd($user);
?>
<aside id="sidebar-wrapper">
    <div class="sidebar-brand">
        <a href="{!! env('app.url') !!}">
            <img src="{!! asset('img/MeAround-white.png') !!}" width="150" height="auto" alt="{!! config('app.name') !!}">
        </a>
    </div>
    <div class="sidebar-brand sidebar-brand-sm">
        <a href="{!! env('app.url') !!}">
            <img src="{!! asset('img/logo.png') !!}" width="60" height="60" alt="{!! config('app.name') !!}">
        </a>
    </div>
    <ul class="sidebar-menu mb-3">
        <li class="menu-header">{{ __('menu.dashboard') }}</li>
        <li class="{{ Request::segment(2) == 'dashboard' ? 'active' : '' }}">
            <a href="{{ route('admin.dashboard.index') }}" class="nav-link"><i class="fas fa-home"></i>
                <span>{{ __('menu.dashboard') }}</span></a>
        </li>


        @if ($user->hasRole("Admin"))
            <li class="dropdown {{ in_array(Request::segment(2),['brand-category','brands','brand-products']) ? 'active' : '' }}">
                <a href="javascript:void(0);" class="nav-link has-dropdown" data-toggle="dropdown"><i class="fas fa-columns"></i> <span>{{ __('menu.brand_section') }}</span></a>
                <ul class="dropdown-menu" >
                    <li class="{{ Request::segment(2) == 'brand-category' ? 'active' : '' }}">
                        <a href="{{ route('admin.brand-category.index') }}" class="nav-link">
                            <i class="fas fa-th"></i>
                            <span>{{ __('menu.brand_category') }} </span>
                        </a>
                    </li>
                    <li class="{{ Request::segment(2) == 'brands' ? 'active' : '' }}">
                        <a href="{{ route('admin.brands.index') }}" class="nav-link">
                            <i class="fas fa-tag"></i>
                            <span>{{ __('menu.brands') }}</span>
                        </a>
                    </li>
                    <li class="{{ Request::segment(2) == 'brand-products' ? 'active' : '' }}">
                        <a href="{{ route('admin.brand-products.index') }}" class="nav-link">
                            <i class="fas fa-cart-plus"></i>
                            <span>{{ __('menu.brand_product') }}</span>
                        </a>
                    </li>
                </ul>
            </li>

            <li class="{{ Request::segment(2) == 'paypal' ? 'active' : '' }}">
                <a href="{{ route('admin.paypal.index') }}" class="nav-link"><i class="fas fa-list"></i>
                    <span>{{ __('menu.paypal') }}</span>
                </a>
            </li>

            <li class="{{ Request::segment(2) == 'regular-payment' ? 'active' : '' }}">
                <a href="{{ route('admin.regular-payment.index') }}" class="nav-link"><i class="fas fa-list"></i>
                    <span>{{ __('menu.regular_payment') }}</span>
                </a>
            </li>

            <li class="{{ Request::segment(2) == 'hashtags' ? 'active' : '' }}">
                <a href="{{ route('admin.hashtags.index') }}" class="nav-link"><i class="fas fa-list"></i>
                    <span>{{ __('menu.hashtags') }}</span>
                </a>
            </li>

            <li class="{{ Request::segment(2) == 'research-form' ? 'active' : '' }}">
                <a href="{{ route('admin.research-form.index') }}" class="nav-link"><i class="fas fa-search"></i>
                    <span>{{ __('menu.research_form') }}</span>
                </a>
            </li>

            <li class="{{ Request::segment(2) == 'feed-log' ? 'active' : '' }}">
                <a href="{{ route('admin.feed-log.index') }}" class="nav-link"><i class="fas fa-list"></i>
                    <span>{{ __('menu.feed_log') }} <span class="feed_log_unread_count unread_count">0</span></span>
                </a>
            </li>

            <li class="{{ Request::segment(2) == 'product-orders' ? 'active' : '' }}">
                <a href="{{ route('admin.product-orders.index') }}" class="nav-link"><i class="fas fa-shopping-cart"></i>
                    <span>{{ __('menu.brand_orders') }}<span class="product_order_unread_count unread_count">0</span></span>
                </a>
            </li>

            <li class="{{ Request::segment(2) == 'admin-chat' ? 'active' : '' }}">
                <a href="{{ route('admin.admin-chat.index') }}" class="nav-link"><i class="fas fa-list"></i>
                    <span>{{ __('menu.admin_chat') }}<span class="admin_chat_unread_count unread_count">0</span></span>
                </a>
            </li>
        @endif

       {{--  @if ($user->can("requested-card-list"))
            <li class="{{ Request::segment(2) == 'requested-cards' ? 'active' : '' }}">
                <a href="{{ route('admin.requested.cards.index') }}" class="nav-link"><i class="fas fa-id-card"></i>
                    <span>Requested Cards <span class="requested_card_unread_count unread_count">0</span></span>
                </a>
            </li>
        @endif --}}
        <!-- <li class="menu-header">Customer</li> -->
        @if ($user->can("top-post-list"))
        <li class="{{ Request::segment(2) == 'top-post' ? 'active' : '' }}">
            <a href="{{ route('admin.top-post.index') }}" class="nav-link"><i {{$fontColor}}  class="fas fa-external-link-alt"></i>
                <span {{$fontColor}}>{{ __('menu.top_post') }}</span></a>
        </li>
        @endif

        @if ($user->can("user-list"))
        <li class="{{ Request::segment(2) == 'users' ? 'active' : '' }}">
            <a href="{{ route('admin.user.index') }}" class="nav-link"><i class="fas fa-users"></i>
                <span>{{ __('menu.user') }} <span class="user_unread_count unread_count">0</span></span></a>
        </li>
        @endif

        @if ($user->hasRole("Admin"))
            <li class="{{ Request::segment(2) == 'music-track' ? 'active' : '' }}">
                <a href="{{ route('admin.music-track.index') }}" class="nav-link"><i class="fas fa-list"></i>
                    <span>{{ __('menu.contents') }}</span>
                </a>
            </li>
        @endif

        @if ($user->hasRole("Admin"))
            <li class="{{ Request::segment(2) == 'upload-instagram' ? 'active' : '' }}">
                <a href="{{ route('admin.upload-instagram.index') }}" class="nav-link"><i class="fas fa-list"></i>
                    <span>{{ __('menu.upload_on_instagram') }} <span class="unread_count">0</span></span>
                </a>
            </li>
        @endif

        @if ($user->hasRole("Admin"))
            <li class="{{ Request::segment(2) == 'instagram-connect-log' ? 'active' : '' }}">
                <a href="{{ route('admin.instagram-connect-log.index') }}" class="nav-link"><i class="fas fa-list"></i>
                    <span>{{ __('menu.instagram_connect_log') }} <span class="instagram_connect_log_unread_count unread_count">0</span></span>
                </a>
            </li>
        @endif

        @if ($user->hasRole("Admin"))
            <li class="{{ Request::segment(2) == 'reels-downloader-log' ? 'active' : '' }}">
                <a href="{{ route('admin.reels-downloader-log.index') }}" class="nav-link"><i class="fas fa-download"></i>
                    <span>{{ __('menu.reels_downloader_log') }}</span>
                </a>
            </li>
        @endif

        @if ($user->can("outside-user-list"))
            <li class="{{ Request::segment(2) == 'outside-community-user' ? 'active' : '' }} outside-user-tab">
                <a href="{{ route('admin.outside-user.list') }}" class="nav-link"><i class="fas fa-users"></i>
                    <span>{{ __('menu.outside_user') }} <span class="outside_unread_count unread_count">0</span></span></a>
            </li>
        @endif

        @if ($user->hasRole("Admin"))
            <li class="{{ Request::segment(2) == 'non-login-user' ? 'active' : '' }} outside-user-tab">
                <a href="{{ route('admin.non-login-user.list') }}" class="nav-link"><i class="fas fa-users"></i>
                    <span>{{ __('menu.non_login_user') }}</span>
                </a>
            </li>
        @endif

        @if ($user->can("business-client-list"))
        <li class="{{ Request::segment(4) == 'posts' ? 'active' : '' }}">
            <a href="{{ route('admin.business-client.get.shop.post') }}" class="nav-link"><i class="fas fa-list"></i>
                <span>{{ __('menu.check_shop_post') }} <span class="shop_post_unread_count unread_count">0</span></span>
            </a>
        </li>
        @endif

        @if ($user->hasRole("Admin"))
            <li class="{{ Request::segment(2) == 'like-order' ? 'active' : '' }}">
                <a href="{{ route('admin.like-order.index') }}" class="nav-link"><i class="fas fa-list"></i>
                    <span class="both">{{ __('menu.like_order') }} <span class="like_order_real_unread_count unread_count unread_purple_count">0</span> <span class="like_order_unread_count unread_count">0</span></span>
                </a>
            </li>
        @endif

        @if ($user->hasRole("Admin"))
            <li class="{{ Request::segment(2) == 'message' ? 'active' : '' }}">
                <a href="{{ route('admin.message.index') }}" class="nav-link"><i class="fas fa-list"></i>
                    <span>{{ __('menu.message') }} <span class="message_unread_count unread_count">0</span></span>
                </a>
            </li>
        @endif

        @if ($user->hasRole("Admin"))
            <li class="{{ Request::segment(2) == 'coupon' ? 'active' : '' }}">
                <a href="{{ route('admin.coupon.index') }}" class="nav-link"><i class="fas fa-list"></i>
                    <span>{{ __('menu.coupon') }}</span>
                </a>
            </li>
        @endif

        @if ($user->hasRole("Admin"))
            <li class="{{ Request::segment(2) == 'insta-category' ? 'active' : '' }}">
                <a href="{{ route('admin.insta-category.index') }}" class="nav-link"><i class="fas fa-list"></i>
                    <span>{{ __('menu.insta_category') }}</span>
                </a>
            </li>
        @endif

        @if ($user->hasRole("Admin"))
            <li class="{{ Request::segment(2) == 'reasons-delete-account' ? 'active' : '' }}">
                <a href="{{ route('admin.reasons-delete-account.index') }}" class="nav-link"><i class="fas fa-list"></i>
                    <span>{{ __('menu.reasons_delete_account') }} <span class="reasons_delete_account_unread_count unread_count">0</span></span>
                </a>
            </li>
        @endif

        @if ($user->hasRole("Admin"))
            <li class="{{ Request::segment(2) == 'deleted-users' ? 'active' : '' }}">
                <a href="{{ route('admin.deleted-users.index') }}" class="nav-link"><i class="fas fa-list"></i>
                    <span>{{ __('menu.deleted_users') }} <span class="deleted_user_unread_count unread_count">0</span></span>
                </a>
            </li>
        @endif

        @if ($user->hasRole("Admin"))
            <li class="{{ Request::segment(2) == 'reported-group-chat' ? 'active' : '' }}">
                <a href="{{ route('admin.reported-group-chat.index') }}" class="nav-link"><i class="fas fa-list"></i>
                    <span>{{ __('menu.reported_group_chat') }} <span class="reported_user_unread_count unread_count">0</span></span>
                </a>
            </li>
        @endif

        @if ($user->hasRole("Admin"))
            <li class="{{ Request::segment(2) == 'reported-message' ? 'active' : '' }}">
                <a href="{{ route('admin.reported-message.index') }}" class="nav-link"><i class="fas fa-list"></i>
                    <span>{{ __('menu.reported_message') }} <span class="reported_message_unread_count unread_count">0</span></span>
                </a>
            </li>
        @endif

        @if ($user->hasRole("Admin"))
        <li class="{{ Request::segment(2) == 'reported-shop' ? 'active' : '' }}">
            <a href="{{ route('admin.reported-shop.index') }}" class="nav-link"><i class="fas fa-list"></i>
                <span>{{ __('menu.report_shop') }} <span class="report_shop_unread_count unread_count">0</span></span>
            </a>
        </li>
        @endif

        @if ($user->can("review-list"))
            <li class="{{ Request::segment(2) == 'check-review' ? 'active' : '' }}">
                <a href="{{ route('admin.check-review.index') }}" class="nav-link"><i class="fas fa-list"></i>
                    <span>{{ __('menu.check_review_post') }} <span class="review_unread_count unread_count"></span></span>
                </a>
            </li>
        @endif

        @if ($user->can("check-bad-complete-list"))
        <li class="{{ Request::segment(2) == 'bad-complete' ? 'active' : '' }}">
            <a href="{{ route('admin.bad-complete.index') }}" class="nav-link"><i class="fas fa-list"></i>
                <span>{{ __('menu.check_bad_complete') }} <span class="bad_complete_unread_count unread_count">0</span></span>
            </a>
        </li>
        @endif

        @if ($user->can("community-list"))
        <li class="{{ Request::segment(2) == 'community' ? 'active' : '' }}">
            <a href="{{ route('admin.community.index') }}" class="nav-link"><i class="fas fa-building"></i>
                <span>{{ __('menu.check_community_post') }}</span></a>
            </li>
        @endif

        @if ($user->can("comment-list"))
        <li class="{{ Request::segment(2) == 'comment' ? 'active' : '' }}">
            <a href="{{ route('admin.comment.index') }}" class="nav-link"><i class="fas fa-comments"></i>
                <span>{{ __('menu.check_comment') }}</span></a>
            </li>
        @endif

        @if ($user->can("business-client-list"))
        <li class="{{ (Request::segment(2) == 'business-client' && empty(Request::segment(3))) ? 'active' : '' }}">
            <a href="{{ route('admin.business-client.index') }}" class="nav-link"><i {{$fontColor}} class="fas fa-map-marker-alt"></i>
                <span {{$fontColor}}>{{ __('menu.business_client') }}</span></a>
        </li>
        @endif

        @if ($user->hasRole("Admin"))
            <li class="{{ Request::segment(2) == 'instagram-account' ? 'active' : '' }} ">
                <a href="{{ route('admin.instagram-account.index') }}" class="nav-link"><i class="fab fa-instagram"></i>
                    <span>{{ __('menu.instagram_account') }}</span>
                </a>
            </li>
        @endif

        @if ($user->can("business-client-list"))
        <li class="{{ Request::segment(2) == 'outside-user' ? 'active' : '' }}">
            <a href="{{ route('admin.outside-user.index') }}" class="nav-link"><i class="fas fa-users"></i>
                <span>Outside Business</span></a>
        </li>
        @endif

        @if ($user->can("requested-client-list"))
        <li class="{{ Request::segment(2) == 'requested-client' ? 'active' : '' }}">
            <a href="{{ route('admin.requested-client.index') }}" class="nav-link"><i class="far fa-id-badge"></i>
                <span>{{ __('menu.requested_client') }} <span class="requested_unread_count unread_count">0</span></span></a>
        </li>
        @endif

        @if ($user->can("reported-client-list"))
        <li class="{{ Request::segment(2) == 'reported-client' ? 'active' : '' }}">
            <a href="{{ route('admin.reported-client.index') }}" class="nav-link"><i class="fas fa-clipboard"></i>
                <span>{{ __('menu.reported_client') }} <span class="reported_unread_count unread_count">0</span></span></a>
        </li>
        @endif

        @if ($user->can("suggest-custom-list"))
        <li class="{{ Request::segment(2) == 'suggest-custom' ? 'active' : '' }}"><a class="nav-link"
            href="{{ route('admin.suggest-custom.index') }}"><i class="fas fa-plus-circle"></i>
            <span>{{ __('menu.suggest_custom') }}</span></a>
        </li>
        @endif

        @if ($user->can("category-list"))
        <li class="{{ Request::segment(2) == 'category' ? 'active' : '' }}"><a class="nav-link"
            href="{{ route('admin.category.index') }}"><i {{$fontColor}} class="fas fa-plus-circle"></i>
            <span {{$fontColor}}>{{ __('menu.categories') }}</span></a>
        </li>
        @endif

        @if ($user->hasRole("Admin"))
            <li class="{{ Request::segment(2) == 'big-category' ? 'active' : '' }}">
                <a href="{{ route('admin.big-category.index') }}" class="nav-link"><i {{$fontColor}} class="fas fa-plus-circle"></i>
                    <span {{$fontColor}}>{{ __('menu.big_categories') }}</span>
                </a>
            </li>
        @endif

        @if ($user->can("currency-coin-list"))
        <li class="{{ Request::segment(2) == 'currency-coin' ? 'active' : '' }}"><a class="nav-link"
            href="{{ route('admin.currency-coin.index') }}"><i class="fas fa-money-check-alt"></i>
            <span>{{ __('menu.currency_coin') }}</span></a>
        </li>
        @endif

        @if ($user->can("reload-coin-list"))
        <li class="{{ Request::segment(2) == 'reload-coin' ? 'active' : '' }}"><a class="nav-link"
            href="{{ route('admin.reload-coin.index') }}"><i class="fas fa-money-bill-alt"></i>
            <span>Reload Coin <span class="reload_coin_unread_count unread_count">0</span></span></a>
        </li>
        @endif

        @if ($user->can("manager-list"))
        <li class="{{ Request::segment(2) == 'manager' ? 'active' : '' }}"><a class="nav-link"
            href="{{ route('admin.manager.index') }}"><i {{$fontColor}} class="fas fa-user-friends"></i>
            <span {{$fontColor}}>{{ __('menu.company_supporter') }}</span></a>
        </li>
        @endif

        @if ($user->can("role-list"))
        <li class="{{ Request::segment(2) == 'roles' ? 'active' : '' }}"><a class="nav-link"
            href="{{ route('admin.roles') }}"><i class="fas fa-user-cog"></i>
            <span>{{ __('menu.role_list') }}</span></a>
        </li>
        @endif

        @if ($user->can("announcement-list"))
        <li class="{{ Request::segment(2) == 'announcement' ? 'active' : '' }}"><a class="nav-link"
            href="{{ route('admin.announcement.index') }}"><i class="fas fa-bullhorn"></i>
            <span>{{ __('menu.annoucement') }}</span></a>
        </li>
        @endif

        @if ($user->can("important-custom-list"))
        <li class="{{ Request::segment(2) == 'important-setting' ? 'active' : '' }}"><a class="nav-link"
            href="{{ route('admin.important-setting.index') }}"><i class="fas fa-chalkboard-teacher"></i>
            <span>{{ __('menu.important_settings') }}</span></a>
        </li>
        @endif

        @if ($user->can("reward-instagram-list"))
        <li class="{{ Request::segment(2) == 'reward-instagram' ? 'active' : '' }}"><a class="nav-link"
            href="{{ route('admin.reward-instagram.index') }}"><i class="fab fa-instagram"></i>
            <span>{{ __('menu.sns_reward') }}</span></a>
        </li>
        @endif

        @if ($user->can("activity-log-list"))
        <li class="{{ Request::segment(2) == 'activity-log' ? 'active' : '' }}"><a class="nav-link"
            href="{{ route('admin.activity-log.index') }}"><i class="fas fa-list-alt"></i>
            <span>{{ __('menu.activity_log') }}</span></a>
        </li>
        @endif

        @if ($user->can("manager-activity-log-list"))
        <li class="{{ Request::segment(2) == 'manager-activity-log' ? 'active' : '' }}"><a class="nav-link"
            href="{{ route('admin.manager-activity-log.index') }}"><i class="fas fa-list-alt"></i>
            <span>{{ __('menu.manager_log') }}</span></a>
        </li>
        @endif

        @if ($user->can("my-business-client-list"))
        <li class="{{ Request::segment(2) == 'my-business-client' ? 'active' : '' }}">
            <a href="{{ route('admin.my-business-client.index') }}" class="nav-link"><i class="fas fa-address-card"></i>
                <span>{{ __('menu.business_client_list') }}</span></a>
        </li>
        @endif

        @if ($user->can("association-list"))
        <li class="{{ Request::segment(2) == 'association' ? 'active' : '' }}">
            <a href="{{ route('admin.association.index') }}" class="nav-link"><i class="fas fa-list-alt"></i>
                <span>{{ __('menu.association') }}</span></a>
        </li>
        @endif

        @if ($user->hasRole("Admin"))
            <li class="{{ Request::segment(2) == 'theme-section' ? 'active' : '' }}">
                <a href="{{ route('admin.theme-section.index') }}" class="nav-link"><i class="fas fa-directions"></i>
                    <span>Theme Section</span></a>
            </li>
        @endif

        @if ($user->can("cards-list"))
        <li class="{{ Request::segment(2) == 'cards' ? 'active' : '' }}">
            <a href="{{ route('admin.cards.index') }}" class="nav-link"><i class="fas fa-id-card"></i>
                <span>{{ __('menu.default_card') }}</span>
            </a>
        </li>
        @endif
        @if ($user->can("wedding-list"))
            <li class="{{ Request::segment(2) == 'wedding' ? 'active' : '' }}">
                <a href="{{ route('admin.wedding.index') }}" class="nav-link"><i class="fas fa-ring"></i>
                    <span>Wedding <i class="fas fa-rings-wedding"></i></span>
                </a>
            </li>
        @endif

        @if ($user->hasRole("Admin"))
            <li class="{{ Request::segment(2) == 'certification-exam' ? 'active' : '' }}">
                <a href="{{ route('admin.certification-exam.index') }}" class="nav-link"><i class="fas fa-certificate"></i>
                    <span>{{ __('menu.certification_exam') }}</span></a>
            </li>
        @endif

    </ul>
</aside>
