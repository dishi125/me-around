<?php

namespace App\Validators;

use Illuminate\Validation\Rule;
use App\Models\ShopPost;
use App\Models\EntityTypes;
use App\Models\Status;

class ShopProfileValidator extends ModelValidator
{
    protected $languageArray = 'validation.shop-profile';

    private $updateRules = [
        'main_name' => 'required',
        //'business_license_number' => 'required',
        'mobile' => 'required|numeric',
    ];

    private $storeUpdateRules = [
        'category_id' => 'required',
        'shop_name' => 'required',
        'email' => 'required',
    ];

    private $portfolioRules = [
        'shop_id' => 'required',
        'portfolio_images' => 'required',
    ];
    private $postRules = [
        'shop_id' => 'required',
        'post_item' => 'required',
    ];
    private $multiPostRules = [
        'shop_id' => 'required',
       // 'post_item' => 'required',
    ];
    private $instagramSharePostRules = [
        'shop_id' => 'required|exists:shops,id',
        'shop_image_id' => 'required|exists:shop_images,id',
    ];
    private $followRules = [
        'shop_id' => 'required|exists:shops,id',
    ];

    private $getShops = [
        'latitude' => 'required',
        'longitude' => 'required',
    ];

    private $searchShops = [
        'latitude' => 'required',
        'longitude' => 'required',
        'keyword' => 'required',
    ];


    public function validateUpdate($inputs)
    {
        return parent::validateLaravelRules($inputs, $this->updateRules);
    }

    public function validateStore($inputs)
    {
        return parent::validateLaravelRules($inputs, $this->storeUpdateRules);
    }

    public function validateGetShop($inputs)
    {
        return parent::validateLaravelRules($inputs, $this->getShops);
    }

    public function validateSearchShop($inputs)
    {
        return parent::validateLaravelRules($inputs, $this->searchShops);
    }

    public function validatePortfolio($inputs)
    {
        return parent::validateLaravelRules($inputs, $this->portfolioRules);
    }
    public function validateInstagramSharePost($inputs)
    {
        return parent::validateLaravelRules($inputs, $this->instagramSharePostRules);
    }
    public function validateMultiPost($inputs){
        return parent::validateLaravelRules($inputs, $this->multiPostRules);
    }
    public function validatePost($inputs)
    {
        $this->postRules['type'] = ['required', Rule::in([ShopPost::IMAGE, ShopPost::VIDEO])];
        if (isset($inputs['type']) && $inputs['type'] == ShopPost::VIDEO) {
            // $this->postRules['post_item'] = ['required','mimetypes:video/*','video_length:15'];
            $this->postRules['post_item'] = ['required','mimetypes:video/*'];
            $this->postRules['video_thumbnail'] = ['required','image'];
        }

        if (isset($inputs['type']) && $inputs['type'] == ShopPost::IMAGE) {
            $this->postRules['post_item'] = ['required', 'image', 'mimes:jpeg,jpg,gif,svg'];
        }
        return parent::validateLaravelRules($inputs, $this->postRules);
    }
    public function validateShopFollow($inputs)
    {
        $this->followRules['follow'] = ['required', Rule::in([1,0])];

        return parent::validateLaravelRules($inputs, $this->followRules);
    }

    public function validateShopStatus($inputs)
    {
        return parent::validateLaravelRules($inputs, $this->followRules);
    }

    public function validateShopStatusChange($inputs)
    {
        $this->followRules['status_id'] = ['required', Rule::in([Status::ACTIVE,Status::INACTIVE,Status::HIDDEN,Status::UNHIDE])];
        return parent::validateLaravelRules($inputs, $this->followRules);
    }

}
