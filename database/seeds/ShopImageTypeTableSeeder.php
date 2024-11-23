<?php

use Illuminate\Database\Seeder;
use App\Models\ShopImagesTypes;

class ShopImageTypeTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $items = ['Thumb', 'Main Profile', 'Workplace', 'Portfolio'];

        foreach($items as $item) {
            ShopImagesTypes::firstOrCreate(['name' => $item]);
        }
    }
}
