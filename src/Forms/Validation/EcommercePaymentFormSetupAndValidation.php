<?php

namespace Sunnysideup\Ecommerce\Forms\Validation;

use SilverStripe\Control\Director;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Extensible;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\TextField;
use Sunnysideup\Ecommerce\Config\EcommerceConfigClassNames;
use Sunnysideup\Ecommerce\Forms\Fields\EcommerceCreditCardField;
use Sunnysideup\Ecommerce\Forms\Fields\ExpiryDateField;
use Sunnysideup\Ecommerce\Model\Money\EcommercePayment;
use Sunnysideup\Ecommerce\Model\Order;
use Sunnysideup\Ecommerce\Money\Payment\EcommercePaymentResult;

//get_credit_card_payment_form_fields: $formHelper->getCreditCardPaymentFormFields
//get_credit_card_payment_form_fields_required: : $formHelper->getCreditCardPaymentFormFieldsRequired
//validate_payment: $formHelper->validatePayment
//validate_and_save_credit_card_information: validateAndSaveCreditCardInformation
//process_payment_form_and_return_next_step: processPaymentFormAndReturnNextStep
//validate_card_number:  validateCardNumber
//validate_expiry_month: :validateExpiryMonth
//validate_CVV: validateCVV

class EcommercePaymentFormSetupAndValidation
{
    use Configurable;
    use Extensible;
    use Injectable;

    /**
     * @var EcommercePayment
     */
    protected $paymentObject;

    /**
     * you can set specific EcommercePayment payment fields here, like this:
     *     MyEcommercePaymentClass
     *         CardNumber: MyCardNumberDBField
     *         NameOnCard: MyNameOnCardDBField
     *         CVVNumber: MyCVVNumberDBField
     *         ExpiryDate: MyExpiryDateDBField.
     *
     * @var string
     */
    private static $table_name = 'EcommercePaymentFormSetupAndValidation';

    private static $db_field_map = [];

    /**
     * Return the payment form fields that should
     * be shown on the checkout order form for the
     * payment type. Example: for {@link DPSPayment},
     * this would be a set of fields to enter your
     * credit card details.
     *
     * @param EcommercePayment $paymentObject
     *
     * @return \SilverStripe\Forms\FieldList
     */
    public function getCreditCardPaymentFormFields($paymentObject = null)
    {
        if ($paymentObject) {
            $this->paymentObject = $paymentObject;
        }
        $paymentClassName = $this->paymentObject->ClassName;
        $fieldList = new FieldList(
            [
                $CardNumberField = new EcommerceCreditCardField(
                    $paymentClassName . '_CardNumber',
                    _t('EcommercePaymentFormSetupAndValidation.CardNumber', 'Card Number')
                ),
                $nameOnCardField = new TextField(
                    $paymentClassName . '_NameOnCard',
                    _t('EcommercePaymentFormSetupAndValidation.NAMEONCARD', 'Name on Card')
                ),
                $expiryDateField = new ExpiryDateField(
                    $paymentClassName . '_ExpiryDate',
                    _t('EcommercePaymentFormSetupAndValidation.EXPIRYDATE', 'Expiry Date')
                ),
                $cvvNumberField = new TextField(
                    $paymentClassName . '_CVVNumber',
                    _t('EcommercePaymentFormSetupAndValidation.CVVNumber', 'Security Number')
                ),
            ]
        );
        $nameOnCardField->setAttribute('maxlength', '40');
        $cvvNumberField->setAttribute('maxlength', '4');
        $cvvNumberField->setAttribute('size', '4');
        $cvvNumberField->setAttribute('autocomplete', 'off');

        return $fieldList;
    }

    /*
     *
     * @param EcommercePayment $paymentObject
     *
     * @return array
     */
    public function getCreditCardPaymentFormFieldsRequired($paymentObject = null)
    {
        if ($paymentObject) {
            $this->paymentObject = $paymentObject;
        }
        $paymentClassName = $this->paymentObject->ClassName;

        return [
            $paymentClassName . '_CardNumber',
            $paymentClassName . '_NameOnCard',
            $paymentClassName . '_ExpiryDate',
            $paymentClassName . '_CVVNumber',
        ];
    }

    /**
     * @param Order $order - the order that is being paid
     * @param array $data  - Array of data that is submittted
     * @param Form  $form  - the form that is being submitted
     *
     * @return bool - true if the data is valid
     */
    public function validatePayment($order, $data, $form)
    {
        if (! $order) {
            $form->sessionMessage(_t('EcommercePayment.NOORDER', 'Order not found.'), 'bad');

            return false;
        }

        //nothing to pay, always valid
        if ((0 === $order->TotalOutstanding() && $order->IsSubmitted()) || $order->IsPaid() || 0 === $order->Total()) {
            return true;
        }
        if (! $this->paymentObject) {
            $this->paymentObject = $this->createPaymentObjectFromData($order, $data);
        }
        if (! $this->paymentObject || ! ($this->paymentObject instanceof EcommercePayment)) {
            $form->sessionMessage(_t('EcommercePaymentFormSetupAndValidation.NOPAYMENTOPTION', 'No Payment option selected.'), 'bad');

            return false;
        }
        // Check payment, get the result back
        return $this->paymentObject->validatePayment($data, $form);
    }

    /**
     * return false if there is an error and
     * returns true if all is well.
     * If there are no errors, then the payment object will also be written...
     * If there are errors, the errors will be added to the form.
     *
     * @param Form             $form
     * @param array            $data
     * @param EcommercePayment $paymentObject
     *
     * @return bool
     */
    public function validateAndSaveCreditCardInformation($data, $form, $paymentObject = null)
    {
        $errors = false;
        if ($paymentObject) {
            $this->paymentObject = $paymentObject;
        }
        $paymentClassName = $this->paymentObject->ClassName;
        $dbFieldMap = Config::inst()->get(EcommercePaymentFormSetupAndValidation::class, 'db_field_map');
        $cardNumberFormFields = [
            'CardNumber',
            'ExpiryDate',
            'CVVNumber',
            'NameOnCard',
        ];
        foreach ($cardNumberFormFields as $dbFieldName) {
            $formFieldName = $paymentClassName . '_' . $dbFieldName;
            //check if there is a credit card at all:
            if (! isset($data[$formFieldName])) {
                return true;
            }
            switch ($dbFieldName) {
                case 'CardNumber':
                    if (isset($dbFieldMap[$paymentClassName]['CardNumber'])) {
                        $dbFieldName = $dbFieldMap[$paymentClassName]['CardNumber'];
                    }
                    $cardNumberParts = is_array($data[$formFieldName]) ? array_slice(array_values($data[$formFieldName]), 0, 4) : [];
                    $cardNumberParts = array_filter($cardNumberParts, 'is_scalar');
                    $this->paymentObject->{$dbFieldName} = trim(implode('', $cardNumberParts));
                    $cardNumber = $this->paymentObject->{$dbFieldName};
                    if (! $this->validateCardNumber($this->paymentObject->{$dbFieldName})) {
                        $form->sessionError(
                            $formFieldName,
                            _t('EcommercePaymentFormSetupAndValidation.INVALID_CREDIT_CARD', 'Invalid credit card number.'),
                            'bad'
                        );
                        $errors = true;
                    }

                    break;
                case 'ExpiryDate':
                    $expiryMonth = is_array($data[$formFieldName]) && isset($data[$formFieldName]['month']) && is_scalar($data[$formFieldName]['month']) ? $data[$formFieldName]['month'] : '';
                    $expiryYear = is_array($data[$formFieldName]) && isset($data[$formFieldName]['year']) && is_scalar($data[$formFieldName]['year']) ? $data[$formFieldName]['year'] : '';
                    $this->paymentObject->{$dbFieldName} = $expiryMonth . $expiryYear;
                    if (! $this->validateExpiryMonth($this->paymentObject->{$dbFieldName})) {
                        $form->sessionError(
                            $formFieldName,
                            _t('EcommercePaymentFormSetupAndValidation.INVALID_EXPIRY_DATE', 'Expiry date not valid.'),
                            'bad'
                        );
                        $errors = true;
                    }

                    break;
                case 'CVVNumber':
                    // $cardNumber has been set in the CardNumber step (above) and is used to find out how long the CVV should be.
                    $cardNumber ??= '';
                    $this->paymentObject->{$dbFieldName} = is_scalar($data[$formFieldName]) ? trim((string) $data[$formFieldName]) : '';
                    if (! $this->validateCVV($cardNumber, $this->paymentObject->{$dbFieldName})) {
                        $form->sessionError(
                            $formFieldName,
                            _t('EcommercePaymentFormSetupAndValidation.INVALID_CVV_NUMBER', 'Invalid security number.'),
                            'bad'
                        );
                        $errors = true;
                    }

                    break;
                case 'NameOnCard':
                    $this->paymentObject->{$dbFieldName} = is_scalar($data[$formFieldName]) ? trim((string) $data[$formFieldName]) : '';
                    if (strlen((string) $this->paymentObject->{$dbFieldName}) < 3) {
                        $form->sessionError(
                            $formFieldName,
                            _t('EcommercePaymentFormSetupAndValidation.NO_CARD_NAME', 'No card name provided.'),
                            'bad'
                        );
                        $errors = true;
                    }

                    break;
                default:
                    user_error('Type must be one of four options: CardNumber, NameOnCard, CVV, ExpiryDate');
            }
        }
        if ($errors) {
            $form->sessionMessage(_t('EcommercePaymentFormSetupAndValidation.PLEASE_REVIEW_CARD_DETAILS', 'Please review your card details.'), 'bad');

            return false;
        }
        // the card details stay in memory (for the payment gateway), they are never saved to the database.
        $this->writePaymentWithoutCardData();

        return true;
    }

    /**
     * The names of the fields (of the payment object) that hold the card number and the card security number.
     *
     * @return string[] - [cardNumberField, cvvField]
     */
    protected function cardFieldNames(EcommercePayment $payment): array
    {
        $dbFieldMap = Config::inst()->get(EcommercePaymentFormSetupAndValidation::class, 'db_field_map');
        $map = $dbFieldMap[$payment->ClassName] ?? [];

        return [$map['CardNumber'] ?? 'CardNumber', $map['CVVNumber'] ?? 'CVVNumber'];
    }

    /**
     * Writes the payment object to the database WITHOUT the card number and the card security number.
     * The values are put back on the object afterwards so that they can still be used to process the payment.
     */
    protected function writePaymentWithoutCardData(): void
    {
        $payment = $this->paymentObject;
        [$cardNumberField, $cvvField] = $this->cardFieldNames($payment);
        $kept = [];
        foreach ([$cardNumberField, $cvvField] as $field) {
            if ($payment->hasDatabaseField($field)) {
                $kept[$field] = $payment->{$field};
                $payment->{$field} = '';
            }
        }

        try {
            $payment->write();
        } finally {
            foreach ($kept as $field => $value) {
                $payment->{$field} = $value;
            }
        }
    }

    /**
     * Removes the card security number (CVV) and masks the card number (only the last four digits are kept)
     * of the payment object, once the payment has been processed.
     * (These fields are only filled in for payment methods that take card details on this website.)
     */
    protected function scrubCardData(): void
    {
        $payment = $this->paymentObject;
        if (! $payment || ! $payment->exists()) {
            return;
        }
        [$cardNumberField, $cvvField] = $this->cardFieldNames($payment);
        $changed = false;
        if ($payment->hasDatabaseField($cvvField) && '' !== (string) $payment->{$cvvField}) {
            $payment->{$cvvField} = '';
            $changed = true;
        }
        if ($payment->hasDatabaseField($cardNumberField)) {
            $number = (string) $payment->{$cardNumberField};
            // only mask if it still looks like a full card number (not already masked)
            if (preg_match('/^\d{12,19}$/', $number)) {
                $payment->{$cardNumberField} = str_repeat('*', strlen($number) - 4) . substr($number, -4);
                $changed = true;
            }
        }
        if ($changed) {
            $payment->write();
        }
    }

    /**
     * Creates a payment object for the payment method that was submitted.
     * SECURITY: the submitted value is user input, so we only ever create a payment object
     * for a payment method that is currently supported (configured and, on live, not a test method)
     * for this order AND that is an EcommercePayment.
     * Anything else returns null.
     *
     * @param null|Order $order
     * @param array      $data  - Array of data that is submittted
     */
    protected function createPaymentObjectFromData($order, $data): ?EcommercePayment
    {
        $paymentClass = is_array($data) ? ($data['PaymentMethod'] ?? null) : null;
        if (! is_string($paymentClass) || '' === $paymentClass) {
            return null;
        }
        //important! convert back to PHP class
        $paymentClass = ltrim(EcommercePayment::html_class_to_php_class($paymentClass), '\\');
        $supportedClasses = array_map(
            static fn ($class) => ltrim((string) $class, '\\'),
            array_keys(EcommercePayment::get_supported_methods($order instanceof Order ? $order : null))
        );
        if (! in_array($paymentClass, $supportedClasses, true)) {
            return null;
        }
        if (! class_exists($paymentClass) || ! is_a($paymentClass, EcommercePayment::class, true)) {
            return null;
        }

        return $paymentClass::create();
    }

    /**
     * Process payment form and return next step in the payment process.
     * Steps taken are:
     * 1. create new payment
     * 2. save form into payment
     * 3. return payment result.
     *
     * @param  Order $order - the order that is being paid
     * @param  array $data  - Array of data that is submittted
     * @param  Form  $form  - the form that is being submitted
     *                      *
     * @return bool  - if successful, this method will return TRUE
     */
    public function processPaymentFormAndReturnNextStep(Order $order, $data, Form $form)
    {
        if (! $this->paymentObject) {
            $this->paymentObject = $this->createPaymentObjectFromData($order, $data);
        }
        if (! $this->paymentObject) {
            return false;
        }
        // Save payment data from form and process payment
        $form->saveInto($this->paymentObject);
        $this->paymentObject->OrderID = $order->ID;
        //important to set the amount and currency (WE SET THEM BOTH AT THE SAME TIME!)
        $this->paymentObject->Amount = $order->getTotalOutstandingAsMoney();
        // $form->saveInto may have put the card details on the payment object: they must not be saved.
        $this->writePaymentWithoutCardData();
        // Process payment, get the result back
        try {
            $result = $this->paymentObject->processPayment($data, $form);
        } finally {
            // the card security number must never be kept and the card number is masked
            // (also when processing the payment throws an error)
            $this->scrubCardData();
        }

        if (! is_a($result, EcommerceConfigClassNames::getName(EcommercePaymentResult::class))) {
            $form->getController()->redirectBack();

            return false;
        }
        if ($result->isProcessing()) {
            //IMPORTANT!!!
            // isProcessing(): Long payment process redirected to another website (PayPal, Worldpay)
            //redirection is taken care of by payment processor
            return $result->getValue();
        }
        //payment is done, redirect to either returntolink
        //OR to the link of the order ....
        // returntolink is posted by the browser so it can not be trusted:
        // we only redirect to pages on this website (open redirect protection).
        $returnToLink = isset($data['returntolink']) && is_string($data['returntolink']) ? $data['returntolink'] : '';
        if ('' !== $returnToLink && Director::is_site_url($returnToLink)) {
            $form->getController()->redirect($returnToLink);
        } else {
            $form->getController()->redirect($order->Link());
        }

        return true;
    }

    /**
     * checks if a credit card is a real credit card number.
     *
     * @reference: http://en.wikipedia.org/wiki/Luhn_algorithm
     *
     * @param int|string $cardNumber
     *
     * @return bool
     */
    public function validateCardNumber($cardNumber)
    {
        if (! $cardNumber) {
            return false;
        }
        for ($sum = 0, $i = strlen((string) $cardNumber) - 1; $i >= 0; --$i) {
            $digit = (int) $cardNumber[$i];
            $sum += ($i % 2) === 0 ? array_sum(str_split($digit * 2)) : $digit;
        }

        return ($sum % 10) === 0;
    }

    /**
     * @todo: finish!
     * valid expiry date
     *
     * @param string $monthYear - e.g. 0218
     *
     * @return bool
     */
    public function validateExpiryMonth($monthYear)
    {
        $month = (int) substr((string) $monthYear, 0, 2);
        $year = (int) ('20' . substr((string) $monthYear, 2));
        $currentYear = (int) date('Y');
        $currentMonth = (int) date('m');
        if ($year > 0) {
            if ($year > $currentYear) {
                return true;
            }
            if ($year === $currentYear && $currentMonth <= $month) {
                return true;
            }
        }

        return false;
    }

    /**
     * @todo: TEST
     * valid CVC/CVV number?
     *
     * @param int $cardNumber
     * @param int $cvv
     *
     * @return bool
     */
    public function validateCVV($cardNumber, $cvv)
    {
        $cardNumber = preg_replace('#\D#', '', (string) $cardNumber);
        $cvv = preg_replace('#\D#', '', (string) $cvv);

        //Checks to see whether the submitted value is numeric (After spaces and hyphens have been removed).
        if (is_numeric($cardNumber)) {
            //Checks to see whether the submitted value is numeric (After spaces and hyphens have been removed).
            if (is_numeric($cvv)) {
                //Splits up the card number into various identifying lengths.
                // $firstOne = substr((string) $cardNumber, 0, 1);
                $firstTwo = substr($cardNumber, 0, 2);

                //If the card is an American Express
                if ('34' === $firstTwo || '37' === $firstTwo) {
                    if (! preg_match('#^\\d{4}$#', $cvv)) {
                        // The credit card is an American Express card
                        // but does not have a four digit CVV code
                        return false;
                    }
                } elseif (! preg_match('#^\\d{3}$#', $cvv)) {
                    // The credit card is a Visa, MasterCard, or Discover Card card
                    // but does not have a three digit CVV code
                    return false;
                }
                //passed all checks
                return true;
            }

            return false;
        }

        return false;
    }
}
