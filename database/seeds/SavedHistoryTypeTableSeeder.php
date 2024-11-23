<?php

use Illuminate\Database\Seeder;
use App\Models\SavedHistoryTypes;

class SavedHistoryTypeTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $items = ['Shop', 'Hospital', 'Community','Reviews','Association Community'];

        foreach($items as $item) {
            SavedHistoryTypes::firstOrCreate(['name' => $item]);
        }
    }
}
