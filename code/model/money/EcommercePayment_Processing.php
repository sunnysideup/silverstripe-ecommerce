<?php

class EcommercePayment_Processing extends EcommercePayment_Result {

	function isSuccess() {
		return false;
	}

	function isProcessing() {
		return true;
	}
}
