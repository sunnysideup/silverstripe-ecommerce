<?php


class OrderToArray extends Object
{

    protected $order = null;

    public function __construct(Order $order)
    {
        parent::__construct();
        $this->order = $order;
    }

    public function getOrder()
    {
        return $this->order;
    }

    public function toArray() :array
    {
        return $order->map()->toArray();
        return $array;
    }

}
