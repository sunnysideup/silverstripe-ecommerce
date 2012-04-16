<?php

/**
 * @description: CMS management for everything you have sold and all related data (e.g. logs, payments)
 *
 * @authors: Silverstripe, Jeremy, Romain, Nicolaas
 *
 * @package: ecommerce
 * @sub-package: cms
 *
 **/

class SalesAdmin extends ModelAdmin{

	public static $url_segment = 'sales';

	public static $menu_title = 'Sales';

	public static $menu_priority = 1;

	public static $collection_controller_class = 'SalesAdmin_CollectionController';

	public static $record_controller_class = 'SalesAdmin_RecordController';

	/**
	 * Standard SS Method
	 * @return Array
	 */
	function getManagedModels() {
		return EcommerceConfig::get("SalesAdmin", "managed_models");
	}

	function init() {
		parent::init();
		Requirements::themedCSS("OrderReport"); // LEAVE HERE - NOT EASY TO INCLUDE VIA TEMPLATE
		Requirements::themedCSS("OrderReport_Print", "print"); // LEAVE HERE - NOT EASY TO INCLUDE VIA TEMPLATE
		Requirements::javascript("ecommerce/javascript/EcomModelAdminExtensions.js"); // LEAVE HERE - NOT EASY TO INCLUDE VIA TEMPLATE
		Requirements::themedCSS("OrderStepField"); // LEAVE HERE
	}


	function urlSegmenter() {
		return self::$url_segment;
	}
}

class SalesAdmin_CollectionController extends ModelAdminEcommerceClass_CollectionController {

	//public function CreateForm() {return false;}

	public function ImportForm() {return false;}
}

//remove delete action
class SalesAdmin_RecordController extends ModelAdminEcommerceClass_RecordController {

	public function EditForm() {
		$form = parent::EditForm();
		if($this->parentController) {
			$currRecordURL = $this->parentController->Link(Controller::join_links($this->currentRecord->ID, "edit"));
			$form->Actions()->insertFirst(new FormAction("goCurr", "Refresh Record"));
			$form->Fields()->push(new HiddenField("currRecordURL", null, $currRecordURL));
		}
		return $form;
	}

}
