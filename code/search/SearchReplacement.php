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


}

class SearchReplacement_Admin extends ModelAdmin {

	private static $url_segment = 'search';
	private static $menu_title = 'Search';
	private static $managed_models = array('SearchReplacement');

}
