<?php





/**
 * @Description: We use this payment check class to double check that payment has arrived against
 * the order placed.  We do this independently of Order as a double-check.  It is important
 * that we do this because the main risk in an e-commerce operation is a fake payment.
 * Any e-commerce operator may set up their own policies on what a payment check
 * entails exactly.  It could include a bank reconciliation or even a phone call to the customer.
 * it is important here that we do not add any payment details. Rather, all we have is a tickbox
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: model
 * @inspiration: Silverstripe Ltd, Jeremy
 **/
class OrderStatusLog_PaymentCheck extends OrderStatusLog {

	private static $defaults = array(
		"InternalUseOnly" => true
	);

	private static $db = array(
		'PaymentConfirmed' => "Boolean",
	);

	/**
	 * Standard SS method
	 * @param Member $member
	 * @return Boolean
	 */
	public function canDelete($member = null) {
		return false;
	}

	private static $searchable_fields = array(
		'OrderID' => array(
			'field' => 'NumericField',
			'title' => 'Order Number'
		),
		"PaymentConfirmed" => true
	);

	private static $summary_fields = array(
		"Created" => "Date",
		"Author.Title" => "Checked by",
		"PaymentConfirmedNice" => "Payment Confirmed"
	);

	private static $casting = array(
		"PaymentConfirmedNice" => "Varchar"
	);

	function PaymentConfirmedNice() {return $this->getPaymentConfirmedNice();}
	function getPaymentConfirmedNice() {if($this->PaymentConfirmed) {return _t("OrderStatusLog.YES", "yes");}return _t("OrderStatusLog.No", "no");}

	private static $singular_name = "Payment Confirmation";
		function i18n_singular_name() { return _t("OrderStatusLog.PAYMENTCONFIRMATION", "Payment Confirmation");}

	private static $plural_name = "Payment Confirmations";
		function i18n_plural_name() { return _t("OrderStatusLog.PAYMENTCONFIRMATIONS", "Payment Confirmations");}

	public function getCMSFields() {
		$fields = parent::getCMSFields();
		$fields->removeByName("Title");
		$fields->removeByName("Note");
		$fields->addFieldToTab(
			'Root.Main',
			new CheckboxField("PaymentConfirmed", _t("OrderStatusLog.CONFIRMED", "Payment is confirmed"))
		);
		return $fields;
	}


	/**
	 *
	 * @return String
	 **/
	function CustomerNote(){return $this->getCustomerNote();}
	function getCustomerNote() {
		if($this->Author()) {
			Config::nest();
			Config::inst()->update('SSViewer', 'theme_enabled', true);
			$html = $this->renderWith("Order_CustomerNote_PaymentCheck");
			Config::unnest();
			return $html;
		}
	}


}


