<?php

/**
 * @description: each order has a shipping address.
 *
 *
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: address
 * @inspiration: Silverstripe Ltd, Jeremy
 **/

class ShippingAddress extends OrderAddress {

	/**
	 * what variables are accessible through  http://mysite.com/api/ecommerce/v1/ShippingAddress/
	 * @var array
	 */
	public static $api_access = array(
		'view' => array(
			'ShippingPrefix',
			'ShippingFirstName',
			'ShippingSurname',
			'ShippingAddress',
			'ShippingAddress2',
			'ShippingCity',
			'ShippingPostalCode',
			'ShippingCountry',
			'ShippingPhone',
			'ShippingMobilePhone'
		)
	);

	public static $db = array(
		'ShippingPrefix' => 'Varchar(10)',
		'ShippingFirstName' => 'Varchar(100)',
		'ShippingSurname' => 'Varchar(100)',
		'ShippingAddress' => 'Varchar(200)',
		'ShippingAddress2' => 'Varchar(200)',
		'ShippingCity' => 'Varchar(100)',
		'ShippingPostalCode' => 'Varchar(30)',
		'ShippingCountry' => 'Varchar(4)',
		'ShippingPhone' => 'Varchar(100)',
		'ShippingMobilePhone' => 'Varchar(100)',
		'Obsolete' => 'Boolean',
		'OrderID' => 'Int' ////NOTE: we have this here for faster look-ups and to make addresses behave similar to has_many dataobjects
	);


	/**
	 * standard SS static definition
	 **/
	public static $has_one = array(
		"ShippingRegion" => "EcommerceRegion"
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
	public static $default_sort = "\"ShippingAddress\".\"ID\" DESC";


	static $indexes = array(
		/* "SearchFields" => "fulltext (Address, Address2, City, PostalCode, Phone)"
		 array(
			'name' => 'SearchFields',
			'type' => 'fulltext',
			'value' => 'ShippingAddress, ShippingAddress2, ShippingCity, ShippingPostalCode, ShippingPhone'
		),
		*/
		"Obsolete" => true,
		"OrderID" => true
	);

	public static $casting = array(
		"ShippingFullCountryName" => "Varchar(200)"
	);

	public static $searchable_fields = array(
		"OrderID" => array(
			"field" => "NumericField",
			"title" => "Order Number"
		),
		"ShippingSurname" => "PartialMatchFilter",
		"ShippingAddress" => "PartialMatchFilter",
		"ShippingCity" => "PartialMatchFilter",
		"ShippingCountry" => "PartialMatchFilter",
		"Obsolete"
	);

	public static $summary_fields = array(
		"Order.Title",
		"Surname",
		"City"
	);

	public static $singular_name = "Shipping Address";
		function i18n_singular_name() { return _t("OrderAddress.SHIPPINGADDRESS", "Shipping Address");}

	public static $plural_name = "Shipping Addresses";
		function i18n_plural_name() { return _t("OrderAddress.SHIPPINGADDRESSES", "Shipping Addresses");}


	/**
	 *
	 *@return FieldSet
	 **/
	function getCMSFields() {
		$fields = parent::getCMSFields();
		$fields->replaceField("OrderID", new ReadonlyField("OrderID"));
		return $fields;
	}



	/**
	 * returns the full name for the shipping country code saved.
	 * @return String
	 **/
	function ShippingFullCountryName() {return $this->getShippingFullCountryName();}
	function getShippingFullCountryName() {
		return EcommerceCountry::find_title($this->ShippingCountry);
	}

	/**
	 * Puts together the fields for the Order Form (and other front-end purposes).
	 * @return Fieldset
	 **/
	public function getFields($member = null) {
		$fields = parent::getEcommerceFields();
		if(EcommerceConfig::get("OrderAddress", "use_separate_shipping_address")) {
			$shippingFieldsHeader = new CompositeField(
				new HeaderField('SendGoodsToADifferentAddress', _t('OrderAddress.SENDGOODSTODIFFERENTADDRESS','Send goods to different address'), 3),
				new LiteralField('ShippingNote', '<p class="message warning">'._t('OrderAddress.SHIPPINGNOTE','Your goods will be sent to the address below.').'</p>'),
				new LiteralField('ShippingHelp', '<p>'._t('OrderAddress.SHIPPINGHELP','You can use this for gift giving. No billing information will be disclosed to this address.').'</p>')
			);

			if($member) {
				if($member->exists()) {
					$this->FillWithLastAddressFromMember($member, true);
					$addresses = $this->previousAddressesFromMember($member);
					if($addresses) {
						if($addresses->count() > 1) {
							$shippingFieldsHeader->push(new SelectOrderAddressField('SelectShippingAddressField', _t('OrderAddress.SELECTBILLINGADDRESS','Select Shipping Address'), $addresses));
						}
					}
				}
				$shippingFields = new CompositeField(
					new TextField('ShippingFirstName', _t('OrderAddress.FIRSTNAME','First Name')),
					new TextField('ShippingSurname', _t('OrderAddress.SURNAME','Surname'))
				);
			}
			else {
				$shippingFields = new CompositeField(
					new TextField('ShippingFirstName', _t('OrderAddress.FIRSTNAME','First Name')),
					new TextField('ShippingSurname', _t('OrderAddress.SURNAME','Surname'))
				);
			}
			//$shippingFields->push(new TextField('ShippingPrefix', _t('OrderAddress.PREFIX','Title (e.g. Ms)')));
			$shippingFields->push(new TextField('ShippingAddress', _t('OrderAddress.ADDRESS','Address')));
			$shippingFields->push(new TextField('ShippingAddress2', _t('OrderAddress.ADDRESS2','&nbsp;')));
			$shippingFields->push(new TextField('ShippingCity', _t('OrderAddress.CITY','Town')));
			$shippingFields->push($this->getPostalCodeField("ShippingPostalCode"));
			$shippingFields->push($this->getRegionField("ShippingRegionID"));
			$shippingFields->push($this->getCountryField("ShippingCountry"));
			$shippingFields->push(new TextField('ShippingPhone', _t('OrderAddress.PHONE','Phone')));
			$shippingFields->push(new TextField('ShippingMobilePhone', _t('OrderAddress.MOBILEPHONE','Mobile Phone')));
			$this->makeSelectedFieldsReadOnly($shippingFields);
			$shippingFieldsHeader->SetID("ShippingFieldsHeader");
			$shippingFields->addExtraClass("orderAddressHolder");
			$fields->push($shippingFieldsHeader);
			$shippingFields->SetID('ShippingFields');
			$fields->push($shippingFields);
		}
		$this->extend('augmentEcommerceShippingAddressFields', $fields);
		return $fields;
	}


	/**
	 * Return which shipping fields should be required on {@link OrderFormAddress}
	 *
	 * @return array
	 */
	function getRequiredFields() {
		$requiredFieldsArray = array(
			'ShippingAddress',
			'ShippingCity',
			'ShippingCountry'
		);
		$this->extend('augmentEcommerceShippingAddressRequiredFields', $requiredFieldsArray);
		return $requiredFieldsArray;
	}

	/**
	 * standard SS method
	 * sets the country to the best known country {@link EcommerceCountry}
	 **/
	function populateDefaults() {
		parent::populateDefaults();
		$this->ShippingCountry = EcommerceCountry::get_country();
	}



}
