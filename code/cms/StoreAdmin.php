<?php


/**
 * @description: CMS management for the store setup (e.g Order Steps, Countries, etc...)
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: cms
 * @inspiration: Silverstripe Ltd, Jeremy
 **/

class StoreAdmin extends ModelAdminEcommerceBaseClass{

	/**
	 * standard SS variable
	 * @var String
	 */
	private static $url_segment = 'shop';

	/**
	 * standard SS variable
	 * @var String
	 */
	private static $menu_title = "Shop Settings";


	/**
	 * standard SS variable
	 * @var Int
	 */
	private static $menu_priority = 3.3;

	/**
	 * standard SS variable
	 * @var String
	 */
	private static $menu_icon = "ecommerce/images/icons/cart-file.gif";


	function init() {
		parent::init();
	}


	/**
	 *
	 *@return String (URLSegment)
	 **/
	function urlSegmenter() {
		return $this->config()->get("url_segment");
	}



	/**
	 * @return array Map of class name to an array of 'title' (see {@link $managed_models})
	 * we make sure that the EcommerceDBConfig is FIRST
	 */
	function getManagedModels() {
		$models = parent::getManagedModels();
		$ecommerceDBConfig = isset($models["EcommerceDBConfig"]) ? $models["EcommerceDBConfig"] : null;
		if($ecommerceDBConfig) {
			unset($models["EcommerceDBConfig"]);
			return array("EcommerceDBConfig" => $ecommerceDBConfig) + $models;
		}
		return $models;
	}

}


