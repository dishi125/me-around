<?php

use Illuminate\Database\Seeder;
use App\Models\PackagePlan;

class PackagePlanTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $items = ['Bronze', 'Silver', 'Gold', 'Platinium'];

        foreach($items as $item) {
            PackagePlan::firstOrCreate(['name' => $item]);
        }
    }
}
