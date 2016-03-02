<?php


class OrderModifier_Descriptor extends DataObject implements EditableEcommerceObject {

	/**
	 * standard SS variable
	 * @var Array
	 */
	private static $db = array(
		"ModifierClassName" => "Varchar(100)",
		"Heading" => "Varchar",
		"Description" => "Text"
	);

	/**
	 * standard SS variable
	 * @var Array
	 */
	private static $has_one = array(
		"Link" => "SiteTree"
	);

	/**
	 * standard SS variable
	 * @var Array
	 */
	private static $indexes = array(
		"ModifierClassName" => true
	);

	/**
	 * standard SS variable
	 * @var Array
	 */
	private static $searchable_fields = array(
		"Heading" => "PartialMatchFilter",
		"Description" => "PartialMatchFilter"
	);

	/**
	 * standard SS variable
	 * @var Array
	 */
	private static $field_labels = array(
		"ModifierClassName" => "Code"
	);

	/**
	 * standard SS variable
	 * @var Array
	 */
	private static $summary_fields = array(
		"RealName" => "Code",
		"Heading" => "Heading",
		"Description" => "Description"
	);

	/**
	 * standard SS variable
	 * @var Array
	 */
	private static $casting = array(
		"RealName" => "Varchar"
	);

	/**
	 * standard SS variable
	 * @var String
	 */
	private static $singular_name = "Order Modifier Description";
		function i18n_singular_name() { return _t("OrderModifier.ORDEREXTRADESCRIPTION", "Order Modifier Description");}

	/**
	 * standard SS variable
	 * @var String
	 */
	private static $plural_name = "Order Modifier Descriptions";
		function i18n_plural_name() { return _t("OrderModifier.ORDEREXTRADESCRIPTIONS", "Order Modifier Descriptions");}

	/**
	 * standard SS method
	 * @param Member $member
	 * @return Boolean
	 **/
	function canCreate($member = null) {
		return false;
	}

	/**
	 * standard SS method
	 * @param Member $member
	 * @return Boolean
	 **/
	function canEdit($member = null) {
		$extended = $this->extendedCan(__FUNCTION__, $member);
		if($extended !== null) {
			return $extended;
		}
		if(Permission::checkMember($member, Config::inst()->get("EcommerceRole", "admin_permission_code"))) {return true;}
		return parent::canEdit($member);
	}

	/**
	 * standard SS method
	 * @param Member $member
	 * @return Boolean
	 **/
	function canDelete($member = null) {
		return false;
	}

	/**
	 * standard SS method
	 * @return FieldList
	 */
	function getCMSFields(){
		$fields = parent::getCMSFields();
		$fields->replaceField("ModifierClassName", new ReadonlyField("RealName", "Name"));
		$fields->replaceField("LinkID", new TreeDropdownField("LinkID", "More info link (optional)", "SiteTree"));
		$fields->replaceField("Description", new TextareaField("Description", "Description"));
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
	 * casted Variable
	 * @return String.
	 */
	function RealName(){return $this->getRealName();}
	function getRealName(){
		if(class_exists($this->ModifierClassName)) {
			$singleton = singleton($this->ModifierClassName);
			return $singleton->i18n_singular_name(). " (".$this->ModifierClassName.")";
		}
		return $this->ModifierClassName;
	}

	/**
	 * stardard SS method
	 */
	function onBeforeWrite(){
		parent::onBeforeWrite();
		if(isset($_REQUEST["NoLinkForOrderModifier_Descriptor"]) && $_REQUEST["NoLinkForOrderModifier_Descriptor"]) {
			$this->LinkID = 0;
		}
	}

	/**
	 * Adds OrderModifier_Descriptors and deletes the irrelevant ones
	 * stardard SS method
	 */
	function requireDefaultRecords(){
		parent::requireDefaultRecords();
		$arrayOfModifiers = EcommerceConfig::get("Order", "modifiers");
		if(!is_array($arrayOfModifiers)) {
			$arrayOfModifiers = array();
		}
		if(count($arrayOfModifiers)) {
			foreach($arrayOfModifiers as $className) {
				$orderModifier_Descriptor = OrderModifier_Descriptor::get()->Filter(Array("ModifierClassName" => $className))->First();
				if(!$orderModifier_Descriptor) {
					$modifier = singleton($className);
					$orderModifier_Descriptor = OrderModifier_Descriptor::create();
					$orderModifier_Descriptor->ModifierClassName = $className;
					$orderModifier_Descriptor->Heading = $modifier->i18n_singular_name();
					$orderModifier_Descriptor->write();
					DB::alteration_message("Creating description for ".$className, "created");
				}
			}
		}
		//delete the ones that are not relevant
		$orderModifierDescriptors = OrderModifier_Descriptor::get();
		if($orderModifierDescriptors && $orderModifierDescriptors->count()) {
			foreach($orderModifierDescriptors as $orderModifierDescriptor) {
				if(!in_array($orderModifierDescriptor->ModifierClassName, $arrayOfModifiers)) {
					$orderModifierDescriptor->delete();
					DB::alteration_message("Deleting description for ".$orderModifierDescriptor->ModifierClassName, "created");
				}
			}
		}
	}

}
