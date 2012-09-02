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

	/**
	 * standard SS variable
	 * @var String
	 */
	public static $url_segment = 'shop';

	/**
	 * standard SS variable
	 * @var String
	 */
	public static $menu_title = "Shop";


	/**
	 * standard SS variable
	 * @var Int
	 */
	public static $menu_priority = 3;

	/**
	 * standard SS variable
	 * @var String
	 */
	public static $collection_controller_class = 'StoreAdmin_CollectionController';

	/**
	 * standard SS variable
	 * @var String
	 */
	public static $record_controller_class = 'StoreAdmin_RecordController';

	/**
	 * standard SS variable
	 * @var String
	 */
	public static $menu_icon = "";

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
