<?php

namespace Sunnysideup\Ecommerce\Money\Payment;

use SilverStripe\View\ViewableData;

abstract class EcommercePaymentResult extends ViewableData
{
    protected $value;

    public function __construct($value = null)
    {
        $this->value = $value;

        parent::__construct();
    }

    public function getValue()
    {
        return $this->value;
    }

    abstract public function isSuccess();

    abstract public function isProcessing();
}
