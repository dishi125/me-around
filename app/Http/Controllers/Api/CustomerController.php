<?php

namespace App\Http\Controllers\Api;

use Auth;
use Carbon\Carbon;
use App\Models\Post;
use App\Models\Shop;
use App\Models\Category;
use App\Models\EntityTypes;
use App\Models\CustomerList;
use Illuminate\Http\Request;
use App\Models\CompletedCustomer;
use App\Models\RequestedCustomer;
use Faker\Provider\ar_JO\Company;
use App\Models\CustomerAttachment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Models\RequestBookingStatus;
use Illuminate\Support\Facades\Lang;
use App\Models\CompleteCustomerDetails;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $inputs = $request->all();
        try {
            $search = $inputs['search'] ?? '';
            $customerQuery = CustomerList::where('is_deleted', 0)->where('user_id', $user->id);

            if (!empty($search)) {
                $customerQuery = $customerQuery->where(function ($q) use ($search) {
                    $q->where('customer_name', 'LIKE', "%{$search}%")
                        ->orWhere('customer_phone', 'LIKE', "%{$search}%");
                });
            }

            $customerList = $customerQuery->paginate(config('constant.pagination_count'), "*", "customer_list");

            return $this->sendSuccessResponse(Lang::get('messages.general.success'), 200, $customerList);
        } catch (\Exception $th) {
            Log::info($th);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function store(Request $request)
    {
        $inputs = $request->all();
        $user = Auth::user();
        try {
            $validator = Validator::make($request->all(), [
                'customer_name' => 'required',
                'customer_phone' => 'required',
            ]);
            if ($validator->fails()) {
                return $this->sendCustomErrorMessage($validator->errors()->toArray(), 422);
            }

            $customer_name = $inputs['customer_name'] ?? '';
            $customer_phone = $inputs['customer_phone'] ?? '';

            if (isset($inputs['id']) && !empty($inputs['id'])) {
                CustomerList::where('id', $inputs['id'])->update([
                    'customer_name' => $customer_name,
                    'customer_phone' => $customer_phone,
                ]);
                $customer = CustomerList::where('id', $inputs['id'])->first();

                return $this->sendSuccessResponse("Customer" . trans("messages.update-success"), 200, $customer);
            } else {
                $customer = CustomerList::create([
                    'user_id' => $user->id,
                    'customer_name' => $customer_name,
                    'customer_phone' => $customer_phone,
                ]);
                return $this->sendSuccessResponse("Customer" . trans("messages.insert-success"), 200, $customer);
            }
            //$customerList = CustomerList::where('user_id',$user->id)->get();

        } catch (\Exception $th) {
            Log::info($th);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
            //throw $th;
        }
    }

    public function destroy($id)
    {
        $user = Auth::user();
        try {
            CustomerList::whereId($id)->where('user_id', $user->id)->update(['is_deleted' => 1]);
            return $this->sendSuccessResponse(Lang::get('messages.shop.delete-tools-meterial-detail'), 200);
        } catch (\Exception $th) {
            Log::info($th);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function destroyMultiple(Request $request)
    {
        $user = Auth::user();
        $inputs = $request->all();
        try {
            $validator = Validator::make($request->all(), [
                'ids' => 'required',
            ], [], [
                'ids' => 'IDs',
            ]);
            if ($validator->fails()) {
                return $this->sendCustomErrorMessage($validator->errors()->toArray(), 422);
            }

            $ids = $inputs['ids'];
            CustomerList::whereIn('id', $ids)->where('user_id', $user->id)->update(['is_deleted' => 1]);
            return $this->sendSuccessResponse(Lang::get('messages.shop.delete-tools-meterial-detail'), 200);
        } catch (\Exception $th) {
            Log::info($th);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function createBooking(Request $request)
    {
        $user = Auth::user();
        $inputs = $request->all();

        try {

            $validator = Validator::make($request->all(), [
                'customer_id' => 'required',
                'revenue' => 'nullable',
                'comment' => 'nullable',
                'latitude' => 'required',
                'longitude' => 'required',
                'entity_type_id' => 'required',
                'entity_id' => 'required',
                'date' => 'required|date|date_format:Y-m-d H:i:s',
            ], [], [
                'customer_id' => 'Customer',
                'revenue' => 'Revenue',
                'comment' => 'Comment',
                'latitude' => 'Latitude',
                'longitude' => 'Longitude',
                'entity_id' => 'Shop/Hospital',
                'entity_type_id' => 'Shop/Hospital',
                'date' => 'Date',
            ]);
            if ($validator->fails()) {
                return $this->sendCustomErrorMessage($validator->errors()->toArray(), 422);
            }

            $main_country = getCountryFromLatLong($inputs['latitude'], $inputs['longitude']);
            $timezone = get_nearest_timezone($inputs['latitude'], $inputs['longitude'], $main_country);
            $bookingDate = Carbon::createFromFormat('Y-m-d H:i:s', $inputs['date'], $timezone)->setTimezone('UTC');

            $memoStatus = 0;
            if (isset($inputs['type']) && !empty($inputs['type']) && $inputs['type'] == 'book') {
                /* if(Carbon::parse($bookingDate)->gt(Carbon::now())){
                    $bookingStatus =  RequestBookingStatus::BOOK;
                }else{
                    $bookingStatus =  RequestBookingStatus::VISIT;
                }   */
                $bookingStatus =  RequestBookingStatus::BOOK;
            } else {
                $memoStatus = 1;
                $bookingStatus =  RequestBookingStatus::COMPLETE;
            }

            $customer = CompleteCustomerDetails::create([
                'user_id' => $user->id,
                'customer_id' => $inputs['customer_id'],
                'revenue' => $inputs['revenue'] ?? 0,
                'comment' => $inputs['comment'] ?? null,
                'date' => $bookingDate,
                'entity_type_id' => $inputs['entity_type_id'] ?? null,
                'entity_id' => $inputs['entity_id'] ?? null,
                'status_id' => $bookingStatus,
                'memo_completed' => $memoStatus
            ]);

            if (isset($inputs['images']) && !empty($inputs['images'])) {
                $customerFolder = config('constant.customer_memo');
                foreach ($inputs['images'] as $imageFile) {
                    if (is_file($imageFile)) {
                        $mainImage = Storage::disk('s3')->putFile($customerFolder, $imageFile, 'public');
                        $fileName = basename($mainImage);
                        $image_url = $customerFolder . '/' . $fileName;

                        CustomerAttachment::create([
                            'entity_id' => $customer->id,
                            'type' => CustomerAttachment::OUTSIDE,
                            'image' => $image_url
                        ]);
                    }
                }
            }
            return $this->sendSuccessResponse("Customer " . trans("messages.insert-success"), 200, $customer);
        } catch (\Exception $th) {
            Log::info($th);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function getCustomerRevenue(Request $request)
    {
        $inputs = $request->all();
        $user = Auth::user();

        try {

            $validator = Validator::make($request->all(), [
                'customer_id' => 'required',
            ], [], [
                'customer_id' => 'Customer',
            ]);
            if ($validator->fails()) {
                return $this->sendCustomErrorMessage($validator->errors()->toArray(), 422);
            }

            $customer_id = $inputs['customer_id'];
            $timezone = $inputs['timezone'] ?? 'UTC';

            $customerData = CompleteCustomerDetails::where('customer_id', $customer_id)
                ->where('status_id', RequestBookingStatus::COMPLETE)
                ->orderBy('created_at', 'desc')
                ->with('images')
                ->paginate(config('constant.pagination_count'), "*", "revenue_data_page");

            collect($customerData->items())->map(function ($item, $key) use ($timezone) {
                $customer = DB::table('customer_lists')->where('id', $item->customer_id)->first();
                $item->user_name = $customer ? $customer->customer_name : '';

                if ($item->entity_type_id == EntityTypes::HOSPITAL) {
                    $post = Post::find($item->entity_id);
                    $item->category_logo = $post && !empty($post->thumbnail_url) && !empty($post->thumbnail_url->image) ? $post->thumbnail_url->image : '';
                } else if ($item->entity_type_id == EntityTypes::SHOP) {
                    $shopsData = DB::table('shops')->whereId($item->entity_id)->first();
                    $category = Category::find($shopsData->category_id);
                    $item->category_logo = $category->logo;
                } else {
                    $item->category_logo = '';
                }

                $item->customer_memo = $item->comment;
                $item->revenue = number_format($item->revenue, 0);
                //$item->display_booking_date = Carbon::parse($item->date)->format('Y/m/d H:i A');
                $item->display_booking_date = Carbon::createFromFormat('Y-m-d H:i:s', Carbon::parse($item->date), "UTC")->setTimezone($timezone)->format('Y/m/d H:i A');
                $item->date = Carbon::parse($item->date)->format('Y.m.d');
                return $item;
            });

            $data['revenue_data'] = $customerData;
            return $this->sendSuccessResponse(Lang::get('messages.request-service.get-revenue'), 200, $data);
        } catch (\Exception $th) {
            Log::info($th);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function editCustomerRevenue(Request $request)
    {
        $inputs = $request->all();
        try {
            $validator = Validator::make($request->all(), [
                'id' => 'required',
                'type' => 'required',
                'revenue' => 'required',
                'comment' => 'required',
            ]);
            if ($validator->fails()) {
                return $this->sendCustomErrorMessage($validator->errors()->toArray(), 422);
            }

            $timezone = $inputs['timezone'] ?? 'UTC';
            if ($inputs['type'] == 'static') {
                CompleteCustomerDetails::whereId($inputs['id'])->update([
                    'revenue' => $inputs['revenue'] ?? '',
                    'comment' => $inputs['comment'] ?? '',
                ]);

                if (isset($inputs['date']) && !empty($inputs['date'])) {
                    CompleteCustomerDetails::whereId($inputs['id'])->update([
                        'date' => Carbon::createFromFormat('Y-m-d H:i:s', $inputs['date'], $timezone)->setTimezone('UTC')
                    ]);
                }

                $imageUploadType = CustomerAttachment::OUTSIDE;
            } else if ($inputs['type'] == 'booked') {
                CompletedCustomer::whereId($inputs['id'])->update([
                    'revenue' => $inputs['revenue'] ?? '',
                    'customer_memo' => $inputs['comment'] ?? '',
                ]);

                if (isset($inputs['date']) && !empty($inputs['date'])) {
                    $customerData = CompletedCustomer::whereId($inputs['id'])->first();
                    CompletedCustomer::whereId($inputs['id'])->update([
                        'date' => Carbon::createFromFormat('Y-m-d H:i:s', $inputs['date'], $timezone)->setTimezone('UTC')
                    ]);

                    if ($customerData) {
                        RequestedCustomer::whereId($customerData->requested_customer_id)->update([
                            'booking_date' => Carbon::createFromFormat('Y-m-d H:i:s', $inputs['date'], $timezone)->setTimezone('UTC')
                        ]);
                    }
                }
                $imageUploadType = CustomerAttachment::INSIDE;
            }

            if (isset($inputs['images']) && !empty($inputs['images'])) {
                $customerFolder = config('constant.customer_memo');
                foreach ($inputs['images'] as $imageFile) {
                    if (is_file($imageFile)) {
                        $mainImage = Storage::disk('s3')->putFile($customerFolder, $imageFile, 'public');
                        $fileName = basename($mainImage);
                        $image_url = $customerFolder . '/' . $fileName;

                        CustomerAttachment::create([
                            'entity_id' => $inputs['id'],
                            'type' => $imageUploadType,
                            'image' => $image_url
                        ]);
                    }
                }
            }

            if (isset($inputs['deleted_image']) && !empty($inputs['deleted_image'])) {
                foreach ($inputs['deleted_image'] as $deleteImage) {
                    $image = DB::table('customer_attachments')->whereId($deleteImage)->where('type', $imageUploadType)->first();
                    if ($image) {
                        Storage::disk('s3')->delete($image->image);
                        CustomerAttachment::where('id', $image->id)->where('type', $imageUploadType)->delete();
                    }
                }
            }

            return $this->sendSuccessResponse("Customer Revenue" . trans("messages.update-success"), 200);
        } catch (\Exception $e) {
            Log::info($e);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function updateBooking(Request $request)
    {
        $inputs = $request->all();
        try {
            $validator = Validator::make($request->all(), [
                'id' => 'required',
                'latitude' => 'required',
                'longitude' => 'required',
            ]);
            if ($validator->fails()) {
                return $this->sendCustomErrorMessage($validator->errors()->toArray(), 422);
            }

            $updatedFields = ['revenue', 'comment', 'date', 'status_id', 'memo_completed'];

            $updatedData = [];
            foreach ($inputs as $key => $value) {
                if (in_array($key, $updatedFields)) {
                    if ($key == 'date') {
                        $main_country = getCountryFromLatLong($inputs['latitude'], $inputs['longitude']);
                        $timezone = get_nearest_timezone($inputs['latitude'], $inputs['longitude'], $main_country);
                        $bookingDate = Carbon::createFromFormat('Y-m-d H:i:s', $value, $timezone)->setTimezone('UTC');
                        $updatedData[$key] = $bookingDate;
                    } elseif($key == 'revenue') {
                        $updatedData[$key] = str_replace(",","",$value);
                    } else {
                        $updatedData[$key] = $value;
                    }
                }
            }

            if (!empty($updatedData) && !empty($inputs['id'])) {
                CompleteCustomerDetails::where('id', $inputs['id'])->update($updatedData);
            }

            $customer = CompleteCustomerDetails::where('id', $inputs['id'])->first();

            if (isset($inputs['images']) && !empty($inputs['images'])) {
                $customerFolder = config('constant.customer_memo');
                foreach ($inputs['images'] as $imageFile) {
                    if (is_file($imageFile)) {
                        $mainImage = Storage::disk('s3')->putFile($customerFolder, $imageFile, 'public');
                        $fileName = basename($mainImage);
                        $image_url = $customerFolder . '/' . $fileName;

                        CustomerAttachment::create([
                            'entity_id' => $customer->id,
                            'type' => CustomerAttachment::OUTSIDE,
                            'image' => $image_url
                        ]);
                    }
                }
            }

            return $this->sendSuccessResponse("Customer Booking" . trans("messages.updaet-success"), 200, $customer);
        } catch (\Exception $e) {
            Log::info($e);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function showCustomerProfile(Request $request)
    {
        $inputs = $request->all();
        try {
            $validator = Validator::make($request->all(), [
                'user_id' => 'required',
            ]);
            if ($validator->fails()) {
                return $this->sendCustomErrorMessage($validator->errors()->toArray(), 422);
            }

            $customer_id = $inputs['user_id'];
            $customerData = CustomerList::where('id', $customer_id)->first();


            if (empty($customerData)) {
                $user_data = (object)[];
                return $this->sendSuccessResponse(Lang::get('messages.messages.check-user-success'), 200, compact('user_data'));
            }
            $inquiry = $customerData->bookingDetails()->where('status_id', RequestBookingStatus::TALK)->count();
            $book = $customerData->bookingDetails()->where('status_id', RequestBookingStatus::BOOK)->count();
            $visit = $customerData->bookingDetails()->where('status_id', RequestBookingStatus::VISIT)->count();
            $complete = $customerData->bookingDetails()->where('status_id', RequestBookingStatus::COMPLETE)->count();
            $noshow = $customerData->bookingDetails()->where('status_id', RequestBookingStatus::NOSHOW)->count();

            $user_data = [
                'user_name' => $customerData->customer_name,
                'user_avatar' => '',
                'inquiry' => $inquiry,
                'book' => $book,
                'visit' => $visit,
                'complete' => $complete,
                'noshow' => $noshow,
            ];

            return $this->sendSuccessResponse(Lang::get('messages.messages.check-user-success'), 200, compact('user_data'));
        } catch (\Exception $e) {
            Log::info($e);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function import(Request $request)
    {
        $inputs = $request->all();
        $user = Auth::user();
        try {
            $validator = Validator::make($request->all(), [
                'contacts' => 'required',
                'contacts.*.customer_name' => 'required',
                'contacts.*.customer_phone' => 'required',
            ], [], [
                'contacts.*.customer_name' => "Customer Name",
                'contacts.*.customer_phone' => "Customer Phone"
            ]);
            if ($validator->fails()) {
                return $this->sendCustomErrorMessage($validator->errors()->toArray(), 422);
            }

            $contacts = $inputs['contacts'] ?? '';

            $customer = [];
            foreach ($contacts as $data) {
                $customer_name = $data['customer_name'] ?? '';
                $customer_phone = $data['customer_phone'] ?? '';

                $customer[] = CustomerList::updateOrCreate([
                    'user_id' => $user->id,
                    'customer_name' => $customer_name,
                    'customer_phone' => $customer_phone,
                    'is_deleted' => 0,
                ]);
            }

            return $this->sendSuccessResponse("Customer" . trans("messages.insert-success"), 200, $customer);
            //$customerList = CustomerList::where('user_id',$user->id)->get();

        } catch (\Exception $th) {
            Log::info($th);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
            //throw $th;
        }
    }

    public function showCompletedCustomerList(Request $request)
    {
        $inputs = $request->all();
        $user = Auth::user();
        try {
            $validator = Validator::make($request->all(), [
                'latitude' => 'required',
                'longitude' => 'required',
                //'start_date' => 'required',
                //'end_date' => 'required',
            ], [], [
                'latitude' => "Location",
                'longitude' => "Location",
            ]);

            if ($validator->fails()) {
                return $this->sendCustomErrorMessage($validator->errors()->toArray(), 422);
            }

            if (isset($inputs['latitude']) && isset($inputs['longitude'])) {
                $country = $inputs['country'] ?? 'KR';
                $timezone = get_nearest_timezone($inputs['latitude'], $inputs['longitude'], $country);
            } else {
                $timezone = '';
            }

            $usersShop = [];
            foreach ($user->entityType as $userEntity) {
                if ($userEntity->entity_type_id == EntityTypes::SHOP) {
                    $usersShop[] = $userEntity->entity_id;
                }
            }

            /* $startDate = $inputs['start_date'];
            $endDate = $inputs['end_date']; */

            /*  $outsideDates = CompleteCustomerDetails::where('entity_type_id', EntityTypes::SHOP)
                ->whereIn('entity_id', $usersShop)->min('date');

            $insideDates = RequestedCustomer::join('completed_customer', function ($join) {
                $join->on('requested_customer.id', '=', 'completed_customer.requested_customer_id')
                    ->whereNull('completed_customer.deleted_at');
            })
                ->where('requested_customer.entity_type_id', EntityTypes::SHOP)
                ->whereIn('requested_customer.entity_id', $usersShop)->min('requested_customer.booking_date'); */



            // TEST

            if ($usersShop) {


                $outsideCustomerQuery = CustomerAttachment::leftjoin('complete_customer_details', function ($join) {
                    $join->on('customer_attachments.entity_id', '=', 'complete_customer_details.id')
                        ->whereNull('complete_customer_details.deleted_at');
                })
                    ->where('customer_attachments.type', CustomerAttachment::OUTSIDE)
                    ->select(
                        DB::raw("GROUP_CONCAT(customer_attachments.id) AS 'ids'"),
                        DB::raw('DATE(complete_customer_details.date) as booking_date'),
                        'customer_attachments.type as type'
                    )
                    ->where('complete_customer_details.status_id', RequestBookingStatus::COMPLETE)
                    ->where('complete_customer_details.entity_type_id', EntityTypes::SHOP)
                    ->whereIn('complete_customer_details.entity_id', $usersShop)
                    ->groupBy(DB::raw("DATE_FORMAT(complete_customer_details.date, '%Y-%m-%d')"));

                // Outside End
                // Inside
                $insideCustomerQuery = CustomerAttachment::leftjoin('completed_customer', function ($join) {
                    $join->on('customer_attachments.entity_id', '=', 'completed_customer.id')
                        ->whereNull('completed_customer.deleted_at');
                })
                    ->leftjoin('requested_customer', function ($join) {
                        $join->on('completed_customer.requested_customer_id', '=', 'requested_customer.id');
                    })
                    ->where('customer_attachments.type', CustomerAttachment::INSIDE)
                    ->select(
                        DB::raw("GROUP_CONCAT(customer_attachments.id) AS 'ids'"),
                        DB::raw('DATE(requested_customer.booking_date) as booking_date'),
                        'customer_attachments.type as type'
                    )
                    ->where('requested_customer.entity_type_id', EntityTypes::SHOP)
                    ->whereIn('requested_customer.entity_id', $usersShop)
                    ->groupBy(DB::raw("DATE_FORMAT(requested_customer.booking_date, '%Y-%m-%d')"))
                    ->union($outsideCustomerQuery)
                    //->groupBy(DB::raw('Date(booking_date)'))
                    ->orderBy('booking_date', 'DESC');

                // TEST



                $queryData = $insideCustomerQuery->paginate(config('constant.days_count'), "*", "customer_list");

                $returnData['customers'] = $this->dateFilters($queryData);
            } else {
                $returnData['customers'] = (object)[];
            }

            //print_r($queryData); exit;
            return $this->sendSuccessResponse("Customer" . trans("messages.insert-success"), 200, $returnData);
        } catch (\Exception $th) {
            Log::info($th);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function showCompletedCustomerListDetail(Request $request)
    {
        $inputs = $request->all();
        $user = Auth::user();
        try {
            $validator = Validator::make($request->all(), [
                'latitude' => 'required',
                'longitude' => 'required',
                'date' => 'required',
            ], [], [
                'latitude' => "Location",
                'longitude' => "Location",
            ]);

            if ($validator->fails()) {
                return $this->sendCustomErrorMessage($validator->errors()->toArray(), 422);
            }

            $usersShop = [];
            foreach ($user->entityType as $userEntity) {
                if ($userEntity->entity_type_id == EntityTypes::SHOP) {
                    $usersShop[] = $userEntity->entity_id;
                }
            }

            if (isset($inputs['latitude']) && isset($inputs['longitude'])) {
                $country = $inputs['country'] ?? 'KR';
                $timezone = get_nearest_timezone($inputs['latitude'], $inputs['longitude'], $country);
            } else {
                $timezone = '';
            }

            $returnData = [];
            if ($usersShop) {

                $outsideQuery = CustomerAttachment::leftjoin('complete_customer_details', function ($join) {
                    $join->on('customer_attachments.entity_id', '=', 'complete_customer_details.id')
                        ->whereNull('complete_customer_details.deleted_at');
                })
                    ->leftjoin('shops', function ($join) {
                        $join->on('shops.id', '=', 'complete_customer_details.entity_id')
                            ->where('complete_customer_details.entity_type_id', EntityTypes::SHOP);
                    })
                    ->leftjoin('customer_lists', 'customer_lists.id', 'complete_customer_details.customer_id')
                    ->where('customer_attachments.type', CustomerAttachment::OUTSIDE)
                    ->select(
                        'customer_attachments.*',
                        'shops.main_name',
                        'shops.shop_name',
                        'complete_customer_details.customer_id',
                        'complete_customer_details.date as booking_date',
                        'complete_customer_details.entity_type_id as entity_type_id',
                        'complete_customer_details.entity_id as shop_id',
                        'customer_lists.customer_name as user_name'
                    )
                    ->where('complete_customer_details.status_id', RequestBookingStatus::COMPLETE)
                    ->where('complete_customer_details.entity_type_id', EntityTypes::SHOP)
                    ->whereIn('complete_customer_details.entity_id', $usersShop)
                    ->whereDate('complete_customer_details.date', $inputs['date']);


                $insideQuery = CustomerAttachment::leftjoin('completed_customer', function ($join) {
                    $join->on('customer_attachments.entity_id', '=', 'completed_customer.id')
                        ->whereNull('completed_customer.deleted_at');
                })
                    ->leftjoin('requested_customer', function ($join) {
                        $join->on('completed_customer.requested_customer_id', '=', 'requested_customer.id');
                    })
                    ->leftjoin('shops', function ($join) {
                        $join->on('shops.id', '=', 'requested_customer.entity_id')
                            ->where('requested_customer.entity_type_id', EntityTypes::SHOP);
                    })
                    ->leftjoin('users_detail', 'users_detail.user_id', 'requested_customer.user_id')
                    ->where('customer_attachments.type', CustomerAttachment::INSIDE)
                    ->select(
                        'customer_attachments.*',
                        'shops.main_name',
                        'shops.shop_name',
                        'requested_customer.user_id as customer_id',
                        'requested_customer.booking_date as booking_date',
                        'requested_customer.entity_type_id as entity_type_id',
                        'requested_customer.entity_id as shop_id',
                        'users_detail.name as user_name'
                    )
                    ->where('requested_customer.entity_type_id', EntityTypes::SHOP)
                    ->whereIn('requested_customer.entity_id', $usersShop)
                    ->whereDate('requested_customer.booking_date', $inputs['date'])
                    ->union($outsideQuery)
                    ->orderBy('booking_date', 'DESC');

                $returnData = $insideQuery->get();

                collect($returnData)->map(function ($item) use ($timezone) {
                    $item->booking_date = $this->formatDateTimeCountryWise($item->booking_date,$timezone);
                    return $item;
                });
                
            }

            return $this->sendSuccessResponse("Customer" . trans("messages.insert-success"), 200, $returnData);
        } catch (\Exception $e) {
            Log::info($e);
            return $this->sendFailedResponse(Lang::get('messages.general.laravel_error'), 400);
        }
    }

    public function dateFilters($data)
    {
        $filteredData = [];
        $paginateData = $data->toArray();
        $user = Auth::user();
        $filterData = $paginateData['data'];

        $newData = collect($filterData)->groupBy(
            [function ($item) {
                return Carbon::parse($item['booking_date'])->format('Y-m-d');
            }]
        );

        $returnData = [];
        $count = 0;
        foreach ($newData as $dateDisplay => $customer) {

            $returnData[$count]['display_date'] = $dateDisplay;

            $tempData = $outsideTemp = $finalArray = [];
            foreach ($customer as $key => $customerData) {
                $ids = explode(',', $customerData['ids']);
                $returnData[$count][$customerData['type']] = $ids;
                if ($customerData['type'] == CustomerAttachment::INSIDE) {
                    $tempData = CustomerAttachment::leftjoin('completed_customer', function ($join) {
                        $join->on('customer_attachments.entity_id', '=', 'completed_customer.id')
                            ->whereNull('completed_customer.deleted_at');
                    })
                        ->leftjoin('requested_customer', function ($join) {
                            $join->on('completed_customer.requested_customer_id', '=', 'requested_customer.id');
                        })
                        ->leftjoin('users_detail', 'users_detail.user_id', 'requested_customer.user_id')
                        ->where('customer_attachments.type', CustomerAttachment::INSIDE)
                        ->select(
                            'customer_attachments.*',
                            'completed_customer.requested_customer_id as customer_id',
                            'requested_customer.booking_date as booking_date',
                            'requested_customer.entity_type_id as entity_type_id',
                            'requested_customer.entity_id as shop_id',
                            'users_detail.name as user_name'
                        )
                        ->where('requested_customer.entity_type_id', EntityTypes::SHOP)
                        ->whereIn('customer_attachments.id', $ids)
                        ->orderBy('booking_date', 'DESC')
                        ->get()->toArray();
                } else {
                    $outsideTemp =  CustomerAttachment::leftjoin('complete_customer_details', function ($join) {
                        $join->on('customer_attachments.entity_id', '=', 'complete_customer_details.id')
                            ->whereNull('complete_customer_details.deleted_at');
                    })
                        ->leftjoin('customer_lists', 'customer_lists.id', 'complete_customer_details.customer_id')
                        ->where('customer_attachments.type', CustomerAttachment::OUTSIDE)
                        ->select(
                            'customer_attachments.*',
                            'complete_customer_details.customer_id',
                            'complete_customer_details.date as booking_date',
                            'complete_customer_details.entity_type_id as entity_type_id',
                            'complete_customer_details.entity_id as shop_id',
                            'customer_lists.customer_name as user_name'
                        )
                        ->where('complete_customer_details.status_id', RequestBookingStatus::COMPLETE)
                        ->where('complete_customer_details.entity_type_id', EntityTypes::SHOP)
                        ->whereIn('customer_attachments.id', $ids)
                        ->orderBy('complete_customer_details.date', 'DESC')
                        ->get()->toArray();
                }
            }

            $finalArray = array_merge($tempData, $outsideTemp);

            $returnData[$count]['booking_data'] = collect($finalArray)->sortByDesc('booking_date')->values();
            $count++;
        }

        $paginateData['data'] = array_values($returnData);
        return $paginateData;
    }
}
