<?php

use Illuminate\Database\Seeder;
use App\Models\EntityTypes;

class EntityTypeTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $items = ['Shop', 'Hospital', 'Normal User','Admin','Manager','Sub Manger','Community','Reviews','Association Community','Shop Post','Requested Card','Sub Admin','Tattoocity Admin','Spa Admin','Challenge Admin','Insta Admin','Qrcode Admin'];

        foreach($items as $item) {
            EntityTypes::firstOrCreate(['name' => $item]);
        }
    }
}
