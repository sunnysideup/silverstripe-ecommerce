<?php

namespace Sunnysideup\Ecommerce\Money;

use SilverStripe\Control\Director;
use SilverStripe\ORM\ArrayLib;
use SilverStripe\ORM\DataObject;
use SilverStripe\View\ViewableData;
use Sunnysideup\Ecommerce\Api\ShoppingCart;
use Sunnysideup\Ecommerce\Config\EcommerceConfig;
use Sunnysideup\Ecommerce\Interfaces\EcommercePaymentSupportedMethodsProviderInterface;
use Sunnysideup\Ecommerce\Model\Money\EcommercePayment;
use Sunnysideup\Ecommerce\Model\Money\PaymentTypes\EcommercePaymentTest;
use Sunnysideup\Ecommerce\Model\Order;

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

class EcommercePaymentSupportedMethodsProvider extends ViewableData implements EcommercePaymentSupportedMethodsProviderInterface
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
            $hideTestPaymentMethods = true;
        }
        $supportedMethods = EcommerceConfig::get(EcommercePayment::class, 'supported_methods');
        if (count($supportedMethods)) {
            if (ArrayLib::is_associative($supportedMethods)) {
                if ($hideTestPaymentMethods) {
                    foreach (array_keys($supportedMethods) as $methodClass) {
                        if (is_subclass_of($methodClass, EcommercePaymentTest::class)) {
                            unset($supportedMethods[$methodClass]);
                        }
                    }
                }
            }
        } else {
            user_error('
                EcommercePayment::$supported_methods() requires an associative array.
                Right now the supported payments methods are: ' . print_r($supportedMethods, 1), E_USER_NOTICE);
        }
        return $supportedMethods;
    }

    public static function assign_payment_gateway($gateway = '')
    {
        user_error('
            This function has not been implemented on this class.
            You can extend this class to allow for a supported method to be set.');
    }

    /**
     * replace a payment with another one
     * @param  array  $array
     * @param  string $oldKey
     * @param  string $newKey
     * @return array
     */
    protected function arrayReplaceKey(array $array, string $oldKey, string $newKey) : array
    {
        $r = [];
        foreach ($array as $k => $v) {
            if ($k === $oldKey) $k = $newKey;
            $r[$k] = $v;
        }

        return $r;
    }
    /**
     * returns the order to use....
     * You can provide one as a param,
     * which basically just checks that it is a real order.
     *
     * @param Order $order (optional) | Int
     *
     * @return Order | DataObject
     */
    protected function orderToUse($order = null)
    {
        if ($order && $order instanceof Order) {
            return $order;
        }
        if (intval($order)) {
            return Order::get()->byID(intval($order));
        }
        return ShoppingCart::current_order();

        user_error("Can't find an order to use");
    }
}
