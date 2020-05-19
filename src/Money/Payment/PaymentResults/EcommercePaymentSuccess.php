<?php

namespace Sunnysideup\Ecommerce\Money\Payment\PaymentResults;


use Sunnysideup\Ecommerce\Money\Payment\EcommercePaymentResult;



class EcommercePaymentSuccess extends EcommercePaymentResult
{
    public function isSuccess()
    {
        return true;
    }

    public function isProcessing()
    {
        return false;
    }
}

