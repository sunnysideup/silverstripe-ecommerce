<?php

namespace Sunnysideup\Ecommerce\Forms\Fields;

use Override;
use SilverStripe\Core\Validation\ValidationResult;
use SilverStripe\Forms\TextField;
use SilverStripe\View\Requirements;

/**
 * Allows input of credit card numbers via four separate form fields,
 * including generic validation of its numeric values.
 *
 * @todo Validate
 */
class EcommerceCreditCardField extends TextField
{
    /**
     * Add default attributes for use on all inputs.
     *
     * @return array List of attributes
     */
    #[Override]
    public function getAttributes()
    {
        return array_merge(
            parent::getAttributes(),
            [
                'autocomplete' => 'off',
                'maxlength' => 4,
                'size' => 4,
            ]
        );
    }

    /**
     * renders with EcommerceCreditCardField.ss.
     *
     * @param mixed $properties
     */
    #[Override]
    public function Field($properties = [])
    {
        Requirements::javascript('sunnysideup/ecommerce: client/javascript/EcomCreditCardValidation.js');
        $parts = $this->value;
        if (! is_array($parts)) {
            $parts = explode("\n", chunk_split((string) $parts, 4, "\n"));
        }

        $parts = array_pad($parts, 4, '');
        $properties['ValueOne'] = $parts[0];
        $properties['ValueTwo'] = $parts[1];
        $properties['ValueThree'] = $parts[2];
        $properties['ValueFour'] = $parts[3];

        return parent::Field($properties);
    }

    /**
     * Get tabindex HTML string.
     *
     * @param int $increment Increase current tabindex by this value
     *
     * @return bool|string
     */
    public function getTabIndexHTML($increment = 0)
    {
        // we can't add a tabindex if there hasn't been one set yet.
        if (null === $this->getAttribute('tabindex')) {
            return false;
        }

        $tabIndex = (int) $this->getAttribute('tabindex') + (int) $increment;

        return is_numeric($tabIndex) ? ' tabindex = "' . $tabIndex . '"' : '';
    }

    #[Override]
    public function dataValue()
    {
        if (is_array($this->value)) {
            return implode('', $this->value);
        }

        return $this->value;
    }

    /**
     * checks if a credit card is a real credit card number.
     *
     * @reference: http://en.wikipedia.org/wiki/Luhn_algorithm
     *
     */
    #[Override]
    public function validate(): ValidationResult
    {
        $this->beforeExtending('updateValidate', function (ValidationResult $result): void {
            // If empty and not required → valid
            $cardNumber = trim(implode('', (array) $this->value));

            if ($cardNumber === '' && ! $this->Required()) {
                return;
            }

            $sum = 0;
            $length = strlen($cardNumber);

            for ($i = $length - 1; $i >= 0; --$i) {
                $digit = (int) $cardNumber[$i];

                if ((($length - $i) % 2) === 0) {
                    $digit *= 2;

                    if ($digit > 9) {
                        $digit -= 9;
                    }
                }

                $sum += $digit;
            }

            if (($sum % 10) !== 0) {
                $result->addFieldError(
                    $this->getName(),
                    _t(
                        'Form.VALID_CREDIT_CARD_NUMBER',
                        'Please ensure you have entered a valid card number.'
                    )
                );
            }
        });

        return parent::validate();
    }
}
