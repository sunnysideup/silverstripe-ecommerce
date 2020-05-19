<?php

namespace Sunnysideup\Ecommerce\Money\Payment\PaymentResults;

use EcommercePaymentResult;


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

