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

	public static $menu_priority = 2;

	public static $url_segment = 'products';

	public static $menu_title = 'Products';


	/**
	 * standard SS variable
	 * @var String
	 */
	public static $menu_icon = "";


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
