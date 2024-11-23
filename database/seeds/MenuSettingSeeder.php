<?php

use Illuminate\Database\Seeder;
use App\Models\MenuSetting;

class MenuSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $data = MenuSetting::MENU_LIST;

        foreach ($data as $item) {
            MenuSetting::firstOrCreate([
                'menu_key' => $item['menu_key'],
                'country_code' => null
            ],[
                'menu_name' => $item['menu_name'],
                'is_show' => $item['is_show'],
                'menu_order' => $item['menu_order']
            ]);
        }
    }
}
