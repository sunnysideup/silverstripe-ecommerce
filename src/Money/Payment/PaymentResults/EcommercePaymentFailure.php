<?php

namespace Sunnysideup\Ecommerce\Money\Payment\PaymentResults;

use Sunnysideup\Ecommerce\Money\Payment\EcommercePaymentResult;

class EcommercePaymentFailure extends EcommercePaymentResult
{
    public function isSuccess()
    {
        return false;
    }

    public function isProcessing()
    {
        return false;
    }
}
