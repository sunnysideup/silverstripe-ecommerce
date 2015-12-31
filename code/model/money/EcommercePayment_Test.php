<?php


/**
 * Payment object representing a generic test payment
 *
 * @package ecommerce
 * @subpackage payment
 */
class EcommercePayment_Test extends EcommercePayment
{

    public function getPaymentFormRequirements()
    {
        return null;
    }
}
