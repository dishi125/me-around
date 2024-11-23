<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Wedding;
use App\Models\WeddingGuestDetail;
use App\Models\WeddingMetaData;
use App\Models\WeddingSettings;
use Illuminate\Support\Str;
use Log;
use DB;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class WeddingController extends Controller
{
    public function index()
    {
        $title = "Wedding";
        return view('admin.wedding.index', compact('title'));
    }

    public function getJsonData(Request $request)
    {
        $columns = array(
            0 => 'weddings.name',
            1 => 'users_detail.name',
            2 => 'countries.name',
        );

        $limit = $request->input('length');
        $start = $request->input('start');
        $search = $request->input('search.value');
        $draw = $request->input('draw');
        $order = $columns[$request->input('order.0.column')];
        $dir = $request->input('order.0.dir');

        try{

        $adminTimezone = $this->getAdminUserTimezone();
        $weedingQuery = Wedding::join('wedding_meta_data','wedding_meta_data.wedding_id', 'weddings.id')
        ->groupBy('weddings.id')
        ->select('weddings.*');

            if (!empty($search)) {
                $weedingQuery = $weedingQuery->where(function($q) use ($search){
                    $q->where('weddings.name', 'LIKE', "%{$search}%")
                    ->orWhere('wedding_meta_data.meta_value', 'LIKE', "%{$search}%");
                });
            }

            $totalData = count($weedingQuery->get());
            $totalFiltered = $totalData;

            $weddingData = $weedingQuery->offset($start)
                ->limit($limit)
                ->orderBy($order, $dir)
                ->get();

            $count = 0;
            foreach($weddingData as $wedding){
                $viewURL = $link = $qrLink = '';
                if($wedding->uuid) {
                    $link = route('wedding.view', $wedding->uuid);
                    $viewURL = "<a role='button' target='_blank' href='$link' class='btn btn-primary btn-sm mr-1'><i class='fas fa-eye'></i></a>";

                    $qrLink = "<a target='_blank' href='".route('wedding.qr.code',['id' => $wedding->uuid])."' class='btn btn-primary btn-sm' ><i class='fas fa-solid fa-qrcode'></i></a>";
                }
                
                $editLink = route('admin.wedding.edit',$wedding->id);
                $editButton = "<a href='$editLink' role='button' class='btn btn-primary btn-sm mx-1' data-toggle='tooltip' data-original-title='Edit'><i class='fa fa-edit' style='font-size: 15px;margin: 4px -3px 4px 0px;'></i></a>";
                $deleteButton =  "<a href='javascript:void(0);' onClick='deleteWeddingConfirmation(".$wedding->id.")' class='btn-sm mx-1 btn btn-danger'><i class='fas fa-trash-alt'></i></i></a>";

                $data[$count]['name'] = $wedding->name;
                $data[$count]['address'] = getMetaData($wedding->id,'address');
                $wedding_date = getMetaData($wedding->id,'wedding_date');
                $data[$count]['date'] = $this->formatDateTimeCountryWise($wedding_date,$adminTimezone);

                $copyIcon = '<a href="javascript:void(0);" onClick="copyTextLink(`'.$link.'`)" class="btn-sm mx-1 btn btn-primary"><i class="fas fa-copy"></i></a>';
                $data[$count]['actions'] = "<div class='d-flex align-items-center'>$editButton $deleteButton $viewURL $copyIcon $qrLink</div>";
               // $data[$count]['sub_title'] = $post->sub_title;
                $count++;
            }

            $jsonData = array(
                "draw" => intval($draw),
                "recordsTotal" => intval($totalData),
                "recordsFiltered" => intval($totalFiltered),
                "data" => $data
            );
            return response()->json($jsonData);
        } catch (\Exception $ex) {
            Log::info($ex);
            $jsonData = array(
                "draw" => 0,
                "recordsTotal" => 0,
                "recordsFiltered" => 0,
                "data" => []
            );
            return response()->json($jsonData);
        }
    }

    public function create()
    {
        $title = "Create Wedding";
        $fields = Wedding::FIELD_LIST;
        $adminTimezone = $this->getAdminUserTimezone();
        return view('admin.wedding.create', compact('title','fields','adminTimezone'));
    }

    public function store(Request $request)
    {
        $inputs = $request->all();
        $fields = Wedding::FIELD_LIST;
        $fieldKeys = array_keys($fields);

        try{
            DB::beginTransaction();
            $adminTimezone = $this->getAdminUserTimezone();
            $wedding = Wedding::create([
                'name' => $inputs['his_name']." Weds ".$inputs['her_name'],
                'uuid' => (string) Str::uuid()
            ]);

            $communityFolder = config('constant.wedding') . '/' . $wedding->id;

            foreach($inputs as $fieldKey => $fieldValue){
                if(in_array($fieldKey,$fieldKeys)){
                    $currentField = $fields[$fieldKey];
                    $singleFieldType = ["text","textarea", "hidden", "address","select"];
                    if(in_array($currentField['type'],$singleFieldType)){
                        WeddingMetaData::create([
                            'wedding_id' => $wedding->id,
                            'meta_key' => $fieldKey,
                            'meta_value' => $fieldValue,
                        ]);
                    }elseif($currentField['type'] == 'date'){
                        $fieldDate = Carbon::createFromFormat('Y-m-d H:i:s', $fieldValue, $adminTimezone)->setTimezone('UTC');
                        WeddingMetaData::create([
                            'wedding_id' => $wedding->id,
                            'meta_key' => $fieldKey,
                            'meta_value' => $fieldDate,
                        ]);
                    }elseif($currentField['type'] == 'repeater'){
                        WeddingMetaData::create([
                            'wedding_id' => $wedding->id,
                            'meta_key' => $fieldKey,
                            'meta_value' => serialize(array_values($fieldValue)),
                        ]);
                    }elseif($currentField['type'] == 'file'){
                        if($currentField['is_multiple'] == false){
                            if (!Storage::exists($communityFolder)) {
                                Storage::makeDirectory($communityFolder);
                            }

                            $mainImage = Storage::disk('s3')->putFile($communityFolder, $fieldValue, 'public');

                            WeddingMetaData::create([
                                'wedding_id' => $wedding->id,
                                'meta_key' => $fieldKey,
                                'meta_value' => $mainImage,
                            ]);
                        }
                    }
                }
            }

            if(!empty($inputs['main_images'])){
                $imagesUpload = [];
                foreach($inputs['main_images'] as $image) {
                    if(is_file($image)){
                        $mainImage = Storage::disk('s3')->putFile($communityFolder, $image,'public');
                        $fileName = basename($mainImage);
                        $image_url = $communityFolder . '/' . $fileName;
                        $imagesUpload[] = $image_url;
                    }
                }

                WeddingMetaData::create([
                    'wedding_id' => $wedding->id,
                    'meta_key' => 'wedding_gallery',
                    'meta_value' => serialize(array_values($imagesUpload)),
                ]);
            }
            DB::commit();
            return response()->json(["success" => true, "message" => "Wedding". trans("messages.insert-success"), "redirect" => route('admin.wedding.index')], 200);
        } catch (\Exception $e) {
            Log::info($e);
            DB::rollback();
            return response()->json(["success" => false, "message" => "Wedding". trans("messages.insert-error"), "redirect" => route('admin.wedding.index')], 200);
        }
    }

    public function edit($id)
    {
        $title = "Edit Wedding";
        $fields = Wedding::FIELD_LIST;
        $adminTimezone = $this->getAdminUserTimezone();
        return view('admin.wedding.create', compact('title','fields','id','adminTimezone'));
    }

    public function update(Request $request,$id)
    {
        $inputs = $request->all();
        $fields = Wedding::FIELD_LIST;
        $fieldKeys = array_keys($fields);

        try{
            DB::beginTransaction();
            $adminTimezone = $this->getAdminUserTimezone();
            $wedding = Wedding::where('id',$id)->update([
                'name' => $inputs['his_name']." Weds ".$inputs['her_name']
            ]);

            $communityFolder = config('constant.wedding') . '/' . $id;

            foreach($inputs as $fieldKey => $fieldValue){
                if(in_array($fieldKey,$fieldKeys)){
                    $currentField = $fields[$fieldKey];
                    $singleFieldType = ["text","textarea", "hidden", "address","select"];
                    if(in_array($currentField['type'],$singleFieldType)){
                        WeddingMetaData::updateOrCreate([
                            'wedding_id' => $id,
                            'meta_key' => $fieldKey,
                        ],
                        [ 'meta_value' => $fieldValue, ]);
                    }elseif($currentField['type'] == 'date'){
                        $fieldDate = Carbon::createFromFormat('Y-m-d H:i:s', $fieldValue, $adminTimezone)->setTimezone('UTC');
                        WeddingMetaData::updateOrCreate([
                            'wedding_id' => $id,
                            'meta_key' => $fieldKey
                        ],[
                            'meta_value' => $fieldDate,
                        ]);
                    }elseif($currentField['type'] == 'repeater'){
                        WeddingMetaData::updateOrCreate([
                            'wedding_id' => $id,
                            'meta_key' => $fieldKey,
                        ],
                        [ 'meta_value' => serialize(array_values($fieldValue)), ]);
                    }elseif($currentField['type'] == 'file'){
                        if($currentField['is_multiple'] == false){
                            if(!empty($fieldValue) && is_file($fieldValue)){
                                if (!Storage::exists($communityFolder)) {
                                    Storage::makeDirectory($communityFolder);
                                }

                                $mainImage = Storage::disk('s3')->putFile($communityFolder, $fieldValue, 'public');

                                WeddingMetaData::updateOrCreate([
                                    'wedding_id' => $id,
                                    'meta_key' => $fieldKey,
                                ],[
                                    'meta_value' => $mainImage,
                                ]);
                            }
                        }
                    }
                }
            }

            if(!empty($inputs['main_images'])){
                $imagesUpload = $imageArray = [];
                $imageData = getMetaData($id,'wedding_gallery');
                $imageArray = $imageData ? unserialize($imageData) : [];
                foreach($inputs['main_images'] as $image) {
                    if(is_file($image)){
                        $mainImage = Storage::disk('s3')->putFile($communityFolder, $image,'public');
                        $fileName = basename($mainImage);
                        $image_url = $communityFolder . '/' . $fileName;
                        $imagesUpload[] = $image_url;
                    }
                }

                $imagesUpload = array_merge($imageArray, $imagesUpload);
                WeddingMetaData::updateOrCreate([
                    'wedding_id' => $id,
                    'meta_key' => 'wedding_gallery',
                ],[
                    'meta_value' => serialize(array_values($imagesUpload)),
                ]);
            }
            DB::commit();
            return response()->json(["success" => true, "message" => "Wedding". trans("messages.update-success"), "redirect" => route('admin.wedding.index')], 200);
        } catch (\Exception $e) {
            Log::info($e);
            DB::rollback();
            return response()->json(["success" => false, "message" => "Wedding". trans("messages.update-error"), "redirect" => route('admin.wedding.index')], 200);
        }
    }

    public function removeImage(Request $request){
        $inputs = $request->all();
        $imageid = $inputs['imageid'] ?? '';
        $index = $inputs['index'] ?? '';

        $fields = Wedding::FIELD_LIST;
        if(!empty($imageid)){
            $imageMeta = DB::table('wedding_meta_data')->whereId($imageid)->first();
            $fieldData = $fields[$imageMeta->meta_key];

            if($imageMeta && $fieldData){
                if($fieldData['is_multiple']){
                    $value = $imageMeta->meta_value;
                    if($value){
                        $valueArray = unserialize($value);
                        $removeImage = $valueArray ? $valueArray[$index] : '';

                        if($removeImage){
                            Storage::disk('s3')->delete($removeImage);
                            array_splice($valueArray, $index, 1);
                        }
                        WeddingMetaData::where('id',$imageid)->update(['meta_value' => serialize($valueArray)]);
                    }
                }else{
                    Storage::disk('s3')->delete($imageMeta->meta_value);
                    WeddingMetaData::where('id',$imageid)->update(['meta_value' => '']);
                }
            }
        }
    }

    public function getDeleteWedding($id){
        $title = "Wedding";
        return view('admin.wedding.delete', compact('id','title'));
    }

    public function deleteWedding(Request $request)
    {
        $inputs = $request->all();
        try{
            $wedding_id = $inputs['wedding_id'] ?? '';
            if($wedding_id){
                WeddingMetaData::where('wedding_id',$wedding_id)->delete();
                Wedding::where('id',$wedding_id)->delete();
            }
            return response()->json(["success" => true, "message" => "Wedding". trans("messages.delete-success")], 200);
        } catch (\Exception $e) {
            Log::info($e);
            return response()->json(["success" => false, "message" => "Wedding". trans("messages.delete-error")], 200);
        }
    }

    public function addMoreField(Request $request)
    {
        $inputs = $request->all();
        $field = $inputs['field'] ?? '';
        $index = $inputs['index'] ?? 1;
        if(!$field) return '';

        $fields = Wedding::FIELD_LIST;
        $addMoreField = $fields[$field];

        if($addMoreField['type'] != 'repeater') return '';

        $more_field = $addMoreField['field_group'];
        return view('admin.wedding.more-form', compact('index','more_field','field'));
    }

    public function viewWedding($uuid)
    {
        $weddingData = Wedding::with('weddingGuests')->where('uuid',$uuid)->first();
        $weddingGuests = $weddingData->weddingGuests;
        $title = 'Wedding';
        if($weddingData){
            $metaData = WeddingMetaData::where('wedding_id',$weddingData->id)->select('meta_value','meta_key')->get();
            $metaData = collect($metaData)->mapWithKeys(function ($value) {
                return [$value->meta_key => $value->meta_value];
            })->toArray();
            $weddingData = array_merge($weddingData->toArray(),$metaData);

            $weddingData['address_latitude'] = $weddingData['address-latitude'];
            $weddingData['address_longitude'] = $weddingData['address-longitude'];
            $weddingData = json_decode (json_encode ($weddingData), FALSE);
        }

        $compareDate = Carbon::parse($weddingData->wedding_date);
        $currentDate = Carbon::now();
        $daysDiff = $compareDate->diffInDays($currentDate);
        
        if($compareDate->isSameDay($currentDate)){
            $wedding_date_text = '오늘';
            $daysDiff = '';
        }elseif($compareDate->gt($currentDate)){
            $wedding_date_text = '후 예정';
        }else{
            $wedding_date_text = '지났습니다';
        }
        $weddingData->weddingGuests = $weddingGuests;
        return view("wedding.wedding-view",compact('weddingData','title','uuid','daysDiff','wedding_date_text'));
    }

    public function saveGuestData(Request $request,$uuid){
        $inputs = $request->all();
        try{
            $weddingData = Wedding::where('uuid',$uuid)->first();

            $html = '';
            if(!empty($uuid) && !empty($weddingData)){
                $date = Carbon::now()->format('Y-m-d H:i:s');
                $guest = WeddingGuestDetail::create([
                    'wedding_id' => $weddingData->id,
                    'name' => $inputs['name'] ?? '',
                    'pass' => $inputs['pass'] ?? '',
                    'description' => $inputs['description'] ?? '',
                    'created_at' => $date
                ]);

                $html .= '<div class="item" data-id="'.$guest->id.'">
                            <div class="close">
                                <span class="date">'.date('Y.m.d',strtotime($date)).'</span> 
                                <span class="icon" onClick="removeComment('.$guest->id.');">
                                    <svg
                                        data-name="Layer 1" id="Layer_1" viewBox="0 0 64 64"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <title></title>
                                        <path
                                            d="M8.25,0,32,23.75,55.75,0,64,8.25,40.25,32,64,55.75,55.75,64,32,40.25,8.25,64,0,55.75,23.75,32,0,8.25Z"
                                            data-name="<Compound Path>" id="_Compound_Path_"></path>
                                    </svg>
                                </span>
                            </div>
                            <div class="name">'.$guest->name.'</div>
                            <div class="text">'.$guest->description.'</div>
                        </div>';
            }            
            return response()->json(["success" => true, 'html' => $html, "message" => "Wedding". trans("messages.insert-success")], 200);
        } catch (\Exception $e) {
            Log::info($e);
            return response()->json(["success" => false, 'html' => '', "message" => "Wedding". trans("messages.insert-error")], 200);
        }
    }

    public function removeGuestData(Request $request)
    {
        $inputs = $request->all();

        try{
            $guest_id = $inputs['guest_id'];
            $delete_pass = $inputs['delete-pass'];

            $data = WeddingGuestDetail::whereId($guest_id)->first();
            if($data->pass == $delete_pass){
                WeddingGuestDetail::whereId($guest_id)->delete();
                $success = true;
            }else{
                $success = false;
            }
            return response()->json(["success" => $success, 'guest_id' => $guest_id, "message" => "Wedding". trans("messages.delete-success")], 200);
        } catch (\Exception $e) {
            Log::info($e);
            return response()->json(["success" => false, "message" => "Wedding". trans("messages.delete-error")], 200);
        }
    }

    public function weddingSetting(){
        $title = "Wedding Settings";
        return view('admin.wedding.settings.settings', compact('title'));
    }

    public function weddingSettingCreate(){
        $title = "Wedding Settings Create";

        $settingTypes = WeddingSettings::SETTING_OPTIONS;
        return view('admin.wedding.settings.create', compact('title','settingTypes'));
    }

    public function ChangeField(Request $request)
    {
        $inputs = $request->all();

        try{
            $select_type = $inputs['select_type'] ?? 'video_file';
            $fields = WeddingSettings::SETTING_OPTION_TYPES;

            $selectedField = $fields[$select_type] ?? $fields[0];

            $returnHTML = view('admin.wedding.settings.field')->with(['field_name' => $select_type,'field_label' => $selectedField['label'],'field_type' => $selectedField['type'], 'accept' => $selectedField['accept'],'field_value' => ''])->render();
            return response()->json(array('success' => true, 'html'=>$returnHTML));

        }catch(\Exception $e){
            Log::info("$e");
            return response()->json(array('success' => false, 'html'=>''));
        }
    }

    public function settingStore(Request $request)
    {
        $inputs = $request->all();

        try{
            $key = $inputs['key'] ?? '';
            if($key){
                $fields = WeddingSettings::SETTING_OPTION_TYPES;

                $selectedField = $fields[$key] ?? $fields[0];

                $fileData = $inputs[$key] ?? '';
                $optionFolder = config('constant.weddingSettings');

                if(!empty($fileData) && is_file($fileData)){
                    $originalName = $fileData->getClientOriginalName();

                    if (!Storage::disk('s3')->exists($optionFolder)) {
                        Storage::disk('s3')->makeDirectory($optionFolder);
                    }
                    $mainFile = Storage::disk('s3')->putFileAs($optionFolder, $fileData, $originalName, 'public');
                    $fileName = basename($mainFile);
                    $file_url = $optionFolder . '/' . $fileName;
                    WeddingSettings::create([
                        'key' => $key,
                        'value' => $file_url,
                        'type' => $selectedField['type'],
                    ]);
                }

            }          
            return response()->json(["success" => true, "message" => "Wedding Settings". trans("messages.insert-success"), "redirect" => route('admin.wedding.settings')], 200);

        }catch(\Exception $e){
            Log::info("$e");
            return response()->json(["success" => false, "message" => "Wedding Settings". trans("messages.insert-error"), "redirect" => route('admin.wedding.index')], 200);
        }
    }

    public function getSettingJsonData(Request $request)
    {
        $columns = array(
            0 => 'key',
            1 => 'value',
            2 => 'created_at',
        );

        $filter = $request->input('filter');
        $limit = $request->input('length');
        $start = $request->input('start');
        $search = $request->input('search.value');
        $draw = $request->input('draw');
        $order = $columns[$request->input('order.0.column')];
        $dir = $request->input('order.0.dir');

        try{

        $adminTimezone = $this->getAdminUserTimezone();
        $weedingQuery = WeddingSettings::where('key',$filter);

            $totalData = count($weedingQuery->get());
            $totalFiltered = $totalData;

            $weddingData = $weedingQuery->offset($start)
                ->limit($limit)
                ->orderBy($order, $dir)
                ->get();

            $count = 0;
            $fields = WeddingSettings::SETTING_OPTION_TYPES;
            foreach($weddingData as $wedding){
                $deleteButton =  "<a href='javascript:void(0);' onClick='deleteWeddingConfirmation(".$wedding->id.")' class='btn-sm mx-1 btn btn-danger'><i class='fas fa-trash-alt'></i></i></a>";

                $selectedField = $fields[ $wedding->key ] ?? $fields[0];

                $data[$count]['type'] = $selectedField['label'];
                $data[$count]['file'] = "<a target='_blank' href='".$wedding->filter_value."'>".basename($wedding->filter_value)."</a>";
                $data[$count]['date'] = $this->formatDateTimeCountryWise($wedding->created_at,$adminTimezone);

                $data[$count]['actions'] = "<div class='d-flex align-items-center'> $deleteButton </div>";
                $count++;
            }

            $jsonData = array(
                "draw" => intval($draw),
                "recordsTotal" => intval($totalData),
                "recordsFiltered" => intval($totalFiltered),
                "data" => $data
            );
            return response()->json($jsonData);
        } catch (\Exception $ex) {
            Log::info($ex);
            $jsonData = array(
                "draw" => 0,
                "recordsTotal" => 0,
                "recordsFiltered" => 0,
                "data" => []
            );
            return response()->json($jsonData);
        }
    }

    public function getSettingDeleteWedding($id){
        $title = "Wedding Settings";
        return view('admin.wedding.settings.delete', compact('id','title'));
    }

    public function deleteWeddingSettings(Request $request)
    {
        $inputs = $request->all();
        try{
            $wedding_id = $inputs['wedding_id'] ?? '';
            if($wedding_id){
                WeddingSettings::where('id',$wedding_id)->delete();
            }
            return response()->json(["success" => true, "message" => "Wedding". trans("messages.delete-success")], 200);
        } catch (\Exception $e) {
            Log::info($e);
            return response()->json(["success" => false, "message" => "Wedding". trans("messages.delete-error")], 200);
        }
    }
}
