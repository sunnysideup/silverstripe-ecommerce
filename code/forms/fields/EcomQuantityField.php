<?php


/**
 * @Description: A links-based field for increasing, decreasing and setting a order item quantity
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: forms
 * @inspiration: Silverstripe Ltd, Jeremy
 **/

class EcomQuantityField extends NumericField {


	/**
	 * the tabindex for the form field
	 * we use this so that you can tab through all the
	 * quantity fields without disruption.
	 * It is saved like this: "FieldName (String)" => tabposition (int)
	 * @var Array
	 **/
	protected static $tabindex = array();

	/**
	 *@var order OrderItem DataObject
	 **/
	protected $orderItem = null;

	/**
	 *@var $parameters Array();???
	 **/
	protected $parameters = null;

	/**
	 *@var $classes Array()
	 **/
	protected $classes = array('ajaxQuantityField');

	/**
	 * max length in digits
	 *@var Integer
	 **/
	protected $maxLength = 3;


	/**
	 * max length in digits
	 *@var Integer
	 **/
	protected $fieldSize = 3;

	/**
	 *@var $template String
	 **/
	protected $template = 'EcomQuantityField';


	/**
	 *@param $object - the buyable / OrderItem
	 **/
	function __construct($object, $parameters = null){
		Requirements::javascript("ecommerce/javascript/EcomQuantityField.js"); // LEAVE HERE - NOT EASY TO INCLUDE VIA TEMPLATE
		if($object instanceof DataObject && $object instanceof BuyableModel){
			$this->orderItem = ShoppingCart::singleton()->findOrMakeItem($object,$parameters);
			 //provide a 0-quantity facade item if there is no such item in cart OR perhaps we should just store the product itself, and do away with the facade, as it might be unnecessary complication
			if(!$this->orderItem) {
				$className = $object->classNameForOrderItem();
				$this->orderItem = new $className($object->dataRecord,0);
			}
		}
		elseif($object instanceof OrderItem && $object->BuyableID){
			$this->orderItem = $object;
		}
		else{
			user_error("EcomQuantityField: no/bad order item or buyable passed to constructor.", E_USER_WARNING);
		}
		$this->parameters = $parameters;
	}

	function setClasses($newclasses, $overwrite = false){
		if($overwrite) {
			$this->classes = array_merge($this->classes,$newclasses);
		}
		else {
			$this->classes = $newclasses;
		}
	}

	function setTemplate($template){
		$this->template = $template;
	}

	/**
	 * alias of OrderItem
	 *@return OrderItem
	 **/
	function Item(){
		return $this->OrderItem();
	}

	/**
	 *@return OrderItem
	 **/
	function OrderItem(){
		return $this->orderItem;
	}

	/**
	 *@return String (HTML)
	 **/
	function Field() {
		$name = $this->orderItem->AJAXDefinitions()->TableID() . '_Quantity_SetQuantityLink';
		if(!isset(self::$tabindex[$name])) {
			self::$tabindex[$name] = count(self::$tabindex) + 1;
		}
		$attributes = array(
			'type' => 'text',
			'class' => implode(' ',$this->classes),
			'name' => $name,
			'value' => ($this->orderItem->Quantity) ? $this->orderItem->Quantity : 0,
			'maxlength' => $this->maxLength,
			'size' => $this->fieldSize,
			'rel' => $this->getQuantityLink(),
			'tabindex' => self::$tabindex[$name],
			'disabled' => 'disabled'
		);
		//IMPROVE ME: hack to use the form field createTag method ...perhaps this should become a form field instead
		$formfield = new FormField($name);
		return $formfield->createTag('input', $attributes);
	}

	/**
	 * Used for storing the quantity update link for ajax use.
	 * @return String (HTML)
	 */
	function AJAXLinkHiddenField(){
		$name = $this->orderItem->AJAXDefinitions()->TableID() . '_Quantity_SetQuantityLink';
		if($quantitylink = $this->getQuantityLink()){
			$attributes = array(
				'type' => 'hidden',
				'class' => 'ajaxQuantityField_qtylink',
				'name' => $name,
				'value' => $quantitylink
			);
			$formfield = new FormField($name);
			return $formfield->createTag('input', $attributes);
		}
	}

	/**
	 *@return String (URLSegment)
	 **/
	function IncrementLink(){
		return ShoppingCart_Controller::add_item_link($this->orderItem->BuyableID, $this->orderItem->BuyableClassName,$this->parameters);
	}

	/**
	 *@return String (URLSegment)
	 **/
	function DecrementLink(){
		return ShoppingCart_Controller::remove_item_link($this->orderItem->BuyableID, $this->orderItem->BuyableClassName,$this->parameters);
	}

	/**
	 *@return HTML
	 **/
	function forTemplate(){
		return $this->renderWith($this->template);
	}

	/**
	 *
	 * @return String
	 */

	protected function getQuantityLink(){
		return ShoppingCart_Controller::set_quantity_item_link($this->orderItem->BuyableID, $this->orderItem->BuyableClassName,$this->parameters);
	}

	/**
	 *
	 * @return Float
	 */
	protected function Quantity() {
		if($this->orderItem) {
			return floatval($this->orderItem->Quantity)-0;
		}
		return 0;
	}

}
