<?php

class ExchangeRateProviderDummy extends ExchangeRateProvider
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
