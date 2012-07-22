<?php


/**
 * @description: CMS management for the store setup (e.g Order Steps, Countries, etc...)
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: cms
 * @inspiration: Silverstripe Ltd, Jeremy
 **/

class StoreAdmin extends ModelAdmin{

	public static $url_segment = 'shop';

	public static $menu_title = "Shop";

	public static $menu_priority = 3;

	//static $url_priority = 50;

	public static $collection_controller_class = 'StoreAdmin_CollectionController';

	public static $record_controller_class = 'StoreAdmin_RecordController';

	/**
	 * Standard SS Method
	 * @return Array
	 */
	function getManagedModels() {
		return EcommerceConfig::get("StoreAdmin", "managed_models");
	}

	function init() {
		parent::init();
	}


	/**
	 *
	 *@return String (URLSegment)
	 **/
	function urlSegmenter() {
		return self::$url_segment;
	}
}

class StoreAdmin_CollectionController extends ModelAdminEcommerceClass_CollectionController {

	public function ImportForm() {return false;}

}

//remove delete action
class StoreAdmin_RecordController extends ModelAdminEcommerceClass_RecordController {




}
