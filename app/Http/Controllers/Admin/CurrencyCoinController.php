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
use App\Models\CurrencyCoin;
use App\Models\Currency;
use App\Models\ReloadCoinCurrency;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\Country;

class CurrencyCoinController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:currency-coin-list', ['only' => ['index']]);
    }


    /* ================= Currency code start ======================== */

    public function index()
    {
        $title = "Reload Coin Currency List";
        return view('admin.currency-coin.index', compact('title'));

    }

    public function indexCoins()
    {
        $title = "Currency Coins List";
        return view('admin.currency-coin.index-coins', compact('title'));

    }

    public function getCurrencyCoinListJsonData(Request $request)
    {
        try {
            Log::info('Start get currency list');
            $user = Auth::user();
            $columns = array(
                0 => 'priority',
                1 => 'name',
                2 => 'bank_name',
                3 => 'bank_account_number',
                4 => 'created_at',
            );

            $limit = $request->input('length');
            $start = $request->input('start');
            $search = $request->input('search.value');
            $draw = $request->input('draw');
            $order = $columns[$request->input('order.0.column')];
            $dir = $request->input('order.0.dir');
            $adminTimezone = $this->getAdminUserTimezone();

            $query = ReloadCoinCurrency::where('status_id',Status::ACTIVE);
            $totalData = $query->count();
            $totalFiltered = $totalData;

            if (!empty($search)) {
                $query = $query->where(function($q) use ($search){
                    $q->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('bank_name', 'LIKE', "%{$search}%")
                    ->orWhere('bank_account_number', 'LIKE', "%{$search}%");
                });

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
                    $edit = route('admin.currency.coin.edit.currency', $currency_id);
                    $nestedData['name'] = $value->name;
                    $nestedData['priority'] = $value->priority;
                    $nestedData['bank_name'] = $value->bank_name;
                    $nestedData['bank_account_number'] = $value->bank_account_number;
                    $nestedData['created_at'] = $this->formatDateTimeCountryWise($value->display_created_at,$adminTimezone,'d-m-Y H:i');
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

    public function getCurrencyCoinJsonData(Request $request)
    {
        try {
            Log::info('Start get currency coin list');
            $user = Auth::user();
            $columns = array(
                0 => 'reload_coin_currency.name',
                1 => 'coins',
                2 => 'created_at',
            );

            $limit = $request->input('length');
            $start = $request->input('start');
            $search = $request->input('search.value');
            $draw = $request->input('draw');
            $order = $columns[$request->input('order.0.column')];
            $dir = $request->input('order.0.dir');
            $adminTimezone = $this->getAdminUserTimezone();

            $query = CurrencyCoin::join('reload_coin_currency','reload_coin_currency.id','currency_coin.currency_id')->select('currency_coin.*','reload_coin_currency.name');
            $totalData = $query->count();
            $totalFiltered = $totalData;

            if (!empty($search)) {
                $query = $query->where(function($q) use ($search){
                    $q->where('reload_coin_currency.name', 'LIKE', "%{$search}%")
                    ->orWhere('currency_coin.coins', 'LIKE', "%{$search}%");
                });
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
                    $edit = route('admin.currency-coin.edit', $currency_id);
                    $nestedData['currency_name'] = $value->name;
                    $nestedData['coins'] = $value->coins;
                    $nestedData['created_at'] = $this->formatDateTimeCountryWise($value->display_created_at,$adminTimezone,'d-m-Y H:i');
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
            Log::info('End get currency coin list');
            return response()->json($jsonData);
        } catch (\Exception $ex) {
            Log::info('Exception in the get currency coin list');
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
        $title = "Add Currency Coins";
        $currency_list = ReloadCoinCurrency::pluck('name', 'id')->all();
        return view('admin.currency-coin.form', compact('title','currency_list'));
    }

    public function store(Request $request)
    {
        try {
            DB::beginTransaction();
            Log::info('Start code for the add currency coins');
            $inputs = $request->all();
            $validator = Validator::make($request->all(), [
                'currency_id' => 'required',
                'coins' => 'required',
            ], [], [
                'currency_id' => 'Currency',
                'coins' => 'Coins',
            ]);
            if ($validator->fails()) {
                notify()->error("Validation Error", "Error", "topRight");
                return redirect()->back()->withErrors($validator)->withInput();
            }

            $categoryData = CurrencyCoin::create(["currency_id" => $inputs['currency_id'],'coins' => $inputs['coins']]);
            DB::commit();
            Log::info('End the code for the add currency coins');
            notify()->success("Currency coins". trans("messages.insert-success"), "Success", "topRight");
            return redirect()->route('admin.currency-coin.coin-index');
        } catch (\Exception $e) {
            Log::info('Exception in the add currency coins');
            Log::info($e);
            notify()->error("Currency coins". trans("messages.insert-error"), "Error", "topRight");
            return redirect()->route('admin.currency-coin.coin-index');
        }
    }

    public function edit(CurrencyCoin $currencyCoin)
    {
        $title = "Edit Currency Coins";
        $currency_list = ReloadCoinCurrency::pluck('name', 'id')->all();
        return view('admin.currency-coin.form', compact('title', 'currencyCoin','currency_list'));
    }

    public function update(Request $request, CurrencyCoin $currencyCoin)
    {
        try {
            Log::info('Start code for the update Currency coins');
            DB::beginTransaction();
            $inputs = $request->all();
            $validator = Validator::make($request->all(), [
                'currency_id' => 'required',
                'coins' => 'required',
            ], [], [
                'currency_id' => 'Currency',
                'coins' => 'Coins',
            ]);
            if ($validator->fails()) {
                notify()->error("Validation Error", "Error", "topRight");
                return redirect()->back()->withErrors($validator)->withInput();
            }

            $categoryData = CurrencyCoin::where('id',$currencyCoin->id)->update(["currency_id" => $inputs['currency_id'], "coins" => $inputs['coins']]);

            Log::info('End the code for the update currency coins');
            DB::commit();
            notify()->success("Currency coins ". trans("messages.update-success"), "Success", "topRight");
            return redirect()->route('admin.currency-coin.coin-index');
        } catch (\Exception $e) {
            Log::info('Exception in the update currency coins.');
            Log::info($e);
            notify()->error("Currency coins". trans("messages.update-error"), "Error", "topRight");
            return redirect()->route('admin.currency-coin.coin-index');
        }
    }

    public function delete($id)
    {
        return view('admin.currency-coin.delete', compact('id'));
    }

    public function destroy(CurrencyCoin $currencyCoin)
    {
        try {
            Log::info('Currency coins delete code start.');
            DB::beginTransaction();
            CurrencyCoin::where('id',$currencyCoin->id)->delete();
            DB::commit();
            Log::info('Currency coins delete code end.');
            notify()->success("Currency coins deleted successfully", "Success", "topRight");
            return redirect()->route('admin.currency-coin.coin-index');
        } catch (\Exception $ex) {
            DB::rollBack();
            Log::info('Currency coins delete exception.');
            Log::info($ex);
            notify()->error("Failed to deleted currency coins", "Error", "topRight");
            return redirect()->route('admin.currency-coin.coin-index');
        }
    }

    /* check name of role already exist */
    public function checkCurrency(Request $request, $id)
    {
        Log::info("start code check currency id.");
        $currency_id = $request->currency_id;
        if ($id != 0) {
            $check = CurrencyCoin::where('currency_id', $currency_id)
                ->where('id', '!=', $id)
                ->count();
            if ($check > 0) {
                echo 'false';
            } else {
                echo 'true';
            }
        } else {
            $check = CurrencyCoin::where('currency_id', $currency_id)
                ->count();
            if ($check > 0) {
                echo 'false';
            } else {
                echo 'true';
            }
        }
        Log::info("end code check currency id.");
    }


    /* Reload Coin Currency */

    public function createCurrency()
    {
        $title = "Add Currency";
        $countries = Country::WhereNotNull('code')->where('is_show',1)->get();
        $countries = collect($countries)->mapWithKeys(function ($value) {
            return [$value->id => $value->name];
        })->toArray();

        return view('admin.currency-coin.form-currency', compact('title','countries'));
    }

    public function storeCurrency(Request $request)
    {
        try {
            DB::beginTransaction();
            Log::info('Start code for the add currency');
            $inputs = $request->all();
            $validator = Validator::make($request->all(), [
                'name' => 'required',
                'priority' => 'required',
                'bank_name' => 'required',
                'bank_account_number' => 'required',
            ], [], [
                'name' => 'Name',
                'priority' => 'Order',
                'bank_name' => 'Bank Name',
                'bank_account_number' => 'Bank Account Number',
            ]);
            if ($validator->fails()) {
                notify()->error("Validation Error", "Error", "topRight");
                return redirect()->back()->withErrors($validator)->withInput();
            }

            $categoryData = ReloadCoinCurrency::create([
                                                    "name" => $inputs['name'],
                                                    "priority" => $inputs['priority'],
                                                    "bank_name" => $inputs['bank_name'],
                                                    "bank_account_number" => $inputs['bank_account_number'],
                                                    "country_id" => $inputs['country_id'] ?? null,
                                                    'status_id' => Status::ACTIVE
                                                ]);
            DB::commit();
            Log::info('End the code for the add currency');
            notify()->success("Currency ". trans("messages.insert-success"), "Success", "topRight");
            return redirect()->route('admin.currency-coin.index');
        } catch (\Exception $e) {
            Log::info('Exception in the add currency');
            Log::info($e);
            notify()->error("Currency ". trans("messages.insert-error"), "Error", "topRight");
            return redirect()->route('admin.currency-coin.index');
        }
    }

    public function editCurrency($id)
    {
        $title = "Edit Currency";
        $currency = ReloadCoinCurrency::find($id);
        $countries = Country::WhereNotNull('code')->where('is_show',1)->get();
        $countries = collect($countries)->mapWithKeys(function ($value) {
            return [$value->id => $value->name];
        })->toArray();

        return view('admin.currency-coin.form-currency', compact('title', 'currency', 'countries'));
    }

    public function updateCurrency(Request $request, $id)
    {
        try {
            Log::info('Start code for the update Currency');
            DB::beginTransaction();
            $inputs = $request->all();
            $validator = Validator::make($request->all(), [
                'name' => 'required',
                'priority' => 'required',
                'bank_name' => 'required',
                'bank_account_number' => 'required',
            ], [], [
                'name' => 'Name',
                'priority' => 'Order',
                'bank_name' => 'Bank Name',
                'bank_account_number' => 'Bank Account Number',
            ]);
            if ($validator->fails()) {
                notify()->error("Validation Error", "Error", "topRight");
                return redirect()->back()->withErrors($validator)->withInput();
            }

            $categoryData = ReloadCoinCurrency::where('id',$id)->update([
                                                "name" => $inputs['name'],
                                                "priority" => $inputs['priority'],
                                                "bank_name" => $inputs['bank_name'],
                                                "bank_account_number" => $inputs['bank_account_number'],
                                                "country_id" => $inputs['country_id'] ?? null,
                                            ]);

            Log::info('End the code for the update currency');
            DB::commit();
            notify()->success("Currency ". trans("messages.update-success"), "Success", "topRight");
            return redirect()->route('admin.currency-coin.index');
        } catch (\Exception $e) {
            Log::info('Exception in the update currency.');
            Log::info($e);
            notify()->error("Currency ". trans("messages.update-error"), "Error", "topRight");
            return redirect()->route('admin.currency-coin.index');
        }
    }

    public function deleteCurrency($id)
    {
        return view('admin.currency-coin.delete-currency', compact('id'));
    }

    public function destroyCurrency($id)
    {
        try {
            Log::info('Currency delete code start.');
            DB::beginTransaction();
            ReloadCoinCurrency::where('id',$id)->delete();
            DB::commit();
            Log::info('Currency delete code end.');
            notify()->success("Currency deleted successfully", "Success", "topRight");
            return redirect()->route('admin.currency-coin.index');
        } catch (\Exception $ex) {
            DB::rollBack();
            Log::info('Currency delete exception.');
            Log::info($ex);
            notify()->error("Failed to deleted currency", "Error", "topRight");
            return redirect()->route('admin.currency-coin.index');
        }
    }


    /* Reload Coin Currency */

}
