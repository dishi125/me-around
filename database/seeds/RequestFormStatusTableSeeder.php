<?php

use Illuminate\Database\Seeder;
use App\Models\RequestFormStatus;

class RequestFormStatusTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $items = ['Confirm', 'Pending', 'Reject'];

        foreach($items as $item) {
            RequestFormStatus::firstOrCreate(['name' => $item]);
        }
    }
}
