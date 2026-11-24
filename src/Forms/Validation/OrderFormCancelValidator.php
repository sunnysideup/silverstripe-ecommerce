<?php

namespace Sunnysideup\Ecommerce\Forms\Validation;

use SilverStripe\Forms\Validation\RequiredFieldsValidator;
use Override;

class OrderFormCancelValidator extends RequiredFieldsValidator
{
    use ExtendedValidationTrait;

    #[Override]
    public function php($data)
    {
        $this->form->saveDataToSession();
        $extensionsAreFine = $this->extensionsAcceptTheData($data, 'We could not cancel the order.');
        if (! $extensionsAreFine) {
            $this->form->sessionError(
                _t('OrderForm.OrderFormCancel', 'We could not cancel the order.'),
                'error'
            );
        }

        $valid = parent::php($data);

        return $extensionsAreFine && $valid;
    }
}
