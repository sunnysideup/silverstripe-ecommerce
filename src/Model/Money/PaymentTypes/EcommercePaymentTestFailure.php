<?php

namespace Sunnysideup\Ecommerce\Model\Money\PaymentTypes;

use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\LiteralField;
use Sunnysideup\Ecommerce\Forms\OrderForm;
use Sunnysideup\Ecommerce\Money\Payment\PaymentResults\EcommercePaymentFailure;

/**
 * Payment object representing a TEST = FAILURE.
 */
class EcommercePaymentTestFailure extends EcommercePaymentTest
{
    /**
     * standard SS variable.
     *
     * @Var String
     */
    private static $singular_name = 'Ecommerce Test Failure Payment';

    /**
     * standard SS variable.
     *
     * @Var String
     */
    private static $plural_name = 'Ecommerce Test Failuer Payments';

    public function i18n_singular_name()
    {
        return $this->Config()->get('singular_name');
    }

    public function i18n_plural_name()
    {
        return $this->Config()->get('plural_name');
    }

    /**
     * @param array     $data The form request data - see OrderForm
     * @param OrderForm $form The form object submitted on
     *
     * @return EcommercePaymentFailure
     */
    public function processPayment($data, OrderForm $form)
    {
        $this->Status = 'Failure';
        $this->Message = '<div>PAYMENT TEST: FAILURE</div>';
        $this->write();

        return new EcommercePaymentFailure();
    }

    public function getPaymentFormFields($amount = 0, $order = null)
    {
        return new FieldList(
            new LiteralField('SuccessBlurb', '<div>FAILURE PAYMENT TEST</div>')
        );
    }
}
