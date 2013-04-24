<?php
/**
 * A field that allows the user to select an old address for the current order.
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: forms
 * @inspiration: Silverstripe Ltd, Jeremy
 **/

class SelectOrderAddressField extends OptionsetField {

	/**
	 * @var DataList
	 */
	protected $addresses = null;

	/**
	 * Creates a new optionset field.
	 * @param String $name The field name
	 * @param String $title The field title
	 * @param DataList $addresses
	 * @param String $value The current value
	 * @param Form $form - The parent form
	 */
	function __construct($name, $title = "", $addresses = null, $value = "", Form $form = null) {
		$this->addresses = $addresses;
		$source = array();
		if($this->addresses && $this->addresses instanceOf DataList) {
			$source = $this->addresses->map("ID", "FullString")->toArray();
		}
		parent::__construct($name, $title, $source, $value, $form);
	}

	/**
	 * Standard SS method - returns the string for the Field.
	 * Note that we include JS from this method.
	 * @return HTML
	 */
	function Field($properties = array()) {
		$jsArray = array();
		$js = '';
		$jsonCompare = array();
		if($this->addresses) {
			foreach($this->addresses as $address) {
				$js .= " EcomSelectOrderAddressField.set_data(".$address->ID.", ".$address->JSONData().");\r\n";
			}
		}
		Requirements::javascript("ecommerce/javascript/EcomSelectOrderAddressField.js");
		Requirements::customScript($js, "Update_".$this->getName());
		return parent::Field();
	}

}

