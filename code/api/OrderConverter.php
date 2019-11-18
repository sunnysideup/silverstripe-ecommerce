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
            $this->shippingAddress = $billing;
        }
    }


    public function getOrder()
    {
        return $this->order;
    }

    private $amountsPerModifierType = [];

    public function getAmountForModifierType($type)
    {
        if(empty($this->amountsPerModifierType)) {
            foreach($this->modifiers as $modifier) {
                if($modifier->Type) {
                    if(! isset($this->amountsPerModifierType[$modifier->Type])) {
                        $this->amountsPerModifierType[$modifier->Type] = 0;
                    }
                    $this->amountsPerModifierType[$modifier->Type] += $modifier->CalculatedTotal;
                }
            }
        }

        return $this->amountsPerModifierType[$type] ?? 0;
    }

    abstract public function convert();

}
