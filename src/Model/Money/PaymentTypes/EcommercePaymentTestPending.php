<?php

/**
 * Payment object representing a TEST = PENDING.
 */
class EcommercePaymentTestPending extends EcommercePaymentTest
{
    /**
     * standard SS variable.
     *
     * @Var String
     */
    private static $singular_name = 'Ecommerce Test Pending Payment';

    /**
     * standard SS variable.
     *
     * @Var String
     */
    private static $plural_name = 'Ecommerce Test Pending Payments';

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
     * @return EcommercePaymentResult
     */
    public function processPayment($data, $form)
    {
        $this->Status = 'Pending';
        $this->Message = '<div>PAYMENT TEST: PENDING</div>';
        $this->write();

        return new EcommercePaymentProcessing();
    }

    public function getPaymentFormFields($amount = 0, $order = null)
    {
        return new FieldList(
            new LiteralField('SuccessBlurb', '<div>PENDING PAYMENT TEST</div>')
        );
    }
}

