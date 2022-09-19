<?php

namespace Sunnysideup\Ecommerce\Forms\Validation;

use SilverStripe\Forms\RequiredFields;

class OrderFormPaymentValidator extends RequiredFields
{
    public function php($data)
    {
        $this->form->saveDataToSession();
        $validExtended = $this->extend('updatePHP', $data, $this);
        if($validExtended === false) {
            $this->form->sessionError(
                _t('OrderForm.OrderFormPayment', 'We could not process your payment.'),
                'error'
            );
        }
        return parent::php($data);
    }
}
