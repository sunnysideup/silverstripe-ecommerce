<?php

namespace Sunnysideup\Ecommerce\Forms\Validation;


use SilverStripe\Forms\RequiredFields;




class OrderFormFeedbackValidator extends RequiredFields
{
    public function php($data)
    {
        $this->form->saveDataToSession();

        return parent::php($data);
    }
}

