<?php

use App\Models\PostLanguage;
use Illuminate\Database\Seeder;

class PostLanguageTableSeeder extends Seeder
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
                'name' => '한국어',
                'icon' => "uploads/language/korean.png",
                'is_support' => 1,
            ],       
            [
                'name' => '中文',
                'icon' => "uploads/language/chinese.png",
                'is_support' => 1,
            ],       
            [
                'name' => '日本語',
                'icon' => "uploads/language/japanese.png",
                'is_support' => 1,
            ],       
            [
                'name' => 'English',
                'icon' => "uploads/language/english.png",
                'is_support' => 1,
            ],       
            [
                'name' => 'عربى',
                'icon' => "uploads/language/arabic.png",
                'is_support' => 0,
            ],       
            [
                'name' => 'Español',
                'icon' => "uploads/language/spanish.png",
                'is_support' => 0,
            ],       
            [
                'name' => 'Français',
                'icon' => "uploads/language/french.png",
                'is_support' => 0,
            ],       
            [
                'name' => 'Tiếng Việt',
                'icon' => "uploads/language/vietnamese.png",
                'is_support' => 0,
            ],       
        ];

        foreach ($items as $item) {
            $plans = PostLanguage::firstOrCreate([
                'name' => $item['name'],
                'icon' => $item['icon'],
                'is_support' => $item['is_support'],
            ]);       
        }
    }
}
