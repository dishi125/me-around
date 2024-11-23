<?php

namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Log;
use Storage;
use Validator;
use App\Models\Category;
use App\Models\Status;
use App\Models\CategoryTypes;
use App\Models\Currency;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class CurrencyController extends Controller
{
    public function __construct()
    {
        
    }

    /* ================= Currency code start ======================== */

    public function index()
    {
        $title = "Currency List";
        return view('admin.category.index-currency', compact('title'));

    }

    public function getCurrencyJsonData(Request $request)
    {
        try {
            Log::info('Start get currency list');
            $user = Auth::user();
            $columns = array(
                0 => 'name',
                1 => 'created_at',
            );

            $limit = $request->input('length');
            $start = $request->input('start');
            $search = $request->input('search.value');
            $draw = $request->input('draw');
            $order = $columns[$request->input('order.0.column')];
            $dir = $request->input('order.0.dir');

            $query = Currency::where('status_id',Status::ACTIVE);
            $totalData = $query->count();
            $totalFiltered = $totalData;

            if (!empty($search)) {
                $query = $query->where('name', 'LIKE', "%{$search}%");
                $totalFiltered = $query->count();
            }

            $currencies = $query->offset($start)
                ->limit($limit)
                ->orderBy($order, $dir)
                ->get();

            $data = array();

            if (!empty($currencies)) {
                foreach ($currencies as $value) {
                    $currency_id = $value->id;
                    $nestedData['id'] = $currency_id;
                    $edit = route('admin.currency.edit', $currency_id);
                    $nestedData['name'] = $value->name;
                    $nestedData['created_at'] = $value->created_at;                    
                    $editButton = "<a role='button' href='" . $edit . "' title='' data-original-title='Edit' class='btn btn-primary' data-toggle='tooltip'><i class='fa fa-edit'></i></a>";
                    $deleteButton = "<a href='javascript:void(0)' role='button' onclick='deleteCategory(" . $currency_id . ")' class='btn btn-danger' data-toggle='tooltip' data-original-title=" . Lang::get('general.delete') . "><i class='fa fa-trash'></button>";
                    $nestedData['actions'] = "$editButton $deleteButton";
                    $data[] = $nestedData;
                }
            }

            $jsonData = array(
                "draw" => intval($draw),
                "recordsTotal" => intval($totalData),
                "recordsFiltered" => intval($totalFiltered),
                "data" => $data,
            );
            Log::info('End get currency list');
            return response()->json($jsonData);
        } catch (\Exception $ex) {
            Log::info('Exception in the get currency list');
            Log::info($ex);
            return response()->json([]);
        }
    }  

    /* ================= currency code end ======================== */

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $title = "Add Currency"; 
        return view('admin.category.form-currency', compact('title'));
    }

    public function store(Request $request)
    {         
        try {
            DB::beginTransaction();
            Log::info('Start code for the add currency');
            $inputs = $request->all();
            $validator = Validator::make($request->all(), [
                'name' => 'required',
            ], [], [
                'name' => 'Name',
            ]);
            if ($validator->fails()) {
                notify()->error("Validation Error", "Error", "topRight");
                return redirect()->back()->withErrors($validator)->withInput();
            }       
                   
            $categoryData = Currency::create(["name" => $inputs['name'],'status_id' => Status::ACTIVE]);
            DB::commit();
            Log::info('End the code for the add currency');
            notify()->success("Currency ". trans("messages.insert-success"), "Success", "topRight");
            return redirect()->route('admin.currency.index');
        } catch (\Exception $e) {
            Log::info('Exception in the add currency');
            Log::info($e);
            notify()->error("Currency ". trans("messages.insert-error"), "Error", "topRight");
            return redirect()->route('admin.currency.index');
        }
    }
    
    public function edit(Currency $currency)
    {
        $title = "Edit Currency";
        return view('admin.category.form-currency', compact('title', 'currency'));
    }
    
    public function update(Request $request, Currency $currency)
    {
        try {
            Log::info('Start code for the update Currency');
            DB::beginTransaction();
            $inputs = $request->all();
            $validator = Validator::make($request->all(), [
                'name' => 'required',
            ], [], [
                'name' => 'Name',
            ]);
            if ($validator->fails()) {
                notify()->error("Validation Error", "Error", "topRight");
                return redirect()->back()->withErrors($validator)->withInput();
            }        
                    
            $categoryData = Currency::where('id',$currency->id)->update(["name" => $inputs['name']]);

            Log::info('End the code for the update currency');
            DB::commit();
            notify()->success("Currency ". trans("messages.update-success"), "Success", "topRight");
            return redirect()->route('admin.currency.index');
        } catch (\Exception $e) {
            Log::info('Exception in the update currency.');
            Log::info($e);
            notify()->error("Currency ". trans("messages.update-error"), "Error", "topRight");
            return redirect()->route('admin.currency.index');
        }
    }
    
    public function delete($id)
    {   
        return view('admin.category.delete-currency', compact('id'));
    }

    public function destroy(Currency $currency)
    {
        try {
            Log::info('Currency delete code start.');
            DB::beginTransaction();            
            Currency::where('id',$currency->id)->delete();
            DB::commit();
            Log::info('Currency delete code end.');
            notify()->success("Currency deleted successfully", "Success", "topRight");
            return redirect()->route('admin.currency.index');
        } catch (\Exception $ex) {
            DB::rollBack();
            Log::info('Currency delete exception.');
            Log::info($ex);
            notify()->error("Failed to deleted currency", "Error", "topRight");
            return redirect()->route('admin.currency.index');        
        }
    }
    
}
