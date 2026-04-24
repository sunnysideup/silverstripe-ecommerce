<?php

declare(strict_types=1);

namespace Sunnysideup\Ecommerce\Money\Payment;

use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Extensible;
use SilverStripe\Core\Injector\Injectable;

abstract class EcommercePaymentResult
{
    use Configurable;
    use Extensible;
    use Injectable;

    public function __construct(protected $value = null)
    {
    }

    public function getValue()
    {
        return $this->value;
    }

    abstract public function isSuccess();

    abstract public function isProcessing();
}
