<?php

use App\Models\User;
use App\Models\Member;
use Illuminate\Database\Seeder;
use App\Models\Config;

class ConfigTableSeeder extends Seeder
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
                'key' => 'Total able shop portfolio post',
                'value' => 50,
                'is_link' => 0,
                'sort_order' => 17,
                'is_different_lang' => 0,
                'is_show_hide' => 0
            ],
            [
                'key' => 'Portfolio limit per a day',
                'value' => '3 times',
                'is_link' => 0,
                'sort_order' => 10,
                'is_different_lang' => 0,
                'is_show_hide' => 0
            ],
            [
                'key' => 'Sponsor post limit',
                'value' => 'per 5 days',
                'is_link' => 0,
                'sort_order' => 16,
                'is_different_lang' => 0,
                'is_show_hide' => 0
            ],
            [
                'key' => 'shop recommendcode money per one recommend',
                'value' => 10000,
                'is_link' => 0,
                'sort_order' => 14,
                'is_different_lang' => 0,
                'is_show_hide' => 0
            ],
            [
                'key' => 'hospital recommendcode money per one recommend',
                'value' => 10000,
                'is_link' => 0,
                'sort_order' => 7,
                'is_different_lang' => 0,
                'is_show_hide' => 0
            ],
            [
                'key' => 'VAT rate',
                'value' => '10%',
                'is_link' => 0,
                'sort_order' => 18,
                'is_different_lang' => 0,
                'is_show_hide' => 0
            ],
            [
                'key' => 'became shop and get credit',
                'value' => 100000,
                'is_link' => 0,
                'sort_order' => 2,
                'is_different_lang' => 0,
                'is_show_hide' => 0
            ],
            [
                'key' => 'became hospital and get credit',
                'value' => 100000,
                'is_link' => 0,
                'sort_order' => 1,
                'is_different_lang' => 0,
                'is_show_hide' => 0
            ],
            [
                'key' => 'business asking e mail',
                'value' => '',
                'is_link' => 1,
                'sort_order' => 20,
                'is_different_lang' => 0,
                'is_show_hide' => 0
            ],
            [
                'key' => 'Company contact number',
                'value' => '',
                'is_link' => 0,
                'sort_order' => 4,
                'is_different_lang' => 0,
                'is_show_hide' => 0
            ],
            [
                'key' => 'push and GPS agreement',
                'value' => '',
                'is_link' => 1,
                'sort_order' => 22,
                'is_different_lang' => 1,
                'is_show_hide' => 0
            ],
            [
                'key' => 'Shop profile add price',
                'value' => 40000,
                'is_link' => 0,
                'sort_order' => 13,
                'is_different_lang' => 0,
                'is_show_hide' => 0
            ],
            [
                'key' => 'Sns Reward',
                'value' => 10000,
                'is_link' => 0,
                'sort_order' => 9,
                'is_different_lang' => 0,
                'is_show_hide' => 0
            ],
            [
                'key' => 'Sns Penalty',
                'value' => 10000,
                'is_link' => 0,
                'sort_order' => 8,
                'is_different_lang' => 0,
                'is_show_hide' => 0
            ],
            [
                'key' => 'SNS Help Video',
                'value' => 'https://www.google.com/',
                'is_link' => 1,
                'sort_order' => 23,
                'is_different_lang' => 1,
                'is_show_hide' => 0
            ],
            [
                'key' => 'Review post reported comment delete',
                'value' => 5,
                'is_link' => 0,
                'sort_order' => 12,
                'is_different_lang' => 0,
                'is_show_hide' => 0
            ],
            [
                'key' => 'Community post reported comment delete',
                'value' => 5,
                'is_link' => 0,
                'sort_order' => 3,
                'is_different_lang' => 0,
                'is_show_hide' => 0
            ],
            [
                'key' => 'Deduct again from first dedect week',
                'value' => 4,
                'is_link' => 0,
                'sort_order' => 6,
                'is_different_lang' => 0,
                'is_show_hide' => 0
            ],
            [
                'key' => 'Reverify user phone number after n days',
                'value' => 90,
                'is_link' => 0,
                'sort_order' => 11,
                'is_different_lang' => 0,
                'is_show_hide' => 0
            ],
            [
                'key' => 'user inconvinience e mail',
                'value' => '',
                'is_link' => 1,
                'sort_order' => 19,
                'is_different_lang' => 0,
                'is_show_hide' => 0
            ],
            [
                'key' => 'SNS Reward visible after n hours',
                'value' => 20,
                'is_link' => 0,
                'sort_order' => 15,
                'is_different_lang' => 0,
                'is_show_hide' => 0
            ],
            [
                'key' => 'reload coin order e mail',
                'value' => '',
                'is_link' => 1,
                'sort_order' => 24,
                'is_different_lang' => 0,
                'is_show_hide' => 0
            ],
            [
                'key' => 'Request Client-Report-SNS Reward Email',
                'value' => '',
                'is_link' => 1,
                'sort_order' => 25,
                'is_different_lang' => 0,
                'is_show_hide' => 0
            ],
            [
                'key' => 'Create Shop Profile limit',
                'value' => 5,
                'is_link' => 0,
                'sort_order' => 26,
                'is_different_lang' => 0,
                'is_show_hide' => 0
            ],
            [
                'key' => 'Price DC tip',
                'value' => 'https://www.google.com/',
                'is_link' => 1,
                'sort_order' => 27,
                'is_different_lang' => 1,
                'is_show_hide' => 0
            ],
            [
                'key' => 'SNS Get Video',
                'value' => 'https://www.google.com/',
                'is_link' => 1,
                'sort_order' => 28,
                'is_different_lang' => 0,
                'is_show_hide' => 0
            ],
            [
                'key' => 'Customer Service Center',
                'value' => 'https://www.google.com/',
                'is_link' => 1,
                'sort_order' => 29,
                'is_different_lang' => 1,
                'is_show_hide' => 0
            ],
            [
                'key' => 'Leave and re join time limit for association',
                'value' => 12,
                'is_link' => 0,
                'sort_order' => 31,
                'is_different_lang' => 0,
                'is_show_hide' => 0
            ],
            [
                'key' => 'Admin Phone Number',
                'value' => "01095219160",
                'is_link' => 0,
                'sort_order' => 32,
                'is_different_lang' => 0,
                'is_show_hide' => 0
            ],
            [
                'key' => 'Master Password',
                'value' => "",
                'is_link' => 0,
                'sort_order' => 33,
                'is_different_lang' => 0,
                'is_show_hide' => 0
            ],
            [
                'key' => 'Hide hospital Button',
                'value' => "",
                'is_link' => 0,
                'sort_order' => 34,
                'is_different_lang' => 0,
                'is_show_hide' => 0
            ],
            [
                'key' => 'suggested Category',
                'value' => "",
                'is_link' => 0,
                'sort_order' => 35,
                'is_different_lang' => 0,
                'is_show_hide' => 1
            ],
            [
                'key' => 'Show Coin Info',
                'value' => 0,
                'is_link' => 0,
                'sort_order' => 36,
                'is_different_lang' => 0,
                'is_show_hide' => 1
            ],
            [
                'key' => 'Show Fixed country',
                'value' => 'Auto',
                'is_link' => 0,
                'sort_order' => 37,
                'is_different_lang' => 0,
                'is_show_hide' => 1
            ],
            [
                'key' => 'Give referral EXP',
                'value' => 700,
                'is_link' => 0,
                'sort_order' => 38,
                'is_different_lang' => 0,
                'is_show_hide' => 0
            ],
            [
                'key' => 'Show Review Tab',
                'value' => 0,
                'is_link' => 0,
                'sort_order' => 39,
                'is_different_lang' => 0,
                'is_show_hide' => 1
            ],
            [
                'key' => 'Switch Character only mode',
                'value' => 0,
                'is_link' => 0,
                'sort_order' => 33,
                'is_different_lang' => 0,
                'is_show_hide' => 1
            ],
            [
                'key' => 'Show Full screen ads',
                'value' => 1,
                'is_link' => 0,
                'sort_order' => 31,
                'is_different_lang' => 0,
                'is_show_hide' => 1
            ],
            [
                'key' => 'Show Banner Ads',
                'value' => 1,
                'is_link' => 0,
                'sort_order' => 32,
                'is_different_lang' => 0,
                'is_show_hide' => 1
            ],
            [
                'key' => 'Only shop mode',
                'value' => 0,
                'is_link' => 0,
                'sort_order' => 33,
                'is_different_lang' => 0,
                'is_show_hide' => 1
            ],
            [
                'key' => 'Purchase Order email',
                'value' => '',
                'is_link' => 1,
                'sort_order' => 18,
                'is_different_lang' => 0,
                'is_show_hide' => 0
            ],
            [
                'key' => 'insta_cron_time',
                'value' => '',
                'is_link' => 0,
                'sort_order' => 18,
                'is_different_lang' => 0,
                'is_show_hide' => 0
            ],
            [
                'key' => 'product_name',
                'value' => "휴대폰",
                'is_link' => 0,
                'sort_order' => 33,
                'is_different_lang' => 0,
                'is_show_hide' => 0
            ],
            [
                'key' => 'product_price',
                'value' => "1000",
                'is_link' => 0,
                'sort_order' => 34,
                'is_different_lang' => 0,
                'is_show_hide' => 0
            ],
            [
                'key' => 'product_payment_method',
                'value' => "01|PAY",
                'is_link' => 0,
                'sort_order' => 34,
                'is_different_lang' => 0,
                'is_show_hide' => 0
            ],
            [
                'key' => 'instagram_register_push_email',
                'value' => 'gwb9160@nate.com',
                'is_link' => 1,
                'sort_order' => 19,
                'is_different_lang' => 0,
                'is_show_hide' => 0
            ],
            [
                'key' => 'signup_email',
                'value' => 'gwb9160@nate.com',
                'is_link' => 1,
                'sort_order' => 20,
                'is_different_lang' => 0,
                'is_show_hide' => 0
            ],
            [
                'key' => 'Show Recent completed shops',
                'value' => 1,
                'is_link' => 0,
                'sort_order' => 40,
                'is_different_lang' => 0,
                'is_show_hide' => 1
            ],
            [
                'key' => 'like order',
                'value' => '',
                'is_link' => 1,
                'sort_order' => 41,
                'is_different_lang' => 0,
                'is_show_hide' => 0
            ],
            [
                'key' => 'delete account reason',
                'value' => '',
                'is_link' => 1,
                'sort_order' => 42,
                'is_different_lang' => 0,
                'is_show_hide' => 0
            ],
            [
                'key' => 'Sign up mark [completely fee]',
                'value' => 1,
                'is_link' => 0,
                'sort_order' => 43,
                'is_different_lang' => 0,
                'is_show_hide' => 1
            ],
            [
                'key' => 'Hide starbucks',
                'value' => 1,
                'is_link' => 0,
                'sort_order' => 44,
                'is_different_lang' => 0,
                'is_show_hide' => 1
            ],
            [
                'key' => 'admin_chat',
                'value' => 'gwb9160@nate.com',
                'is_link' => 1,
                'sort_order' => 45,
                'is_different_lang' => 0,
                'is_show_hide' => 0
            ],
            [
                'key' => 'Instagram additional service suggestion',
                'value' => 1,
                'is_link' => 0,
                'sort_order' => 46,
                'is_different_lang' => 0,
                'is_show_hide' => 1
            ],
        ];

        foreach ($items as $item) {
            $key = Str::slug($item['key'], '_');
            $planCount = Config::where('key', $key)->count();
            if ($planCount == 0) {
                $plans = Config::firstOrCreate([
                    'key' => $key,
                    'value' => $item['value'],
                    'is_link' => $item['is_link'],
                    'sort_order' => $item['sort_order'],
                    'is_different_lang' => $item['is_different_lang'],
                    'is_show_hide' => $item['is_show_hide'] ?? 0,
                ]);
            }
        }
    }
}
