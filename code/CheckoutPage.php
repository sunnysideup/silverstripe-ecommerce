<?php

/**
 * CheckoutPage is a CMS page-type that shows the order
 * details to the customer for their current shopping
 * cart on the site. It also lets the customer review
 * the items in their cart, and manipulate them (add more,
 * deduct or remove items completely). The most important
 * thing is that the {@link CheckoutPage_Controller} handles
 * the {@link OrderForm} form instance, allowing the customer
 * to fill out their shipping details, confirming their order
 * and making a payment.
 *
 * @see CheckoutPage_Controller->Order()
 * @see OrderForm
 * @see CheckoutPage_Controller->OrderForm()
 *
 * The CheckoutPage_Controller is also responsible for setting
 * up the modifier forms for each of the OrderModifiers that are
 * enabled on the site (if applicable - some don't require a form
 * for user input). A usual implementation of a modifier form would
 * be something like allowing the customer to enter a discount code
 * so they can receive a discount on their order.
 *
 * @see OrderModifier
 * @see CheckoutPage_Controller->ModifierForms()
 *
 * TO DO: get rid of all the messages...
 *
 * @authors: Silverstripe, Jeremy, Nicolaas
 *
 * @package: ecommerce
 * @sub-package: pages
 *
 **/

class CheckoutPage extends CartPage {

	public static $icon = 'ecommerce/images/icons/CheckoutPage';

	public static $db = array (
		'HasCheckoutSteps' => 'Boolean',
	);

	public static $has_one = array (
		'TermsPage' => 'Page'
	);

	/**
	 * Returns the Terms and Conditions Page (if there is one).
	 * @return DataObject (Page)
	 */
	public static function find_terms_and_conditions_page() {
		$checkoutPage = DataObject::get_one('CheckoutPage', "\"ClassName\" = 'CheckoutPage'");
		if($checkoutPage) {
			return DataObject::get_by_id('Page', $checkoutPage->TermsPageID);
		}
	}

	/**
	 * Returns the link or the Link to the account page on this site
	 * @return String (URLSegment)
	 */
	public static function find_link() {
		if ($page = DataObject::get_one("CheckoutPage")) {
			return $page->Link();
		}
		user_error("No Checkout Page has been created - it is recommended that you create this page type for correct functioning of E-commerce.", E_USER_NOTICE);
		return "";
	}

	/**
	 * Returns the link to the next step
	 * @param String - $currentStep is the step that has just been actioned....
	 * @return String (URLSegment)
	 */
	public static function find_next_step_link($currentStep, $doPreviousInstead = false) {
		if($link = self::find_link()){
			$steps = EcommerceConfig::get("CheckoutPage_Controller", "checkout_steps");
			if(in_array($currentStep, $steps)) {
				$key = array_search($currentStep, $steps);
				if($key!==FALSE) {
					if($doPreviousInstead) {
						$key--;
					}
					else {
						$key++;
					}
					if(isset($steps[$key])) {
						$nextStep = $steps[$key];
					}
				}
			}
			if($nextStep) {
				return $link."checkoutstep"."/".$nextStep."/";
			}
			return $link;
		}
		return "";
	}

	/**
	 * Returns the link to the checkout page on this site, using
	 * a specific Order ID that already exists in the database.
	 *
	 * @param int $orderID ID of the {@link Order}
	 * @param boolean $urlSegment If set to TRUE, only returns the URLSegment field
	 * @return string Link to checkout page
	 */
	public static function get_checkout_order_link($orderID) {
		if($page = self::find_link()) {
			return $page->Link("showorder") . "/" . $orderID . "/";
		}
		return "";
	}

	/**
	 * Standard SS function, we only allow for one checkout page to exist
	 *@return Boolean
	 **/
	function canCreate($member = null) {
		return !DataObject :: get_one("CheckoutPage", "\"ClassName\" = 'CheckoutPage'");
	}

	/**
	 * Standard SS function
	 *@return FieldSet
	 **/
	function getCMSFields() {
		$fields = parent :: getCMSFields();
		$fields->removeFieldFromTab('Root.Content.Messages.Messages.Actions',"ProceedToCheckoutLabel");
		$fields->removeFieldFromTab('Root.Content.Messages.Messages.Actions',"ContinueShoppingLabel");
		$fields->removeFieldFromTab('Root.Content.Messages.Messages.Actions',"ContinuePageID");
		$fields->removeFieldFromTab('Root.Content.Messages.Messages.Actions',"LoadOrderLinkLabel");
		$fields->removeFieldFromTab('Root.Content.Messages.Messages.Actions',"CurrentOrderLinkLabel");
		$fields->removeFieldFromTab('Root.Content.Messages.Messages.Actions',"SaveOrderLinkLabel");
		$fields->removeFieldFromTab('Root.Content.Messages.Messages.Actions',"DeleteOrderLinkLabel");
		$fields->addFieldToTab('Root.Content.Process', new TreeDropdownField('TermsPageID', 'Terms and Conditions Page', 'SiteTree'));
		$fields->addFieldToTab('Root.Content.Process', new CheckboxField('HasCheckoutSteps', 'Checkout Process in Steps'));
		$fields->addFieldToTab('Root.Content.Main', new HtmlEditorField('InvitationToCompleteOrder', 'Invitation to complete order ... shown when the customer can do a regular checkout', $row = 4));
		//The Content field has a slightly different meaning for the Checkout Page.
		$fields->removeFieldFromTab('Root.Content.Main', "Content");
		$fields->addFieldToTab('Root.Content.Messages.Messages.AlwaysVisible', new HtmlEditorField('Content', 'General note - always visible on the checkout page', 7, 7));
		if(DataObject::get_one("OrderModifier_Descriptor")) {
			$orderModifierDescriptionField = new ComplexTableField($controller = $this, $name = "Order Extras Explanations", "OrderModifier_Descriptor");
			$orderModifierDescriptionField->setRelationAutoSetting(false);
			$orderModifierDescriptionField->setTitle("edit messages above order extras forms");
			$orderModifierDescriptionField->setPermissions(array("show", "edit"));
			$fields->addFieldToTab('Root.Content.Messages.Messages.OrderExtras',$orderModifierDescriptionField);
		}
		if(DataObject::get_one("CheckoutPage_StepDescription")) {
			$checkoutStepDescriptionField = new ComplexTableField($controller = $this, $name = "Checkout Step Descriptions", "CheckoutPage_StepDescription");
			$checkoutStepDescriptionField->setRelationAutoSetting(false);
			$checkoutStepDescriptionField->setTitle("Checkout Step Descriptions");
			$checkoutStepDescriptionField->setPermissions(array("show", "edit"));
			$fields->addFieldToTab('Root.Content.Messages.Messages.CheckoutSteps',$checkoutStepDescriptionField);
		}
		return $fields;
	}

}

class CheckoutPage_Controller extends CartPage_Controller {

	/**
	 * FOR STEP STUFF SEE BELOW
	 **/

	/**
	 * Standard SS function
	 * if set to false, user can edit order, if set to true, user can only review order
	 **/
	public function init() {
		parent::init();
		Requirements::javascript('ecommerce/javascript/EcomPayment.js');
		$this->steps = EcommerceConfig::get("CheckoutPage_Controller", "checkout_steps");
		if($this->HasCheckoutSteps) {
			if(!$this->checkoutstep) {
				$this->currentStep = Session::get("CheckoutPage_Controller_Step");
			}
		}
	}



	/**
	 * Returns a DataObjectSet of {@link OrderModifierForm} objects. These
	 * forms are used in the OrderInformation HTML table for the user to fill
	 * in as needed for each modifier applied on the site.
	 *
	 * @return DataObjectSet
	 */
	function ModifierForms() {
		if ($this->currentOrder) {
			return $this->currentOrder->getModifierForms();
		}
	}

	/**
	 * Returns a form allowing a user to enter their
	 * details to checkout their order.
	 *
	 * @return OrderForm object
	 */
	function OrderFormAddress() {
		$form = new OrderFormAddress($this, 'OrderFormAddress');
		$this->data()->extend('updateOrderFormAddress', $form);
		//load session data
		if ($data = Session::get("FormInfo.{$form->FormName()}.data")) {
			$form->loadDataFrom($data);
		}
		return $form;
	}


	/**
	 * Returns a form allowing a user to enter their
	 * details to checkout their order.
	 *
	 * @return OrderForm object
	 */
	function OrderForm() {
		$form = new OrderForm($this, 'OrderForm');
		$this->data()->extend('updateOrderForm', $form);
		//load session data
		if ($data = Session :: get("FormInfo.{$form->FormName()}.data")) {
			$form->loadDataFrom($data);
		}
		return $form;
	}


	/**
	 * Can the user proceed? It must be an editable order (see @link CartPage)
	 * and is must also contain items.
	 *
	 * @return boolean
	 */
	function CanCheckout() {
		return $this->currentOrder->Items()  && !$this->currentOrder->IsSubmitted();
	}


	function ModifierForm($request) {
		user_error("Make sure that you set the controller for your ModifierForm to a controller directly associated with the Modifier", E_USER_WARNING);
		return array ();
	}

	/**
	 * STEP STUFF ---------------------------------------------------------------------------
	 *


	/**
	 *@var $currentStep Integer
	 **/
	protected $currentStep = "";

	/**
	 *@var Array
	 **/
	protected $steps = Array();

	/**
	 * returns a dataobject set of the steps.
	 * Or just one step if that is more relevant.
	 *
	 * @return Null | DataObject (CheckoutPage_Description) | DataObjectSet (CheckoutPage_Description)
	 */
	function Steps($number = 0) {
		$where = '';
		if($number) {
			$where = "\"CheckoutPage_StepDescription\".\"ID\" = $number";
		}
		$dos = DataObject::get("CheckoutPage_StepDescription", $where, "\"ID\" ASC");
		foreach($dos as $do) {
			if($this->currentStep && $do->Code() == $this->currentStep) {
				$do->LinkingMode = "current";
			}
			else {
				$do->LinkingMode = "link";
			}
			$do->Link = $this->Link("checkoutstep")."/".$do->Code."/";
		}
		if($number) {
			return $dos->First();
		}
		return $dos;
	}

	function StepsContentHeading($number) {
		$do = $this->Steps($number);
		if($do) {
			return $do->Heading;
		}
	}

	function StepsContentAbove($number) {
		$do = $this->Steps($number);
		if($do) {
			return $do->Above;
		}
	}

	function StepsContentBelow($number) {
		$do = $this->Steps($number);
		if($do) {
			return $do->Below;
		}
	}

	/**
	 * Show only one step in the order process (e.g. only show OrderItems)
	 */
	function checkoutstep($request) {
		$this->HasCheckoutSteps = true;
		$step = $request->Param("ID");
		if($step) {
			if (in_array($step, $this->steps)) {
				$this->currentStep = $step;
				Session::set("CheckoutPage_Controller_Step", $step);
			}
		}
		return array ();
	}


	/**
	 *@param String $part (OrderItems, OrderModifiers, OrderForm, OrderPayment)
	 *@return Boolean
	 **/
	public function CanShowStep($step) {
		if ($this->ShowOnlyCurrentStep()) {
			return ($step == $this->currentStep);
		}
		else {
			return in_array($step, $this->steps);
		}
	}

	/**
	 * Is this the final step in the process
	 * @return Boolean
	 */
	public function ShowOnlyCurrentStep(){
		return $this->currentStep ? true : false;
	}

	/**
	 * Is this the final step in the process?
	 * @return Boolean
	 */
	public function IsFinalStep(){
		foreach($this->steps as $finalStep) {
			//do nothing...
		}
		return ($this->currentStep == $finalStep);
	}


	/**
	 * returns the percentage of steps done (0 - 100)
	 * @return Integer
	 */
	public function PercentageDone(){
		return round($this->currentStepNumber() / $this->numberOfSteps(), 2) * 100;
	}

	/**
	 * returns the number of the current step (e.g. step 1)
	 * @return Integer
	 */
	protected function currentStepNumber(){
		$key = 1;
		if($this->currentStep) {
			$key = array_search($this->currentStep, $this->steps);
			$key++;
		}
		return $key;
	}

	/**
	 * returns the total number of steps (e.g. 3)
	 * @return Integer
	 */
	protected function numberOfSteps(){
		return count($this->steps);
	}



}

/***
 * Class used to describe the steps in the checkout
 *
 */

class CheckoutPage_StepDescription extends DataObject{

	static $db = array(
		"Heading" => "Varchar",
		"Above" => "Text",
		"Below" => "Text"
	);

	public static $searchable_fields = array(
		"Heading" => "PartialMatchFilter",
		"Above" => "PartialMatchFilter",
		"Below" => "PartialMatchFilter"
	);

	public static $field_labels = array(
		"Above" => "Above Checkout Step",
		"Below" => "Below Checkout Step"
	);

	public static $summary_fields = array(
		"ID" => "Step Number",
		"Code" => "Code",
		"Heading" => "Heading"
	);

	public static $casting = array(
		"Code" => "Varchar",
		"Title" => "Varchar"
	);

	public static $singular_name = "Checkout Step Description";
		function i18n_singular_name() { return _t("CheckoutPage.CHECKOUTSTEPDESCRIPTION", "Checkout Step Description");}

	public static $plural_name = "Checkout Step Descriptions";
		function i18n_plural_name() { return _t("CheckoutPage.CHECKOUTSTEPDESCRIPTIONS", "Checkout Step Descriptions");}

	static $can_create = false;

	public function canCreate($member = null) {return false;}

	public function canView($member = null) {return true;}

	public function canEdit($member = null) {return true;}

	public function canDelete($member = null) {return false;}

	function getCMSFields(){
		$fields = parent::getCMSFields();
		$fields->replaceField("Description", new TextareaField("Description", "Description", 3));
		$fields->addFieldToTab("Root.Main", new ReadonlyField("Above", "Text Above"));
		$fields->addFieldToTab("Root.Main", new ReadonlyField("Below", "Text Below"));
		return $fields;
	}

	/**
	 * casted variable
	 * @return String
	 */
	function Code(){return $this->getCode();}
	function getCode(){
		$array = EcommerceConfig::get("CheckoutPage_Controller", "checkout_steps");
		$number = $this->ID-1;
		if(is_array($array) && isset($array[$number])) {
			return $array[$number];
		}
		return _t("CheckoutPage.ERROR", "Error");
	}

	/**
	 * casted variable
	 * @return String
	 */
	function Title(){return $this->getTitle();}
	function getTitle(){
		return $this->Heading;
	}

	function requireDefaultRecords(){
		parent::requireDefaultRecords();
		$steps = EcommerceConfig::get("CheckoutPage_Controller", "checkout_steps");
		if(is_array($steps) && count($steps)) {
			foreach($steps as $id => $code) {
				$newID = $id + 1;
				if($obj = DataObject::get_by_id("CheckoutPage_StepDescription", $newID)) {
					//do nothing
				}
				else {
					$obj = new CheckoutPage_StepDescription();
					$obj->ID = $newID;
					$obj->Heading = $this->getDefaultTitle($code);
					$obj->write();
				}
			}
		}
	}

	/**
	 * turns code into title (default values)
	 * @param String $code - code
	 * @return String
	 */
	private function getDefaultTitle($code) {
		switch($code) {
			case "orderitems":
				return _t("CheckoutPage.ORDERITEMS", "Order items");
				break;
			case "orderformaddress":
				return _t("CheckoutPage.ORDERFORMADDRESS", "Your details");
				break;
			case "orderconfirmationandpayment":
				return _t("CheckoutPage.ORDERCONFIRMATIONANDPAYMENT", "Confirm and pay");
				break;
		}
		return $code;
	}

}
