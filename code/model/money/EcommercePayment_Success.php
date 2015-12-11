<?php

class EcommercePayment_Success extends EcommercePayment_Result {

	function isSuccess() {
		return true;
	}

	function isProcessing() {
		return false;
	}
}

