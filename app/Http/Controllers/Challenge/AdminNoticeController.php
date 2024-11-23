<?php

namespace App\Http\Controllers\Challenge;

use App\Http\Controllers\Controller;
use App\Models\ChallengeAdmin;
use App\Models\ChallengeAdminNotice;
use App\Models\ChallengeNotice;
use App\Models\LinkChallengeSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class AdminNoticeController extends Controller
{
    public function index(Request $request)
    {
        $title = 'Admin Notice';
        return view('challenge.admin-notice.index', compact('title'));
    }

    public function saveData(Request $request){
        try{
            DB::beginTransaction();
            $inputs = $request->all();
            $validator = Validator::make($inputs, [
                'title' => 'required',
                'notice' => 'required',
            ], [
                'title.required' => 'The title is required.',
                'notice.required' => 'The notice is required.',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors(), 'success' => false]);
            }

            $data = ChallengeAdminNotice::create([
                "title" => $inputs['title'],
                'notice' => $inputs['notice'],
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
            1 => 'notice',
            2 => 'created_at',
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

            $userQuery = ChallengeAdminNotice::query();

            if (!empty($search)) {
                $userQuery = $userQuery->where(function ($q) use ($search) {
                    $q->where('title', 'LIKE', "%{$search}%")
                        ->orWhere('notice', 'LIKE', "%{$search}%");
                });
            }

            $totalData = count($userQuery->get());
            $totalFiltered = $totalData;

            $allData = $userQuery->offset($start)
                ->limit($limit)
                ->orderBy($order, $dir)
                ->get();

            $count = 0;
            foreach ($allData as $notice) {
                $data[$count]['title'] = $notice->title;
                $data[$count]['notice'] = $notice->notice;
                $data[$count]['time'] = $this->formatDateTimeCountryWise($notice->created_at,$adminTimezone);

                $editBtn = '<a href="javascript:void(0)" role="button" onclick="editNotice('.$notice->id.')" class="btn btn-primary btn-sm mx-1" data-toggle="tooltip" data-original-title="Edit"><i class="fa fa-edit" style="font-size: 15px;"></i></a>';
                $deleteBtn = '<a href="javascript:void(0)" role="button" onclick="deleteNotice('.$notice->id.')" class="btn btn-primary btn-sm mx-1" data-toggle="tooltip" data-original-title="Delete"><i class="fa fa-trash" style="font-size: 15px;"></i></a>';
                $data[$count]['action'] = "$editBtn $deleteBtn";

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

    public function editImage(){
        $admin = ChallengeAdmin::where('id',1)->first();

        return view('challenge.admin-notice.edit-image-popup',compact('admin'));
    }

    public function updateImage(Request $request){
        try{
            DB::beginTransaction();
            $inputs = $request->all();
            $validator = Validator::make($inputs, [
                'image' => 'image|mimes:jpeg,jpg,png,gif,svg',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors(), 'success' => false]);
            }

            $thumb_path = DB::table('challenge_admin')->where('id',1)->pluck('image')->first();
            if ($request->hasFile('admin_image')) {
                Storage::disk('s3')->delete($thumb_path);

                $thumbFolder = config('constant.challenge_admin');
                if (!Storage::exists($thumbFolder)) {
                    Storage::makeDirectory($thumbFolder);
                }
                $thumb = Storage::disk('s3')->putFile($thumbFolder, $request->file('admin_image'),'public');
                $fileName = basename($thumb);
                $thumb_path = $thumbFolder . '/' . $fileName;
            }

            $adminThumb = ChallengeAdmin::updateOrCreate([
                'id' => 1,
            ],[
                "image" => $thumb_path,
            ]);

            DB::commit();
            return response()->json(array('success' => true));
        }catch(\Exception $e){
            Log::info("$e");
            DB::rollBack();
            return response()->json(array('success' => false));
        }
    }

    public function editBio(){
        $admin = ChallengeAdmin::where('id',1)->first();

        return view('challenge.admin-notice.edit-bio-popup',compact('admin'));
    }

    public function updateBio(Request $request){
        try{
            DB::beginTransaction();
            $inputs = $request->all();
            $validator = Validator::make($inputs, [
                'bio' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors(), 'success' => false]);
            }

            $adminBio = ChallengeAdmin::updateOrCreate([
                'id' => 1,
            ],[
                "bio" => $inputs['bio'],
            ]);

            DB::commit();
            return response()->json(array('success' => true));
        }catch(\Exception $e){
            Log::info("$e");
            DB::rollBack();
            return response()->json(array('success' => false));
        }
    }

    public function editData($id){
        $ChallengeAdminNotice = ChallengeAdminNotice::where('id',$id)->first();

        return view('challenge.admin-notice.edit-popup',compact('ChallengeAdminNotice'));
    }

    public function updateData(Request $request){
        try{
            DB::beginTransaction();
            $inputs = $request->all();
            $validator = Validator::make($inputs, [
                'edit_title' => 'required',
                'edit_notice' => 'required',
            ],[
                'edit_title.required' => 'The title is required.',
                'edit_notice.required' => 'The notice is required.',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors(), 'success' => false]);
            }

            $adminNotice = ChallengeAdminNotice::where('id',$inputs['admin_notice_id'])->update([
                "title" => $inputs['edit_title'],
                "notice" => $inputs['edit_notice'],
            ]);

            DB::commit();
            return response()->json(array('success' => true));
        }catch(\Exception $e){
            Log::info("$e");
            DB::rollBack();
            return response()->json(array('success' => false));
        }
    }

    public function getDelete($id)
    {
        return view('challenge.admin-notice.delete-popup', compact('id'));
    }

    public function deleteData(Request $request){
        $inputs = $request->all();
        $noticeId = $inputs['noticeId'];
        try {
            DB::beginTransaction();

            if(!empty($noticeId)){
                ChallengeAdminNotice::where('id',$noticeId)->delete();

                DB::commit();
                return $this->sendSuccessResponse('Admin notice deleted successfully.', 200);
            }else{
                return $this->sendSuccessResponse('Failed to delete admin notice.', 201);
            }
        } catch (\Exception $ex) {
            DB::rollBack();
            return $this->sendFailedResponse('Failed to delete admin notice.', 201);
        }
    }

}
