<?php


/**
 * @description: CMS management for everything you have sold and all related data (e.g. logs, payments)
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: cms
 * @inspiration: Silverstripe Ltd, Jeremy
 **/

class SalesAdmin extends ModelAdminEcommerceBaseClass{

	/**
	 * standard SS variable
	 * @var String
	 */
	public static $url_segment = 'sales';

	/**
	 * standard SS variable
	 * @var String
	 */
	public static $menu_title = 'Sales';

	/**
	 * standard SS variable
	 * @var Int
	 */
	public static $menu_priority = 1;

	/**
	 * Change this variable if you don't want the Import from CSV form to appear.
	 * This variable can be a boolean or an array.
	 * If array, you can list className you want the form to appear on. i.e. array('myClassOne','myClasstwo')
	 */
	public $showImportForm = false;

	/**
	 * standard SS variable
	 * @var String
	 */
	public static $menu_icon = "";

	function init() {
		parent::init();
		//Requirements::themedCSS("OrderReport", 'ecommerce'); // LEAVE HERE - NOT EASY TO INCLUDE VIA TEMPLATE
		//Requirements::themedCSS("Order_Invoice", 'ecommerce', "print"); // LEAVE HERE - NOT EASY TO INCLUDE VIA TEMPLATE
		//Requirements::themedCSS("Order_PackingSlip", 'ecommerce', "print"); // LEAVE HERE - NOT EASY TO INCLUDE VIA TEMPLATE
		//Requirements::themedCSS("OrderStepField",'ecommerce'); // LEAVE HERE
		//Requirements::javascript("ecommerce/javascript/EcomModelAdminExtensions.js"); // LEAVE HERE - NOT EASY TO INCLUDE VIA TEMPLATE
	}


	function urlSegmenter() {
		return self::$url_segment;
	}
}

/*
SalesAdmin_CollectionController extends ModelAdminEcommerceClass_CollectionController {

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
*/
