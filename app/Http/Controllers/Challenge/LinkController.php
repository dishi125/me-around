<?php

namespace App\Http\Controllers\Challenge;

use App\Http\Controllers\Controller;
use App\Models\LinkChallengeSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class LinkController extends Controller
{
    public function index(Request $request)
    {
        $title = 'Link';
        return view('challenge.link.index', compact('title'));
    }

    public function saveLink(Request $request){
        try{
            DB::beginTransaction();
            $inputs = $request->all();
            $validator = Validator::make($inputs, [
                'title' => 'required',
                'link' => 'required',
            ], [
                'title.required' => 'The title is required.',
                'link.required' => 'The link is required.',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors(), 'success' => false]);
            }

            $data = LinkChallengeSetting::create([
                "title" => $inputs['title'],
                'link' => $inputs['link'],
            ]);

            DB::commit();
            return response()->json(array('success' => true));
        }catch(\Exception $e){
            Log::info("$e");
            DB::rollBack();
            return response()->json(array('success' => false));
        }
    }

    public function getJsonAllData(Request $request)
    {
        $columns = array(
            0 => 'title',
            1 => 'link',
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

            $userQuery = LinkChallengeSetting::query();

            if (!empty($search)) {
                $userQuery = $userQuery->where(function ($q) use ($search) {
                    $q->where('title', 'LIKE', "%{$search}%")
                        ->orWhere('link', 'LIKE', "%{$search}%");
                });
            }

            $totalData = count($userQuery->get());
            $totalFiltered = $totalData;

            $allData = $userQuery->offset($start)
                ->limit($limit)
                ->orderBy($order, $dir)
                ->get();

            $count = 0;
            foreach ($allData as $link) {
                $data[$count]['title'] = $link->title;
                $data[$count]['link'] = "<a href='$link->link' target='_blank'>$link->link</a>";

                $count++;
            }

            $jsonData = array(
                "draw" => intval($draw),
                "recordsTotal" => intval($totalData),
                "recordsFiltered" => intval($totalFiltered),
                "data" => $data
            );
            return response()->json($jsonData);
        } catch (Exception $ex) {
            Log::info('Exception all link list');
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

}
