<?php

namespace Sunnysideup\Ecommerce\Forms\Validation;

use SilverStripe\Forms\Validation\RequiredFieldsValidator;
use Override;

class OrderFormPaymentValidator extends RequiredFieldsValidator
{
    use ExtendedValidationTrait;

    #[Override]
    public function php($data)
    {
        $this->form->saveDataToSession();
        $extensionsAreFine = $this->extensionsAcceptTheData($data, 'We could not process your payment.');
        if (! $extensionsAreFine) {
            $this->form->sessionError(
                _t('OrderForm.OrderFormPayment', 'We could not process your payment.'),
                'error'
            );
        }

        $valid = parent::php($data);

        return $extensionsAreFine && $valid;
    }
}
