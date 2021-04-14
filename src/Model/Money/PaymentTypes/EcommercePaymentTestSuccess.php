<?php

namespace Sunnysideup\Ecommerce\Model\Money\PaymentTypes;

use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\LiteralField;
use Sunnysideup\Ecommerce\Forms\OrderForm;
use Sunnysideup\Ecommerce\Model\Order;
use Sunnysideup\Ecommerce\Money\Payment\PaymentResults\EcommercePaymentSuccess;

/**
 * Payment object representing a TEST = SUCCESS.
 *
 * @internal
 * @coversNothing
 */
class EcommercePaymentTestSuccess extends EcommercePaymentTest
{
    /**
     * standard SS variable.
     *
     * @var string
     */
    private static $singular_name = 'Ecommerce Test Success Payment';

    /**
     * standard SS variable.
     *
     * @var string
     */
    private static $plural_name = 'Ecommerce Test Success Payments';

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
     * @return EcommercePaymentSuccess
     */
    public function processPayment($data, OrderForm $form)
    {
        $this->Status = 'Success';
        $this->Message = '<div>PAYMENT TEST: SUCCESS</div>';
        $this->write();

        return new EcommercePaymentSuccess();
    }

    public function getPaymentFormFields($amount = 0, ?Order $order = null): FieldList
    {
        return new FieldList(
            new LiteralField('SuccessBlurb', '<div>SUCCESSFUL PAYMENT TEST</div>')
        );
    }

    public function getPaymentFormRequirements(): array
    {
        return [];
    }
}
