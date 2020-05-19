<?php

namespace Sunnysideup\Ecommerce\Money;





/***
 * the sole purpose of this class is to provide an exchange rate
 * from currency 1 to currency 2.
 * It can provide number that reads as follows:
 *
 * If I exchange 1 USD I will get EUR 0.8
 * This is the exchange rate.
 *
 * So, how many do I get of the "to" currency
 * when I have one "from" currency.
 *
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: money
 **/

class ExchangeRateProvider extends Object
{
    /**
     * adds a bit of additional cost to account for the exchange cost.
     *
     * @var floatval
     */
    protected $exchangeCostMultiplier = 1.05;

    /**
     * cache of exchange rates.
     *
     * @var array
     */
    private static $_memory_cache = [];

    /**
     * Get the exchange rate.
     *
     * @param string $fromCode e.g. NZD
     * @param string $toCode   e.g. USD
     *
     * @return float
     * @return float
     */
    public function ExchangeRate($fromCode, $toCode)
    {
        $fromCode = strtoupper($fromCode);
        $toCode = strtoupper($toCode);
        $cacheCode = $fromCode . '_' . $toCode;
        if (isset(self::$_memory_cache[$cacheCode])) {
            return self::$_memory_cache[$cacheCode];
        }

/**
  * ### @@@@ START REPLACEMENT @@@@ ###
  * WHY: automated upgrade
  * OLD: Session:: (case sensitive)
  * NEW: SilverStripe\Control\Controller::curr()->getRequest()->getSession()-> (COMPLEX)
  * EXP: If THIS is a controller than you can write: $this->getRequest(). You can also try to access the HTTPRequest directly. 
  * ### @@@@ STOP REPLACEMENT @@@@ ###
  */
        if ($value = SilverStripe\Control\Controller::curr()->getRequest()->getSession()->get($cacheCode)) {
            self::$_memory_cache[$cacheCode] = $value;
        } else {
            $value = $this->getRate($fromCode, $toCode);
            self::$_memory_cache[$cacheCode] = $value;

/**
  * ### @@@@ START REPLACEMENT @@@@ ###
  * WHY: automated upgrade
  * OLD: Session:: (case sensitive)
  * NEW: SilverStripe\Control\Controller::curr()->getRequest()->getSession()-> (COMPLEX)
  * EXP: If THIS is a controller than you can write: $this->getRequest(). You can also try to access the HTTPRequest directly. 
  * ### @@@@ STOP REPLACEMENT @@@@ ###
  */
            SilverStripe\Control\Controller::curr()->getRequest()->getSession()->set($cacheCode, $value);
        }

        return self::$_memory_cache[$cacheCode];
    }

    /**
     * gets a rate from a FROM and a TO currency.
     * see https://free.currencyconverterapi.com/ for limitations
     *
     * @param string $fromCode - UPPERCASE Code, e.g. NZD
     * @param string $toCode   - UPPERCASE Code, e.g. EUR
     *
     * @return float - returns exchange rate
     */
    protected function getRate($fromCode, $toCode)
    {
        $rate = 0;
        $reference = $fromCode . '_' . $toCode;
        $url = 'http://free.currencyconverterapi.com/api/v5/convert?q=' . $reference . '&compact=y';
        if (($ch = @curl_init())) {
            $timeout = 5; // set to zero for no timeout
            curl_setopt($ch, CURLOPT_URL, "${url}");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
            $record = curl_exec($ch);
            curl_close($ch);
        }
        if (! $record) {

/**
  * ### @@@@ START REPLACEMENT @@@@ ###
  * WHY: automated upgrade
  * OLD: file_get_contents (case sensitive)
  * NEW: file_get_contents (COMPLEX)
  * EXP: Use new asset abstraction (https://docs.silverstripe.org/en/4/changelogs/4.0.0#asset-storage
  * ### @@@@ STOP REPLACEMENT @@@@ ###
  */
            $record = file_get_contents($url);
        }

        if ($record) {
            $currencyData = json_decode($record);
            $rate = $currencyData->{$reference}->val;
            if (! $rate) {
                user_error('There was a problem retrieving the exchange rate.');
            }
        }
        if ($rate !== 1) {
            $rate *= $this->exchangeCostMultiplier;
        }

        return $rate;
    }
}

