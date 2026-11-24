<?php

namespace Sunnysideup\Ecommerce\Forms\Validation;

use SilverStripe\Forms\Validation\RequiredFieldsValidator;
use Override;

class OrderModifierFormValidator extends RequiredFieldsValidator
{
    use ExtendedValidationTrait;

    #[Override]
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
