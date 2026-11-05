<?php

namespace Sunnysideup\Ecommerce\Forms\Validation;

/**
 * For validators (SilverStripe\Forms\Validator) that run an "updatePHP" extension hook.
 *
 * How this works in SilverStripe 5:
 *
 *  - Extensible::extend() ALWAYS returns an ARRAY with the (non-null) answer of each extension.
 *    It never returns false, so `false === $this->extend(...)` is never true.
 *  - Validator::validate() IGNORES what php() returns. A form is only invalid if an error was
 *    registered with validationError() (which marks the ValidationResult as invalid).
 *
 * So: extensions should register their errors with $validator->validationError(), and an extension that
 * returns false is a failure too (we make sure the form really is invalid, so it can not be missed).
 */
trait ExtendedValidationTrait
{
    /**
     * Runs the "updatePHP" extension hook.
     *
     * @param array  $data    the submitted data
     * @param string $message shown (as a form-wide error) if an extension says no without registering an error
     *
     * @return bool true if no extension has a problem with the data
     */
    protected function extensionsAcceptTheData($data, string $message): bool
    {
        $answers = $this->extend('updatePHP', $data, $this);
        if (is_array($answers) && in_array(false, $answers, true)) {
            if ($this->result->isValid()) {
                // an empty field name is a form-wide message.
                $this->validationError('', $message, 'required');
            }

            return false;
        }

        return true;
    }
}
