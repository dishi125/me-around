<?php

use Illuminate\Database\Seeder;
use App\Models\RequestBookingStatus;

class RequsetBookingStatusTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $items = ['Talk', 'Booked', 'Visited', 'Completed', 'No Show', 'Cancelled'];

        foreach($items as $item) {
            RequestBookingStatus::firstOrCreate(['name' => $item]);
        }
    }
}
