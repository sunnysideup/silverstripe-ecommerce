<?php


/**
 * EcommerceDevelopmentAdminDecorator adds extra functionality to the DevelopmentAdmin
 * It creates a developer view (as in www.mysite.com/dev/) specifically for ecommerce.
 *
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: cms
 * @inspiration: Silverstripe Ltd, Jeremy
 **/

class EcommerceDevelopmentAdminDecorator extends Extension{

	/**
	 * handles ecommerce request or provide options to run request in the form of HTML output.
	 * @param SS_HTTPRequest
	 * @return HTML
	 **/

	function ecommerce(SS_HTTPRequest $request) {
		return EcommerceDatabaseAdmin::create();
	}

}


