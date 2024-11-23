<?php

use App\Models\DiscountCondition;
use Illuminate\Database\Seeder;

class DiscountConditionTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $items = [
            ['title' => 'condition_1'],       
            ['title' => 'condition_2'],       
            ['title' => 'condition_3'],       
            ['title' => 'condition_4'],       
            ['title' => 'condition_5'], 
        ];

        foreach ($items as $item) {
            $plans = DiscountCondition::firstOrCreate([
                'title' => $item['title'],
            ]);     
        }
    }
}
