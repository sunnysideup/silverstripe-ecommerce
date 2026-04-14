<?php

namespace Sunnysideup\Ecommerce\Model\Money\PaymentTypes;

use Override;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\LiteralField;
use Sunnysideup\Ecommerce\Model\Money\EcommercePayment;
use Sunnysideup\Ecommerce\Model\Order;
use Sunnysideup\Ecommerce\Money\Payment\PaymentResults\EcommercePaymentFailure;

/**
 * Payment object representing a TEST = FAILURE.
 */
class EcommercePaymentTestFailure extends EcommercePaymentTest
{
    private static $table_name = 'EcommercePaymentTestFailure';

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

    #[Override]
    public function i18n_singular_name()
    {
        return $this->Config()->get('singular_name');
    }

    #[Override]
    public function plural_name()
    {
        return $this->Config()->get('plural_name');
    }

    /**
     * @param array $data The form request data - see OrderForm
     * @param Form  $form The form object submitted on
     *
     * @return EcommercePaymentFailure
     */
    #[Override]
    public function processPayment($data, Form $form)
    {
        $this->Status = EcommercePayment::FAILURE_STATUS;
        $this->Message = '<div>PAYMENT TEST: FAILURE</div>';
        $this->write();

        return EcommercePaymentFailure::create();
    }

    #[Override]
    public function getPaymentFormFields($amount = 0, ?Order $order = null): FieldList
    {
        return FieldList::create(LiteralField::create('SuccessBlurb', '<div>FAILURE PAYMENT TEST</div>'));
    }
}
