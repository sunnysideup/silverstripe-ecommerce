<?php


/**
 * Payment object representing a generic test payment.
 */
class EcommercePayment_Test extends EcommercePayment
{
    /**
     * standard SS variable.
     *
     * @Var String
     */
    private static $singular_name = 'Ecommerce Test Payment';

    /**
     * standard SS variable.
     *
     * @Var String
     */
    private static $plural_name = 'Ecommerce Test Payments';

    public function i18n_singular_name()
    {
        return $this->Config()->get('singular_name');
    }

    public function i18n_plural_name()
    {
        return $this->Config()->get('plural_name');
    }

    public function getPaymentFormRequirements()
    {
        return;
    }
}
