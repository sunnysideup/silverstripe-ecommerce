<?php

class ExchangeRateProvider_Dummy extends ExchangeRateProvider
{
    /**
     * @param string $fromCode
     * @param string $toCode
     */
    public function ExchangeRate($fromCode, $toCode)
    {
        return 1;
    }
}
