<?php
/**
 * SubTotal modifier provides a way to display subtotal within the list of modifiers.
 *
 * @authors: Silverstripe, Jeremy, Nicolaas
 *
 * @package: ecommerce
 * @sub-package: modifiers
 *

 **/

class SubTotalModifier extends OrderModifier {

// ######################################## *** model defining static variables (e.g. $db, $has_one)

	public static $singular_name = "Sub total modifier";
		function i18n_singular_name() { return _t("SubTotalModifier.SUBTOTALMODIFIER", "Sub total modifier");}

	public static $plural_name = "Sub total modifiers";
		function i18n_plural_name() { return _t("SubTotalModifier.SUBTOTALMODIFIER", "Sub total modifiers");}

// ######################################## *** cms variables + functions (e.g. getCMSFields, $searchableFields)
// ######################################## *** other (non) static variables (e.g. protected static $special_name_for_something, protected $order)

// ######################################## *** CRUD functions (e.g. canEdit)
// ######################################## *** init and update functions

// ######################################## *** form functions (e. g. showform and getform)
// ######################################## *** template functions (e.g. ShowInTable, TableTitle, etc...) ... USES DB VALUES


	public function CanBeRemoved(){
		return false;
	}

	public function TableTitle(){return $this->getTableTitle();}
	public function getTableTitle(){
		return _t("SubtTotalModifier.SUBTOTAL", "Sub Total");
	}



// ######################################## ***  inner calculations ... USED CALCULATED VALUES
// ######################################## *** calculate database fields: protected function Live[field name] ... USED CALCULATED VALUES


	protected function LiveCalculatedTotal(){
		return 0;
	}


	/**
	 * This overrides the table value to show the subtotal, but the LiveCalculatedTotal is always 0 (see below)
	 */
	protected function LiveTableValue() {
		$order = $this->Order();
		return $order->SubTotal() + $order->ModifiersSubTotal($this->class,true);
	}

// ######################################## *** Type Functions (IsChargeable, IsDeductable, IsNoChange, IsRemoved)
// ######################################## *** standard database related functions (e.g. onBeforeWrite, onAfterWrite, etc...)
// ######################################## *** AJAX related functions
// ######################################## *** debug functions

}
