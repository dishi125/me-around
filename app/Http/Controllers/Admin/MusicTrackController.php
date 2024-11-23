<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MusicTrack;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class MusicTrackController extends Controller
{
    public function index()
    {
        $title = "Music List";
        return view('admin.music-track.index', compact('title'));
    }

    public function create()
    {
        $title = "Add Music";
        return view('admin.music-track.form', compact('title'));
    }

    public function getJsonData(Request $request)
    {
        try {
            Log::info('Start get music list');
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

            $query = MusicTrack::query();
            $totalData = $query->count();
            $totalFiltered = $totalData;

            if (!empty($search)) {
                $query = $query->where('title', 'LIKE', "%{$search}%");
                $totalFiltered = $query->count();
            }

            $music_track = $query->offset($start)
                ->limit($limit)
                ->orderBy($order, $dir)
                ->get();

            $data = array();
            if (!empty($music_track)) {
                foreach ($music_track as $value) {
                    $file = Storage::disk('s3')->url($value->file);
                    $nestedData['file'] = '<audio preload="auto" controls><source src="'.$file.'"></source></audio>';
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

            Log::info('End get music list');
            return response()->json($jsonData);
        } catch (\Exception $ex) {
            Log::info('Exception in music list');
            Log::info($ex);
            return response()->json([]);
        }
    }

    public function store(Request $request)
    {
        $inputs = $request->all();
        try {
            DB::beginTransaction();
            Log::info('Start code for the add category');
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
                $categoryFolder = config('constant.music-track');
                if (!Storage::exists($categoryFolder)) {
                    Storage::makeDirectory($categoryFolder);
                }
                $logo = Storage::disk('s3')->putFile($categoryFolder, $request->file('file'), 'public');
                $fileName = basename($logo);
                $data['file'] = $categoryFolder . '/' . $fileName;
            }

            MusicTrack::create($data);

            DB::commit();
            notify()->success("Music track " . trans("messages.insert-success"), "Success", "topRight");
            return redirect()->route('admin.music-track.index');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::info($e);
            notify()->error("Music track ". trans("messages.insert-error"), "Error", "topRight");
            return redirect()->route('admin.music-track.index');
        }
    }

}
