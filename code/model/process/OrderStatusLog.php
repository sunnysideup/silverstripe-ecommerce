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

class OrderStatusLog extends DataObject {

	/**
	 * standard SS variable
	 * @var Array
	 */
	public static $db = array(
		'Title' => 'Varchar(100)',
		'Note' => 'HTMLText',
		'InternalUseOnly' => 'Boolean'
	);

	/**
	 * standard SS variable
	 * @var Array
	 */
	public static $has_one = array(
		"Author" => "Member",
		"Order" => "Order"
	);

	/**
	 * standard SS variable
	 * @var Array
	 */
	public static $casting = array(
		"CustomerNote" => "HTMLText",
		"Type" => "Varchar",
		"InternalUseOnlyNice" => "Varchar"
	);

	/**
	 * standard SS variable
	 * @var Array
	 */
	public static $summary_fields = array(
		"Created" => "Date",
		"Type" => "Type",
		"Title" => "Title",
		"InternalUseOnlyNice" => "Internal use only"
	);

	/**
	 * standard SS variable
	 * @var Array
	 */
	public static $defaults = array(
		"InternalUseOnly" => true
	);

	/**
	 * casted method
	 * @return String
	 */
	function InternalUseOnlyNice() {return $this->getInternalUseOnlyNice();}
	function getInternalUseOnlyNice() {if($this->InternalUseOnly) { return _t("OrderStatusLog.YES", "Yes");} return _t("OrderStatusLog.No", "No");}


	/**
	 * Standard SS method
	 * @param Member $member
	 * @return Boolean
	 */
	public function canView($member = null) {
		if(!$member) {
			$member = Member::currentUser();
		}
		if($member && $member->IsShopAdmin()) {
			return true;
		}
		if(!$this->InternalUseOnly) {
			if($this->Order()) {
				if($this->Order()->MemberID == $member->ID) {
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * Standard SS method
	 * @param Member $member
	 * @return Boolean
	 */
	public function canDelete($member = null) {
		return false;
	}

	/**
	 * Standard SS method
	 * @param Member $member
	 * @return Boolean
	 */
	public function canCreate($member = null) {
		return true;
	}

	/**
	 * Standard SS method
	 * @param Member $member
	 * @return Boolean
	 */
	public function canEdit($member = null) {
		if($order = $this->Order()) {
			return $order->canEdit($member);
		}
		return false;
	}

	/**
	 * standard SS variable
	 * @var Array
	 */
	public static $searchable_fields = array(
		'OrderID' => array(
			'field' => 'NumericField',
			'title' => 'Order Number'
		),
		"ClassName" => array(
			'title' => 'Type',
			'filter' => 'ExactMatchFilter'
		),
		"Title" => "PartialMatchFilter",
		"Note" => "PartialMatchFilter"
	);

	/**
	 * standard SS variable
	 * @var String
	 */
	public static $singular_name = "Order Log Entry";
		function i18n_singular_name() { return _t("OrderStatusLog.ORDERLOGENTRY", "Order Log Entry");}

	/**
	 * standard SS variable
	 * @var String
	 */
	public static $plural_name = "Order Log Entries";
		function i18n_plural_name() { return _t("OrderStatusLog.ORDERLOGENTRIES", "Order Log Entries");}

	/**
	 * Standard SS variable.
	 * @var String
	 */
	public static $description = "A record of anything that happened with an order.";

	/**
	 * standard SS variable
	 * @var String
	 */
	public static $default_sort = "\"Created\" DESC";

	/**
	 * standard SS method
	 */
	function populateDefaults() {
		parent::populateDefaults();
		$this->AuthorID = Member::currentUserID();
	}

	/**
	*
	*@return FieldList
	**/
	function getCMSFields() {

		$fields = parent::getCMSFields();
		$fields->dataFieldByName("Note")->setRows(3);
		$fields->dataFieldByName("Title")->setTitle("Subject");
		$fields->replaceField("AuthorID", $fields->dataFieldByName("AuthorID")->performReadonlyTransformation());

		//OrderID Field
		$fields->removeByName("OrderID");
		if($this->exists() && $this->OrderID && $this->Order()->exists()) {
			$fields->addFieldToTab("Root.Main", new ReadOnlyField("OrderTitle", _t("OrderStatusLog.ORDER_TITLE", "Order Title"), $this->Order()->Title()));
		}

		//ClassName Field
		$availableLogs = EcommerceConfig::get("OrderStatusLog", "available_log_classes_array");
		$availableLogs = array_merge($availableLogs, array(EcommerceConfig::get("OrderStatusLog", "order_status_log_class_used_for_submitting_order")));
		$ecommerceClassNameOrTypeDropdownField = new EcommerceClassNameOrTypeDropdownField("ClassName", _t("OrderStatusLog.TYPE", "Type"), "OrderStatusLog", $availableLogs);
		$ecommerceClassNameOrTypeDropdownField->setIncludeBaseClass(true);
		$fields->addFieldToTab("Root.Main", $ecommerceClassNameOrTypeDropdownField, "Title");
		if($this->exists()) {
			$classNameField = $fields->dataFieldByName("ClassName");
			$fields->replaceField("ClassName", $classNameField->performReadonlyTransformation());
		}
		return $fields;
	}


	/**
	 *
	 * @return String
	 **/
	function Type() {return $this->getType();}
	function getType() {
		return $this->i18n_singular_name();
	}

	/**
	 * Determine which properties on the DataObject are
	 * searchable, and map them to their default {@link FormField}
	 * representations. Used for scaffolding a searchform for {@link ModelAdmin}.
	 *
	 * Some additional logic is included for switching field labels, based on
	 * how generic or specific the field type is.
	 *
	 * Used by {@link SearchContext}.
	 *
	 * @param array $_params
	 * 	'fieldClasses': Associative array of field names as keys and FormField classes as values
	 * 	'restrictFields': Numeric array of a field name whitelist
	 * @return FieldList
	 */
	public function scaffoldSearchFields($_params = null) {
		$fields = parent::scaffoldSearchFields($_params);
		$fields->replaceField("OrderID", new NumericField("OrderID", "Order Number"));
		$availableLogs = EcommerceConfig::get("OrderStatusLog", "available_log_classes_array");
		$availableLogs = array_merge($availableLogs, array(EcommerceConfig::get("OrderStatusLog", "order_status_log_class_used_for_submitting_order")));
		$ecommerceClassNameOrTypeDropdownField = new EcommerceClassNameOrTypeDropdownField("ClassName", "Type", "OrderStatusLog", $availableLogs);
		$ecommerceClassNameOrTypeDropdownField->setIncludeBaseClass(true);
		$fields->replaceField("ClassName", $ecommerceClassNameOrTypeDropdownField);
		return $fields;
	}

	/**
	 * standard SS method
	 *
	 */
	function onBeforeWrite() {
		parent::onBeforeWrite();
		//START HACK TO PREVENT LOSS OF ORDERID CAUSED BY COMPLEX TABLE FIELDS....
		// THIS MEANS THAT A LOG CAN NEVER SWITCH FROM ONE ORDER TO ANOTHER...
		if($this->exists()) {
			$orderID = $this->getField("OrderID");
			if($orderID) {
				$this->OrderID = $orderID;
			}
		}
		//END HACK TO PREVENT LOSS
		if(!$this->AuthorID) {
			if($member = Member::currentUser()) {
				$this->AuthorID = $member->ID;
			}
		}
		if(!$this->Title) {
			$this->Title = _t("OrderStatusLog.ORDERUPDATE", "Order Update");
		}
	}

	/**
	 *
	 *@return String
	 **/
	function CustomerNote() {return $this->getCustomerNote();}
	function getCustomerNote() {
		return $this->Note;
	}


	/**
	 * returns the standard EcommerceDBConfig for use within OrderSteps.
	 * @return EcommerceDBConfig
	 */
	protected function EcomConfig(){
		return EcommerceDBConfig::current_ecommerce_db_config();
	}

	/**
	 * Debug helper method.
	 * Can be called from /shoppingcart/debug/
	 * @return String
	 */
	public function debug() {
		return EcommerceTaskDebugCart::debug_object($this);
	}

}



/**
 * OrderStatusLog_Submitted is an important class that is created when an order is submitted.
 * It is created by the order and it signifies to the OrderStep to continue to the next step.
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: model
 * @inspiration: Silverstripe Ltd, Jeremy
 **/

class OrderStatusLog_Submitted extends OrderStatusLog {

	public static $db = array(
		"OrderAsHTML" => "HTMLText",
		"OrderAsString" => "Text",
		"OrderAsJSON" => "Text",
		"SequentialOrderNumber" => "Int",
		"Total" => "Currency",
		"SubTotal" => "Currency"
	);

	public static $defaults = array(
		"InternalUseOnly" => true
	);

	public static $casting = array(
		"HTMLRepresentation" => "HTMLText"
	);

	public static $singular_name = "Submitted Order";
		function i18n_singular_name() { return _t("OrderStatusLog.SUBMITTEDORDER", "Submitted Order - Fulltext Backup");}

	public static $plural_name = "Submitted Orders";
		function i18n_plural_name() { return _t("OrderStatusLog.SUBMITTEDORDERS", "Submitted Orders - Fulltext Backup");}

	/**
	 * Standard SS variable.
	 * @var String
	 */
	public static $description = "The record that the order has been submitted by the customer.  This is important in e-commerce, because from here, nothing can change to the order.";

	/**
	 * Standard SS method
	 * @param Member $member
	 * @return Boolean
	 */
	public function canDelete($member = null) {
		return false;
	}

	/**
	 * Standard SS method
	 * @param Member $member
	 * @return Boolean
	 */
	public function canEdit($member = null) {
		return false;
	}

	/**
	 * Standard SS method
	 * @param Member $member
	 * @return Boolean
	 */
	public function canCreate($member = null) {
		return true;
	}


	/**
	* can only be created when the order is submitted
	*@return String
	**/
	function HTMLRepresentation(){return $this->getHTMLRepresentation();}
	function getHTMLRepresentation(){
		if($this->OrderAsHTML) {
			return $this->OrderAsHTML;
		}
		elseif($this->OrderAsString) {
			return unserialize($this->OrderAsString);
		}
		else {
			return $this->OrderAsJSON;
		}
	}

	/**
	 * adding a sequential order number.
	 */
	function onBeforeWrite() {
		parent::onBeforeWrite();
		if($order = $this->Order()) {
			if(!$this->Total) {
				$this->Total = $order->Total();
				$this->SubTotal = $order->SubTotal();
			}
		}
		if(!intval($this->SequentialOrderNumber)) {
			$min = intval(EcommerceConfig::get("Order", "order_id_start_number"));
			if(isset($this->ID)) {
				$id = intval($this->ID);
			}
			else {
				$id = 0;
			}
			$lastOne = OrderStatusLog_Submitted::get()
				->Filter(array("ID" => $id))
				->Sort("SequentialOrderNumber", "DESC")
				->First();
			if($lastOne) {
				$this->SequentialOrderNumber = intval($lastOne->SequentialOrderNumber) + 1;
				if($this->SequentialOrderNumber < $min) {
					$this->SequentialOrderNumber = $min;
				}
			}
			else {
				$this->SequentialOrderNumber = $min;
			}
		}
		if(!intval($this->SequentialOrderNumber)) {
			$this->SequentialOrderNumber = 1;
		}
	}

}



/**
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: model
 * @inspiration: Silverstripe Ltd, Jeremy
 **/
class OrderStatusLog_Cancel extends OrderStatusLog {

	public static $defaults = array(
		"Title" => "Order Cancelled",
		"InternalUseOnly" => false
	);

	public static $singular_name = "Cancelled Order";
		function i18n_singular_name() { return _t("OrderStatusLog.SUBMITTEDORDER", "Cancelled Order");}

	public static $plural_name = "Cancelled Orders";
		function i18n_plural_name() { return _t("OrderStatusLog.SUBMITTEDORDERS", "Cancelled Orders");}

	/**
	 * Standard SS variable.
	 * @var String
	 */
	public static $description = "A record noting the cancellation of an order.  ";

	/**
	 * Standard SS method
	 * @param Member $member
	 * @return Boolean
	 */
	public function canDelete($member = null) {
		return false;
	}

	/**
	 * Standard SS method
	 * @param Member $member
	 * @return Boolean
	 */
	public function canEdit($member = null) {
		return false;
	}

	/**
	 * Standard SS method
	 * @param Member $member
	 * @return Boolean
	 */
	public function canCreate($member = null) {
		return false;
	}


}

/**
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: model
 * @inspiration: Silverstripe Ltd, Jeremy
 **/
class OrderStatusLog_Dispatch extends OrderStatusLog {

	public static $defaults = array(
		"InternalUseOnly" => true
	);

	public static $singular_name = "Order Log Dispatch Entry";
		function i18n_singular_name() { return _t("OrderStatusLog.ORDERLOGDISPATCHENTRY", "Order Log Dispatch Entry");}

	public static $plural_name = "Order Log Dispatch Entries";
		function i18n_plural_name() { return _t("OrderStatusLog.ORDERLOGDISPATCHENTRIES", "Order Log Dispatch Entries");}

	/**
	 * Standard SS method
	 * @param Member $member
	 * @return Boolean
	 */
	public function canDelete($member = null) {
		return EcommerceRole::current_member_is_shop_admin($member);
	}


}

/**
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: model
 * @inspiration: Silverstripe Ltd, Jeremy
 **/
class OrderStatusLog_DispatchElectronicOrder extends OrderStatusLog_Dispatch {

	public static $db = array(
		'Link' => 'Text',
	);

	public static $singular_name = "Order Log Electronic Dispatch Entry";
		function i18n_singular_name() { return _t("OrderStatusLog.ORDERLOGELECTRONICDISPATCHENTRY", "Order Log Electronic Dispatch Entry");}

	public static $plural_name = "Order Log Electronic Dispatch Entries";
		function i18n_plural_name() { return _t("OrderStatusLog.ORDERLOGELECTRONICDISPATCHENTRIES", "Order Log Electronic Dispatch Entries");}

}

/**
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: model
 * @inspiration: Silverstripe Ltd, Jeremy
 **/
class OrderStatusLog_DispatchPhysicalOrder extends OrderStatusLog_Dispatch {

	public static $db = array(
		'DispatchedBy' => 'Varchar(100)',
		'DispatchedOn' => 'Date',
		'DispatchTicket' => 'Varchar(100)',
	);

	public static $indexes = array(
		"DispatchedOn" => true,
		"DispatchTicket" => true
	);

	public static $searchable_fields = array(
		'OrderID' => array(
			'field' => 'NumericField',
			'title' => 'Order Number'
		),
		"Title" => "PartialMatchFilter",
		"Note" => "PartialMatchFilter",
		"DispatchedBy" => "PartialMatchFilter",
		'DispatchTicket' => 'PartialMatchFilter'
	);

	public static $summary_fields = array(
		"DispatchedOn" => "Date",
		"DispatchedBy" => "Dispatched By",
		"OrderID" => "Order ID",
	);


	public static $defaults = array(
		"InternalUseOnly" => false
	);

	public static $singular_name = "Order Log Physical Dispatch Entry";
		function i18n_singular_name() { return _t("OrderStatusLog.ORDERLOGPHYSICALDISPATCHENTRY", "Order Log Physical Dispatch Entry");}

	public static $plural_name = "Order Log Physical Dispatch Entries";
		function i18n_plural_name() { return _t("OrderStatusLog.ORDERLOGPHYSICALDISPATCHENTRIES", "Order Log Physical Dispatch Entries");}


	public static $default_sort = "\"DispatchedOn\" DESC, \"Created\" DESC";

	function populateDefaults() {
		parent::populateDefaults();
		$this->Title = _t("OrderStatusLog.ORDERDISPATCHED", "Order Dispatched");
		$this->DispatchedOn = date('Y-m-d');
		if(Security::database_is_ready()) {
			if(Member::currentUser()) {
				$this->DispatchedBy = Member::currentUser()->getTitle();
			}
		}
	}

	/**
	*
	*@return FieldList
	**/
	function getCMSFields() {
		$fields = parent::getCMSFields();
		$dispatchedOnLabel = _t("OrderStatusLog.DISPATCHEDON", "Dispatched on (Year - month - date): ");
		$fields->replaceField("DispatchedOn", new TextField("DispatchedOn", $dispatchedOnLabel));
		return $fields;
	}

	function onBeforeWrite() {
		parent::onBeforeWrite();
		if(!$this->DispatchedOn) {
			$this->DispatchedOn = DBField::create_field('Date', date('Y-m-d'));
		}
	}

	/**
	*
	*@return String
	**/
	function CustomerNote() {return $this->getCustomerNote();}
	function getCustomerNote() {
		return $this->renderWith("LogDispatchPhysicalOrderCustomerNote");
	}


}

/**
 * @Description: We use this payment check class to double check that payment has arrived against
 * the order placed.  We do this independently of Order as a double-check.  It is important
 * that we do this because the main risk in an e-commerce operation is a fake payment.
 * Any e-commerce operator may set up their own policies on what a payment check
 * entails exactly.  It could include a bank reconciliation or even a phone call to the customer.
 * it is important here that we do not add any payment details. Rather, all we have is a tickbox
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: model
 * @inspiration: Silverstripe Ltd, Jeremy
 **/
class OrderStatusLog_PaymentCheck extends OrderStatusLog {

	public static $defaults = array(
		"InternalUseOnly" => true
	);

	public static $db = array(
		'PaymentConfirmed' => "Boolean",
	);

	/**
	 * Standard SS method
	 * @param Member $member
	 * @return Boolean
	 */
	public function canDelete($member = null) {
		return false;
	}

	public static $searchable_fields = array(
		'OrderID' => array(
			'field' => 'NumericField',
			'title' => 'Order Number'
		),
		"PaymentConfirmed" => true
	);

	public static $summary_fields = array(
		"Created" => "Date",
		"Author.Title" => "Checked by",
		"PaymentConfirmedNice" => "Payment Confirmed"
	);

	public static $casting = array(
		"PaymentConfirmedNice" => "Varchar"
	);

	function PaymentConfirmedNice() {return $this->getPaymentConfirmedNice();}
	function getPaymentConfirmedNice() {if($this->PaymentConfirmed) {return _t("OrderStatusLog.YES", "yes");}return _t("OrderStatusLog.No", "no");}

	public static $singular_name = "Payment Confirmation";
		function i18n_singular_name() { return _t("OrderStatusLog.PAYMENTCONFIRMATION", "Payment Confirmation");}

	public static $plural_name = "Payment Confirmations";
		function i18n_plural_name() { return _t("OrderStatusLog.PAYMENTCONFIRMATIONS", "Payment Confirmations");}

	public function getCMSFields() {
		$fields = parent::getCMSFields();
		$fields->removeByName("Title");
		$fields->removeByName("Note");
		$fields->addFieldToTab(
			'Root.Main',
			new CheckboxField("PaymentConfirmed", _t("OrderStatusLog.CONFIRMED", "Payment is confirmed"))
		);
		return $fields;
	}


	/**
	*
	*@return String
	**/
	function CustomerNote(){return $this->getCustomerNote();}
	function getCustomerNote() {
		if($this->Author()) {
			if($this->PaymentConfirmed) {
				return _t("OrderStatusLog.PAYMENTCONFIRMEDBY", "Payment Confirmed by: ").$this->Author()->getTitle()." | ".$this->Created;
			}
			else {
				return _t("OrderStatusLog.PAYMENTDECLINEDBY", "Payment DECLINED by: ").$this->Author()->getTitle()." | ".$this->Created;
			}
		}
	}


}



/**
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: model
 * @inspiration: Silverstripe Ltd, Jeremy
 **/

class OrderStatusLog_Archived extends OrderStatusLog {


	public static $defaults = array(
		"InternalUseOnly" => false
	);


	public static $singular_name = "Archived Order - Additional Note";
		function i18n_singular_name() { return _t("OrderStatusLog.ARCHIVEDORDERS", "Archived Order - Additional Note");}

	public static $plural_name = "Archived Order - Additional Notes";
		function i18n_plural_name() { return _t("OrderStatusLog.ARCHIVEDORDERS", "Archived Order - Additional Notes");}

	/**
	 * Standard SS method
	 * @param Member $member
	 * @return Boolean
	 */
	public function canDelete($member = null) {
		return false;
	}

	/**
	 * Standard SS method
	 * @param Member $member
	 * @return Boolean
	 */
	public function canEdit($member = null) {
		return true;
	}

	/**
	 * Standard SS method
	 * @param Member $member
	 * @return Boolean
	 */
	public function canCreate($member = null) {
		return true;
	}

}

