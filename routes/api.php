<?php

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
 */
$exceptMethods = ['except' => ['create', 'edit']];

Route::post('/apple-redirect', 'DeeplinkController@appleRedirect');
Route::name('api')->namespace('Api')->group(function () {

    // Clear View, Config, Cache
    Route::get('/clear', function () {
        \Artisan::call('optimize:clear');
        dd('cleard');
    })->name('.clear');

    // Manager Permission
    // Route::get('permission/manager', 'CronjobController@managerpermission')->name('.permission.add');

    // Authentication
    Route::post('/register', 'AuthenticateController@register')->name('.auth.register');
    Route::post('/register-validate', 'AuthenticateController@registerValidate')->name('.validate.register');
    Route::post('/login', 'AuthenticateController@login')->name('.auth.login');
    Route::post('/check/mobile', 'AuthenticateController@checkMobile')->name('.auth.check.mobile');
    Route::post('/forgot/email', 'AuthenticateController@forgotEmail')->name('.auth.forgot.email');
    Route::post('/forgot/password', 'AuthenticateController@forgotPassword')->name('.auth.forgot.password');
    Route::post('/update/password', 'AuthenticateController@changePassword')->name('.update.password');
    Route::post('/check/code', 'UserProfileController@checkRecommondedCode')->name('auth.check.code');

    // Admin in Application
    Route::post('/admin/login', 'AdminController@adminLogin')->name('admin.auth.login');

    // Route::get('/deeplink', 'AuthenticateController@sendDefferedDeepLink')->name('.deeplink');

    // Address
    Route::post('/country', 'AddressController@getCountry')->name('.country.index');
    Route::post('/current/city', 'AddressController@getCurrentCity')->name('.current.city');
    Route::post('/city', 'AddressController@getAllCity')->name('.city.index');

    // Category
    Route::post('/category', 'CategoryController@index')->name('.category.index');
    Route::get('/currency', 'CategoryController@indexCurrency')->name('.currency.index');

    Route::post('/get/mearound/category', 'CategoryController@getMearoundCategory');

    Route::post('/get/country-wise/category', 'CategoryController@getCountryWiseCategory');
    // Shops
    Route::post('/get/all/shops', 'ShopController@getAllShops')->name('.get.all.shops');
    Route::post('/get/all/shops/distance', 'ShopController@getAllShopsDistance')->name('.get.all.shops.distance');
    // Route::get('/get/category/shops/{id}', 'ShopController@getCategoryShops')->name('.get.category.shops');
    Route::get('/get/shop/detail/{id}', 'ShopController@getShopDetail')->name('.get.shop.detail');
    Route::post('/search/shop', 'ShopController@searchShop')->name('.search.shop');

    // Hospital
    Route::get('/get/post/languages', 'PostController@postLanguages')->name('.get.post.languages');
    Route::post('/get/all/hospital/posts', 'HospitalController@getAllHospital')->name('.get.all.hospital');
    Route::post('/get/best/hospital/posts', 'HospitalController@getBestHospitals')->name('.get.best.hospital');
    Route::get('/get/hospital/post/detail/{id}', 'HospitalController@getPostDetail')->name('.get.hospital.detail');
    Route::post('/get/all/hospital/event', 'HospitalController@getHospitalPosts');


    // Reviews
    Route::post('/get/shop/review', 'ReviewsController@getShopReview')->name('.get.shop.review');
    Route::post('/get/hospital/review', 'ReviewsController@getHospitalReview')->name('.get.hospital.review');
    Route::post('/get/shop/review/detail/{id}', 'ReviewsController@getShopReviewDetail')->name('.get.shop.review.detail');
    Route::post('/get/hospital/review/detail/{id}', 'ReviewsController@getHospitalReviewDetail')->name('.get.hospital.review.detail');
    Route::post('/update/shop/review/detail/{id}', 'ReviewsController@updateShopReviewDetail')->name('.set.shop.review.detail');
    Route::post('/update/hospital/review/detail/{id}', 'ReviewsController@updateShopReviewDetail')->name('.set.hospital.review.detail');

    //Community
    Route::post('/get/community', 'CommunityController@getCommunity')->name('.get.community');
    Route::post('/get/community/detail/{id}', 'CommunityController@getCommunityDetail')->name('.get.community.detail');
    Route::post('/get/community/category', 'CommunityController@getCommunityCategories')->name('.get.community.category');

    //Home
    Route::post('/get/home/detail', 'HomeController@index')->name('.get.home.detail');
    Route::post('/get/recent/liked/post', 'HomeController@getRecentLikedPost')->name('.get.recent.liked.post');
    Route::post('/get/config/link', 'HomeController@getConfigLink')->name('.get.config.link');
    Route::post('/get/multiple/config', 'HomeController@getMultipleConfig');
    Route::get('/get/following/shops', 'HomeController@followingShops')->name('.get.user.following.shops');

    //Sugget Custom
    Route::post('/get/custom/category', 'SuggestCustomController@getCustomCategory')->name('.get.custom.category');
    Route::post('/get/all/custom/shops', 'SuggestCustomController@getAllCustomShops')->name('.get.all.custom.shops');
    Route::get('/get/all/custom/shops/deeplink', 'SuggestCustomController@getDeeplink')->name('.get.all.custom.shops.deeplink');


    // Association
    Route::post('/get/community/tabs', 'AssociationController@getCommunityTabs')->name('get.community.tabs');
    Route::post('/get/sub-category', 'AssociationController@getSubCategoryList')->name('get.sub-category');
    Route::post('/get/association', 'AssociationController@getAssociation')->name('get.association');
    Route::post('/get/association/detail/{id}', 'AssociationController@getAssociationDetail')->name('get.association.detail');

    Route::post('/list/all/shops', 'ShopListController@index')->name('.get.auth.all.shops');
    Route::post('/list/all/shops/new', 'ShopListController@listAllShops');

    // HashTag
    Route::post('hashtags/search', 'HashTagController@listHashTag');
    Route::post('hashtags/detail', 'HashTagController@hashTagDetail');
    Route::post('/get/all/portfolio', 'HomeController@LoadAllPortfolio');
    Route::post('/get/portfolio/detail/{id}', 'ShopController@loadPortfolio');

    Route::post('new/home/detail', 'NewHomepageController@index');

    Route::get('get/default/country/settings', 'AuthenticateController@getDefaultSetting');

    // Non Login user
    Route::get('/get/non-login/points', 'CardsController@getWithoutLoginUserPoints');
    Route::get('/non-login/cards', 'CardsController@getNonLoginCards');
    Route::post('/save/non-login/details', 'NonLoginUserDetailController@saveDetails');
    Route::post('/get/non-login/details', 'NonLoginUserDetailController@getDetails');
    Route::post('/get/non-login/message', 'NonLoginUserDetailController@getMessages');
    Route::post('/get/non-login/notice', 'NonLoginUserDetailController@getNotice');
    Route::post('/get/group-chat', 'NonLoginUserDetailController@getGroupChat');


    Route::get("menu/items", "MenuSettingApiController@index");
    Route::get("get/app/versions", "MenuSettingApiController@appVersions");

    Route::post('/get/card/details/non-login', 'CardsController@getCardDetail');
    Route::post('/get/card/level/details/non-login', 'CardsController@getCardLevelDetail');

    Route::post('give/card/love', 'CardLoveController@giveLoveNonLogin');


    // Brand
    Route::post("get/brands", "BrandApiController@index");
    Route::post("get/brand/products", "BrandApiController@brandProducts");

    // Social Connect
    Route::post("connect/social-profile", 'AuthenticateController@connectSocialProfile');
    Route::post('/get/all/non-login/search/detail', 'ShopListController@getAllSearchResults');

    Route::post("remove/duplicate/posts/entry/{isapi?}","NewHomepageController@removeDuplicatePostsData");

    // Update Details
    Route::post("checkalready","ShopProfileController@checkAlready");
    Route::post("hashtag-issue","ShopProfileController@fixedHashTagIssue");
    Route::post("update/carddetail","ShopProfileController@updateCardDetails");
    // Update Details

    Route::post('get/user/category','ManageUserCategoryController@index');
    Route::post('update/user/category','ManageUserCategoryController@editUserCategory');

    Route::post('send/business/notification','ShopProfileController@sendBusinessNotification');
    Route::get('/get/shop-item-category/list/{id}', 'ShopPriceController@indexPriceCategory')->name('.shop-item-category.list');
    Route::get('check-price-debug','ShopProfileController@priceDebug');

    Route::post('/get/user/detail','UserProfileController@UserDetail'); //for user info inside group chat

    Route::post('/get/shop/link','ShopProfileController@shopProfileLink');

    Route::post('save_location','UserProfileController@saveLocation');
    Route::post('last_location','UserProfileController@lastLocation');

    Route::post('business_referral_list','UserProfileController@referralBusinessUserList');
    Route::post('normal_referral_list','UserProfileController@referralNormalUserList');

    //Spa category - Start
    Route::post('/list/all/shops/spa', 'SpaController@listShops');
    Route::post('/spa/shop-profile', 'SpaController@shopProfile');
    Route::post('/spa/shop-info', 'SpaController@shopInfo');
    Route::post('/spa/price-list/{id}', 'SpaController@priceListShop');
    Route::post('/spa/shop-posts', 'SpaController@shopPosts');
    //Spa category - End

    //Tattoocity - Start
    Route::post('tattoo/home', 'TattooController@index');
    Route::post('tattoo/hashtags', 'TattooController@hashtagsList');
    Route::post('tattoo/shops', 'TattooController@shopsList');
    Route::post('tattoo/hashtags/detail', 'TattooController@hashtagDetail');
    Route::post('tattoo/get/user/category','TattooController@categoryList');
    //Tattoocity - End

    //Challenge - Start
    Route::post('challenge/list', 'ChallengeController@listChallenge');
    Route::post('challenge/list/category-wise', 'ChallengeController@listChallengeCategory');
    Route::post('challenge/menu', 'ChallengeController@menuList');
    //Challenge - End

    //Insta - Start
    Route::post('insta/google_signup', 'InstaController@googleSignup');
    Route::post('insta/google_login', 'InstaController@googleLogin');
    Route::post('insta/apple_signup', 'InstaController@appleSignup');
    Route::post('insta/apple_login', 'InstaController@appleLogin');
    //Insta - End

    //Qr code - Start
    Route::post('qr-code/google_signup', 'QrcodeController@googleSignup');
    Route::post('qr-code/google_login', 'QrcodeController@googleLogin');
    Route::post('qr-code/apple_signup', 'QrcodeController@appleSignup');
    Route::post('qr-code/apple_login', 'QrcodeController@appleLogin');
    //Qr code - End

    Route::post('/instagram-login', 'AuthenticateController@instaLogin');
    Route::post('/map-page', 'ShopListController@mapPageList');
    Route::post('apple_signup', 'AuthenticateController@appleSignup');
    Route::post('apple_login', 'AuthenticateController@appleLogin');
    Route::group(['middleware' => ['jwt.verify']], function ($api) {
        //Tattoocity - Start
        Route::post('tattoo/user/following/shops', 'TattooController@followingShops');
        Route::post('tattoo/get/user/profile', 'TattooController@getUserProfile');
        //Tattoocity - End

        //Challenge - Start
        Route::post('challenge/home', 'ChallengeController@homePage');
        Route::post('challenge/participated-list', 'ChallengeController@listParticipatedChallenge');
        Route::post('challenge/view-room', 'ChallengeController@viewChallenge');
        Route::post('challenge/create-period-challenge', 'ChallengeController@createPeriodChallenge');
        Route::post('challenge/create-challenge', 'ChallengeController@createChallenge');
        Route::post('challenge/category-list', 'ChallengeController@listCategory');
        Route::post('challenge/thumb-list', 'ChallengeController@listThumb');
        Route::post('challenge/participate', 'ChallengeController@participate');
        Route::post('challenge/upload-images', 'ChallengeController@uploadImages');
        Route::post('challenge/verified-images', 'ChallengeController@verifiedImages');
        Route::post('challenge/view-user-profile', 'ChallengeController@viewUserProfile');
        Route::post('challenge/follow-unfollow', 'ChallengeController@followUnfollow');
        Route::post('challenge/follower-list', 'ChallengeController@followerList');
        Route::post('challenge/following-list', 'ChallengeController@followingList');
        Route::post('challenge/logout-page', 'ChallengeController@logoutPage');
        Route::post('challenge/post-detail', 'ChallengeController@postDetail');
        Route::post('challenge/remove-participation', 'ChallengeController@removeParticipate');
        Route::post('challenge/participated-list/all', 'ChallengeController@allParticipated');
        Route::post('challenge/expired-list/all', 'ChallengeController@allExpired');
        Route::post('challenge/achievement-page', 'ChallengeController@achievementPage');
        Route::post('challenge/invite/follower-list', 'ChallengeController@inviteFollowerList');
        Route::post('challenge/invite-user', 'ChallengeController@inviteUser');
        Route::post('challenge/invited-list', 'ChallengeController@invitedList');
        Route::post('challenge/notice-list', 'ChallengeController@noticeList');
        Route::post('challenge/admin-notice', 'ChallengeController@adminNoticeList');
        Route::post('challenge/invite-app', 'ChallengeController@sendInvite');
        //Challenge - End

        //Qr code - Start
        Route::post('qr-code/create-qr', 'QrcodeController@createQr');
        Route::post('qr-code/list', 'QrcodeController@listQr');
        //Qr code - End

        //Insta - Start
        Route::post('insta/post-list', 'InstaController@postList');
        Route::post('insta/promot-insta-around', 'InstaController@promotInstaAround');
        Route::post('insta/download-post', 'InstaController@downloadPost');
        //Insta - End

        Route::post('latest-shop/detail', 'ShopController@latestShop');
        Route::post('my-profile', 'InstaController@myProfile');
        Route::post('shop-profile/details', 'InstaController@shopData');

        Route::post('/update/showhide','UserProfileController@updateShowhide'); //for save of settings button inside group chat
        Route::post('report-user','UserProfileController@reportUser'); //report user inside group chat
        Route::post('group-chat/report-message','UserProfileController@reportMessage'); //report message inside group chat
        Route::post('check_shop_post','ShopPostController@allPosts'); //for admin user
        Route::post('all_user_details','AdminUserController@allUserData'); //user page for admin user
        Route::post('reported_users','AdminUserController@reportedUsers'); //for admin user
        Route::post('reported_messages','AdminUserController@reportedMessages'); //for admin user
        Route::post('all_users','AdminUserController@allUsers');

        Route::post('all_chats_list','MessageController@allChatList'); //chat tab and business chat tab list
        Route::post('share_group','MessageController@shareGroupChat'); //For send message inside group chat
        Route::post('share_chat','MessageController@shareChat'); //For send message inside chat tab/business chat tab

        //Apply user for chat in group chat (only for admin user)
        Route::post('search_user','ApplyUserController@searchUser');
        Route::post('apply_user','ApplyUserController@applyUser');
        Route::post('recent_applied_list','ApplyUserController@recentAppliedUser');

        Route::post('connect/business/instagram',"AuthenticateController@connectInstagramWithBusiness");

        Route::post("save/posts/entry", "NewHomepageController@insertPostsDataDummy");

        Route::post("delete/my/account", "UserProfileController@deleteMySelf");
        Route::get("check_signup_time", "UserProfileController@check_signup_time");

        Route::post("disconnect/social-profile", 'AuthenticateController@disconnectSocialProfile');

        Route::post('update/card/love', 'CardLoveController@updateLove');

        Route::post('new/home/user/detail', 'NewHomepageController@indexLogin');

        Route::post('/get/all/search/detail', 'ShopListController@getAllSearchResults');
        Route::post('/list/all/user/shops', 'ShopListController@index')->name('.get.auth.all.shops');
        Route::post('/get/all/user/portfolio', 'HomeController@LoadAllPortfolio');
        Route::post('/get/user/portfolio/detail/{id}', 'ShopController@loadPortfolio');

        Route::post('list/all/user/shops/new','ShopListController@listAllShops');

        Route::post('shop-post/add/comment','ShopPostController@addComment');

        // Admin in Application
        Route::post('/admin/dashboard', 'AdminController@adminDashboard')->name('admin.dashboard');
        Route::post('/admin/dashboard/customer', 'AdminController@adminDashboardCustomer')->name('admin.dashboard.customer');
        Route::post('/admin/business/detail', 'AdminController@getBusinessDetail')->name('admin.business.detail');
        Route::get('/admin/reload/coin-logs', 'AdminController@getReloadCoinLogs')->name('admin.reload.coin-logs');
        Route::post('/admin/notice', 'AdminController@getNotice')->name('admin.notice');
        Route::post('/admin/update/profile', 'AdminController@updateProfile')->name('admin.update.profile');
        Route::get('/admin/profile/detail', 'AdminController@getProfileDetail');
        Route::post('/admin/join/association', 'AdminController@adminJoinAssociation');
        Route::post('/admin/leave/association', 'AdminController@leaveJoinAssociation');
        Route::post('/admin/requested/client', 'RequestedClientController@getRequestedClient')->name('admin.requested.client');
        Route::post('/admin/requested/client/response', 'RequestedClientController@requestedClientResponse')->name('admin.requested.client.response');
        Route::post('/admin/like-order/today-post-real', 'LikeOrderController@todayPostReal')->name('admin.like_order.today_post_real');

        // Admin Reload coin
        Route::post('/admin/reload-coin/request-list', 'AdminReloadCoinController@getReloadCoinRequest');
        Route::post('/admin/reload-coin/actions', 'AdminReloadCoinController@reloadCoinRequestActions');

        // LogOut
        Route::post('/logout', 'AuthenticateController@logOut')->name('.auth.logout');
        Route::post('/reverify/user', 'AuthenticateController@userReVerify')->name('.auth.user.reverify');

        // update device token
        Route::post('/update/devicetoken', 'AuthenticateController@updateDeviceToken')->name('update.devicetoken');

        // User Detail
        // Route::get("/user/profile", "UserController@userDetail")->name('.auth.profile.show');
        // Route::post("/user/profile", "UserController@editDetail")->name('.auth.profile.update');

        // User Request for Hospital or Shop
        // Route::get('/user/request', 'RequestFormController@index')->name('.user.request.index');
        Route::post('/user/request', 'RequestedClientController@store')->name('.user.request.store');


        // Shop Profile
        Route::post('/business/profile', 'ShopProfileController@getShopBusinessProfile')->name('.shop.business.profile');
        Route::get('/edit/profile/shop/{id}', 'ShopProfileController@editShopProfile')->name('.shop.edit.profile');
        Route::get('/get/shop/detail/', 'ShopProfileController@getShopDetail')->name('.shop.get.detail');
        Route::get('/shop/detail/', 'ShopProfileController@shopDetail')->name('.shop.detail');
        Route::post('/update/profile/shop/{id}', 'ShopProfileController@updateShopBusinessProfile')->name('.shop.update.profile');
        Route::post('/update/outside/shop/{id}', 'ShopProfileController@updateOutsideShopBusinessProfile')->name('.shop.update.profile');
        Route::post('/create/shop', 'ShopProfileController@store')->name('.shop.store');
        Route::post('/add/shop/portfolio', 'ShopProfileController@addShopPortfolio')->name('.shop.prtfolio.add');
        Route::post('/add/shop/post', 'ShopProfileController@addShopPost')->name('.shop.post.add');
        Route::post('/add/shop/multiple/post', 'ShopProfileController@addMultipleShopPost');
        Route::post('/update/shop/multiple/post', 'ShopProfileController@updateMultipleShopPost');
        Route::post('/save/multiple/post', 'ShopProfileController@saveMultipleShopPost');
        Route::post('/follow/shop', 'ShopProfileController@followShop')->name('.shop.follow');
        Route::get('/shop/post/detail/{id}', 'ShopProfileController@shopPostDetail')->name('.shop.post.detail');
        Route::get('/shop/post/delete/{id}', 'ShopProfileController@shopPostDelete')->name('.shop.post.delete');
        Route::get('/shop/image/delete/{id}', 'ShopProfileController@deleteShopImage')->name('.shop.image.delete');
        Route::get('/shop/inactive/{id}', 'ShopProfileController@inActiveShop')->name('.shop.inactive');
        Route::post('/shop/status/detail', 'ShopProfileController@statusDetail')->name('.shop.status.detail');
        Route::post('/shop/status/change', 'ShopProfileController@statusChange')->name('.shop.status.change');
        Route::post('/manage/clicks', 'ShopProfileController@manageClick')->name('manage.clicks');
        Route::post('shop/update/business/detail','ShopProfileController@updateBusinessDetail');
        Route::post('read/gifticon/detail','ShopProfileController@readGifticonDetail');
        Route::post('read/gifticon/detail','ShopProfileController@readGifticonDetail');
        Route::get('admin/shop/post/delete/{id}', 'ShopProfileController@adminShopPostDelete')->name('.shop.post.admin.delete');

        //Shop
        Route::get('/get/auth/shop/detail/{id}', 'ShopController@getAuthShopDetail')->name('.get.auth.shop.detail');
        Route::post('/get/auth/all/shops/distance', 'ShopController@getAuthAllShopsDistance')->name('.get.auth.all.shops.distance');
        Route::post('/get/auth/all/shops', 'ShopController@getAuthAllShops')->name('.get.auth.all.shops');
        Route::post('/search/auth/shop', 'ShopController@searchShop')->name('.search.auth.shop');
        Route::post('/connect-sns/users', 'UserProfileController@connectSNStoProfile');
        Route::post('/get/instagram-detail', 'ShopController@getInstaDetail')->name('.get.auth.insta.detail');

        //Shop Items
        Route::get('/edit/shop-item-category/{id}', 'ShopPriceController@editPriceCategory')->name('.shop-item-category.edit');
        Route::post('/update/shop-item-category/{id}', 'ShopPriceController@updatePriceCategory')->name('.shop-item-category.update');
        Route::get('/delete/shop-item-category/{id}', 'ShopPriceController@deletePriceCategory')->name('.shop-item-category.delete');
        Route::post('/add/shop-item-category', 'ShopPriceController@storePriceCategory')->name('.shop-item-category.add');

        Route::get('/edit/shop-item/{id}', 'ShopPriceController@editPrice')->name('.shop-items.edit');
        Route::post('/update/shop-item/{id}', 'ShopPriceController@updatePrice')->name('.shop-items.update');
        Route::get('/delete/shop-item/{id}', 'ShopPriceController@deletePrice')->name('.shop-items.delete');
        Route::post('/add/shop-item', 'ShopPriceController@storePrice')->name('.shop-items.add');
        Route::post('/get/discount/condition', 'ShopPriceController@getDiscountCondition')->name('.get.discount.condition');
        Route::post('/select/discount/condition', 'ShopPriceController@selectDiscountCondition')->name('.select.discount.condition');

        //User Profile
        Route::post('/get/user/profile', 'UserProfileController@getUserProfile')->name('.user.profile.get');
        Route::post('/get/user/info', 'UserProfileController@getUserInfo')->name('.user.info.get');
        Route::post('/get/user/popup', 'UserProfileController@getUserPopup')->name('.user.popup.get');
        Route::post('/get/user/popup/hide', 'UserProfileController@popupHide')->name('.user.popup.hide.get');
        Route::post('/change/user/language', 'UserProfileController@changeUserLanguage')->name('.change.user.language');
        Route::post('/change/user/location', 'UserProfileController@changeUserLocation')->name('.change.user.location');
        Route::post('/update/user/profile/image', 'UserProfileController@updateAvatarImage')->name('.user.profile.image.update');
        Route::post('/update/user/profile', 'UserProfileController@updateUserProfile')->name('.user.profile.update');
        Route::post('/change/password', 'UserProfileController@changePassword')->name('.user.change.password');
        Route::post('/add/user/history', 'UserProfileController@addHistory')->name('.add.user.history');
        Route::post('/remove/user/history', 'UserProfileController@removeFromHistory')->name('.remove.user.history');
        Route::post('/get/user/customer', 'UserProfileController@getCustomers')->name('.user.customer.get');
        Route::post('/get/user/schedule', 'UserProfileController@getSchedule')->name('.user.schedule.get');
        Route::get('/get/user/entity/list', 'UserProfileController@getUserEntityList')->name('.user.entity.list.get');
        Route::post('/add/user/schedule', 'UserProfileController@addSchedule')->name('.user.schedule.add');
        Route::get('/delete/user/schedule/{id}', 'UserProfileController@deleteSchedule')->name('.user.schedule.delete');
        Route::post('/get/plan/list', 'UserProfileController@getCreditPlans')->name('.get.plan.list');
        Route::post('/update/plan', 'UserProfileController@updateCreditPlans')->name('.update.plan');
        Route::get('/deactivate/profile', 'UserProfileController@deactivateProfile')->name('.deactivate.profile');
        Route::get('/activate/profile', 'UserProfileController@activateProfile')->name('.activate.profile');
        Route::post('/entity/status/change', 'UserProfileController@activateInactivateEntity')->name('.entity.status.change');
        Route::post('/get/search/history', 'UserProfileController@getSearchHistory')->name('.get.search.history');
        Route::post('/get/save/history', 'UserProfileController@getSaveHistory')->name('.get.save.history');
        Route::post('/get/user/posts', 'UserProfileController@getYourPost')->name('.get.user.posts');
        Route::post('/update/entity/address', 'UserProfileController@updateEntityAddress')->name('.update.entity.address');
        Route::post('/check/coin/usage', 'UserProfileController@checkCoinUsage')->name('.check.coin.usage');
        Route::get('/delete/search/history/{id}', 'UserProfileController@deleteSearchHistory')->name('.user.schedule.delete');
        Route::post('/user/inconvinence/mail', 'UserProfileController@userInconvinenceMail')->name('.user.inconvinence.mail');
        Route::post('/business/inconvinence/mail', 'UserProfileController@BussinessInconvinenceMail')->name('.user.inconvinence.mail');

        // Block / UnBlock User
        Route::post('/get/block/user', 'UserProfileController@getBlockedUser')->name('.get.block.user');

        Route::post('/user/block', 'UserProfileController@blockUser')->name('.user.block');
        Route::post('/user/unblock', 'UserProfileController@unBlockUser')->name('.user.unblock');

        Route::post('/shop/block', 'ShopBlockController@blockShop')->name('.shop.block');
        Route::post('/shop/unblock', 'ShopBlockController@unblockShop')->name('.shop.unblock');
        Route::post('/shop/block/list', 'ShopBlockController@blockShopList')->name('.shop.block.list');

        Route::post('/shop/report', 'ShopBlockController@reportShop')->name('.shop.report');

        // Hospital Profile
        Route::post('/business/hospital/profile', 'HospitalProfileController@getHospitalBusinessProfile')->name('.hospital.business.profile');
        Route::get('/edit/profile/hospital/{id}', 'HospitalProfileController@editHospitalProfile')->name('.hospital.edit.profile');
        Route::post('/update/profile/hospital/{id}', 'HospitalProfileController@updateHospitalBusinessProfile')->name('.hospital.update.profile');
        Route::get('/hospital/post/{id}', 'HospitalProfileController@hospitalPost')->name('.hospital.post');
        Route::post('/hospital/status/detail', 'HospitalProfileController@statusDetail')->name('.hospital.status.detail');
        Route::post('/hospital/status/change', 'HospitalProfileController@statusChange')->name('.hospital.status.change');

        // Add Doctor
        Route::post('/add/doctor', 'DoctorController@addDoctor')->name('.add.doctor');
        Route::get('/edit/doctor/{id}', 'DoctorController@editDoctor')->name('.edit.doctor');
        Route::post('/update/doctor/{id}', 'DoctorController@updateDoctor')->name('.update.doctor');
        Route::get('/delete/doctor/{id}', 'DoctorController@deleteDoctor')->name('.delete.doctor');
        Route::post('/list/doctor', 'DoctorController@listDoctor')->name('.list.doctor');

        // Hospital
        Route::get('/get/auth/hospital/post/detail/{id}', 'HospitalController@getPostDetail')->name('.get.hospital.detail');
        Route::post('/get/auth/all/hospital/posts', 'HospitalController@getAllHospitals')->name('.get.auth.all.hospital');

        // Get hospital posts new
        Route::post('/get/auth/all/hospital/post', 'HospitalController@getAllHospital')->name('get.auth.all.hospital.post');

        // Add Hospital Post

        Route::post('/add/hospital/post', 'PostController@addHospitalPost')->name('.add.hospital.post');
        Route::get('/hospital/post/delete/{id}', 'PostController@hospitalPostDelete')->name('.hospital.post.delete');
        Route::get('/edit/hospital/post/{id}', 'PostController@editPost')->name('.edit.hospital.detail');
        Route::post('/update/hospital/post/{id}', 'PostController@updatePost')->name('.update.hospital.detail');

        // Request To Ask
        Route::post('/request/service', 'RequestToAskController@requestService')->name('.request.service');
        Route::post('/complete/service/{id}', 'RequestToAskController@completeService')->name('.complete.service');
        Route::post('/noshow/service/{id}', 'RequestToAskController@noShowService')->name('.noshow.service');
        Route::post('/cancel/service/{id}', 'RequestToAskController@cancelService')->name('.cancel.service');
        Route::post('/complete/service/memo/{id}', 'RequestToAskController@completedServiceMemo')->name('.complete.service.memo');
        Route::post('/get/year/revenue', 'RequestToAskController@getYearRevenue')->name('.get.year.revenue');
        Route::post('/get/month/revenue', 'RequestToAskController@getMonthRevenue')->name('.get.month.revenue');
        Route::post('/get/user/revenue', 'RequestToAskController@getUserRevenue')->name('.get.user.revenue');
        Route::post('/dismiss/service/{id}', 'RequestToAskController@dismissService')->name('.dismiss.service');
        Route::post('/change/service/date/{id}', 'RequestToAskController@changeServiceDate')->name('.change.service.date');
        Route::get('/get/completed/service/list', 'RequestToAskController@getCompletedService')->name('.get.completed.service.list');
        Route::post('/credit/deduct', 'RequestToAskController@creditDeduct')->name('.credit.deduct');

        // Reviews
        Route::post('/add/shop/review', 'ReviewsController@addShopReview')->name('.add.shop.review');
        Route::post('/add/hospital/review', 'ReviewsController@addHospitalReview')->name('.add.hospital.review');
        Route::get('/like/review/{id}', 'ReviewsController@likeReview')->name('.like.review');
        Route::get('/unlike/review/{id}', 'ReviewsController@unlikeReview')->name('.unlike.review');
        Route::get('/delete/review/{id}', 'ReviewsController@deleteReview')->name('.delete.review');
        Route::post('/comment/review/{id}', 'ReviewsController@commentReview')->name('.comment.review');
        Route::get('/like/review/comment/{id}', 'ReviewsController@likeReviewComment')->name('.like.review.comment');
        Route::get('/delete/review/comment/{id}', 'ReviewsController@deleteReviewComment')->name('.delete.review.comment');
        Route::post('/update/review/comment/{id}', 'ReviewsController@updateReviewComment')->name('.update.review.comment');
        Route::post('/comment/review/reply/{id}', 'ReviewsController@reviewCommentReply')->name('.comment.review.reply');
        Route::get('/like/comment/review/reply/{id}', 'ReviewsController@reviewCommentReplyLike')->name('.like.comment.review.reply');
        Route::get('/delete/comment/review/reply/{id}', 'ReviewsController@reviewCommentReplyDelete')->name('.delete.comment.review.reply');
        Route::post('/update/comment/review/reply/{id}', 'ReviewsController@updateReviewCommentReply')->name('.update.comment.review.reply');
        Route::post('/get/auth/shop/review', 'ReviewsController@getShopReview')->name('.get.auth.shop.review');
        Route::post('/get/auth/hospital/review', 'ReviewsController@getHospitalReview')->name('.get.auth.hospital.review');
        Route::post('/get/auth/shop/review/detail/{id}', 'ReviewsController@getShopReviewDetail')->name('.get.auth.shop.review.detail');
        Route::post('/get/auth/hospital/review/detail/{id}', 'ReviewsController@getHospitalReviewDetail')->name('.get.auth.hospital.review.detail');
        // Route::get('/get/auth/shop/review/detail/{id}', 'ReviewsController@getShopReviewDetail')->name('.get.auth.shop.review.detail');
        // Route::get('/get/auth/hospital/review/detail/{id}', 'ReviewsController@getHospitalReviewDetail')->name('.get.auth.hospital.review.detail');

        // Community
        Route::post('/get/auth/community', 'CommunityController@getCommunity')->name('.get.community');
        Route::post('/get/auth/community/detail/{id}', 'CommunityController@getCommunityDetail')->name('.get.auth.community.detail');
        Route::post('/add/community', 'CommunityController@addCommunity')->name('.add.community');
        Route::post('/update/community/{id}', 'CommunityController@updateCommunity')->name('.update.community');
        Route::get('/like/community/{id}', 'CommunityController@likeCommunity')->name('.like.community');
        Route::get('/unlike/community/{id}', 'CommunityController@unlikeCommunity')->name('.unlike.community');
        Route::post('/comment/community/{id}', 'CommunityController@commentCommunity')->name('.comment.community');
        Route::get('/like/community/comment/{id}', 'CommunityController@likeCommunityComment')->name('.like.community.comment');
        Route::post('/comment/community/reply/{id}', 'CommunityController@communityCommentReply')->name('.comment.review.reply');
        Route::get('/delete/community/{id}', 'CommunityController@deleteCommunity')->name('.delete.community');
        Route::get('/delete/community/comment/{id}', 'CommunityController@deleteCommunityComment')->name('.delete.community.comment');
        Route::post('/update/community/comment/{id}', 'CommunityController@updateCommunityComment')->name('.update.community.comment');
        Route::get('/like/comment/community/reply/{id}', 'CommunityController@communityCommentReplyLike')->name('.like.comment.community.reply');
        Route::get('/delete/comment/community/reply/{id}', 'CommunityController@communityCommentReplyDelete')->name('.delete.comment.community.reply');
        Route::post('/update/comment/community/reply/{id}', 'CommunityController@updateCommunityCommentReply')->name('.update.comment.community.reply');

        // Report
        Route::post('/get/report/category', 'ReportController@getReportCategory')->name('.get.report.category');
        Route::post('/add/report/client', 'ReportController@addReportClient')->name('.add.report.client');

        // Suggest Custom

        Route::post('/custom/user/request', 'SuggestCustomController@store')->name('.custom.user.request');

        //Home
        Route::post('/get/user/home/detail', 'HomeController@index')->name('.get.user.home.detail');
        Route::get('/get/user/following/shops', 'HomeController@followingShops')->name('.get.user.following.shops');
        Route::get('/get/user/booking/list', 'HomeController@getUserBookingCompletedList')->name('.get.user.booking.list');


        // Connect Instagram
        Route::post('/instagram/share/image', 'ShopController@instagramSharePost')->name('.instagram.share.image');
        Route::get('/sns-reward/request', 'UserProfileController@rewardSNSRequest');

        // Payment
        Route::post('/paypal/payment', 'PaymentController@payWithPaypal')->name('.paypal.payment');

        // Messages
        Route::post('/get/notices', 'MessageController@getNotices')->name('.get.notices');
        Route::post('/delete/notice', 'MessageController@deleteNotices')->name('delete.notices');
        Route::post('/get/counselling/message', 'MessageController@getCounsellingMessage')->name('.get.counselling.message');
        Route::post('/get/business/message', 'MessageController@getBusinessMessage')->name('.get.business.message');
        Route::post('/delete/chat/messages', 'MessageController@deleteChatMessages')->name('.delete.chat.messages');
        Route::get('/get/messages/count', 'MessageController@getChatCount')->name('.get.chat.count');
        Route::post('/initiate/chat', 'MessageController@initiateChatMessages')->name('.initiate.chat.messages');
        Route::post('/check/user', 'MessageController@checkUser')->name('.check.user');
        Route::post('/inquiry/user/list', 'MessageController@inquiryUserList')->name('.inquiry.user.list');
        Route::post('/get/admin/message', 'MessageController@getAdminMessage')->name('.get.admin.message');
        Route::post('/pin/user/chat', 'MessageController@pinUserChat');
        Route::post('/notification/user/chat', 'MessageController@notificationUserChat');

        // Reload Coin
        Route::get('/get/reload/coin/currency/list', 'ReloadCoinsController@getReloadCoinCurrencyList')->name('.get.reload.coin.currency.list');
        Route::post('/get/reload/coin/data', 'ReloadCoinsController@getReloadCoinData')->name('.get.reload.coin.data');
        Route::post('/reload/coin', 'ReloadCoinsController@reloadCoin')->name('.reload.coin');

        // Association
        //Route::get('/get/community/tabs', 'AssociationController@getCommunityTabs')->name('get.community.tabs');
        //Route::post('/get/sub-category', 'AssociationController@getSubCategoryList')->name('get.sub-category');
        //Route::post('/get/association', 'AssociationController@getAssociation')->name('get.association');
        //Route::get('/get/association/detail/{id}', 'AssociationController@getAssociationDetail')->name('get.association.detail');


        Route::post('/join/association', 'AssociationController@joinAssociation')->name('join.association.detail');
        Route::post('/remove/association/member', 'AssociationController@removeAssociationMember')->name('remove.association.member');

        Route::post('/manage/category', 'AssociationController@manageAssociationCategory')->name('manage.association.category');
        Route::post('/delete/category', 'AssociationController@deleteAssociationCategory')->name('delete.association.category');
        Route::post('/update/category/status', 'AssociationController@updateCategoryStatus')->name('update.category.status');

        Route::post('/make/manager', 'AssociationController@makeMemberToManger')->name('make.member.manager');

        Route::post('/edit/association', 'AssociationController@saveAssociation')->name('edit.association');
        Route::post('/save/association/like', 'AssociationController@saveLike');

        // Association Community
        Route::post('/add/association-community', 'AssociationCommunityController@addCommunity');
        Route::post('/edit/association-community/{id}', 'AssociationCommunityController@editCommunity');
        Route::post('/get/association-community', 'AssociationCommunityController@getAssociationCommunity');
        Route::post('/get/association-community/detail/{id}', 'AssociationCommunityController@getCommunityDetail');
        Route::get('/delete/association-community/{id}', 'AssociationCommunityController@deleteCommunity');

        // Association community comment
        Route::post('/add/association/community/comment', 'AssociationCommunityCommentController@addComment');
        Route::post('/update/association-community/comment/{id}', 'AssociationCommunityCommentController@updateCommunityComment');
        Route::get('/delete/association-community/comment/{id}', 'AssociationCommunityCommentController@deleteCommunityComment');

        Route::post('/get/saved/community/tabs', 'AssociationController@getSavedCommunityTabs')->name('get.saved.community.tabs');
        Route::post('/get/saved/community/data', 'AssociationController@getSavedCommunityData')->name('get.saved.community.data');

        // Recycle Options
        Route::post('/recycle/option', 'ShopDetailController@getRecycleOptions');
        Route::post('/save/shop/detail', 'ShopDetailController@saveShopDetail')->name('save.shop.detail');
        Route::post('/manage/shop-usage/detail', 'ShopDetailController@saveUsageInformation');
        Route::get('/get/shop/info/{shop_id}', 'ShopDetailController@getShopDetail')->name('get.shop.detail');
        Route::get('/delete/shop/usage/info/{shop_info_id}', 'ShopDetailController@deleteShopDetail')->name('delete.shop.usage.info');

        Route::post('/save/shop-detail/language', 'ShopDetailController@saveShopLanguageDetail');

        Route::post('customer/list', 'CustomerController@index');
        Route::post('customer/create', 'CustomerController@store');
        Route::post('customer/import', 'CustomerController@import');
        Route::post('customer/remove/multiple', 'CustomerController@destroyMultiple');
        Route::get('customer/remove/{id}', 'CustomerController@destroy');
        Route::post('customer/booking/create', 'CustomerController@createBooking');
        Route::post('get/customer/revenue', 'CustomerController@getCustomerRevenue');
        Route::post('edit/customer/revenue', 'CustomerController@editCustomerRevenue');
        Route::post('customer/booking/update', 'CustomerController@updateBooking');
        Route::post('customer/profile/detail', 'CustomerController@showCustomerProfile');

        Route::post('completed/customer/list', 'CustomerController@showCompletedCustomerList');
        Route::post('completed/customer/list/detail', 'CustomerController@showCompletedCustomerListDetail');
        Route::post('get/user/shops', 'ShopListController@getShopDetails');
        Route::post('get/hashtags', 'HashTagController@index');
        Route::post('user/hashtags/detail', 'HashTagController@hashTagDetail');

        // Cards controller
        Route::get('/get/user/points/{user_id?}', 'CardsController@getUserPoints')->name('.get.user.points');
        Route::get('/get/referral/users/{user_id?}', 'CardsController@getReferralUsers');
        Route::get('/cards', 'CardsController@getCards');
        Route::post('/select/cards', 'CardsController@selectCards');
        Route::post('/apply/card', 'CardsController@ApplyCard');
        Route::post('/change/card/riv', 'CardsController@changeCardRiv');
        Route::post('/sell/request/card', 'CardsController@sellRequestCard');
        Route::post('/get/card/details', 'CardsController@getCardDetail');
        Route::post('/get/card/level/details', 'CardsController@getCardLevelDetail');
        Route::post('/remove/dead/card', 'CardsController@removeDeadCard');
        Route::post('/restart/default/card', 'CardsController@restartDefaultCard');

        Route::post('/sold/card', 'SoldCardController@soldRequestCard');
        Route::get('/get/followers/coin/detail', 'CardsController@getFollowersCoinDetail');
        Route::get('get/deduct/coin/detail', 'CardsController@getDeductCoinDetail');

        Route::get('check/user/business', 'UserProfileController@checkUserBusiness');

        // Wedding
        Route::post("wedding/list", "WeddingController@index");
        Route::post("wedding/detail/{id}", "WeddingController@weddingDetail");

        // purchase Product
        Route::post("purchase/product", "BrandApiController@purchaseProduct");

        //Instagram Category
        Route::post("instagram-category/list", "InstaCategoryApiController@listCategory");
        Route::post("instagram-category/options", "InstaCategoryApiController@listOption");
        Route::post("instagram-category/subscribe-plan", "InstaCategoryApiController@subscribePlan");  //For apply and subscribe
    });
});
