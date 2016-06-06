<?php

interface EcommercePaymentSupportedMethodsProviderInterface
{
    /**
     * how can the customer pay?
     * @param mixed $order
     *
     * @return array
     */
    function SupportedMethods($order);

    /**
     * force one particular gateway.
     */
    public static function set_payment_gateway($gateway = "");

}
