<?php

namespace Sunnysideup\Ecommerce\Interfaces;

use Sunnysideup\Ecommerce\Model\Order;

interface EcommercePaymentSupportedMethodsProviderInterface
{
    /**
     * how can the customer pay?
     */
    public function SupportedMethods(?Order $order = null): array;

    /**
     * assign the right payment gateways for the user.
     *
     * @param string $gateway (optional)
     */
    public static function assign_payment_gateway(?string $gateway = '');
}
