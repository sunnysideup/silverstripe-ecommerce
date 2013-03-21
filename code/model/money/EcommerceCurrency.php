<?php


/**
 * Object to manage currencies
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: money
 **/

class EcommerceCurrency extends DataObject {

	/**
	 * standard SS variable
	 * @var Array
	 */
	public static $db = array(
		"Code" => "Varchar(5)",
		"Name" => "Varchar(100)",
		"InUse" => "Boolean"
	);

	/**
	 * standard SS variable
	 * @var Array
	 */
	public static $indexes = array(
		"Code" => true,
	);

	/**
	 * standard SS variable
	 * @var Array
	 */
	public static $casting = array(
		"IsDefault" => "Boolean",
		"IsDefaultNice" => "Varchar",
		"InUseNice" => "Varchar",
		"ExchangeRate" => "Double"
	);

	/**
	 * standard SS variable
	 * @var Array
	 */
	public static $searchable_fields = array(
		"Code" => "PartialMatchFilter",
		"Name" => "PartialMatchFilter"
	);

	/**
	 * standard SS variable
	 * @var Array
	 */
	public static $field_labels = array(
		"Code" => "Short Code (e.g. NZD)",
		"Name" => "Name (e.g. New Zealand Dollar)",
		"InUse" => "It is available for use?",
		"ExchangeRate" => "Exchange Rate",
		"ExchangeRateExplanation" => "Exchange Rate explanation",
		"IsDefaultNice" => "Is default currency for site"
	);

	/**
	 * standard SS variable
	 * @var Array
	 */
	public static $summary_fields = array(
		"Code" => "Code",
		"Name" => "Name",
		"InUseNice" => "Available",
		"IsDefaultNice" => "Default Currency",
		"ExchangeRate" => "Exchange Rate"
	); //note no => for relational fields

	/**
	 * standard SS variable
	 * @var String
	 */
	public static $singular_name = "Currency";
		function i18n_singular_name() { return _t("EcommerceCurrency.CURRENCY", "Currency");}

	/**
	 * standard SS variable
	 * @var String
	 */
	public static $plural_name = "Currencies";
		function i18n_plural_name() { return _t("EcommerceCurrency.CURRENCIES", "Currencies");}

	/**
	 * standard SS variable
	 * @var String
	 */
	public static $default_sort = "\"InUse\" DESC, \"Name\" ASC, \"Code\" ASC";

	/**
	 * standard SS variable
	 * @var Array
	 */
	public static $defaults = array(
		"InUse" => true
	);

	/**
	 * NOTE: when there is only one currency we return NULL as one currency is meaningless.
	 * @return DataObjectSet (EcommerceCurrency list) | Null
	 */
	public static function ecommerce_currency_list(){
		$dos = DataObject::get(
			"EcommerceCurrency",
			"\"InUse\" = 1",
			"IF(\"Code\" = '".Payment::site_currency()."', 0, 1) ASC, \"InUse\" DESC, \"NAME\" ASC, \"Code\" ASC"
		);
		if($dos) {
			if(1 == $dos->count()) {
				$dos = null;
			}
		}
		return $dos;
	}

	/**
	 * @param Float $price
	 * @param Order $order
	 * @param String $name
	 * @param Boolean $forceCreation - set true to always return Money Object
	 * @return EcommerceMoney | Null
	 */
	public static function display_price($price, $order = null, $name = "DisplayPrice", $forceCreation = false){
		if(!$order) {
			$order = ShoppingCart::current_order();
		}
		if($order) {
			if($order->HasAlternativeCurrency()) {
				$exchangeRate = $order->ExchangeRate;
				if($exchangeRate && $exchangeRate != 1) {
					$currency = $order->CurrencyUsed();
					if($currency) {
						$newPrice = $exchangeRate * $price;
						if($newPrice) {
							$ecommerceMoneyObject = new Money($name);
							$ecommerceMoneyObject->SetAmount($newPrice);
							$ecommerceMoneyObject->SetCurrency($currency->Code);
							return $ecommerceMoneyObject;
						}
					}
				}
			}
		}
		if($forceCreation){
			return DBField::create(
				'Money',
				array(
					"Amount" => $price,
					"Currency" => Payment::site_currency()
				)
			);
		}
	}


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
		return DataObject::get_one("EcommerceCurrency", "\"Code\"  = '$currencyCode' AND \"InUse\" = 1");
	}


	/**
	 * STANDARD SILVERSTRIPE STUFF
	 **/
	function getCMSFields(){
		$fields = parent::getCMSFields();
		$fieldLabels = $this->fieldLabels();
		$fields->addFieldToTab("Root.Main", new ReadonlyField("IsDefaulNice", $fieldLabels["IsDefaultNice"], $this->getIsDefaultNice()));
		if(!$this->isDefault()) {
			$fields->addFieldToTab("Root.Main", new ReadonlyField("ExchangeRate", $fieldLabels["ExchangeRate"], $this->ExchangeRate()));
			$fields->addFieldToTab("Root.Main", new ReadonlyField("ExchangeRateExplanation", $fieldLabels["ExchangeRateExplanation"], $this->ExchangeRateExplanation()));
		}
		return $fields;
	}

	/**
	 * casted variable method
	 */
	public function IsDefault(){return $this->getIsDefault();}
	public function getIsDefault(){
		if($this->ID && !$this->Code) {
			user_error("The currency #$this->ID does not have a code");
		}
		return strtolower($this->Code) ==  strtolower(Payment::site_currency());
	}

	/**
	 * casted variable method
	 * @return String
	 */
	public function IsDefaultNice(){ return $this->getIsDefaultNice();}
	public function getIsDefaultNice(){
		if($this->getIsDefault()) {
			return _t("EcommerceCurrency.YES", "Yes");
		}
		else {
			return _t("EcommerceCurrency.NO", "No");
		}
	}

	/**
	 * casted variable method
	 * @return String
	 */
	public function InUseNice(){ return $this->getInUseNice();}
	public function getInUseNice(){
		if($this->InUse) {
			return _t("EcommerceCurrency.YES", "Yes");
		}
		else {
			return _t("EcommerceCurrency.NO", "No");
		}
	}

	/**
	 * casted variable
	 * @return Double
	 */
	public function ExchangeRate(){ return $this->getExchangeRate();}
	public function getExchangeRate(){
		$className = EcommerceConfig::get("EcommerceCurrency", "exchange_provider_class");
		$obj = new ExchangeRateProvider();
		return $obj->ExchangeRate( Payment::site_currency(), $this->Code);
	}

	/**
	 * casted variable
	 * @return Double
	 */
	public function ExchangeRateExplanation(){ return $this->getExchangeRateExplanation();}
	public function getExchangeRateExplanation(){
		$string = "1 ".Payment::site_currency()." = ".round($this->getExchangeRate(), 3)." ".$this->Code;
		$string .= ", 1 ".$this->Code." = ".round(1 / $this->getExchangeRate(), 3)." ".Payment::site_currency();
		return $string;
	}

	/**
	 * @return Boolean
	 */
	public function IsCurrent(){
		$currentOrder = ShoppingCart::current_order();
		if($currentOrder) {
			if($currentOrder->CurrencyUsedID == $this->ID) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Returns the link that can be used in the shopping cart to
	 * set the preferred currency to this one.
	 * For example: /shoppingcart/setcurrency/nzd/
	 * @return String
	 */
	public function Link() {
		return ShoppingCart_Controller::set_currency_link($this->Code);
	}

	/**
	 * returns the link type
	 * @return String (link | default | current)
	 */
	public function LinkingMode() {
		$linkingMode = "";
		if($this->IsDefault()) {
			$linkingMode .= " default";
		}
		if($this->IsCurrent()) {
			$linkingMode .= " current";
		}
		else {
			$linkingMode .= " link";
		}
		return $linkingMode;
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
		if(!DataObject::get_one("EcommerceCurrency", "\"Code\" = '$defaultCurrencyCode'")) {
			$obj = new EcommerceCurrency();
			$obj->Code = $defaultCurrencyCode;
			$obj->Name = $defaultCurrencyCode;
			$obj->InUse = 1;
			$obj->write();
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

	/**
	 * returns a list of currencies
	 * "Code" => Name
	 * @var array
	 */
	public function getCurrencyList(){
		return $this->currencyList;
	}

	/**
	 * list of currencies
	 * @var Array
	 */
	protected $currencyList = array(
		"afa" => "afghanistan afghanis",
		"all" => "albania leke",
		"dzd" => "algeria dinars",
		"ars" => "argentina pesos",
		"aud" => "australia dollars",
		"ats" => "austria schillings*",
		"bsd" => "bahamas dollars",
		"bhd" => "bahrain dinars",
		"bdt" => "bangladesh taka",
		"bbd" => "barbados dollars",
		"bef" => "belgium francs*",
		"bmd" => "bermuda dollars",
		"brl" => "brazil reais",
		"bgn" => "bulgaria leva",
		"cad" => "canada dollars",
		"xof" => "cfa bceao francs",
		"xaf" => "cfa beac francs",
		"clp" => "chile pesos",
		"cny" => "china yuan renminbi",
		"cop" => "colombia pesos",
		"crc" => "costa rica colones",
		"hrk" => "croatia kuna",
		"cyp" => "cyprus pounds",
		"czk" => "czech republic koruny",
		"dkk" => "denmark kroner",
		"dem" => "deutsche (germany) marks*",
		"dop" => "dominican republic pesos",
		"nlg" => "dutch (netherlands) guilders*",
		"xcd" => "eastern caribbean dollars",
		"egp" => "egypt pounds",
		"eek" => "estonia krooni",
		"eur" => "euro",
		"fjd" => "fiji dollars",
		"fim" => "finland markkaa*",
		"frf" => "france francs*",
		"dem" => "germany deutsche marks*",
		"xau" => "gold ounces",
		"grd" => "greece drachmae*",
		"nlg" => "holland (netherlands) guilders*",
		"hkd" => "hong kong dollars",
		"huf" => "hungary forint",
		"isk" => "iceland kronur",
		"xdr" => "imf special drawing right",
		"inr" => "india rupees",
		"idr" => "indonesia rupiahs",
		"irr" => "iran rials",
		"iqd" => "iraq dinars",
		"iep" => "ireland pounds*",
		"ils" => "israel new shekels",
		"itl" => "italy lire*",
		"jmd" => "jamaica dollars",
		"jpy" => "japan yen",
		"jod" => "jordan dinars",
		"kes" => "kenya shillings",
		"krw" => "korea (south) won",
		"kwd" => "kuwait dinars",
		"lbp" => "lebanon pounds",
		"luf" => "luxembourg francs*",
		"myr" => "malaysia ringgits",
		"mtl" => "malta liri",
		"mur" => "mauritius rupees",
		"mxn" => "mexico pesos",
		"mad" => "morocco dirhams",
		"nlg" => "netherlands guilders*",
		"nzd" => "new zealand dollars",
		"nok" => "norway kroner",
		"omr" => "oman rials",
		"pkr" => "pakistan rupees",
		"xpd" => "palladium ounces",
		"pen" => "peru nuevos soles",
		"php" => "philippines pesos",
		"xpt" => "platinum ounces",
		"pln" => "poland zlotych",
		"pte" => "portugal escudos*",
		"qar" => "qatar riyals",
		"rol" => "romania lei",
		"rub" => "russia rubles",
		"sar" => "saudi arabia riyals",
		"xag" => "silver ounces",
		"sgd" => "singapore dollars",
		"skk" => "slovakia koruny",
		"sit" => "slovenia tolars",
		"zar" => "south africa rand",
		"krw" => "south korea won",
		"esp" => "spain pesetas*",
		"xdr" => "special drawing rights (imf)",
		"lkr" => "sri lanka rupees",
		"sdd" => "sudan dinars",
		"sek" => "sweden kronor",
		"chf" => "switzerland francs",
		"twd" => "taiwan new dollars",
		"thb" => "thailand baht",
		"ttd" => "trinidad and tobago dollars",
		"tnd" => "tunisia dinars",
		"try" => "turkey new lira",
		"trl" => "turkey lira*",
		"aed" => "united arab emirates dirhams",
		"gbp" => "united kingdom pounds",
		"usd" => "united states dollars",
		"veb" => "venezuela bolivares",
		"vnd" => "vietnam dong",
		"zmk" => "zambia kwacha"
	);


}






