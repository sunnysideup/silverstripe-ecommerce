<?php

/**
 * if you want to implement rules around selecting
 * specific payment gateways for specific orders then
 * you need to extend this class and add the following to
 * mysite/_config/config.yml:
 * <code yml>
 *  Injector:
 *    EcommercePaymentSupportedMethodsProvider:
 *      class: MyCustom_EcommercePaymentSupportedMethodsProvider
 * </code>.
 *
 * in PHP you will have something like this:
 * <code php>
 * class MyCustom_EcommercePaymentSupportedMethodsProvider extends EcommercePaymentSupportedMethodsProvider {
 *  //....
 * }
 * </code>
 */
class EcommercePaymentSupportedMethodsProvider extends Object
{
    /**
     * this method returns an associative array of payment methods
     * available for the current order.
     *
     * @return array
     */
    public function SupportedMethods($order = null)
    {
        $hideTestPaymentMethods = false;
        if (Director::isLive()) {
            $hideTestPaymentMethods = false;
        }
        $supportedMethods = EcommerceConfig::get('EcommercePayment', 'supported_methods');
        if (ArrayLib::is_associative($supportedMethods)) {
            if ($hideTestPaymentMethods) {
                if (count($supportedMethods)) {
                    foreach ($supportedMethods as $methodClass => $methodTitle) {
                        if (is_subclass_of($methodClass, 'EcommercePayment_Test')) {
                            unset($supportedMethods[$methodClass]);
                        }
                    }
                }
            }
        } else {
            user_error('EcommercePayment::$supported_methods() requires an associative array. Right now the supported payments methods are: '.print_r($supportedMethods, 1), E_USER_NOTICE);
        }

        return $supportedMethods;
    }

    /**
     * returns the order to use.... You can provide one
     * which basically just checks that it is a real order.
     *
     * @param Order | Int
     *
     * @return Order
     */
    protected function orderToUse($order = null)
    {
        if ($order && $order instanceof $order) {
            return $order;
        }
        if (intval($order)) {
            return Order::get()->byID(intval($order));
        } else {
            return ShoppingCart::current_order();
        }
        user_error("Can't find an order to use");
    }
}
