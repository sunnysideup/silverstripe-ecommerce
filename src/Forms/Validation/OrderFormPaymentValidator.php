<?php

namespace Sunnysideup\Ecommerce\Forms\Validation;

use RequiredFields;



class OrderFormPaymentValidator extends RequiredFields
{
    public function php($data)
    {
        $this->form->saveDataToSession();

        return parent::php($data);
    }
}

