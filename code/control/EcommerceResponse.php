<?php

/**
 * @description: This class is a base class for Ecommerce Responses such as Cart Response
 *
 * @authors: Jeremy, Nicolaas
 *
 * @package: ecommerce
 * @sub-package: control
 *
 **/
abstract class EcommerceResponse extends SS_HTTPResponse {


	/**
	 *
	 * @param String $status the status to return
	 * @param String $message the message to return with the retur
	 * @param Null | Array $data, that should be included
	 */
	public function ReturnCartData($status, $message = "", $data = null) {
		user_error("Make sure to extend the EcommerceResponse class for your own purposes", E_USER_NOTICE);
	}

}
