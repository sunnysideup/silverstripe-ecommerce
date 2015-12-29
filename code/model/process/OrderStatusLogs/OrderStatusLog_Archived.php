<?php

/**
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: model
 * @inspiration: Silverstripe Ltd, Jeremy
 **/

class OrderStatusLog_Archived extends OrderStatusLog {


	private static $defaults = array(
		"InternalUseOnly" => false
	);


	private static $singular_name = "Archived Order - Additional Note";
		function i18n_singular_name() { return _t("OrderStatusLog.ARCHIVEDORDERS", "Archived Order - Additional Note");}

	private static $plural_name = "Archived Order - Additional Notes";
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

	function getCMSFields(){
		$fields = parent::getCMSFields();
		$fields->replaceField("ClassName", new HiddenField("ClassName", "ClassName", $this->ClassName));
		$fields->addFieldToTab("Root.Main", new ReadonlyField("Created", "Created"));
		return $fields;
	}

}

