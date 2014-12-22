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

class ProductConfigModelAdmin extends ModelAdminEcommerceBaseClass {

	private static $menu_priority = 3.19;

	private static $url_segment = 'product-config';

	private static $menu_title = 'Products Settings';

	/**
	 * standard SS variable
	 * @var String
	 */
	private static $menu_icon = "ecommerce/images/icons/product-file.gif";


}
