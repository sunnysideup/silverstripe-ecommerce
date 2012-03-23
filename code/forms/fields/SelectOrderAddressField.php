<?php
/**
 * Set of radio buttons designed to emulate a dropdown.
 * It even uses the same constructor as a dropdown field.
 *
 * This field allows you to ensure that a form element is submitted is not optional and is part of a fixed set of
 * data. This field uses the input type of radio. It's a direct subclass of {@link DropdownField},
 * so the constructor and arguments are in the same format.
 *
 * <b>Usage</b>
 *
 * <code>
 * new OptionsetField(
 *    $name = "Foobar",
 *    $title = "FooBar's optionset",
 *    $source = array(
 *       "1" => "Option 1",
 *       "2" => "Option 2",
 *       "3" => "Option 3",
 *       "4" => "Option 4",
 *       "5" => "Option 5"
 *    ),
 *    $value = "1"
 * );
 * </code>
 *
 * You can use the helper functions on data object set to create the source array. eg:
 *
 * <code>
 * //Database request for the object
 * $myDoSet = DataObject::get("FooBars","");
 * if($myDoSet){
 *  // This returns an array of ID => Title
 *  $map = $myDoSet->toDropDownMap();
 *
 *   // Instantiate the OptionsetField
 *   $fieldset = new Fieldset(
 *     new OptionsetField(
 *      $name = "Foobar",
 *      $title = "FooBar's optionset",
 *      $source = $map,
 *      $value = $map[0]
 *     )
 *   );
 * }
 *
 * // Pass the fields to the form constructor. etc
 * </code>
 *
 * @see CheckboxSetField for multiple selections through checkboxes instead.
 * @see DropdownField for a simple <select> field with a single element.
 * @see TreeDropdownField for a rich and customizeable UI that can visualize a tree of selectable elements
 *
 * @package forms
 * @subpackage fields-basic
 */
class SelectOrderAddressField extends OptionsetField {

	/**
	 * @var Null | DataObjectSet
	 */
	protected $addresses = null;

	/**
	 * Creates a new optionset field.
	 * @param name The field name
	 * @param title The field title
	 * @param source DataObjectSet
	 * @param value The current value
	 * @param form The parent form
	 */
	function __construct($name, $title = "", $addresses = null, $value = "", $form = null) {
		$this->addresses = $addresses;
		$source = array();
		if($this->addresses) {
			$source = $this->addresses->map("ID", "FullString");
		}
		parent::__construct($name, $title, $source, $value, $form);
	}

	/**
	 *
	 */
	function Field() {
		$jsArray = array();
		$js = '';
		$jsonCompare = array();
		if($this->addresses) {
			foreach($this->addresses as $address) {
				$js .= " EcomSelectOrderAddressField.set_data(".$address->ID.", ".$address->JSONData().");\r\n";
			}
		}
		Requirements::javascript("ecommerce/javascript/EcomSelectOrderAddressField.js");
		Requirements::customScript($js, "Update_".$this->Name());
		return parent::Field();
	}

}
?>
