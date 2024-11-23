<?php

namespace App\Http\Controllers\Admin;

use App\Models\GifticonDetail;
use App\Models\ShopConnectLink;
use App\Models\UserHiddenCategory;
use App\Models\UserLocationHistory;
use App\Models\UserReferral;
use App\Models\UserReferralDetail;
use Illuminate\Support\Facades\Crypt;
use Log;
use Auth;
use Hash;
use Exception;
use Validator;
use Carbon\Carbon;
use App\Models\City;
use App\Models\Shop;
use App\Models\User;
use App\Models\Cards;
use App\Models\Config;
use App\Models\Notice;
use App\Models\Status;
use App\Util\Firebase;
use App\Models\Address;
use App\Models\Manager;
use App\Mail\CommonMail;
use App\Models\Category;
use App\Models\Hospital;
use App\Models\CardLevel;
use App\Models\Community;
use App\Models\UserCards;
use App\Models\UserCredit;
use App\Models\UserDetail;
use App\Models\UserPoints;
use App\Models\EntityTypes;
use App\Models\PackagePlan;
use App\Models\RequestForm;
use App\Models\UserDevices;
use Illuminate\Support\Str;
use App\Models\DefaultCards;
use App\Models\ReportClient;
use Illuminate\Http\Request;
use App\Models\CategoryTypes;
use App\Models\UserCardLevel;
use Illuminate\Http\Response;
use App\Models\CardLevelDetail;
use App\Models\CommunityImages;
use App\Models\DefaultCardsRives;
use App\Models\RequestFormStatus;
use App\Models\UserCreditHistory;
use Illuminate\Http\JsonResponse;
use App\Models\NonLoginUserDetail;
use App\Models\UserEntityRelation;
use Illuminate\Support\Facades\DB;
use App\Models\LinkedSocialProfile;
use App\Http\Controllers\Controller;
use App\Models\AssociationCommunity;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use App\Models\AssociationCommunityImage;
use App\Models\AssociationCommunityComment;

class UserController extends Controller
{
    protected $firebase;

    public function __construct()
    {
        $this->firebase = new Firebase();
        $this->middleware('permission:user-list', ['only' => ['index']]);
    }

    public function index(Request $request)
    {
        $title = 'All User';
        /*  DB::table('community_comments')->where('is_admin_read', 1)->update(['is_admin_read' => 0]);
        DB::table('community_comment_reply')->where('is_admin_read', 1)->update(['is_admin_read' => 0]);
        DB::table('association_community_comments')->where('is_admin_read', 1)->update(['is_admin_read' => 0]); */
        DB::table('users')->where('is_admin_read', 1)->update(['is_admin_read' => 0]);

        $totalUsers = UserEntityRelation::join('users', 'users.id', 'user_entity_relation.user_id')->whereIN('user_entity_relation.entity_type_id', [EntityTypes::NORMALUSER, EntityTypes::HOSPITAL, EntityTypes::SHOP])->whereIn('users.status_id', [Status::ACTIVE, Status::INACTIVE])->whereNotNull('users.email')->distinct('user_id')->count('user_id');
        $totalShops = UserEntityRelation::where('entity_type_id', EntityTypes::SHOP)->distinct('user_entity_relation.user_id')->count('user_entity_relation.user_id');
        $totalHospitals = UserEntityRelation::where('entity_type_id', EntityTypes::HOSPITAL)->distinct('user_entity_relation.user_id')->count('user_entity_relation.user_id');

        $totalNormalUser = $totalUsers - ($totalShops + $totalHospitals);

        $category = Category::where('category.status_id', Status::ACTIVE)
            ->where('category.category_type_id', EntityTypes::SHOP)
            ->where('category.parent_id', 0)
            ->get();

        $unreadReferralCount = DB::table('users_detail')->join('users', 'users.id', 'users_detail.user_id')->whereNull('users.deleted_at')->whereNotNull('users_detail.recommended_by')->where('users_detail.is_referral_read', 1)->count();
        return view('admin.users.index', compact('title', 'category', 'totalUsers', 'totalShops', 'totalHospitals', 'totalNormalUser', 'unreadReferralCount'));
    }

    public function getJsonAllData(Request $request)
    {
        $columns = array(
            0 => 'users_detail.name',
            2 => 'users.email',
            3 => 'users_detail.mobile',
            6 => 'users.created_at',
            10 => 'users.last_login',
            11 => 'user_cards.love_count',
            12 => 'users_detail.level',
            14 => 'action',
        );

        $filter = $request->input('filter');
        $categoryFilterID = $request->input('category');
        $hide_other = $request->input('hide_other');
        $limit = $request->input('length');
        $start = $request->input('start');
        $search = $request->input('search.value');
        $draw = $request->input('draw');
        $order = $columns[$request->input('order.0.column')];
        $dir = $request->input('order.0.dir');

        $adminTimezone = $this->getAdminUserTimezone();
        $viewButton = '';
        $toBeShopButton = '';
        $loginUser = Auth::user();
//        try {
            $data = [];
            $userIDs = [];

            if ($filter == 'user') {
                $userIDs = DB::table('users')->leftJoin('user_entity_relation', 'user_entity_relation.user_id', 'users.id')
                    ->whereIN('user_entity_relation.entity_type_id', [EntityTypes::HOSPITAL, EntityTypes::SHOP])
                    ->whereNotNull('users.email')
                    ->whereNull('users.deleted_at')
                    ->pluck('users.id');
                $userIDs = ($userIDs) ? $userIDs->toArray() : [];
            }

            $userQuery = DB::table('users')->leftJoin('user_entity_relation', 'user_entity_relation.user_id', 'users.id')
                ->leftJoin('users_detail', 'users_detail.user_id', 'users.id')
                ->leftjoin('shops', function ($query) use ($filter) {
                    $query->on('users.id', '=', 'shops.user_id');
                })
                ->leftjoin('user_cards', function ($query) use ($filter) {
                    $query->on('users.id', '=', 'user_cards.user_id')->where('user_cards.is_applied', 1);
                })
                ->leftjoin('hospitals', 'user_entity_relation.entity_id', 'hospitals.id')
                ->whereIN('user_entity_relation.entity_type_id', [EntityTypes::NORMALUSER, EntityTypes::HOSPITAL, EntityTypes::SHOP])
                ->whereNotNull('users.email')
                ->whereNull('users.deleted_at')
                ->whereIn('users.status_id', [Status::ACTIVE, Status::INACTIVE])
                ->whereNotIn('users.id', $userIDs)
                ->select(
                    'users.id',
                    'users_detail.name',
                    'users_detail.level',
                    'users_detail.mobile',
                    'users_detail.recommended_by',
                    'users_detail.recommended_code',
                    'users_detail.promot_insta_around',
                    'users.inquiry_phone',
                    'users.connect_instagram',
                    'users.email',
                    'users.is_admin_access',
                    'users.is_support_user',
                    'users.created_at as date',
                    'users.last_login as last_access',
                    'users.app_type',
                    DB::raw('IFNULL(user_cards.love_count, 0) as love_count'),
                    DB::raw('(SELECT group_concat(entity_type_id) from user_entity_relation WHERE user_id = users.id) as entity_types')
                )
                ->selectSub(function ($q) {
                    $q->select(DB::raw('count(id) as count'))->from('linked_social_profiles')->where('social_type', LinkedSocialProfile::Instagram)->whereRaw("`user_id` = `users`.`id`");
                }, 'linked_account_count')
                ->selectSub(function ($q) {
                    $q->select('ref.name as referred_by_name')->from('users_detail as ref')->join('users as ru', 'ru.id', 'ref.user_id')->whereNull('ru.deleted_at')->whereIn('ru.status_id', [Status::ACTIVE, Status::INACTIVE])->whereRaw("`ref`.`user_id` = `users_detail`.`recommended_by`");
                }, 'referred_by_name')
                ->groupBy('users.id');

            if (!empty($search)) {
                $userQuery = $userQuery->where(function ($q) use ($search) {
                    $q->where('users_detail.name', 'LIKE', "%{$search}%")
                        ->orWhere('users.email', 'LIKE', "%{$search}%")
                        ->orWhere('users.created_at', 'LIKE', "%{$search}%")
                        ->orWhere('shops.shop_name', 'LIKE', "%{$search}%")
                        ->orWhere('shops.main_name', 'LIKE', "%{$search}%")
                        ->orWhere('hospitals.main_name', 'LIKE', "%{$search}%")
                        ->orWhere('users_detail.mobile', 'LIKE', "%{$search}%")
                        ->orWhere('shops.another_mobile', 'LIKE', "%{$search}%");
                });
            }

            if ($filter != 'all' && $filter != 'user' && $filter != 'referred-user' && $filter != 'call' && $filter != 'naver_book' && $filter != 'admin_user' && $filter != 'support_user') {
                if ($filter == 'active') {
                    $filterWhere = Status::ACTIVE;
                } elseif ($filter == 'inactive') {
                    $filterWhere = Status::INACTIVE;
                } elseif ($filter == 'pending') {
                    $filterWhere = Status::PENDING;
                }
                $userQuery = $userQuery->where(function ($q) use ($filterWhere) {
                    $q->where('shops.status_id', $filterWhere)
                        ->orWhere('hospitals.status_id', $filterWhere);
                });
            }

            if ($filter == 'call') {
                $userQuery = $userQuery->whereNotNull('shops.another_mobile');
            }
            if ($filter == 'naver_book') {
                $userQuery = $userQuery->whereNotNull('shops.booking_link');
            }
            if ($filter == 'admin_user') {
                $userQuery = $userQuery->where('users.is_admin_access',1);
            }
            if ($filter == 'support_user') {
                $userQuery = $userQuery->where('users.is_support_user',1);
            }

            $userQuery = $userQuery->selectSub(function ($q) {
                $q->select(DB::raw('count(users_detail.id) as count'))->from('users_detail')->whereRaw("`users_detail`.`recommended_by` = `users`.`id`");
            }, 'referral_count');

            // Count Number
            $userQuery = $userQuery->selectSub(function ($q) {
                $q->select(DB::raw('count(users_detail.id) as count'))->from('users_detail')->where('users_detail.is_referral_read', 1)->whereRaw("`users_detail`.`recommended_by` = `users`.`id`");
            }, 'new_referral_count');

            if ($filter == 'referred-user') {

                // Max date for order
                $userQuery = $userQuery->selectSub(function ($q) {
                    $q->select(DB::raw('MAX(users_detail.created_at)'))->from('users_detail')->whereNull('users_detail.deleted_at')->whereRaw("`users_detail`.`recommended_by` = `users`.`id`");
                }, 'max_referral_date');


                //$userQuery = $userQuery->whereRaw('referral_count > 0');
                $userQuery = $userQuery->havingRaw('referral_count > 0')->orderByRaw('max_referral_date DESC');


                // Reset count
                // DB::table('users_detail')->where('is_referral_read',1)->update(['is_referral_read' => 0]);
            }

            if (!empty($categoryFilterID) && $categoryFilterID != 'all') {
                $userQuery = $userQuery->where('shops.category_id', $categoryFilterID);
            }

            if($hide_other==1){
                $userQuery = $userQuery->where('users.app_type',"mearound");
            }

            $totalData = count($userQuery->get());
            $totalFiltered = $totalData;

            $userQuery = $userQuery->offset($start)->limit($limit);
            if ($filter != 'referred-user') {
                $userQuery = $userQuery->orderBy($order, $dir);
            }
            $userData = $userQuery->get();

            $count = 0;
            foreach ($userData as $user) {
                $statusHtml =  '<div style="display:flex;" class="align-items-center">';
                $userTypes = explode(",", $user->entity_types);
                $outsideBusinessButton = $viewCommunity = $viewCards = '';
                $portfolioCount = '';
                $otherNumber = $new_referral_count_div = '';
                $inquiry_phone = ($user->inquiry_phone == true) ? "<img class='ml-2 small-list-icon' src='" . asset('img/call-require.png') . "' />" : '';
                $connect_instagram = ($user->connect_instagram == true) ? "<img class='ml-2 small-list-icon' src='" . asset('img/connect_instagram.png') . "' />" : '';
                $outsideHtml = $outsideDisplayHtml = '';
                $viewCardRoute = route('admin.user.show-cards', [$user->id]);
                $viewCards = "<a class='mx-1 btn btn-primary ml-2' href='$viewCardRoute' ><i class='fa fa-id-card' aria-hidden='true'></i></a>";

                if (!empty($user->referred_by_name)) {
                    $referredByButton = "<a role='button' href='javascript:void(0);' onClick='showReferralDetail({$user->recommended_by})' title='' data-original-title='View' class='btn btn-primary btn-sm mt-2' data-toggle='tooltip'>{$user->referred_by_name}</a>";
                } else {
                    $referredByButton = '';
                }

                $referralHTML = '';
                $serviceData = '<div style="display:flex;" class="align-items-center">';
                if (in_array(EntityTypes::HOSPITAL, $userTypes)) {
                    $userType = "Hospital";
                    $hospitalData = DB::table('hospitals')->join('user_entity_relation', 'user_entity_relation.entity_id', 'hospitals.id')
                        ->where('user_entity_relation.user_id', $user->id)
                        ->where('user_entity_relation.entity_type_id', EntityTypes::HOSPITAL)
                        ->select('hospitals.*')
                        ->first();
                    if ($hospitalData->status_id == Status::ACTIVE) {
                        $statusHtml .= '<span class="badge badge-success">&nbsp;</span>';
                    } elseif ($hospitalData->status_id == Status::PENDING) {
                        $statusHtml .= '<span class="badge" style="background-color: #fff700;">&nbsp;</span>';
                    } else {
                        $statusHtml .= '<span class="badge badge-secondary">&nbsp;</span>';
                    }
                    $linkButton = route('admin.business-client.hospital.show', $hospitalData->id);

                    $viewButton = "<a role='button' href='$linkButton'  data-original-title='View' class='btn btn-primary btn-sm ' data-toggle='tooltip'><i class='fas fa-eye mt-1'></i></a>";
                    $toBeShopButton = '';

                    $loginUser = Auth::user();

//                    $editCoinButton = '';
                    $editCoinButton = $loginUser->hasRole('Admin') ? "<a href='javascript:void(0)' role='button' onclick='editCredits(" . $user->id . ")' class='btn btn-primary btn-sm mx-1' data-toggle='tooltip' data-original-title='Edit'><i class='fa fa-edit' style='font-size: 15px;margin: 4px -5px 4px 0;'></i></a>" : "";

                    if ($hospitalData->business_link != '') {
                        $statusHtml .= '<i class="fas fa-star" style="font-size: 25px; color: #fff700; -webkit-text-stroke-width: 1px; -webkit-text-stroke-color: #43425d;"></i>';
                    }
                    $referralHTML = $this->getReferralDetail($user, $user->referral_count);
                }
                elseif (in_array(EntityTypes::SHOP, $userTypes)) {
                    $viewCommunityRoute = route('admin.user.show-community', [$user->id]);
                    $viewCommunity = "<a class='btn btn-primary' href='$viewCommunityRoute' ><img src='" . asset('img/community.svg') . "' /></a>";

                    $portfolioCount = DB::table('shop_posts')->join('shops', 'shops.id', 'shop_posts.shop_id')
                        ->whereNull('shop_posts.deleted_at')
                        ->whereNull('shops.deleted_at')
                        ->where('shops.user_id', $user->id)
                        ->count();

                    $userType = "Shop";
                    $viewButton = "<a role='button' href='javascript:void(0)' onclick='viewShopProfile(" . $user->id . ")'  title='' data-original-title='View' class='btn btn-primary btn-sm ' data-toggle='tooltip'><i class='fas fa-eye mt-1'></i></a>";
                    $toBeShopButton = '';

                    $shopsData = DB::table('shops')->whereNull('deleted_at')->where('user_id', $user->id)->get();

                    $isOutsideBusiness = false;
                    if ($shopsData) {
                        foreach ($shopsData as $key => $value) {
                            if ($value->status_id == Status::ACTIVE) {
                                $statusHtml .= '<span class="badge badge-success">&nbsp;</span>';
                            } elseif ($value->status_id == Status::PENDING) {
                                $statusHtml .= '<span class="badge" style="background-color: #fff700;">&nbsp;</span>';
                            } else {
                                $statusHtml .= '<span class="badge badge-secondary">&nbsp;</span>';
                            }
                            if (!empty($value->business_link) && $value->chat_option == 1) {
                                $isOutsideBusiness = true;
                                $outsideHtml .= "<a href='{$value->business_link}' target='_blank' ><img src='" . asset('img/booking.png') . "' /></a>";
                            } elseif ($value->chat_option != 2) {
                                $outsideHtml .= "<img src='" . asset('img/MeAround-chat.png') . "' />";
                            }

                            if (!empty($value->booking_link)) {
                                $outsideHtml .= "<a href='{$value->booking_link}' target='_blank' ><img src='" . asset('img/business-chat.png') . "' /></a>";
                            }

                            if (!empty($value->another_mobile)) {
                                $otherNumber .= "<br/><span class='copy_clipboard'>{$value->another_mobile}</span>";
                                $outsideHtml .= "<a href='tel:{$value->another_mobile}' ><img src='" . asset('img/call-shop.png') . "' /></a>";
                            }

                            $service_checked = "";
                            if ($value->is_regular_service) {
                                $service_checked = "checked";
                            }

                            $instaDate = Carbon::now()->addDays($value->count_days)->format('Y-m-d');
                            if ($value->count_days == 0 && $value->last_count_updated_at != NULL) {
                                $instaDate = Carbon::parse($value->last_count_updated_at)->format('Y-m-d');
                            }

                            $service = '<input id="regular_service" ' . $service_checked . ' type="checkbox" name="regular_service" value="1" class="form-check-input ml-0 " disabled /><label class="ml-4 pl-1 pt-2">Regular Service</label>';

                            $serviceData .= '<div class="update_service" id="' . $value->id . '" onclick="instagramServicePopup(`' . $value->id . '`);" ><div class="count_days">' . $value->count_days . '</div><div class="expiry_date">' . $instaDate . '</div><div class="service">' . $service . '</div></div>';
                        }
                    }

                    // dD($serviceData);

                    $outsideDisplayHtml = "<div class='d-flex align-items-center business-buttons' >$outsideHtml</div>";
                    if ($isOutsideBusiness == true) {
                        // $statusHtml .= '<i class="fas fa-star" style="font-size: 25px; color: #fff700; -webkit-text-stroke-width: 1px; -webkit-text-stroke-color: #43425d;"></i>';
                    }

                    $loginUser = Auth::user();
                    $editCoinButton = $loginUser->hasRole('Admin') ? "<a href='javascript:void(0)' role='button' onclick='editCredits(" . $user->id . ")' class='btn btn-primary btn-sm mx-1' data-toggle='tooltip' data-original-title='Edit'><i class='fa fa-edit' style='font-size: 15px;margin: 4px -5px 4px 0;'></i></a>" : "";
                    $referralHTML = $this->getReferralDetail($user, $user->referral_count);
                }
                else {
                    $userType = "User";
                    $viewButton = "";
                    $viewCommunityRoute = route('admin.user.show-community', [$user->id]);
                    $viewCommunity = "<a class='btn btn-primary' href='$viewCommunityRoute' ><img src='" . asset('img/community.svg') . "' /></a>";


                    $isRequested = RequestForm::leftjoin('category', 'category.id', 'request_forms.category_id')
                        ->leftjoin('cities', 'cities.id', 'request_forms.city_id')
                        ->whereIn('entity_type_id', [EntityTypes::HOSPITAL, EntityTypes::SHOP])
                        ->where('request_status_id', RequestFormStatus::PENDING)
                        ->where('user_id', $user->id)
                        ->select('request_forms.*')->count();

                    if ($isRequested > 0) {
                        $toBeShopButton = "<a role='button' href='javascript:void(0)' title='' data-original-title='Requested' class='btn btn-danger btn-sm ' data-toggle='tooltip' style='pointer-events: none; cursor: default;'>Requested </a>";
                    } else {
                        $toBeShopButton = "<a role='button' href='javascript:void(0)' onclick='viewUserToShop(" . $user->id . ")'  title='' data-original-title='User to be shop' class='btn btn-primary btn-sm ' data-toggle='tooltip'>User to be shop</a>";
                    }

//                    $editCoinButton = '';
                    $editCoinButton = $loginUser->hasRole('Admin') ? "<a href='javascript:void(0)' role='button' onclick='editCredits(" . $user->id . ")' class='btn btn-primary btn-sm mx-1' data-toggle='tooltip' data-original-title='Edit'><i class='fa fa-edit' style='font-size: 15px;margin: 4px -5px 4px 0;'></i></a>" : "";
                }

                $serviceData .= '</div>';
                $statusHtml .= '</div>';
                $deleteButton = "<a role='button' href='javascript:void(0)' onclick='deleteUser(" . $user->id . ")' title='' data-original-title='Delete Account' class='mx-1 btn btn-primary btn-sm' data-toggle='tooltip'>Delete</a>";
                if ($loginUser->hasRole('Sub Admin')) {
                    $editButton = "";
                }
                else {
                    $editButton = "<a role='button' href='javascript:void(0)' onclick='editPassword(" . $user->id . ")' title='' data-original-title='Edit Account' class='mx-1 btn btn-primary btn-sm mb-1' data-toggle='tooltip'>Edit Password</a>";
                }

                if ($loginUser->hasRole('Admin')) {
                    $editEmailButton = "<a role='button' href='javascript:void(0)' onclick='editEmail(`" . route('admin.user.get-edit-email', [$user->id]) . "`)' title='' data-original-title='Edit Account' class='mx-1 btn btn-primary btn-sm mb-1' data-toggle='tooltip'>Edit Email</a>";
                } else {
                    $editEmailButton = '';
                }

                if ($user->new_referral_count && $user->new_referral_count > 0) {
                    $new_referral_count_div = "<span class='list_unread_referral_count unread_referral_count'>{$user->new_referral_count}</span>";
                }

                $style = ($user->is_admin_access == 1) ? "color:deeppink" : '';
                $referral_code = "<p style='margin: 0'>Referral code: <span class='copy_code'>$user->recommended_code</span></p>";
                if($user->recommended_by!=null) {
                    $signup_code = UserDetail::where('user_id', $user->recommended_by)->pluck('recommended_code')->first();
                    $signup_via = "<p style='margin: 0'>Signup via: $signup_code</p>";
                }
                else {
                    $signup_via = '<div class="d-flex align-items-center">
                                    <input type="text" name="signup_code" id="signup_code" placeholder="Enter code">
                                    <input type="submit" class="btn btn-dark ml-1" value="Save" user-id="'.$user->id.'" id="btn_save_signup_code">
                            </div>';
                }
                $app_type = "";
                if ($user->app_type!="mearound"){
                    $app_type = ucfirst($user->app_type);
                }
                $data[$count]['name'] = "<div class='d-flex align-items-center'>
<p style='$style;margin: 0'>$user->name</p>
<a role='button' onclick='editUsername(`" . route('admin.user.get-edit-username', [$user->id]) . "`)' title='' data-original-title='Edit Username' class='mx-1 btn btn-primary btn-sm' data-toggle='tooltip'>Edit</a>
<p style='margin: 0'>$app_type</p>
</div>" .$referral_code.$signup_via. $referredByButton;
                $data[$count]['business_profile'] = "<div class='d-flex align-items-center'>$viewButton</div>";

                $shopCategory = DB::table('category')
                    ->leftjoin('category_settings', function ($join) {
                        $join->on('category_settings.category_id', '=', 'category.id')
                            ->where('category_settings.country_code', 'KR');
                    })
                    ->select('category.*')
                    ->whereIn('category.category_type_id', [CategoryTypes::SHOP])
                    ->whereNull('category.deleted_at')
                    ->orderBy('category_settings.order', 'ASC')
                    ->get();
                $category_dropdown = "";
                $latest_shop = Shop::where('user_id',$user->id)->orderBy('created_at','DESC')->first();
                if ($shopCategory) {
                    if (!empty($latest_shop)) {
                        $category_dropdown = '<select shop_id="' . $latest_shop->id . '" class="form-control w-50 mt-2" name="category_select" id="category_select">';
                        foreach ($shopCategory as $cat) {
                            $selected = ($latest_shop->category_id == $cat->id) ? 'selected' : '';
                            $category_dropdown .= '<option value="' . $cat->id . '" ' . $selected . '>' . $cat->name . '</option>';
                        }
                        $category_dropdown .= '</select>';
                    }
                }
                $data[$count]['email'] = $user->email.$category_dropdown;

                if (Auth::user()->hasRole('Sub Admin')) {
                    $data[$count]['phone'] = "";
                }
                else {
                    $data[$count]['phone'] = '<span class="copy_clipboard">' . $user->mobile . '</span>' . $otherNumber;
                }

                if (Auth::user()->hasRole('Sub Admin')) {
                    $data[$count]['location'] = "";
                }
                else {
                    $data[$count]['location'] = '<div class="d-flex align-items-center"><a role="button" href="javascript:void(0)" onclick="viewLocations(' . $user->id . ')" title="" data-original-title="View" class="btn btn-primary btn-sm " data-toggle="tooltip"><i class="fas fa-eye mt-1"></i></a></div>';
                }

                if (Auth::user()->hasRole('Sub Admin')) {
                    $data[$count]['service'] = "";
                }
                else {
                    $data[$count]['service'] = $serviceData;
                }

                $data[$count]['signup'] = $this->formatDateTimeCountryWise($user->date, $adminTimezone);

                $data[$count]['business_type'] = $userType . $inquiry_phone . $connect_instagram . "<p>$user->promot_insta_around</p>";

                $instagram_data = "";
                if (!empty($latest_shop)) {
                    $instagram_user_name = LinkedSocialProfile::where('user_id', $user->id)->where('shop_id', $latest_shop->id)->where('social_type', LinkedSocialProfile::Instagram)->pluck('social_name')->first();
                    if (empty($instagram_user_name)){
                        //connect instagram link btn
                        $shopConnect = ShopConnectLink::firstOrCreate([
                            'shop_id' => $latest_shop->id,
                            'is_expired' => 0
                        ]);
                        $shopConnectCopy = "$shopConnect->id|$latest_shop->id|$latest_shop->user_id|" . Carbon::parse($shopConnect->created_at)->timestamp;
                        $shopConnectCopyLink = route('social.profile.connect', ['code' => Crypt::encrypt($shopConnectCopy)]);
                        $instagram_data = '<a class="btn btn-primary btn-sm connect ml-3 mr-1 p-1 pl-2 pr-2 rounded" href="javascript:void(0);" onclick="copyTextLink(`'.$shopConnectCopyLink.'`)"> Instagram connect Link </a>';
                    }
                    else {
                        $instagram_data = "<div>$instagram_user_name</div>";
                    }
                }
                if ($user->linked_account_count > 0) {
                    $data[$count]['portfolio_count'] = $portfolioCount . "<div>Instagram</div>$instagram_data";
                } else {
                    $data[$count]['portfolio_count'] = $portfolioCount.$instagram_data;
                }
                $data[$count]['status'] = "$statusHtml $outsideDisplayHtml";
                $data[$count]['love_count'] = $user->love_count;
                $data[$count]['level'] = $user->level;

                $data[$count]['referral'] = "<div class='position-absolute'>{$referralHTML}{$new_referral_count_div}</div>";
                $data[$count]['last_access'] = $this->formatDateTimeCountryWise($user->last_access, $adminTimezone);

                if ($loginUser->hasRole('Sub Admin')){
                    $editPhoneNumber = "";
                }
                else {
                    $editPhoneNumber = '<a role="button" href="javascript:void(0)" onclick="editPhone(`' . route('admin.user.get-edit-phone', [$user->id]) . '`)" title="" data-original-title="Edit Phone Number" class="mx-1 btn btn-primary btn-sm" data-toggle="tooltip">Edit Phone Number</a>';
                }
                $data[$count]['actions'] = "<div class='button-container'>
                                            <div class='vertical-buttons'>$editButton $editEmailButton $editPhoneNumber</div>
                                            <div class='d-flex'>$toBeShopButton $deleteButton $editCoinButton $viewCommunity $viewCards</div>
                                            </div>";
//                $data[$count]['actions'] = '<div class="button-container">
//                    <div class="vertical-buttons">
//                        <button class="btn btn-primary button">Button 1</button>
//                        <button class="btn btn-primary button">Button 2</button>
//                    </div>
//                    <div class="d-flex">
//                        <button class="btn btn-primary button">Button 3</button>
//                        <button class="btn btn-primary button">Button 4</button>
//                        <button class="btn btn-primary button">Button 5</button>
//                    </div>
//                </div>';
                $count++;
            }


            if ($filter == 'referred-user') {
                // Reset count
                DB::table('users_detail')->where('is_referral_read', 1)->update(['is_referral_read' => 0]);
            }

            $jsonData = array(
                "draw" => intval($draw),
                "recordsTotal" => intval($totalData),
                "recordsFiltered" => intval($totalFiltered),
                "data" => $data
            );
            return response()->json($jsonData);
        /*} catch (Exception $ex) {
            Log::info('Exception all hospital list');
            Log::info($ex);
            $jsonData = array(
                "draw" => intval($draw),
                "recordsTotal" => 0,
                "recordsFiltered" => 0,
                "data" => []
            );
            return response()->json($jsonData);
        }*/
    }

    public function getReferralDetail($user, $referralCount)
    {
        //$referralCount = UserDetail::where('recommended_by',$user_id)->count();
        //  if($referralCount < 1 && empty($user->recommended_by)) return '-';
        return "<a role='button' href='javascript:void(0);' onClick='showReferralDetail($user->id)' title='' data-original-title='View' class='btn btn-primary btn-sm' data-toggle='tooltip'>$referralCount <i class='fas fa-eye'></i></a>";
    }

    public function showRefferalUser($id)
    {
        $adminTimezone = $this->getAdminUserTimezone();
        $user_shops = DB::table('shops')->whereNull('deleted_at')->where('user_id', $id)->get();
        $referralUser = User::join('users_detail', 'users_detail.user_id', 'users.id')->select('users_detail.*', 'users_detail.name as display_name', 'users.email')->where('users.id', $id)->first();
        $users = UserDetail::join('users', 'users.id', 'users_detail.user_id')->select('users.*', 'users_detail.name', 'users_detail.mobile')->where('users_detail.recommended_by', $id)->withTrashed()->get();
        $processed_coffee = UserReferralDetail::where('user_id', $id)->where('is_sent', 1)->count();
        $cnt_referral = UserReferral::where('referred_by', $id)->count();
        $cnt_not_sent = UserReferralDetail::where('user_id', $id)->where('is_sent', 0)->count();
        $gifticons = GifticonDetail::with('attachments')->where('user_id', $id)->get();

        return view('admin.users.show-referral-popup', compact('users', 'referralUser', 'adminTimezone', 'user_shops', 'processed_coffee', 'cnt_referral', 'cnt_not_sent', 'id', 'gifticons'));
    }

    public function getUserToShopView($id)
    {

        $category = Category::where('category_type_id', CategoryTypes::SHOP)->get();
        $category = collect($category)->mapWithKeys(function ($value) {
            return [$value->id => $value->name];
        })->toArray();

        $suggestCategory = Category::where('category_type_id', CategoryTypes::CUSTOM)->get();
        $suggestCategory = collect($suggestCategory)->mapWithKeys(function ($value) {
            return [$value->id => $value->name];
        })->toArray();

        $category = ['Category' => $category, 'Suggest Category' => $suggestCategory];

        $hospitalCategory = Category::where('category_type_id', CategoryTypes::HOSPITAL)->get();
        $hospitalCategory = collect($hospitalCategory)->mapWithKeys(function ($value) {
            return [$value->id => $value->name];
        })->toArray();

        return view('admin.users.tobeshop', compact('id', 'category', 'suggestCategory', 'hospitalCategory'));
    }

    public function updateUserShopCategory(Request $request)
    {
        $inputs = $request->all();
        $category = $inputs['category'] ?? NULL;
        $shop_id = $inputs['shop_id'] ?? NULL;

        if ($shop_id && $category) {
            Shop::whereId($shop_id)->update(['category_id' => $category]);
            $jsonData = array(
                'success' => true,
            );
        } else {
            $jsonData = array(
                'success' => false,
            );
        }

        return response()->json($jsonData);
    }

    public function updateUserToShop(Request $request)
    {
        try {
            DB::beginTransaction();
            Log::info('Start code for the add currency');
            $inputs = $request->all();

            $id = $inputs['id'];
            $user = User::find($id);
            $shop_name = $inputs['shop_name'] ?? NULL;
            $category = $inputs['category'] ?? NULL;
            $hospital_category = $inputs['hospital_category'] ?? NULL;
            $recommend_code = !empty($inputs['supporter_code']) ? $inputs['supporter_code'] : NULL;
            $manager = Manager::where('recommended_code', $recommend_code)->first();
            $manager_id = $manager ? $manager->id : 0;
            $request_status_id = $manager ? RequestFormStatus::CONFIRM : RequestFormStatus::PENDING;

            $user_type = $index['user_type'] ?? 'shop';
            $validator = Validator::make($request->all(), [
                'shop_name' => 'required',
                'category' => 'required_if:user_type,shop',
                'hospital_category' => 'required_if:user_type,hospital'
            ], [], [
                'shop_name' => 'Shop Name',
                'category' => 'Category',
                'hospital_category' => 'Category',
            ]);

            if ($validator->fails()) {
                return response()->json(array(
                    'response' => false,
                    'message' => implode('<br/>', $validator->errors()->all())
                ));
            }
            if (!empty($recommend_code) && empty($manager)) {

                return response()->json(array(
                    'response' => false,
                    'message' => trans("messages.language_4.supported_code_expired")
                ));
            }


            $dt = Carbon::now();

            $requestData = [
                'user_id' => $id,
                'entity_type_id' => ($user_type == 'hospital') ? EntityTypes::HOSPITAL : EntityTypes::SHOP,
                'category_id' => ($user_type == 'hospital') ? $hospital_category : $category,
                'name' => $shop_name,
                'address' => NULL,
                'address2' => NULL,
                'country_id' => NULL,
                'city_id' => NULL,
                'latitude' => NULL,
                'longitude' => NULL,
                'main_country' => NULL,
                'business_license_number' => '',
                'request_status_id' => $request_status_id,
                'email' => '',
                'request_count' => DB::raw('request_count + 1'),
                'manager_id' => $manager_id,
            ];

            if ($manager) {
                $this->approveRequest($requestData, $user, $user_type);
            }

            $request_form = RequestForm::create($requestData);

            /*
            $shop = Shop::create([
                'email' => NULL,
                'mobile' => NULL,
                'shop_name' => $shop_name,
                'best_portfolio' => NULL,
                'business_licence' => NULL,
                'identification_card' => NULL,
                'business_license_number' => NULL,
                'status_id' => Status::ACTIVE,
                'category_id' => $category,
                'user_id' => $id,
                'manager_id' => 0,
                'credit_deduct_date' => $dt->toDateString()
            ]);
            $entity_id = $shop->id;
            $config = Config::where('key', Config::BECAME_SHOP)->first();

            UserEntityRelation::create(['user_id' => $id,"entity_type_id" => EntityTypes::SHOP,'entity_id' => $shop->id]);

            $defaultCredit = $config ? (int) filter_var($config->value, FILTER_SANITIZE_NUMBER_INT) : 150000;
            $credit = UserCredit::updateOrCreate([
                'user_id' => $id,
                'credits' => DB::raw("credits + $defaultCredit")
            ]);

            $creditHistory = UserCreditHistory::create([
                'user_id' => $id,
                'amount' => $defaultCredit,
                'total_amount' => $defaultCredit,
                'transaction' => 'credit',
                'type' => UserCreditHistory::DEFAULT
            ]);

            $plan = PackagePlan::BRONZE;
            $userDetail = UserDetail::where('user_id',$id)->update(['package_plan_id' => $plan,'manager_id' => $user->id,'plan_expire_date' => Carbon::now()->addDays(30)]);
            */

            $config = Config::where('key', Config::REQUEST_CLIENT_REPORT_SNS_REWARD_EMAIL)->first();
            if ($config) {
                $userData = [];
                $userData['email_body'] = "<p><b>Business Name: </b>" . $request_form->name . "</p>";
                $userData['email_body'] .= "<p><b>Type of Business: </b>" . $request_form->category_name . "</p>";
                /*
                $userData['email_body'] .= "<p><b>Address: </b>".$request_form->address."</p>";
                $userData['email_body'] .= "<p><b>City: </b>".$request_form->city_name."</p>";
                $userData['email_body'] .= "<p><b>Phone Number: </b>".$request_form->mobile."</p>";
                $userData['email_body'] .= "<p><b>Email: </b>".$request_form->email."</p>";
                $userData['email_body'] .= "<p><b>Business Licence Number: </b>".$request_form->business_license_number."</p>";
                */
                $userData['title'] = 'Requested Client';
                $userData['subject'] = 'Requested Client';
                $userData['username'] = 'Admin';
                if ($config->value) {
                    Mail::to($config->value)->send(new CommonMail($userData));
                }
            }

            DB::commit();
            return response()->json(array(
                'response' => true,
                'message' => trans("messages.profile.update")

            ), 200);
        } catch (Exception $e) {
            Log::info($e);
            return response()->json(array(
                'response' => false,
                'message' => trans("messages.save-error")

            ), 400);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return Response
     */
    public function approveRequest($data, $user, $type = 'shop')
    {
        $dt = Carbon::now();
        $category = Category::find($data['category_id']);

        $plan = $category && $category->category_type_id == CategoryTypes::CUSTOM ? PackagePlan::PLATINIUM : PackagePlan::BRONZE;
        $userDetail = UserDetail::where('user_id', $user->id)->update(['package_plan_id' => $plan, 'manager_id' => $data['manager_id'], 'plan_expire_date' => Carbon::now()->addDays(30)]);
        $userLangDetail = UserDetail::where('user_id', $user->id)->first();
        if ($type == 'hospital') {
            $hospital = Hospital::create([
                'email' => $data['email'] ?? NULL,
                'mobile' => $user->mobile,
                'main_name' => $data['name'] ?? NULL,
                'business_licence' => $data['business_licence'] ?? NULL,
                'interior_photo' => $data['interior_photo'] ?? NULL,
                'business_license_number' => $data['business_license_number'] ?? NULL,
                'status_id' => Status::ACTIVE,
                'category_id' => $data['category_id'] ?? null,
                'manager_id' => $data['manager_id'] ?? NULL,
                'credit_deduct_date' => $dt->toDateString()
            ]);
            $entity_id = $hospital->id;
            $config = Config::where('key', Config::BECAME_HOSPITAL)->first();
        } else {
            $shop = Shop::create([
                'email' => $data['email'] ?? NULL,
                'mobile' => $user->mobile,
                'shop_name' => $data['name'] ?? NULL,
                'best_portfolio' => $data['best_portfolio'] ?? NULL,
                'business_licence' => $data['business_licence'] ?? NULL,
                'identification_card' => $data['identification_card'] ?? NULL,
                'business_license_number' => $data['business_license_number'] ?? NULL,
                'status_id' => Status::ACTIVE,
                'category_id' => $data['category_id'] ?? NULL,
                'user_id' => $data['user_id'],
                'manager_id' => $data['manager_id'] ?? NULL,
                'uuid' => (string) Str::uuid(),
                'credit_deduct_date' => $dt->toDateString()
            ]);
            $entity_id = $shop->id;
            $config = Config::where('key', Config::BECAME_SHOP)->first();
            syncGlobalPriceSettings($entity_id, $userLangDetail->language_id ?? 4);
        }

        $this->updateUserChatStatus();

        UserEntityRelation::create([
            'user_id' => $data['user_id'],
            'entity_type_id' => $data['entity_type_id'],
            'entity_id' => $entity_id,
        ]);

        $city = City::where('id', $data['city_id'])->first();
        if ($city) {
            $address = Address::create([
                'entity_type_id' => $data['entity_type_id'],
                'entity_id' => $entity_id,
                'address' => $data['address'],
                'latitude' => $data['latitude'],
                'longitude' => $data['longitude'],
                'country_id' => $data['country_id'],
                'main_country' => $data['main_country'],
                'state_id' => $city->state_id,
                'city_id' => $data['city_id'],
                'main_address' => Status::ACTIVE,
            ]);
        }

        $defaultCredit = $config ? (int)filter_var($config->value, FILTER_SANITIZE_NUMBER_INT) : 150000;
        $credit = UserCredit::updateOrCreate([
            'user_id' => $data['user_id'],
            'credits' => DB::raw("credits + $defaultCredit")
        ]);

        $creditHistory = UserCreditHistory::create([
            'user_id' => $data['user_id'],
            'amount' => $defaultCredit,
            'total_amount' => $defaultCredit,
            'transaction' => 'credit',
            'type' => UserCreditHistory::DEFAULT
        ]);

        $notice = Notice::create([
            'notify_type' => Notice::BECAME_BUSINESS_USER,
            'user_id' => $data['user_id'],
            'to_user_id' => $data['user_id'],
            'entity_type_id' => $data['entity_type_id'],
            'entity_id' => $entity_id,
            'is_aninomity' => 0
        ]);

        $supporterNotice = Notice::create([
            'notify_type' => Notice::ADDED_AS_CLIENT,
            'user_id' => $data['user_id'],
            'to_user_id' => $data['manager_id'],
            'entity_type_id' => $data['entity_type_id'],
            'entity_id' => $entity_id,
            'is_aninomity' => 0
        ]);

        // Send push notification to supporter
        $devices = UserDevices::where('user_id', $data['manager_id'])->pluck('device_token')->toArray();
        $language_id = 4;
        $key = Notice::ADDED_AS_CLIENT . '_' . $language_id;
        $format = __("notice.$key", ['username' => $supporterNotice->user_name]);

        $title_msg = '';
        $notify_type = Notice::ADDED_AS_CLIENT;
        $notificationData = $data;

        if (count($devices) > 0) {
            $result = $this->sentPushNotification($devices, $title_msg, $format, $notificationData, $notify_type, $entity_id);
        }

        if ($data['entity_type_id'] == EntityTypes::SHOP && $category && $category->category_type_id != CategoryTypes::CUSTOM) {
            $config1 = Config::where('key', Config::SHOP_PROFILE_ADD_PRICE)->first();
            $defaultCredit1 = $config1 ? (int)filter_var($config1->value, FILTER_SANITIZE_NUMBER_INT) : 0;
            $userCredits = UserCredit::where('user_id', $data['user_id'])->first();
            $old_credit = $userCredits->credits;
            $total_credit = $old_credit - $defaultCredit1;
            if ($defaultCredit1 && $defaultCredit1 > 0) {
                $userCredits = UserCredit::where('user_id', $data['user_id'])->update(['credits' => $total_credit]);
                UserCreditHistory::create([
                    'user_id' => $data['user_id'],
                    'amount' => $defaultCredit1,
                    'total_amount' => $total_credit,
                    'transaction' => 'debit',
                    'type' => UserCreditHistory::REGULAR
                ]);
            }
        }
    }

    public function getUserForm(Request $request)
    {
        $inputs = $request->all();

        $index = $inputs['index'];
        return view('admin.users.user-form', compact('index'));
    }

    public function getEditAccount($id)
    {
        return view('admin.users.edit-account', compact('id'));
    }

    public function getEditEmail($id)
    {
        $userdata = DB::table('users')->whereId($id)->first();
        return view('admin.users.change-email-popup', compact('id', 'userdata'));
    }

    public function getEditPhone($id)
    {
        $userdata = DB::table('users_detail')->where('user_id',$id)->whereNull('deleted_at')->first();
        return view('admin.users.change-phone-popup', compact('id', 'userdata'));
    }

    public function getEditUsername($id)
    {
        $user_detail = DB::table('users_detail')->where('user_id',$id)->whereNull('deleted_at')->first();
        $user = DB::table('users')->where('id',$id)->whereNull('deleted_at')->first();
        $shop_profiles = Shop::where('user_id',$id)->where(function($q){
                        $q->orWhereNotNull('shop_name')
                            ->orWhereNotNull('main_name');
                    })
                    ->get();
        return view('admin.users.change-username-popup', compact('id', 'user_detail', 'user', 'shop_profiles'));
    }

    public function getAccount($id)
    {
        return view('admin.users.delete-account', compact('id'));
    }

    public function editEmailAddress(Request $request, $id)
    {

        $inputs = $request->all();
        $validator = Validator::make($inputs, [
            //'email' => 'required|max:255|unique:users,email,NULL,id,deleted_at,NULL',
            'email' => 'required|max:255|unique:users,email,' . $id
        ], [
            'email.unique' => 'This Email is already been taken.',
        ]);

        if ($validator->fails()) {
            return response()->json(array(
                'success' => false,
                'errors' => $validator->getMessageBag()->toArray()
            ), 400);
        }

        try {
            $email = $request->email;
            if (!empty($email)) {
                $user = User::where('id', $id)->update([
                    'email' => $email,
                ]);
            }
            return response()->json(array(
                'success' => true,
                'message' => "Email successfully updated."
            ), 200);
        } catch (Exception $ex) {
            Log::info($ex);
            return response()->json(array(
                'success' => false,
                'message' => "Unable to update Email"
            ), 400);
        }
    }

    public function editPhone(Request $request, $id)
    {
        $inputs = $request->all();
        $validator = Validator::make($inputs, [
            'phone' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(array(
                'success' => false,
                'errors' => $validator->getMessageBag()->toArray()
            ), 400);
        }

        DB::beginTransaction();
        try {
            $phone = $request->phone;
            if (!empty($phone)) {
                $user = UserDetail::where('user_id', $id)->update([
                    'mobile' => $phone,
                ]);
            }

            DB::commit();
            notify()->success("Phone number successfully updated.", "Success", "topRight");
            return redirect()->route('admin.user.index');
        } catch (Exception $ex) {
            DB::rollBack();
            Log::info($ex);
            notify()->error("Unable to update phone number", "Error", "topRight");
            return redirect()->route('admin.user.index');
        }
    }

    public function editUsername(Request $request, $id)
    {
        $inputs = $request->all();
        $validator = Validator::make($inputs, [
            'username' => 'required'
        ], [
            'username.required' => 'Please enter user name.',
        ]);

        if ($validator->fails()) {
            return response()->json(array(
                'success' => false,
                'errors' => $validator->getMessageBag()->toArray()
            ), 400);
        }

        DB::beginTransaction();
        try {
            UserDetail::where('user_id', $id)->update([
                'name' => $request->username,
                'gender' => $request->gender,
                'mbti' => $request->mbti,
            ]);

            User::where('id',$id)->update([
                'is_show_gender' => $request->is_show_gender,
                'is_show_mbti' => $request->is_show_mbti,
            ]);

            if (isset($request->is_show_shop)) {
                foreach ($request->is_show_shop as $key => $value) {
                    Shop::where('id', $key)->update([
                        'is_show' => $value
                    ]);
                }
            }

            DB::commit();
            return response()->json(array(
                'success' => true,
                'message' => "User successfully updated."
            ), 200);
        } catch (Exception $ex) {
            DB::rollBack();
            Log::info($ex);
            return response()->json(array(
                'success' => false,
                'message' => "Unable to update user"
            ), 400);
        }
    }

    public function editAccount(Request $request, $id)
    {
        try {
            Log::info('Start edit user ');

            $password = $request->password;

            if (!empty($password)) {

                $user = User::where('id', $id)->update([
                    'password' => Hash::make($password),
                ]);

                notify()->success("Password successfully updated.", "Success", "topRight");
            } else {
                notify()->error("Unable to Edit Password", "Error", "topRight");
            }


            return redirect()->route('admin.user.index');
        } catch (Exception $ex) {
            DB::rollBack();
            Log::info('Exception in edit user');
            Log::info($ex);
            notify()->error("Unable to Edit Password", "Error", "topRight");
            return redirect()->route('admin.user.index');
        }
    }

    public function deleteAccount(Request $request, $id)
    {
        try {
            Log::info('Start delete user ');
            DB::beginTransaction();
            $userId = $id;

            $userRelation = UserEntityRelation::where('user_id', $userId)->get();
            foreach ($userRelation as $ur) {
                if ($ur->entity_type_id == EntityTypes::SHOP) {
                    Shop::where('id', $ur->entity_id)->delete();
                } else if ($ur->entity_type_id == EntityTypes::HOSPITAL) {
                    Hospital::where('id', $ur->entity_id)->delete();
                }
            }
            Community::where('user_id', $userId)->delete();
            UserDetail::where('user_id', $userId)->delete();
            Notice::where('user_id', $userId)->delete();
            $userRelation = UserEntityRelation::where('user_id', $userId)->delete();
            $deleteReport = ReportClient::where('reported_user_id', $userId)->delete();
            User::where('id', $userId)->delete();

            DB::commit();
            Log::info('End delete user.');
            notify()->success("User Account deleted successfully.", "Success", "topRight");
            return redirect()->route('admin.user.index');
        } catch (Exception $ex) {
            DB::rollBack();
            Log::info('Exception in delete user');
            Log::info($ex);
            notify()->error("Unable to delete user", "Error", "topRight");
            return redirect()->route('admin.user.index');
        }
    }

    public function createUser()
    {
        $title = "Add User";
        return view('admin.users.multi-form', compact('title'));
    }

    public function storeUserMultiple(Request $request)
    {
        $inputs = $request->all();


        $validator = Validator::make($inputs, [
            'details' => 'required',
            'details.*.username' => 'required',
            'details.*.email' => 'required|max:255',
            'details.*.phone_number' => 'required|numeric',
            'details.*.password' => 'required|min:6|confirmed',
            'details.*.password_confirmation' => 'required|min:6'
        ], [
            'details.*.username.required' => 'This Field is required.',
            'details.*.email.required' => 'This Field is required.',
            'details.*.email.unique' => 'This Email is already been taken.',
            'details.*.phone_number.required' => 'This Field is required.',
            'details.*.phone_number.numeric' => 'This Field must be a number.',
            'details.*.password.required' => 'This Field is required.',
            'details.*.password.min' => 'This Field must be at least 6 characters.',
            'details.*.password.confirmed' => 'The Password does not match.',
            'details.*.password_confirmation.min' => 'This Field must be at least 6 characters.',
            'details.*.password_confirmation.required' => 'This Field is required.',
        ]);

        if ($validator->fails()) {
            return response()->json(array(
                'success' => false,
                'errors' => $validator->getMessageBag()->toArray()
            ), 400);
        }

        $details = $inputs['details'];

        try {
            DB::beginTransaction();
            foreach ($details as $data) {
                $is_exist_user = User::where('email', $data['email'])->whereIn('app_type',['mearound','tattoocity','spa'])->count();
                if ($is_exist_user > 0){
                    return response()->json(array('success' => false,'message' => $data['email']." already exist."));
                }

                $user = User::create([
                    "email" => $data['email'],
                    'username' => $data['username'],
                    "password" => Hash::make($data['password']),
                    'status_id' => Status::ACTIVE,
                ]);

                UserEntityRelation::create(['user_id' => $user->id, "entity_type_id" => EntityTypes::NORMALUSER, 'entity_id' => $user->id]);
                $random_code = mt_rand(1000000, 9999999);
                $member = UserDetail::create([
                    'user_id' => $user->id,
                    'country_id' => NULL,
                    'name' => trim($data['username']),
                    'email' => $data['email'],
                    'phone_code' => $data['phone_code'] ?? NULL,
                    'mobile' => $data['phone_number'],
                    'gender' => $data['gender'] ?? NULL,
                    'device_type_id' => NULL,
                    'device_id' => NULL,
                    'device_token' => NULL,
                    'recommended_code' => $random_code,
                    'recommended_by' => NULL,
                    'points_updated_on' => Carbon::now(),
                    'points' => UserDetail::POINTS_40,
                    // 'manager_id' => 0,
                ]);

                // Assign Default if not Assigned Card Start
                $getDefaultCard = DefaultCards::select('dcr.default_card_id', 'dcr.id as riv_id', 'dcr.background_rive', 'dcr.character_rive')->leftJoin('default_cards_rives as dcr', 'default_card_id', 'default_cards.id')->where('default_cards.name', DefaultCards::DEFAULT_CARD)->first();

                $default_cards_id = (!empty($getDefaultCard)) ? $getDefaultCard->default_card_id : 0;
                $default_cards_riv_id = (!empty($getDefaultCard)) ? $getDefaultCard->riv_id : 0;
                $background_rive = (!empty($getDefaultCard)) ? $getDefaultCard->background_rive : NULL;
                $character_rive = (!empty($getDefaultCard)) ? $getDefaultCard->character_rive : NULL;

                $cardWhere = [
                    'user_id' => $user->id,
                    'default_cards_id' => $default_cards_id,
                    'default_cards_riv_id' => $default_cards_riv_id,
                ];

                $assignDefualtCard = UserCards::updateOrCreate($cardWhere);

                $cardLevelData = CardLevelDetail::where('main_card_id', $default_cards_riv_id)->get();
                if (!empty($cardLevelData->toArray())) {
                    foreach ($cardLevelData as $card) {
                        UserCardLevel::updateOrCreate([
                            'user_card_id' => $assignDefualtCard->id,
                            'card_level' => $card->card_level,
                        ]);
                    }
                } else {
                    $other_level = CardLevel::where('id', '!=', CardLevel::DEFAULT_LEVEL)->get();
                    foreach ($other_level as $level) {
                        UserCardLevel::updateOrCreate([
                            'user_card_id' => $assignDefualtCard->id,
                            'card_level' => $level->id,
                        ]);
                    }
                }
                // Assign Default Card End
            }
            DB::commit();
            $jsonData = array(
                'success' => true,
                'message' => "User " . trans("messages.insert-success"),
                'redirect' => route('admin.user.index')
            );
        } catch (Exception $e) {
            Log::info($e);
            $jsonData = array(
                'success' => false,
                'message' => "User " . trans("messages.insert-error"),
                'redirect' => route('admin.user.index')
            );
        }
        return response()->json($jsonData);
    }

    public function storeUserMultipleBusiness(Request $request)
    {
        $inputs = $request->all();


        $validator = Validator::make($inputs, [
            'details' => 'required',
            'details.*.username' => 'required',
            'details.*.email' => 'required|max:255',
            'details.*.phone_number' => 'required|numeric',
            'details.*.password' => 'required|min:6|confirmed',
            'details.*.password_confirmation' => 'required|min:6'
        ], [
            'details.*.username.required' => 'This Field is required.',
            'details.*.email.required' => 'This Field is required.',
            'details.*.email.unique' => 'This Email is already been taken.',
            'details.*.phone_number.required' => 'This Field is required.',
            'details.*.phone_number.numeric' => 'This Field must be a number.',
            'details.*.password.required' => 'This Field is required.',
            'details.*.password.min' => 'This Field must be at least 6 characters.',
            'details.*.password.confirmed' => 'The Password does not match.',
            'details.*.password_confirmation.min' => 'This Field must be at least 6 characters.',
            'details.*.password_confirmation.required' => 'This Field is required.',
        ]);

        if ($validator->fails()) {
            return response()->json(array(
                'success' => false,
                'errors' => $validator->getMessageBag()->toArray()
            ), 400);
        }

        $details = $inputs['details'];
        $type = $inputs['type'];

        try {
            DB::beginTransaction();
            foreach ($details as $data) {
                $is_exist_user = User::where('email', $data['email'])->whereIn('app_type',['mearound','tattoocity','spa'])->count();
                if ($is_exist_user > 0){
                    return response()->json(array('success' => false,'message' => $data['email']." already exist."));
                }

                $user = User::create([
                    "email" => $data['email'],
                    'username' => $data['username'],
                    "password" => Hash::make($data['password']),
                    'status_id' => Status::ACTIVE,
                ]);

                UserEntityRelation::create(['user_id' => $user->id, "entity_type_id" => EntityTypes::NORMALUSER, 'entity_id' => $user->id]);
                $random_code = mt_rand(1000000, 9999999);
                $member = UserDetail::create([
                    'user_id' => $user->id,
                    'country_id' => NULL,
                    'package_plan_id' => PackagePlan::BRONZE,
                    'plan_expire_date' => Carbon::now()->addDays(30),
                    'name' => trim($data['username']),
                    'email' => $data['email'],
                    'phone_code' => $data['phone_code'] ?? NULL,
                    'mobile' => $data['phone_number'],
                    'gender' => $data['gender'] ?? NULL,
                    'device_type_id' => NULL,
                    'device_id' => NULL,
                    'device_token' => NULL,
                    'recommended_code' => $random_code,
                    'recommended_by' => NULL,
                    'points_updated_on' => Carbon::now(),
                    'points' => UserDetail::POINTS_40,
                    // 'manager_id' => 0,
                ]);

                if ($type == EntityTypes::HOSPITAL) {
                    $category = Category::where('category_type_id', CategoryTypes::HOSPITAL)->first();
                    $hospital = Hospital::create([
                        'email' => $data['email'] ?? NULL,
                        'mobile' => $data['phone_number'],
                        'main_name' => NULL,
                        'business_licence' => NULL,
                        'interior_photo' => NULL,
                        'business_license_number' => '',
                        'status_id' => Status::PENDING,
                        'category_id' => $category->id,
                        'manager_id' => '',
                        'credit_deduct_date' => Carbon::now()->toDateString()
                    ]);
                    $entity_id = $hospital->id;
                    $config = Config::where('key', Config::BECAME_HOSPITAL)->first();
                    $redirectURL = route('admin.business-client.hospital.show', ['id' => $entity_id]);
                } else {
                    $category = Category::where('category_type_id', CategoryTypes::SHOP)->first();
                    $shop = Shop::create([
                        'email' => $data['email'] ?? NULL,
                        'mobile' => $data['phone_number'],
                        'shop_name' => NULL,
                        'best_portfolio' => NULL,
                        'business_licence' => NULL,
                        'identification_card' => NULL,
                        'business_license_number' => '',
                        'status_id' => Status::PENDING,
                        'category_id' => $category->id,
                        'user_id' => $user->id,
                        'manager_id' => '',
                        'uuid' => (string) Str::uuid(),
                        'credit_deduct_date' => Carbon::now()->toDateString()
                    ]);
                    $entity_id = $shop->id;
                    syncGlobalPriceSettings($entity_id);
                    $config = Config::where('key', Config::BECAME_SHOP)->first();
                    $redirectURL = route('admin.business-client.shop.show', ['id' => $entity_id]);
                }
                UserEntityRelation::create([
                    'user_id' => $user->id,
                    'entity_type_id' => $type,
                    'entity_id' => $entity_id,
                ]);

                $defaultCredit = $config ? (int) filter_var($config->value, FILTER_SANITIZE_NUMBER_INT) : 150000;
                $credit = UserCredit::updateOrCreate([
                    'user_id' => $user->id,
                    'credits' => DB::raw("credits + $defaultCredit")
                ]);
            }
            DB::commit();

            if (count($details) > 1) {
                $redirectURL = route('admin.user.index');
            }

            $jsonData = array(
                'success' => true,
                'message' => "User " . trans("messages.insert-success"),
                'redirect' => $redirectURL
            );
        } catch (Exception $e) {
            Log::info($e);
            $jsonData = array(
                'success' => false,
                'message' => "User " . trans("messages.insert-error"),
                'redirect' => route('admin.user.index')
            );
        }
        return response()->json($jsonData);
    }
    public function storeUser(Request $request)
    {
        try {
            DB::beginTransaction();
            Log::info('Start code for the add currency');
            $inputs = $request->all();

            // print_r($inputs);die;

            $validator = Validator::make($inputs, [
                'username' => 'required',
                'email' => 'required|string|max:255|unique:users,email,NULL,id,deleted_at,NULL',
                'phone_number' => 'required|numeric',
                'password' => 'required| string|min:6|confirmed',
                'password_confirmation' => 'required|string|min:6'
            ], [
                'username.required' => 'The User Name is required.',
                'email.required' => 'The Email is required.',
                'email.unique' => 'This Email is already been taken.',
                'phone_number.required' => 'The Phone Number is required.',
                'password.required' => 'The password is required.',
            ]);

            //print_r($validator->getMessage())
            if ($validator->fails()) {
                //notify()->error("Validation Error", "Error", "topRight");
                return redirect()->back()->withErrors($validator)->withInput();
            }

            $user = User::create([
                "email" => $inputs['email'],
                'username' => $inputs['username'],
                "password" => Hash::make($inputs['password']),
                'status_id' => Status::ACTIVE,
            ]);

            UserEntityRelation::create(['user_id' => $user->id, "entity_type_id" => EntityTypes::NORMALUSER, 'entity_id' => $user->id]);
            $random_code = mt_rand(1000000, 9999999);
            $member = UserDetail::create([
                'user_id' => $user->id,
                'country_id' => NULL,
                'name' => trim($inputs['username']),
                'email' => $inputs['email'],
                'phone_code' => $inputs['phone_code'] ?? NULL,
                'mobile' => $inputs['phone_number'],
                'gender' => $inputs['gender'] ?? NULL,
                'device_type_id' => NULL,
                'device_id' => NULL,
                'device_token' => NULL,
                'recommended_code' => $random_code,
                'recommended_by' => NULL,
                'points_updated_on' => Carbon::now(),
                'points' => UserDetail::POINTS_40,
                // 'manager_id' => 0,
            ]);

            DB::commit();
            Log::info('End the code for the add currency');
            notify()->success("User " . trans("messages.insert-success"), "Success", "topRight");
            return redirect()->route('admin.user.index');
        } catch (Exception $e) {
            Log::info('Exception in the add currency');
            Log::info($e);
            notify()->error("User " . trans("messages.insert-error"), "Error", "topRight");
            return redirect()->route('admin.user.index');
        }
    }

    public function showCommunity(Request $request, $id)
    {
        $title = "User Community";
        $userDetails = DB::table('users_detail')->where('user_id', $id)->first();

        return view('admin.users.index-community', compact('title', 'id', 'userDetails'));
    }

    public function getCommunityJsonAllData(Request $request, $id)
    {
        $columns = array(
            0 => 'title',
            7 => 'created_at',
        );

        $limit = $request->input('length');
        $start = $request->input('start');
        $search = $request->input('search.value');
        $draw = $request->input('draw');
        $order = $columns[$request->input('order.0.column')];
        $dir = $request->input('order.0.dir');

        $viewButton = '';
        try {
            $data = [];
            $userIDs = [];

            $associationCommunity = AssociationCommunity::where('user_id', $id)
                ->select(
                    'id',
                    'user_id',
                    'associations_id',
                    'category_id',
                    'title',
                    'description',
                    'views_count',
                    'created_at'
                )
                ->addSelect(DB::raw("'associations' as community_type"));
            if (!empty($search)) {
                $communityQuery = $associationCommunity->where(function ($q) use ($search) {
                    $q->where('title', 'LIKE', "%{$search}%")
                        ->orWhere('description', 'LIKE', "%{$search}%");
                });
            }

            $communityQuery = Community::where('user_id', $id)
                ->select(
                    'id',
                    'user_id',
                    'category_id as associations_id',
                    'category_id',
                    'title',
                    'description',
                    'views_count',
                    'created_at'
                )
                ->addSelect(DB::raw("'category' as community_type"));


            if (!empty($search)) {
                $communityQuery = $communityQuery->where(function ($q) use ($search) {
                    $q->where('title', 'LIKE', "%{$search}%")
                        ->orWhere('description', 'LIKE', "%{$search}%");
                });
            }


            $communityQuery = $communityQuery->union($associationCommunity);

            $totalData = count($communityQuery->get());
            $totalFiltered = $totalData;

            $communityData = $communityQuery->offset($start)
                ->limit($limit)
                ->orderBy($order, $dir)
                ->get();


            $count = 0;
            foreach ($communityData as $community) {

                $linkButton = route('admin.user.community.edit', ['id' => $community->user_id, 'community_id' => $community->id, 'type' => $community->community_type]);

                $viewButton = "<a role='button' href='$linkButton'  data-original-title='View' class='btn btn-primary btn-sm ' data-toggle='tooltip'><i class='far fa-edit pb-1 pt-1'></i></a>";

                if ($community->community_type == 'category') {
                    $images = CommunityImages::where('community_id', $community->id)->get(['image']);
                } else {
                    $images = AssociationCommunityImage::where('community_id', $community->id)->get(['image']);
                }

                $deleteButton = route('admin.user.community.delete', ['community_id' => $community->id, 'type' => $community->community_type]);
                $deleteButtonView = "<a role='button' href='javascript:void(0)' onclick='deleteCommunity(`$deleteButton`);'  data-original-title='View' class='ml-3 btn btn-primary btn-sm ' data-toggle='tooltip'><i class='fas fa-trash pb-1 pt-1'></i></a>";

                $imageHTML = '';
                if (!empty($images)) {
                    $imageHTML .= '<div class="gallery gallery-md d-flex" id="work_place_gallery">';
                    foreach ($images as $key => $image) {
                        $imageHTML .= '<div style="display:inline-grid;cursor: pointer;" id="image_' . $community->id . "_" . $key . '">';
                        $imageHTML .= '<div class="gallery-item" data-image="' . $image->image . '" data-title="' . $community->title . '"></div>';
                        $imageHTML .= '</div>';
                    }
                    $imageHTML .= '</div>';
                }


                $data[$count]['title'] = $community->title;
                $data[$count]['description'] = $community->description;
                $data[$count]['like_count'] = $community->likes_count;
                $data[$count]['comment_count'] = $community->comments_count;
                $data[$count]['view_count'] = $community->views_count;
                $data[$count]['images'] = $imageHTML;
                $data[$count]['type'] = ucfirst($community->community_type);
                $data[$count]['date'] = $community->created_at;
                $data[$count]['actions'] = "<div class='d-flex align-items-center'>$viewButton $deleteButtonView</div>";
                $count++;
            }

            $jsonData = array(
                "draw" => intval($draw),
                "recordsTotal" => intval($totalData),
                "recordsFiltered" => intval($totalFiltered),
                "data" => $data
            );
            return response()->json($jsonData);
        } catch (Exception $ex) {
            Log::info('Exception all hospital list');
            Log::info($ex);
            $jsonData = array(
                "draw" => intval($draw),
                "recordsTotal" => 0,
                "recordsFiltered" => 0,
                "data" => []
            );
            return response()->json($jsonData);
        }
    }

    public function createCommunityUserView(Request $request, $id, $community_id = 0, $type = 'category')
    {
        $pageName = $community_id ? "Edit" : "Create";
        $title = "$pageName Community";

        $community_tabs = getCommunityTabs($id);

        if ($community_id) {
            if ($type == 'associations') {
                $community_data = AssociationCommunity::find($community_id);
            } else {
                $community_data = Community::find($community_id);
            }
        } else {
            $community_data = (object)[];
        }
        return view('admin.users.community-form', compact('title', 'id', 'community_tabs', 'community_id', 'community_data', 'type'));
    }

    public function removeCommunityImage(Request $request)
    {
        $inputs = $request->all();
        try {
            $imageid = $inputs['imageid'] ?? '';
            $deletetype = $inputs['deletetype'] ?? '';
            DB::beginTransaction();

            if ($imageid && $deletetype) {
                if ($deletetype == 'associations') {
                    $image = DB::table('association_community_images')->whereId($imageid)->first();
                    if ($image) {
                        Storage::disk('s3')->delete($image->image);
                        AssociationCommunityImage::where('id', $image->id)->delete();
                    }
                } else {
                    $image = DB::table('community_images')->whereId($imageid)->first();
                    if ($image) {
                        Storage::disk('s3')->delete($image->image);
                        CommunityImages::where('id', $image->id)->delete();
                    }
                }
            }

            DB::commit();
        } catch (Exception $e) {
            Log::info($e);
            DB::rollBack();
            notify()->error("Image " . trans("messages.insert-error"), "Error", "topRight");
        }
    }

    public function loadSubCategory(Request $request)
    {
        $inputs = $request->all();
        $category = $inputs['category'] ?? '';
        $editcategoryid = $inputs['editcategoryid'] ?? '';
        $returnHTML = '';

        if ($category) {
            $categoryArray = explode('_', $category);
            $type = $categoryArray[0];
            $categoryID = $categoryArray[1];

            $returnHTML = loadSubCategoryHtml($type, $categoryID, $editcategoryid);
        }
        $jsonData = array(
            'success' => true,
            'html' => $returnHTML,
        );
        return response()->json($jsonData);
    }

    public function createCommunityUser(Request $request, $id)
    {
        $inputs = $request->all();

        try {
            DB::beginTransaction();
            $validator = Validator::make($request->all(), [
                "title" => "required",
                "description" => "required",
                "category" => "required",
                "subcategory" => "required",
                "main_language_image" => "required|array",
            ], [], [
                'title' => 'Title',
                'description' => 'Description',
                'category' => 'Category',
                'subcategory' => 'Category',
                'main_language_image' => 'Image',
            ]);

            if ($validator->fails()) {
                return response()->json(array(
                    'success' => false,
                    'errors' => $validator->getMessageBag()->toArray()
                ), 400);
            }


            $community_id = $inputs['community_id'] ?? 'KR';
            $main_country = $inputs['$main_country'] ?? 'KR';
            $category = $inputs['category'] ?? '';
            $user_id = $inputs['user_id'] ?? '';
            $categoryArray = explode('_', $category);
            $type = $categoryArray[0];
            $categoryID = $categoryArray[1];

            if ($type == 'category') {
                $requestData = [
                    'category_id' => $inputs['subcategory'],
                    'title' => $inputs['title'],
                    'description' => $inputs['description'],
                    'user_id' => $user_id,
                    'country_code' => $main_country
                ];
                if (!empty($community_id)) {
                    $community = Community::whereId($community_id)->update($requestData);
                } else {
                    $community = Community::create($requestData);
                    $community_id = $community->id;
                    $message = "Updated";
                }

                $communityFolder = config('constant.community') . '/' . $community_id;

                if (!Storage::exists($communityFolder)) {
                    Storage::makeDirectory($communityFolder);
                }


                if (!empty($inputs['main_language_image'])) {
                    foreach ($inputs['main_language_image'] as $image) {
                        if (is_file($image)) {
                            $mainImage = Storage::disk('s3')->putFile($communityFolder, $image, 'public');
                            $fileName = basename($mainImage);
                            $image_url = $communityFolder . '/' . $fileName;
                            $temp = [
                                'community_id' => $community_id,
                                'image' => $image_url
                            ];
                            CommunityImages::create($temp);
                        }
                    }
                }
            } elseif ($type == 'associations') {
                $requestData = [
                    'associations_id' => $categoryID,
                    'category_id' => $inputs['subcategory'],
                    'title' => $inputs['title'],
                    'description' => $inputs['description'],
                    'user_id' => $user_id,
                    'country_code' => $main_country,
                    'is_pin' => 0,
                ];

                if (!empty($community_id)) {
                    $community = AssociationCommunity::whereId($community_id)->update($requestData);
                } else {
                    $community = AssociationCommunity::create($requestData);
                    $community_id = $community->id;
                }
                $communityFolder = config('constant.association_community') . '/' . $community_id;

                if (!Storage::disk('s3')->exists($communityFolder)) {
                    Storage::disk('s3')->makeDirectory($communityFolder);
                }

                if (!empty($inputs['main_language_image'])) {
                    foreach ($inputs['main_language_image'] as $image) {
                        if (is_file($image)) {
                            $mainImage = Storage::disk('s3')->putFile($communityFolder, $image, 'public');
                            $fileName = basename($mainImage);
                            $image_url = $communityFolder . '/' . $fileName;
                            $temp = [
                                'community_id' => $community_id,
                                'image' => $image_url
                            ];
                            AssociationCommunityImage::create($temp);
                        }
                    }
                }
            }

            DB::commit();
            $jsonData = array(
                'success' => true,
                'message' => "Community successfully created.",
                'redirect' => route('admin.user.show-community', ['id' => $user_id])
            );
            return response()->json($jsonData);
        } catch (Exception $e) {
            Log::info($e);
            notify()->error("User " . trans("messages.insert-error"), "Error", "topRight");
            return redirect()->route('admin.user.index');
        }
    }

    public function deleteUserCommunity(Request $request, $community_id = 0, $type = 'category')
    {
        try {
            DB::beginTransaction();
            if ($type == 'category') {
                $reviewComment = Community::find($community_id);
                if ($reviewComment) {
                    UserPoints::where([
                        'entity_type' => UserPoints::UPLOAD_COMMUNITY_POST,
                        'entity_id' => $community_id,
                    ])->delete();

                    $getImages = DB::table('community_images')->where('community_id', $community_id)->get();
                    if ($getImages) {
                        foreach ($getImages as $keyImages => $valueImages) {
                            Storage::disk('s3')->delete($valueImages->image);
                        }
                        CommunityImages::where('community_id', $community_id)->delete();
                    }
                    $review = Community::where('id', $community_id)->delete();
                }
            } else {
                $community = AssociationCommunity::find($community_id);
                $getImages = DB::table('association_community_images')->where('community_id', $community_id)->get();

                if ($getImages) {
                    foreach ($getImages as $keyImages => $valueImages) {
                        Storage::disk('s3')->delete($valueImages->image);
                    }
                }
                $community->images()->delete();

                $getComments = AssociationCommunityComment::where('community_id', $community->id)->get();

                if ($getComments) {
                    foreach ($getComments as $keyComment => $valueComment) {
                        $valueComment->likes()->delete();
                        $valueComment->delete();
                    }
                }
                $community->delete();
            }

            DB::commit();
            return response()->json(array(
                'success' => true,
                'message' => "Community successfully deleted."
            ), 200);
        } catch (Exception $e) {
            DB::rollBack();
            Log::info($e);
            return response()->json(array(
                'success' => false,
                'message' => "Error in community delete."
            ), 400);
        }
    }

    public function makeUserOutside(Request $request, $id)
    {
        $inputs = $request->all();
        try {
            $is_outside = (isset($inputs['is_outside']) && $inputs['is_outside'] == 'true') ? 1 : 0;
            UserDetail::where('user_id', $id)->update(['is_outside' => $is_outside]);

            return response()->json(array(
                'success' => true,
                'message' => "Outside user successfully updated."
            ), 200);
        } catch (Exception $e) {
            Log::info($e);
            return response()->json(array(
                'success' => false,
                'message' => "Error in Outside User."
            ), 200);
        }
    }

    public function listOutsideUsers()
    {
        $title = "Outside User ( Community )";
        $outsideCommentCount = DB::table('community')
            ->join('community_comments', function ($query) {
                $query->on('community.id', '=', 'community_comments.community_id')
                    ->whereNull('community_comments.deleted_at');
            })
            ->join('users_detail', 'users_detail.user_id', 'community.user_id')
            ->where('users_detail.is_outside', 1)
            ->where('community_comments.is_admin_read', 1)
            ->update(['community_comments.is_admin_read' => 0]);

        $outsideCommentReplyCount = DB::table('community_comments')
            ->join('community_comment_reply', function ($query) {
                $query->on('community_comments.id', '=', 'community_comment_reply.community_comment_id')
                    ->whereNull('community_comment_reply.deleted_at');
            })
            ->where('community_comment_reply.is_admin_read', 1)
            ->join('users_detail', 'users_detail.user_id', 'community_comments.user_id')
            ->where('users_detail.is_outside', 1)
            ->update(['community_comment_reply.is_admin_read' => 0]);

        $outsideAssociationComment = DB::table('association_communities')
            ->join('association_community_comments', function ($query) {
                $query->on('association_communities.id', '=', 'association_community_comments.community_id')
                    ->whereNull('association_community_comments.deleted_at');
            })
            ->join('users_detail', 'users_detail.user_id', 'association_communities.user_id')
            ->where('users_detail.is_outside', 1)
            ->where('association_community_comments.is_admin_read', 1)
            ->where('association_community_comments.parent_id', 0)
            ->update(['association_community_comments.is_admin_read' => 0]);

        $outsideAssociationReplyComment = DB::table('association_community_comments')
            ->join('association_community_comments as child', function ($query) {
                $query->on('association_community_comments.id', '=', 'child.parent_id')
                    ->whereNull('child.deleted_at');
            })
            ->join('users_detail', 'users_detail.user_id', 'association_community_comments.user_id')
            ->where('users_detail.is_outside', 1)
            ->where('child.is_admin_read', 1)
            ->where('child.parent_id', '!=', 0)
            ->update(['child.is_admin_read' => 0]);

        return view('admin.users.outside.index', compact('title'));
    }

    public function getJsonAllOutsideUsers(Request $request): JsonResponse
    {
        try {
            $columns = array(
                0 => 'users_detail.name',
                1 => 'users.email',
                2 => 'users_detail.mobile',
                3 => 'users.created_at',
                4 => 'action',
            );

            $limit = $request->input('length');
            $start = $request->input('start');
            $search = $request->input('search.value');
            $draw = $request->input('draw');
            $order = $columns[$request->input('order.0.column')];
            $dir = $request->input('order.0.dir');
            $adminTimezone = $this->getAdminUserTimezone();

            $query = User::join('users_detail', 'users_detail.user_id', 'users.id')
                ->where('users_detail.is_outside', 1)
                ->select(
                    'users_detail.mobile as phone',
                    'users.id',
                    'users_detail.name',
                    'users.email',
                    'users.created_at as date'
                );

            $query = $query->groupBy('users.id');

            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('users_detail.name', 'LIKE', "%{$search}%")
                        ->orWhere('users.email', 'LIKE', "%{$search}%")
                        ->orWhere('users_detail.mobile', 'LIKE', "%{$search}%")
                        ->orWhere('users.created_at', 'LIKE', "%{$search}%");
                });
            }

            $totalData = count($query->get());
            $totalFiltered = $totalData;

            $users = $query->offset($start)
                ->limit($limit)
                ->orderBy($order, $dir)
                ->get();

            $data = array();
            if (!empty($users)) {
                foreach ($users as $value) {
                    $id = $value['id'];

                    $nestedData['name'] = $value['name'];
                    $nestedData['email'] = $value['email'];
                    $nestedData['phone'] = $value['phone'];
                    $nestedData['signup'] = $this->formatDateTimeCountryWise($value['date'], $adminTimezone, 'Y-m-d H:i');

                    $show = route('admin.outside-community-user.user.view', [$value['id']]);
                    $viewButton = "<a role='button' href='" . $show . "' title='' data-original-title='View' class='mx-1 btn btn-primary btn-sm' data-toggle='tooltip'>View</a>";

                    $nestedData['actions'] = "<div class='d-flex'>$viewButton</div>";

                    $data[] = $nestedData;
                }
            }

            $jsonData = array(
                "draw" => intval($draw),
                "recordsTotal" => intval($totalData),
                "recordsFiltered" => intval($totalFiltered),
                "data" => $data
            );
            return response()->json($jsonData);
        } catch (Exception $ex) {
            Log::info($ex);
            return response()->json(array(
                "draw" => 0,
                "recordsTotal" => 0,
                "recordsFiltered" => 0,
                "data" => []
            ));
        }
    }

    public function getOutsideUserView($id)
    {
        $title = "Outside User Detail";
        return view('admin.users.outside.detail', compact('title', 'id'));
    }

    public function getJsonAllOutsideUsersDetail(Request $request, $id): JsonResponse
    {
        $columns = array(
            0 => 'title',
            6 => 'created_at',
            7 => 'created_at',
        );

        $limit = $request->input('length');
        $start = $request->input('start');
        $search = $request->input('search.value');
        $draw = $request->input('draw');
        $order = $columns[$request->input('order.0.column')];
        $dir = $request->input('order.0.dir');

        $viewButton = '';
        try {
            $data = [];
            $userIDs = [];

            $otherAssociationCommentsIds = DB::table('association_communities')
                ->join('association_community_comments', function ($query) use ($id) {
                    $query->on('association_communities.id', '=', 'association_community_comments.community_id')
                        ->where('association_community_comments.user_id', $id)
                        ->whereNull('association_community_comments.deleted_at');
                })
                ->join('association_community_comments as child', function ($query) use ($id) {
                    $query->on('association_community_comments.id', '=', 'child.parent_id')
                        ->whereNull('child.deleted_at');
                })
                ->where('association_communities.user_id', '!=', $id)
                ->groupBy('association_communities.id')
                ->pluck('association_communities.id')->toArray();

            $associationCommentsIds = DB::table('association_communities')
                ->join('association_community_comments', function ($query) {
                    $query->on('association_communities.id', '=', 'association_community_comments.community_id')
                        ->whereNull('association_community_comments.deleted_at');
                })
                ->where('association_communities.user_id', $id)
                ->groupBy('association_communities.id')
                ->pluck('association_communities.id')->toArray();

            $associationWhereIn = array_merge($otherAssociationCommentsIds, $associationCommentsIds);

            $associationCommunity = DB::table('association_communities')
                ->leftJoin('association_community_comments', 'association_community_comments.community_id', 'association_communities.id')
                ->whereIn('association_communities.id', $associationWhereIn)
                ->select(
                    'association_communities.id',
                    'association_communities.user_id',
                    'association_communities.associations_id',
                    'association_communities.category_id',
                    'association_communities.title',
                    'association_communities.description',
                    'association_communities.views_count',
                    'association_communities.created_at',
                    DB::raw('COUNT(association_community_comments.id) as comments_count')
                )
                ->addSelect(DB::raw("'associations' as community_type"))
                ->groupBy('association_communities.id');
            if (!empty($search)) {
                $associationCommunity = $associationCommunity->where(function ($q) use ($search) {
                    $q->where('association_communities.title', 'LIKE', "%{$search}%")
                        ->orWhere('association_communities.description', 'LIKE', "%{$search}%");
                });
            }


            // Community Data
            $otherCommentsIds = DB::table('community')
                ->join('community_comments', function ($query) use ($id) {
                    $query->on('community.id', '=', 'community_comments.community_id')
                        ->where('community_comments.user_id', $id)
                        ->whereNull('community_comments.deleted_at');
                })
                ->join('community_comment_reply', function ($query) use ($id) {
                    $query->on('community_comments.id', '=', 'community_comment_reply.community_comment_id')
                        ->whereNull('community_comment_reply.deleted_at');
                })
                ->where('community.user_id', '!=', $id)
                ->groupBy('community.id')
                ->pluck('community.id')->toArray();

            $commentsIds = DB::table('community')
                ->join('community_comments', function ($query) {
                    $query->on('community.id', '=', 'community_comments.community_id')
                        ->whereNull('community_comments.deleted_at');
                })
                ->where('community.user_id', $id)
                ->groupBy('community.id')
                ->pluck('community.id')->toArray();

            $commWhereIn = array_merge($otherCommentsIds, $commentsIds);

            $communityQuery = DB::table('community')
                ->leftJoin('community_comments', 'community_comments.community_id', 'community.id')
                ->whereIn('community.id', $commWhereIn)
                ->select(
                    'community.id',
                    'community.user_id',
                    'community.category_id as associations_id',
                    'community.category_id',
                    'community.title',
                    'community.description',
                    'community.views_count',
                    'community.created_at',
                    DB::raw('COUNT(community_comments.id) as comments_count')
                )
                ->addSelect(DB::raw("'category' as community_type"))
                ->groupBy('community.id');;


            if (!empty($search)) {
                $communityQuery = $communityQuery->where(function ($q) use ($search) {
                    $q->where('title', 'LIKE', "%{$search}%")
                        ->orWhere('description', 'LIKE', "%{$search}%");
                });
            }


            $communityQuery = $communityQuery->union($associationCommunity);
            // $communityQuery = $associationCommunity;

            $totalData = count($communityQuery->get());
            $totalFiltered = $totalData;

            $communityData = $communityQuery->offset($start)
                ->limit($limit)
                ->orderBy($order, $dir)
                ->get();


            $count = 0;
            foreach ($communityData as $community) {

                $linkButton = route('admin.user.community.edit', ['id' => $community->user_id, 'community_id' => $community->id, 'type' => $community->community_type]);

                $viewButton = "<a role='button' href='$linkButton'  data-original-title='View' class='btn btn-primary btn-sm ' data-toggle='tooltip'><i class='far fa-edit pb-1 pt-1'></i></a>";

                if ($community->community_type == 'category') {
                    $images = CommunityImages::where('community_id', $community->id)->get(['image']);
                } else {
                    $images = AssociationCommunityImage::where('community_id', $community->id)->get(['image']);
                }

                $deleteButton = route('admin.user.community.delete', ['community_id' => $community->id, 'type' => $community->community_type]);
                $deleteButtonView = "<a role='button' href='javascript:void(0)' onclick='deleteCommunity(`$deleteButton`);'  data-original-title='View' class='ml-3 btn btn-primary btn-sm ' data-toggle='tooltip'><i class='fas fa-trash pb-1 pt-1'></i></a>";

                $imageHTML = '';
                if (!empty($images)) {
                    $imageHTML .= '<div class="gallery gallery-md d-flex" id="work_place_gallery">';
                    foreach ($images as $key => $image) {
                        $imageHTML .= '<div style="display:inline-grid;cursor: pointer;" id="image_' . $community->id . "_" . $key . '">';
                        $imageHTML .= '<div class="gallery-item" data-image="' . $image->image . '" data-title="' . $community->title . '"></div>';
                        $imageHTML .= '</div>';
                    }
                    $imageHTML .= '</div>';
                }


                $data[$count]['title'] = $community->title;
                $data[$count]['description'] = $community->description;
                $data[$count]['like_count'] = 0; //$community->likes_count;
                $data[$count]['comment_count'] = $community->comments_count;
                $data[$count]['view_count'] = $community->views_count;
                $data[$count]['images'] = $imageHTML;
                $data[$count]['type'] = ucfirst($community->community_type);
                $data[$count]['date'] = $community->created_at;
                $data[$count]['actions'] = "<div class='d-flex align-items-center'>$viewButton $deleteButtonView</div>";
                $count++;
            }

            $jsonData = array(
                "draw" => intval($draw),
                "recordsTotal" => intval($totalData),
                "recordsFiltered" => intval($totalFiltered),
                "data" => $data
            );
            return response()->json($jsonData);
        } catch (Exception $ex) {
            Log::info('Exception all hospital list');
            Log::info($ex);
            $jsonData = array(
                "draw" => intval($draw),
                "recordsTotal" => 0,
                "recordsFiltered" => 0,
                "data" => []
            );
            return response()->json($jsonData);
        }
    }

    public function giveCards(Request $request, $id)
    {
        $inputs = $request->all();
        try {
            $give_card = $inputs['give_card'] ?? '';
            if ($give_card) {
                $card = DefaultCardsRives::find($give_card);
                $cardData = [
                    'user_id' => $id,
                    'default_cards_id' => $card->default_card_id,
                    'default_cards_riv_id' => $card->id
                ];
                $createCard = UserCards::create($cardData);

                createUserCardDetail($card, $createCard);
            }
            $jsonData = array(
                'success' => true,
                'message' => "Give card Successfully"
            );
            return response()->json($jsonData);
        } catch (Exception $e) {
            Log::info($e);
            $jsonData = array(
                'success' => false,
                'message' => "User Card" . trans("messages.update-error")
            );
            return response()->json($jsonData);
        }
    }
    public function removeCards(Request $request, $id)
    {
        $inputs = $request->all();
        try {
            $card_id = $inputs['card_id'] ?? '';

            if ($card_id) {
                UserCards::where('id', $card_id)->where('user_id', $id)->delete();
            }
            $jsonData = array(
                'success' => true,
                'message' => "User Card" . trans("messages.delete-success")
            );
            return response()->json($jsonData);
        } catch (Exception $e) {
            Log::info($e);
            $jsonData = array(
                'success' => false,
                'message' => "User Card" . trans("messages.delete-error")
            );
            return response()->json($jsonData);
        }
    }

    public function deleteCardModel(Request $request, $id)
    {
        $title = "Delete Card";
        return view('admin.users.cards.delete', compact('title', 'id'));
    }
    public function giveCardsModel(Request $request, $id)
    {
        $title = "Give Card";
        $cards = DefaultCardsRives::leftjoin('default_cards', 'default_cards.id', 'default_cards_rives.default_card_id')
            ->select(
                'default_cards_rives.*',
                'default_cards.name as card_range',
                'default_cards.id as default_id'
            )
            ->orderBy('default_cards.id')
            ->get();

        $groupByCards = collect($cards)->groupBy('card_range');

        $assignCard = UserCards::where('user_id', $id)->pluck('default_cards_riv_id')->toArray();
        return view('admin.users.cards.give-card', compact('title', 'id', 'cards', 'assignCard', 'groupByCards'));
    }

    public function showCards(Request $request, $id)
    {
        $title = "User Owned Cards";
        return view('admin.users.cards.index', compact('title', 'id'));
    }

    public function getCardsJsonData(Request $request, $id)
    {
        $columns = array(
            0 => 'user_cards.created_at',
        );

        $limit = $request->input('length');
        $start = $request->input('start');
        $search = $request->input('search.value');
        $draw = $request->input('draw');
        $order = $columns[$request->input('order.0.column')];
        $dir = $request->input('order.0.dir');

        $defaultCardData = DefaultCards::where('name', 'like', DefaultCards::DEFAULT_CARD)->first();
        try {
            $data = [];
            //$query = UserCards::where('user_id',$id);
            $query = DefaultCardsRives::join('user_cards', 'user_cards.default_cards_riv_id', 'default_cards_rives.id')
                ->where('user_cards.user_id', $id)
                ->select(
                    'user_cards.*',
                    'default_cards_rives.default_card_id',
                    'default_cards_rives.card_name',
                    'default_cards_rives.default_card_id',
                    'default_cards_rives.background_rive',
                    'default_cards_rives.character_rive'
                );

            $totalData = count($query->get());
            $totalFiltered = $totalData;

            $data = $query->offset($start)
                ->limit($limit)
                ->orderBy($order, $dir)
                ->get();
            // print_r($data);die;

            $count = 0;
            foreach ($data as $cards) {
                $deleteButton = '';
                if ($cards->default_cards_id != $defaultCardData->id && $cards->is_applied != 1) {
                    $deleteButton =  "<a href='javascript:void(0);' onClick='deleteUserCards($cards->id)' class='btn btn-danger'><i class='fas fa-trash-alt'></i></i></a>";
                }


                $data[$count]['title'] = $cards->card_name;
                $data[$count]['range'] = $cards->tab_name;

                $cardsLevelData = getUserCardDetailLevelWise($cards);

                $data[$count]['background_riv'] = !empty($cardsLevelData->background_rive_url) ? '<a href="' . $cardsLevelData->background_rive_url . '" target="_blank">' . basename($cardsLevelData->background_rive_url) . '</a>' : '';

                $data[$count]['character_riv'] = !empty($cardsLevelData->character_rive_url) ? '<a href="' . $cardsLevelData->character_rive_url . '" target="_blank">' . basename($cardsLevelData->character_rive_url) . '</a>' : '';

                $data[$count]['assigned_date'] = Carbon::parse($cards->created_at)->format('Y-m-d');

                $data[$count]['card_level'] = getLevelNameByID($cards->active_level);
                $data[$count]['love_count'] = $cards->love_count;

                $data[$count]['actions'] = "<div class='d-flex align-items-center'> $deleteButton </div>";
                $count++;
            }

            $jsonData = array(
                "draw" => intval($draw),
                "recordsTotal" => intval($totalData),
                "recordsFiltered" => intval($totalFiltered),
                "data" => $data
            );
            return response()->json($jsonData);
        } catch (Exception $ex) {
            Log::info($ex);
            $jsonData = array(
                "draw" => intval($draw),
                "recordsTotal" => 0,
                "recordsFiltered" => 0,
                "data" => []
            );
            return response()->json($jsonData);
        }
    }

    public function giveEXP(Request $request, $id)
    {
        $inputs = $request->all();
        $user = Auth::user();
        try {
            DB::beginTransaction();
            $exp = $inputs['exp'] ?? 0;
            UserDetail::where('user_id', $id)->update(['points' => DB::raw("points + $exp")]);
            $userDetails = DB::table('users_detail')->where('user_id', $id)->first();

            UserPoints::create([
                'user_id' => $id,
                'entity_type' => UserPoints::ADMIN_GIVE_EXP,
                'entity_id' => '',
                'entity_created_by_id' => $user->id,
                'points' => $exp
            ]);

            $getLevel = DB::table('levels')->select('id')->where('points', '<=', $userDetails->points)->orderBy('id', 'desc')->first();
            $updateLevel = !empty($getLevel) ? $getLevel->id : 1;

            if (($userDetails) && $updateLevel > $userDetails->level) {
                $cards = Cards::select('card_number')->whereRaw("start <=" . $updateLevel . " OR (end <= " . $updateLevel . ")")->orderBy('id', 'desc')->limit(0, 1)->first()->toArray();
                $cardNumber = !empty($cards) ? $cards['card_number'] : 1;

                UserDetail::where('user_id', $id)->update(['level' => $updateLevel, 'card_number' => $cardNumber]);

                $getUserOwnCardCount = UserCards::where(['user_id' => $id])->count();
                if ($cardNumber > $getUserOwnCardCount) {
                    $getCardsByLevelQ = DefaultCardsRives::select('default_cards_rives.*')->leftjoin('default_cards as dc', 'dc.id', 'default_cards_rives.default_card_id');

                    $getCardsByLevelQ = $getCardsByLevelQ->whereRaw("(dc.start <= " . $updateLevel . " AND dc.end >= " . $updateLevel . " )");

                    $getCardsByLevelQ = $getCardsByLevelQ->whereNotIn('default_cards_rives.id', function ($q) use ($id) {
                        $q->select('default_cards_id')->from('user_cards')->where('user_id', $id);
                    });

                    $getCardsByLevelQ = $getCardsByLevelQ->inRandomOrder()->limit(1)->first();

                    if (!empty($getCardsByLevelQ)) {
                        $cardData = [
                            'user_id' => $id,
                            'default_cards_id' => $getCardsByLevelQ->default_card_id,
                            'default_cards_riv_id' => $getCardsByLevelQ->id
                        ];
                        $userCard = UserCards::create($cardData);

                        createUserCardDetail($getCardsByLevelQ, $userCard);
                    }
                }

                /* $language_id = $userDetails->language_id;
                $nextCardLevel = getUserNextAwailLevel($id, $updateLevel);
                $key = Notice::LEVEL_UP . '_' . $language_id;
                $userIds = [$id];
                $devices = UserDevices::whereIn('user_id', $userIds)->pluck('device_token')->toArray();

                $title_msg = __("notice.$key");
                $notify_type = Notice::LEVEL_UP;

                $notice = Notice::create([
                    'notify_type' => Notice::LEVEL_UP,
                    'user_id' => $id,
                    'to_user_id' => $id,
                    'entity_type_id' => 3,
                    'entity_id' => $id,
                    'title' => 'LV ' . $updateLevel,
                    'sub_title' => $nextCardLevel,
                    'is_aninomity' => 0
                ]);

                $next_level_key = "language_$language_id.next_level_card";
                $next_level_msg = __("messages.$next_level_key", ['level' => $nextCardLevel]);

                $format = 'LV ' . $updateLevel . " " . $next_level_msg;
                $notificationData = [
                    'id' => $id,
                    'user_id' => $id,
                    'title' => $title_msg,
                ];
                if (count($devices) > 0) {
                    $result = $this->sentPushNotification($devices, $title_msg, $format, $notificationData, $notify_type);
                } */
            }

            DB::commit();
            $jsonData = array(
                'success' => true,
                'message' => "User EXP" . trans("messages.update-success")
            );
            return response()->json($jsonData);
        } catch (Exception $e) {
            DB::rollback();
            Log::info($e);
            $jsonData = array(
                'success' => false,
                'message' => "User EXP" . trans("messages.update-error")
            );
            return response()->json($jsonData);
        }
    }

    public function listNonLoginUsers()
    {
        $title = "Non-Login User";
        $totalUser = NonLoginUserDetail::count();
        return view('admin.users.non-login.index', compact('title', 'totalUser'));
    }

    public function getJsonAllNonLoginUsers(Request $request): JsonResponse
    {
        try {
            $columns = array(
                0 => 'username',
                4 => 'created_at',
                5 => 'last_access',
            );

            $limit = $request->input('length');
            $start = $request->input('start');
            $search = $request->input('search.value');
            $draw = $request->input('draw');
            $order = $columns[$request->input('order.0.column')];
            $dir = $request->input('order.0.dir');

            $query = NonLoginUserDetail::select('*')
                ->selectSub(function ($q) {
                    $q->select(DB::raw('count(non_login_love_details.id) as count'))->from('non_login_love_details')->whereRaw("`non_login_love_details`.`device_id` = `non_login_user_details`.`device_id`");
                }, 'love_count')
                //->havingRaw('love_count > 0')
                //->whereNotNull('username')
            ;

            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('username', 'LIKE', "%{$search}%");
                });
            }

            $totalData = count($query->get());
            $totalFiltered = $totalData;

            $users = $query->offset($start)
                ->limit($limit)
                ->orderBy($order, $dir)
                ->get();

            $data = array();
            if (!empty($users)) {
                foreach ($users as $value) {
                    $id = $value['id'];

                    $nestedData['name'] = $value['username'];
                    $nestedData['gender'] = $value['gender'];
                    $nestedData['love_count'] = $value['love_count'];
                    $nestedData['first_access'] = Carbon::parse($value['created_at'])->format('Y-m-d H:i:s');
                    $nestedData['last_access'] = $value['last_access'];
                    $nestedData['location'] = '<div class="d-flex align-items-center"><a role="button" href="javascript:void(0)" onclick="viewLocations('.$value['id'].')" title="" data-original-title="View" class="btn btn-primary btn-sm " data-toggle="tooltip"><i class="fas fa-eye mt-1"></i></a></div>';

                    $nestedData['actions'] = "<div class='d-flex'> - </div>";

                    $data[] = $nestedData;
                }
            }

            $jsonData = array(
                "draw" => intval($draw),
                "recordsTotal" => intval($totalData),
                "recordsFiltered" => intval($totalFiltered),
                "data" => $data
            );
            return response()->json($jsonData);
        } catch (Exception $ex) {
            Log::info($ex);
            return response()->json(array(
                "draw" => 0,
                "recordsTotal" => 0,
                "recordsFiltered" => 0,
                "data" => []
            ));
        }
    }

    public function showNonloginUserLocations($id){
        $adminTimezone = $this->getAdminUserTimezone();
        $locations = UserLocationHistory::leftjoin('countries','countries.code','user_location_histories.country_code')
        ->where('user_location_histories.user_id',$id)
            ->where('user_location_histories.user_type',UserHiddenCategory::NONLOGIN)
            ->orderBy('user_location_histories.created_at','DESC')
            ->select(['user_location_histories.*','countries.name as country_name'])
            ->get();
        return view('admin.non-login.show-locations-popup', compact('locations','adminTimezone'));
    }

    public function lostCategoryShop(Request $request)
    {
        $title = "Lost Category Shop";
        return view('admin.users.lost-shop.index', compact('title'));
    }

    public function getJsonAllLostCategoryData(Request $request)
    {
        try {
            $columns = array(
                0 => 'shops.main_name',
                1 => 'shops.shop_name',
                2 => 'shops.shop_name',
                3 => 'social_name',
                4 => 'last_access',
            );

            $filter = $request->input('filter');
            $limit = $request->input('length');
            $start = $request->input('start');
            $search = $request->input('search.value');
            $draw = $request->input('draw');
            $order = $columns[$request->input('order.0.column')];
            $dir = $request->input('order.0.dir');

            $query = Shop::select(
                'shops.*',
                'users_detail.name as user_name',
                'category.name as category_display_name'
            )
                ->leftJoin('users_detail', 'users_detail.user_id', 'shops.user_id')
                ->join('category', function ($join) {
                    $join->on('shops.category_id', '=', 'category.id')
                        ->whereNotNull('category.deleted_at');
                });

            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('shops.main_name', 'LIKE', "%{$search}%")
                        ->orWhere('shops.shop_name', 'LIKE', "%{$search}%")
                        ->orWhere('users_detail.name', 'LIKE', "%{$search}%");
                });
            }

            $totalData = count($query->get());
            $totalFiltered = $totalData;

            $users = $query->offset($start)
                ->limit($limit)
                ->orderBy($order, $dir)
                ->get();

            $data = array();

            $shopCategory = DB::table('category')->whereIn('category_type_id', [CategoryTypes::SHOP])->whereNull('deleted_at')->get();
            $shopCategory = collect($shopCategory)->mapWithKeys(function ($value) {
                return [$value->id => $value->name];
            })->toArray();

            $shopCustomCategory = $catSuggest = DB::table('category')->whereIn('category_type_id', [CategoryTypes::CUSTOM])->whereNull('deleted_at')->get();
            $shopCustomCategory = collect($shopCustomCategory)->mapWithKeys(function ($value) {
                return [$value->id => $value->name];
            })->toArray();
            $checkCustomCategory = collect($catSuggest)->map(function ($value) {
                return $value->id;
            })->toArray();

            if (!empty($users)) {
                foreach ($users as $value) {
                    $id = $value['id'];

                    $nestedData['user_name'] = $value['user_name'];
                    $nestedData['active_name'] = $value['main_name'];
                    $nestedData['shop_name'] = $value['shop_name'];
                    $nestedData['category'] = $value['category_display_name'];

                    $viewLink = route('admin.business-client.shop.show', $id);
                    $nestedData['view_shop'] = "<a role='button' href='$viewLink' title='' class='btn btn-primary btn-sm mr-3'>See</a>";

                    $html = "<select shop_id='$id' name='category_select' id='category_select' class='form-control selectform'>";
                    $html .= "<option>Select Category</option>";
                    if (in_array($value['category_id'], $checkCustomCategory)) {
                        foreach ($shopCustomCategory as $id => $cat) {
                            $html .= "<option value='$id' > $cat </option>";
                        }
                    } else {
                        foreach ($shopCategory as $id => $cat) {
                            $html .= "<option value='$id' > $cat </option>";
                        }
                    }
                    $html .= "</select>";

                    $nestedData['actions'] = "<div class='d-flex'> $html </div>";

                    $data[] = $nestedData;
                }
            }

            $jsonData = array(
                "draw" => intval($draw),
                "recordsTotal" => intval($totalData),
                "recordsFiltered" => intval($totalFiltered),
                "data" => $data,
                "query" => $query->toSql()
            );
            return response()->json($jsonData);
        } catch (\Exception $ex) {
            Log::info($ex);
            return response()->json(array(
                "draw" => 0,
                "recordsTotal" => 0,
                "recordsFiltered" => 0,
                "data" => []
            ));
        }
    }

    public function send_coffee($id)
    {
        try {
            DB::beginTransaction();

            $not_sent_coffee = UserReferralDetail::where('user_id', $id)->where('is_sent', 0)->count();
            if ($not_sent_coffee) {
                UserReferralDetail::where('user_id', $id)
                    ->where('is_sent', 0)
                    ->update(['is_sent' => 1]);

                $processed_coffee = UserReferralDetail::where('user_id', $id)->where('is_sent', 1)->count();

                DB::commit();

                $jsonData = array(
                    'success' => true,
                    'message' => "The coffee has been sent successfully.",
                    'processed_coffee' => $processed_coffee
                );
                return response()->json($jsonData);
            } else {
                $jsonData = array(
                    'success' => false,
                    'message' => "The coffee has not been sent successfully."
                );
                return response()->json($jsonData);
            }
        } catch (Exception $e) {
            DB::rollback();
            Log::info($e);
            $jsonData = array(
                'success' => false,
                'message' => "The coffee has not been sent successfully."
            );
            return response()->json($jsonData);
        }
    }

    public function getGifticon($id = '', $gifti_id = '')
    {
        $data['id'] = $id;
        if ($gifti_id != '' && $gifti_id != 0) {
            $data['gifticon'] = GifticonDetail::with('attachments')->where('id', $gifti_id)->first();
        }

        return view('admin.users.show-gifticon-popup', $data);
    }

    public function showUserLocations($id){
        $adminTimezone = $this->getAdminUserTimezone();
        $locations = UserLocationHistory::leftjoin('countries','countries.code','user_location_histories.country_code')
            ->where('user_location_histories.user_id',$id)
            ->where('user_location_histories.user_type',UserHiddenCategory::LOGIN)
            ->orderBy('user_location_histories.created_at','DESC')
            ->select('user_location_histories.*', 'countries.name as country_name')
            ->get();
        return view('admin.users.show-locations-popup', compact('locations','adminTimezone'));
    }

    public function addSignupCode(Request $request)
    {
        try {
            $inputs = $request->all();
            $recommended_user_id = UserDetail::where('recommended_code',$inputs['signup_code'])->pluck('user_id')->first();
            if ($recommended_user_id==null){
                $jsonData = [
                    'success' => false,
                    'message' => "Failed to add signup code!!",
                ];
                return response()->json($jsonData);
            }

            UserDetail::where('user_id',$inputs['user_id'])->update(['recommended_by' => $recommended_user_id]);
            $jsonData = [
                'success' => true,
                'message' => "Signup code added successfully.",
            ];
            return response()->json($jsonData);
        } catch (\Exception $ex) {
            $jsonData = [
                'success' => false,
                'message' => "Failed to add signup code!!",
            ];
            return response()->json($jsonData);
        }
    }

}
