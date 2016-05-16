<?php

class EcommercePayment_Processing extends EcommercePayment_Result
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
