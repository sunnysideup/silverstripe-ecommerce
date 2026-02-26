<?php

namespace Sunnysideup\Ecommerce\Forms\Validation;

use SilverStripe\Forms\Validation\RequiredFieldsValidator;

class OrderFormCancelValidator extends RequiredFieldsValidator
{
    public function php($data)
    {
        $this->form->saveDataToSession();
        $validExtended = $this->extend('updatePHP', $data, $this);
        if (false === $validExtended) {
            $this->form->sessionError(
                _t('OrderForm.OrderFormCancel', 'We could not cancel the order.'),
                'error'
            );
        }

        return parent::php($data);
    }
}
