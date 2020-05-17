<?php


abstract class EcommercePaymentResult extends Object
{
    protected $value;

    public function __construct($value = null)
    {
        $this->value = $value;
    }

    public function getValue()
    {
        return $this->value;
    }

    abstract public function isSuccess();

    abstract public function isProcessing();
}
