<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CardLevel;
use App\Models\CardLevelDetail;
use App\Models\CardStatusRives;
use App\Models\CardStatusThumbnails;
use App\Models\DefaultCards;
use App\Models\DefaultCardsRives;
use App\Models\EntityTypes;
use App\Models\Notice;
use App\Models\UserCardLevel;
use App\Models\UserCardResetHistory;
use App\Models\UserCards;
use App\Models\CardMusic;
use App\Models\UserCardSellRequest;
use App\Models\UserDevices;
use DB;
use Illuminate\Http\Request;
use Log;
use Storage;
use Validator;

class CardsController extends Controller
{
    public function index()
    {
        $title = "Card List";
        $cards_tabs = DefaultCards::get();
        return view('admin.cards.index', compact('title','cards_tabs'));

    }

    public function getJsonData(Request $request){

        $columns = array(
            0 => 'card_name',
            1 => 'default_card_id',
            2 => 'action',
        );

        $limit = $request->input('length');
        $start = $request->input('start');
        $search = $request->input('search.value');
        $draw = $request->input('draw');
        $order = $columns[$request->input('order.0.column')];
        $dir = $request->input('order.0.dir');
        $adminTimezone = $this->getAdminUserTimezone();

        DB::enableQueryLog();
        try {
            $data = [];
            $getDefault = DefaultCards::first();
            $filter = !empty($request->input('filter')) ? $request->input('filter') : '';
            $cardsQuery = DefaultCardsRives::select('*');
            if($filter){
                $cardsQuery->where(['default_card_id' => $filter]);
            }

            if (!empty($search)) {
                $cardsQuery = $cardsQuery->where(function($q) use ($search){
                    $q->where('card_name', 'LIKE', "%{$search}%");
                });

            }
            $totalData = count($cardsQuery->get());
            $totalFiltered = $totalData;

            $cardsData = $cardsQuery->offset($start)
            ->limit($limit)
            ->orderBy($order, $dir)
            ->get();

            $count = 0;

            foreach($cardsData as $cardsVal){

                $data[$count]['name'] = $cardsVal->card_name;
                $data[$count]['range'] = $cardsVal->tab_name;
                 $data[$count]['order'] = $cardsVal->order;

                $userLink = route('admin.cards.users', $cardsVal->id);
                $userButton =  "<a href='".$userLink."' class='btn btn-primary ml-2'><i class='fas fa-users'></i></a>";

                $edit = route('admin.cards.edit', $cardsVal->id);
                $editButton =  "<a href='".$edit."' class='btn btn-primary mr-2'><i class='fa fa-edit'></i></a>";

                $deleteButton = '';
                if($getDefault->id != $filter){
                    $deleteButton =  "<a href='javascript:void(0);' onClick='deleteCard(".$cardsVal->id.")' class='btn btn-danger'><i class='fas fa-trash-alt'></i></i></a>";
                }

                $viewButton =  "<a href='javascript:void(0);' onClick='viewCard(".$cardsVal->id.",`".$cardsVal->character_rive_url."`,`1`)' class='btn btn-primary ml-2'><i class='fas fa-eye'></i></a>";

                $musicLink = route('admin.manage.music',['card' => $cardsVal->id]);
                $musicButton =  "<a href='$musicLink' class='btn btn-primary ml-2' data-toggle='tooltip' title='Manage Music'><i class='fas fa-music'></i></a>";

                $statusLink = route('admin.manage.status.rive',['card' => $cardsVal->id]);
                $viewLevelButton =  "<a href='$statusLink' class='btn btn-primary ml-2' data-toggle='tooltip' title='Manage Status Riv'><i class='fa fa-solid fa-layer-group'></i></a>";

                $data[$count]['actions'] = "<div class='d-flex'> $editButton $deleteButton $userButton $viewButton $viewLevelButton $musicButton</div>";
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
                "draw" => intval($draw),
                "recordsTotal" => 0,
                "recordsFiltered" => 0,
                "data" => []
            );
            return response()->json($jsonData);
        }
    }

    public function create()
    {
        $title = "Add rive";
        $first_card_level = CardLevel::first();
        $other_level = CardLevel::where('id','!=',$first_card_level->id)->get();
        $cards_tabs = DefaultCards::where('name','!=', DefaultCards::DEFAULT_CARD)->pluck('name','id');
        $isDefaultCard = false;

        $disabledOptions = [];
        $createdRives = DefaultCardsRives::select('default_card_id')->get();
        if($createdRives){
            foreach($createdRives as $rive){
                $disabledOptions[$rive->default_card_id] = [ "disabled" => false ];
            }
        }
        return view('admin.cards.form', compact('title','cards_tabs','first_card_level','other_level','isDefaultCard','disabledOptions'));
    }

    public function store(Request $request)
    {
        try {
            DB::beginTransaction();

            $inputs = $request->all();
            $validator = Validator::make($request->all(), [
                'card_name' => 'required',
                'card_id' => 'required',
            ], [], [
                'card_name' => 'Card Name',
                'card_id' => 'Card Id',
            ]);
            if ($validator->fails()) {
                notify()->error("Validation Error", "Error", "topRight");
                return redirect()->back()->withErrors($validator)->withInput();
            }

            $data = [
                "card_name" => $inputs['card_name'],
                "default_card_id" => $inputs['card_id'],
                "usd_price" => $inputs['usd_price'] ?? 0,
                "japanese_yen_price" => $inputs['japanese_price'] ?? 0,
                "chinese_yuan_price" => $inputs['chinese_price'] ?? 0,
                "korean_won_price" => $inputs['korean_price'] ?? 0,
                "required_love_in_days" => $inputs['required_love_in_days'] ?? 1,
                "order" => $inputs['order'] ?? 0,
            ];

            $cardInsert = DefaultCardsRives::create($data);

            $cardFiles = [];
            if ($request->hasFile('feeding_rive')) {
                $feedingFolder = config('constant.feeding_rive')."/$cardInsert->id";
                if (!Storage::exists($feedingFolder)) {
                    Storage::makeDirectory($feedingFolder);
                }

                $originalName = $request->file('feeding_rive')->getClientOriginalName();
                $feedingRiv = Storage::disk('s3')->putFileAs($feedingFolder, $request->file('feeding_rive'),$originalName,'public');
                $fileName = basename($feedingRiv);
                $cardFiles['feeding_rive'] = $feedingFolder . '/' . $fileName;
            }

            if ($request->hasFile('background_rive')) {
                $backgroundFolder = config('constant.background_rive')."/$cardInsert->id";
                if (!Storage::exists($backgroundFolder)) {
                    Storage::makeDirectory($backgroundFolder);
                }

                $originalName = $request->file('background_rive')->getClientOriginalName();
                $backgroundRiv = Storage::disk('s3')->putFileAs($backgroundFolder, $request->file('background_rive'),$originalName,'public');
                $fileName = basename($backgroundRiv);
                $cardFiles['background_rive'] = $backgroundFolder . '/' . $fileName;
            }

            if ($request->hasFile('background_thumbnail')) {
                $backgroundFolder = config('constant.background_thumbnail')."/$cardInsert->id";
                if (!Storage::exists($backgroundFolder)) {
                    Storage::makeDirectory($backgroundFolder);
                }

                $originalName = $request->file('background_thumbnail')->getClientOriginalName();
                $backgroundRiv = Storage::disk('s3')->putFileAs($backgroundFolder, $request->file('background_thumbnail'),$originalName,'public');
                $fileName = basename($backgroundRiv);
                $cardFiles['background_thumbnail'] = $backgroundFolder . '/' . $fileName;
            }

            if ($request->hasFile('character_rive')) {
                $characterFolder = config('constant.character_rive')."/$cardInsert->id";
                if (!Storage::exists($characterFolder)) {
                    Storage::makeDirectory($characterFolder);
                }
                $originalName = $request->file('character_rive')->getClientOriginalName();
                $characterRiv = Storage::disk('s3')->putFileAs($characterFolder, $request->file('character_rive'),$originalName,'public');
                $fileName = basename($characterRiv);
                $cardFiles['character_rive'] = $characterFolder . '/' . $fileName;
            }

            if ($request->hasFile('character_thumbnail')) {
                $characterFolder = config('constant.character_thumbnail')."/$cardInsert->id";
                if (!Storage::exists($characterFolder)) {
                    Storage::makeDirectory($characterFolder);
                }
                $originalName = $request->file('character_thumbnail')->getClientOriginalName();
                $characterRiv = Storage::disk('s3')->putFileAs($characterFolder, $request->file('character_thumbnail'),$originalName,'public');
                $fileName = basename($characterRiv);
                $cardFiles['character_thumbnail'] = $characterFolder . '/' . $fileName;
            }

            if ($request->hasFile('download_file')) {
                $downloadFolder = config('constant.download_file')."/$cardInsert->id";
                if (!Storage::exists($downloadFolder)) {
                    Storage::makeDirectory($downloadFolder);
                }
                $originalName = $request->file('download_file')->getClientOriginalName();
                $downloadFile = Storage::disk('s3')->putFileAs($downloadFolder, $request->file('download_file'),$originalName,'public');
                $fileName = basename($downloadFile);
                $cardFiles['download_file'] = $downloadFolder . '/' . $fileName;
            }

            $cardData = DefaultCardsRives::where('id',$cardInsert->id)->update($cardFiles);

            $level = $inputs['level'] ?? [];

            if(!empty($level)){
                foreach ($level as $level_data){
                    $createData = [];
                    $card_level = $level_data['card_level'];
                    $createData = [
                        "card_name" => $level_data['card_name'],
                        "usd_price" => $level_data['usd_price'] ?? 0,
                        "japanese_yen_price" => $level_data['japanese_price'] ?? 0,
                        "chinese_yuan_price" => $level_data['chinese_price'] ?? 0,
                        "korean_won_price" => $level_data['korean_price'] ?? 0,
                        "required_love_in_days" => $level_data['required_love_in_days'] ?? 1,
                    ];

                    if (isset($level_data['background_rive']) && is_file($level_data['background_rive'])) {
                        $backgroundFolder = config('constant.background_rive')."/$cardInsert->id/$card_level";
                        if (!Storage::exists($backgroundFolder)) {
                            Storage::makeDirectory($backgroundFolder);
                        }

                        $originalName = $level_data['background_rive']->getClientOriginalName();
                        $backgroundRiv = Storage::disk('s3')->putFileAs($backgroundFolder, $level_data['background_rive'],$originalName,'public');
                        $fileName = basename($backgroundRiv);
                        $createData['background_rive'] = $backgroundFolder . '/' . $fileName;
                    }

                    if (isset($level_data['background_thumbnail']) && is_file($level_data['background_thumbnail'])) {
                        $backgroundFolder = config('constant.background_thumbnail')."/$cardInsert->id/$card_level";
                        if (!Storage::exists($backgroundFolder)) {
                            Storage::makeDirectory($backgroundFolder);
                        }

                        $originalName = $level_data['background_thumbnail']->getClientOriginalName();
                        $backgroundRiv = Storage::disk('s3')->putFileAs($backgroundFolder, $level_data['background_thumbnail'],$originalName,'public');
                        $fileName = basename($backgroundRiv);
                        $createData['background_thumbnail'] = $backgroundFolder . '/' . $fileName;
                    }

                    if (isset($level_data['character_rive']) && is_file($level_data['character_rive'])) {
                        $characterFolder = config('constant.character_rive')."/$cardInsert->id/$card_level";
                        if (!Storage::exists($characterFolder)) {
                            Storage::makeDirectory($characterFolder);
                        }
                        $originalName = $level_data['character_rive']->getClientOriginalName();
                        $characterRiv = Storage::disk('s3')->putFileAs($characterFolder, $level_data['character_rive'],$originalName,'public');
                        $fileName = basename($characterRiv);
                        $createData['character_rive'] = $characterFolder . '/' . $fileName;
                    }

                    if (isset($level_data['character_thumbnail']) && is_file($level_data['character_thumbnail'])) {
                        $characterFolder = config('constant.character_thumbnail')."/$cardInsert->id/$card_level";
                        if (!Storage::exists($characterFolder)) {
                            Storage::makeDirectory($characterFolder);
                        }
                        $originalName = $level_data['character_thumbnail']->getClientOriginalName();
                        $characterRiv = Storage::disk('s3')->putFileAs($characterFolder, $level_data['character_thumbnail'],$originalName,'public');
                        $fileName = basename($characterRiv);
                        $createData['character_thumbnail'] = $characterFolder . '/' . $fileName;
                    }

                    if (isset($level_data['download_file']) && is_file($level_data['download_file'])) {
                        $downloadFolder = config('constant.download_file'). "/$cardInsert->id/$card_level";
                        if (!Storage::exists($downloadFolder)) {
                            Storage::makeDirectory($downloadFolder);
                        }
                        $originalName = $level_data['download_file']->getClientOriginalName();
                        $downloadFile = Storage::disk('s3')->putFileAs($downloadFolder, $level_data['download_file'],$originalName,'public');
                        $fileName = basename($downloadFile);
                        $createData['download_file'] = $downloadFolder . '/' . $fileName;
                    }

                    if (isset($level_data['feeding_rive']) && is_file($level_data['feeding_rive'])) {
                        $feedingFolder = config('constant.feeding_rive'). "/$cardInsert->id/$card_level";
                        if (!Storage::exists($feedingFolder)) {
                            Storage::makeDirectory($feedingFolder);
                        }
                        $originalName = $level_data['feeding_rive']->getClientOriginalName();
                        $feedingFile = Storage::disk('s3')->putFileAs($feedingFolder, $level_data['feeding_rive'],$originalName,'public');
                        $fileName = basename($feedingFile);
                        $createData['feeding_rive'] = $feedingFolder . '/' . $fileName;
                    }

                    CardLevelDetail::updateOrCreate(
                        [
                            "main_card_id" => $cardInsert->id,
                            "card_level" => $card_level
                        ],
                        $createData
                    );
                }
            }
            DB::commit();

            notify()->success("Cards ". trans("messages.insert-success"), "Success", "topRight");
            return redirect()->route('admin.cards.index');
        } catch (\Exception $e) {
            //dd($inputs);
            Log::info($e);
            notify()->error("Cards ". trans("messages.insert-error"), "Error", "topRight");
            return redirect()->route('admin.cards.index');
        }
    }

    public function edit(DefaultCardsRives $card)
    {
        $title = "Edit Card";
        $first_card_level = CardLevel::whereId(CardLevel::DEFAULT_LEVEL)->first();
        $other_level = CardLevel::where('id','!=',CardLevel::DEFAULT_LEVEL)->get();
        $defaultCard = getDefaultCard();
        $isDefaultCard = ($defaultCard->id == $card->id);
        if($isDefaultCard){
            $cards_tabs = ["1"=>"Default"];
        }else{
            $cards_tabs = DefaultCards::where('id','!=',CardLevel::DEFAULT_LEVEL)->pluck('name','id');
        }
        $disabledOptions = [];
        $createdRives = DefaultCardsRives::select('default_card_id')->get();
        if($createdRives){
            foreach($createdRives as $rive){
                if($rive->default_card_id != $card->default_card_id){
                    $disabledOptions[$rive->default_card_id] = [ "disabled" => false ];
                }
            }
        }
        return view('admin.cards.form', compact('title', 'cards_tabs','card','first_card_level','other_level','isDefaultCard','disabledOptions'));
    }

    public function update(Request $request, DefaultCardsRives $card)
    {
        try {

            DB::beginTransaction();
            $inputs = $request->all();
            $validator = Validator::make($request->all(), [
                'card_name' => 'required',
            ], [], [
                'card_name' => 'Card Name',
            ]);

            if ($validator->fails()) {
                notify()->error("Validation Error", "Error", "topRight");
                return redirect()->back()->withErrors($validator)->withInput();
            }

            $data = [
                "card_name" => $inputs['card_name'],
                "usd_price" => $inputs['usd_price'] ?? 0,
                "japanese_yen_price" => $inputs['japanese_price'] ?? 0,
                "chinese_yuan_price" => $inputs['chinese_price'] ?? 0,
                "korean_won_price" => $inputs['korean_price'] ?? 0,
                "required_love_in_days" => $inputs['required_love_in_days'] ?? 1,
                "order" => $inputs['order'] ?? 0,
            ];


            if(isset($inputs['card_id'])){
                $data["default_card_id"] = $inputs['card_id'];
            }

            if ($request->hasFile('background_thumbnail')) {
                $feedingFolder = config('constant.background_thumbnail')."/$card->id";
                if (!Storage::exists($feedingFolder)) {
                    Storage::makeDirectory($feedingFolder);
                }

                $originalName = $request->file('background_thumbnail')->getClientOriginalName();
                $feedingRiv = Storage::disk('s3')->putFileAs($feedingFolder, $request->file('background_thumbnail'),$originalName,'public');
                $fileName = basename($feedingRiv);
                $data['background_thumbnail'] = $feedingFolder . '/' . $fileName;
            }

            if ($request->hasFile('feeding_rive')) {
                $feedingFolder = config('constant.feeding_rive')."/$card->id";
                if (!Storage::exists($feedingFolder)) {
                    Storage::makeDirectory($feedingFolder);
                }

                $originalName = $request->file('feeding_rive')->getClientOriginalName();
                $feedingRiv = Storage::disk('s3')->putFileAs($feedingFolder, $request->file('feeding_rive'),$originalName,'public');
                $fileName = basename($feedingRiv);
                $data['feeding_rive'] = $feedingFolder . '/' . $fileName;
            }

            if ($request->hasFile('background_rive')) {
                $backgroundFolder = config('constant.background_rive')."/$card->id";
                if (!Storage::exists($backgroundFolder)) {
                    Storage::makeDirectory($backgroundFolder);
                }

                $originalName = $request->file('background_rive')->getClientOriginalName();
                $backgroundRiv = Storage::disk('s3')->putFileAs($backgroundFolder, $request->file('background_rive'),$originalName,'public');
                $fileName = basename($backgroundRiv);
                $data['background_rive'] = $backgroundFolder . '/' . $fileName;
            }

            if ($request->hasFile('character_rive')) {
                $characterFolder = config('constant.character_rive')."/$card->id";
                if (!Storage::exists($characterFolder)) {
                    Storage::makeDirectory($characterFolder);
                }
                $originalName = $request->file('character_rive')->getClientOriginalName();
                $characterRiv = Storage::disk('s3')->putFileAs($characterFolder, $request->file('character_rive'),$originalName,'public');
                $fileName = basename($characterRiv);
                $data['character_rive'] = $characterFolder . '/' . $fileName;
            }

            if ($request->hasFile('character_thumbnail')) {
                $characterFolder = config('constant.character_thumbnail')."/$card->id";
                if (!Storage::exists($characterFolder)) {
                    Storage::makeDirectory($characterFolder);
                }
                $originalName = $request->file('character_thumbnail')->getClientOriginalName();
                $characterRiv = Storage::disk('s3')->putFileAs($characterFolder, $request->file('character_thumbnail'),$originalName,'public');
                $fileName = basename($characterRiv);
                $data['character_thumbnail'] = $characterFolder . '/' . $fileName;
            }

            if ($request->hasFile('download_file')) {
                $downloadFolder = config('constant.download_file')."/$card->id";
                if (!Storage::exists($downloadFolder)) {
                    Storage::makeDirectory($downloadFolder);
                }
                $originalName = $request->file('download_file')->getClientOriginalName();
                $downloadFile = Storage::disk('s3')->putFileAs($downloadFolder, $request->file('download_file'),$originalName,'public');
                $fileName = basename($downloadFile);
                $data['download_file'] = $downloadFolder . '/' . $fileName;
            }

            $cardData = DefaultCardsRives::updateOrCreate(['id' => $card->id],$data);

            $level = $inputs['level'] ?? [];

            if(!empty($level)){
                foreach ($level as $level_data){
                    $createData = [];
                    $card_level = $level_data['card_level'];
                    $createData = [
                        "card_name" => $level_data['card_name'],
                        "usd_price" => $level_data['usd_price'] ?? 0,
                        "japanese_yen_price" => $level_data['japanese_price'] ?? 0,
                        "chinese_yuan_price" => $level_data['chinese_price'] ?? 0,
                        "korean_won_price" => $level_data['korean_price'] ?? 0,
                        "required_love_in_days" => $level_data['required_love_in_days'] ?? 1
                    ];

                    if (isset($level_data['background_rive']) && is_file($level_data['background_rive'])) {
                        $backgroundFolder = config('constant.background_rive')."/$card->id/$card_level";
                        if (!Storage::exists($backgroundFolder)) {
                            Storage::makeDirectory($backgroundFolder);
                        }

                        $originalName = $level_data['background_rive']->getClientOriginalName();
                        $backgroundRiv = Storage::disk('s3')->putFileAs($backgroundFolder, $level_data['background_rive'],$originalName,'public');
                        $fileName = basename($backgroundRiv);
                        $createData['background_rive'] = $backgroundFolder . '/' . $fileName;
                    }

                    if (isset($level_data['background_thumbnail']) && is_file($level_data['background_thumbnail'])) {
                        $backgroundFolder = config('constant.background_thumbnail')."/$card->id/$card_level";
                        if (!Storage::exists($backgroundFolder)) {
                            Storage::makeDirectory($backgroundFolder);
                        }

                        $originalName = $level_data['background_thumbnail']->getClientOriginalName();
                        $backgroundRiv = Storage::disk('s3')->putFileAs($backgroundFolder, $level_data['background_thumbnail'],$originalName,'public');
                        $fileName = basename($backgroundRiv);
                        $createData['background_thumbnail'] = $backgroundFolder . '/' . $fileName;
                    }

                    if (isset($level_data['character_rive']) && is_file($level_data['character_rive'])) {
                        $characterFolder = config('constant.character_rive')."/$card->id/$card_level";
                        if (!Storage::exists($characterFolder)) {
                            Storage::makeDirectory($characterFolder);
                        }
                        $originalName = $level_data['character_rive']->getClientOriginalName();
                        $characterRiv = Storage::disk('s3')->putFileAs($characterFolder, $level_data['character_rive'],$originalName,'public');
                        $fileName = basename($characterRiv);
                        $createData['character_rive'] = $characterFolder . '/' . $fileName;
                    }

                    if (isset($level_data['character_thumbnail']) && is_file($level_data['character_thumbnail'])) {
                        $characterFolder = config('constant.character_thumbnail')."/$card->id/$card_level";
                        if (!Storage::exists($characterFolder)) {
                            Storage::makeDirectory($characterFolder);
                        }
                        $originalName = $level_data['character_thumbnail']->getClientOriginalName();
                        $characterRiv = Storage::disk('s3')->putFileAs($characterFolder, $level_data['character_thumbnail'],$originalName,'public');
                        $fileName = basename($characterRiv);
                        $createData['character_thumbnail'] = $characterFolder . '/' . $fileName;
                    }

                    if (isset($level_data['download_file']) && is_file($level_data['download_file'])) {
                        $downloadFolder = config('constant.download_file'). "/$card->id/$card_level";
                        if (!Storage::exists($downloadFolder)) {
                            Storage::makeDirectory($downloadFolder);
                        }
                        $originalName = $level_data['download_file']->getClientOriginalName();
                        $downloadFile = Storage::disk('s3')->putFileAs($downloadFolder, $level_data['download_file'],$originalName,'public');
                        $fileName = basename($downloadFile);
                        $createData['download_file'] = $downloadFolder . '/' . $fileName;
                    }

                    if (isset($level_data['feeding_rive']) && is_file($level_data['feeding_rive'])) {
                        $feedingFolder = config('constant.feeding_rive'). "/$card->id/$card_level";
                        if (!Storage::exists($feedingFolder)) {
                            Storage::makeDirectory($feedingFolder);
                        }
                        $originalName = $level_data['feeding_rive']->getClientOriginalName();
                        $feedingFile = Storage::disk('s3')->putFileAs($feedingFolder, $level_data['feeding_rive'],$originalName,'public');
                        $fileName = basename($feedingFile);
                        $createData['feeding_rive'] = $feedingFolder . '/' . $fileName;
                    }

                    CardLevelDetail::updateOrCreate(
                        [
                            "main_card_id" => $card->id,
                            "card_level" => $card_level
                        ],
                        $createData
                    );
                }
            }

            DB::commit();

            notify()->success("Cards ". trans("messages.update-success"), "Success", "topRight");
            return redirect()->route('admin.cards.index');
        } catch (\Exception $e) {

            Log::info($e);
            notify()->error("Cards ". trans("messages.update-error"), "Error", "topRight");
            return redirect()->route('admin.cards.index');
        }
    }

    public function delete($id)
    {
        $title = "Card";
        return view('admin.cards.delete', compact('id','title'));
    }

    public function destroy(DefaultCardsRives $id)
    {
        Storage::disk('s3')->delete($id->background_rive);
        Storage::disk('s3')->delete($id->character_rive);
        DefaultCardsRives::where('id',$id->id)->delete();

        DB::commit();
        $jsonData = [
            'status_code' => 200,
            'message' => "Cards ". trans("messages.delete-success")
        ];
        return response()->json($jsonData);
    }

    public function requestedCard(Request $request)
    {
        $title = "User's card sale request";
        DB::table('user_cards')->where('is_admin_read',1)->update(['is_admin_read' => 0]);
        return view('admin.requested-cards.index', compact('title'));
    }

    public function getJsonDataRequestedCard(Request $request){

        $columns = array(
            0 => 'users_detail.name',
            5 => 'default_cards_rives.card_name',
            6 => 'default_cards_rives.default_card_id',
            8    => 'user_card_sell_requests.created_at',
        );

        $limit = $request->input('length');
        $start = $request->input('start');
        $search = $request->input('search.value');
        $draw = $request->input('draw');
        $order = $columns[$request->input('order.0.column')];
        $dir = $request->input('order.0.dir');

        try {
            $data = [];

            $cardsQuery = UserCardSellRequest::join('user_cards','user_cards.id','user_card_sell_requests.card_id')
                ->join('users_detail','users_detail.user_id','user_cards.user_id')
                ->join('default_cards_rives','default_cards_rives.id','user_cards.default_cards_riv_id')
                ->join('default_cards','default_cards.id','default_cards_rives.default_card_id')
                ->leftjoin('user_bank_details','user_bank_details.id','user_cards.bank_id')
                ->leftjoin('card_level_details', function ($join) {
                    $join->on('card_level_details.main_card_id', '=', 'default_cards_rives.id')
                    ->whereRaw('card_level_details.card_level = user_card_sell_requests.card_level');
                })
                ->select(
                    'user_cards.*',
                    'default_cards_rives.card_name',
                    'default_cards.name as tab_name',
                    'users_detail.name as user_name',
                    'user_bank_details.recipient_name',
                    'user_bank_details.bank_name',
                    'user_bank_details.bank_account_number',
                    'user_card_sell_requests.status as card_status',
                    'user_card_sell_requests.id as sell_card_id',
                    'user_card_sell_requests.card_level as current_card_level',
                    DB::raw('(CASE
                        WHEN user_card_sell_requests.card_level != 1 THEN IFNULL(card_level_details.usd_price, default_cards_rives.usd_price)
                        ELSE default_cards_rives.usd_price
                    END) AS level_usd_price')
                )
                ->groupBy('user_card_sell_requests.id');
                //->whereIn('status',[UserCards::SOLD_CARD_STATUS, UserCards::REQUESTED_STATUS,UserCards::REQUEST_ACCEPT_STATUS]);

            if (!empty($search)) {
                $cardsQuery = $cardsQuery->where(function($q) use ($search){
                    $q->where('default_cards_rives.card_name', 'LIKE', "%{$search}%")
                    ->orWhere('users_detail.name', 'LIKE', "%{$search}%");
                });

            }
            $totalData = count($cardsQuery->get());
            $totalFiltered = $totalData;

            $cardsData = $cardsQuery->offset($start)
            ->limit($limit)
            ->orderBy($order, $dir)
            ->get();

            $count = 0;
            $defaultCard = getDefaultCard();

            foreach($cardsData as $cardsVal){

                $data[$count]['user_name'] = $cardsVal->user_name;
                $data[$count]['name'] = $cardsVal->card_name;
                $data[$count]['range'] = $cardsVal->tab_name;
                $data[$count]['recipient_name'] = $cardsVal->recipient_name;
                $data[$count]['bank_name'] = $cardsVal->bank_name;
                $data[$count]['bank_account_number'] = $cardsVal->bank_account_number;
                $data[$count]['card_level'] = getLevelNameByID($cardsVal->current_card_level);
                //$data[$count]['price'] = ($cardsVal->default_riv_detail && $cardsVal->default_riv_detail->usd_price) ? "$".$cardsVal->default_riv_detail->usd_price : 0;
                $data[$count]['price'] = ($cardsVal->level_usd_price) ? "$".$cardsVal->level_usd_price : 0;

                $disabled = ($cardsVal->card_status == 0) ? "" : "disabled";

                /* if($defaultCard->id == $cardsVal->default_cards_riv_id ){
                    $disabled = "disabled";
                } */

                $editButton = "<button $disabled role='button' href='javascript:void(0)' onclick='processedCard(" . $cardsVal->sell_card_id . ")' class='mx-1 btn btn-primary btn-sm' data-toggle='tooltip'>Processed</button>";

                $rejectButton = '';
                //if($defaultCard->id != $cardsVal->default_cards_riv_id) {
                    $rejectButton = "<button $disabled role='button' href='javascript:void(0)' onclick='rejectCard(" . $cardsVal->sell_card_id . ")' class='mx-1 btn btn-danger btn-sm' data-toggle='tooltip'>Reject</button>";
                //}

                $data[$count]['actions'] = "<div class='d-flex'> $editButton $rejectButton </div>";
                $count++;
            }

            $jsonData = array(
                "draw" => intval($draw),
                "recordsTotal" => intval($totalData),
                "recordsFiltered" => intval($totalFiltered),
                "data" => $data,
                "cardsData" => $cardsData
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

    public function processedCard(Request $request, $card_id)
    {
        $title = "Card Process";

        return view('admin.requested-cards.confirm', compact('title','card_id'));
    }

    public function actionProcessedCard(Request $request, $card_id)
    {
        try{
            DB::beginTransaction();
            $sellCard = UserCardSellRequest::where('id',$card_id)->first();
            $user_card = UserCards::whereId($sellCard->card_id)->first();
            $user_detail = DB::table('users_detail')->where('user_id', $user_card->user_id)->first();
            $language_id = ($user_detail) ? $user_detail->language_id : 4;
            $devices = UserDevices::where('user_id', $user_card->user_id)->pluck('device_token')->toArray();

            $defaultCard = getDefaultCard();

            if($defaultCard->id == $user_card->default_cards_riv_id){
                if($user_card->active_level == CardLevel::DEFAULT_LEVEL){
                    $card_level_status = $user_card->card_level_status;
                }else{
                    $cardLevelData = UserCardLevel::where('user_card_id',$sellCard->card_id)->where('card_level',$user_card->active_level)->first();
                    $card_level_status = $cardLevelData->card_level_status;
                }
                $history = UserCardResetHistory::create([
                    'user_id' => $user_card->user_id,
                    'sell_card_id' => $card_id,
                    'card_level' => $user_card->active_level,
                    'love_count' => $user_card->love_count,
                    'card_level_status' => $card_level_status
                ]);
                UserCards::whereId($sellCard->card_id)->update([
                    'status' => UserCards::ASSIGN_STATUS,
                    'love_count' => 0,
                    'active_level' => CardLevel::DEFAULT_LEVEL,
                    'card_level_status' => UserCards::NORMAL_STATUS
                ]);

                $title_msg = __("messages.language_$language_id.process_default_card");
                $notify_type = Notice::SELL_DEFAULT_CARD_SUCCESS;
                $format = ($user_card && $user_card->default_riv_detail ) ? $user_card->default_riv_detail->card_name : '';

                Notice::create([
                    'notify_type' => Notice::SELL_DEFAULT_CARD_SUCCESS,
                    'user_id' => $user_card->user_id,
                    'to_user_id' => $user_card->user_id,
                    'entity_type_id' => EntityTypes::REQUESTED_CARD,
                    'entity_id' => $sellCard->card_id,
                    'title' => $format,
                    'sub_title' => $history->id,
                ]);

                if (count($devices) > 0) {
                    $result = $this->sentPushNotification($devices,$title_msg, $format, [] ,$notify_type, $sellCard->card_id);
                }
            }else{
                UserCards::whereId($sellCard->card_id)->update(['status' => UserCards::REQUEST_ACCEPT_STATUS,'is_applied' => 0]);

                if($user_card->is_applied == true){
                    UserCards::where('default_cards_riv_id',$defaultCard->id)->update(['is_applied' => 1]);
                }

                $title_msg = __("messages.language_$language_id.process_card");
                $notify_type = Notice::SELL_CARD_SUCCESS;
                $format = ($user_card && $user_card->default_riv_detail ) ? $user_card->default_riv_detail->card_name : '';

                $notice = Notice::create([
                    'notify_type' => Notice::SELL_CARD_SUCCESS,
                    'user_id' => $user_card->user_id,
                    'to_user_id' => $user_card->user_id,
                    'entity_type_id' => EntityTypes::REQUESTED_CARD,
                    'entity_id' => $sellCard->card_id,
                    'title' => $format,
                    'sub_title' => '',
                ]);

                if (count($devices) > 0) {
                    $result = $this->sentPushNotification($devices,$title_msg, $format, [] ,$notify_type, $sellCard->card_id);
                }
            }

            UserCardSellRequest::where('id',$card_id)->update(['status' => 1]);
            DB::commit();
            $jsonData = array(
                'success' => true,
                'message' => 'Card Processed successfully',
            );
            return response()->json($jsonData);
        }  catch (\Exception $ex) {
            Log::info($ex);
            DB::rollback();
            $jsonData = array(
                'success' => false,
                'message' => 'Error in Card Processed',
            );
            return response()->json($jsonData);
        }
    }

    public function rejectCard(Request $request, $card_id)
    {
        $title = "Card Reject";
        return view('admin.requested-cards.reject', compact('title','card_id'));
    }

    public function actionRejectCard(Request $request, $card_id)
    {
        try{
            $inputs = $request->all();
            $reason = $inputs['reason'] ?? '';
            DB::beginTransaction();
            $sellCard = UserCardSellRequest::where('id',$card_id)->first();
            $user_card = UserCards::whereId($sellCard->card_id)->first();
            $user_detail = DB::table('users_detail')->where('user_id', $user_card->user_id)->first();
            $language_id = ($user_detail) ? $user_detail->language_id : 4;

            $devices = UserDevices::where('user_id', $user_card->user_id)->pluck('device_token')->toArray();

            $defaultCard = getDefaultCard();

            if($defaultCard->id == $user_card->default_cards_riv_id) {
                if ($user_card->active_level == CardLevel::DEFAULT_LEVEL) {
                    $card_level_status = $user_card->card_level_status;
                } else {
                    $cardLevelData = UserCardLevel::where('user_card_id', $sellCard->card_id)->where('card_level', $user_card->active_level)->first();
                    $card_level_status = $cardLevelData->card_level_status;
                }
                UserCardResetHistory::create([
                    'user_id' => $user_card->user_id,
                    'sell_card_id' => $card_id,
                    'card_level' => $user_card->active_level,
                    'love_count' => $user_card->love_count,
                    'card_level_status' => $card_level_status
                ]);
                UserCards::whereId($sellCard->card_id)->update([
                    'status' => UserCards::ASSIGN_STATUS,
                    'love_count' => 0,
                    'active_level' => CardLevel::DEFAULT_LEVEL,
                    'card_level_status' => UserCards::NORMAL_STATUS
                ]);
            }else {
                UserCards::whereId($sellCard->card_id)->update(['status' => UserCards::ASSIGN_STATUS]);
            }

            $title_msg = __("messages.language_$language_id.reject_card");
            $notify_type = Notice::SELL_CARD_REJECT;
            $format = $reason;

            $notice = Notice::create([
                'notify_type' => Notice::SELL_CARD_REJECT,
                'user_id' => $user_card->user_id,
                'to_user_id' => $user_card->user_id,
                'entity_type_id' => EntityTypes::REQUESTED_CARD,
                'entity_id' => $sellCard->card_id,
                'title' => ($user_card && $user_card->default_riv_detail) ? $user_card->default_riv_detail->card_name : '',
                'sub_title' => $reason,
            ]);

            if (count($devices) > 0) {
                $result = $this->sentPushNotification($devices, $title_msg, $format, [], $notify_type, $sellCard->card_id);
            }
            UserCardSellRequest::where('id',$card_id)->update(['status' => 2]);
            DB::commit();
            $jsonData = array(
                'success' => true,
                'message' => 'Card Rejected successfully',
            );
            return response()->json($jsonData);
        }  catch (\Exception $ex) {
            Log::info($ex);
            DB::rollback();
            $jsonData = array(
                'success' => false,
                'message' => 'Error in Card Reject',
            );
            return response()->json($jsonData);
        }
    }


    public function userCards(Request $request, $card)
    {
        $title = "Card's User";
        return view('admin.cards.assign-users.index', compact('title','card'));
    }

    public function getUserCardsJson(Request $request, $card){
        $columns = array(
            0 => 'default_cards_rives.card_name',
            1 => 'users_detail.name',
            2 => 'default_cards_rives.default_card_id',
            3 => 'action',
        );

        $limit = $request->input('length');
        $start = $request->input('start');
        $search = $request->input('search.value');
        $draw = $request->input('draw');
        $order = $columns[$request->input('order.0.column')];
        $dir = $request->input('order.0.dir');

        try {
            $data = [];
            $cardsQuery = UserCards::join('users_detail','users_detail.user_id','user_cards.user_id')
                ->join('default_cards_rives','default_cards_rives.id','user_cards.default_cards_riv_id')
                ->join('users','users.id','users_detail.user_id')
                ->select(
                    'user_cards.*',
                    'default_cards_rives.card_name',
                    'users_detail.name as user_name',
                    'users_detail.mobile as phone',
                    'users.email as email_address'
                )
                ->where('default_cards_rives.id',$card)
                ->whereNull('users.deleted_at');

            if (!empty($search)) {
                $cardsQuery = $cardsQuery->where(function($q) use ($search){
                    $q->where('users.email', 'LIKE', "%{$search}%")
                    ->orWhere('users_detail.name', 'LIKE', "%{$search}%");
                });

            }

            $cardsQuery = $cardsQuery->groupBy('users.id');

            $totalData = count($cardsQuery->get());
            $totalFiltered = $totalData;

            $cardsData = $cardsQuery->offset($start)
            ->limit($limit)
            ->orderBy($order, $dir)
            ->get();

            $count = 0;

            foreach($cardsData as $cardsVal){

                $data[$count]['card_name'] = $cardsVal->card_name;
                $data[$count]['user_name'] = $cardsVal->user_name;
                $data[$count]['email_address'] = $cardsVal->email_address;
                $data[$count]['phone'] = $cardsVal->phone;

                $data[$count]['actions'] = "<div class='d-flex'> - </div>";
                $count++;
            }

            $jsonData = array(
                "draw" => intval($draw),
                "recordsTotal" => intval($totalData),
                "recordsFiltered" => intval($totalFiltered),
                "data" => $data,
                "cardsData" => $cardsData
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

    public function viewCardData(Request $request,$card)
    {
        $title = "View Card";
        $inputs = $request->all();
        $cardData = DefaultCardsRives::find($card);
        $fileData = $inputs['file'] ?? $cardData->character_rive_url;
        $background = $inputs['background'] ?? 1; //

        if($background == 1){
            $backgroundRiv = $cardData->background_rive_url;
        }else{
            $levelDetailData = $cardData->cardLevels()->firstWhere('card_level',$background);
            $backgroundRiv = $levelDetailData->background_rive_url ?? '';
        }

        return view('admin.cards.view', compact('card','title','cardData','fileData','backgroundRiv'));
    }

    public function removeCardImage(Request $request,$card = ''){
        $inputs = $request->all();

        try{
            DB::beginTransaction();
            $removeKey = $inputs['remove_key'] ?? '';
            $remove_id = $inputs['remove_id'] ?? '';
            if($removeKey && $card && $remove_id == 1){
                $cardData = DefaultCardsRives::whereId($card)->first();
                if($cardData->$removeKey){
                    Storage::disk('s3')->delete($cardData->$removeKey);
                    DefaultCardsRives::whereId($card)->update([$removeKey => '']);
                }
            }else if($remove_id > 1 && $removeKey){
                $cardData = CardLevelDetail::where('main_card_id',$card)->where('card_level',$remove_id)->first();
                if($cardData->$removeKey){
                    Storage::disk('s3')->delete($cardData->$removeKey);
                    CardLevelDetail::where('main_card_id',$card)->where('card_level',$remove_id)->update([$removeKey => '']);
                }
            }

            DB::commit();
            $jsonData = array(
                'success' => true,
                'message' => 'Card Image removed successfully'
            );
            return response()->json($jsonData);
        }catch (\Exception $ex) {
            Log::info($ex);
            DB::rollback();
            $jsonData = array(
                'success' => false,
                'message' => 'Error in Card Image removed',
            );
            return response()->json($jsonData);
        }
    }

    public function cardMusicStore(Request $request, $card){
        $inputs = $request->all();
        try{

            $optionFolder = config('constant.card_music')."/".$card;
            $music_file = $inputs['music_file'];

            if(!empty($music_file) && is_file($music_file)){
                $originalName = $music_file->getClientOriginalName();

                if (!Storage::disk('s3')->exists($optionFolder)) {
                    Storage::disk('s3')->makeDirectory($optionFolder);
                }
                $mainFile = Storage::disk('s3')->putFileAs($optionFolder, $music_file, $originalName, 'public');
                $fileName = basename($mainFile);
                //echo basename($mainFile); exit;
                $file_url = $optionFolder . '/' . $originalName;
                CardMusic::create([
                    'card_id' => $card,
                    'music_file' => $file_url,
                    'menu_order' => 0,
                ]);
            }

            DB::commit();
            $jsonData = array(
                'success' => true,
                'message' => 'Card Music successfully added.',
                'redirect' => route('admin.manage.music',['card' => $card])
            );
            return response()->json($jsonData);
        }catch (\Exception $ex) {
            Log::info($ex);
            DB::rollback();
            $jsonData = array(
                'success' => false,
                'message' => 'Error in Card music add',
                'redirect' => route('admin.manage.music',['card' => $card])
            );
            return response()->json($jsonData);
        }
    }

    public function deleteMusic(Request $request){
        $inputs = $request->all();
        try{
            $music_id = $inputs['music_id'] ?? '';
            if($music_id){
                $musicData = CardMusic::where('id',$music_id)->first();
                if($musicData){
                    Storage::disk('s3')->delete($musicData->music_file);
                }
                CardMusic::where('id',$music_id)->delete();
            }
            return response()->json(["success" => true, "message" => "Music". trans("messages.delete-success")], 200);
        } catch (\Exception $e) {
            Log::info($e);
            return response()->json(["success" => false, "message" => "Music". trans("messages.delete-error")], 200);
        }
    }

    public function updateOrder(Request $request){
        $inputs = $request->all();
        try{
            $order = $inputs['order'] ?? '';
            if(!empty($order)){
                foreach($order as $value){
                    $isUpdate = CardMusic::where('id',$value['id'])->update(['menu_order' => $value['position']]);
                }
            }
            return response()->json([
                'success' => true
            ]);
        }catch (\Exception $ex) {
            Log::info($ex);
            return response()->json([
                'success' => false
            ]);
        }
    }

    public function getDeleteMusic($id){
        $title = "Music";
        return view('admin.cards.music.delete', compact('id','title'));
    }

    public function createMusicView(Request $request, $card){
        $title = "Create Music";
        return view('admin.cards.music.create',compact('title','card'));
    }
    public function manageMusic(Request $request, $card){
        $title = "Manage Music";
        return view('admin.cards.music.index',compact('title','card'));
    }

    public function getMusicJsonData(Request $request,$card)
    {
        $columns = array(
            0 => 'music_file',
            1 => 'created_at',
            2 => 'menu_order',
        );

        $limit = $request->input('length');
        $start = $request->input('start');
        $search = $request->input('search.value');
        $draw = $request->input('draw');
        $order = $columns[$request->input('order.0.column')];
        $dir = $request->input('order.0.dir');

        try{

        $adminTimezone = $this->getAdminUserTimezone();
        $musicQuery = CardMusic::select('card_music.*')->where('card_id',$card);

            if (!empty($search)) {
                $musicQuery = $musicQuery->where(function($q) use ($search){
                    $q->where('card_music.music_file', 'LIKE', "%{$search}%");
                });
            }

            $totalData = count($musicQuery->get());
            $totalFiltered = $totalData;

            $musicData = $musicQuery->offset($start)
                ->limit($limit)
                ->orderBy($order, $dir)
                ->get();

            $count = 0;
            foreach($musicData as $music){

                $deleteButton =  "<a href='javascript:void(0);' onClick='deleteMusicConfirmation(".$music->id.")' class='btn-sm mx-1 btn btn-danger'><i class='fas fa-trash-alt'></i></a>";
                $playButton =  "<a href='javascript:void(0);' onClick='playMusic(`".$music->music_file_url."`,this)' class='btn-sm mx-1 btn btn-primary play playiconparent'><i class='playicon fas fa-play'></i></a>";

                $data[$count]['id'] = $music->id;
                $data[$count]['name'] = $music->music_name ?? '';
                $data[$count]['date'] = $this->formatDateTimeCountryWise($music->created_at,$adminTimezone);
                $data[$count]['order'] = $music->menu_order;

                $data[$count]['actions'] = "<div class='d-flex align-items-center'>$playButton $deleteButton</div>";
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


    public function manageStatusRive(Request $request, $card)
    {
        $title = "Manage Status Rive";
        $cardLevel = CardLevel::all();
        $cardData = DefaultCardsRives::find($card);
        $statusRives = $cardData->cardLevelStatusRive;
        $statusThumbs = $cardData->cardLevelStatusThumb;
        return view('admin.cards.manage-status-rive',compact('title','card','cardLevel','statusRives','statusThumbs'));
    }

    public function updateStatusRive(Request $request, $card)
    {
        $inputs = $request->all();
        try {
            $detail = $inputs['detail'] ?? [];
            $isFileUpdate = false;

            if(!empty($detail)){
                foreach ($detail as $data){
                    if(is_array($data) && !empty($data)){
                        foreach ($data as $fileData){
                            $file = $fileData['file'] ?? '';
                            $thumb_file = $fileData['thumb_file'] ?? '';
                            $level_id = $fileData['level_id'] ?? '';
                            $status = $fileData['status'] ?? '';
                            if(isset($file) && is_file($file)){
                                $statusRiveFolder = config('constant.status_rive_file'). "/$card/$level_id/$status";

                                if (!Storage::exists($statusRiveFolder)) {
                                    Storage::makeDirectory($statusRiveFolder);
                                }
                                $originalName = $file->getClientOriginalName();
                                $downloadFile = Storage::disk('s3')->putFileAs($statusRiveFolder, $file,$originalName,'public');
                                if(!empty($downloadFile) && !empty($level_id) && !empty($status)) {
                                    $fileName = basename($downloadFile);
                                    $fileURL = $statusRiveFolder . '/' . $fileName;

                                    CardStatusRives::updateOrCreate([
                                        'card_id' => $card,
                                        'card_level_id' => $level_id,
                                        'card_level_status' => $status
                                    ], [
                                        'character_riv' => $fileURL
                                    ]);
                                    $isFileUpdate = true;
                                }
                            }

                            if(isset($thumb_file) && is_file($thumb_file)){
                                $statusThumbFolder = config('constant.status_thumb_file'). "/$card/$level_id/$status";

                                if (!Storage::exists($statusThumbFolder)) {
                                    Storage::makeDirectory($statusThumbFolder);
                                }
                                $originalName = $thumb_file->getClientOriginalName();
                                $downloadFile = Storage::disk('s3')->putFileAs($statusThumbFolder, $thumb_file,$originalName,'public');
                                if(!empty($downloadFile) && !empty($level_id) && !empty($status)) {
                                    $fileName = basename($downloadFile);
                                    $fileURL = $statusThumbFolder . '/' . $fileName;

                                    CardStatusThumbnails::updateOrCreate([
                                        'card_id' => $card,
                                        'card_level_id' => $level_id,
                                        'card_level_status' => $status
                                    ], [
                                        'character_thumb' => $fileURL
                                    ]);
                                    $isFileUpdate = true;
                                }
                            }
                        }
                    }
                }

                if($isFileUpdate != true) {
                    $jsonData = array(
                        'success' => false,
                        'message' => 'Please update at least one status file',
                    );
                } else {
                    $jsonData = array(
                        'success' => true,
                        'message' => 'Card Processed successfully',
                        'redirect' => route('admin.cards.index')
                    );
                }
                return response()->json($jsonData);
            }
        } catch (\Exception $e){
            Log::info($e);
            $jsonData = array(
                'success' => false,
                'message' => 'Error in update status rive',
            );
            return response()->json($jsonData);
        }
    }
}
