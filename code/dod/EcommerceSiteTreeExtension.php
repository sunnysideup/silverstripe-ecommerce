<?php

/**
 *@description: adds a few functions to SiteTree to give each page some e-commerce related functionality.
 *
 *
 * @authors: Silverstripe, Jeremy, Nicolaas
 *
 * @package ecommerce
 * @sub-package integration
 * @todo: move all items into EcommerceConfig.
 *
 **/


class EcommerceSiteTreeExtension extends DataObjectDecorator {

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


}

class EcommerceSiteTreeExtension_Controller extends Extension {

	/**
	 * WATCH: does this get included too early to too much?
	 * This is called by Controller::init();
	 **/
	function onBeforeInit() {
		Requirements::javascript(THIRDPARTY_DIR."/jquery/jquery.js");
		Requirements::javascript("ecommerce/javascript/EcomCart.js");
	}


	/**
	 * This returns a link that displays just the cart, for use in ajax calls.
	 * @see ShoppingCart::showcart
	 * It uses AjaxSimpleCart.ss to render the cart.
	 * @return string
	 **/
	function SimpleCartLinkAjax() {
		return ShoppingCart_Controller::get_url_segment()."/showcart/";
	}


	/**
	 * returns the current order.
	 * @return Order
	 **/
	function Cart() {
		return ShoppingCart::current_order();
	}


}
