<?php

/**
 * Payment object representing a TEST = PENDING.
 */
class EcommercePayment_TestPending extends EcommercePayment_Test
{

    /**
     * standard SS variable.
     *
     * @Var String
     */
    private static $singular_name = 'Ecommerce Test Pending Payment';
    public function i18n_singular_name()
    {
        return $this->Config()->get('singular_name');
    }

    /**
     * standard SS variable.
     *
     * @Var String
     */
    private static $plural_name = 'Ecommerce Test Pending Payments';
    public function i18n_plural_name()
    {
        return $this->Config()->get('plural_name');
    }

    /**
     * @param array     $data The form request data - see OrderForm
     * @param OrderForm $form The form object submitted on
     *
     * @return EcommercePayment_Result
     */
    public function processPayment($data, $form)
    {
        $this->Status = 'Pending';
        $this->Message = '<div>PAYMENT TEST: PENDING</div>';
        $this->write();

        return new EcommercePayment_Processing();
    }

    public function getPaymentFormFields()
    {
        return new FieldList(
            new LiteralField('SuccessBlurb', '<div>PENDING PAYMENT TEST</div>')
        );
    }
}
