<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CouponController extends Controller
{
    public function index(Request $request)
    {
        $title = 'Coupon';

        return view('admin.coupon.index', compact('title'));
    }

    public function saveCoupon(Request $request){
        try{
            $inputs = $request->all();

            $image_url = null;
            if ($request->hasFile('image')) {
                $couponFolder = config('constant.coupon');

                if (!Storage::disk('s3')->exists($couponFolder)) {
                    Storage::disk('s3')->makeDirectory($couponFolder);
                }

                $mainFile = Storage::disk('s3')->putFile($couponFolder, $request->file('image'), 'public');
                $fileName = basename($mainFile);
                $image_url = $couponFolder . '/' . $fileName;
            }

            Coupon::create([
                'title' => $inputs['title'],
                'image' => $image_url,
            ]);

            return response()->json(array('success' => true));
        }catch(\Exception $e){
            Log::info("$e");
            return response()->json(array('success' => false));
        }
    }

    public function couponJsonData(Request $request){
        $columns = array(
            0 => 'title',
            1 => 'image',
        );

        $limit = $request->input('length');
        $start = $request->input('start');
        $search = $request->input('search.value');
        $draw = $request->input('draw');
        $order = $columns[$request->input('order.0.column')];
        $dir = $request->input('order.0.dir');
        $adminTimezone = $this->getAdminUserTimezone();

        try {
            $data = [];
            $query = Coupon::query();

            if (!empty($search)) {
                $query =  $query->where(function ($q) use ($search) {
                    $q->where('title', 'LIKE', "%{$search}%");
                });
            }

            $totalData = count($query->get());
            $totalFiltered = $totalData;

            $couponData = $query->offset($start)
                ->limit($limit)
                ->orderBy($order, $dir)
                ->get();

            $count = 0;
            foreach($couponData as $coupon){
                $data[$count]['title'] = $coupon->title;
                $data[$count]['image'] = '<img src="'.$coupon->image.'" width="50" height="50" alt="Coupon Image">';

                $linkUrl = url('coupon/view/'.$coupon->id);
                $data[$count]['copy_link'] = '<a href="'.$linkUrl.'" class="btn-sm mx-1 btn btn-primary" target="_blank">Copy link</a>';

                $count++;
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
                "draw" => intval($draw),
                "recordsTotal" => 0,
                "recordsFiltered" => 0,
                "data" => []
            );
            return response()->json($jsonData);
        }
    }

    public function getImage($id){
        $coupon = Coupon::where('id',$id)->first();
        return view('admin.coupon.view',compact('coupon'));
    }
}
