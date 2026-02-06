<?php

namespace Sunnysideup\Ecommerce\Api;

use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Extensible;
use SilverStripe\Core\Injector\Injectable;
use Sunnysideup\Ecommerce\Model\Order;

abstract class OrderConverter
{
    use Configurable;
    use Extensible;
    use Injectable;

    protected $order;

    protected $currencyCode = '';

    protected $billingAddress;

    protected $shippingAddress;

    protected $modifiers;

    protected $orderItems;

    private $amountsPerModifierType = [];

    public function __construct(?Order $order = null)
    {
        if (! $order instanceof \Sunnysideup\Ecommerce\Model\Order) {
            $order = ShoppingCart::current_order();
        }
        ClassHelpers::check_for_instance_of($order, Order::class);
        $this->order = $order;

        $this->retrieveOrderDetails();
    }

    public function retrieveOrderDetails()
    {
        $this->currencyCode = $this->order->CurrencyUsed()->Code;
        $this->billingAddress = $this->order->BillingAddress();
        $this->orderItems = $this->order->OrderItems();
        $this->modifiers = $this->order->Modifiers();
        if ($this->order->IsSeparateShippingAddress()) {
            $this->shippingAddress = $this->order->ShippingAddress();
            $this->shippingAddress->setFieldsToMatchBillingAddress();

        } else {
            $this->shippingAddress = $this->billingAddress;
        }
    }

    public function getOrder()
    {
        return $this->order;
    }

    public function getAmountForModifierType($type)
    {
        if (empty($this->amountsPerModifierType[$type])) {
            foreach ($this->modifiers as $modifier) {
                $myType = $modifier->Type ?? $modifier->getLiveType();
                if ($myType === $type) {
                    if (! isset($this->amountsPerModifierType[$type])) {
                        $this->amountsPerModifierType[$type] = 0;
                    }
                    $this->amountsPerModifierType[$type] += $modifier->TableValue;
                }
            }
        }

        return $this->amountsPerModifierType[$type] ?? 0;
    }

    abstract public function convert();

    protected function implodeAndTrim($fields, $glue = '')
    {
        return trim(
            implode(
                ' ',
                $fields
            )
        );
    }
}
