<?php

namespace Sunnysideup\Ecommerce\Forms\Validation;

use SilverStripe\Forms\Validation\RequiredFieldsValidator;

class OrderFormPaymentValidator extends RequiredFieldsValidator
{
    public function php($data)
    {
        $this->form->saveDataToSession();
        $validExtended = $this->extend('updatePHP', $data, $this);
        if (false === $validExtended) {
            $this->form->sessionError(
                _t('OrderForm.OrderFormPayment', 'We could not process your payment.'),
                'error'
            );
        }

        return parent::php($data);
    }
}
