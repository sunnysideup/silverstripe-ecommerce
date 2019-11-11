<?php


abstract class OrderConverter extends Object
{

    protected $order = null;

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
    }

    public function getOrder()
    {
        return $this->order;
    }


    abstract public function convert();

}
