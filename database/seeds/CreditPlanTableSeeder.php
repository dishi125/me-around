<?php

use App\Models\User;
use App\Models\Member;
use Illuminate\Database\Seeder;
use App\Models\EntityTypes;
use App\Models\PackagePlan;
use App\Models\CreditPlans;

class CreditPlanTableSeeder extends Seeder
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
                'entity_type_id' => EntityTypes::HOSPITAL,
                'package_plan_id' => PackagePlan::BRONZE,
                'deduct_rate' => 2000,
                'amount' => 300000,
                'km' => 1.5,
                'no_of_posts' => 5
            ],
            [
                'entity_type_id' => EntityTypes::HOSPITAL,
                'package_plan_id' => PackagePlan::SILVER,
                'deduct_rate' => 1500,
                'amount' => 500000,
                'km' => 2.5,
                'no_of_posts' => 10
            ],
            [
                'entity_type_id' => EntityTypes::HOSPITAL,
                'package_plan_id' => PackagePlan::GOLD,
                'deduct_rate' => 1000,
                'amount' => 700000,
                'km' => 3.5,
                'no_of_posts' => 15
            ],
            [
                'entity_type_id' => EntityTypes::HOSPITAL,
                'package_plan_id' => PackagePlan::PLATINIUM,
                'deduct_rate' => 500,
                'amount' => 900000,
                'km' => 4.5,
                'no_of_posts' => 20
            ], 
            [
                'entity_type_id' => EntityTypes::SHOP,
                'package_plan_id' => PackagePlan::BRONZE,
                'deduct_rate' => 2000,
                'amount' => 200000,
                'km' => 1.5,
                'no_of_posts' => 0
            ],
            [
                'entity_type_id' => EntityTypes::SHOP,
                'package_plan_id' => PackagePlan::SILVER,
                'deduct_rate' => 1500,
                'amount' => 400000,
                'km' => 2.5,
                'no_of_posts' => 0
            ],
            [
                'entity_type_id' => EntityTypes::SHOP,
                'package_plan_id' => PackagePlan::GOLD,
                'deduct_rate' => 1000,
                'amount' => 600000,
                'km' => 3.5,
                'no_of_posts' => 0
            ],
            [
                'entity_type_id' => EntityTypes::SHOP,
                'package_plan_id' => PackagePlan::PLATINIUM,
                'deduct_rate' => 500,
                'amount' => 800000,
                'km' => 4.5,
                'no_of_posts' => 0
            ],          
        ];

        foreach ($items as $item) {
            $plans = CreditPlans::firstOrCreate([
                'entity_type_id' => $item['entity_type_id'],
                'package_plan_id' => $item['package_plan_id'],
                'deduct_rate' => $item['deduct_rate'],
                'amount' => $item['amount'],
                'km' => $item['km'],
                'no_of_posts' => $item['no_of_posts'],
            ]);       
        }
    }
}
