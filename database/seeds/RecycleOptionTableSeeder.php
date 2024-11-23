<?php

use Illuminate\Database\Seeder;
use App\Models\RecycleOption;

class RecycleOptionTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $items = [
            [
                "value" => "permanent",
                "type" => "permanent"
            ],
            [
                "value" => "1",
                "type" => "time"
            ],
            ["value" => "2"],
            ["value" => "3"],
            ["value" => "4"],
            ["value" => "5"],
            ["value" => "6"],
            ["value" => "7"],
            ["value" => "8"],
            ["value" => "9"],
            ["value" => "10"],
            ["value" => "20"],
            ["value" => "30"],
            ["value" => "40"],
            ["value" => "50"],
            ["value" => "60"],
            ["value" => "70"],
            ["value" => "80"],
            ["value" => "90"],
            ["value" => "100"],
            ["value" => "1000"]
        ];

        foreach ($items as $item) {

            if(empty($item['type'])){
                $type = 'times';
            }else{
                $type = $item['type'];
            }

            $recycle = RecycleOption::firstOrCreate([
                'value' => $item['value'],
                'type' => $type,
                
            ]);       
        }
    }
}
