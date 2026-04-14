<?php

namespace Sunnysideup\Ecommerce\Forms\Validation;

use SilverStripe\Forms\Validation\RequiredFieldsValidator;
use Override;

class OrderFormPaymentValidator extends RequiredFieldsValidator
{
    #[Override]
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
