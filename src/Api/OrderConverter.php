<?php

namespace Sunnysideup\Ecommerce\Api;


use Shoppingcart;

use Sunnysideup\Ecommerce\Model\Order;
use SilverStripe\View\ViewableData;





/**
  * ### @@@@ START REPLACEMENT @@@@ ###
  * WHY: automated upgrade
  * OLD:  extends Object (ignore case)
  * NEW:  extends ViewableData (COMPLEX)
  * EXP: This used to extend Object, but object does not exist anymore. You can also manually add use Extensible, use Injectable, and use Configurable
  * ### @@@@ STOP REPLACEMENT @@@@ ###
  */
abstract class OrderConverter extends ViewableData
{
    protected $order = null;

    protected $currencyCode = '';

    protected $billingAddress = null;

    protected $shippingAddress = null;

    protected $modifiers = null;

    protected $orderItems = null;

    private $amountsPerModifierType = [];

    public function __construct($order = null)
    {
        parent::__construct();
        if ($order === null) {
            $order = Shoppingcart::current_order();
        }
        if ($order instanceof Order) {
            $this->order = $order;
        } else {
            user_error('We expect an order here ;-), provided is: ' . print_r($order, 1));
        }

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

