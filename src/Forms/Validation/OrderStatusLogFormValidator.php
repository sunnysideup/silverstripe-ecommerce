<?php

namespace Sunnysideup\Ecommerce\Forms\Validation;

use RequiredFields;



class OrderStatusLogFormValidator extends RequiredFields
{
    public function php($data)
    {
        $this->form->saveDataToSession();

        return parent::php($data);
    }
}

