<?php

namespace App\Http\Controllers\Admin;

use Log;

use Carbon\Carbon;
use App\Models\City;
use App\Models\User;
use App\Models\State;
use App\Models\Status;
use App\Models\Address;
use App\Models\Country;
use App\Models\Manager;
use App\Models\EntityTypes;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\ReloadCoinRequest;
use App\Models\UserEntityRelation;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use App\Models\ManagerActivityLogs;
use App\Validators\MangerValidator;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Lang;
use Spatie\Permission\Models\Permission;

class ManagerController extends Controller
{

    private $managerValidator;

    public function __construct()
    {
        $this->managerValidator = new MangerValidator();
        $this->middleware('permission:manager-list', ['only' => ['index']]);
    }


    /* ================ Manager List Start ======================= */
    public function index()
    {
        $title = 'Company/Supporter';
        return view('admin.manager.index', compact('title'));
    }

    public function getJsonManagerData(Request $request)
    {
        try {
            Log::info('Start all manager list');
            $columns = array(
                //0 => 'id',
                0 => 'name',
                1 => 'mobile',
                2 => 'email',
                3 => 'client_count',
                4 => 'activeclient',
                5 => 'created_at',
                6 => 'shoptotal',
            );
            $user = Auth::user();
            $limit = $request->input('length');
            $start = $request->input('start');
            $search = $request->input('search.value');
            $draw = $request->input('draw');
            $order = $columns[$request->input('order.0.column')];
            $dir = $request->input('order.0.dir');
            $adminTimezone = $this->getAdminUserTimezone();

            $query = User::join('managers', 'managers.user_id', 'users.id')
                ->join('user_entity_relation', 'user_entity_relation.user_id', 'users.id')
                ->where('user_entity_relation.entity_type_id', EntityTypes::MANAGER)
                ->select('users.id', 'users.email', 'managers.name', 'managers.mobile', 'users.created_at', 'managers.id as manager_id', 'managers.recommended_code')
                ->selectSub(function ($q) {
                    $q->select(DB::raw('count(users_detail.id) as count'))->from('users_detail')->whereNull('users_detail.deleted_at')->whereRaw("`users_detail`.`manager_id` = `managers`.`id`");
                }, 'client_count');

            $managers =  $query->get();

            if (!empty($managers)) {
                foreach ($managers as $value) {
                    $countClientResult = $this->getClientDetail($value['manager_id']);
                    $countClient = $countClientResult['grouped_result'] ?? '';
                    $value->shoptotal = $countClientResult['shop_count'] ?? 0;
                    $value->activeclient = $countClient['active'] ?? 0;
                    $value->inactiveclient = $countClient['inactive'] ?? 0;
                    $value->pendingclient = $countClient['pending'] ?? 0;

                    $value->coinAmount = ReloadCoinRequest::join('users_detail', 'users_detail.user_id', 'reload_coins_request.user_id')
                        ->where('users_detail.manager_id', $value['manager_id'])
                        ->where('status', ReloadCoinRequest::GIVE_COIN)
                        ->whereBetween('reload_coins_request.created_at', [Carbon::now()->startOfMonth()->subMonth(1), Carbon::now()])
                        ->select(
                            DB::raw('SUM(reload_coins_request.coin_amount) as coin_amount'),
                            DB::raw("MONTH(reload_coins_request.created_at) as month")
                        )
                        ->groupBy('month')
                        ->get();
                }
            }

            if (!empty($search)) {
                $managers = collect($managers)->filter(function ($item) use ($search) {
                    return false !== stripos($item->email, $search) ||
                        false !== stripos($item->name, $search) ||
                        false !== stripos($item->mobile, $search);
                })->values();
            }

            if ($dir == 'asc') {
                $managers = collect($managers)->sortBy($order);
            } else {
                $managers = collect($managers)->sortByDesc($order);
            }


            $totalData = count($managers);
            $totalFiltered = $totalData;
            $managers = collect($managers)->slice($start, $limit);

            $data = array();
            if (!empty($managers)) {
                foreach ($managers as $value) {
                    $id = $value['id'];
                    $manager_id = $value['manager_id'];
                    $edit = route('admin.manager.edit', $id);
                    $clients = route('admin.business-client.index') . '?manager_id=' . $manager_id;
                    $nestedData['checkbox'] = "<td><div class=\"custom-checkbox custom-control\">
                    <input type=\"checkbox\" data-checkboxes=\"mygroup\" class=\"custom-control-input checkbox_id check-all-hospital\" id=\"$id\" data-id=\"$id\" value=\"$id\" name=\"checkbox_id[]\"><label for=\"$id\" class=\"custom-control-label\">&nbsp;</label></div></td>";

                    $nestedData['coinAmount'] = $value['coinAmount'];
                    $nestedData['name'] = $value['name'];
                    $nestedData['mobile'] = $value['mobile'];
                    $nestedData['email'] = $value['email'];
                    $nestedData['client_count'] = $value['client_count'];
                    $nestedData['shoptotal'] = $value['shoptotal'];
                    $nestedData['supporter_code'] = $value['recommended_code'];
                    $nestedData['active_count'] = $this->getClientDetailButton($value['activeclient'], $value['inactiveclient'], $value['pendingclient']);

                    $nestedData['date'] = $this->formatDateTimeCountryWise($value['display_created_at'], $adminTimezone, 'd-m-Y H:i');

                    $editButton = "<a role='button' href='" . $edit . "' title='' data-original-title='Edit' class='btn btn-primary btn-sm' data-toggle='tooltip'><i class='fa fa-edit'></i></a>";
                    $deleteButton =  "<a role='button' href='javascript:void(0)' onclick='deleteManager(" . $id . ")' title='' data-original-title='Delete' class='btn btn-danger btn-sm' data-toggle='tooltip'><i class='fa fa-trash'></i></a>";
                    $clientButton = "<a href='" . $clients . "' class='btn btn-outline-primary btn-sm' data-toggle='tooltip' data-original-title='See Clients'>Client</a>";
                    $nestedData['actions'] = "$clientButton $editButton $deleteButton ";
                    $data[] = $nestedData;
                }
            }
            $jsonData = array(
                "draw" => intval($draw),
                "recordsTotal" => intval($totalData),
                "recordsFiltered" => intval($totalFiltered),
                "data" => $data
            );
            Log::info('End manager list');
            return response()->json($jsonData);
        } catch (\Exception $ex) {
            Log::info('Exception manager list');
            Log::info($ex);
            return response()->json([]);
        }
    }
    public function getJsonSubManagerData(Request $request)
    {
        try {
            Log::info('Start sub manager list');

            $columns = array(
                //0 => 'id',
                0 => 'name',
                1 => 'mobile',
                2 => 'email',
                3 => 'client_count',
                4 => 'activeclient',
                5 => 'created_at',
                6 => 'shoptotal',
            );
            $user = Auth::user();
            $limit = $request->input('length');
            $start = $request->input('start');
            $search = $request->input('search.value');
            $draw = $request->input('draw');
            $order = $columns[$request->input('order.0.column')];
            $dir = $request->input('order.0.dir');
            $adminTimezone = $this->getAdminUserTimezone();

            $query = User::join('managers', 'managers.user_id', 'users.id')
                ->join('user_entity_relation', 'user_entity_relation.user_id', 'users.id')
                ->where('user_entity_relation.entity_type_id', EntityTypes::SUBMANAGER)
                ->select('users.id', 'users.email', 'managers.name', 'managers.mobile', 'users.created_at', 'managers.id as manager_id', 'managers.recommended_code')
                ->selectSub(function ($q) {
                    $q->select(DB::raw('count(users_detail.id) as count'))->from('users_detail')->whereNull('users_detail.deleted_at')->whereRaw("`users_detail`.`manager_id` = `managers`.`id`");
                }, 'client_count');

            $managers =  $query->get();

            if (!empty($managers)) {
                foreach ($managers as $value) {
                    $countClientResult = $this->getClientDetail($value['manager_id']);
                    $countClient = $countClientResult['grouped_result'] ?? '';
                    $value->shoptotal = $countClientResult['shop_count'] ?? 0;
                    $value->activeclient = $countClient['active'] ?? 0;
                    $value->inactiveclient = $countClient['inactive'] ?? 0;
                    $value->pendingclient = $countClient['pending'] ?? 0;

                    $value->coinAmount = ReloadCoinRequest::join('users_detail', 'users_detail.user_id', 'reload_coins_request.user_id')
                        ->where('users_detail.manager_id', $value['manager_id'])
                        ->where('status', ReloadCoinRequest::GIVE_COIN)
                        ->whereBetween('reload_coins_request.created_at', [Carbon::now()->startOfMonth()->subMonth(1), Carbon::now()])
                        ->select(
                            DB::raw('SUM(reload_coins_request.coin_amount) as coin_amount'),
                            DB::raw("MONTH(reload_coins_request.created_at) as month")
                        )
                        ->groupBy('month')
                        ->toSql();
                }
            }

            if (!empty($search)) {
                $managers = collect($managers)->filter(function ($item) use ($search) {
                    return false !== stripos($item->email, $search) ||
                        false !== stripos($item->name, $search) ||
                        false !== stripos($item->mobile, $search);
                })->values();
            }

            if ($dir == 'asc') {
                $managers = collect($managers)->sortBy($order);
            } else {
                $managers = collect($managers)->sortByDesc($order);
            }


            $totalData = count($managers);
            $totalFiltered = $totalData;
            $managers = collect($managers)->slice($start, $limit);

            $data = array();
            if (!empty($managers)) {
                foreach ($managers as $value) {
                    $id = $value['id'];
                    $manager_id = $value['manager_id'];
                    $edit = route('admin.manager.edit', $id);
                    $clients = route('admin.business-client.index') . '?manager_id=' . $manager_id;
                    $nestedData['checkbox'] = "<td><div class=\"custom-checkbox custom-control\">
                    <input type=\"checkbox\" data-checkboxes=\"mygroup\" class=\"custom-control-input checkbox_id check-all-hospital\" id=\"$id\" data-id=\"$id\" value=\"$id\" name=\"checkbox_id[]\"><label for=\"$id\" class=\"custom-control-label\">&nbsp;</label></div></td>";

                    $nestedData['coinAmount'] = $value['coinAmount'];
                    $nestedData['name'] = $value['name'];
                    $nestedData['mobile'] = $value['mobile'];
                    $nestedData['email'] = $value['email'];
                    $nestedData['client_count'] = $value['client_count'];
                    $nestedData['shoptotal'] = $value['shoptotal'];
                    $nestedData['supporter_code'] = $value['recommended_code'];
                    $nestedData['active_count'] = $this->getClientDetailButton($value['activeclient'], $value['inactiveclient'], $value['pendingclient']);

                    $nestedData['date'] = $this->formatDateTimeCountryWise($value['display_created_at'], $adminTimezone, 'd-m-Y H:i');

                    $editButton = "<a role='button' href='" . $edit . "' title='' data-original-title='Edit' class='btn btn-primary btn-sm' data-toggle='tooltip'><i class='fa fa-edit'></i></a>";
                    $deleteButton =  "<a href='javascript:void(0)' role='button' onclick='deleteManager(" . $id . ")' class='btn btn-danger btn-sm' data-toggle='tooltip' data-original-title=" . Lang::get('general.delete') . "><i class='fa fa-trash'></a>";
                    $clientButton = "<a href='" . $clients . "' class='btn btn-outline-primary btn-sm' data-toggle='tooltip' data-original-title='See Clients'>Client</a>";
                    $nestedData['actions'] = "$clientButton $editButton $deleteButton";
                    $data[] = $nestedData;
                }
            }
            $jsonData = array(
                "draw" => intval($draw),
                "recordsTotal" => intval($totalData),
                "recordsFiltered" => intval($totalFiltered),
                "data" => $data
            );
            Log::info('End sub manager list');
            return response()->json($jsonData);
        } catch (\Exception $ex) {
            Log::info('Exception sub manager list');
            Log::info($ex);
            return response()->json([]);
        }
    }

    public function getClientDetailButton($active, $inactive, $pending)
    {
        $button = '';

        if ($active > 0) {
            $button .= '<span class="badge badge-success"> (' . $active . ')</span>  ';
        }

        if ($inactive > 0) {
            $button .= '<span class="badge badge-secondary"> (' . $inactive . ')</span>';
        }

        if ($pending > 0) {
            $button .= '<span class="badge" style="background-color: #fff700;"> (' . $pending . ')</span>';
        }
        return $button;
    }

    public function getClientDetail($managerID)
    {
        $shopHospitalCountAmount = '(CASE
                WHEN user_entity_relation.entity_type_id = 1 THEN  count(DISTINCT shops.id)
                WHEN user_entity_relation.entity_type_id = 2 THEN count(DISTINCT hospitals.id)
                ELSE ""
                END) * credit_plans.amount ';

        $shopHospitalCount = '(CASE
            WHEN user_entity_relation.entity_type_id = 1 THEN  count(DISTINCT shops.id)
            WHEN user_entity_relation.entity_type_id = 2 THEN count(DISTINCT hospitals.id)
            ELSE 0
            END) ';

        $query = DB::table('users')->join('user_entity_relation', 'user_entity_relation.user_id', 'users.id')
            ->join('users_detail', 'users_detail.user_id', 'users.id')
            ->join('user_credits', 'user_credits.user_id', 'users.id')
            ->leftjoin('credit_plans', function ($query) {
                $query->on('credit_plans.package_plan_id', '=', 'users_detail.package_plan_id')
                    ->whereRaw('credit_plans.entity_type_id = user_entity_relation.entity_type_id');
            })
            ->leftjoin('shops', function ($query) {
                $query->on('users.id', '=', 'shops.user_id');
            })
            ->leftjoin('hospitals', 'user_entity_relation.entity_id', 'hospitals.id')
            ->whereIN('user_entity_relation.entity_type_id', [EntityTypes::HOSPITAL, EntityTypes::SHOP])
            ->whereIn('users.status_id', [Status::ACTIVE, Status::INACTIVE])
            ->where('users_detail.manager_id', $managerID);

        $result = $query->select(
            'users.id',
            'user_credits.credits',
            'user_entity_relation.entity_type_id'
        )
            ->selectRaw("{$shopHospitalCount} AS total_count")
            ->selectRaw("{$shopHospitalCountAmount} AS total_plan_amount")
            ->selectRaw("(CASE WHEN {$shopHospitalCountAmount} <= user_credits.credits THEN  'active'
                        ELSE 'inactive'
                        END) AS is_user_active")
            ->groupBy('users.id')
            ->get();


        foreach ($result as $data) {
            $businessUserId = $data->id;

            if ($data->entity_type_id == EntityTypes::SHOP) {
                $shopCount = DB::table('shops')->where('user_id', $businessUserId)->count();
                $shopPendingCount = DB::table('shops')->where('user_id', $businessUserId)->where('status_id', Status::PENDING)->count();

                if ($shopCount == $shopPendingCount) {
                    $data->is_user_active = 'pending';
                }
            } else {

                $hospitalPendingcount = DB::table('user_entity_relation')->leftjoin('hospitals', 'user_entity_relation.entity_id', 'hospitals.id')
                    ->where('user_entity_relation.entity_type_id', EntityTypes::HOSPITAL)->where('user_entity_relation.user_id', $businessUserId)
                    ->where('hospitals.status_id', Status::PENDING)->count();

                if (!empty($hospitalPendingcount)) {
                    $data->is_user_active = 'pending';
                }
            }
        }

        $totalShopCount = collect($result)->sum('total_count');
        $groupedResult = collect($result)->groupBy('is_user_active')->map->count()->toArray();

        ksort($groupedResult);
        if (empty($groupedResult)) {
            return '';
        }

        return ['grouped_result' => $groupedResult, 'shop_count' => $totalShopCount];
    }

    public function create()
    {
        $title = "Add Company/Supporter";
        $countryList = Country::where('is_show', 1)->pluck('name', 'id')->all();
        $stateList = State::pluck('name', 'id')->all();
        $cityList = City::pluck('name', 'id')->all();
        $user = Auth::user();
        $isAdmin = $user->hasAnyRole(['Admin']);
        $isManager = $user->hasAnyRole(['Manager']);
        if ($isAdmin) {
            $roles = Role::where('name', '!=', 'Admin')->get()->pluck('display_name', 'id');
        } else if ($isManager) {
            $roles = Role::where('name', '!=', 'Admin')->where('name', '!=', 'Manager')->get()->pluck('display_name', 'id');
        } else {
            $roles = [];
        }

        return view('admin.manager.form', compact('title', 'countryList', 'stateList', 'cityList', 'roles'));
    }

    public function store(Request $request)
    {
        try {
            DB::beginTransaction();
            Log::info('Start code for the add manager');
            $inputs = $request->all();
            $validator = $this->managerValidator->validateStore($request->all());

            if ($validator->fails()) {
                return redirect()->back()->withErrors($validator)->withInput();
            }
            //$recommended_code = Str::upper(Str::random(7));
            $recommended_code = $inputs["recommended_code"] ?? null;
            $role = Role::find($inputs['role']);
            $entityType = $role->name == 'Manager' ? EntityTypes::MANAGER : EntityTypes::SUBMANAGER;
            $userData = [
                "email" => $inputs["email"],
                "username" => $inputs["email"],
                "password" => Hash::make($inputs['password']),
                "status_id" => Status::ACTIVE,
            ];
            $user = User::create($userData);
            UserEntityRelation::create(['user_id' => $user->id, "entity_type_id" => $entityType, 'entity_id' => $user->id]);
            $data = [
                "name" => $inputs['name'],
                "mobile" => $inputs['mobile'],
                "recommended_code" => $recommended_code,
                'user_id' => $user->id
            ];
            $manager = Manager::create($data);
            $address = Address::create([
                "entity_type_id" => $entityType,
                "entity_id" => $user->id,
                "address" => $inputs['address'] ?? '',
                "country_id" => $inputs['country'],
                "state_id" => $inputs['state'] ?? null,
                "city_id" => $inputs['city'] ?? null,
                "main_address" => 1
            ]);

            $user->assignRole([$role->id]);

            $logData = [
                'activity_type' => ManagerActivityLogs::CREATE_MANAGER,
                'user_id' => auth()->user()->id,
                'value' => Lang::get('messages.manager_activity.create_manager'),
                'entity_id' => $user->id,
            ];
            $this->addManagerActivityLogs($logData);

            DB::commit();
            Log::info('End the code for the add manager');
            notify()->success("Manager added successfully", "Success", "topRight");
            return redirect()->route('admin.manager.index');
        } catch (\Exception $e) {
            Log::info('Exception in the add manager');
            Log::info($e);
            notify()->error("Failed to add manager", "Error", "topRight");
            return redirect()->route('admin.manager.index');
        }
    }


    public function edit($id)
    {
        $title = "Edit Company/Supporter";
        $countryList = Country::where('is_show', 1)->pluck('name', 'id')->all();
        $stateList = State::pluck('name', 'id')->all();
        $cityList = City::pluck('name', 'id')->all();
        $user = Auth::user();
        $isAdmin = $user->hasAnyRole(['Admin']);
        $isManager = $user->hasAnyRole(['Manager']);
        if ($isAdmin) {
            $roles = Role::where('name', '!=', 'Admin')->get()->pluck('name', 'id');
        } else if ($isManager) {
            $roles = Role::where('name', '!=', 'Admin')->where('name', '!=', 'Manager')->get()->pluck('name', 'id');
        } else {
            $roles = [];
        }
        $user = User::find($id);
        $manager = User::join('managers', 'managers.user_id', 'users.id')
            ->leftjoin('addresses', 'users.id', 'addresses.entity_id')
            ->where('users.id', $id)
            ->where('addresses.entity_type_id', $user->entity_type_id)
            ->where('addresses.entity_id', $id)
            ->where('addresses.main_address', 1)
            ->select('users.id', 'users.email', 'managers.name', 'managers.mobile', 'addresses.address', 'addresses.country_id', 'addresses.state_id', 'addresses.city_id', 'users.created_at', 'managers.recommended_code')->first();
        return view('admin.manager.form', compact('title', 'manager', 'countryList', 'stateList', 'cityList', 'roles'));
    }

    public function update(Request $request, $id)
    {
        try {
            Log::info('Start code for the update manager');
            DB::beginTransaction();
            $inputs = $request->all();

            $manager = Manager::where('user_id', $id)->first();
            $validator = $this->managerValidator->validateUpdate($request->all(), $manager->id);

            if ($validator->fails()) {
                return redirect()->back()->withErrors($validator)->withInput();
            }
            $user = User::find($id);
            $recommended_code = $inputs["recommended_code"] ?? null;
            if (!empty($inputs['password'])) {
                User::where('id', $user->id)->update(["password" => Hash::make($inputs['password'])]);
            }

            $data = [
                "name" => $inputs['name'],
                "mobile" => $inputs['mobile'],
                "recommended_code" => $recommended_code
            ];
            $manager = Manager::where('user_id', $user->id)->update($data);
            $address = Address::where('entity_id', $user->id)->where('entity_type_id', $user->entity_type_id)->update([
                "address" => $inputs['address'] ?? '',
                "country_id" => $inputs['country'],
                "state_id" => $inputs['state'] ?? null,
                "city_id" => $inputs['city'] ?? null,
                "main_address" => 1
            ]);

            Log::info('End the code for the update manager');
            DB::commit();
            notify()->success("Manager updated successfully", "Success", "topRight");
            return redirect()->route('admin.manager.index');
        } catch (\Exception $e) {
            dd($e->getMessage());
            DB::rollBack();
            Log::info('Exception in the update manager.');
            Log::info($e);
            notify()->error("Failed to update manager", "Error", "topRight");
            return redirect()->route('admin.manager.index');
        }
    }


    public function delete($id)
    {
        return view('admin.manager.delete', compact('id'));
    }

    public function destroy($id)
    {
        try {
            Log::info('manager delete code start.');
            DB::beginTransaction();

            $user = User::find($id);
            $manager = Manager::where('user_id', $user->id)->delete();
            $address = Address::where('entity_id', $user->id)->where('entity_type_id', $user->entity_type_id)->delete();
            $user->delete();
            DB::commit();
            Log::info('manager delete code end.');
            notify()->success("Manager deleted successfully", "Success", "topRight");
            return redirect()->route('admin.manager.index');
        } catch (\Exception $ex) {
            DB::rollBack();
            Log::info('manager delete exception.');
            Log::info($ex);
            notify()->error("Failed to deleted manager", "Error", "topRight");
            return redirect()->route('admin.manager.index');
        }
    }

    public function getState($id)
    {
        $state_data = State::where('country_id', $id)->pluck('name', 'id')->all();
        return response()->json(compact('state_data'));
    }
    public function getCity($id)
    {
        $city_data = City::where('state_id', $id)->pluck('name', 'id')->all();
        return response()->json(compact('city_data'));
    }

    public function checkRecommendedCode(Request $request)
    {
        $managerId = !empty($request->manager_id) ? $request->manager_id : 0;
        $recommended_code = !empty($request->recommended_code) ? $request->recommended_code : null;
        if (!empty($managerId)) {
            $recommendedCode = Manager::where('user_id', '!=', $managerId)->where('recommended_code', $recommended_code)
                ->first();
        } else {
            $recommendedCode = Manager::where('recommended_code', $recommended_code)->first();
        }

        if (!empty($recommendedCode)) {
            return "false";
        }
        return "true";
    }


    /* ================ Manager List Code End ======================= */

    /* ================ Activity Log Code Start ======================= */
    public function indexActivityLog()
    {
        $title = "Activity Log";
        return view('admin.manager.index-activity-log', compact('title'));
    }

    public function getJsonAllActivityLogData(Request $request)
    {
        try {
            Log::info('Start all activity log');

            $shops = [
                [
                    'id' => 1,
                    'manager_name' => 'Name 1',
                    'activity' => 'Log In',
                    'ip' => 9693920011,
                    'date' => '04-06-2020 10:09',
                ],
                [
                    'id' => 2,
                    'manager_name' => 'Name 2',
                    'activity' => 'Logout',
                    'ip' => 9693920011,
                    'date' => '04-06-2020 10:09',
                    'status' => 'Active'
                ],
                [
                    'id' => 3,
                    'manager_name' => 'Name 3',
                    'activity' => 'Deducting Rate',
                    'ip' => 9693920011,
                    'date' => '04-06-2020 10:09',
                ],
                [
                    'id' => 4,
                    'manager_name' => 'Name 4',
                    'activity' => 'Delete Account',
                    'ip' => 9693920011,
                    'date' => '04-06-2020 10:09',
                ],
                [
                    'id' => 5,
                    'manager_name' => 'Name 5',
                    'activity' => 'Log In',
                    'ip' => 9693920011,
                    'date' => '04-06-2020 10:09',
                ],
                [
                    'id' => 6,
                    'manager_name' => 'Name 6',
                    'activity' => 'Log Out',
                    'ip' => 9693920011,
                    'date' => '04-06-2020 10:09',
                ],
            ];
            $totalFiltered = 1;
            $draw = $request->input('draw');
            $totalData = count($shops);
            $data = array();

            if (!empty($shops)) {
                foreach ($shops as $value) {
                    $id = $value['id'];
                    $nestedData['checkbox'] = "<td><div class=\"custom-checkbox custom-control\">
                    <input type=\"checkbox\" data-checkboxes=\"mygroup\" class=\"custom-control-input checkbox_id check-all-shop\" id=\"$id\" data-id=\"$id\" value=\"$id\" name=\"checkbox_id[]\"><label for=\"$id\" class=\"custom-control-label\">&nbsp;</label></div></td>";

                    $nestedData['manager_name'] = $value['manager_name'];
                    $nestedData['activity'] = $value['activity'];
                    $nestedData['ip'] = $value['ip'];
                    $nestedData['date'] = $value['date'];
                    $data[] = $nestedData;
                }
            }
            $jsonData = array(
                "draw" => intval($draw),
                "recordsTotal" => intval($totalData),
                "recordsFiltered" => intval($totalFiltered),
                "data" => $data
            );
            Log::info('End all activity log');
            return response()->json($jsonData);
        } catch (\Exception $ex) {
            Log::info('Exception all activity log');
            Log::info($ex);
            return response()->json([]);
        }
    }
    public function getJsonDeductingRateActivityLogData(Request $request)
    {
        try {
            Log::info('Start all activity log');

            $shops = [
                [
                    'id' => 1,
                    'manager_name' => 'Name 1',
                    'activity' => 'Deducting Rate',
                    'ip' => 9693920011,
                    'date' => '04-06-2020 10:09',
                ],
                [
                    'id' => 2,
                    'manager_name' => 'Name 2',
                    'activity' => 'Deducting Rate',
                    'ip' => 9693920011,
                    'date' => '04-06-2020 10:09',
                    'status' => 'Active'
                ],
                [
                    'id' => 3,
                    'manager_name' => 'Name 3',
                    'activity' => 'Deducting Rate',
                    'ip' => 9693920011,
                    'date' => '04-06-2020 10:09',
                ],
                [
                    'id' => 4,
                    'manager_name' => 'Name 4',
                    'activity' => 'Deducting Rate',
                    'ip' => 9693920011,
                    'date' => '04-06-2020 10:09',
                ],
                [
                    'id' => 5,
                    'manager_name' => 'Name 5',
                    'activity' => 'Deducting Rate',
                    'ip' => 9693920011,
                    'date' => '04-06-2020 10:09',
                ],
                [
                    'id' => 6,
                    'manager_name' => 'Name 6',
                    'activity' => 'Deducting Rate',
                    'ip' => 9693920011,
                    'date' => '04-06-2020 10:09',
                ],
            ];
            $totalFiltered = 1;
            $draw = $request->input('draw');
            $totalData = count($shops);
            $data = array();

            if (!empty($shops)) {
                foreach ($shops as $value) {
                    $id = $value['id'];
                    $nestedData['checkbox'] = "<td><div class=\"custom-checkbox custom-control\">
                    <input type=\"checkbox\" data-checkboxes=\"mygroup\" class=\"custom-control-input checkbox_id check-all-shop\" id=\"$id\" data-id=\"$id\" value=\"$id\" name=\"checkbox_id[]\"><label for=\"$id\" class=\"custom-control-label\">&nbsp;</label></div></td>";

                    $nestedData['manager_name'] = $value['manager_name'];
                    $nestedData['activity'] = $value['activity'];
                    $nestedData['ip'] = $value['ip'];
                    $nestedData['date'] = $value['date'];
                    $data[] = $nestedData;
                }
            }
            $jsonData = array(
                "draw" => intval($draw),
                "recordsTotal" => intval($totalData),
                "recordsFiltered" => intval($totalFiltered),
                "data" => $data
            );
            Log::info('End all activity log');
            return response()->json($jsonData);
        } catch (\Exception $ex) {
            Log::info('Exception all activity log');
            Log::info($ex);
            return response()->json([]);
        }
    }
    public function getJsonClientCreditActivityLogData(Request $request)
    {
        try {
            Log::info('Start all activity log');

            $shops = [
                [
                    'id' => 1,
                    'manager_name' => 'Name 1',
                    'activity' => 'Edit Client Credit',
                    'ip' => 9693920011,
                    'date' => '04-06-2020 10:09',
                ],
                [
                    'id' => 2,
                    'manager_name' => 'Name 2',
                    'activity' => 'Edit Client Credit',
                    'ip' => 9693920011,
                    'date' => '04-06-2020 10:09',
                    'status' => 'Active'
                ],
                [
                    'id' => 3,
                    'manager_name' => 'Name 3',
                    'activity' => 'Edit Client Credit',
                    'ip' => 9693920011,
                    'date' => '04-06-2020 10:09',
                ],
                [
                    'id' => 4,
                    'manager_name' => 'Name 4',
                    'activity' => 'Edit Client Credit',
                    'ip' => 9693920011,
                    'date' => '04-06-2020 10:09',
                ],
                [
                    'id' => 5,
                    'manager_name' => 'Name 5',
                    'activity' => 'Edit Client Credit',
                    'ip' => 9693920011,
                    'date' => '04-06-2020 10:09',
                ],
                [
                    'id' => 6,
                    'manager_name' => 'Name 6',
                    'activity' => 'Edit Client Credit',
                    'ip' => 9693920011,
                    'date' => '04-06-2020 10:09',
                ],
            ];
            $totalFiltered = 1;
            $draw = $request->input('draw');
            $totalData = count($shops);
            $data = array();

            if (!empty($shops)) {
                foreach ($shops as $value) {
                    $id = $value['id'];
                    $nestedData['checkbox'] = "<td><div class=\"custom-checkbox custom-control\">
                    <input type=\"checkbox\" data-checkboxes=\"mygroup\" class=\"custom-control-input checkbox_id check-all-shop\" id=\"$id\" data-id=\"$id\" value=\"$id\" name=\"checkbox_id[]\"><label for=\"$id\" class=\"custom-control-label\">&nbsp;</label></div></td>";

                    $nestedData['manager_name'] = $value['manager_name'];
                    $nestedData['activity'] = $value['activity'];
                    $nestedData['ip'] = $value['ip'];
                    $nestedData['date'] = $value['date'];
                    $data[] = $nestedData;
                }
            }
            $jsonData = array(
                "draw" => intval($draw),
                "recordsTotal" => intval($totalData),
                "recordsFiltered" => intval($totalFiltered),
                "data" => $data
            );
            Log::info('End all activity log');
            return response()->json($jsonData);
        } catch (\Exception $ex) {
            Log::info('Exception all activity log');
            Log::info($ex);
            return response()->json([]);
        }
    }
    public function getJsonDeleteAccountActivityLogData(Request $request)
    {
        try {
            Log::info('Start all activity log');

            $shops = [
                [
                    'id' => 1,
                    'manager_name' => 'Name 1',
                    'activity' => 'Delete Account',
                    'ip' => 9693920011,
                    'date' => '04-06-2020 10:09',
                ],
                [
                    'id' => 2,
                    'manager_name' => 'Name 2',
                    'activity' => 'Delete Account',
                    'ip' => 9693920011,
                    'date' => '04-06-2020 10:09',
                    'status' => 'Active'
                ],
                [
                    'id' => 3,
                    'manager_name' => 'Name 3',
                    'activity' => 'Delete Account',
                    'ip' => 9693920011,
                    'date' => '04-06-2020 10:09',
                ],
                [
                    'id' => 4,
                    'manager_name' => 'Name 4',
                    'activity' => 'Delete Account',
                    'ip' => 9693920011,
                    'date' => '04-06-2020 10:09',
                ],
                [
                    'id' => 5,
                    'manager_name' => 'Name 5',
                    'activity' => 'Delete Account',
                    'ip' => 9693920011,
                    'date' => '04-06-2020 10:09',
                ],
                [
                    'id' => 6,
                    'manager_name' => 'Name 6',
                    'activity' => 'Delete Account',
                    'ip' => 9693920011,
                    'date' => '04-06-2020 10:09',
                ],
            ];
            $totalFiltered = 1;
            $draw = $request->input('draw');
            $totalData = count($shops);
            $data = array();

            if (!empty($shops)) {
                foreach ($shops as $value) {
                    $id = $value['id'];
                    $nestedData['checkbox'] = "<td><div class=\"custom-checkbox custom-control\">
                    <input type=\"checkbox\" data-checkboxes=\"mygroup\" class=\"custom-control-input checkbox_id check-all-shop\" id=\"$id\" data-id=\"$id\" value=\"$id\" name=\"checkbox_id[]\"><label for=\"$id\" class=\"custom-control-label\">&nbsp;</label></div></td>";

                    $nestedData['manager_name'] = $value['manager_name'];
                    $nestedData['activity'] = $value['activity'];
                    $nestedData['ip'] = $value['ip'];
                    $nestedData['date'] = $value['date'];
                    $data[] = $nestedData;
                }
            }
            $jsonData = array(
                "draw" => intval($draw),
                "recordsTotal" => intval($totalData),
                "recordsFiltered" => intval($totalFiltered),
                "data" => $data
            );
            Log::info('End all activity log');
            return response()->json($jsonData);
        } catch (\Exception $ex) {
            Log::info('Exception all activity log');
            Log::info($ex);
            return response()->json([]);
        }
    }

    public function managerActivityLog()
    {
        $title = 'Manager Activity Logs';
        return view('admin.manager-activity-logs.index', compact('title'));
    }

    public function getAllManagerActivityLog(Request $request)
    {

        $columns = array(
            0 => 'manager_name',
            1 => 'email',
            2 => 'activity_type_name',
            4 => 'activity_name',
            5 => 'value',
            6 => 'created_at',
        );

        $filter = $request->input('filter');
        $limit = $request->input('length');
        $start = $request->input('start');
        $search = $request->input('search.value');
        $draw = $request->input('draw');
        $order = $columns[$request->input('order.0.column')];
        $dir = $request->input('order.0.dir');
        $adminTimezone = $this->getAdminUserTimezone();

        try {

            $logs = ManagerActivityLogs::leftJoin('managers', 'managers.user_id', 'manager_activity_logs.user_id')
                ->leftJoin('user_entity_relation', 'user_entity_relation.user_id', 'manager_activity_logs.user_id')
                ->leftJoin('users', 'users.id', 'manager_activity_logs.user_id')
                ->select(
                    'manager_activity_logs.*',
                    'managers.name as manager_name',
                    'users.email',
                    'managers.mobile'
                )
                ->where('user_entity_relation.entity_type_id', '!=', EntityTypes::ADMIN)
                ->get();

            if ($dir == 'asc') {
                $logs = collect($logs)->sortBy($order);
            } else {
                $logs = collect($logs)->sortByDesc($order);
            }

            if (!empty($search)) {
                $logs = collect($logs)->filter(function ($item) use ($search) {
                    return false !== stripos($item->manager_name, $search) ||
                        false !== stripos($item->activity_type_name, $search) ||
                        false !== stripos($item->activity_name, $search) ||
                        false !== stripos($item->value, $search);
                })->values();
            }

            $totalData = count($logs);
            $totalFiltered = $totalData;

            $logs = collect($logs)->slice($start, $limit);

            $count = 0;
            foreach ($logs as $log) {

                $data[$count]['manager_name'] = $log->manager_name;
                $data[$count]['email'] = $log->email;
                $data[$count]['activity_type'] = $log->activity_type_name;
                $data[$count]['phone'] = $log->mobile;
                $data[$count]['name'] = $log->activity_name;
                $data[$count]['activity'] = $log->value;
                $data[$count]['date'] = $this->formatDateTimeCountryWise($log->created_at, $adminTimezone);
                $count++;
            }

            $jsonData = array(
                "draw" => intval($draw),
                "recordsTotal" => intval($totalData),
                "recordsFiltered" => intval($totalFiltered),
                "data" => $data
            );
            return response()->json($jsonData);
        } catch (\Exception $ex) {
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

    /* ================ Activity Log Code End ======================= */
}
