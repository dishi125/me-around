<?php

namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Traits\HasRoles;

class RoleController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    function __construct()
    {
        $this->middleware('permission:role-list', ['only' => ['index']]);
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $title = "Role List";
        return view('admin.roles.index', compact('title'));
    }


    public function getJsonData(Request $request)
    {
        $columns = array(
            0 => 'id',
            1 => 'display_name, name',
        );
        $user = Auth::user();
        $limit = $request->input('length');
        $start = $request->input('start');
        $search = $request->input('search.value');
        $draw = $request->input('draw');
        $order = $columns[$request->input('order.0.column')];
        $dir = $request->input('order.0.dir');
        $query = Role::select(
            'id',
            'name',
            'display_name'
        );
        $totalData = $query->count();
        $totalFiltered = $totalData;

        if (!empty($search)) {
            $query = $query->where('name', 'LIKE', "%{$search}%")->orWhere('display_name', 'LIKE', "%{$search}%");
            $totalFiltered = $query->count();
        }
        $filters = $query->offset($start)
            ->limit($limit)
            ->orderBy($order, $dir)
            ->get();

        $data = array();

        if (!empty($filters)) {
            foreach ($filters as $value) {
                $roleId = $value->id;
                $nestedData['id'] = $roleId;
                $edit = route('admin.roles.edit', $roleId);
                $display_name = str_replace("_", " ", strtolower($value->display_name));
                $name = str_replace("_", " ", strtolower($value->name));
                $nestedData['name'] = ($display_name) ? ucwords($display_name) : ucwords($name);
                $editButton = $deleteButton = null;
                $editButton = "<a role='button' href='" . $edit . "' title='' data-original-title='Edit' class='btn btn-primary' data-toggle='tooltip'><i class='fa fa-edit'></i></a>";
                // if ($user->can("role-delete")) {
                //     $deleteButton = "<a href='javascript:void(0)' role='button' onclick='deleteRecord(" . $roleId . ")' class='btn btn-outline-danger' data-toggle='tooltip' data-original-title='delete)'><i class='fa fa-trash'></button>";
                // }
                $nestedData['actions'] = "$editButton";
                $data[] = $nestedData;
            }
        }

        $jsonData = array(
            "draw" => intval($draw),
            "recordsTotal" => intval($totalData),
            "recordsFiltered" => intval($totalFiltered),
            "data" => $data,
        );
        return response()->json($jsonData);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $title = "Add Role";
        $permission = Permission::get();
        return view('admin.roles.form', compact('title', 'permission'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        Log::info('Start code for the add roles.');
        DB::beginTransaction();
        try {
            $inputs = $request->all();
            $name = strtolower(str_replace(" ", "_", $inputs['name']));
            $role = Role::create(['name' => $inputs['name'], 'display_name' => $inputs['name']]);
            if (!empty($inputs['permission'])) {
                $role->syncPermissions($inputs['permission']);
            }
            DB::commit();
            Log::info('End code for the add roles.');
            return redirect()->route('admin.roles')->with(['toastr' => ['success' => "Role" . trans("message.insert-success")]]);
        } catch (\Exception $exception) {
            DB::rollBack();
            Log::info('Exception code for the add roles.' . $exception);
            Log::info($exception);
            return redirect()->route('admin.roles')->with(['toastr' => ['error' => "Role" . trans("message.insert-error")]]);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $title = "Edit Role";
        $rolesData = Role::find($id);
        $permission = Permission::get();
        $rolePermissions = DB::table("role_has_permissions")->where("role_has_permissions.role_id", $id)
            ->pluck('role_has_permissions.permission_id', 'role_has_permissions.permission_id')
            ->all();
        return view('admin.roles.form', compact('title', 'rolesData', 'permission', 'rolePermissions'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        Log::info('Start code for the update roles.');
        DB::beginTransaction();
        try {
            $inputs = $request->all();
            $role = Role::find($id);
            $name = strtolower(str_replace(" ", "_", $inputs['name']));
            $role->name = $inputs['name'];
            $role->display_name = $inputs['name'];
            $role->save();
            if (!empty($inputs['permission'])) {
                $role->syncPermissions($inputs['permission']);
            }
            DB::commit();
            Log::info('End code for the update roles.');
            return redirect()->route('admin.roles')->with(['toastr' => ['success' => "Role" . trans("message.update-success")]]);
        } catch (\Exception $exception) {
            DB::rollBack();
            Log::info('Exception code for the update roles.' . $exception);
            Log::info($exception);
            return redirect()->route('admin.roles')->with(['toastr' => ['error' => "Role" . trans("message.update-error")]]);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */

    public function deleteRecord($id)
    {
        return view('admin.roles.delete', compact('id'));
    }

    public function destroy($id)
    {
        Log::info('Start code for the delete roles.');
        DB::beginTransaction();
        try {
            Role::where("id", $id)->delete();
            DB::commit();
            Log::info('End code for the delete roles.');
            return redirect()->route('admin.roles')->with(['toastr' => ['success' => "Role" . trans("message.delete-success")]]);
        } catch (\Exception $exception) {
            DB::rollBack();
            Log::info('exception code for the delete roles.' . $exception);
            Log::info($exception);
            return redirect()->route('admin.roles')->with(['toastr' => ['error' => "Role" . trans("message.delete-error")]]);
        }
    }


    /* check name of role already exist */
    public function checkUserRole(Request $request, $id)
    {
        Log::info("start code check role name.");
        if ($id != 0) {
            $name = $request->name;
            $userRole = Role::where('name', $name)
                ->where('id', '!=', $id)
                ->count();
            if ($userRole > 0) {
                echo 'false';
            } else {
                echo 'true';
            }
        } else {
            $name = $request->name;
            $userRole = Role::where('name', $name)
                ->count();
            if ($userRole > 0) {
                echo 'false';
            } else {
                echo 'true';
            }
        }
        Log::info("end code check role name.");
    }
}
