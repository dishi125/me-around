<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Laravel\Socialite\Facades\Socialite;

class UploadOnInstagramController extends Controller
{
    public function index(Request $request)
    {
        $title = 'Upload on instagram';

        return view('admin.upload-instagram.index', compact('title'));
    }

    public function getJsonAllData(Request $request)
    {
        $adminTimezone = $this->getAdminUserTimezone();

//        try {
            $columns = array(
                0 => 'shops.main_name',
                1 => 'shops.shop_name',
                2 => 'social_name',
                3 => 'linked_social_profiles.created_at',
                4 => 'users.email',
            );

            $limit = $request->input('length');
            $start = $request->input('start');
            $search = $request->input('search.value');
            $draw = $request->input('draw');
            $order = $columns[$request->input('order.0.column')];
            $dir = $request->input('order.0.dir');

            $query = Shop::select(
                'shops.*',
                'linked_social_profiles.id as insta_id',
                'linked_social_profiles.created_at as signup_date',
                'linked_social_profiles.is_valid_token as is_valid_token',
                'linked_social_profiles.invalid_token_date',
                DB::raw('IFNULL(linked_profile_histories.social_name, linked_social_profiles.social_name) as social_name'),
                DB::raw('IFNULL(linked_social_profiles.social_id, "") as is_connect'),
                'linked_profile_histories.last_disconnected_date',
                'users.email as user_email',
                'linked_social_profiles.mail_count',
                'linked_social_profiles.last_send_mail_at',
                DB::raw('IFNULL(linked_social_profiles.access_token, linked_profile_histories.access_token) as access_token')
                )
                ->leftjoin('linked_social_profiles','linked_social_profiles.shop_id','shops.id')
                ->leftjoin('linked_profile_histories', function ($join) {
                    $join->on('shops.id', '=', 'linked_profile_histories.shop_id');
                })
                ->leftjoin('users', function ($join) {
                    $join->on('linked_social_profiles.user_id', '=', 'users.id')
                        ->whereNull('users.deleted_at');
                })
                ->where(function ($q){
                    $q->whereNotNull('linked_social_profiles.id')
                        ->orWhereNotNull('linked_profile_histories.id');
                })
                ->whereNotNull('linked_social_profiles.social_id')
                ->where('linked_social_profiles.is_valid_token',1);

            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('shops.main_name', 'LIKE', "%{$search}%")
                        ->orWhere('shops.shop_name', 'LIKE', "%{$search}%");
                });
            }

            $totalData = count($query->get());
            $totalFiltered = $totalData;

            $users = $query->offset($start)
                ->limit($limit)
                ->orderBy($order, $dir)
                ->get();

//            dd($users->toArray());
            $data = array();
            if (!empty($users)) {
                foreach ($users as $value) {
                    $id = $value['id'];

                    $nestedData['active_name'] = $value['main_name'];
                    $nestedData['shop_name'] = $value['shop_name'];
                    $nestedData['instagram'] = $value['social_name'];
                    $nestedData['signup_date'] = $this->formatDateTimeCountryWise($value['signup_date'],$adminTimezone);
                    $nestedData['email'] = $value['user_email'];

                    $viewLink = route('admin.business-client.shop.show', $id);
                    $uploadLink = route('admin.upload-instagram.select', ($value['access_token'])?$value['access_token']:null);
                    $nestedData['view_shop'] = "<a role='button' href='$viewLink' title='' class='btn btn-primary btn-sm mr-3'>See</a>";
                    $nestedData['action'] = "<a role='button' href='$uploadLink' title='' class='btn btn-primary btn-sm mr-3'>Upload</a>";

                    $data[] = $nestedData;
                }
            }

            $jsonData = array(
                "draw" => intval($draw),
                "recordsTotal" => intval($totalData),
                "recordsFiltered" => intval($totalFiltered),
                "data" => $data,
                "query" => $query->toSql()
            );
            return response()->json($jsonData);
       /* } catch (\Exception $ex) {
            //Log::info($ex);
            return response()->json(array(
                "draw" => 0,
                "recordsTotal" => 0,
                "recordsFiltered" => 0,
                "data" => []
            ));
        }*/
    }

    public function getFiles($access_token){
        $title = 'Upload on instagram';

        return view('admin.upload-instagram.form', compact('title','access_token'));
    }

    public function saveInstagram(Request $request){
//        dd($request->all());
        $inputs = $request->all();
//        try {
            DB::beginTransaction();
            $validator = Validator::make($request->all(), [
                'media_file' => 'required|file|mimes:jpeg,jpg,png,gif,mp4,mov'
            ], [], [
                'media_file' => 'Media file',
            ]);
            if ($validator->fails()) {
                notify()->error("Validation Error", "Error", "topRight");
                return redirect()->back()->withErrors($validator)->withInput();
            }

            $uploadedFile = $request->file('media_file');
            $extension = $uploadedFile->getClientOriginalExtension();
            $mediaType = explode('/', $uploadedFile->getMimeType())[0];
            $fileName = date('Y-m-d').'_'.rand(0,9).time().".".$extension;
            $uploadedFile->move(public_path('img/instagram_post'), $fileName);

            $mediaPath = public_path('img/instagram_post/'.$fileName);
            if ($mediaType === 'image') {
                $response = uploadImage($inputs['access_token'],$mediaPath);
            } elseif ($mediaType === 'video') {
                $response = uploadVideo($inputs['access_token'],$mediaPath);
            } else {
                notify()->error("Invalid media type!!", "Error", "topRight");
                return redirect()->route('admin.upload-instagram.index');
            }

            DB::commit();
            notify()->success("Instagram post added successfully", "Success", "topRight");
            return redirect()->route('admin.upload-instagram.index');
        /*} catch (\Exception $e) {
            DB::rollBack();
            Log::info($e);
            notify()->error("Something went wrong!!", "Error", "topRight");
            return redirect()->route('admin.upload-instagram.index');
        }*/
    }

    // Redirect to Instagram for authentication
    public function redirectToInstagram()
    {
        return Socialite::driver('facebook')->scopes(['instagram_basic', 'instagram_content_publish'])->redirect();
    }

    // Callback URL for Instagram authentication
    public function handleInstagramCallback()
    {
        $user = Socialite::driver('facebook')->user();
        dd($user);
        // Store the user's long-lived access token in the database
        // You may want to handle this part as per your application's requirements
        $longLivedAccessToken = $user->token;

        // Use the $longLivedAccessToken to upload media to Instagram
        // Replace MEDIA_URL with the URL of the media (image or video) you want to upload.
        $response = Http::post(
            "https://graph.instagram.com/me/media",
            [
                'image_url' => 'MEDIA_URL',
                'access_token' => $longLivedAccessToken,
            ]
        );

        // Handle the response and do any necessary error checking and logging
        // $response->json() contains the response data from Instagram
    }
}
