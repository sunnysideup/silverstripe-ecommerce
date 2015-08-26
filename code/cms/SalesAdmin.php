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
	private static $url_segment = 'sales';

	/**
	 * standard SS variable
	 * @var String
	 */
	private static $menu_title = 'Sales';

	/**
	 * standard SS variable
	 * @var Int
	 */
	private static $menu_priority = 3.1;

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
	private static $menu_icon = "ecommerce/images/icons/money-file.gif";

	function init() {
		parent::init();
		Requirements::javascript('ecommerce/javascript/EcomBuyableSelectField.js');
		//Requirements::themedCSS("OrderReport", 'ecommerce'); // LEAVE HERE - NOT EASY TO INCLUDE VIA TEMPLATE
		//Requirements::themedCSS("Order_Invoice", 'ecommerce', "print"); // LEAVE HERE - NOT EASY TO INCLUDE VIA TEMPLATE
		//Requirements::themedCSS("Order_PackingSlip", 'ecommerce', "print"); // LEAVE HERE - NOT EASY TO INCLUDE VIA TEMPLATE
		//Requirements::themedCSS("OrderStepField",'ecommerce'); // LEAVE HERE
		//Requirements::javascript("ecommerce/javascript/EcomModelAdminExtensions.js"); // LEAVE HERE - NOT EASY TO INCLUDE VIA TEMPLATE
	}


	function urlSegmenter() {
		return $this->config()->get("url_segment");
	}
	
	/**
	 * @return DataList
	 */
	function getList(){
		$list = parent::getList();
		if(singleton($this->modelClass) instanceof Order) {
			$list = $list->innerJoin("OrderStep", "\"OrderStep\".\"ID\" = \"Order\".\"StatusID\"");
		}
		return $list;
	}


}

