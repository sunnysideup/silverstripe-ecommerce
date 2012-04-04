<?php

/**
 *@description: adds a few functions to SiteTree to give each page some e-commerce related functionality.
 *
 *
 * @authors: Silverstripe, Jeremy, Nicolaas
 *
 * @package ecommerce
 * @sub-package integration
 *
 **/


class EcommerceSiteTreeExtension extends DataObjectDecorator {

	function extraStatics(){
		return array(
			'casting' => array(
				"EcommerceMenuTitle" => "Varchar"
			)
		);
	}

	/**
	 *@return Boolean
	 **/
	function ShopClosed() {
		$siteConfig = DataObject::get_one("SiteConfig");
		return $siteConfig->ShopClosed;
	}

	/**
	 *@return Order
	 **/
	function Cart() {
		return ShoppingCart::current_order();
	}

	/**
	 * @return Integer
	 **/
	public function NumItemsInCart() {
		$order = ShoppingCart::current_order();
		if($order) {
			return $order->TotalItems();
		}
		return 0;
	}

	/**
	 * @return String (HTML Snippet)
	 **/
	function getEcommerceMenuTitle() {
		return $this->owner->getMenuTitle();
	}
	function EcommerceMenuTitle(){return $this->getEcommerceMenuTitle();}


	/**
	 * returns the instance of EcommerceConfigAjax for use in templates.
	 * In templates, it is used like this:
	 * $EcommerceConfigAjax.TableID
	 *
	 * @return EcommerceConfigAjax
	 **/
	public function AJAXDefinitions() {
		return EcommerceConfigAjax::get_one($this->owner);
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
						var newText = "'.Convert::raw2js($page->getEcommerceMenuTitle()).'"
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

	/**
	 *@return Boolean
	 **/
	public function MoreThanOneItemInCart() {
		return $this->owner->NumItemsInCart() > 1;
	}

	/**
	 *@return Float
	 **/
	public function SubTotalCartValue() {
		$order = ShoppingCart::current_order();
		return $order->SubTotal;
	}

	/**
	 *@return String (URLSegment)
	 **/
	public function AccountPageLink() {
		return AccountPage::find_link();
	}

	/**
	 *@return String (URLSegment)
	 **/
	public function CheckoutLink() {
		return CheckoutPage::find_link();
	}

	/**
	 *@return String (URLSegment)
	 **/
	public function CartPage() {
		return CartPage::find_link();
	}

	/**
	 *@return String (URLSegment)
	 **/
	public function OrderConfirmationPage() {
		return OrderConfirmationPage::find_link();
	}


	/**
	 * tells us if the current page is part of e-commerce.
	 * @return Boolean
	 */
	function IsEcommercePage () {
		return false;
	}

}
