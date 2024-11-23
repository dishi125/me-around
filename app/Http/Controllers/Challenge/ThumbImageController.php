<?php

namespace App\Http\Controllers\Challenge;

use App\Http\Controllers\Controller;
use App\Models\ChallengeCategory;
use App\Models\ChallengeThumb;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ThumbImageController extends Controller
{
    public function index()
    {
        $title = "Thumb image list";

        return view('challenge.thumb-image.index', compact('title'));
    }

    public function saveThumb(Request $request){
        try{
            DB::beginTransaction();
            $inputs = $request->all();
            $validator = Validator::make($inputs, [
                'image' => 'required|image|mimes:jpeg,jpg,png,gif,svg',
                'order' => 'required',
                'challenge_type' => 'required',
                'category' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors(), 'success' => false]);
            }

            if ($request->hasFile('image')) {
                $thumbFolder = config('constant.challenge_thumb');
                if (!Storage::exists($thumbFolder)) {
                    Storage::makeDirectory($thumbFolder);
                }
                $thumb = Storage::disk('s3')->putFile($thumbFolder, $request->file('image'),'public');
                $fileName = basename($thumb);
                $thumb_path = $thumbFolder . '/' . $fileName;
            }

            $ChallengeThumb = ChallengeThumb::create([
                "image" => $thumb_path,
                "order" => $inputs['order'],
                "challenge_type" => $inputs['challenge_type'],
                "category_id" => $inputs['category'],
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
            0 => 'image',
            1 => 'challenge_type',
            2 => 'category_id',
            3 => 'order',
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

            $Query = ChallengeThumb::leftjoin('challenge_categories','challenge_categories.id','challenge_thumbs.category_id')
                    ->select(
                        'challenge_thumbs.*',
                        'challenge_categories.name'
                    );

            if (!empty($search)) {
                $Query = $Query->where(function ($q) use ($search) {
                    $q->where('order', 'LIKE', "%{$search}%");
                });
            }

            $totalData = count($Query->get());
            $totalFiltered = $totalData;

            $thumbData = $Query->offset($start)
                ->limit($limit)
                ->orderBy($order, $dir)
                ->get();

            $count = 0;
            foreach ($thumbData as $thumb) {
                $image_path = Storage::disk('s3')->url($thumb->image);
                $data[$count]['image'] = "<img src='$image_path' alt='Thumb Image' width='100' height='100'>";
                $data[$count]['type'] = ($thumb->challenge_type==1) ? __('general.challenge') : __('general.period_challenge');
                $data[$count]['category'] = $thumb->name;
                $data[$count]['order'] = $thumb->order;

                $editBtn = '<a href="javascript:void(0)" role="button" onclick="editThumb('.$thumb->id.')" class="btn btn-primary btn-sm mx-1" data-toggle="tooltip" data-original-title="Edit"><i class="fa fa-edit" style="font-size: 15px;"></i></a>';
                $data[$count]['action'] = "$editBtn";

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
            Log::info('Exception all thumb list');
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

    public function getCategories(Request $request){
        try{
            DB::beginTransaction();
            $inputs = $request->all();

            $html = "";
            $html .= '<div class="col-md-4">
                                <label>'.__('forms.thumb.select_category').'</label>
                            </div>
                            <div class="col-md-8">
                            <select name="category" class="form-control" id="category">
                            <option selected disabled>Select...</option>';
            if ($inputs['challenge_type']==ChallengeCategory::CHALLENGE){
                $challenge_cats = ChallengeCategory::where('challenge_type',ChallengeCategory::CHALLENGE)->orderBy('order','ASC')->get();
                foreach ($challenge_cats as $cat){
                    $html .= '<option value="'.$cat->id.'">'.$cat->name.'</option>';
                }
            }
            elseif ($inputs['challenge_type']==ChallengeCategory::PERIODCHALLENGE){
                $period_challenge_cats = ChallengeCategory::where('challenge_type',ChallengeCategory::PERIODCHALLENGE)->orderBy('order','ASC')->get();
                foreach ($period_challenge_cats as $cat){
                    $html .= '<option value="'.$cat->id.'">'.$cat->name.'</option>';
                }
            }
            $html .= '</select>
                       </div>';

            DB::commit();
            return response()->json(array('success' => true, 'html' => $html));
        }catch(\Exception $e){
            Log::info("$e");
            DB::rollBack();
            return response()->json(array('success' => false));
        }
    }

    public function editData($id){
        $thumb = ChallengeThumb::where('id',$id)->first();
        if ($thumb->challenge_type==ChallengeCategory::CHALLENGE){
            $challenge_cats = ChallengeCategory::where('challenge_type',ChallengeCategory::CHALLENGE)->orderBy('order','ASC')->get();
        }
        elseif ($thumb->challenge_type==ChallengeCategory::PERIODCHALLENGE){
            $period_challenge_cats = ChallengeCategory::where('challenge_type',ChallengeCategory::PERIODCHALLENGE)->orderBy('order','ASC')->get();
        }

        return view('challenge.thumb-image.edit-popup',compact('challenge_cats','period_challenge_cats','thumb'));
    }

    public function updateThumb(Request $request){
        try{
            DB::beginTransaction();
            $inputs = $request->all();
            $validator = Validator::make($inputs, [
                'edit_image' => 'image|mimes:jpeg,jpg,png,gif,svg',
                'edit_order' => 'required',
                'edit_challenge_type' => 'required',
                'category' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors(), 'success' => false]);
            }

            $thumb_path = ChallengeThumb::where('id',$inputs['thumb_id'])->pluck('image')->first();
            if ($request->hasFile('edit_image')) {
                Storage::disk('s3')->delete($thumb_path);

                $thumbFolder = config('constant.challenge_thumb');
                if (!Storage::exists($thumbFolder)) {
                    Storage::makeDirectory($thumbFolder);
                }
                $thumb = Storage::disk('s3')->putFile($thumbFolder, $request->file('edit_image'),'public');
                $fileName = basename($thumb);
                $thumb_path = $thumbFolder . '/' . $fileName;
            }

            $ChallengeThumb = ChallengeThumb::where('id',$inputs['thumb_id'])->update([
                "image" => $thumb_path,
                "order" => $inputs['edit_order'],
                "challenge_type" => $inputs['edit_challenge_type'],
                "category_id" => $inputs['category'],
            ]);

            DB::commit();
            return response()->json(array('success' => true));
        }catch(\Exception $e){
            Log::info("$e");
            DB::rollBack();
            return response()->json(array('success' => false));
        }
    }

}
