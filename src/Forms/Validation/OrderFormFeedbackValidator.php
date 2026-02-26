<?php

namespace Sunnysideup\Ecommerce\Forms\Validation;

use SilverStripe\Forms\Validation\RequiredFieldsValidator;

class OrderFormFeedbackValidator extends RequiredFieldsValidator
{
    public function php($data)
    {
        $this->form->saveDataToSession();
        $validExtended = $this->extend('updatePHP', $data, $this);
        if (false === $validExtended) {
            $this->form->sessionError(
                _t('OrderForm.OrderFormFeedback', 'We could not process your feedback.'),
                'error'
            );
        }

        return parent::php($data);
    }
}
