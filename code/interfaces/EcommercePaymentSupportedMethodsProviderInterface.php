<?php

interface EcommercePaymentSupportedMethodsProviderInterface
{
    /**
     * how can the customer pay?
     * @param mixed $order
     *
     * @return array
     */
    public function SupportedMethods($order);

    /**
     * assign the right payment gateways for the user
     * @param string (optional) $gateway
     */
    public static function assign_payment_gateway($gateway = '');
}
