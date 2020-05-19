<?php
/**
 * Allows input of credit card numbers via four separate form fields,
 * including generic validation of its numeric values.
 *
 * @todo Validate
 */
class EcommerceCreditCardField extends DBTextField
{
    /**
     * Add default attributes for use on all inputs.
     *
     * @return array List of attributes
     */
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
     */
    public function Field($properties = [])
    {
        Requirements::javascript('sunnysideup/ecommerce: ecommerce/javascript/EcomCreditCardValidation.js');
        $parts = $this->value;
        if (! is_array($parts)) {
            $parts = explode("\n", chunk_split($parts, 4, "\n"));
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
     * @return string
     */
    public function getTabIndexHTML($increment = 0)
    {
        // we can't add a tabindex if there hasn't been one set yet.
        if ($this->getAttribute('tabindex') === null) {
            return false;
        }

        $tabIndex = (int) $this->getAttribute('tabindex') + (int) $increment;

        return is_numeric($tabIndex) ? ' tabindex = "' . $tabIndex . '"' : '';
    }

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
     */
    public function validate($validator)
    {
        // If the field is empty then don't return an invalidation message
        $cardNumber = trim(implode('', $this->value));
        if (! $cardNumber && ! $this->Required()) {
            return true;
        }
        for ($sum = 0, $i = strlen($cardNumber) - 1; $i >= 0; --$i) {
            $digit = (int) $cardNumber[$i];
            $sum += ($i % 2) === 0 ? array_sum(str_split($digit * 2)) : $digit;
        }
        if (! (($sum % 10) === 0)) {
            $validator->validationError(
                $this->name,
                _t(
                    'Form.VALID_CREDIT_CARD_NUMBER',
                    'Please ensure you have entered a valid card number.'
                ),
                'bad'
            );

            return false;
        }
    }
}

