<?php


/**
 * Payment object representing a TEST = SUCCESS
 *
 * @package ecommerce
 * @subpackage payment
 */
class EcommercePayment_TestSuccess extends EcommercePayment_Test {

	/**
	 * @param array $data The form request data - see OrderForm
	 * @param OrderForm $form The form object submitted on
	 *
	 * @return EcommercePayment_Result
	 */
	function processPayment($data, $form) {
		$this->Status = 'Success';
		$this->Message = '<div>PAYMENT TEST: SUCCESS</div>';
		$this->write();
		return new EcommercePayment_Success();
	}

	function getPaymentFormFields() {
		return new FieldList(
			new LiteralField("SuccessBlurb", '<div>SUCCESSFUL PAYMENT TEST</div>')
		);
	}

	function getPaymentFormRequirements() {
		return null;
	}
}


