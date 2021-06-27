<?php

namespace Sunnysideup\Ecommerce\Model\Money\PaymentTypes;

use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\LiteralField;
use Sunnysideup\Ecommerce\Forms\OrderForm;
use Sunnysideup\Ecommerce\Model\Order;
use Sunnysideup\Ecommerce\Money\Payment\PaymentResults\EcommercePaymentFailure;

/**
 * Payment object representing a TEST = FAILURE.
 *
 * @internal
 * @coversNothing
 */
class EcommercePaymentTestFailure extends EcommercePaymentTest
{
    /**
     * standard SS variable.
     *
     * @var string
     */
    private static $singular_name = 'Ecommerce Test Failure Payment';

    /**
     * standard SS variable.
     *
     * @var string
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
     * @param array $data The form request data - see OrderForm
     * @param Form  $form The form object submitted on
     *
     * @return EcommercePaymentFailure
     */
    public function processPayment($data, Form $form)
    {
        $this->Status = 'Failure';
        $this->Message = '<div>PAYMENT TEST: FAILURE</div>';
        $this->write();

        return new EcommercePaymentFailure();
    }

    public function getPaymentFormFields($amount = 0, ?Order $order = null): FieldList
    {
        return new FieldList(
            new LiteralField('SuccessBlurb', '<div>FAILURE PAYMENT TEST</div>')
        );
    }
}
