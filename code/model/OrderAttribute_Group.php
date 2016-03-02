<?php



/**
 * Allows you to group OrderAttributes
 *
 *
 */
class OrderAttribute_Group extends DataObject implements EditableEcommerceObject {

	private static $db = array(
		"Name" => "Varchar",
		"Sort" => "Int"
	);

	/**
	 * Standard SS variable
	 * @var Array
	 */
	private static $indexes = array(
		"Sort" => true
	);


	/**
	 * Standard SS Method
	 * @param Member $member
	 * @var Boolean
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
	 * Standard SS Method
	 * @param Member $member
	 * @var Boolean
	 */
	public function canView($member = null) {
		$extended = $this->extendedCan(__FUNCTION__, $member);
		if($extended !== null) {
			return $extended;
		}
		if(Permission::checkMember($member, Config::inst()->get("EcommerceRole", "admin_permission_code"))) {return true;}
		return parent::canEdit($member);
	}

	/**
	 * Standard SS Method
	 * @param Member $member
	 * @var Boolean
	 */
	public function canEdit($member = null) {
		$extended = $this->extendedCan(__FUNCTION__, $member);
		if($extended !== null) {
			return $extended;
		}
		if(Permission::checkMember($member, Config::inst()->get("EcommerceRole", "admin_permission_code"))) {return true;}
		return parent::canEdit($member);
	}

	/**
	 * Standard SS Method
	 * @param Member $member
	 * @var Boolean
	 */
	public function canDelete($member = null) {
		$extended = $this->extendedCan(__FUNCTION__, $member);
		if($extended !== null) {
			return $extended;
		}
		if(Permission::checkMember($member, Config::inst()->get("EcommerceRole", "admin_permission_code"))) {return true;}
		return parent::canEdit($member);
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

}
