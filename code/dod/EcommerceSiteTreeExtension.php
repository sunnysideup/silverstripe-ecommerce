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
	 *@return Order
	 **/
	function Cart() {
		return ShoppingCart::current_order();
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

	/*
	 *TO DO: this even seemed to be called then the CMS is opened
	 **/
	function onAfterInit() {
		Requirements::javascript(THIRDPARTY_DIR."/jquery/jquery.js");
		Requirements::javascript("ecommerce/javascript/EcomCart.js");
		$checkoutPages = DataObject::get("CartPage");
		$jsArray = Array();
		if($checkoutPages) {
			foreach($checkoutPages as $page) {
				$jsArray[] = '
				jQuery("a[href=\''.str_replace('/', '\/', Convert::raw2js($page->Link())).'\']").each(
					function(i, el) {
						var oldText = jQuery(el).text();
						var newText = "'.Convert::raw2js($page->MenuTitleExtension()).'"
						if(newText) {
							jQuery(el).html(oldText + newText)
						}
					}
				);';
			}
		}
		if(count($jsArray)) {
			Requirements::customScript(
				'
				jQuery(document).ready(
					function() {
						'.implode("", $jsArray).'
					}
				);'
				,"getEcommerceMenuTitle"
			);
		}
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


}
