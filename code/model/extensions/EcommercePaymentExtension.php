<?php

/**
 * @description: This is a Hack class that adds a few features to payment,
 * needed to run e-commerce.
 * Eventually this class will be deleted.
 *
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: payment
 * @sub-package: ecommerce
 * @inspiration: Silverstripe Ltd, Jeremy
 **/

class EcommercePaymentExtension extends Payment {

	/**
	 * example of return value: array('ChequePayment' => 'Cheque Payment Option')
	 * @return Array
	 **/
	public static function get_suppertod_methods() {
		$obj = singleton("EcommercePaymentExtension");
		return $obj->getSupportedMethods();
	}

	/**
	 * example of return value: array('ChequePayment' => 'Cheque Payment Option')
	 *@return Array
	 **/
	function getSupportedMethods() {
		return self::$supported_methods;
	}


}
