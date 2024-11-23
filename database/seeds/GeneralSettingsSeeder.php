<?php

use App\Models\GeneralSettings;
use Illuminate\Database\Seeder;

class GeneralSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $data = [
            ['key' => 'ios_app_version','label' => 'IOS App Version', 'value' => 1],
            ['key' => 'android_app_version','label' => 'Android App Version', 'value' => 1],
            ['key' => 'display_app_version','label' => 'Display App Version', 'value' => 1],
            ['key' => 'last_deleted_view','label' => 'Last Deleted View', 'value' => date('Y-m-d H:i:s')],
        ];

        foreach ($data as $item) {
            GeneralSettings::firstOrCreate([
                'key' => $item['key']
            ],[
                'label' => $item['label'],
                'value' => $item['value']
            ]);
        }
    }
}
