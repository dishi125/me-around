<?php

namespace App\Validators;

use Illuminate\Validation\Rule;
use App\Models\Association;
use App\Models\AssociationUsers;

class AssociationValidator extends ModelValidator
{

    private $joinAssociation = [
        'association_id' => 'required',
    ];  

    private $removeAssociation = [
        'association_id' => 'required',
        
    ];  

    private $getAssociation = [
        'latitude' => 'required',
        'longitude' => 'required',
    ];  
    
    private $associationCategory = [
        'association_id' => 'required',
        'name' => 'required',
        'order' => 'integer|nullable',
    ];  

    private $makeManager = [
        'association_id' => 'required',
        'user_id' => 'required',
    ];

    private $subCategory = [
        'category_id' => 'required',
        'language_id' => 'required',
    ];

    private $saveAssociation = [
        'association_name' => 'required',
        'id' => 'required',
    ]; 

    private $saveAssociationLike = [
        'type' => 'required',
        'entity_id' => 'required',
    ]; 

    private $updateCategory = [
        'category_id' => 'required',
    ];

    public function validateJoinAssociation($inputs)
    {
        return parent::validateLaravelRules($inputs, $this->joinAssociation);
    }

    public function validateRemoveAssociation($inputs)
    {
        $this->removeAssociation['remove_type'] = ['required', Rule::in([Association::SELF,Association::KICK,Association::REMOVE])];
        $this->removeAssociation['type'] = ['required', Rule::in([AssociationUsers::MEMBER,AssociationUsers::MANAGER,AssociationUsers::SUPPORTER])];
        $this->removeAssociation['user_id'] = 'exclude_if:remove_type,self|required';
        return parent::validateLaravelRules($inputs, $this->removeAssociation);
    }

    public function validateGetAssociation($inputs)
    {
        return parent::validateLaravelRules($inputs, $this->getAssociation);
    }

    public function validateAssociationCategory($inputs)
    {
        return parent::validateLaravelRules($inputs, $this->associationCategory);
    }

    public function validateMakeManager($inputs)
    {
        return parent::validateLaravelRules($inputs, $this->makeManager);
    }

    public function validateSubCategory($inputs)
    {
        $this->subCategory['type'] = ['required', Rule::in(['category','associations'])];
        return parent::validateLaravelRules($inputs, $this->subCategory);
    }

    public function validateSaveAssociation($inputs)
    {
        return parent::validateLaravelRules($inputs, $this->saveAssociation);
    }

    public function validateSaveAssociationLike($inputs)
    {
        return parent::validateLaravelRules($inputs, $this->saveAssociationLike);
    }

    public function validateUpdateStatusCategory($inputs)
    {
        return parent::validateLaravelRules($inputs, $this->updateCategory);
    }
}
