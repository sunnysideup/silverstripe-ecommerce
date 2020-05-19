<?php

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

