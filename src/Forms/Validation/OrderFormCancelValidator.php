<?php

namespace Sunnysideup\Ecommerce\Forms\Validation;

use SilverStripe\Forms\RequiredFields;

class OrderFormCancelValidator extends RequiredFields
{
    use ExtendedValidationTrait;

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
