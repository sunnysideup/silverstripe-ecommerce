<?php
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
 */


class ExchangeRateProvider extends Object {

	/**
	 * cache of exchange rates
	 *
	 */
	private static $memory_cache = array();
	/**
	 * Currency Code "from"
	 * @var String
	 */
	protected $fromCode = "";


	/**
	 * Currency Code "to"
	 * @var String
	 */
	protected $toCode = "";


	/**
	 * TO DO: make this work
	 *
	 *
	 */
	function ExchangeRate($fromCode, $toCode) {
		$cacheCode = $fromCode.$toCode;
		if(isset(self::$memory_cache[$cacheCode])) {
			return self::$memory_cache[$cacheCode];
		}
		else {
			$this->fromCode = $fromCode;
			$this->toCode = $toCode;
			$value = 1;
			self::$memory_cache[$cacheCode] = $value;
			return $value;
		}
	}


}
