<?php

use Illuminate\Database\Seeder;
use App\Models\DefaultCards;

class DefaultCardsTableSeeder extends Seeder
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
                'name' => 'Default',
                'start' => 1,
                'end' => 79,
            ],
            [
                'name' => 'Lv. 80',
                'start' => 80,
                'end' => 159,
            ],
            [
                'name' => 'Lv. 160',
                'start' => 160,
                'end' => 239,
            ],
            [
                'name' => 'Lv. 240',
                'start' => 240,
                'end' => 319,
            ],
            [
                'name' => 'Lv. 320',
                'start' => 320,
                'end' => 399,
            ],
            [
                'name' => 'Lv. 400',
                'start' => 400,
                'end' => 479,
            ],
            [
                'name' => 'Lv. 480',
                'start' => 480,
                'end' => 559,
            ],
            [
                'name' => 'Lv. 560',
                'start' => 560,
                'end' => 639,
            ],
            [
                'name' => 'Lv. 640',
                'start' => 640,
                'end' => 719,
            ],
            [
                'name' => 'Lv. 720',
                'start' => 720,
                'end' => 799,
            ],
            [
                'name' => 'Lv. 800',
                'start' => 800,
                'end' => 879,
            ],
            [
                'name' => 'Lv. 880',
                'start' => 880,
                'end' => 959,
            ],
            [
                'name' => 'Lv. 960',
                'start' => 960,
                'end' => 1039,
            ],
        ];

        foreach ($items as $item) {
            $cards = DefaultCards::firstOrCreate([
                'name' => $item['name'],
                'start' => $item['start'],
                'end' => $item['end'],
            ]);       
        }
    }
}
