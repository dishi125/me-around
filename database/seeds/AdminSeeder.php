<?php

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\Manager;
use App\Models\EntityTypes;
use App\Models\Status;
use App\Models\UserEntityRelation;
use Illuminate\Support\Str;

class AdminSeeder extends Seeder
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
                'name' => 'Spa Admin',
                'username' => 'spa@gmail.com',
                'email' => 'spa@gmail.com',
                'password' => bcrypt('dnjs9160'),
                'entity_type_id' => EntityTypes::SPAADMIN,
                'status_id' => Status::ACTIVE,
                'role' => 'Spa Admin',
                'app_type' => 'spa',
                'is_admin_read' => 0,
            ],
            [
                'name' => 'Tattoocity Admin',
                'username' => 'tattoo@gmail.com',
                'email' => 'tattoo@gmail.com',
                'password' => bcrypt('dnjs9160'),
                'entity_type_id' => EntityTypes::TATTOOADMIN,
                'status_id' => Status::ACTIVE,
                'role' => 'Tattoocity Admin',
                'app_type' => 'tattoocity',
                'is_admin_read' => 0,
            ],
            [
                'name' => 'Challenge Admin',
                'username' => 'challenge@gmail.com',
                'email' => 'challenge@gmail.com',
                'password' => bcrypt('Iu1ieN!!'),
                'entity_type_id' => EntityTypes::CHALLENGEADMIN,
                'status_id' => Status::ACTIVE,
                'role' => 'Challenge Admin',
                'app_type' => 'challenge',
                'is_admin_read' => 0,
            ],
            [
                'name' => 'Insta Admin',
                'username' => 'admin@admin.com',
                'email' => 'admin@admin.com',
                'password' => bcrypt('concetto@123'),
                'entity_type_id' => EntityTypes::INSTAADMIN,
                'status_id' => Status::ACTIVE,
                'role' => 'Insta Admin',
                'app_type' => 'insta',
                'is_admin_read' => 0,
            ],
            [
                'name' => 'Qrcode Admin',
                'username' => 'admin@admin.com',
                'email' => 'admin@admin.com',
                'password' => bcrypt('concetto@123'),
                'entity_type_id' => EntityTypes::QRCODEADMIN,
                'status_id' => Status::ACTIVE,
                'role' => 'Qrcode Admin',
                'app_type' => 'qr_code',
                'is_admin_read' => 0,
            ],
        ];

        foreach ($items as $item) {
            $newUser = User::firstOrCreate([
                'email' => $item['email'],
                'username' => $item['username'],
                'status_id' => $item['status_id'],
                'app_type' => $item['app_type'],
                'is_admin_read' => $item['is_admin_read'],
            ],[
                'password' => $item['password'],
            ]);

            $manager = Manager::create([
                'name' => $item['name'],
                'user_id' => $newUser->id,
                'recommended_code' => Str::upper(Str::random(7))
            ]);

            UserEntityRelation::create(['user_id' => $newUser->id,"entity_type_id" => $item['entity_type_id'],'entity_id' => $newUser->id]);

            $role = Role::firstOrCreate(['name' => $item['role']]);
            $role->syncPermissions(Permission::all());
            $newUser->assignRole([$role->id]);
        }
    }

}
