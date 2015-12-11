<?php



abstract class EcommercePayment_Result {

	protected $value;

	function __construct($value = null) {
		$this->value = $value;
	}

	function getValue() {
		return $this->value;
	}

	abstract function isSuccess();

	abstract function isProcessing();

}
