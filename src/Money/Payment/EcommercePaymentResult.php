<?php

namespace Sunnysideup\Ecommerce\Money\Payment;

use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Extensible;
use SilverStripe\Core\Injector\Injectable;

abstract class EcommercePaymentResult
{
    use Configurable;
    use Extensible;
    use Injectable;

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
