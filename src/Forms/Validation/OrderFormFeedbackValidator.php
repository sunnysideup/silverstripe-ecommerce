<?php

namespace Sunnysideup\Ecommerce\Forms\Validation;

use SilverStripe\Forms\RequiredFields;

class OrderFormFeedbackValidator extends RequiredFields
{
    use ExtendedValidationTrait;

    public function php($data)
    {
        $this->form->saveDataToSession();
        $extensionsAreFine = $this->extensionsAcceptTheData($data, 'We could not process your feedback.');
        if (! $extensionsAreFine) {
            $this->form->sessionError(
                _t('OrderForm.OrderFormFeedback', 'We could not process your feedback.'),
                'error'
            );
        }

        $valid = parent::php($data);

        return $extensionsAreFine && $valid;
    }
}
