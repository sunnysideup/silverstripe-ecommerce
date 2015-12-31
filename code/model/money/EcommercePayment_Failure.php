<?php

class EcommercePayment_Failure extends EcommercePayment_Result
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
