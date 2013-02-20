<?php

/**
 * @description: see OrderStep.md
 *
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: model
 * @inspiration: Silverstripe Ltd, Jeremy
 **/

class OrderStep extends DataObject {

	/**
	 * standard SS variable
	 * @return Array
	 */
	public static $db = array(
		"Name" => "Varchar(50)",
		"Code" => "Varchar(50)",
		"Description" => "Text",
		"EmailSubject" => "Varchar(200)",
		"CustomerMessage" => "HTMLText",
		//customer privileges
		"CustomerCanEdit" => "Boolean",
		"CustomerCanCancel" => "Boolean",
		"CustomerCanPay" => "Boolean",
		//What to show the customer...
		"ShowAsUncompletedOrder" => "Boolean",
		"ShowAsInProcessOrder" => "Boolean",
		"ShowAsCompletedOrder" => "Boolean",
		"HideStepFromCustomer" => "Boolean",
		//sorting index
		"Sort" => "Int"
	);

	/**
	 * standard SS variable
	 * @return Array
	 */
	public static $indexes = array(
		"Code" => true,
		"Sort" => true
	);

	/**
	 * standard SS variable
	 * @return Array
	 */
	public static $has_many = array(
		"Orders" => "Order",
		"OrderEmailRecords" => "OrderEmailRecord"
	);

	/**
	 * standard SS variable
	 * @return Array
	 */
	public static $field_labels = array(
		"Sort" => "Sorting Index",
		"CustomerCanEdit" => "Customer can edit order",
		"CustomerCanPay" => "Customer can pay order",
		"CustomerCanCancel" => "Customer can cancel order"
	);

	/**
	 * standard SS variable
	 * @return Array
	 */
	public static $summary_fields = array(
		"Name" => "Name",
		"CustomerCanEditNice" => "customer can edit",
		"ShowAsUncompletedOrderNice" => "uncomplete",
		"ShowAsInProcessOrderNice" => "in process",
		"ShowAsCompletedOrderNice" => "complete",
		"HideStepFromCustomerNice" => "hide step from customer",
		"HasCustomerMessageNice" => "includes message to customer"
	);

	/**
	 * standard SS variable
	 * @return Array
	 */
	public static $casting = array(
		"CustomerCanEditNice" => "Varchar",
		"CustomerCanPayNice" => "Varchar",
		"CustomerCanCancelNice" => "Varchar",
		"ShowAsUncompletedOrderNice" => "Varchar",
		"ShowAsInProcessOrderNice" => "Varchar",
		"ShowAsCompletedOrderNice" => "Varchar",
		"HideStepFromCustomerNice" => "Varchar",
		"HasCustomerMessageNice" => "Varchar"
	);

	/**
	 * standard SS variable
	 * @return Array
	 */
	public static $searchable_fields = array(
		'Name' => array(
			'title' => 'Name',
			'filter' => 'PartialMatchFilter'
		),
		'Code' => array(
			'title' => 'Code',
			'filter' => 'PartialMatchFilter'
		)
	);

	/**
	 * casted variable
	 * @return String
	 */
	function CustomerCanEditNice() {return $this->getCustomerCanEditNice();}
		function getCustomerCanEditNice() {if($this->CustomerCanEdit) {return _t("OrderStep.YES", "Yes");}return _t("OrderStep.NO", "No");}


	/**
	 * casted variable
	 * @return String
	 */
	function CustomerCanPayNice() {return $this->getCustomerCanPayNice();}
		function getCustomerCanPayNice() {if($this->CustomerCanPay) {return _t("OrderStep.YES", "Yes");}return _t("OrderStep.NO", "No");}


	/**
	 * casted variable
	 * @return String
	 */
	function CustomerCanCancelNice() {return $this->getCustomerCanCancelNice();}
		function getCustomerCanCancelNice() {if($this->CustomerCanCancel) {return _t("OrderStep.YES", "Yes");}return _t("OrderStep.NO", "No");}

	function ShowAsUncompletedOrderNice() {return $this->getShowAsUncompletedOrderNice();}
	function getShowAsUncompletedOrderNice() {if($this->ShowAsUncompletedOrder) {return _t("OrderStep.YES", "Yes");}return _t("OrderStep.NO", "No");}

	/**
	 * casted variable
	 * @return String
	 */
	function ShowAsInProcessOrderNice() {return $this->getShowAsInProcessOrderNice();}
		function getShowAsInProcessOrderNice() {if($this->ShowAsInProcessOrder) {return _t("OrderStep.YES", "Yes");}return _t("OrderStep.NO", "No");}

	/**
	 * casted variable
	 * @return String
	 */
	function ShowAsCompletedOrderNice() {return $this->getShowAsCompletedOrderNice();}
		function getShowAsCompletedOrderNice() {if($this->ShowAsCompletedOrder) {return _t("OrderStep.YES", "Yes");}return _t("OrderStep.NO", "No");}

	/**
	 * casted variable
	 * @return String
	 */
	function HideStepFromCustomerNice() {return $this->getHideStepFromCustomerNice();}
		function getHideStepFromCustomerNice() {if($this->HideStepFromCustomer) {return _t("OrderStep.YES", "Yes");}return _t("OrderStep.NO", "No");}

	/**
	 * standard SS variable
	 * @return String
	 */
	public static $singular_name = "Order Step";
		function i18n_singular_name() { return _t("OrderStep.ORDERSTEP", "Order Step");}

	/**
	 * standard SS variable
	 * @return String
	 */
	public static $plural_name = "Order Steps";
		function i18n_plural_name() { return _t("OrderStep.ORDERSTEPS", "Order Steps");}

	/**
	 * SUPER IMPORTANT TO KEEP ORDER!
	 * standard SS variable
	 * @return String
	 */
	public static $default_sort = "\"Sort\" ASC";

	/**
	 * turns code into ID
	 * @param String $code
	 * @param Int
	 */
	public static function get_status_id_from_code($code) {
		if($otherStatus = DataObject::get_one("OrderStep", "\"Code\" = '".$code."'")) {
			return $otherStatus->ID;
		}
		return 0;
	}

	/**
	 *
	 *@return Array
	 **/
	static function get_codes_for_order_steps_to_include() {
		$newArray = array();
		$array = EcommerceConfig::get("OrderStep", "order_steps_to_include");
		if(is_array($array) && count($array)) {
			foreach($array as $className) {
				$code = singleton($className)->getMyCode();
				$newArray[$className] = strtoupper($code);
			}
		}
		return $newArray;
	}

	/**
	 *
	 *@return Array
	 **/
	static function get_not_created_codes_for_order_steps_to_include() {
		$array = EcommerceConfig::get("OrderStep", "order_steps_to_include");
		if(is_array($array) && count($array)) {
			foreach($array as $className) {
				if(DataObject::get_one($className)) {
					unset($array[$className]);
				}
			}
		}
		return $array;
	}

	/**
	 *
	 *@return String
	 **/
	function getMyCode() {
		$array = Object::uninherited_static($this->ClassName, 'defaults');
		if(!isset($array["Code"])) {user_error($this->class." does not have a default code specified");}
		return $array["Code"];
	}

	/**
	 * IMPORTANT:: MUST HAVE Code must be defined!!!
	 * standard SS variable
	 * @return Array
	 */
	public static $defaults = array(
		"CustomerCanEdit" => 0,
		"CustomerCanCancel" => 0,
		"CustomerCanPay" => 1,
		"ShowAsUncompletedOrder" => 0,
		"ShowAsInProcessOrder" => 0,
		"ShowAsCompletedOrder" => 0,
		"Code" => "ORDERSTEP"
	);

	/**
	 * standard SS method
	 */
	function populateDefaults() {
		parent::populateDefaults();
		$this->Description = $this->myDescription();
	}

	/**
	 *
	 *@return Fieldset
	 **/
	function getCMSFields() {
		$fields = parent::getCMSFields();
		//replacing
		if($this->hasCustomerMessage()) {
			$fields->addFieldToTab("Root.CustomerMessage", new TextField("EmailSubject", _t("OrderStep.EMAILSUBJECT", "Email Subject (if any), you can use [OrderNumber] as a tag that will be replaced with the actual Order Number.")));
			$fields->addFieldToTab("Root.CustomerMessage", new HTMLEditorField("CustomerMessage", _t("OrderStep.CUSTOMERMESSAGE", "Customer Message (if any)"), 5));
			if($testEmailLink = $this->testEmailLink()) {
				$fields->addFieldToTab("Root.CustomerMessage", new LiteralField("testEmailLink", "<p><a href=\"".$testEmailLink."\" target=\"_blank\">"._t("OrderStep.VIEW_EMAIL_EXAMPLE", "View email example in browser")."</a></p>"));
			}
		}
		else {
			$fields->removeFieldFromTab("Root.Main", "EmailSubject");
			$fields->removeFieldFromTab("Root.Main", "CustomerMessage");
		}
		//adding
		if(!$this->exists() || !$this->isDefaultStatusOption()) {
			$fields->removeFieldFromTab("Root.Main", "Code");
			$fields->addFieldToTab("Root.Main", new DropdownField("ClassName", _t("OrderStep.TYPE", "Type"), self::get_not_created_codes_for_order_steps_to_include()), "Name");
		}
		if($this->isDefaultStatusOption()) {
			$fields->replaceField("Code", $fields->dataFieldByName("Code")->performReadonlyTransformation());
		}
		//headers
		$fields->addFieldToTab("Root.Main", new HeaderField("WARNING1", _t("OrderStep.CAREFUL", "CAREFUL! please edit with care"), 1), "Description");
		$fields->addFieldToTab("Root.Main", new HeaderField("WARNING2", _t("OrderStep.CUSTOMERCANCHANGE", "What can be changed during this step?"), 3), "CustomerCanEdit");
		$fields->addFieldToTab("Root.Main", new HeaderField("WARNING5", _t("OrderStep.ORDERGROUPS", "Order groups for customer?"), 3), "ShowAsUncompletedOrder");
		$fields->addFieldToTab("Root.Main", new HeaderField("WARNING7", _t("OrderStep.SORTINGINDEXHEADER", "Index Number (lower number come first)"), 3), "Sort");
		$orderTable = new HasManyComplexTableField(
			$this,
			"Orders", //$name
			"Order", //$sourceClass =
			null, //$fieldList =
			null, //$detailedFormFields =
			"\"StatusID\" = ".$this->ID."", //$sourceFilter =
			"\"ID\" DESC", //$sourceSort =
			null //$sourceJoin =
		);
		$orderTable->setPageSize(20);
		$orderTable->setPermissions(array('export', 'show'));
		$fields->addFieldToTab('Root.Orders',$orderTable);
		$fields->addFieldToTab("Root.Main", new TextareaField("Description", _t("OrderStep.DESCRIPTION", "Explanation for internal use only"), 5), "WARNING1");
		return $fields;
	}


	/**
	 * tells the order to display itself with an alternative display page.
	 * in that way, orders can be displayed differently for certain steps
	 * for example, in a print step, the order can be displayed in a
	 * PRINT ONLY format.
	 *
	 * When the method return null, the order is displayed using the standard display page
	 * @see Order::DisplayPage
	 *
	 *
	 * @return Null|Object (Page)
	 **/
	public function AlternativeDisplayPage() {
		return null;
	}

	/**
	 * Allows the opportunity for the Order Step to add any fields to Order::getCMSFields
	 * Usually this is added before ActionNextStepManually
	 *@param FieldSet $fields
	 *@param Order $order
	 *@return FieldSet
	 **/
	public function addOrderStepFields(&$fields, $order) {
		return $fields;
	}

	/**
	 *
	 *@return ValidationResult
	 **/
	function validate() {
		$result = DataObject::get_one(
			"OrderStep",
			" (\"Name\" = '".$this->Name."' OR \"Code\" = '".strtoupper($this->Code)."') AND \"OrderStep\".\"ID\" <> ".intval($this->ID));
		if($result) {
			return new ValidationResult(false, _t("OrderStep.ORDERSTEPALREADYEXISTS", "An order status with this name already exists. Please change the name and try again."));
		}
		$result = ($this->ClassName == "OrderStep" ? true : false);
		if($result) {
			return new ValidationResult(false, _t("OrderStep.ORDERSTEPCLASSNOTSELECTED", "You need to select the right order status class."));
		}
		return parent::validate();
	}


/**************************************************
* moving between statusses...
**************************************************/
	/**
	 * initStep:
	 * makes sure the step is ready to run.... (e.g. check if the order is ready to be emailed as receipt).
	 * should be able to run this function many times to check if the step is ready
	 * @see Order::doNextStatus
	 * @param Order object
	 * @return Boolean - true if the current step is ready to be run...
	 **/
	public function initStep($order) {
		user_error("Please implement the initStep method in a subclass (".get_class().") of OrderStep", E_USER_WARNING);
		return true;
	}

	/**
	 *doStep:
	 * should only be able to run this function once (init stops you from running it twice - in theory....)
	 *runs the actual step
	 * @see Order::doNextStatus
	 *@param Order object
	 * @return Boolean - true if run correctly
	 **/
	public function doStep($order) {
		user_error("Please implement the initStep method in a subclass (".get_class().") of OrderStep", E_USER_WARNING);
		return true;
	}

	/**
	 * nextStep:
	 * returns the next step (checks if everything is in place for the next step to run...)
	 * @see Order::doNextStatus
	 * @param Order object
	 * @return DataObject | Null (next step OrderStep object)
	 **/
	public function nextStep($order) {
		$nextOrderStepObject = DataObject::get_one("OrderStep", "\"Sort\" > ".$this->Sort);
		if($nextOrderStepObject) {
			return $nextOrderStepObject;
		}
		return null;
	}



/**************************************************
* Boolean checks
**************************************************/

	/**
	 *
	 *@return Boolean
	 **/
	public function hasPassed($code, $orIsEqualTo = false) {
		$otherStatus = DataObject::get_one("OrderStep", "\"Code\" = '".$code."'");
		if($otherStatus) {
			if($otherStatus->Sort < $this->Sort) {
				return true;
			}
			if($orIsEqualTo && $otherStatus->Code == $this->Code) {
				return true;
			}
		}
		else {
			user_error("could not find $code in OrderStep", E_USER_NOTICE);
		}
		return false;
	}

	/**
	 *
	 *@return Boolean
	 **/
	public function hasPassedOrIsEqualTo($code) {
		return $this->hasPassed($code, true);
	}

	/**
	 *
	 *@return Boolean
	 **/
	public function hasNotPassed($code) {
		return (bool)!$this->hasPassed($code, true);
	}

	/**
	 *
	 *@return Boolean
	 **/
	public function isBefore($code) {
		return (bool)!$this->hasPassed($code, false);
	}

	/**
	 *
	 *@return Boolean
	 **/
	protected function isDefaultStatusOption() {
		return in_array($this->Code, self::get_codes_for_order_steps_to_include());
	}






/**************************************************
* Email
**************************************************/

	/**
	 * @var String
	 */
	protected $emailClassName = "";

	/**
	 * returns the email class used for emailing the
	 * customer during a specific step (IF ANY!)
	 * @return String
	 */
	public function getEmailClassName(){
		return $this->emailClassName;
	}

	/**
	 * sets the email class used for emailing the
	 * customer during a specific step (IF ANY!)
	 * @param String
	 */
	public function setEmailClassName($s){
		$this->emailClassName = $s;
	}

	/**
	 * returns a link that can be used to test
	 * the email being sent during this step
	 * this method returns NULL if no email
	 * is being sent OR if there is no suitable Order
	 * to test with...
	 * @return String
	 */
	protected function testEmailLink(){
		if($this->getEmailClassName()) {
			$orders = DataObject::get(
				"Order",
				"\"OrderStep\".\"Sort\" >= ".$this->Sort,
				"IF(\"OrderStep\".\"Sort\" > ".$this->Sort.", 0, 1) ASC, \"OrderStep\".\"Sort\" ASC, RAND() ASC",
				"INNER JOIN \"OrderStep\" ON \"OrderStep\".\"ID\" = \"Order\".\"StatusID\""
			);
			if($orders && $orders->count()) {
				if($order = $orders->First()) {
					return OrderConfirmationPage::get_email_link($order->ID, $this->getEmailClassName(), $actuallySendEmail = false, $alternativeOrderStepID = $this->ID);
				}
			}
		}
	}

	/**
	 * Has an email been sent to the customer for this
	 * order step.
	 *
	 * @param Order $order
	 * @param Boolean $sendEvenIfDelayed
	 *
	 * @return Boolean
	 **/
	public function hasBeenSent($order, $checkDateOfOrder = true) {
		//if it has been more than a week since the order was last edited (submitted) then we do not send emails as
		//this would be embarrasing.
		if( $checkDateOfOrder && (strtotime($order->LastEdited) < strtotime("-10 days"))) {
			return true;
		}
		return DataObject::get_one("OrderEmailRecord", "\"OrderEmailRecord\".\"OrderID\" = ".$order->ID." AND \"OrderEmailRecord\".\"OrderStepID\" = ".$this->ID." AND	\"OrderEmailRecord\".\"Result\" = 1");
	}

	/**
	 * For some ordersteps this returns true...
	 * @return Boolean
	 **/
	protected function hasCustomerMessage() {
		return false;
	}

	/**
	 * Formatted answer for "hasCustomerMessage"
	 * @return String
	 */
	public function HasCustomerMessageNice() {return $this->getHasCustomerMessageNice();}
	public function getHasCustomerMessageNice() {
		return $this->hasCustomerMessage() ?  _t("OrderStep.YES", "Yes") :  _t("OrderStep.NO", "No");
	}






/**************************************************
* Order Status Logs
**************************************************/

	/**
	 * The OrderStatusLog that is relevant to the particular step.
	 * @var String
	 */
	protected $relevantLogEntryClassName = "";

	/**
	 * @return string
	 */
	public function getRelevantLogEntryClassName(){
		return $this->relevantLogEntryClassName;
	}

	/**
	 * @param String
	 */
	public function setRelevantLogEntryClassName($s){
		$this->relevantLogEntryClassName = $s;
	}

	/**
	 * returns the OrderStatusLog that is relevant to this step.
	 * @param Order $order
	 * @return OrderStatusLog
	 */
	public function RelevantLogEntry(Order $order){
		if($className = $this->getRelevantLogEntryClassName()) {
			return DataObject::get_one($className, "\"OrderID\" = ".$order->ID);
		}
	}





/**************************************************
* Silverstripe Standard Data Object Methods
**************************************************/

	/**
	 *
	 *@return Boolean
	 **/
	public function canDelete($member = null) {
		//cant delete last status if there are orders with this status
		$nextOrderStepObject = DataObject::get_one("OrderStep", "\"Sort\" > ".intval($this->Sort) -0);
		if(!$nextOrderStepObject) {
			if($ordersWithThisStatus = DataObject::get_one("Order", "\"StatusID\" =".intval($this->ID)-0)) {
				return false;
			}
		}
		if($this->isDefaultStatusOption()) {
			return false;
		}
		if(in_array($this->Code, self::get_codes_for_order_steps_to_include())) {
			return false;
		}
		return true;
	}

	/**
	 *
	 *@return Boolean
	 **/
	public function canCreate($member = null) {
		return false;
	}

	/**
	 * standard SS method
	 */
	function onBeforeWrite() {
		parent::onBeforeWrite();
		$this->Code = strtoupper($this->Code);
	}

	/**
	 * move linked orders to the next status
	 * standard SS method
	 */
	function onBeforeDelete() {
		parent::onBeforeDelete();
		$nextOrderStepObject = DataObject::get_one("OrderStep", "\"Sort\" > ".$this->Sort);
		if($nextOrderStepObject) {
			$ordersWithThisStatus = DataObject::get("Order", "\"StatusID\" =".$this->ID);
			if($ordersWithThisStatus) {
				foreach($ordersWithThisStatus as $orderWithThisStatus) {
					$orderWithThisStatus->StatusID = $nextOrderStepObject->ID;
					$orderWithThisStatus->write();
				}
			}
		}
	}

	/**
	 * standard SS method
	 */
	function onAfterDelete() {
		parent::onAfterDelete();
		$this->requireDefaultRecords();
	}


	/**
	 * standard SS method
	 * USED TO BE: Unpaid,Query,Paid,Processing,Sent,Complete,AdminCancelled,MemberCancelled,Cart
	 */
	public function requireDefaultRecords() {
		parent::requireDefaultRecords();
		$orderStepsToInclude = EcommerceConfig::get("OrderStep", "order_steps_to_include");
		$codesToInclude = self::get_codes_for_order_steps_to_include();
		$indexNumber = 0;
		if($orderStepsToInclude && count($orderStepsToInclude)) {
			if($codesToInclude && count($codesToInclude)) {
				foreach($codesToInclude as $className => $code) {
					$indexNumber +=10;
					if(!DataObject::get_one($className)) {
						if(!DataObject::get_one("OrderStep", "\"Code\" = '".strtoupper($code)."'")) {
							$obj = new $className();
							$obj->Code = strtoupper($obj->Code);
							$obj->Description = $obj->myDescription();
							$obj->write();
							DB::alteration_message("Created \"$code\" as $className.", "created");
						}
					}
					$obj = DataObject::get_one("OrderStep", "\"Code\" = '".strtoupper($code)."'");
					if($obj) {
						if($obj->Sort != $indexNumber) {
							$obj->Sort = $indexNumber;
							$obj->write();
						}
					}
				}
			}
		}
		$steps = DataObject::get("OrderStep");
		foreach($steps as $step) {
			if(!$step->Description) {
				$step->Description = $step->myDescription();
				$step->write();
			}
		}
		/*
		 * This was causing errors
		$otherOrderSteps = DataObject::get("OrderStep", "\"ClassName\" NOT IN ('".implode("', '", $orderStepsToInclude)."')");
		if($otherOrderSteps) {
			foreach($otherOrderSteps as $otherOrderStep) {
				DB::alteration_message("Deleting OrderStep ".$otherOrderStep->Code, "deleted");
				$otherOrderStep->delete();
			}
		}
		*/
	}

	/**
	 * returns the standard EcommerceDBConfig for use within OrderSteps.
	 * @return EcommerceDBConfig
	 */
	protected function EcomConfig(){
		return EcommerceDBConfig::current_ecommerce_db_config();
	}

	/**
	 * Explains the current order step.
	 * @return String
	 */
	protected function myDescription(){
		return _t("OrderStep.DESCRIPTION", "No description has been provided for this step.");
	}

}

/**
 * This is the first Order Step.
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: model
 * @inspiration: Silverstripe Ltd, Jeremy
 **/

class OrderStep_Created extends OrderStep {

	public static $defaults = array(
		"CustomerCanEdit" => 1,
		"CustomerCanPay" => 1,
		"CustomerCanCancel" => 1,
		"Name" => "Create",
		"Code" => "CREATED",
		"ShowAsUncompletedOrder" => 1
	);

	/**
	 * Can always run step.
	 *@param DataObject - $order Order
	 *@return Boolean
	 **/
	public function initStep($order) {
		return true;
	}

	/**
	 * Add the member to the order, in case the member is not an admin.
	 *@param DataObject - $order Order
	 *@return Boolean
	 **/
	public function doStep($order) {
		if(!$order->MemberID) {
			$member = Member::currentUser();
			if($member) {
				if(!$member->IsShopAdmin()) {
					$order->MemberID = $member->ID();
					$order->write();
				}
			}
		}
		return true;
	}

	/**
	 * We can run the next step, once any items have been added.
	 * @param DataObject - $order Order
	 * @return DataObject | Null (nextStep DataObject)
	 **/
	public function nextStep($order) {
		if($order->TotalItems($recalculate = true)) {
			return parent::nextStep($order);
		}
		return null;
	}

	/**
	 * Allows the opportunity for the Order Step to add any fields to Order::getCMSFields
	 *@param FieldSet $fields
	 *@param Order $order
	 *@return FieldSet
	 **/
	function addOrderStepFields(&$fields, $order) {
		$fields = parent::addOrderStepFields($fields, $order);
		if(!$order->IsSubmitted()) {
			//LINE BELOW IS NOT REQUIRED
			$header = _t("OrderStep.SUBMITORDER", "Submit Order");
			$label = _t("OrderStep.SUBMITNOW", "Submit Now");
			$msg = _t("OrderStep.MUSTDOSUBMITRECORD", "<p>Tick the box below to submit this order.</p>");
			$problems = array();
			if(!$order->Items()) {
				$problems[] = "There are no items associated with this order.";
			}
			if(!$order->MemberID) {
				$problems[] = "There is no customer associated with this order.";
			}
			if(!$order->BillingAddressID) {
				$problems[] = "There is no billing address associated with this order.";
			}
			elseif($billingAddress = $order->BillingAddress()) {
				$requiredBillingFields = $billingAddress->getRequiredFields();
				if($requiredBillingFields && is_array($requiredBillingFields) && count($requiredBillingFields)) {
					foreach($requiredBillingFields as $requiredBillingField) {
						if(!$billingAddress->$requiredBillingField) {
							$problems[] = "There is no -- $requiredBillingField -- recorded in the billing address.";
						}
					}
				}
			}
			if(count($problems)) {
				$msg = "<p>You can not submit this order because:</p> <ul><li>".implode("</li><li>", $problems)."</li></ul>";
			}
			$fields->addFieldToTab("Root.Next", new HeaderField("CreateSubmitRecordHeader", $header, 3), "ActionNextStepManually");
			$fields->addFieldToTab("Root.Next", new LiteralField("CreateSubmitRecordMessage", $msg), "ActionNextStepManually");
			if(!$problems) {
				$fields->addFieldToTab("Root.Next", new CheckboxField("SubmitOrderViaCMS", $label), "ActionNextStepManually");
			}
		}
		return $fields;
	}

	/**
	 * Explains the current order step.
	 * @return String
	 */
	protected function myDescription(){
		return _t("OrderStep.CREATED_DESCRIPTION", "During this step the customer creates her or his order. The shop admininistrator does not do anything during this step.");
	}

}


/**
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: model
 * @inspiration: Silverstripe Ltd, Jeremy
 **/
class OrderStep_Submitted extends OrderStep {

	static $db = array(
		"SaveOrderAsHTML" => "Boolean",
		"SaveOrderAsSerializedObject" => "Boolean",
		"SaveOrderAsJSON" => "Boolean"
	);

	static $defaults = array(
		"CustomerCanEdit" => 0,
		"CustomerCanPay" => 1,
		"CustomerCanCancel" => 0,
		"Name" => "Submit",
		"Code" => "SUBMITTED",
		"ShowAsInProcessOrder" => 1,
		"SaveOrderAsHTML" => 1,
		"SaveOrderAsSerializedObject" => 0,
		"SaveOrderAsJSON" => 0
	);

	/**
	 * The OrderStatusLog that is relevant to the particular step.
	 * @var String
	 */
	protected $relevantLogEntryClassName = "OrderStatusLog_Submitted";

	/**
	 * @return string
	 */
	public function getRelevantLogEntryClassName(){
		return EcommerceConfig::get("OrderStatusLog", "order_status_log_class_used_for_submitting_order");
	}

	public function getCMSFields() {
		$fields = parent::getCMSFields();
		$fields->addFieldToTab("Root.Main", new HeaderField("HowToSaveSubmittedOrder", _t("OrderStep.HOWTOSAVESUBMITTEDORDER", "How would you like to make a backup of your order at the moment it is submitted?"), 3), "SaveOrderAsHTML");
		return $fields;
	}

	/**
	 * Can run this step once any items have been submitted.
	 *@param DataObject - $order Order
	 *@return Boolean
	 **/
	public function initStep($order) {
		return (bool) $order->TotalItems($recalculate = true);
	}

	/**
	 * Add a member to the order - in case he / she is not a shop admin.
	 *@param DataObject - $order Order
	 *@return Boolean
	 **/
	public function doStep($order) {
		if(!$order->IsSubmitted()) {
			$className = $this->getRelevantLogEntryClassName();
			if(class_exists($className)) {
				$obj = new $className();
				if($obj instanceOf OrderStatusLog) {
					//save versions
					//@todo: test and implement
					/*
					if($this->Attributes()->exists()){
						foreach($this->Attributes() as $attribute){
							if($attribute instanceof OrderItem){
								$buyable = $attribute->Buyable();
								if(isset($buyable->Version)) {
									$attribute->Version = $buyable->Version;
									$attribute->write();
								}
							}
						}
          }
          */
					$obj->OrderID = $order->ID;
					$obj->Title = $this->Name;
					//it is important we add this here so that we can save the 'submitted' version.
					//this is particular important for the Order Item Links.
					$obj->write();
					$saved = false;
					if($this->SaveOrderAsJSON)												{$obj->OrderAsJSON = $order->ConvertToJSON(); $saved = true;}
					if($this->SaveOrderAsHTML)												{$obj->OrderAsHTML = $order->ConvertToHTML(); $saved = true;}
					if($this->SaveOrderAsSerializedObject || !$saved)	{$obj->OrderAsString = $order->ConvertToString();$saved = true; }
					$obj->write();
				}
				else {
					user_error('EcommerceConfig::get("OrderStatusLog", "order_status_log_class_used_for_submitting_order") refers to a class that is NOT an instance of OrderStatusLog');
				}
			}
			else {
				user_error('EcommerceConfig::get("OrderStatusLog", "order_status_log_class_used_for_submitting_order") refers to a non-existing class');
			}
			$order->LastEdited = "'".SS_Datetime::now()->Rfc2822()."'";
			$order->write($showDebug = false, $forceInsert = false, $forceWrite = true);
		}
		return true;
	}

	/**
	 * go to next step if order has been submitted.
	 *@param DataObject - $order Order
	 *@return DataObject | Null	(next step OrderStep)
	 **/
	public function nextStep($order) {
		if($order->IsSubmitted()) {
			return parent::nextStep($order);
		}
		return null;
	}


	/**
	 * Allows the opportunity for the Order Step to add any fields to Order::getCMSFields
	 *@param FieldSet $fields
	 *@param Order $order
	 *@return FieldSet
	 **/
	function addOrderStepFields(&$fields, $order) {
		$fields = parent::addOrderStepFields($fields, $order);
		$msg = _t("OrderStep.CANADDGENERALLOG", " ... if you want to make some notes about this step then do this here...");
		$fields->addFieldToTab("Root.Next", $order->OrderStatusLogsTable("OrderStatusLog", $msg),"ActionNextStepManually");
		return $fields;
	}

	/**
	 * Explains the current order step.
	 * @return String
	 */
	protected function myDescription(){
		return _t("OrderStep.SUBMITTED_DESCRIPTION", "The official moment the order gets submitted by the customer. The hand-shake for a commercial transaction.");
	}

}

/**
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: model
 * @inspiration: Silverstripe Ltd, Jeremy
 **/

class OrderStep_SentInvoice extends OrderStep {

	/**
	 * @var String
	 */
	protected $emailClassName = "Order_InvoiceEmail";

	static $db = array(
		"SendInvoiceToCustomer" => "Boolean"
	);

	public static $defaults = array(
		"CustomerCanEdit" => 0,
		"CustomerCanCancel" => 0,
		"CustomerCanPay" => 1,
		"Name" => "Send invoice",
		"Code" => "INVOICED",
		"ShowAsInProcessOrder" => 1,
		"SendInvoiceToCustomer" => 0
	);

	public function getCMSFields() {
		$fields = parent::getCMSFields();
		$fields->addFieldToTab("Root.Main", new HeaderField("ActuallySendTheInvoice", _t("OrderStep.ACTUALLYSENDTHEINVOICE", "Actually send the invoice? "), 3), "SendInvoiceToCustomer");
		return $fields;
	}

	/**
	 * can run step once order has been submitted.
	 * NOTE: must have a payment (even if it is a fake payment).
	 * The reason for this is if people pay straight away then they want to see the payment shown on their invoice.
	 * @param DataObject $order Order
	 * @return Boolean
	 **/
	public function initStep($order) {
		if( $order->IsSubmitted()) {
			if($payments = $order->Payments()) {
				if($payments->count()) {
					return true;
				}
			}
		}
		return false;

	}

	/**
	 * send invoice to customer
	 * or in case this is not selected, it will send a message to the shop admin only
	 * The latter is useful in case the payment does not go through (and no receipt is received).
	 * @param DataObject $order Order
	 * @return Boolean
	 **/
	public function doStep($order) {
		$subject = $this->EmailSubject;
		$message = "";
		if($this->SendInvoiceToCustomer){
			if(!$this->hasBeenSent($order)) {
				return $order->sendEmail($subject, $message, $resend = false, $adminOnly = false, $this->getEmailClassName());
			}
		}
		else {
			if(!$this->hasBeenSent($order)) {
				//looks like we are sending an error, but we are actually just sending a message to admin
				$message = _t("OrderStep.THISMESSAGENOTSENTTOCUSTOMER", "NOTE: This message was not sent to the customer.")."<br /><br /><br /><br />".$message;
				return $order->sendAdminNotification($subject, $message);
			}
		}
		return true;
	}

	/**
	 * can do next step once the invoice has been sent or in case the invoice does not need to be sent.
	 * @param DataObject $order Order
	 * @return DataObject | Null	(next step OrderStep object)
	 **/
	public function nextStep($order) {
		if(!$this->SendInvoiceToCustomer || $this->hasBeenSent($order)) {
			return parent::nextStep($order);
		}
		return null;
	}

	/**
	 * Allows the opportunity for the Order Step to add any fields to Order::getCMSFields
	 *@param FieldSet $fields
	 *@param Order $order
	 *@return FieldSet
	 **/
	function addOrderStepFields(&$fields, $order) {
		$fields = parent::addOrderStepFields($fields, $order);
		$msg = _t("OrderStep.CANADDGENERALLOG", " ... if you want to make some notes about this step then do this here...");
		$fields->addFieldToTab("Root.Next", $order->OrderStatusLogsTable("OrderStatusLog", $msg),"ActionNextStepManually");
		return $fields;
	}

	/**
	 * For some ordersteps this returns true...
	 * @return Boolean
	 **/
	protected function hasCustomerMessage() {
		return $this->SendInvoiceToCustomer;
	}

	/**
	 * Explains the current order step.
	 * @return String
	 */
	protected function myDescription(){
		return _t("OrderStep.SENTINVOICE_DESCRIPTION", "Invoice gets sent to the customer via e-mail. In many cases, it is better to only send a receipt and sent the invoice to the shop admin only so that they know an order is coming, while the customer only sees a receipt which shows payment as well as ");
	}
}


/**
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: model
 * @inspiration: Silverstripe Ltd, Jeremy
 **/

class OrderStep_Paid extends OrderStep {

	public static $defaults = array(
		"CustomerCanEdit" => 0,
		"CustomerCanCancel" => 0,
		//the one below may seem a bit paradoxical, but the thing is that the customer can pay up to and inclusive of this step
		//that ist he code PAID means that the Order has been paid ONCE this step is completed
		"CustomerCanPay" => 1,
		"Name" => "Pay",
		"Code" => "PAID",
		"ShowAsInProcessOrder" => 1
	);

	public function initStep($order) {
		return true;
	}

	public function doStep($order) {
		return true;
	}

	/**
	 * can go to next step if order has been paid
	 *@param DataObject $order Order
	 *@return DataObject | Null	(next step OrderStep object)
	 **/
	public function nextStep($order) {
		if($order->IsPaid()) {
			return parent::nextStep($order);
		}
		return null;
	}

	/**
	 * Allows the opportunity for the Order Step to add any fields to Order::getCMSFields
	 *@param FieldSet $fields
	 *@param Order $order
	 *@return FieldSet
	 **/
	function addOrderStepFields(&$fields, $order) {
		$fields = parent::addOrderStepFields($fields, $order);
		if(!$order->IsPaid()) {
			$header = _t("OrderStep.SUBMITORDER", "Order NOT Paid");
			$msg = _t("OrderStep.ORDERNOTPAID", "This order can not be completed, because it has not been paid. You can either create a payment or change the status of any existing payment to <i>success</i>.");
			$fields->addFieldToTab("Root.Next", new HeaderField("NotPaidHeader", $header, 3), "ActionNextStepManually");
			$fields->addFieldToTab("Root.Next", new LiteralField("NotPaidMessage", '<p>'.$msg.'</p>'), "ActionNextStepManually");
		}
		return $fields;
	}

	/**
	 * Explains the current order step.
	 * @return String
	 */
	protected function myDescription(){
		return _t("OrderStep.PAID_DESCRIPTION", "The order is paid in full.");
	}

}

/**
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: model
 * @inspiration: Silverstripe Ltd, Jeremy
 **/

class OrderStep_Confirmed extends OrderStep {

	public static $defaults = array(
		"CustomerCanEdit" => 0,
		"CustomerCanCancel" => 0,
		"CustomerCanPay" => 0,
		"Name" => "Confirm",
		"Code" => "CONFIRMED",
		"ShowAsInProcessOrder" => 1
	);

	/**
	 * @var String
	 */
	protected $relevantLogEntryClassName = "OrderStatusLog_PaymentCheck";

	public function initStep($order) {
		return true;
	}

	public function doStep($order) {
		return true;
	}

	/**
	 * can go to next step if order payment has been confirmed...
	 *@param DataObject $order Order
	 *@return DataObject | Null - DataObject = OrderStep
	 **/
	public function nextStep($order) {
		if(DataObject::get_one($this->getRelevantLogEntryClassName(), "\"OrderID\" = ".$order->ID." AND \"PaymentConfirmed\" = 1")) {
			return parent::nextStep($order);
		}
		return null;
	}

	/**
	 * Allows the opportunity for the Order Step to add any fields to Order::getCMSFields
	 * @param FieldSet $fields
	 * @param Order $order
	 * @return FieldSet
	 **/
	function addOrderStepFields(&$fields, $order) {
		$fields = parent::addOrderStepFields($fields, $order);
		$msg = _t("OrderStep.MUSTDOPAYMENTCHECK", " ... To move this order to the next step you must carry out a payment check (is the money in the bank?) by creating a record here (click me)");
		$fields->addFieldToTab("Root.Next", $order->OrderStatusLogsTable("OrderStatusLog_PaymentCheck", $msg),"ActionNextStepManually");
		$fields->addFieldToTab("Root.Next", new LiteralField("ExampleOfThingsToCheck", EcommerceConfig::get("OrderStep_Confirmed", "list_of_things_to_check")));
		return $fields;
	}

	/**
	 * Explains the current order step.
	 * @return String
	 */
	protected function myDescription(){
		return _t("OrderStep.CONFIRMED_DESCRIPTION", "The shop administrator confirms all the details for the current order.");
	}

}

/**
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: model
 * @inspiration: Silverstripe Ltd, Jeremy
 **/

class OrderStep_SentReceipt extends OrderStep {

	/**
	 * @var String
	 */
	protected $emailClassName = "Order_ReceiptEmail";

	static $db = array(
		"SendReceiptToCustomer" => "Boolean"
	);

	public static $defaults = array(
		"CustomerCanEdit" => 0,
		"CustomerCanCancel" => 0,
		"CustomerCanPay" => 0,
		"Name" => "Send receipt",
		"Code" => "RECEIPTED",
		"ShowAsInProcessOrder" => 1,
		"SendReceiptToCustomer" => 1
	);


	public function getCMSFields() {
		$fields = parent::getCMSFields();
		$fields->addFieldToTab("Root.CustomerMessage", new HeaderField("ActuallySendReceiptToCustomer", _t("OrderStep.ACTUALLYSENDRECEIPT", "Actually send the receipt?"), 3));
		$fields->addFieldToTab("Root.CustomerMessage", new CheckboxField("SendReceiptToCustomer", _t("OrderStep.SENDRECEIPTTOCUSTOMER", "Send receipt to customer?"), 3));
		return $fields;
	}

	public function initStep($order) {
		return $order->IsPaid();
	}

	public function doStep($order) {
		$subject = $this->EmailSubject;
		$message = "";
		if($this->SendReceiptToCustomer){
			if(!$this->hasBeenSent($order)) {
				$order->sendEmail($subject, $message, $resend = false, $adminOnly = false, $this->getEmailClassName());
			}
		}
		else {
			//looks like we are sending an error, but we are just using this for notification
			if(!$this->hasBeenSent($order)) {
				$message = _t("OrderStep.THISMESSAGENOTSENTTOCUSTOMER", "NOTE: This message was not sent to the customer.")."<br /><br /><br /><br />".$message;
				return $order->sendAdminNotification($subject, $message);
			}
		}
		return true;
	}

	/**
	 * can continue if receipt has been sent or if there is no need to send a receipt.
	 *@param DataObject $order Order
	 *@return DataObject | Null - DataObject = next OrderStep
	 **/
	public function nextStep($order) {
		if(!$this->SendReceiptToCustomer || $this->hasBeenSent($order)) {
			return parent::nextStep($order);
		}
		return null;
	}


	/**
	 * Allows the opportunity for the Order Step to add any fields to Order::getCMSFields
	 *@param FieldSet $fields
	 *@param Order $order
	 *@return FieldSet
	 **/
	function addOrderStepFields(&$fields, $order) {
		$fields = parent::addOrderStepFields($fields, $order);
		$msg = _t("OrderStep.CANADDGENERALLOG", " ... if you want to make some notes about this step then do this here...)");
		$fields->addFieldToTab("Root.Next", $order->OrderStatusLogsTable("OrderStatusLog", $msg),"ActionNextStepManually");
		return $fields;
	}


	/**
	 * For some ordersteps this returns true...
	 * @return Boolean
	 **/
	protected function hasCustomerMessage() {
		return $this->SendReceiptToCustomer;
	}

	/**
	 * Explains the current order step.
	 * @return String
	 */
	protected function myDescription(){
		return _t("OrderStep.SENTRECEIPT_DESCRIPTION", "The customer is sent a receipt.");
	}

}

/**
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: model
 * @inspiration: Silverstripe Ltd, Jeremy
 **/
class OrderStep_Sent extends OrderStep {

	/**
	 * @var String
	 */
	protected $emailClassName = "Order_StatusEmail";

	static $db = array(
		"SendDetailsToCustomer" => "Boolean"
	);

	public static $defaults = array(
		"CustomerCanEdit" => 0,
		"CustomerCanCancel" => 0,
		"CustomerCanPay" => 0,
		"Name" => "Send order",
		"Code" => "SENT",
		"ShowAsCompletedOrder" => 1
	);

	/**
	 * The OrderStatusLog that is relevant to the particular step.
	 * @var String
	 */
	protected $relevantLogEntryClassName = "OrderStatusLog_DispatchPhysicalOrder";

	public function getCMSFields() {
		$fields = parent::getCMSFields();
		$fields->addFieldToTab("Root.Main", new HeaderField("ActuallySendDetails", _t("OrderStep.ACTUALLYSENDDETAILS", "Send details to the customer?"), 3), "SendDetailsToCustomer");
		return $fields;
	}

	public function initStep($order) {
		return true;
	}

	public function doStep($order) {
		return true;
	}

	/**
	 *
	 *@param DataObject $order Order
	 *@return DataObject | Null - DataObject = OrderStep
	 **/
	public function nextStep($order) {
		if($this->RelevantLogEntry($order)) {
			$subject = $this->EmailSubject;
			$message = "";
			if($this->SendDetailsToCustomer){
				if(!$this->hasBeenSent($order)) {
					$subject = $this->EmailSubject;
					$order->sendEmail($subject, $message, $resend = false, $adminOnly = false, $this->getEmailClassName());
				}
			}
			else {
				if(!$this->hasBeenSent($order)) {
					//looks like we are sending an error, but we are just using this for notification
					$message = _t("OrderStep.THISMESSAGENOTSENTTOCUSTOMER", "NOTE: This message was not sent to the customer.")."<br /><br /><br /><br />".$message;
					$order->sendAdminNotification($subject, $message);
				}
			}
			return parent::nextStep($order);
		}
		return null;
	}

	/**
	 * Allows the opportunity for the Order Step to add any fields to Order::getCMSFields
	 * @param FieldSet $fields
	 * @param Order $order
	 * @return FieldSet
	 **/
	function addOrderStepFields(&$fields, $order) {
		$fields = parent::addOrderStepFields($fields, $order);
		$msg = _t("OrderStep.MUSTENTERDISPATCHRECORD", " ... To move this order to the next step you enter the dispatch details in the logs.");
		$fields->addFieldToTab("Root.Next", $order->OrderStatusLogsTable("OrderStatusLog_DispatchPhysicalOrder", $msg),"ActionNextStepManually");
		return $fields;
	}

	/**
	 * For some ordersteps this returns true...
	 * @return Boolean
	 **/
	protected function hasCustomerMessage() {
		return $this->SendDetailsToCustomer;
	}

	/**
	 * Explains the current order step.
	 * @return String
	 */
	protected function myDescription(){
		return _t("OrderStep.SENT_DESCRIPTION", "During this step we record the delivery details for the order such as the courrier ticket number and whatever else is relevant.");
	}

}

/**
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: model
 * @inspiration: Silverstripe Ltd, Jeremy
 **/

class OrderStep_Archived extends OrderStep {

	public static $defaults = array(
		"CustomerCanEdit" => 0,
		"CustomerCanCancel" => 0,
		"CustomerCanPay" => 0,
		"Name" => "Archived order",
		"Code" => "ARCHIVED",
		"ShowAsCompletedOrder" => 1
	);

	public function initStep($order) {
		return true;
	}

	public function doStep($order) {
		return true;
	}

	/**
	 *
	 *@param DataObject $order Order
	 *@return DataObject | Null - DataObject = OrderStep
	 **/
	public function nextStep($order) {
		//IMPORTANT
		return null;
	}

	/**
	 * Allows the opportunity for the Order Step to add any fields to Order::getCMSFields
	 *@param FieldSet $fields
	 *@param Order $order
	 *@return FieldSet
	 **/
	function addOrderStepFields(&$fields, $order) {
		$fields = parent::addOrderStepFields($fields, $order);
		$msg = _t("OrderStep.CANADDGENERALLOG", " ... if you want to make some notes about this order then do this here ...");
		$fields->addFieldToTab("Root.Next", $order->OrderStatusLogsTable("OrderStatusLog_Archived", $msg),"ActionNextStepManually");
		return $fields;
	}

	/**
	 * Explains the current order step.
	 * @return String
	 */
	protected function myDescription(){
		return _t("OrderStep.ARCHIVED_DESCRIPTION", "This is typically the last step in the order process. Nothing needs to be done to the order anymore.  We keep the order in the system for record-keeping and statistical purposes.");
	}

}


