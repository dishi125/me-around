<?php

use Illuminate\Database\Seeder;

class ChallengeConfigTableSeeder extends Seeder
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
                'key' => 'signup email',
                'value' => "gwb9160@nate.com",
            ],
            [
                'key' => 'new verification post',
                'value' => "gwb9160@nate.com",
            ],
        ];

        foreach ($items as $item) {
            $key = Str::slug($item['key'], '_');
            $planCount = \App\Models\ChallengeConfig::where('key', $key)->count();
            if ($planCount == 0) {
                $plans = \App\Models\ChallengeConfig::firstOrCreate([
                    'key' => $key,
                    'value' => $item['value'],
                ]);
            }
        }
    }
}
