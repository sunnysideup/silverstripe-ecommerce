<?php

namespace Sunnysideup\Ecommerce\Forms\Validation;

use ViewableData;
use FieldList;
use EcommerceCreditCardField;
use TextField;
use ExpiryDateField;
use EcommercePayment;
use Config;


//get_credit_card_payment_form_fields: $formHelper->getCreditCardPaymentFormFields
//get_credit_card_payment_form_fields_required: : $formHelper->getCreditCardPaymentFormFieldsRequired
//validate_payment: $formHelper->validatePayment
//validate_and_save_credit_card_information: validateAndSaveCreditCardInformation
//process_payment_form_and_return_next_step: processPaymentFormAndReturnNextStep
//validate_card_number:  validateCardNumber
//validate_expiry_month: :validateExpiryMonth
//validate_CVV: validateCVV


/**
  * ### @@@@ START REPLACEMENT @@@@ ###
  * WHY: automated upgrade
  * OLD:  extends Object (ignore case)
  * NEW:  extends ViewableData (COMPLEX)
  * EXP: This used to extend Object, but object does not exist anymore. You can also manually add use Extensible, use Injectable, and use Configurable
  * ### @@@@ STOP REPLACEMENT @@@@ ###
  */
class EcommercePaymentFormSetupAndValidation extends ViewableData
{
    /**
     * @var EcommercePayment
     */
    protected $paymentObject = null;

    /**
     * you can set specific EcommercePayment payment fields here, like this:
     *     MyEcommercePaymentClass
     *         CardNumber: MyCardNumberDBField
     *         NameOnCard: MyNameOnCardDBField
     *         CVVNumber: MyCVVNumberDBField
     *         ExpiryDate: MyExpiryDateDBField.
     *
     * @var array
     */

/**
  * ### @@@@ START REPLACEMENT @@@@ ###
  * OLD: private static $db (case sensitive)
  * NEW: 
    private static $table_name = '[SEARCH_REPLACE_CLASS_NAME_GOES_HERE]';

    private static $db (COMPLEX)
  * EXP: Check that is class indeed extends DataObject and that it is not a data-extension!
  * ### @@@@ STOP REPLACEMENT @@@@ ###
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
     * @return FieldList
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
        if (($order->TotalOutstanding() === 0 && $order->IsSubmitted()) || $order->IsPaid() || $order->Total() === 0) {
            return true;
        }
        if (! $this->paymentObject) {
            $paymentClass = ! empty($data['PaymentMethod']) ? $data['PaymentMethod'] : null;
            if ($paymentClass) {
                //important! convert back to PHP class
                $paymentClass = EcommercePayment::html_class_to_php_class($paymentClass);
                if (class_exists($paymentClass)) {
                    $this->paymentObject = $paymentClass::create();
                }
            }
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
        $dbFieldMap = Config::inst()->get('EcommercePaymentFormSetupAndValidation', 'db_field_map');
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
                    $this->paymentObject->{$dbFieldName} = trim(
                        $data[$formFieldName][0] .
                        $data[$formFieldName][1] .
                        $data[$formFieldName][2] .
                        $data[$formFieldName][3]
                    );
                    $cardNumber = $this->paymentObject->{$dbFieldName};
                    if (! $this->validateCardNumber($this->paymentObject->{$dbFieldName})) {

/**
  * ### @@@@ START REPLACEMENT @@@@ ###
  * WHY: automated upgrade
  * OLD: $form->addErrorMessage( (case sensitive)
  * NEW: $form->sessionError( (COMPLEX)
  * EXP: SilverStripe\Forms\Form->addErrorMessage(): Removed. Use `sessionMessage()` or `sessionError()` to add a form level message, throw a `ValidationException` during submission, or add a custom validator.
  * ### @@@@ STOP REPLACEMENT @@@@ ###
  */
                        $form->sessionError(
                            $formFieldName,
                            _t('EcommercePaymentFormSetupAndValidation.INVALID_CREDIT_CARD', 'Invalid credit card number.'),
                            'bad'
                        );
                        $errors = true;
                    }
                    break;
                case 'ExpiryDate':
                    $this->paymentObject->{$dbFieldName} =
                        $data[$formFieldName]['month'] .
                        $data[$formFieldName]['year'];
                    if (! $this->validateExpiryMonth($this->paymentObject->{$dbFieldName})) {

/**
  * ### @@@@ START REPLACEMENT @@@@ ###
  * WHY: automated upgrade
  * OLD: $form->addErrorMessage( (case sensitive)
  * NEW: $form->sessionError( (COMPLEX)
  * EXP: SilverStripe\Forms\Form->addErrorMessage(): Removed. Use `sessionMessage()` or `sessionError()` to add a form level message, throw a `ValidationException` during submission, or add a custom validator.
  * ### @@@@ STOP REPLACEMENT @@@@ ###
  */
                        $form->sessionError(
                            $formFieldName,
                            _t('EcommercePaymentFormSetupAndValidation.INVALID_EXPIRY_DATE', 'Expiry date not valid.'),
                            'bad'
                        );
                        $errors = true;
                    }
                    break;
                case 'CVVNumber':
                    $this->paymentObject->{$dbFieldName} = trim($data[$formFieldName]);
                    if (! $this->validateCVV($cardNumber, $this->paymentObject->{$dbFieldName})) {

/**
  * ### @@@@ START REPLACEMENT @@@@ ###
  * WHY: automated upgrade
  * OLD: $form->addErrorMessage( (case sensitive)
  * NEW: $form->sessionError( (COMPLEX)
  * EXP: SilverStripe\Forms\Form->addErrorMessage(): Removed. Use `sessionMessage()` or `sessionError()` to add a form level message, throw a `ValidationException` during submission, or add a custom validator.
  * ### @@@@ STOP REPLACEMENT @@@@ ###
  */
                        $form->sessionError(
                            $formFieldName,
                            _t('EcommercePaymentFormSetupAndValidation.INVALID_CVV_NUMBER', 'Invalid security number.'),
                            'bad'
                        );
                    }
                    break;
                case 'NameOnCard':
                    $this->paymentObject->{$dbFieldName} = trim($data[$formFieldName]);
                    if (strlen($this->paymentObject->{$dbFieldName}) < 3) {

/**
  * ### @@@@ START REPLACEMENT @@@@ ###
  * WHY: automated upgrade
  * OLD: $form->addErrorMessage( (case sensitive)
  * NEW: $form->sessionError( (COMPLEX)
  * EXP: SilverStripe\Forms\Form->addErrorMessage(): Removed. Use `sessionMessage()` or `sessionError()` to add a form level message, throw a `ValidationException` during submission, or add a custom validator.
  * ### @@@@ STOP REPLACEMENT @@@@ ###
  */
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
        $this->paymentObject->write();

        return true;
    }

    /**
     * Process payment form and return next step in the payment process.
     * Steps taken are:
     * 1. create new payment
     * 2. save form into payment
     * 3. return payment result.
     *
     * @param Order $order - the order that is being paid
     * @param Form  $form  - the form that is being submitted
     * @param array $data  - Array of data that is submittted
     *
     * @return bool - if successful, this method will return TRUE
     */
    public function processPaymentFormAndReturnNextStep($order, $data, $form)
    {
        if (! $this->paymentObject) {
            $paymentClass = ! empty($data['PaymentMethod']) ? $data['PaymentMethod'] : null;
            if ($paymentClass) {
                if (class_exists($paymentClass)) {
                    $this->paymentObject = $paymentClass::create();
                }
            }
        }
        if (! $this->paymentObject) {
            return false;
        }
        // Save payment data from form and process payment
        $form->saveInto($this->paymentObject);
        $this->paymentObject->OrderID = $order->ID;
        //important to set the amount and currency (WE SET THEM BOTH AT THE SAME TIME!)
        $this->paymentObject->Amount = $order->getTotalOutstandingAsMoney();
        $this->paymentObject->write();
        // Process payment, get the result back
        $result = $this->paymentObject->processPayment($data, $form);

/**
  * ### @@@@ START REPLACEMENT @@@@ ###
  * WHY: automated upgrade
  * OLD:  Object:: (case sensitive)
  * NEW:  SilverStripe\\Core\\Injector\\Injector::inst()-> (COMPLEX)
  * EXP: Check if this is the right implementation, this is highly speculative.
  * ### @@@@ STOP REPLACEMENT @@@@ ###
  */
        if (! is_a($result, SilverStripe\Core\Injector\Injector::inst()->getCustomClass('EcommercePaymentResult'))) {
            $form->controller->redirectBack();

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
        if (isset($data['returntolink'])) {
            $form->controller->redirect($data['returntolink']);
        } else {
            $form->controller->redirect($order->Link());
        }

        return true;
    }

    /**
     * checks if a credit card is a real credit card number.
     *
     * @reference: http://en.wikipedia.org/wiki/Luhn_algorithm
     *
     * @param string | Int $cardNumber
     *
     * @return bool
     */
    public function validateCardNumber($cardNumber)
    {
        if (! $cardNumber) {
            return false;
        }
        for ($sum = 0, $i = strlen($cardNumber) - 1; $i >= 0; --$i) {
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
        $month = intval(substr($monthYear, 0, 2));
        $year = intval('20' . substr($monthYear, 2));
        $currentYear = intval(Date('Y'));
        $currentMonth = intval(Date('m'));
        if (($month > 0 || $month < 13) && $year > 0) {
            if ($year > $currentYear) {
                return true;
            } elseif ($year === $currentYear) {
                if ($currentMonth <= $month) {
                    return true;
                }
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
        $cardNumber = preg_replace('/\D/', '', $cardNumber);
        $cvv = preg_replace('/\D/', '', $cvv);

        //Checks to see whether the submitted value is numeric (After spaces and hyphens have been removed).
        if (is_numeric($cardNumber)) {
            //Checks to see whether the submitted value is numeric (After spaces and hyphens have been removed).
            if (is_numeric($cvv)) {
                //Splits up the card number into various identifying lengths.
                // $firstOne = substr($cardNumber, 0, 1);
                $firstTwo = substr($cardNumber, 0, 2);

                //If the card is an American Express
                if ($firstTwo === '34' || $firstTwo === '37') {
                    if (! preg_match("/^\d{4}$/", $cvv)) {
                        // The credit card is an American Express card
                        // but does not have a four digit CVV code
                        return false;
                    }
                } elseif (! preg_match("/^\d{3}$/", $cvv)) {
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

