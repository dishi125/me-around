<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GifFilter;
use App\Models\MusicTrack;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class GifController extends Controller
{
    public function index()
    {
        $title = "Gif Filter List";
        return view('admin.gif-filter.index', compact('title'));
    }

    public function create()
    {
        $title = "Add Gif Filter";
        return view('admin.gif-filter.form', compact('title'));
    }

    public function store(Request $request)
    {
        $inputs = $request->all();
        try {
            DB::beginTransaction();
            Log::info('Start code for the add gif filter');
            $validator = Validator::make($request->all(), [
                'file' => 'required',
                'title' => 'required',
            ]);
            if ($validator->fails()) {
                return redirect()->back()->withErrors($validator)->withInput();
            }

            $data = [
                "title" => $inputs['title'],
            ];

            if ($request->hasFile('file')) {
                $categoryFolder = config('constant.gif-filter');
                if (!Storage::exists($categoryFolder)) {
                    Storage::makeDirectory($categoryFolder);
                }
                $logo = Storage::disk('s3')->putFile($categoryFolder, $request->file('file'), 'public');
                $fileName = basename($logo);
                $data['file'] = $categoryFolder . '/' . $fileName;
            }

            GifFilter::create($data);

            DB::commit();
            notify()->success("Gif filter " . trans("messages.insert-success"), "Success", "topRight");
            return redirect()->route('admin.gif-filter.index');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::info($e);
            notify()->error("Gif filter ". trans("messages.insert-error"), "Error", "topRight");
            return redirect()->route('admin.gif-filter.index');
        }
    }

    public function getJsonData(Request $request)
    {
        try {
            Log::info('Start get gif filter list');
            $user = Auth::user();
            $columns = array(
                0 => 'title',
                1 => 'file',
            );

            $limit = $request->input('length');
            $start = $request->input('start');
            $search = $request->input('search.value');
            $draw = $request->input('draw');
            $order = $columns[$request->input('order.0.column')];
            $dir = $request->input('order.0.dir');
            // dd($search);

            $query = GifFilter::query();
            $totalData = $query->count();
            $totalFiltered = $totalData;

            if (!empty($search)) {
                $query = $query->where('title', 'LIKE', "%{$search}%");
                $totalFiltered = $query->count();
            }

            $gif_filters = $query->offset($start)
                ->limit($limit)
                ->orderBy($order, $dir)
                ->get();

            $data = array();
            if (!empty($gif_filters)) {
                foreach ($gif_filters as $value) {
                    $file = Storage::disk('s3')->url($value->file);
                    $nestedData['file'] = '<img src="'.$file.'" width="50px" height="50px" alt="GIF filter">';
                    $nestedData['title'] = $value->title;

                    $data[] = $nestedData;
                }
            }

            $jsonData = array(
                "draw" => intval($draw),
                "recordsTotal" => intval($totalData),
                "recordsFiltered" => intval($totalFiltered),
                "data" => $data,
            );

            Log::info('End get Gif filter list');
            return response()->json($jsonData);
        } catch (\Exception $ex) {
            Log::info('Exception in gif filter list');
            Log::info($ex);
            return response()->json([]);
        }
    }

}
