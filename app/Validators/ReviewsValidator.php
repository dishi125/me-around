<?php

namespace App\Validators;

use Illuminate\Validation\Rule;

class ReviewsValidator extends ModelValidator
{
    protected $languageArray = 'validation.reviews';

    private $shopRules = [
        // 'before_images' => 'required',
        'after_images' => 'required',
        'rating' => 'required|integer',
        'review_comment' => 'required',
        'shop_id' =>'required|exists:shops,id',
        'booking_id' =>'required|exists:requested_customer,id'
    ];    
    private $hospitalRules = [
        // 'before_images' => 'required',
        'after_images' => 'required',
        'rating' => 'required|integer',
        'review_comment' => 'required',
        'hospital_post_id' =>'required|exists:posts,id',
        'category_id' =>'exists:category,id',
        'doctor_id' =>'exists:doctors,id',
        'booking_id' =>'required|exists:requested_customer,id'
    ];  
    
    private $getRules = [
        'category_id' => 'exists:category,id',
        'latitude' => 'required',
        'longitude' => 'required',
    ];

    private $commentRules = [
        'comment' => 'required',
    ];

    private $commentReplyRules = [
        'comment' => 'required',
        'reply_parent_id' => 'exists:review_comment_reply,id'
    ];

    public function validateShopReview($inputs)
    {
        return parent::validateLaravelRules($inputs, $this->shopRules);
    }

    public function validateHospitalReview($inputs)
    {
        return parent::validateLaravelRules($inputs, $this->hospitalRules);
    }

    public function validateGetReview($inputs)
    {
        return parent::validateLaravelRules($inputs, $this->getRules);
    }

    public function validateReviewComment($inputs)
    {
        return parent::validateLaravelRules($inputs, $this->commentRules);
    }

    public function validateReviewCommentReply($inputs)
    {
        return parent::validateLaravelRules($inputs, $this->commentReplyRules);
    }
    
}
