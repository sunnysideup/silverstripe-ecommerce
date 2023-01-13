<?php

namespace Sunnysideup\Ecommerce\Forms\Validation;

use SilverStripe\Forms\RequiredFields;

class OrderStatusLogFormValidator extends RequiredFields
{
    public function php($data)
    {
        $this->form->saveDataToSession();
        $validExtended = $this->extend('updatePHP', $data, $this);
        if (false === $validExtended) {
            $this->form->sessionError(
                _t('OrderForm.OrderModifierForm', 'We could not update your order status.'),
                'error'
            );
        }

        return parent::php($data);
    }
}
