<?php


/**
 * @description: this class is the base class for modifier forms in the checkout form... we could do with more stuff here....
 *
 * @see OrderModifier
 *
 *
 * @authors: Silverstripe, Jeremy, Nicolaas
 *
 * @package: ecommerce
 * @sub-package: forms
 *
 **/

class OrderModifierForm extends Form {

	protected $order;


	/**
	 *NOTE: we semi-enforce using the OrderModifier_Controller here to deal with the submission of the OrderModifierForm
	 * You can use your own modifiers or an extension of OrderModifier_Controller by setting the first parameter (optionalController)
	 * to your own controller.
	 *
	 *@param $optionalController Controller
	 *@param $name String
	 *@param $fields FieldSet
	 *@param $actions FieldSet
	 *@param $validator SS_Validator
	 **/

	function __construct($optionalController = null, $name, FieldSet $fields, FieldSet $actions,$optionalValidator = null){
		if(!$optionalController) {
			$controllerClassName = EcommerceConfig::get("OrderModifierForm", "controller_class");
			$optionalController = new $controllerClassName();
		}
		if(!$optionalValidator) {
			$validatorClassName = EcommerceConfig::get("OrderModifierForm", "validator_class");
			$optionalValidator = new $validatorClassName();
		}
		parent::__construct($optionalController, $name, $fields, $actions, $optionalValidator);
		Requirements::themedCSS($this->ClassName);
		Requirements::javascript(THIRDPARTY_DIR."/jquery-form/jquery.form.js");
		//add JS for the modifier - added in modifier
	}

	function redirect($status = "success", $message = ""){
		//return ShoppingCart::singleton()->addmessage($status, $message);
	}

	function submit($data, $form, $message = "order updated", $status = "good") {
		//to do - add other checks here...
		 return ShoppingCart::singleton()->setMessageAndReturn($message, $status);
	}

}



/**
 * This controller allows you to submit modifier forms from anywhere on the site, especially the cart / checkout page.
 */
class OrderModifierForm_Controller extends Controller{

	protected $currentOrder = null;

	static $allowed_actions = array(
		'removemodifier'
	);

	public function init() {
		parent::init();
		$this->currentOrder = ShoppingCart::current_order();
		$this->initVirtualMethods();
	}

	/**
	 * Inits the virtual methods from the name of the modifier forms to
	 * redirect the action method to the form class
	 */
	protected function initVirtualMethods() {
		if($this->currentOrder) {
			if($forms = $this->currentOrder->getModifierForms($this)) {
				foreach($forms as $form) {
					$this->addWrapperMethod($form->Name(), 'getOrderModifierForm');
					self::$allowed_actions[] = $form->Name(); // add all these forms to the list of allowed actions also
				}
			}
		}
	}

	/**
	 * Return a specific {@link OrderModifierForm} by it's name.
	 *
	 * @param string $name The name of the form to return
	 * @return Form
	 */
	protected function getOrderModifierForm($name) {
		if($this->currentOrder) {
			if($forms = $this->currentOrder->getModifierForms($this)) {
				foreach($forms as $form) {
					if($form->Name() == $name) return $form;
				}
			}
		}
	}

	function Link($action = null){
		$action = ($action)? "/$action/" : "";
		return $this->class.$action;
	}

	function removemodifier(){
		//See issue 149
	}

}


class OrderModifierForm_Validator extends RequiredFields{


}
