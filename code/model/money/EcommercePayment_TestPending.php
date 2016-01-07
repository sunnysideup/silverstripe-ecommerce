<?php

/**
 * Payment object representing a TEST = PENDING
 *
 * @package ecommerce
 * @subpackage payment
 */
class EcommercePayment_TestPending extends EcommercePayment_Test {

	/**
	 * @param array $data The form request data - see OrderForm
	 * @param OrderForm $form The form object submitted on
	 *
	 * @return EcommercePayment_Result
	 */
	function processPayment($data, $form) {
		$this->Status = 'Pending';
		$this->Message = '<div>PAYMENT TEST: PENDING</div>';
		$this->write();
		return new EcommercePayment_Processing();
	}

	function getPaymentFormFields() {
		return new FieldList(
			new LiteralField("SuccessBlurb", '<div>PENDING PAYMENT TEST</div>')
		);
	}

}
