<?php

use Illuminate\Database\Seeder;
use App\Models\CategoryTypes;
use App\Models\Category;
use App\Models\Status;

class CategoryTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $categories = [
            'Shop' => [
                'Eye Brows','Hair designer','Tattoo','PT','Nail', 'Waxing','Makeup','Eyebrow Extension','Hair tattoo','Hair extension', 'Skin Shop', 'Dog Beauty', 'Fitness club', 'Yoga / pilates','Massage','Hair shop',
            ],
            'Community' => [
                'Talk' => [
                    'For Woman', 'For Man', 'Fashion', 'Beauty', 'Teenager', 'Twenties', 'Thirties', 'Beauty Job', 'Job', 'Diet',
                ],
                'Sergery'=> [
                    'For Woman', 'For Man', 'Fashion', 'Beauty', 'Teenager', 'Twenties', 'Thirties', 'Beauty Job', 'Job', 'Diet',
                ], 
                'Anonymity'=> [
                    'For Woman', 'For Man', 'Fashion', 'Beauty', 'Teenager', 'Twenties', 'Thirties', 'Beauty Job', 'Job', 'Diet',
                ],
            ],
            'Hospital' => [
                'Treatment' => [
                    'Eyes', 'Nose', 'Breast', 'Liposuction', 'Facial Contour', 'Transplantation of Fat', 'Hair Line', 'Tooth', 'Botox Piller', 'Skin',
                ],
                'Sergery' => [
                    'Eyes', 'Nose', 'Breast', 'Liposuction', 'Facial Contour', 'Transplantation of Fat', 'Hair Line', 'Tooth', 'Botox Piller', 'Skin',
                ],
            ],
            'Report' => [
                'Shop' => [
                    'Price expose on photo', 'Portfolio Unauthorized theft', 'Image Unauthorized theft', 'Exposed phone number on photo', 'Exposed address on photo', 'Low quality portfolio','Bad cancel booking',
                ],
                'Hospital' => [
                    'False advertising', 'Portfolio Unauthorized theft', 'Image Unauthorized theft', 'Exposed phone number on photo', 'Exposed address on photo', 'Low quality portfolio','Bad cancel booking',
                ],
                'User from Shop' => [
                    'Portfolio Unauthorized theft', 'Image Unauthorized theft','Swear word'
                ],
                'Review' => [
                    'Portfolio Unauthorized theft', 'Image Unauthorized theft','Swear word'
                ],
                'Community' => [
                    'Portfolio Unauthorized theft', 'Image Unauthorized theft','Swear word'
                ],
            ],
            'Custom' => [
                'Interior','Academy', 'Device center'
            ]
        ];

        foreach ($categories as $key => $category) {
            $type = CategoryTypes::where('name', $key)->first();
            foreach ($category as $keyName => $items) {
                if (is_string($keyName)) {
                    $name = Category::firstOrCreate(['name' => $keyName, 'category_type_id' => $type->id,'status_id' => Status::ACTIVE]);
                }
                if (is_array($items)) {
                    foreach ($items as $item) {
                        if ($type->id == CategoryTypes::SHOP) {
                            $slug = Str::slug($item,'_');
                            Category::firstOrCreate(['name' => $item, 'category_type_id' => $type->id, 'logo' => 'uploads/category/' . $slug . '.png', 'parent_id' => $name->id,'status_id' => Status::ACTIVE]);
                        } elseif( $type->id == CategoryTypes::CUSTOM) {
                           Category::firstOrCreate(['name' => $item, 'category_type_id' => $type->id, 'status_id' => Status::ACTIVE]);
                        } else {
                            Category::firstOrCreate(['name' => $item, 'category_type_id' => $type->id, 'parent_id' => $name->id,'status_id' => Status::ACTIVE]);
                         }
                    }
                } else {
                    if ($type->id == CategoryTypes::SHOP) {
                        $slug = Str::slug($items,'_');
                        Category::firstOrCreate(['name' => $items, 'category_type_id' => $type->id ,'logo' => 'uploads/category/' . $slug . '.png','status_id' => Status::ACTIVE]);
                    } else if($type->id == CategoryTypes::CUSTOM){
                        Category::firstOrCreate(['name' => $items,'type' => 'custom','category_type_id' => $type->id ,'status_id' => Status::ACTIVE]);
                    } else {
                        Category::firstOrCreate(['name' => $items, 'category_type_id' => $type->id ,'status_id' => Status::ACTIVE]);
                    }
                }
            }
        }
    }
}
