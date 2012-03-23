<?php
/**
 * Handles calculation of sales tax on Orders.
 *
 * If you would like to make your own tax calculator,
 * create a subclass of this and enable it by using
 * {@link Order::set_modifiers()} in your project
 * _config.php file.
 *
 * Sample configuration in your _config.php:
 *
 * <code>
 *	//rate , name, isexclusive
 * 	FlatTaxModifier::set_tax(0.15, 'GST', false);
 * </code>
 *
 * @authors: Silverstripe, Jeremy, Nicolaas
 *
 * @package: ecommerce
 * @sub-package: modifiers
 *
 **/

class FlatTaxModifier extends OrderModifier {

// ######################################## *** model defining static variables (e.g. $db, $has_one)

	public static $db = array(
		'Country' => 'Text',
		'Rate' => 'Double',
		'TaxType' => "Enum('Exclusive,Inclusive')",
		'TaxableAmount' => "Currency"
	);


	public static $singular_name = "Flat Tax";
		function i18n_singular_name() { return _t("FlatTaxModifier.FLATTAXMODIFIER", "Flat Tax");}

	public static $plural_name = "Flat Taxes";
		function i18n_plural_name() { return _t("FlatTaxModifier.FLATTAXMODIFIER", "Flat Taxes");}

// ######################################## *** cms variables + functions (e.g. getCMSFields, $searchableFields)

// ######################################## *** other (non) static variables (e.g. protected static $special_name_for_something, protected $order)


	protected $includedmessage = "%.1f%% %s (inclusive)";
	protected $excludedmessage = "%.1f%% %s";

// ######################################## *** CRUD functions (e.g. canEdit)
// ######################################## *** init and update functions

	/**
	 * updates database fields
	 * @param Bool $force - run it, even if it has run already
	 * @return void
	 */
	public function runUpdate($force = true) {
		$this->checkField("Country");
		$this->checkField("Rate");
		$this->checkField("TaxType");
		$this->checkField("TaxableAmount");
		parent::runUpdate($force);
	}

// ######################################## *** form functions (showform and getform)
// ######################################## *** template functions (e.g. ShowInTable, TableTitle, etc...) ... USES DB VALUES

	public function ShowInTable() {
		return $this->Rate;
	}


	/**
	 * The title of what appears on the OrderInformation
	 * template table on the checkout page.
	 *
	 * PRECONDITION: There is a rate set.
	 *
	 * @return string
	 */

	public function TableTitle() {return $this->getTableTitle();}
	public function getTableTitle() {
		$message = ($this->IsExclusive()) ? $this->excludedmessage : $this->includedmessage;
		return sprintf($message,$this->Rate * 100,$this->Name);
	}
// ######################################## ***  inner calculations.... USES CALCULATED VALUES


	protected function IsExclusive() {
		return EcommerceConfig::get("FlatTaxModifier", "exclusive");
	}

// ######################################## *** calculate database fields ... USES CALCULATED VALUES

	/**
	 * The total amount from the {@link Order} that
	 * is taxable.
	 */
	protected function LiveTaxableAmount() {
		$order = $this->Order();
		return $order->SubTotal() + $order->ModifiersSubTotal($this->class);
	}


	protected function LiveRate() {
		return  EcommerceConfig::get("FlatTaxModifier", "rate");;
	}

	protected function LiveName() {
		return  EcommerceConfig::get("FlatTaxModifier", "name");
	}

	protected function LiveTaxType() {
		if($this->IsExclusive()) {
			return "Exclusive";
		}
		return "Inclusive";
	}

	protected function LiveCalculatedTotal() {
		if($this->IsExclusive()) {
			$this->TaxableAmount() * $this->LiveRate();
		}
		else {
			return 0;
		}
	}

	public function LiveTableValue(){
		return $this->TaxableAmount * $this->Rate;
	}
// ######################################## *** Type functions
	public function IsChargeable() {
		if($this->IsExclusive()) {
			return true;
		}
	}

	public function IsNoChange() {
		if(!$this->IsChargeable()) {
			return true;
		}
	}


// ######################################## *** standard database related functions (e.g. onBeforeWrite, onAfterWrite, etc...)

	public function onBeforeWrite() {
		parent::onBeforeWrite();
	}

// ######################################## *** AJAX related functions
// ######################################## *** debug functions

}
