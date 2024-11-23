<?php

namespace App\Http\Controllers\Api;

use App\Models\AdminChatNotificationDetail;
use App\Models\AdminMessageNotificationStatus;
use App\Models\CardLevel;
use App\Models\DefaultCardsRives;
use App\Models\GroupMessage;
use App\Models\NodeUserCountry;
use App\Models\UserCardLevel;
use App\Models\UserCards;
use App\Models\UserDevices;
use App\Util\Firebase;
use Validator;
use Carbon\Carbon;
use App\Models\Post;
use App\Models\Shop;
use App\Models\User;
use App\Models\Notice;
use App\Models\Status;
use App\Models\Address;
use App\Models\Message;
use App\Models\Reviews;
use App\Models\Category;
use App\Models\Hospital;
use App\Models\ShopPost;
use App\Models\Community;
use App\Models\PostImages;
use App\Models\ShopDetail;
use App\Models\ShopImages;
use App\Models\UserCredit;
use App\Models\UserDetail;
use App\Models\ActivityLog;
use App\Models\CreditPlans;
use App\Models\EntityTypes;
use App\Models\AdminMessage;
use App\Models\CustomerList;
use Illuminate\Http\Request;
use App\Models\ShopImagesTypes;
use App\Models\UserBlockHistory;
use App\Models\ReloadCoinRequest;
use App\Models\RequestedCustomer;
use App\Models\AdminChatPinDetail;
use App\Models\UserEntityRelation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Models\AssociationCommunity;
use App\Models\RequestBookingStatus;
use App\Validators\MessageValidator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Lang;
use App\Models\CompleteCustomerDetails;
use Illuminate\Support\Facades\Storage;
use App\Models\MessageNotificationStatus;


class MessageController extends Controller
{

    private $messageValidator;
    protected $firebase;

    function __construct()
    {
        $this->messageValidator = new MessageValidator();
        $this->firebase = new Firebase();
    }

    public function getCounsellingData($user, $inputs)
    {
        $notInUsers = DB::table('users')->where('id', '!=', $user->id)
            ->where(function ($q) {
                $q->where('status_id', '!=', Status::ACTIVE)->orWhere('chat_status', '!=', 1);
            })->pluck('id');

        $chatQuery = Message::whereRaw("messages.type='text' and (messages.from_user_id = " . $user->id . " OR messages.to_user_id = " . $user->id . ")")
            //->whereRaw("messages.type='text' and (messages.from_user_id IN (".$user->id.") OR messages.to_user_id = ".$user->id.")")
            ->whereNotIn('messages.from_user_id', $notInUsers)
            ->whereNotIn('messages.to_user_id', $notInUsers)
            ->select(DB::raw('max(messages.id) as message_id'))
            ->groupBy(['messages.entity_id','messages.is_guest'])
            ->pluck('message_id');

        $messagesDataShopQuery = Message::leftjoin('shops', function ($join) {
            $join->on('shops.id', '=', 'messages.entity_id')
                ->where('messages.entity_type_id', EntityTypes::SHOP);
        })
                ->leftjoin('category', function ($join) {
                    $join->on('shops.category_id', '=', 'category.id')
                        ->whereNull('category.deleted_at');
                })
            ->leftjoin('posts', function ($join) {
                $join->on('posts.id', '=', 'messages.entity_id')
                    ->where('messages.entity_type_id', EntityTypes::HOSPITAL);
            })
            ->leftjoin('hospitals', function ($join) {
                $join->on('hospitals.id', '=', 'posts.hospital_id');
            })
            ->leftjoin('user_entity_relation', function ($join) {
                $join->on('hospitals.id', '=', 'user_entity_relation.entity_id')
                    ->where('user_entity_relation.entity_type_id', EntityTypes::HOSPITAL);
            })
            ->whereRaw("if(`messages`.`entity_type_id`= 1 ,`shops`.`user_id` != $user->id,`user_entity_relation`.`user_id` != $user->id)")
            // ->where('shops.user_id','!=',$user->id)
            ->whereNull('shops.deleted_at')
            ->whereNull('posts.deleted_at');
        // ->whereRaw($shopQuery);
        if (isset($inputs['search']) && $inputs['search'] != '') {
            $messagesDataShopQuery = $messagesDataShopQuery->whereRaw("if(messages.entity_type_id = 1 , shops.main_name like '%{$inputs['search']}%',hospitals.main_name like '%{$inputs['search']}%')");
        }
        if (isset($inputs['message_id']) && $inputs['message_id'] != '') {
            $messagesDataShopQuery = $messagesDataShopQuery->where("messages.id", $inputs['message_id']);
        } else {
            $messagesDataShopQuery = $messagesDataShopQuery->whereIn('messages.id', $chatQuery);
        }
        $counselling_chat_list = $messagesDataShopQuery->select(
            'messages.*',
            \DB::raw('(CASE
                WHEN messages.entity_type_id = 1 THEN  shops.show_price
                ELSE 0
            END) AS show_price'),
            \DB::raw('(CASE
                WHEN messages.entity_type_id = 1 THEN  shops.show_address
                ELSE 0
            END) AS show_address'),
            \DB::raw('(CASE
                                                WHEN messages.entity_type_id = 1 THEN  shops.main_name
                                                WHEN messages.entity_type_id = 2 THEN hospitals.main_name
                                                ELSE ""
                                                END) AS main_name'),
            \DB::raw('(CASE
                                                WHEN messages.entity_type_id = 1 THEN  shops.shop_name
                                                WHEN messages.entity_type_id = 2 THEN posts.title
                                                ELSE ""
                                                    END) AS sub_name')
        )
            ->orderBy('messages.id', 'DESC')
            ->groupBy('messages.id');
//            ->paginate(config('constant.pagination_count'), "*", "counselling_chat_list_page");

        $concatQuery = 'CASE
                                WHEN messages.from_user_id = '.$user->id.' THEN CONCAT(messages.from_user_id, "_", messages.to_user_id)
                                ELSE CONCAT(messages.to_user_id, "_", messages.from_user_id)
                            END';
        $chatQuery = Message::whereRaw("messages.type='text' and (messages.from_user_id = " . $user->id . " OR messages.to_user_id = " . $user->id . ")")
            ->where('messages.entity_type_id',0)
            ->where('messages.entity_id',0)
            ->select(DB::raw('max(messages.id) as message_id'))
            ->selectRaw("{$concatQuery} AS uniqe_records")
            ->groupBy('uniqe_records')
            ->pluck('message_id');
        $user_chat_list = Message::whereRaw("messages.type='text' and (messages.from_user_id = " . $user->id . " OR messages.to_user_id = " . $user->id . ")")
            ->where('entity_type_id',0)
            ->where('entity_id',0);
        if (isset($inputs['message_id']) && $inputs['message_id'] != '') {
            $user_chat_list = $user_chat_list->where("messages.id", $inputs['message_id']);
        } else {
            $user_chat_list = $user_chat_list->whereIn('messages.id', $chatQuery);
        }
        $user_chat_list = $user_chat_list->select(
            'messages.*',
            \DB::raw('0 AS show_price'),
            \DB::raw('0 AS show_address'),
            \DB::raw('"" AS main_name'),
            \DB::raw('"" AS sub_name')
        )->orderBy('messages.id', 'DESC');
        $final_chat_list = $counselling_chat_list
            ->union($user_chat_list)
            ->orderBy(DB::raw("DATE_FORMAT(FROM_UNIXTIME(created_at/1000), '%Y-%m-%d %H:%i:%s')"),'DESC');
//            ->orderByRaw('id','DESC');
        if (isset($inputs['limit'])){
            $final_chat_list = $final_chat_list->limit($inputs['limit'])->get();
        } else {
            $final_chat_list = $final_chat_list->paginate(config('constant.pagination_count'), "*", "counselling_chat_list_page");
        }
//        dd($final_chat_list->toArray());

        $i = 0;
        $language_id = isset($inputs['language_id']) ? $inputs['language_id'] : 4;
        if (isset($inputs['latitude']) && isset($inputs['longitude'])) {
            $main_country = getCountryFromLatLong($inputs['latitude'], $inputs['longitude']);
            $timezone = get_nearest_timezone($inputs['latitude'], $inputs['longitude'], $main_country);
        } else {
            $timezone = '';
        }
        $req_timezone = isset($inputs['timezone']) ? $inputs['timezone'] : $timezone;

        if (!empty($final_chat_list)) {
            foreach ($final_chat_list as $value) {
                $booking = DB::table('requested_customer')->select('id')
                    ->where('entity_type_id', $value['entity_type_id'])
                    ->where('entity_id', $value['entity_id'])
                    ->where('user_id', $user->id)
                    ->where('request_booking_status_id', RequestBookingStatus::BOOK)->first();

                $completedBooking = DB::table('requested_customer')->where('entity_type_id', $value['entity_type_id'])
                    ->where('entity_id', $value['entity_id'])
                    ->where('user_id', $user->id)
                    ->where('request_booking_status_id', RequestBookingStatus::COMPLETE)->count();

                $bookingStatus = DB::table('requested_customer')->where('entity_type_id', $value['entity_type_id'])
                    ->where('entity_id', $value['entity_id'])
                    ->where('user_id', $user->id)
                    ->orderBy('id', 'desc')->first();

                $count = DB::table('messages')->where('status', 0)
                    ->where('entity_type_id', $value['entity_type_id'])
                    ->where('entity_id', $value['entity_id'])
                    ->where('to_user_id', $user->id)
                    ->where(function ($q) use ($value) {
                        $q->where('from_user_id', $value['from_user_id'])
                            ->orWhere('from_user_id', $value['to_user_id']);
                    })
                    ->count();

                $toId = $value['to_user_id'];
                $fromId = $value['from_user_id'];
                $getBlockStatus = UserBlockHistory::select('is_block')->where(['block_for' => UserBlockHistory::VIDEO_CALL]);
                $getBlockStatus = $getBlockStatus->where(function ($query) use ($toId, $fromId) {
                    $query->orWhere(['user_id' => $fromId, 'block_user_id' => $toId])
                        ->orWhere(['user_id' => $toId, 'block_user_id' => $fromId]);
                });
                $getBlockStatus = $getBlockStatus->first();
                $final_chat_list[$i]['is_block'] = (!empty($getBlockStatus) && $getBlockStatus->is_block == 1) ? 1 : 0;

                if ($value['entity_type_id'] == EntityTypes::HOSPITAL) {
                    $post = DB::table('posts')->where('id', $value['entity_id'])->first();
                    $entity_id = $post ? $post->hospital_id :  0;

                    $postImage = PostImages::where('post_id', $value['entity_id'])->where('type', PostImages::THUMBNAIL)->select(['id', 'image'])->first();
                    $category_image = $postImage  ? $postImage->image : "";
                    $value['hospital_id'] = $entity_id;

                    $isEtcDetails = false;
                } else {
                    $entity_id = $value['entity_id'];

                    $shop1 = DB::table('shops')->select('category.logo')->join('category', 'category.id', 'shops.category_id')->where('shops.id', $value['entity_id'])->first();
                    if (!empty($shop1) && !empty($shop1->logo) &&  !filter_var($shop1->logo, FILTER_VALIDATE_URL)) {
                        $category_image = Storage::disk('s3')->url($shop1->logo);
                    } else {
                        $category_image = '';
                    }
                    $value['hospital_id'] = 0;

                    $etcDetail = ShopDetail::where('shop_id', $entity_id)->whereIn('type', [ShopDetail::TYPE_CERTIFICATE, ShopDetail::TYPE_TOOLS_MATERIAL_INFO])->first();
                    $isEtcDetails = (!empty($etcDetail)) ? true : false;
                }

                $seconds = $value->created_at / 1000;
                $created_date = date("Y-m-d H:i:s", $seconds);

                $location = Address::where('entity_type_id', $value['entity_type_id'])->where('entity_id', $entity_id)->first();
                $final_chat_list[$i]['count'] = $count;
                $final_chat_list[$i]['is_booked'] = $booking ? 1 : 0;
                $final_chat_list[$i]['booking_id'] = $booking ? $booking->id : 0;
                $final_chat_list[$i]['completed_booking_count'] = $completedBooking;
                $final_chat_list[$i]['booking_status'] = $bookingStatus ? $bookingStatus->request_booking_status_id : 0;
//                $final_chat_list[$i]['time_difference'] = $bookingStatus ? timeAgo($bookingStatus->created_at, $language_id, $req_timezone)  : "";
                $final_chat_list[$i]['time_difference'] = $value ? timeAgo($created_date, $language_id, $req_timezone)  : "";
                $final_chat_list[$i]['location'] = $location;
                $final_chat_list[$i]['category_image'] = $category_image;
                $final_chat_list[$i]['is_qualification'] = $isEtcDetails;
                $final_chat_list[$i]['is_admin_chat'] = false;
                $final_chat_list[$i]['is_user_chat'] = false;

                if ($value['entity_type_id']==0 && $value['entity_id']==0){
                    $this_user = ($user->id!=$value['from_user_id']) ? $value['from_user_id'] : $value['to_user_id'];
                    $this_user = UserDetail::where('user_id',$this_user)->select(['name','avatar','is_character_as_profile','user_id'])->first();
                    $final_chat_list[$i]['main_name'] = $this_user->name;
                    $final_chat_list[$i]['is_character_as_profile'] = $this_user->is_character_as_profile;
                    $final_chat_list[$i]['user_applied_card'] = getThumbnailUserAppliedCard($this_user->user_id);
                    $final_chat_list[$i]['avatar'] = $this_user->avatar;
                    $final_chat_list[$i]['time_difference'] = $value ? timeAgo($created_date, $language_id, $req_timezone)  : "";
                    $final_chat_list[$i]['is_user_chat'] = true;
                }

                $i++;
            }
            // $test = array_multisort($temp, SORT_DESC, $final_chat_list);
        }

        if(((!isset($_REQUEST['counselling_chat_list_page']) || (isset($_REQUEST['counselling_chat_list_page']) && $_REQUEST['counselling_chat_list_page'] == 1)) && $user->is_admin_access != 1) && !isset($inputs['allChatList'])){
            $adminQuery = AdminMessage::whereRaw("admin_messages.type='text' and (admin_messages.from_user = " . $user->id . " OR admin_messages.to_user = " . $user->id . ")")
                ->orderBy('admin_messages.created_at','DESC')
                ->first();

            $shop_profile = Shop::where('user_id',$user->id)->orderBy('created_at','desc')->select(['main_name','shop_name'])->first();
            $count = AdminMessage::where('is_read', 0)
                ->where('from_user', 0)
                ->where('to_user', $user->id)
                ->count();
            if(!empty($adminQuery)){
                $adminQuery->is_admin_chat = true;
                $adminQuery->is_user_chat = false;
                $adminQuery->from_user_id = $user->id;
                $adminQuery->to_user_id = 0;
                $adminQuery->main_name = (!empty($shop_profile)) ? $shop_profile->main_name : null;
                $adminQuery->sub_name = (!empty($shop_profile)) ? $shop_profile->shop_name : null;
                $adminQuery->image = asset('img/logo.png');
                $adminQuery->time_difference = $adminQuery ? timeAgo($adminQuery->created_at, $language_id, $req_timezone)  : "";
                $adminQuery->count = $count;
                $adminChat = $adminQuery;
            }else{
                $adminChat = [
                    'is_admin_chat' => true,
                    'is_user_chat' => false,
                    'from_user_id' => $user->id,
                    'to_user_id' => 0,
                    'main_name' => (!empty($shop_profile)) ? $shop_profile->main_name : null,
                    'sub_name' => (!empty($shop_profile)) ? $shop_profile->shop_name : null,
                    'message' => "",
                    'image' => asset('img/logo.png'),
                    'time_difference' => "",
                    'count' => 0
                ];
            }

            //print_r($adminQuery); exit;
            $updatedItems = $final_chat_list->getCollection();
            $updatedItems = $updatedItems->prepend(collect($adminChat));
            $final_chat_list->setCollection($updatedItems);
        }

        return $final_chat_list;
    }

    public function getBusinessData($user, $inputs)
    {
        $per_page = $inputs['per_page'] ?? 6;
        if (isset($inputs['latitude']) && isset($inputs['longitude'])) {
            $main_country = getCountryFromLatLong($inputs['latitude'], $inputs['longitude']);
            $timezone = get_nearest_timezone($inputs['latitude'], $inputs['longitude'], $main_country);
        } else {
            $timezone = '';
        }
        $req_timezone = isset($inputs['timezone']) ? $inputs['timezone'] : $timezone;

        $user['hospital_count'] = UserEntityRelation::where('entity_type_id', EntityTypes::HOSPITAL)->where('user_id', $user->id)->count();
        $user['shop_count'] = UserEntityRelation::where('entity_type_id', EntityTypes::SHOP)->where('user_id', $user->id)->count();
        $businessData = (object)[];
        if ($user['hospital_count'] > 0) {
            $hospital = UserEntityRelation::where('entity_type_id', EntityTypes::HOSPITAL)->where('user_id', $user->id)->first();
            $tempData = DB::table('messages')->where('messages.entity_type_id', EntityTypes::HOSPITAL)
                ->join('posts', function ($join) {
                    $join->on('posts.id', '=', 'messages.entity_id')
                        ->where('messages.entity_type_id', EntityTypes::HOSPITAL);
                })
                /* ->join('users', function ($join) {
                                $join->on('users.id', '=', 'messages.to_user_id')
                                    ->where('users.status_id', '=' , Status::ACTIVE)
                                    ->where('users.chat_status', '=' , 1);
                            })
                            ->join('users as newUser', function ($join) {
                                $join->on('newUser.id', '=', 'messages.from_user_id')
                                    ->where('newUser.status_id', '=' , Status::ACTIVE)
                                    ->where('newUser.chat_status', '=' , 1);
                            }) */
                ->join('hospitals', function ($join) {
                    $join->on('hospitals.id', '=', 'posts.hospital_id');
                })
                ->where(function ($q) use ($user) {
                    $q->where('messages.from_user_id', $user->id)
                        ->orWhere('messages.to_user_id', $user->id);
                })
                ->where('hospitals.id', $hospital->entity_id)
                ->whereNull('posts.deleted_at')
                ->where('messages.type', 'text')
                ->orderBy('messages.id', 'desc')
                ->get(['messages.*']);
            $rooms = [];
            $messageIds = [];
            foreach ($tempData as $t) {
                $fromTo =  $t->entity_id . '-' . $t->from_user_id . '-' . $t->to_user_id. '-' . $t->is_guest;
                $toFrom = $t->entity_id . '-' . $t->to_user_id . '-' . $t->from_user_id. '-' . $t->is_guest;
                if (!in_array($fromTo, $rooms) && !in_array($toFrom, $rooms)) {
                    $rooms[] =  $fromTo;
                    $rooms[] = $toFrom;
                    array_push($messageIds, $t->id);
                }
            }

            $messagesDataHospitalQuery = Message::join('users_detail', function ($join) use ($user) {
                $join->on('users_detail.user_id', '=', DB::raw("(if (messages.from_user_id = $user->id , messages.to_user_id, messages.from_user_id))"))
                    ->where('messages.entity_type_id', EntityTypes::HOSPITAL);
            })
                ->join('posts', function ($join) {
                    $join->on('posts.id', '=', 'messages.entity_id')
                        ->where('messages.entity_type_id', EntityTypes::HOSPITAL);
                })
                ->join('hospitals', function ($join) {
                    $join->on('hospitals.id', '=', 'posts.hospital_id');
                })
                ->leftjoin('category', 'category.id', 'hospitals.category_id')
                ->join('user_entity_relation', function ($join) {
                    $join->on('hospitals.id', '=', 'user_entity_relation.entity_id')
                        ->where('user_entity_relation.entity_type_id', EntityTypes::HOSPITAL);
                })
                ->whereNull('posts.deleted_at')
                ->where('user_entity_relation.user_id', $user->id);
            // ->whereRaw($hospitalQuery);
            if (isset($inputs['search']) && $inputs['search'] != '') {
                $messagesDataHospitalQuery = $messagesDataHospitalQuery->where('users_detail.name', 'like', "%{$inputs['search']}%");
            }
            if (isset($inputs['message_id']) && $inputs['message_id'] != '') {
                $messagesDataHospitalQuery = $messagesDataHospitalQuery->where("messages.id", $inputs['message_id']);
            } else {
                $messagesDataHospitalQuery = $messagesDataHospitalQuery->whereIn('messages.id', $messageIds);
            }

            $businessData = $messagesDataHospitalQuery->orderBy('messages.id', 'desc')->select('messages.*', 'users_detail.name', 'users_detail.avatar as user_image', 'users_detail.user_id as user_id', 'users_detail.is_character_as_profile', 'category.name as category_name', 'hospitals.main_name', 'posts.title as sub_name');
            if (isset($inputs['limit'])){
                $businessData = $businessData->limit($inputs['limit'])->get();
            }
            else {
                $businessData = $businessData->paginate(config('constant.pagination_count'), "*", "business_chat_list_page");
            }

            foreach ($businessData as $hospitalData) {
                $postImage = PostImages::where('post_id', $hospitalData->entity_id)->where('type', PostImages::THUMBNAIL)->select(['id', 'image'])->first();
                $hospitalData->category_image = $postImage  ? $postImage->image : "";

                $hospitalData->user_applied_card = getUserAppliedCard($hospitalData->user_id);
            }
        } else if ($user['shop_count'] > 0) {
            $tempData = DB::table('messages')->where('messages.entity_type_id', EntityTypes::SHOP)
                ->join('shops', function ($join) {
                    $join->on('shops.id', '=', 'messages.entity_id')
                        ->where('messages.entity_type_id', EntityTypes::SHOP);
                })
                ->where(function($query) use ($user){
                    if ($user) {
                        $query->whereRaw("shops.id NOT IN (select shop_id from shop_block_histories where user_id = {$user->id} AND is_block = 1)");
                    }
                })
                /* ->join('users', function ($join) {
                                $join->on('users.id', '=', 'messages.to_user_id')
                                    ->where('users.status_id', '=' , Status::ACTIVE)
                                    ->where('users.chat_status', '=' , 1);
                            })
                            ->join('users as newUser', function ($join) {
                                $join->on('newUser.id', '=', 'messages.from_user_id')
                                    ->where('newUser.status_id', '=' , Status::ACTIVE)
                                    ->where('newUser.chat_status', '=' , 1);
                            }) */
                ->where(function ($q) use ($user) {
                    $q->where('messages.from_user_id', $user->id)
                        ->orWhere('messages.to_user_id', $user->id);
                })
                ->whereNull('shops.deleted_at')
                ->where('shops.user_id', $user->id)
                ->where('messages.type', 'text')
                ->orderBy('messages.id', 'desc')
                ->get(['messages.*']);
            $rooms = [];
            $messageIds = [];
            foreach ($tempData as $t) {
                $fromTo =  $t->entity_id . '-' . $t->from_user_id . '-' . $t->to_user_id. '-' . $t->is_guest;
                $toFrom = $t->entity_id . '-' . $t->to_user_id . '-' . $t->from_user_id. '-' . $t->is_guest;
                if (!in_array($fromTo, $rooms) && !in_array($toFrom, $rooms)) {
                    $rooms[] =  $fromTo;
                    $rooms[] = $toFrom;
                    array_push($messageIds, $t->id);
                }
            }


            $messagesDataShopQuery = Message::join('users_detail', function ($join) use ($user) {
                $join->on('users_detail.user_id', '=', DB::raw("(if (messages.from_user_id = $user->id , messages.to_user_id, messages.from_user_id ))"))
                    ->where('messages.entity_type_id', EntityTypes::SHOP);
            })
                ->join('shops', function ($join) {
                    $join->on('shops.id', '=', 'messages.entity_id')
                        ->where('messages.entity_type_id', EntityTypes::SHOP);
                })
                ->join('category', function ($join) {
                    $join->on('shops.category_id', '=', 'category.id')
                        ->whereNull('category.deleted_at');
                });
            // ->whereIn('messages.id',$messageIds);
            if (isset($inputs['search']) && $inputs['search'] != '') {
                $messagesDataShopQuery = $messagesDataShopQuery->where('users_detail.name', 'like', "%{$inputs['search']}%");
            }
            if (isset($inputs['message_id']) && $inputs['message_id'] != '') {
                $messagesDataShopQuery = $messagesDataShopQuery->where("messages.id", $inputs['message_id']);
            } else {
                $messagesDataShopQuery = $messagesDataShopQuery->whereIn('messages.id', $messageIds);
            }
            $businessData = $messagesDataShopQuery->orderBy('messages.id', 'desc')->select('messages.*', 'users_detail.name', 'users_detail.user_id as user_id', 'users_detail.avatar as user_image', 'users_detail.is_character_as_profile', 'category.name as category_name', 'category.logo as category_image', 'shops.main_name', 'shops.shop_name as sub_name');
            if (isset($inputs['limit'])){
                $businessData = $businessData->limit($inputs['limit'])->get();
            }
            else {
                $businessData = $businessData->paginate(config('constant.pagination_count'), "*", "business_chat_list_page");
            }
        }
        $language_id = isset($inputs['language_id']) ? $inputs['language_id'] : 4;
        if ($businessData) {
            foreach ($businessData as $bd) {
                if ($bd->entity_type_id == EntityTypes::SHOP) {
                    $bd->category_image = $bd->category_image != null ? Storage::disk('s3')->url($bd->category_image) : $bd->category_image;
                    $bd->hospital_id = 0;

                    $etcDetail = ShopDetail::where('shop_id', $bd->entity_id)->whereIn('type', [ShopDetail::TYPE_CERTIFICATE, ShopDetail::TYPE_TOOLS_MATERIAL_INFO])->first();
                    $isEtcDetails = (!empty($etcDetail)) ? true : false;
                } else {
                    $post = Post::find($bd->entity_id);
                    $bd->hospital_id = $post ? $post->hospital_id : 0;

                    $isEtcDetails = false;
                }
                $bd->is_qualification = $isEtcDetails;

                $bd->user_image = $bd->user_image != null ? Storage::disk('s3')->url($bd->user_image) : asset('img/avatar/avatar-1.png');

                $bd->user_applied_card = getUserAppliedCard($bd->user_id);
                $bd->thumb_user_applied_card = getThumbnailUserAppliedCard($bd->user_id);

                $user_id = $bd->to_user_id == $user->id ? $bd->from_user_id : $bd->to_user_id;

                $toId = $bd->to_user_id;
                $fromId = $bd->from_user_id;
                $getBlockStatus = UserBlockHistory::select('is_block');
                $getBlockStatus = $getBlockStatus->where(function ($query) use ($toId, $fromId) {
                    $query->orWhere(['user_id' => $fromId, 'block_user_id' => $toId])
                        ->orWhere(['user_id' => $toId, 'block_user_id' => $fromId]);
                });
                $getBlockStatus = $getBlockStatus->first();

                $bd->is_block = (!empty($getBlockStatus) && $getBlockStatus->is_block == 1) ? 1 : 0;

                $booking = DB::table('requested_customer')->where('entity_type_id', $bd->entity_type_id)
                    ->where('entity_id', $bd->entity_id)
                    ->where('user_id', $user_id)
                    ->where('request_booking_status_id', RequestBookingStatus::BOOK)->first();

                $completedBooking = DB::table('requested_customer')->where('entity_type_id', $bd->entity_type_id)
                    ->where('entity_id', $bd->entity_id)
                    ->where('user_id', $user_id)
                    ->where('request_booking_status_id', RequestBookingStatus::COMPLETE)->count();

                $bookingStatus = DB::table('requested_customer')->where('entity_type_id', $bd->entity_type_id)
                    ->where('entity_id', $bd->entity_id)
                    ->where('user_id', $user_id)
                    ->orderBy('updated_at', 'desc')->first();

                $lastCompleted = DB::table('requested_customer')->where('entity_type_id', $bd->entity_type_id)
                    ->where('entity_id', $bd->entity_id)
                    ->where('user_id', $user_id)
                    ->where('request_booking_status_id', RequestBookingStatus::COMPLETE)
                    ->orderBy('updated_at', 'desc')->first();

                if ($lastCompleted) {
                    $userimage = UserDetail::where('user_id', $lastCompleted->user_id)->first();

                    $lastCompleted->is_character_as_profile = $userimage ? $userimage->is_character_as_profile : 1;
                    $lastCompleted->user_applied_card = getUserAppliedCard($lastCompleted->user_id);

                    $lastCompleted->user_image = $userimage ? $userimage->avatar : '';
                    $lastCompleted->user_name = $userimage ? $userimage->name : '';
                    $lastCompleted->show_in_home = (bool)$lastCompleted->show_in_home;
                    $lastCompleted->is_cancelled_by_shop = (bool)$lastCompleted->is_cancelled_by_shop;

                    if ($lastCompleted->entity_type_id == EntityTypes::SHOP) {
                        $shop = DB::table('shops')->where('id', $lastCompleted->entity_id)->first();
                        $lastCompleted->requested_item_name = !empty($shop) ? $shop->shop_name : '';
                        $lastCompleted->main_name = !empty($shop) ? $shop->main_name : '';
                    } else {
                        $post = DB::table('posts')->where('id', $lastCompleted->entity_id)->first();
                        $lastCompleted->requested_item_name = !empty($post) ? $post->title : '';
                        $hospitalData = DB::table('hospitals')->where('id', $post->hospital_id)->first();
                        $lastCompleted->main_name = !empty($hospitalData) ? $hospitalData->main_name : '';
                    }
                }

                if ($bookingStatus && !empty($bookingStatus)) {
                    if (!empty($bookingStatus) && property_exists(collect($bookingStatus), 'booking_date')) {
                        $date = new Carbon($bookingStatus->booking_date);
                        $bookingStatus->booking_date = $date->format('Y-m-d H:i:s');
                    } else {
                        $bookingStatus->booking_date = Carbon::now()->format('Y-m-d H:i:s');
                    }

                    if ($bookingStatus->entity_type_id == EntityTypes::SHOP) {
                        $shop = DB::table('shops')->where('id', $bookingStatus->entity_id)->first();
                        $category = Category::find($shop ? $shop->category_id : 0);
                        $bookingStatus->title = $shop ? $shop->shop_name : "";
                        $bookingStatus->requested_item_name = !empty($shop) ? $shop->shop_name : '';
                        $bookingStatus->main_name = !empty($shop) ? $shop->main_name : '';
                    } else {
                        $post = DB::table('posts')->where('id', $bookingStatus->entity_id)->first();
                        $category = Category::find($post ? $post->category_id : 0);
                        $bookingStatus->title = $post ? $post->title : "";
                        $bookingStatus->requested_item_name = !empty($post) ? $post->title : '';
                        $hospitalData = DB::table('hospitals')->where('id', $post->hospital_id)->first();
                        $bookingStatus->main_name = !empty($hospitalData) ? $hospitalData->main_name : '';
                    }
                    $bookingStatus->category_id = $category ? $category->id : 0;
                    $bookingStatus->category_name = $category ? $category->name : "";
                    $bookingStatus->category_logo = $category ? $category->logo : "";


                    $bookingStatus->show_in_home = (bool)$bookingStatus->show_in_home;
                    $bookingStatus->is_cancelled_by_shop = (bool)$bookingStatus->is_cancelled_by_shop;


                    if ($timezone) {
                        $currTime = Carbon::now()->format('Y-m-d H:i:s');
                        $startTime = Carbon::createFromFormat('Y-m-d H:i:s', $bookingStatus->booking_date, "UTC")->setTimezone($timezone);
                        $finishTime = Carbon::createFromFormat('Y-m-d H:i:s', $currTime, "UTC")->setTimezone($timezone);
                        $totalDuration = $finishTime->diffInSeconds($startTime);

                        $date = strtotime($bookingStatus->booking_date);

                        $seconds =  $totalDuration;
                        $days = floor($seconds / 86400);
                        $seconds %= 86400;
                        $hours = floor($seconds / 3600);
                        $seconds %= 3600;
                        $minutes = floor($seconds / 60);
                        $seconds %= 60;

                        $bookingStatus->time_left = [
                            'days' => $days,
                            'hours' => $hours,
                            'minutes' => $minutes,
                            'seconds' => $seconds,
                        ];
                    }

                    if ($bookingStatus->entity_type_id == EntityTypes::HOSPITAL) {
                        $post = DB::table('posts')->where('id', $bookingStatus->entity_id)->first();
                        $entity_id = $post ? $post->hospital_id : 0;
                        $bookingStatus->location = Address::where('entity_type_id', $bookingStatus->entity_type_id)
                            ->where('entity_id', $entity_id)->first();
                    } else {
                        $bookingStatus->location = Address::where('entity_type_id', $bookingStatus->entity_type_id)
                            ->where('entity_id', $bookingStatus->entity_id)->first();
                    }

                    $userimage = UserDetail::where('user_id', $bookingStatus->user_id)->first();
                    $bookingStatus->user_name = $userimage ? $userimage->name : '';
                    $bookingStatus->user_image = $userimage ? $userimage->avatar : '';


                    $bookingStatus->is_character_as_profile = $userimage ? $userimage->is_character_as_profile : 1;
                    $bookingStatus->user_applied_card = getUserAppliedCard($bookingStatus->user_id);

                    if ($timezone != '') {
                        $test = Carbon::createFromFormat('Y-m-d H:i:s', $bookingStatus->booking_date, "UTC")->setTimezone($timezone);
                        $bookingStatus->booking_date = $test->toDateTimeString();
                    }
                }

                $bd->is_booked = $booking ? 1 : 0;
                $bd->booking_id = $booking ? $booking->id : 0;
                $bd->completed_booking_count = $completedBooking;
                $bd->booking_status = $bookingStatus ? $bookingStatus->request_booking_status_id : 0;

                $seconds = $bd->created_at / 1000;
                $created_date = date("Y-m-d H:i:s", $seconds);
                $bd->time_difference = $bd ? timeAgo($created_date, $language_id, $req_timezone)  : "null";


                if ($bd->booking_id == 0 && $bd->completed_booking_count > 0 && $bd->booking_status == 1) {
                    $bd->booking_data = $lastCompleted;
                } else {
                    $bd->booking_data = $bookingStatus;
                }

                $bd->count = DB::table('messages')->where('status', 0)
                    ->where('entity_type_id', $bd->entity_type_id)
                    ->where('entity_id', $bd->entity_id)
                    ->where('to_user_id', $user->id)
                    ->where(function ($q) use ($bd) {
                        $q->where('from_user_id', $bd->from_user_id)
                            ->orWhere('from_user_id', $bd->from_user_id);
                    })
                    ->count();
            }
        }
        return $businessData;
    }

    public function getCounsellingMessage(Request $request)
    {
        $user = Auth::user();
        $inputs = $request->all();
        try {
            Log::info('Start code for get counselling messages');
            if ($user) {
                $validation = Validator::make($request->all(), [
                    'latitude' => 'required',
                    'longitude' => 'required',
                ], [], [
                    'latitude' => 'Latitude',
                    'longitude' => 'Longitude',
                ]);

                if ($validation->fails()) {
                    Log::info('End code for get business messages');
                    return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
                }

                $data['counselling_chat_list'] = $this->getCounsellingData($user, $inputs);

                MessageNotificationStatus::join('users_detail', function ($join) use ($user) {
                    $join->on('users_detail.user_id', '=', 'messages_notification_status.user_id')
                        ->where('messages_notification_status.entity_type_id', EntityTypes::HOSPITAL);
                })
                    ->join('posts', function ($join) {
                        $join->on('posts.id', '=', 'messages_notification_status.entity_id')
                            ->where('messages_notification_status.entity_type_id', EntityTypes::HOSPITAL);
                    })
                    ->join('hospitals', function ($join) {
                        $join->on('hospitals.id', '=', 'posts.hospital_id');
                    })
                    ->leftjoin('category', 'category.id', 'hospitals.category_id')
                    ->join('user_entity_relation', function ($join) {
                        $join->on('hospitals.id', '=', 'user_entity_relation.entity_id')
                            ->where('user_entity_relation.entity_type_id', EntityTypes::HOSPITAL);
                    })
                    ->where('user_entity_relation.user_id', '!=', $user->id)
                    ->where('messages_notification_status.user_id', $user->id)
                    ->update(['notification_status' => 0]);

                MessageNotificationStatus::join('users_detail', function ($join) use ($user) {
                    $join->on('users_detail.user_id', '=', 'messages_notification_status.user_id')
                        ->where('messages_notification_status.entity_type_id', EntityTypes::SHOP);
                })
                    ->join('shops', function ($join) {
                        $join->on('shops.id', '=', 'messages_notification_status.entity_id')
                            ->where('messages_notification_status.entity_type_id', EntityTypes::SHOP);
                    })
                    ->join('category', function ($join) {
                        $join->on('shops.category_id', '=', 'category.id')
                            ->whereNull('category.deleted_at');
                    })
                    ->where('shops.user_id', '!=', $user->id)
                    ->where('messages_notification_status.user_id', $user->id)
                    ->update(['notification_status' => 0]);

                /*MessageNotificationStatus::join('posts', function ($join) {
                    $join->on('posts.id', '=', 'messages_notification_status.entity_id')
                        ->where('messages_notification_status.entity_type_id', EntityTypes::HOSPITAL);
                })
                    ->join('hospitals', function ($join) {
                        $join->on('hospitals.id', '=', 'posts.hospital_id');
                    })
                    ->join('user_entity_relation', function ($join) {
                        $join->on('hospitals.id', '=', 'user_entity_relation.entity_id')
                            ->where('user_entity_relation.entity_type_id', EntityTypes::HOSPITAL);
                    })
                    ->where('user_entity_relation.user_id', '!=', $user->id)
                    ->whereIn('messages_notification_status.entity_type_id', [EntityTypes::HOSPITAL, EntityTypes::SHOP])
                    ->where('messages_notification_status.user_id', $user->id)
                    ->update(['notification_status' => 0]);

                MessageNotificationStatus::join('shops', function ($join) use ($user) {
                    $join->on('shops.id', '=', 'messages_notification_status.entity_id')
                        ->where('messages_notification_status.entity_type_id', EntityTypes::SHOP)
                        ->where('shops.user_id', '!=', $user->id);
                })
                    ->join('category', function ($join) {
                        $join->on('shops.category_id', '=', 'category.id')
                            ->whereNull('category.deleted_at');
                    })
                    ->whereIn('messages_notification_status.entity_type_id', [EntityTypes::HOSPITAL, EntityTypes::SHOP])
                    ->where('messages_notification_status.user_id', $user->id)
                    ->update(['notification_status' => 0]);*/

                MessageNotificationStatus::join('users_detail', function ($join) use ($user) {
                    $join->on('users_detail.user_id', '=', 'messages_notification_status.user_id');
                })
                    ->where('messages_notification_status.entity_type_id',0)
                    ->where('messages_notification_status.entity_id',0)
                    ->where('messages_notification_status.user_id', $user->id)
                    ->update(['notification_status' => 0]);

                if($user->is_admin_access!=1) {
                    AdminMessageNotificationStatus::where('notification_status', 1)
                        ->where('user_id', $user->id)
                        ->update(['notification_status' => 0]);
                }

                Log::info('End code for the get counselling messages');
                return $this->sendSuccessResponse(Lang::get('messages.messages.success'), 200, $data);
            } else {
                Log::info('End code for get counselling messages');
                return $this->sendSuccessResponse(Lang::get('messages.user.token_expired'), 401);
            }
        } catch (\Exception $e) {
            Log::info('Exception in get counselling messages');
            Log::info($e);
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function getBusinessMessage(Request $request)
    {
        $user = Auth::user();
        $inputs = $request->all();
        try {
            Log::info('Start code for get business messages');
            if ($user) {
                $validation = Validator::make($request->all(), [
                    'latitude' => 'required',
                    'longitude' => 'required',
                ], [], [
                    'latitude' => 'Latitude',
                    'longitude' => 'Longitude',
                ]);

                if ($validation->fails()) {
                    Log::info('End code for get business messages');
                    return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
                }

                $emptyResponse = [
                    "data" => [],
                    "total" => 0
                ];
                $chatDetails = $this->getBusinessData($user, $inputs);
                if (empty(collect($chatDetails)->toArray())) {
                    $chatDetails = $emptyResponse;
                }
                $data['business_chat_list'] = ($user->status_id != Status::ACTIVE || $user->chat_status == 0) ? $emptyResponse : $chatDetails;

                MessageNotificationStatus::join('posts', function ($join) {
                    $join->on('posts.id', '=', 'messages_notification_status.entity_id')
                        ->where('messages_notification_status.entity_type_id', EntityTypes::HOSPITAL);
                })
                    ->join('hospitals', function ($join) {
                        $join->on('hospitals.id', '=', 'posts.hospital_id');
                    })
                    ->join('user_entity_relation', function ($join) {
                        $join->on('hospitals.id', '=', 'user_entity_relation.entity_id')
                            ->where('user_entity_relation.entity_type_id', EntityTypes::HOSPITAL);
                    })
                    ->where('user_entity_relation.user_id', '=', $user->id)
                    ->whereIn('messages_notification_status.entity_type_id', [EntityTypes::HOSPITAL, EntityTypes::SHOP])
                    ->where('messages_notification_status.user_id', $user->id)
                    ->update(['notification_status' => 0]);

                MessageNotificationStatus::join('shops', function ($join) use ($user) {
                    $join->on('shops.id', '=', 'messages_notification_status.entity_id')
                        ->where('messages_notification_status.entity_type_id', EntityTypes::SHOP)
                        ->where('shops.user_id', $user->id);
                })
                    ->join('category', function ($join) {
                        $join->on('shops.category_id', '=', 'category.id')
                            ->whereNull('category.deleted_at');
                    })
                    ->whereIn('messages_notification_status.entity_type_id', [EntityTypes::HOSPITAL, EntityTypes::SHOP])
                    ->where('messages_notification_status.user_id', $user->id)
                    ->update(['notification_status' => 0]);

                Log::info('End code for the get business messages');
                return $this->sendSuccessResponse(Lang::get('messages.messages.success'), 200, $data);
            } else {
                Log::info('End code for get business messages');
                return $this->sendSuccessResponse(Lang::get('messages.user.token_expired'), 401);
            }
        } catch (\Exception $e) {
            Log::info('Exception in get business messages');
            Log::info($e);
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }
    public function getChatCount(Request $request)
    {
        $user = Auth::user();
        $inputs = $request->all();
        try {
            Log::info('Start code for get business messages');
            if ($user) {
                $user['hospital_count'] = UserEntityRelation::where('entity_type_id', EntityTypes::HOSPITAL)->where('user_id', $user->id)->count();
                $user['shop_count'] = UserEntityRelation::where('entity_type_id', EntityTypes::SHOP)->where('user_id', $user->id)->count();
                $notify_count = 0;
                $notify_counselling_count_hospital = MessageNotificationStatus::join('users_detail', function ($join) use ($user) {
                    $join->on('users_detail.user_id', '=', 'messages_notification_status.user_id')
                        ->where('messages_notification_status.entity_type_id', EntityTypes::HOSPITAL);
                })
                    ->join('posts', function ($join) {
                        $join->on('posts.id', '=', 'messages_notification_status.entity_id')
                            ->where('messages_notification_status.entity_type_id', EntityTypes::HOSPITAL);
                    })
                    ->join('hospitals', function ($join) {
                        $join->on('hospitals.id', '=', 'posts.hospital_id');
                    })
                    ->leftjoin('category', 'category.id', 'hospitals.category_id')
                    ->join('user_entity_relation', function ($join) {
                        $join->on('hospitals.id', '=', 'user_entity_relation.entity_id')
                            ->where('user_entity_relation.entity_type_id', EntityTypes::HOSPITAL);
                    })
                    ->where('user_entity_relation.user_id', '!=', $user->id)
                    ->where('messages_notification_status.notification_status', 1)
                    ->where('messages_notification_status.user_id', $user->id)->count();

                $notify_counselling_count_shop = MessageNotificationStatus::join('users_detail', function ($join) use ($user) {
                    $join->on('users_detail.user_id', '=', 'messages_notification_status.user_id')
                        ->where('messages_notification_status.entity_type_id', EntityTypes::SHOP);
                })
                    ->join('shops', function ($join) {
                        $join->on('shops.id', '=', 'messages_notification_status.entity_id')
                            ->where('messages_notification_status.entity_type_id', EntityTypes::SHOP);
                    })
                    ->join('category', function ($join) {
                        $join->on('shops.category_id', '=', 'category.id')
                            ->whereNull('category.deleted_at');
                    })
                    ->where('shops.user_id', '!=', $user->id)
                    ->where('messages_notification_status.notification_status', 1)
                    ->where('messages_notification_status.user_id', $user->id)->count();

                $notify_counselling_count_user = MessageNotificationStatus::join('users_detail', function ($join) use ($user) {
                        $join->on('users_detail.user_id', '=', 'messages_notification_status.user_id');
                    })
                    ->where('messages_notification_status.entity_type_id',0)
                    ->where('messages_notification_status.entity_id',0)
                    ->where('messages_notification_status.notification_status', 1)
                    ->where('messages_notification_status.user_id', $user->id)->count();

                $notify_counselling_count_admin = 0;
                if($user->is_admin_access!=1){
                    $notify_counselling_count_admin = AdminMessageNotificationStatus::where('notification_status', 1)
                        ->where('user_id', $user->id)->count();
                }

                if ($user['hospital_count'] > 0) {
                    $notify_count = MessageNotificationStatus::join('users_detail', function ($join) use ($user) {
                        $join->on('users_detail.user_id', '=', 'messages_notification_status.user_id')
                            ->where('messages_notification_status.entity_type_id', EntityTypes::HOSPITAL);
                    })
                        ->join('posts', function ($join) {
                            $join->on('posts.id', '=', 'messages_notification_status.entity_id')
                                ->where('messages_notification_status.entity_type_id', EntityTypes::HOSPITAL);
                        })
                        ->join('hospitals', function ($join) {
                            $join->on('hospitals.id', '=', 'posts.hospital_id');
                        })
                        ->leftjoin('category', 'category.id', 'hospitals.category_id')
                        ->join('user_entity_relation', function ($join) {
                            $join->on('hospitals.id', '=', 'user_entity_relation.entity_id')
                                ->where('user_entity_relation.entity_type_id', EntityTypes::HOSPITAL);
                        })
                        ->where('user_entity_relation.user_id', $user->id)
                        ->where('messages_notification_status.notification_status', 1)
                        ->where('messages_notification_status.user_id', $user->id)->count();
                } else if ($user['shop_count'] > 0) {
                    $notify_count = MessageNotificationStatus::join('users_detail', function ($join) use ($user) {
                        $join->on('users_detail.user_id', '=', 'messages_notification_status.user_id')
                            ->where('messages_notification_status.entity_type_id', EntityTypes::SHOP);
                    })
                        ->join('shops', function ($join) {
                            $join->on('shops.id', '=', 'messages_notification_status.entity_id')
                                ->where('messages_notification_status.entity_type_id', EntityTypes::SHOP);
                        })
                        ->join('category', function ($join) {
                            $join->on('shops.category_id', '=', 'category.id')
                                ->whereNull('category.deleted_at');
                        })
                        ->where('shops.user_id', $user->id)
                        ->where('messages_notification_status.notification_status', 1)
                        ->where('messages_notification_status.user_id', $user->id)->count();
                }
                $notice_count = Notice::where('to_user_id', $user->id)->where('is_read', 0)->count();
                $counselling_data = $this->getCounsellingData($user, $inputs);
                $counselling_data_count = 0;
                foreach ($counselling_data as $cd) {
                    if ($cd) {
                        $counselling_data_count += $cd['count'];
                    }
                }

                $business_chat_list = $this->getBusinessData($user, $inputs);
                $business_chat_list_count = 0;
                foreach ($business_chat_list as $cd) {
                    if ($cd) {
                        $business_chat_list_count += $cd->count;
                    }
                }

                $admin_chat_count = 0;
                if($user->is_admin_access==1){
                    $admin_chat_count = AdminMessageNotificationStatus::where('notification_status', 1)
                        ->where('user_id', 0)->count();
                }

                $data = [
                    'notify_count' => $notify_count,
                    'notify_counselling_count' => $notify_counselling_count_hospital + $notify_counselling_count_shop + $notify_counselling_count_user + $notify_counselling_count_admin,
                    'counselling_chat_total' => ($user->status_id != Status::ACTIVE || $user->chat_status == 0) ? 0 : $counselling_data_count,
                    'business_chat_total' => ($user->status_id != Status::ACTIVE || $user->chat_status == 0) ? 0 : $business_chat_list_count,
                    'notice_total' => $notice_count,
                    'verify_status' => $user->verify_status,
                    'admin_chat_total' => ($user->is_admin_access==1) ? $admin_chat_count : 0,
                ];

                Log::info('End code for the get business messages');
                return $this->sendSuccessResponse(Lang::get('messages.messages.success'), 200, $data);
            } else {
                Log::info('End code for get business messages');
                return $this->sendSuccessResponse(Lang::get('messages.user.token_expired'), 401);
            }
        } catch (\Exception $e) {
            Log::info('Exception in get business messages');
            Log::info($e);
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function deleteChatMessages(Request $request)
    {
        $user = Auth::user();
        $inputs = $request->all();
        try {
            Log::info('Start code for delete business messages');
            if ($user) {
                $validation = $this->messageValidator->validateDelete($inputs);
                if ($validation->fails()) {
                    Log::info('End code for add hospital post');
                    return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
                }
                Message::where('entity_type_id', $inputs['entity_type_id'])
                    ->where('entity_id', $inputs['entity_id'])
                    ->where(function ($q) use ($inputs) {
                        $q->where('from_user_id', $inputs['from_user_id'])
                            ->orWhere('from_user_id', $inputs['to_user_id'])
                            ->orWhere('to_user_id', $inputs['from_user_id'])
                            ->orWhere('to_user_id', $inputs['to_user_id']);
                    })->delete();

                Log::info('End code for the delete business messages');
                return $this->sendSuccessResponse(Lang::get('messages.messages.delete-success'), 200, []);
            } else {
                Log::info('End code for delete business messages');
                return $this->sendSuccessResponse(Lang::get('messages.user.token_expired'), 401);
            }
        } catch (\Exception $e) {
            Log::info('Exception in delete business messages');
            Log::info($e);
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }
    public function initiateChatMessages(Request $request)
    {
        $user = Auth::user();
        $inputs = $request->all();
        try {
            Log::info('Start code for initiate chat messages');
            if ($user) {
                $validation = $this->messageValidator->validateIntiate($inputs);
                if ($validation->fails()) {
                    Log::info('End code for initiate chat messages');
                    return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
                }
                $data = $where = [
                    'entity_type_id' => $inputs['entity_type_id'],
                    'entity_id' => $inputs['entity_id'],
                    'user_id' => $inputs['user_id'],
                    'request_booking_status_id' => RequestBookingStatus::TALK
                ];

                $requestCustomer = RequestedCustomer::create($data);
                $country = '';
                if ($inputs['entity_type_id'] == EntityTypes::SHOP) {
                    $address = Address::where('entity_type_id', $inputs['entity_type_id'])
                        ->where('entity_id', $inputs['entity_id'])->first();
                    $country = $address ? $address->main_country : '';
                } else if ($inputs['entity_type_id'] == EntityTypes::HOSPITAL) {
                    $post = Post::find($inputs['entity_id']);
                    $hospital_id = $post ? $post->hospital_id : null;
                    $address = Address::where('entity_type_id', $inputs['entity_type_id'])
                        ->where('entity_id', $hospital_id)->first();
                    $country = $address ? $address->main_country : '';
                }
                ActivityLog::create([
                    'entity_type_id' => $inputs['entity_type_id'],
                    'entity_id' => $inputs['entity_id'],
                    'user_id' => $inputs['user_id'],
                    'country' => $country,
                    'request_booking_status_id' => RequestBookingStatus::TALK,
                ]);

                Log::info('End code for the initiate chat messages');
                return $this->sendSuccessResponse(Lang::get('messages.messages.initiate-success'), 200, []);
            } else {
                Log::info('End code for initiate chat messages');
                return $this->sendSuccessResponse(Lang::get('messages.user.token_expired'), 401);
            }
        } catch (\Exception $e) {
            Log::info('Exception in initiate chat messages');
            Log::info($e);
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function getNotices(Request $request)
    {
        $user = Auth::user();
        $inputs = $request->all();
        try {
            Log::info('Start code for get counselling messages');
            if ($user) {
                $inputs = $request->all();
                $validation = Validator::make($request->all(), [
                    'latitude' => 'required',
                    'longitude' => 'required',
                ], [], [
                    'latitude' => 'Latitude',
                    'longitude' => 'Longitude',
                ]);

                if ($validation->fails()) {
                    Log::info('End code for get counselling messages');
                    return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
                }

                $language_id = $request->has('language_id') ? $inputs['language_id'] : 4;
                $notices = $this->getNoticeData($inputs['latitude'], $inputs['longitude'], $language_id);
                $user_details = ['id' => $user->id, 'status_id' => $user->status_id, 'chat_status' => $user->chat_status];
                Log::info('End code for the get counselling messages');
                return $this->sendSuccessResponse(Lang::get('messages.messages.success'), 200, compact('notices', 'user_details'));
            } else {
                Log::info('End code for get counselling messages');
                return $this->sendSuccessResponse(Lang::get('messages.user.token_expired'), 401);
            }
        } catch (\Exception $e) {
            //print_r($e->getMessage());die;
            Log::info('Exception in get counselling messages');
            Log::info($e);
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function getNoticeData($latitude, $longitude, $language_id)
    {
        $user = Auth::user();
        $main_country = getCountryFromLatLong($latitude, $longitude);
        $timezone = get_nearest_timezone($latitude, $longitude, $main_country);

        $carbonLanguageArray = [1 => 'ko', 2 => 'zh-CN', 3 => 'ja', 4 => 'en'];
        $notInNotice = [Notice::LEVEL_UP,Notice::USER_MISSED_CARD,Notice::LIKE_SHOP_POST,Notice::CONNECTING_FIRST_TIME_IN_DAY,Notice::REWARD_RECOMMENDED];
        $notice_count = Notice::where('to_user_id', $user->id)->update(['is_read' => 1]);
        $notices = Notice::where('to_user_id', $user->id)->whereNotIn('notify_type',$notInNotice)->orderBy('id', 'desc')->paginate(config('constant.pagination_count'), "*", "notices_page");
        foreach ($notices as $notice) {
            $notice->time_difference = $notice ? timeAgo($notice->created_at, $language_id)  : "null";
            $notice->image = '';
            if ($notice->notify_type == Notice::COMMUNITY_POST_COMMENT || $notice->notify_type == Notice::COMMUNITY_REPLY_COMMENT || $notice->notify_type == Notice::COMMUNITY_POST_LIKE) {
                $community = Community::find($notice->entity_id);

                $key = $notice->notify_type . '_' . $language_id;
                $notice->heading = __("notice.$key", ['username' => $notice->user_name]);
                $notice->title = $community ? $community->title : '';
                $notice->community_id = $community ? $community->id : '';
                $notice->description = '';
            } else if ($notice->notify_type == Notice::ADMIN_NOTICE) {
                $key = $notice->notify_type . '_' . $language_id;
                $notice->heading = __("notice.$key");
                // $notice->title = $community ? $community->title : '';
                $notice->description = '';
            } else if ($notice->notify_type == Notice::OUTSIDE_VISIT || $notice->notify_type == Notice::OUTSIDE_HOUR_1_BEFORE_VISIT) {

                $customer = CompleteCustomerDetails::find($notice->entity_id);
                $customerData = CustomerList::find($customer->customer_id);
                $title = $notice->title;
                $title = $notice->title = $customerData ? $customerData->customer_name : $title;

                if ($customer['entity_type_id'] == EntityTypes::HOSPITAL) {
                    $post = Post::find($customer['entity_id']);

                    $userDetail = DB::table('posts')
                        ->join('hospitals', function ($join) {
                            $join->on('hospitals.id', '=', 'posts.hospital_id')
                                ->whereNull('hospitals.deleted_at');
                        })
                        ->join('user_entity_relation', function ($join) {
                            $join->on('hospitals.id', '=', 'user_entity_relation.entity_id')
                                ->where('user_entity_relation.entity_type_id', EntityTypes::HOSPITAL);
                        })
                        ->join('users', function ($join) {
                            $join->on('users.id', '=', 'user_entity_relation.user_id');
                        })
                        ->select('users.id', 'users.chat_status', 'users.status_id', 'posts.title')
                        ->where('posts.id', $customer['entity_id'])
                        ->whereNull('users.deleted_at')
                        ->whereNull('posts.deleted_at')
                        ->first();

                    $entity_id = $post ? $post->hospital_id : 0;
                    $location = Address::where('entity_type_id', $customer['entity_type_id'])
                        ->where('entity_id', $entity_id)->first();
                    $main_title = $userDetail ? $userDetail->title : $notice->title;
                } else {
                    $userDetail = DB::table('users')->join('shops', 'shops.user_id', 'users.id')
                        ->select('users.id', 'users.chat_status', 'users.status_id', 'shops.shop_name', 'shops.main_name')
                        ->where('shops.id', $customer['entity_id'])
                        ->whereNull('users.deleted_at')
                        ->first();


                    $location = Address::where('entity_type_id', $customer['entity_type_id'])
                        ->where('entity_id', $customer['entity_id'])->first();

                    $main_title = $userDetail ? $userDetail->main_name . " / " . $userDetail->shop_name : $notice->title;
                }

                if ($userDetail) {
                    $notice->shop_user_detail = $userDetail;
                } else {
                    $notice->shop_user_detail = ["id" => 0, "chat_status" => 0, "status_id" => 0];
                }

                $currTime = Carbon::now()->format('Y-m-d H:i:s');
                $startTime = Carbon::createFromFormat('Y-m-d H:i:s', $customer->date, "UTC")->setTimezone($timezone);
                $finishTime = Carbon::createFromFormat('Y-m-d H:i:s', $currTime, "UTC")->setTimezone($timezone);
                $totalDuration = $finishTime->diffInSeconds($startTime);
                $date = strtotime($customer->date);
                $seconds =  $totalDuration;
                $days = floor($seconds / 86400);
                $seconds %= 86400;
                $hours = floor($seconds / 3600);
                $seconds %= 3600;
                $minutes = floor($seconds / 60);
                $seconds %= 60;

                $customer['time_left'] = [
                    'days' => $days,
                    'hours' => $hours,
                    'minutes' => $minutes,
                    'seconds' => $seconds,
                ];
                $customer->booking_date = Carbon::createFromFormat('Y-m-d H:i:s', $customer->date, "UTC")->setTimezone($timezone);


                $customer['location'] = $location;

                if ($notice->notify_type == Notice::OUTSIDE_HOUR_1_BEFORE_VISIT) {
                    $noticeKey = Notice::HOUR_1_BEFORE_VISIT;
                } else {
                    $noticeKey = Notice::VISIT;
                }

                $key = $noticeKey . '_' . $language_id;

                $sub_key = "visiting_day_" . $language_id;
                $date = Carbon::createFromFormat('Y-m-d H:i:s', $notice->sub_title, "UTC")->setTimezone($timezone);
                $notice->heading = __("notice.$key", ['name' => $title]);
                $notice->sub_title = __("notice.$sub_key") . " " . $date->format('Y/F/d A g:i');
                $notice->title = $main_title;
                $notice->description = '';
                $notice->booking_data = $customer;
            } else if ($notice->notify_type == Notice::BOOKING || $notice->notify_type == Notice::BOOKING_CANCEL || $notice->notify_type == Notice::HOUR_1_BEFORE_VISIT || $notice->notify_type == Notice::HOUR_2_BEFORE_VISIT || $notice->notify_type == Notice::VISIT) {
                $customer = RequestedCustomer::find($notice->entity_id);
                $title = $notice->title;
                if ($notice->notify_type == Notice::HOUR_1_BEFORE_VISIT || $notice->notify_type == Notice::VISIT) {
                    $title = $notice->title = $customer ? $customer->user_name : "";
                } elseif ($customer && $customer->user_id != $user->id) {
                    $title = $customer->user_name;
                }

                $booking = RequestedCustomer::find($notice->entity_id);

                if ($booking['entity_type_id'] == EntityTypes::HOSPITAL) {
                    $post = Post::find($booking['entity_id']);

                    $userDetail = DB::table('posts')
                        ->join('hospitals', function ($join) {
                            $join->on('hospitals.id', '=', 'posts.hospital_id')
                                ->whereNull('hospitals.deleted_at');
                        })
                        ->join('user_entity_relation', function ($join) {
                            $join->on('hospitals.id', '=', 'user_entity_relation.entity_id')
                                ->where('user_entity_relation.entity_type_id', EntityTypes::HOSPITAL);
                        })
                        ->join('users', function ($join) {
                            $join->on('users.id', '=', 'user_entity_relation.user_id');
                        })
                        ->select('users.id', 'users.chat_status', 'users.status_id')
                        ->where('posts.id', $booking['entity_id'])
                        ->whereNull('users.deleted_at')
                        ->whereNull('posts.deleted_at')
                        ->first();

                    $entity_id = $post ? $post->hospital_id : 0;
                    $location = Address::where('entity_type_id', $booking['entity_type_id'])
                        ->where('entity_id', $entity_id)->first();
                    $main_title = $booking ? $booking->requested_item_name : $notice->title;
                } else {
                    $userDetail = DB::table('users')->join('shops', 'shops.user_id', 'users.id')
                        ->select('users.id', 'users.chat_status', 'users.status_id')
                        ->where('shops.id', $booking['entity_id'])
                        ->whereNull('users.deleted_at')
                        ->first();


                    $location = Address::where('entity_type_id', $booking['entity_type_id'])
                        ->where('entity_id', $booking['entity_id'])->first();

                    $main_title = $booking ? $booking->main_name . " / " . $booking->requested_item_name : $notice->title;
                }

                if ($userDetail) {
                    $notice->shop_user_detail = $userDetail;
                } else {
                    $notice->shop_user_detail = ["id" => 0, "chat_status" => 0, "status_id" => 0];
                }

                $currTime = Carbon::now()->format('Y-m-d H:i:s');
                $startTime = Carbon::createFromFormat('Y-m-d H:i:s', $booking->booking_date, "UTC")->setTimezone($timezone);
                $finishTime = Carbon::createFromFormat('Y-m-d H:i:s', $currTime, "UTC")->setTimezone($timezone);
                $totalDuration = $finishTime->diffInSeconds($startTime);
                $date = strtotime($booking->booking_date);
                $seconds =  $totalDuration;
                $days = floor($seconds / 86400);
                $seconds %= 86400;
                $hours = floor($seconds / 3600);
                $seconds %= 3600;
                $minutes = floor($seconds / 60);
                $seconds %= 60;

                $booking['time_left'] = [
                    'days' => $days,
                    'hours' => $hours,
                    'minutes' => $minutes,
                    'seconds' => $seconds,
                ];
                $booking->booking_date = Carbon::createFromFormat('Y-m-d H:i:s', $booking->booking_date, "UTC")->setTimezone($timezone);


                $booking['location'] = $location;
                $key = $notice->notify_type . '_' . $language_id;
                $sub_key = "visiting_day_" . $language_id;

                $languageKey = ($language_id < 4) ? $carbonLanguageArray[$language_id] : 'en';
                Carbon::setLocale($languageKey);
                $date = Carbon::createFromFormat('Y-m-d H:i:s', $notice->sub_title, "UTC")->setTimezone($timezone);
                $notice->heading = __("notice.$key", ['name' => $title]);

                //$notice->sub_title = __("notice.$sub_key")." ".$date->format('Y/F/d A g:i');
                $notice->sub_title = __("notice.$sub_key") . " " . $date->translatedFormat('Y / F / jS / A g:i');
                $notice->title = $main_title;
                $notice->description = '';
                $notice->booking_data = $booking;
            } else if ($notice->notify_type == Notice::REPORT) {
                $key = $notice->notify_type . '_' . $language_id;
                $notice->heading = __("notice.$key");
                $notice->description = '';
            } else if ($notice->notify_type == Notice::ADD_MULTI_PROFILE) {
                $key = $notice->notify_type . '_' . $language_id;
                $sub_key = "mearound_" . $language_id;
                $notice->heading = __("notice.$sub_key");
                $notice->title = __("notice.$key", ['name' => $notice->title]);
                $notice->description = '';
            } else if ($notice->notify_type == Notice::OUT_OF_COINS) {
                $key = $notice->notify_type . '_' . $language_id;
                $sub_key = "mearound_" . $language_id;
                $notice->heading = __("notice.$sub_key");
                $notice->title = __("notice.$key");
                $user_detail = UserDetail::where('user_id', $notice->to_user_id)->first();
                if ($notice->entity_type_id == EntityTypes::SHOP) {
                    $shops = Shop::where('user_id', $notice->to_user_id)->get();
                    $temp = '';
                    foreach ($shops as $s) {
                        $temp .= $s->shop_name . '(' . $s->main_name . '), ';
                    }
                    $notice->sub_title = rtrim($temp, ', ');
                    $creditPlan = CreditPlans::where('entity_type_id', EntityTypes::SHOP)->where('package_plan_id', $user_detail->package_plan_id)->first();
                    $defaultCredit = $creditPlan ? $creditPlan->amount : 0;
                    $key1 = "minimum_coin_" . $language_id;
                    $notice->description =  __("notice.$key1") . "(" . $defaultCredit . ") * " . count($shops);
                } else {
                    $userRelation = UserEntityRelation::where('entity_type_id', EntityTypes::HOSPITAL)->where('user_id', $notice->to_user_id)->first();
                    $hospital = Hospital::where('id', $userRelation->entity_id)->get();
                    $temp = '';
                    foreach ($hospital as $s) {
                        $temp .= $s->main_name . ', ';
                    }
                    $notice->sub_title = rtrim($temp, ', ');
                    $creditPlan = CreditPlans::where('entity_type_id', EntityTypes::HOSPITAL)->where('package_plan_id', $user_detail->package_plan_id)->first();
                    $defaultCredit = $creditPlan ? $creditPlan->amount : 0;
                    $key1 = "minimum_coin_" . $language_id;
                    $notice->description =  __("notice.$key1") . "(" . $defaultCredit . ") * " . count($hospital);
                }
            } else if ($notice->notify_type == Notice::PROFILE_ACTIVATE || $notice->notify_type == Notice::PROFILE_DEACTIVATE || $notice->notify_type == Notice::PROFILE_PENDING || $notice->notify_type == Notice::PROFILE_HIDE || $notice->notify_type == Notice::PROFILE_UNHIDE) {
                $key = $notice->notify_type . '_' . $language_id;
                $sub_key = "mearound_" . $language_id;
                $notice->heading = __("notice.$sub_key");
                $notice->title = __("notice.$key");
                $notice->description = '';
                if ($notice->entity_type_id == EntityTypes::SHOP) {
                    $shop = Shop::find($notice->entity_id);
                    $notice->status_id = $shop->status_id;
                    $notice->status_name = $shop->status_name;
                } else if ($notice->entity_type_id == EntityTypes::HOSPITAL) {
                    $hospital = Hospital::find($notice->entity_id);
                    $notice->status_id = $hospital->status_id;
                    $notice->status_name = $hospital->status_name;
                }
            } elseif ($notice->notify_type == Notice::FOLLOW) {
                $key = $notice->notify_type . '_' . $language_id;
                $notice->heading = __("notice.$key", ['name' => $notice->user_name]);
                $notice->description = '';
            } else if ($notice->notify_type == Notice::POST_EXPIRE) {
                $post = NULL;
                if ($notice->entity_type_id == EntityTypes::HOSPITAL) {
                    $post = Post::find($notice->entity_id);
                }
                $key = $notice->notify_type . '_' . $language_id;
                $sub_key = "expired_" . $language_id;
                $date = new Carbon($notice->sub_title);
                $notice->heading = __("notice.$key");
                $notice->sub_title = __("notice.$sub_key") . " " . $date->format('Y/F/d');
                $notice->description = '';
                $notice->image = $post && !empty($post->thumbnail_url) ? $post->thumbnail_url->image : '';
            } else if ($notice->notify_type == Notice::REWARD_RECOMMENDED) {
                $userCredits = UserCredit::where('user_id', $notice->to_user_id)->first();
                $sub_key1 = "mearound_" . $language_id;
                $notice->heading = __("notice.$sub_key1");
                $key = $notice->notify_type . '_' . $language_id;
                $sub_key = "rewarded_coin_" . $language_id;
                $notice->sub_title = __("notice.$sub_key") . " +" . $notice->sub_title . 'coins , ';
                $notice->description = number_format((float)$notice->title) . " coins left";;
                $notice->title = __("notice.$key");
            } else if ($notice->notify_type == Notice::INQUIRY_COIN_DEDUCT) {
                $userCredits = UserCredit::where('user_id', $notice->to_user_id)->first();
                $key = $notice->notify_type . '_' . $language_id;
                $notice->heading = __("notice.$key");
                $sub_key = "deduct_coin_once_4_week_" . $language_id;
                $notice->description = __("notice.$sub_key") . " -" . $notice->sub_title . 'coins , ' . number_format((float)$userCredits->credits) . " coins left";
                $notice->sub_title = $notice->title;
                $notice->title = $notice->user_name;
            } else if ($notice->notify_type == Notice::MONTHLY_COIN_DEDUCT) {
                $userCredits = UserCredit::where('user_id', $notice->to_user_id)->first();
                $key = $notice->notify_type . '_' . $language_id;
                $notice->heading = __("notice.$key");
                $sub_key = "deducted_coin_" . $language_id;
                $notice->title = __("notice.$sub_key", ['name' => $notice->title]);
                $notice->sub_title = '-' . $notice->sub_title . ' Coins, ' . number_format((float)$userCredits->credits) . " coins left";
                $notice->description = '';
            } else if ($notice->notify_type == Notice::NOSHOW) {
                $key = $notice->notify_type . '_' . $language_id;
                $notice->heading = __("notice.$key");
                $notice->description = '';
            } else if ($notice->notify_type == Notice::POST_LIKE) {
                $image = '';
                if ($notice->entity_type_id == EntityTypes::HOSPITAL) {
                    $post = Post::find($notice->entity_id);
                    $image = $post && !empty($post->thumbnail_url) ? $post->thumbnail_url->image : '';
                }
                if ($notice->entity_type_id == EntityTypes::SHOP) {
                    $post = ShopPost::find($notice->entity_id);
                    $image = $post ? $post->post_item : '';
                }
                $key = $notice->notify_type . '_' . $language_id;
                $notice->heading = __("notice.$key");
                $notice->description = '';
                $notice->image = $image;
            } else if ($notice->notify_type == Notice::ADMIN_SETTING_CHANGE_NOTIFICATION) {
                $key = $notice->notify_type . '_' . $language_id;
                $notice->heading = __("notice.$key");
                $notice->description = '';
                $user = User::find($notice->to_user_id);
                $notice->package_plan_id = $user ? $user->package_plan_id : 0;
                $notice->package_plan_name = $user ? $user->package_plan_name : 0;
            } else if ($notice->notify_type == Notice::REVIEW_POST_COMMENT || $notice->notify_type == Notice::REVIEW_REPLY_COMMENT) {
                $review = Reviews::find($notice->entity_id);
                $key = $notice->notify_type . '_' . $language_id;

                $notice->heading = __("notice.$key", ['username' => $notice->user_name]);
                $notice->title = $review ? $review->review_comment : '';
                $notice->description = '';
                $notice->review_entity_id = $review ? $review->entity_type_id : 0;
            } else if ($notice->notify_type == Notice::REWARD_RECOMMENDED_ONCE) {
                $sub_key1 = "mearound_" . $language_id;
                $notice->heading = __("notice.$sub_key1");
                $key = $notice->notify_type . '_' . $language_id;
                $notice->title = __("notice.$key");
                $notice->description = '';
            } else if ($notice->notify_type == Notice::RELOAD_COIN_REQUEST) {
                $reload_data = ReloadCoinRequest::find($notice->entity_id);
                $order_number_key = "order_number_" . $language_id;
                $coin_amount_key = "coin_amount_" . $language_id;
                $key = $notice->notify_type . '_' . $language_id;
                $notice->heading = __("notice.$key");
                $notice->title = __("notice.$order_number_key") . " " . $notice->title;
                $notice->sub_title = __("notice.$coin_amount_key") . " " . $notice->sub_title;
                $notice->description = '';
                $notice->reload_data = $reload_data;
            } else if ($notice->notify_type == Notice::RELOAD_COIN_REQUEST_ACCEPTED) {
                $reload_data = ReloadCoinRequest::find($notice->entity_id);
                $key = $notice->notify_type . '_' . $language_id;
                $notice->heading = __("notice.$key");
                $notice->title = "+" . $notice->title . " coins";
                $notice->description = '';
                $notice->reload_data = $reload_data;
            } else if ($notice->notify_type == Notice::RELOAD_COIN_REQUEST_REJECTED) {
                $reload_data = ReloadCoinRequest::find($notice->entity_id);
                $remittance_amount_key = "remittance_amount_" . $language_id;
                $coin_amount_key = "coin_amount_" . $language_id;
                $key = $notice->notify_type . '_' . $language_id;
                $notice->heading = __("notice.$key");
                $notice->title = __("notice.$remittance_amount_key");
                $notice->description = '';
                $notice->reload_data = $reload_data;
            } else if ($notice->notify_type == Notice::BECAME_BUSINESS_USER || $notice->notify_type == Notice::UPLOAD_SHOP_POST || $notice->notify_type == Notice::UPLOAD_COMMUNITY_POST || $notice->notify_type == Notice::REVIEW_SHOP_POST || $notice->notify_type == Notice::REVIEW_HOSPITAL_POST || $notice->notify_type == Notice::LIKE_SHOP_POST || $notice->notify_type == Notice::COMMENT_ON_COMMUNITY_POST || $notice->notify_type == Notice::NEW_CARD_ACQUIRED) {
                $key = $notice->notify_type . '_' . $language_id;
                $notice->heading = __("notice.$key");
                $notice->description = '';
            } else if ($notice->notify_type == Notice::CONNECTING_FIRST_TIME_IN_DAY) {
                $key = $notice->notify_type . '_' . $language_id;
                $notice->heading = __("notice.$key");
                $notice->description = '';

                if ($notice->sub_title) {
                    $nextLevel = $notice->sub_title;
                } else {
                    $userDetails = DB::table('users_detail')->where('user_id', $notice->to_user_id)->first();
                    $nextLevel = getUserNextAwailLevel($notice->to_user_id, $userDetails->level);
                }

                $next_level_key = "language_$language_id.next_level_card";
                $next_level_msg = __("messages.$next_level_key", ['level' => $nextLevel]);
                $notice->title = $notice->title;
                $notice->sub_title = $next_level_msg;
            } else if ($notice->notify_type == Notice::DEAD_CARD) {
                $dead_card_key = "language_$language_id.dead_card";
                $dead_card_msg = __("messages.$dead_card_key");
                $notice->heading = $dead_card_msg;

                $notice->title = '';
                $notice->description = '';
                $notice->sub_title = '';
            } else if ($notice->notify_type == Notice::USER_MISSED_CARD) {

                if ($notice->sub_title) {
                    $next_level_key = "language_$language_id.user_missed_card";
                    $notice->heading = __("messages.$next_level_key", ['dayCount' => $notice->sub_title]);
                } else {
                    $notice->heading = $notice->title;
                }
                $notice->title = '';
                $notice->description = '';
                $notice->sub_title = '';
            } else if ($notice->notify_type == Notice::LOVE_REFERRAL) {

                /*  if ($notice->sub_title) {
                    $notice->title = __("messages.language_$language_id.subscriber", ['name' => $notice->sub_title]);
                } else {
                    $notice->title = '';
                }
                */
                if(empty($notice->entity_id)){
                    $notice->title = $notice->sub_title;
                    $love_notice_key = "language_$language_id.give_referral_exp_old";
                    $notice->heading = __("messages.$love_notice_key");
                }else{
                    $notice->title = "+10 Love, +1 Starbuck stamp({$notice->entity_id}/3)";
                    $love_notice_key = "language_$language_id.give_referral_exp";
                    $notice->heading = __("messages.$love_notice_key",['username' => $notice->sub_title]);
                }
                $notice->description = '';
                $notice->sub_title = '';
            } else if ($notice->notify_type == Notice::LEVEL_UP || $notice->notify_type == Notice::LIKE_COMMUNITY_OR_REVIEW_POST) {
                $key = $notice->notify_type . '_' . $language_id;
                $notice->heading = __("notice.$key");
                $notice->description = '';



                if ($notice->sub_title) {
                    $nextLevel = $notice->sub_title;
                } else {
                    $userDetails = DB::table('users_detail')->where('user_id', $notice->to_user_id)->first();
                    $nextLevel = getUserNextAwailLevel($notice->to_user_id, $userDetails->level);
                }

                $next_level_key = "language_$language_id.next_level_card";
                $next_level_msg = __("messages.$next_level_key", ['level' => $nextLevel]);
                $notice->title = $notice->title;
                $notice->sub_title = $next_level_msg;
            } else if ($notice->notify_type == Notice::SNS_REWARD) {
                $sub_key1 = "mearound_" . $language_id;
                $notice->heading = __("notice.$sub_key1");
                $key = $notice->notify_type . '_' . $language_id;
                $sub_key = "rewarded_coin_" . $language_id;
                $notice->description = $notice->sub_title . " coins left";;
                $notice->sub_title = __("notice.$sub_key") . " +" . $notice->title;
                $notice->title = __("notice.$key");
            } else if ($notice->notify_type == Notice::SNS_PENALTY || $notice->notify_type == Notice::SNS_REJECT) {
                $sub_key1 = "mearound_" . $language_id;
                $notice->heading = __("notice.$sub_key1");
                $key = $notice->notify_type . '_' . $language_id;
                $notice->title = __("notice.$key");
                $notice->description = '';
            } else if ($notice->notify_type == Notice::WRITE_REVIEW) {
                $key = $notice->notify_type . '_' . $language_id;
                //$notice->title = $community ? $community->title : '';
                $notice->heading = __("notice.$key", ['name' => $notice->user_name]);
                $notice->description = '';
            } else if (in_array($notice->notify_type, [Notice::BECAME_MANAGER, Notice::BECAME_PRESIDENT, Notice::JOIN_ASSOCIATION])) {

                $key = "language_$language_id." . $notice->notify_type;

                //$notice->heading = $notice->title;
                $notice->heading = __("messages.$key", ['name' => $notice->user_name]);
                $notice->title = $notice->sub_title;
                $notice->sub_title = '';
                $notice->description = '';
            } elseif ($notice->notify_type == Notice::ASSOCIATION_COMMUNITY_COMMENT || $notice->notify_type == Notice::ASSOCIATION_COMMUNITY_COMMENT_REPLY) {
                $community = AssociationCommunity::find($notice->entity_id);

                $notify_type = ($notice->notify_type == Notice::ASSOCIATION_COMMUNITY_COMMENT) ? Notice::COMMUNITY_POST_COMMENT  : Notice::COMMUNITY_REPLY_COMMENT;
                $key = $notify_type . '_' . $language_id;
                $notice->heading = __("notice.$key", ['username' => $notice->user_name]);
                $notice->title = $community ? $community->title : '';
                $notice->community_id = $community ? $community->id : '';
                $notice->description = '';
            } elseif ($notice->notify_type == Notice::ASSOCIATION_COMMUNITY_POST) {
                $community = AssociationCommunity::find($notice->entity_id);

                $notify_type = Notice::ASSOCIATION_COMMUNITY_POST;
                $key = $notify_type . '_' . $language_id;
                $association_name = '';
                if ($community) {
                    $association_name = $community->association->association_name;
                }
                $notice->heading = __("notice.$key", ['association_name' => $association_name]);
            } elseif ($notice->notify_type == Notice::FOLLOWED_BUSINESS) {

                $businessKey = Notice::FOLLOWED_BUSINESS . '_' . $language_id;
                $businessFormat = __("notice.$businessKey", ['name' => $notice->user_name]);

                $notice->heading = $businessFormat;

                $shop_count = UserEntityRelation::where('entity_type_id', EntityTypes::SHOP)->where('user_id', $notice->user_id)->count();
                $hospital_count = UserEntityRelation::where('entity_type_id', EntityTypes::HOSPITAL)->where('user_id', $notice->user_id)->count();

                if ($shop_count) {
                    $shops = DB::table('shops')->where('user_id', $notice->user_id)->whereNull('deleted_at')->select('main_name', 'shop_name')->get();

                    $notice->is_shop_user = true;
                    $notice->all_shops = collect($shops)->map(function ($item) {
                        $mainName = ($item->main_name) ? ' / ' . $item->main_name : '';
                        return  $item->shop_name . $mainName;
                    });
                } elseif ($hospital_count) {
                    $notice->is_shop_user = false;
                    $user_entity_relation = UserEntityRelation::where('user_id', $notice->user_id)
                        ->where('entity_type_id', EntityTypes::HOSPITAL)
                        ->first();
                    $hospitalData = DB::table('hospitals')->where('id', $user_entity_relation->entity_id)->whereNull('deleted_at')->select('main_name')->get();

                    $notice->all_shops = collect($hospitalData)->map(function ($item) {
                        return  $item->main_name;
                    });
                }
            } elseif ($notice->notify_type == Notice::SELL_CARD_SUCCESS) {
                $title_msg = __("messages.language_$language_id.process_card");
                $notice->heading = $title_msg;
                $notice->description = '';
            } elseif ($notice->notify_type == Notice::SELL_DEFAULT_CARD_SUCCESS) {
                $title_msg = __("messages.language_$language_id.process_default_card");
                $notice->heading = $title_msg;
                $notice->description = '';
            } elseif ($notice->notify_type == Notice::SELL_CARD_REJECT) {
                $title_msg = __("messages.language_$language_id.reject_card");
                $notice->heading = $title_msg;
                $notice->description = '';
            } elseif ($notice->notify_type == Notice::GIVE_REFERRAL_EXP) {
                $title_msg = __("messages.language_$language_id.give_referral_exp");
                $notice->heading = $title_msg;

                if ($notice->sub_title) {
                    $nextLevel = $notice->sub_title;
                } else {
                    $userDetails = DB::table('users_detail')->where('user_id', $notice->to_user_id)->first();
                    $nextLevel = getUserNextAwailLevel($notice->to_user_id, $userDetails->level);
                }

                $next_level_key = "language_$language_id.next_level_card";
                $next_level_msg = __("messages.$next_level_key", ['level' => $nextLevel]);
                $notice->title = $notice->title;
                $notice->sub_title = $next_level_msg;


                /*
                $subtitle = $notice->sub_title;
                $subtitlearray = explode("(",$subtitle);


                $notice->sub_title = '';
                $notice->description = '';

                if(!empty($subtitlearray)){
                    $notice->title = $subtitlearray[0] ?? '';
                    $notice->sub_title = isset($subtitlearray[1]) ? "(".$subtitlearray[1] : '';
                }else{
                    $notice->title = $notice->sub_title;
                } */
            }elseif(in_array($notice->notify_type,['call_button','chat_button','book_button'])){
                $key = "language_$language_id." . $notice->notify_type;
                $notice->heading = __("messages.$key");
                $notice->title = $notice->sub_title;
                $notice->sub_title = '';
                $notice->description = '';
            } elseif ($notice->notify_type == Notice::GIFTICON) {
                $key = Notice::GIFTICON.'_'.$language_id;
                $title_msg = __("notice.$key");
                $format = __("notice.body_$key");
                $notice->heading = $title_msg;
                $notice->title = $format;
                $notice->sub_title = '';
                $notice->description = '';
            }
        }

        return $notices;
    }

    public function checkUser(Request $request)
    {
        $user = Auth::user();
        $inputs = $request->all();
        try {
            Log::info('Start code for check user');
            if ($user) {
                $inputs = $request->all();
                // $validation = $this->messageValidator->validateCheckUser($inputs);
                // if ($validation->fails()) {
                //     Log::info('End code for check user');
                //     return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
                // }
                $validation = Validator::make($request->all(), [
                    'user_id' => 'required',
                ], [], [
                    'user_id' => 'User Id',
                ]);

                if ($validation->fails()) {
                    Log::info('End code for get business messages');
                    return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
                }

                $id = $inputs['user_id'];
                $language_id = !empty($inputs['language_id']) ? $inputs['language_id'] : 4;
                $inquiry = ActivityLog::where('user_id', $id)->where('request_booking_status_id', RequestBookingStatus::TALK)->count();
                $book = ActivityLog::where('user_id', $id)->where('request_booking_status_id', RequestBookingStatus::BOOK)->count();
                $visit = ActivityLog::where('user_id', $id)->where('request_booking_status_id', RequestBookingStatus::VISIT)->count();
                $complete = ActivityLog::where('user_id', $id)->where('request_booking_status_id', RequestBookingStatus::COMPLETE)->count();
                $noshow = ActivityLog::where('user_id', $id)->where('request_booking_status_id', RequestBookingStatus::NOSHOW)->count();

                $user_detail = UserDetail::where('user_id', $id)->first();
                $user_info = User::where('id', $id)->first();

                $key = "language_$language_id.$user_detail->gender";
                $user_data = [
                    'user_name' => $user_detail ? $user_detail->name : '',
                    'user_avatar' => $user_detail ? $user_detail->avatar : '',
                    'language_id' => $user_detail ? $user_detail->language_id : '',
                    'language_name' => $user_detail ? $user_detail->language_name : '',
                    'gender' => $user_detail ? __("messages.$key") : '',
                    'inquiry' => $inquiry,
                    'book' => $book,
                    'visit' => $visit,
                    'complete' => $complete,
                    'noshow' => $noshow,
                    'is_character_as_profile' => $user_detail ? $user_detail->is_character_as_profile : 1,
                    'user_applied_card' => getUserAppliedCard($id)
                ];

                Log::info('End code for the check user');
                return $this->sendSuccessResponse(Lang::get('messages.messages.check-user-success'), 200, compact('user_data'));
            } else {
                Log::info('End code for check user');
                return $this->sendSuccessResponse(Lang::get('messages.user.token_expired'), 401);
            }
        } catch (\Exception $e) {
            print_r($e->getMessage());
            die;
            Log::info('Exception in check user');
            Log::info($e);
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function inquiryUserList(Request $request)
    {
        $user = Auth::user();
        $inputs = $request->all();
        try {
            Log::info('Start code for get user inquiry data');
            if ($user) {
                $validation = $this->messageValidator->validateGetInquiryList($inputs);
                if ($validation->fails()) {
                    Log::info('End code for get user inquiry data');
                    return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
                }

                $inquiry_data_query = '';

                if ($inputs['entity_type_id'] == EntityTypes::SHOP) {
                    $inquiry_data_query = Shop::join('activity_log', 'shops.id', 'activity_log.entity_id')
                        ->join('category', function ($join) {
                            $join->on('shops.category_id', '=', 'category.id')
                                ->whereNull('category.deleted_at');
                        })
                        ->select('shops.*');
                } else {
                    $inquiry_data_query = Post::join('activity_log', 'posts.id', 'activity_log.entity_id')->select('posts.*');
                }

                if ($inputs['booking_status_id'] != RequestBookingStatus::COMPLETE) {
                    $inquiry_data_query = $inquiry_data_query->groupBy('id');
                }
                $inquiry_data = $inquiry_data_query->where('activity_log.user_id', $user->id)
                    ->where('activity_log.request_booking_status_id', $inputs['booking_status_id'])
                    ->where('activity_log.entity_type_id', $inputs['entity_type_id'])
                    ->paginate(config('constant.pagination_count'));

                Log::info('End code for the get user inquiry data');
                return $this->sendSuccessResponse(Lang::get('messages.messages.check-user-success'), 200, compact('inquiry_data'));
            } else {
                Log::info('End code for get user inquiry data');
                return $this->sendSuccessResponse(Lang::get('messages.user.token_expired'), 401);
            }
        } catch (\Exception $e) {
            Log::info('Exception in get user inquiry data');
            Log::info($e);
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function deleteNotices(Request $request)
    {

        $user = Auth::user();
        try {
            $inputs = $request->all();
            $validation = Validator::make($request->all(), [
                'latitude' => 'required',
                'longitude' => 'required',
                'id' => 'required'
            ], [], [
                'latitude' => 'Latitude',
                'longitude' => 'Longitude',
                'id' => 'ID',
            ]);

            if ($validation->fails()) {
                Log::info('End code for get counselling messages');
                return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
            }

            Notice::where('id', $inputs['id'])->delete();
            $language_id = $request->has('language_id') ? $inputs['language_id'] : 4;
            $notices = $this->getNoticeData($inputs['latitude'], $inputs['longitude'], $language_id);
            $user_details = ['id' => $user->id, 'status_id' => $user->status_id, 'chat_status' => $user->chat_status];

            return $this->sendSuccessResponse(Lang::get('messages.messages.delete-notice'), 200, compact('notices', 'user_details'));
        } catch (\Exception $e) {

            print_r($e->getMessage());
            die;
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function getAdminMessage(Request $request)
    {
        $user = Auth::user();
        $inputs = $request->all();
        try {
            if($user->is_admin_access != 1){
                return $this->sendSuccessResponse(Lang::get('messages.shop.not-admin'), 401);
            }
            $language_id = isset($inputs['language_id']) ? $inputs['language_id'] : 4;
            if (isset($inputs['latitude']) && isset($inputs['longitude'])) {
                $main_country = getCountryFromLatLong($inputs['latitude'], $inputs['longitude']);
                $timezone = get_nearest_timezone($inputs['latitude'], $inputs['longitude'], $main_country);
            } else {
                $timezone = '';
            }
            $req_timezone = isset($inputs['timezone']) ? $inputs['timezone'] : $timezone;

            $loginID = $user->id;
            $user_id = 0;
          //  $concatQuery = 'CONCAT(admin_messages.from_user, "_", admin_messages.to_user)';
            $concatQuery = 'CASE
                                WHEN admin_messages.from_user = 0 THEN CONCAT(admin_messages.from_user, "_", admin_messages.to_user)
                                ELSE CONCAT(admin_messages.to_user, "_", admin_messages.from_user)
                            END';

            $chatQuery = AdminMessage::whereRaw("admin_messages.type='text' and (admin_messages.from_user = " . $user_id . " OR admin_messages.to_user = " . $user_id . ") and (admin_messages.from_user != '".$loginID."' AND admin_messages.to_user != '".$loginID."')")
                ->select(DB::raw('max(admin_messages.id) as message_id'))
                ->selectRaw("{$concatQuery} AS uniqe_records")
                ->groupBy('uniqe_records')
              //  ->orderBy('admin_messages.created_at','DESC')
               // ->get();
                ->pluck('message_id');
            $resdata = [];
            $data = AdminMessage::whereIn('admin_messages.id',$chatQuery)
                ->leftjoin('admin_chat_pin_details', function ($join) use ($user) {
                    $join->on('admin_chat_pin_details.chat_user_id', '=', 'admin_messages.from_user')
                    ->orOn('admin_chat_pin_details.chat_user_id', '=', 'admin_messages.to_user')
                    ->where('admin_chat_pin_details.admin_id',$user->id);
                })
                ->leftjoin('admin_chat_notification_details', function ($join) use ($user) {
                    $join->on('admin_chat_notification_details.chat_user_id', '=', 'admin_messages.from_user')
                        ->orOn('admin_chat_notification_details.chat_user_id', '=', 'admin_messages.to_user')
                        ->where('admin_chat_notification_details.admin_id',$user->id);
                })
                //->orderBy('admin_chat_pin_details.is_pin','DESC')
                //->orderBy('admin_chat_pin_details.created_at','DESC')
                ->orderByRaw('CASE
                    WHEN admin_chat_pin_details.is_pin = 1 THEN `admin_chat_pin_details`.`updated_at`
                    ELSE `admin_messages`.`updated_at`
                    END DESC'
                )
                //->orderBy('admin_messages.created_at','DESC')
                ->select('admin_messages.*',DB::raw('IFNULL(admin_chat_pin_details.is_pin, 0) as is_pin'),DB::raw('IFNULL(admin_chat_notification_details.is_receive, 1) as is_notification_receive'))
                ->paginate(config('constant.pagination_count'), "*", "admin_chat_list_page");

            $updatedItems = $data->getCollection();
            $updatedItems = $updatedItems->map(function ($item) use($language_id,$timezone,$req_timezone){
                $item->from_user_id = $item->from_user;
                //$item->to_user_id = $item->to_user;

                if(!empty($item->to_user)){
                    $userID = $item->to_user;
                }else{
                    $userID = $item->from_user;
                }
                $item->to_user_id = $userID;
                $shopData = DB::table('shops')->where('user_id',$userID)->whereNull('deleted_at')->first();
                $userData = DB::table('users_detail')->where('user_id',$userID)->whereNull('deleted_at')->first();
                if($shopData){
                    $shopImage = ShopImages::where('shop_id', $shopData->id)->where('shop_image_type',ShopImagesTypes::WORKPLACE)->first();

                    $item->image = $shopImage->image ?? asset('img/avatar/avatar-1.png');
                    //$item->main_name = $shopData->main_name ?? '';
                    $displayName = [];
                    if(!empty($userData) && !empty($userData->name)){
                        $displayName[] = $userData->name;
                    }
                    if(!empty($shopData) && !empty($shopData->main_name)){
                        $displayName[] = $shopData->main_name;
                    }
                    if(!empty($shopData) && !empty($shopData->shop_name)){
                        $displayName[] = $shopData->shop_name;
                    }

                    $item->main_name = implode(' / ',$displayName);
                   // $item->main_name = (!empty($userData) && !empty($userData->name)) ? $userData->name : ((!empty($shopData) && !empty($shopData->main_name))  ? $shopData->main_name : "");
                }else{
                    $userDetail = DB::table('users_detail')->where('user_id',$userID)->first();
                    $item->image = asset('img/avatar/avatar-1.png');
                    $item->main_name = (!empty($userDetail) && !empty($userDetail->name)) ? $userDetail->name : "";
                }

                $item->time_difference = $item ? timeAgo($item->created_at, $language_id, $req_timezone)  : "";

                $count = DB::table('admin_messages')->where('is_read', 0)
                    ->where('to_user', 0)
                    ->where(function ($q) use ($item) {
                        $q->where('from_user', $item['from_user'])
                            ->orWhere('from_user', $item['to_user']);
                    })
                    ->count();
                $item->count = $count;

                return $item;
            });
            $data->setCollection($updatedItems);

            $resdata['counselling_chat_list'] = $data;

            AdminMessageNotificationStatus::where('notification_status', 1)
                ->where('user_id', 0)
                ->update(['notification_status' => 0]);

            return $this->sendSuccessResponse(Lang::get('messages.messages.success'), 200, $resdata);
        } catch (\Throwable $th) {
            Log::info($th);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function pinUserChat(Request $request)
    {
        $inputs = $request->all();
        $user = Auth::user();
        try {
            $chat_user_id = $inputs['user_id'];

            $isPinned = AdminChatPinDetail::where('is_pin' , 1)->where('admin_id' , $user->id)->where('chat_user_id' , $chat_user_id)->first();

            if(!empty($isPinned)){
                $isPinned->update(['is_pin' => 0]);
            }else{
                AdminChatPinDetail::updateOrCreate(
                    [
                        'admin_id' => $user->id,
                        'chat_user_id' => $chat_user_id,
                    ],[
                        'is_pin' => 1
                    ]
                );
            }

            return $this->sendSuccessResponse(Lang::get('messages.messages.success'), 200);
        } catch (\Throwable $th) {
            Log::info($th);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function notificationUserChat(Request $request)
    {
        $inputs = $request->all();
        $user = Auth::user();
        try {
            $chat_user_id = $inputs['user_id'];

            $isPinned = AdminChatNotificationDetail::where('is_receive' , 1)->where('admin_id' , $user->id)->where('chat_user_id' , $chat_user_id)->first();

            if(!empty($isPinned)){
                $isPinned->update(['is_receive' => 0]);
            }else{
                AdminChatNotificationDetail::updateOrCreate(
                    [
                        'admin_id' => $user->id,
                        'chat_user_id' => $chat_user_id,
                    ],[
                        'is_receive' => 1
                    ]
                );
            }

            return $this->sendSuccessResponse(Lang::get('messages.messages.success'), 200);
        } catch (\Throwable $th) {
            Log::info($th);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function allChatList(Request $request)
    {
        $user = Auth::user();
        $inputs = $request->all();
        try {
            Log::info('Start code for get all chat list');
            if ($user) {
                $validation = Validator::make($request->all(), [
                    'latitude' => 'required',
                    'longitude' => 'required',
                ], [], [
                    'latitude' => 'Latitude',
                    'longitude' => 'Longitude',
                ]);

                if ($validation->fails()) {
                    return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
                }

                $inputs['limit'] = 5;
                $inputs['allChatList'] = true;
                $data['chat'] = $this->getCounsellingData($user, $inputs); //Chat tab data
                $chatDetails = $this->getBusinessData($user, $inputs);
                $data['business_chat'] = ($user->status_id != Status::ACTIVE || $user->chat_status == 0) ? [] : $chatDetails; //Business chat tab data
                Log::info('End code for get all chat list');
                return $this->sendSuccessResponse(Lang::get('messages.messages.success'), 200, $data);
            } else {
                Log::info('End code for get all chat list');
                return $this->sendSuccessResponse(Lang::get('messages.user.token_expired'), 401);
            }
        } catch (\Exception $e) {
            Log::info('Exception in get all chat list');
            Log::info($e);
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function shareGroupChat(Request $request)
    {
        $user = Auth::user();
        $inputs = $request->all();
//        dd(round(Carbon::now()->getPreciseTimestamp(3)));
//        dd(strtotime("now"), time()*1000, (int)floor(microtime(true) * 1000));
        try {
            DB::beginTransaction();

            if ($user) {
                $validation = Validator::make($request->all(), [
                    'from_user_id' => 'required',
                    'type' => 'required',
                    'message' => 'required',
                    'country' => 'required',
                ]);

                if ($validation->fails()) {
                    return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
                }

                $message_id = DB::table('group_messages')->insertGetId([
                    "from_user" => $request->from_user_id,
                    "type" => $request->type,
                    "message" => $request->message,
                    "country" => $request->country,
                    "created_at" => (int)floor(microtime(true) * 1000),
                ]);

                $message_data = DB::table('group_messages')->where('id', $message_id)->first();
                $message_data->from_user_id = (int)$request->from_user_id;
                $message_data->message = ($message_data->type=="file") ? url('chat-root/'.$message_data->message) : $message_data->message;

                $appliedCard = DefaultCardsRives::join('user_cards', 'user_cards.default_cards_riv_id', 'default_cards_rives.id')
                    ->select('default_cards_rives.*','user_cards.id as user_card_id','user_cards.active_level','user_cards.card_level_status')
                    ->where(['user_cards.user_id' => $request->from_user_id,'user_cards.is_applied' => 1])
                    ->first();
                if (!$appliedCard){
                    $message_data->background_thumbnail_url = "";
                    $message_data->character_thumbnail_url = "";
                }
                else {
                    if ($appliedCard->active_level == CardLevel::DEFAULT_LEVEL) {
                        $message_data->background_thumbnail_url = $appliedCard->background_thumbnail_url ?? "";
                        if($appliedCard->card_level_status == UserCards::NORMAL_STATUS) {
                            $message_data->character_thumbnail_url = $appliedCard->character_thumbnail_url ?? '';
                        }else{
                            $defaultCardStatus = $appliedCard->cardLevelStatusThumb()->where('card_level_status',$appliedCard->card_level_status)->where('card_level_id',$appliedCard->active_level)->first();
                            $message_data->character_thumbnail_url = $defaultCardStatus->character_thumb_url ?? '';
                        }
                    }
                    else {
                        $cardLevelData = UserCardLevel::where('user_card_id',$appliedCard->user_card_id)->where('card_level',$appliedCard->active_level)->first();
                        $levelCard = $appliedCard->cardLevels()->firstWhere('card_level', $appliedCard->active_level);
                        if ($levelCard) {
                            $card_level_status = $cardLevelData->card_level_status ?? UserCards::NORMAL_STATUS;
                            $message_data->background_thumbnail_url = $levelCard->background_thumbnail_url ?? '';
                            if($card_level_status == UserCards::NORMAL_STATUS) {
                                $message_data->character_thumbnail_url = $levelCard->character_thumbnail_url ?? '';
                            }else{
                                $defaultCardStatus = $appliedCard->cardLevelStatusThumb()->where('card_level_status',$card_level_status)->where('card_level_id',$appliedCard->active_level)->first();
                                $message_data->character_thumbnail_url = $defaultCardStatus->character_thumb_url ?? '';
                            }
                        }
                    }
                }

                $user_data = UserDetail::where('user_id',$request->from_user_id)->select(['name','avatar','is_character_as_profile'])->first();
                $message_data->name = !empty($user_data) ? $user_data->name : "";
                $message_data->avatar = !empty($user_data) ? $user_data->avatar : "";
                $message_data->is_character_as_profile = !empty($user_data) ? $user_data->is_character_as_profile : "";

                $receiver_user_ids = NodeUserCountry::where('country',$request->country)->where('from_user_id', '!=' , $request->from_user_id)->pluck('from_user_id')->toArray();
                $devices = UserDevices::whereIn('user_id', $receiver_user_ids)->pluck('device_token')->toArray();
                $notificationData = $message_data;
                if (count($devices) > 0) {
                    $result = $this->sentPushNotification($devices, "Group Chat", "Group Chat Message", $notificationData);
                }
                DB::commit();
                return $this->sendSuccessResponse(Lang::get('messages.messages.message-sent'), 200, $message_data);
            } else {
                return $this->sendSuccessResponse(Lang::get('messages.user.token_expired'), 401);
            }
        } catch (\Exception $e) {
            Log::info($e);
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function shareChat(Request $request)
    {
        $user = Auth::user();
        $inputs = $request->all();
        try {
            DB::beginTransaction();

            if ($user) {
                $validation = Validator::make($request->all(), [
                    'from_user_id' => 'required',
                    'to_user_id' => 'required',
                    'type' => 'required',
                    'message' => 'required',
                    'entity_type_id' => 'required',
                    'entity_id' => 'required',
                ]);

                if ($validation->fails()) {
                    return $this->sendCustomErrorMessage($validation->errors()->toArray(), 422);
                }

                $message_id = Message::insertGetId([
                    "entity_type_id" => $inputs['entity_type_id'],
                    "entity_id" => $inputs['entity_id'],
                    "from_user_id" => $inputs['from_user_id'],
                    "to_user_id" => $inputs['to_user_id'],
                    "type" => $inputs['type'],
                    "message" => $inputs['message'],
                    "status" => 0,
                    "created_at" => (int)floor(microtime(true) * 1000),
                ]);

                $message_data = Message::where('id', $message_id)->first();
                $message_data->time = $message_data->created_at;

                $devices = UserDevices::whereIn('user_id', [$message_data->to_user_id])->pluck('device_token')->toArray();
                $notificationData = $message_data->toArray();
                if (count($devices) > 0) {
                    $result = $this->sentPushNotification($devices, "Chat", "Chat Message", $notificationData);
                }
                DB::commit();
                return $this->sendSuccessResponse(Lang::get('messages.messages.message-sent'), 200, $message_data);
            } else {
                return $this->sendSuccessResponse(Lang::get('messages.user.token_expired'), 401);
            }
        } catch (\Exception $e) {
            Log::info($e);
            DB::rollBack();
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

}
