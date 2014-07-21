<?php

class SearchReplacement extends DataObject {

	private static $db = array(
		'Search' => 'Text',
		'Replace' => 'Varchar'
	);

	private static $summary_fields = array(
		'Search' => 'Aliases (e.g. Biike)',
		'Replace' => 'Proper name (e.g. Bike)'
	);

	private static $field_labels = array(
		'Search' => 'Aliases',
		'Replace' => 'Proper Name'
	);

	private static $separator = ',';

	function fieldLabels($includerelations = true) {
		return array(
			'Search' => 'When someone searches for ... (separate searches by '.$this->Config()->get("separator").") - aliases",
			'Replace' => 'It is replaced by - proper name ...'
		);
	}

	function onBeforeWrite() {
		parent::onBeforeWrite();
		//all lower case and make replace double spaces
		$this->Search = trim(preg_replace('!\s+!', ' ', strtolower($this->Search)));
		$searchArray = array();
		foreach(explode(",", $this->Search) as $term ) {
			$searchArray[] = trim($term);
		}
		$this->Search = implode(",",$searchArray);
		$this->Replace = strtolower($this->Replace);
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
