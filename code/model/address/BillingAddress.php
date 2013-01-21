<?php

/**
 * @description: each order has a billing address.
 *
 *
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: address
 * @inspiration: Silverstripe Ltd, Jeremy
 **/

class BillingAddress extends OrderAddress {

	/**
	 * what variables are accessible through  http://mysite.com/api/ecommerce/v1/BillingAddress/
	 * @var array
	 */
	public static $api_access = array(
		'view' => array(
			'Prefix',
			'FirstName',
			'Surname',
			'Address',
			'Address2',
			'City',
			'PostalCode',
			'Country',
			'Phone',
			'MobilePhone',
			'Email'
		)
	);

	/**
	 * standard SS variable
	 * @return Array
	 */
	static $db = array(
		'Prefix' => 'Varchar(10)',
		'FirstName' => 'Varchar(100)',
		'Surname' => 'Varchar(100)',
		'Address' => 'Varchar(200)',
		'Address2' => 'Varchar(200)',
		'City' => 'Varchar(100)',
		'PostalCode' => 'Varchar(30)',
		'Country' => 'Varchar(4)',
		'Phone' => 'Varchar(50)',
		'MobilePhone' => 'Varchar(50)',
		'Email' => 'Varchar(250)',
		'Obsolete' => 'Boolean',
		'OrderID' => 'Int' //NOTE: we have this here for faster look-ups and to make addresses behave similar to has_many dataobjects
	);

	/**
	 * HAS_ONE =array(ORDER => ORDER);
	 * we place this relationship here
	 * (rather than in the parent class: OrderAddress)
	 * because that makes for a cleaner relationship
	 * (otherwise we ended up with a "has two" relationship in Order)
	 **/
	static $has_one = array(
		"Region" => "EcommerceRegion"
	);

	/**
	 * standard SS static definition
	 **/
	public static $belongs_to = array(
		"Order" => "Order"
	);

	/**
	 * standard SS static definition
	 */
	public static $default_sort = "\"BillingAddress\".\"ID\" DESC";

	/**
	 * standard SS variable
	 * @return Array
	 */
	static $indexes = array(
		/* "SearchFields" => "fulltext (FirstName, Surname, Address, Address2, City, PostalCode, Email)"
		array(
			'name' => 'SearchFields',
			'type' => 'fulltext',
			'value' => 'FirstName, Surname, Address, Address2, City, PostalCode, Email'
		),
		*/
		"Obsolete" => true,
		"OrderID" => true
	);

	/**
	 * standard SS variable
	 * @return Array
	 */
	public static $casting = array(
		"FullCountryName" => "Varchar"
	);

	/**
	 * standard SS variable
	 * @return Array
	 */
	public static $searchable_fields = array(
		"OrderID" => array(
			"field" => "NumericField",
			"title" => "Order Number"
		),
		"Email" => "PartialMatchFilter",
		"FirstName" => "PartialMatchFilter",
		"Surname" => "PartialMatchFilter",
		"Address" => "PartialMatchFilter",
		"City" => "PartialMatchFilter",
		"Country" => "PartialMatchFilter",
		"Obsolete"
	);

	/**
	 * standard SS variable
	 * @return Array
	 */
	public static $summary_fields = array(
		"Order.Title",
		"Surname",
		"City"
	);

	/**
	 * standard SS variable
	 * @return String
	 */
	public static $singular_name = "Billing Address";
		function i18n_singular_name() { return _t("OrderAddress.BILLINGADDRESS", "Billing Address");}

	/**
	 * standard SS variable
	 * @return String
	 */
	public static $plural_name = "Billing Addresses";
		function i18n_plural_name() { return _t("OrderAddress.BILLINGADDRESSES", "Billing Addresses");}

	/**
	 * method for casted variable
	 *@return String
	 **/
	function FullCountryName() {return $this->getFullCountryName();}
	function getFullCountryName() {
		return EcommerceCountry::find_title($this->Country);
	}

	/**
	 *
	 *@return FieldSet
	 **/
	function getCMSFields() {
		$fields = parent::getCMSFields();
		$fields->replaceField("OrderID", new ReadonlyField("OrderID", _t("OrderAddress.ORDERID", "Order #")));
		$fields->replaceField("Email", new EmailField("Email", _t("OrderAddress.EMAIL", "Email")));
		$fields->replaceField("RegionID", $this->getRegionField("RegionID"));
		$fields->replaceField("Country", $this->getCountryField("Country"));
		return $fields;
	}

	/**
	 *@return Fieldset
	 **/
	public function getFields($member = null) {
		$fields = parent::getEcommerceFields();
		$fields->push(new HeaderField('BillingDetails', _t('OrderAddress.BILLINGDETAILS','Billing Details'), 3));
		if($member) {
			if($member->exists()) {
				$this->FillWithLastAddressFromMember($member, true);
				$addresses = $this->previousAddressesFromMember($member);
				if($addresses) {
					if($addresses->count() > 1) {
						$fields->push(new SelectOrderAddressField('SelectBillingAddressField', _t('OrderAddress.SELECTBILLINGADDRESS','Select Billing Address'), $addresses));
					}
				}
			}
			$billingFields = new CompositeField(
				new EmailField('Email', _t('OrderAddress.EMAIL','Email')),
				new TextField('FirstName', _t('OrderAddress.FIRSTNAME','First Name')),
				new TextField('Surname', _t('OrderAddress.SURNAME','Surname'))
			);
		}
		else {
			$billingFields = new CompositeField(
				new EmailField('Email', _t('OrderAddress.EMAIL','Email')),
				new TextField('FirstName', _t('OrderAddress.FIRSTNAME','First Name')),
				new TextField('Surname', _t('OrderAddress.SURNAME','Surname'))
			);
		}
		//$billingFields->push(new TextField('Prefix', _t('OrderAddress.PREFIX','Title (e.g. Ms)')));
		$billingFields->push(new TextField('Address', _t('OrderAddress.ADDRESS','Address')));
		$billingFields->push(new TextField('Address2', _t('OrderAddress.ADDRESS2','&nbsp;')));
		$billingFields->push(new TextField('City', _t('OrderAddress.CITY','Town')));
		$billingFields->push($this->getPostalCodeField("PostalCode"));
		$billingFields->push($this->getRegionField("RegionID"));
		$billingFields->push($this->getCountryField("Country"));
		$billingFields->push(new TextField('Phone', _t('OrderAddress.PHONE','Phone')));
		$billingFields->push(new TextField('MobilePhone', _t('OrderAddress.MOBILEPHONE','Mobile Phone')));
		$billingFields->SetID('BillingFields');
		$billingFields->addExtraClass("orderAddressHolder");
		$this->makeSelectedFieldsReadOnly($billingFields);
		$fields->push($billingFields);
		$this->extend('augmentEcommerceBillingAddressFields', $fields);
		return $fields;
	}

	/**
	 * Return which billing address fields should be required on {@link OrderFormAddress}
	 *
	 * @return array
	 */
	function getRequiredFields() {
		$requiredFieldsArray = array(
			'Email',
			'FirstName',
			'Surname',
			'Address',
			'City',
			'PostalCode',
		);
		$this->extend('augmentEcommerceBillingAddressRequiredFields', $requiredFieldsArray);
		return $requiredFieldsArray;
	}

	/**
	 * standard SS method
	 * sets the country to the best known country {@link EcommerceCountry}
	 **/
	function populateDefaults() {
		parent::populateDefaults();
		$this->Country = EcommerceCountry::get_country();
	}



}
