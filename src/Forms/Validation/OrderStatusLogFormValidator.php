<?php

namespace Sunnysideup\Ecommerce\Forms\Validation;

use SilverStripe\Forms\Validation\RequiredFieldsValidator;
use Override;

class OrderStatusLogFormValidator extends RequiredFieldsValidator
{
    use ExtendedValidationTrait;

    #[Override]
    public function php($data)
    {
        $this->form->saveDataToSession();
        $extensionsAreFine = $this->extensionsAcceptTheData($data, 'We could not update your order status.');
        if (! $extensionsAreFine) {
            $this->form->sessionError(
                _t('OrderForm.OrderModifierForm', 'We could not update your order status.'),
                'error'
            );
        }

        $valid = parent::php($data);

        return $extensionsAreFine && $valid;
    }
}
