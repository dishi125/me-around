<?php

use App\Models\User;
use Illuminate\Database\Seeder;
use App\Models\Country;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\Manager;
use App\Models\EntityTypes;
use App\Models\Address;
use App\Models\Status;
use App\Models\UserEntityRelation;
use Illuminate\Support\Str;

class SubAdminSeeder extends Seeder
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
                'name' => 'Sub Admin',
                'username' => 'admin@gmail.com',
                'email' => 'admin@gmail.com',
                'password' => bcrypt('dnjs9160'),
                'entity_type_id' => EntityTypes::SUBADMIN,
                'status_id' => Status::ACTIVE,
                'role' => 'Sub Admin'
            ],
        ];

        foreach ($items as $item) {
            $newUser = User::firstOrCreate([
                'email' => $item['email'],
                'password' => $item['password'],
                'username' => $item['username'],
                'status_id' => $item['status_id'],
            ]);

            $manager = Manager::create([
                'name' => $item['name'],
                'user_id' => $newUser->id,
                'recommended_code' => Str::upper(Str::random(7))
            ]);

            UserEntityRelation::create(['user_id' => $newUser->id,"entity_type_id" => $item['entity_type_id'],'entity_id' => $newUser->id]);

//            $role = Role::firstOrCreate(['name' => $item['role']]);
//            $role->syncPermissions(Permission::all());
            $role = Role::where("name","Sub Admin")->first();
            $newUser->assignRole([$role->id]);
        }
    }
}
