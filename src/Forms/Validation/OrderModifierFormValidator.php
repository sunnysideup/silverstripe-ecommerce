<?php

namespace Sunnysideup\Ecommerce\Forms\Validation;

use SilverStripe\Forms\RequiredFields;

class OrderModifierFormValidator extends RequiredFields
{
    public function php($data)
    {
        $this->form->saveDataToSession();
        $validExtended = $this->extend('updatePHP', $data, $this);
        if (false === $validExtended) {
            $this->form->sessionError(
                _t('OrderForm.OrderModifierForm', 'We could not process your order details.'),
                'error'
            );
        }

        return parent::php($data);
    }
}
