<?php


/**
 * @description: Manages everything you sell.
 * Can include ProductVariations, etc..
 *
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: cms
 **/

class ProductsAndGroupsModelAdmin extends ModelAdminEcommerceBaseClass {

	private static $menu_priority = 2;

	private static $url_segment = 'products';

	private static $menu_title = 'Products';


	/**
	 * standard SS variable
	 * @var String
	 */
	private static $menu_icon = "";


	function init() {
		parent::init();
		//Requirements::javascript("ecommerce/javascript/EcomModelAdminExtensions.js"); // LEAVE HERE - NOT EASY TO INCLUDE VIA TEMPLATE
	}

	/**
	 *@return String (URL Segment)
	 **/
	function urlSegmenter() {
		return self::$url_segment;
	}


}
