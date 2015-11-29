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

class OrderStep extends DataObject implements EditableEcommerceObject {

	/**
	 * standard SS variable
	 * @return Array
	 */
	private static $db = array(
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
	private static $indexes = array(
		"Code" => true,
		"Sort" => true
	);

	/**
	 * standard SS variable
	 * @return Array
	 */
	private static $has_many = array(
		"Orders" => "Order",
		"OrderEmailRecords" => "OrderEmailRecord"
	);

	/**
	 * standard SS variable
	 * @return Array
	 */
	private static $field_labels = array(
		"Sort" => "Sorting Index",
		"CustomerCanEdit" => "Customer can edit order",
		"CustomerCanPay" => "Customer can pay order",
		"CustomerCanCancel" => "Customer can cancel order"
	);

	/**
	 * standard SS variable
	 * @return Array
	 */
	private static $summary_fields = array(
		"Name" => "Name",
		"ShowAsSummary" => "Phase"
	);

	/**
	 * standard SS variable
	 * @return Array
	 */
	private static $casting = array(
		"Title" => "Varchar",
		"CustomerCanEditNice" => "Varchar",
		"CustomerCanPayNice" => "Varchar",
		"CustomerCanCancelNice" => "Varchar",
		"ShowAsUncompletedOrderNice" => "Varchar",
		"ShowAsInProcessOrderNice" => "Varchar",
		"ShowAsCompletedOrderNice" => "Varchar",
		"HideStepFromCustomerNice" => "Varchar",
		"HasCustomerMessageNice" => "Varchar",
		"ShowAsSummary" => "HTMLText"
	);

	/**
	 * standard SS variable
	 * @return Array
	 */
	private static $searchable_fields = array(
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
	function Title() {return $this->getTitle();}
		function getTitle() {return $this->Name;}


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
	private static $singular_name = "Order Step";
		function i18n_singular_name() { return _t("OrderStep.ORDERSTEP", "Order Step");}

	/**
	 * standard SS variable
	 * @return String
	 */
	private static $plural_name = "Order Steps";
		function i18n_plural_name() { return _t("OrderStep.ORDERSTEPS", "Order Steps");}

	/**
	 * Standard SS variable.
	 * @var String
	 */
	private static $description = "A step that any order goes through.";


	/**
	 * SUPER IMPORTANT TO KEEP ORDER!
	 * standard SS variable
	 * @return String
	 */
	private static $default_sort = "\"Sort\" ASC";

	/**
	 * turns code into ID
	 * @param String $code
	 * @param Int
	 */
	public static function get_status_id_from_code($code) {
		$otherStatus = OrderStep::get()->filter(array("Code" => $code))->First();
		if($otherStatus) {
			return $otherStatus->ID;
		}
		return 0;
	}

	/**
	 *
	 *@return Array
	 **/
	public static function get_codes_for_order_steps_to_include() {
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
	 * returns a list of ordersteps that have not been created yet.
	 * @return Array
	 **/
	public static function get_not_created_codes_for_order_steps_to_include() {
		$array = EcommerceConfig::get("OrderStep", "order_steps_to_include");
		if(is_array($array) && count($array)) {
			foreach($array as $className) {
				$obj = $className::get()->First();
				if($obj) {
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
		$array = Config::inst()->get($this->ClassName, "defaults", Config::UNINHERITED);
		if(!isset($array["Code"])) {user_error($this->class." does not have a default code specified");}
		return $array["Code"];
	}

	/**
	 * IMPORTANT:: MUST HAVE Code must be defined!!!
	 * standard SS variable
	 * @return Array
	 */
	private static $defaults = array(
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
	 *@return FieldList
	 **/
	function getCMSFields() {
		$fields = parent::getCMSFields();
		//replacing
		if($this->hasCustomerMessage()) {
			$fields->addFieldToTab("Root.CustomerMessage", new TextField("EmailSubject", _t("OrderStep.EMAILSUBJECT", "Email Subject (if any), you can use [OrderNumber] as a tag that will be replaced with the actual Order Number.")));
			$fields->addFieldToTab("Root.CustomerMessage", $htmlEditorField = new HTMLEditorField("CustomerMessage", _t("OrderStep.CUSTOMERMESSAGE", "Customer Message (if any)")));
			if($testEmailLink = $this->testEmailLink()) {
				$fields->addFieldToTab("Root.CustomerMessage", new LiteralField("testEmailLink", "<p><a href=\"".$testEmailLink."\" data-popup=\"true\">"._t("OrderStep.VIEW_EMAIL_EXAMPLE", "View email example in browser")."</a></p>"));
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
		$fields->addFieldToTab("Root.Main", new HeaderField("WARNING1", _t("OrderStep.CAREFUL", "CAREFUL! please edit details below with care"), 2), "Description");
		$fields->addFieldToTab("Root.Main", new HeaderField("WARNING2", _t("OrderStep.CUSTOMERCANCHANGE", "What can be changed during this step?"), 3), "CustomerCanEdit");
		$fields->addFieldToTab("Root.Main", new HeaderField("WARNING5", _t("OrderStep.ORDERGROUPS", "Order groups for customer?"), 3), "ShowAsUncompletedOrder");
		$fields->addFieldToTab("Root.Main", new HeaderField("HideStepFromCustomerHeader", _t("OrderStep.HIDE_STEP_FROM_CUSTOMER_HEADER", "Customer Interaction"), 3), "HideStepFromCustomer");
		//final cleanup
		$fields->removeFieldFromTab("Root.Main", "Sort");
		$fields->addFieldToTab("Root.Main", new TextareaField("Description", _t("OrderStep.DESCRIPTION", "Explanation for internal use only")), "WARNING1");
		return $fields;
	}

	/**
	 * link to edit the record
	 * @param String | Null $action - e.g. edit
	 * @return String
	 */
	public function CMSEditLink($action = null) {
		return Controller::join_links(
			Director::baseURL(),
			"/admin/shop/".$this->ClassName."/EditForm/field/".$this->ClassName."/item/".$this->ID."/",
			$action
		);
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
	 *
	 * @param FieldList $fields
	 * @param Order $order
	 * @return FieldList
	 **/
	public function addOrderStepFields(FieldList $fields, Order $order) {
		return $fields;
	}

	/**
	 *
	 *@return ValidationResult
	 **/
	function validate() {
		$result = parent::validate();
		$anotherOrderStepWithSameNameOrCode = OrderStep::get()
			->filter(
				array(
					"Name" => $this->Name,
					"Code" => strtoupper($this->Code)
				)
			)
			->exclude(array("ID" => intval($this->ID)))
			->First();
		if($anotherOrderStepWithSameNameOrCode) {
			$result->error( _t("OrderStep.ORDERSTEPALREADYEXISTS", "An order status with this name already exists. Please change the name and try again."));
		}
		return $result;
	}


/**************************************************
* moving between statusses...
**************************************************/
	/**
	 *initStep:
	 * makes sure the step is ready to run.... (e.g. check if the order is ready to be emailed as receipt).
	 * should be able to run this function many times to check if the step is ready
	 * @see Order::doNextStatus
	 * @param Order object
	 * @return Boolean - true if the current step is ready to be run...
	 **/
	public function initStep(Order $order) {
		user_error("Please implement the initStep method in a subclass (".get_class().") of OrderStep", E_USER_WARNING);
		return true;
	}

	/**
	 *doStep:
	 * should only be able to run this function once
	 * (init stops you from running it twice - in theory....)
	 * runs the actual step
	 * @see Order::doNextStatus
	 * @param Order object
	 * @return Boolean - true if run correctly.
	 **/
	public function doStep(Order $order) {
		user_error("Please implement the initStep method in a subclass (".get_class().") of OrderStep", E_USER_WARNING);
		return true;
	}

	/**
	 * nextStep:
	 * returns the next step (after it checks if everything is in place for the next step to run...)
	 * @see Order::doNextStatus
	 * @param Order $order
	 * @return OrderStep | Null (next step OrderStep object)
	 **/
	public function nextStep(Order $order) {
		$nextOrderStepObject = OrderStep::get()
			->filter(array("Sort:GreaterThan" => $this->Sort))
			->First();
		if($nextOrderStepObject) {
			return $nextOrderStepObject;
		}
		return null;
	}

/**************************************************
* Boolean checks
**************************************************/

	/**
	 * Checks if a step has passed (been completed) in comparison to the current step
	 *
	 * @param String $code: the name of the step to check
	 * @param Boolean $orIsEqualTo if set to true, this method will return TRUE if the step being checked is the current one
	 * @return Boolean
	 **/
	public function hasPassed($code, $orIsEqualTo = false) {
		$otherStatus = OrderStep::get()
			->filter(array("Code" => $code))
			->First();
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
	 * @param String $code
	 * @return Boolean
	 **/
	public function hasPassedOrIsEqualTo($code) {
		return $this->hasPassed($code, true);
	}

	/**
	 * @param String $code
	 * @return Boolean
	 **/
	public function hasNotPassed($code) {
		return (bool)!$this->hasPassed($code, true);
	}

	/**
	 * Opposite of hasPassed
	 * @param String $code
	 * @return Boolean
	 **/
	public function isBefore($code) {
		return (bool) $this->hasPassed($code, false) ? false : true;
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
	 * return true if one already or done now.
	 * 
	 * @return boolean;
	 */ 
	protected function sendEmailForStep(){
		if(!$this->hasBeenSent($order)) {
			$subject = $this->EmailSubject;
			$message = "";
			if($this->SendDetailsToCustomer){
				return $order->sendEmail($subject, $message, $resend = false, $adminOnly = false, $this->getEmailClassName());
			}
			else {
				//looks like we are sending an error, but we are just using this for notification
				$message = _t("OrderStep.THISMESSAGENOTSENTTOCUSTOMER", "NOTE: This message was not sent to the customer.")."<br /><br /><br /><br />".$message;
				return $order->sendAdminNotification($subject, $message);
			}
		}
		return true;
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
			$orders = Order::get()
				->where("\"OrderStep\".\"Sort\" >= ".$this->Sort)
				->sort("IF(\"OrderStep\".\"Sort\" > ".$this->Sort.", 0, 1) ASC, \"OrderStep\".\"Sort\" ASC, RAND() ASC")
				->innerJoin("OrderStep", "\"OrderStep\".\"ID\" = \"Order\".\"StatusID\"");
			if($orders->count()) {
				if($order = $orders->First()) {
					return OrderConfirmationPage::get_email_link($order->ID, $this->getEmailClassName(), $actuallySendEmail = false, $alternativeOrderStepID = $this->ID);
				}
			}
		}
	}

	/**
	 * Has an email been sent to the customer for this
	 * order step.
	 *"-10 days"
	 *
	 * @param Order $order
	 * @param Boolean $checkDateOfOrder
	 * @return Boolean
	 **/
	public function hasBeenSent(Order $order, $checkDateOfOrder = true) {
		//if it has been more than a XXX days since the order was last edited (submitted) then we do not send emails as
		//this would be embarrasing.
		if( $checkDateOfOrder) {
			if($log = $order->SubmissionLog()) {
				$lastEditedValue = $log->LastEdited;
			}
			else {
				$lastEditedValue = $order->LastEdited;
			}
			if((strtotime($lastEditedValue) < strtotime("-".EcommerceConfig::get("OrderStep", "number_of_days_to_send_update_email")." days"))) {
				return true;
			}
		}
		$count = OrderEmailRecord::get()
			->Filter(array(
				"OrderID" => $order->ID,
				"OrderStepID" => $this->ID,
				"Result" => 1
			))
			->count();
		return $count ? true : false;
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


	/**
	 * Formatted answer for "hasCustomerMessage"
	 * @return String
	 */
	public function ShowAsSummary() {return $this->getShowAsSummary();}
	public function getShowAsSummary() {
		$v = "<strong>";
		if($this->ShowAsUncompletedOrder) {
			$v .= _t("OrderStep.UNCOMPLETED", "Uncompleted");
		}
		elseif($this->ShowAsInProcessOrder) {
			$v .= _t("OrderStep.INPROCESS", "In process");
		}
		elseif($this->ShowAsCompletedOrder) {
			$v .= _t("OrderStep.COMPLETED", "Completed");
		}
		$v .= "</strong>";
		$canArray = array();
		if($this->CustomerCanEdit) {
			$canArray[] = _t("OrderStep.EDITABLE", "edit");
		}
		if($this->CustomerCanPay) {
			$canArray[] = _t("OrderStep.PAY", "pay");
		}
		if($this->CustomerCanCancel) {
			$canArray[] = _t("OrderStep.CANCEL", "cancel");
		}
		if(count($canArray)){
			$v .=  "<br />"._t("OrderStep.CUSTOMER_CAN", "Customer Can").": ".implode(", ", $canArray)."";
		}
		if($this->hasCustomerMessage()) {
			$v .= "<br />"._t("OrderStep.CUSTOMER_MESSAGES", "Includes message to customer");
		}
		return DBField::create_field("HTMLText", $v);
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
	 * @return OrderStatusLog | Null
	 */
	public function RelevantLogEntry(Order $order){
		if($className = $this->getRelevantLogEntryClassName()) {
			return $className::get()->filter(array("OrderID" => $order->ID))->First();
		}
	}





/**************************************************
* Silverstripe Standard Data Object Methods
**************************************************/


	/**
	 * Standard SS method
	 * These are only created programmatically.
	 * @param Member $member
	 * @return Boolean
	 */
	public function canCreate($member = null) {
		return false;
	}

	/**
	 * Standard SS method
	 * @param Member $member
	 * @return Boolean
	 */
	public function canView($member = null) {
		if(Permission::checkMember($member, Config::inst()->get("EcommerceRole", "admin_permission_code"))) {return true;}
		return parent::canEdit($member);
	}

	/**
	 * standard SS method
	 * @param Member | NULL
	 * @return Boolean
	 */
	public function canEdit($member = null){
		if(Permission::checkMember($member, Config::inst()->get("EcommerceRole", "admin_permission_code"))) {return true;}
		return parent::canEdit($member);
	}

	/**
	 * Standard SS method
	 * @param Member $member
	 * @return Boolean
	 */
	public function canDelete($member = null) {
		//cant delete last status if there are orders with this status
		$nextOrderStepObject = $this->NextOrderStep();
		if($nextOrderStepObject) {
			//do nothing
		}
		else{
			$orderCount = Order::get()
				->filter(array("StatusID" => intval($this->ID)-0))
				->count();
			if($orderCount) {
				return false;
			}
		}
		if($this->isDefaultStatusOption()) {
			return false;
		}
		if(in_array($this->Code, self::get_codes_for_order_steps_to_include())) {
			return false;
		}
		if(Permission::checkMember($member, Config::inst()->get("EcommerceRole", "admin_permission_code"))) {return true;}
		return parent::canEdit($member);
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
		$nextOrderStepObject = $this->NextOrderStep();
		//backup
		if($nextOrderStepObject) {
			//do nothing
		}
		else {
			$previousOrderStepObject = $this->PreviousOrderStep();
		}
		if($previousOrderStepObject) {
			$ordersWithThisStatus = Order::get()->filter(array("StatusID" => $this->ID));
			if($ordersWithThisStatus && $ordersWithThisStatus->count()) {
				foreach($ordersWithThisStatus as $orderWithThisStatus) {
					$orderWithThisStatus->StatusID = $previousOrderStepObject->ID;
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

	protected function NextOrderStep(){
		return OrderStep::get()
			->filter(array("Sort:GreaterThan" => $this->Sort))
			->First();
	}

	protected function PreviousOrderStep(){
		return OrderStep::get()
			->filter(array("Sort:LessThan" => $this->Sort))
			->First();
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
					$itemCount = $className::get()->Count();
					if($itemCount) {
						$obj = $className::get()->First();
						if($obj->Code != $code) {
							$obj->Code = $code;
							$obj->write();
						}
						$parentObj = singleton("OrderStep");
						if($obj->Description == $parentObj->myDescription()) {
							$obj->Description = $obj->myDescription();
							$obj->write();
						}
					}
					else {
						$obj = new $className();
						$obj->Code = strtoupper($obj->Code);
						$obj->Description = $obj->myDescription();
						$obj->write();
						DB::alteration_message("Created \"$code\" as $className.", "created");
					}
					$obj = OrderStep::get()
						->filter(array("Code" => strtoupper($code)))
						->First();
					if($obj) {
						if($obj->Sort != $indexNumber) {
							$obj->Sort = $indexNumber;
							$obj->write();
						}
					}
					else {
						user_error("There was an error in creating the $code OrderStep");
					}
				}
			}
		}
		$steps = OrderStep::get();
		foreach($steps as $step) {
			if(!$step->Description) {
				$step->Description = $step->myDescription();
				$step->write();
			}
		}
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
