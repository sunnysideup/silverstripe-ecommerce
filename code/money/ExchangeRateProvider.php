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
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: money
 **/

class ExchangeRateProvider extends Object {

	/**
	 * cache of exchange rates
	 *
	 */
	private static $memory_cache = array();


	/**
	 * adds a bit of additional cost to account for the exchange cost.
	 * @var floatval
	 */
	protected $exchangeCostMultiplier = 1.05;

	/**
	 * Get the exchange rate
	 * @param String $fromCode e.g. NZD
	 * @param String $toCode e.g. USD
	 * @return Double
	 * @return Float
	 */
	public function ExchangeRate($fromCode, $toCode) {
		$fromCode = strtoupper($fromCode);
		$toCode = strtoupper($toCode);
		$cacheCode = $fromCode."_".$toCode;
		if(isset(self::$memory_cache[$cacheCode])) {
			return self::$memory_cache[$cacheCode];
		}
		else {
			if($value = Session::get($cacheCode)) {
				self::$memory_cache[$cacheCode] = $value;
			}
			else {
				$value = $this->getRate($fromCode, $toCode);
				self::$memory_cache[$cacheCode] = $value;
				Session::set($cacheCode, $value);
			}
		}
		return self::$memory_cache[$cacheCode];
	}

	/**
	 * gets a rate from a FROM and a TO currency
	 *
	 * @param String $fromCode - UPPERCASE Code, e.g. NZD
	 * @param String $toCode - UPPERCASE Code, e.g. EUR
	 * @return Double - returns exchange rate
	 */
	protected function getRate($fromCode, $toCode) {
		$rate = 0;
		//$url = http://finance.yahoo.com/currency/convert?amt=1&from=NZD&to=USD&submit=Convert
		$url = 'http://download.finance.yahoo.com/d/quotes.csv?s='.$fromCode.$toCode.'=X&f=sl1d1t1ba&e=.csv';
		if (($ch = @curl_init())) {
			$timeout = 5; // set to zero for no timeout
			curl_setopt ($ch, CURLOPT_URL, "$url");
			curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
			$record = curl_exec($ch);
			curl_close($ch);
		}
		if(!$record) {
			$record = file_get_contents($url);
		}
		if ($record) {
			$currencyData = explode(',', $record);
			$rate = $currencyData[1];
			if(!$rate) {
				$rate = $currencyData[2];
			}
		}
		if($rate != 1) {
			$rate = $rate * $this->exchangeCostMultiplier;
		}
		return $rate;
	}



}

class ExchangeRateProvider_Dummy extends ExchangeRateProvider {
		/**
	 *
	 * @param String $fromCode
	 * @param String $toCode
	 */
	public function ExchangeRate($fromCode, $toCode) {
		return 1;
	}
}