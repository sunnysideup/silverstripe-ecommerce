<?php


/**
 * Payment object representing a TEST = SUCCESS.
 */
class EcommercePayment_TestSuccess extends EcommercePayment_Test
{
    /**
     * standard SS variable.
     *
     * @Var String
     */
    private static $singular_name = 'Ecommerce Test Success Payment';

    /**
     * standard SS variable.
     *
     * @Var String
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
     * @return EcommercePayment_Result
     */
    public function processPayment($data, $form)
    {
        $this->Status = 'Success';
        $this->Message = '<div>PAYMENT TEST: SUCCESS</div>';
        $this->write();

        return new EcommercePayment_Success();
    }

    public function getPaymentFormFields($amount = 0, $order = null)
    {
        return new FieldList(
            new LiteralField('SuccessBlurb', '<div>SUCCESSFUL PAYMENT TEST</div>')
        );
    }

    public function getPaymentFormRequirements()
    {
        return;
    }
}
