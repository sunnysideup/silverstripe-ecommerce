<?php

class EcommercePayment_Failure extends EcommercePayment_Result {

	function isSuccess() {
		return false;
	}

	function isProcessing() {
		return false;
	}
}
