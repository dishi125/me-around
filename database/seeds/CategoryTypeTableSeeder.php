<?php

use Illuminate\Database\Seeder;
use App\Models\CategoryTypes;

class CategoryTypeTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $items = ['Shop', 'Hospital', 'Community','Report', 'Custom', 'Suggest 2', 'Shop 2'];

        foreach($items as $item) {
            CategoryTypes::firstOrCreate(['name' => $item]);
        }
    }
}
