<?php

use Illuminate\Database\Seeder;

class InstaImportantSettingSeeder extends Seeder
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
                'field' => 'Default download',
                'value' => 10,
            ],
        ];

        foreach ($items as $item) {
            $data = \App\Models\InstaImportantSetting::firstOrCreate([
                'field' => $item['field'],
                'value' => $item['value'],
            ]);
        }

    }
}
