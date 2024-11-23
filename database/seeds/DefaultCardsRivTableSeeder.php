<?php

use Illuminate\Database\Seeder;
use App\Models\DefaultCards;
use App\Models\DefaultCardsRives;
use App\Models\CardLevel;
use App\Models\CardLevelDetail;

class DefaultCardsRivTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $getDefault = DefaultCards::where('name',DefaultCards::DEFAULT_CARD)->first();

        if($getDefault){
            $data = [
                'card_name' => DefaultCards::DEFAULT_CARD,
                'order' => 1,
                'required_love_in_days' => 1,
                'card_level' => 1,
            ];
            $cardRive = DefaultCardsRives::updateOrCreate(['default_card_id'=>$getDefault->id],$data);

            $other_level = CardLevel::where('id','!=',CardLevel::DEFAULT_LEVEL)->get();

            if($cardRive){
                foreach ($other_level as $level_data){
                    $createData = [];
                    $createData = [
                        "card_name" => DefaultCards::DEFAULT_CARD,
                        "usd_price" => 0,
                        "japanese_yen_price" => 0,
                        "chinese_yuan_price" => 0,
                        "korean_won_price" => 0,
                        "required_love_in_days" => 1,
                    ];
                    CardLevelDetail::updateOrCreate(
                        [
                            "main_card_id" => $cardRive->id,
                            "card_level" => $level_data->id
                        ],
                        $createData
                    );
                }
            }
        }
    }
}
