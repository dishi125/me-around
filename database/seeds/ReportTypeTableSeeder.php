<?php

use Illuminate\Database\Seeder;
use App\Models\ReportTypes;

class ReportTypeTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $items = ['Shop','Hospital', 'Shop User','Shop Portfolio','Reviews','Reviews Comment','Reviews Comment Reply','Community','Community Comment','Community Comment Reply','Shop Place','Hospital Place','Association Community','Association Community Comment'];

        foreach($items as $item) {
            ReportTypes::firstOrCreate(['name' => $item]);
        }
    }
}
