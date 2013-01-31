<?php

/**
 * @description: This class helps you to manage regions within the context of e-commerce.
 * The regions can be states (e.g. we only sell within New York and Penn State), suburbs (pizza delivery place),
 * or whatever other geographical borders you are using.
 * Each region has one country, so a region can not span more than one country.
 *
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: address
 * @inspiration: Silverstripe Ltd, Jeremy
 **/

class EcommerceRegion extends DataObject {

	/**
	 * what variables are accessible through  http://mysite.com/api/ecommerce/v1/EcommerceRegion/
	 * @var array
	 */
	public static $api_access = array(
		'view' => array(
				"Code",
				"Name"
			)
	 );

	/**
	 * standard SS variable
	 * @var Array
	 */
	static $db = array(
		"Code" => "Varchar(20)",
		"Name" => "Varchar(200)",
		"DoNotAllowSales" => "Boolean"
	);

	/**
	 * standard SS variable
	 * @var Array
	 */
	static $has_one = array(
		"Country" => "EcommerceCountry"
	);

	/**
	 * standard SS variable
	 * @var String
	 */
	static $indexes = array(
		"Code" => true
	);

	/**
	 * standard SS variable
	 * @var String
	 */
	static $default_sort = "\"Name\" ASC";

	/**
	 * standard SS variable
	 * @var String
	 */
	public static $singular_name = "Region";
		function i18n_singular_name() { return _t("EcommerceRegion.REGION", "Region");}

	/**
	 * standard SS variable
	 * @var String
	 */
	public static $plural_name = "Regions";
		function i18n_plural_name() { return _t("EcommerceRegion.REGIONS", "Regions");}

	/**
	 * standard SS variable
	 * @var Array
	 */
	public static $searchable_fields = array(
		"Name" => "PartialMatchFilter",
		"Code" => "PartialMatchFilter"
	);

	/**
	 * standard SS variable
	 * @var Array
	 */
	public static $field_labels = array(
		"Name" => "Region"
	);

	/**
	 * standard SS variable
	 * @var Array
	 */
	public static $summary_fields = array(
		"Name" => "Name",
		"Country.Title"
	);


	/**
	 * do we use regions at all in this ecommerce application?
	 * @return Bool
	 **/
	public static function show() {
		return DataObject::get_one("EcommerceRegion") ? true : false;
	}

	/**
	 * Standard SS method
	 * @return FieldSet
	 **/
	function getCMSFields() {
		$fields = parent::getCMSFields();
		//$fields->replaceField("CountryID", new DropdownField("CountryID", EcommerceCountry::i18n_singular_name(), "EcommerceCountry"));
		return $fields;
	}

	/**
	 * checks if a code is allowed
	 * @param String $code - e.g. NZ, NSW, or CO
	 * @return Boolean
	 */
	public static function code_allowed($code) {
		return array_key_exists($code, self::list_of_allowed_entries_for_dropdown());
	}

	/**
	 * converts a code into a proper title
	 * @param $code String (Code)
	 * @return String ( name)
	 */
	public static function find_title($code) {
		$options = self::get_default_array();
		// check if code was provided, and is found in the country array
		if($options && isset($options[$code])) {
			return $options[$code];
		}
		else {
			return "";
		}
	}

	/**
	 * This function returns back the default list of regions, filtered by the currently selected country.
	 * @return Array - array of CountryCode => Country
	 **/
	protected static function get_default_array() {
		$defaultArray = array();
		$regions = DataObject::get("EcommerceRegion", "\"DoNotAllowSales\" <> 1 AND \"CountryID\"  = '".EcommerceCountry::get_country_id()."'");
		if($regions) {
			foreach($regions as $region) {
				$defaultArray[$region->Code] = $region->Name;
			}
		}
		return $defaultArray;
	}

	// DYNAMIC LIMITS.....

	/**
	 * takes the defaultArray and limits it with "only show" and "do not show" value, relevant for the current order.
	 * @return Array (Code, Title)
	 **/
	public static function list_of_allowed_entries_for_dropdown() {
		$defaultArray = self::get_default_array();
		$onlyShow = self::get_for_current_order_only_show_regions();
		$doNotShow = self::get_for_current_order_do_not_show();
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
		return $defaultArray;
	}


	/**
	 * these variables and methods allow to to "dynamically limit the regions available, based on, for example: ordermodifiers, item selection, etc....
	 * for example, if hot delivery of a catering item is only available in a certain region, then the regions can be limited with the methods below.
	 * NOTE: these methods / variables below are IMPORTANT, because they allow the dropdown for the region to be limited for just that order
	 * @var Array of regions codes, e.g. ("NSW", "WA", "VIC");
	**/
	protected static $for_current_order_only_show_regions = array();
		static function set_for_current_order_only_show_regions($a) {
			if(count(self::$for_current_order_only_show_regions)) {
				//we INTERSECT here so that only countries allowed by all forces (modifiers) are added.
				self::$for_current_order_only_show_regions = array_intersect($a, self::$for_current_order_only_show_regions);
			}
			else {
				self::$for_current_order_only_show_regions = $a;
			}
		}
		//NOTE: this method below is more generic (does not have _regions part)
		//so that it can be used by a method that is shared between EcommerceCountry and EcommerceRegion
		static function get_for_current_order_only_show_regions() {return self::$for_current_order_only_show_regions;}

	protected static $for_current_order_do_not_show_regions = array();
		static function set_for_current_order_do_not_show_regions($a) {
			//We MERGE here because several modifiers may limit the countries
			self::$for_current_order_do_not_show_regions = array_merge($a, self::$for_current_order_do_not_show_regions);
		}
		static function get_for_current_order_do_not_show() {return self::$for_current_order_do_not_show_regions;}


	/**
	 * This function works out the most likely region for the current order
	 * @return Int
	 **/
	public static function get_region_id() {
		$regionID = 0;
		if($order = ShoppingCart::current_order()) {
			if($region = $order->Region()) {
				$regionID = $region->ID;
			}
		}
		//3. check GEOIP information
		if(!$regionID) {
			$regionArray = self::list_of_allowed_entries_for_dropdown();
			if(is_array($regionArray) && count($regionArray)) {
				foreach($regionArray as $regionCode => $regionName) {
					//we stop at the first one... as we have no idea which one is the best.
					if($region = DataObject::get_one("EcommerceRegion", "\"Code\" = '$regionCode'")) {
						$regionID = $region->ID;
					}
					break;
				}
			}
		}
		return $regionID;
	}

	/**
	 * @alias for get_region_id
	 */
	public static function get_region() {
		return self::get_region_id();
	}


}

