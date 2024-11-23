<?php

use App\Models\MetalkOptions;
use App\Models\MetalkDropdown;
use Illuminate\Database\Seeder;

class MetalkOptionsSeed extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $options = [
            ['key' => 'tablet_leaves_riv', 'label' => 'Tablet Leaves Riv', 'type' => MetalkOptions::FILE, 'options_type' => MetalkOptions::THEME_OPTIONS],
            ['key' => 'tablet_winter_riv', 'label' => 'Tablet Winter Riv', 'type' => MetalkOptions::FILE, 'options_type' => MetalkOptions::THEME_OPTIONS],
            ['key' => 'tablet_spring_riv', 'label' => 'Tablet Spring Riv', 'type' => MetalkOptions::FILE, 'options_type' => MetalkOptions::THEME_OPTIONS],
            ['key' => 'select_tablet_riv', 'label' => 'Select Tablet Riv', 'type' => MetalkOptions::DROPDOWN, 'options_type' => MetalkOptions::THEME_OPTIONS ,
                'options' => [
                   ['key' => 'tablet_leaves_riv', 'label' => 'Tablet Leaves Riv'],
                   ['key' => 'tablet_winter_riv', 'label' => 'Tablet Winter Riv'],
                   ['key' => 'tablet_spring_riv', 'label' => 'Tablet Spring Riv']
                ]
            ],
            ['key' => 'background_image', 'label' => 'Background Image', 'type' => MetalkOptions::FILE, 'options_type' => MetalkOptions::THEME_OPTIONS],

            // EXPLANATION 
            ['key' => 'thumbnail_image', 'label' => 'Thumbnail Image', 'type' => MetalkOptions::FILE, 'options_type' => MetalkOptions::EXPLANATION],
            ['key' => 'interior_image', 'label' => 'Interior/Work Image', 'type' => MetalkOptions::FILE, 'options_type' => MetalkOptions::EXPLANATION],
            ['key' => 'main_image', 'label' => 'Main Profile Image', 'type' => MetalkOptions::FILE, 'options_type' => MetalkOptions::EXPLANATION],
            ['key' => 'certification_info', 'label' => 'Certification, tool info', 'type' => MetalkOptions::FILE, 'options_type' => MetalkOptions::EXPLANATION],
            ['key' => 'address_info', 'label' => 'Address info', 'type' => MetalkOptions::FILE, 'options_type' => MetalkOptions::EXPLANATION],
            ['key' => 'price_settings', 'label' => 'Price Settings', 'type' => MetalkOptions::FILE, 'options_type' => MetalkOptions::EXPLANATION],
            ['key' => 'willing_discount', 'label' => 'Willing Discount', 'type' => MetalkOptions::FILE, 'options_type' => MetalkOptions::EXPLANATION],
            ['key' => 'add_phone_number', 'label' => 'Wants to add Phone number', 'type' => MetalkOptions::FILE, 'options_type' => MetalkOptions::EXPLANATION],
            ['key' => 'activity_name', 'label' => 'Activity Name ( Main Name )', 'type' => MetalkOptions::FILE, 'options_type' => MetalkOptions::EXPLANATION],
            ['key' => 'place_name', 'label' => 'Place Name ( Sub Name )', 'type' => MetalkOptions::FILE, 'options_type' => MetalkOptions::EXPLANATION],
            ['key' => 'specialty_of', 'label' => 'Specialty of ( Sub Title )', 'type' => MetalkOptions::FILE, 'options_type' => MetalkOptions::EXPLANATION],
        ];

        foreach($options as $data){
            $optionUpdated = MetalkOptions::updateOrCreate([
                'key' => $data['key'],
            ],[
                'label' => $data['label'],
                'type' => $data['type'],
                'options_type' => $data['options_type'],
            ]);

            if($data['type'] == MetalkOptions::DROPDOWN && isset($data['options'])){
                //$option->dropdown()->sync($data['options'],true); 
                foreach($data['options'] as $option){
                    MetalkDropdown::updateOrCreate([
                        'key' => $option['key'],
                    ],[
                        'label' => $option['label'],
                        'metalk_options_id' => $optionUpdated->id
                    ]); 
                }
            }
        }
    }
}
