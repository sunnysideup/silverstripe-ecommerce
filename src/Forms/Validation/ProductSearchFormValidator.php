<?php

namespace Sunnysideup\Ecommerce\Forms\Validation;

use SilverStripe\Forms\RequiredFields;

class ProductSearchFormValidator extends RequiredFields
{
    use ExtendedValidationTrait;

    public function php($data)
    {
        $this->form->saveDataToSession();
        $extensionsAreFine = $this->extensionsAcceptTheData($data, 'We could not search for products.');
        if (! $extensionsAreFine) {
            $this->form->sessionError(
                _t('OrderForm.OrderModifierForm', 'We could not search for products.'),
                'error'
            );
        }

        $valid = parent::php($data);

        return $extensionsAreFine && $valid;
    }
}
