<?php

namespace App\Http\Controllers\Challenge;

use App\Http\Controllers\Controller;
use App\Models\ChallengeMenu;
use App\Models\ChallengeThumb;
use App\Models\User;
use App\Models\UserDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class NamingCustomizingController extends Controller
{
    public function index(Request $request)
    {
        $title = 'Naming customizing';
        $menus = ChallengeMenu::get();

        return view('challenge.naming-customizing.index', compact('title','menus'));
    }

    public function editData()
    {
        $menus = ChallengeMenu::get();
        return view('challenge.naming-customizing.edit-popup',compact('menus'));
    }

    public function updateData(Request $request){
//        try{
            DB::beginTransaction();
            $inputs = $request->all();
            $validator = Validator::make($inputs, [
                'menu_eng_1' => 'required',
                'menu_eng_2' => 'required',
                'menu_eng_3' => 'required',
                'menu_kr_1' => 'required',
                'menu_kr_2' => 'required',
                'menu_kr_3' => 'required',
            ], [
                'menu_eng_1.required' => 'Menu name is required.',
                'menu_eng_2.required' => 'Menu name is required.',
                'menu_eng_3.required' => 'Menu name is required.',
                'menu_kr_1.required' => 'Menu name is required.',
                'menu_kr_2.required' => 'Menu name is required.',
                'menu_kr_3.required' => 'Menu name is required.',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors(), 'success' => false]);
            }

            ChallengeMenu::updateOrCreate([
                'id' => 1,
            ],[
                'eng_menu' => $inputs['menu_eng_1'],
                'kr_menu' => $inputs['menu_kr_1'],
            ]);
            ChallengeMenu::updateOrCreate([
                'id' => 2,
            ],[
                'eng_menu' => $inputs['menu_eng_2'],
                'kr_menu' => $inputs['menu_kr_2'],
            ]);
            ChallengeMenu::updateOrCreate([
                'id' => 3,
            ],[
                'eng_menu' => $inputs['menu_eng_3'],
                'kr_menu' => $inputs['menu_kr_3'],
            ]);

            DB::commit();
            return response()->json(array('success' => true));
        /*}catch(\Exception $e){
            Log::info("$e");
            DB::rollBack();
            return response()->json(array('success' => false));
        }*/
    }

}
