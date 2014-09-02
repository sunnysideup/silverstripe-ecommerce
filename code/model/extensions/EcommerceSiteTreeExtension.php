<?php

/**
 * @description: adds a few functions to SiteTree to give each page
 * some e-commerce related functionality.
 *
 *
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: extensions
 * @inspiration: Silverstripe Ltd, Jeremy
 **/


class EcommerceSiteTreeExtension extends SiteTreeExtension {

	/**
	 * returns the instance of EcommerceConfigAjax for use in templates.
	 * In templates, it is used like this:
	 * $AJAXDefinitions.TableID
	 *
	 * @return EcommerceConfigAjax
	 **/
	public function AJAXDefinitions() {
		return EcommerceConfigAjax::get_one($this->owner);
	}

	/**
	 * @return EcommerceDBConfig
	 **/
	function EcomConfig() {
		return EcommerceDBConfig::current_ecommerce_db_config();
	}

	/**
	 * tells us if the current page is part of e-commerce.
	 * @return Boolean
	 */
	function IsEcommercePage () {
		return false;
	}


	/**
	 * Log in link
	 * @return String
	 */
	function EcommerceLogInLink (){
		if($this->owner->IsEcommercePage()) {
			$link = $this->owner->Link();
		}
		else {
			$link = $this->EcomConfig()->AccountPageLink();
		}
		return "/Security/login?BackURL=".urlencode($link);
	}

	/**
	 * clears all the Zend Caches
	 * @param String cacheName
	 * @param String cacheKey
	 */
	function ClearZendCaches($cacheName = "", $cacheKey = ""){
		if($cacheName && $cacheKey) {
			$cache = SS_Cache::factory($cacheName);
			$cache->remove($cacheKey);
		}
		else {
			$cache = SS_Cache::factory('any');
			$cache->clean(Zend_Cache::CLEANING_MODE_ALL);
		}
	}

}

class EcommerceSiteTreeExtension_Controller extends Extension {

	/**
	 * standard SS method.
	 * Runs before the Page::init method is called.
	 *
	 */
	function onBeforeInit(){
		//make sure that with a simple flush=all, all the caches are flushed...
		if(isset($_GET["flush"]) && $_GET["flush"] == "all") {
			$this->owner->ClearZendCaches();
		}
		Requirements::javascript(THIRDPARTY_DIR."/jquery/jquery.js");
		//Requirements::block(THIRDPARTY_DIR."/jquery/jquery.js");
		//Requirements::javascript(Director::protocol()."ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js");
		//todo: check if we even need this (via ShoppingCartsRequirements.ss)
		if($this->owner->dataRecord) {
			if(is_a($this->owner->dataRecord, Object::getCustomClass("Product")) || is_a($this->owner->dataRecord, Object::getCustomClass("ProductGroup"))) {
				Session::set("ContinueShoppingLink", substr($_SERVER['REQUEST_URI'], strlen(Director::baseURL())));
			}
		}
	}

	/**
	 * Standard SS method.
	 * Runs after the Page::init method is called.
	 */
	function onAfterInit(){
		Requirements::javascript(EcommerceConfig::get("EcommerceConfigAjax", "cart_js_file_location"));
		Requirements::javascript(EcommerceConfig::get("EcommerceConfigAjax", "dialogue_js_file_location"));
		Requirements::themedCSS("Cart", 'ecommerce');
		Requirements::themedCSS("jquery.colorbox", 'ecommerce');
	}

	/**
	 * This returns a link that displays just the cart, for use in ajax calls.
	 * @see ShoppingCart::showcart
	 * It uses AjaxSimpleCart.ss to render the cart.
	 * @return string
	 **/
	function SimpleCartLinkAjax() {
		return EcommerceConfig::get("ShoppingCart_Controller", "get_url_segment")."/showcart/?ajax=1";
	}

	/**
	 * returns the current order.
	 * @return Order
	 **/
	function Cart() {
		return ShoppingCart::current_order();
	}

	/**
	 *
	 * @return String (Link)
	 */
	function ContinueShoppingLink(){
		$link = Session::get("ContinueShoppingLink");
		if(!$link) {
			$link = Director::baseURL();
		}
		return $link;
	}


}
