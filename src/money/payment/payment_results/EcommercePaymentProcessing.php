<?php

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

