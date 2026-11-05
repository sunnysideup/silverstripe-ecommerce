<?php

namespace Sunnysideup\Ecommerce\Forms\Validation;

use SilverStripe\Forms\RequiredFields;

class OrderModifierFormValidator extends RequiredFields
{
    use ExtendedValidationTrait;

    public function php($data)
    {
        $this->form->saveDataToSession();
        $extensionsAreFine = $this->extensionsAcceptTheData($data, 'We could not process your order details.');
        if (! $extensionsAreFine) {
            $this->form->sessionError(
                _t('OrderForm.OrderModifierForm', 'We could not process your order details.'),
                'error'
            );
        }

        $valid = parent::php($data);

        return $extensionsAreFine && $valid;
    }
}
