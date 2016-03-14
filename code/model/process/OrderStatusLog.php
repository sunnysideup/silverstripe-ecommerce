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

class OrderStatusLog extends DataObject implements EditableEcommerceObject {

	/**
	 * standard SS variable
	 * @var Array
	 */
	private static $db = array(
		'Title' => 'Varchar(100)',
		'Note' => 'HTMLText',
		'InternalUseOnly' => 'Boolean'
	);

	/**
	 * standard SS variable
	 * @var Array
	 */
	private static $has_one = array(
		"Author" => "Member",
		"Order" => "Order"
	);

	/**
	 * standard SS variable
	 * @var Array
	 */
	private static $casting = array(
		"CustomerNote" => "HTMLText",
		"Type" => "Varchar",
		"InternalUseOnlyNice" => "Varchar"
	);

	/**
	 * standard SS variable
	 * @var Array
	 */
	private static $summary_fields = array(
		"Created" => "Date",
		"Type" => "Type",
		"Title" => "Title",
		"InternalUseOnlyNice" => "Internal use only"
	);

	/**
	 * standard SS variable
	 * @var Array
	 */
	private static $defaults = array(
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
	public function canCreate($member = null) {
		$extended = $this->extendedCan(__FUNCTION__, $member);
		if($extended !== null) {
			return $extended;
		}
		if(Permission::checkMember($member, Config::inst()->get("EcommerceRole", "admin_permission_code"))) {return true;}
		return parent::canEdit($member);
	}

	/**
	 * Standard SS method
	 * @param Member $member
	 * @return Boolean
	 */
	public function canView($member = null) {
		$extended = $this->extendedCan(__FUNCTION__, $member);
		if($extended !== null) {
			return $extended;
		}
		if(Permission::checkMember($member, Config::inst()->get("EcommerceRole", "admin_permission_code"))) {return true;}
		if(!$this->InternalUseOnly) {
			if($this->Order()) {
				if($this->Order()->MemberID == $member->ID) {
					return true;
				}
			}
		}
		return parent::canView($member);
	}


	/**
	 * Standard SS method
	 * @param Member $member
	 * @return Boolean
	 */
	public function canEdit($member = null) {
		$extended = $this->extendedCan(__FUNCTION__, $member);
		if($extended !== null) {
			return $extended;
		}
		if($order = $this->Order()) {
			return $order->canEdit($member);
		}
		return false;
	}

	/**
	 * Standard SS method
	 * logs can never be deleted...
	 * @param Member $member
	 * @return Boolean
	 */
	public function canDelete($member = null) {
		$extended = $this->extendedCan(__FUNCTION__, $member);
		if($extended !== null) {
			return $extended;
		}
		return false;
	}

	/**
	 * standard SS variable
	 * @var Array
	 */
	private static $searchable_fields = array(
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
	private static $singular_name = "Order Log Entry";
		function i18n_singular_name() { return _t("OrderStatusLog.ORDERLOGENTRY", "Order Log Entry");}

	/**
	 * standard SS variable
	 * @var String
	 */
	private static $plural_name = "Order Log Entries";
		function i18n_plural_name() { return _t("OrderStatusLog.ORDERLOGENTRIES", "Order Log Entries");}

	/**
	 * Standard SS variable.
	 * @var String
	 */
	private static $description = "A record of anything that happened with an order.";

	/**
	 * standard SS variable
	 * @var String
	 */
	private static $default_sort = "\"Created\" DESC";

	/**
	 * standard SS method
	 */
	function populateDefaults() {
		parent::populateDefaults();
		if(Security::database_is_ready()) {
			$this->AuthorID = Member::currentUserID();
		}
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
		$ecommerceClassNameOrTypeDropdownField = EcommerceClassNameOrTypeDropdownField::create("ClassName", _t("OrderStatusLog.TYPE", "Type"), "OrderStatusLog", $availableLogs);
		$ecommerceClassNameOrTypeDropdownField->setIncludeBaseClass(true);
		$fields->addFieldToTab("Root.Main", $ecommerceClassNameOrTypeDropdownField, "Title");
		if($this->exists()) {
			$classNameField = $fields->dataFieldByName("ClassName");
			$fields->replaceField("ClassName", $classNameField->performReadonlyTransformation());
		}
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
			"/admin/sales/".$this->ClassName."/EditForm/field/".$this->ClassName."/item/".$this->ID."/",
			$action
		);
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
		$ecommerceClassNameOrTypeDropdownField = EcommerceClassNameOrTypeDropdownField::create("ClassName", "Type", "OrderStatusLog", $availableLogs);
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


