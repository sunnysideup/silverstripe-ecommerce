<?php


abstract class OrderConverter extends Object
{

    protected $order = null;

    protected $currencyCode = '';

    protected $billingAddress = null;

    protected $shippingAddress = null;

    protected $modifiers = null;

    protected $orderItems = null;

    public function __construct(?Order $order = null)
    {
        parent::__construct();
        if($order === null) {
            $order = Shoppingcart::current_order();
        }
        if($order instanceof Order) {
            $this->order = $order;
        } else {
            user_error('We expect an order here ;-), provided is: '.print_r($order, 1));
        }

        $this->retrieveOrderDetails();
    }

    public function retrieveOrderDetails()
    {
        $this->currencyCode = $this->order->CurrencyUsed()->Code;
        $this->billingAddress = $this->order->BillingAddress();
        $this->orderItems = $this->order->OrderItems();
        $this->modifiers = $this->order->Modifiers();
        if($this->order->IsSeparateShippingAddress()) {
            $this->shippingAddress = $this->order->ShippingAddress();
        } else {
            $this->shippingAddress = $this->billingAddress;
        }
    }


    public function getOrder()
    {
        return $this->order;
    }

    private $amountsPerModifierType = [];

    public function getAmountForModifierType($type)
    {
        if(empty($this->amountsPerModifierType[$type])) {

            foreach($this->modifiers as $modifier) {
                $myType = $modifier->Type ?? $modifier->getLiveType();
                if($myType === $type) {
                    if(! isset($this->amountsPerModifierType[$type])) {
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
