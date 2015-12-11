<?php


/**
 * Payment object representing a TEST = FAILURE
 *
 * @package ecommerce
 * @subpackage payment
 */
class EcommercePayment_TestFailure extends EcommercePayment_Test {

	function processPayment($data, $form) {
		$this->Status = 'Failure';
		$this->Message = '<div>PAYMENT TEST: FAILURE</div>';
		$this->write();
		return new EcommercePayment_Failure();
	}

	function getPaymentFormFields() {
		return new FieldList(
			new LiteralField("SuccessBlurb", '<div>FAILURE PAYMENT TEST</div>')
		);
	}

}
