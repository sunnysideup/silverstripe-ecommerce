<?php


/**
 * SimpleShoppingModifier is the default shipping calculation
 * scheme. It lets you set fixed shipping* costs, or a fixed
 * cost for each country you're delivering to.
 *
 * This is a very basic class that is more like an example than
 * a usable class.
 *
 * If you require more advanced shipping control, we suggest
 * that you create your own subclass of {@link OrderModifier}
 *
 *
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: model
 * @inspiration: Silverstripe Ltd, Jeremy
 **/

class SimpleShippingModifier extends OrderModifier {


// ######################################## *** model defining static variables (e.g. $db, $has_one)

	static $db = array(
		'Country' => 'Varchar(3)',
		'ShippingChargeType' => "Enum('Default,ForCountry')"
	);


	public static $singular_name = "Simple Shipping Charge";
		function i18n_singular_name() { return _t("SimpleShippingModifier.SIMPLESHIPPINGMODIFIER", "Simple Shipping Charge");}

	public static $plural_name = "Simple Shipping Charges";
		function i18n_plural_name() { return _t("SimpleShippingModifier.SIMPLESHIPPINGMODIFIER", "Simple Shipping Charges");}

	/**
	 * Standard SS variable.
	 * @var String
	 */
	public static $description = "Adds shipping costs to the order.";
// ######################################## *** cms variables + functions (e.g. getCMSFields, $searchableFields)


// ######################################## *** other (non) static variables (e.g. protected static $special_name_for_something, protected $order)



// ######################################## *** CRUD functions (e.g. canEdit)
// ######################################## *** init and update functions
	/**
	 * updates database fields
	 * @param Bool $force - run it, even if it has run already
	 * @return void
	 */
	public function runUpdate($force = true) {
		$this->checkField("Country");
		$this->checkField("ShippingChargeType");
		parent::runUpdate($force);
	}


// ######################################## *** form functions (e. g. Showform and getform)
// ######################################## *** template functions (e.g. ShowInTable, TableTitle, etc...) ...  USES DB VALUES

	/**
	 * @return boolean
	 */

	public function ShowInCart() {
		return $this->CalculationTotal() > 0;
	}

	/**
	 * @return string
	 */
	public function TableTitle() {return $this->getTableTitle();}
	public function getTableTitle() {
		if($this->Country) {
			$countryList = EcommerceCountry::get_country_dropdown();
			return _t("SimpleShippingModifier.SHIPPINGTO", "Shipping to")." ".$countryList[$this->Country];
		}
		else {
			return _t("SimpleShippingModifier.SHIPPING", "Shipping");
		}
	}

	/**
	 * @return string
	 */
	public function CartTitle() {return $this->getCartTitle();}
	public function getCartTitle() {
		return _t("SimpleShippingModifier.SHIPPING", "Shipping");
	}


// ######################################## ***  inner calculations....  USES CALCULATED VALUES

	protected function IsDefaultCharge() {
		return !$this->LiveCountry() || !array_key_exists($this->LiveCountry(), EcommerceConfig::get("SimpleShippingModifier", "charges_by_country"));
	}

// ######################################## *** calculate database fields: protected function Live[field name] ...  USES CALCULATED VALUES

	/**
	 * Returns the most likely country for the sale.
	 * @return String
	 */
	protected function LiveCountry() {
		return EcommerceCountry::get_country();
	}

	/**
	 * Find the amount for the shipping on the shipping country for the order.
	 */
	protected function LiveCalculatedTotal() {
		$defaultCharge = EcommerceConfig::get("SimpleShippingModifier", "default_charge");
		$chargesByCountry = EcommerceConfig::get("SimpleShippingModifier", "charges_by_country");
		return $this->IsDefaultCharge() ? $defaultCharge : $chargesByCountry[$this->LiveCountry()];
	}

	protected function LiveShippingChargeType() {
		$this->IsDefaultCharge() ? 'Default' : 'ForCountry';
	}

// ######################################## *** Type Functions (IsChargeable, IsDeductable, IsNoChange, IsRemoved)
// ######################################## *** standard database related functions (e.g. onBeforeWrite, onAfterWrite, etc...)
// ######################################## *** AJAX related functions
// ######################################## *** debug functions

}
