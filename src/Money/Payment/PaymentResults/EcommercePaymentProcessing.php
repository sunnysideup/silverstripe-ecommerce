<?php

namespace Sunnysideup\Ecommerce\Money\Payment\PaymentResults;


use Sunnysideup\Ecommerce\Money\Payment\EcommercePaymentResult;



class EcommercePaymentProcessing extends EcommercePaymentResult
{
    public function isSuccess()
    {
        return false;
    }

    public function isProcessing()
    {
        return true;
    }
}

