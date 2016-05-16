<?php


/**
 * Payment object representing a TEST = FAILURE.
 */
class EcommercePayment_TestFailure extends EcommercePayment_Test
{
    /**
     * @param array     $data The form request data - see OrderForm
     * @param OrderForm $form The form object submitted on
     *
     * @return EcommercePayment_Result
     */
    public function processPayment($data, $form)
    {
        $this->Status = 'Failure';
        $this->Message = '<div>PAYMENT TEST: FAILURE</div>';
        $this->write();

        return new EcommercePayment_Failure();
    }

    public function getPaymentFormFields()
    {
        return new FieldList(
            new LiteralField('SuccessBlurb', '<div>FAILURE PAYMENT TEST</div>')
        );
    }
}
