<?php

namespace App\Http\Controllers\Admin;

use Log;
use Illuminate\Http\Request;
use App\Models\BrandProducts;
use App\Models\ProductOrders;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Lang;

class ProductOrdersController extends Controller
{
    public function index(Request $request)
    {
        $title = "Product Orders";

        $products = BrandProducts::orderBy('sort_order')->get();

        $products = collect($products)->mapWithKeys(function ($value) {
            return [$value->id => $value->name];
        })->toArray();
        
        DB::table('product_orders')->where('is_admin_read',1)->update(['is_admin_read' => 0]);
        
        return view('admin.product-orders.index', compact('title','products'));
    }

    public function getJsonData(Request $request){

        try {
            $columns = array(
                0 => 'users_detail.name',
                1 => 'brand_products.name',
                2 => 'product_orders.coin_amount',
                3 => 'users_detail.mobile',
                4 => 'product_orders.created_at',
            );

            $filter = $request->input('filter');
            $limit = $request->input('length');
            $start = $request->input('start');
            $search = $request->input('search.value');
            $draw = $request->input('draw');
            $order = $columns[$request->input('order.0.column')];
            $dir = $request->input('order.0.dir');

            $query = ProductOrders::join('users_detail','users_detail.user_id','product_orders.user_id')
                ->join('brand_products','brand_products.id','product_orders.product_id')
                ->select(
                    'product_orders.*',
                    'users_detail.name as username',
                    'users_detail.mobile as phone',
                    'brand_products.name as productname'
                );

            if(!empty($filter)){
                $query = $query->where('product_orders.product_id',$filter);
            }

            $totalData = $query->count();
            $totalFiltered = $totalData;

            if (!empty($search)) {
                $query = $query->where(function ($q) use ($search) {
                    $q->where('users_detail.name', 'LIKE', "%{$search}%")
                        ->orWhere('brand_products.name', 'LIKE', "%{$search}%");
                });
                $totalFiltered = $query->count();
            }

            $orders = $query->offset($start)
                ->limit($limit)
                ->orderBy($order, $dir)
                ->get();

                $data = array();

            if (!empty($orders)) {
                foreach ($orders as $value) {
                    $nestedData['user_name'] = $value->username;
                    $nestedData['product_name'] = $value->productname;
                    $nestedData['price'] = $value->coin_amount;
                    $nestedData['phone'] = $value->phone;
                    $nestedData['date'] = date("Y-m-d H:i:s",strtotime($value->created_at));
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
        } catch (\Exception $ex) {
            Log::info($ex);
            $jsonData = array(
                "draw" => intval(0),
                "recordsTotal" => intval(0),
                "recordsFiltered" => intval(0),
                "data" => [],
            );
            return response()->json($jsonData);
        }
    }
}
