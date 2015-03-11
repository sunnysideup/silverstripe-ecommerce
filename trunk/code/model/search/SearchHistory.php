<?php

class SearchHistory Extends DataObject {

	private static $db = array(
		"Title" => "Varchar(255)"
	);

	private static $default_sort = "\"Created\" DESC";

	/**
	 * creates a new entry if you are not a shop admin
	 *
	 * @param String $keywordString
	 * @return Int
	 */
	static function add_entry($keywordString) {
		if($member = Member::currentUser()) {
			if($member->IsShopAdmin()) {
				return -1;
			}
		}
		$obj = new SearchHistory();
		$obj->Title = $keywordString;
		return $obj->write();
	}

	/**
	 * remove excessive spaces
	 */
	function onBeforeWrite() {
		$this->Title = trim(preg_replace('!\s+!', ' ', $this->Title));
		parent::onBeforeWrite();
	}

	/**
	 * standard SS method
	 * @param Member $member
	 * @return Boolean
	 */
	public function canCreate($member = null) {return false;}

	/**
	 * standard SS method
	 * @param Member $member
	 * @return Boolean
	 */
	public function canView($member = null) {
		if(Permission::checkMember($member, Config::inst()->get("EcommerceRole", "admin_permission_code"))) {return true;}
		return parent::canEdit($member);
	}

	/**
	 * standard SS method
	 * @param Member $member
	 * @return Boolean
	 */
	public function canEdit($member = null) {return false;}

	/**
	 * standard SS method
	 * @param Member $member
	 * @return Boolean
	 */
	public function canDelete($member = null) {return false;}

}
