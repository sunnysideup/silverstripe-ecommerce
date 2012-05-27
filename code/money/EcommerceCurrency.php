<?php


/**
 * Object to manage currencies
 *
 *
 **/


class EcommerceCurrency extends DataObject {

	public static $db = array(
		"Code" => "Varchar(5)",
		"Name" => "Varchar(100)",
		"InUse" => "Boolean"
	);

	public static $has_one = array(
		"EcommerceCurrencyFormat" => "EcommerceCurrencyFormat"
	);

	public static $indexes = array(
		"Code" => true,
	);

	public static $casting = array(
		"IsDefault" => "Boolean",
		"ExchangeRate" => "Double"
	);
	//formatting

	public static $searchable_fields = array(
		"Code" => "PartialMatchFilter",
		"Name" => "PartialMatchFilter"
	);

	public static $field_labels = array(
		"Code" => "Short Code (e.g. NZD)",
		"Name" => "Name (e.g. New Zealand Dollar)",
		"InUse" => "Is it in use on the website"
	);

	public static $summary_fields = array("Code" => "Code","Name" => "Name"); //note no => for relational fields

	public static $singular_name = "Currency";
		function i18n_singular_name() { return _t("EcommerceCurrency.CURRENCY", "Currency");}

	public static $plural_name = "Currencies";
		function i18n_plural_name() { return _t("EcommerceCurrency.CURRENCIES", "Currencies");}

	//defaults
	public static $default_sort = "\"InUse\" DESC, \"Code\" ASC, \"Name\" ASC";

	public static $defaults = array(
		"InUse" => true
	);

	/**
	 *
	 * @return Int - the ID of the currency
	 */
	public static function default_currency_id() {
		$currency = DataObject::get_one("EcommerceCurrency", "\"Code\"  = '".Payment::site_currency()."' AND \"InUse\" = 1");
		if($currency) {
			return $currency->ID;
		}
		return 0;
	}

	/**
	 * Only returns a currency when it is a valid currency.
	 * @param String $currencyCode - the code of the currency
	 * @return EcommerceCurrency | Null
	 */
	public static function get_currency_from_code($currencyCode) {
		$currency = DataObject::get_one("EcommerceCurrency", "\"Code\"  = '$currencyCode' AND \"InUse\" = 1");
		if($currency) {
			return $currency;
		}
		return null;
	}

	/**
	 * casted variable method
	 */
	public function IsDefault(){ return $this->getIsDefault();}
	public function getIsDefault(){
		if(!$this->Code) {
			user_error("This currency (ID = ".$this->ID.") does not have a code ");
		}
		return strtolower($this->Code) ==  Payment::site_currency();
	}

	public function ExchangeRate(){ return $this->getExchangeRate();}
	public function getExchangeRate(){
		$className = EcommerceConfig::get("EcommerceCurrency", "exchange_provider_class");
		$obj = new ExchangeRateProvider();
		return $obj->ExchangeRate($from = Payment::site_currency(), $to = $this->Code);
	}

	/**
	 * Standard SS Method
	 * Adds the default currency
	 */
	public function populateDefaults() {
		parent::populateDefaults();
		$this->InUse = true;
	}

	/**
	 * Standard SS Method
	 * Adds the default currency
	 */
	function requireDefaultRecords(){
		parent::requireDefaultRecords();
		$defaultCurrencyCode = Payment::site_currency();
		if(!DataObject::get("EcommerceCurrency", "\"Code\" = '$defaultCurrencyCode'")) {
			$obj = new EcommerceCurrency();
			$obj->Code = $defaultCurrencyCode;
			$obj->Name = $defaultCurrencyCode;
			$obj->InUse = true;
		}
	}

	/**
	 * Debug helper method.
	 * Can be called from /shoppingcart/debug/
	 * @return String
	 */
	public function debug() {
		$html =  "
			<h2>".$this->ClassName."</h2><ul>";
		$fields = Object::get_static($this->ClassName, "db");
		foreach($fields as  $key => $type) {
			$html .= "<li><b>$key ($type):</b> ".$this->$key."</li>";
		}
		$fields = Object::get_static($this->ClassName, "casting");
		foreach($fields as  $key => $type) {
			$method = "get".$key;
			$html .= "<li><b>$key ($type):</b> ".$this->$method()." </li>";
		}
		$html .= "</ul>";
		return $html;
	}



}






