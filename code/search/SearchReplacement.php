<?php

class SearchReplacement extends DataObject {

	private static $db = array(
		'Replace' => 'Varchar',
		'Search' => 'Text'
	);

	private static $summary_fields = array(
		'Replace' => 'To Replace With',
		'Search' => 'Searches'
	);

	private static $field_labels = array(
		'Replace' => 'Replace With ...',
		'Search' => '... The Following Searches'
	);

	private static $separator = ',';

	function onBeforeWrite() {
		parent::onBeforeWrite();
		$this->Replace = strtolower($this->Replace);
		$this->Search = strtolower($this->Search);
	}

	/**
	 * standard SS method
	 * @param Member $member
	 * @return Boolean
	 */
	public function canCreate($member = null) {
		if(Permission::checkMember($member, Config::inst()->get("EcommerceRole", "admin_permission_code"))) {return true;}
		return parent::canEdit($member);
	}

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
	public function canEdit($member = null) {
		if(Permission::checkMember($member, Config::inst()->get("EcommerceRole", "admin_permission_code"))) {return true;}
		return parent::canEdit($member);
	}

	/**
	 * standard SS method
	 * @param Member $member
	 * @return Boolean
	 */
	public function canDelete($member = null) {
		if(Permission::checkMember($member, Config::inst()->get("EcommerceRole", "admin_permission_code"))) {return true;}
		return parent::canEdit($member);
	}

}
