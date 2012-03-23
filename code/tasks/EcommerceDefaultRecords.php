<?php


/**
 * @description:
 *
 * @authors: Silverstripe, Jeremy, Nicolaas
 *
 * @package: ecommerce
 * @sub-package: setup
 *
 **/


class EcommerceDefaultRecords extends BuildTask {


	protected $title = "Create e-commerce default records";

	protected $description = "These default records are basic stuff like an account page, a few products, a product group.";

	function run($request) {
		$update = array();
		// ACCOUNT PAGE
		$accountPage = DataObject::get_one('AccountPage');
		if(!$accountPage) {
			$accountPage = new AccountPage();
			$accountPage->Title = 'Account';
			$accountPage->MenuTitle = 'Account';
			$accountPage->MetaTitle = 'Account';
			$accountPage->Content = '<p>This is the account page. It is used for shop users to login and change their member details if they have an account.</p>';
			$accountPage->URLSegment = 'account';
			$accountPage->ShowInMenus = 0;
			$page->writeToStage('Stage');
			$accountPage->publish('Stage', 'Live');
			DB::alteration_message('Account page \'Account\' created', 'created');
		}
		else {
			DB::alteration_message('No need to create an account page, it already exists.');
		}
		//CHECKOUT PAGE


		//CHECKOUT PAGE

		$checkoutPage = DataObject::get_one('CheckoutPage');
		if(!$checkoutPage) {
			$checkoutPage = new CheckoutPage();
			$checkoutPage->Content = '<p>This is the checkout page. You can edit all the messages in the Content Management System.</p>';
			$checkoutPage->Title = 'Checkout';
			$checkoutPage->MetaTitle = 'Checkout';
			$checkoutPage->MenuTitle = 'Checkout';
			$checkoutPage->URLSegment = 'checkout';
			$update[] = 'Checkout page \'Checkout\' created';
			$checkoutPage->ShowInMenus = 0;
		}
		else {
			DB::alteration_message('No need to create an checkout page, it already exists.');
		}
		if($checkoutPage) {
			if($checkoutPage->TermsPageID == 0 && $termsPage = DataObject::get_one('Page', "\"URLSegment\" = 'terms-and-conditions'")) {
				$checkoutPage->TermsPageID = $termsPage->ID;
				$update[] = 'added terms page';
			}
			else {
				DB::alteration_message('No terms and conditions page linked.');
			}
			if(!DataObject::get_one('OrderConfirmationPage')) {
				$orderConfirmationPage = new OrderConfirmationPage();
				$orderConfirmationPage->ParentID = $checkoutPage->ID;
				$orderConfirmationPage->Title = 'order confirmation';
				$orderConfirmationPage->MenuTitle = 'order confirmation';
				$orderConfirmationPage->MetaTitle = 'order confirmation';
				$orderConfirmationPage->Content = '<p>This is the order confirmation page. It is used to confirm orders after they have been placed in the checkout page.</p>';
				$orderConfirmationPage->URLSegment = 'order-confirmation';
				$orderConfirmationPage->ShowInMenus = 0;
				$orderConfirmationPage->writeToStage('Stage');
				$orderConfirmationPage->publish('Stage', 'Live');
				DB::alteration_message('Order Confirmation created', 'created');
			}
			else {
				DB::alteration_message('No need to create an Order Confirmation Page. It already exists.');
			}
		}


		$update = array();
		$siteConfig = DataObject::get_one("SiteConfig");
		if($siteConfig) {
			if(!$siteConfig->ReceiptEmail) {
				$siteConfig->ReceiptEmail = Email::getAdminEmail();
				if(!$siteConfig->ReceiptEmail) {
					user_error("you must set an AdminEmail (Email::setAdminEmail)", E_USER_NOTICE);
				}
				$update[]= "created default entry for ReceiptEmail";
			}
			if(!$siteConfig->ReceiptSubject) {
				$siteConfig->ReceiptSubject = "Shop Sale Information {OrderNumber}";
				$update[]= "created default entry for ReceiptSubject";
			}
			if(!$siteConfig->DispatchEmailSubject) {
				$siteConfig->DispatchEmailSubject = "Your order has been dispatched";
				$update[]= "created default entry for DispatchEmailSubject";
			}
			if(!$siteConfig->NumberOfProductsPerPage) {
				$siteConfig->NumberOfProductsPerPage = 12;
				$update[]= "created default entry for NumberOfProductsPerPage";
			}
			if(count($update)) {
				$siteConfig->write();
				DB::alteration_message($siteConfig->ClassName." created/updated: ".implode(" --- ",$update), 'created');
			}
		}

	}




}

