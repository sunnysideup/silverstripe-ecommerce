<?php

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
