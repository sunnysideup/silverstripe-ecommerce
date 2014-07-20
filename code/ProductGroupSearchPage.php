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
	private static $icon = 'ecommerce/images/icons/productsearch';

	/**
	 * Standard SS variable.
	 * @var String
	 */
	private static $description = "This page manages searching for products.";

	/**
	 * Standard SS variable.
	 */
	private static $singular_name = "Product Search Page";
		function i18n_singular_name() { return _t("ProductGroupSearchPage.SINGULARNAME", "Product Search Page");}

	/**
	 * Standard SS variable.
	 */
	private static $plural_name = "Product Search Pages";
		function i18n_plural_name() { return _t("ProductGroupSearchPage.PLURALNAME", "Product Search Pages");}


	/**
	 * Standard SS function, we only allow for one Product Search Page to exist
	 * but we do allow for extensions to exist at the same time.
	 * @param Member $member
	 * @return Boolean
	 */
	function canCreate($member = null) {
		return ProductSearchPage::get()->filter(array("ClassName" => "ProductSearchPage"))->Count() ? false : $this->canEdit($member);
	}

	/**
	 * Shop Admins can edit
	 * @param Member $member
	 * @return Boolean
	 */
	function canEdit($member = null) {
		if(Permission::checkMember($member, Config::inst()->get("EcommerceRole", "admin_permission_code"))) {return true;}
		return parent::canEdit($member);
	}

	/**
	 * Standard SS method
	 * @param Member $member
	 * @return Boolean
	 */
	public function canDelete($member = null) {
		if(Permission::checkMember($member, Config::inst()->get("EcommerceRole", "admin_permission_code"))) {return true;}
		return parent::canEdit($member);
	}

	/**
	 * Standard SS method
	 * @param Member $member
	 * @return Boolean
	 */
	public function canPublish($member = null) {
		if(Permission::checkMember($member, Config::inst()->get("EcommerceRole", "admin_permission_code"))) {return true;}
		return parent::canEdit($member);
	}

	/**
	 * Standard SS method
	 * //check if it is in a current cart?
	 * @param Member $member
	 * @return Boolean
	 */
	public function canDeleteFromLive($member = null) {
		return false;
	}

}

class ProductGroupSearchPage_Controller extends ProductGroup_Controller {


}
