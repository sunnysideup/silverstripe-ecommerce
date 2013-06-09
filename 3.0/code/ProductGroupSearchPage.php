<?php
/**
 *
 * This page manages searching for products
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: Pages
 **/


class ProductGroupSearchPage extends ProductGroup {

	/**
	 * standard SS variable
	 * @static String | Array
	 *
	 */
	public static $icon = 'ecommerce/images/icons/productsearch';

	/**
	 * Standard SS variable.
	 */
	public static $singular_name = "Product Search Page";
		function i18n_singular_name() { return _t("ProductGroupSearchPage.SINGULARNAME", "Product Search Page");}

	/**
	 * Standard SS variable.
	 */
	public static $plural_name = "Product Search Pages";
		function i18n_plural_name() { return _t("ProductGroupSearchPage.PLURALNAME", "Product Search Pages");}

	/**
	 * This temporarily hides the page while we
	 * it is being completed.
	 */
	function canCreate($member = null){
		return false;
	}

}
class ProductGroupSearchPage_Controller extends ProductGroup_Controller {


}
