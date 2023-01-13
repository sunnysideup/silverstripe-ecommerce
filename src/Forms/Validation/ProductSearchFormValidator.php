<?php

namespace Sunnysideup\Ecommerce\Forms\Validation;

use SilverStripe\Forms\RequiredFields;

class ProductSearchFormValidator extends RequiredFields
{
    public function php($data)
    {
        $this->form->saveDataToSession();
        $validExtended = $this->extend('updatePHP', $data, $this);
        if (false === $validExtended) {
            $this->form->sessionError(
                _t('OrderForm.OrderModifierForm', 'We could not search for products.'),
                'error'
            );
        }

        return parent::php($data);
    }
}
