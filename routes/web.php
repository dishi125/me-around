<?php

use App\Models\CategoryTypes;
use App\Models\Challenge;
use App\Models\ChallengeParticipatedUser;
use App\Models\UserDevices;
use Carbon\Carbon;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
 */

Auth::routes();

Route::get('/business', function () {
    return redirect(route('business.dashboard.index'));
});
Route::get('/user', function () {
    return redirect(route('user.dashboard.index'));
});
Route::get('/tattoocity', function () {
    return redirect(route('tattoocity.dashboard.index'));
});
Route::get('/spa', function () {
    return redirect(route('spa.dashboard.index'));
});
Route::get('/challenge', function () {
    return redirect(route('challenge.dashboard.index'));
});
Route::get('/insta', function () {
    return redirect(route('insta.dashboard.index'));
});
Route::get('/qr-code', function () {
    return redirect(route('qr-code.dashboard.index'));
});

Route::get('/', function () {
    return redirect(route('login'));
});

Route::get('/admin', function () {
    return redirect(route('admin.dashboard.index'));
});

Route::get('/home', function () {
    return redirect(route('admin.dashboard.index'));
});
Route::get('/profile/connect/{code?}', 'DeeplinkController@connectLinkView')->name('social.profile.connect');

Route::get('/get_all_posts', 'Admin\InstagramController@getInstaPosts')->name('get.all.posts');
Route::get('/social-redirect', 'DeeplinkController@socialRedirect')->name('social-redirect');

Route::get('update/uuid', 'Admin\BusinessClientController@updateUuid');
Route::get('wedding/{uuid}', 'Admin\WeddingController@viewWedding')->name('wedding.view');
Route::get('shop/{uuid}', 'Admin\BusinessClientController@viewFrontShop')->name('shop.view');
Route::get('pages/{slug}', 'CMSPagesController@viewPages')->name('page.view');
Route::post('save/guest/book/{uuid}', 'Admin\WeddingController@saveGuestData')->name('save.guest.book');
Route::post('remove/guest/book', 'Admin\WeddingController@removeGuestData')->name('remove.guest.book');

Route::get('user/profile/verify/email/{email}', 'Admin\UserProfileController@verifyUserEmail')->name('user.profile.verify.email');
Route::post('user/profile/verify/email/', 'Admin\UserProfileController@updateUserEmail')->name('user.profile.update.email');
Route::get('check/email/exist', 'Admin\UserProfileController@checkEmailExist')->name('check.email.exist');
Route::get('deeplink', 'DeeplinkController@index')->name('deeplink');
Route::get('shop-deeplink', 'DeeplinkController@shopDeeplink')->name('shop-deeplink');
Route::get('qr/code', 'Admin\DashboardController@getQr')->name('qr.code');
Route::get('wedding/qr/code/{id}', 'Admin\DashboardController@getWeddingQr')->name('wedding.qr.code');
Route::get('download/wedding/qr/code/{id}', 'Admin\DashboardController@downloadWeddingQr')->name('download.wedding.qr.code');

Route::get('shop/qr/code/{type}/{id}', 'Admin\DashboardController@getShopQr')->name('shop.qr.code');
Route::get('download/shop/qr/code/{type}/{id}', 'Admin\DashboardController@downloadShopQr')->name('download.shop.qr.code');

Route::post('user/update/language/{id}', 'Admin\DashboardController@updateLanguage')->name('user.update.language');
Route::post('check/user/unread-comments', 'Admin\ThemeSectionController@checkUserUnreadComments')->name('check.user.unread-comment');

Route::name('admin.')->prefix('admin')->middleware(['auth', 'authlocalization'])->group(function () {
    Route::post('disconnect/instagram/{id}', 'Admin\BusinessClientController@DisconnectInstagram')->name('disconnect.instagram');
    Route::get('instagram-account', 'Admin\InstagramController@indexList')->name('instagram-account.index');
    Route::post('instagram-account/table/all', 'Admin\InstagramController@getJsonAllData')->name('instagram-account.all.table');
    Route::get('instagram-account/status/send-mail/{insta_id}', 'Admin\InstagramController@statusSendMail')->name('instagram-account.status.send-mail');
    Route::get('instagram-account/status/send-mail-all', 'Admin\InstagramController@statusSendMailAll')->name('instagram-account.status.send-mail-all');

    //Connected from Reels downloer log
    Route::get('reels-downloader-log', 'Admin\ReelsDownloaderLogController@index')->name('reels-downloader-log.index');

    // User Dashboard
    Route::get('dashboard', 'Admin\DashboardController@index')->name('dashboard.index');
    Route::get('dashboard/hospital', 'Admin\DashboardController@indexHospital')->name('dashboard.hospital.index');
    Route::get('dashboard/shop', 'Admin\DashboardController@indexShop')->name('dashboard.shop.index');
    Route::get('dashboard/detail/year/{id}', 'Admin\DashboardController@getYearDetail')->name('dashboard.year.detail');
    Route::get('dashboard/detail/month/{id}/{date}', 'Admin\DashboardController@getMonthDetail')->name('dashboard.month.detail');
    Route::get('dashboard/detail/day/{id}/{date}', 'Admin\DashboardController@getDayDetail')->name('dashboard.day.detail');
    Route::get('dashboard/detail/all/day/{id}/{date}', 'Admin\DashboardController@getAllDayDetail')->name('dashboard.all.day.detail');
    Route::post('dashboard/click/detail', 'Admin\DashboardController@getAllClickDetail')->name('dashboard.click.detail');

    // Qr-code
    Route::get('qr/code', 'Admin\DashboardController@getQr')->name('qr.code');

    /* Manage Roles */
    Route::get('roles/name/{id}', 'Admin\RoleController@checkUserRole')->name('roles.name');
    Route::get('roles/delete/{id}', 'Admin\RoleController@deleteRecord')->name('roles.delete');
    Route::resource('roles', 'Admin\RoleController', ['names' => ['index' => 'roles']]);
    Route::post('roles/table', 'Admin\RoleController@getJsonData')->name('roles.table');

    // User Profile
    Route::get('profile/{header?}', 'Admin\ProfileController@edit')->name('profile.show')->defaults('header', '');;
    Route::put('profile/{id}', 'Admin\ProfileController@update')->name('profile.update');
    Route::put('profile/changepassword/{id}', 'Admin\ProfileController@changePassword')->name('profile.changepassword');

    // Outside user
    Route::get('outside-user', 'Admin\BusinessClientController@indexOutsideUser')->name('outside-user.index');
    Route::post('outside-user/table/all/shop', 'Admin\BusinessClientController@getJsonAllOutsideUserShopData')->name('outside-user.all.shop.table');

    // Give Credit
    Route::post('give/all/user/credit', 'Admin\BusinessClientController@giveAllCredits')->name('give.all.user.credit');
    Route::post('business-client/give/all/credit/', 'Admin\BusinessClientController@updateAllUserCredits')->name('business-client.give.all.credit');

    //Business Client
    Route::get('business-client', 'Admin\BusinessClientController@index')->name('business-client.index');
    Route::post('business-client/table/all/{id}', 'Admin\BusinessClientController@getJsonAllData')->name('business-client.all.table');
    Route::post('business-client/table/active/{id}', 'Admin\BusinessClientController@getJsonActiveData')->name('business-client.active.table');
    Route::post('business-client/table/inactive/{id}', 'Admin\BusinessClientController@getJsonInActiveData')->name('business-client.inactive.table');
    Route::post('business-client/table/pending/{id}', 'Admin\BusinessClientController@getJsonPendingData')->name('business-client.pending.table');
    Route::get('business-client/hospital', 'Admin\BusinessClientController@indexHospital')->name('business-client.hospital.index');
    Route::post('business-client/table/all/hospital/{id}', 'Admin\BusinessClientController@getJsonAllHospitalData')->name('business-client.all.hospital.table');
    Route::post('business-client/table/active/hospital/{id}', 'Admin\BusinessClientController@getJsonActiveHospitalData')->name('business-client.active.hospital.table');
    Route::post('business-client/table/inactive/hospital/{id}', 'Admin\BusinessClientController@getJsonInActiveHospitalData')->name('business-client.inactive.hospital.table');
    Route::post('business-client/table/pending/hospital/{id}', 'Admin\BusinessClientController@getJsonPendingHospitalData')->name('business-client.pending.hospital.table');
    Route::get('business-client/shop', 'Admin\BusinessClientController@indexShop')->name('business-client.shop.index');
    Route::post('business-client/table/all/shop/{id}', 'Admin\BusinessClientController@getJsonAllShopData')->name('business-client.all.shop.table');
    Route::post('business-client/table/active/shop/{id}', 'Admin\BusinessClientController@getJsonActiveShopData')->name('business-client.active.shop.table');
    Route::post('business-client/table/inactive/shop/{id}', 'Admin\BusinessClientController@getJsonInActiveShopData')->name('business-client.inactive.shop.table');
    Route::post('business-client/table/pending/shop/{id}', 'Admin\BusinessClientController@getJsonPendingShopData')->name('business-client.pending.shop.table');
    Route::get('business-client/{id}/hospital', 'Admin\BusinessClientController@show')->name('business-client.hospital.show');
    Route::get('business-client/{id}/shop', 'Admin\BusinessClientController@showShop')->name('business-client.shop.show');
    Route::post('business-client/update/shop', 'Admin\BusinessClientController@updateShop')->name('business-client.update.shop');
    Route::post('business-client/update/hodpital', 'Admin\BusinessClientController@updateHospital')->name('business-client.update.hodpital');
    Route::post('business-client/upload/shop/images', 'Admin\BusinessClientController@uploadShopImages')->name('business-client.upload.shop.images');
    Route::post('business-client/delete/shop/images', 'Admin\BusinessClientController@deleteShopImages')->name('business-client.delete.shop.images');
    Route::post('business-client/save/supporter', 'Admin\BusinessClientController@saveSupporter')->name('business-client.save.supporter');
    Route::post('business-client/upload/hospital/images', 'Admin\BusinessClientController@uploadHospitalImages')->name('business-client.upload.hospital.images');
    Route::post('business-client/delete/hospital/images', 'Admin\BusinessClientController@deleteHospitalImages')->name('business-client.delete.hospital.images');
    Route::get('business-client/referral/{id}', 'Admin\BusinessClientController@viewReferralDetail')->name('business-client.referral.detail');
    Route::post('business-client/{id}/update-status', 'Admin\BusinessClientController@updateShopStatus')->name('business-client.shop-status-update');
    Route::post('business-client/get_shop_price', 'Admin\BusinessClientController@get_shop_price')->name('business-client.get_shop_price');
    Route::post('instagram-service/update/shop/{id}', 'Admin\BusinessClientController@updateInstaServiceShop')->name('instagram-service.update.shop');
    Route::post('send/notification/050_number', 'Admin\BusinessClientController@sendNotification050')->name('business-client.send_notification_050');
    Route::get('address-detail', 'Admin\BusinessClientController@address_detail')->name('business-client.address-detail');

    // Posts
    Route::get('business-client/{id}/posts/create', 'Admin\PostManagementController@create')->name('business-client.posts.create');
    Route::post('business-client/posts/store', 'Admin\PostManagementController@store')->name('business-client.posts.store');
    Route::get('business-client/posts/{id}/edit', 'Admin\PostManagementController@edit')->name('business-client.posts.edit');
    Route::put('business-client/posts/{id}', 'Admin\PostManagementController@update')->name('business-client.posts.update');

    Route::get('business-client/edit/credit/{id}', 'Admin\BusinessClientController@editCredits')->name('business-client.edit.credit');
    Route::post('business-client/edit/access', 'Admin\BusinessClientController@editAccess')->name('business-client.edit.access');
    Route::post('business-client/edit/support-user', 'Admin\BusinessClientController@editSupport')->name('business-client.edit.support-user');
    Route::post('business-client/edit/support-type', 'Admin\BusinessClientController@editSupportType')->name('business-client.edit.support-type');
    Route::get('business-client/view/profile/{id}', 'Admin\BusinessClientController@viewHospitalProfile')->name('business-client.view.profile');
    Route::get('business-client/view/logs/{id}', 'Admin\BusinessClientController@viewLogs')->name('business-client.view.logs');
    Route::get('business-client/view/shop/profile/{id}', 'Admin\BusinessClientController@viewShopProfile')->name('business-client.view.shop.profile');
    Route::get('business-client/view/shop/profile/link/{id}', 'Admin\BusinessClientController@viewShopProfileLink')->name('business-client.view.shop.profile.link');
    Route::post('business-client/add/credit/', 'Admin\BusinessClientController@updateCredits')->name('business-client.add.credit');
    Route::post('business-client/delete/profile/', 'Admin\BusinessClientController@deleteBusinessProfile')->name('business-client.delete.profile');
    Route::post('business-client/delete/user/', 'Admin\BusinessClientController@deleteUser')->name('business-client.delete.user');
    Route::get('business-client/shoppost/{id}/edit/{from?}', 'Admin\BusinessClientController@editShopPost')->name('business-client.shoppost.edit');
    Route::get('business-client/shoppost/{id}/create', 'Admin\BusinessClientController@createShopPost')->name('business-client.shoppost.create');
    Route::put('business-client/shoppost/{id}/update', 'Admin\BusinessClientController@updateShopPost')->name('business-client.shoppost.update');
    Route::put('business-client/shoppost/{id}/store', 'Admin\BusinessClientController@storeShopPost')->name('business-client.shoppost.store');
    Route::post('business-client/remove/shop/{id}', 'Admin\BusinessClientController@deleteShop')->name('business-client.remove.shop');
    Route::post('business-client/edit/love-count-daily', 'Admin\BusinessClientController@updateDailyLoveCount')->name('business-client.edit.love-count-daily');
    Route::post('business-client/edit/increase-love-count', 'Admin\BusinessClientController@editIncreaseLoveCount')->name('business-client.edit.increase-love-count');
    Route::post('shop/save', 'Admin\BusinessClientController@saveShop')->name('shop.save');
    Route::post('shoppost/edit/display-video', 'Admin\BusinessClientController@editDisplayVideo')->name('shoppost.edit.display-video');
    Route::post('shop/add-info', 'Admin\BusinessClientController@saveInfo')->name('shop.add-info');

    Route::post('shoppost/all/remove', 'Admin\BusinessClientController@removeAllShopPostImage')->name('shoppost-image.all.remove');
    Route::post('shoppost/image/remove', 'Admin\BusinessClientController@removeShopPostImage')->name('shoppost-image.remove');
    Route::get('shoppost/remove/{id}', 'Admin\BusinessClientController@removeShopPost')->name('shoppost.remove');

    Route::get('business-client/view/price/category/{id}/{cat_id?}', 'Admin\BusinessClientController@viewShopPriceCategory')->name('business-client.view.price.category');
    Route::post('business-client/save/shop/price/category', 'Admin\BusinessClientController@saveShopPriceCategory')->name('business-client.save.shop.price.category');

    Route::get('business-client/view/price/{id}/{cat_id}/{price_id?}', 'Admin\BusinessClientController@viewShopPrice')->name('business-client.view.price');
    Route::post('business-client/save/shop/price', 'Admin\BusinessClientController@saveShopPrice')->name('business-client.save.shop.price');
    Route::post('price/image/remove', 'Admin\BusinessClientController@priceremoveImage')->name('price-image.remove');

    Route::get('business-client/shop/price/delete/{id}/{type}', 'Admin\BusinessClientController@deleteShopPrice')->name('business-client.shop.price.delete');
    Route::delete('business-client/shop/price/destroy/{id}/{type}', 'Admin\BusinessClientController@destroyShopPrice')->name('business-client.destroy.shop.price');

    Route::get('business-client/shop/posts/{hashtag_id?}', 'Admin\BusinessClientController@indexShopPost')->name('business-client.get.shop.post');
    Route::post('business-client/table/shop/posts', 'Admin\BusinessClientController@getJsonShopPostData')->name('business-client.shop.post.table');
    Route::get('business-client/shop/posts/delete/{id}', 'Admin\BusinessClientController@deleteShopPost')->name('business-client.delete.shop.post');
    Route::delete('business-client/shop/posts/destroy/{id}', 'Admin\BusinessClientController@destroyShopPost')->name('business-client.destroy.shop.post');
    Route::post("remove-text/shop/posts", 'Admin\BusinessClientController@removeTextDescription')->name('remove.text.shop.post');
    Route::post("get/remove-text/shop/posts/list", 'Admin\BusinessClientController@getTextDescription')->name('get.remove.text.shop.post');
    Route::post('download-shop-posts', 'Admin\BusinessClientController@downloadShopPost')->name('download.shop-posts');
    Route::get('proxy-image', 'Admin\BusinessClientController@proxyImage')->name('proxy-image.shop-posts');
    Route::post('get/shop-posts/url', 'Admin\BusinessClientController@ShopPostUrl')->name('get.shop-posts-url');
    Route::get('shops-in-circle', 'Admin\BusinessClientController@getShopsInCircle');
    Route::post('shops/generate-text-file', 'Admin\BusinessClientController@generateTextFile')->name('shop.generate-text-file');

    Route::post('business-client/reload/coin/{id}', 'Admin\BusinessClientController@reloadCoinUser')->name('business-client.reload.coin');

    // Requested Client
    Route::get('requested-client', 'Admin\RequestedClientController@getAll')->name('requested-client.index');
    Route::post('requested-client/table/all', 'Admin\RequestedClientController@getJsonAllData')->name('requested-client.all.table');
    Route::get('requested-client/hospital', 'Admin\RequestedClientController@index')->name('requested-client.hospital.index');
    Route::post('requested-client/table/all/hospital', 'Admin\RequestedClientController@getJsonAllHospitalData')->name('requested-client.all.hospital.table');
    Route::get('requested-client/shop', 'Admin\RequestedClientController@indexShop')->name('requested-client.shop.index');
    Route::post('requested-client/table/all/shop', 'Admin\RequestedClientController@getJsonAllShopData')->name('requested-client.all.shop.table');
    Route::get('requested-client/suggest', 'Admin\RequestedClientController@indexSuggest')->name('requested-client.suggest.index');
    Route::post('requested-client/table/all/suggest', 'Admin\RequestedClientController@getJsonAllSuggestData')->name('requested-client.all.suggest.table');
    Route::post('requested-client/approve/multiple', 'Admin\RequestedClientController@approveMultiple')->name('requested-client.approve.multiple');
    Route::post('requested-client/reject/multiple', 'Admin\RequestedClientController@rejectMultiple')->name('requested-client.reject.multiple');
    Route::post('requested-client/reject/mention', 'Admin\RequestedClientController@confirmRejectMention')->name('requested-client.reject-mention');

    Route::get('requested-client/confirmed/{type?}', 'Admin\RequestedClientController@getAllConfirmed')->name('requested-client.confirmed.index')->defaults('type', 'all');
    Route::post('requested-client/confirmed/table/all', 'Admin\RequestedClientController@getJsonAllConfirmedData')->name('requested-client.confirmed.all.table');

    // Reported Client
    Route::get('reported-client/delete/{id}', 'Admin\ReportedClientController@delete')->name('reported-client.delete');
    Route::delete('reported-client/destroy/{id}', 'Admin\ReportedClientController@destroy')->name('reported-client.destroy');
    Route::get('reported-client', 'Admin\ReportedClientController@getAll')->name('reported-client.index');
    Route::post('reported-client/table/all', 'Admin\ReportedClientController@getJsonAllData')->name('reported-client.all.table');
    Route::post('reported-client/table/all/data/{category}', 'Admin\ReportedClientController@getJsonAllData')->name('reported-client.all.data.table.category');
    Route::get('reported-client/hospital', 'Admin\ReportedClientController@index')->name('reported-client.hospital.index');
    Route::post('reported-client/table/all/hospital', 'Admin\ReportedClientController@getJsonAllHospitalData')->name('reported-client.all.hospital.table');
    Route::post('reported-client/table/all/hospital/{category}', 'Admin\ReportedClientController@getJsonAllHospitalData')->name('reported-client.all.hospital.table.category');
    Route::get('reported-client/shop', 'Admin\ReportedClientController@indexShop')->name('reported-client.shop.index');
    Route::post('reported-client/table/all/shop', 'Admin\ReportedClientController@getJsonAllShopData')->name('reported-client.all.shop.table');
    Route::post('reported-client/table/all/shop/{category}', 'Admin\ReportedClientController@getJsonAllShopData')->name('reported-client.all.shop.table.category');
    Route::get('reported-client/user', 'Admin\ReportedClientController@indexUser')->name('reported-client.user.index');
    Route::post('reported-client/table/all/user', 'Admin\ReportedClientController@getJsonAllUserData')->name('reported-client.all.user.table');
    Route::post('reported-client/table/all/user/{category}', 'Admin\ReportedClientController@getJsonAllUserData')->name('reported-client.all.user.table.category');
    Route::get('reported-client/community', 'Admin\ReportedClientController@indexCommunity')->name('reported-client.community.index');
    Route::post('reported-client/table/all/community', 'Admin\ReportedClientController@getJsonAllCommunityData')->name('reported-client.all.community.table');
    Route::post('reported-client/table/all/community/{category}', 'Admin\ReportedClientController@getJsonAllCommunityData')->name('reported-client.all.community.table.category');
    Route::get('reported-client/review', 'Admin\ReportedClientController@indexReview')->name('reported-client.review.index');
    Route::post('reported-client/table/all/review', 'Admin\ReportedClientController@getJsonAllReviewData')->name('reported-client.all.review.table');
    Route::post('reported-client/table/all/review/{category}', 'Admin\ReportedClientController@getJsonAllReviewData')->name('reported-client.all.review.table.category');
    // Route::get('reported-client/{id}/hospital', 'Admin\ReportedClientController@show')->name('reported-client.hospital.show');
    // Route::get('reported-client/{id}/shop', 'Admin\ReportedClientController@showShop')->name('reported-client.shop.show');
    Route::get('reported-client/{id}/user', 'Admin\ReportedClientController@showUser')->name('reported-client.user.show');
    Route::get('reported-client/{id}/community', 'Admin\ReportedClientController@showCommunity')->name('reported-client.community.show');
    Route::get('reported-client/{id}/review', 'Admin\ReportedClientController@showReview')->name('reported-client.review.show');
    Route::post('reported-client/warning/mention', 'Admin\ReportedClientController@warningMention')->name('reported-client.warning-mention');
    Route::get('reported-client/warning/user/{id}', 'Admin\ReportedClientController@warningUser')->name('reported-client.warning-user');
    Route::get('reported-client/get/post/{id}', 'Admin\ReportedClientController@getPost')->name('reported-client.get-post');
    Route::post('reported-client/delete/post/{id}', 'Admin\ReportedClientController@deletePost')->name('reported-client.delete-post');
    Route::get('reported-client/get/all/post/{id}', 'Admin\ReportedClientController@getAllPost')->name('reported-client.get-all-post');
    Route::post('reported-client/delete/all/post/{id}', 'Admin\ReportedClientController@deleteAllPost')->name('reported-client.delete-all-post');
    Route::get('reported-client/get/account/{id}', 'Admin\ReportedClientController@getAccount')->name('reported-client.get-account');
    Route::post('reported-client/delete/account/{id}', 'Admin\ReportedClientController@deleteAccount')->name('reported-client.delete-account');

    // Category
    Route::get('category/parent/{id}', 'Admin\CategoryController@parent')->name('category.parent');
    Route::get('category/delete/{id}', 'Admin\CategoryController@delete')->name('category.delete');
    Route::post('category/hospital/table', 'Admin\CategoryController@getHospitalJsonData')->name('category.hospital.table');
    Route::post('category/shop/table/{custom?}', 'Admin\CategoryController@getShopJsonData')->name('category.shop.table')->where('custom', CategoryTypes::SHOP . '|' . CategoryTypes::SHOP2 . '|')->defaults('custom', CategoryTypes::SHOP);
    Route::post('category/community/table', 'Admin\CategoryController@getCommunityJsonData')->name('category.community.table');
    Route::post('category/suggest/table/{custom?}', 'Admin\CategoryController@getSuggestJsonData')->name('category.suggest.table')->where('custom', CategoryTypes::CUSTOM . '|' . CategoryTypes::CUSTOM2 . '|')->defaults('custom', CategoryTypes::CUSTOM);
    Route::post('category/report/table', 'Admin\CategoryController@getReportJsonData')->name('category.report.table');
    Route::get('category/report', 'Admin\CategoryController@indexReport')->name('category.report.index');
    Route::get('category/suggest/{custom?}', 'Admin\CategoryController@indexSuggest')->name('category.suggest.index')->where('custom', CategoryTypes::CUSTOM . '|' . CategoryTypes::CUSTOM2 . '|')->defaults('custom', CategoryTypes::CUSTOM);
    Route::get('category/shop/{custom?}', 'Admin\CategoryController@indexShop')->name('category.shop.index')->where('custom', CategoryTypes::SHOP . '|' . CategoryTypes::SHOP2 . '|')->defaults('custom', CategoryTypes::SHOP);
    Route::get('category/community', 'Admin\CategoryController@indexCommunity')->name('category.community.index');
    Route::resource('category', 'Admin\CategoryController');
    Route::post('currency/table', 'Admin\CurrencyController@getCurrencyJsonData')->name('currency.table');
    Route::get('currency/delete/{id}', 'Admin\CurrencyController@delete')->name('currency.delete');
    Route::resource('currency', 'Admin\CurrencyController');

    //Music track
    Route::get('music-track', 'Admin\MusicTrackController@index')->name('music-track.index');
    Route::get('music-track/create', 'Admin\MusicTrackController@create')->name('music-track.create');
    Route::post('music-track/table', 'Admin\MusicTrackController@getJsonData')->name('music-track.table');
    Route::post('music-track/store', 'Admin\MusicTrackController@store')->name('music-track.store');
    Route::get('gif-filter', 'Admin\GifController@index')->name('gif-filter.index');
    Route::get('gif-filter/create', 'Admin\GifController@create')->name('gif-filter.create');
    Route::post('gif-filter/store', 'Admin\GifController@store')->name('gif-filter.store');
    Route::post('gif-filter/table', 'Admin\GifController@getJsonData')->name('gif-filter.table');

    //Instagram category
    Route::get('insta-category', 'Admin\InstagramCategoryController@index')->name('insta-category.index');
    Route::get('insta-category/create', 'Admin\InstagramCategoryController@create')->name('insta-category.create');
    Route::post('insta-category/store', 'Admin\InstagramCategoryController@store')->name('insta-category.store');
    Route::post('insta-category/table', 'Admin\InstagramCategoryController@tableData')->name('insta-category.table');
    Route::get('insta-category/{id}/edit', 'Admin\InstagramCategoryController@edit')->name('insta-category.edit');
    Route::post('insta-category/{id}/update', 'Admin\InstagramCategoryController@update')->name('insta-category.update');
    Route::get('insta-category/delete/{id}', 'Admin\InstagramCategoryController@delete')->name('insta-category.delete');
    Route::delete('insta-category/destroy/{id}', 'Admin\InstagramCategoryController@destroy')->name('insta-category.destroy');
    Route::post('insta-category/update/order', 'Admin\InstagramCategoryController@updateOrder')->name('insta-category.update.order');

    // Big Category
    Route::get('big-category', 'Admin\BigCategoryController@index')->name('big-category.index');
    Route::post('big-category/table', 'Admin\BigCategoryController@tableData')->name('big-category.table');
    Route::get('big-category/create', 'Admin\BigCategoryController@create')->name('big-category.create');
    Route::post('big-category/store', 'Admin\BigCategoryController@store')->name('big-category.store');
    Route::get('big-category/{id}/edit', 'Admin\BigCategoryController@edit')->name('big-category.edit');
    Route::post('big-category/{id}/update', 'Admin\BigCategoryController@update')->name('big-category.update');
    Route::get('big-category/delete/{id}', 'Admin\BigCategoryController@delete')->name('big-category.delete');
    Route::delete('big-category/destroy/{id}', 'Admin\BigCategoryController@destroy')->name('big-category.destroy');

    Route::get('currency-coin/check-currency/{id}', 'Admin\CurrencyCoinController@checkCurrency')->name('currency-coin.currency');
    Route::get('currency-coin/coins', 'Admin\CurrencyCoinController@indexCoins')->name('currency-coin.coin-index');
    Route::post('currency-coin/table', 'Admin\CurrencyCoinController@getCurrencyCoinJsonData')->name('currency.coin.table');
    Route::post('currency-coin/list/table', 'Admin\CurrencyCoinController@getCurrencyCoinListJsonData')->name('currency.coin.list.table');
    Route::get('currency-coin/delete/{id}', 'Admin\CurrencyCoinController@delete')->name('currency.coin.delete');
    Route::get('currency-coin/currency/create', 'Admin\CurrencyCoinController@createCurrency')->name('currency.coin.create.currency');
    Route::post('currency-coin/currency/store', 'Admin\CurrencyCoinController@storeCurrency')->name('currency.coin.store.currency');
    Route::get('currency-coin/currency/edit/{id}', 'Admin\CurrencyCoinController@editCurrency')->name('currency.coin.edit.currency');
    Route::put('currency-coin/currency/update/{id}', 'Admin\CurrencyCoinController@updateCurrency')->name('currency.coin.update.currency');
    Route::get('currency-coin/currency/delete/{id}', 'Admin\CurrencyCoinController@deleteCurrency')->name('currency.coin.delete.currency');
    Route::delete('currency-coin/currency/destroy/{id}', 'Admin\CurrencyCoinController@destroyCurrency')->name('currency.coin.destroy.currency');
    Route::resource('currency-coin', 'Admin\CurrencyCoinController');

    //Managers
    Route::get('get/state/{id}', 'Admin\ManagerController@getState')->name('manager.get.state');
    Route::get('get/city/{id}', 'Admin\ManagerController@getCity')->name('manager.get.city');
    Route::get('manager/delete/{id}', 'Admin\ManagerController@delete')->name('category.delete');
    Route::get('manager/activity-log', 'Admin\ManagerController@indexActivityLog')->name('manager.activity-log.index');
    Route::post('manager/table/', 'Admin\ManagerController@getJsonManagerData')->name('manager.all.table');
    Route::post('manager/table/sub-manager', 'Admin\ManagerController@getJsonSubManagerData')->name('manager.sub-manager.table');
    Route::post('manager/table/activity-log/all', 'Admin\ManagerController@getJsonAllActivityLogData')->name('manager.activity-log.all.table');
    Route::post('manager/table/activity-log/deducting-rate', 'Admin\ManagerController@getJsonDeductingRateActivityLogData')->name('manager.activity-log.deducting-rate.table');
    Route::post('manager/table/activity-log/client-credit', 'Admin\ManagerController@getJsonClientCreditActivityLogData')->name('manager.activity-log.client-credit.table');
    Route::post('manager/table/activity-log/delete-account', 'Admin\ManagerController@getJsonDeleteAccountActivityLogData')->name('manager.activity-log.delete-account.table');
    Route::post('manager/recommended-code/check', 'Admin\ManagerController@checkRecommendedCode')->name('manager.recommended-code.check');
    Route::resource('manager', 'Admin\ManagerController');

    // Announcement
    Route::resource('announcement', 'Admin\AnnouncementController');

    //Important Setting
    Route::get('important-setting', 'Admin\ImportantSettingController@index')->name('important-setting.index');
    Route::get('important-setting/limit-custom', 'Admin\ImportantSettingController@indexLimitCustom')->name('important-setting.limit-custom.index');
    Route::get('important-setting/limit-custom/link', 'Admin\ImportantSettingController@indexLimitLinkCustom')->name('important-setting.limit-custom.index-links');
    Route::post('important-setting/table/all/hospital', 'Admin\ImportantSettingController@getJsonAllHospitalData')->name('important-setting.all.hospital.table');
    Route::post('important-setting/table/all/shop', 'Admin\ImportantSettingController@getJsonAllShopData')->name('important-setting.all.shop.table');
    Route::post('important-setting/table/limit-custom', 'Admin\ImportantSettingController@getJsonLimitCustomData')->name('important-setting.limit-custom.table');
    Route::post('important-setting/table/limit-link-custom', 'Admin\ImportantSettingController@getJsonLimitLinkCustomData')->name('important-setting.limit-link-custom.table');
    Route::post('important-setting/send/notification', 'Admin\ImportantSettingController@sendNotification')->name('important-setting.send.notification');
    Route::get('important-setting/limit-custom/edit/{id}', 'Admin\ImportantSettingController@editLimitCustom')->name('important-setting.limit-custom.edit');
    Route::put('important-setting/limit-custom/update/{id}', 'Admin\ImportantSettingController@updateLimitCustom')->name('important-setting.limit-custom.update');
    Route::put('important-setting/limit-custom/update/{id}/product', 'Admin\ImportantSettingController@updateLimitCustomProduct')->name('important-setting.limit-custom.update-product');
    Route::get('important-setting/hospital/edit/{id}', 'Admin\ImportantSettingController@editHospital')->name('important-setting.hospital.edit');
    Route::put('important-setting/hospital/update/{id}', 'Admin\ImportantSettingController@updateHospital')->name('important-setting.hospital.update');
    Route::get('important-setting/shop/edit/{id}', 'Admin\ImportantSettingController@editShop')->name('important-setting.shop.edit');
    Route::put('important-setting/shop/update/{id}', 'Admin\ImportantSettingController@updateShop')->name('important-setting.shop.update');
    Route::get('important-setting/instagram-settings', 'Admin\ImportantSettingController@indexInstagram')->name('important-setting.instagram-settings');
    Route::post('important-setting/instagram-settings/save', 'Admin\ImportantSettingController@saveInstagram')->name('save.instagram_time');
    Route::get('important-setting/payple-settings', 'Admin\ImportantSettingController@indexPaypleSetting')->name('important-setting.payple-setting.index');
    Route::post('important-setting/table/payple-settings', 'Admin\ImportantSettingController@getJsonPaypleData')->name('important-setting.payple-settings.table');

    Route::get('important-setting/show-hide', 'Admin\ImportantSettingController@indexShowHide')->name('important-setting.show-hide.index');
    Route::post('important-setting/show-hide/table', 'Admin\ImportantSettingController@getJsonShowHideData')->name('important-setting.show-hide.table');
    Route::get('important-setting/show-hide/edit/{id}/{type?}/{country?}', 'Admin\ImportantSettingController@editShowHide')->name('important-setting.show-hide.edit');
    Route::put('important-setting/show-hide/update/{id}', 'Admin\ImportantSettingController@updateShowHide')->name('important-setting.show-hide.update');
    Route::put('important-setting/country/update/{id}', 'Admin\ImportantSettingController@updateCountryDetail')->name('important-setting.country.update');

    Route::put('important-setting/show-hide/category-options', 'Admin\ImportantSettingController@saveCategoryCountry')->name('save.category.country');
    Route::post('get/category-options/value', 'Admin\ImportantSettingController@getCategoryCountry')->name('get.category.country.value');

    Route::get('important-setting/limit-custom/edit/{id}/language', 'Admin\ImportantSettingController@editLimitCustomLanguage')->name('important-setting.limit-custom.edit.language');
    Route::put('important-setting/limit-custom/update/{id}/language', 'Admin\ImportantSettingController@updateLimitCustomLanguage')->name('important-setting.limit-custom.update.language');


    // Menu Settings
    Route::get('important-setting/menu-settings', 'Admin\MenuSettingAdminController@index')->name('important-setting.menu-setting.index');
    Route::post('important-setting/menu-settings/table', 'Admin\MenuSettingAdminController@tableData')->name('important-setting.menu-setting.table');
    Route::post('menu-settings/update', 'Admin\MenuSettingAdminController@updateOnOff')->name('menu-setting.update');
    Route::post('menu-settings/update/category', 'Admin\MenuSettingAdminController@updateCategoryOnOff')->name('menu-setting.updatecategory');
    Route::post('menu-settings/update/order', 'Admin\MenuSettingAdminController@updateOrder')->name('menu-setting.update.order');
    Route::get('edit/menu-setting/{id}', 'Admin\MenuSettingAdminController@editMenuSetting')->name('menu-setting.edit.menu');
    Route::put('update/menu-setting/{id}', 'Admin\MenuSettingAdminController@saveMenuSetting')->name('important-setting.menu.update');

    // Category Setting Page
    Route::get('important-setting/category-settings', 'Admin\CategorySettingsAdminController@index')->name('important-setting.category-setting.index');
    Route::post('important-setting/category-settings/table', 'Admin\CategorySettingsAdminController@tableData')->name('important-setting.category-setting.table');
    Route::post('important-setting/category-settings/card', 'Admin\CategorySettingsAdminController@cardData')->name('important-setting.category-setting.card');
    Route::post('important-setting/category-settings/update-card-order', 'Admin\CategorySettingsAdminController@updateCardOrder')->name('important-setting.category-setting.update-card-order');
    Route::post('category-settings/update', 'Admin\CategorySettingsAdminController@updateOnOff')->name('category-setting.update');
    Route::post('category-settings/update-hidden', 'Admin\CategorySettingsAdminController@updateOnOffHidden')->name('category-setting.update.hidden');
    Route::post('category-settings/update/order', 'Admin\CategorySettingsAdminController@updateOrder')->name('category-setting.update.order');
    Route::get('important-setting/category-settings/create', 'Admin\CategorySettingsAdminController@createCategory')->name('important-setting.category-setting.create');
    Route::post('important-setting/category-settings/store', 'Admin\CategorySettingsAdminController@storeCategroy')->name('important-setting.category-setting.store');
    Route::get('important-setting/category-settings/{id}/edit/{country_code?}', 'Admin\CategorySettingsAdminController@editCategory')->name('important-setting.category-setting.edit');
    Route::post('important-setting/category-settings/{id}/update', 'Admin\CategorySettingsAdminController@updateCategory')->name('important-setting.category-setting.update');
    Route::post('category-settings/update/big-category/order', 'Admin\CategorySettingsAdminController@updateBigcategoryOrder')->name('category-setting.update.big-category.order');
    Route::post('category-settings/display/big-category', 'Admin\CategorySettingsAdminController@displayeBigcategory')->name('category-setting.display.big-category');
    Route::get('category-settings/delete/{id}', 'Admin\CategorySettingsAdminController@deleteCategory')->name('category-setting.delete');
    Route::delete('category-settings/destroy/{id}', 'Admin\CategorySettingsAdminController@destroyCategory')->name('category-settings.destroy');

    // App Version
    Route::get('important-setting/app-version', 'Admin\ImportantSettingController@viewAppVersion')->name('important-setting.app-version.index');
    Route::post('important-setting/update/app-version', 'Admin\ImportantSettingController@updateSettings')->name('important-setting.app-version.update');

    Route::get('important-setting/policy-pages', 'CMSPagesController@index')->name('important-setting.policy-pages.index');
    Route::post('important-setting/policy-pages/list', 'CMSPagesController@getJsonData')->name('policy-pages.get.data');
    Route::get('important-setting/policy-pages/create', 'CMSPagesController@create')->name('policy-pages.create');
    Route::get('important-setting/policy-pages/edit/{page}', 'CMSPagesController@edit')->name('policy-pages.edit');
    Route::post('policy-pages/update/{id}', 'CMSPagesController@update')->name('policy-pages.update');
    Route::post('policy-pages/store', 'CMSPagesController@store')->name('policy-pages.store');
    Route::get('policy-pages/get/delete/{id}', 'CMSPagesController@getDelete')->name('policy-pages.get.delete');
    Route::post('policy-pages/delete', 'CMSPagesController@deletePage')->name('policy-pages.delete');
    Route::post('/page/ckeditor/upload', 'CMSPagesController@uploadImage')->name('ckeditor.upload');

    //global price settings
    Route::get('important-setting/global-price-settings', 'Admin\GlobalPriceController@index')->name('important-setting.global-price-setting.index');
    Route::post('important-setting/global-price-settings/table', 'Admin\GlobalPriceController@tableData')->name('important-setting.global-price-setting.table');
    Route::get('important-setting/global-price-settings/price-category/create', 'Admin\GlobalPriceController@createPriceCategory')->name('important-setting.global-price-setting.price-category.create');
    Route::post('important-setting/global-price-settings/price-category/store', 'Admin\GlobalPriceController@storePriceCategory')->name('important-setting.global-price-setting.price-category.store');
    Route::get('important-setting/global-price-settings/price-category/{id}/edit', 'Admin\GlobalPriceController@editPriceCategory')->name('important-setting.global-price-setting.price-category.edit');
    Route::post('important-setting/global-price-settings/price-category/{id}/update', 'Admin\GlobalPriceController@updatePriceCategory')->name('important-setting.global-price-setting.price-category.update');
    Route::get('important-setting/global-price-settings/price/{id}/create', 'Admin\GlobalPriceController@createPrice')->name('important-setting.global-price-setting.price.add');
    Route::post('important-setting/global-price-settings/price/store', 'Admin\GlobalPriceController@storePrice')->name('important-setting.global-price-setting.price.store');
    Route::get('important-setting/global-price-settings/price/{id}/edit', 'Admin\GlobalPriceController@editPrice')->name('important-setting.global-price-setting.price.edit');
    Route::post('important-setting/global-price-settings/price/{id}/update', 'Admin\GlobalPriceController@updatePrice')->name('important-setting.global-price-setting.price.update');
    Route::get('important-setting/global-price-settings/delete/{id}/{type}', 'Admin\GlobalPriceController@delete')->name('important-setting.global-price-setting.delete');
    Route::delete('important-setting/global-price-settings/destroy/{id}/{type}', 'Admin\GlobalPriceController@destroy')->name('important-setting.global-price-setting.destroy');

    // Reward Instagram
    Route::get('reward-instagram', 'Admin\RewardInstagramController@index')->name('reward-instagram.index');
    Route::post('reward-instagram/table/all/hospital', 'Admin\RewardInstagramController@getJsonAllData')->name('reward-instagram.all.table');
    Route::get('reward-instagram/penalty/{id}', 'Admin\RewardInstagramController@givePenalty')->name('reward-instagram.penalty');
    Route::get('reward-instagram/reject/{id}', 'Admin\RewardInstagramController@giveReject')->name('reward-instagram.reject');
    Route::get('reward-instagram/reward/{id}', 'Admin\RewardInstagramController@giveReward')->name('reward-instagram.reward');
    Route::get('reward-instagram/view/shop/image/{id}', 'Admin\RewardInstagramController@viewShopImage')->name('reward-instagram.view.shop.image');
    Route::post('reward-instagram/reward/multiple', 'Admin\RewardInstagramController@rewardMultiple')->name('reward-instagram.reward.multiple');
    Route::post('reward-instagram/reject/mention', 'Admin\RewardInstagramController@PenaltyRejectMention')->name('reward-instagram.reject.mention');

    // Top Post
    Route::get('top-post/hospital', 'Admin\TopPostController@hospitalPost')->name('top-post.hospital.index');
    Route::get('top-post/popup', 'Admin\TopPostController@popupPost')->name('top-post.popup.index');
    //Route::get('top-post-old', 'Admin\TopPostController@oldindex')->name('top-post.oldindex');
    //Route::get('top-post', 'Admin\TopPostController@index')->name('top-post.index');
    Route::post('top-post/country-details', 'Admin\TopPostController@filterCountryData')->name('top-post.country-details');
    Route::resource('top-post', 'Admin\TopPostController');
    Route::post('top-post/add/post/', 'Admin\TopPostController@addPost')->name('top-post.add.post');
    Route::post('top-post/store/post/', 'Admin\TopPostController@storePost')->name('top-post.store.post');
    Route::get('top-post/edit/post/{id}', 'Admin\TopPostController@editPost')->name('top-post.edit.post');
    Route::post('top-post/update/post/', 'Admin\TopPostController@updatePost')->name('top-post.update.post');
    Route::post('top-post/update/checkbox/', 'Admin\TopPostController@updateCheckbox')->name('top-post.update.checkbox');
    Route::post('top-post/update/hospital/post/', 'Admin\TopPostController@updateHospitalPost')->name('top-post.update.hospital-post');
    Route::get('top-post/delete/{id}', 'Admin\TopPostController@delete')->name('top-post.delete');
    Route::get('top-post/get/events', 'Admin\TopPostController@getHospitalEvents')->name('top-post.get.events');

    //Suggest Custom
    Route::get('suggest-custom', 'Admin\SuggestCustomController@index')->name('suggest-custom.index');
    Route::post('suggest-custom/table/all', 'Admin\SuggestCustomController@getJsonAllData')->name('suggest-custom.all.table');
    Route::post('suggest-custom/table/active', 'Admin\SuggestCustomController@getJsonActiveData')->name('suggest-custom.active.table');
    Route::post('suggest-custom/table/inactive', 'Admin\SuggestCustomController@getJsonInactiveData')->name('suggest-custom.inactive.table');
    Route::get('suggest-custom/{id}', 'Admin\SuggestCustomController@show')->name('suggest-custom.show');
    Route::get('suggest-custom/edit/credit/{id}', 'Admin\SuggestCustomController@editCredits')->name('suggest-custom.edit.credit');
    Route::get('suggest-custom/view/profile/{id}', 'Admin\SuggestCustomController@viewProfile')->name('suggest-custom.view.profile');

    // Activity Log
    Route::get('activity-log', 'Admin\ActivityLogController@index')->name('activity-log.index');
    Route::get('activity-log/hospital', 'Admin\ActivityLogController@indexHospital')->name('activity-log.hospital.index');
    Route::get('activity-log/shop', 'Admin\ActivityLogController@indexShop')->name('activity-log.shop.index');
    Route::get('activity-log/custom', 'Admin\ActivityLogController@indexCustom')->name('activity-log.custom.index');

    // Get AJAX
    Route::post('activity-log/shop/filter', 'Admin\ActivityLogController@getJsonAllData')->name('activity-log.shop-filter');

    //Reload Coin
    Route::get('reload-coin', 'Admin\ReloadCoinController@index')->name('reload-coin.index');
    Route::get('reload-coin/reject/popup/{id}', 'Admin\ReloadCoinController@rejectCoinPopup')->name('reload-coin.reject.popup');
    Route::post('reload-coin/reject/coins/{id}', 'Admin\ReloadCoinController@rejectCoins')->name('reload-coin.reject.coins');
    Route::get('reload-coin/confirm/popup/{id}', 'Admin\ReloadCoinController@giveCoinPopup')->name('reload-coin.confirm.popup');
    Route::post('reload-coin/confirm/coins/{id}', 'Admin\ReloadCoinController@giveCoins')->name('reload-coin.confirm.coins');
    Route::post('reload-coin/table/all/{id}', 'Admin\ReloadCoinController@getJsonAllData')->name('reload-coin.all.table');
    Route::post('reload-coin/table/all/shop/{id}', 'Admin\ReloadCoinController@getJsonAllShopData')->name('reload-coin.all.shop.table');
    Route::post('reload-coin/table/all/hospital/{id}', 'Admin\ReloadCoinController@getJsonAllHospitalData')->name('reload-coin.all.hospital.table');

    //My Business Client
    Route::get('my-business-client', 'Admin\MyBusinessClientController@index')->name('my-business-client.index');
    Route::post('my-business-client/table/all', 'Admin\BusinessClientController@getJsonAllData')->name('my-business-client.all.table');
    Route::post('my-business-client/table/active', 'Admin\BusinessClientController@getJsonActiveData')->name('my-business-client.active.table');
    Route::post('my-business-client/table/inactive', 'Admin\BusinessClientController@getJsonInActiveData')->name('my-business-client.inactive.table');
    Route::get('my-business-client/hospital', 'Admin\MyBusinessClientController@indexHospital')->name('my-business-client.hospital.index');
    Route::get('my-business-client/shop', 'Admin\MyBusinessClientController@indexShop')->name('my-business-client.shop.index');
    Route::post('my-business-client/table/all/hospital', 'Admin\BusinessClientController@getJsonAllHospitalData')->name('my-business-client.all.hospital.table');
    Route::post('my-business-client/table/active/hospital', 'Admin\BusinessClientController@getJsonActiveHospitalData')->name('my-business-client.active.hospital.table');
    Route::post('my-business-client/table/inactive/hospital', 'Admin\BusinessClientController@getJsonInActiveHospitalData')->name('my-business-client.inactive.hospital.table');
    Route::post('my-business-client/table/all/shop', 'Admin\BusinessClientController@getJsonAllShopData')->name('my-business-client.all.shop.table');
    Route::post('my-business-client/table/active/shop', 'Admin\BusinessClientController@getJsonActiveShopData')->name('my-business-client.active.shop.table');
    Route::post('my-business-client/table/inactive/shop', 'Admin\BusinessClientController@getJsonInActiveShopData')->name('my-business-client.inactive.shop.table');
    Route::get('my-business-client/{id}/hospital', 'Admin\MyBusinessClientController@show')->name('my-business-client.hospital.show');
    Route::get('my-business-client/{id}/shop', 'Admin\MyBusinessClientController@showShop')->name('my-business-client.shop.show');
    Route::get('my-business-client/edit/credit/{id}', 'Admin\MyBusinessClientController@editCredits')->name('my-business-client.edit.credit');
    Route::get('my-business-client/view/profile/{id}', 'Admin\MyBusinessClientController@viewHospitalProfile')->name('my-business-client.view.profile');
    Route::get('my-business-client/view/logs/{id}', 'Admin\MyBusinessClientController@viewLogs')->name('my-business-client.view.logs');
    Route::get('my-business-client/view/shop/profile/{id}', 'Admin\MyBusinessClientController@viewShopProfile')->name('my-business-client.view.shop.profile');
    Route::post('my-business-client/add/credit/', 'Admin\MyBusinessClientController@updateCredits')->name('my-business-client.add.credit');
    Route::post('my-business-client/delete/profile/', 'Admin\MyBusinessClientController@deleteBusinessProfile')->name('my-business-client.delete.profile');
    Route::post('my-business-client/delete/user/', 'Admin\MyBusinessClientController@deleteUser')->name('my-business-client.delete.user');

    //Users
    Route::get('users', 'Admin\UserController@index')->name('user.index');
    Route::get('users/{id}/view-community', 'Admin\ViewCommunityController@viewCommunity')->name('user.view.community');
    Route::get('users/{id}/community', 'Admin\UserController@showCommunity')->name("user.show-community");
    Route::post('users/community/table/all/{id}', 'Admin\UserController@getCommunityJsonAllData')->name('user.community.all.table');
    Route::get('users/{id}/community/create/view', 'Admin\UserController@createCommunityUserView')->name('user.community.create.view');
    Route::post('users/{id}/community/create', 'Admin\UserController@createCommunityUser')->name('user.community.create');
    Route::get('users/{id}/community/edit/{community_id}/{type?}', 'Admin\UserController@createCommunityUserView')->name('user.community.edit');
    Route::post('users/community/delete/{community_id}/{type?}', 'Admin\UserController@deleteUserCommunity')->name('user.community.delete');
    Route::post('users/community/remove/image', 'Admin\UserController@removeCommunityImage')->name('user.community.remove.image');
    Route::post('users/community/load/category', 'Admin\UserController@loadSubCategory')->name('user.community.load.category');
    Route::post('users/table/all', 'Admin\UserController@getJsonAllData')->name('user.all.table');
    Route::get('users/get/account/{id}', 'Admin\UserController@getAccount')->name('user.get-account');
    Route::get('users/get/edit/account/{id}', 'Admin\UserController@getEditAccount')->name('user.get-edit-account');
    Route::get('users/get/edit/email/{id}', 'Admin\UserController@getEditEmail')->name('user.get-edit-email');
    Route::get('users/get/edit/phone/{id}', 'Admin\UserController@getEditPhone')->name('user.get-edit-phone');
    Route::post('users/delete/account/{id}', 'Admin\UserController@deleteAccount')->name('user.delete-account');
    Route::post('users/edit/account/{id}', 'Admin\UserController@editAccount')->name('user.edit-account');
    Route::post('users/edit/email/{id}', 'Admin\UserController@editEmailAddress')->name('user.edit-email');
    Route::post('users/edit/phone/{id}', 'Admin\UserController@editPhone')->name('user.edit-phone');
    Route::get('users/create', 'Admin\UserController@createUser')->name('user.create');
    Route::post('users/store', 'Admin\UserController@storeUser')->name('user.store');
    Route::post('users/storemultiple', 'Admin\UserController@storeUserMultiple')->name('user.storemultiple');
    Route::post('users/storemultiple/business', 'Admin\UserController@storeUserMultipleBusiness')->name('user.storemultiple.business');
    Route::get('view/user/to/shop/{id}', 'Admin\UserController@getUserToShopView')->name('user.to-be-shop');
    Route::post('update/user/to/shop', 'Admin\UserController@updateUserToShop')->name('update.user.to.shop');
    Route::post('update/user/shop/category', 'Admin\UserController@updateUserShopCategory')->name('update.user.shop.category');
    Route::post('users/{id}/load/community/detail', 'Admin\ViewCommunityController@loadCommunityDetails')->name('load.user.community');
    Route::post('users/{id}/community/detail/{community_id}', 'Admin\ViewCommunityController@communityDetails')->name('user.community.detail');
    Route::post('users/community/like/{community_id}', 'Admin\ViewCommunityController@likeCommunity')->name('user.community.like');
    Route::post('users/community/comment/{community_id}', 'Admin\ViewCommunityController@postComments')->name('user.community.comment');
    Route::post('make/user/outside/{id}', 'Admin\UserController@makeUserOutside')->name('make.user.outside');
    Route::get('show/referral/users/{id}', 'Admin\UserController@showRefferalUser')->name('show.referral.user');
    Route::get('users/send_coffee/{id}', 'Admin\UserController@send_coffee')->name('user.send_coffee');
    Route::get('show/gifticon/modal/{id?}/{gifti_id?}', 'Admin\UserController@getGifticon')->name('user.get.gitfticon');
    Route::post('gifticon/store', 'Admin\GifticonController@store')->name('gifticon.store');
    Route::post('gifticon/update/{id}', 'Admin\GifticonController@update')->name('gifticon.update');
    Route::post('gifticon/image/remove', 'Admin\GifticonController@removeImage')->name('gifticon-image.remove');
    Route::get('show/locations/user/{id}', 'Admin\UserController@showUserLocations')->name('show.locations.user');
    Route::post('users/add/signup-code', 'Admin\UserController@addSignupCode')->name('user.signup-code.save');
    Route::get('users/get/edit/username/{id}', 'Admin\UserController@getEditUsername')->name('user.get-edit-username');
    Route::post('users/edit/username/{id}', 'Admin\UserController@editUsername')->name('user.edit-username');

    Route::get('users/lost-category', 'Admin\UserController@lostCategoryShop')->name('user.lost-category');
    Route::post('users/lost-category/table', 'Admin\UserController@getJsonAllLostCategoryData')->name('user.lost-category.table');

    Route::get('outside-community-user', 'Admin\UserController@listOutsideUsers')->name('outside-user.list');
    Route::post('outside-community-user/table/all', 'Admin\UserController@getJsonAllOutsideUsers')->name('outside-community-user.all.table');
    Route::get('outside-community-user/{id}', 'Admin\UserController@getOutsideUserView')->name('outside-community-user.user.view');
    Route::post('outside-community-user/table/all/{id}', 'Admin\UserController@getJsonAllOutsideUsersDetail')->name('outside-community-user.user.community.list');

    Route::get('non-login-user', 'Admin\UserController@listNonLoginUsers')->name('non-login-user.list');
    Route::post('non-login-user/table/all', 'Admin\UserController@getJsonAllNonLoginUsers')->name('non-login-user.all.table');
    Route::get('show/locations/non-login-user/{id}', 'Admin\UserController@showNonloginUserLocations')->name('show.locations.non-login-user');

    Route::get('feed-log', 'Admin\FeedLogController@index')->name('feed-log.index');
    Route::post('feed-log/table', 'Admin\FeedLogController@getJsonData')->name('feed-log.table');
    Route::post('feed-log/edit/note', 'Admin\FeedLogController@editNote')->name('feed-log.edit-note');
    Route::get('show/all-feed-logs/user/{id}', 'Admin\FeedLogController@showUserFeedlogs')->name('show.all-feed-logs.user');
    Route::get('show/auto-love-users', 'Admin\FeedLogController@showAutoloveUsers')->name('show.auto-love-users');
    Route::post('user/add-more/love', 'Admin\FeedLogController@addMoreLove')->name('user.add-more.love');

    Route::post('get/user/form', 'Admin\UserController@getUserForm')->name('get.user.form');

    Route::get('users/{id}/cards', 'Admin\UserController@showCards')->name("user.show-cards");
    Route::get('give/user/card/model/{id}', 'Admin\UserController@giveCardsModel')->name("give.user.cards.model");
    Route::post('give/user/card/{id}', 'Admin\UserController@giveCards')->name("give.user.card");
    Route::get('delete/user/card/model/{id}', 'Admin\UserController@deleteCardModel')->name("delete.user.card.model");
    Route::post('remove/user/card/{id}', 'Admin\UserController@removeCards')->name("remove.user.card");
    Route::post('users/cards/table/{id}', 'Admin\UserController@getCardsJsonData')->name('user.cards.table');
    Route::post('users/{id}/give/exp', 'Admin\UserController@giveEXP')->name("user.give.exp");

    // Community
    Route::get('community', 'Admin\CommunityController@index')->name('community.index');
    Route::post('community/table', 'Admin\CommunityController@getJsonData')->name('community.table');

    Route::get('community/delete/{id}', 'Admin\CommunityController@delete')->name('community.delete');
    Route::post('community/delete', 'Admin\CommunityController@deleteCommunity')->name('community.delete');

    Route::get('community/delete/user/{id}', 'Admin\CommunityController@deleteCommunityUser')->name('community.delete.user');
    Route::post('community/delete/user', 'Admin\CommunityController@deleteUser')->name('community.delete.user');

    Route::get('community/{id}', 'Admin\CommunityController@show')->name('community.show');

    Route::get('comment', 'Admin\CommentController@index')->name('comment.index');
    Route::post('comment/table', 'Admin\CommentController@getJsonData')->name('comment.table');
    Route::get('comment/get/account/{id}', 'Admin\CommentController@getAccount')->name('comment.get-account');
    Route::post('comment/delete/account/{id}', 'Admin\CommentController@deleteAccount')->name('comment.delete-account');
    Route::get('comment/get/comment/{id}/{table}', 'Admin\CommentController@getCommentModel')->name('comment.get-comment');
    Route::post('comment/delete/comment/{id}/{table}', 'Admin\CommentController@deleteCommentDetails')->name('comment.delete-comment');
    Route::get('comment/{table}/{id}', 'Admin\CommentController@show')->name('comment.show');

    Route::post('delete/user/details', 'Controller@deleteUserDetails')->name('delete.user.details');

    // Check Bad Completed
    Route::get('bad-complete', 'Admin\CheckBadCompleteController@index')->name('bad-complete.index');
    Route::post('bad-complete/table/all', 'Admin\CheckBadCompleteController@getJsonAllData')->name('bad-complete.all.table');
    Route::post('bad-complete/table/two-week', 'Admin\CheckBadCompleteController@getJsonTwoWeekData')->name('bad-complete.two-week.table');

    // Manager Activity Logs
    Route::get('manager-activity-log', 'Admin\ManagerController@managerActivityLog')->name('manager-activity-log.index');
    Route::post('manager-activity-log/table/all', 'Admin\ManagerController@getAllManagerActivityLog')->name('manager-activity-log.all.table');

    // Review Post
    Route::get('check-review', 'Admin\CheckReviewPost@index')->name('check-review.index');
    Route::post('check-review/table/data', 'Admin\CheckReviewPost@getJsonData')->name('review-post.table');
    Route::get('check-review/detail/{id}', 'Admin\CheckReviewPost@show')->name('review-post.show');
    Route::get('check-review/get/delete/{id}', 'Admin\CheckReviewPost@getDeletePost')->name('get.review-post.delete');
    Route::post('check-review/delete/detail', 'Admin\CheckReviewPost@DeleteReviewPost')->name('review-post.delete');

    Route::get('reported-shop', 'Admin\ShopBlockAdminController@index')->name('reported-shop.index');
    Route::post('reported-shop/table', 'Admin\ShopBlockAdminController@getJsonData')->name('reported-shop.table');
    Route::get('reported-shop/detail/{id}', 'Admin\ShopBlockAdminController@viewDetail')->name('reported-shop.view');

    //like order
    Route::get('like-order', 'Admin\LikeOrderController@index')->name('like-order.index');
    Route::post('like-order/table', 'Admin\LikeOrderController@getJsonData')->name('like-order.table');
    Route::post('like-order/expired-user/table', 'Admin\LikeOrderController@getJsonDataExpired')->name('like-order.expired-user.table');
    Route::post('give/shop/post/like', 'Admin\LikeOrderController@giveLike')->name('give.like.post');
    Route::get('instagram/service/{shop}', 'Admin\LikeOrderController@instagramServicePopup')->name('instagram.service');

    //Delete account reasons
    Route::get('reasons-delete-account', 'Admin\DeleteAccountReasonController@index')->name('reasons-delete-account.index');
    Route::post('reasons-delete-account/table', 'Admin\DeleteAccountReasonController@getJsonData')->name('reasons-delete-account.table');
    Route::get('reasons-delete-account/delete/{id}', 'Admin\DeleteAccountReasonController@delete')->name('reasons-delete-account.delete');
    Route::delete('reasons-delete-account/destroy/{id}', 'Admin\DeleteAccountReasonController@destroy')->name('reasons-delete-account.destroy');

    //deleted users
    Route::get('deleted-users', 'Admin\DeletedUserController@index')->name('deleted-users.index');
    Route::post('deleted-users/table', 'Admin\DeletedUserController@getJsonData')->name('deleted-users.table');
    Route::get('deleted-users/view/shop/profile/{id}', 'Admin\DeletedUserController@viewShopProfile')->name('deleted-users.view.shop.profile');

    //Instagram connect log
    Route::get('instagram-connect-log', 'Admin\InstagramConnectLogController@index')->name('instagram-connect-log.index');
    Route::post('instagram-connect-log/table', 'Admin\InstagramConnectLogController@getJsonData')->name('instagram-connect-log.table');
    Route::get('instagram-connect-log/status/send-mail/{insta_log_id}', 'Admin\InstagramConnectLogController@statusSendMail')->name('instagram-connect-log.status.send-mail');

    //Upload on instagram
    Route::get('upload-instagram', 'Admin\UploadOnInstagramController@index')->name('upload-instagram.index');
    Route::post('upload-instagram/table/all', 'Admin\UploadOnInstagramController@getJsonAllData')->name('upload-instagram.all.table');
    Route::get('upload-instagram/upload/{access_token}', 'Admin\UploadOnInstagramController@getFiles')->name('upload-instagram.select');
    Route::post('upload-instagram/save', 'Admin\UploadOnInstagramController@saveInstagram')->name('upload-instagram.store');
    Route::get('instagram/redirect', 'Admin\UploadOnInstagramController@redirectToInstagram');
    Route::get('instagram/callback', 'Admin\UploadOnInstagramController@handleInstagramCallback');

    //Paypal
    Route::get('paypal', 'Admin\PaypalController@index')->name('paypal.index');
    Route::post('paypal/add-bill', 'Admin\PaypalController@saveBill')->name('paypal.store.bill');
    Route::post('paypal/table', 'Admin\PaypalController@billinggetJsonData')->name('paypal.table');

    //Coupon
    Route::get('coupon', 'Admin\CouponController@index')->name('coupon.index');
    Route::post('coupon/add', 'Admin\CouponController@saveCoupon')->name('coupon.store');
    Route::post('coupon/table', 'Admin\CouponController@couponJsonData')->name('coupon.table');

    //Regular payments
    Route::get('regular-payment', 'Admin\RegularPaymentController@index')->name('regular-payment.index');
    Route::post('regular-payment/table', 'Admin\RegularPaymentController@regularPaymentgetJsonData')->name('regular-payment.table');
    Route::post('regular-payment/repayment', 'Admin\RegularPaymentController@rePayment')->name('regular-payment.repayment');
    Route::get('regular-payment/get/edit/{id}', 'Admin\RegularPaymentController@getEdit')->name('regular-payment.get-edit');
    Route::post('regular-payment/edit/{id}', 'Admin\RegularPaymentController@updateData')->name('regular-payment.edit');
    Route::get('regular-payment/calendar', 'Admin\RegularPaymentController@calendarIndex')->name('regular-payment.calendar-index');
    Route::get('regular-payment/get/edit-next-payment/{id}', 'Admin\RegularPaymentController@getEditNextPay')->name('regular-payment.get.edit-next-payment');
    Route::post('regular-payment/edit-next-payment/{id}', 'Admin\RegularPaymentController@editNextPayment')->name('regular-payment.edit-next-payment');
    Route::get('regular-payment/payment-log/{id}', 'Admin\RegularPaymentController@paymentLog')->name('regular-payment.payment-log');
    Route::get('regular-payment/{next_payment}', 'Admin\RegularPaymentController@nextPayIndex')->name('regular-payment.next-payment-index');
    Route::post('regular-payment/next-payment/table', 'Admin\RegularPaymentController@nextPaymentJsonData')->name('regular-payment.next-payment.table');
    Route::get('show/all-payment', 'Admin\RegularPaymentController@showAllPayment')->name('show.all-payments');
    Route::post('regular-payment/next-payment-visibility', 'Admin\RegularPaymentController@editNextPaymentVisibility')->name('regular-payment.next-payment-visibility');
    Route::post('regular-payment/remove-payment', 'Admin\RegularPaymentController@removePayment')->name('regular-payment.remove-payment');
    Route::post('regular-payment/month-payments', 'Admin\RegularPaymentController@monthPayments')->name('regular-payment.month-payments');

    //Hashtag list
    Route::get('hashtags', 'Admin\HashtagController@index')->name('hashtags.index');
    Route::post('hashtags/table', 'Admin\HashtagController@getJsonAllData')->name('hashtags.table');
    Route::post('hashtags/update/show-hide', 'Admin\HashtagController@updateOnOff')->name('hashtags.update.show-hide');

    //Research form
    Route::get('research-form', 'Admin\ResearchFormController@index')->name('research-form.index');

    //Admin chat
    Route::get('admin-chat', 'Admin\AdminChatController@index')->name('admin-chat.index');
    Route::post('admin-chat/table', 'Admin\AdminChatController@getJsonData')->name('admin-chat.table');
    Route::get('admin-chat/show/user-messages/{id}', 'Admin\AdminChatController@showUserMessages')->name('show.admin-chat.user-messages');

    //group chat messages
    Route::get('message', 'Admin\MessageController@index')->name('message.index');
    Route::post('message/table', 'Admin\MessageController@getJsonData')->name('message.table');
    Route::get('message/delete/{id}', 'Admin\MessageController@deleteMessage')->name('message.delete');
    Route::delete('message/destroy/{id}', 'Admin\MessageController@destroyMessage')->name('message.destroy');
    Route::post('message/save', 'Admin\MessageController@storeData')->name('message.store');
    Route::post('message/multiple/remove', 'Admin\MessageController@removeMultipleMessage')->name('message.multiple.remove');
    Route::get('chat-bot/{country}', 'Admin\MessageController@chatBotIndex')->name('message.chat-bot.index');
    Route::post('chat-bot/message/store', 'Admin\MessageController@saveMessage')->name('message.chat-bot.store');
    Route::post('chat-bot/table', 'Admin\MessageController@chatBotgetJsonData')->name('message.chat-bot.table');

    //reported group chat
    Route::get('reported-group-chat', 'Admin\ReportedUserController@index')->name('reported-group-chat.index');
    Route::post('reported-group-chat/table', 'Admin\ReportedUserController@getJsonData')->name('reported-group-chat.table');
    Route::get('group-chat/show/user-messages/{id}', 'Admin\ReportedUserController@showUserMessages')->name('show.group-chat.user-messages');
    Route::get('group-chat/delete/message/{id}', 'Admin\ReportedUserController@deleteMessage')->name('group-chat.delete.message');
    Route::delete('group-chat/destroy/message/{id}', 'Admin\ReportedUserController@destroyMessage')->name('group-chat.destroy.message');

    //Reported message
    Route::get('reported-message', 'Admin\ReportedMessageController@index')->name('reported-message.index');
    Route::post('reported-message/table', 'Admin\ReportedMessageController@getJsonData')->name('reported-message.table');

    // Association
    Route::get('association', 'Admin\AssociationController@index')->name('association.index');
    Route::get('association/form/{id?}', 'Admin\AssociationController@form')->name('association.form');
    Route::get('association/manage/{id?}', 'Admin\AssociationController@manageAssociation')->name('association.manage');
    Route::post('association/save', 'Admin\AssociationController@saveAssociates')->name('association.save');
    Route::post('association/data', 'Admin\AssociationController@getJsonData')->name('association.data');
    Route::post('association/image/remove', 'Admin\AssociationController@removeImage')->name('association-image.remove');
    Route::post('association/delete', 'Admin\AssociationController@deleteAssociation')->name('association.delete');
    Route::get('association/get/delete/{id}', 'Admin\AssociationController@getDeleteAssociation')->name('association.get.delete');

    Route::get('association/show/{association}', 'Admin\AssociationController@show')->name('association.show');

    Route::post('association/category/data', 'Admin\AssociationController@getCatrgoryJsonData')->name('association.category.data');
    Route::get('association/category/form/{association}/{id?}', 'Admin\AssociationController@categoryForm')->name('association.category.form');
    Route::post('association/category/save', 'Admin\AssociationController@saveAssociatesCategory')->name('association.category/save');

    Route::get('association/category/get/delete/{id}', 'Admin\AssociationController@getDeleteCategory')->name('get.association-category.delete');
    Route::delete('association/category/destroy/{id}', 'Admin\AssociationController@destroyCategory')->name('association-category.destroy');

    Route::get('reload/coin-logs', 'Admin\ReloadCoinController@showReloadCoinLogs')->name('reload.coin-logs.show');
    Route::post('reload/coin-logs/data', 'Admin\ReloadCoinController@getReloadCoinJsonData')->name('reload.coin-logs.data');

    Route::get('theme-section', 'Admin\ThemeSectionController@index')->name('theme-section.index');
    Route::post('theme-section/save-options', 'Admin\ThemeSectionController@saveOptions')->name('save.options');

    Route::get('important-setting/explanation', 'Admin\ThemeSectionController@showExplanation')->name('explanation.index');
    Route::post('explanation/table/all', 'Admin\ThemeSectionController@getJsonAllData')->name('explanation.all.table');
    Route::get('important-setting/explanation/edit/{id}', 'Admin\ThemeSectionController@editExplanation')->name('explanation.edit');
    Route::put('important-setting/explanation/update/{id}', 'Admin\ThemeSectionController@updateExplanation')->name('explanation.update');

    // default cards
    Route::get('cards', 'Admin\CardsController@index')->name('cards.index');
    Route::post('cards/table', 'Admin\CardsController@getJsonData')->name('cards.table');
    Route::get('cards/create', 'Admin\CardsController@create')->name('cards.create');
    Route::post('cards/store', 'Admin\CardsController@store')->name('cards.store');
    Route::get('cards/edit/{card}', 'Admin\CardsController@edit')->name('cards.edit');
    Route::post('cards/update/{card}', 'Admin\CardsController@update')->name('cards.update');
    Route::get('cards/delete/{id}', 'Admin\CardsController@delete')->name('cards.delete');
    Route::delete('cards/destroy/{id}', 'Admin\CardsController@destroy')->name('cards.destroy');
    Route::get('cards/users/{card}', 'Admin\CardsController@userCards')->name('cards.users');
    Route::post('cards/users/table/{card}', 'Admin\CardsController@getUserCardsJson')->name('cards.table.users');
    Route::post('view/cards/{card}', 'Admin\CardsController@viewCardData')->name('view.cards.detail');
    Route::post('remove/cards/image/{card?}', 'Admin\CardsController@removeCardImage')->name('remove.cards.image');
    Route::get('manage/card/level/{card}', 'Admin\CardsController@manageStatusRive')->name('manage.status.rive');
    Route::post('card/level/update/{card}', 'Admin\CardsController@updateStatusRive')->name('cards.level.rive.update');
    Route::get('manage/card/music/{card}', 'Admin\CardsController@manageMusic')->name('manage.music');
    Route::post('card/music/list/{card}', 'Admin\CardsController@getMusicJsonData')->name('card.music.get.data');
    Route::get('manage/card/music/{card}/create', 'Admin\CardsController@createMusicView')->name('create.card.music');
    Route::post('manage/card/music/{card}/store', 'Admin\CardsController@cardMusicStore')->name('card.music.store');
    Route::get('music/get/delete/{id}', 'Admin\CardsController@getDeleteMusic')->name('music.get.delete');
    Route::post('music/delete', 'Admin\CardsController@deleteMusic')->name('music.delete');
    Route::post('music/update/order', 'Admin\CardsController@updateOrder')->name('card.music.update.order');


    Route::get('requested-cards', 'Admin\CardsController@requestedCard')->name('requested.cards.index');
    Route::post('requested-cards/table', 'Admin\CardsController@getJsonDataRequestedCard')->name('requested-cards.table');
    Route::get('requested-cards/processed/{card_id}', 'Admin\CardsController@processedCard')->name('requested-cards.processed');
    Route::post('requested-cards/action/processed/{card_id}', 'Admin\CardsController@actionProcessedCard')->name('requested-cards.action.processed');
    Route::get('requested-cards/reject/{card_id}', 'Admin\CardsController@rejectCard')->name('requested-cards.reject');
    Route::post('requested-cards/action/reject/{card_id}', 'Admin\CardsController@actionRejectCard')->name('requested-cards.action.reject');

    // Wedding
    Route::get('wedding', 'Admin\WeddingController@index')->name('wedding.index');
    Route::post('wedding/list', 'Admin\WeddingController@getJsonData')->name('wedding.get.data');
    Route::get('wedding/create', 'Admin\WeddingController@create')->name('wedding.create');
    Route::get('wedding/{id}/edit', 'Admin\WeddingController@edit')->name('wedding.edit');
    Route::post('wedding/update/{id}', 'Admin\WeddingController@update')->name('wedding.update');
    Route::post('wedding/store', 'Admin\WeddingController@store')->name('wedding.store');
    Route::post('wedding/image/remove', 'Admin\WeddingController@removeImage')->name('wedding-image.remove');
    Route::get('wedding/get/delete/{id}', 'Admin\WeddingController@getDeleteWedding')->name('wedding.get.delete');
    Route::post('wedding/delete', 'Admin\WeddingController@deleteWedding')->name('wedding.delete');
    Route::post('wedding/add/more', 'Admin\WeddingController@addMoreField')->name('wedding.add.more');

    Route::get('wedding/settings', 'Admin\WeddingController@weddingSetting')->name('wedding.settings');
    Route::get('wedding/settings/create', 'Admin\WeddingController@weddingSettingCreate')->name('wedding.settings.create');
    Route::post('wedding/setting/list', 'Admin\WeddingController@getSettingJsonData')->name('wedding.setting.get.data');
    Route::post('wedding/settings/change/field', 'Admin\WeddingController@ChangeField')->name('wedding.change.field');
    Route::get('wedding/settings/get/delete/{id}', 'Admin\WeddingController@getSettingDeleteWedding')->name('wedding.settings.get.delete');
    Route::post('wedding/settings/delete', 'Admin\WeddingController@deleteWeddingSettings')->name('wedding.settings.delete');
    Route::post('wedding/settings/store', 'Admin\WeddingController@settingStore')->name('wedding.settings.store');

    // Brand Section
    Route::resource('brand-category', 'Admin\BrandCategoryController');
    Route::post('brand-category/table', 'Admin\BrandCategoryController@getJsonData')->name('brand-category.table');
    Route::post('brand-category/update/order', 'Admin\BrandCategoryController@updateOrder')->name('brand-category.update.order');
    Route::get('brand-category/delete/{id}', 'Admin\BrandCategoryController@delete')->name('brand-category.delete');

    Route::resource('brands', 'Admin\BrandsController');
    Route::post('brands/table', 'Admin\BrandsController@getJsonData')->name('brands.table');
    Route::post('brands/update/order', 'Admin\BrandsController@updateOrder')->name('brands.update.order');
    Route::get('brands/delete/{id}', 'Admin\BrandsController@delete')->name('brands.delete');

    Route::resource('brand-products', 'Admin\BrandProductController');
    Route::post('brand-products/table', 'Admin\BrandProductController@getJsonData')->name('brand-products.table');
    Route::post('brand-products/update/order', 'Admin\BrandProductController@updateOrder')->name('brand-products.update.order');
    Route::get('brand-products/delete/{id}', 'Admin\BrandProductController@delete')->name('brand-products.delete');

    Route::get('product-orders', 'Admin\ProductOrdersController@index')->name('product-orders.index');
    Route::post('product-orders/table', 'Admin\ProductOrdersController@getJsonData')->name('product-orders.table');

    Route::get('certification-exam', 'Admin\CertificationController@index')->name('certification-exam.index');
    Route::post('certification-exam/tests/table', 'Admin\CertificationController@getJsonData')->name('tests.table');
    Route::get('certification-exam/tests/create', 'Admin\CertificationController@createTests')->name('tests.create');
    Route::post('certification-exam/tests/store', 'Admin\CertificationController@storeTests')->name('tests.store');
    Route::get('certification-exam/tests/edit/{id}', 'Admin\CertificationController@editTests')->name('tests.edit');
    Route::put('certification-exam/tests/update/{id}', 'Admin\CertificationController@updateTests')->name('tests.update');
    Route::get('certification-exam/tests/delete/{id}', 'Admin\CertificationController@delete')->name('tests.delete');
    Route::delete('certification-exam/tests/destroy/{id}', 'Admin\CertificationController@destroy')->name('tests.destroy');
});

// Business Login
Route::name('business.')->prefix('business')->group(function () {
    Auth::routes();
});
Route::name('business.')->prefix('business')->middleware('BusinessAuth')->group(function () {
    Route::get('profile/{header?}', 'Admin\ProfileController@edit')->name('profile.show')->defaults('header', 'business-');
    Route::get('dashboard', 'Business\DashboardController@index')->name('dashboard.index');

    //Post Management
    Route::get('posts', 'Business\PostManagementController@index')->name('posts.index');
    Route::post('posts/table/all', 'Business\PostManagementController@getJsonAllData')->name('post.all.table');
    Route::post('posts/add-image', 'Business\PostManagementController@getNewImageHtml')->name('post.add-image');
    Route::post('posts/image/remove', 'Business\PostManagementController@removePostImage')->name('post-image.remove');
    Route::get('posts/get/delete/{id}', 'Business\PostManagementController@getDeletePost')->name('get.post.delete');
    Route::post('posts/delete/detail', 'Business\PostManagementController@DeletePost')->name('post.delete');
    Route::resource('posts', 'Business\PostManagementController'); //->name('post.edit');


    // Customer Management
    Route::get('customers', 'Business\CustomerController@index')->name('customers.index');
    Route::post('customers/table', 'Business\CustomerController@getJsonData')->name('customers.table');

    // Hospital Management
    Route::get('hospital/edit', 'Business\PostManagementController@editHospital')->name('hospital.manage');

    // Shop Management
    //Route::resource('shop', 'Business\ShopManagementController');
    //Route::post('shop/table/all', 'Business\ShopManagementController@getJsonAllData')->name('post.all.table');

    // Community
    Route::get('community', 'Business\CommunityPostController@index')->name('community.index');
    Route::post('community/table/all', 'Business\CommunityPostController@getJsonAllData')->name('community.all.table');
    Route::post('community/store', 'Business\CommunityPostController@store')->name('community.store');
    Route::get('community/details/{id}', 'Business\CommunityPostController@showDetails')->name('community.details');

    // Association
    Route::get('association/{header?}', 'User\AssociationController@index')->name('association.index')->defaults('header', 'business-');
    Route::post('association/data', 'User\AssociationController@getJsonData')->name('association.data');
    Route::get('association/show/{association}/{header?}', 'User\AssociationController@show')->name('association.show')->defaults('header', 'business-');
    Route::post('association/show/user/data', 'User\AssociationController@getUserJsonData')->name('association.details');
});

// User Login
Route::name('user.')->prefix('user')->group(function () {
    Auth::routes();
});
Route::name('user.')->prefix('user')->middleware('UserAuth')->group(function () {
    Route::get('profile/{header?}', 'Admin\ProfileController@edit')->name('profile.show')->defaults('header', 'user-');
    Route::get('dashboard', 'User\DashboardController@index')->name('dashboard.index');

    Route::get('association/{header?}', 'User\AssociationController@index')->name('association.index')->defaults('header', 'user-');
    Route::post('association/data', 'User\AssociationController@getJsonData')->name('association.data');
    Route::get('association/show/{association}/{header?}', 'User\AssociationController@show')->name('association.show')->defaults('header', 'user-');
    Route::post('association/show/user/data', 'User\AssociationController@getUserJsonData')->name('association.details');
});

// Tattoocity Login
Route::name('tattoocity.')->prefix('tattoocity')->group(function () {
    Auth::routes();
});
Route::name('tattoocity.')->prefix('tattoocity')->middleware(['TattoocityAuth','authlocalization'])->group(function () {
    Route::get('dashboard', 'Tattoocity\DashboardController@index')->name('dashboard.index');

    //Users
    Route::get('users', 'Tattoocity\UserController@index')->name('user.index');
    Route::post('users/table/all', 'Tattoocity\UserController@getJsonAllData')->name('user.all.table');
});

// Spa Login
Route::name('spa.')->prefix('spa')->group(function () {
    Auth::routes();
});
Route::name('spa.')->prefix('spa')->middleware(['SpaAuth','authlocalization'])->group(function () {
    Route::get('dashboard', 'Spa\DashboardController@index')->name('dashboard.index');

    //Users
    Route::get('users', 'Spa\UserController@index')->name('user.index');
    Route::post('users/table/all', 'Spa\UserController@getJsonAllData')->name('user.all.table');
});
Route::get('spa/support', 'CMSPagesController@supportPageSpa')->name('spa.support-page.view');

// Challenge Login
Route::name('challenge.')->prefix('challenge')->group(function () {
    Auth::routes();
    Route::get('pages/{slug}', 'Challenge\PolicyController@viewPages')->name('page.view');
});
Route::name('challenge.')->prefix('challenge')->middleware(['ChallengeAuth','authlocalization'])->group(function () {
    Route::get('dashboard', 'Challenge\DashboardController@index')->name('dashboard.index');

    //Users
    Route::get('users', 'Challenge\UserController@index')->name('user.index');
    Route::post('users/table/all', 'Challenge\UserController@getJsonAllData')->name('user.all.table');
    Route::post('users/add-user', 'Challenge\UserController@saveUser')->name('user.store');
    Route::get('users/challenges/{id}', 'Challenge\UserController@challengeList')->name('users.challenge-list');
    Route::get('users/calender/{id}', 'Challenge\UserController@calenderIndex')->name('users.calender.index');
    Route::get('users/edit/{id}', 'Challenge\UserController@editData')->name('users.edit');
    Route::post('users/edit-user', 'Challenge\UserController@updateUser')->name('user.update');

    //Calender
    Route::get('calendar/period-challenge', 'Challenge\CalenderController@periodChallengeIndex')->name('calendar.period-challenge.index');
    Route::get('calendar/challenge', 'Challenge\CalenderController@challengeIndex')->name('calendar.challenge.index');

    //Challenge
    Route::get('challenge-page', 'Challenge\ChallengeController@index')->name('challenge-page.index');
    Route::post('period-challenge/save', 'Challenge\ChallengeController@savePeriodChallenge')->name('period-challenge.save');
    Route::post('challenge/save', 'Challenge\ChallengeController@saveChallenge')->name('challenge.save');
    Route::post('challenge-page/table/all', 'Challenge\ChallengeController@getJsonAllData')->name('challenge-page.all.table');
    Route::get('challenge-page/edit/{id}', 'Challenge\ChallengeController@editData')->name('challenge-page.edit');
    Route::post('challenge-page/update', 'Challenge\ChallengeController@updateData')->name('challenge-page.update');
    Route::get('challenge-page/users/{id}', 'Challenge\ChallengeController@userList')->name('challenge-page.users');
    Route::post('challenge-page/select-users', 'Challenge\ChallengeController@selectUsers')->name('challenge-page.select-users');
    Route::get('challenge-page/view/{id}', 'Challenge\ChallengeController@viewChallenge')->name('challenge-page.view');
    Route::post('get/thumb-list', 'Challenge\ChallengeController@getThumb')->name('thumb-list');

    //Thumb image list
    Route::get('thumb-image', 'Challenge\ThumbImageController@index')->name('thumb-image.index');
    Route::post('thumb-image/store', 'Challenge\ThumbImageController@saveThumb')->name('thumb-image.store');
    Route::post('thumb-image/table/all', 'Challenge\ThumbImageController@getJsonAllData')->name('thumb-image.all.table');
    Route::post('get/category-dropdown', 'Challenge\ThumbImageController@getCategories')->name('category-dropdown');
    Route::get('thumb-image/edit/{id}', 'Challenge\ThumbImageController@editData')->name('thumb-image.edit');
    Route::post('thumb-image/update', 'Challenge\ThumbImageController@updateThumb')->name('thumb-image.update');

    Route::get('delete-account', 'Challenge\DeleteAccountController@index')->name('delete-account.index');

    Route::get('verification', 'Challenge\VerificationController@index')->name('verification.index');
    Route::post('verification/table/all', 'Challenge\VerificationController@getJsonAllData')->name('verification.all.table');
    Route::post('verification/update/verify', 'Challenge\VerificationController@updateVerify')->name('verification.update.verify');
    Route::post('verification/update/reject', 'Challenge\VerificationController@updateReject')->name('verification.update.reject');
    Route::post('verification/view-images', 'Challenge\VerificationController@viewImages')->name('verification.view-images');

    Route::get('order', 'Challenge\OrderController@index')->name('order.index');
    Route::post('order/table/all', 'Challenge\OrderController@getJsonAllData')->name('order.all.table');

    Route::get('category', 'Challenge\CategoryController@index')->name('category.index');
    Route::post('category/add', 'Challenge\CategoryController@saveCategory')->name('category.store');
    Route::post('category/table', 'Challenge\CategoryController@getJsonData')->name('category.table');
    Route::post('category/update-show-hide', 'Challenge\CategoryController@updateShowHide')->name('category.update-show-hide');
    Route::post('category/update/order', 'Challenge\CategoryController@updateOrder')->name('category.update.order');
    Route::get('category/edit/{id}', 'Challenge\CategoryController@editData')->name('category.edit');
    Route::post('category/update', 'Challenge\CategoryController@updateCategory')->name('category.update');

    Route::get('admin-push', 'Challenge\AdminPushController@index')->name('admin-push.index');

    Route::get('policy', 'Challenge\PolicyController@index')->name('policy.index');
    Route::post('policy/list', 'Challenge\PolicyController@getJsonData')->name('policy.get.data');
    Route::get('policy/create', 'Challenge\PolicyController@create')->name('policy.create');
    Route::post('policy/store', 'Challenge\PolicyController@store')->name('policy.store');
    Route::post('policy/ckeditor/upload', 'Challenge\PolicyController@uploadImage')->name('ckeditor.upload');
    Route::get('policy/edit/{page}', 'Challenge\PolicyController@edit')->name('policy.edit');
    Route::post('policy/update/{id}', 'Challenge\PolicyController@update')->name('policy.update');
    Route::get('policy/get/delete/{id}', 'Challenge\PolicyController@getDelete')->name('policy.get.delete');
    Route::post('policy/delete', 'Challenge\PolicyController@deletePage')->name('policy.delete');

    Route::get('link', 'Challenge\LinkController@index')->name('link.index');
    Route::post('link/add', 'Challenge\LinkController@saveLink')->name('link.store');
    Route::post('link/table/all', 'Challenge\LinkController@getJsonAllData')->name('link.all.table');

    Route::get('naming-customizing', 'Challenge\NamingCustomizingController@index')->name('naming-customizing.index');
    Route::get('naming-customizing/edit', 'Challenge\NamingCustomizingController@editData')->name('naming-customizing.edit');
    Route::post('naming-customizing/update', 'Challenge\NamingCustomizingController@updateData')->name('naming-customizing.update');

    Route::get('invite-text', 'Challenge\InviteTextController@index')->name('invite-text.index');
    Route::post('invite-text/save', 'Challenge\InviteTextController@saveInviteText')->name('invite-text.store');

    Route::get('notification-admin', 'Challenge\NotificationAdminController@index')->name('notification-admin.index');
    Route::post('notification-admin/table', 'Challenge\NotificationAdminController@getJsonData')->name('notification-admin.table');
    Route::get('notification-admin/edit/{id}', 'Challenge\NotificationAdminController@editData')->name('notification-admin.edit');
    Route::put('notification-admin/update/{id}', 'Challenge\NotificationAdminController@updateData')->name('notification-admin.update');

    Route::get('admin-notice', 'Challenge\AdminNoticeController@index')->name('admin-notice.index');
    Route::post('admin-notice/add', 'Challenge\AdminNoticeController@saveData')->name('admin-notice.store');
    Route::post('admin-notice/table/all', 'Challenge\AdminNoticeController@getJsonAllData')->name('admin-notice.all.table');
    Route::get('admin-notice/edit/image', 'Challenge\AdminNoticeController@editImage')->name('admin-notice.edit.image');
    Route::post('admin-notice/update-image', 'Challenge\AdminNoticeController@updateImage')->name('admin-notice.update-image');
    Route::get('admin-notice/edit/bio', 'Challenge\AdminNoticeController@editBio')->name('admin-notice.edit.bio');
    Route::post('admin-notice/update-bio', 'Challenge\AdminNoticeController@updateBio')->name('admin-notice.update-bio');
    Route::get('admin-notice/edit/{id}', 'Challenge\AdminNoticeController@editData')->name('admin-notice.edit');
    Route::post('admin-notice/update', 'Challenge\AdminNoticeController@updateData')->name('admin-notice.update');
    Route::get('admin-notice/get-delete/{id}', 'Challenge\AdminNoticeController@getDelete')->name('admin-notice.get-delete');
    Route::post('admin-notice/delete', 'Challenge\AdminNoticeController@deleteData')->name('admin-notice.delete');

    Route::get('kakao-talk-link', 'Challenge\KakaoTalkLinkController@index')->name('kakao-talk-link.index');
    Route::get('kakao-talk-link/edit', 'Challenge\KakaoTalkLinkController@editData')->name('kakao-talk-link.edit');
    Route::post('kakao-talk-link/update', 'Challenge\KakaoTalkLinkController@updateData')->name('kakao-talk-link.update');

    Route::get('invitation-management', 'Challenge\InvitationManagementController@index')->name('invitation-management.index');
    Route::post('invitation-management/follower/table', 'Challenge\InvitationManagementController@getJsonAllData')->name('invitation-management.follower.table');
    Route::get('invitation-management/invited-users/{id}', 'Challenge\InvitationManagementController@invitedFollowerList')->name('invitation-management.invited-users.users');
    Route::get('invitation-management/app-invitation', 'Challenge\InvitationManagementController@appInvitationIndex')->name('invitation-management.app-invitation.index');
    Route::post('invitation-management/app-invitation/table', 'Challenge\InvitationManagementController@appInvitationJsonAllData')->name('invitation-management.app-invitation.table');
});

// Insta Login
Route::name('insta.')->prefix('insta')->group(function () {
    Auth::routes();
});
Route::name('insta.')->prefix('insta')->middleware(['InstaAuth','authlocalization'])->group(function () {
    Route::get('dashboard', 'Insta\DashboardController@index')->name('dashboard.index');

    Route::get('important-setting', 'Insta\ImportantSettingController@index')->name('important-setting.index');
    Route::post('important-setting/table', 'Insta\ImportantSettingController@getJsonData')->name('important-setting.table');
    Route::get('important-setting/edit/{id}', 'Insta\ImportantSettingController@editData')->name('important-setting.edit');
    Route::put('important-setting/update/{id}', 'Insta\ImportantSettingController@updateData')->name('important-setting.update');

    //Users
    Route::get('users', 'Insta\UserController@index')->name('user.index');
    Route::post('users/table/all', 'Insta\UserController@getJsonAllData')->name('user.all.table');
});

// QR code Login
Route::name('qr-code.')->prefix('qr-code')->group(function () {
    Auth::routes();
});
Route::name('qr-code.')->prefix('qr-code')->middleware(['QrCodeAuth','authlocalization'])->group(function () {
    Route::get('dashboard', 'QrCode\DashboardController@index')->name('dashboard.index');
});

Route::get('add_to_user_referrals', function () {
    $users_detail = \App\Models\UserDetail::whereNotNull('recommended_by')->get();
    foreach ($users_detail as $user_detail) {
        \App\Models\UserReferral::create([
            'referred_by' => $user_detail->recommended_by,
            'referral_user' => $user_detail->user_id,
        ]);
    }

    echo "Done";
    exit;
});

Route::get('add_to_user_referral_details', function () {
    $referred_by = \App\Models\UserReferral::get();
    foreach ($referred_by as $referred) {
        $start = $referred->id - 1;
        $cnt = \App\Models\UserReferral::where('referred_by', $referred->referred_by)->where('has_coffee_access', 0)->limit(3)->get();

        if (count($cnt) == 3) {
            \App\Models\UserReferralDetail::create([
                'user_id' => $cnt[0]['referred_by'],
            ]);

            foreach ($cnt as $c) {
                $c->has_coffee_access = 1;
                $c->save();
            }
        }
    }

    echo "Done";
    exit;
});

Route::get('user-account/request-delete', 'UserController@getDelete');
Route::post('user-account/submit-request-delete', 'UserController@submitDelete');
Route::get('paypal/{id}/payment', 'Admin\PaypalController@getPayment')->name('paypal.get.payment');
Route::post('paypal/authenticate', 'Admin\PaypalController@authenticate')->name('paypal.authenticate');
Route::post('paypal/payment/result/{id}', 'Admin\PaypalController@paymentResult')->name('paypal.payment.result');
Route::get('coupon/view/{id}', 'Admin\CouponController@getImage');


Route::get('change_signup',function (){
    \App\Models\User::where('signup_type',null)->update([
        'signup_type' => 'email'
    ]);
});


