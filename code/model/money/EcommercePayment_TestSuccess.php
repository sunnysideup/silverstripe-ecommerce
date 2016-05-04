<?php


/**
 * Payment object representing a TEST = SUCCESS.
 */
class EcommercePayment_TestSuccess extends EcommercePayment_Test
{
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

    public function getPaymentFormFields()
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
