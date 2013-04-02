<?php

/**
 * @description: This class helps you to manage countries within the context of e-commerce.
 * For example: To what countries can be sold.
 * /dev/build/?resetecommercecountries=1 will reset the list of countries...
 *
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: address
 * @inspiration: Silverstripe Ltd, Jeremy
 **/


class EcommerceCountry extends DataObject {

	/**
	 * what variables are accessible through  http://mysite.com/api/ecommerce/v1/EcommerceCountry/
	 * @var array
	 */
	public static $api_access = array(
		'view' => array(
				"Code",
				"Name"
			)
	 );
	/**
	 * Standard SS Variable
	 * @var Array
	 **/
	static $db = array(
		"Code" => "Varchar(20)",
		"Name" => "Varchar(200)",
		"DoNotAllowSales" => "Boolean"
	);

	/**
	 * Standard SS Variable
	 * @var Array
	 **/
	static $has_many = array(
		"Regions" => "EcommerceRegion"
	);

	/**
	 * Standard SS Variable
	 * @var Array
	 **/
	static $indexes = array(
		"Code" => true,
		"DoNotAllowSales" => true
	);

	/**
	 * standard SS variable
	 * @var Array
	 */
	public static $summary_fields = array(
		"Code" => "Code",
		"Name" => "Name",
		"AllowSalesNice" => "Allow Sales"
	);

	/**
	 * standard SS variable
	 * @var Array
	 */
	public static $casting = array(
		"AllowSales" => "Boolean",
		"AllowSalesNice" => "Varchar"
	);

	/**
	 * STANDARD SILVERSTRIPE STUFF
	 * @todo: how to translate this?
	 **/
	public static $searchable_fields = array(
		'Code' => "PartialMatchFilter",
		'Name' => "PartialMatchFilter",
		'DoNotAllowSales' => array(
			'field' => 'CheckboxField',
			'filter' => 'EcommerceCountryFilters_AllowSales',
			'title' => 'Allow Sales'
		)
	);

	/**
	 * Standard SS Variable
	 * @var String
	 **/
	static $default_sort = "\"DoNotAllowSales\" ASC, \"Name\" ASC";

	/**
	 * Standard SS Variable
	 * @var String
	 **/
	public static $singular_name = "Country";
		function i18n_singular_name() { return _t("EcommerceCountry.COUNTRY", "Country");}

	/**
	 * Standard SS Variable
	 * @var String
	 **/
	public static $plural_name = "Countries";
		function i18n_plural_name() { return _t("EcommerceCountry.COUNTRIES", "Countries");}

	/**
	 * return false or true
	 * @ return Boolean
	 */
	function canCreate($member = null){
		if(!$member) {
			$member = Member::currentMember();
			return $member->IsShopAdmin ? true : false;
		}
	}

	/**
	 * return false or true
	 * @ return Boolean
	 */
	function canDelete($member = null){
		return false;
	}

		/**
	 * This function exists as a shortcut.
	 * If there is only ONE allowed country code
	 * then a lot of checking of countries can be avoided.
	 * @return String - countrycode
	 **/
	public static function get_fixed_country_code() {
		$a = EcommerceConfig::get("EcommerceCountry", "allowed_country_codes");
		if(is_array($a) && count($a) == 1) {
			return array_shift($a);
		}
		return "";
	}

	/**
	 * returns the country name from a code
	 * @paramString $code  (Code)
	 * @return String ( name)
	 **/
	public static function find_title($code) {
		$options = Geoip::getCountryDropDown();
		// check if code was provided, and is found in the country array
		if(isset($options[$code])) {
			return $options[$code];
		}
		else {
			return "";
		}
	}

	/**
	 * Memory for the customer's country.
	 * @var Null | String
	 */
	protected static $get_country_cache = null;
		public static function reset_get_country_cache() {self::$get_country_cache = null;}

	/**
	 * This function works out the most likely country for the current order.
	 * @return String - Country Code - e.g. NZ
	 **/
	public static function get_country() {
		if(self::$get_country_cache === null) {
			$countryCode = '';
			//1. fixed country is first
			$countryCode = self::get_fixed_country_code();
			if(!$countryCode) {
				//2. check shipping address
				if($o = ShoppingCart::current_order()) {
					$countryCode = $o->Country();
				}
				//3. check GEOIP information
				if(!$countryCode) {
					$countryCode = @Geoip::visitor_country();
					//4 check default country set in GEO IP....
					if(!$countryCode) {
						$countryCode = Geoip::$default_country_code;
						//5. take the FIRST country from the get_allowed_country_codes
						if(!$countryCode) {
							$countryArray = self::list_of_allowed_entries_for_dropdown();
							if(is_array($countryArray) && count($countryArray)) {
								foreach($countryArray as $countryCode => $countryName) {
									//we stop at the first one... as we have no idea which one is the best.
									break;
								}
							}
						}
					}
				}
			}
			self::$get_country_cache = $countryCode;
		}
		return self::$get_country_cache;
	}

	/**
	 * Memory for allow country to check
	 * @var Null | Boolean
	 */
	protected static $allow_sales_cache = null;
		public static function reset_allow_sales_cache() {self::$allow_sales_cache = null;}


	/**
	 * Checks if we are allowed to sell to this person.
	 * @return Boolean
	 */
	public static function allow_sales() {
		if(self::$allow_sales_cache === null) {
			self::$allow_sales_cache = true;
			$countryCode = @Geoip::visitor_country();
			if($countryCode) {
				if(DataObject::get_one("EcommerceCountry", "\"DoNotAllowSales\" = 1 AND \"Code\" = '$countryCode'")) {
					self::$allow_sales_cache = false;
				}
			}
		}
		return self::$allow_sales_cache;
	}

	/**
	 * returns the ID of the country.
	 * @param String
	 * @return Int
	 **/
	public static function get_country_id($countryCode = "") {
		if(!$countryCode) {
			$countryCode = self::get_country();
		}
		$country = DataObject::get_one("EcommerceCountry", "\"Code\" = '$countryCode'");
		if($country) {
			return $country->ID;
		}
		return 0;
	}

	/**
	 * returns an array of Codes => Names of all countries that can be used.
	 * Use "list_of_allowed_entries_for_dropdown" to get the list.
	 * @return Array
	 **/
	protected static function get_default_array() {
		$defaultArray = array();
		$countries = null;
		if($code = self::get_fixed_country_code()) {
			$defaultArray[$code] = self::find_title($code);
			return $defaultArray;
		}
		$countries = DataObject::get("EcommerceCountry", "DoNotAllowSales <> 1");
		if($countries) {
			foreach($countries as $country) {
				$defaultArray[$country->Code] = $country->Name;
			}
		}
		return $defaultArray;
	}

	/**
	 * Standar SS method
	 * @return FieldSet
	 **/
	function getCMSFields() {
		$fields = parent::getCMSFields();
		return $fields;
	}

	/**
	 *
	 * standard SS method
	 */
	function requireDefaultRecords() {
		parent::requireDefaultRecords();
		if(!DataObject::get("EcommerceCountry") || isset($_REQUEST["resetecommercecountries"])) {
			$task = new EcommerceCountryAndRegionTasks();
			$task->run(null);
		}
	}

	//DYNAMIC LIMITATIONS

	/**
	 * these variables and methods allow to "dynamically limit the countries available,
	 * based on, for example: ordermodifiers, item selection, etc....
	 * for example, if a person chooses delivery within Australasia (with modifier) -
	 * then you can limit the countries available to "Australasian" countries
	 */

	/**
	 * List of countries that should be shown
	 * @param Array $a: should be country codes e.g. array("NZ", "NP", "AU");
	 * @var Array
	 */
	protected static $for_current_order_only_show_countries = array();
		static function set_for_current_order_only_show_countries($a) {
			if(count(self::$for_current_order_only_show_countries)) {
				//we INTERSECT here so that only countries allowed by all forces (modifiers) are added.
				self::$for_current_order_only_show_countries = array_intersect($a, self::$for_current_order_only_show_countries);
			}
			else {
				self::$for_current_order_only_show_countries = $a;
			}
		}
		static function get_for_current_order_only_show_countries() {return self::$for_current_order_only_show_countries;}

	/**
	 * List of countries that should NOT be shown
	 * @param Array $a: should be country codes e.g. array("NZ", "NP", "AU");
	 * @var Array
	 */
	protected static $for_current_order_do_not_show_countries = array();
		static function set_for_current_order_do_not_show_countries($a) {
			//We MERGE here because several modifiers may limit the countries
			self::$for_current_order_do_not_show_countries = array_merge($a, self::$for_current_order_do_not_show_countries);
		}
		static function get_for_current_order_do_not_show_countries() {return self::$for_current_order_do_not_show_countries;}

	/**
	 *
	 * @var Array
	 */
	private static $list_of_allowed_entries_for_dropdown_array = array();

	/**
	 * takes the defaultArray and limits it with "only show" and "do not show" value, relevant for the current order.
	 * @return Array (Code, Title)
	 **/
	public static function list_of_allowed_entries_for_dropdown() {
		if(!self::$list_of_allowed_entries_for_dropdown_array){
			$defaultArray = self::get_default_array();
			$onlyShow = self::get_for_current_order_only_show_countries();
			$doNotShow = self::get_for_current_order_do_not_show_countries();
			if(is_array($onlyShow) && count($onlyShow)) {
				foreach($defaultArray as $key => $value) {
					if(!in_array($key, $onlyShow)) {
						unset($defaultArray[$key]);
					}
				}
			}
			if(is_array($doNotShow) && count($doNotShow)) {
				foreach($doNotShow as $code) {
					if(isset($defaultArray[$code])) {
						unset($defaultArray[$code]);
					}
				}
			}
			self::$list_of_allowed_entries_for_dropdown_array = $defaultArray;
		}
		return self::$list_of_allowed_entries_for_dropdown_array;
	}

	/**
	 *checks if a code is allowed
	 *@param String $code - e.g. NZ, NSW, or CO
	 *@return Boolean
	 **/
	public static function code_allowed($code) {
		return array_key_exists($code, self::list_of_allowed_entries_for_dropdown());
	}

	/**
	 * Casted variable to show if sales are allowed to this country.
	 * @return Boolean
	 */
	public function AllowSales(){return $this->getAllowSales();}
	public function getAllowSales(){
		if($this->DoNotAllowSales) {
			return false;
		}
		else {
			return true;
		}
	}

	/**
	 * Casted variable to show if sales are allowed to this country.
	 * @return String
	 */
	public function AllowSalesNice(){return $this->getAllowSalesNice();}
	public function getAllowSalesNice(){
		if($this->AllowSales()) {
			return _t("EcommerceCountry.YES", "Yes");
		}
		else {
			return _t("EcommerceCountry.NO", "No");
		}
	}

}

