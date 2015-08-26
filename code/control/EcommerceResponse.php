<?php

/**
 * @description: This class is a base class for Ecommerce Responses such as Cart Response
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: control
 * @inspiration: Silverstripe Ltd, Jeremy
 **/

abstract class EcommerceResponse extends SS_HTTPResponse {


	/**
	 *
	 * @param String $status the status to return
	 * @param String $message the message to return with the retur
	 * @param Null | Array $data, that should be included
	 */
	public function ReturnCartData(Array $messages = array(), Array $additionalData = null, $status = "success") {
		user_error("Make sure to extend the EcommerceResponse::ReturnCartData class for your own purposes.", E_USER_NOTICE);
	}


}
