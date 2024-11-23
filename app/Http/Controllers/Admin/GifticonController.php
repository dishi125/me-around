<?php

namespace App\Http\Controllers\Admin;

use App\Models\EntityTypes;
use App\Models\Notice;
use App\Models\UserDetail;
use App\Models\UserDevices;
use Illuminate\Http\Request;
use App\Models\GifticonDetail;
use App\Models\GifticonAttachment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;

class GifticonController extends Controller
{
    public function index()
    {

    }

    public function store(Request $request)
    {
        $inputs = $request->all();
        try {
            $image_url = '';
            $title = $inputs['title'] ?? '';
            $user_id = $inputs['user_id'] ?? '';
            $gifticon = GifticonDetail::create([
                'user_id' => $user_id,
                'title' => $title
            ]);

            if ($request->hasFile('gifticon_images') && $gifticon) {
                $gifticonFolder = config('constant.gifticon');

                if (!Storage::disk('s3')->exists($gifticonFolder)) {
                    Storage::disk('s3')->makeDirectory($gifticonFolder);
                }

                $mainFile = Storage::disk('s3')->putFile($gifticonFolder, $request->file('gifticon_images'), 'public');
                $fileName = basename($mainFile);
                $image_url = $gifticonFolder . '/' . $fileName;

                GifticonAttachment::create([
                    'gifticon_id' => $gifticon->id,
                    'attachment_item' => $image_url
                ]);
            }

            // Send Push notification start
            $notice = Notice::create([
                'notify_type' => Notice::GIFTICON,
                'user_id' => $user_id,
                'to_user_id' => $user_id,
//                'entity_type_id' => EntityTypes::SHOP_POST,
                'entity_id' => $gifticon->id,
                'title' => $title,
                'sub_title' => $title,
                'is_aninomity' => 0
            ]);

            $user_detail = UserDetail::where('user_id', $user_id)->first();
            $language_id = $user_detail->language_id;
            $key = Notice::GIFTICON.'_'.$language_id;
            $userIds = [$user_id];

            $format = __("notice.body_$key");
            $devices = UserDevices::whereIn('user_id', $userIds)->pluck('device_token')->toArray();

            $title_msg = __("notice.$key");
            $notify_type = Notice::GIFTICON;

            $notificationData = [
                'id' => $gifticon->id,
                'user_id' => $user_id,
                'title' => $title_msg,
            ];
            if (count($devices) > 0) {
                $result = $this->sentPushNotification($devices,$title_msg, $format, $notificationData ,$notify_type);
            }
            // Send Push notification end

            $gifticons_cnt = GifticonDetail::where('user_id', $user_id)->count();

            return response()->json(array(
                'success' => true,
                'message' => "Gifticon created successfully.",
                'gifticons_cnt' => $gifticons_cnt
            ), 200);

        } catch (\Throwable $th) {
            Log::info($th);
            return response()->json(array(
                'success' => false,
                'message' => "Gifticon is not created successfully"
            ), 400);
        }
    }

    public function update(Request $request, $id)
    {
        $inputs = $request->all();
        try {
            $image_url = '';

            $data = [
                'title' => $inputs['title'],
            ];
            $gifticon = GifticonDetail::where('id',$id)->update($data);

            if ($request->hasFile('gifticon_images') && $gifticon) {
                $gifticonFolder = config('constant.gifticon');

                if (!Storage::disk('s3')->exists($gifticonFolder)) {
                    Storage::disk('s3')->makeDirectory($gifticonFolder);
                }

                $mainFile = Storage::disk('s3')->putFile($gifticonFolder, $request->file('gifticon_images'), 'public');
                $fileName = basename($mainFile);
                $image_url = $gifticonFolder . '/' . $fileName;

                GifticonAttachment::create([
                    'gifticon_id' => $id,
                    'attachment_item' => $image_url
                ]);
            }

            return response()->json(array(
                'success' => true,
                'message' => "Gifticon updated successfully."
            ), 200);

        } catch (\Throwable $th) {
            Log::info($th);
            return response()->json(array(
                'success' => false,
                'message' => "Gifticon is not updated successfully"
            ), 400);
        }
    }

    public function removeImage(Request $request){
        $inputs = $request->all();
        $imageid = $inputs['imageid'] ?? '';

        if(!empty($imageid)){
            $image = GifticonAttachment::whereId($imageid)->first();

            if($image){
                Storage::disk('s3')->delete($image->attachment_item);
                GifticonAttachment::whereId($imageid)->delete();
            }
        }
    }

}
