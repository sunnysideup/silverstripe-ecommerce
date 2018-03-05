<?php

class EcommercePayment_Success extends EcommercePayment_Result
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
