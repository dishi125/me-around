<?php

use Illuminate\Database\Seeder;
use App\Models\BasicMentions;

class BasicMentionTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $items = [
            'requested_client_reject',
            'reported_shop_warning_comment',
            'reported_hospital_warning_comment',
            'reported_community_warning_comment',
            'reported_review_warning_comment',
            'reported_shop_user_warning_comment',
            'reward_instagram_reject',
            'reward_instagram_penalty',
        ];

        foreach($items as $item) {
            BasicMentions::firstOrCreate(['name' => $item,'value' => ""]);
        }
    }
}
