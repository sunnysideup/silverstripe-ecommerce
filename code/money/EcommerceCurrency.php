<?php


/**
 * Object to manage currencies
 *
 *
 **/


class EcommerceCurrency extends DataObject {

	static $db = array(
		"Code" => "Varchar(5)",
		"Name" => "Varchar(100)",
		"InUse" => "Boolean",
	);

	public static $has_one = array(
		"EcommerceCurrencyFormat" => "EcommerceCurrencyFormat"
	);

	static $indexes = array(
		"Code" => true,
	);

	static $casting = array(
		"IsDefault" => "Boolean",
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
	public static $default_sort = "InUse DESC, Code ASC, Name ASC";

	public static $defaults = array(
		"InUse" => true
	);

	/**
	 * casted variable method
	 */
	function IsDefault(){ return $this->getIsDefault();}
	function getIsDefault(){
		return strtolower($this->code) ==  Payment::site_currency();
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






