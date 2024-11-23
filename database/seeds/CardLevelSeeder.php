<?php

use App\Models\CardLevel;
use Illuminate\Database\Seeder;

class CardLevelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $data = [
            /* ['level_name' => 'Egg', 'start' => 0, 'end' => 28, 'range' => '0~28'],
            ['level_name' => 'Small', 'start' => 29, 'end' => 68, 'range' => '29~68'],
            ['level_name' => 'Middle', 'start' => 69, 'end' => 188, 'range' => '69~188'],
            ['level_name' => 'Big', 'start' => 189, 'end' => 388, 'range' => '189~388'],
            ['level_name' => 'Adult', 'start' => 389, 'end' => 688, 'range' => '389~688'], */
            ['level_name' => 'Egg', 'start' => 0, 'end' => 21, 'range' => '0~21'],
            ['level_name' => 'Small', 'start' => 22, 'end' => 50, 'range' => '22~50'],
            ['level_name' => 'Middle', 'start' => 51, 'end' => 100, 'range' => '51~100'],
            ['level_name' => 'Big', 'start' => 101, 'end' => 200, 'range' => '101~200'],
            ['level_name' => 'Adult', 'start' => 201, 'end' => 350, 'range' => '201~350'],
        ];

        foreach ($data as $item) {
            CardLevel::updateOrCreate([
                'level_name' => $item['level_name']
            ],[
                'start' => $item['start'],
                'end' => $item['end'],
                'range' => $item['range']
            ]);
        }
    }
}
