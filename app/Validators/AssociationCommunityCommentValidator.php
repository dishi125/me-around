<?php

namespace App\Validators;

use Illuminate\Validation\Rule;

class AssociationCommunityCommentValidator extends ModelValidator
{
    protected $languageArray = 'validation.comments';
    
    private $commentRules = [
        'comment' => 'required',
        'community_id' => 'required'
    ];  

    private $commentEditRules = [
        'comment' => 'required',
    ];  
    
    public function validateCommunityComment($inputs)
    {
        return parent::validateLaravelRules($inputs, $this->commentRules);
    }

    public function validateCommunityCommentEdit($inputs)
    {
        return parent::validateLaravelRules($inputs, $this->commentEditRules);
    }

}
