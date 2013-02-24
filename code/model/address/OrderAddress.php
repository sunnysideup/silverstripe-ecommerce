<?php


/**
 * @description: each order has an address: a Shipping and a Billing address
 * This is a base-class for both.
 *
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: address
 * @inspiration: Silverstripe Ltd, Jeremy
 **/

class OrderAddress extends DataObject {


	/**
	 * standard SS static definition
	 */
	public static $singular_name = "Order Address";
		function i18n_singular_name() { return _t("OrderAddress.ORDERADDRESS", "Order Address");}


	/**
	 * standard SS static definition
	 */
	public static $plural_name = "Order Addresses";
		function i18n_plural_name() { return _t("OrderAddress.ORDERADDRESSES", "Order Addresses");}


	/**
	 * standard SS static definition
	 */
	public static $casting = array(
		"FullName" => "Text",
		"FullString" => "Text",
		"JSONData" => "Text"
	);

	/**
	 * returns the id of the MAIN country field for template manipulation.
	 * Main means the one that is used as the primary one (e.g. for tax purposes).
	 * @see EcommerceConfig::get("OrderAddress", "use_shipping_address_for_main_region_and_country")
	 * @return String
	 */
	public static function get_country_field_ID() {
		if(EcommerceConfig::get("OrderAddress", "use_shipping_address_for_main_region_and_country")) {
			return "ShippingCountry";
		}
		else {
			return "Country";
		}
	}

	/**
	 * returns the id of the MAIN region field for template manipulation.
	 * Main means the one that is used as the primary one (e.g. for tax purposes).
	 * @return String
	 */
	public static function get_region_field_ID() {
		if(EcommerceConfig::get("OrderAddress", "use_shipping_address_for_main_region_and_country")) {
			return "ShippingRegion";
		}
		else {
			return "Region";
		}
	}


	/**
	 * There might be times when a modifier needs to make an address field read-only.
	 * In that case, this is done here.
	 *
	 * @var Array
	 */
	protected $readOnlyFields = array();

	/**
	 * sets a field to readonly state
	 * we use this when modifiers have been set that require a field to be a certain value
	 * for example - a PostalCode field maybe set in the modifier.
	 * @param String $fieldName
	 */
	function addReadOnlyField($fieldName) {
		$this->readOnlyFields[$fieldName] = $fieldName;
	}

	/**
	 * removes a field from the readonly state
	 * @param String $fieldName
	 */
	function removeReadOnlyField($fieldName) {
		unset($this->readOnlyFields[$fieldName]);
	}

	/**
	 * save edit status for speed's sake
	 * @var Boolean
	 */
	protected $_canEdit = null;

	/**
	 * save view status for speed's sake
	 * @var Boolean
	 */
	protected $_canView = null;


	/**
	 * standard SS method
	 * @return Boolean
	 **/
	function canCreate($member = null) {
		return true;
	}

	/**
	 * Standard SS method
	 * This is an important method.
	 *
	 * @return Boolean
	 **/
	function canView($member = null) {
		if($this->_canView === null) {
			$this->_canView = false;
			if($this->Order()) {
				if($this->Order()->exists()) {
					if($this->Order()->canView($member)) {
						$this->_canView = true;
					}
				}
			}
		}
		return $this->_canView;
	}

	/**
	 * Standard SS method
	 * This is an important method.
	 *
	 * @return Boolean
	 **/
	function canEdit($member = null) {
		if($this->_canEdit === null) {
			$this->_canEdit = false;
			if($this->Order()) {
				if($this->Order()->exists()) {
					if($this->Order()->canEdit($member)) {
						$this->_canEdit = true;
					}
				}
			}
		}
		return $this->_canEdit;
	}

	/**
	 *
	 *@return FieldSet
	 **/
	function scaffoldSearchFields(){
		$fields = parent::scaffoldSearchFields();
		$fields->replaceField("OrderID", new NumericField("OrderID", "Order Number"));
		return $fields;
	}


	/**
	 *
	 *
	 * @return FieldSet
	 */

	protected function getEcommerceFields() {
		return new FieldSet();
	}
	/**
	 * put together a textfield for a postal code field
	 * @param String $name - name of the field
	 * @return TextField
	 **/
	protected function getPostalCodeField($name) {
		$field = new TextField($name, _t('OrderAddress.POSTALCODE','Postal Code'));
		$postalCodeURL = EcommerceDBConfig::current_ecommerce_db_config()->PostalCodeURL;
		$postalCodeLabel = EcommerceDBConfig::current_ecommerce_db_config()->PostalCodeLabel;
		if($postalCodeURL && $postalCodeLabel){
			$prefix = EcommerceConfig::get("OrderAddress", "field_class_and_id_prefix");
			$field->setRightTitle('<a href="'.$postalCodeURL.'" id="'.$prefix.$name.'Link" class="'.$prefix.'postalCodeLink">'.$postalCodeLabel.'</a>');
		}
		return $field;
	}

	/**
	 * put together a dropdown for the region field
	 * @param String $name - name of the field
	 * @return DropdownField
	 **/
	protected function getRegionField($name) {
		if(EcommerceRegion::show()) {
			$regionsForDropdown = EcommerceRegion::list_of_allowed_entries_for_dropdown();
			$count = count($regionsForDropdown);
			if($count< 1) {
					$regionField = new HiddenField($name, '', 0);
			}
			else {
				$regionField = new DropdownField($name,EcommerceRegion::i18n_singular_name(), $regionsForDropdown);
				if($count < 2) {
					$regionField = $regionField->performReadonlyTransformation();
				}
				else {
					$regionField->setEmptyString(_t("OrderAdress.PLEASE_SELECT_REGION", "--- Select Region ---"));
				}
			}
		}
		else {
			//adding region field here as hidden field to make the code easier below...
			$regionField = new HiddenField($name, '', 0);
		}
		$prefix = EcommerceConfig::get("OrderAddress", "field_class_and_id_prefix");
		$regionField->addExtraClass($prefix.'ajaxRegionField');
		return $regionField;
	}

	/**
	 * put together a dropdown for the country field
	 * @param String $name - name of the field
	 * @return DropdownField
	 **/
	protected function getCountryField($name) {
		$countriesForDropdown = EcommerceCountry::list_of_allowed_entries_for_dropdown();
		$countryField = new DropdownField($name, EcommerceCountry::i18n_singular_name(), $countriesForDropdown, EcommerceCountry::get_country());
		if(count($countriesForDropdown) < 2) {
			$countryField = $countryField->performReadonlyTransformation();
			if(count($countriesForDropdown) < 1) {
				$countryField = new HiddenField($name, '', "not available");
			}
		}
		$prefix = EcommerceConfig::get("OrderAddress", "field_class_and_id_prefix");
		$countryField->addExtraClass($prefix.'ajaxCountryField');
		return $countryField;
	}

	/**
	 * makes selected fields into read only using the $this->readOnlyFields array
	 *
	 * @param Object(FieldSet)
	 */
	protected function makeSelectedFieldsReadOnly(&$fields) {
		$this->extend("augmentMakeSelectedFieldsReadOnly");
		if(is_array($this->readOnlyFields) && count($this->readOnlyFields) ) {
			foreach($this->readOnlyFields as $readOnlyField) {
				if($oldField = $fields->fieldByName($readOnlyField)) {
					$fields->replaceField($readOnlyField, $oldField->performReadonlyTransformation());
				}
			}
		}
	}

	/**
	 * Saves region - both shipping and billing fields are saved here for convenience sake (only one actually gets saved)
	 * NOTE: do not call this method SetCountry as this has a special meaning! *
	 * @param String -  RegionID
	 **/
	public function SetRegionFields($regionID) {
		$this->RegionID = $regionID;
		$this->ShippingRegionID = $regionID;
		$this->write();
	}

	/**
	 * Saves country - both shipping and billing fields are saved here for convenience sake (only one actually gets saved)
	 * NOTE: do not call this method SetCountry as this has a special meaning!
	 * @param String - CountryCode - e.g. NZ
	 */
	public function SetCountryFields($countryCode) {
		$this->Country = $countryCode;
		$this->ShippingCountry = $countryCode;
		$this->write();
	}

	/**
	 * Casted variable
	 * returns the full name of the person, e.g. "John Smith"
	 *
	 * @return String
	 */
	public function getFullName() {
		$fieldNameField = $this->fieldPrefix()."FirstName";
		$fieldFirst = $this->$fieldNameField;
		$lastNameField =  $this->fieldPrefix()."Surname";
		$fieldLast = $this->$lastNameField;
		return $fieldFirst.' '.$fieldLast;
	}
		public function FullName(){ return $this->getFullName();}

	/**
	 * Casted variable
	 * returns the full strng of the record
	 *
	 * @return String
	 */
	public function getFullString() {
		return $this->renderWith("Order_Address".str_replace("Address", "", $this->ClassName)."FullString");
	}
		public function FullString(){ return $this->getFullString();}

	/**
	 * returns a string that can be used to find out if two addresses are the same.
	 * @return String
	 */
	protected function comparisonString(){
		$comparisonString = "";
		$excludedFields = array("ID", "OrderID");
		$fields = $this->stat("db");
		$regionFieldName = $this->fieldPrefix()."RegionID";
		$fields[$regionFieldName] = $regionFieldName;
		if($fields) {
			foreach($fields as $field => $useless) {
				if(!in_array($field, $excludedFields)) {
					$comparisonString .= preg_replace('/\s+/', '', $this->$field);
				}
			}
		}
		return strtolower(trim($comparisonString));
	}

	/**
	 *@param String - $prefix = either "" or "Shipping"
	 *@return array of fields for an Order DataObject
	 **/
	protected function getFieldNameArray($fieldPrefix = '') {
		$fieldNameArray = array(
			"Email",
			"Prefix",
			"FirstName",
			"Surname",
			"Address",
			"Address2",
			"City",
			"PostalCode",
			"RegionID",
			"Country",
			"Phone",
			"MobilePhone",
		);
		if($fieldPrefix) {
			foreach($fieldNameArray as $key => $value) {
				$fieldNameArray[$key] = $fieldPrefix.$value;
			}
		}
		return $fieldNameArray;
	}

	/**
	 * returns the field prefix string for shipping addresses
	 * @return String
	 **/
	protected function baseClassLinkingToOrder() {
		if($this instanceOf BillingAddress) {
			return "BillingAddress";
		}
		elseif($this instanceOf ShippingAddress) {
			return "ShippingAddress";
		}
	}

	/**
	 * returns the field prefix string for shipping addresses
	 * @return String
	 **/
	protected function fieldPrefix() {
		if($this->baseClassLinkingToOrder() == "BillingAddress") {
			return "";
		}
		else {
			return "Shipping";
		}
	}



	/**
	 * Copies the last address used by the member.
	 * @return DataObject (OrderAddress / ShippingAddress / BillingAddfress)
	 * @param Object (Member) $member
	 * @param Boolean $write - should the address be written
	 * @todo: are there times when the Shipping rather than the Billing address should be linked?
	 */
	public function FillWithLastAddressFromMember($member, $write = false) {
		$excludedFields = array("ID", "OrderID");
		$fieldPrefix = $this->fieldPrefix();
		if($member && $member->exists()) {
			$oldAddress = $this->lastAddressFromMember($member);
			if($oldAddress) {
				$fieldNameArray = $this->getFieldNameArray($fieldPrefix);
				foreach($fieldNameArray as $field) {
					if(!$this->$field && isset($oldAddress->$field) && !in_array($field, $excludedFields)) {
						$this->$field = $oldAddress->$field;
					}
				}
			}
			//copy data from  member
			if($this instanceOf BillingAddress) {
				$this->Email = $member->Email;
			}
			$fieldNameArray = array("FirstName" => $fieldPrefix."FirstName", "Surname" => $fieldPrefix."Surname");
			foreach($fieldNameArray as $memberField => $fieldName) {
				//NOTE, we always override the Billing Address (which does not have a fieldPrefix)
				if(!$this->$fieldName || $this instanceOf BillingAddress) {$this->$fieldName = $member->$memberField;}
			}
		}
		if($write) {
			$this->write();
		}
		return $this;
	}

/**
	 * Finds the last address used by this member
	 * @param Object (Member)
	 * @return Null | DataObject (ShippingAddress / BillingAddress)
	 **/
	protected function lastAddressFromMember($member = null) {
		$addresses = $this->previousAddressesFromMember($member, true);
		if($addresses) {
			return $addresses->First();
		}
	}

	/**
	 * Finds the last order used by this member
	 * @param Object (Member)
	 * @return Null | DataObject (Order)
	 **/
	protected function lastOrderFromMember($member = null) {
		$orders = $this->previousOrdersFromMember($member, true);
		if($orders) {
			return $orders->First();
		}
	}


	/**
	 * Finds previous addresses from the member of the current address
	 * @param Object (Member)
	 * @return Null | DataObjectSet (filled with ShippingAddress / BillingAddress)
	 **/
	protected function previousAddressesFromMember($member = null, $onlyLastRecord = false, $keepDoubles = false) {
		$orders = $this->previousOrdersFromMember($member, $onlyLastRecord);
		$returnDos = null;
		if($orders) {
			$fieldName = $this->ClassName."ID";
			$array = $orders->map($fieldName, $fieldName);
			if(is_array($array) && count($array)) {
				$limit = null;
				if($onlyLastRecord) {
					$limit = 1;
				}
				$addresses = DataObject::get(
					$this->ClassName,
					"\"".$this->ClassName."\".\"ID\" IN (".implode(",", $array).") AND \"Obsolete\" = 0",
					"\"Order\".\"ID\" DESC",
					"INNER JOIN \"Order\" ON \"Order\".\"$fieldName\" = \"".$this->ClassName."\".\"ID\" ",
					$limit
				);
				//NOTE the !
				if($keepDoubles || $onlyLastRecord) {
					$returnDos = $addresses;
				}
				else {
					if($addresses) {
						$addressCompare = array();
						foreach($addresses as $address) {
							$comparisonString = $address->comparisonString();
							if(in_array($comparisonString, $addressCompare)) {

							}
							else {
								$addressCompare[$address->ID] = $comparisonString;
								if(!$returnDos) {
									$returnDos = new DataObjectSet();
								}
								$returnDos->push($address);
							}
						}
					}
				}
			}
		}
		return $returnDos;
	}

	/**
	 * make an address obsolete and include all the addresses that are identical.
	 *
	 */
	public function MakeObsolete($member = null){
		$addresses = $this->previousAddressesFromMember($member, $onlyLastRecord = false, $includeDoubles = true);
		$comparisonString = $this->comparisonString();
		if($addresses) {
			foreach($addresses as $address) {
				if($address->comparisonString() == $comparisonString) {
					$address->Obsolete = 1;
					$address->write();
				}
			}
		}
		$this->Obsolete = 1;
		$this->write();
	}

	/**
	 * Finds previous orders from the member of the current address
	 * @param Object (Member)
	 * @return Null | DataObjectSet (Order)
	 **/
	protected function previousOrdersFromMember($member = null, $onlyLastRecord = false) {
		if(!$member) {
			$member = $this->getMemberFromOrder();
		}
		if($member && $member->exists()) {
			$limit = null;
			if($onlyLastRecord) {
				$limit = 1;
			}
			$fieldName = $this->ClassName."ID";
			$orders = DataObject::get(
				"Order",
				"\"MemberID\" = ".$member->ID." AND \"$fieldName\" <> ".$this->ID,
				"\"Order\".\"ID\" DESC ",
				$join = null,
				$limit
			);
			return $orders;
		}
	}

	/**
	 * find the member associated with the current Order and address.
	 * @return DataObject (Member) | Null
	 * @Note: this needs to be public to give DODS (extensions access to this)
	 * @todo: can wre write $this->Order() instead????
	 **/
	public function getMemberFromOrder() {
		if($this->exists()) {
			if($order = $this->Order()) {
				if($order->exists()) {
					if($order->MemberID) {
						return DataObject::get_by_id("Member", $order->MemberID);
					}
				}
			}
		}
	}

	function onAfterWrite(){
		parent::onAfterWrite();
		if($this->exists()) {
			$order = DataObject::get_one("Order", "\"". $this->ClassName."ID\" = ".$this->ID);
			if($order && $order->ID != $this->OrderID) {
				$this->OrderID = $order->ID;
				$this->write();
			}
		}
	}

	function RemoveLink(){
		return ShoppingCart_Controller::remove_address_link($this->ID, $this->ClassName);
	}

	/**
	 * converts an address into JSON
	 * @return String (JSON)
	 */
	function getJSONData(){return $this->JSONData();}
	function JSONData(){
		$jsArray = array();
		if(!isset($fields)) {
			$fields = $this->stat("db");
			$regionFieldName = $this->fieldPrefix()."RegionID";
			$fields[$regionFieldName] = $regionFieldName;
		}
		if($fields) {
			foreach($fields as $name => $field) {
				$jsArray[$name] = $this->$name;
			}
		}
		return Convert::array2json($jsArray);
	}


	/**
	 * returns the instance of EcommerceDBConfig
	 *
	 * @return EcommerceDBConfig
	 **/
	public function EcomConfig(){
		return EcommerceDBConfig::current_ecommerce_db_config();
	}

	/**
	 * standard SS Method
	 * saves the region code
	 */
	function onBeforeWrite(){
		parent::onBeforeWrite();
		$fieldPrefix = $this->fieldPrefix();
		$idField = $fieldPrefix . "RegionID";
		if($this->$idField) {
			$region = DataObject::get_by_id("EcommerceRegion", $this->$idField);
			if($region) {
				$codeField = $fieldPrefix."RegionCode";
				$this->$codeField = $region->Code;
			}
		}
	}

}

